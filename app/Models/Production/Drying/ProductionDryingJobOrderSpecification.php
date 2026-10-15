<?php

namespace App\Models\Production\Drying;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Master\ProductSlabType;

class ProductionDryingJobOrderSpecification extends Model
{
    use HasFactory;

    protected $table = 'production_drying_job_order_specifications';

    protected $fillable = [
        'production_drying_job_order_id',
        'product_slab_type_id',
        'spec_name',
        'spec_value',
        'uom',
        'value_type',
        'created_by',
        'updated_by',
    ];

    public function dryingJobOrder()
    {
        return $this->belongsTo(ProductionDryingJobOrder::class, 'production_drying_job_order_id');
    }

    public function productSlabType()
    {
        return $this->belongsTo(ProductSlabType::class, 'product_slab_type_id');
    }
}
