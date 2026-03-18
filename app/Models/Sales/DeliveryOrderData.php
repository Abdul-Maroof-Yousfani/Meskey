<?php

namespace App\Models\Sales;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Master\Brands;

class DeliveryOrderData extends Model
{
    use HasFactory;

    protected $table = "delivery_order_data";
    protected $guarded  = ["id", "created_at", "updated_at"];
    protected $with = ['brand'];

    public function delivery_order() {
        return $this->belongsTo(DeliveryOrder::class, 'delivery_order_id');
    }

    public function brand() {
        return $this->belongsTo(Brands::class, 'brand_id');
    }

    public function salesOrderData() {
        return $this->belongsTo(SalesOrderData::class, 'so_data_id');
    }

    public function item() {
        return $this->belongsTo(\App\Models\Product::class, 'item_id');
    }
}
