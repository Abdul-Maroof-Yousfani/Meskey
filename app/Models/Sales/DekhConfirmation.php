<?php

namespace App\Models\Sales;

use App\Models\ApprovalsModule\ApprovalLog;
use App\Models\ApprovalsModule\ApprovalRow;
use App\Models\User;
use App\Traits\HasApproval;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class DekhConfirmation extends Model
{
    use HasFactory, HasApproval;

    protected $table = 'dekh_confirmations';
    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = [
        'is_completed' => 'boolean',
        'completed_at' => 'datetime',
        'vehicle_assigned_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::created(function ($model) {
            $inspection = $model->inspection;
            if ($inspection) {
                $inspection->update([
                    'is_completed' => $model->is_completed ? 1 : 0,
                    'completed_at' => $model->completed_at,
                    'completed_by' => $model->completed_by,
                    'confirmation_approval_status' => $model->am_approval_status,
                    'confirmation_am_change_made' => $model->am_change_made ?? 1,
                    'vehicle_no' => $model->vehicle_no,
                    'vehicle_assigned_at' => $model->vehicle_assigned_at,
                    'vehicle_assigned_by' => $model->vehicle_assigned_by,
                ]);
            }
        });

        static::updated(function ($model) {
            $inspection = $model->inspection;
            if ($inspection) {
                $inspection->update([
                    'is_completed' => $model->is_completed ? 1 : 0,
                    'completed_at' => $model->completed_at,
                    'completed_by' => $model->completed_by,
                    'confirmation_approval_status' => $model->am_approval_status,
                    'confirmation_am_change_made' => $model->am_change_made ?? 1,
                    'vehicle_no' => $model->vehicle_no,
                    'vehicle_assigned_at' => $model->vehicle_assigned_at,
                    'vehicle_assigned_by' => $model->vehicle_assigned_by,
                ]);
            }
        });
    }

    public function inspection()
    {
        return $this->belongsTo(PreSaleInspection::class, 'pre_sale_inspection_id');
    }

    public function completedBy()
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function vehicleAssignedBy()
    {
        return $this->belongsTo(User::class, 'vehicle_assigned_by');
    }

    /**
     * Override createApprovalRows so an approval row is created automatically
     * without needing configuration in approval_module_roles.
     */
    public function createApprovalRows()
    {
        $module = $this->getApprovalModule();
        $moduleId = $module ? $module->id : 43;
        $currentCycle = $this->getCurrentApprovalCycle();

        $this->approvalRows()->updateOrCreate([
            'module_id' => $moduleId,
            'record_id' => $this->id,
            'approval_cycle' => $currentCycle,
        ], [
            'role_id' => 1,
            'required_count' => 1,
            'current_count' => 0,
            'status' => 'pending',
        ]);
    }

    /**
     * Determine if a user can approve Dekh Confirmation.
     * Controlled by 'dekh-confirmation-approval' permission and Company Location.
     * Factory location is NOT matched on approval.
     */
    public function canApprove(User $user = null): bool
    {
        $user = $user ?? Auth::user();
        if (!$user) {
            return false;
        }

        // Dekh must be marked as complete first
        if (!$this->is_completed) {
            return false;
        }

        // If changes required (reverted) and not yet modified
        if (isset($this->am_change_made) && (int)$this->am_change_made === 0) {
            return false;
        }

        $status = strtolower($this->am_approval_status ?? '');
        if (in_array($status, ['approved', 'rejected'])) {
            return false;
        }

        // Check if user has permission or is super-admin
        $isSuperAdmin = $user->user_type === 'super-admin' || $user->hasRole('Admin') || $user->hasRole('admin');
        if ($isSuperAdmin) {
            $hasPermission = true;
        } else {
            $hasPermission = false;
            try {
                $hasPermission = canAccess('dekh-confirmation-approval');
            } catch (\Throwable $e) {
                $hasPermission = false;
            }
            if (!$hasPermission) {
                $hasPermission = $user->can('dekh-confirmation-approval');
            }
        }
        if (!$hasPermission) {
            return false;
        }

        // Check Company Location (factory location is NOT checked on approval)
        if (!$isSuperAdmin) {
            $inspection = $this->inspection;
            if ($inspection) {
                $userLocations = get_locations($user->id)->pluck('id')->map(fn($id) => (int)$id)->toArray();
                if (empty($userLocations) || !in_array((int)$inspection->location_id, $userLocations)) {
                    return false;
                }
            }
        }

        // Check if user already approved in current cycle
        $currentCycle = $this->getCurrentApprovalCycle();
        $module = $this->getApprovalModule();
        $moduleId = $module ? $module->id : 43;

        $alreadyActed = $this->approvalLogs()
            ->where('module_id', $moduleId)
            ->where('approval_cycle', $currentCycle)
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->where('action', 'approved')
            ->exists();

        if ($alreadyActed) {
            return false;
        }

        return true;
    }

    /**
     * Check if user is authorized to act (revert/reject).
     */
    public function canAct(User $user = null): bool
    {
        $user = $user ?? Auth::user();
        if (!$user) {
            return false;
        }

        $isSuperAdmin = $user->user_type === 'super-admin' || $user->hasRole('Admin') || $user->hasRole('admin');
        if ($isSuperAdmin) {
            $hasPermission = true;
        } else {
            $hasPermission = false;
            try {
                $hasPermission = canAccess('dekh-confirmation-approval');
            } catch (\Throwable $e) {
                $hasPermission = false;
            }
            if (!$hasPermission) {
                $hasPermission = $user->can('dekh-confirmation-approval');
            }
        }
        if (!$hasPermission) {
            return false;
        }

        if (!$isSuperAdmin) {
            $inspection = $this->inspection;
            if ($inspection) {
                $userLocations = get_locations($user->id)->pluck('id')->map(fn($id) => (int)$id)->toArray();
                if (empty($userLocations) || !in_array((int)$inspection->location_id, $userLocations)) {
                    return false;
                }
            }
        }

        return true;
    }

    public function canUserApprove(User $user = null): bool
    {
        return $this->canApprove($user);
    }

    /**
     * Grant confirmation approval.
     */
    public function approve($comments = null): bool
    {
        $user = Auth::user();
        if (!$user || !$this->canApprove($user)) {
            return false;
        }

        $module = $this->getApprovalModule();
        $moduleId = $module ? $module->id : 43;
        $currentCycle = $this->getCurrentApprovalCycle();
        $roleId = $user->roles()->latest()->first()?->id ?? 1;

        ApprovalLog::create([
            'module_id'      => $moduleId,
            'record_id'      => $this->id,
            'user_id'        => $user->id,
            'role_id'        => $roleId,
            'action'         => 'approved',
            'status'         => 'active',
            'approval_cycle' => $currentCycle,
            'comments'       => $comments ?: 'Confirmation Approved',
        ]);

        $this->approvalRows()
            ->where('module_id', $moduleId)
            ->where('approval_cycle', $currentCycle)
            ->update(['status' => 'approved', 'current_count' => 1]);

        $this->am_approval_status = 'approved';
        $this->am_change_made = 1;
        $this->save();

        if ($this->inspection) {
            $this->inspection->update([
                'confirmation_approval_status' => 'approved',
                'confirmation_am_change_made' => 1,
            ]);
        }

        return true;
    }

    /**
     * Decline confirmation permanently.
     */
    public function reject($comments = null): bool
    {
        $user = Auth::user();
        if (!$user || !$this->canAct($user)) {
            return false;
        }

        $module = $this->getApprovalModule();
        $moduleId = $module ? $module->id : 43;
        $currentCycle = $this->getCurrentApprovalCycle();
        $roleId = $user->roles()->latest()->first()?->id ?? 1;

        $this->approvalLogs()
            ->where('module_id', $moduleId)
            ->where('approval_cycle', $currentCycle)
            ->where('status', 'active')
            ->update(['status' => 'inactive']);

        ApprovalLog::create([
            'module_id'      => $moduleId,
            'record_id'      => $this->id,
            'user_id'        => $user->id,
            'role_id'        => $roleId,
            'action'         => 'rejected',
            'status'         => 'active',
            'approval_cycle' => $currentCycle,
            'comments'       => $comments ?: 'Confirmation Declined',
        ]);

        $this->approvalRows()
            ->where('module_id', $moduleId)
            ->where('approval_cycle', $currentCycle)
            ->update(['status' => 'rejected']);

        $this->am_approval_status = 'rejected';
        $this->save();

        if ($this->inspection) {
            $this->inspection->update([
                'confirmation_approval_status' => 'rejected',
            ]);
        }

        return true;
    }

    /**
     * Revert confirmation for revision.
     */
    public function revert($comments = null): bool
    {
        $user = Auth::user();
        if (!$user || !$this->canAct($user)) {
            return false;
        }

        $module = $this->getApprovalModule();
        $moduleId = $module ? $module->id : 43;
        $currentCycle = $this->getCurrentApprovalCycle();
        $roleId = $user->roles()->latest()->first()?->id ?? 1;

        ApprovalLog::create([
            'module_id'      => $moduleId,
            'record_id'      => $this->id,
            'user_id'        => $user->id,
            'role_id'        => $roleId,
            'action'         => 'reverted',
            'status'         => 'active',
            'approval_cycle' => $currentCycle,
            'comments'       => $comments ?: 'Confirmation Reverted',
        ]);

        $this->approvalRows()
            ->where('module_id', $moduleId)
            ->where('approval_cycle', $currentCycle)
            ->update(['status' => 'pending', 'current_count' => 0]);

        $this->is_completed = 0;
        $this->am_approval_status = 'reverted';
        $this->am_change_made = 0;
        $this->save();

        if ($this->inspection) {
            $this->inspection->update([
                'is_completed' => 0,
                'confirmation_approval_status' => 'reverted',
                'confirmation_am_change_made' => 0,
            ]);
        }

        return true;
    }
}
