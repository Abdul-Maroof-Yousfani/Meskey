<?php

namespace App\Models\Production\JobOrder;

use App\Models\BagType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobOrderPackingSubItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_order_packing_item_id',
        'bag_product_id',
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

    public function packingItem()
    {
        return $this->belongsTo(JobOrderPackingItem::class, 'job_order_packing_item_id');
    }

    public function bagType()
    {
        return $this->belongsTo(BagType::class, 'bag_type_id');
    }
}

