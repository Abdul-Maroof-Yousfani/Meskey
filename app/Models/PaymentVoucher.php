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

        static::deleting(function ($model) {
            \Illuminate\Support\Facades\DB::table('settlement_adjustments')->where('voucher_no', $model->unique_no)->delete();
        });
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

        $soAccumulated = [];
        $doAccumulated = [];

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
                $soAccumulated[$item->sales_order_id] = ($soAccumulated[$item->sales_order_id] ?? 0) + $amount;

                if ($soAccumulated[$item->sales_order_id] > $remAdvance + 0.01) {
                    return "Cannot approve Payment Voucher {$this->unique_no}: Sales Order {$soRef} only has Rs. " . number_format($remAdvance, 2) . " available advance balance, but this voucher refunds Rs. " . number_format($soAccumulated[$item->sales_order_id], 2) . ". Balance may have been adjusted by a Journal Voucher or consumed.";
                }
            }

            // Case 2: Delivery Order refund
            if ($item->delivery_order_id) {
                $do = $item->deliveryOrder ?? \App\Models\Sales\DeliveryOrder::find($item->delivery_order_id);
                $doRef = $do ? ($do->reference_no ?: "DO #{$item->delivery_order_id}") : "DO #{$item->delivery_order_id}";

                $doAvailable = $pvController->getDeliveryOrderRemainingRefundAmount($do, $this->unique_no, $this->id);
                $doAccumulated[$item->delivery_order_id] = ($doAccumulated[$item->delivery_order_id] ?? 0) + $amount;

                if ($doAccumulated[$item->delivery_order_id] > $doAvailable + 0.01) {
                    return "Cannot approve Payment Voucher {$this->unique_no}: Delivery Order {$doRef} only has Rs. " . number_format($doAvailable, 2) . " returnable balance, but this voucher refunds Rs. " . number_format($doAccumulated[$item->delivery_order_id], 2) . ". Balance may have been adjusted by a Journal Voucher or consumed.";
                }

                if ($do && $do->so_id) {
                    $soAccumulated[$do->so_id] = ($soAccumulated[$do->so_id] ?? 0) + $amount;
                    $soNetTotal = $pvController->getSalesOrderTotalNetAdvance($do->so_id, $this->unique_no, $this->id);
                    if ($soAccumulated[$do->so_id] > $soNetTotal + 0.01) {
                        $soRef = \App\Models\Sales\SalesOrder::find($do->so_id)->reference_no ?? ("SO #" . $do->so_id);
                        return "Cannot approve Payment Voucher {$this->unique_no}: The total refund for Delivery Order(s) and Sales Order {$soRef} exceeds the net remaining advance balance of Rs. " . number_format($soNetTotal, 2) . ".";
                    }
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

            // Case 4: Journal Voucher refund
            if ($item->journal_voucher_id || $item->journal_voucher_detail_id || $item->reference_type === 'journal_voucher') {
                $jvDetailId = $item->journal_voucher_detail_id ?: $item->reference_id;
                $jvDetail = \App\Models\JournalVoucherDetail::with('journalVoucher')->find($jvDetailId);
                if (!$jvDetail && $item->journal_voucher_id) {
                    $jvDetail = \App\Models\JournalVoucherDetail::where('journal_voucher_id', $item->journal_voucher_id)
                        ->where('credit_amount', '>', 0)
                        ->first();
                }

                $creditAmt = $jvDetail ? (float) $jvDetail->credit_amount : 0;
                $jvNo = $jvDetail?->journalVoucher?->jv_no ?: ('JV #' . $item->journal_voucher_id);

                $settlementSpent = (float) \Illuminate\Support\Facades\DB::table('settlement_adjustments')
                    ->where('reference_type', 'journal_voucher')
                    ->where('reference_id', $jvDetail ? $jvDetail->journal_voucher_id : $item->journal_voucher_id)
                    ->where('voucher_no', '!=', $this->unique_no)
                    ->sum('amount');

                $otherDpvRefunded = (float) \App\Models\PaymentVoucherData::where(function ($q) use ($item, $jvDetail) {
                    if ($jvDetail) {
                        $q->where('journal_voucher_detail_id', $jvDetail->id)
                          ->orWhere('reference_id', $jvDetail->id);
                    }
                    if ($item->journal_voucher_id) {
                        $q->orWhere('journal_voucher_id', $item->journal_voucher_id);
                    }
                })
                ->where('payment_voucher_id', '!=', $this->id)
                ->whereHas('paymentVoucher', function ($q) {
                    $q->whereNull('deleted_at')
                      ->where('am_approval_status', '!=', 'rejected');
                })
                ->sum('net_amount');

                $otherJvAdjusted = (float) \App\Models\JournalVoucherDetail::where('reference_jv_id', $jvDetail ? $jvDetail->journal_voucher_id : $item->journal_voucher_id)
                    ->whereHas('journalVoucher', function ($q) {
                        $q->whereNull('deleted_at');
                        $q->where('am_approval_status', '!=', 'rejected');
                    })
                    ->sum('debit_amount');

                $jvAvailable = max(0, $creditAmt - max($settlementSpent, ($otherDpvRefunded + $otherJvAdjusted)));
                $netAmount = (float) ($item->net_amount ?: $item->amount);

                if ($netAmount > $jvAvailable + 0.01) {
                    return "Cannot approve Payment Voucher {$this->unique_no}: Journal Voucher {$jvNo} only has Rs. " . number_format($jvAvailable, 2) . " available balance, but this voucher refunds Rs. " . number_format($netAmount, 2) . ".";
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
     * Auto-approve the voucher upon save/creation and post ledger transactions immediately.
     */
    public function autoApprove($comments = 'Auto-approved upon creation')
    {
        $this->refresh();

        $module = $this->getApprovalModule();
        $statusCol = ($module && isset($module->approval_column)) ? $module->approval_column : 'am_approval_status';

        $this->update([
            $statusCol => 'approved',
            'am_change_made' => 1,
        ]);

        if ($module) {
            $currentCycle = $this->getCurrentApprovalCycle();
            $rows = $this->approvalRows()
                ->where('module_id', $module->id)
                ->where('approval_cycle', $currentCycle)
                ->get();

            if ($rows->isEmpty()) {
                $this->createApprovalRows();
                $rows = $this->approvalRows()
                    ->where('module_id', $module->id)
                    ->where('approval_cycle', $currentCycle)
                    ->get();
            }

            foreach ($rows as $row) {
                $row->update([
                    'current_count' => $row->required_count,
                    'status' => 'approved',
                ]);
            }

            $userId = \Illuminate\Support\Facades\Auth::user()?->id ?? 1;
            $roleId = $rows->first()?->role_id ?? 1;

            \App\Models\ApprovalsModule\ApprovalLog::create([
                'module_id' => $module->id,
                'record_id' => $this->id,
                'user_id' => $userId,
                'role_id' => $roleId,
                'action' => 'approved',
                'status' => 'active',
                'approval_cycle' => $currentCycle,
                'comments' => $comments,
            ]);
        }

        // Post accounting ledger transactions immediately
        $this->postLedgerTransactions();
    }

    /**
     * Build transaction entries definition for both ledger posting and preview.
     */
    public function buildTransactionEntries(): array
    {
        $prefix = $this->voucher_type === 'bank_payment_voucher' ? 'BPV' : 'CPV';
        $voucherDate = $this->pv_date ? $this->pv_date->format('Y-m-d') : now()->format('Y-m-d');
        $entries = [];

        // CASE 1: Direct Payment Voucher for Sale (Sale Returns / Customer Excess Advances)
        if ($this->module_type === 'dpv_for_sale') {
            $this->loadMissing([
                'paymentVoucherData.customerAdvance',
                'paymentVoucherData.salesOrder',
                'paymentVoucherData.deliveryOrder',
                'paymentVoucherData.journalVoucher',
                'paymentVoucherData.account'
            ]);

            $totalAmount = 0;
            foreach ($this->paymentVoucherData as $item) {
                $netAmount = (float) ($item->net_amount ?: $item->amount);
                if ($netAmount <= 0) continue;

                $targetAccId = $item->account_id ?: $this->request_account_id;
                $desc = $item->description;

                if ($item->journal_voucher_id || $item->reference_type === 'journal_voucher') {
                    $jvNo = $item->journalVoucher?->jv_no ?? '';
                    $itemRemarks = "DPV JV Refund" . ($jvNo ? " ({$jvNo})" : "") . " - " . ($desc ?: 'Refund of JV Adjustment');
                } elseif ($item->customer_advance_id || $item->reference_type === 'customer_advance') {
                    $advNo = $item->customerAdvance?->voucher_no ?? '';
                    $itemRemarks = "DPV Excess Refund" . ($advNo ? " ({$advNo})" : "") . " - " . ($desc ?: 'Refund');
                } else {
                    $refStr = $item->delivery_order_id ? "DO #{$item->delivery_order_id}" : ($item->sales_order_id ? "SO #{$item->sales_order_id}" : '');
                    $itemRemarks = "DPV Sale Return - " . ($desc ?: $refStr);
                }

                $entries[] = [
                    'amount' => $netAmount,
                    'account_id' => $targetAccId,
                    'type' => 'debit',
                    'additional' => [
                        'purpose' => "$prefix-{$this->id}",
                        'counter_account_id' => $this->account_id,
                        'remarks' => $itemRemarks,
                        'voucher_date' => $voucherDate,
                    ],
                ];

                $totalAmount += $netAmount;
            }

            if ($totalAmount > 0) {
                $entries[] = [
                    'amount' => $totalAmount,
                    'account_id' => $this->account_id,
                    'type' => 'credit',
                    'additional' => [
                        'purpose' => "$prefix-{$this->id}",
                        'counter_account_id' => $this->request_account_id,
                        'remarks' => "Direct Payment Voucher (Sale)",
                        'voucher_date' => $voucherDate,
                    ],
                ];
            }
        }
        // CASE 2: General Direct Payment Voucher (Expense / Direct)
        elseif ($this->is_direct) {
            $this->loadMissing(['paymentVoucherData.account']);

            $totalAmount = 0;
            foreach ($this->paymentVoucherData as $item) {
                $netAmount = (float) ($item->net_amount ?: $item->amount);
                if ($netAmount <= 0) continue;

                $desc = $item->description ? "Direct payment - " . $item->description : "Direct payment - " . ($item->account?->name ?? 'Expense');

                $entries[] = [
                    'amount' => $netAmount,
                    'account_id' => $item->account_id,
                    'type' => 'debit',
                    'additional' => [
                        'purpose' => "$prefix-{$this->id}",
                        'counter_account_id' => $this->account_id,
                        'remarks' => $desc,
                        'voucher_date' => $voucherDate,
                    ],
                ];

                $totalAmount += $netAmount;
            }

            if ($totalAmount > 0) {
                $entries[] = [
                    'amount' => $totalAmount,
                    'account_id' => $this->account_id,
                    'type' => 'credit',
                    'additional' => [
                        'purpose' => "$prefix-{$this->id}",
                        'counter_account_id' => $this->request_account_id,
                        'remarks' => 'Direct payment voucher',
                        'voucher_date' => $voucherDate,
                    ],
                ];
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
                $entries[] = [
                    'amount' => $amount,
                    'account_id' => $this->account_id ?: $this->bank_account_id,
                    'type' => 'credit',
                    'additional' => [
                        'purpose' => "$prefix-{$this->id}-{$this->unique_no}",
                        'payment_against' => (string) $item->purchase_bill_id,
                        'against_reference_no' => $refNo,
                        'counter_account_id' => $this->supplier_id,
                        'remarks' => $remarks,
                        'voucher_date' => $voucherDate,
                    ],
                ];

                // Debit supplier
                $entries[] = [
                    'amount' => $amount,
                    'account_id' => $this->supplier_id,
                    'type' => 'debit',
                    'additional' => [
                        'purpose' => "$prefix-{$this->id}-{$this->unique_no}",
                        'payment_against' => (string) $item->purchase_bill_id,
                        'against_reference_no' => $refNo,
                        'counter_account_id' => $this->account_id ?: $this->bank_account_id,
                        'remarks' => $remarks,
                        'voucher_date' => $voucherDate,
                    ],
                ];

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

                $entries[] = [
                    'amount' => $payableAmount,
                    'account_id' => $this->request_account_id,
                    'type' => 'debit',
                    'additional' => [
                        'purpose' => "$prefix-{$this->id}-{$this->unique_no}",
                        'payment_against' => $reqType,
                        'against_reference_no' => "{$truckNo}/{$biltyNo}",
                        'counter_account_id' => $this->account_id,
                        'remarks' => $supplierRemarks,
                        'reference_no' => $grnNo,
                        'grn_no' => $grnNo,
                        'voucher_date' => $voucherDate,
                    ],
                ];

                $totalAmount += $payableAmount;
            }

            if ($totalAmount > 0) {
                $bankRemarks = "A payment of Rs. " . number_format($totalAmount, 2) . " has been made" . ($this->voucher_type === 'bank_payment_voucher' ? ' through bank transfer.' : ' in cash.');
                $entries[] = [
                    'amount' => $totalAmount,
                    'account_id' => $this->account_id,
                    'type' => 'credit',
                    'additional' => [
                        'purpose' => "$prefix-{$this->id}-{$this->unique_no}",
                        'payment_against' => implode(', ', $allPaymentAgainst),
                        'against_reference_no' => implode(', ', $allReferenceNos),
                        'counter_account_id' => $this->request_account_id,
                        'remarks' => $bankRemarks,
                        'voucher_date' => $voucherDate,
                    ],
                ];
            }
        }

        return $entries;
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

        $entries = $this->buildTransactionEntries();

        foreach ($entries as $entry) {
            createTransaction(
                $entry['amount'],
                $entry['account_id'],
                1,
                $this->unique_no,
                $entry['type'],
                'no',
                $entry['additional'] ?? []
            );
        }
    }

    /**
     * Get preview transaction objects before approval.
     */
    public function getPreviewTransactions()
    {
        $entries = $this->buildTransactionEntries();
        $transactions = collect();

        foreach ($entries as $entry) {
            $account = Account::find($entry['account_id']);
            $additional = $entry['additional'] ?? [];
            $tx = new \App\Models\Master\Account\Transaction(array_merge([
                'amount' => $entry['amount'],
                'account_id' => $entry['account_id'],
                'type' => $entry['type'],
                'voucher_no' => $this->unique_no,
                'status' => 'pending',
            ], $additional));

            if ($account) {
                $tx->setRelation('account', $account);
            }

            $transactions->push($tx);
        }

        return $transactions;
    }

    /**
     * Resolve Payee name across all payment voucher types.
     */
    public function getPaidToNameAttribute(): string
    {
        if ($this->module_type === 'bill_payment_voucher') {
            if ($this->supplier) {
                return $this->supplier->name;
            }
            if ($this->supplier_id) {
                $acc = Account::find($this->supplier_id);
                if ($acc) return $acc->name;
            }
        }

        if ($this->requestAccount) {
            return $this->requestAccount->name;
        }

        if ($this->request_account_id) {
            $reqAcc = Account::find($this->request_account_id);
            if ($reqAcc) return $reqAcc->name;
        }

        $this->loadMissing('paymentVoucherData.account');
        $firstItemAcc = $this->paymentVoucherData->first()?->account;
        if ($firstItemAcc) {
            return $firstItemAcc->name;
        }

        if ($this->supplier) {
            return $this->supplier->name;
        }

        // Check first payment request
        $this->loadMissing(['paymentVoucherData.paymentRequest.paymentRequestData.purchaseOrder.supplier', 'paymentVoucherData.paymentRequest.deliveryChallan']);
        $firstItem = $this->paymentVoucherData->first();
        if ($firstItem && $firstItem->paymentRequest) {
            $pr = $firstItem->paymentRequest;
            if ($pr->paymentRequestData?->supplier_name) {
                return $pr->paymentRequestData->supplier_name;
            }
            if ($pr->paymentRequestData?->purchaseOrder?->supplier?->name) {
                return $pr->paymentRequestData->purchaseOrder->supplier->name;
            }
            if ($pr->account_id) {
                $acc = Account::find($pr->account_id);
                if ($acc) return $acc->name;
            }
        }

        return 'N/A';
    }

    /**
     * Get default remarks if remarks is empty.
     */
    public function getDefaultRemarksAttribute(): string
    {
        if (!empty($this->remarks)) {
            return $this->remarks;
        }

        if ($this->module_type === 'dpv_for_sale') {
            return 'Direct Payment Voucher (Sale Return / Customer Refund)';
        }

        if ($this->is_direct) {
            return 'Direct Payment Voucher';
        }

        if ($this->module_type === 'bill_payment_voucher') {
            return 'Bill Payment Voucher';
        }

        return 'Payment Voucher against Payment Request';
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

        // Revert settlement_adjustments created for this voucher
        \Illuminate\Support\Facades\DB::table('settlement_adjustments')->where('voucher_no', $this->unique_no)->delete();

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
