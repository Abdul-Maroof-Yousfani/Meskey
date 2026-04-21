<?php

namespace App\Models\Export;

use App\Models\Master\Brands;
use App\Models\Master\Color;
use App\Models\Master\Stitching;
use App\Models\BagPacking;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExportDeliveryOrderPackingSubItem extends Model
{
    use HasFactory;

    protected $table = 'export_delivery_order_packing_sub_items';

    protected $fillable = [
        'export_delivery_order_packing_item_id',
        'bag_type_id',
        'bag_size_id',
        'no_of_primary_bags',
        'no_of_bags',
        'empty_bags',
        'extra_bags',
        'empty_bag_weight',
        'total_bags',
        'total_kgs',
        'stitching_id',
        'bag_color_id',
        'brand_id',
        'thread_color_id',
        'attachment',
    ];

    protected $casts = [
        'no_of_primary_bags' => 'integer',
        'no_of_bags' => 'integer',
        'empty_bags' => 'integer',
        'extra_bags' => 'integer',
        'empty_bag_weight' => 'decimal:2',
        'total_bags' => 'integer',
        'total_kgs' => 'decimal:2',
    ];

    public function packingItem()
    {
        return $this->belongsTo(ExportDeliveryOrderPackingItem::class, 'export_delivery_order_packing_item_id');
    }

    public function bagType()
    {
        return $this->belongsTo(\App\Models\BagType::class, 'bag_type_id');
    }

    public function bagSize()
    {
        return $this->belongsTo(BagPacking::class, 'bag_size_id');
    }

    public function bagPacking()
    {
        return $this->belongsTo(BagPacking::class, 'bag_size_id');
    }

    public function stitching()
    {
        return $this->belongsTo(Stitching::class);
    }

    public function bagColor()
    {
        return $this->belongsTo(Color::class, 'bag_color_id');
    }

    public function brand()
    {
        return $this->belongsTo(Brands::class);
    }

    public function threadColor()
    {
        return $this->belongsTo(Color::class, 'thread_color_id');
    }
}
