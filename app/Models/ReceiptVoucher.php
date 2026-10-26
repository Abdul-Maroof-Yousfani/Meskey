<?php

namespace App\Models;

use App\Models\Master\Account\Account;
use App\Models\Master\Customer;
use App\Models\Master\Tax;
use App\Models\Sales\DeliveryOrder;
use App\Models\Sales\SalesOrder;
use App\Models\Sales\SalesInvoice;
use App\Models\CustomerAdvance;
use App\Models\CustomerAdvanceAdjustment;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasApproval;

class ReceiptVoucher extends Model
{
    use HasFactory, SoftDeletes, HasApproval;

    /**
     * Cached old audit snapshot of references.
     */
    protected ?array $auditOldSnapshot = null;

    protected $fillable = [
        'unique_no',
        'rv_date',
        'ref_bill_no',
        'bill_date',
        'cheque_no',
        'cheque_date',
        'account_id',
        'customer_id',
        'bank_account_id',
        'bank_account_type',
        'module_id',
        'module_type',
        'voucher_type',
        'remarks',
        'total_amount',
        'company_id',
        "is_direct",
        'am_approval_status',
        'am_change_made',
        'allow_excess_amount'
    ];

    protected $casts = [
        'rv_date' => 'date',
        'bill_date' => 'date',
        'cheque_date' => 'date',
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

        static::updating(function ($model) {
            $originalStatus = strtolower($model->getOriginal('am_approval_status') ?? '');
            $newStatus = strtolower($model->am_approval_status ?? '');
            if ($model->isDirty('am_approval_status')) {
                if (in_array($originalStatus, ['approved', 'rejected'])) {
                    throw new \Exception("Receipt Voucher is already {$originalStatus} and status cannot be changed.");
                }
                if ($originalStatus === 'reverted' && !in_array($newStatus, ['pending', 'reverted'])) {
                    throw new \Exception("Receipt Voucher is reverted and cannot be {$newStatus} directly. It must be updated to pending first.");
                }
            }
        });

        static::deleting(function ($model) {
            $status = strtolower($model->getOriginal('am_approval_status') ?? $model->am_approval_status ?? '');
            if (in_array($status, ['approved', 'rejected'])) {
                throw new \Exception("Receipt Voucher is already {$status} and cannot be deleted.");
            }
        });
    }

    /**
     * Audit log configuration for Receipt Voucher.
     */
    public static function auditLogConfig(): array
    {
        return [
            'field_labels' => [
                'unique_no'             => 'RV Number',
                'rv_date'               => 'Voucher Date',
                'voucher_type'          => 'Voucher Type',
                'account_id'            => 'Received In Account',
                'customer_id'           => 'Customer',
                'ref_bill_no'           => 'Reference Bill No',
                'bill_date'             => 'Bill Date',
                'cheque_no'             => 'Cheque / Ref No',
                'cheque_date'           => 'Cheque Date',
                'total_amount'          => 'Total Amount',
                'remarks'               => 'Remarks',
                'allow_excess_amount'   => 'Allow Excess Amount',
                'is_direct'             => 'Is Direct RV',
                'items_count'           => 'Items Count',

                // Direct Receipt Voucher Entry columns:
                'entry_account'         => 'Account (To)',
                'entry_amount'          => 'Amount',
                'entry_tax'             => 'Tax',
                'entry_tax_amount'      => 'Tax Amount',
                'entry_net_amount'      => 'Net Amount',
                'entry_description'     => 'Description',

                // Standard Receipt Voucher Document columns:
                'document_type'         => 'Document Type',
                'document_no'           => 'Document No',
                'document_amount'       => 'Document Amount',
                'document_tax'          => 'Tax',
                'document_tax_amount'   => 'Tax Amount',
                'document_net_amount'   => 'Net Amount',
                'document_line_desc'    => 'Line Description',

                // Bank Details / Advances:
                'bank_account'          => 'Bank / Cash Account',
                'bank_amount'           => 'Bank Amount',
                'bank_cheque_no'        => 'Bank Cheque No',
                'advance_consumed'      => 'Advance Consumed',
            ],
            'relation_resolvers' => [
                'voucher_type' => function ($val) {
                    $map = [
                        'bank_payment_voucher' => 'Bank Receipt Voucher (BRV)',
                        'cash_payment_voucher' => 'Cash Receipt Voucher (CRV)',
                    ];
                    return $map[$val] ?? ucwords(str_replace('_', ' ', $val));
                },
                'allow_excess_amount' => function ($val) {
                    return $val ? 'Yes' : 'No';
                },
                'is_direct' => function ($val) {
                    return $val ? 'Yes' : 'No';
                },
            ],
            'subtitle' => function ($model) {
                $parts = ['RV #' . ($model->unique_no ?? $model->id)];
                if (isset($model->total_amount)) {
                    $parts[] = 'Rs. ' . number_format((float) $model->total_amount, 2);
                }
                if (isset($model->rv_date)) {
                    $parts[] = 'Date: ' . (\Carbon\Carbon::parse($model->rv_date)->format('d M Y'));
                }
                if (isset($model->voucher_type)) {
                    $parts[] = function_exists('formatEnumValue') ? formatEnumValue($model->voucher_type) : ucfirst($model->voucher_type);
                }
                return implode(' | ', $parts);
            },
            'empty_message' => 'No edits or updates have been recorded for this receipt voucher yet.',
        ];
    }

    /**
     * Human-readable model display name for audit logs.
     */
    public function auditModelName(): string
    {
        return $this->is_direct ? 'Direct Receipt Voucher' : 'Receipt Voucher';
    }

    /**
     * Action prefix for audit logs (e.g. direct_receipt_voucher or receipt_voucher).
     */
    public function auditActionPrefix(): string
    {
        return $this->is_direct ? 'direct_receipt_voucher' : 'receipt_voucher';
    }

    public function items()
    {
        return $this->hasMany(ReceiptVoucherItem::class, 'receipt_voucher_id');
    }

    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function delivery_orders() {
        return $this->belongsToMany(DeliveryOrder::class, "delivery_order_receipt_voucher", "receipt_voucher_id", "delivery_order_id")->withPivot("amount");
    }

    public function advances()
    {
        return $this->hasMany(ReceiptVoucherAdvance::class, 'receipt_voucher_id');
    }

    public function bankDetails()
    {
        return $this->hasMany(ReceiptVoucherBankDetail::class, 'receipt_voucher_id');
    }

    protected function onApprovalComplete()
    {
        $module = $this->getApprovalModule();

        if (isset($module->approval_column, $this->{$module->approval_column})) {
            $this->update([$module->approval_column => 'approved']);
        }

        $purpose = "RV-{$this->id}-{$this->unique_no}";
        $customerAccountId = $this->customer->account_id ?? null;

        // Debit Transactions for Bank Details and corresponding Credit to Customer
        foreach ($this->bankDetails as $detail) {
            if ($detail->account_id && $detail->amount > 0) {
                // Debit the selected Bank / Cash Account
                createTransaction(
                    $detail->amount,
                    $detail->account_id,
                    $this->company_id,
                    $this->unique_no,
                    "debit",
                    "no",
                    [
                        "purpose" => $purpose,
                        "payment_against" => $this->unique_no,
                        "counter_account_id" => $customerAccountId,
                        "remarks" => $this->remarks . ($detail->cheque_no ? " (Cheque: {$detail->cheque_no})" : "")
                    ]
                );

                // Credit the Customer Account against this specific Bank / Cash Account
                if ($customerAccountId) {
                    createTransaction(
                        $detail->amount,
                        $customerAccountId,
                        $this->company_id,
                        $this->unique_no,
                        "credit",
                        "no",
                        [
                            "purpose" => $purpose,
                            "payment_against" => $this->unique_no,
                            "counter_account_id" => $detail->account_id,
                            "remarks" => $this->remarks . ($detail->cheque_no ? " (Cheque: {$detail->cheque_no})" : "")
                        ]
                    );
                }
            }
        }

        // Fallback for legacy vouchers created before bankDetails or without bank details
        $totalAdvanceConsumed = \App\Models\CustomerAdvanceAdjustment::where('voucher_no', $this->unique_no)->sum('amount');
        if ($this->bankDetails->isEmpty() && $totalAdvanceConsumed == 0 && $this->account_id) {
            createTransaction(
                $this->total_amount,
                $this->account_id,
                $this->company_id,
                $this->unique_no,
                "debit",
                "no",
                [
                    "purpose" => $purpose,
                    "payment_against" => $this->unique_no,
                    "counter_account_id" => $customerAccountId,
                    "remarks" => $this->remarks
                ]
            );

            if ($customerAccountId) {
                createTransaction(
                    $this->total_amount,
                    $customerAccountId,
                    $this->company_id,
                    $this->unique_no,
                    "credit",
                    "no",
                    [
                        "purpose" => $purpose,
                        "payment_against" => $this->unique_no,
                        "counter_account_id" => $this->account_id,
                        "remarks" => $this->remarks
                    ]
                );
            }
        }

        // Excess Amount Logic (not-allocated)
        // $notAllocatedItems = $this->items()->where('reference_type', 'not-allocated')->get();
        // foreach ($notAllocatedItems as $notAllocatedItem) {
        //     createTransaction(
        //         $notAllocatedItem->net_amount,
        //         $customerAccountId,
        //         $this->company_id,
        //         "-",
        //         "credit",
        //         "no",
        //         [
        //             "purpose" => "Extra Amount Received (Item #{$notAllocatedItem->id}) for the customer " . ($this->customer->name ?? ''),
        //             "payment_against" => $this->unique_no,
        //             "counter_account_id" => $this->account_id,
        //             "remarks" => "Customer advance created from excess payment against Receipt Voucher " . $this->unique_no,
        //             "receipt_voucher_item_id" => $notAllocatedItem->id
        //         ]
        //     );
        // }
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
        if ($this->is_direct) {
            $oldAccounts = [];
            $oldAmounts = [];
            $oldTaxes = [];
            $oldTaxAmounts = [];
            $oldNetAmounts = [];
            $oldDescriptions = [];

            $items = $this->relationLoaded('items') ? $this->items : $this->items()->get();
            foreach ($items as $oldItem) {
                $acc = Account::find($oldItem->account_id);
                $accName = $acc ? ($acc->name . ($acc->unique_no ? " ({$acc->unique_no})" : "")) : ($oldItem->account_id ? "Account #{$oldItem->account_id}" : '-');
                $tax = $oldItem->tax_id ? Tax::find($oldItem->tax_id) : null;
                $taxStr = $tax ? ($tax->name . ' (' . (float) $tax->percentage . '%)') : 'No Tax';

                $oldAccounts[] = $accName;
                $oldAmounts[] = number_format((float) $oldItem->amount, 2);
                $oldTaxes[] = $taxStr;
                $oldTaxAmounts[] = number_format((float) $oldItem->tax_amount, 2);
                $oldNetAmounts[] = number_format((float) $oldItem->net_amount, 2);
                $oldDescriptions[] = !empty($oldItem->line_desc) ? trim($oldItem->line_desc) : '-';
            }

            return [
                'entry_account'     => !empty($oldAccounts) ? implode(', ', $oldAccounts) : null,
                'entry_amount'      => !empty($oldAmounts) ? implode(', ', $oldAmounts) : null,
                'entry_tax'         => !empty($oldTaxes) ? implode(', ', $oldTaxes) : null,
                'entry_tax_amount'  => !empty($oldTaxAmounts) ? implode(', ', $oldTaxAmounts) : null,
                'entry_net_amount'  => !empty($oldNetAmounts) ? implode(', ', $oldNetAmounts) : null,
                'entry_description' => !empty($oldDescriptions) ? implode(', ', $oldDescriptions) : null,
                'items_count'       => count($oldAccounts),
            ];
        }

        $oldDocTypes = [];
        $oldDocNos = [];
        $oldDocAmounts = [];
        $oldDocTaxes = [];
        $oldDocTaxAmounts = [];
        $oldDocNetAmounts = [];
        $oldDocDescs = [];

        $items = $this->relationLoaded('items') ? $this->items : $this->items()->get();
        foreach ($items as $oldItem) {
            if ($oldItem->reference_type === 'not-allocated') continue;
            $docNo = '—';
            $docType = ucwords(str_replace(['_', '-'], ' ', (string) $oldItem->reference_type));
            if ($oldItem->reference_type === 'sale_order') {
                $docType = 'Sale Order';
                $so = SalesOrder::find($oldItem->reference_id);
                $docNo = $so ? ($so->reference_no ?? "SO-{$oldItem->reference_id}") : "SO-{$oldItem->reference_id}";
            } elseif ($oldItem->reference_type === 'sales_invoice') {
                $docType = 'Sale Invoice';
                $inv = SalesInvoice::find($oldItem->reference_id);
                $docNo = $inv ? ($inv->si_no ?? "INV-{$oldItem->reference_id}") : "INV-{$oldItem->reference_id}";
            }
            $tax = $oldItem->tax_id ? Tax::find($oldItem->tax_id) : null;
            $taxStr = $tax ? ($tax->name . ' (' . (float) $tax->percentage . '%)') : 'No Tax';

            $oldDocTypes[] = $docType;
            $oldDocNos[] = $docNo;
            $oldDocAmounts[] = number_format((float) $oldItem->amount, 2);
            $oldDocTaxes[] = $taxStr;
            $oldDocTaxAmounts[] = number_format((float) $oldItem->tax_amount, 2);
            $oldDocNetAmounts[] = number_format((float) $oldItem->net_amount, 2);
            $oldDocDescs[] = !empty($oldItem->line_desc) ? trim($oldItem->line_desc) : '-';
        }

        $advances = $this->relationLoaded('advances') ? $this->advances : $this->advances()->get();
        foreach ($advances as $oldAdv) {
            $tax = $oldAdv->tax_id ? Tax::find($oldAdv->tax_id) : null;
            $taxStr = $tax ? ($tax->name . ' (' . (float) $tax->percentage . '%)') : 'No Tax';

            $oldDocTypes[] = 'Advance';
            $oldDocNos[] = $oldAdv->adv_no ?? 'ADV';
            $oldDocAmounts[] = number_format((float) $oldAdv->amount, 2);
            $oldDocTaxes[] = $taxStr;
            $oldDocTaxAmounts[] = number_format((float) $oldAdv->tax_amount, 2);
            $oldDocNetAmounts[] = number_format((float) $oldAdv->net_amount, 2);
            $oldDocDescs[] = !empty($oldAdv->line_desc) ? trim($oldAdv->line_desc) : '-';
        }

        $oldBankAccounts = [];
        $oldBankAmounts = [];
        $oldBankCheques = [];
        $bankDetails = $this->relationLoaded('bankDetails') ? $this->bankDetails : $this->bankDetails()->get();
        foreach ($bankDetails as $oldBank) {
            $bAcc = Account::find($oldBank->account_id);
            $bName = $bAcc ? ($bAcc->name . ($bAcc->unique_no ? " ({$bAcc->unique_no})" : "")) : "Account #{$oldBank->account_id}";
            $oldBankAccounts[] = $bName;
            $oldBankAmounts[] = number_format((float) $oldBank->amount, 2);
            $oldBankCheques[] = !empty($oldBank->cheque_no) ? trim($oldBank->cheque_no) : '-';
        }

        $oldAdvAdjustments = CustomerAdvanceAdjustment::where('voucher_no', $this->unique_no)->get();
        $oldAdvancesConsumed = [];
        foreach ($oldAdvAdjustments as $adj) {
            $ca = CustomerAdvance::find($adj->customer_advance_id);
            $vNo = $ca?->voucher_no ?? "Advance #{$adj->customer_advance_id}";
            $oldAdvancesConsumed[] = "{$vNo} (Rs. " . number_format((float) $adj->amount, 2) . ")";
        }

        $totalCount = count($oldDocTypes);

        return [
            'document_type'       => !empty($oldDocTypes) ? implode(', ', $oldDocTypes) : null,
            'document_no'         => !empty($oldDocNos) ? implode(', ', $oldDocNos) : null,
            'document_amount'     => !empty($oldDocAmounts) ? implode(', ', $oldDocAmounts) : null,
            'document_tax'        => !empty($oldDocTaxes) ? implode(', ', $oldDocTaxes) : null,
            'document_tax_amount' => !empty($oldDocTaxAmounts) ? implode(', ', $oldDocTaxAmounts) : null,
            'document_net_amount' => !empty($oldDocNetAmounts) ? implode(', ', $oldDocNetAmounts) : null,
            'document_line_desc'  => !empty($oldDocDescs) ? implode(', ', $oldDocDescs) : null,
            'bank_account'        => !empty($oldBankAccounts) ? implode(', ', $oldBankAccounts) : null,
            'bank_amount'         => !empty($oldBankAmounts) ? implode(', ', $oldBankAmounts) : null,
            'bank_cheque_no'      => !empty($oldBankCheques) ? implode(', ', $oldBankCheques) : null,
            'advance_consumed'    => !empty($oldAdvancesConsumed) ? implode(', ', $oldAdvancesConsumed) : null,
            'items_count'         => $totalCount,
        ];
    }

    /**
     * Build reference snapshot from incoming request payload.
     */
    public function buildNewReferencesSnapshot(): array
    {
        $req = request();
        if (!$req) {
            return [];
        }

        if ($this->is_direct || $req->has('account')) {
            $accounts_array = $req->account ?? [];
            $amounts = $req->amount ?? [];
            $tax_ids = $req->tax_id ?? [];
            $tax_amounts = $req->tax_amount ?? [];
            $net_amounts = $req->net_amount ?? [];
            $descriptions = $req->description ?? [];

            $newAccounts = [];
            $newAmounts = [];
            $newTaxes = [];
            $newTaxAmounts = [];
            $newNetAmounts = [];
            $newDescriptions = [];

            for ($i = 0; $i < count($accounts_array); $i++) {
                if (empty($accounts_array[$i]) || empty($amounts[$i])) continue;
                $acc = Account::find($accounts_array[$i]);
                $accName = $acc ? ($acc->name . ($acc->unique_no ? " ({$acc->unique_no})" : "")) : ($accounts_array[$i] ? "Account #{$accounts_array[$i]}" : '-');
                $tax = !empty($tax_ids[$i]) ? Tax::find($tax_ids[$i]) : null;
                $taxStr = $tax ? ($tax->name . ' (' . (float) $tax->percentage . '%)') : 'No Tax';

                $newAccounts[] = $accName;
                $newAmounts[] = number_format((float) ($amounts[$i] ?? 0), 2);
                $newTaxes[] = $taxStr;
                $newTaxAmounts[] = number_format((float) ($tax_amounts[$i] ?? 0), 2);
                $newNetAmounts[] = number_format((float) ($net_amounts[$i] ?? 0), 2);
                $newDescriptions[] = !empty($descriptions[$i]) ? trim($descriptions[$i]) : '-';
            }

            return [
                'entry_account'     => !empty($newAccounts) ? implode(', ', $newAccounts) : null,
                'entry_amount'      => !empty($newAmounts) ? implode(', ', $newAmounts) : null,
                'entry_tax'         => !empty($newTaxes) ? implode(', ', $newTaxes) : null,
                'entry_tax_amount'  => !empty($newTaxAmounts) ? implode(', ', $newTaxAmounts) : null,
                'entry_net_amount'  => !empty($newNetAmounts) ? implode(', ', $newNetAmounts) : null,
                'entry_description' => !empty($newDescriptions) ? implode(', ', $newDescriptions) : null,
                'items_count'       => count($newAccounts),
            ];
        }

        $rawItems = $req->items ?? [];
        if (!is_array($rawItems)) {
            return [];
        }

        $newDocTypes = [];
        $newDocNos = [];
        $newDocAmounts = [];
        $newDocTaxes = [];
        $newDocTaxAmounts = [];
        $newDocNetAmounts = [];
        $newDocDescs = [];

        foreach ($rawItems as $item) {
            if (empty($item['reference_id']) && empty($item['reference_type'])) continue;
            $refType = $item['reference_type'] ?? '';
            $refId = $item['reference_id'] ?? null;
            $itemAmount = (float) ($item['amount'] ?? ($item['amount_display'] ?? 0));
            $taxId = $item['tax_id'] ?? null;
            $taxAmount = (float) ($item['tax_amount'] ?? 0);
            $netAmount = (float) ($item['net_amount'] ?? ($itemAmount + $taxAmount));
            $desc = $item['line_desc'] ?? '';
            $tax = $taxId ? Tax::find($taxId) : null;
            $taxStr = $tax ? ($tax->name . ' (' . (float) $tax->percentage . '%)') : 'No Tax';

            if ($refType === 'advance') {
                $docType = 'Advance';
                $docNo = $item['adv_no'] ?? 'ADV';
            } elseif ($refType === 'sale_order') {
                $docType = 'Sale Order';
                $so = SalesOrder::find($refId);
                $docNo = $so ? ($so->reference_no ?? "SO-{$refId}") : "SO-{$refId}";
            } elseif ($refType === 'sales_invoice') {
                $docType = 'Sale Invoice';
                $inv = SalesInvoice::find($refId);
                $docNo = $inv ? ($inv->si_no ?? "INV-{$refId}") : "INV-{$refId}";
            } else {
                $docType = ucwords(str_replace(['_', '-'], ' ', (string) $refType));
                $docNo = (string) $refId;
            }

            $newDocTypes[] = $docType;
            $newDocNos[] = $docNo;
            $newDocAmounts[] = number_format($itemAmount, 2);
            $newDocTaxes[] = $taxStr;
            $newDocTaxAmounts[] = number_format($taxAmount, 2);
            $newDocNetAmounts[] = number_format($netAmount, 2);
            $newDocDescs[] = !empty($desc) ? trim($desc) : '-';
        }

        $newBankAccounts = [];
        $newBankAmounts = [];
        $newBankCheques = [];
        $newAdvancesConsumed = [];

        if ($req->filled('bank_details') && is_array($req->bank_details)) {
            foreach ($req->bank_details as $detail) {
                if (!empty($detail['customer_advance_id']) && !empty($detail['amount'])) {
                    $advance = CustomerAdvance::find($detail['customer_advance_id']);
                    $vNo = $advance ? $advance->voucher_no : "Advance #{$detail['customer_advance_id']}";
                    $newAdvancesConsumed[] = "{$vNo} (Rs. " . number_format((float) $detail['amount'], 2) . ")";
                } elseif (!empty($detail['account_id']) && !empty($detail['amount'])) {
                    $bAcc = Account::find($detail['account_id']);
                    $bName = $bAcc ? ($bAcc->name . ($bAcc->unique_no ? " ({$bAcc->unique_no})" : "")) : "Account #{$detail['account_id']}";
                    $newBankAccounts[] = $bName;
                    $newBankAmounts[] = number_format((float) $detail['amount'], 2);
                    $newBankCheques[] = !empty($detail['cheque_no']) ? trim($detail['cheque_no']) : '-';
                }
            }
        }

        return [
            'document_type'       => !empty($newDocTypes) ? implode(', ', $newDocTypes) : null,
            'document_no'         => !empty($newDocNos) ? implode(', ', $newDocNos) : null,
            'document_amount'     => !empty($newDocAmounts) ? implode(', ', $newDocAmounts) : null,
            'document_tax'        => !empty($newDocTaxes) ? implode(', ', $newDocTaxes) : null,
            'document_tax_amount' => !empty($newDocTaxAmounts) ? implode(', ', $newDocTaxAmounts) : null,
            'document_net_amount' => !empty($newDocNetAmounts) ? implode(', ', $newDocNetAmounts) : null,
            'document_line_desc'  => !empty($newDocDescs) ? implode(', ', $newDocDescs) : null,
            'bank_account'        => !empty($newBankAccounts) ? implode(', ', $newBankAccounts) : null,
            'bank_amount'         => !empty($newBankAmounts) ? implode(', ', $newBankAmounts) : null,
            'bank_cheque_no'      => !empty($newBankCheques) ? implode(', ', $newBankCheques) : null,
            'advance_consumed'    => !empty($newAdvancesConsumed) ? implode(', ', $newAdvancesConsumed) : null,
            'items_count'         => count($newDocTypes),
        ];
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
