<?php

namespace App\Models\Export;

use App\Models\Sales\LoadingSlip;

class ExportLoadingSlip extends LoadingSlip
{
    protected $table = 'loading_slips';

    protected static function booted()
    {
        static::creating(function ($model) {
            $model->type = 'export_loading_slip';
        });

        static::updating(function ($model) {
            $model->type = 'export_loading_slip';
        });

        static::addGlobalScope('export_type', function ($builder) {
            $builder->withoutGlobalScope('sale_type')->where('type', 'export_loading_slip');
        });
    }

    public function deliveryOrder()
    {
        return $this->belongsTo(ExportDeliveryOrder::class, "delivery_order_id");
    }

    public function secondWeighbridge()
    {
        return $this->hasOne(ExportSecondWeighbridge::class, 'loading_slip_id');
    }

    public function stacks()
    {
        return $this->hasMany(ExportLoadingSlipStack::class, 'loading_slip_id');
    }

    public function hasRejectedDispatchQc(): bool
    {
        $latestDispatchQc = $this->loadingProgramItem?->exportDispatchQc;

        if (!$latestDispatchQc || $latestDispatchQc->status !== 'reject') {
            return false;
        }

        return true;
    }

    public function getLatestRejectedDispatchQc()
    {
        return $this->loadingProgramItem?->latestRejectedExportDispatchQc;
    }

    public function canBeEdited(): bool
    {
        $loadingProgramItem = $this->loadingProgramItem;

        if (!$loadingProgramItem) {
            return true;
        }

        if ($loadingProgramItem->hasAcceptedExportDispatchQc()) {
            return false;
        }

        return true;
    }
}
