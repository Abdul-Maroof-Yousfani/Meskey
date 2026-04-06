<?php

namespace App\Models\Procurement\Store;

use App\Models\Master\Supplier;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Models\Product;
class PurchaseBillData extends Model
{
    use HasFactory;
    protected $table = "purchase_bills_data";
    protected $guarded = [
        "id",
        "created_at",
        "updated_at"
    ];

    public function PurchaseOrderReceivingData() {
        return $this->belongsTo(PurchaseOrderReceivingData::class, "purchase_order_receiving_data_id");
    }

    public function item() {
        return $this->belongsTo(Product::class, "item_id");
    }

    public function debit_note_data() {
        return $this->hasMany(DebitNoteData::class, "purchase_bill_data_id");
    }
    
    public function purchase_bill() {
        return $this->belongsTo(PurchaseBill::class, "purchase_bill_id");
    }
    public function purchaseBill() {
        return $this->belongsTo(PurchaseBill::class, "purchase_bill_id");
    }
}
