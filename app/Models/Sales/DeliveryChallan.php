<?php

namespace App\Models\Sales;

use App\Traits\HasApproval;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryChallan extends Model
{
    use HasFactory, HasApproval;
    protected $fillable = [
        "customer_id",
        "reference_number",
        "dispatch_date",
        "dc_no",
        "sauda_type",
        "location_id",
        "arrival_id",
        "company_id",
        "remarks",
        "labour",
        "labour_amount",
        "transporter",
        "transporter_id",
        "transporter_amount",
        "inhouse-weighbridge",
        "weighbridge-amount",
        "created_by_id",
        "am_approval_status",
        "am_change_made"
    ];
    

    public function delivery_challan_data() {
        return $this->hasMany(DeliveryChallanData::class, "delivery_challan_id", "id");
    }


    public function delivery_order() {
        return $this->belongsToMany(DeliveryOrder::class, "delivery_challan_delivery_order", "delivery_challan_id", "delivery_order_id");
    }

    public function receivingRequest() {
        return $this->hasOne(ReceivingRequest::class, "delivery_challan_id");
    }
    
}
