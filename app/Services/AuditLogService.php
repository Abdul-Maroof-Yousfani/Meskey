<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditLogService
{
    /**
     * Log an action on an Eloquent model.
     *
     * @param Model $model
     * @param string $action
     * @param string $description
     * @param array|null $oldValues
     * @param array|null $newValues
     * @param int|null $userId
     * @return AuditLog
     */
    public static function log(
        Model $model,
        string $action,
        string $description,
        ?array $oldValues = null,
        ?array $newValues = null,
        mixed $userId = null
    ): AuditLog {
        $effectiveUserId = null;
        if (is_numeric($userId)) {
            $effectiveUserId = (int) $userId;
        } elseif (Auth::user()) {
            $effectiveUserId = Auth::user()->id;
        } elseif (is_numeric(Auth::id())) {
            $effectiveUserId = (int) Auth::id();
        }

        return AuditLog::create([
            'user_id'     => $effectiveUserId,
            'action'      => $action,
            'description' => $description,
            'model_type'  => get_class($model),
            'model_id'    => $model->getKey(),
            'old_values'  => $oldValues,
            'new_values'  => $newValues,
            'ip_address'  => Request::ip(),
            'user_agent'  => Request::userAgent(),
        ]);
    }

    /**
     * Log an arbitrary action without an explicit model instance.
     */
    public static function logCustom(
        string $action,
        string $description,
        ?string $modelType = null,
        ?int $modelId = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        mixed $userId = null
    ): AuditLog {
        $effectiveUserId = null;
        if (is_numeric($userId)) {
            $effectiveUserId = (int) $userId;
        } elseif (Auth::user()) {
            $effectiveUserId = Auth::user()->id;
        } elseif (is_numeric(Auth::id())) {
            $effectiveUserId = (int) Auth::id();
        }

        return AuditLog::create([
            'user_id'     => $effectiveUserId,
            'action'      => $action,
            'description' => $description,
            'model_type'  => $modelType,
            'model_id'    => $modelId,
            'old_values'  => $oldValues,
            'new_values'  => $newValues,
            'ip_address'  => Request::ip(),
            'user_agent'  => Request::userAgent(),
        ]);
    }
}
