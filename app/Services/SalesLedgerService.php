<?php

namespace App\Services;

use App\Models\Master\Account\Account;
use App\Models\Master\Account\Stock;
use App\Models\Master\Account\Transaction;
use App\Models\Master\Broker;
use App\Models\Master\Customer;
use App\Models\Master\Transporter;
use App\Models\Master\Vendor;
use App\Models\Procurement\PaymentRequest;
use App\Models\Procurement\PaymentRequestApproval;
use App\Models\Procurement\PaymentRequestData;
use App\Models\Product;
use App\Models\User;
use App\Models\Master\Account\TransactionVoucherType;
use App\Models\Sales\DeliveryChallan;
use App\Models\Sales\DeliveryChallanData;
use App\Models\Sales\ReceivingRequest;
use App\Models\Sales\LogisticsBill;
use App\Models\Sales\ReceivingRequestItem;
use App\Models\Master\ArrivalLocation;
use App\Models\Sales\LoadingProgramItem;
use App\Models\Sales\SalesInvoice;
use App\Models\Sales\SalesInvoiceData;
use App\Models\Sales\SalesReturn;
use Illuminate\Support\Facades\DB;

class SalesLedgerService
{
    /**
     * Handle Delivery Challan Approval: Tickets, Stock, and Ledger Transactions
     */
    public function handleDeliveryChallanApproval(DeliveryChallan $deliveryChallan): void
    {
        // 1. Update Ticket Process Status
        foreach ($deliveryChallan->delivery_challan_data as $data) {
            if ($data->ticket_id) {
                $ticket = \App\Models\Sales\LoadingProgramItem::find($data->ticket_id);
                if ($ticket) {
                    $ticket->update(['process_status' => 'DC Approved']);
                }
            }
        }

        // 2. Stock Out Transaction upon DC Approval
        foreach ($deliveryChallan->delivery_challan_data as $dcData) {
            $itemId = $dcData->item_id ?? $dcData->product_id;
            if ($itemId && $dcData->qty > 0) {
                // 1. Current cost price of product from latest Stock-In (Warehouse Inventory Weighted Average Cost)
                $latestStockIn = Stock::where('product_id', $itemId)
                    ->where('type', 'stock-in')
                    ->whereNotNull('avg_cost_price')
                    ->where('avg_cost_price', '>', 0)
                    ->latest('id')
                    ->first();

                // Tareeqa 1: Sale is valued at the current warehouse stock's Weighted Average Cost
                $avgCostPrice = $latestStockIn ? (float)$latestStockIn->avg_cost_price : 0;

                $existingStock = Stock::where('voucher_no', $deliveryChallan->dc_no)
                    ->where('voucher_type', 'delivery_challan')
                    ->where('product_id', $itemId)
                    ->first();

                if (!$existingStock) {
                    createStockTransaction(
                        $itemId,
                        'delivery_challan',
                        $deliveryChallan->dc_no,
                        $dcData->qty,
                        'stock-out',
                        $dcData->qty * $dcData->rate,
                        $dcData->rate,
                        $deliveryChallan->remarks ?? "DC Stock Out: {$deliveryChallan->dc_no}",
                        ['avg_cost_price' => $avgCostPrice],
                        $avgCostPrice
                    );
                } else {
                    $existingStock->update([
                        'qty' => $dcData->qty,
                        'price' => $dcData->qty * $dcData->rate,
                        'avg_price_per_kg' => $dcData->rate,
                        'avg_cost_price' => $avgCostPrice,
                        'narration' => $deliveryChallan->remarks ?? "DC Stock Out: {$deliveryChallan->dc_no}"
                    ]);
                }
            }
        }

        // 3. Create / Sync Receiving Request upon DC Approval (for Pohanch)
        if (strtolower($deliveryChallan->sauda_type ?? '') == 'pohanch') {
            $receivingRequest = $deliveryChallan->receivingRequest;
            $dcDataFirst = $deliveryChallan->delivery_challan_data->first();

            if (!$receivingRequest) {
                $receivingRequest = ReceivingRequest::create([
                    'delivery_challan_id' => $deliveryChallan->id,
                    'dc_no' => $deliveryChallan->dc_no,
                    'dc_date' => $deliveryChallan->dispatch_date,
                    'truck_number' => $dcDataFirst?->truck_no ?? null,
                    'labour' => $deliveryChallan->labour,
                    'transporter' => $deliveryChallan->transporter,
                    'inhouse_weighbridge' => $deliveryChallan->{'inhouse-weighbridge'} ?? null,
                    'labour_amount' => $deliveryChallan->labour_amount ?? 0,
                    'transporter_amount' => $deliveryChallan->transporter_amount ?? 0,
                    'inhouse_weighbridge_amount' => $deliveryChallan->{'weighbridge-amount'} ?? 0,
                    'company_id' => $deliveryChallan->company_id,
                    'created_by_id' => $deliveryChallan->created_by_id,
                    'am_approval_status' => 'draft',
                ]);
            } else {
                $rrUpdate = [
                    'dc_no' => $deliveryChallan->dc_no,
                    'dc_date' => $deliveryChallan->dispatch_date,
                    'truck_number' => $dcDataFirst?->truck_no ?? null,
                    'labour' => $deliveryChallan->labour,
                    'transporter' => $deliveryChallan->transporter,
                    'inhouse_weighbridge' => $deliveryChallan->{'inhouse-weighbridge'} ?? null,
                    'labour_amount' => $deliveryChallan->labour_amount ?? 0,
                    'transporter_amount' => $deliveryChallan->transporter_amount ?? 0,
                    'inhouse_weighbridge_amount' => $deliveryChallan->{'weighbridge-amount'} ?? 0,
                    'created_by_id' => $deliveryChallan->created_by_id,
                ];
                if ($receivingRequest->am_approval_status !== 'approved') {
                    $rrUpdate['am_approval_status'] = 'draft';
                }
                $receivingRequest->update($rrUpdate);
            }

            // Sync Receiving Request Items only if not approved yet
            if ($receivingRequest->am_approval_status !== 'approved') {
                $receivingRequest->items()->delete();
                foreach ($deliveryChallan->delivery_challan_data as $dcData) {
                    $product = $dcData->product;
                    ReceivingRequestItem::create([
                        'receiving_request_id' => $receivingRequest->id,
                        'delivery_challan_data_id' => $dcData->id,
                        'item_id' => $dcData->item_id,
                        'item_name' => $product?->name ?? 'N/A',
                        'dispatch_weight' => $dcData->qty ?? 0,
                        'receiving_weight' => 0,
                        'difference_weight' => $dcData->qty ?? 0,
                        'seller_portion' => 0,
                        'remaining_amount' => $dcData->qty ?? 0,
                    ]);
                }
            }

            // 4. Pohanch Sauda Ledger Entries
            $salesOrder = $deliveryChallan->delivery_order->first()?->salesOrder;

            DB::transaction(function () use ($deliveryChallan, $salesOrder) {
                $dc_no = $deliveryChallan->dc_no;
                $voucherTypeId = 3;

                $handleTransaction = function($amount, $accountId, $voucherTypeId, $voucherNo, $type, $isOpening, $additionalData) {
                    $tx = Transaction::where('voucher_no', $voucherNo)
                            ->where('purpose', $additionalData['purpose'])
                            ->where('type', $type)
                            ->first();
                    if ($tx) {
                        $tx->update([
                            'amount' => $amount,
                            'account_id' => $accountId,
                            'counter_account_id' => $additionalData['counter_account_id'] ?? null,
                            'payment_against' => $additionalData['payment_against'] ?? null,
                            'against_reference_no' => $additionalData['against_reference_no'] ?? null,
                            'remarks' => $additionalData['remarks'] ?? null,
                        ]);
                    } else {
                        createTransaction($amount, $accountId, $voucherTypeId, $voucherNo, $type, $isOpening, $additionalData);
                    }
                };

                $customerAccountId = $deliveryChallan->customer?->account_id 
                    ?? \App\Models\Master\Customer::find($deliveryChallan->customer_id)?->account_id
                    ?? Account::where('table_name', 'customers')->where('name', $deliveryChallan->customer?->name)->value('id');
                $salesRevenueAccount = Account::where('hierarchy_path', '4-2')->first();
                $inventoryAccountId = $salesRevenueAccount?->id;

                $transporterObj = Transporter::find($deliveryChallan->transporter);
                $transporterAccountId = $transporterObj?->account_id;

                $labourObj = Vendor::find($deliveryChallan->labour);
                $labourAccountId = $labourObj?->account_id;

                $brokerAccountId = $salesOrder?->broker?->account_id;
                $sellerUser = $salesOrder?->parent_user ?? ($salesOrder?->parent_user_id ? \App\Models\User::find($salesOrder->parent_user_id) : null);
                $sellerAccountId = $sellerUser?->account_id;

                $transporterExpenseAccount = Account::where('hierarchy_path', '5-3')->first();
                $labourExpenseAccount = Account::where('hierarchy_path', '5-4')->first();
                $commissionExpenseAccount = Account::where('hierarchy_path', '5-5')->first();

                $totalSaleAmount = 0;
                $totalQty = 0;
                foreach ($deliveryChallan->delivery_challan_data as $data) {
                    $effectiveQty = ($deliveryChallan->is_bardana && ($data->total_bag_weight ?? 0) > 0)
                        ? ($data->billed_qty ?? max(0, $data->qty - $data->total_bag_weight))
                        : $data->qty;
                    $totalSaleAmount += ($effectiveQty * $data->rate);
                    $totalQty += $data->qty;
                }

                if ($totalSaleAmount > 0 && $customerAccountId && $inventoryAccountId) {
                    // Sale Entry - Customer Debit
                    $handleTransaction($totalSaleAmount, $customerAccountId, $voucherTypeId, $dc_no, 'debit', 'no', [
                        'counter_account_id' => $inventoryAccountId,
                        'purpose' => "delivery-challan-sale",
                        'payment_against' => "pohanch-sale",
                        'against_reference_no' => $dc_no,
                        'remarks' => "Sale booked against DC: {$dc_no}. Amount receivable from customer.",
                    ]);

                    // Sale Entry - Sales Revenue Credit
                    $handleTransaction($totalSaleAmount, $inventoryAccountId, $voucherTypeId, $dc_no, 'credit', 'no', [
                        'counter_account_id' => $customerAccountId,
                        'purpose' => "delivery-challan-sale",
                        'payment_against' => "pohanch-sale",
                        'against_reference_no' => $dc_no,
                        'remarks' => "Sales revenue credited for sale against DC: {$dc_no}.",
                    ]);
                }

                // 1.1 Cost of Goods Sold (COGS) & Inventory Asset Entry
                $cogsAccount = Account::where('hierarchy_path', '6-2')->first();
                foreach ($deliveryChallan->delivery_challan_data as $dcData) {
                    $itemId = $dcData->item_id ?? $dcData->product_id;
                    $stock = Stock::where('voucher_no', $dc_no)
                        ->where('voucher_type', 'delivery_challan')
                        ->where('product_id', $itemId)
                        ->first();

                    $costRate = (float)($stock?->avg_cost_price ?? 0);
                    $itemCogsAmount = (float)$dcData->qty * $costRate;

                    $product = \App\Models\Product::find($itemId);
                    $productInventoryAccountId = $product?->account_id ?? Account::where('hierarchy_path', '1-2')->first()?->id;

                    if ($cogsAccount && $productInventoryAccountId) {
                        // Debit COGS (6-2)
                        $handleTransaction(round($itemCogsAmount, 2), $cogsAccount->id, $voucherTypeId, $dc_no, 'debit', 'no', [
                            'counter_account_id' => $productInventoryAccountId,
                            'purpose' => "delivery-challan-cogs-item-{$itemId}",
                            'payment_against' => "cogs-expense",
                            'against_reference_no' => $dc_no,
                            'remarks' => "Cost of Goods Sold for item {$product?->name} on DC: {$dc_no}.",
                        ]);

                        // Credit Inventory Asset
                        $handleTransaction(round($itemCogsAmount, 2), $productInventoryAccountId, $voucherTypeId, $dc_no, 'credit', 'no', [
                            'counter_account_id' => $cogsAccount->id,
                            'purpose' => "delivery-challan-inventory-item-{$itemId}",
                            'payment_against' => "inventory-asset",
                            'against_reference_no' => $dc_no,
                            'remarks' => "Inventory asset reduced for item {$product?->name} on DC: {$dc_no}.",
                        ]);
                    }
                }

                if ($deliveryChallan->transporter_amount > 0 && $transporterExpenseAccount && $transporterAccountId) {
                    // Transporter Expense Debit
                    $handleTransaction($deliveryChallan->transporter_amount, $transporterExpenseAccount->id, $voucherTypeId, $dc_no, 'debit', 'no', [
                        'counter_account_id' => $transporterAccountId,
                        'purpose' => "transporter-expense",
                        'payment_against' => "pohanch-sale-expense",
                        'against_reference_no' => $dc_no,
                        'remarks' => "Transporter expense booked for DC: {$dc_no}.",
                    ]);

                    // Transporter Payable Credit
                    $handleTransaction($deliveryChallan->transporter_amount, $transporterAccountId, $voucherTypeId, $dc_no, 'credit', 'no', [
                        'counter_account_id' => $transporterExpenseAccount->id,
                        'purpose' => "transporter-payable",
                        'payment_against' => "pohanch-sale-payable",
                        'against_reference_no' => $dc_no,
                        'remarks' => "Transporter payable booked for DC: {$dc_no}.",
                    ]);
                }

                if ($deliveryChallan->labour_amount > 0 && $labourExpenseAccount && $labourAccountId) {
                    // Labour Expense Debit
                    $handleTransaction($deliveryChallan->labour_amount, $labourExpenseAccount->id, $voucherTypeId, $dc_no, 'debit', 'no', [
                        'counter_account_id' => $labourAccountId,
                        'purpose' => "labour-expense",
                        'payment_against' => "pohanch-sale-expense",
                        'against_reference_no' => $dc_no,
                        'remarks' => "Labour expense booked for DC: {$dc_no}.",
                    ]);

                    // Labour Payable Credit
                    $handleTransaction($deliveryChallan->labour_amount, $labourAccountId, $voucherTypeId, $dc_no, 'credit', 'no', [
                        'counter_account_id' => $labourExpenseAccount->id,
                        'purpose' => "labour-payable",
                        'payment_against' => "pohanch-sale-payable",
                        'against_reference_no' => $dc_no,
                        'remarks' => "Labour payable booked for DC: {$dc_no}.",
                    ]);
                }

                if ($salesOrder && $salesOrder->commission_per_kg > 0 && $commissionExpenseAccount && $brokerAccountId && $totalQty > 0) {
                    $commissionAmount = $totalQty * $salesOrder->commission_per_kg;

                    // Commission Expense Debit
                    $handleTransaction($commissionAmount, $commissionExpenseAccount->id, $voucherTypeId, $dc_no, 'debit', 'no', [
                        'counter_account_id' => $brokerAccountId,
                        'purpose' => "commission-expense",
                        'payment_against' => "pohanch-sale-expense",
                        'against_reference_no' => $dc_no,
                        'remarks' => "Broker commission expense booked for DC: {$dc_no}.",
                    ]);

                    // Broker Payable Credit
                    $handleTransaction($commissionAmount, $brokerAccountId, $voucherTypeId, $dc_no, 'credit', 'no', [
                        'counter_account_id' => $commissionExpenseAccount->id,
                        'purpose' => "broker-payable",
                        'payment_against' => "pohanch-sale-payable",
                        'against_reference_no' => $dc_no,
                        'remarks' => "Broker payable booked for DC: {$dc_no}.",
                    ]);
                }

                // Seller Commission Entry
                if ($salesOrder && $salesOrder->seller_commission_per_kg > 0 && $commissionExpenseAccount && $sellerAccountId && $totalQty > 0) {
                    $sellerCommissionAmount = $totalQty * $salesOrder->seller_commission_per_kg;

                    // Seller Commission Expense Debit
                    $handleTransaction($sellerCommissionAmount, $commissionExpenseAccount->id, $voucherTypeId, $dc_no, 'debit', 'no', [
                        'counter_account_id' => $sellerAccountId,
                        'purpose' => "seller-commission-expense",
                        'payment_against' => "pohanch-sale-expense",
                        'against_reference_no' => $dc_no,
                        'remarks' => "Seller commission expense booked for DC: {$dc_no}.",
                    ]);

                    // Seller Payable Credit
                    $handleTransaction($sellerCommissionAmount, $sellerAccountId, $voucherTypeId, $dc_no, 'credit', 'no', [
                        'counter_account_id' => $commissionExpenseAccount->id,
                        'purpose' => "seller-payable",
                        'payment_against' => "pohanch-sale-payable",
                        'against_reference_no' => $dc_no,
                        'remarks' => "Seller commission payable booked for DC: {$dc_no}.",
                    ]);
                } else {
                    Transaction::where('voucher_no', $dc_no)->whereIn('purpose', ['seller-commission-expense', 'seller-payable'])->delete();
                }
            });
        } elseif (in_array(strtolower(str_replace(['-', ' ', '_'], '', $deliveryChallan->sauda_type ?? '')), ['xmill'])) {
            // 5. X-Mill Sauda Ledger Entries
            $deliveryOrder = $deliveryChallan->delivery_order->first() ?? $deliveryChallan->delivery_order()->first();
            $salesOrder = $deliveryOrder?->salesOrder;

            if ($salesOrder && in_array(strtolower($salesOrder->transporter_used ?? ''), ['no', '0', 'false', 'yes', '1', 'true'])) {
                DB::transaction(function () use ($deliveryChallan, $salesOrder) {
                    $dc_no = $deliveryChallan->dc_no;
                    $voucherTypeId = 3;

                    $handleTransaction = function($amount, $accountId, $voucherTypeId, $voucherNo, $type, $isOpening, $additionalData) {
                        $tx = Transaction::where('voucher_no', $voucherNo)
                                ->where('purpose', $additionalData['purpose'])
                                ->where('type', $type)
                                ->first();
                        if ($tx) {
                            $tx->update([
                                'amount' => $amount,
                                'account_id' => $accountId,
                                'counter_account_id' => $additionalData['counter_account_id'] ?? null,
                                'payment_against' => $additionalData['payment_against'] ?? null,
                                'against_reference_no' => $additionalData['against_reference_no'] ?? null,
                                'remarks' => $additionalData['remarks'] ?? null,
                            ]);
                        } else {
                            createTransaction($amount, $accountId, $voucherTypeId, $voucherNo, $type, $isOpening, $additionalData);
                        }
                    };

                    $customerAccountId = $deliveryChallan->customer?->account_id ?? Customer::find($deliveryChallan->customer_id)?->account_id;
                    $salesRevenueAccount = Account::where('hierarchy_path', '4-2')->first();
                    $inventoryAccountId = $salesRevenueAccount?->id;

                    $labourObj = Vendor::find($deliveryChallan->labour);
                    $labourAccountId = $labourObj?->account_id;

                    $brokerAccountId = $salesOrder?->broker?->account_id;
                    $sellerUser = $salesOrder?->parent_user ?? ($salesOrder?->parent_user_id ? \App\Models\User::find($salesOrder->parent_user_id) : null);
                    $sellerAccountId = $sellerUser?->account_id;
                    $commissionExpenseAccount = Account::where('hierarchy_path', '5-5')->first();

                    $totalSaleAmount = 0;
                    $totalQty = 0;
                    foreach ($deliveryChallan->delivery_challan_data as $data) {
                        $effectiveQty = ($deliveryChallan->is_bardana && ($data->total_bag_weight ?? 0) > 0)
                            ? ($data->billed_qty ?? max(0, $data->qty - $data->total_bag_weight))
                            : $data->qty;
                        $totalSaleAmount += ($effectiveQty * $data->rate);
                        $totalQty += $data->qty;
                    }

                    // 1. Sale Entry
                    if ($totalSaleAmount > 0 && $customerAccountId && $inventoryAccountId) {
                        // Sale Entry - Customer Debit
                        $handleTransaction($totalSaleAmount, $customerAccountId, $voucherTypeId, $dc_no, 'debit', 'no', [
                            'counter_account_id' => $inventoryAccountId,
                            'purpose' => "delivery-challan-sale",
                            'payment_against' => "x-mill-sale",
                            'against_reference_no' => $dc_no,
                            'remarks' => "X-Mill Sale booked against DC: {$dc_no}.",
                        ]);

                        // Sale Entry - Sales Revenue Credit
                        $handleTransaction($totalSaleAmount, $inventoryAccountId, $voucherTypeId, $dc_no, 'credit', 'no', [
                            'counter_account_id' => $customerAccountId,
                            'purpose' => "delivery-challan-sale",
                            'payment_against' => "x-mill-sale",
                            'against_reference_no' => $dc_no,
                            'remarks' => "X-Mill Sale booked against DC: {$dc_no}.",
                        ]);
                    }

                    // 1.1 Cost of Goods Sold (COGS) & Inventory Asset Entry
                    $cogsAccount = Account::where('hierarchy_path', '6-2')->first();
                    foreach ($deliveryChallan->delivery_challan_data as $dcData) {
                        $itemId = $dcData->item_id ?? $dcData->product_id;
                        $stock = Stock::where('voucher_no', $dc_no)
                            ->where('voucher_type', 'delivery_challan')
                            ->where('product_id', $itemId)
                            ->first();

                        $costRate = (float)($stock?->avg_cost_price ?? 0);
                        $itemCogsAmount = (float)$dcData->qty * $costRate;

                        $product = \App\Models\Product::find($itemId);
                        $productInventoryAccountId = $product?->account_id ?? Account::where('hierarchy_path', '1-2')->first()?->id;

                        if ($cogsAccount && $productInventoryAccountId) {
                            // Debit COGS (6-2)
                            $handleTransaction(round($itemCogsAmount, 2), $cogsAccount->id, $voucherTypeId, $dc_no, 'debit', 'no', [
                                'counter_account_id' => $productInventoryAccountId,
                                'purpose' => "delivery-challan-cogs-item-{$itemId}",
                                'payment_against' => "cogs-expense",
                                'against_reference_no' => $dc_no,
                                'remarks' => "Cost of Goods Sold for item {$product?->name} on DC: {$dc_no}.",
                            ]);

                            // Credit Inventory Asset
                            $handleTransaction(round($itemCogsAmount, 2), $productInventoryAccountId, $voucherTypeId, $dc_no, 'credit', 'no', [
                                'counter_account_id' => $cogsAccount->id,
                                'purpose' => "delivery-challan-inventory-item-{$itemId}",
                                'payment_against' => "inventory-asset",
                                'against_reference_no' => $dc_no,
                                'remarks' => "Inventory asset reduced for item {$product?->name} on DC: {$dc_no}.",
                            ]);
                        }
                    }

                    // 2. Transporter Entry (if Transporter = Yes)
                    $isTransporterYes = in_array(strtolower($salesOrder->transporter_used ?? ''), ['yes', '1', 'true']);
                    $transporterAmount = (float)($deliveryChallan->transporter_amount ?? 0);
                    $transporterObj = Transporter::find($deliveryChallan->transporter) ?? Vendor::find($deliveryChallan->transporter);
                    $transporterAccountId = $transporterObj?->account_id;

                    if ($isTransporterYes && $transporterAmount > 0 && $customerAccountId && $transporterAccountId) {
                        // Customer Debit
                        $handleTransaction($transporterAmount, $customerAccountId, $voucherTypeId, $dc_no, 'debit', 'no', [
                            'counter_account_id' => $transporterAccountId,
                            'purpose' => "x-mill-transporter-receivable",
                            'payment_against' => "x-mill-sale-transporter",
                            'against_reference_no' => $dc_no,
                            'remarks' => "Transporter expense charged to Customer for DC: {$dc_no}.",
                        ]);

                        // Transporter Payable Credit
                        $handleTransaction($transporterAmount, $transporterAccountId, $voucherTypeId, $dc_no, 'credit', 'no', [
                            'counter_account_id' => $customerAccountId,
                            'purpose' => "transporter-payable",
                            'payment_against' => "x-mill-sale-transporter",
                            'against_reference_no' => $dc_no,
                            'remarks' => "Transporter payable booked for DC: {$dc_no}.",
                        ]);
                    }

                    // 2.1 Create / Sync Receiving Request record for X-Mill (so it appears as Logistics Bill)
                    if ($isTransporterYes) {
                        $receivingRequest = $deliveryChallan->receivingRequest;
                        $dcDataFirst = $deliveryChallan->delivery_challan_data->first();
                        if (!$receivingRequest) {
                            ReceivingRequest::create([
                                'delivery_challan_id' => $deliveryChallan->id,
                                'dc_no' => $deliveryChallan->dc_no,
                                'dc_date' => $deliveryChallan->dispatch_date,
                                'truck_number' => $dcDataFirst?->truck_no ?? null,
                                'transporter' => $deliveryChallan->transporter,
                                'transporter_amount' => $deliveryChallan->transporter_amount ?? 0,
                                'company_id' => $deliveryChallan->company_id,
                                'created_by_id' => $deliveryChallan->created_by_id,
                                'am_approval_status' => 'approved',
                                'am_change_made' => 1,
                            ]);
                        } else {
                            $receivingRequest->update([
                                'dc_no' => $deliveryChallan->dc_no,
                                'dc_date' => $deliveryChallan->dispatch_date,
                                'truck_number' => $dcDataFirst?->truck_no ?? null,
                                'transporter' => $deliveryChallan->transporter,
                                'transporter_amount' => $deliveryChallan->transporter_amount ?? 0,
                                'company_id' => $deliveryChallan->company_id,
                                'am_approval_status' => 'approved',
                            ]);
                        }
                    }

                    // 3. Labour Entry only if UnPaid
                    $labourAmount = (float)($deliveryChallan->labour_amount ?? 0);
                    if ($labourAmount <= 0 && (float)($deliveryChallan->labour_rate ?? 0) > 0) {
                        $totalBags = (float)$deliveryChallan->delivery_challan_data->sum('no_of_bags');
                        $labourAmount = $totalBags > 0 ? ($totalBags * (float)$deliveryChallan->labour_rate) : ($totalQty * (float)$deliveryChallan->labour_rate);
                    }

                    $isUnpaid = in_array(strtolower(trim($deliveryChallan->labour_status ?? '')), ['not_paid', 'unpaid', 'not paid', 'not-paid']);

                    if ($isUnpaid && $labourAmount > 0 && $customerAccountId && $labourAccountId) {
                        // Customer Debit
                        $handleTransaction($labourAmount, $customerAccountId, $voucherTypeId, $dc_no, 'debit', 'no', [
                            'counter_account_id' => $labourAccountId,
                            'purpose' => "x-mill-labour-receivable",
                            'payment_against' => "x-mill-sale-labour",
                            'against_reference_no' => $dc_no,
                            'remarks' => "Labour expense charged to Customer for DC: {$dc_no} (Status: UnPaid).",
                        ]);

                        // Labour Payable Credit
                        $handleTransaction($labourAmount, $labourAccountId, $voucherTypeId, $dc_no, 'credit', 'no', [
                            'counter_account_id' => $customerAccountId,
                            'purpose' => "labour-payable",
                            'payment_against' => "x-mill-sale-labour",
                            'against_reference_no' => $dc_no,
                            'remarks' => "Labour payable booked for DC: {$dc_no}.",
                        ]);
                    }

                    // 4. Broker Entry
                    if ($salesOrder && $salesOrder->commission_per_kg > 0 && $commissionExpenseAccount && $brokerAccountId && $totalQty > 0) {
                        $commissionAmount = $totalQty * $salesOrder->commission_per_kg;

                        // Commission Expense Debit
                        $handleTransaction($commissionAmount, $commissionExpenseAccount->id, $voucherTypeId, $dc_no, 'debit', 'no', [
                            'counter_account_id' => $brokerAccountId,
                            'purpose' => "commission-expense",
                            'payment_against' => "x-mill-sale-expense",
                            'against_reference_no' => $dc_no,
                            'remarks' => "Commission expense booked for DC: {$dc_no}.",
                        ]);

                        // Broker Payable Credit
                        $handleTransaction($commissionAmount, $brokerAccountId, $voucherTypeId, $dc_no, 'credit', 'no', [
                            'counter_account_id' => $commissionExpenseAccount->id,
                            'purpose' => "broker-payable",
                            'payment_against' => "x-mill-sale-payable",
                            'against_reference_no' => $dc_no,
                            'remarks' => "Broker payable booked for DC: {$dc_no}.",
                        ]);
                    }

                    // 5. Seller Commission Entry
                    if ($salesOrder && $salesOrder->seller_commission_per_kg > 0 && $commissionExpenseAccount && $sellerAccountId && $totalQty > 0) {
                        $sellerCommissionAmount = $totalQty * $salesOrder->seller_commission_per_kg;

                        // Seller Commission Expense Debit
                        $handleTransaction($sellerCommissionAmount, $commissionExpenseAccount->id, $voucherTypeId, $dc_no, 'debit', 'no', [
                            'counter_account_id' => $sellerAccountId,
                            'purpose' => "seller-commission-expense",
                            'payment_against' => "x-mill-sale-expense",
                            'against_reference_no' => $dc_no,
                            'remarks' => "Seller commission expense booked for DC: {$dc_no}.",
                        ]);

                        // Seller Payable Credit
                        $handleTransaction($sellerCommissionAmount, $sellerAccountId, $voucherTypeId, $dc_no, 'credit', 'no', [
                            'counter_account_id' => $commissionExpenseAccount->id,
                            'purpose' => "seller-payable",
                            'payment_against' => "x-mill-sale-payable",
                            'against_reference_no' => $dc_no,
                            'remarks' => "Seller commission payable booked for DC: {$dc_no}.",
                        ]);
                    } else {
                        Transaction::where('voucher_no', $dc_no)->whereIn('purpose', ['seller-commission-expense', 'seller-payable'])->delete();
                    }
                });
            }
        } else {
            $receivingRequest = $deliveryChallan->receivingRequest;
            if ($receivingRequest) {
                $receivingRequest->items()->delete();
                $receivingRequest->delete();
            }
        }

        // In-House Weighbridge Ledger Entry for Arrival Location
        $this->handleInHouseWeighbridgeLedger($deliveryChallan);

        // Sync Payment Requests for Delivery Challan
        $this->syncDeliveryChallanPaymentRequests($deliveryChallan);
    }

    public function handleInHouseWeighbridgeLedger(DeliveryChallan $deliveryChallan): void
    {
        $dc_no = $deliveryChallan->dc_no;
        $voucherTypeId = 3; // Delivery Challan
        $companyId = $deliveryChallan->company_id ?? 1;

        $handleTransaction = function($amount, $accountId, $voucherTypeId, $voucherNo, $type, $isOpening, $additionalData) {
            $tx = Transaction::where('voucher_no', $voucherNo)
                    ->where('purpose', $additionalData['purpose'])
                    ->where('type', $type)
                    ->first();
            if ($tx) {
                $tx->update([
                    'amount' => $amount,
                    'account_id' => $accountId,
                    'counter_account_id' => $additionalData['counter_account_id'] ?? null,
                    'payment_against' => $additionalData['payment_against'] ?? null,
                    'against_reference_no' => $additionalData['against_reference_no'] ?? null,
                    'remarks' => $additionalData['remarks'] ?? null,
                ]);
            } else {
                createTransaction($amount, $accountId, $voucherTypeId, $voucherNo, $type, $isOpening, $additionalData);
            }
        };

        // Collect unique tickets from delivery_challan_data
        $ticketIds = $deliveryChallan->delivery_challan_data->pluck('ticket_id')->filter()->unique();

        $processedTickets = [];

        if ($ticketIds->isNotEmpty()) {
            foreach ($ticketIds as $ticketId) {
                $ticket = LoadingProgramItem::with(['firstWeighbridge', 'arrivalLocation'])->find($ticketId);
                if (!$ticket) continue;

                // Priority: first_weighbridge_location_id -> arrival_location_id -> DC arrival_id
                $locationId = $ticket->first_weighbridge_location_id ?? $ticket->arrival_location_id ?? $deliveryChallan->arrival_id;
                $weighbridgeAmount = 0;

                if ($ticket->firstWeighbridge && (float)$ticket->firstWeighbridge->weighbridge_amount > 0) {
                    $weighbridgeAmount = (float)$ticket->firstWeighbridge->weighbridge_amount;
                } elseif ((float)($deliveryChallan->{'weighbridge-amount'} ?? 0) > 0) {
                    $weighbridgeAmount = (float)$deliveryChallan->{'weighbridge-amount'};
                }

                $purposeAsset = "inhouse-weighbridge-asset-ticket-{$ticket->id}";
                $purposeRevenue = "inhouse-weighbridge-revenue-ticket-{$ticket->id}";

                if ($locationId && $weighbridgeAmount > 0) {
                    $arrivalLocation = ArrivalLocation::find($locationId);
                    if ($arrivalLocation) {
                        $accounts = $this->getOrCreateArrivalLocationAccounts($arrivalLocation, $companyId);
                        $assetAccount = $accounts['asset'] ?? null;
                        $revenueAccount = $accounts['revenue'] ?? null;

                        if ($assetAccount && $revenueAccount) {
                            $truckNo = $ticket->truck_number ?? 'N/A';

                            // Debit: In-House Weighbridge Asset (1-7-X)
                            $handleTransaction($weighbridgeAmount, $assetAccount->id, $voucherTypeId, $dc_no, 'debit', 'no', [
                                'counter_account_id' => $revenueAccount->id,
                                'purpose' => $purposeAsset,
                                'payment_against' => "inhouse-weighbridge-fee",
                                'against_reference_no' => $dc_no,
                                'remarks' => "In-house weighbridge collection for {$arrivalLocation->name} on DC: {$dc_no} (Truck: {$truckNo}, Ticket: {$ticket->transaction_number}).",
                            ]);

                            // Credit: In-House Weighbridge Revenue (4-4-X)
                            $handleTransaction($weighbridgeAmount, $revenueAccount->id, $voucherTypeId, $dc_no, 'credit', 'no', [
                                'counter_account_id' => $assetAccount->id,
                                'purpose' => $purposeRevenue,
                                'payment_against' => "inhouse-weighbridge-fee",
                                'against_reference_no' => $dc_no,
                                'remarks' => "In-house weighbridge service revenue earned by {$arrivalLocation->name} on DC: {$dc_no} (Truck: {$truckNo}, Ticket: {$ticket->transaction_number}).",
                            ]);

                            $processedTickets[] = $ticket->id;
                        }
                    }
                } else {
                    // Clean up if amount is 0
                    Transaction::where('voucher_no', $dc_no)->whereIn('purpose', [$purposeAsset, $purposeRevenue])->delete();
                }
            }
        }

        // Fallback: If no tickets were processed but DC itself has weighbridge-amount and arrival_id
        if (empty($processedTickets) && (float)($deliveryChallan->{'weighbridge-amount'} ?? 0) > 0) {
            $locationId = $deliveryChallan->arrival_id ?? $deliveryChallan->location_id;
            $arrivalLocation = $locationId ? ArrivalLocation::find($locationId) : null;
            $weighbridgeAmount = (float)$deliveryChallan->{'weighbridge-amount'};

            if ($arrivalLocation && $weighbridgeAmount > 0) {
                $accounts = $this->getOrCreateArrivalLocationAccounts($arrivalLocation, $companyId);
                $assetAccount = $accounts['asset'] ?? null;
                $revenueAccount = $accounts['revenue'] ?? null;

                if ($assetAccount && $revenueAccount) {
                    $purposeAsset = "inhouse-weighbridge-asset";
                    $purposeRevenue = "inhouse-weighbridge-revenue";

                    // Debit: In-House Weighbridge Asset (1-7-X)
                    $handleTransaction($weighbridgeAmount, $assetAccount->id, $voucherTypeId, $dc_no, 'debit', 'no', [
                        'counter_account_id' => $revenueAccount->id,
                        'purpose' => $purposeAsset,
                        'payment_against' => "inhouse-weighbridge-fee",
                        'against_reference_no' => $dc_no,
                        'remarks' => "In-house weighbridge collection for {$arrivalLocation->name} on DC: {$dc_no}.",
                    ]);

                    // Credit: In-House Weighbridge Revenue (4-4-X)
                    $handleTransaction($weighbridgeAmount, $revenueAccount->id, $voucherTypeId, $dc_no, 'credit', 'no', [
                        'counter_account_id' => $assetAccount->id,
                        'purpose' => $purposeRevenue,
                        'payment_against' => "inhouse-weighbridge-fee",
                        'against_reference_no' => $dc_no,
                        'remarks' => "In-house weighbridge service revenue earned by {$arrivalLocation->name} on DC: {$dc_no}.",
                    ]);
                }
            }
        }
    }

    public function getOrCreateArrivalLocationAccounts(ArrivalLocation $arrivalLocation, $companyId = 1): array
    {
        return [
            'asset' => $arrivalLocation->getOrCreateAssetAccount($companyId),
            'revenue' => $arrivalLocation->getOrCreateRevenueAccount($companyId),
        ];
    }

    public function handleReceivingRequestApproval(ReceivingRequest|LogisticsBill $receivingRequest): void
    {
        $dc = $receivingRequest->deliveryChallan;
        $dc_no = $receivingRequest->dc_no;
        $voucherTypeId = 3;

        DB::transaction(function () use ($receivingRequest, $dc, $dc_no, $voucherTypeId) {
            $handleTransaction = function($amount, $accountId, $voucherTypeId, $voucherNo, $type, $isOpening, $additionalData) use ($receivingRequest, $dc) {
                $additionalData['company_id'] = $additionalData['company_id'] ?? $receivingRequest->company_id ?? $dc?->company_id ?? 1;
                $tx = Transaction::where('voucher_no', $voucherNo)
                        ->where('purpose', $additionalData['purpose'])
                        ->where('type', $type)
                        ->first();
                if ($tx) {
                    $tx->update([
                        'amount' => $amount,
                        'account_id' => $accountId,
                        'counter_account_id' => $additionalData['counter_account_id'] ?? null,
                        'payment_against' => $additionalData['payment_against'] ?? null,
                        'against_reference_no' => $additionalData['against_reference_no'] ?? null,
                        'remarks' => $additionalData['remarks'] ?? null,
                    ]);
                } else {
                    createTransaction($amount, $accountId, $voucherTypeId, $voucherNo, $type, $isOpening, $additionalData);
                }
            };

            // Unloading Labour Expense
            $unloadingPaidBy = strtolower($receivingRequest->unloading_paid_by ?? '');
            
            $unloadingLabourAmount = 0;
            foreach ($receivingRequest->items as $item) {
                $bags = floatval($item->deliveryChallanData?->no_of_bags ?? 0);
                $rate = floatval($item->unloading_labour_rate ?? 0);
                $unloadingLabourAmount += ($bags * $rate);
            }
            
            if ($unloadingLabourAmount > 0) {
                $unloadingLabourExpAccount = Account::where('hierarchy_path', '5-6')->first();

                if ($unloadingPaidBy == 'customer') {
                    $customerAccountId = $dc->customer?->account_id;
                    if ($unloadingLabourExpAccount && $customerAccountId) {
                        // Debit Unloading Labour Expense
                        $handleTransaction($unloadingLabourAmount, $unloadingLabourExpAccount->id, $voucherTypeId, $dc_no, 'debit', 'no', [
                            'counter_account_id' => $customerAccountId,
                            'purpose' => "unloading-labour-expense",
                            'payment_against' => "pohanch-sale-expense",
                            'against_reference_no' => $dc_no,
                            'remarks' => "Unloading Labour expense booked for Receiving Request.",
                        ]);

                        // Credit Customer
                        $handleTransaction($unloadingLabourAmount, $customerAccountId, $voucherTypeId, $dc_no, 'credit', 'no', [
                            'counter_account_id' => $unloadingLabourExpAccount->id,
                            'purpose' => "unloading-labour-payable",
                            'payment_against' => "pohanch-sale-payable",
                            'against_reference_no' => $dc_no,
                            'remarks' => "Unloading Labour paid by customer for Receiving Request.",
                        ]);
                    }
                } elseif ($unloadingPaidBy == 'transporter') {
                    $transporterObj = Transporter::find($receivingRequest->transporter);
                    $transporterAccountId = $transporterObj?->account_id;

                    if ($unloadingLabourExpAccount && $transporterAccountId) {
                        // Debit Unloading Labour Expense
                        $handleTransaction($unloadingLabourAmount, $unloadingLabourExpAccount->id, $voucherTypeId, $dc_no, 'debit', 'no', [
                            'counter_account_id' => $transporterAccountId,
                            'purpose' => "unloading-labour-expense",
                            'payment_against' => "pohanch-sale-expense",
                            'against_reference_no' => $dc_no,
                            'remarks' => "Unloading Labour expense booked for Receiving Request.",
                        ]);

                        // Credit Transporter
                        $handleTransaction($unloadingLabourAmount, $transporterAccountId, $voucherTypeId, $dc_no, 'credit', 'no', [
                            'counter_account_id' => $unloadingLabourExpAccount->id,
                            'purpose' => "unloading-labour-payable",
                            'payment_against' => "pohanch-sale-payable",
                            'against_reference_no' => $dc_no,
                            'remarks' => "Unloading Labour paid by transporter for Receiving Request.",
                        ]);
                    }
                }
            }

            // Weighbridges Expense 
            $weighbridgePaidBy = strtolower($receivingRequest->weighbridge_paid_by ?? '');
            
            $totalWeighbridgeAmount = 0;
            foreach ($receivingRequest->weighbridges as $wb) {
                $totalWeighbridgeAmount += floatval($wb->amount ?? 0);
            }
            
            if ($totalWeighbridgeAmount > 0) {
                $weighbridgeExpAccount = Account::where('hierarchy_path', '5-7')->first();

                if ($weighbridgePaidBy == 'customer') {
                    $customerAccountId = $dc->customer?->account_id;
                    if ($weighbridgeExpAccount && $customerAccountId) {
                        // Debit Weighbridges Expense
                        $handleTransaction($totalWeighbridgeAmount, $weighbridgeExpAccount->id, $voucherTypeId, $dc_no, 'debit', 'no', [
                            'counter_account_id' => $customerAccountId,
                            'purpose' => "weighbridge-expense",
                            'payment_against' => "pohanch-sale-expense",
                            'against_reference_no' => $dc_no,
                            'remarks' => "Weighbridges expense booked for Receiving Request.",
                        ]);

                        // Credit Customer
                        $handleTransaction($totalWeighbridgeAmount, $customerAccountId, $voucherTypeId, $dc_no, 'credit', 'no', [
                            'counter_account_id' => $weighbridgeExpAccount->id,
                            'purpose' => "weighbridge-payable",
                            'payment_against' => "pohanch-sale-payable",
                            'against_reference_no' => $dc_no,
                            'remarks' => "Weighbridges paid by customer for Receiving Request.",
                        ]);
                    }
                } elseif ($weighbridgePaidBy == 'transporter') {
                    $transporterObj = Transporter::find($receivingRequest->transporter);
                    $transporterAccountId = $transporterObj?->account_id;

                    if ($weighbridgeExpAccount && $transporterAccountId) {
                        // Debit Weighbridges Expense
                        $handleTransaction($totalWeighbridgeAmount, $weighbridgeExpAccount->id, $voucherTypeId, $dc_no, 'debit', 'no', [
                            'counter_account_id' => $transporterAccountId,
                            'purpose' => "weighbridge-expense",
                            'payment_against' => "pohanch-sale-expense",
                            'against_reference_no' => $dc_no,
                            'remarks' => "Weighbridges expense booked for Receiving Request.",
                        ]);

                        // Credit Transporter
                        $handleTransaction($totalWeighbridgeAmount, $transporterAccountId, $voucherTypeId, $dc_no, 'credit', 'no', [
                            'counter_account_id' => $weighbridgeExpAccount->id,
                            'purpose' => "weighbridge-payable",
                            'payment_against' => "pohanch-sale-payable",
                            'against_reference_no' => $dc_no,
                            'remarks' => "Weighbridges paid by transporter for Receiving Request.",
                        ]);
                    }
                }
            }

            // Transporter Deduction
            $deductionAmount = floatval($receivingRequest->transporter_deduction ?? 0);
            if ($deductionAmount > 0) {
                $transporterObj = Transporter::find($receivingRequest->transporter);
                $transporterAccountId = $transporterObj?->account_id;
                $customerAccountId = $dc->customer?->account_id;

                if ($customerAccountId && $transporterAccountId) {
                    // Debit Transporter (Decreases Transporter Payable)
                    $handleTransaction($deductionAmount, $transporterAccountId, $voucherTypeId, $dc_no, 'debit', 'no', [
                        'counter_account_id' => $customerAccountId,
                        'purpose' => "receiving-request-transporter-deduction",
                        'payment_against' => "pohanch-sale-payable",
                        'against_reference_no' => $dc_no,
                        'remarks' => "Transporter deduction charged on Receiving Request for DC: {$dc_no}",
                    ]);

                    // Credit Customer (Decreases Customer Receivable)
                    $handleTransaction($deductionAmount, $customerAccountId, $voucherTypeId, $dc_no, 'credit', 'no', [
                        'counter_account_id' => $transporterAccountId,
                        'purpose' => "receiving-request-customer-deduction",
                        'payment_against' => "pohanch-sale-receivable",
                        'against_reference_no' => $dc_no,
                        'remarks' => "Transporter deduction adjusted for customer on Receiving Request for DC: {$dc_no}",
                    ]);
                }
            } else {
                Transaction::where('voucher_no', $dc_no)->whereIn('purpose', [
                    'receiving-request-transporter-deduction',
                    'receiving-request-customer-deduction'
                ])->delete();
            }

            // Weight Difference
            $totalSaleAmount = 0;
            $totalQty = 0;
            foreach ($dc->delivery_challan_data as $data) {
                $totalSaleAmount += ($data->qty * $data->rate);
                $totalQty += $data->qty;
            }
            
            $averageRate = $totalQty > 0 ? ($totalSaleAmount / $totalQty) : 0;
            
            $dispatchedWeight = $totalQty;
            $arrivedWeight = $receivingRequest->arrived_weight ?? 0;
            
            // Case 1: Shortage (Arrived < Dispatched)
            if ($arrivedWeight > 0 && $arrivedWeight < $dispatchedWeight) {
                Transaction::where('voucher_no', $dc_no)->whereIn('purpose', [
                    'receiving-request-excess-weight-adjustment',
                    'receiving-request-excess-profit'
                ])->delete();

                Transaction::where('voucher_no', $dc_no)
                    ->where('purpose', 'receiving-request-short-weight-adjustment')
                    ->delete();

                $shortWeight = max(0, $dispatchedWeight - $arrivedWeight);
                $exemptedWeight = min(max(0, floatval($receivingRequest->exempted_weight ?? 0)), $shortWeight);
                $penaltyWeight = max(0, $shortWeight - $exemptedWeight);
                
                $totalShortAmount = $shortWeight * $averageRate;
                $exemptedLossAmount = $exemptedWeight * $averageRate;
                $penaltyAmount = $penaltyWeight * $averageRate;
                
                $customerAccountId = $dc->customer?->account_id;
                
                if ($customerAccountId) {
                    // Pair 1: Short Weight Loss (Exempted) - Loss Debit & Customer Credit
                    if ($exemptedLossAmount > 0) {
                        $lossAccount = Account::where('hierarchy_path', '4-1-4')->first();
                        if ($lossAccount) {
                            // Debit Loss Account
                            $handleTransaction($exemptedLossAmount, $lossAccount->id, $voucherTypeId, $dc_no, 'debit', 'no', [
                                'counter_account_id' => $customerAccountId,
                                'purpose' => "receiving-request-short-loss",
                                'payment_against' => "pohanch-sale-loss",
                                'against_reference_no' => $dc_no,
                                'remarks' => "Short weight loss (Exempted {$exemptedWeight} kg) on Receiving Request for DC: {$dc_no}",
                            ]);

                            // Credit Customer Account (Counter = Loss Account)
                            $handleTransaction($exemptedLossAmount, $customerAccountId, $voucherTypeId, $dc_no, 'credit', 'no', [
                                'counter_account_id' => $lossAccount->id,
                                'purpose' => "receiving-request-short-loss-customer",
                                'payment_against' => "pohanch-sale-receivable",
                                'against_reference_no' => $dc_no,
                                'remarks' => "Short weight loss (Exempted {$exemptedWeight} kg) adjusted for customer on Receiving Request for DC: {$dc_no}",
                            ]);
                        }
                    } else {
                        Transaction::where('voucher_no', $dc_no)->whereIn('purpose', [
                            'receiving-request-short-loss',
                            'receiving-request-short-loss-customer',
                        ])->delete();
                    }
                    
                    // Pair 2: Transporter Penalty - Transporter Debit & Customer Credit
                    if ($penaltyAmount > 0) {
                        $transporterObj = Transporter::find($receivingRequest->transporter);
                        $transporterAccountId = $transporterObj?->account_id;
                        
                        if ($transporterAccountId) {
                            // Debit Transporter Account
                            $handleTransaction($penaltyAmount, $transporterAccountId, $voucherTypeId, $dc_no, 'debit', 'no', [
                                'counter_account_id' => $customerAccountId,
                                'purpose' => "receiving-request-short-penalty",
                                'payment_against' => "pohanch-sale-payable",
                                'against_reference_no' => $dc_no,
                                'remarks' => "Short weight penalty ({$penaltyWeight} kg) charged to Transporter on Receiving Request for DC: {$dc_no}",
                            ]);

                            // Credit Customer Account (Counter = Transporter Account)
                            $handleTransaction($penaltyAmount, $customerAccountId, $voucherTypeId, $dc_no, 'credit', 'no', [
                                'counter_account_id' => $transporterAccountId,
                                'purpose' => "receiving-request-short-penalty-customer",
                                'payment_against' => "pohanch-sale-receivable",
                                'against_reference_no' => $dc_no,
                                'remarks' => "Short weight penalty ({$penaltyWeight} kg) charged to Transporter adjusted for customer on Receiving Request for DC: {$dc_no}",
                            ]);
                        }
                    } else {
                        Transaction::where('voucher_no', $dc_no)->whereIn('purpose', [
                            'receiving-request-short-penalty',
                            'receiving-request-short-penalty-customer',
                        ])->delete();
                    }
                }
            } elseif ($arrivedWeight > $dispatchedWeight && $dispatchedWeight > 0) {
                // Delete short entries if any
                Transaction::where('voucher_no', $dc_no)->whereIn('purpose', [
                    'receiving-request-short-loss',
                    'receiving-request-short-loss-customer',
                    'receiving-request-short-penalty',
                    'receiving-request-short-penalty-customer',
                    'receiving-request-short-weight-adjustment',
                ])->delete();

                // Case 2: Excess Weight (Arrived > Dispatched) -> Profit
                $grossExcessWeight = $arrivedWeight - $dispatchedWeight;
                $exemptedWeight = min(max(0, floatval($receivingRequest->exempted_weight ?? 0)), $grossExcessWeight);
                $netGainWeight = max(0, $grossExcessWeight - $exemptedWeight);
                $totalExcessAmount = $netGainWeight * $averageRate;
                
                $customerAccountId = $dc->customer?->account_id;
                
                if ($totalExcessAmount > 0 && $customerAccountId) {
                    $profitAccount = Account::where('hierarchy_path', '4-1-4')->first();
                    
                    if ($profitAccount) {
                        $remarksGain = $exemptedWeight > 0
                            ? "Excess weight adjustment (+{$netGainWeight} kg net gain, {$exemptedWeight} kg exempted) on Receiving Request for DC: {$dc_no}"
                            : "Excess weight adjustment (+{$netGainWeight} kg) on Receiving Request for DC: {$dc_no}";

                        $remarksProfit = $exemptedWeight > 0
                            ? "Excess weight gain/profit (+{$netGainWeight} kg net gain, {$exemptedWeight} kg exempted) on Receiving Request for DC: {$dc_no}"
                            : "Excess weight gain/profit (+{$netGainWeight} kg) on Receiving Request for DC: {$dc_no}";

                        // 1. Debit Customer (Customer got extra goods, so receivable increases)
                        $handleTransaction($totalExcessAmount, $customerAccountId, $voucherTypeId, $dc_no, 'debit', 'no', [
                            'counter_account_id' => $profitAccount->id,
                            'purpose' => "receiving-request-excess-weight-adjustment",
                            'payment_against' => "pohanch-sale-receivable",
                            'against_reference_no' => $dc_no,
                            'remarks' => $remarksGain,
                        ]);

                        // 2. Credit Gain/Profit Account (hierarchy 4-1-4)
                        $handleTransaction($totalExcessAmount, $profitAccount->id, $voucherTypeId, $dc_no, 'credit', 'no', [
                            'counter_account_id' => $customerAccountId,
                            'purpose' => "receiving-request-excess-profit",
                            'payment_against' => "pohanch-sale-profit",
                            'against_reference_no' => $dc_no,
                            'remarks' => $remarksProfit,
                        ]);
                    }
                } else {
                    Transaction::where('voucher_no', $dc_no)->whereIn('purpose', [
                        'receiving-request-excess-weight-adjustment',
                        'receiving-request-excess-profit'
                    ])->delete();
                }
            } else {
                // Arrived == Dispatched or no weight diff -> delete both short and excess
                Transaction::where('voucher_no', $dc_no)->whereIn('purpose', [
                    'receiving-request-short-loss',
                    'receiving-request-short-loss-customer',
                    'receiving-request-short-penalty',
                    'receiving-request-short-penalty-customer',
                    'receiving-request-short-weight-adjustment',
                    'receiving-request-excess-weight-adjustment',
                    'receiving-request-excess-profit',
                ])->delete();
            }

            // Transporter Other Amoun
            $otherAmount = floatval($receivingRequest->transporter_other_amount ?? 0);
            if ($otherAmount > 0) {
                $transporterObj = Transporter::find($receivingRequest->transporter);
                $transporterAccountId = $transporterObj?->account_id;
                $transporterExpAccount = Account::where('hierarchy_path', '5-3')->first();

                if ($transporterAccountId && $transporterExpAccount) {
                    // Debit Transporter Expense
                    $handleTransaction($otherAmount, $transporterExpAccount->id, $voucherTypeId, $dc_no, 'debit', 'no', [
                        'counter_account_id' => $transporterAccountId,
                        'purpose' => "logistics-bill-transporter-other-expense",
                        'payment_against' => "pohanch-sale-expense",
                        'against_reference_no' => $dc_no,
                        'remarks' => "Transporter other expense booked for Logistics Bill / RR: {$dc_no}.",
                    ]);

                    // Credit Transporter Payable
                    $handleTransaction($otherAmount, $transporterAccountId, $voucherTypeId, $dc_no, 'credit', 'no', [
                        'counter_account_id' => $transporterExpAccount->id,
                        'purpose' => "logistics-bill-transporter-other-payable",
                        'payment_against' => "pohanch-sale-payable",
                        'against_reference_no' => $dc_no,
                        'remarks' => "Transporter other payable booked for Logistics Bill / RR: {$dc_no}.",
                    ]);
                }
            } else {
                Transaction::where('voucher_no', $dc_no)->whereIn('purpose', [
                    'logistics-bill-transporter-other-expense',
                    'logistics-bill-transporter-other-payable'
                ])->delete();
            }

            // Demurrage & Detention Expense
            $demurrageAmount = floatval($receivingRequest->demurrage_detention_amount ?? 0);
            if ($demurrageAmount > 0) {
                $transporterObj = Transporter::find($receivingRequest->transporter);
                $transporterAccountId = $transporterObj?->account_id;
                $demurrageExpAccount = Account::where('hierarchy_path', '5-8')->first();

                if ($transporterAccountId && $demurrageExpAccount) {
                    // Debit Demurrage & Detention Expense
                    $handleTransaction($demurrageAmount, $demurrageExpAccount->id, $voucherTypeId, $dc_no, 'debit', 'no', [
                        'counter_account_id' => $transporterAccountId,
                        'purpose' => "demurrage-detention-expense",
                        'payment_against' => "pohanch-sale-expense",
                        'against_reference_no' => $dc_no,
                        'remarks' => "Demurrage & Detention expense booked for Logistics Bill / RR: {$dc_no}.",
                    ]);

                    // Credit Transporter Payable
                    $handleTransaction($demurrageAmount, $transporterAccountId, $voucherTypeId, $dc_no, 'credit', 'no', [
                        'counter_account_id' => $demurrageExpAccount->id,
                        'purpose' => "demurrage-detention-payable",
                        'payment_against' => "pohanch-sale-payable",
                        'against_reference_no' => $dc_no,
                        'remarks' => "Demurrage & Detention payable booked to transporter for Logistics Bill / RR: {$dc_no}.",
                    ]);
                }
            } else {
                Transaction::where('voucher_no', $dc_no)->whereIn('purpose', [
                    'demurrage-detention-expense',
                    'demurrage-detention-payable'
                ])->delete();
            }

            // Sales Return Transporter Expense 
            $srTransporterAmount = floatval($receivingRequest->sales_return_transporter_amount ?? 0);
            if ($srTransporterAmount > 0) {
                $transporterObj = Transporter::find($receivingRequest->transporter);
                $transporterAccountId = $transporterObj?->account_id;
                $transporterExpAccount = Account::where('hierarchy_path', '5-3')->first();

                if ($transporterAccountId && $transporterExpAccount) {
                    $srNo = $receivingRequest->salesReturn?->sr_no ?? "SR";

                    // Debit Transporter Expense
                    $handleTransaction($srTransporterAmount, $transporterExpAccount->id, $voucherTypeId, $dc_no, 'debit', 'no', [
                        'counter_account_id' => $transporterAccountId,
                        'purpose' => "sales-return-transporter-expense",
                        'payment_against' => "pohanch-sale-expense",
                        'against_reference_no' => $dc_no,
                        'remarks' => "Sales Return Transporter expense booked for Logistics Bill / RR: {$dc_no} ({$srNo}).",
                    ]);

                    // Credit Transporter Payable
                    $handleTransaction($srTransporterAmount, $transporterAccountId, $voucherTypeId, $dc_no, 'credit', 'no', [
                        'counter_account_id' => $transporterExpAccount->id,
                        'purpose' => "sales-return-transporter-payable",
                        'payment_against' => "pohanch-sale-payable",
                        'against_reference_no' => $dc_no,
                        'remarks' => "Sales Return Transporter payable booked for Logistics Bill / RR: {$dc_no} ({$srNo}).",
                    ]);
                }
            } else {
                Transaction::where('voucher_no', $dc_no)->whereIn('purpose', [
                    'sales-return-transporter-expense',
                    'sales-return-transporter-payable'
                ])->delete();
            }
        });

        // Sync Payment Requests for Receiving Request / Logistics Bill
        $this->syncReceivingRequestPaymentRequests($receivingRequest);
    }

    public function handleSalesReturnApproval(SalesReturn $salesReturn): void
    {
        $sr_no = $salesReturn->sr_no;
        $voucherTypeId = 10;

        // 1. Stock In Transaction & WAC Recalculation for each returned item
        $returnedItemCosts = [];

        foreach ($salesReturn->sale_return_data as $returnData) {
            $itemId = $returnData->item_id ?? $returnData->item?->id ?? $returnData->sale_invoice_data?->item_id;
            if ($itemId && $returnData->quantity > 0) {
                // A. Find original Delivery Challan's cost price (Tareeqa A)
                $originalCostRate = 0;
                $dcNo = null;

                $siData = SalesInvoiceData::find($returnData->sale_invoice_data_id);
                if ($siData) {
                    if ($siData->dc_data_id) {
                        $dcData = DeliveryChallanData::with('deliveryChallan')->find($siData->dc_data_id);
                        $dcNo = $dcData?->deliveryChallan?->dc_no;
                    }
                } elseif ($dcData = DeliveryChallanData::with('deliveryChallan')->find($returnData->sale_invoice_data_id)) {
                    $dcNo = $dcData?->deliveryChallan?->dc_no;
                } elseif ($rrItem = ReceivingRequestItem::with('receiving_request.deliveryChallan')->find($returnData->sale_invoice_data_id)) {
                    $dcNo = $rrItem->receiving_request?->deliveryChallan?->dc_no;
                }

                if ($dcNo) {
                    $dcStock = Stock::where('voucher_no', $dcNo)
                        ->where('voucher_type', 'delivery_challan')
                        ->where('product_id', $itemId)
                        ->first();
                    if ($dcStock && $dcStock->avg_cost_price > 0) {
                        $originalCostRate = (float)$dcStock->avg_cost_price;
                    }
                }

                // Fallback: If original DC cost not found (e.g. historical data), use latest warehouse stock-in avg_cost_price
                if ($originalCostRate <= 0) {
                    $latestStockIn = Stock::where('product_id', $itemId)
                        ->where('type', 'stock-in')
                        ->where('voucher_no', '!=', $sr_no)
                        ->whereNotNull('avg_cost_price')
                        ->where('avg_cost_price', '>', 0)
                        ->latest('id')
                        ->first();
                    $originalCostRate = $latestStockIn ? (float)$latestStockIn->avg_cost_price : 0;
                }

                $returnQty = (float)$returnData->quantity;
                $returnedItemCosts[] = [
                    'item_id' => $itemId,
                    'quantity' => $returnQty,
                    'cost_rate' => $originalCostRate,
                    'cost_amount' => $returnQty * $originalCostRate,
                ];

                // B. Recalculate Warehouse Weighted Average Cost (WAC) including this returned stock
                // B. Recalculate Warehouse Weighted Average Cost (WAC) including this returned stock
                // Fetch the most recent stock-in to get the latest valid WAC
                $latestStockInForWac = Stock::where('product_id', $itemId)
                    ->where('type', 'stock-in')
                    ->where('voucher_no', '!=', $sr_no)
                    ->whereNotNull('avg_cost_price')
                    ->where('avg_cost_price', '>', 0)
                    ->latest('id')
                    ->first();
                
                $currentWac = $latestStockInForWac ? (float)$latestStockInForWac->avg_cost_price : $originalCostRate;

                // Calculate current on-hand quantity
                $totalStockInQty = Stock::where('product_id', $itemId)
                    ->where('type', 'stock-in')
                    ->where('voucher_no', '!=', $sr_no)
                    ->sum('qty');
                    
                $totalStockOutQty = Stock::where('product_id', $itemId)
                    ->where('type', 'stock-out')
                    ->sum('qty');

                $currentOnHandQty = max(0, $totalStockInQty - $totalStockOutQty);
                $returnCostValue = $returnQty * $originalCostRate;

                if ($currentOnHandQty > 0) {
                    $currentTotalValue = $currentOnHandQty * $currentWac;
                    $combinedQty = $currentOnHandQty + $returnQty;
                    $combinedValue = $currentTotalValue + $returnCostValue;
                    
                    $newWac = $combinedQty > 0 ? ($combinedValue / $combinedQty) : $originalCostRate;
                } else {
                    $newWac = $originalCostRate;
                }

                // C. Save / Update Stock Record
                $rate = (float)($returnData->rate ?? 0);
                $netAmount = (float)($returnData->net_amount ?? 0);
                if ($netAmount <= 0) {
                    $netAmount = (float)($returnData->amount ?? 0);
                }
                if ($netAmount <= 0) {
                    $netAmount = (float)($returnQty * $rate);
                }

                $existingStock = Stock::where('voucher_no', $sr_no)
                    ->where('voucher_type', 'sale_return')
                    ->where('product_id', $itemId)
                    ->first();

                if (!$existingStock) {
                    createStockTransaction(
                        $itemId,
                        'sale_return',
                        $sr_no,
                        $returnQty,
                        'stock-in',
                        $netAmount,
                        $rate,
                        $salesReturn->remarks ?? "Sales Return Stock-In: {$sr_no}",
                        [
                            'avg_cost_price' => $newWac,
                            'company_location_id' => $salesReturn->company_location_id,
                            'arrival_id' => $salesReturn->arrival_location_id,
                            'subarrival_id' => $salesReturn->storage_location_id,
                        ],
                        $newWac
                    );
                } else {
                    $existingStock->update([
                        'qty' => $returnQty,
                        'price' => $netAmount,
                        'avg_price_per_kg' => $rate,
                        'avg_cost_price' => $newWac,
                        'narration' => $salesReturn->remarks ?? "Sales Return Stock-In: {$sr_no}",
                        'company_location_id' => $salesReturn->company_location_id,
                        'arrival_id' => $salesReturn->arrival_location_id,
                        'subarrival_id' => $salesReturn->storage_location_id,
                    ]);
                }
            }
        }

        // 2. Balanced Ledger Entries in DB Transaction
        DB::transaction(function () use ($salesReturn, $sr_no, $voucherTypeId, $returnedItemCosts) {
            $handleTransaction = function($amount, $accountId, $voucherTypeId, $voucherNo, $type, $isOpening, $additionalData) {
                $tx = Transaction::where('voucher_no', $voucherNo)
                        ->where('purpose', $additionalData['purpose'])
                        ->where('type', $type)
                        ->first();
                if ($tx) {
                    $tx->update([
                        'amount' => $amount,
                        'account_id' => $accountId,
                        'counter_account_id' => $additionalData['counter_account_id'] ?? null,
                        'payment_against' => $additionalData['payment_against'] ?? null,
                        'against_reference_no' => $additionalData['against_reference_no'] ?? null,
                        'remarks' => $additionalData['remarks'] ?? null,
                        'voucher_date' => $additionalData['voucher_date'] ?? $tx->voucher_date,
                    ]);
                } else {
                    createTransaction($amount, $accountId, $voucherTypeId, $voucherNo, $type, $isOpening, $additionalData);
                }
            };

            // 2.1 Revenue, Discount & Tax Reversals
            $totalGrossReturn = 0;
            $totalDiscountReturn = 0;
            $totalGstReturn = 0;
            $totalNetReturn = 0;

            foreach ($salesReturn->sale_return_data as $data) {
                $gross = (float)($data->gross_amount > 0 ? $data->gross_amount : ((float)$data->quantity * (float)$data->rate));
                
                $disc = (float)($data->discount_amount ?? 0);
                if ($disc <= 0 && (float)($data->discount_percent ?? 0) > 0) {
                    $disc = round($gross * ((float)$data->discount_percent / 100), 2);
                }

                $amt = (float)($data->amount > 0 ? $data->amount : ($gross - $disc));

                $gst = (float)($data->gst_amount ?? 0);
                if ($gst <= 0 && (float)($data->gst_percentage ?? 0) > 0) {
                    $gst = round($amt * ((float)$data->gst_percentage / 100), 2);
                }

                $net = (float)($data->net_amount > 0 ? $data->net_amount : ($amt + $gst));

                $totalGrossReturn += $gross;
                $totalDiscountReturn += $disc;
                $totalGstReturn += $gst;
                $totalNetReturn += $net;
            }

            $totalGrossReturn = round($totalGrossReturn, 2);
            $totalDiscountReturn = round($totalDiscountReturn, 2);
            $totalGstReturn = round($totalGstReturn, 2);
            $totalNetReturn = round($totalNetReturn, 2);

            if ($totalNetReturn <= 0) {
                $totalNetReturn = round($totalGrossReturn - $totalDiscountReturn + $totalGstReturn, 2);
            }

            $customerAccountId = $salesReturn->customer?->account_id ?? Customer::find($salesReturn->customer_id)?->account_id;
            $salesReturnAccount = Account::where('hierarchy_path', '4-3')->first();
            $discountAccount = Account::where('hierarchy_path', '6-1')->first();
            $taxAccount = Account::where('hierarchy_path', '2-7')->first();

            $voucherDate = $salesReturn->date ?? now()->format('Y-m-d');
            $companyId = $salesReturn->company_id ?? auth()->user()?->current_company_id ?? 1;
            $createdBy = $salesReturn->created_by ?? auth()->user()?->id ?? 1;

            if ($totalGrossReturn > 0 && $customerAccountId && $salesReturnAccount) {
                // Entry 1: Sales Return Account Debit (DR) - Reverses Gross Sale Revenue
                $handleTransaction($totalGrossReturn, $salesReturnAccount->id, $voucherTypeId, $sr_no, 'debit', 'no', [
                    'company_id' => $companyId,
                    'voucher_date' => $voucherDate,
                    'created_by' => $createdBy,
                    'counter_account_id' => $customerAccountId,
                    'purpose' => "sales-return",
                    'payment_against' => "sale-return",
                    'against_reference_no' => $sr_no,
                    'remarks' => "Sales Return gross revenue reversal booked against SR: {$sr_no}.",
                ]);

                // Entry 2: Customer Account Credit (CR) - Reverses Net Customer Receivable
                $handleTransaction($totalNetReturn, $customerAccountId, $voucherTypeId, $sr_no, 'credit', 'no', [
                    'company_id' => $companyId,
                    'voucher_date' => $voucherDate,
                    'created_by' => $createdBy,
                    'counter_account_id' => $salesReturnAccount->id,
                    'purpose' => "sales-return",
                    'payment_against' => "sale-return",
                    'against_reference_no' => $sr_no,
                    'remarks' => "Sales Return credited to Customer against SR: {$sr_no}.",
                ]);
            }

            // Entry 3: Discount Account Credit (CR) - Reverses Discount Expense allowed on returned goods
            if ($totalDiscountReturn > 0 && $discountAccount && $customerAccountId) {
                $handleTransaction($totalDiscountReturn, $discountAccount->id, $voucherTypeId, $sr_no, 'credit', 'no', [
                    'company_id' => $companyId,
                    'voucher_date' => $voucherDate,
                    'created_by' => $createdBy,
                    'counter_account_id' => $customerAccountId,
                    'purpose' => "sales-return-discount",
                    'payment_against' => "sale-return",
                    'against_reference_no' => $sr_no,
                    'remarks' => "Sales Return discount reversed against SR: {$sr_no}.",
                ]);
            } else {
                Transaction::where('voucher_no', $sr_no)
                    ->where('purpose', 'sales-return-discount')
                    ->delete();
            }

            // Entry 4: Tax Account Debit (DR) - Reverses Output Tax / GST liability on returned goods
            if ($totalGstReturn > 0 && $taxAccount && $customerAccountId) {
                $handleTransaction($totalGstReturn, $taxAccount->id, $voucherTypeId, $sr_no, 'debit', 'no', [
                    'company_id' => $companyId,
                    'voucher_date' => $voucherDate,
                    'created_by' => $createdBy,
                    'counter_account_id' => $customerAccountId,
                    'purpose' => "sales-return-tax",
                    'payment_against' => "sale-return",
                    'against_reference_no' => $sr_no,
                    'remarks' => "Sales Return Tax (GST) reversed against SR: {$sr_no}.",
                ]);
            } else {
                Transaction::where('voucher_no', $sr_no)
                    ->where('purpose', 'sales-return-tax')
                    ->delete();
            }

            // 2.2 Inventory & COGS Reversal: Debit Inventory Asset (1-2), Credit COGS (6-2)
            $cogsAccount = Account::where('hierarchy_path', '6-2')->first();

            foreach ($returnedItemCosts as $costInfo) {
                $itemId = $costInfo['item_id'];
                $itemCostAmount = $costInfo['cost_amount'];

                if ($itemCostAmount > 0 && $cogsAccount) {
                    $product = Product::find($itemId);
                    $productInventoryAccountId = $product?->account_id ?? Account::where('hierarchy_path', '1-2')->first()?->id;

                    if ($productInventoryAccountId) {
                        // Entry 3: Debit Inventory Asset (1-2) - Restores inventory value
                        $handleTransaction(round($itemCostAmount, 2), $productInventoryAccountId, $voucherTypeId, $sr_no, 'debit', 'no', [
                            'counter_account_id' => $cogsAccount->id,
                            'purpose' => "sales-return-inventory-item-{$itemId}",
                            'payment_against' => "inventory-asset",
                            'against_reference_no' => $sr_no,
                            'remarks' => "Inventory asset restored for returned item {$product?->name} on SR: {$sr_no}.",
                        ]);

                        // Entry 4: Credit COGS (6-2) - Reverses cost of goods sold
                        $handleTransaction(round($itemCostAmount, 2), $cogsAccount->id, $voucherTypeId, $sr_no, 'credit', 'no', [
                            'counter_account_id' => $productInventoryAccountId,
                            'purpose' => "sales-return-cogs-item-{$itemId}",
                            'payment_against' => "cogs-expense",
                            'against_reference_no' => $sr_no,
                            'remarks' => "Cost of Goods Sold reversed for returned item {$product?->name} on SR: {$sr_no}.",
                        ]);
                    }
                }
            }

            // Weight Gain / Loss Entry 
            $weightGainLossAccount = Account::where('hierarchy_path', '4-1-4')->first();
            $customerAccountId = $salesReturn->customer?->account_id ?? Customer::find($salesReturn->customer_id)?->account_id;

            if ($weightGainLossAccount && $customerAccountId) {
                foreach ($salesReturn->sale_return_data as $returnData) {
                    $returnQty    = (float)$returnData->quantity;
                    $returnRate   = (float)($returnData->rate ?? 0);

                    // Find original sold qty from the linked DC data via sale_invoice_data
                    $originalSoldQty = 0;
                    $siData = SalesInvoiceData::find($returnData->sale_invoice_data_id);
                    if ($siData) {
                        $originalSoldQty = (float)($siData->qty ?? $siData->quantity ?? 0);
                    } elseif ($dcData = DeliveryChallanData::find($returnData->sale_invoice_data_id)) {
                        $originalSoldQty = (float)$dcData->qty;
                    } elseif ($rrItem = ReceivingRequestItem::find($returnData->sale_invoice_data_id)) {
                        $originalSoldQty = (float)($rrItem->dispatch_weight ?? $rrItem->receiving_weight ?? 0);
                    }

                    // No comparison possible if original qty unknown
                    if ($originalSoldQty <= 0 || $returnRate <= 0) {
                        continue;
                    }

                    $diffQty    = $returnQty - $originalSoldQty;   // positive = gain, negative = loss
                    $diffAmount = round(abs($diffQty) * $returnRate, 2);

                    if ($diffAmount <= 0) {
                        // Exactly equal — clean up any stale gain/loss entries
                        Transaction::where('voucher_no', $sr_no)
                            ->whereIn('purpose', [
                                "sales-return-weight-gain-item-{$returnData->id}",
                                "sales-return-weight-loss-item-{$returnData->id}",
                            ])->delete();
                        continue;
                    }

                    if ($diffQty > 0) {
                        // ---- WEIGHT GAIN ----
                        // Customer Debit (receivable increases for extra qty)
                        $handleTransaction($diffAmount, $customerAccountId, $voucherTypeId, $sr_no, 'debit', 'no', [
                            'company_id'           => $companyId,
                            'voucher_date'         => $voucherDate,
                            'created_by'           => $createdBy,
                            'counter_account_id'   => $weightGainLossAccount->id,
                            'purpose'              => "sales-return-weight-gain-item-{$returnData->id}",
                            'payment_against'      => "sale-return-weight-gain",
                            'against_reference_no' => $sr_no,
                            'stock'                => abs($diffQty),
                            'remarks'              => "Weight Gain on SR: {$sr_no}. Returned " . $returnQty . " vs Sold " . $originalSoldQty . ". Diff: " . $diffQty . " kg.",
                        ]);

                        // Weight Gain / Loss Account Credit
                        $handleTransaction($diffAmount, $weightGainLossAccount->id, $voucherTypeId, $sr_no, 'credit', 'no', [
                            'company_id'           => $companyId,
                            'voucher_date'         => $voucherDate,
                            'created_by'           => $createdBy,
                            'counter_account_id'   => $customerAccountId,
                            'purpose'              => "sales-return-weight-gain-gl-item-{$returnData->id}",
                            'payment_against'      => "sale-return-weight-gain",
                            'against_reference_no' => $sr_no,
                            'stock'                => abs($diffQty),
                            'remarks'              => "Weight Gain on SR: {$sr_no}. Extra: {$diffQty} kg @ {$returnRate}.",
                        ]);

                        // Remove any stale loss entries for this line
                        Transaction::where('voucher_no', $sr_no)
                            ->whereIn('purpose', [
                                "sales-return-weight-loss-item-{$returnData->id}",
                                "sales-return-weight-loss-gl-item-{$returnData->id}",
                            ])->delete();
                    } else {
                        // ---- WEIGHT LOSS ----
                        // Weight Gain / Loss Account Debit
                        $handleTransaction($diffAmount, $weightGainLossAccount->id, $voucherTypeId, $sr_no, 'debit', 'no', [
                            'company_id'           => $companyId,
                            'voucher_date'         => $voucherDate,
                            'created_by'           => $createdBy,
                            'counter_account_id'   => $customerAccountId,
                            'purpose'              => "sales-return-weight-loss-item-{$returnData->id}",
                            'payment_against'      => "sale-return-weight-loss",
                            'against_reference_no' => $sr_no,
                            'stock'                => abs($diffQty),
                            'remarks'              => "Weight Loss on SR: {$sr_no}. Returned " . $returnQty . " vs Sold " . $originalSoldQty . ". Diff: " . $diffQty . " kg.",
                        ]);

                        // Customer Credit (less returned than sold → reduce receivable)
                        $handleTransaction($diffAmount, $customerAccountId, $voucherTypeId, $sr_no, 'credit', 'no', [
                            'company_id'           => $companyId,
                            'voucher_date'         => $voucherDate,
                            'created_by'           => $createdBy,
                            'counter_account_id'   => $weightGainLossAccount->id,
                            'purpose'              => "sales-return-weight-loss-gl-item-{$returnData->id}",
                            'payment_against'      => "sale-return-weight-loss",
                            'against_reference_no' => $sr_no,
                            'stock'                => abs($diffQty),
                            'remarks'              => "Weight Loss credited from customer on SR: {$sr_no}. Short: " . abs($diffQty) . " kg @ {$returnRate}.",
                        ]);

                        // Remove any stale gain entries for this line
                        Transaction::where('voucher_no', $sr_no)
                            ->whereIn('purpose', [
                                "sales-return-weight-gain-item-{$returnData->id}",
                                "sales-return-weight-gain-gl-item-{$returnData->id}",
                            ])->delete();
                    }
                }
            }
        });
    }

    /**
     * Handle Sales Invoice Approval: Ledger Adjustment Entries for Discount & GST (Option A)
     */
    public function handleSalesInvoiceApproval(SalesInvoice $salesInvoice): void
    {
        $salesInvoice->loadMissing('sales_invoice_data', 'customer');

        // 1. Calculate Total Discount and Total GST
        $totalDiscount = 0;
        $totalGst = 0;

        foreach ($salesInvoice->sales_invoice_data as $data) {
            $gross       = (float)($data->gross_amount > 0 ? $data->gross_amount : ((float)$data->qty * (float)$data->rate));
            $amtAfterDisc = (float)($data->amount ?? 0);   // = gross - discount
            $netAmt      = (float)($data->net_amount ?? 0); // = amount + gst

            // Discount
            $disc = (float)($data->discount_amount ?? 0);
            if ($disc <= 0 && $gross > $amtAfterDisc) {
                // Derive from stored amounts (most reliable)
                $disc = $gross - $amtAfterDisc;
            }
            if ($disc <= 0 && (float)($data->discount_percent ?? 0) > 0) {
                $disc = $gross * ((float)$data->discount_percent / 100);
            }
            $totalDiscount += max(0, $disc);

            // GST 
            $gst = (float)($data->gst_amount ?? 0);
            if ($gst <= 0 && $netAmt > $amtAfterDisc) {
                // Derive from stored net_amount - amount (most reliable)
                $gst = $netAmt - $amtAfterDisc;
            }
            if ($gst <= 0 && (float)($data->gst_percent ?? 0) > 0) {
                $taxable = $gross - $disc;
                $gst = $taxable * ((float)$data->gst_percent / 100);
            }
            $totalGst += max(0, $gst);
        }

        $si_no = $salesInvoice->si_no;

        // 2. If neither discount nor GST exists, delete any existing SI adjustment entries and exit (no ledger hit)
        if ($totalDiscount <= 0 && $totalGst <= 0) {
            Transaction::where('voucher_no', $si_no)
                ->where('purpose', 'like', 'sales-invoice-%')
                ->delete();
            return;
        }

        // 3. Resolve Accounts
        $customerAccountId = $salesInvoice->customer?->account_id ?? Customer::find($salesInvoice->customer_id)?->account_id;
        $discountAccount = Account::where('hierarchy_path', '6-1')->first();
        $taxAccount = Account::where('hierarchy_path', '2-7')->first();

        if (!$customerAccountId) {
            \Log::warning("SalesInvoice {$si_no}: Customer account not found. Cannot post discount/tax ledger entries.");
            return;
        }

        $voucherType = TransactionVoucherType::firstOrCreate(
            ['name' => 'Sales Invoice'],
            ['code' => 'SI', 'status' => 'active']
        );
        $voucherTypeId = $voucherType->id;
        $voucherDate = $salesInvoice->invoice_date ?? now()->format('Y-m-d');
        $companyId = $salesInvoice->company_id ?? auth()->user()?->current_company_id ?? 1;
        $createdBy = $salesInvoice->created_by_id ?? auth()->user()?->id ?? 1;

        DB::transaction(function () use (
            $salesInvoice,
            $si_no,
            $totalDiscount,
            $totalGst,
            $customerAccountId,
            $discountAccount,
            $taxAccount,
            $voucherTypeId,
            $voucherDate,
            $companyId,
            $createdBy
        ) {
            $handleTransaction = function($amount, $accountId, $voucherTypeId, $voucherNo, $type, $isOpening, $additionalData) {
                $tx = Transaction::where('voucher_no', $voucherNo)
                        ->where('purpose', $additionalData['purpose'])
                        ->where('type', $type)
                        ->first();
                if ($tx) {
                    $tx->update([
                        'amount' => $amount,
                        'account_id' => $accountId,
                        'counter_account_id' => $additionalData['counter_account_id'] ?? null,
                        'payment_against' => $additionalData['payment_against'] ?? null,
                        'against_reference_no' => $additionalData['against_reference_no'] ?? null,
                        'remarks' => $additionalData['remarks'] ?? null,
                        'voucher_date' => $additionalData['voucher_date'] ?? $tx->voucher_date,
                    ]);
                } else {
                    createTransaction($amount, $accountId, $voucherTypeId, $voucherNo, $type, $isOpening, $additionalData);
                }
            };

            // A. Discount Double Entry (if Discount > 0)
            if ($totalDiscount > 0 && $discountAccount) {
                // Entry 1: Debit Discount Account (6-1)
                $handleTransaction($totalDiscount, $discountAccount->id, $voucherTypeId, $si_no, 'debit', 'no', [
                    'company_id' => $companyId,
                    'voucher_date' => $voucherDate,
                    'created_by' => $createdBy,
                    'counter_account_id' => $customerAccountId,
                    'purpose' => 'sales-invoice-discount',
                    'payment_against' => 'sale-invoice',
                    'against_reference_no' => $si_no,
                    'remarks' => "Sales Invoice Discount booked against SI: {$si_no}.",
                ]);

                // Entry 2: Credit Customer Account
                $handleTransaction($totalDiscount, $customerAccountId, $voucherTypeId, $si_no, 'credit', 'no', [
                    'company_id' => $companyId,
                    'voucher_date' => $voucherDate,
                    'created_by' => $createdBy,
                    'counter_account_id' => $discountAccount->id,
                    'purpose' => 'sales-invoice-discount',
                    'payment_against' => 'sale-invoice',
                    'against_reference_no' => $si_no,
                    'remarks' => "Sales Invoice Discount credited to Customer against SI: {$si_no}.",
                ]);
            } else {
                Transaction::where('voucher_no', $si_no)
                    ->where('purpose', 'sales-invoice-discount')
                    ->delete();
            }

            // B. Tax (GST) Double Entry (if GST > 0)
            if ($totalGst > 0 && $taxAccount) {
                // Entry 3: Debit Customer Account
                $handleTransaction($totalGst, $customerAccountId, $voucherTypeId, $si_no, 'debit', 'no', [
                    'company_id' => $companyId,
                    'voucher_date' => $voucherDate,
                    'created_by' => $createdBy,
                    'counter_account_id' => $taxAccount->id,
                    'purpose' => 'sales-invoice-tax',
                    'payment_against' => 'sale-invoice',
                    'against_reference_no' => $si_no,
                    'remarks' => "Sales Invoice Tax (GST) charged to Customer against SI: {$si_no}.",
                ]);

                // Entry 4: Credit Tax Account (2-7)
                $handleTransaction($totalGst, $taxAccount->id, $voucherTypeId, $si_no, 'credit', 'no', [
                    'company_id' => $companyId,
                    'voucher_date' => $voucherDate,
                    'created_by' => $createdBy,
                    'counter_account_id' => $customerAccountId,
                    'purpose' => 'sales-invoice-tax',
                    'payment_against' => 'sale-invoice',
                    'against_reference_no' => $si_no,
                    'remarks' => "Sales Invoice Tax (GST) booked against SI: {$si_no}.",
                ]);
            } else {
                Transaction::where('voucher_no', $si_no)
                    ->where('purpose', 'sales-invoice-tax')
                    ->delete();
            }
        });
    }

    public function syncDeliveryChallanPaymentRequests(DeliveryChallan $deliveryChallan): void
    {
        $deliveryChallan->loadMissing(['delivery_challan_data', 'customer', 'delivery_order.salesOrder.broker', 'receivingRequest']);

        $dc_no = $deliveryChallan->dc_no;
        $saudaType = strtolower(str_replace(['-', ' ', '_'], '', $deliveryChallan->sauda_type ?? ''));
        $isXmill = in_array($saudaType, ['xmill']);
        $salesOrder = $deliveryChallan->delivery_order->first()?->salesOrder ?? $deliveryChallan->delivery_order()->first()?->salesOrder;

        $firstItem = $deliveryChallan->delivery_challan_data->first();
        $truckNo = $firstItem?->truck_no ?? null;
        $biltyNo = $firstItem?->bilty_no ?? null;
        $dispatchDate = $deliveryChallan->dispatch_date ?? now()->format('Y-m-d');
        $totalQty = (float)$deliveryChallan->delivery_challan_data->sum('qty');
        $totalBags = (float)$deliveryChallan->delivery_challan_data->sum('no_of_bags');

        // Helper to create or update PaymentRequestData and PaymentRequest
        $upsertPaymentRequest = function (
            string $requestType,
            ?int $accountId,
            float $amount,
            string $notes,
            array $extraData = []
        ) use ($deliveryChallan, $truckNo, $biltyNo, $dispatchDate, $totalQty, $totalBags) {
            if (!$accountId || $amount <= 0) {
                // If amount is 0 or no account, delete any existing unpaid PR for this type
                $existingPr = PaymentRequest::where('delivery_challan_id', $deliveryChallan->id)
                    ->where('request_type', $requestType)
                    ->whereDoesntHave('paymentVoucherData')
                    ->first();
                if ($existingPr) {
                    $prData = $existingPr->paymentRequestData;
                    $existingPr->delete();
                    if ($prData && $prData->paymentRequests()->count() === 0) {
                        $prData->delete();
                    }
                }
                return;
            }

            // Find existing PR for this DC and request_type
            $paymentRequest = PaymentRequest::where('delivery_challan_id', $deliveryChallan->id)
                ->where('request_type', $requestType)
                ->first();

            // Do not alter if already paid via Payment Voucher
            if ($paymentRequest && $paymentRequest->paymentVoucherData) {
                return;
            }

            $paymentRequestData = $paymentRequest?->paymentRequestData;

            $dataPayload = array_merge([
                'delivery_challan_id' => $deliveryChallan->id,
                'account_id' => $accountId,
                'truck_no' => $truckNo,
                'loading_date' => $dispatchDate,
                'bilty_no' => $biltyNo,
                'no_of_bags' => $totalBags,
                'loading_weight' => $totalQty,
                'total_amount' => $amount,
                'remaining_amount' => $amount,
                'paid_amount' => 0,
                'module_type' => 'delivery_challan',
                'notes' => $notes,
            ], $extraData);

            if (!$paymentRequestData) {
                $paymentRequestData = PaymentRequestData::create($dataPayload);
            } else {
                $paymentRequestData->update($dataPayload);
            }

            $isDcApprovedType = in_array($requestType, [
                'freight_labour_payment',
                'broker_commission_payment',
                'seller_commission_payment',
            ]);

            $status = $isDcApprovedType ? 'approved' : 'pending';
            $approverId = auth()->user()->id ?? 1;

            if (!$paymentRequest) {
                $paymentRequest = PaymentRequest::create([
                    'delivery_challan_id' => $deliveryChallan->id,
                    'payment_request_data_id' => $paymentRequestData->id,
                    'account_id' => $accountId,
                    'request_no' => $this->generatePaymentRequestNumber(),
                    'request_type' => $requestType,
                    'module_type' => 'delivery_challan',
                    'amount' => $amount,
                    'status' => $status,
                    'am_approval_status' => $status,
                    'approved_by' => $isDcApprovedType ? $approverId : null,
                    'approved_at' => $isDcApprovedType ? now() : null,
                    'description' => $notes,
                ]);
            } else {
                $updateData = [
                    'account_id' => $accountId,
                    'amount' => $amount,
                    'description' => $notes,
                ];
                if ($isDcApprovedType && $paymentRequest->status !== 'approved') {
                    $updateData['status'] = 'approved';
                    $updateData['am_approval_status'] = 'approved';
                    $updateData['approved_by'] = $approverId;
                    $updateData['approved_at'] = now();
                }
                $paymentRequest->update($updateData);
            }

            if ($isDcApprovedType) {
                PaymentRequestApproval::updateOrCreate(
                    [
                        'payment_request_id' => $paymentRequest->id,
                    ],
                    [
                        'payment_request_data_id' => $paymentRequestData->id,
                        'status' => 'approved',
                        'approver_id' => $approverId,
                        'amount' => $amount,
                        'request_type' => $requestType,
                        'remarks' => 'Auto-approved via Delivery Challan',
                    ]
                );
            }
        };

        // 1. Transporter Payment Request
        $isTransporterUsed = false;
        if ($isXmill) {
            $isTransporterUsed = in_array(strtolower($salesOrder->transporter_used ?? ''), ['yes', '1', 'true']);
        } else {
            $isTransporterUsed = true;
        }

        $existingRr = $deliveryChallan->receivingRequest;
        if ($existingRr && in_array(strtolower($existingRr->am_approval_status ?? ''), ['approved', 'completed'])) {
            $this->syncReceivingRequestPaymentRequests($existingRr);
        } else {
            $transporterAmount = (float)($deliveryChallan->transporter_amount ?? 0);
            $transporterObj = Transporter::find($deliveryChallan->transporter) ?? Vendor::find($deliveryChallan->transporter);
            $transporterAccountId = $transporterObj?->account_id;

            if ($isTransporterUsed && $transporterAmount > 0 && $transporterAccountId) {
                $notes = "Freight payment for DC: {$dc_no} (Truck: {$truckNo}, Bilty: {$biltyNo})";
                $upsertPaymentRequest('freight_payment', $transporterAccountId, $transporterAmount, $notes);
            } else {
                $upsertPaymentRequest('freight_payment', null, 0, '');
            }
        }

        // 2. Loading Labour Vendor Payment Request
        $labourAmount = (float)($deliveryChallan->labour_amount ?? 0);
        if ($labourAmount <= 0 && (float)($deliveryChallan->labour_rate ?? 0) > 0) {
            $labourAmount = $totalBags > 0 ? ($totalBags * (float)$deliveryChallan->labour_rate) : ($totalQty * (float)$deliveryChallan->labour_rate);
        }

        $shouldPayLabour = true;
        if ($isXmill) {
            $isUnpaid = in_array(strtolower(trim($deliveryChallan->labour_status ?? '')), ['not_paid', 'unpaid', 'not paid', 'not-paid']);
            $shouldPayLabour = $isUnpaid;
        }

        $labourObj = Vendor::find($deliveryChallan->labour);
        $labourAccountId = $labourObj?->account_id;

        if ($shouldPayLabour && $labourAmount > 0 && $labourAccountId) {
            $notes = "Loading labour payment for DC: {$dc_no} ({$labourObj->name})";
            $upsertPaymentRequest('freight_labour_payment', $labourAccountId, $labourAmount, $notes, [
                'labour_vendor_id' => $deliveryChallan->labour,
            ]);
        } else {
            $upsertPaymentRequest('freight_labour_payment', null, 0, '');
        }

        // 3. Broker Commission Payment Request
        if ($salesOrder && (float)$salesOrder->commission_per_kg > 0 && $totalQty > 0) {
            $brokerCommission = $totalQty * (float)$salesOrder->commission_per_kg;
            $brokerAccountId = $salesOrder->broker?->account_id ?? Broker::find($salesOrder->broker_id)?->account_id;
            if ($brokerCommission > 0 && $brokerAccountId) {
                $notes = "Broker commission for DC: {$dc_no} (" . ($salesOrder->broker?->name ?? 'Broker') . ")";
                $upsertPaymentRequest('broker_commission_payment', $brokerAccountId, $brokerCommission, $notes, [
                    'broker_id' => $salesOrder->broker_id,
                ]);
            } else {
                $upsertPaymentRequest('broker_commission_payment', null, 0, '');
            }
        } else {
            $upsertPaymentRequest('broker_commission_payment', null, 0, '');
        }

        // 4. Seller Commission Payment Request
        if ($salesOrder && (float)$salesOrder->seller_commission_per_kg > 0 && $totalQty > 0) {
            $sellerCommission = $totalQty * (float)$salesOrder->seller_commission_per_kg;
            $sellerUser = $salesOrder->parent_user ?? ($salesOrder->parent_user_id ? User::find($salesOrder->parent_user_id) : null);
            $sellerAccountId = $sellerUser?->account_id;
            if ($sellerCommission > 0 && $sellerAccountId) {
                $notes = "Seller commission for DC: {$dc_no} (" . ($sellerUser?->name ?? 'Seller') . ")";
                $upsertPaymentRequest('seller_commission_payment', $sellerAccountId, $sellerCommission, $notes);
            } else {
                $upsertPaymentRequest('seller_commission_payment', null, 0, '');
            }
        } else {
            $upsertPaymentRequest('seller_commission_payment', null, 0, '');
        }
    }

    public function syncReceivingRequestPaymentRequests(ReceivingRequest|LogisticsBill $receivingRequest): void
    {
        $dc = $receivingRequest->deliveryChallan ?? DeliveryChallan::find($receivingRequest->delivery_challan_id);
        if (!$dc) {
            return;
        }

        $receivingRequest->loadMissing(['items.deliveryChallanData', 'weighbridges', 'deliveryChallan.delivery_challan_data']);

        $baseFreight = (float)($dc->transporter_amount ?? 0);
        $deduction = (float)($receivingRequest->transporter_deduction ?? 0);

        // Shortage penalty calculation (matching handleReceivingRequestApproval)
        $totalSaleAmount = 0;
        $totalDispatchedQty = 0;
        foreach ($dc->delivery_challan_data as $data) {
            $totalSaleAmount += ($data->qty * $data->rate);
            $totalDispatchedQty += $data->qty;
        }
        $averageRate = $totalDispatchedQty > 0 ? ($totalSaleAmount / $totalDispatchedQty) : 0;
        $arrivedWeight = (float)($receivingRequest->arrived_weight ?? 0);

        $penaltyAmount = 0;
        if ($arrivedWeight > 0 && $arrivedWeight < $totalDispatchedQty) {
            $shortWeight = max(0, $totalDispatchedQty - $arrivedWeight);
            $exemptedWeight = min(max(0, (float)($receivingRequest->exempted_weight ?? 0)), $shortWeight);
            $penaltyWeight = max(0, $shortWeight - $exemptedWeight);
            $penaltyAmount = $penaltyWeight * $averageRate;
        }

        // Unloading Labour (only if paid by transporter)
        $unloadingPaidBy = strtolower($receivingRequest->unloading_paid_by ?? '');
        $unloadingLabourAmount = 0;
        if ($unloadingPaidBy === 'transporter') {
            foreach ($receivingRequest->items as $item) {
                $bags = (float)($item->deliveryChallanData?->no_of_bags ?? 0);
                $rate = (float)($item->unloading_labour_rate ?? 0);
                $unloadingLabourAmount += ($bags * $rate);
            }
        }

        // Weighbridges (only if paid by transporter)
        $weighbridgePaidBy = strtolower($receivingRequest->weighbridge_paid_by ?? '');
        $weighbridgeAmount = 0;
        if ($weighbridgePaidBy === 'transporter') {
            foreach ($receivingRequest->weighbridges as $wb) {
                $weighbridgeAmount += (float)($wb->amount ?? 0);
            }
        }

        $otherAmount = (float)($receivingRequest->transporter_other_amount ?? 0);
        $demurrageAmount = (float)($receivingRequest->demurrage_detention_amount ?? 0);
        $srTransporterAmount = (float)($receivingRequest->sales_return_transporter_amount ?? 0);

        $netTransporter = round(
            $baseFreight
            - $deduction
            - $penaltyAmount
            + $unloadingLabourAmount
            + $weighbridgeAmount
            + $otherAmount
            + $demurrageAmount
            + $srTransporterAmount,
            2
        );

        $breakdown = "Freight: Rs. " . number_format($baseFreight, 2);
        if ($deduction > 0) $breakdown .= " | Ded: -Rs. " . number_format($deduction, 2);
        if ($penaltyAmount > 0) $breakdown .= " | Shortage: -Rs. " . number_format($penaltyAmount, 2);
        if ($unloadingLabourAmount > 0) $breakdown .= " | Unloading: +Rs. " . number_format($unloadingLabourAmount, 2);
        if ($weighbridgeAmount > 0) $breakdown .= " | Weighbridge: +Rs. " . number_format($weighbridgeAmount, 2);
        if ($demurrageAmount > 0) $breakdown .= " | Demurrage: +Rs. " . number_format($demurrageAmount, 2);
        if ($otherAmount > 0) $breakdown .= " | Other: +Rs. " . number_format($otherAmount, 2);
        if ($srTransporterAmount > 0) $breakdown .= " | SR Freight: +Rs. " . number_format($srTransporterAmount, 2);
        $breakdown .= " | Net: Rs. " . number_format($netTransporter, 2);

        $transporterPr = PaymentRequest::where('delivery_challan_id', $dc->id)
            ->where('request_type', 'freight_payment')
            ->first();

        // Do not edit if already paid via payment voucher
        if ($transporterPr && $transporterPr->paymentVoucherData) {
            return;
        }

        $finalAmount = max(0, $netTransporter);

        if ($transporterPr) {
            $transporterPr->update([
                'amount' => $finalAmount,
                'description' => $breakdown,
            ]);

            $prData = $transporterPr->paymentRequestData;
            if ($prData) {
                $prData->update([
                    'total_amount' => $finalAmount,
                    'remaining_amount' => $finalAmount,
                    'notes' => $breakdown,
                    'loading_weight' => ($arrivedWeight > 0 ? $arrivedWeight : $totalDispatchedQty),
                    'exempted_weight' => $receivingRequest->exempted_weight ?? 0,
                ]);
            }
        } else {
            // If not found but transporter is used, create it
            $transporterObj = Transporter::find($receivingRequest->transporter ?? $dc->transporter)
                ?? Vendor::find($receivingRequest->transporter ?? $dc->transporter);
            $transporterAccountId = $transporterObj?->account_id;

            if ($transporterAccountId && $finalAmount > 0) {
                $firstItem = $dc->delivery_challan_data->first();
                $truckNo = $receivingRequest->truck_number ?? $firstItem?->truck_no;
                $biltyNo = $firstItem?->bilty_no;

                $prData = PaymentRequestData::create([
                    'delivery_challan_id' => $dc->id,
                    'account_id' => $transporterAccountId,
                    'truck_no' => $truckNo,
                    'loading_date' => $dc->dispatch_date ?? now()->format('Y-m-d'),
                    'bilty_no' => $biltyNo,
                    'no_of_bags' => $dc->delivery_challan_data->sum('no_of_bags'),
                    'loading_weight' => ($arrivedWeight > 0 ? $arrivedWeight : $totalDispatchedQty),
                    'total_amount' => $finalAmount,
                    'remaining_amount' => $finalAmount,
                    'paid_amount' => 0,
                    'module_type' => 'delivery_challan',
                    'notes' => $breakdown,
                ]);

                PaymentRequest::create([
                    'delivery_challan_id' => $dc->id,
                    'payment_request_data_id' => $prData->id,
                    'account_id' => $transporterAccountId,
                    'request_no' => $this->generatePaymentRequestNumber(),
                    'request_type' => 'freight_payment',
                    'module_type' => 'delivery_challan',
                    'amount' => $finalAmount,
                    'status' => 'pending',
                    'am_approval_status' => 'pending',
                    'description' => $breakdown,
                ]);
            }
        }
    }

    public function autoApproveDeliveryChallanPaymentRequests(int $deliveryChallanId): void
    {
        $paymentRequests = PaymentRequest::where('delivery_challan_id', $deliveryChallanId)
            ->where('request_type', 'freight_payment')
            ->whereDoesntHave('paymentVoucherData')
            ->get();

        $approverId = auth()->user()->id ?? 1;

        foreach ($paymentRequests as $pr) {
            $pr->update([
                'status' => 'approved',
                'am_approval_status' => 'approved',
                'approved_by' => $approverId,
                'approved_at' => now(),
            ]);

            PaymentRequestApproval::updateOrCreate(
                [
                    'payment_request_id' => $pr->id,
                ],
                [
                    'payment_request_data_id' => $pr->payment_request_data_id,
                    'status' => 'approved',
                    'approver_id' => $approverId,
                    'amount' => $pr->amount,
                    'request_type' => $pr->request_type,
                    'remarks' => 'Auto-approved via Logistics Bill',
                ]
            );
        }
    }

    private function generatePaymentRequestNumber(): string
    {
        $prefix = 'PR-';
        $yearMonth = date('Ym');
        $latest = PaymentRequest::where('request_no', 'like', $prefix . $yearMonth . '%')
            ->orderBy('id', 'desc')
            ->first();

        if ($latest && preg_match('/PR-\d{6}(\d+)/', $latest->request_no, $matches)) {
            $number = intval($matches[1]) + 1;
        } else {
            $number = 1;
        }

        return $prefix . $yearMonth . str_pad($number, 4, '0', STR_PAD_LEFT);
    }
}
