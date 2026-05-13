<?php

namespace App\Models\Procurement\Store;

use App\Models\Category;
use App\Models\Master\Supplier;
use App\Models\Product;
use App\Models\GrnNumber;
use App\Models\Master\Account\Stock;
use App\Models\Master\GrnNumber as MasterGrnNumber;
use App\Models\Master\Tax;
use App\Traits\HasApproval;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrderData extends Model
{
    use HasFactory, HasApproval;
    protected $table = "purchase_order_data";
    protected $guarded = [];

    protected static function booted()
    {
        static::updating(function ($model) {
            $original = $model->getOriginal();
            $oldStatus = strtolower($original['am_approval_status'] ?? 'pending');

            if ($oldStatus === 'approved' || $oldStatus === 'rejected') {
                $dirty = $model->getDirty();
                // Allow only approval-related columns to change (e.g. for revert/neglect workflow)
                $allowedColumns = ['am_approval_status', 'updated_at', 'am_approval_log', 'am_change_made'];
                
                foreach ($dirty as $column => $value) {
                    if (!in_array($column, $allowedColumns)) {
                        return false; // Cancels the update operation
                    }
                }
            } else {
                // Change tracking for pending items
                $changes = $model->getDirty();
                $hasRealChanges = false;
                foreach ($changes as $key => $newValue) {
                    if ($key !== "am_change_made" && $key !== "updated_at") {
                        $hasRealChanges = true;
                        break;
                    }
                }
                if ($hasRealChanges && $model->getAttribute('am_change_made') !== null) {
                    $model->am_change_made = 1;
                }
            }
        });

        static::deleting(function ($model) {
            $status = strtolower($model->am_approval_status ?? 'pending');
            if ($status === 'approved' || $status === 'rejected') {
                return false; // Cancels the deletion
            }
        });
    }
    protected $casts = [
        'printing_sample' => 'array',
    ];


    protected function onApprovalComplete()
    {
        $module = $this->getApprovalModule();
        if ($module && isset($module->approval_column)) {
            $this->update([$module->approval_column => 'approved']);
        }

        $parent = $this->purchase_order;
        if ($parent) {
            $parent->syncStatusFromItems('System: Item approved');
        }
    }

    protected function onApprovalRejected()
    {
        $module = $this->getApprovalModule();
        if ($module && isset($module->approval_column)) {
            $this->update([$module->approval_column => 'rejected']);
        }

        $parent = $this->purchase_order;
        if ($parent) {
            $parent->syncStatusFromItems('System: Item rejected');
        }
    }

    protected function onApprovalReverted()
    {
        $module = $this->getApprovalModule();
        if ($module && isset($module->approval_column)) {
            $this->update([$module->approval_column => 'reverted']);
        }

        $parent = $this->purchase_order;
        if ($parent) {
            $parent->syncStatusFromItems('System: Item reverted');
        }
    }

    protected function onPartialApprovalComplete()
    {
        $module = $this->getApprovalModule();
        if ($module && isset($module->approval_column)) {
            $this->update([$module->approval_column => 'partial_approved']);
        }

        $parent = $this->purchase_order;
        if ($parent) {
            $parent->syncStatusFromItems('System: Item partially approved');
        }
    }

    public function canApprove()
    {
        $status = $this->am_approval_status;
        if ($status === 'approved' || $status === 'rejected') {
            return false;
        }

        $user = auth()->user();
        if (!$user) return false;

        $parent = $this->purchase_order;
        if (!$parent) return false;

        $module = $parent->getApprovalModule();
        if (!$module) return false;

        $userRoleIds = [];
        if ($user->roles) {
            $userRoleIds = $user->roles->pluck('id')->toArray();
        }

        $requiredRoles = [];
        if ($module->roles) {
            $requiredRoles = $module->roles->pluck('role_id')->toArray();
        }

        // Check if user has the required role for the PO module
        $hasRole = !empty(array_intersect($userRoleIds, $requiredRoles));
        if (!$hasRole) return false;

        // If the item is pending, allow approval
        $pendingStatuses = ['pending', 'partial_approved', 'reverted', 'returned', 'neglected'];
        if (in_array(strtolower($status), $pendingStatuses) || $status == null) {
            return true;
        }

        return $parent->canApprove();
    }

    public function getApprovalModule()
    {
        return $this->purchase_order ? $this->purchase_order->getApprovalModule() : null;
    }

    public function purchase_order()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function grn() {
        return $this->hasOne(PurchaseOrderReceivingData::class, "purchase_order_data_id");
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function item()
    {
        return $this->belongsTo(Product::class, 'item_id');
    }

    public function purchase_request_data()
    {
        return $this->hasOne(PurchaseRequestData::class, 'id', 'purchase_request_data_id');
    }

    public function purchase_quotation_data()
    {
        return $this->hasOne(PurchaseQuotationData::class, 'id', 'purchase_quotation_data_id');
    }

    /**
     * Get all GRNs for this purchase order data
     */
    public function grns(): HasMany
    {
        return $this->hasMany(MasterGrnNumber::class, 'model_id')
            ->where('model_type', 'purchase-order-data');
    }

    /**
     * Get all stocks for this purchase order data
     */
    /**
     * Get all stocks related to this purchase order data through GRN numbers
     */
    public function stocks()
    {
        $grnNumbers = $this->grns()->pluck('unique_no');
        return Stock::whereIn('voucher_no', $grnNumbers);
    }

    /**
     * Get the total received quantity for this purchase order item
     */
    public function getTotalReceivedQtyAttribute(): float
    {
        return $this->stocks->sum('qty');
    }

    /**
     * Get the remaining quantity to be received
     */
    public function getRemainingQtyAttribute(): float
    {
        return max(0, $this->qty - $this->total_received_qty);
    }

    /**
     * Check if the purchase order item is fully received
     */
    public function getIsFullyReceivedAttribute(): bool
    {
        return $this->remaining_qty <= 0;
    }

    public function tax()
    {
        return $this->belongsTo(Tax::class, 'tax_id');
    }

    public function job_orders() {
        return $this->hasMany(PurchaseAgainstJobOrder::class, "purchase_request_data_id", "purchase_request_data_id");
    }
}
