<?php

namespace App\Models\Arrival;

use App\Models\Master\ProductSlabType;
use App\Models\PurchaseSamplingRequest;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseSamplingResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'purchase_sampling_request_id',
        'product_slab_type_id',
        'suggested_deduction',
        'checklist_value',
        'remark',
        'product_slab_type_id',
        'applied_deduction',
        'relief_deduction',
    ];

    public function samplingRequest()
    {
        return $this->belongsTo(PurchaseSamplingRequest::class, 'purchase_sampling_request_id');
    }

    public function slabType()
    {
        return $this->hasOne(ProductSlabType::class, 'id', 'product_slab_type_id');
    }
}
