<?php

namespace App\Models\Sales;

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

    public function delivery_order_data() {
        return $this->hasMany(DeliveryOrderData::class);
    }

    public function receipt_vouchers() {
        return $this->belongsToMany(ReceiptVoucher::class, "delivery_order_receipt_voucher", "delivery_order_id", "receipt_voucher_id")->withPivot("amount", "receipt_voucher_id");
    }

    public function withheld_receipt_voucher() {
        return $this->belongsTo(ReceiptVoucher::class, "withhold_for_rv_id");
    }

     public function locations() {
        return $this->morphMany(Location::class, 'locationable');
    }

    public function delivery_challans() {
        return $this->belongsToMany(DeliveryChallan::class, "delivery_challan_delivery_order", "delivery_order_id", "delivery_challan_id")->withPivot("qty");
    }
}
