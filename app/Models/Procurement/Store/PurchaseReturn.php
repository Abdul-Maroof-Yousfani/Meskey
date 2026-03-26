<?php

namespace App\Models\Procurement\Store;

use App\Models\Master\CompanyLocation;
use App\Models\Master\Supplier;
use App\Models\Product;
use App\Models\User;
use App\Traits\HasApproval;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Master\Account\Stock;


class PurchaseReturn extends Model
{
    use HasFactory, HasApproval;
    protected $table = "purchase_returns";
    protected $guarded = [
        "id",
        "created_at",
        "updated_at"
    ];
    public static function booted() {
        static::updated(function($return) {
            $column = $return->getApprovalModule()->approval_column ?? 'am_approval_status';
            if ($return->isDirty($column) && $return->{$column} === 'approved') {
                foreach($return->purchase_return_data as $data) {
                    $supplier = Supplier::select("id", "account_id")->find($return->supplier_id);
                    $product = Product::select("id", "account_id")->find($data->item_id);
                    $amount = $data->net_amount;

                    if ($supplier && $supplier->account_id) {
                         createTransaction(
                            $amount,
                            $supplier->account_id,
                            6,
                            $return->pr_no,
                            'debit',
                            'no',
                            [
                                'payment_against' => "Purchase Return",
                                'remarks' => "Purchase Return"
                            ] 
                        );
                    }

                    if ($product && $product->account_id) {
                        createTransaction(
                            $amount,
                            $product->account_id,
                            6,
                            $return->pr_no,
                            'credit',
                            'no',
                            [
                                'payment_against' => "Purchase Return",
                                'remarks' => "Product is Returned"
                            ] 
                        );
                    }

                    Stock::create([
                        'product_id' => $data->item_id,
                        'voucher_type' => 'purchase_return',
                        'voucher_no' => $return->pr_no,
                        'qty' => $data->quantity,
                        'type' => 'stock-out',
                        'narration' => 'Purchase Return',
                        'price' => $amount,
                        'avg_price_per_kg' => $amount,
                    ]);
                }
            }
        });
    }



    public function supplier() 
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function purchase_return_data() {
        return $this->hasMany(PurchaseReturnData::class, 'purchase_return_id');
    }

    public function purchaseBills() {
        return $this->belongsToMany(PurchaseBill::class, 'purchase_bill_purchase_return', 'purchase_return_id', 'purchase_bill_id');
    }
    public function purchase_bill() {
        return $this->belongsToMany(PurchaseBill::class, 'purchase_bill_purchase_return', 'purchase_return_id', 'purchase_bill_id');
    }
    

    public function company_location() {
        return $this->belongsTo(CompanyLocation::class, 'company_location_id');
    }

    public function created_by_user() {
        return $this->belongsTo(User::class, 'created_by');
    }
    public function item() {
        return $this->belongsTo(Product::class, 'item_id');
    }
}
