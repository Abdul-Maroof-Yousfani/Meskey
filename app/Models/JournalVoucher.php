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

    /**
     * Cached old audit snapshot of details.
     */
    protected ?array $auditOldSnapshot = null;

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
        static::observe(\App\Observers\UniversalAuditObserver::class);

        static::retrieved(function ($model) {
            if (!request() || !request()->isMethodSafe() || app()->runningInConsole()) {
                $model->captureInitialAuditSnapshot();
            }
        });

        static::saving(function ($model) {
            if ($model->exists && method_exists($model, 'hasReferencesChanged') && $model->hasReferencesChanged()) {
                $model->updated_at = now();
            }
        });

        static::creating(function ($model) {
            if (auth()->check() && empty($model->created_by)) {
                $model->created_by = auth()->user()?->id ?? 1;
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
        $soCreditUsage = [];
        $doCreditUsage = [];
        $refJvUsage = [];
        $rvUsage = [];
        $advUsage = [];

        $jvController = app(\App\Http\Controllers\Finance\JournalVoucherController::class);

        foreach ($this->journalVoucherDetails as $detail) {
            $debit = (float) $detail->debit_amount;
            $credit = (float) $detail->credit_amount;

            if ($debit > 0) {
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
            } elseif ($credit > 0) {
                if ($detail->delivery_order_id) {
                    $doCreditUsage[$detail->delivery_order_id] = ($doCreditUsage[$detail->delivery_order_id] ?? 0) + $credit;
                } elseif ($detail->sales_order_id) {
                    $soCreditUsage[$detail->sales_order_id] = ($soCreditUsage[$detail->sales_order_id] ?? 0) + $credit;
                }
            }
        }

        $soPoolUsage = [];

        // Validate SO limits (direct SO adjustments without DO)
        foreach ($soUsage as $soId => $requiredDebit) {
            $so = \App\Models\Sales\SalesOrder::find($soId);
            $soRef = $so ? ($so->reference_no ?: "SO #{$soId}") : "SO #{$soId}";

            $available = $jvController->getSalesOrderRemainingAdvance($soId, $this->id);

            if ($requiredDebit > $available + 0.01) {
                return "Cannot approve Journal Voucher {$this->jv_no}: Sales Order {$soRef} only has Rs. " . number_format($available, 2) . " available advance balance, but this voucher attempts to adjust Rs. " . number_format($requiredDebit, 2) . ". Payment has already been refunded via Payment Voucher or consumed.";
            }

            $soPoolUsage[$soId] = ($soPoolUsage[$soId] ?? 0) + $requiredDebit;
        }

        // Validate DO limits
        foreach ($doUsage as $doId => $requiredDebit) {
            $do = \App\Models\Sales\DeliveryOrder::with(['delivery_order_data.item'])->find($doId);
            if (!$do) continue;

            $available = $jvController->getDeliveryOrderRemainingAdvance($do, $this->id);
            if ($requiredDebit > $available + 0.01) {
                return "Cannot approve Journal Voucher {$this->jv_no}: Delivery Order {$do->reference_no} only has Rs. " . number_format($available, 2) . " available advance balance, but this voucher attempts to adjust Rs. " . number_format($requiredDebit, 2) . ".";
            }

            if ($do->so_id) {
                $soPoolUsage[$do->so_id] = ($soPoolUsage[$do->so_id] ?? 0) + $requiredDebit;
                $soNetTotal = $jvController->getSalesOrderTotalNetAdvance($do->so_id, $this->id);
                if ($soPoolUsage[$do->so_id] > $soNetTotal + 0.01) {
                    $soRef = \App\Models\Sales\SalesOrder::find($do->so_id)->reference_no ?? ("SO #" . $do->so_id);
                    return "Cannot approve Journal Voucher {$this->jv_no}: The total adjustment for Delivery Order(s) and Sales Order {$soRef} exceeds the net remaining advance balance of Rs. " . number_format($soNetTotal, 2) . ".";
                }
            }
        }

        // Validate Credit lines to SO / DO (transferring advance INTO order)
        foreach ($soCreditUsage as $soId => $totalCreditEntered) {
            $so = \App\Models\Sales\SalesOrder::find($soId);
            if (!$so) continue;

            $soTotalOrderValue = (float) \Illuminate\Support\Facades\DB::table('sales_order_data')
                ->where('sale_order_id', $soId)
                ->sum(\Illuminate\Support\Facades\DB::raw('qty * rate'));

            if ($soTotalOrderValue > 0) {
                $currentAdvance = $jvController->getSalesOrderActualAdvanceReceived($soId, $this->id);
                $remainingOrderCapacity = max(0, $soTotalOrderValue - $currentAdvance);

                if ($totalCreditEntered > $remainingOrderCapacity + 0.01) {
                    $soRef = $so->reference_no ?: "SO #{$soId}";
                    return "Cannot approve Journal Voucher {$this->jv_no}: The entered credit amount (" . number_format($totalCreditEntered, 2) . ") for Sales Order {$soRef} exceeds its remaining unadvanced order amount of " . number_format($remainingOrderCapacity, 2) . ".";
                }
            }
        }

        foreach ($doCreditUsage as $doId => $totalCreditEntered) {
            $do = \App\Models\Sales\DeliveryOrder::with(['delivery_order_data'])->find($doId);
            if (!$do) continue;

            $doTotalValue = 0;
            foreach ($do->delivery_order_data as $dod) {
                $doTotalValue += (float)$dod->qty * (float)$dod->rate;
            }

            if ($doTotalValue > 0) {
                $currentDoAdvance = $jvController->getDeliveryOrderActualAdvanceReceived($do, $this->id);
                $remainingDoCapacity = max(0, $doTotalValue - $currentDoAdvance);

                if ($totalCreditEntered > $remainingDoCapacity + 0.01) {
                    return "Cannot approve Journal Voucher {$this->jv_no}: The entered credit amount (" . number_format($totalCreditEntered, 2) . ") for Delivery Order {$do->reference_no} exceeds its remaining unadvanced order amount of " . number_format($remainingDoCapacity, 2) . ".";
                }
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

    /**
     * Map unique_no attribute to jv_no for generic audit log handlers.
     */
    public function getUniqueNoAttribute(): ?string
    {
        return $this->jv_no;
    }

    /**
     * Calculate total voucher amount (total debit).
     */
    public function getTotalAmountAttribute(): float
    {
        if (request() && request()->has('details') && is_array(request()->details)) {
            $sum = 0;
            foreach (request()->details as $d) {
                $sum += (float) ($d['debit_amount'] ?? 0);
            }
            if ($sum > 0) {
                return (float) $sum;
            }
        }

        if ($this->relationLoaded('journalVoucherDetails')) {
            return (float) $this->journalVoucherDetails->sum('debit_amount');
        }
        return (float) $this->journalVoucherDetails()->sum('debit_amount');
    }

    /**
     * Identifier for audit logs.
     */
    public function auditIdentifier(): string
    {
        return (string) ($this->jv_no ?: "#{$this->id}");
    }

    /**
     * Human-readable model display name for audit logs.
     */
    public function auditModelName(): string
    {
        return 'Journal Voucher';
    }

    /**
     * Action prefix for audit logs.
     */
    public function auditActionPrefix(): string
    {
        return 'journal_voucher';
    }

    /**
     * Configuration for audit logs modal rendering.
     */
    public static function auditLogConfig(): array
    {
        return [
            'field_labels' => [
                'jv_no'              => 'JV Number',
                'jv_date'            => 'JV Date',
                'description'        => 'Description / Remarks',
                'am_approval_status' => 'Approval Status',
                'comments'           => 'Approval Comments',
                'entry_account'      => 'Accounts',
                'entry_debit'        => 'Debit Amounts',
                'entry_credit'       => 'Credit Amounts',
                'entry_description'  => 'Line Descriptions',
                'entry_reference'    => 'References / Vouchers',
                'total_debit'        => 'Total Debit',
                'total_credit'       => 'Total Credit',
                'items_count'        => 'Items Count',
            ],
            'skip_keys' => [
                'company_id',
                'deleted_at',
                'am_change_made',
                'created_by',
                'approve_user_id',
                'delete_user_id',
                'username',
                'jv_status',
                'status',
            ],
            'relation_resolvers' => [
                'am_approval_status' => function ($val) {
                    return ucwords(str_replace('_', ' ', (string) $val));
                },
                'total_debit' => function ($val) {
                    return is_numeric($val) ? 'Rs. ' . number_format((float) $val, 2) : $val;
                },
                'total_credit' => function ($val) {
                    return is_numeric($val) ? 'Rs. ' . number_format((float) $val, 2) : $val;
                },
            ],
            'subtitle' => function ($model) {
                $parts = ['JV #' . ($model->jv_no ?? $model->id)];
                if (!empty($model->jv_date)) {
                    $parts[] = 'Date: ' . (\Carbon\Carbon::parse($model->jv_date)->format('d M Y'));
                }
                $totalDebit = (float) $model->total_amount;
                if ($totalDebit > 0) {
                    $parts[] = 'Rs. ' . number_format($totalDebit, 2);
                }
                $customer = $model->customer_name;
                if (!empty($customer) && $customer !== '—') {
                    $parts[] = 'Customer: ' . $customer;
                }
                if (!empty($model->am_approval_status)) {
                    $parts[] = 'Status: ' . ucfirst($model->am_approval_status);
                }
                return implode(' | ', $parts);
            },
            'empty_message' => 'No edits or updates have been recorded for this journal voucher yet.',
        ];
    }

    /**
     * Capture initial snapshot of references for audit logging before any deletes/updates.
     */
    public function captureInitialAuditSnapshot(): void
    {
        if ($this->auditOldSnapshot !== null || !$this->exists) {
            return;
        }
        $this->auditOldSnapshot = $this->buildCurrentReferencesSnapshot();
    }

    /**
     * Get the cached old snapshot of references.
     */
    public function getOldReferencesSnapshot(): ?array
    {
        if ($this->auditOldSnapshot === null && $this->exists) {
            $this->auditOldSnapshot = $this->buildCurrentReferencesSnapshot();
        }
        return $this->auditOldSnapshot;
    }

    /**
     * Build reference snapshot from current database state.
     */
    public function buildCurrentReferencesSnapshot(): array
    {
        $details = $this->relationLoaded('journalVoucherDetails')
            ? $this->journalVoucherDetails
            : $this->journalVoucherDetails()->with('account')->get();

        $accounts = [];
        $debits = [];
        $credits = [];
        $descriptions = [];
        $references = [];
        $totalDebit = 0;
        $totalCredit = 0;

        foreach ($details as $d) {
            $accName = $d->account ? ($d->account->name . ($d->account->unique_no ? " ({$d->account->unique_no})" : "")) : ($d->acc_id ? "Account #{$d->acc_id}" : '-');
            $accounts[] = $accName;
            $debit = (float) $d->debit_amount;
            $credit = (float) $d->credit_amount;
            $debits[] = number_format($debit, 2);
            $credits[] = number_format($credit, 2);
            $descriptions[] = !empty($d->description) ? trim($d->description) : '-';

            $ref = '-';
            if (!empty($d->voucher_no)) {
                $type = $d->voucher_type ? ucwords(str_replace('_', ' ', $d->voucher_type)) : 'Ref';
                $ref = "{$type} #{$d->voucher_no}";
            } elseif (!empty($d->receipt_voucher_id)) {
                $ref = "RV #{$d->receipt_voucher_id}";
            } elseif (!empty($d->sales_order_id)) {
                $ref = "SO #{$d->sales_order_id}";
            } elseif (!empty($d->delivery_order_id)) {
                $ref = "DO #{$d->delivery_order_id}";
            } elseif (!empty($d->customer_advance_id)) {
                $ref = "Advance #{$d->customer_advance_id}";
            } elseif (!empty($d->reference_jv_id)) {
                $ref = "JV #{$d->reference_jv_id}";
            }
            $references[] = $ref;
            $totalDebit += $debit;
            $totalCredit += $credit;
        }

        return [
            'entry_account'     => !empty($accounts) ? implode(', ', $accounts) : null,
            'entry_debit'       => !empty($debits) ? implode(', ', $debits) : null,
            'entry_credit'      => !empty($credits) ? implode(', ', $credits) : null,
            'entry_description' => !empty($descriptions) ? implode(', ', $descriptions) : null,
            'entry_reference'   => !empty($references) ? implode(', ', $references) : null,
            'total_debit'       => (float) $totalDebit,
            'total_credit'      => (float) $totalCredit,
            'items_count'       => count($details),
        ];
    }

    /**
     * Build reference snapshot from incoming request payload.
     */
    public function buildNewReferencesSnapshot(): array
    {
        $req = request();
        if ($req && $req->has('details') && is_array($req->details)) {
            $accounts = [];
            $debits = [];
            $credits = [];
            $descriptions = [];
            $references = [];
            $totalDebit = 0;
            $totalCredit = 0;

            foreach ($req->details as $d) {
                if (empty($d['acc_id']) && empty($d['debit_amount']) && empty($d['credit_amount'])) {
                    continue;
                }
                $accId = $d['acc_id'] ?? null;
                $acc = $accId ? \App\Models\Master\Account\Account::find($accId) : null;
                $accName = $acc ? ($acc->name . ($acc->unique_no ? " ({$acc->unique_no})" : "")) : ($accId ? "Account #{$accId}" : '-');
                $accounts[] = $accName;

                $debit = isset($d['debit_amount']) ? round((float) $d['debit_amount'], 2) : 0;
                $credit = isset($d['credit_amount']) ? round((float) $d['credit_amount'], 2) : 0;
                $debits[] = number_format($debit, 2);
                $credits[] = number_format($credit, 2);
                $descriptions[] = !empty($d['description']) ? trim($d['description']) : '-';

                $vNo = $d['voucher_no'] ?? null;
                $vType = $d['voucher_type'] ?? null;
                if (empty($vNo)) {
                    if (!empty($d['customer_advance_id'])) {
                        $adv = \App\Models\CustomerAdvance::find($d['customer_advance_id']);
                        $vNo = $adv ? $adv->voucher_no : "Advance #{$d['customer_advance_id']}";
                        $vType = 'customer_advance';
                    } elseif (!empty($d['delivery_order_id'])) {
                        $do = \App\Models\Sales\DeliveryOrder::find($d['delivery_order_id']);
                        $vNo = $do ? ($do->reference_no ?: "DO #{$do->id}") : "DO #{$d['delivery_order_id']}";
                        $vType = 'delivery_order';
                    } elseif (!empty($d['reference_jv_id'])) {
                        $refJv = JournalVoucher::find($d['reference_jv_id']);
                        $vNo = $refJv ? $refJv->jv_no : "JV #{$d['reference_jv_id']}";
                        $vType = 'journal_voucher';
                    } elseif (!empty($d['sales_order_id'])) {
                        $so = \App\Models\Sales\SalesOrder::find($d['sales_order_id']);
                        $vNo = $so ? ($so->reference_no ?? $so->unique_no) : "SO #{$d['sales_order_id']}";
                        $vType = 'sales_order';
                    } elseif (!empty($d['receipt_voucher_id'])) {
                        $rv = \App\Models\ReceiptVoucher::find($d['receipt_voucher_id']);
                        $vNo = $rv ? $rv->unique_no : "RV #{$d['receipt_voucher_id']}";
                        $vType = 'receipt_voucher';
                    }
                }

                $ref = '-';
                if (!empty($vNo)) {
                    $typeStr = $vType ? ucwords(str_replace('_', ' ', $vType)) : 'Ref';
                    $ref = "{$typeStr} #{$vNo}";
                }
                $references[] = $ref;
                $totalDebit += $debit;
                $totalCredit += $credit;
            }

            return [
                'entry_account'     => !empty($accounts) ? implode(', ', $accounts) : null,
                'entry_debit'       => !empty($debits) ? implode(', ', $debits) : null,
                'entry_credit'      => !empty($credits) ? implode(', ', $credits) : null,
                'entry_description' => !empty($descriptions) ? implode(', ', $descriptions) : null,
                'entry_reference'   => !empty($references) ? implode(', ', $references) : null,
                'total_debit'       => (float) $totalDebit,
                'total_credit'      => (float) $totalCredit,
                'items_count'       => count($accounts),
            ];
        }

        return $this->buildCurrentReferencesSnapshot();
    }

    /**
     * Check if child reference snapshot changed between old and new state.
     */
    public function hasReferencesChanged(): bool
    {
        $old = $this->getOldReferencesSnapshot() ?? [];
        $new = $this->buildNewReferencesSnapshot();

        if (empty($old) && empty($new)) {
            return false;
        }

        $allKeys = array_unique(array_merge(array_keys($old), array_keys($new)));
        foreach ($allKeys as $k) {
            $o = $old[$k] ?? null;
            $n = $new[$k] ?? null;
            if ($o != $n) {
                return true;
            }
        }
        return false;
    }
}
