<?php

namespace App\Models\Arrival;

use App\Models\Master\ProductSlab;
use App\Models\Master\ProductSlabType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

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
        'applied_deduction_maund',
        'relief_deduction',
    ];

    public function arrivalSamplingRequest()
    {
        return $this->belongsTo(ArrivalSamplingRequest::class, 'arrival_sampling_request_id');
    }

    public function slabType()
    {
        return $this->hasOne(ProductSlabType::class, 'id', 'product_slab_type_id');
    }

    public function productSlab()
    {
        return $this->hasOne(ProductSlab::class, 'product_slab_type_id', 'product_slab_type_id');
    }

    protected static function booted()
    {
        static::created(function ($result) {
            if ($result->arrival_sampling_request_id) {
                $samplingRequest = ArrivalSamplingRequest::find($result->arrival_sampling_request_id);
                if ($samplingRequest && is_null($samplingRequest->result_posted_at)) {
                    $samplingRequest->update([
                        'result_posted_at' => now(),
                        'done_by' => Auth::user()->id,
                    ]);
                }
            }
        });
    }
}

//9726