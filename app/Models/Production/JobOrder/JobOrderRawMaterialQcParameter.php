<?php

namespace App\Models\Production\JobOrder;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobOrderRawMaterialQcParameter extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_order_raw_material_qc_item_id',
        'parameter_name',
        'parameter_value',
        'uom'
    ];

    public function qcItem()
    {
        return $this->belongsTo(JobOrderRawMaterialQcItem::class);
    }
}