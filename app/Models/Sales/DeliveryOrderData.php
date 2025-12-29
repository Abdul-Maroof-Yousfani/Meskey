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
        return $this->belongsTo(DeliveryOrder::class, 'delivery_order_id');
    }

    public function salesOrderData() {
        return $this->belongsTo(SalesOrderData::class, 'so_data_id');
    }

    public function item() {
        return $this->belongsTo(\App\Models\Product::class, 'item_id');
    }
}
