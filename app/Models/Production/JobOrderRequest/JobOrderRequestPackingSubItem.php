<?php

namespace App\Models\Production\JobOrderRequest;

use App\Models\BagType;
use App\Models\Product;
use App\Models\Master\Size;
use App\Models\Master\Stitching;
use App\Models\Master\Brands;
use App\Models\Master\Color;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobOrderRequestPackingSubItem extends Model
{
    use HasFactory;

    protected $table = 'job_order_request_packing_sub_items';

    protected $fillable = [
        'job_order_request_packing_item_id',
        'bag_type_id',
        'bag_product_id',
        'bag_size_id',
        'no_of_primary_bags',
        'packing_size',
        'no_of_bags',
        'empty_bags',
        'empty_bags_percentage',
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
        return $this->belongsTo(JobOrderRequestPackingItem::class, 'job_order_request_packing_item_id');
    }

    public function bagType()
    {
        return $this->belongsTo(BagType::class, 'bag_type_id');
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
