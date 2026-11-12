<?php

namespace App\Models\Procurement\Store;

use App\Services\StoreLedgerService;
use App\Traits\HasApproval;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    /** Final approval: mark approved, then post stock-in + ledger through StoreLedgerService. */
    public function onApprovalComplete() {
        $this->am_approval_status = "approved";
        $this->save();

        $this->postApproval();
    }

    /**
     * Shared by manual approval and auto-approval (QcController).
     * The deduction type (no / half / full) is fixed first because the purchase bill's
     * qty and deduction are calculated from it.
     */
    public function postApproval(): void
    {
        identify_deduction_type($this);
        app(StoreLedgerService::class)->postQcApproval($this);
    }

    public function validateBeforeApproval(): ?string
    {
        return app(StoreLedgerService::class)->validate(fn () => app(StoreLedgerService::class)->buildQcEntries($this));
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
