<?php

namespace App\Models\Export;

use App\Models\Sales\DeliveryOrder;

class ExportDeliveryOrder extends DeliveryOrder
{
    protected $table = "delivery_order";

    /**
     * Scope for export type records
     */
    protected static function booted()
    {
        static::creating(function ($model) {
            $model->type = 'export_order';
        });

        static::addGlobalScope('export_type', function ($builder) {
            $builder->where('type', 'export_order');
        });
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
