<?php

namespace App\Models\Procurement\Store;

use Illuminate\Database\Eloquent\Model;
use App\Models\Product;
use App\Models\Master\Brands;
use App\Models\UnitOfMeasure;

class BagIssuanceItem extends Model
{
    protected $fillable = [
        'bag_issuance_id',
        'job_order_id',
        'item_id',
        'brand_id',
        'unit_id',
        'quantity',
        'remarks',
    ];

    public function bagIssuance()
    {
        return $this->belongsTo(BagIssuance::class);
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
