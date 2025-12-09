<?php

namespace App\Models;

use App\Models\Sales\DeliveryOrder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReceiptVoucher extends Model
{
    use HasFactory;


    public function delivery_orders() {
        return $this->belongsToMany(DeliveryOrder::class, "delivery_order_receipt_voucher", "receipt_voucher_id", "delivery_order_id")->withPivot("amount");
    }
}
