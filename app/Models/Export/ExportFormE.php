<?php

namespace App\Models\Export;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExportFormE extends Model
{
    use HasFactory;

    protected $table = 'export_form_es';

    protected $fillable = [
        'export_order_id',
        'buyer_id',
        'job_order_id',
        'form_e_no',
        'form_e_date',
        'attachment',
        'total_quantity',
        'remaining_quantity',
        'input_quantity',
        'export_snapshot',
        'created_by',
    ];

    protected $casts = [
        'export_snapshot' => 'array',
    ];

    public function exportOrder()
    {
        return $this->belongsTo(\App\Models\Export\ExportOrder::class, 'export_order_id');
    }

    public function buyer()
    {
        return $this->belongsTo(\App\Models\Master\Customer::class, 'buyer_id');
    }

    public function jobOrder()
    {
        return $this->belongsTo(\App\Models\Production\JobOrder\JobOrder::class, 'job_order_id');
    }
}
