<?php

namespace App\Models\ApprovalsModule;

use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role;

class ApprovalModuleRole extends Model
{
    protected $fillable = [
        'module_id',
        'role_id',
        'approval_count',
        'approval_order'
    ];

    public function module()
    {
        return $this->belongsTo(ApprovalModule::class);
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }
}
