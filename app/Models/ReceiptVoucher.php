<?php

namespace App\Models;

use App\Models\Master\Account\Account;
use App\Models\Master\Customer;
use App\Models\Sales\DeliveryOrder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasApproval;

class ReceiptVoucher extends Model
{
    use HasFactory, SoftDeletes, HasApproval;

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

        // Debit Transactions for Bank Details
        foreach ($this->bankDetails as $detail) {
            if ($detail->account_id) {
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
            }
        }

        // Generate Credit transaction to Customer
        $totalAdvanceConsumed = \App\Models\CustomerAdvanceAdjustment::where('voucher_no', $this->unique_no)->sum('amount');
        $creditAmount = $this->total_amount - $totalAdvanceConsumed;

        // Also if no bank details, we used to create a single debit transaction
        if ($this->bankDetails->isEmpty() && $totalAdvanceConsumed == 0) {
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
        }

        if ($creditAmount > 0) {
            createTransaction(
                $creditAmount,
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
}
