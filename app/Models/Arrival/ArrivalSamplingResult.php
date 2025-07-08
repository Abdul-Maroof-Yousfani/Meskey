<?php

namespace App\Models\Arrival;

use App\Models\Master\ProductSlab;
use App\Models\Master\ProductSlabType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ArrivalSamplingResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'arrival_sampling_request_id',
        'product_slab_type_id',
        'suggested_deduction',
        'checklist_value',
        'remark',
        'applied_deduction',
        'relief_deduction',
    ];

    public function slabType()
    {
        return $this->hasOne(ProductSlabType::class, 'id', 'product_slab_type_id');
    }

    public function productSlab()
    {
        return $this->hasOne(ProductSlab::class, 'product_slab_type_id', 'product_slab_type_id');
    }
}
