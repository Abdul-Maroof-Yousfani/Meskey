<?php

namespace App\Models\Production\JobOrderRequest;

use App\Models\Master\ProductSlabType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobOrderRequestSpecification extends Model
{
    use HasFactory;

    protected $table = 'job_order_request_specifications';

    protected $fillable = [
        'job_order_request_id',
        'product_slab_type_id',
        'spec_name',
        'spec_value',
        'uom',
        'value_type',
    ];

    public function jobOrderRequest()
    {
        return $this->belongsTo(JobOrderRequest::class);
    }

    public function productSlabType()
    {
        return $this->belongsTo(ProductSlabType::class);
    }
}
