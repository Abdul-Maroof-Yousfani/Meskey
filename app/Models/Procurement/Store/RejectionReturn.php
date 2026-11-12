<?php

namespace App\Models\Procurement\Store;

use App\Models\Master\Supplier;
use App\Models\User;
use App\Services\StoreLedgerService;
use App\Traits\HasApproval;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class RejectionReturn extends Model
{
    use SoftDeletes, HasApproval {
        onApprovalComplete as protected traitOnApprovalComplete;
    }

    protected $guarded = ['id'];

    /** Stock-out and ledger are posted only when the return is fully approved. */
    protected function onApprovalComplete()
    {
        $this->traitOnApprovalComplete();
        app(StoreLedgerService::class)->postRejectionReturn($this);
    }

    /** Pending / reverted returns can still be edited or deleted; approved and rejected ones are locked. */
    public function isLocked(): bool
    {
        return in_array(strtolower((string) $this->am_approval_status), ['approved', 'rejected']);
    }

    /**
     * A return saved before approvers were assigned to the module has no approval rows,
     * so nobody could ever approve it. Create them for the current cycle when missing.
     */
    public function ensureApprovalRows(): void
    {
        $module = $this->getApprovalModule();
        if ($module && !$this->approvalRows()->where('module_id', $module->id)
                ->where('approval_cycle', $this->getCurrentApprovalCycle())->exists()) {
            $this->createApprovalRows();
        }
    }

    public function validateBeforeApproval(): ?string
    {
        return app(StoreLedgerService::class)->validate(fn () => app(StoreLedgerService::class)->buildRejectionReturnEntries($this));
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function grn(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderReceiving::class, 'grn_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(RejectionReturnItem::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Remove this return's items together with the stock-out and ledger rows they posted,
     * so an edit can re-create them without doubling and a delete puts the bags back in stock.
     */
    public function clearItems(): void
    {
        app(StoreLedgerService::class)->reverseRejectionReturn($this);
        $this->items()->delete();
    }

    /**
     * Qty of an item from this GRN that may still be returned:
     * approved QC "rejected without deduction" qty minus what other active returns already took.
     */
    public static function returnableQty(int $grnId, int $itemId, ?int $excludeReturnId = null): float
    {
        $qcQty = (float) PurchaseBagQC::where('am_approval_status', 'approved')
            ->whereHas('grn', function ($q) use ($grnId, $itemId) {
                $q->where('purchase_order_receiving_id', $grnId)->where('item_id', $itemId);
            })
            ->sum('rejection_return');

        $alreadyReturned = (float) RejectionReturnItem::where('item_id', $itemId)
            ->whereHas('rejectionReturn', function ($q) use ($grnId, $excludeReturnId) {
                $q->where('grn_id', $grnId)
                    ->where('am_approval_status', '!=', 'rejected')
                    ->when($excludeReturnId, fn ($q) => $q->where('id', '!=', $excludeReturnId));
            })
            ->sum('quantity');

        return max(0, $qcQty - $alreadyReturned);
    }
}
