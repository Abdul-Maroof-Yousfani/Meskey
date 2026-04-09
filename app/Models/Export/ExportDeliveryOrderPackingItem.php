<?php

namespace App\Models\Export;

use App\Models\BagCondition;
use App\Models\BagType;
use App\Models\Master\Brands;
use App\Models\Master\Color;
use App\Models\Master\Stitching;
use App\Models\Sales\DeliveryOrder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExportDeliveryOrderPackingItem extends Model
{
    use HasFactory;

    protected $table = 'export_delivery_order_packing_items';

    protected $fillable = [
        'delivery_order_id',
        'company_location_id',
        'bag_type_id',
        'bag_condition_id',
        'bag_size',
        'no_of_bags',
        'extra_bags',
        'empty_bags',
        'total_bags',
        'total_kgs',
        'metric_tons',
        'stuffing_in_container',
        'no_of_containers',
        'brand_id',
        'bag_color_id',
        'thread_color_id',
        'stitching_id',
        'min_weight_empty_bags',
        'extra_bags_percentage',
        'fumigation_company_id',
    ];

    protected $casts = [
        'bag_size' => 'decimal:2',
        'metric_tons' => 'decimal:4',
        'stuffing_in_container' => 'decimal:2',
        'total_kgs' => 'decimal:2',
        'min_weight_empty_bags' => 'decimal:2',
        'extra_bags_percentage' => 'decimal:2',
        'fumigation_company_id' => 'array',
    ];

    /**
     * Auto calculations
     */
    protected static function booted()
    {
        static::saving(function ($item) {
            // Total KGs & Bags calculation
            $item->total_kgs = $item->metric_tons * 1000;
            if ($item->bag_size > 0 && $item->no_of_bags == 0) {
                // If user didn't enter bags but we have MT and Size
                $item->no_of_bags = $item->total_kgs / $item->bag_size;
            }
        });
    }

    public function deliveryOrder()
    {
        return $this->belongsTo(DeliveryOrder::class, 'delivery_order_id');
    }

    public function brand()
    {
        return $this->belongsTo(Brands::class);
    }

    public function bagType()
    {
        return $this->belongsTo(BagType::class);
    }

    public function bagCondition()
    {
        return $this->belongsTo(BagCondition::class);
    }

    public function bagColor()
    {
        return $this->belongsTo(Color::class);
    }

    public function subItems()
    {
        return $this->hasMany(ExportDeliveryOrderPackingSubItem::class, 'export_delivery_order_packing_item_id');
    }

    public function threadColor()
    {
        return $this->belongsTo(Color::class, 'thread_color_id');
    }

    public function stitching()
    {
        return $this->belongsTo(Stitching::class);
    }

    public function companyLocation()
    {
        return $this->belongsTo(\App\Models\Master\CompanyLocation::class);
    }
}
