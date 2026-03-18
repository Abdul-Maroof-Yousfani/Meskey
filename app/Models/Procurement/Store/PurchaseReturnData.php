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

    public static function booted() {
        static::created(function($purchase_return_data) {
            $supplier = Supplier::select("id", "account_id")->find($purchase_return_data->purchase_return->supplier_id);
            $product = Product::select("id", "account_id")->find($purchase_return_data->item_id);
            $amount = $purchase_return_data->net_amount;
            
            createTransaction(
                $amount,
                $supplier->account_id,
                6,
                $purchase_return_data->purchase_return->pr_no,
                'debit',
                'no',
                [
                    'payment_against' => "Purchase Return",
                    'remarks' => "Purchase Return"
                ]  
            );


            createTransaction(
                $amount,
                $product->account_id,
                6,
                $purchase_return_data->purchase_return->pr_no,
                'credit',
                'no',
                [
                    'payment_against' => "Purchase Return",
                    'remarks' => "Product is Returned"
                ]  
            );

            Stock::create([
                'product_id' => $purchase_return_data->item_id,
                'voucher_type' => 'purchase_return',
                'voucher_no' => $purchase_return_data->purchase_return->pr_no,
                'qty' => $purchase_return_data->quantity,
                'type' => 'stock-out',
                'narration' => 'Purchase Return',
                'price' => $amount,
                'avg_price_per_kg' => $amount,
            ]);

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
