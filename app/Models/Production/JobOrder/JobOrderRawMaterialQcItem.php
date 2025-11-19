<?php

namespace App\Models\Production\JobOrder;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobOrderRawMaterialQcItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_order_raw_material_qc_id',
        'product_id',
        'arrival_sublocation_id',
        'suggested_quantity'
    ];

    public function qc()
    {
        return $this->belongsTo(JobOrderRawMaterialQc::class, 'job_order_raw_material_qc_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function sublocation()
    {
        return $this->belongsTo(ArrivalSublocation::class, 'arrival_sublocation_id');
    }

    public function parameters()
    {
        return $this->hasMany(JobOrderRawMaterialQcParameter::class);
    }
}