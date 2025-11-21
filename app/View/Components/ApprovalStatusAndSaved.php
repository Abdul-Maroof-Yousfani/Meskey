<?php

namespace App\View\Components;

use App\Traits\HasApproval;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class ApprovalStatusAndSaved extends Component
{
     use HasApproval;

    public $model;
    public $module;
    public $requiredApprovals;
    public $currentApprovals;
    public $approvalLogs;

    public function __construct($model)
    {
        $this->model = $model;
        $this->module = $model->getApprovalModule();
        // $this->requiredApprovals = $model->getRequiredApprovals();
        // $this->currentApprovals = $model->getCurrentApprovals();
        // $this->approvalLogs = $model->approvalLogs()->with('user', 'role')->latest()->get();
    }

    public function render()
    {
        return view('components.approval-status-and-saved');
    }
}
