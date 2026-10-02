<?php

namespace App\Models\ProductionV2;

use App\Models\Master\ProductSlab;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductionJobOrderSpecification extends Model
{
    use HasFactory;

    protected $table = 'production_job_order_specifications';

    protected $fillable = [
        'job_order_id',
        'product_slab_type_id',
        'spec_name',
        'spec_value',
        'value_type',
        'uom',
    ];

    public function jobOrder()
    {
        return $this->belongsTo(JobOrderV2::class, 'job_order_id');
    }

    public function productSlabType()
    {
        return $this->belongsTo(ProductSlab::class, 'product_slab_type_id');
    }
}
