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
        'bag_type_id',
        'bag_size',
        'no_of_bags',
        'total_kgs',
        'empty_bags',
        'empty_bag_weight',
        'total_bags',
        'bag_color_id',
        'thread_color_id',
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

