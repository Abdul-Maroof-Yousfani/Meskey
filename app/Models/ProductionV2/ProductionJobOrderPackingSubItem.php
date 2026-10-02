<?php

namespace App\Models\ProductionV2;

use App\Models\Master\Brands;
use App\Models\Master\Color;
use App\Models\Master\Size;
use App\Models\Master\Stitching;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductionJobOrderPackingSubItem extends Model
{
    use HasFactory;

    protected $table = 'production_job_order_packing_sub_items';

    protected $fillable = [
        'job_order_packing_item_id',
        'bag_product_id',
        'bag_size_id',
        'no_of_primary_bags',
        'packing_size',
        'no_of_bags',
        'empty_bags',
        'extra_bags',
        'extra_bags_percentage',
        'empty_bag_weight',
        'total_bags',
        'total_kgs',
        'stitching_id',
        'bag_color_id',
        'brand_id',
        'thread_color_id',
        'attachment',
    ];

    public function packingItem()
    {
        return $this->belongsTo(ProductionJobOrderPackingItem::class, 'job_order_packing_item_id');
    }

    public function bagProduct()
    {
        return $this->belongsTo(Product::class, 'bag_product_id');
    }

    public function bagSize()
    {
        return $this->belongsTo(Size::class, 'bag_size_id');
    }

    public function stitching()
    {
        return $this->belongsTo(Stitching::class, 'stitching_id');
    }

    public function bagColor()
    {
        return $this->belongsTo(Color::class, 'bag_color_id');
    }

    public function brand()
    {
        return $this->belongsTo(Brands::class, 'brand_id');
    }

    public function threadColor()
    {
        return $this->belongsTo(Color::class, 'thread_color_id');
    }
}
