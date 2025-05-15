<?php

namespace App\Models\Arrival;

use App\Models\Master\ProductSlabType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseSamplingResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'arrival_sampling_request_id',
        'product_slab_type_id',
        'suggested_deduction',
        'checklist_value',
        'remark',
        'product_slab_type_id',
        'applied_deduction',
        'relief_deduction',
    ];

    public function slabType()
    {
        return $this->hasOne(ProductSlabType::class, 'id', 'product_slab_type_id');
    }
}
