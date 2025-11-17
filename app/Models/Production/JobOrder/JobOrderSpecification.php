<?php

namespace App\Models\Production\JobOrder;

use App\Models\Master\ProductSlabType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Master\ProductSlab;

class JobOrderSpecification extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_order_id',
        'product_slab_type_id',
        'spec_name',
        'spec_value',
        'uom',
        'value_type'
    ];

    public function jobOrder()
    {
        return $this->belongsTo(JobOrder::class);
    }

    public function productSlabType()
    {
        return $this->belongsTo(ProductSlabType::class);
    }
}