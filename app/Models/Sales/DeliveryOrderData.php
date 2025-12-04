<?php

namespace App\Models\Sales;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryOrderData extends Model
{
    use HasFactory;

    protected $table = "delivery_order_data";
    protected $guarded  = ["id", "created_at", "updated_at"];

    public function delivery_order() {
        return $this->hasOne(DeliveryOrder::class);
    }
}
