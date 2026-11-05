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
        // Bags rejected without deduction were stocked in at QC approval;
        // returning them to the supplier takes them out of stock.
        static::created(function($rejectionReturnItem) {
            $rejectionReturn = $rejectionReturnItem->rejectionReturn;
            $grn = $rejectionReturn?->grn;
            $product = Product::find($rejectionReturnItem->item_id);
            $amount = $rejectionReturnItem->quantity * $rejectionReturnItem->rate;

            Stock::create([
                "product_id" => $rejectionReturnItem->item_id,
                "voucher_type" => "qc",
                "voucher_no" => $rejectionReturn?->return_no,
                "qty" => $rejectionReturnItem->quantity,
                "type" => "stock-out",
                "narration" => "Rejection Return Item",
                "price" => $amount,
                "avg_price_per_kg" => $amount,
                "company_location_id" => $grn?->location_id,
                "parent_id" => $grn?->id,
                "parentable_type" => self::class,
                "parentable_id" => $rejectionReturnItem->id,
            ]);

            if ($product && $product->account_id) {
                createTransaction(
                    $amount,
                    $product->account_id,
                    9,
                    $rejectionReturn?->return_no ?? '-',
                    'credit',
                    'no',
                    [
                        'grn_no' => $grn?->purchase_order_receiving_no,
                        'purpose' => 'purchase-bag-qc',
                        'against_reference_number' => $grn?->purchase_order_receiving_no,
                        'payment_against' => "QC",
                        'remarks' => "Purchase Bag QC"
                    ]
                );
            }
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
