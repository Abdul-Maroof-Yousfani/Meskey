<?php

namespace App\Traits;

use App\Models\ApprovalsModule\ApprovalLog;
use App\Models\ApprovalsModule\ApprovalModule;
use App\Models\ApprovalsModule\ApprovalRow;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

trait HasApprovalSalesOrder
{
    protected $approvalModuleCache = null;

    protected static function bootHasApprovalSalesOrder()
    {
        static::creating(function ($model) {
            if (empty($model->so_approval_stage)) {
                $model->so_approval_stage = 'stage_1_pending';
            }
            if (empty($model->am_approval_status)) {
                $model->am_approval_status = 'pending';
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
        $moduleId = $module ? $module->id : 16;

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
        return strtolower($this->so_approval_stage ?? 'stage_1_pending');
    }

    /**
     * Check if a user has Local-Sales permission.
     */
    public function userHasSalesOrderPermission(User $user): bool
    {
        if ($user->user_type === 'super-admin') {
            return true;
        }

        return $user->can('local-sales') || $user->can('sale-order');
    }

    /**
     * Check if a user is authorized to act (approve/reject/revert) at the current stage.
     */
    public function canAct(User $user = null): bool
    {
        $user = $user ?? Auth::user();
        if (!$user) {
            return false;
        }

        // If main status is rejected
        $mainStatus = strtolower($this->am_approval_status ?? '');
        if ($mainStatus === 'rejected') {
            return false;
        }

        $currentStage = $this->getApprovalStage();
        if ($currentStage === 'rejected') {
            return false;
        }

        // -------------------------------------------------------------
        // STAGE 1: Branch / Parent / Self Approval
        // -------------------------------------------------------------
        if ($currentStage === 'stage_1_pending') {
            $creatorId = $this->created_by ?? Auth::id();
            $creator = User::find($creatorId);

            $parentId = $creator?->parent_user_id ?? $this->parent_user_id;

            // Case A: Creator has a parent user
            if (!empty($parentId)) {
                // The creator CANNOT approve his own order when he has a parent!
                if ($user->id == $creatorId) {
                    return false;
                }

                // 1. Parent user himself can act
                if ($user->id == $parentId) {
                    return true;
                }

                // 2. Any other child user under the same parent who has local-sales / sale-order permission
                if ($user->parent_user_id == $parentId && $this->userHasSalesOrderPermission($user)) {
                    return true;
                }

                return false;
            }

            // Case B: Creator has NO parent user (self-approval at Stage 1)
            if ($creator && $user->id == $creator->id) {
                return true;
            }

            return false;
        }

        // -------------------------------------------------------------
        // STAGE 2: Head Office Approval (Mandatory)
        // -------------------------------------------------------------
        if ($currentStage === 'headoffice_pending') {
            // Must have 'headoffice-saleorder-approval' permission OR be super-admin
            return $user->user_type === 'super-admin' || $user->can('headoffice-saleorder-approval');
        }

        return false;
    }

    /**
     * Determine if current authenticated user (or provided user) can approve at the current stage.
     */
    public function canApprove(User $user = null): bool
    {
        $user = $user ?? Auth::user();
        if (!$user) {
            return false;
        }

        // If changes required (reverted) and not yet modified
        if (isset($this->am_change_made) && $this->am_change_made == 0) {
            return false;
        }

        $mainStatus = strtolower($this->am_approval_status ?? '');
        $hasAmendment = method_exists($this, 'hasPendingDeliveryDateAmendment') && $this->hasPendingDeliveryDateAmendment();
        if ($mainStatus === 'approved' && !$hasAmendment) {
            return false;
        }

        $currentStage = $this->getApprovalStage();
        if ($currentStage === 'approved' && !$hasAmendment) {
            return false;
        }

        $currentCycle = $this->getCurrentApprovalCycle();
        $module = $this->getApprovalModule();
        $moduleId = $module ? $module->id : 16;

        // Check if user already took an approved action in this current cycle
        $alreadyActed = $this->approvalLogs()
            ->where('module_id', $moduleId)
            ->where('approval_cycle', $currentCycle)
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->whereIn('action', ['approved', 'partial_approved'])
            ->exists();
        if ($alreadyActed) {
            return false;
        }

        return $this->canAct($user);
    }

    public function canUserApprove(User $user = null): bool
    {
        return $this->canApprove($user);
    }

    /**
     * Create initial approval rows upon creation.
     */
    public function createApprovalRows()
    {
        $module = $this->getApprovalModule();
        $moduleId = $module ? $module->id : 16;
        $currentCycle = $this->getCurrentApprovalCycle();
        $creator = User::find($this->created_by ?? Auth::id());

        // Resolve role for Stage 1
        $stage1RoleId = 1;
        $parentId = $creator?->parent_user_id ?? $this->parent_user_id;
        if (!empty($parentId)) {
            $parent = User::find($parentId);
            $stage1RoleId = $parent?->roles()?->latest()?->first()?->id ?? ($creator?->roles()?->latest()?->first()?->id ?? 1);
        } elseif ($creator) {
            $stage1RoleId = $creator->roles()?->latest()?->first()?->id ?? 1;
        }

        // Resolve role for Stage 2 (Head Office / Admin)
        $hoRoleId = 1; // Admin role

        // Stage 1 Row
        ApprovalRow::create([
            'module_id' => $moduleId,
            'record_id' => $this->id,
            'role_id' => $stage1RoleId,
            'required_count' => 1,
            'current_count' => 0,
            'approval_cycle' => $currentCycle,
            'status' => 'pending'
        ]);

        // Stage 2 Row
        ApprovalRow::create([
            'module_id' => $moduleId,
            'record_id' => $this->id,
            'role_id' => $hoRoleId,
            'required_count' => 1,
            'current_count' => 0,
            'approval_cycle' => $currentCycle,
            'status' => 'pending'
        ]);
    }

    /**
     * Create a new approval cycle (for Delivery Date Amendment or Re-submission after revert).
     */
    public function createNewApprovalCycle(): int
    {
        $module = $this->getApprovalModule();
        $moduleId = $module ? $module->id : 16;
        $newCycle = $this->getCurrentApprovalCycle() + 1;

        $creator = User::find($this->created_by ?? Auth::id());
        $stage1RoleId = 1;
        $parentId = $creator?->parent_user_id ?? $this->parent_user_id;
        if (!empty($parentId)) {
            $parent = User::find($parentId);
            $stage1RoleId = $parent?->roles()?->latest()?->first()?->id ?? ($creator?->roles()?->latest()?->first()?->id ?? 1);
        } elseif ($creator) {
            $stage1RoleId = $creator->roles()?->latest()?->first()?->id ?? 1;
        }

        $hoRoleId = 1;

        // Stage 1 Row
        ApprovalRow::create([
            'module_id' => $moduleId,
            'record_id' => $this->id,
            'role_id' => $stage1RoleId,
            'required_count' => 1,
            'current_count' => 0,
            'approval_cycle' => $newCycle,
            'status' => 'pending'
        ]);

        // Stage 2 Row
        ApprovalRow::create([
            'module_id' => $moduleId,
            'record_id' => $this->id,
            'role_id' => $hoRoleId,
            'required_count' => 1,
            'current_count' => 0,
            'approval_cycle' => $newCycle,
            'status' => 'pending'
        ]);

        $this->so_approval_stage = 'stage_1_pending';
        $this->am_approval_status = 'pending';
        $this->am_change_made = 1;
        $this->saveQuietly();

        return $newCycle;
    }

    /**
     * Approve the Sales Order according to current sequential stage.
     */
    public function approve($comments = null): bool
    {
        $user = Auth::user();
        if (!$user || !$this->canApprove($user)) {
            return false;
        }

        $module = $this->getApprovalModule();
        $moduleId = $module ? $module->id : 16;
        $currentCycle = $this->getCurrentApprovalCycle();
        $userRole = $user->roles()->latest()->first();
        $roleId = $userRole ? $userRole->id : 1;

        $currentStage = $this->getApprovalStage();

        // -------------------------------------------------------------
        // ACTION AT STAGE 1: Move to Head Office Pending
        // -------------------------------------------------------------
        if ($currentStage === 'stage_1_pending') {
            ApprovalLog::create([
                'module_id' => $moduleId,
                'record_id' => $this->id,
                'user_id' => $user->id,
                'role_id' => $roleId,
                'action' => 'partial_approved',
                'status' => 'active',
                'approval_cycle' => $currentCycle,
                'comments' => $comments ?: 'Stage 1 Approved',
            ]);

            // Mark Stage 1 pending row as approved
            $firstPendingRow = $this->approvalRows()
                ->where('module_id', $moduleId)
                ->where('approval_cycle', $currentCycle)
                ->where('status', 'pending')
                ->orderBy('id', 'asc')
                ->first();

            if ($firstPendingRow) {
                $firstPendingRow->update(['status' => 'approved', 'current_count' => 1]);
            }

            $this->so_approval_stage = 'headoffice_pending';
            $this->am_approval_status = 'pending';
            $this->saveQuietly();

            return true;
        }

        // -------------------------------------------------------------
        // ACTION AT STAGE 2 (HEAD OFFICE): Final Approval
        // -------------------------------------------------------------
        if ($currentStage === 'headoffice_pending') {
            ApprovalLog::create([
                'module_id' => $moduleId,
                'record_id' => $this->id,
                'user_id' => $user->id,
                'role_id' => $roleId,
                'action' => 'approved',
                'status' => 'active',
                'approval_cycle' => $currentCycle,
                'comments' => $comments ?: 'Head Office Approved',
            ]);

            // Mark Stage 2 pending row as approved
            $hoPendingRow = $this->approvalRows()
                ->where('module_id', $moduleId)
                ->where('approval_cycle', $currentCycle)
                ->where('status', 'pending')
                ->orderBy('id', 'asc')
                ->first();

            if ($hoPendingRow) {
                $hoPendingRow->update(['status' => 'approved', 'current_count' => 1]);
            }

            $this->so_approval_stage = 'approved';
            $this->am_approval_status = 'approved';
            $this->am_change_made = 1;
            $this->saveQuietly();

            $this->onApprovalComplete();

            return true;
        }

        return false;
    }

    /**
     * Reject the Sales Order permanently.
     */
    public function reject($comments = null): bool
    {
        $user = Auth::user();
        if (!$user || !$this->canAct($user)) {
            return false;
        }

        $module = $this->getApprovalModule();
        $moduleId = $module ? $module->id : 16;
        $currentCycle = $this->getCurrentApprovalCycle();
        $userRole = $user->roles()->latest()->first();
        $roleId = $userRole ? $userRole->id : 1;

        $this->approvalLogs()
            ->where('module_id', $moduleId)
            ->where('approval_cycle', $currentCycle)
            ->where('status', 'active')
            ->update(['status' => 'inactive']);

        ApprovalLog::create([
            'module_id' => $moduleId,
            'record_id' => $this->id,
            'user_id' => $user->id,
            'role_id' => $roleId,
            'action' => 'rejected',
            'status' => 'active',
            'approval_cycle' => $currentCycle,
            'comments' => $comments ?: 'Declined',
        ]);

        $this->approvalRows()
            ->where('module_id', $moduleId)
            ->where('approval_cycle', $currentCycle)
            ->update(['status' => 'rejected']);

        $this->so_approval_stage = 'rejected';
        $this->am_approval_status = 'rejected';
        $this->am_change_made = 0;
        $this->saveQuietly();

        $this->onApprovalRejected();

        return true;
    }

    /**
     * Revert the Sales Order for modification.
     */
    public function revert($comments = null): bool
    {
        $user = Auth::user();
        if (!$user || !$this->canAct($user)) {
            return false;
        }

        $module = $this->getApprovalModule();
        $moduleId = $module ? $module->id : 16;
        $currentCycle = $this->getCurrentApprovalCycle();
        $userRole = $user->roles()->latest()->first();
        $roleId = $userRole ? $userRole->id : 1;

        $this->approvalLogs()
            ->where('module_id', $moduleId)
            ->where('approval_cycle', $currentCycle)
            ->where('status', 'active')
            ->update(['status' => 'inactive']);

        ApprovalLog::create([
            'module_id' => $moduleId,
            'record_id' => $this->id,
            'user_id' => $user->id,
            'role_id' => $roleId,
            'action' => 'reverted',
            'status' => 'active',
            'approval_cycle' => $currentCycle,
            'comments' => $comments ?: 'Reverted for modification',
        ]);

        $this->approvalRows()
            ->where('module_id', $moduleId)
            ->where('approval_cycle', $currentCycle)
            ->update(['status' => 'reverted']);

        $this->so_approval_stage = 'reverted';
        $this->am_approval_status = 'reverted';
        $this->am_change_made = 0;
        $this->saveQuietly();

        $this->onApprovalReverted();

        return true;
    }

    /**
     * Resubmit Sales Order after edit. Starts a new cycle and resets to Stage 1.
     */
    public function resubmitAfterEdit(): void
    {
        $this->createNewApprovalCycle();
    }

    protected function onApprovalComplete()
    {
        // Handled in SalesOrder model
    }

    protected function onApprovalRejected()
    {
        // Handled in SalesOrder model
    }

    protected function onApprovalReverted()
    {
        // Handled in SalesOrder model
    }
}
