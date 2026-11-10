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
            $builder->withoutGlobalScope('sale_type')->where('loading_slips.type', 'export_loading_slip');
        });
    }

    public function deliveryOrder()
    {
        return $this->belongsTo(ExportDeliveryOrder::class, "delivery_order_id");
    }

    /**
     * Weight of the empty + extra bags on this slip.
     * Per-bag weight is "Min Weight Empty Bags (G)" from the export order's packing details.
     */
    public function extraEmptyBagsWeightInfo(): array
    {
        $item = $this->loadingProgramItem;
        $exportOrder = $this->deliveryOrder?->exportOrder
            ?? $item?->exportOrders?->first()
            ?? $item?->exportLoadingProgram?->exportOrders?->first()
            ?? $item?->exportLoadingProgram?->exportOrder;

        $perBagWeightG = (float) optional(
            $exportOrder?->packingItems->first(fn ($packingItem) => (float) $packingItem->min_weight_empty_bags > 0)
        )->min_weight_empty_bags;

        $bags = (int) $this->empty_bags + (int) $this->extra_bags;

        return [
            'bags' => $bags,
            'per_bag_weight_g' => $perBagWeightG,
            'weight_kg' => round($bags * $perBagWeightG / 1000, 2),
        ];
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
