<?php

namespace App\Models\Procurement\Store;

use App\Models\Master\Account\Account;
use App\Models\Master\Supplier;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DebitNoteData extends Model
{
    use HasFactory;

    protected $table = "debit_note_items_table";

    protected $fillable = [
        'debit_note_id',
        'grn_id',
        'bill_id',
        'item_id',
        'grn_qty',
        'debit_note_quantity',
        'rate',
        'amount',
        'purchase_bill_data_id',
        'am_approval_status',
        'am_change_made',
    ];

    public static function booted() {

        
    }

    public function debit_note() {
        return $this->belongsTo(DebitNote::class, "debit_note_id");
    }

    public function grn() {
        return $this->belongsTo(PurchaseOrderReceiving::class, "grn_id");
    }

    public function bill() {
        return $this->belongsTo(PurchaseBill::class, "bill_id");
    }

    public function item() {
        return $this->belongsTo(\App\Models\Product::class, "item_id");
    }
}
