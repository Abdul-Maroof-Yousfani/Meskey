<?php

namespace App\Models;

use App\Models\Procurement\Store\PurchaseOrder;
use App\Models\Procurement\Store\PurchaseOrderReceiving;
use App\Models\Procurement\Store\PurchaseRequest;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bill extends Model
{
    use HasFactory;

    protected $guarded = [
        "id",
        "created_at",
        "updated_at"
    ];

    public function grn() {
        return $this->belongsTo(PurchaseOrderReceiving::class, "purchase_order_receiving_id");
    }

    public function purchase_request() {
        return $this->belongsTo(PurchaseRequest::class, "purchase_request_id");
    }

    public function purchase_order() {
        return $this->belongsTo(PurchaseOrder::class, "purchase_order_id");
    }

    public function bill_data() {
        return $this->hasMany(BillData::class, "bill_id");
    }
}
