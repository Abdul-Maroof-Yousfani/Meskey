<?php

namespace App\Models\Sales;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FirstWeighbridge extends Model
{
    use HasFactory;

    protected $table = "sales_first_weighbridges";

    protected $guarded = ["id", "created_at", "updated_at"];

    public function deliveryOrder() {
        return $this->belongsTo(DeliveryOrder::class, "delivery_order_id");
    }

    public function truckType() {
        return $this->belongsTo(\App\Models\Master\ArrivalTruckType::class, "truck_type_id");
    }

    public function createdBy() {
        return $this->belongsTo(\App\Models\User::class, "created_by");
    }
}
