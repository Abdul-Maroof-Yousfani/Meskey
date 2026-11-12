<?php

namespace App\Models\Procurement\Store;

use App\Http\Requests\Procurement\PurchaseRequest;
use App\Models\Master\Supplier;
use App\Services\StoreLedgerService;
use App\Traits\HasApproval;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Procurement\Store\PurchaseOrderReceiving;
use App\Models\Procurement\Store\PurchaseOrder;
use App\Models\Procurement\Store\PurchaseBillData;

class PurchaseBill extends Model
{
    use HasFactory, HasApproval {
        onApprovalComplete as protected traitOnApprovalComplete;
    }

    /** Ledger is posted only when the bill is fully approved. */
    protected function onApprovalComplete()
    {
        $this->traitOnApprovalComplete();
        app(StoreLedgerService::class)->postPurchaseBill($this);
    }

    public function validateBeforeApproval(): ?string
    {
        foreach ($this->bill_data as $line) {
            $qc = $line->PurchaseOrderReceivingData?->qc;
            if ($qc && $qc->am_approval_status !== 'approved') {
                return "QC of item #{$line->item_id} is not approved yet. Approve the QC before the bill.";
            }
        }

        return app(StoreLedgerService::class)->validate(fn () => app(StoreLedgerService::class)->buildBillEntries($this));
    }

    protected $guarded = [
        "id",
        "created_at",
        "updated_at"
    ];


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
