<?php

namespace App\Models\Export;

use App\Models\BagType;
use App\Models\Master\Brands;
use Illuminate\Database\Eloquent\Model;

/**
 * One packaging on an export loading slip (truck): its bags, empty bags and extra bags.
 */
class ExportLoadingSlipItem extends Model
{
    protected $table = 'export_loading_slip_items';

    protected $fillable = [
        'loading_slip_id',
        'delivery_order_id',
        'delivery_order_packing_item_id',
        'bag_type_id',
        'brand_id',
        'bag_size',
        'no_of_bags',
        'empty_bags',
        'extra_bags',
        'kilogram',
    ];

    protected $casts = [
        'bag_size' => 'decimal:2',
        'kilogram' => 'decimal:2',
    ];

    public function loadingSlip()
    {
        return $this->belongsTo(ExportLoadingSlip::class, 'loading_slip_id');
    }

    public function deliveryOrder()
    {
        return $this->belongsTo(ExportDeliveryOrder::class, 'delivery_order_id');
    }

    public function deliveryOrderPackingItem()
    {
        return $this->belongsTo(ExportDeliveryOrderPackingItem::class, 'delivery_order_packing_item_id');
    }

    public function bagType()
    {
        return $this->belongsTo(BagType::class, 'bag_type_id');
    }

    public function brand()
    {
        return $this->belongsTo(Brands::class, 'brand_id');
    }
}
