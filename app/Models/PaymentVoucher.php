<?php

namespace App\Models;

use App\Models\Master\Account\Account;
use App\Models\Master\Supplier;
use App\Traits\HasApproval;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentVoucher extends Model
{
    use HasFactory, SoftDeletes, HasApproval {
        approve as traitApprove;
    }

    protected $fillable = [
        'unique_no',
        'pv_date',
        'ref_bill_no',
        'bill_date',
        'cheque_no',
        'cheque_date',
        'account_id',
        'request_account_id',
        'supplier_id',
        'am_approval_status',
        'bank_account_type',
        'bank_account_id',
        'module_id',
        'module_type',
        'voucher_type',
        'remarks',
        'total_amount',
        "is_direct"
    ];

    protected $casts = [
        'pv_date' => 'date',
        'bill_date' => 'date',
        'cheque_date' => 'date',
    ];

    protected static function booted()
    {
        static::updating(
            function ($model) {
                $changes = $model->getDirty();
                $changedColumns = [];

                foreach ($changes as $key => $newValue) {
                    if ($key !== "am_change_made") {
                        $oldValue = $model->getOriginal($key);
                        $changedColumns[$key] = [
                            'old' => $oldValue,
                            'new' => $newValue,
                        ];
                    }
                }

                if (!empty($changedColumns)) {
                    if ($model->getAttribute('am_change_made') !== null) {
                        $model->am_change_made = 1;
                    }
                }
            }
        );
    }

    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function bank_account() {
        return $this->belongsTo(Account::class, 'bank_account_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function requestAccount()
    {
        return $this->belongsTo(Account::class, 'request_account_id');
    }

    public function paymentVoucherData()
    {
        return $this->hasMany(PaymentVoucherData::class, 'payment_voucher_id');
    }

    public function billPaymentVoucherData() {
        return $this->hasMany(BillPaymentVoucherData::class, 'payment_voucher_id');
    }

    public function approve($comments = null)
    {
        $valError = $this->validateBeforeApproval();
        if ($valError) {
            return false;
        }
        return $this->traitApprove($comments);
    }

    /**
     * Validate balances before approval to prevent race conditions & negative balances.
     * Returns null if valid, or an error message string if invalid.
     */
    public function validateBeforeApproval(): ?string
    {
        if ($this->module_type !== 'dpv_for_sale' && !$this->is_direct) {
            return null;
        }

        $this->loadMissing([
            'paymentVoucherData.salesOrder',
            'paymentVoucherData.deliveryOrder',
            'paymentVoucherData.customerAdvance'
        ]);

        $pvController = app(\App\Http\Controllers\Finance\PaymentVoucherController::class);

        foreach ($this->paymentVoucherData as $index => $item) {
            $amount = (float) $item->amount;
            if ($amount <= 0) {
                continue;
            }

            // Case 1: Direct Sales Order refund (without DO)
            if ($item->sales_order_id && empty($item->delivery_order_id)) {
                $so = $item->salesOrder ?? \App\Models\Sales\SalesOrder::find($item->sales_order_id);
                $soRef = $so ? ($so->reference_no ?: "SO #{$item->sales_order_id}") : "SO #{$item->sales_order_id}";

                // Calculate remaining advance on SO excluding THIS voucher's amount
                $remAdvance = $pvController->getSalesOrderRemainingAdvance($item->sales_order_id, $this->unique_no, $this->id);

                if ($amount > $remAdvance + 0.01) {
                    return "Cannot approve Payment Voucher {$this->unique_no}: Sales Order {$soRef} only has Rs. " . number_format($remAdvance, 2) . " available advance balance, but this voucher refunds Rs. " . number_format($amount, 2) . ". Balance may have been adjusted by a Journal Voucher or consumed.";
                }
            }

            // Case 2: Delivery Order refund
            if ($item->delivery_order_id) {
                $do = $item->deliveryOrder ?? \App\Models\Sales\DeliveryOrder::find($item->delivery_order_id);
                $doRef = $do ? ($do->reference_no ?: "DO #{$item->delivery_order_id}") : "DO #{$item->delivery_order_id}";

                $totalRemainingAmount = 0;
                $totalDispatchedValue = 0;

                if ($do) {
                    foreach ($do->delivery_order_data as $dod) {
                        $iDoQty = (float) $dod->qty;
                        $iDcQty = (float) \App\Models\Sales\DeliveryChallanData::where('do_data_id', $dod->id)
                            ->whereHas('deliveryChallan', function ($q) {
                                $q->where('am_approval_status', '!=', 'rejected');
                            })
                            ->sum('qty');
                        $iRemQty = max(0, $iDoQty - $iDcQty);
                        $iRate = (float) $dod->rate;
                        $totalRemainingAmount += $iRemQty * $iRate;
                        $totalDispatchedValue += $iDcQty * $iRate;
                    }

                    $attachedRvAmount = (float) \Illuminate\Support\Facades\DB::table('delivery_order_receipt_voucher as dorv')
                        ->join('receipt_vouchers as rv', 'rv.id', '=', 'dorv.receipt_voucher_id')
                        ->whereNull('rv.deleted_at')
                        ->where('rv.am_approval_status', 'approved')
                        ->where('dorv.delivery_order_id', $do->id)
                        ->sum('dorv.amount');

                    if ($attachedRvAmount > 0) {
                        $remainingAttachedAdvance = max(0, $attachedRvAmount - $totalDispatchedValue);
                        $totalRemainingAmount = max($totalRemainingAmount, $remainingAttachedAdvance);
                    }
                }

                // Deduct OTHER DPV refunds on this DO (exclude this voucher)
                $otherRefunded = (float) \App\Models\PaymentVoucherData::where('delivery_order_id', $item->delivery_order_id)
                    ->where('payment_voucher_id', '!=', $this->id)
                    ->whereHas('paymentVoucher', function ($q) {
                        $q->whereNull('deleted_at');
                        $q->where('am_approval_status', '!=', 'rejected');
                    })
                    ->sum('amount');

                $doAvailable = max(0, $totalRemainingAmount - $otherRefunded);

                if ($amount > $doAvailable + 0.01) {
                    return "Cannot approve Payment Voucher {$this->unique_no}: Delivery Order {$doRef} only has Rs. " . number_format($doAvailable, 2) . " returnable balance, but this voucher refunds Rs. " . number_format($amount, 2) . ".";
                }
            }

            // Case 3: Customer Advance / Excess Payment refund
            if ($item->customer_advance_id) {
                $adv = $item->customerAdvance ?? \App\Models\CustomerAdvance::find($item->customer_advance_id);
                $advRef = $adv ? $adv->voucher_no : "Advance #{$item->customer_advance_id}";

                $available = $adv ? (float) $adv->remaining_amount : 0;
                // Add back this voucher's adjustment if recorded
                $adjThis = (float) \App\Models\CustomerAdvanceAdjustment::where('customer_advance_id', $item->customer_advance_id)
                    ->where('voucher_no', $this->unique_no)
                    ->sum('amount');
                $available += $adjThis;

                // Deduct any other non-rejected DPV refunds on this customer advance
                $otherDpv = (float) \App\Models\PaymentVoucherData::where('customer_advance_id', $item->customer_advance_id)
                    ->where('payment_voucher_id', '!=', $this->id)
                    ->whereHas('paymentVoucher', function ($q) {
                        $q->whereNull('deleted_at');
                        $q->where('am_approval_status', '!=', 'rejected');
                    })
                    ->sum('amount');
                $available = max(0, $available - $otherDpv);

                if ($amount > $available + 0.01) {
                    return "Cannot approve Payment Voucher {$this->unique_no}: Excess Payment {$advRef} only has Rs. " . number_format($available, 2) . " available balance, but this voucher refunds Rs. " . number_format($amount, 2) . ".";
                }
            }
        }

        return null;
    }

    protected function onApprovalComplete()
    {
        $module = $this->getApprovalModule();
        $updateData = [
            'am_change_made' => 1,
        ];
        if ($module && isset($module->approval_column)) {
            $updateData[$module->approval_column] = 'approved';
        }
        $this->update($updateData);

        // Post ledger transactions upon approval
        $this->postLedgerTransactions();
    }

    /**
     * Post accounting ledger transactions upon full approval of the voucher.
     */
    public function postLedgerTransactions()
    {
        // 1. Guard against duplicate posting
        $existingTx = \App\Models\Master\Account\Transaction::where(function ($q) {
            $q->where('voucher_no', $this->unique_no)
              ->orWhere('purpose', 'like', "BPV-{$this->id}%")
              ->orWhere('purpose', 'like', "CPV-{$this->id}%")
              ->orWhere('purpose', 'like', "PV-{$this->id}%");
        })->exists();

        if ($existingTx) {
            return;
        }

        $prefix = $this->voucher_type === 'bank_payment_voucher' ? 'BPV' : 'CPV';
        $voucherDate = $this->pv_date ? $this->pv_date->format('Y-m-d') : now()->format('Y-m-d');

        // CASE 1: Direct Payment Voucher for Sale (Sale Returns / Customer Excess Advances)
        if ($this->module_type === 'dpv_for_sale') {
            $this->loadMissing(['paymentVoucherData.customerAdvance', 'paymentVoucherData.salesOrder', 'paymentVoucherData.deliveryOrder']);

            $totalAmount = 0;
            foreach ($this->paymentVoucherData as $item) {
                $netAmount = (float) ($item->net_amount ?: $item->amount);
                if ($netAmount <= 0) continue;

                $targetAccId = $item->account_id ?: $this->request_account_id;
                $desc = $item->description;

                if ($item->customer_advance_id || $item->reference_type === 'customer_advance') {
                    $advNo = $item->customerAdvance?->voucher_no ?? '';
                    $itemRemarks = "DPV Excess Refund" . ($advNo ? " ({$advNo})" : "") . " - " . ($desc ?: 'Refund');
                } else {
                    $refStr = $item->delivery_order_id ? "DO #{$item->delivery_order_id}" : ($item->sales_order_id ? "SO #{$item->sales_order_id}" : '');
                    $itemRemarks = "DPV Sale Return - " . ($desc ?: $refStr);
                }

                createTransaction(
                    $netAmount,
                    $targetAccId,
                    1,
                    $this->unique_no,
                    'debit',
                    'no',
                    [
                        'purpose' => "$prefix-{$this->id}",
                        'counter_account_id' => $this->account_id,
                        'remarks' => $itemRemarks,
                        'voucher_date' => $voucherDate,
                    ]
                );

                $totalAmount += $netAmount;
            }

            if ($totalAmount > 0) {
                createTransaction(
                    $totalAmount,
                    $this->account_id,
                    1,
                    $this->unique_no,
                    'credit',
                    'no',
                    [
                        'purpose' => "$prefix-{$this->id}",
                        'counter_account_id' => $this->request_account_id,
                        'remarks' => "Direct Payment Voucher (Sale)",
                        'voucher_date' => $voucherDate,
                    ]
                );
            }
        }
        // CASE 2: General Direct Payment Voucher (Expense / Direct)
        elseif ($this->is_direct) {
            $this->loadMissing(['paymentVoucherData']);

            $totalAmount = 0;
            foreach ($this->paymentVoucherData as $item) {
                $netAmount = (float) ($item->net_amount ?: $item->amount);
                if ($netAmount <= 0) continue;

                createTransaction(
                    $netAmount,
                    $item->account_id,
                    1,
                    $this->unique_no,
                    'debit',
                    'no',
                    [
                        'purpose' => "$prefix-{$this->id}",
                        'counter_account_id' => $this->account_id,
                        'remarks' => "Direct payment - " . ($item->description ?? ''),
                        'voucher_date' => $voucherDate,
                    ]
                );

                $totalAmount += $netAmount;
            }

            if ($totalAmount > 0) {
                createTransaction(
                    $totalAmount,
                    $this->account_id,
                    1,
                    $this->unique_no,
                    'credit',
                    'no',
                    [
                        'purpose' => "$prefix-{$this->id}",
                        'counter_account_id' => $this->request_account_id,
                        'remarks' => 'Direct payment voucher',
                        'voucher_date' => $voucherDate,
                    ]
                );
            }
        }
        // CASE 3: Bill Payment Voucher via BillPaymentVoucherData (Purchase Bills)
        elseif ($this->billPaymentVoucherData()->exists()) {
            $this->loadMissing(['billPaymentVoucherData']);

            $totalAmount = 0;
            foreach ($this->billPaymentVoucherData as $item) {
                $amount = (float) $item->amount;
                if ($amount <= 0) continue;

                $purchaseBill = \App\Models\Procurement\Store\PurchaseBill::find($item->purchase_bill_id);
                $refNo = $purchaseBill ? $purchaseBill->reference_no : (string) $item->purchase_bill_id;
                $remarks = "A payment of Rs. " . number_format($amount, 2) . " has been made." . ($this->voucher_type === 'bank_payment_voucher' ? ' through bank transfer.' : ' in cash.');

                // Credit bank/cash
                createTransaction(
                    $amount,
                    $this->account_id ?: $this->bank_account_id,
                    1,
                    $this->unique_no,
                    'credit',
                    'no',
                    [
                        'purpose' => "$prefix-{$this->id}-{$this->unique_no}",
                        'payment_against' => (string) $item->purchase_bill_id,
                        'against_reference_no' => $refNo,
                        'counter_account_id' => $this->supplier_id,
                        'remarks' => $remarks,
                        'voucher_date' => $voucherDate,
                    ]
                );

                // Debit supplier
                createTransaction(
                    $amount,
                    $this->supplier_id,
                    1,
                    $this->unique_no,
                    'debit',
                    'no',
                    [
                        'purpose' => "$prefix-{$this->id}-{$this->unique_no}",
                        'payment_against' => (string) $item->purchase_bill_id,
                        'against_reference_no' => $refNo,
                        'counter_account_id' => $this->account_id ?: $this->bank_account_id,
                        'remarks' => $remarks,
                        'voucher_date' => $voucherDate,
                    ]
                );

                $totalAmount += $amount;
            }
        }
        // CASE 4: Bill Payment Voucher via PaymentRequest (Raw Material Purchase / Delivery Challan / PRs)
        else {
            $this->loadMissing(['paymentVoucherData.paymentRequest.paymentRequestData.purchaseOrder', 'paymentVoucherData.paymentRequest.deliveryChallan.delivery_challan_data']);

            $totalAmount = 0;
            $allPaymentAgainst = [];
            $allReferenceNos = [];

            foreach ($this->paymentVoucherData as $item) {
                $payableAmount = (float) ($item->net_amount ?: $item->amount);
                if ($payableAmount <= 0) continue;

                $paymentRequest = $item->paymentRequest;
                $ticketDetails = '';
                $truckNo = 'N/A';
                $biltyNo = 'N/A';
                $grnNo = null;
                $reqType = 'PR';

                if ($paymentRequest) {
                    $dc = $paymentRequest->deliveryChallan;
                    $prData = $paymentRequest->paymentRequestData;
                    $grnNo = $prData?->grn_no ?? null;
                    $reqType = $paymentRequest->request_type ?? 'PR';

                    if ($dc) {
                        $firstDcData = $dc->delivery_challan_data->first();
                        $truckNo = $prData->truck_no ?? $firstDcData?->truck_no ?? 'N/A';
                        $biltyNo = $prData->bilty_no ?? $firstDcData?->bilty_no ?? 'N/A';
                        $ticketDetails = "DC# {$dc->dc_no} VEH# {$truckNo} Bilty# {$biltyNo}";
                        $ticketNo = $dc->dc_no;
                    } elseif (!empty($prData?->purchase_ticket_id)) {
                        $ticketNo = $prData->purchaseTicket->unique_no ?? 'N/A';
                        $truckNo = $prData->purchaseTicket->purchaseFreight->truck_no ?? 'N/A';
                        $biltyNo = $prData->purchaseTicket->purchaseFreight->bilty_no ?? 'N/A';
                        $ticketDetails = "Purchase Ticket #{$ticketNo} VEH# {$truckNo} Bilty# {$biltyNo} PO# " . ($prData->purchaseOrder->contract_no ?? 'N/A');
                    } elseif (!empty($prData?->arrival_ticket_id)) {
                        $ticketNo = $prData->arrivalTicket->unique_no ?? 'N/A';
                        $truckNo = $prData->arrivalTicket->truck_no ?? 'N/A';
                        $biltyNo = $prData->arrivalTicket->bilty_no ?? 'N/A';
                        $ticketDetails = "Arrival Ticket #{$ticketNo} VEH# {$truckNo} Bilty# {$biltyNo} PO# " . ($prData->purchaseOrder->contract_no ?? 'N/A') . " GRN# " . ($prData->grn_no ?? 'N/A');
                    } else {
                        $ticketNo = $paymentRequest->request_no ?? 'PR';
                        $truckNo = $prData->truck_no ?? 'N/A';
                        $biltyNo = $prData->bilty_no ?? 'N/A';
                        $ticketDetails = "PR# {$paymentRequest->id} VEH# {$truckNo} Bilty# {$biltyNo}";
                    }

                    $allPaymentAgainst[] = "{$ticketNo}-{$paymentRequest->id}";
                    $allReferenceNos[] = "{$truckNo}/{$biltyNo}";
                }

                $supplierRemarks = "A payment of Rs. " . number_format($payableAmount, 2) . " has been made" . ($ticketDetails ? " for: {$ticketDetails}" : "") . ($this->voucher_type === 'bank_payment_voucher' ? ' through bank transfer.' : ' in cash.');

                createTransaction(
                    $payableAmount,
                    $this->request_account_id,
                    1,
                    $this->unique_no,
                    'debit',
                    'no',
                    [
                        'purpose' => "$prefix-{$this->id}-{$this->unique_no}",
                        'payment_against' => $reqType,
                        'against_reference_no' => "{$truckNo}/{$biltyNo}",
                        'counter_account_id' => $this->account_id,
                        'remarks' => $supplierRemarks,
                        'reference_no' => $grnNo,
                        'grn_no' => $grnNo,
                        'voucher_date' => $voucherDate,
                    ]
                );

                $totalAmount += $payableAmount;
            }

            if ($totalAmount > 0) {
                $bankRemarks = "A payment of Rs. " . number_format($totalAmount, 2) . " has been made" . ($this->voucher_type === 'bank_payment_voucher' ? ' through bank transfer.' : ' in cash.');
                createTransaction(
                    $totalAmount,
                    $this->account_id,
                    1,
                    $this->unique_no,
                    'credit',
                    'no',
                    [
                        'purpose' => "$prefix-{$this->id}-{$this->unique_no}",
                        'payment_against' => implode(', ', $allPaymentAgainst),
                        'against_reference_no' => implode(', ', $allReferenceNos),
                        'counter_account_id' => $this->request_account_id,
                        'remarks' => $bankRemarks,
                        'voucher_date' => $voucherDate,
                    ]
                );
            }
        }
    }

    protected function onApprovalRejected()
    {
        $module = $this->getApprovalModule();
        $updateData = [
            'am_change_made' => 0,
        ];
        if ($module && isset($module->approval_column)) {
            $updateData[$module->approval_column] = 'rejected';
        }
        $this->update($updateData);

        // Delete transactions generated upon creation of this payment voucher
        \App\Models\Master\Account\Transaction::where(function ($q) {
            $q->where('voucher_no', $this->unique_no)
              ->orWhere('purpose', 'like', "BPV-{$this->id}%")
              ->orWhere('purpose', 'like', "CPV-{$this->id}%")
              ->orWhere('purpose', 'like', "PV-{$this->id}%");
        })->delete();

        // Revert any customer advance adjustments made by this PV
        $adjustments = \App\Models\CustomerAdvanceAdjustment::where('voucher_no', $this->unique_no)->get();
        foreach ($adjustments as $adj) {
            $adv = \App\Models\CustomerAdvance::find($adj->customer_advance_id);
            if ($adv) {
                $adv->used_amount -= $adj->amount;
                $adv->remaining_amount += $adj->amount;
                $adv->status = ($adv->used_amount <= 0.01) ? 'pending' : 'partial_payment';
                $adv->save();
            }
            $adj->delete();
        }

        // If DOs or SOs were auto-closed by this DPV, re-open them if undelivered items exist
        if ($this->module_type === 'dpv_for_sale') {
            foreach ($this->paymentVoucherData as $item) {
                if ($item->delivery_order_id) {
                    $do = \App\Models\Sales\DeliveryOrder::with('delivery_order_data')->find($item->delivery_order_id);
                    if ($do && $do->do_status === 'closed') {
                        $hasPending = false;
                        foreach ($do->delivery_order_data as $dod) {
                            $dcQty = (float) \App\Models\Sales\DeliveryChallanData::where('do_data_id', $dod->id)
                                ->whereHas('deliveryChallan', function ($q) {
                                    $q->where('am_approval_status', '!=', 'rejected');
                                })
                                ->sum('qty');
                            if ((float)$dod->qty - $dcQty > 0.01) {
                                $hasPending = true;
                                break;
                            }
                        }
                        if ($hasPending) {
                            $do->update(['do_status' => 'active']);
                        }
                    }
                }
                if ($item->sales_order_id) {
                    $so = \App\Models\Sales\SalesOrder::with('sales_order_data')->find($item->sales_order_id);
                    if ($so && $so->status === 'closed') {
                        $hasPendingSo = false;
                        foreach ($so->sales_order_data as $sod) {
                            if (function_exists('delivery_order_qty_balance') && delivery_order_qty_balance($sod->id) > 0.01) {
                                $hasPendingSo = true;
                                break;
                            }
                        }
                        if ($hasPendingSo) {
                            $so->update(['status' => 'approved', 'contract_status' => 'active']);
                        }
                    }
                }
            }
        }
    }

    protected function onApprovalReverted()
    {
        $module = $this->getApprovalModule();
        $updateData = [
            'am_change_made' => 0,
        ];
        if ($module && isset($module->approval_column)) {
            $updateData[$module->approval_column] = 'reverted';
        }
        $this->update($updateData);

        // Delete transactions generated upon approval of this payment voucher
        \App\Models\Master\Account\Transaction::where(function ($q) {
            $q->where('voucher_no', $this->unique_no)
              ->orWhere('purpose', 'like', "BPV-{$this->id}%")
              ->orWhere('purpose', 'like', "CPV-{$this->id}%")
              ->orWhere('purpose', 'like', "PV-{$this->id}%");
        })->delete();
    }
}
