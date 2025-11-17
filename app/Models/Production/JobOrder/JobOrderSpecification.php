<?php

namespace App\Models\Production\JobOrder;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Master\ProductSlab;

class JobOrderSpecification extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_order_id',
        'product_slab_id',
        'spec_name',
        'spec_value',
        'uom'
    ];

    public function jobOrder()
    {
        return $this->belongsTo(JobOrder::class);
    }

    public function productSlab()
    {
        return $this->belongsTo(ProductSlab::class);
    }
}