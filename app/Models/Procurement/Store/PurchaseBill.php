<?php

namespace App\Models\Procurement\Store;

use App\Http\Requests\Procurement\PurchaseRequest;
use App\Traits\HasApproval;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Procurement\Store\PurchaseOrderReceiving;
use App\Models\Procurement\Store\PurchaseOrder;
use App\Models\Procurement\Store\PurchaseBillData;

class PurchaseBill extends Model
{
    use HasFactory, HasApproval;

    protected $guarded = [
        "id",
        "created_at",
        "updated_at"
    ];

    protected $table = "purchase_bills";

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
        return $this->hasMany(PurchaseBillData::class, "purchase_bill_id");
    }
}
