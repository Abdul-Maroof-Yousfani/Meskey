<?php

namespace App\Models\Procurement\Store;

use App\Models\Master\CompanyLocation;
use App\Models\Master\Supplier;
use App\Models\PaymentTerm;
use App\Models\Procurement\PaymentRequest;
use App\Traits\HasApproval;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\User;

class PurchaseOrder extends Model
{
    use HasFactory, HasApproval;
    protected $table = "purchase_orders";
    protected $guarded = [];

    protected static function boot()
    {
        parent::boot();
        static::updating(function ($model) {
            $original = $model->getOriginal();
            $oldStatus = strtolower($original['am_approval_status'] ?? 'pending');

            if ($oldStatus === 'approved' || $oldStatus === 'rejected') {
                $dirty = $model->getDirty();
                // Allow only approval-related columns to change (e.g. for revert/neglect workflow)
                $allowedColumns = ['am_approval_status', 'updated_at', 'am_approval_log'];
                
                foreach ($dirty as $column => $value) {
                    if (!in_array($column, $allowedColumns)) {
                        return false;
                    }
                }
            }
        });

        static::deleting(function ($model) {
            $status = strtolower($model->am_approval_status ?? 'pending');
            if ($status === 'approved' || $status === 'rejected') {
                return false;
            }
        });
    }

    public function location()
    {
        return $this->belongsTo(CompanyLocation::class, 'location_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function purchase_request()
    {
        return $this->belongsTo(PurchaseRequest::class, 'purchase_request_id');
    }

    public function purchase_quotation()
    {
        return $this->belongsTo(PurchaseQuotation::class, 'purchase_quotation_id');
    }

    public function purchaseOrderData()
    {
        return $this->hasMany(PurchaseOrderData::class,'purchase_order_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderData::class, 'purchase_order_id');
    }

    public function paymentRequests(): HasMany
    {
        return $this->hasMany(PaymentRequest::class, 'purchase_order_id');
    }

    public function getTotalAmountAttribute()
    {
        return $this->items()->sum('total');
    }

    public function getTotalPaidAttribute()
    {
        return $this->paymentRequests()->where('status', 'approved')->sum('amount');
    }

    public function getRemainingAmountAttribute()
    {
        return max(0, $this->total_amount - $this->total_paid);
    }

    public function getIsFullyPaidAttribute()
    {
        return $this->remaining_amount <= 0;
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function paymentTerm()
    {
        return $this->belongsTo(PaymentTerm::class, 'payment_term_id');
    }

    public function syncStatusFromItems($comments = 'System: Status synced from items')
    {
        $allChildren = $this->purchaseOrderData()->get();
        $pendingCount = $allChildren->whereNotIn('am_approval_status', ['approved', 'rejected', 'neglected', 'returned', 'reverted'])->count();
        $approvedCount = $allChildren->where('am_approval_status', 'approved')->count();
        $rejectedCount = $allChildren->where('am_approval_status', 'rejected')->count();

        if ($pendingCount == 0) {
            if ($approvedCount > 0 && $rejectedCount == 0) {
                if ($this->canApprove()) {
                    $this->approve($comments);
                } else if ($this->am_approval_status !== 'approved') {
                    $this->update(['am_approval_status' => 'approved']);
                }
            } elseif ($approvedCount > 0 && $rejectedCount > 0) {
                if ($this->canApprove()) {
                    $this->partial_approve($comments);
                } else if ($this->am_approval_status !== 'partial_approved') {
                    $this->update(['am_approval_status' => 'partial_approved']);
                }
            } elseif ($rejectedCount > 0) {
                if ($this->canApprove()) {
                    $this->reject($comments);
                } else if ($this->am_approval_status !== 'rejected') {
                    $this->update(['am_approval_status' => 'rejected']);
                }
            }
        } else {
            if ($approvedCount > 0 || $rejectedCount > 0) {
                if ($this->canApprove()) {
                    $this->partial_approve($comments);
                } else if ($this->am_approval_status !== 'partial_approved') {
                    $this->update(['am_approval_status' => 'partial_approved']);
                }
            }
        }
    }

    public function canApprove()
    {
        $status = $this->getApprovalStatus();
        $hasPendingItems = $this->purchaseOrderData()->whereNotIn('am_approval_status', ['approved', 'rejected', 'neglected', 'reverted', 'returned'])->exists();

        if (($status === 'approved' || $status === 'rejected') && !$hasPendingItems) {
            return false;
        }

        $user = auth()->user();
        if (!$user) return false;

        $module = $this->getApprovalModule();
        if (!$module) return false;

        if (isset($this->am_change_made) && $this->am_change_made == 0 && !$hasPendingItems) return false;

        $userRoleIds = [];
        if ($user->roles) {
            $userRoleIds = $user->roles->pluck('id')->toArray();
        }

        $requiredRoles = [];
        if ($module->roles) {
            $requiredRoles = $module->roles->pluck('role_id')->toArray();
        }

        // Check if user has the required role
        $hasRole = !empty(array_intersect($userRoleIds, $requiredRoles));
        if (!$hasRole) return false;

        // If there are pending items, the user should be able to approve them 
        // as long as they have the required role for this module.
        if ($hasPendingItems) {
            return true;
        }

        if (isset($this->am_change_made) && $this->am_change_made == 0) return false;

        $currentCycle = $this->getCurrentApprovalCycle();

        $userAlreadyActed = $this->approvalLogs()
            ->where('module_id', $module->id)
            ->where('approval_cycle', $currentCycle)
            ->where('user_id', $user->id)
            ->whereIn('action', ['approved', 'partial_approved', 'rejected'])
            ->where('status', 'active')
            ->exists();

        if ($userAlreadyActed) return false;

        if ($module->requires_sequential_approval) {
            $approvalRows = $this->approvalRows()
                ->where('module_id', $module->id)
                ->where('approval_cycle', $currentCycle)
                ->orderBy('id')
                ->get();

            foreach ($approvalRows as $row) {
                if ($row->current_count < $row->required_count) {
                    return in_array($row->role_id, $userRoleIds);
                }
            }
        }

        $userApprovalRows = $this->approvalRows()
            ->where('module_id', $module->id)
            ->where('approval_cycle', $currentCycle)
            ->whereIn('role_id', $userRoleIds)
            ->whereIn('status', ['pending', 'partial_approved', 'approved'])
            ->get();

        foreach ($userApprovalRows as $row) {
            if ($row->current_count < $row->required_count) {
                return true;
            }
        }

        return false;
    }

    protected function onPartialApprovalComplete()
    {
        $module = $this->getApprovalModule();
        if ($module && isset($module->approval_column)) {
            $this->update([$module->approval_column => 'partial_approved']);
        }
    }
}
