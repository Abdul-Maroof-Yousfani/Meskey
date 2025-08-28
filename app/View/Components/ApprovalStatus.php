<?php

namespace App\View\Components;

use Illuminate\View\Component;
use App\Traits\HasApproval;

class ApprovalStatus extends Component
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
        return view('components.approval-status');
    }
}
