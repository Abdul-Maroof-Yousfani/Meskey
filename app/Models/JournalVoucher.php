<?php

namespace App\Models;

use App\Models\Acl\Company;
use App\Models\User;
use App\Traits\HasApproval;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class JournalVoucher extends Model
{
    use HasFactory, SoftDeletes, HasApproval {
        approve as traitApprove;
    }

    protected $fillable = [
        'jv_date',
        'jv_no',
        'description',
        'username',
        'status',
        'jv_status',
        'am_approval_status',
        'am_change_made',
        'created_by',
        'approve_user_id',
        'delete_user_id',
        'company_id'
    ];

    protected $casts = [
        'jv_date' => 'date',
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            if (auth()->check() && empty($model->created_by)) {
                $model->created_by = auth()->id();
            }
            if (empty($model->am_approval_status)) {
                $model->am_approval_status = 'pending';
            }
            if (!isset($model->am_change_made)) {
                $model->am_change_made = 1;
            }
        });

        static::updating(function ($model) {
            $originalStatus = strtolower($model->getOriginal('am_approval_status') ?? '');
            $newStatus = strtolower($model->am_approval_status ?? '');
            if ($model->isDirty('am_approval_status')) {
                if (in_array($originalStatus, ['approved', 'rejected'])) {
                    throw new \Exception("Journal Voucher is already {$originalStatus} and status cannot be changed.");
                }
                if ($originalStatus === 'reverted' && !in_array($newStatus, ['pending', 'reverted'])) {
                    throw new \Exception("Journal Voucher is reverted and cannot be {$newStatus} directly. It must be updated to pending first.");
                }
            }
        });

        static::deleting(function ($model) {
            $status = strtolower($model->getOriginal('am_approval_status') ?? $model->am_approval_status ?? '');
            if (in_array($status, ['approved', 'rejected'])) {
                throw new \Exception("Journal Voucher is already {$status} and cannot be deleted.");
            }
        });
    }

    public function journalVoucherDetails()
    {
        return $this->hasMany(JournalVoucherDetail::class, 'journal_voucher_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approveUser()
    {
        return $this->belongsTo(User::class, 'approve_user_id');
    }

    public function deleteUser()
    {
        return $this->belongsTo(User::class, 'delete_user_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function getCustomerNameAttribute(): string
    {
        $customers = collect();

        $details = $this->relationLoaded('journalVoucherDetails')
            ? $this->journalVoucherDetails
            : $this->journalVoucherDetails()->with([
                'account',
                'receiptVoucher.customer',
                'customerAdvance.customer',
                'salesOrder.customer'
            ])->get();

        foreach ($details as $detail) {
            if ($detail->receiptVoucher && $detail->receiptVoucher->customer) {
                $customers->push($detail->receiptVoucher->customer->name);
            }
            if ($detail->customerAdvance && $detail->customerAdvance->customer) {
                $customers->push($detail->customerAdvance->customer->name);
            }
            if ($detail->salesOrder && $detail->salesOrder->customer) {
                $customers->push($detail->salesOrder->customer->name);
            }
            if ($detail->deliveryOrder && $detail->deliveryOrder->customer) {
                $customers->push($detail->deliveryOrder->customer->name);
            }
            if ($detail->referenceJournalVoucher) {
                $customers->push($detail->referenceJournalVoucher->customer_name);
            }
            if ($detail->account && strtolower($detail->account->table_name ?? '') === 'customers') {
                $customers->push($detail->account->name);
            }
        }

        $unique = $customers->filter()->unique()->values();

        return $unique->isNotEmpty() ? $unique->implode(', ') : '—';
    }

    protected function onApprovalComplete()
    {
        $module = $this->getApprovalModule();

        $updateData = [
            'am_change_made' => 1,
            'jv_status' => 'approved',
            'approve_user_id' => optional(auth()->user())->id,
        ];
        if ($module && isset($module->approval_column)) {
            $updateData[$module->approval_column] = 'approved';
        }
        $this->update($updateData);

        // Post ledger transactions if they do not already exist
        $txVoucherType = \App\Models\Master\Account\TransactionVoucherType::where('code', 'JV')->first();
        if ($txVoucherType) {
            $existingTx = \App\Models\Master\Account\Transaction::where('purpose', 'like', "journal-voucher-{$this->id}-%")
                ->exists();

            if (!$existingTx) {
                foreach ($this->journalVoucherDetails as $detail) {
                    $voucherNo = $detail->voucher_no;
                    $detailVoucherType = $detail->voucher_type;

                    if (empty($voucherNo)) {
                        if ($detail->delivery_order_id && $detail->deliveryOrder) {
                            $voucherNo = $detail->deliveryOrder->reference_no ?: ('DO #' . $detail->delivery_order_id);
                            $detailVoucherType = 'delivery_order';
                        } elseif ($detail->reference_jv_id && $detail->referenceJournalVoucher) {
                            $voucherNo = $detail->referenceJournalVoucher->jv_no;
                            $detailVoucherType = 'journal_voucher';
                        } elseif ($detail->customer_advance_id && $detail->customerAdvance) {
                            $voucherNo = $detail->customerAdvance->voucher_no;
                            $detailVoucherType = 'excess_payment';
                        } elseif ($detail->receipt_voucher_id && $detail->receiptVoucher) {
                            $voucherNo = $detail->receiptVoucher->unique_no;
                            $detailVoucherType = $detailVoucherType ?: 'receipt_voucher';
                        } elseif ($detail->sales_order_id && $detail->salesOrder) {
                            $voucherNo = $detail->salesOrder->reference_no ?? $detail->salesOrder->unique_no;
                            $detailVoucherType = $detailVoucherType ?: 'sales_order';
                        } elseif (!empty($detail->voucher_id)) {
                            if ($detail->voucher_type === 'customer_advance') {
                                $adv = \App\Models\CustomerAdvance::find($detail->voucher_id);
                                $voucherNo = $adv ? $adv->voucher_no : (string) $detail->voucher_id;
                                $detailVoucherType = 'excess_payment';
                            } elseif ($detail->grn) {
                                $voucherNo = $detail->grn->unique_no;
                                $detailVoucherType = $detailVoucherType ?: 'grn';
                            } else {
                                $voucherNo = (string) $detail->voucher_id;
                            }
                        }
                    }

                    if ($detailVoucherType === 'customer_advance') {
                        $detailVoucherType = 'excess_payment';
                    }

                    $isGrn = strtolower($detailVoucherType ?? '') === 'grn' || (is_string($voucherNo) && str_starts_with($voucherNo, 'KHI-'));
                    $typeLabel = $detailVoucherType ? strtoupper(str_replace('_', ' ', $detailVoucherType)) : ($isGrn ? 'GRN' : 'VOUCHER');

                    if ($detail->debit_amount > 0) {
                        $formattedAmount = number_format($detail->debit_amount, 2);
                        if (!empty($voucherNo)) {
                            $remarks = "Amount Rs. {$formattedAmount} adjusted against {$typeLabel}# {$voucherNo}";
                            if (!empty($detail->description)) {
                                $remarks .= " ({$detail->description})";
                            } elseif (!empty($this->description)) {
                                $remarks .= " ({$this->description})";
                            }
                        } else {
                            $remarks = $detail->description ?? ($this->description ?? "Journal entry for {$this->jv_no}");
                        }

                        createTransaction(
                            (float) $detail->debit_amount,
                            $detail->acc_id,
                            $txVoucherType->id,
                            $this->jv_no,
                            'debit',
                            'no',
                            [
                                'reference_no' => $voucherNo ?? '',
                                'grn_no' => $isGrn ? $voucherNo : null,
                                'purpose' => "journal-voucher-{$this->id}-{$this->jv_no}",
                                'remarks' => $remarks,
                                'voucher_date' => $this->jv_date ? $this->jv_date->format('Y-m-d') : now()->format('Y-m-d')
                            ]
                        );
                    }

                    if ($detail->credit_amount > 0) {
                        $formattedAmount = number_format($detail->credit_amount, 2);
                        if (!empty($voucherNo)) {
                            $remarks = "Amount Rs. {$formattedAmount} adjusted against {$typeLabel}# {$voucherNo}";
                            if (!empty($detail->description)) {
                                $remarks .= " ({$detail->description})";
                            } elseif (!empty($this->description)) {
                                $remarks .= " ({$this->description})";
                            }
                        } else {
                            $remarks = $detail->description ?? ($this->description ?? "Journal entry for {$this->jv_no}");
                        }

                        createTransaction(
                            (float) $detail->credit_amount,
                            $detail->acc_id,
                            $txVoucherType->id,
                            $this->jv_no,
                            'credit',
                            'no',
                            [
                                'reference_no' => $voucherNo ?? '',
                                'grn_no' => $isGrn ? $voucherNo : null,
                                'purpose' => "journal-voucher-{$this->id}-{$this->jv_no}",
                                'remarks' => $remarks,
                                'voucher_date' => $this->jv_date ? $this->jv_date->format('Y-m-d') : now()->format('Y-m-d')
                            ]
                        );
                    }
                }
            }
        }
    }

    protected function onApprovalRejected()
    {
        $module = $this->getApprovalModule();
        $updateData = [
            'am_change_made' => 0,
            'jv_status' => 'rejected'
        ];
        if ($module && isset($module->approval_column)) {
            $updateData[$module->approval_column] = 'rejected';
        }
        // Revert any customer advance adjustments made by this JV
        $adjustments = \App\Models\CustomerAdvanceAdjustment::where('voucher_no', $this->jv_no)->get();
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

        \Illuminate\Support\Facades\DB::table('settlement_adjustments')->where('voucher_no', $this->jv_no)->delete();

        $this->update($updateData);
    }

    protected function onApprovalReverted()
    {
        $module = $this->getApprovalModule();
        $updateData = [
            'am_change_made' => 0,
            'jv_status' => 'reverted'
        ];
        if ($module && isset($module->approval_column)) {
            $updateData[$module->approval_column] = 'reverted';
        }

        // Revert any customer advance adjustments made by this JV
        $adjustments = \App\Models\CustomerAdvanceAdjustment::where('voucher_no', $this->jv_no)->get();
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

        \Illuminate\Support\Facades\DB::table('settlement_adjustments')->where('voucher_no', $this->jv_no)->delete();

        $this->update($updateData);
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
        $this->loadMissing([
            'journalVoucherDetails.salesOrder',
            'journalVoucherDetails.deliveryOrder',
            'journalVoucherDetails.referenceJournalVoucher',
            'journalVoucherDetails.receiptVoucher',
            'journalVoucherDetails.customerAdvance'
        ]);

        $soUsage = [];
        $doUsage = [];
        $refJvUsage = [];
        $rvUsage = [];
        $advUsage = [];

        foreach ($this->journalVoucherDetails as $detail) {
            $debit = (float) $detail->debit_amount;
            if ($debit <= 0) {
                continue;
            }

            if ($detail->delivery_order_id) {
                $doUsage[$detail->delivery_order_id] = ($doUsage[$detail->delivery_order_id] ?? 0) + $debit;
            } elseif ($detail->sales_order_id) {
                $soUsage[$detail->sales_order_id] = ($soUsage[$detail->sales_order_id] ?? 0) + $debit;
            }

            if ($detail->reference_jv_id) {
                $refJvUsage[$detail->reference_jv_id] = ($refJvUsage[$detail->reference_jv_id] ?? 0) + $debit;
            }

            if ($detail->receipt_voucher_id) {
                $rvUsage[$detail->receipt_voucher_id] = ($rvUsage[$detail->receipt_voucher_id] ?? 0) + $debit;
            }

            if ($detail->customer_advance_id) {
                $advUsage[$detail->customer_advance_id] = ($advUsage[$detail->customer_advance_id] ?? 0) + $debit;
            }
        }

        // Validate SO limits (direct SO adjustments without DO)
        foreach ($soUsage as $soId => $requiredDebit) {
            $so = \App\Models\Sales\SalesOrder::find($soId);
            $soRef = $so ? ($so->reference_no ?: "SO #{$soId}") : "SO #{$soId}";

            // 1. Total advance received for this SO from approved RVs
            $soRvAdvance = (float) \Illuminate\Support\Facades\DB::table('receipt_voucher_items')
                ->join('receipt_vouchers', 'receipt_vouchers.id', '=', 'receipt_voucher_items.receipt_voucher_id')
                ->whereNull('receipt_vouchers.deleted_at')
                ->where('receipt_vouchers.am_approval_status', 'approved')
                ->where('receipt_voucher_items.reference_type', 'sale_order')
                ->where('receipt_voucher_items.reference_id', $soId)
                ->sum('receipt_voucher_items.net_amount');

            // 2. Advance attached/consumed by Delivery Orders for this SO
            $soDoAdvanceConsumed = (float) \Illuminate\Support\Facades\DB::table('delivery_order_receipt_voucher as dorv')
                ->join('delivery_order as do_tbl', 'do_tbl.id', '=', 'dorv.delivery_order_id')
                ->where('do_tbl.so_id', $soId)
                ->where('do_tbl.am_approval_status', '!=', 'rejected')
                ->sum('dorv.amount');

            // 3. Other approved JVs on this SO (exclude this JV)
            $rvIdsForSo = \Illuminate\Support\Facades\DB::table('receipt_voucher_items')
                ->where('reference_type', 'sale_order')
                ->where('reference_id', $soId)
                ->pluck('receipt_voucher_id')
                ->toArray();

            $soOtherJvAdjusted = (float) \Illuminate\Support\Facades\DB::table('journal_voucher_details')
                ->join('journal_vouchers', 'journal_vouchers.id', '=', 'journal_voucher_details.journal_voucher_id')
                ->whereNull('journal_vouchers.deleted_at')
                ->whereNull('journal_voucher_details.deleted_at')
                ->where('journal_vouchers.am_approval_status', 'approved')
                ->where('journal_vouchers.id', '!=', $this->id)
                ->where(function ($q) use ($soId, $rvIdsForSo) {
                    $q->where('journal_voucher_details.sales_order_id', $soId)
                      ->whereNull('journal_voucher_details.delivery_order_id');
                    if (!empty($rvIdsForSo)) {
                        $q->orWhere(function ($sub) use ($rvIdsForSo) {
                            $sub->whereIn('journal_voucher_details.receipt_voucher_id', $rvIdsForSo)
                                ->whereNull('journal_voucher_details.sales_order_id')
                                ->whereNull('journal_voucher_details.delivery_order_id');
                        });
                    }
                })
                ->sum('journal_voucher_details.debit_amount');

            // 4. DPV refunds for this SO (not rejected)
            $alreadyRefundedSo = (float) \App\Models\PaymentVoucherData::where('sales_order_id', $soId)
                ->whereNull('delivery_order_id')
                ->whereHas('paymentVoucher', function ($q) {
                    $q->whereNull('deleted_at');
                    $q->where('am_approval_status', '!=', 'rejected');
                })
                ->sum('amount');

            $available = max(0, $soRvAdvance - $soDoAdvanceConsumed - $soOtherJvAdjusted - $alreadyRefundedSo);

            if ($requiredDebit > $available + 0.01) {
                return "Cannot approve Journal Voucher {$this->jv_no}: Sales Order {$soRef} only has Rs. " . number_format($available, 2) . " available advance balance, but this voucher attempts to adjust Rs. " . number_format($requiredDebit, 2) . ". Payment has already been refunded via Payment Voucher or consumed.";
            }
        }

        // Validate DO limits
        foreach ($doUsage as $doId => $requiredDebit) {
            $do = \App\Models\Sales\DeliveryOrder::with(['delivery_order_data.item'])->find($doId);
            if (!$do) continue;

            $totalDoQty = 0;
            $totalDcQty = 0;
            $totalRemainingQty = 0;
            $totalRemainingAmount = 0;
            $totalDispatchedValue = 0;

            foreach ($do->delivery_order_data as $itemData) {
                $iDoQty = (float) $itemData->qty;
                $iDcQty = (float) \App\Models\Sales\DeliveryChallanData::where('do_data_id', $itemData->id)
                    ->whereHas('deliveryChallan', function ($q) {
                        $q->where('am_approval_status', '!=', 'rejected');
                    })
                    ->sum('qty');

                $iRemQty = max(0, $iDoQty - $iDcQty);
                $iRate = (float) $itemData->rate;
                $iRemAmt = $iRemQty * $iRate;

                $totalDoQty += $iDoQty;
                $totalDcQty += $iDcQty;
                $totalRemainingQty += $iRemQty;
                $totalRemainingAmount += $iRemAmt;
                $totalDispatchedValue += ($iDcQty * $iRate);
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

            $alreadyRefunded = (float) \App\Models\PaymentVoucherData::where('delivery_order_id', $do->id)
                ->whereHas('paymentVoucher', function ($q) {
                    $q->whereNull('deleted_at');
                    $q->where('am_approval_status', '!=', 'rejected');
                })
                ->sum('amount');

            $alreadyJvAdjusted = (float) JournalVoucherDetail::where('delivery_order_id', $do->id)
                ->whereHas('journalVoucher', function ($q) {
                    $q->whereNull('deleted_at');
                    $q->where('am_approval_status', 'approved');
                    $q->where('id', '!=', $this->id);
                })
                ->sum('debit_amount');

            $available = max(0, $totalRemainingAmount - ($alreadyRefunded + $alreadyJvAdjusted));
            if ($requiredDebit > $available + 0.01) {
                return "Cannot approve Journal Voucher {$this->jv_no}: Delivery Order {$do->reference_no} only has Rs. " . number_format($available, 2) . " available advance balance, but this voucher attempts to adjust Rs. " . number_format($requiredDebit, 2) . ".";
            }
        }

        // Validate Reference JV limits
        foreach ($refJvUsage as $refJvId => $requiredDebit) {
            $refJv = JournalVoucher::find($refJvId);
            if (!$refJv) continue;

            $refDetail = JournalVoucherDetail::where('journal_voucher_id', $refJvId)
                ->where('credit_amount', '>', 0)
                ->first();
            $creditAmount = (float) ($refDetail ? $refDetail->credit_amount : 0);

            $settlementSpent = (float) \Illuminate\Support\Facades\DB::table('settlement_adjustments')
                ->where('reference_type', 'journal_voucher')
                ->where('reference_id', $refJvId)
                ->where('voucher_no', '!=', $this->jv_no)
                ->sum('amount');

            $pvDataSpent = (float) \Illuminate\Support\Facades\DB::table('payment_voucher_data')
                ->join('payment_vouchers', 'payment_vouchers.id', '=', 'payment_voucher_data.payment_voucher_id')
                ->whereNull('payment_vouchers.deleted_at')
                ->whereNull('payment_voucher_data.deleted_at')
                ->where('payment_vouchers.am_approval_status', '!=', 'rejected')
                ->where('payment_voucher_data.journal_voucher_id', $refJvId)
                ->sum('payment_voucher_data.net_amount');

            $otherJvSpent = (float) JournalVoucherDetail::join('journal_vouchers', 'journal_vouchers.id', '=', 'journal_voucher_details.journal_voucher_id')
                ->whereNull('journal_vouchers.deleted_at')
                ->whereNull('journal_voucher_details.deleted_at')
                ->where('journal_vouchers.am_approval_status', 'approved')
                ->where('journal_vouchers.id', '!=', $this->id)
                ->where('journal_voucher_details.reference_jv_id', $refJvId)
                ->sum('journal_voucher_details.debit_amount');

            $alreadySpent = max($settlementSpent, $pvDataSpent, $otherJvSpent);
            $available = max(0, $creditAmount - $alreadySpent);

            if ($requiredDebit > $available + 0.01) {
                return "Cannot approve Journal Voucher {$this->jv_no}: Reference Journal Voucher {$refJv->jv_no} only has Rs. " . number_format($available, 2) . " available credit balance, but this voucher attempts to adjust Rs. " . number_format($requiredDebit, 2) . ".";
            }
        }

        // Validate RV limits (where not already validated via SO)
        foreach ($rvUsage as $rvId => $requiredDebit) {
            $rv = \App\Models\ReceiptVoucher::find($rvId);
            if (!$rv) continue;

            $doConsumed = (float) \Illuminate\Support\Facades\DB::table('delivery_order_receipt_voucher as dorv')
                ->join('delivery_order as do_tbl', 'do_tbl.id', '=', 'dorv.delivery_order_id')
                ->where('do_tbl.am_approval_status', '!=', 'rejected')
                ->where('dorv.receipt_voucher_id', $rvId)
                ->sum('dorv.amount');

            $otherJvConsumed = (float) \Illuminate\Support\Facades\DB::table('journal_voucher_details')
                ->join('journal_vouchers', 'journal_vouchers.id', '=', 'journal_voucher_details.journal_voucher_id')
                ->whereNull('journal_vouchers.deleted_at')
                ->whereNull('journal_voucher_details.deleted_at')
                ->where('journal_vouchers.am_approval_status', 'approved')
                ->where('journal_vouchers.id', '!=', $this->id)
                ->where('journal_voucher_details.receipt_voucher_id', $rvId)
                ->sum('journal_voucher_details.debit_amount');

            $rvSoIds = \Illuminate\Support\Facades\DB::table('receipt_voucher_items')
                ->where('receipt_voucher_id', $rvId)
                ->whereIn('reference_type', ['sale_order', 'sales_invoice'])
                ->pluck('reference_id');

            $dpvConsumed = 0;
            if ($rvSoIds->isNotEmpty()) {
                $dpvConsumed = (float) \App\Models\PaymentVoucherData::whereIn('sales_order_id', $rvSoIds)
                    ->whereHas('paymentVoucher', function ($q) {
                        $q->whereNull('deleted_at');
                        $q->where('am_approval_status', '!=', 'rejected');
                    })
                    ->sum('amount');
            }

            $soLinkedAmount = (float) \Illuminate\Support\Facades\DB::table('receipt_voucher_items')
                ->where('receipt_voucher_id', $rvId)
                ->whereIn('reference_type', ['sale_order', 'sales_invoice'])
                ->sum('net_amount');
            $baseAmount = $soLinkedAmount > 0 ? $soLinkedAmount : (float) $rv->total_amount;

            $available = max(0, $baseAmount - ($doConsumed + $otherJvConsumed + $dpvConsumed));

            if ($requiredDebit > $available + 0.01) {
                return "Cannot approve Journal Voucher {$this->jv_no}: Receipt Voucher {$rv->unique_no} only has Rs. " . number_format($available, 2) . " available balance, but this voucher attempts to adjust Rs. " . number_format($requiredDebit, 2) . ".";
            }
        }

        // Validate Excess Advance / Customer Advance limits
        foreach ($advUsage as $advId => $requiredDebit) {
            $adv = \App\Models\CustomerAdvance::find($advId);
            if (!$adv) continue;

            $available = (float) $adv->remaining_amount;
            // Add back any adjustment recorded for this JV
            $prevAdj = (float) \App\Models\CustomerAdvanceAdjustment::where('customer_advance_id', $advId)
                ->where('voucher_no', $this->jv_no)
                ->sum('amount');
            $available += $prevAdj;

            // Also deduct any pending/approved DPV refunds on this customer advance
            $dpvRefunded = (float) \App\Models\PaymentVoucherData::where('customer_advance_id', $advId)
                ->whereHas('paymentVoucher', function ($q) {
                    $q->whereNull('deleted_at');
                    $q->where('am_approval_status', '!=', 'rejected');
                })
                ->sum('amount');

            $available = max(0, $available - $dpvRefunded);

            if ($requiredDebit > $available + 0.01) {
                return "Cannot approve Journal Voucher {$this->jv_no}: Excess Payment {$adv->voucher_no} only has Rs. " . number_format($available, 2) . " available balance, but this voucher attempts to adjust Rs. " . number_format($requiredDebit, 2) . ".";
            }
        }

        return null;
    }
}
