<?php

namespace App\Traits;

use App\Models\ApprovalsModule\ApprovalLog;
use App\Models\ApprovalsModule\ApprovalModule;
use App\Models\ApprovalsModule\ApprovalModuleRole;
use App\Models\ApprovalsModule\ApprovalRow;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

trait HasApprovalSalesInquiry
{
    protected $approvalModuleCache = null;

    protected static function bootHasApprovalSalesInquiry()
    {
        static::creating(function ($model) {
            if (empty($model->si_approval_stage)) {
                $model->si_approval_stage = 'stage_1_pending';
            }
            if (empty($model->am_approval_status)) {
                $model->am_approval_status = 'pending';
            }
            if (!isset($model->am_change_made)) {
                $model->am_change_made = 1;
            }
        });

        static::created(function ($model) {
            $model->createApprovalRows();
        });
    }

    public function approvalLogs(): HasMany
    {
        return $this->hasMany(ApprovalLog::class, 'record_id');
    }

    public function approvalRows(): HasMany
    {
        return $this->hasMany(ApprovalRow::class, 'record_id');
    }

    public function getApprovalModule()
    {
        if ($this->approvalModuleCache === null) {
            $this->approvalModuleCache = ApprovalModule::where('model_class', get_class($this))->first();
        }
        return $this->approvalModuleCache;
    }

    public function getApprovalRowsForModule()
    {
        $module = $this->getApprovalModule();
        if (!$module) {
            return collect();
        }
        return $this->approvalRows()->where('module_id', $module->id)->get();
    }

    public function getCurrentApprovalCycle(): int
    {
        $module = $this->getApprovalModule();
        $moduleId = $module ? $module->id : 21;

        $logCycle = (int) ($this->approvalLogs()
            ->where('module_id', $moduleId)
            ->max('approval_cycle') ?? 1);

        $rowCycle = (int) ($this->approvalRows()
            ->where('module_id', $moduleId)
            ->max('approval_cycle') ?? 1);

        return max(1, $logCycle, $rowCycle);
    }

    public function getRequiredApprovals(): array
    {
        $module = $this->getApprovalModule();
        if (!$module) {
            return [];
        }

        $currentCycle = $this->getCurrentApprovalCycle();
        return $this->approvalRows()
            ->where('module_id', $module->id)
            ->where('approval_cycle', $currentCycle)
            ->pluck('required_count', 'role_id')
            ->toArray();
    }

    public function getCurrentApprovals(): array
    {
        $module = $this->getApprovalModule();
        if (!$module) {
            return [];
        }

        $currentCycle = $this->getCurrentApprovalCycle();
        return $this->approvalRows()
            ->where('module_id', $module->id)
            ->where('approval_cycle', $currentCycle)
            ->pluck('current_count', 'role_id')
            ->toArray();
    }

    public function getApprovalStatus(): string
    {
        return strtolower($this->am_approval_status ?? 'pending');
    }

    public function getApprovalStage(): string
    {
        return strtolower($this->si_approval_stage ?? 'stage_1_pending');
    }

    public function userHasSalesInquiryPermission(User $user): bool
    {
        if ($user->user_type === 'super-admin') {
            return true;
        }
        return $user->can('sales-inquiry') || $user->can('sales-inquiry-list');
    }

    public function canAct(User $user = null): bool
    {
        $user = $user ?? Auth::user();
        if (!$user) return false;

        $mainStatus = strtolower($this->am_approval_status ?? '');
        if ($mainStatus === 'rejected') return false;

        $currentStage = $this->getApprovalStage();
        if ($currentStage === 'rejected') return false;

        if ($currentStage === 'stage_1_pending') {
            $creatorId = $this->created_by ?? Auth::id();
            $creator = User::find($creatorId);
            $parentId = $creator?->parent_user_id;

            if (!empty($parentId)) {
                if ($user->id == $creatorId) return false;
                if ($user->id == $parentId) return true;
                if ($user->parent_user_id == $parentId && $this->userHasSalesInquiryPermission($user)) return true;
                return false;
            }

            if ($creator && $user->id == $creator->id) return true;
            return false;
        }

        if ($currentStage === 'headoffice_pending') {
            return $user->user_type === 'super-admin' || $user->can('headoffice-sale-inquiry-approval');
        }

        return false;
    }

    public function canApprove(User $user = null): bool
    {
        $user = $user ?? Auth::user();
        if (!$user) return false;

        if (isset($this->am_change_made) && $this->am_change_made == 0) return false;

        $mainStatus = strtolower($this->am_approval_status ?? '');
        if ($mainStatus === 'approved') return false;

        $currentStage = $this->getApprovalStage();
        if ($currentStage === 'approved') return false;

        $currentCycle = $this->getCurrentApprovalCycle();
        $module = $this->getApprovalModule();
        $moduleId = $module ? $module->id : 21;

        if ($currentStage === 'stage_1_pending') {
            $alreadyActed = $this->approvalLogs()
                ->where('module_id', $moduleId)
                ->where('approval_cycle', $currentCycle)
                ->where('user_id', $user->id)
                ->where('status', 'active')
                ->whereIn('action', ['approved', 'partial_approved'])
                ->exists();
            if ($alreadyActed) return false;
        } elseif ($currentStage === 'headoffice_pending') {
            $alreadyApprovedHO = $this->approvalLogs()
                ->where('module_id', $moduleId)
                ->where('approval_cycle', $currentCycle)
                ->where('user_id', $user->id)
                ->where('status', 'active')
                ->where('action', 'approved')
                ->exists();
            if ($alreadyApprovedHO) return false;
        }

        return $this->canAct($user);
    }

    public function canUserApprove(User $user = null): bool
    {
        return $this->canApprove($user);
    }

    public function createApprovalRows()
    {
        $module = $this->getApprovalModule();
        $moduleId = $module ? $module->id : 21;
        $currentCycle = $this->getCurrentApprovalCycle();
        $creator = User::find($this->created_by ?? Auth::id());

        $stage1RoleId = 1;
        $parentId = $creator?->parent_user_id;
        if (!empty($parentId)) {
            $parent = User::find($parentId);
            $stage1RoleId = $parent?->roles()?->latest()?->first()?->id ?? ($creator?->roles()?->latest()?->first()?->id ?? 1);
        } elseif ($creator) {
            $stage1RoleId = $creator->roles()?->latest()?->first()?->id ?? 1;
        }

        $hoRoleId = 1;

        if ($stage1RoleId == $hoRoleId) {
            $fallbackRole = ApprovalModuleRole::where('module_id', $moduleId)->where('role_id', '!=', $hoRoleId)->first();
            if ($fallbackRole) {
                $stage1RoleId = $fallbackRole->role_id;
            }
        }

        ApprovalRow::create([
            'module_id' => $moduleId,
            'record_id' => $this->id,
            'role_id'   => $stage1RoleId,
            'required_count' => 1,
            'current_count'  => 0,
            'approval_cycle' => $currentCycle,
            'status' => 'pending'
        ]);

        if ($stage1RoleId != $hoRoleId) {
            ApprovalRow::create([
                'module_id' => $moduleId,
                'record_id' => $this->id,
                'role_id'   => $hoRoleId,
                'required_count' => 1,
                'current_count'  => 0,
                'approval_cycle' => $currentCycle,
                'status' => 'pending'
            ]);
        }
    }

    public function createNewApprovalCycle(): int
    {
        $module = $this->getApprovalModule();
        $moduleId = $module ? $module->id : 21;
        $newCycle = $this->getCurrentApprovalCycle() + 1;

        $creator = User::find($this->created_by ?? Auth::id());
        $stage1RoleId = 1;
        $parentId = $creator?->parent_user_id;
        if (!empty($parentId)) {
            $parent = User::find($parentId);
            $stage1RoleId = $parent?->roles()?->latest()?->first()?->id ?? ($creator?->roles()?->latest()?->first()?->id ?? 1);
        } elseif ($creator) {
            $stage1RoleId = $creator->roles()?->latest()?->first()?->id ?? 1;
        }

        $hoRoleId = 1;

        if ($stage1RoleId == $hoRoleId) {
            $fallbackRole = ApprovalModuleRole::where('module_id', $moduleId)->where('role_id', '!=', $hoRoleId)->first();
            if ($fallbackRole) {
                $stage1RoleId = $fallbackRole->role_id;
            }
        }

        ApprovalRow::create([
            'module_id' => $moduleId,
            'record_id' => $this->id,
            'role_id'   => $stage1RoleId,
            'required_count' => 1,
            'current_count'  => 0,
            'approval_cycle' => $newCycle,
            'status' => 'pending'
        ]);

        if ($stage1RoleId != $hoRoleId) {
            ApprovalRow::create([
                'module_id' => $moduleId,
                'record_id' => $this->id,
                'role_id'   => $hoRoleId,
                'required_count' => 1,
                'current_count'  => 0,
                'approval_cycle' => $newCycle,
                'status' => 'pending'
            ]);
        }

        $this->si_approval_stage = 'stage_1_pending';
        $this->am_approval_status = 'pending';
        $this->am_change_made = 1;
        $this->saveQuietly();

        return $newCycle;
    }

    public function approve($comments = null): bool
    {
        $user = Auth::user();
        if (!$user || !$this->canApprove($user)) return false;

        $module = $this->getApprovalModule();
        $moduleId = $module ? $module->id : 21;
        $currentCycle = $this->getCurrentApprovalCycle();
        $userRole = $user->roles()->latest()->first();
        $roleId = $userRole ? $userRole->id : 1;
        $currentStage = $this->getApprovalStage();

        if ($currentStage === 'stage_1_pending') {
            ApprovalLog::create([
                'module_id'     => $moduleId,
                'record_id'     => $this->id,
                'user_id'       => $user->id,
                'role_id'       => $roleId,
                'action'        => 'partial_approved',
                'status'        => 'active',
                'approval_cycle' => $currentCycle,
                'comments'      => $comments ?: 'Stage 1 Approved',
            ]);

            $firstPendingRow = $this->approvalRows()
                ->where('module_id', $moduleId)
                ->where('approval_cycle', $currentCycle)
                ->where('status', 'pending')
                ->orderBy('id', 'asc')
                ->first();

            if ($firstPendingRow) {
                $firstPendingRow->update(['status' => 'approved', 'current_count' => 1]);
            }

            $this->si_approval_stage = 'headoffice_pending';
            $this->am_approval_status = 'pending';
            $this->saveQuietly();

            return true;
        }

        if ($currentStage === 'headoffice_pending') {
            ApprovalLog::create([
                'module_id'     => $moduleId,
                'record_id'     => $this->id,
                'user_id'       => $user->id,
                'role_id'       => $roleId,
                'action'        => 'approved',
                'status'        => 'active',
                'approval_cycle' => $currentCycle,
                'comments'      => $comments ?: 'Head Office Approved',
            ]);

            $hoPendingRow = $this->approvalRows()
                ->where('module_id', $moduleId)
                ->where('approval_cycle', $currentCycle)
                ->where('status', 'pending')
                ->orderBy('id', 'asc')
                ->first();

            if ($hoPendingRow) {
                $hoPendingRow->update(['status' => 'approved', 'current_count' => 1]);
            }

            $this->si_approval_stage = 'approved';
            $this->am_approval_status = 'approved';
            $this->am_change_made = 1;
            $this->saveQuietly();

            $this->onApprovalComplete();

            return true;
        }

        return false;
    }

    public function reject($comments = null): bool
    {
        $user = Auth::user();
        if (!$user || !$this->canAct($user)) return false;

        $module = $this->getApprovalModule();
        $moduleId = $module ? $module->id : 21;
        $currentCycle = $this->getCurrentApprovalCycle();
        $userRole = $user->roles()->latest()->first();
        $roleId = $userRole ? $userRole->id : 1;

        $this->approvalLogs()
            ->where('module_id', $moduleId)
            ->where('approval_cycle', $currentCycle)
            ->where('status', 'active')
            ->update(['status' => 'inactive']);

        ApprovalLog::create([
            'module_id'     => $moduleId,
            'record_id'     => $this->id,
            'user_id'       => $user->id,
            'role_id'       => $roleId,
            'action'        => 'rejected',
            'status'        => 'active',
            'approval_cycle' => $currentCycle,
            'comments'      => $comments ?: 'Declined',
        ]);

        $this->approvalRows()
            ->where('module_id', $moduleId)
            ->where('approval_cycle', $currentCycle)
            ->update(['status' => 'rejected']);

        $this->si_approval_stage = 'rejected';
        $this->am_approval_status = 'rejected';
        $this->am_change_made = 0;
        $this->saveQuietly();

        $this->onApprovalRejected();

        return true;
    }

    public function revert($comments = null): bool
    {
        $user = Auth::user();
        if (!$user || !$this->canAct($user)) return false;

        $module = $this->getApprovalModule();
        $moduleId = $module ? $module->id : 21;
        $currentCycle = $this->getCurrentApprovalCycle();
        $userRole = $user->roles()->latest()->first();
        $roleId = $userRole ? $userRole->id : 1;

        $this->approvalLogs()
            ->where('module_id', $moduleId)
            ->where('approval_cycle', $currentCycle)
            ->where('status', 'active')
            ->update(['status' => 'inactive']);

        ApprovalLog::create([
            'module_id'     => $moduleId,
            'record_id'     => $this->id,
            'user_id'       => $user->id,
            'role_id'       => $roleId,
            'action'        => 'reverted',
            'status'        => 'active',
            'approval_cycle' => $currentCycle,
            'comments'      => $comments ?: 'Reverted for modification',
        ]);

        $this->approvalRows()
            ->where('module_id', $moduleId)
            ->where('approval_cycle', $currentCycle)
            ->update(['status' => 'reverted']);

        $this->si_approval_stage = 'reverted';
        $this->am_approval_status = 'reverted';
        $this->am_change_made = 0;
        $this->saveQuietly();

        $this->onApprovalReverted();

        return true;
    }

    public function resubmitAfterEdit(): void
    {
        $this->createNewApprovalCycle();
    }

    protected function onApprovalComplete() {}
    protected function onApprovalRejected() {}
    protected function onApprovalReverted() {}
}
