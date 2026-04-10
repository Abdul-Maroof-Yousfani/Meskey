<?php

namespace App\Models\Sales;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FirstWeighbridge extends Model
{
    use HasFactory;

    protected $table = "general_first_weighbridges";

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

    public function loadingProgramItem() {
        return $this->belongsTo(LoadingProgramItem::class, "loading_program_item_id");
    }

    public function deliveryOrder() {
        return $this->hasOneThrough(DeliveryOrder::class, LoadingProgramItem::class, 'id', 'id', 'loading_program_item_id', 'delivery_order_id');
    }

    public function truckType() {
        return $this->belongsTo(\App\Models\Master\ArrivalTruckType::class, "truck_type_id");
    }

    public function createdBy() {
        return $this->belongsTo(\App\Models\User::class, "created_by");
    }
}
