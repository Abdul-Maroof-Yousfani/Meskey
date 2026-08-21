<?php

namespace App\Models\Sales;

use App\Models\Master\Account\Account;
use App\Models\Master\Account\Transaction;
use App\Models\Master\Transporter;
use App\Models\User;
use App\Traits\HasApproval;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ReceivingRequest extends Model
{
    use HasFactory;
    use HasApproval {
        onApprovalComplete as traitOnApprovalComplete;
        onApprovalRejected as traitOnApprovalRejected;
    }

    protected $fillable = [
        'delivery_challan_id',
        'dc_no',
        'dc_date',
        'truck_number',
        'bilty',
        'labour',
        'transporter',
        'inhouse_weighbridge',
        'labour_amount',
        'transporter_amount',
        'transporter_deduction',
        'weighbridge_amount',
        'inhouse_weighbridge_amount',
        'company_id',
        'created_by_id',
        'am_approval_status',
        'am_change_made',
        'arrived_date',
        'arrived_weight',
        'exempted_weight',
        'payment_weight',
        'unloading_paid_by',
        'weighbridge_paid_by'
    ];

    protected $casts = [
        'dc_date' => 'date',
        'labour_amount' => 'decimal:2',
        'transporter_amount' => 'decimal:2',
        'transporter_deduction' => 'decimal:2',
        'weighbridge_amount' => 'decimal:2',
        'inhouse_weighbridge_amount' => 'decimal:2',
    ];

    public function deliveryChallan()
    {
        return $this->belongsTo(DeliveryChallan::class, 'delivery_challan_id');
    }

    public function items()
    {
        return $this->hasMany(ReceivingRequestItem::class, 'receiving_request_id');
    }

    public function weighbridges()
    {
        return $this->hasMany(ReceivingRequestWeighbridge::class, 'receiving_request_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    protected function onApprovalComplete()
    {
        $this->traitOnApprovalComplete();

        // 1. Get DC and Sales Order
        $dc = $this->deliveryChallan;
        $dc_no = $this->dc_no;
        $company_id = $this->company_id ?? (auth()->check() ? auth()->user()->current_company_id : 1);
        $salesOrder = $dc->delivery_order->first()?->salesOrder;
        $voucherTypeId = 3;

        // if ($salesOrder && strtolower($salesOrder->transporter_used) == 'yes') {
            DB::transaction(function () use ($dc, $dc_no, $voucherTypeId, $salesOrder) {
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
                $unloadingPaidBy = strtolower($this->unloading_paid_by ?? '');
                
                $unloadingLabourAmount = 0;
                foreach ($this->items as $item) {
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
                        $transporterObj = Transporter::find($this->transporter);
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
                $weighbridgePaidBy = strtolower($this->weighbridge_paid_by ?? '');
                
                $totalWeighbridgeAmount = 0;
                foreach ($this->weighbridges as $wb) {
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
                        $transporterObj = Transporter::find($this->transporter);
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
                $deductionAmount = floatval($this->transporter_deduction ?? 0);
                if ($deductionAmount > 0) {
                    $transporterObj = Transporter::find($this->transporter);
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

                // weight difference entries
                $totalSaleAmount = 0;
                $totalQty = 0;
                foreach ($dc->delivery_challan_data as $data) {
                    $totalSaleAmount += ($data->qty * $data->rate);
                    $totalQty += $data->qty;
                }
                
                $averageRate = $totalQty > 0 ? ($totalSaleAmount / $totalQty) : 0;
                
                $dispatchedWeight = $totalQty;
                $arrivedWeight = $this->arrived_weight ?? 0;
                
                // Only adjust if there is a shortage
                if ($arrivedWeight > 0 && $arrivedWeight < $dispatchedWeight) {
                    $shortWeight = $dispatchedWeight - $arrivedWeight;
                    $exemptedWeight = $this->exempted_weight ?? 0;
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
                            $transporterObj = Transporter::find($this->transporter);
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
                    // Excess Weight (Arrived > Dispatched) -> Profit
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
            });
        // }
    }

    protected function onApprovalRejected()
    {
        $this->traitOnApprovalRejected();
    }
}

