<?php

namespace App\View\Components;

use Illuminate\View\Component;

class AuditLogs extends Component
{
    public $logs;
    public $title;
    public $subtitle;
    public $emptyMessage;

    public function __construct($logs = [], $title = 'Audit Logs', $subtitle = null, $emptyMessage = null)
    {
        $this->logs = $logs;
        $this->title = $title;
        $this->subtitle = $subtitle;
        $this->emptyMessage = $emptyMessage ?? 'No edits or updates have been recorded yet.';
    }

    public function render()
    {
        return view('components.audit-logs');
    }
}
