<?php

namespace App\Traits;

use App\Models\ApprovalsModule\ApprovalLog;
use App\Models\ApprovalsModule\ApprovalModule;
use App\Models\ApprovalsModule\ApprovalRow;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

trait HasApproval
{
    protected $approvalModuleCache = null;

    protected static function bootHasApproval()
    {
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

    public function getApprovalStatus()
    {
        $module = $this->getApprovalModule();
        if (!$module) {
            return 'not_required';
        }

        if (isset($this->am_change_made) && $this->am_change_made == 0) {
            return 'changes_required';
        }
        $currentCycle = $this->getCurrentApprovalCycle();
        $approvalRows = $this->approvalRows()->where('module_id', $module->id)->where('approval_cycle', $currentCycle)->get();
        foreach ($approvalRows as $row) {
            if ($row->status === 'rejected') {
                return 'rejected';
            }
            if ($row->status === 'pending') {
                return 'pending';
            }
            if ($row->status === 'partial_approved') {
                return 'partial_approved';
            }
        }

        return 'pending';
    }

    public function getCurrentApprovalCycle()
    {
        $module = $this->getApprovalModule();
        if (!$module) {
            return 1;
        }

        return $this->approvalRows()->where('module_id', $module->id)->max('approval_cycle') ?? 1;
    }

    public function createApprovalRows()
    {
        $module = $this->getApprovalModule();
        if (!$module) {
            return;
        }

        $currentCycle = $this->getCurrentApprovalCycle();

        foreach ($module->roles as $moduleRole) {
            ApprovalRow::create([
                'module_id' => $module->id,
                'record_id' => $this->id,
                'role_id' => $moduleRole->role_id,
                'required_count' => $moduleRole->approval_count,
                'current_count' => 0,
                'approval_cycle' => $currentCycle,
                'status' => 'pending'
            ]);
        }
    }

    public function getRequiredApprovals()
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

    public function getCurrentApprovals()
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
        if (isset($this->am_change_made) && $this->am_change_made == 0) {
            return false;
        }

        $userRoleIds = $user->roles->pluck('id')->toArray();
        $requiredRoles = $module->roles->pluck('role_id')->toArray();
        if (empty(array_intersect($userRoleIds, $requiredRoles))) {
            return false;
        }

        if ($this->getApprovalStatus() !== 'pending') {
            return false;
        }

        $currentCycle = $this->getCurrentApprovalCycle();

        $userAlreadyApproved = $this->approvalLogs()
            ->where('module_id', $module->id)
            ->where('approval_cycle', $currentCycle)
            ->where('user_id', $user->id)
            ->where('action', 'approved')
            ->where('status', 'active')
            ->exists();

        if ($userAlreadyApproved) {
            return false;
        }

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
            ->where('status', 'pending')
            ->get();

        foreach ($userApprovalRows as $row) {
            if ($row->current_count < $row->required_count) {
                return true;
            }
        }

        return true;
    }

    public function partial_approve($comments = null)
    {
        $user = Auth::user();
        if (!$user) {
            return false;
        }

        if (!$this->canApprove()) {
            return false;
        }

        $module = $this->getApprovalModule();
        $currentCycle = $this->getCurrentApprovalCycle();
        $userRoleId = $user->roles->first()->id;

        $approvalRow = $this->approvalRows()
            ->where('module_id', $module->id)
            ->where('approval_cycle', $currentCycle)
            ->where('role_id', $userRoleId)
            ->first();

        if ($approvalRow && $approvalRow->current_count < $approvalRow->required_count) {
            $approvalRow->increment('current_count');

            if ($approvalRow->current_count >= $approvalRow->required_count) {
                $approvalRow->update(['status' => 'partial_approved']);
            }
        }

        ApprovalLog::create([
            'module_id' => $module->id,
            'record_id' => $this->id,
            'user_id' => $user->id,
            'role_id' => $userRoleId,
            'action' => 'partial_approved',
            'status' => 'active',
            'approval_cycle' => $currentCycle,
            'comments' => $comments,
        ]);

        if ($this->getApprovalStatus() === 'partial_approved') {
            $this->onPartialApprovalComplete();
        }

        return true;
    }

    public function approve($comments = null)
    {
        $user = Auth::user();
        if (!$user) {
            return false;
        }

        if (!$this->canApprove()) {
            return false;
        }

        $module = $this->getApprovalModule();
        $currentCycle = $this->getCurrentApprovalCycle();
        $userRoleId = $user->roles->first()->id;

        $approvalRow = $this->approvalRows()
            ->where('module_id', $module->id)
            ->where('approval_cycle', $currentCycle)
            ->where('role_id', $userRoleId)
            ->first();

        if ($approvalRow && $approvalRow->current_count < $approvalRow->required_count) {
            $approvalRow->increment('current_count');

            if ($approvalRow->current_count >= $approvalRow->required_count) {
                $approvalRow->update(['status' => 'approved']);
            }
        }

        ApprovalLog::create([
            'module_id' => $module->id,
            'record_id' => $this->id,
            'user_id' => $user->id,
            'role_id' => $userRoleId,
            'action' => 'approved',
            'status' => 'active',
            'approval_cycle' => $currentCycle,
            'comments' => $comments,
        ]);

        if ($this->getApprovalStatus() === 'approved') {
            $this->onApprovalComplete();
        }

        return true;
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

        $currentCycle = $this->getCurrentApprovalCycle();
        $userRoleId = $user->roles->first()->id;

        $this->approvalLogs()
            ->where('module_id', $module->id)
            ->where('approval_cycle', $currentCycle)
            ->where('status', 'active')
            ->update(['status' => 'inactive']);

        $this->approvalRows()
            ->where('module_id', $module->id)
            ->where('approval_cycle', $currentCycle)
            ->update(['status' => 'rejected']);

        ApprovalLog::create([
            'module_id' => $module->id,
            'record_id' => $this->id,
            'user_id' => $user->id,
            'role_id' => $userRoleId,
            'action' => 'rejected',
            'status' => 'active',
            'approval_cycle' => $currentCycle,
            'comments' => $comments,
        ]);

        $this->createNewApprovalCycle();

        $this->onApprovalRejected();

        return true;
    }

    public function revert($comments = null)
    {
        $user = Auth::user();
        if (!$user) {
            return false;
        }

        $module = $this->getApprovalModule();
        if (!$module) {
            return false;
        }

        $currentCycle = $this->getCurrentApprovalCycle();
        $userRoleId = $user->roles->first()->id;

        $this->approvalLogs()
            ->where('module_id', $module->id)
            ->where('approval_cycle', $currentCycle)
            ->where('status', 'active')
            ->update(['status' => 'inactive']);

        $this->approvalRows()
            ->where('module_id', $module->id)
            ->where('approval_cycle', $currentCycle)
            ->update(['status' => 'reverted']);

        // Create rejection log
        ApprovalLog::create([
            'module_id' => $module->id,
            'record_id' => $this->id,
            'user_id' => $user->id,
            'role_id' => $userRoleId,
            'action' => 'reverted',
            'status' => 'active',
            'approval_cycle' => $currentCycle,
            'comments' => $comments,
        ]);

        $this->createNewApprovalCycle();

        $this->onApprovalReverted();

        return true;
    }


    protected function createNewApprovalCycle()
    {
        $module = $this->getApprovalModule();
        if (!$module) {
            return;
        }

        $newCycle = $this->getCurrentApprovalCycle() + 1;

        foreach ($module->roles as $moduleRole) {
            ApprovalRow::create([
                'module_id' => $module->id,
                'record_id' => $this->id,
                'role_id' => $moduleRole->role_id,
                'required_count' => $moduleRole->approval_count,
                'current_count' => 0,
                'approval_cycle' => $newCycle,
                'status' => 'pending'
            ]);
        }
    }

    protected function onApprovalComplete()
    {
        $module = $this->getApprovalModule();

        if (isset($module->approval_column, $this->{$module->approval_column})) {
            $this->update([$module->approval_column => 'approved']);
        }

        if (isset($this->am_change_made)) {
            $this->update(['am_change_made' => 1]);
        }
    }

    protected function onPartialApprovalComplete()
    {
        $module = $this->getApprovalModule();

        if (isset($module->approval_column, $this->{$module->approval_column})) {
            $this->update([$module->approval_column => 'partial approved']);
        }

        if (isset($this->am_change_made)) {
            $this->update(['am_change_made' => 1]);
        }
    }

    protected function onApprovalRejected()
    {
        $module = $this->getApprovalModule();

        if (isset($module->approval_column, $this->{$module->approval_column})) {
            $this->update([$module->approval_column => 'rejected']);
        }

        if (isset($this->am_change_made)) {
            $this->update(['am_change_made' => 0]);
        }
    }

    protected function onApprovalReverted()
    {
        $module = $this->getApprovalModule();

        if (isset($module->approval_column, $this->{$module->approval_column})) {
            $this->update([$module->approval_column => 'reverted']);
        }

        if (isset($this->am_change_made)) {
            $this->update(['am_change_made' => 0]);
        }
    }
}
