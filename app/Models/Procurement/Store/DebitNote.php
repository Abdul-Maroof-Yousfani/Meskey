<?php

namespace App\Models\Procurement\Store;

use App\Models\Master\Supplier;
use App\Models\Product;
use App\Traits\HasApproval;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DebitNote extends Model
{
    use HasFactory, HasApproval;

    protected $fillable = [
        'grn_id',
        'bill_id',
        'reference_number',
        'transaction_date',
        'created_by',
        'am_approval_status',
        'am_change_made',
    ];

    protected $table = "debit_notes";


    public static function booted() {
        static::deleting(function($debit_note) {

            // foreach ($debit_note->debit_note_data as $debit_note_data) {
            //     $purchase_bill_data = PurchaseBillData::find($debit_note_data->purchase_bill_data_id);
            //     $grn_data = $purchase_bill_data->PurchaseOrderReceivingData;
            //     $grn_data->qty += $debit_note_data->debit_note_quantity;
            //     $grn_data->save();
            // }
        });

        static::updated(function($debit_note) {

            if($debit_note->wasChanged('am_approval_status') && $debit_note->am_approval_status == "approved") {
                foreach ($debit_note->debit_note_data as $debit_note_data) {
                    $supplier = Supplier::select("id", "account_id")->find($debit_note_data->grn->supplier_id);
                    $inventory_account_id = 28;

                    $product = Product::select("id", "account_id")->find($debit_note_data->item->id);
                    $inventory = \App\Models\Master\Account\Account::find($product->account_id);
                    $purchase_bill_data = PurchaseBillData::find($debit_note_data->purchase_bill_data_id);

                    // Decreasing quantity of GRN, due to adjustment in debit note
                    $grn_data = $purchase_bill_data->PurchaseOrderReceivingData;
                    $grn_data->qty -= $debit_note_data->debit_note_quantity;
                    $grn_data->save();

                    createTransaction(
                        $debit_note_data->amount,
                        $supplier->account_id,
                        7,
                        $debit_note_data->debit_note->reference_number,
                        'debit',
                        'no',
                        [
                            'payment_against' => "Debit Note",
                            'remarks' => "Debit Note"
                        ]
                    );


                    createTransaction(
                        $debit_note_data->amount,
                        $inventory->id,
                        7,
                        $debit_note_data->debit_note->reference_number,
                        'credit',
                        'no',
                        [
                            'payment_against' => "Debit Note",
                            'remarks' => "Debit Note"
                        ]  
                    );
                }
            }
        });
    }

    public function debit_note_data() {
        return $this->hasMany(DebitNoteData::class, "debit_note_id");
    }
        
    public function grn() {
        return $this->belongsTo(PurchaseOrderReceiving::class, "grn_id");
    }

    public function bill() {
        return $this->belongsTo(PurchaseBill::class, "bill_id");
    }

    public function created_by_user() {
        return $this->belongsTo(\App\Models\User::class, "created_by");
    }
}
