<?php

namespace App\Models\ApprovalsModule;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role;

class ApprovalLog extends Model
{
    protected $fillable = [
        'module_id',
        'record_id',
        'user_id',
        'role_id',
        'action',
        'comments'
    ];

    public function module()
    {
        return $this->belongsTo(ApprovalModule::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }
}
