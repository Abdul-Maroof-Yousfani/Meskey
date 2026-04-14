<?php

namespace App\Models\Sales;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SecondWeighbridge extends Model
{
    use HasFactory;

    protected $table = "sales_second_weighbridges";

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

    public function loadingSlip() {
        return $this->belongsTo(LoadingSlip::class, "loading_slip_id");
    }

    public function deliveryOrder() {
        return $this->hasOneThrough(DeliveryOrder::class, LoadingSlip::class, 'id', 'id', 'loading_slip_id', 'delivery_order_id');
    }

    public function loadingProgramItem() {
        return $this->hasOneThrough(LoadingProgramItem::class, LoadingSlip::class, 'id', 'id', 'loading_slip_id', 'loading_program_item_id');
    }

    public function truckType() {
        return $this->belongsTo(\App\Models\Master\ArrivalTruckType::class, "truck_type_id");
    }

    public function createdBy() {
        return $this->belongsTo(\App\Models\User::class, "created_by");
    }

    public function items() {
        return $this->hasMany(SecondWeighbridgeItem::class, "second_weighbridge_id");
    }
}
