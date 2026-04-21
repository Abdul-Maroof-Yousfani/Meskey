<?php

namespace App\Models\Production;

use Illuminate\Database\Eloquent\Model;
use App\Models\Product;
use App\Models\Master\Brands;
use App\Models\UnitOfMeasure;

class BagRequestItem extends Model
{
    protected $fillable = [
        'bag_request_id',
        'job_order_id',
        'item_id',
        'brand_id',
        'unit_id',
        'quantity',
        'remarks',
    ];

    public function bagRequest()
    {
        return $this->belongsTo(BagRequest::class);
    }

    public function jobOrder()
    {
        return $this->belongsTo(\App\Models\Production\JobOrder\JobOrder::class, 'job_order_id');
    }

    public function item()
    {
        return $this->belongsTo(Product::class, 'item_id');
    }

    public function brand()
    {
        return $this->belongsTo(Brands::class);
    }

    public function unit()
    {
        return $this->belongsTo(UnitOfMeasure::class, 'unit_id');
    }
}
