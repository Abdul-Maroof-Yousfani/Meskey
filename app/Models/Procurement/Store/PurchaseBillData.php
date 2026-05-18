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

    protected static function booted() {
        static::creating(function($model) {
            // getAccountDetailsByHierarchyPath

                $bill = $model->purchase_bill;
                $supplier = Supplier::find($bill->supplier_id);
                $item = Product::find($model->item_id);

                if($supplier && $supplier->account_id) {
                    createTransaction(
                        $model->final_amount,
                        $supplier->account_id,
                        5,
                        $model->purchase_bill->bill_no,
                        'credit',
                        'no',
                        [
                            'payment_against' => "Purchase Bill",
                            'remarks' => $data->description ?? "Purchase Bill"
                        ] 
                    );
                }

                 if($model->discount_amount > 0) {
                    createTransaction(
                        $model->discount_amount,
                        getAccountDetailsByHierarchyPath("6-1")->id,
                        5,
                        $model->purchase_bill->bill_no,
                        'credit',
                        'no',
                        [
                            'payment_against' => "Purchase Bill",
                            'remarks' => $data->description ?? "Purchase Bill"
                        ] 
                    );
                }

                if($model->deduction > 0) {
                    createTransaction(
                        $model->deduction,
                        getAccountDetailsByHierarchyPath("5-2")->id,
                        5,
                        $model->purchase_bill->bill_no,
                        'credit',
                        'no',
                        [
                            'payment_against' => "Purchase Bill",
                            'remarks' => $data->description ?? "Purchase Bill"
                        ] 
                    );
                }


                if($item && $item->account_id) {
                    createTransaction(
                        $model->gross_amount,
                        $item->account_id,
                        5,
                        $model->purchase_bill->bill_no,
                        'debit',
                        'no',
                        [
                            'payment_against' => "Purchase Bill",
                            'remarks' => $data->description ?? "Purchase Bill"
                        ] 
                    );
                }

               
                if($model->tax_amount > 0) {
                    createTransaction(
                        $model->tax_amount,
                        getAccountDetailsByHierarchyPath("2-6")->id,
                        5,
                        $model->purchase_bill->bill_no,
                        'debit',
                        'no',
                        [
                            'payment_against' => "Purchase Bill",
                            'remarks' => $data->description ?? "Purchase Bill"
                        ] 
                    );
                }

        
        });
    }

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
