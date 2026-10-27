<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditLogService
{
    /**
     * Cache for recently logged signatures in current request.
     */
    protected static array $recentlyLogged = [];

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
        $modelClass = get_class($model);
        $modelId = (string) $model->getKey();

        // Normalize values for comparison
        $normOld = is_array($oldValues) ? $oldValues : [];
        $normNew = is_array($newValues) ? $newValues : [];
        ksort($normOld);
        ksort($normNew);
        $diffSig = md5(json_encode($normOld) . json_encode($normNew));
        $cacheKey = "{$modelClass}:{$modelId}:{$diffSig}";

        if (isset(self::$recentlyLogged[$cacheKey])) {
            return self::$recentlyLogged[$cacheKey];
        }

        // Check if an identical log was just created in DB within the last 2 seconds
        $recent = AuditLog::where(function ($q) use ($modelClass) {
                $q->where('model_type', $modelClass)
                  ->orWhere('model_type', class_basename($modelClass));
            })
            ->where('model_id', $modelId)
            ->where('created_at', '>=', now()->subSeconds(2))
            ->latest('id')
            ->first();

        if ($recent) {
            $dbOld = is_array($recent->old_values) ? $recent->old_values : json_decode($recent->old_values, true);
            $dbNew = is_array($recent->new_values) ? $recent->new_values : json_decode($recent->new_values, true);
            if (is_array($dbOld)) ksort($dbOld);
            if (is_array($dbNew)) ksort($dbNew);
            if (md5(json_encode($dbOld ?? []) . json_encode($dbNew ?? [])) === $diffSig) {
                self::$recentlyLogged[$cacheKey] = $recent;
                return $recent;
            }
        }

        $effectiveUserId = null;
        if (is_numeric($userId)) {
            $effectiveUserId = (int) $userId;
        } elseif (Auth::user()) {
            $effectiveUserId = Auth::user()->id;
        } elseif (is_numeric(Auth::id())) {
            $effectiveUserId = (int) Auth::id();
        } else {
            $effectiveUserId = 1;
        }

        $log = AuditLog::create([
            'user_id'     => $effectiveUserId,
            'action'      => $action,
            'description' => $description,
            'model_type'  => $modelClass,
            'model_id'    => $modelId,
            'old_values'  => $oldValues,
            'new_values'  => $newValues,
            'ip_address'  => Request::ip(),
            'user_agent'  => Request::userAgent(),
        ]);

        self::$recentlyLogged[$cacheKey] = $log;
        return $log;
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
        } else {
            $effectiveUserId = 1;
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

    /**
     * Cache for resolved relation names (e.g. accounts, customers).
     */
    protected static array $relationCache = [];

    /**
     * Render the audit logs modal view for a given model.
     *
     * @param Model|null $model
     * @param array $options
     * @return \Illuminate\View\View
     */
    public static function renderLogs(?Model $model, array $options = [])
    {
        if (!$model) {
            return view('components.audit-logs', [
                'logs'         => [],
                'title'        => 'Audit Logs',
                'subtitle'     => null,
                'emptyMessage' => $options['empty_message'] ?? 'Record not found or no logs available.',
            ]);
        }

        // Merge options with model auditLogConfig if present
        $modelConfig = method_exists($model, 'auditLogConfig') ? $model::auditLogConfig() : [];
        $options = array_replace_recursive($modelConfig, $options);

        $modelClass = get_class($model);
        $modelBasename = class_basename($model);
        $modelId = $model->getKey();

        $isMasterModel = in_array($modelBasename, [
            'Broker', 'Customer', 'Supplier', 'Vendor', 'Transporter', 'Bank', 'User', 'Product'
        ]);

        $includeForeignKeys = $options['include_foreign_keys'] ?? !$isMasterModel;

        $foreignKeys = $includeForeignKeys
            ? [strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $modelBasename)) . '_id']
            : ($options['foreign_keys'] ?? []);
        $modelTypes = [$modelClass, $modelBasename, 'App\\Models\\' . $modelBasename];

        $extraModelTypeIds = [];
        if ($modelBasename === 'Product') {
            $modelTypes[] = \App\Models\Master\ProductSlab::class;
            $modelTypes[] = 'ProductSlab';
            $modelTypes[] = 'App\Models\Master\ProductSlab';
        } elseif ($modelBasename === 'ArrivalTicket' || $modelBasename === 'PurchaseTicket') {
            $foreignKeys[] = 'ticket_id';
            $foreignKeys[] = 'arrival_ticket_id';
            $foreignKeys[] = 'purchase_ticket_id';
            $modelTypes[] = \App\Models\Arrival\ArrivalTicket::class;
            $modelTypes[] = \App\Models\PurchaseTicket::class;
            $modelTypes[] = 'ArrivalTicket';
            $modelTypes[] = 'PurchaseTicket';

            if (class_exists(\App\Models\Procurement\PaymentRequestData::class)) {
                $prDataIds = \App\Models\Procurement\PaymentRequestData::where('ticket_id', $modelId)
                    ->orWhere('purchase_ticket_id', $modelId)
                    ->pluck('id')
                    ->toArray();
                if (!empty($prDataIds)) {
                    $extraModelTypeIds[\App\Models\Procurement\PaymentRequestData::class] = $prDataIds;
                    $extraModelTypeIds['PaymentRequestData'] = $prDataIds;

                    if (class_exists(\App\Models\Procurement\PaymentRequest::class)) {
                        $prIds = \App\Models\Procurement\PaymentRequest::whereIn('payment_request_data_id', $prDataIds)
                            ->pluck('id')
                            ->toArray();
                        if (!empty($prIds)) {
                            $extraModelTypeIds[\App\Models\Procurement\PaymentRequest::class] = $prIds;
                            $extraModelTypeIds['PaymentRequest'] = $prIds;
                        }
                    }
                }
            }
        } elseif ($modelBasename === 'ArrivalPurchaseOrder') {
            $foreignKeys[] = 'purchase_order_id';
            $foreignKeys[] = 'arrival_purchase_order_id';

            if (class_exists(\App\Models\Procurement\PaymentRequestData::class)) {
                $poPrDataIds = \App\Models\Procurement\PaymentRequestData::where('purchase_order_id', $modelId)
                    ->pluck('id')
                    ->toArray();
                if (!empty($poPrDataIds)) {
                    $extraModelTypeIds[\App\Models\Procurement\PaymentRequestData::class] = $poPrDataIds;
                    $extraModelTypeIds['PaymentRequestData'] = $poPrDataIds;

                    if (class_exists(\App\Models\Procurement\PaymentRequest::class)) {
                        $poPrIds = \App\Models\Procurement\PaymentRequest::whereIn('payment_request_data_id', $poPrDataIds)
                            ->orWhere('purchase_order_id', $modelId)
                            ->pluck('id')
                            ->toArray();
                        if (!empty($poPrIds)) {
                            $extraModelTypeIds[\App\Models\Procurement\PaymentRequest::class] = $poPrIds;
                            $extraModelTypeIds['PaymentRequest'] = $poPrIds;
                        }
                    }
                }
            }
        } elseif ($modelBasename === 'SalesOrder') {
            $foreignKeys[] = 'sale_order_id';
            $foreignKeys[] = 'sales_order_id';
        } elseif ($modelBasename === 'ArrivalQcReliefParameter') {
            $foreignKeys[] = 'arrival_qc_relief_id';
            $foreignKeys[] = 'arrival_qc_relief_parameter_id';

            $relatedParamIds = \App\Models\Master\ArrivalQcReliefParameter::withTrashed()
                ->where('product_id', $model->product_id)
                ->pluck('id')
                ->toArray();
            if (!empty($relatedParamIds)) {
                $extraModelTypeIds[\App\Models\Master\ArrivalQcReliefParameter::class] = $relatedParamIds;
                $extraModelTypeIds['ArrivalQcReliefParameter'] = $relatedParamIds;
            }
        }

        $modelUniqueNo = $model->unique_no ?? $model->code ?? $model->contract_no ?? $model->jv_no ?? null;

        $logs = AuditLog::with('user')
            ->where(function ($q) use ($modelTypes, $modelId, $foreignKeys, $extraModelTypeIds, $modelUniqueNo, $isMasterModel) {
                $q->where(function ($sq) use ($modelTypes, $modelId) {
                    $sq->whereIn('model_type', $modelTypes)
                        ->where('model_id', $modelId);
                });
                foreach ($extraModelTypeIds as $type => $ids) {
                    if (!empty($ids)) {
                        $q->orWhere(function ($sq) use ($type, $ids) {
                            $sq->where('model_type', $type)
                               ->whereIn('model_id', $ids);
                        });
                    }
                }
                foreach ($foreignKeys as $fk) {
                    $q->orWhere(function ($sq) use ($fk, $modelId) {
                        $sq->where('old_values', 'like', '%"' . $fk . '":' . $modelId . ',%')
                           ->orWhere('old_values', 'like', '%"' . $fk . '":' . $modelId . '}%')
                           ->orWhere('old_values', 'like', '%"' . $fk . '": ' . $modelId . ',%')
                           ->orWhere('old_values', 'like', '%"' . $fk . '": ' . $modelId . '}%')
                           ->orWhere('old_values', 'like', '%"' . $fk . '":"' . $modelId . '"%')
                           ->orWhere('new_values', 'like', '%"' . $fk . '":' . $modelId . ',%')
                           ->orWhere('new_values', 'like', '%"' . $fk . '":' . $modelId . '}%')
                           ->orWhere('new_values', 'like', '%"' . $fk . '": ' . $modelId . ',%')
                           ->orWhere('new_values', 'like', '%"' . $fk . '": ' . $modelId . '}%')
                           ->orWhere('new_values', 'like', '%"' . $fk . '":"' . $modelId . '"%');
                    });
                }
                if (!empty($modelUniqueNo) && !$isMasterModel) {
                    $q->orWhere(function ($sq) use ($modelTypes, $modelUniqueNo) {
                        $sq->whereIn('model_type', $modelTypes)
                           ->where('description', 'like', "%#{$modelUniqueNo}%");
                    });
                }
            })
            ->orderBy('created_at', 'desc')
            ->get();

        if (!empty($options['filter_actions'])) {
            $logs = $logs->filter(function ($log) use ($options) {
                return in_array($log->action, $options['filter_actions']);
            })->values();
        }

        if ($modelBasename === 'ArrivalQcReliefParameter' || !empty($options['exclude_create'])) {
            $logs = $logs->reject(function ($log) {
                $act = strtolower($log->action ?? '');
                return str_contains($act, 'create') || str_contains($act, 'store');
            })->values();
        }

        $fieldLabels = array_merge(self::defaultFieldLabels(), $options['field_labels'] ?? []);
        $skipKeys = array_merge(
            ['id', 'deleted_at', 'created_at', 'updated_at', 'company_id', 'creator_id', 'am_change_made', 'remember_token', 'password', 'broker_one_name', 'broker_two_name', 'broker_three_name', 'supplier_name', 'payment_request_data_id', 'ticket_id', 'purchase_ticket_id', 'arrival_ticket_id', 'purchase_order_id'],
            $options['skip_keys'] ?? []
        );
        $customResolvers = $options['relation_resolvers'] ?? [];

        $filteredLogs = [];
        foreach ($logs as $log) {
            $rawOld = is_array($log->old_values) ? $log->old_values : (!empty($log->old_values) ? json_decode($log->old_values, true) : []);
            if (is_string($rawOld)) $rawOld = json_decode($rawOld, true);

            $rawNew = is_array($log->new_values) ? $log->new_values : (!empty($log->new_values) ? json_decode($log->new_values, true) : []);
            if (is_string($rawNew)) $rawNew = json_decode($rawNew, true);

            $old = self::flattenArray($rawOld ?? []);
            $new = self::flattenArray($rawNew ?? []);

            $parsedChanges = [];
            $allKeys = array_unique(array_merge(array_keys($old ?? []), array_keys($new ?? [])));

            foreach ($allKeys as $key) {
                $baseKey = str_contains($key, '.') ? substr(strrchr($key, '.'), 1) : $key;

                if (in_array($key, $skipKeys) || in_array($baseKey, $skipKeys)) continue;

                $oldVal = $old[$key] ?? null;
                $newVal = $new[$key] ?? null;

                if ($oldVal != $newVal) {
                    $resolver = $customResolvers[$key] ?? $customResolvers[$baseKey] ?? null;
                    $oldResolved = self::resolveFieldValue($baseKey, $oldVal, $resolver);
                    $newResolved = self::resolveFieldValue($baseKey, $newVal, $resolver);

                    $label = $fieldLabels[$key] ?? $fieldLabels[$baseKey] ?? ucwords(str_replace(['_', '.'], ' ', $baseKey));

                    $parsedChanges[] = [
                        'key'      => $key,
                        'base_key' => $baseKey,
                        'label'    => $label,
                        'old'      => is_array($oldResolved) ? json_encode($oldResolved) : $oldResolved,
                        'new'      => is_array($newResolved) ? json_encode($newResolved) : $newResolved,
                        'raw_old'  => $oldVal,
                        'raw_new'  => $newVal,
                    ];
                }
            }

            if (count($parsedChanges) > 1) {
                $parsedChanges = array_values(array_filter($parsedChanges, fn($c) => !in_array($c['key'], ['updated_at', 'created_at']) && !in_array($c['base_key'] ?? '', ['updated_at', 'created_at'])));
            }

            // Deduplicate redundant id + name pairs (e.g. broker_id + broker_name, gala_id + gala_name)
            $redundantPairs = [
                'broker_id' => 'broker_name',
                'broker_one_id' => 'broker_one_name',
                'broker_two_id' => 'broker_two_name',
                'broker_three_id' => 'broker_three_name',
                'accounts_of_id' => 'accounts_of_name',
                'accounts_of' => 'accounts_of_name',
                'supplier_id' => 'supplier_name',
                'station_id' => 'station_name',
                'station' => 'station_name',
                'miller_id' => 'miller_name',
                'gala_id' => 'gala_name',
            ];
            foreach ($redundantPairs as $idKey => $nameKey) {
                $idItem = null;
                $nameItem = null;
                foreach ($parsedChanges as $c) {
                    $cBase = $c['base_key'] ?? $c['key'];
                    if ($cBase === $idKey) $idItem = $c;
                    if ($cBase === $nameKey) $nameItem = $c;
                }
                if ($idItem && $nameItem) {
                    if (!empty($nameItem['old']) || !empty($nameItem['new'])) {
                        foreach ($parsedChanges as &$c) {
                            $cBase = $c['base_key'] ?? $c['key'];
                            if ($cBase === $idKey) {
                                if (!empty($nameItem['old'])) $c['old'] = $nameItem['old'];
                                if (!empty($nameItem['new'])) $c['new'] = $nameItem['new'];
                                break;
                            }
                        }
                        unset($c);
                    }
                    $parsedChanges = array_values(array_filter($parsedChanges, fn($c) => ($c['base_key'] ?? $c['key']) !== $nameKey));
                }
            }

            $log->parsed_changes = $parsedChanges;
            $filteredLogs[] = $log;
        }

        // Deduplicate any logs that recorded identical changes in the same minute
        $dedupedLogs = [];
        $seenSignatures = [];
        foreach ($filteredLogs as $log) {
            $changesKey = md5(json_encode(collect($log->parsed_changes ?? [])->map(fn($c) => ($c['key'] ?? '') . ':' . (is_scalar($c['old'] ?? null) ? $c['old'] : json_encode($c['old'] ?? '')) . '->' . (is_scalar($c['new'] ?? null) ? $c['new'] : json_encode($c['new'] ?? '')))->sort()->values()->all()));
            $timeKey = $log->created_at ? $log->created_at->format('Y-m-d H:i') : 'no_time';
            $sig = "{$timeKey}:{$changesKey}";

            if (isset($seenSignatures[$sig])) {
                continue;
            }
            $seenSignatures[$sig] = true;
            $dedupedLogs[] = $log;
        }

        $logs = $dedupedLogs;

        // Title
        $title = $options['title'] ?? ("Audit Logs: " . ucwords(str_replace('_', ' ', \Illuminate\Support\Str::snake($modelBasename))) . " #" . ($model->unique_no ?? $model->id));

        // Subtitle
        $subtitle = null;
        if (isset($options['subtitle'])) {
            $subtitle = is_callable($options['subtitle']) ? $options['subtitle']($model) : $options['subtitle'];
        } else {
            $parts = [ucwords(str_replace('_', ' ', \Illuminate\Support\Str::snake($modelBasename))) . ' #' . ($model->unique_no ?? $model->id)];
            if (isset($model->total_amount)) {
                $parts[] = 'Rs. ' . number_format((float) $model->total_amount, 2);
            }
            if (isset($model->rv_date)) {
                $parts[] = 'Date: ' . (\Carbon\Carbon::parse($model->rv_date)->format('d M Y'));
            }
            $subtitle = implode(' | ', $parts);
        }

        $emptyMessage = $options['empty_message'] ?? 'No edits or updates have been recorded yet.';

        return view('components.audit-logs', compact('logs', 'title', 'subtitle', 'emptyMessage'));
    }

    /**
     * Resolve a field value into a readable string (handling foreign keys, flags, enums).
     */
    public static function resolveFieldValue(string $key, mixed $val, ?callable $customResolver = null): mixed
    {
        if ($val === null || $val === '') {
            return null;
        }

        if ($customResolver) {
            $result = $customResolver($val, $key);
            if ($result !== null) {
                return $result;
            }
        }

        if (is_array($val)) {
            return json_encode($val);
        }

        // Dates ending with 00:00:00 (clean date display)
        if (is_string($val) && preg_match('/^(\d{4}-\d{2}-\d{2})[ T]00:00:00(\.\d+)?(Z)?$/', $val, $m)) {
            return $m[1];
        }

        // Beneficiary / Party Bank Account (Payment Voucher, Direct PV, etc.)
        if ($key === 'bank_account_id' && (is_numeric($val) || (is_string($val) && ctype_digit((string) $val)))) {
            $cacheKey = "party_bank_{$val}";
            if (isset(self::$relationCache[$cacheKey])) return self::$relationCache[$cacheKey];

            $bank = \App\Models\SupplierCompanyBankDetail::find($val)
                ?? \App\Models\SupplierOwnerBankDetail::find($val)
                ?? \App\Models\CustomerCompanyBankDetail::find($val)
                ?? \App\Models\CustomerOwnerBankDetail::find($val)
                ?? \App\Models\BrokerCompanyBankDetail::find($val)
                ?? \App\Models\BrokerOwnerBankDetail::find($val)
                ?? \App\Models\TransporterCompanyBankDetail::find($val)
                ?? \App\Models\TransporterOwnerBankDetail::find($val)
                ?? \App\Models\VendorCompanyBankDetail::find($val)
                ?? \App\Models\VendorOwnerBankDetail::find($val);

            if ($bank) {
                $bankName = $bank->bank_name ?? 'Bank';
                $title = $bank->account_title ?? ($bank->title ?? '');
                $accNo = $bank->account_number ?? '';
                $res = "{$bankName} - {$title}" . ($accNo ? " ({$accNo})" : "");
                self::$relationCache[$cacheKey] = $res;
                return $res;
            }
        }

        // Accounts
        if (in_array($key, ['account_id', 'entry_account', 'bank_account', 'bank_account_id', 'counter_account_id']) && (is_numeric($val) || (is_string($val) && ctype_digit((string) $val)))) {
            $cacheKey = "account_{$val}";
            if (isset(self::$relationCache[$cacheKey])) return self::$relationCache[$cacheKey];
            if (class_exists(\App\Models\Master\Account\Account::class)) {
                $acc = \App\Models\Master\Account\Account::find($val);
                $res = $acc ? ($acc->name . ($acc->unique_no ? " ({$acc->unique_no})" : "")) : "Account #{$val}";
                self::$relationCache[$cacheKey] = $res;
                return $res;
            }
        }

        // Customers
        if (in_array($key, ['customer_id']) && (is_numeric($val) || (is_string($val) && ctype_digit((string) $val)))) {
            $cacheKey = "customer_{$val}";
            if (isset(self::$relationCache[$cacheKey])) return self::$relationCache[$cacheKey];
            if (class_exists(\App\Models\Master\Customer::class)) {
                $cust = \App\Models\Master\Customer::find($val);
                $res = $cust ? ($cust->name . ($cust->unique_no ? " ({$cust->unique_no})" : "")) : "Customer #{$val}";
                self::$relationCache[$cacheKey] = $res;
                return $res;
            }
        }

        // Suppliers & Accounts Of
        if (in_array($key, ['supplier_id', 'accounts_of_id', 'accounts_of']) && (is_numeric($val) || (is_string($val) && ctype_digit((string) $val)))) {
            $cacheKey = "supplier_{$val}";
            if (isset(self::$relationCache[$cacheKey])) return self::$relationCache[$cacheKey];
            if (class_exists(\App\Models\Master\Supplier::class)) {
                $sup = \App\Models\Master\Supplier::find($val);
                $res = $sup ? ($sup->name . ($sup->unique_no ? " ({$sup->unique_no})" : "")) : "Supplier #{$val}";
                self::$relationCache[$cacheKey] = $res;
                return $res;
            }
        }

        // Transporters
        if (in_array($key, ['transporter_id']) && (is_numeric($val) || (is_string($val) && ctype_digit((string) $val)))) {
            $cacheKey = "transporter_{$val}";
            if (isset(self::$relationCache[$cacheKey])) return self::$relationCache[$cacheKey];
            if (class_exists(\App\Models\Master\Transporter::class)) {
                $trans = \App\Models\Master\Transporter::find($val);
                $res = $trans ? ($trans->name . ($trans->unique_no ? " ({$trans->unique_no})" : "")) : "Transporter #{$val}";
                self::$relationCache[$cacheKey] = $res;
                return $res;
            }
        }

        // Brokers
        if (in_array($key, ['broker_id', 'broker_one_id', 'broker_two_id', 'broker_three_id']) && (is_numeric($val) || (is_string($val) && ctype_digit((string) $val)))) {
            $cacheKey = "broker_{$val}";
            if (isset(self::$relationCache[$cacheKey])) return self::$relationCache[$cacheKey];
            // Check supplier first (Arrival tickets manage brokers as suppliers)
            if (class_exists(\App\Models\Master\Supplier::class)) {
                $sup = \App\Models\Master\Supplier::find($val);
                if ($sup) {
                    $res = $sup->name . ($sup->unique_no ? " ({$sup->unique_no})" : "");
                    self::$relationCache[$cacheKey] = $res;
                    return $res;
                }
            }
            if (class_exists(\App\Models\Master\Broker::class)) {
                $brk = \App\Models\Master\Broker::find($val);
                $res = $brk ? ($brk->name . ($brk->unique_no ? " ({$brk->unique_no})" : "")) : "Broker #{$val}";
                self::$relationCache[$cacheKey] = $res;
                return $res;
            }
        }

        // Stations
        if (in_array($key, ['station_id', 'station']) && (is_numeric($val) || (is_string($val) && ctype_digit((string) $val)))) {
            $cacheKey = "station_{$val}";
            if (isset(self::$relationCache[$cacheKey])) return self::$relationCache[$cacheKey];
            if (class_exists(\App\Models\Master\Station::class)) {
                $st = \App\Models\Master\Station::find($val);
                $res = $st ? $st->name : "Station #{$val}";
                self::$relationCache[$cacheKey] = $res;
                return $res;
            }
        }

        // Truck Types
        if (in_array($key, ['truck_type_id', 'arrival_truck_type_id']) && (is_numeric($val) || (is_string($val) && ctype_digit((string) $val)))) {
            $cacheKey = "truck_type_{$val}";
            if (isset(self::$relationCache[$cacheKey])) return self::$relationCache[$cacheKey];
            if (class_exists(\App\Models\Master\ArrivalTruckType::class)) {
                $tt = \App\Models\Master\ArrivalTruckType::find($val);
                $res = $tt ? $tt->name : "Truck Type #{$val}";
                self::$relationCache[$cacheKey] = $res;
                return $res;
            }
        }

        // Millers
        if (in_array($key, ['miller_id']) && (is_numeric($val) || (is_string($val) && ctype_digit((string) $val)))) {
            $cacheKey = "miller_{$val}";
            if (isset(self::$relationCache[$cacheKey])) return self::$relationCache[$cacheKey];
            if (class_exists(\App\Models\Master\Miller::class)) {
                $mil = \App\Models\Master\Miller::find($val);
                $res = $mil ? $mil->name : "Miller #{$val}";
                self::$relationCache[$cacheKey] = $res;
                return $res;
            }
        }

        // Sauda Types
        if (in_array($key, ['sauda_type_id', 'sauda_type']) && (is_numeric($val) || (is_string($val) && ctype_digit((string) $val)))) {
            $cacheKey = "sauda_type_{$val}";
            if (isset(self::$relationCache[$cacheKey])) return self::$relationCache[$cacheKey];
            if (class_exists(\App\Models\SaudaType::class)) {
                $st = \App\Models\SaudaType::find($val);
                $res = $st ? $st->name : "Sauda Type #{$val}";
                self::$relationCache[$cacheKey] = $res;
                return $res;
            }
        }

        // Arrival Purchase Orders / Contracts
        if (in_array($key, ['arrival_purchase_order_id', 'purchase_order_id']) && (is_numeric($val) || (is_string($val) && ctype_digit((string) $val)))) {
            $cacheKey = "apo_{$val}";
            if (isset(self::$relationCache[$cacheKey])) return self::$relationCache[$cacheKey];
            if (class_exists(\App\Models\ArrivalPurchaseOrder::class)) {
                $po = \App\Models\ArrivalPurchaseOrder::find($val);
                $res = $po ? ($po->contract_no ?? $po->unique_no ?? "PO #{$val}") : "PO #{$val}";
                self::$relationCache[$cacheKey] = $res;
                return $res;
            }
        }

        // Products
        if (in_array($key, ['product_id', 'qc_product', 'arrival_product_id']) && (is_numeric($val) || (is_string($val) && ctype_digit((string) $val)))) {
            $cacheKey = "product_{$val}";
            if (isset(self::$relationCache[$cacheKey])) return self::$relationCache[$cacheKey];
            if (class_exists(\App\Models\Product::class)) {
                $p = \App\Models\Product::find($val);
                $res = $p ? $p->name : "Product #{$val}";
                self::$relationCache[$cacheKey] = $res;
                return $res;
            }
        }

        // Arrival Locations
        if ($key === 'arrival_location_id' && (is_numeric($val) || (is_string($val) && ctype_digit((string) $val)))) {
            $cacheKey = "arrival_location_{$val}";
            if (isset(self::$relationCache[$cacheKey])) return self::$relationCache[$cacheKey];
            if (class_exists(\App\Models\Master\ArrivalLocation::class)) {
                $aloc = \App\Models\Master\ArrivalLocation::find($val);
                if ($aloc) {
                    $res = $aloc->name;
                    self::$relationCache[$cacheKey] = $res;
                    return $res;
                }
            }
        }

        // Company / General Locations
        if (in_array($key, ['location_id', 'company_location_id', 'unloading_location_id']) && (is_numeric($val) || (is_string($val) && ctype_digit((string) $val)))) {
            $cacheKey = "location_{$val}";
            if (isset(self::$relationCache[$cacheKey])) return self::$relationCache[$cacheKey];
            if (class_exists(\App\Models\Master\CompanyLocation::class)) {
                $loc = \App\Models\Master\CompanyLocation::find($val);
                if ($loc) {
                    $res = $loc->name;
                    self::$relationCache[$cacheKey] = $res;
                    return $res;
                }
            }
            if (class_exists(\App\Models\Master\ArrivalLocation::class)) {
                $aloc = \App\Models\Master\ArrivalLocation::find($val);
                if ($aloc) {
                    $res = $aloc->name;
                    self::$relationCache[$cacheKey] = $res;
                    return $res;
                }
            }
        }

        // Gala / Sub Locations
        if (in_array($key, ['gala_id']) && (is_numeric($val) || (is_string($val) && ctype_digit((string) $val)))) {
            $cacheKey = "gala_{$val}";
            if (isset(self::$relationCache[$cacheKey])) return self::$relationCache[$cacheKey];
            if (class_exists(\App\Models\Master\ArrivalSubLocation::class)) {
                $g = \App\Models\Master\ArrivalSubLocation::find($val);
                $res = $g ? $g->name : "Gala #{$val}";
                self::$relationCache[$cacheKey] = $res;
                return $res;
            }
        }

        // Bag Types, Condition, Packing
        if ($key === 'bag_type_id' && (is_numeric($val) || (is_string($val) && ctype_digit((string) $val)))) {
            $cacheKey = "bag_type_{$val}";
            if (isset(self::$relationCache[$cacheKey])) return self::$relationCache[$cacheKey];
            if (class_exists(\App\Models\BagType::class)) {
                $bt = \App\Models\BagType::find($val);
                $res = $bt ? $bt->name : "Bag Type #{$val}";
                self::$relationCache[$cacheKey] = $res;
                return $res;
            }
        }
        if ($key === 'bag_condition_id' && (is_numeric($val) || (is_string($val) && ctype_digit((string) $val)))) {
            $cacheKey = "bag_cond_{$val}";
            if (isset(self::$relationCache[$cacheKey])) return self::$relationCache[$cacheKey];
            if (class_exists(\App\Models\BagCondition::class)) {
                $bc = \App\Models\BagCondition::find($val);
                $res = $bc ? $bc->name : "Bag Condition #{$val}";
                self::$relationCache[$cacheKey] = $res;
                return $res;
            }
        }
        if ($key === 'bag_packing_id' && (is_numeric($val) || (is_string($val) && ctype_digit((string) $val)))) {
            $cacheKey = "bag_pack_{$val}";
            if (isset(self::$relationCache[$cacheKey])) return self::$relationCache[$cacheKey];
            if (class_exists(\App\Models\BagPacking::class)) {
                $bp = \App\Models\BagPacking::find($val);
                $res = $bp ? $bp->name : "Bag Packing #{$val}";
                self::$relationCache[$cacheKey] = $res;
                return $res;
            }
        }

        // Location Types (Hodi, Floor, etc.)
        if (in_array($key, ['location_type_id']) && (is_numeric($val) || (is_string($val) && ctype_digit((string) $val)))) {
            $cacheKey = "location_type_{$val}";
            if (isset(self::$relationCache[$cacheKey])) return self::$relationCache[$cacheKey];
            if (class_exists(\App\Models\Master\LocationType::class)) {
                $lt = \App\Models\Master\LocationType::find($val);
                $res = $lt ? $lt->name : "Location Type #{$val}";
                self::$relationCache[$cacheKey] = $res;
                return $res;
            }
        }

        // Truck Types
        if (in_array($key, ['truck_type_id', 'arrival_truck_type_id']) && (is_numeric($val) || (is_string($val) && ctype_digit((string) $val)))) {
            $cacheKey = "truck_type_{$val}";
            if (isset(self::$relationCache[$cacheKey])) return self::$relationCache[$cacheKey];
            if (class_exists(\App\Models\Master\ArrivalTruckType::class)) {
                $tt = \App\Models\Master\ArrivalTruckType::find($val);
                $res = $tt ? $tt->name : "Truck Type #{$val}";
                self::$relationCache[$cacheKey] = $res;
                return $res;
            }
        }

        // Millers
        if (in_array($key, ['miller_id']) && (is_numeric($val) || (is_string($val) && ctype_digit((string) $val)))) {
            $cacheKey = "miller_{$val}";
            if (isset(self::$relationCache[$cacheKey])) return self::$relationCache[$cacheKey];
            if (class_exists(\App\Models\Master\Miller::class)) {
                $m = \App\Models\Master\Miller::find($val);
                $res = $m ? $m->name : "Miller #{$val}";
                self::$relationCache[$cacheKey] = $res;
                return $res;
            }
        }

        // Divisions
        if (in_array($key, ['division_id']) && (is_numeric($val) || (is_string($val) && ctype_digit((string) $val)))) {
            $cacheKey = "division_{$val}";
            if (isset(self::$relationCache[$cacheKey])) return self::$relationCache[$cacheKey];
            if (class_exists(\App\Models\Master\Division::class)) {
                $d = \App\Models\Master\Division::find($val);
                $res = $d ? $d->name : "Division #{$val}";
                self::$relationCache[$cacheKey] = $res;
                return $res;
            }
        }

        // Calculation types
        if (in_array($key, ['calculation_type', 'broker_one_calculation_type', 'broker_two_calculation_type', 'broker_three_calculation_type'])) {
            $types = [
                'trucks' => 'Trucks Wise',
                'quantity' => 'Quantity Wise',
            ];
            return $types[$val] ?? ucfirst($val);
        }

        // Users & Decision Makers
        if (in_array($key, [
            'user_id', 'created_by', 'updated_by', 'decision_id',
            'ticket_verified_by', 'verified_by', 'creator_id',
            'sample_taken_by', 'approved_by', 'done_by'
        ]) && (is_numeric($val) || (is_string($val) && ctype_digit((string) $val)))) {
            $cacheKey = "user_{$val}";
            if (isset(self::$relationCache[$cacheKey])) return self::$relationCache[$cacheKey];
            if (class_exists(\App\Models\User::class)) {
                $u = \App\Models\User::find($val);
                $res = $u ? $u->name : "User #{$val}";
                self::$relationCache[$cacheKey] = $res;
                return $res;
            }
        }

        // Status fields
        if (in_array($key, [
            'document_approval_status',
            'first_qc_status',
            'second_qc_status',
            'arrival_slip_status',
            'freight_status',
            'location_transfer_status',
            'first_weighbridge_status',
            'second_weighbridge_status',
            'qc_status',
            'status'
        ]) && is_string($val)) {
            return ucwords(str_replace('_', ' ', $val));
        }

        // Lock & Boolean fields
        if ($key === 'is_arrival_lock') {
            return ($val == 1 || $val === true || $val === '1') ? 'Locked' : 'Unlocked';
        }
        if (in_array($key, [
            'is_ticket_verified',
            'is_lumpsum_deduction',
            'freight_paid_by_supplier',
            'is_re_sampling',
            'is_done',
            'is_request_by_purchaser',
            'is_auto_approved',
            'is_resampling_made'
        ])) {
            return ($val == 1 || $val === true || $val === '1') ? 'Yes' : 'No';
        }

        // Taxes
        if (in_array($key, ['tax_id']) && (is_numeric($val) || (is_string($val) && ctype_digit((string) $val)))) {
            $cacheKey = "tax_{$val}";
            if (isset(self::$relationCache[$cacheKey])) return self::$relationCache[$cacheKey];
            if (class_exists(\App\Models\Master\Tax::class)) {
                $tax = \App\Models\Master\Tax::find($val);
                $res = $tax ? ($tax->name . ' (' . (float) $tax->percentage . '%)') : "Tax #{$val}";
                self::$relationCache[$cacheKey] = $res;
                return $res;
            }
        }

        // Voucher Types
        if ($key === 'voucher_type') {
            $map = [
                'bank_payment_voucher' => 'Bank Receipt Voucher (BRV)',
                'cash_payment_voucher' => 'Cash Receipt Voucher (CRV)',
                'bpv' => 'Bank Payment Voucher (BPV)',
                'cpv' => 'Cash Payment Voucher (CPV)',
                'brv' => 'Bank Receipt Voucher (BRV)',
                'crv' => 'Cash Receipt Voucher (CRV)',
                'jv'  => 'Journal Voucher (JV)',
            ];
            return $map[$val] ?? ucwords(str_replace('_', ' ', $val));
        }

        return $val;
    }

    /**
     * Resolve a slug or name to a Model class.
     */
    public static function resolveModelClass(string $modelType): ?string
    {
        $map = [
            'receipt-voucher'         => \App\Models\ReceiptVoucher::class,
            'receipt_voucher'         => \App\Models\ReceiptVoucher::class,
            'customer'                => \App\Models\Master\Customer::class,
            'supplier'                => \App\Models\Master\Supplier::class,
            'transporter'             => \App\Models\Master\Transporter::class,
            'broker'                  => \App\Models\Master\Broker::class,
            'vendor'                  => \App\Models\Master\Vendor::class,
            'product'                 => \App\Models\Product::class,
            'product-slab'            => \App\Models\Product::class,
            'product_slab'            => \App\Models\Product::class,
            'payment-voucher'         => \App\Models\PaymentVoucher::class,
            'payment_voucher'         => \App\Models\PaymentVoucher::class,
            'journal-voucher'         => \App\Models\JournalVoucher::class,
            'journal_voucher'         => \App\Models\JournalVoucher::class,
            'delivery-order'          => \App\Models\Sales\DeliveryOrder::class,
            'delivery_order'          => \App\Models\Sales\DeliveryOrder::class,
            'delivery-challan'        => \App\Models\Sales\DeliveryChallan::class,
            'delivery_challan'        => \App\Models\Sales\DeliveryChallan::class,
            'receiving-request'       => \App\Models\Sales\ReceivingRequest::class,
            'receiving_request'       => \App\Models\Sales\ReceivingRequest::class,
            'sales-return'            => \App\Models\Sales\SalesReturn::class,
            'sales_return'            => \App\Models\Sales\SalesReturn::class,
            'logistics'               => \App\Models\Sales\Logistics::class,
            'sales-order'             => \App\Models\Sales\SalesOrder::class,
            'sales_order'             => \App\Models\Sales\SalesOrder::class,
            'sale-order'              => \App\Models\Sales\SalesOrder::class,
            'sale_order'              => \App\Models\Sales\SalesOrder::class,
            'sales-invoice'           => \App\Models\Sales\SalesInvoice::class,
            'sales_invoice'           => \App\Models\Sales\SalesInvoice::class,
            'bank'                    => \App\Models\Export\Bank::class,
            'ticket'                  => \App\Models\Arrival\ArrivalTicket::class,
            'arrival-ticket'          => \App\Models\Arrival\ArrivalTicket::class,
            'payment-request'         => \App\Models\PurchaseTicket::class,
            'payment_request'         => \App\Models\PurchaseTicket::class,
            'payment-request-approval'=> \App\Models\Procurement\PaymentRequest::class,
            'freight-request'         => \App\Models\Arrival\ArrivalTicket::class,
            'purchase-order'          => \App\Models\ArrivalPurchaseOrder::class,
            'purchase_order'          => \App\Models\ArrivalPurchaseOrder::class,
            'store-purchase-order'    => \App\Models\Procurement\Store\PurchaseOrder::class,
            'advance-payment-request' => \App\Models\ArrivalPurchaseOrder::class,
            'ticket-contracts'         => \App\Models\Arrival\ArrivalTicket::class,
            'verified-contracts'      => \App\Models\Arrival\ArrivalTicket::class,
            'ticket-payment-request'  => \App\Models\Arrival\ArrivalTicket::class,
            'user'                    => \App\Models\User::class,
            'user-test'               => \App\Models\User::class,
            'users-test'              => \App\Models\User::class,
            'arrival-qc-relief'       => \App\Models\Master\ArrivalQcReliefParameter::class,
            'arrival_qc_relief'       => \App\Models\Master\ArrivalQcReliefParameter::class,
        ];

        if (isset($map[$modelType])) {
            return $map[$modelType];
        }

        $studly = \Illuminate\Support\Str::studly($modelType);
        $candidates = [
            "App\\Models\\{$studly}",
            "App\\Models\\Master\\{$studly}",
            "App\\Models\\Finance\\{$studly}",
            "App\\Models\\Sales\\{$studly}",
            "App\\Models\\Procurement\\RawMaterial\\{$studly}",
        ];

        foreach ($candidates as $candidate) {
            if (class_exists($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * Default human-readable labels for common database columns.
     */
    public static function defaultFieldLabels(): array
    {
        return [
            'unique_no'                     => 'Voucher / Code #',
            'rv_date'                       => 'Voucher Date',
            'voucher_type'                  => 'Voucher Type',
            'account_id'                    => 'Account',
            'customer_id'                   => 'Customer',
            'supplier_id'                   => 'Supplier',
            'transporter_id'                => 'Transporter',
            'broker_id'                     => 'Broker',
            'broker_one_id'                 => 'Broker 1',
            'broker_one_commission'         => 'Broker 1 Commission',
            'broker_one_calculation_type'   => 'Broker 1 Calculation Type',
            'broker_two_id'                 => 'Broker 2',
            'broker_two_commission'         => 'Broker 2 Commission',
            'broker_two_calculation_type'   => 'Broker 2 Calculation Type',
            'broker_three_id'               => 'Broker 3',
            'broker_three_commission'       => 'Broker 3 Commission',
            'broker_three_calculation_type' => 'Broker 3 Calculation Type',
            'supplier_commission'           => 'Supplier Commission',
            'calculation_type'              => 'PO Calculation Type',
            'ref_bill_no'                   => 'Reference Bill No',
            'bill_date'                     => 'Bill Date',
            'cheque_no'                     => 'Cheque / Ref No',
            'cheque_date'                   => 'Cheque Date',
            'total_amount'                  => 'Total Amount',
            'remaining_amount'              => 'Remaining Amount',
            'paid_amount'                   => 'Paid Amount',
            'payment_request_amount'        => 'Payment Request Amount',
            'freight_pay_request_amount'    => 'Freight Request Amount',
            'advance_freight'               => 'Advance Freight',
            'lumpsum_deduction'             => 'Lump Sum Deduction',
            'lumpsum_deduction_amount'      => 'Lump Sum Deduction Amount',
            'lumpsum_deduction_kgs'         => 'Lump Sum Deduction (Kg)',
            'lumpsum_deduction_kgs_amount'  => 'Lump Sum Deduction Amount (Kg)',
            'other_deduction_kg'            => 'Other Deduction (Kg)',
            'other_deduction_value'         => 'Other Deduction Value',
            'rerate_on_access_weight_kg'     => 'Rerate Excess Weight (Kg)',
            'rerate_on_access_weight_rate'   => 'Rerate Excess Weight Rate',
            'rerate_on_access_weight_amount' => 'Rerate Excess Weight Amount',
            'other_adjustment_amount'        => 'Other Adjustment Amount',
            'bag_weight'                    => 'Bag Weight',
            'bag_weight_total'              => 'Bag Weight Total',
            'bag_weight_amount'             => 'Bag Weight Amount',
            'bag_rate'                      => 'Bag Rate',
            'bag_rate_amount'               => 'Bag Rate Amount',
            'loading_weighbridge_amount'    => 'Loading Weighbridge Amount',
            'brokery_amount'                => 'Brokery Amount',
            'commission_amount'             => 'Commission Amount',
            'request_type'                  => 'Request Type',
            'module_type'                   => 'Module Type',
            'amount'                        => 'Amount',
            'remarks'                       => 'Remarks',
            'allow_excess_amount'           => 'Allow Excess Amount',
            'is_direct'                     => 'Is Direct',
            'items_count'                   => 'Items Count',
            'status'                        => 'Status',
            'phone'                         => 'Phone',
            'email'                         => 'Email',
            'address'                       => 'Address',
            'ntn'                           => 'NTN',
            'strn'                          => 'STRN',
            'station_id'                    => 'Station',
            'station'                       => 'Station',
            'station_name'                  => 'Station',
            'truck_type_id'                 => 'Truck Type',
            'arrival_truck_type_id'         => 'Truck Type',
            'miller_id'                     => 'Miller',
            'miller_name'                   => 'Miller',
            'decision_id'                   => 'Decision Maker',
            'decision_making'               => 'Decision Making',
            'decision_making_time'          => 'Decision Making Time',
            'accounts_of'                   => 'Accounts Of',
            'accounts_of_id'                => 'Accounts Of',
            'accounts_of_name'              => 'Accounts Of',
            'broker_name'                   => 'Broker',
            'arrival_purchase_order_id'     => 'Purchase Order / Contract',
            'sauda_type_id'                 => 'Sauda Type',
            'sauda_type'                    => 'Sauda Type',
            'product_id'                    => 'Product',
            'qc_product'                    => 'QC Product',
            'arrival_product_id'            => 'Arrival Product',
            'location_id'                   => 'Location',
            'company_location_id'           => 'Company Location',
            'arrival_location_id'           => 'Arrival Warehouse / Location',
            'location_type_id'              => 'Location Type',
            'unloading_location_id'         => 'Unloading Location',
            'gala_id'                       => 'Gala',
            'gala_name'                     => 'Gala',
            'bag_type_id'                   => 'Bag Type',
            'bag_condition_id'              => 'Bag Condition',
            'bag_packing_id'                => 'Bag Packing',
            'bag_packing_approval'          => 'Bag Packing Approval',
            'filling_bags_no'               => 'Filling Bags No',
            'total_bags'                    => 'Total Bags',
            'amanat'                        => 'Amanat',
            'division_id'                   => 'Division',
            'is_ticket_verified'            => 'Ticket Verified',
            'ticket_verified_by'            => 'Verified By',
            'verify_at'                     => 'Verified At',
            'verified_at'                   => 'Verified At',
            'first_qc_status'               => '1st QC Status',
            'second_qc_status'              => '2nd QC Status',
            'document_approval_status'      => 'Document Approval Status',
            'first_weighbridge_status'      => '1st Weighbridge Status',
            'second_weighbridge_status'     => '2nd Weighbridge Status',
            'location_transfer_status'      => 'Location Transfer Status',
            'arrival_slip_status'           => 'Arrival Slip Status',
            'is_arrival_lock'               => 'Arrival Lock Status',
            'locked_at'                     => 'Locked At',
            'unlocked_at'                   => 'Unlocked At',
            'first_weight'                  => 'First Weight (Gross)',
            'second_weight'                 => 'Second Weight (Tare)',
            'net_weight'                    => 'Net Weight',
            'arrived_net_weight'            => 'Arrived Net Weight',
            'loading_weight'                => 'Loading Weight',
            'truck_no'                      => 'Truck #',
            'bilty_no'                      => 'Bilty #',
            'bags'                          => 'Bags',
            'loading_date'                  => 'Loading Date',
            'closing_trucks_qty'            => 'Closing Trucks Qty',
            'sample_money'                  => 'Sample Money',
            'sample_money_type'             => 'Sample Money Type',
            'valid_period'                  => 'Valid Period',
            'configured_parameters'         => 'Configured Parameters',
            'parameter_name'                => 'Parameter Name',
            'relief_type'                   => 'Relief Type',
            'relief_value'                  => 'Relief Value',
            'from_range'                    => 'From Range',
            'to_range'                      => 'To Range',
            'fixed_value'                   => 'Fixed Value',
        ];
    }

    /**
     * Flatten a nested associative array into dot-notation keys.
     */
    public static function flattenArray(mixed $arr, string $prefix = ''): array
    {
        $result = [];
        if (!is_array($arr)) return $result;
        foreach ($arr as $key => $value) {
            $newKey = $prefix === '' ? (string) $key : "{$prefix}.{$key}";
            if (is_array($value) && !empty($value) && !isset($value[0])) {
                $result = array_merge($result, self::flattenArray($value, $newKey));
            } else {
                $result[$newKey] = $value;
            }
        }
        return $result;
    }
}
