<?php

namespace App\Models\Sales;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SecondWeighbridgeItem extends Model
{
    use HasFactory;

    protected $table = "sales_second_weighbridge_items";
    protected $guarded = ["id", "created_at", "updated_at"];

    public function secondWeighbridge()
    {
        return $this->belongsTo(SecondWeighbridge::class, "second_weighbridge_id");
    }

    public function deliveryOrder()
    {
        return $this->belongsTo(DeliveryOrder::class, "delivery_order_id");
    }
}
