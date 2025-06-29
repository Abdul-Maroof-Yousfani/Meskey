<?php

namespace App\Traits;

use App\Models\ApprovalsModule\ApprovalLog;
use App\Models\ApprovalsModule\ApprovalModule;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

trait HasApproval
{
    public function approvalLogs(): HasMany
    {
        return $this->hasMany(ApprovalLog::class, 'record_id')
            ->where('module_id', optional($this->getApprovalModule())->id);
    }

    public function getApprovalModule()
    {
        return ApprovalModule::where('model_class', get_class($this))->first();
    }

    public function getApprovalStatus()
    {
        $module = $this->getApprovalModule();
        if (!$module) {
            return 'not_required';
        }

        $requiredApprovals = $this->getRequiredApprovals();
        $currentApprovals = $this->getCurrentApprovals();

        foreach ($requiredApprovals as $roleId => $requiredCount) {
            $currentCount = $currentApprovals[$roleId] ?? 0;
            if ($currentCount < $requiredCount) {
                return 'pending';
            }
        }

        return 'approved';
    }

    public function getRequiredApprovals()
    {
        $module = $this->getApprovalModule();
        if (!$module) {
            return [];
        }

        return $module->roles->pluck('approval_count', 'role_id')->toArray();
    }

    public function getCurrentApprovals()
    {
        return $this->approvalLogs()
            ->where('action', 'approved')
            ->selectRaw('role_id, COUNT(*) as count')
            ->groupBy('role_id')
            ->get()
            ->pluck('count', 'role_id')
            ->toArray();
    }

    public function canApprove()
    {
        $user = Auth::user();
        if (!$user) {
            return false;
        }

        $module = $this->getApprovalModule();
        if (!$module) {
            return false;
        }

        $userRoleIds = $user->roles->pluck('id')->toArray();
        $requiredRoles = $module->roles->pluck('role_id')->toArray();

        // Check if user has any of the required roles
        if (empty(array_intersect($userRoleIds, $requiredRoles))) {
            return false;
        }

        // Check if approval is still pending
        if ($this->getApprovalStatus() !== 'pending') {
            return false;
        }

        // For sequential approval, check if previous roles are completed
        if ($module->requires_sequential_approval) {
            $currentApprovals = $this->getCurrentApprovals();
            $requiredApprovals = $this->getRequiredApprovals();
            $roles = $module->roles()->orderBy('approval_order')->get();

            foreach ($roles as $moduleRole) {
                $roleId = $moduleRole->role_id;
                $requiredCount = $moduleRole->approval_count;
                $currentCount = $currentApprovals[$roleId] ?? 0;

                // If this is a role the user has, check if it's ready for approval
                if (in_array($roleId, $userRoleIds)) {
                    // If previous roles aren't complete, user can't approve yet
                    if ($currentCount < $requiredCount) {
                        // Check if user hasn't already approved for this role
                        $alreadyApproved = $this->approvalLogs()
                            ->where('user_id', $user->id)
                            ->where('role_id', $roleId)
                            ->count();

                        if ($alreadyApproved < $requiredCount) {
                            return true;
                        }
                    }
                    return false;
                }

                // If previous role isn't complete, stop checking
                if ($currentCount < $requiredCount) {
                    return false;
                }
            }
        } else {
            // Non-sequential approval - check if user hasn't already approved for any role
            foreach ($user->roles as $role) {
                $moduleRole = $module->roles->where('role_id', $role->id)->first();
                if ($moduleRole) {
                    $alreadyApproved = $this->approvalLogs()
                        ->where('user_id', $user->id)
                        ->where('role_id', $role->id)
                        ->count();

                    if ($alreadyApproved < $moduleRole->approval_count) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    public function approve($comments = null)
    {
        $user = Auth::user();
        if (!$user) {
            return false;
        }

        $module = $this->getApprovalModule();
        if (!$module) {
            return false;
        }

        // Find the first role that user has and can approve
        foreach ($user->roles as $role) {
            $moduleRole = $module->roles->where('role_id', $role->id)->first();
            if ($moduleRole) {
                $alreadyApproved = $this->approvalLogs()
                    ->where('user_id', $user->id)
                    ->where('role_id', $role->id)
                    ->exists();

                if (!$alreadyApproved) {
                    ApprovalLog::create([
                        'module_id' => $module->id,
                        'record_id' => $this->id,
                        'user_id' => $user->id,
                        'role_id' => $role->id,
                        'action' => 'approved',
                        'comments' => $comments,
                    ]);

                    // Check if all approvals are complete
                    if ($this->getApprovalStatus() === 'approved') {
                        $this->onApprovalComplete();
                    }

                    return true;
                }
            }
        }

        return false;
    }

    public function reject($comments = null)
    {
        $user = Auth::user();
        if (!$user) {
            return false;
        }

        $module = $this->getApprovalModule();
        if (!$module) {
            return false;
        }

        ApprovalLog::create([
            'module_id' => $module->id,
            'record_id' => $this->id,
            'user_id' => $user->id,
            'role_id' => $user->roles->first()->id,
            'action' => 'rejected',
            'comments' => $comments,
        ]);

        $this->onApprovalRejected();

        return true;
    }

    protected function onApprovalComplete()
    {
        if (isset($this->status)) {
            $this->update(['status' => 'approved']);
        }
    }

    protected function onApprovalRejected()
    {
        if (isset($this->status)) {
            $this->update(['status' => 'rejected']);
        }
    }
}
