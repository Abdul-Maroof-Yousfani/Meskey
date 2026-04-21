<?php

namespace App\Models\Procurement\Store;

use App\Models\Product;
use App\Models\Master\Account\Stock;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RejectionReturnItem extends Model
{
    protected $guarded = ['id'];

    protected static function booted() {
        static::created(function($rejectionReturnItem) {
           
            $product = Product::find($rejectionReturnItem->item_id);
            $stock = Stock::create([
                "product_id" => $product->account_id,
                "voucher_type" => "qc",
                "voucher_no" => "qc",
                "qty" => $rejectionReturnItem->quantity,
                "type" => "stock-out",
                "narration" => "Rejection Return Item",
                "price" => $rejectionReturnItem->quantity * $rejectionReturnItem->rate,
                "avg_price_per_kg" => $rejectionReturnItem->quantity * $rejectionReturnItem->rate,
                'parent_id' => $rejectionReturnItem->rejectionReturn->purchase_order_data_id
            ]);


            createTransaction(
                $rejectionReturnItem->quantity * $rejectionReturnItem->rate,
                $product->account_id,
                9,
                '-',
                'credit',
                'no',
                [
                    'grn_no' => $rejectionReturnItem->rejectionReturn->grn->purchase_order_receiving_no,
                    'purpose' => 'purchase-bag-qc',
                    'against_reference_number' => $rejectionReturnItem->rejectionReturn->grn->purchase_order_receiving_no,
                    'payment_against' => "QC",
                    'remarks' => "Purchase Bag QC"
                ]
            );
        });

    }

    public function rejectionReturn(): BelongsTo
    {
        return $this->belongsTo(RejectionReturn::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'item_id');
    }
}
