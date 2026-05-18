<?php

namespace App\Models\Procurement\Store;

use App\Http\Requests\Procurement\PurchaseRequest;
use App\Models\Master\Supplier;
use App\Traits\HasApproval;
use Illuminate\Database\Eloquent\Casts\Attribute;
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


    public static function booted() {
        // static::updated(function($bill) {
        //     $column = $bill->getApprovalModule()->approval_column ?? 'am_approval_status';
        //     if ($bill->isDirty($column) && $bill->{$column} === 'approved') {
        //         foreach($bill->bill_data as $data) {
        //             $supplier = Supplier::select("id", "account_id")->find($bill->supplier_id);
        //             if ($supplier && $supplier->account_id) {
        //                  createTransaction(
        //                     $data->final_amount,
        //                     $supplier->account_id,
        //                     5,
        //                     $bill->bill_no,
        //                     'credit',
        //                     'no',
        //                     [
        //                         'payment_against' => "Purchase Bill",
        //                         'remarks' => $data->description ?? "Purchase Bill"
        //                     ] 
        //                 );
        //             }
        //         }
        //     }
        // });
    }


    protected $table = "purchase_bills";

    public function grn() {
        return $this->belongsTo(PurchaseOrderReceiving::class, "purchase_order_receiving_id");
    }

    public function supplier() {
        return $this->belongsTo(Supplier::class, "supplier_id");
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

    public function purchaseReturns() {
        return $this->belongsToMany(PurchaseReturn::class, 'purchase_bill_purchase_return', 'purchase_bill_id', 'purchase_return_id');
    }

    public function debitNotes() {
        return $this->hasMany(DebitNote::class, "bill_id");
    }

}
