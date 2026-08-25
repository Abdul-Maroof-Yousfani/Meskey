<?php

namespace App\Services;

use App\Models\Master\Account\Account;
use App\Models\Master\Account\Stock;
use App\Models\Master\Account\Transaction;
use App\Models\Master\Customer;
use App\Models\Master\Transporter;
use App\Models\Master\Vendor;
use App\Models\Sales\DeliveryChallan;
use App\Models\Sales\ReceivingRequest;
use App\Models\Sales\ReceivingRequestItem;
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
                        $deliveryChallan->remarks ?? "DC Stock Out: {$deliveryChallan->dc_no}"
                    );
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
                $receivingRequest->update([
                    'dc_no' => $deliveryChallan->dc_no,
                    'dc_date' => $deliveryChallan->dispatch_date,
                    'truck_number' => $dcDataFirst?->truck_no ?? null,
                    'labour' => $deliveryChallan->labour,
                    'transporter' => $deliveryChallan->transporter,
                    'inhouse_weighbridge' => $deliveryChallan->{'inhouse-weighbridge'} ?? null,
                    'labour_amount' => $deliveryChallan->labour_amount ?? 0,
                    'transporter_amount' => $deliveryChallan->transporter_amount ?? 0,
                    'inhouse_weighbridge_amount' => $deliveryChallan->{'weighbridge-amount'} ?? 0,
                    'am_approval_status' => 'draft',
                    'created_by_id' => $deliveryChallan->created_by_id,
                ]);
            }

            // Sync Receiving Request Items
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

                $customerAccountId = $deliveryChallan->customer?->account_id;
                $salesRevenueAccount = Account::where('hierarchy_path', '4-2')->first();
                $inventoryAccountId = $salesRevenueAccount?->id;

                $transporterObj = Transporter::find($deliveryChallan->transporter);
                $transporterAccountId = $transporterObj?->account_id;

                $labourObj = Vendor::find($deliveryChallan->labour);
                $labourAccountId = $labourObj?->account_id;

                $brokerAccountId = $salesOrder?->broker?->account_id;

                $transporterExpenseAccount = Account::where('hierarchy_path', '5-3')->first();
                $labourExpenseAccount = Account::where('hierarchy_path', '5-4')->first();
                $commissionExpenseAccount = Account::where('hierarchy_path', '5-5')->first();

                $totalSaleAmount = 0;
                $totalQty = 0;
                foreach ($deliveryChallan->delivery_challan_data as $data) {
                    $totalSaleAmount += ($data->qty * $data->rate);
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
                    $commissionExpenseAccount = Account::where('hierarchy_path', '5-5')->first();

                    $totalSaleAmount = 0;
                    $totalQty = 0;
                    foreach ($deliveryChallan->delivery_challan_data as $data) {
                        $totalSaleAmount += ($data->qty * $data->rate);
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

                    // 2. Transporter Entry (if Transporter = Yes)
                    $isTransporterYes = in_array(strtolower($salesOrder->transporter_used ?? ''), ['yes', '1', 'true']);
                    $transporterAmount = (float)($deliveryChallan->transporter_amount ?? 0);
                    $transporterObj = Vendor::find($deliveryChallan->transporter);
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
                });
            }
        } else {
            $receivingRequest = $deliveryChallan->receivingRequest;
            if ($receivingRequest) {
                $receivingRequest->items()->delete();
                $receivingRequest->delete();
            }
        }
    }

    /**
     * Handle Receiving Request / Logistics Bill Approval: Ledger Transactions
     */
    public function handleReceivingRequestApproval(ReceivingRequest $receivingRequest): void
    {
        $dc = $receivingRequest->deliveryChallan;
        $dc_no = $receivingRequest->dc_no;
        $voucherTypeId = 3;

        DB::transaction(function () use ($receivingRequest, $dc, $dc_no, $voucherTypeId) {
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

            // ==========================================
            // 1. Unloading Labour Expense Entry
            // ==========================================
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

            // ==========================================
            // 2. Weighbridges Expense Entry
            // ==========================================
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

            // ==========================================
            // 3. Transporter Deduction Entry
            // ==========================================
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
            }

            // ==========================================
            // 4. Weight Difference Entries
            // ==========================================
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
                $shortWeight = $dispatchedWeight - $arrivedWeight;
                $exemptedWeight = $receivingRequest->exempted_weight ?? 0;
                $penaltyWeight = max(0, $shortWeight - $exemptedWeight);
                
                $totalShortAmount = $shortWeight * $averageRate;
                $exemptedLossAmount = $exemptedWeight * $averageRate;
                $penaltyAmount = $penaltyWeight * $averageRate;
                
                $customerAccountId = $dc->customer?->account_id;
                
                if ($customerAccountId) {
                    // Debit Short Weight Loss (Exempted)
                    if ($exemptedLossAmount > 0) {
                        $lossAccount = Account::where('hierarchy_path', '4-1-4')->first();
                        if ($lossAccount) {
                            $handleTransaction($exemptedLossAmount, $lossAccount->id, $voucherTypeId, $dc_no, 'debit', 'no', [
                                'counter_account_id' => $customerAccountId,
                                'purpose' => "receiving-request-short-loss",
                                'remarks' => "Short weight loss (Exempted {$exemptedWeight} kg) on Receiving Request for DC: {$dc_no}",
                            ]);
                        }
                    }
                    
                    // Debit Transporter (Penalty)
                    if ($penaltyAmount > 0) {
                        $transporterObj = Transporter::find($receivingRequest->transporter);
                        $transporterAccountId = $transporterObj?->account_id;
                        
                        if ($transporterAccountId) {
                            $handleTransaction($penaltyAmount, $transporterAccountId, $voucherTypeId, $dc_no, 'debit', 'no', [
                                'counter_account_id' => $customerAccountId,
                                'purpose' => "receiving-request-short-penalty",
                                'remarks' => "Short weight penalty ({$penaltyWeight} kg) charged to Transporter on Receiving Request for DC: {$dc_no}",
                            ]);
                        }
                    }

                    // Create a new Credit entry for the customer to adjust for the short amount
                    $handleTransaction($totalShortAmount, $customerAccountId, $voucherTypeId, $dc_no, 'credit', 'no', [
                        'purpose' => "receiving-request-short-weight-adjustment",
                        'remarks' => "Short weight adjustment ({$shortWeight} kg) on Receiving Request for DC: {$dc_no}",
                    ]);
                }
            } elseif ($arrivedWeight > $dispatchedWeight && $dispatchedWeight > 0) {
                // Case 2: Excess Weight (Arrived > Dispatched) -> Profit
                $excessWeight = $arrivedWeight - $dispatchedWeight;
                $totalExcessAmount = $excessWeight * $averageRate;
                
                $customerAccountId = $dc->customer?->account_id;
                
                if ($totalExcessAmount > 0 && $customerAccountId) {
                    $profitAccount = Account::where('hierarchy_path', '4-1-4')->first();
                    
                    if ($profitAccount) {
                        // 1. Debit Customer (Customer got extra goods, so receivable increases)
                        $handleTransaction($totalExcessAmount, $customerAccountId, $voucherTypeId, $dc_no, 'debit', 'no', [
                            'counter_account_id' => $profitAccount->id,
                            'purpose' => "receiving-request-excess-weight-adjustment",
                            'payment_against' => "pohanch-sale-receivable",
                            'against_reference_no' => $dc_no,
                            'remarks' => "Excess weight adjustment (+{$excessWeight} kg) on Receiving Request for DC: {$dc_no}",
                        ]);

                        // 2. Credit Gain/Profit Account (hierarchy 4-1-4)
                        $handleTransaction($totalExcessAmount, $profitAccount->id, $voucherTypeId, $dc_no, 'credit', 'no', [
                            'counter_account_id' => $customerAccountId,
                            'purpose' => "receiving-request-excess-profit",
                            'payment_against' => "pohanch-sale-profit",
                            'against_reference_no' => $dc_no,
                            'remarks' => "Excess weight gain/profit (+{$excessWeight} kg) on Receiving Request for DC: {$dc_no}",
                        ]);
                    }
                }
            }

            // ==========================================
            // 5. Transporter Other Amount Entry
            // ==========================================
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

            // ==========================================
            // 6. Demurrage & Detention Expense Entry
            // ==========================================
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

            // ==========================================
            // 7. Sales Return Transporter Expense Entry
            // ==========================================
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
    }

    /**
     * Handle Sales Return Approval: Stock In & Ledger Transactions
     */
    public function handleSalesReturnApproval(SalesReturn $salesReturn): void
    {
        $sr_no = $salesReturn->sr_no;
        $voucherTypeId = 10;

        // 1. Stock In Transaction for each returned item
        foreach ($salesReturn->sale_return_data as $returnData) {
            $itemId = $returnData->item_id ?? $returnData->item?->id ?? $returnData->sale_invoice_data?->item_id;
            if ($itemId && $returnData->quantity > 0) {
                $existingStock = Stock::where('voucher_no', $sr_no)
                    ->where('voucher_type', 'sale_return')
                    ->where('product_id', $itemId)
                    ->first();

                $rate = (float)($returnData->rate ?? 0);
                $netAmount = (float)($returnData->net_amount ?? 0);
                if ($netAmount <= 0) {
                    $netAmount = (float)($returnData->amount ?? 0);
                }
                if ($netAmount <= 0) {
                    $netAmount = (float)($returnData->quantity * $rate);
                }

                if (!$existingStock) {
                    createStockTransaction(
                        $itemId,
                        'sale_return',
                        $sr_no,
                        $returnData->quantity,
                        'stock-in',
                        $netAmount,
                        $rate,
                        $salesReturn->remarks ?? "Sales Return Stock-In: {$sr_no}"
                    );
                } else {
                    $existingStock->update([
                        'qty' => $returnData->quantity,
                        'rate' => $rate,
                        'total_amount' => $netAmount,
                        'remarks' => $salesReturn->remarks ?? "Sales Return Stock-In: {$sr_no}"
                    ]);
                }
            }
        }

        // 2. Balanced Ledger Entries in DB Transaction
        DB::transaction(function () use ($salesReturn, $sr_no, $voucherTypeId) {
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

            $totalReturnAmount = 0;
            foreach ($salesReturn->sale_return_data as $data) {
                $net = (float)($data->net_amount ?? 0);
                if ($net <= 0) {
                    $net = (float)($data->amount ?? 0);
                }
                if ($net <= 0) {
                    $net = (float)($data->quantity * $data->rate);
                }
                $totalReturnAmount += $net;
            }

            $customerAccountId = $salesReturn->customer?->account_id ?? Customer::find($salesReturn->customer_id)?->account_id;
            $salesReturnAccount = Account::where('hierarchy_path', '4-3')->first();

            if ($totalReturnAmount > 0 && $customerAccountId && $salesReturnAccount) {
                // Entry 1: Sales Return Account Debit (DR)
                $handleTransaction($totalReturnAmount, $salesReturnAccount->id, $voucherTypeId, $sr_no, 'debit', 'no', [
                    'counter_account_id' => $customerAccountId,
                    'purpose' => "sales-return",
                    'payment_against' => "sale-return",
                    'against_reference_no' => $sr_no,
                    'remarks' => "Sales Return booked against SR: {$sr_no}.",
                ]);

                // Entry 2: Customer Account Credit (CR)
                $handleTransaction($totalReturnAmount, $customerAccountId, $voucherTypeId, $sr_no, 'credit', 'no', [
                    'counter_account_id' => $salesReturnAccount->id,
                    'purpose' => "sales-return",
                    'payment_against' => "sale-return",
                    'against_reference_no' => $sr_no,
                    'remarks' => "Sales Return credited to Customer against SR: {$sr_no}.",
                ]);
            }
        });
    }
}
