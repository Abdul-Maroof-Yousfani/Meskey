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

        $this->postStockIn();

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

    /**
     * Stock-in for the QC'd GRN line: the full received qty
     * (accepted + rejected with deduction + rejected without deduction).
     * Bags rejected without deduction physically stay in store until the supplier
     * collects them; the Rejection Return then posts the matching stock-out.
     * Used by both manual approval and auto-approval; safe to call more than once.
     */
    public function postStockIn(): ?Stock
    {
        $grnLine = $this->grn;
        $grn = $grnLine?->purchase_order_receiving;
        if (!$grnLine || !$grn) {
            return null;
        }

        $alreadyPosted = Stock::where('voucher_type', 'grn')
            ->where('type', 'stock-in')
            ->where(function ($q) use ($grnLine, $grn) {
                $q->where(function ($q) use ($grnLine) {
                    $q->where('parentable_type', PurchaseOrderReceivingData::class)
                        ->where('parentable_id', $grnLine->id);
                })->orWhere(function ($q) use ($grnLine, $grn) {
                    // Rows posted before this fix were linked to the GRN header.
                    $q->where('parentable_type', PurchaseOrderReceiving::class)
                        ->where('parentable_id', $grn->id)
                        ->where('product_id', $grnLine->item_id);
                });
            })
            ->first();

        if ($alreadyPosted) {
            return $alreadyPosted;
        }

        $qty = (float) $this->accepted_quantity
            + (float) $this->rejected_quantity
            + (float) ($this->rejection_return ?? 0);
        if ($qty <= 0) {
            return null;
        }

        $price = $qty * (float) ($grnLine->purchase_order_data->rate ?? 0);

        return Stock::create([
            'product_id' => $grnLine->item_id,
            'voucher_type' => 'grn',
            'voucher_no' => $grn->purchase_order_receiving_no,
            'qty' => $qty,
            'type' => 'stock-in',
            'narration' => 'Goods Received Note',
            'price' => $price,
            'avg_price_per_kg' => $price,
            'company_location_id' => $grn->location_id,
            'parent_id' => $grn->id,
            'parentable_type' => PurchaseOrderReceivingData::class,
            'parentable_id' => $grnLine->id,
        ]);
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

