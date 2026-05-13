<?php

namespace App\Models\Procurement\Store;

use App\Models\Master\CompanyLocation;
use App\Traits\HasApproval;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;


class PurchaseRequest extends Model
{
    use HasFactory, HasApproval;

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

    protected $fillable = [
        'purchase_request_no',
        'company_id',
        'category_id',
        'purchase_date',
        'location_id',
        'reference_no',
        'description',
        'purchase_request_status',
        'approved_user_name',
        'am_approval_status',
        'am_change_made',
        'status',
        'po_status',
        'created_by',
        
        'job_orders',
        'department_id',
        'request_by_id'
    ];

    public function location()
    {
        return $this->belongsTo(CompanyLocation::class, 'location_id');
    }

    public function locations() {
        return $this->morphMany(Location::class, 'locationable');
    }

    public function category()
    {
        return $this->belongsTo(\App\Models\Category::class, 'category_id');
    }

    public function PurchaseData()
    {
        return $this->hasMany(PurchaseRequestData::class);
    }

    public function quotation()
    {
        return $this->hasOne(PurchaseQuotation::class, 'purchase_request_id');
    }

    public function purchase_quotation()
    {
        return $this->belongsTo(PurchaseQuotation::class, 'purchase_request_id');
    }

    public function purchase_order() {
        return $this->belongsTo(PurchaseOrderData::class, "purchase_order_id", "id");
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function department()
    {
        return $this->belongsTo(\App\Models\Master\Department::class, 'department_id');
    }

    public function requestBy()
    {
        return $this->belongsTo(\App\Models\Master\RequestBy::class, 'request_by_id');
    }

    public function syncStatusFromItems($comments = 'System: Status synced from items')
    {
        $allChildren = $this->PurchaseData()->get();
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
                } else if ($this->am_approval_status !== 'partial approved') {
                    $this->update(['am_approval_status' => 'partial approved']);
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
                } else if ($this->am_approval_status !== 'partial approved') {
                    $this->update(['am_approval_status' => 'partial approved']);
                }
            }
        }
    }

    public function canApprove()
    {
        $status = $this->getApprovalStatus();
        $hasPendingItems = $this->PurchaseData()->whereNotIn('am_approval_status', ['approved', 'rejected', 'neglected'])->exists();

        if (($status === 'approved' || $status === 'rejected') && !$hasPendingItems) {
            return false;
        }

        // Call the rest of the logic manually or via a helper if we don't want to copy-paste
        // Since I can't change the trait, I'll implement the necessary parts here
        $user = auth()->user();
        if (!$user) return false;

        $module = $this->getApprovalModule();
        if (!$module) return false;

        if (isset($this->am_change_made) && $this->am_change_made == 0 && !$hasPendingItems) return false;

        $userRoleIds = $user->roles->pluck('id')->toArray();
        $requiredRoles = $module->roles->pluck('role_id')->toArray();

        if (empty(array_intersect($userRoleIds, $requiredRoles))) return false;

        $currentCycle = $this->getCurrentApprovalCycle();

        $userAlreadyActed = $this->approvalLogs()
            ->where('module_id', $module->id)
            ->where('approval_cycle', $currentCycle)
            ->where('user_id', $user->id)
            ->whereIn('action', ['approved', 'partial_approved', 'rejected'])
            ->where('status', 'active')
            ->exists();

        if ($userAlreadyActed && !$hasPendingItems) return false;

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

                if ($hasPendingItems && in_array($row->role_id, $userRoleIds)) {
                    return true;
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
            if ($row->current_count < $row->required_count || $hasPendingItems) {
                return true;
            }
        }

        return false;
    }
}
