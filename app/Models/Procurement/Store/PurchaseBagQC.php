<?php

namespace App\Models\Procurement\Store;

use App\Models\Master\Account\Stock;
use App\Models\Master\Tax;
use App\Models\Product;
use App\Traits\HasApproval;
use App\Models\Master\Supplier;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Procurement\Store\PurchaseOrderReceiving;

class PurchaseBagQC extends Model
{
    use HasFactory, HasApproval;
    protected $table = "purchase_bag_qc";
    protected $guarded = ["id", "created", "updated_at"];

    protected $attributes = [
        'deduction_per_bag' => 0
    ];

    public function bags() {
        return $this->hasMany(QCItems::class, "qc_id");
    }
    

    public function onApprovalComplete() {
        $this->am_approval_status = "approved";
        approve_qc($this);

        $itemId = $this->grn->item_id;
        $supplierId = $this->grn->purchase_order_receiving->supplier_id;
        $supplier = Supplier::find($supplierId);

        $price = ($this->accepted_quantity + $this->rejected_quantity) * $this->grn->purchase_order_data->rate;

        Stock::create([
            'product_id' => $itemId,
            'voucher_type' => 'grn',
            'voucher_no' => $this->grn->purchase_order_receiving->purchase_order_receiving_no,
            'qty' => $this->accepted_quantity + $this->rejected_quantity,
            'type' => 'stock-in',
            'narration' => 'Goods Received Note',
            'price' => $price,
            'avg_price_per_kg' => $price,
            'company_location_id' => $this->grn->purchase_order_receiving->location_id,
            'parent_id' => $this->grn->purchase_order_receiving->id,
            "parentable_type" => PurchaseOrderReceiving::class,
            "parentable_id" => $this->grn->purchase_order_receiving->id,
        ]);

        // $product = Product::select("id", "account_id")->find($itemId);
        // if($product->account_id) {
        //     createTransaction(
        //         $price,
        //         $product->account_id,
        //         8,
        //         $this->grn->purchase_order_receiving->purchase_order_receiving_no,
        //         'debit',
        //         'no',
        //         [
        //             'grn_no' => $this->grn->purchase_order_receiving->purchase_order_receiving_no,
        //             'purpose' => 'goods-receiving-note',
        //             'against_reference_number' => $this->grn->purchase_order_receiving->purchase_order_receiving_no,
        //             'payment_against' => "Goods Received Note",
        //             'remarks' => "Goods Received Note"
        //         ]  
        //     );
        // }

        // if($supplier && $supplier->account_id) {
        //     createTransaction(
        //         $price,
        //         $supplier->account_id,
        //         8,
        //         $this->grn->purchase_order_receiving->purchase_order_receiving_no,
        //         'credit',
        //         'no',
        //         [
        //             'grn_no' => $this->grn->purchase_order_receiving->purchase_order_receiving_no,
        //             'purpose' => 'goods-receiving-note',
        //             'against_reference_number' => $this->grn->purchase_order_receiving->purchase_order_receiving_no,
        //             'payment_against' => "Goods Received Note",
        //             'remarks' => "Goods Received Note"
        //         ]  
        //     );
        // }
    }

    public function scopeFilter($query)
    {
        if ($this->canUserApprove()) {
            return $query->where('is_qc_approved', 'pending');
        }

        return $query;
    }

    public function grn() {
        return $this->belongsTo(PurchaseOrderReceivingData::class, "purchase_order_receiving_data_id");
    }

}

