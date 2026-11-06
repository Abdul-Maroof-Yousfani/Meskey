<?php

namespace App\Models\Sales;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Master\Brand as Brands;
use App\Models\Master\Transporter;

class LoadingProgramItem extends Model
{
    use HasFactory;

    protected $table = 'loading_program_items';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    public function loadingProgram()
    {
        return $this->belongsTo(LoadingProgram::class)->withoutGlobalScopes();
    }

    public function exportLoadingProgram()
    {
        return $this->belongsTo(\App\Models\Export\ExportLoadingProgram::class, 'loading_program_id');
    }

    public function saleOrders()
    {
        return $this->belongsToMany(SalesOrder::class, 'loading_program_item_sale_order', 'loading_program_item_id', 'sale_order_id')->withTimestamps();
    }
    
    public function exportOrders()
    {
        return $this->belongsToMany(\App\Models\Export\ExportOrder::class, 'loading_program_item_export_order', 'loading_program_item_id', 'export_order_id')->withTimestamps();
    }
    
    public function deliveryOrders()
    {
        return $this->belongsToMany(DeliveryOrder::class, 'loading_program_item_delivery_order', 'loading_program_item_id', 'delivery_order_id')
            ->withoutGlobalScopes()
            ->withTimestamps();
    }

    public function arrivalLocation()
    {
        return $this->belongsTo(\App\Models\Master\ArrivalLocation::class);
    }

    public function subArrivalLocation()
    {
        return $this->belongsTo(\App\Models\Master\ArrivalSubLocation::class);
    }

    public function brand()
    {
        return $this->belongsTo(\App\Models\Master\Brands::class, "brand_id");
    }

    public function firstWeighbridge()
    {
        return $this->hasOne(\App\Models\Sales\FirstWeighbridge::class, "loading_program_item_id");
    }

    public function exportFirstWeighbridge()
    {
        return $this->hasOne(\App\Models\Export\ExportFirstWeighbridge::class, "loading_program_item_id");
    }
    public function salesQc() {
        return $this->hasOne(\App\Models\Sales\SalesQc::class);
    }

    public function exportQc() {
        return $this->hasOne(\App\Models\Export\ExportQc::class);
    }

    public function exportDispatchQcs() {
        return $this->hasMany(\App\Models\Export\ExportDispatchQc::class);
    }

    public function exportDispatchQc() {
        return $this->hasOne(\App\Models\Export\ExportDispatchQc::class)->latestOfMany();
    }

    public function acceptedExportDispatchQc() {
        return $this->hasOne(\App\Models\Export\ExportDispatchQc::class)
            ->where('status', 'accept')
            ->latestOfMany();
    }

    public function latestRejectedExportDispatchQc() {
        return $this->hasOne(\App\Models\Export\ExportDispatchQc::class)
            ->where('status', 'reject')
            ->latestOfMany();
    }

    public function hasAcceptedExportDispatchQc(): bool {
        return $this->exportDispatchQcs()->where('status', 'accept')->exists();
    }
    
    /**
     * Get all dispatch QCs for this ticket (supports multiple QCs after rejections)
     */
    public function dispatchQcs() {
        return $this->hasMany(\App\Models\Sales\DispatchQc::class);
    }
    
    /**
     * Get the latest dispatch QC (for backward compatibility)
     */
    public function dispatchQc() {
        return $this->hasOne(\App\Models\Sales\DispatchQc::class)->latestOfMany();
    }
    
    /**
     * Get the latest accepted dispatch QC
     */
    public function acceptedDispatchQc() {
        return $this->hasOne(\App\Models\Sales\DispatchQc::class)
            ->where('status', 'accept')
            ->latestOfMany();
    }
    
    /**
     * Get the latest rejected dispatch QC
     */
    public function latestRejectedDispatchQc() {
        return $this->hasOne(\App\Models\Sales\DispatchQc::class)
            ->where('status', 'reject')
            ->latestOfMany();
    }
    
    /**
     * Check if this ticket has an accepted dispatch QC
     */
    public function hasAcceptedDispatchQc(): bool {
        return $this->dispatchQcs()->where('status', 'accept')->exists();
    }
    
    /**
     * Check if a new dispatch QC can be created for this ticket
     * (only if no accepted QC exists)
     */
    public function canCreateNewDispatchQc(): bool {
        return !$this->hasAcceptedDispatchQc();
    }

    public function loadingSlip() {
        return $this->hasOne(\App\Models\Sales\LoadingSlip::class);
    }

    public function exportLoadingSlip() {
        return $this->hasOne(\App\Models\Export\ExportLoadingSlip::class, "loading_program_item_id");
    }

    public function secondWeighbridge()
    {
        return $this->hasOneThrough(
            \App\Models\Sales\SecondWeighbridge::class,
            \App\Models\Sales\LoadingSlip::class,
            'loading_program_item_id',
            'loading_slip_id',
            'id',
            'id'
        );
    }

    public function exportSecondWeighbridge()
    {
        return $this->hasOneThrough(
            \App\Models\Export\ExportSecondWeighbridge::class,
            \App\Models\Export\ExportLoadingSlip::class,
            'loading_program_item_id',
            'loading_slip_id',
            'id',
            'id'
        );
    }

    public function delivery_challan_data() {
        return $this->hasOne(DeliveryChallanData::class, "ticket_id");
    }

    public function outerItems()
    {
        return $this->hasMany(\App\Models\Export\ExportOuterItem::class, 'loading_program_item_id');
    }

    public function transporter()
    {
        return $this->belongsTo(Transporter::class);
    }

    public function hasClosedSaleOrder(): bool
    {
        $closedStatuses = ['close-contract-due-to-market-down', 'close-with-market-rate-penalty', 'closed', 'close'];
        $cancelledStatuses = ['cancelled', 'closed'];

        // Direct linked sale orders
        if ($this->saleOrders()->where(function ($q) use ($closedStatuses, $cancelledStatuses) {
            $q->whereIn('contract_status', $closedStatuses)->orWhereIn('status', $cancelledStatuses);
        })->exists()) {
            return true;
        }

        // Through delivery orders
        if ($this->deliveryOrders()->whereHas('salesOrder', function ($q) use ($closedStatuses, $cancelledStatuses) {
            $q->whereIn('contract_status', $closedStatuses)->orWhereIn('status', $cancelledStatuses);
        })->exists()) {
            return true;
        }

        // Through loading program
        if ($this->loadingProgram) {
            if ($this->loadingProgram->saleOrder && $this->loadingProgram->saleOrder->isClosed()) {
                return true;
            }
            if ($this->loadingProgram->deliveryOrder && $this->loadingProgram->deliveryOrder->salesOrder && $this->loadingProgram->deliveryOrder->salesOrder->isClosed()) {
                return true;
            }
        }

        return false;
    }

    /** DOs this truck is loading against. */
    public function linkedDeliveryOrderIds(): array
    {
        $ids = \Illuminate\Support\Facades\DB::table('loading_program_item_delivery_order')
            ->where('loading_program_item_id', $this->id)
            ->pluck('delivery_order_id')
            ->all();

        if (empty($ids) && $this->delivery_order_id) {
            $ids = [$this->delivery_order_id];
        }
        if (empty($ids) && $this->loading_program_id) {
            $headerDo = \Illuminate\Support\Facades\DB::table('loading_programs')->where('id', $this->loading_program_id)->value('delivery_order_id');
            $ids = $headerDo ? [$headerDo] : [];
        }

        return $ids;
    }

    /** With every DO of the truck closed, the truck stops at whichever step it has reached before Second Weighbridge. */
    public function allDeliveryOrdersClosed(): bool
    {
        $ids = $this->linkedDeliveryOrderIds();
        if (empty($ids)) {
            return false;
        }

        return !\Illuminate\Support\Facades\DB::table('delivery_order')
            ->whereIn('id', $ids)
            ->where('do_status', '!=', 'closed')
            ->exists();
    }

    public function hasAnyClosedDeliveryOrder(): bool
    {
        $ids = $this->linkedDeliveryOrderIds();

        return !empty($ids) && \Illuminate\Support\Facades\DB::table('delivery_order')
            ->whereIn('id', $ids)
            ->where('do_status', 'closed')
            ->exists();
    }

    /** Qty this truck can still load from its open DOs. */
    public function openQtyBalance(): float
    {
        $slip = new LoadingSlip();
        $slip->setRelation('loadingProgramItem', $this);

        return (float) get_second_weighbridge_balance($slip);
    }

    public function scopeWhereDoesntHaveClosedSaleOrder($query)
    {
        $closedStatuses = ['close-contract-due-to-market-down', 'close-with-market-rate-penalty', 'closed', 'close'];
        $cancelledStatuses = ['cancelled', 'closed'];

        return $query->whereDoesntHave('saleOrders', function ($q) use ($closedStatuses, $cancelledStatuses) {
            $q->whereIn('contract_status', $closedStatuses)->orWhereIn('status', $cancelledStatuses);
        })->whereDoesntHave('deliveryOrders.salesOrder', function ($q) use ($closedStatuses, $cancelledStatuses) {
            $q->whereIn('contract_status', $closedStatuses)->orWhereIn('status', $cancelledStatuses);
        })->whereDoesntHave('loadingProgram.saleOrder', function ($q) use ($closedStatuses, $cancelledStatuses) {
            $q->whereIn('contract_status', $closedStatuses)->orWhereIn('status', $cancelledStatuses);
        })->whereDoesntHave('loadingProgram.deliveryOrder.salesOrder', function ($q) use ($closedStatuses, $cancelledStatuses) {
            $q->whereIn('contract_status', $closedStatuses)->orWhereIn('status', $cancelledStatuses);
        });
    }
    public function scopeWhereDeliveryStatusNotClosed($query)
    {
        return $query->whereHas('deliveryOrders', function ($q) {
            $q->where('do_status', '!=', 'closed');
        });
    }
  
}
