<?php

namespace App\Http\Controllers;

use App\Services\AuditLogService;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    /**
     * Universal modal endpoint to render audit logs for any supported model.
     *
     * @param Request $request
     * @param string $modelType
     * @param int|string $id
     * @return \Illuminate\View\View
     */
    public function showModal(Request $request, $modelType = null, $id = null)
    {
        $resolvedModelType = $request->route('modelType') ?: $modelType;

        $targetId = $request->route('id')
            ?? $request->route('ticket')
            ?? $request->route('productId')
            ?? $request->route('userId')
            ?? $id;

        if ($targetId === null && is_numeric($modelType)) {
            $targetId = $modelType;
            $resolvedModelType = $request->route('modelType') ?? 'receipt-voucher';
        }

        if ($targetId instanceof \Illuminate\Database\Eloquent\Model) {
            $targetId = $targetId->getKey();
        }

        $modelType = $resolvedModelType ?: 'receipt-voucher';
        $id = $targetId;

        $modelClass = AuditLogService::resolveModelClass($modelType);

        if (!$modelClass || !class_exists($modelClass)) {
            return view('components.audit-logs', [
                'logs'         => [],
                'title'        => 'Audit Logs',
                'subtitle'     => null,
                'emptyMessage' => 'Model type not recognized or supported.',
            ]);
        }

        try {
            $query = method_exists($modelClass, 'withTrashed')
                ? $modelClass::withTrashed()
                : $modelClass::query();

            $model = $query->find($id);

            if (!$model) {
                return view('components.audit-logs', [
                    'logs'         => [],
                    'title'        => 'Audit Logs',
                    'subtitle'     => null,
                    'emptyMessage' => 'Record not found.',
                ]);
            }

            $options = [];
            if (in_array($modelType, ['payment-request', 'payment_request', 'ticket-payment-request'])) {
                $options['title'] = 'Payment Request Audit Logs';
            }

            return AuditLogService::renderLogs($model, $options);
        } catch (\Throwable $e) {
            return view('components.audit-logs', [
                'logs'         => [],
                'title'        => 'Audit Logs',
                'subtitle'     => null,
                'emptyMessage' => 'Unable to load audit logs at this time.',
            ]);
        }
    }
}
