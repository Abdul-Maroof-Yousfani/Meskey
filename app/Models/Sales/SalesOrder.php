<?php

namespace App\Models\Sales;

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
        "am_approval_status"
    ];

    public function sales_order_data() {
        return $this->hasMany(SalesOrderData::class, "sale_order_id");
    }


    public function sale_inquiry() {
        return $this->belongsTo(SalesOrder::class, "inquiry_id", "id");
    }

     public function locations() {
        return $this->morphMany(Location::class, 'locationable');
    }
}
