<?php

namespace App\Models\Export;

use App\Models\Sales\FirstWeighbridge;

class ExportFirstWeighbridge extends FirstWeighbridge
{
    protected $table = "general_first_weighbridges";

    protected static function booted()
    {
        static::creating(function ($model) {
            $model->type = 'export_order';
        });

        static::updating(function ($model) {
            $model->type = 'export_order';
        });

        static::addGlobalScope('export_type', function ($builder) {
            $builder->where('type', 'export_order');
        });
    }

    public function deliveryOrder()
    {
        return $this->belongsTo(ExportDeliveryOrder::class, "delivery_order_id");
    }
}
