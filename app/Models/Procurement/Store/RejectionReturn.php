<?php

namespace App\Models\Procurement\Store;

use App\Models\Master\Account\Stock;
use App\Models\Master\Account\Transaction;
use App\Models\Master\Supplier;
use App\Models\Product;
use App\Models\User;
use App\Traits\HasApproval;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class RejectionReturn extends Model
{
    use SoftDeletes, HasApproval;

    protected $guarded = ['id'];

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
        $itemIds = $this->items()->pluck('id');

        if ($itemIds->isNotEmpty()) {
            Stock::where('parentable_type', RejectionReturnItem::class)
                ->whereIn('parentable_id', $itemIds)
                ->delete();
        }

        if ($this->return_no) {
            Transaction::where('transaction_voucher_type_id', 9)
                ->where('voucher_no', $this->return_no)
                ->delete();
        }

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
