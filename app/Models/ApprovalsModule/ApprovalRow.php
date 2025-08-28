<?php

namespace App\Models\ApprovalsModule;

use App\Models\ApprovalsModule\ApprovalModule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Permission\Models\Role;

class ApprovalRow extends Model
{
    protected $fillable = [
        'module_id',
        'record_id',
        'role_id',
        'required_count',
        'current_count',
        'approval_cycle',
        'status'
    ];

    public function module(): BelongsTo
    {
        return $this->belongsTo(ApprovalModule::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }
}
