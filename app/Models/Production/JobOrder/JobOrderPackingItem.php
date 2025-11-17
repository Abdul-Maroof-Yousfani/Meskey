<?php

namespace App\Models\Production\JobOrder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobOrderPackingItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_order_id',
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
        'min_weight_empty_bags'
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            // Auto calculate totals
            $model->total_bags = $model->no_of_bags + $model->extra_bags + $model->empty_bags;
            $model->total_kgs = $model->no_of_bags * $model->bag_size;
            $model->metric_tons = $model->total_kgs / 1000;
        });
    }

    public function jobOrder()
    {
        return $this->belongsTo(JobOrder::class);
    }
}