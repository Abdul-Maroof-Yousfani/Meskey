<?php

namespace App\Models\Sales;

use App\Models\Master\PayType;
use App\Models\Procurement\Store\Location;
use App\Traits\HasApproval;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesOrder extends Model
{
    use HasFactory, HasApproval;

    protected $fillable = [
        "delivery_date",
        "expiry_date",
        "reference_no",
        "customer_id",
        "inquiry_id",
        "sauda_type",
        "payment_term_id",
        "company_id",
        "am_approval_status",
        "pay_type_id"
    ];

    public function sales_order_data() {
        return $this->hasMany(SalesOrderData::class, "sale_order_id");
    }


    public function sale_inquiry() {
        return $this->belongsTo(SalesInquiry::class, "inquiry_id", "id");
    }

     public function locations() {
        return $this->morphMany(Location::class, 'locationable');
    }

    public function delivery_orders() {
        return $this->hasMany(DeliveryOrder::class, "so_id");
    }

    public function delivery_order_transactions() {
        return $this->hasMany(DeliveryOrderTransaction::class, "sale_order_id");
    }

    public function pay_type() {
        return $this->belongsTo(PayType::class, "pay_type_id");
    }
    public function delivery_order_data() {
        return $this->hasOne(DeliveryOrderData::class, "so_data_id");
    }
}
