<?php

namespace App\Models\Export;

use App\Models\Sales\SecondWeighbridge;

class ExportSecondWeighbridge extends SecondWeighbridge
{
    protected $table = 'sales_second_weighbridges';

    protected static function booted()
    {
        static::creating(function ($model) {
            $model->type = 'export_order';
        });

        static::updating(function ($model) {
            $model->type = 'export_order';
        });

        static::addGlobalScope('export_type', function ($builder) {
            $builder->withoutGlobalScope('sale_type')->where('type', 'export_order');
        });
    }

    public function loadingSlip()
    {
        return $this->belongsTo(ExportLoadingSlip::class, 'loading_slip_id');
    }

    public function deliveryOrder()
    {
        return $this->hasOneThrough(ExportDeliveryOrder::class, ExportLoadingSlip::class, 'id', 'id', 'loading_slip_id', 'delivery_order_id');
    }

    public function loadingProgramItem()
    {
        return $this->hasOneThrough(
            \App\Models\Sales\LoadingProgramItem::class,
            ExportLoadingSlip::class,
            'id',
            'id',
            'loading_slip_id',
            'loading_program_item_id'
        );
    }
}
