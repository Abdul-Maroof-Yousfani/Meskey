<?php

namespace App\Models\Procurement\Store;

use App\Models\Master\Account\Stock;
use App\Models\Master\Tax;
use App\Models\Product;
use App\Traits\HasApproval;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseBagQC extends Model
{
    use HasFactory, HasApproval;
    protected $table = "purchase_bag_qc";
    protected $guarded = ["id", "created", "updated_at"];

    protected $attributes = [
        'deduction_per_bag' => 0
    ];

    public function bags() {
        return $this->hasMany(QCItems::class, "qc_id");
    }
    
    public static function booted() {
        static::updated(function($bag_qc) {
            if($bag_qc->wasChanged('am_approval_status') && $bag_qc->am_approval_status == "approved") {
        
                $rate = $bag_qc?->grn?->purchase_order_data?->rate ?? 0;
                $qty = $bag_qc?->grn?->purchase_order_data?->qty ?? 0;
                
                
                $product = Product::select("id", "account_id")->find($bag_qc->grn->item_id);
                
                if(!$rate) {
                    return;
                }

                if(!$product) {
                    return;
                }


                $stock = Stock::create([
                    "product_id" => $product->account_id,
                    "voucher_type" => "qc",
                    "voucher_no" => "qc",
                    "qty" => $bag_qc->rejected_quantity,
                    "type" => "stock-out",
                    "narration" => "Qc Item rejection",
                    "price" => $bag_qc->rejected_quantity * $rate,
                    "avg_price_per_kg" => $bag_qc->rejected_quantity * $rate,
                    'parent_id' => $bag_qc->grn->purchase_order_data_id
                ]);

                createTransaction(
                    $bag_qc->rejected_quantity * $rate,
                    $product->account_id,
                    9,
                    '-',
                    'credit',
                    'no',
                    [
                        'payment_against' => "Againt QC",
                        'remarks' => "Items rejected are returning"
                    ]  
                );

                // $stock = Stock::where("product_id", $bag_qc->grn->item_id)
                //                 ->where("voucher_type", "grn")
                //                 ->where("voucher_no", $bag_qc->grn->purchase_order_receiving->purchase_order_receiving_no)
                //                 ->first();
                
                // $stock->qty = $bag_qc->accepted_quantity;
                // $stock->price = $bag_qc->accepted_quantity * $rate;
                // $stock->avg_price_per_kg = $bag_qc->accepted_quantity * $rate;
                // $stock->save();
            }
        });
    }

    public function scopeFilter($query)
    {
        if ($this->canUserApprove()) {
            return $query->where('is_qc_approved', 'pending');
        }

        return $query;
    }

    public function grn() {
        return $this->belongsTo(PurchaseOrderReceivingData::class, "purchase_order_receiving_data_id");
    }

}

