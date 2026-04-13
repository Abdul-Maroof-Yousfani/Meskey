<?php

namespace App\Models\Sales;

use App\Models\Master\Customer;
use App\Models\Procurement\Store\Location;
use App\Models\ReceiptVoucher;
use App\Traits\HasApproval;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryOrder extends Model
{
    use HasFactory, HasApproval;

    protected $table = "delivery_order";
    protected $guarded = ["id", "created_at", "updated_at"];

    protected static function booted()
    {
        static::creating(function ($model) {
            $model->type = 'sale_order';
        });

        static::updating(function ($model) {
            $model->type = 'sale_order';
        });

        static::addGlobalScope('sale_type', function ($builder) {
            $builder->where('type', 'sale_order');
        });
    }

    public function delivery_order_data()
    {
        return $this->hasMany(DeliveryOrderData::class);
    }

    public function receipt_vouchers()
    {
        return $this->belongsToMany(ReceiptVoucher::class, "delivery_order_receipt_voucher", "delivery_order_id", "receipt_voucher_id")->withPivot("amount", "receipt_voucher_id", "receipt_voucher_advance_id", "withhold_amount");
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function withheld_receipt_voucher()
    {
        return $this->belongsTo(ReceiptVoucher::class, "withhold_for_rv_id");
    }

    public function locations()
    {
        return $this->morphMany(Location::class, 'locationable');
    }

    public function delivery_challans()
    {
        return $this->belongsToMany(DeliveryChallan::class, "delivery_challan_delivery_order", "delivery_order_id", "delivery_challan_id")->withPivot("qty");
    }
    public function firstWeighbridge()
    {
        return $this->hasOne(FirstWeighbridge::class, "delivery_order_id");
    }

    public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class, "so_id");
    }

    public function arrivalLocation()
    {
        return $this->belongsTo(\App\Models\Master\ArrivalLocation::class, "arrival_location_id");
    }

    public function subArrivalLocation()
    {
        return $this->belongsTo(\App\Models\Master\ArrivalSubLocation::class, "sub_arrival_location_id");
    }

    public function secondWeighbridge()
    {
        return $this->hasOneThrough(
            LoadingProgram::class,    // The original model (replace with correct class name)
            LoadingSlip::class,
            'delivery_order_id',      // First foreign key: loading_slips.delivery_order_id references delivery_orders.id
            'loading_slip_id',        // Second foreign key: ??? Wait — this needs fixing based on your logic
            'id',                     // Local key on DeliveryOrder (delivery_orders.id)
            'id'                      // Local key on the far model? No — this doesn't match
        );
    }

    public function saleSecondWeighbridge()
    {
        return $this->hasMany(SecondWeighbridgeItem::class, "delivery_order_id");
    }

    public function loadingProgram()
    {
        return $this->hasOne(LoadingProgram::class, "delivery_order_id");
    }

    public function loadingSlips()
    {
        return $this->hasMany(LoadingSlip::class, "delivery_order_id");
    }

    public function loadingProgramItems()
    {
        return $this->hasMany(LoadingProgramItem::class, "delivery_order_id");
    }
    // public function exportOrder()
    // {
    //     return $this->belongsTo(\App\Models\Export\ExportOrder::class, 'export_order_id');
    // }

    // public function exportFormE()
    // {
    //     return $this->belongsTo(\App\Models\Export\ExportFormE::class, 'export_form_e_id');
    // }

    // public function exportPackingItems()
    // {
    //     return $this->hasMany(\App\Models\Export\ExportDeliveryOrderPackingItem::class, 'delivery_order_id');
    // }

    /**
     * Override createApprovalRows from HasApproval trait to handle duplicates safely
     */
    public function createApprovalRows()
    {
        $module = $this->getApprovalModule();
        if (!$module) {
            return;
        }

        $currentCycle = $this->getCurrentApprovalCycle();

        foreach ($module->roles as $moduleRole) {
            \App\Models\ApprovalsModule\ApprovalRow::updateOrCreate(
                [
                    'module_id' => $module->id,
                    'record_id' => $this->id,
                    'role_id' => $moduleRole->role_id,
                    'approval_cycle' => $currentCycle,
                ],
                [
                    'required_count' => $moduleRole->approval_count,
                    'current_count' => 0,
                    'status' => 'pending'
                ]
            );
        }
    }
}
