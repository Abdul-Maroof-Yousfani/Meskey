<?php

namespace App\Models\Sales;

use App\Models\Master\Account\Account;
use App\Models\Master\Customer;
use App\Models\Master\Transporter;
use App\Models\Master\Vendor;
use App\Models\Procurement\Store\FactoryLocation;
use App\Models\Procurement\Store\SectionLocation;
use App\Traits\HasApproval;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class DeliveryChallan extends Model
{
    use HasFactory;
    use HasApproval {
        onApprovalComplete as traitOnApprovalComplete;
        onApprovalRejected as traitOnApprovalRejected;
    }

    protected $table = 'delivery_challans';

    protected $fillable = [
        "customer_id",
        "reference_number",
        "dispatch_date",
        "dc_no",
        "sauda_type",
        "location_id",
        "arrival_id",
        "company_id",
        "remarks",
        "labour",
        "labour_amount",
        "transporter",
        "transporter_id",
        "transporter_amount",
        "inhouse-weighbridge",
        "weighbridge-amount",
        "subarrival_id",
        "created_by_id",
        "section_id",
        'labour_rate',
        "labour_status",
        "am_approval_status",
        "am_change_made"
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            $model->type = 'sale_delivery_challan';
        });

        static::updating(function ($model) {
            $model->type = 'sale_delivery_challan';
        });

        static::addGlobalScope('sale_type', function ($builder) {
            $builder->where('delivery_challans.type', 'sale_delivery_challan');
        });
    }


    public function delivery_challan_data()
    {
        return $this->hasMany(DeliveryChallanData::class, "delivery_challan_id", "id");
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function delivery_order()
    {
        return $this->belongsToMany(DeliveryOrder::class, "delivery_challan_delivery_order", "delivery_challan_id", "delivery_order_id");
    }

    public function receivingRequest()
    {
        return $this->hasOne(ReceivingRequest::class, "delivery_challan_id");
    }


    public function factories()
    {
        return $this->morphMany(FactoryLocation::class, 'factoryable');
    }

    public function sections()
    {
        return $this->morphMany(SectionLocation::class, 'sectionable');
    }

    protected function onApprovalComplete()
    {
        $this->traitOnApprovalComplete();

        foreach ($this->delivery_challan_data as $data) {
            if ($data->ticket_id) {
                $ticket = LoadingProgramItem::find($data->ticket_id);
                if ($ticket) {
                    $ticket->update(['process_status' => 'DC Generated']);
                }
            }
        }

        // Create Receiving Request upon DC Approval
        if (strtolower($this->sauda_type) == 'pohanch') {
            $receivingRequest = $this->receivingRequest;
            $dcDataFirst = $this->delivery_challan_data->first();

            if (!$receivingRequest) {
                $receivingRequest = ReceivingRequest::create([
                    'delivery_challan_id' => $this->id,
                    'dc_no' => $this->dc_no,
                    'dc_date' => $this->dispatch_date,
                    'truck_number' => $dcDataFirst?->truck_no ?? null,
                    'labour' => $this->labour,
                    'transporter' => $this->transporter,
                    'inhouse_weighbridge' => $this->{'inhouse-weighbridge'} ?? null,
                    'labour_amount' => $this->labour_amount ?? 0,
                    'transporter_amount' => $this->transporter_amount ?? 0,
                    'inhouse_weighbridge_amount' => $this->{'weighbridge-amount'} ?? 0,
                    'company_id' => $this->company_id,
                    'created_by_id' => $this->created_by_id,
                    'am_approval_status' => 'draft',
                ]);
            } else {
                $receivingRequest->update([
                    'dc_no' => $this->dc_no,
                    'dc_date' => $this->dispatch_date,
                    'truck_number' => $dcDataFirst?->truck_no ?? null,
                    'labour' => $this->labour,
                    'transporter' => $this->transporter,
                    'inhouse_weighbridge' => $this->{'inhouse-weighbridge'} ?? null,
                    'labour_amount' => $this->labour_amount ?? 0,
                    'transporter_amount' => $this->transporter_amount ?? 0,
                    'inhouse_weighbridge_amount' => $this->{'weighbridge-amount'} ?? 0,
                    'am_approval_status' => 'draft',
                    'created_by_id' => $this->created_by_id,
                ]);
            }

            // Sync Receiving Request Items
            $receivingRequest->items()->delete();
            foreach ($this->delivery_challan_data as $dcData) {
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


            //Ledgers
            $salesOrder = $this->delivery_order->first()?->salesOrder;

            // if ($salesOrder && strtolower($salesOrder->transporter_used) == 'yes') {
            DB::transaction(function () use ($salesOrder) {
                $dc_no = $this->dc_no;
                $company_id = $this->company_id ?? (auth()->check() ? auth()->user()->current_company_id : 1);

                $handleTransaction = function($amount, $accountId, $companyId, $voucherNo, $type, $isOpening, $additionalData) {
                    $tx = \App\Models\Master\Account\Transaction::where('voucher_no', $voucherNo)
                            ->where('purpose', $additionalData['purpose'])
                            ->where('type', $type)
                            ->first();
                    if ($tx) {
                        $tx->update([
                            'amount' => $amount,
                            'account_id' => $accountId,
                            'company_id' => $companyId,
                            'counter_account_id' => $additionalData['counter_account_id'] ?? null,
                            'payment_against' => $additionalData['payment_against'] ?? null,
                            'against_reference_no' => $additionalData['against_reference_no'] ?? null,
                            'remarks' => $additionalData['remarks'] ?? null,
                        ]);
                    } else {
                        createTransaction($amount, $accountId, $companyId, $voucherNo, $type, $isOpening, $additionalData);
                    }
                };

                $customerAccountId = $this->customer?->account_id;
                $salesRevenueAccount = Account::where('hierarchy_path', '4-2')->first();
                $inventoryAccountId = $salesRevenueAccount?->id;

                $transporterObj = Transporter::find($this->transporter);
                $transporterAccountId = $transporterObj?->account_id;

                $labourObj = Vendor::find($this->labour);
                $labourAccountId = $labourObj?->account_id;

                $brokerAccountId = $salesOrder?->broker?->account_id;

                $transporterExpenseAccount = Account::where('hierarchy_path', '5-3')->first();
                $labourExpenseAccount = Account::where('hierarchy_path', '5-4')->first();
                $commissionExpenseAccount = Account::where('hierarchy_path', '5-5')->first();

                $totalSaleAmount = 0;
                $totalQty = 0;
                foreach ($this->delivery_challan_data as $data) {
                    $totalSaleAmount += ($data->qty * $data->rate);
                    $totalQty += $data->qty;
                }

                if ($totalSaleAmount > 0 && $customerAccountId && $inventoryAccountId) {
                    // Sale Entry - Customer Debit
                    $handleTransaction($totalSaleAmount, $customerAccountId, $company_id, $dc_no, 'debit', 'no', [
                        'counter_account_id' => $inventoryAccountId,
                        'purpose' => "delivery-challan-sale",
                        'payment_against' => "pohanch-sale",
                        'against_reference_no' => $dc_no,
                        'remarks' => "Sale booked against DC: {$dc_no}. Amount receivable from customer.",
                    ]);

                    // Sale Entry - Sales Revenue Credit
                    $handleTransaction($totalSaleAmount, $inventoryAccountId, $company_id, $dc_no, 'credit', 'no', [
                        'counter_account_id' => $customerAccountId,
                        'purpose' => "delivery-challan-sale",
                        'payment_against' => "pohanch-sale",
                        'against_reference_no' => $dc_no,
                        'remarks' => "Sales revenue credited for sale against DC: {$dc_no}.",
                    ]);
                }

                if ($this->transporter_amount > 0 && $transporterExpenseAccount && $transporterAccountId) {
                    // Transporter Expense Debit
                    $handleTransaction($this->transporter_amount, $transporterExpenseAccount->id, $company_id, $dc_no, 'debit', 'no', [
                        'counter_account_id' => $transporterAccountId,
                        'purpose' => "transporter-expense",
                        'payment_against' => "pohanch-sale-expense",
                        'against_reference_no' => $dc_no,
                        'remarks' => "Transporter expense booked for DC: {$dc_no}.",
                    ]);

                    // Transporter Payable Credit
                    $handleTransaction($this->transporter_amount, $transporterAccountId, $company_id, $dc_no, 'credit', 'no', [
                        'counter_account_id' => $transporterExpenseAccount->id,
                        'purpose' => "transporter-payable",
                        'payment_against' => "pohanch-sale-payable",
                        'against_reference_no' => $dc_no,
                        'remarks' => "Transporter payable booked for DC: {$dc_no}.",
                    ]);
                }

                if ($this->labour_amount > 0 && $labourExpenseAccount && $labourAccountId) {
                    // Labour Expense Debit
                    $handleTransaction($this->labour_amount, $labourExpenseAccount->id, $company_id, $dc_no, 'debit', 'no', [
                        'counter_account_id' => $labourAccountId,
                        'purpose' => "labour-expense",
                        'payment_against' => "pohanch-sale-expense",
                        'against_reference_no' => $dc_no,
                        'remarks' => "Labour expense booked for DC: {$dc_no}.",
                    ]);

                    // Labour Payable Credit
                    $handleTransaction($this->labour_amount, $labourAccountId, $company_id, $dc_no, 'credit', 'no', [
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
                    $handleTransaction($commissionAmount, $commissionExpenseAccount->id, $company_id, $dc_no, 'debit', 'no', [
                        'counter_account_id' => $brokerAccountId,
                        'purpose' => "commission-expense",
                        'payment_against' => "pohanch-sale-expense",
                        'against_reference_no' => $dc_no,
                        'remarks' => "Broker commission expense booked for DC: {$dc_no}.",
                    ]);

                    // Broker Payable Credit
                    $handleTransaction($commissionAmount, $brokerAccountId, $company_id, $dc_no, 'credit', 'no', [
                        'counter_account_id' => $commissionExpenseAccount->id,
                        'purpose' => "broker-payable",
                        'payment_against' => "pohanch-sale-payable",
                        'against_reference_no' => $dc_no,
                        'remarks' => "Broker payable booked for DC: {$dc_no}.",
                    ]);
                }
            });
            // }
        } else {
            $receivingRequest = $this->receivingRequest;
            if ($receivingRequest) {
                $receivingRequest->items()->delete();
                $receivingRequest->delete();
            }
        }
    }

    protected function onApprovalRejected()
    {
        $this->traitOnApprovalRejected();

        foreach ($this->delivery_challan_data as $data) {
            if ($data->ticket_id) {
                $ticket = \App\Models\Sales\LoadingProgramItem::find($data->ticket_id);
                if ($ticket) {
                    $ticket->update(['process_status' => 'DC Rejected']);
                }
            }
        }
    }

}
