<?php

namespace App\Models\Procurement\Store;

use App\Models\Master\Account\Stock;
use App\Models\Master\Supplier;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\Product;

class PurchaseReturnData extends Model
{
    use HasFactory;

    protected $table = "purchase_return_data";
    protected $guarded = [
        "id",
        "created_at",
        "updated_at"
    ];

    protected static function booted() {
        static::creating(function($model) {
            $return = $model->purchase_return;
            if (!$return) {
                return;
            }

            // Fetch necessary records
            $supplier = Supplier::find($return->supplier_id);
            $item     = Product::find($model->item_id);

            // Use returned amounts (NOT full bill amounts) - Very Important for Partial Return
            $netAmount      = $model->net_amount ?? 0;        // Net amount being returned
            $grossAmount    = $model->gross_amount ?? 0;      // Gross value of returned items
            $discountAmount = $model->discount_amount ?? 0;
            $deduction      = $model->deduction ?? 0;         // Allowance
            $taxAmount      = $model->tax_amount ?? 0;

            $prNo = $return->pr_no;

            // 1. Supplier Account - Debit (Reducing our liability)
            if ($supplier && $supplier->account_id && $netAmount > 0) {
                createTransaction(
                    $netAmount,
                    $supplier->account_id,
                    6,                    // voucher type
                    $prNo,
                    'debit',
                    'no',
                    [
                        'payment_against' => "Purchase Return",
                        'remarks'         => "Purchase Return Against Bill"
                    ]
                );
            }

            // 2. Discount Received - Debit
            if ($discountAmount > 0) {
                createTransaction(
                    $discountAmount,
                    getAccountDetailsByHierarchyPath("6-1")->id,   // Discount Received Account
                    6,
                    $prNo,
                    'debit',
                    'no',
                    [
                        'payment_against' => "Purchase Return",
                        'remarks'         => "Discount on Purchase Return"
                    ]
                );
            }

            // 3. Allowance / Deduction - Debit
            if ($deduction > 0) {
                createTransaction(
                    $deduction,
                    getAccountDetailsByHierarchyPath("5-2")->id,   // Allowance Account (confirm path)
                    6,
                    $prNo,
                    'debit',
                    'no',
                    [
                        'payment_against' => "Purchase Return",
                        'remarks'         => "Allowance on Purchase Return"
                    ]
                );
            }

            // 4. Item / Inventory (Jute etc.) - Credit (Reducing Inventory/Expense)
            if ($item && $item->account_id && $grossAmount > 0) {
                createTransaction(
                    $grossAmount,
                    $item->account_id,
                    6,
                    $prNo,
                    'credit',
                    'no',
                    [
                        'payment_against' => "Purchase Return",
                        'remarks'         => "Purchase Return - " . $item->name
                    ]
                );
            }

            // 5. Tax Receivable / Input Tax - Credit (Reducing Tax Claim)
            if ($taxAmount > 0) {
                createTransaction(
                    $taxAmount,
                    getAccountDetailsByHierarchyPath("2-6")->id,   // Tax Receivable Account
                    6,
                    $prNo,
                    'credit',
                    'no',
                    [
                        'payment_against' => "Purchase Return",
                        'remarks'         => "Tax on Purchase Return"
                    ]
                );
            }
        });
    }



    public function purchase_return() {
        return $this->belongsTo(PurchaseReturn::class, 'purchase_return_id');
    }

    public function purchase_bill_data() {
        return $this->belongsTo(PurchaseBillData::class, 'purchase_bill_data_id');
    }
    
    public function item() {
        return $this->belongsTo(Product::class, 'item_id');
    }
    
}
