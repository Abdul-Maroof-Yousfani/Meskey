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
            } elseif (in_array($modelType, ['advance-payment-request', 'advance_payment_request'])) {
                $options['title'] = 'Advance Payment Request Audit Logs';
                $contractNo = $model->contract_no ?? 'N/A';
                $supplierName = $model->supplier?->name ?? 'N/A';
                $productName = $model->product?->name ?? $model->qcProduct?->name ?? 'N/A';
                $options['subtitle'] = "Contract #{$contractNo} | Supplier: {$supplierName} | Commodity: {$productName}";
                $options['empty_message'] = 'No advance payment requests recorded for this contract yet.';
                $options['filter_actions'] = [
                    'advance_payment_request_created',
                    'advance_payment_request_updated',
                    'advance_payment_request_approval_updated',
                    'payment_request_approval_updated',
                ];
            } elseif (in_array($modelType, ['arrival-qc-relief', 'arrival_qc_relief'])) {
                $productName = $model->product?->name ?? 'Product';
                $locationName = $model->location?->name ?? 'All Locations';
                $options['title'] = "Arrival QC Relief Audit Logs: {$productName}";
                $options['subtitle'] = "Location: {$locationName} | Period: " . ($model->start_date ? $model->start_date->format('d M Y') : 'N/A') . ' to ' . ($model->end_date ? $model->end_date->format('d M Y') : 'N/A');
                $options['exclude_create'] = true;
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
