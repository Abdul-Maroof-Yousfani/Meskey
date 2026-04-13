<?php

namespace App\Models\Export;

use App\Models\Master\Customer;
use App\Models\Procurement\Store\Location;
use App\Models\Sales\DeliveryOrder;
use App\Models\Sales\LoadingProgramItem;
use App\Traits\HasApproval;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ExportDeliveryOrder extends DeliveryOrder
{
    use HasFactory, HasApproval;
    protected $table = "delivery_order";
    protected $guarded = ["id", "created_at", "updated_at"];

    /**
     * Scope for export type records
     */
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

    // public function customer()
    // {
    //     return $this->belongsTo(Customer::class, 'customer_id');
    // }

    public function exportFormE()
    {
        return $this->belongsTo(\App\Models\Export\ExportFormE::class, 'export_form_e_id');
    }

    // public function locations()
    // {
    //     return $this->morphMany(Location::class, 'locationable');
    // }

    // public function arrivalLocation()
    // {
    //     return $this->belongsTo(\App\Models\Master\ArrivalLocation::class, "arrival_location_id");
    // }

    // public function subArrivalLocation()
    // {
    //     return $this->belongsTo(\App\Models\Master\ArrivalSubLocation::class, "sub_arrival_location_id");
    // }

    // public function loadingProgram()
    // {
    //     return $this->hasOne(ExportLoadingProgram::class, "delivery_order_id");
    // }

    // public function loadingProgramItems()
    // {
    //     return $this->hasMany(LoadingProgramItem::class, "delivery_order_id");
    // }
    public function exportOrder()
    {
        return $this->belongsTo(\App\Models\Export\ExportOrder::class, 'export_order_id');
    }

    public function exportPackingItems()
    {
        return $this->hasMany(\App\Models\Export\ExportDeliveryOrderPackingItem::class, 'delivery_order_id');
    }

    public function firstWeighbridge()
    {
        return $this->hasOne(ExportFirstWeighbridge::class, "delivery_order_id");
    }

    /**
     * Override createApprovalRows from HasApproval trait to handle duplicates safely
     */
    // public function createApprovalRows()
    // {
    //     $module = $this->getApprovalModule();
    //     if (!$module) {
    //         return;
    //     }

    //     $currentCycle = $this->getCurrentApprovalCycle();

    //     foreach ($module->roles as $moduleRole) {
    //         \App\Models\ApprovalsModule\ApprovalRow::updateOrCreate(
    //             [
    //                 'module_id' => $module->id,
    //                 'record_id' => $this->id,
    //                 'role_id' => $moduleRole->role_id,
    //                 'approval_cycle' => $currentCycle,
    //             ],
    //             [
    //                 'required_count' => $moduleRole->approval_count,
    //                 'current_count' => 0,
    //                 'status' => 'pending'
    //             ]
    //         );
    //     }
    // }
}
