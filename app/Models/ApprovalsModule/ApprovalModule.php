<?php

namespace App\Models\ApprovalsModule;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApprovalModule extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'model_class',
        'approval_column',
        'is_active',
        'requires_sequential_approval'
    ];

    public function roles(): HasMany
    {
        return $this->hasMany(ApprovalModuleRole::class, 'module_id')
            ->orderBy('approval_order');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(ApprovalLog::class, 'module_id');
    }
}
