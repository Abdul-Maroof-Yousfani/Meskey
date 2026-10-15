<?php

namespace App\Observers;

use App\Models\AuditLog;
use App\Services\AuditLogService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class UniversalAuditObserver
{
    /**
     * Fields to skip during change tracking.
     */
    protected static array $skipKeys = [
        'id',
        'created_at',
        'updated_at',
        'deleted_at',
        'company_id',
        'am_change_made',
        'remember_token',
        'password',
    ];

    /**
     * Handle the Model "created" event.
     */
    public function created(Model $model): void
    {
        $modelName = self::getModelDisplayName($model);
        $identifier = self::getModelIdentifier($model);
        $action = self::getActionName($model, 'created');

        $amount = isset($model->total_amount) ? (float) $model->total_amount : (isset($model->amount) ? (float) $model->amount : null);
        $amountStr = ($amount !== null && $amount > 0) ? " for Rs. " . number_format($amount, 2) : "";

        $desc = "{$modelName} '{$identifier}' created{$amountStr}.";

        $attributes = $model->getAttributes();
        $newValues = [];
        foreach ($attributes as $k => $v) {
            if (in_array($k, self::$skipKeys)) continue;
            if ($v instanceof \Carbon\Carbon) {
                $v = $v->format('Y-m-d');
            }
            $newValues[$k] = $v;
        }

        if (method_exists($model, 'buildNewReferencesSnapshot')) {
            $extra = $model->buildNewReferencesSnapshot();
            foreach ($extra as $ek => $ev) {
                if ($ev !== null && $ev !== '') {
                    $newValues[$ek] = $ev;
                }
            }
        }

        AuditLogService::log(
            $model,
            $action,
            $desc,
            null,
            $newValues
        );
    }

    /**
     * Handle the Model "updated" event.
     */
    public function updated(Model $model): void
    {
        $modelName = self::getModelDisplayName($model);
        $identifier = self::getModelIdentifier($model);

        // If this record was just created in this request (e.g. setting total_amount right after create):
        if ($model->wasRecentlyCreated) {
            $createdAction = self::getActionName($model, 'created');
            $lastLog = AuditLog::where('model_type', get_class($model))
                ->where('model_id', $model->getKey())
                ->where('action', $createdAction)
                ->latest()
                ->first();

            if ($lastLog) {
                $newVals = is_array($lastLog->new_values) ? $lastLog->new_values : json_decode($lastLog->new_values, true);
                if (isset($model->total_amount)) {
                    $newVals['total_amount'] = (float) $model->total_amount;
                } elseif (isset($model->amount)) {
                    $newVals['amount'] = (float) $model->amount;
                }

                if (method_exists($model, 'buildNewReferencesSnapshot')) {
                    $extra = $model->buildNewReferencesSnapshot();
                    foreach ($extra as $ek => $ev) {
                        if ($ev !== null && $ev !== '') {
                            $newVals[$ek] = $ev;
                        }
                    }
                }

                $amount = isset($model->total_amount) ? (float) $model->total_amount : (isset($model->amount) ? (float) $model->amount : null);
                $amountStr = ($amount !== null && $amount > 0) ? " for Rs. " . number_format($amount, 2) : "";

                $lastLog->update([
                    'new_values'  => $newVals,
                    'description' => "{$modelName} '{$identifier}' created{$amountStr}.",
                ]);
                return;
            }
        }

        $changes = $model->getChanges();
        $oldValues = [];
        $newValues = [];

        foreach ($changes as $key => $newValue) {
            if (in_array($key, self::$skipKeys)) continue;

            $oldValue = $model->getOriginal($key);

            if ($oldValue instanceof \Carbon\Carbon) $oldValue = $oldValue->format('Y-m-d');
            if ($newValue instanceof \Carbon\Carbon) $newValue = $newValue->format('Y-m-d');

            if ($oldValue != $newValue) {
                $oldValues[$key] = $oldValue;
                $newValues[$key] = $newValue;
            }
        }

        // Check for reference snapshot diffs (e.g. Selected References or child line items)
        if (method_exists($model, 'getOldReferencesSnapshot') && method_exists($model, 'buildNewReferencesSnapshot')) {
            $oldExtra = $model->getOldReferencesSnapshot() ?? [];
            $newExtra = $model->buildNewReferencesSnapshot();

            $allKeys = array_unique(array_merge(array_keys($oldExtra), array_keys($newExtra)));
            foreach ($allKeys as $ek) {
                $o = $oldExtra[$ek] ?? null;
                $n = $newExtra[$ek] ?? null;
                if ($o != $n) {
                    $oldValues[$ek] = $o;
                    $newValues[$ek] = $n;
                }
            }
        }

        if (!empty($oldValues) || !empty($newValues)) {
            $action = self::getActionName($model, 'updated');
            $desc = "{$modelName} '{$identifier}' updated.";

            AuditLogService::log(
                $model,
                $action,
                $desc,
                $oldValues,
                $newValues
            );
        }
    }

    /**
     * Handle the Model "deleted" event.
     */
    public function deleted(Model $model): void
    {
        $modelName = self::getModelDisplayName($model);
        $identifier = self::getModelIdentifier($model);
        $action = self::getActionName($model, 'deleted');
        $desc = "{$modelName} '{$identifier}' deleted.";

        $snapshot = [];
        $idKey = isset($model->unique_no) ? 'unique_no' : (isset($model->code) ? 'code' : (isset($model->name) ? 'name' : 'id'));
        $snapshot[$idKey] = $identifier;
        if (isset($model->total_amount)) $snapshot['total_amount'] = (float) $model->total_amount;
        if (isset($model->amount)) $snapshot['amount'] = (float) $model->amount;

        AuditLogService::log(
            $model,
            $action,
            $desc,
            $snapshot,
            null
        );
    }

    /**
     * Handle the Model "restored" event.
     */
    public function restored(Model $model): void
    {
        $modelName = self::getModelDisplayName($model);
        $identifier = self::getModelIdentifier($model);
        $action = self::getActionName($model, 'restored');
        $desc = "{$modelName} '{$identifier}' restored.";

        AuditLogService::log(
            $model,
            $action,
            $desc,
            null,
            ['identifier' => $identifier]
        );
    }

    /**
     * Resolve human-friendly display name for the model.
     */
    public static function getModelDisplayName(Model $model): string
    {
        if (method_exists($model, 'auditModelName')) {
            return $model->auditModelName();
        }
        return ucwords(str_replace('_', ' ', Str::snake(class_basename($model))));
    }

    /**
     * Resolve identifier for the model (e.g. Code, Name, Unique No, ID).
     */
    public static function getModelIdentifier(Model $model): string
    {
        if (method_exists($model, 'auditIdentifier')) {
            return (string) $model->auditIdentifier();
        }
        return (string) ($model->unique_no ?? $model->code ?? $model->name ?? $model->title ?? $model->reference_no ?? "#{$model->getKey()}");
    }

    /**
     * Generate action slug, e.g. receipt_voucher_created or customer_updated.
     */
    public static function getActionName(Model $model, string $event): string
    {
        if (method_exists($model, 'auditActionPrefix')) {
            $prefix = $model->auditActionPrefix();
        } else {
            $prefix = Str::snake(class_basename($model));
        }
        return "{$prefix}_{$event}";
    }
}
