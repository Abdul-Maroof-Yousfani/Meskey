<?php

namespace App\Models\Procurement\Store;

use App\Models\Category;
use App\Models\Master\Supplier;
use App\Models\Product;
use App\Models\GrnNumber;
use App\Models\Master\Account\Stock;
use App\Models\Master\GrnNumber as MasterGrnNumber;
use App\Traits\HasApproval;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrderReceivingData extends Model
{
    use HasFactory, HasApproval;
    protected $table = "purchase_order_receiving_data";
    protected $guarded = [];


    public function purchase_order_receiving()
    {
        return $this->belongsTo(PurchaseOrderReceiving::class);
    }

    public function purchase_order_data() {
        return $this->belongsTo(PurchaseOrderData::class, "purchase_order_data_id");
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function item()
    {
        return $this->belongsTo(Product::class, 'item_id');
    }

    public function purchase_request_data()
    {
        return $this->hasOne(PurchaseRequestData::class, 'id', 'purchase_request_data_id');
    }

    /**
     * Get all GRNs for this purchase order data
     */
    public function grns(): HasMany
    {
        return $this->hasMany(MasterGrnNumber::class, 'model_id')
            ->where('model_type', 'purchase-order-data');
    }

    /**
     * Get all stocks for this purchase order data
     */
    /**
     * Get all stocks related to this purchase order data through GRN numbers
     */
    public function stocks()
    {
        $grnNumbers = $this->grns()->pluck('unique_no');
        return Stock::whereIn('voucher_no', $grnNumbers);
    }

    /**
     * Get the total received quantity for this purchase order item
     */
    public function getTotalReceivedQtyAttribute(): float
    {
        return $this->stocks->sum('qty');
    }

    /**
     * Get the remaining quantity to be received
     */
    public function getRemainingQtyAttribute(): float
    {
        return max(0, $this->qty - $this->total_received_qty);
    }

    /**
     * Check if the purchase order item is fully received
     */
    public function getIsFullyReceivedAttribute(): bool
    {
        return $this->remaining_qty <= 0;
    }
}
