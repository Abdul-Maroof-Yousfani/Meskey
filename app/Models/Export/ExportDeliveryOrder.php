<?php

namespace App\Models\Export;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExportDeliveryOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'export_order_id',
        'buyer_id',
        'export_form_e_id',
        'remarks',
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

    public function exportFormE()
    {
        return $this->belongsTo(\App\Models\Export\ExportFormE::class, 'export_form_e_id');
    }
}
