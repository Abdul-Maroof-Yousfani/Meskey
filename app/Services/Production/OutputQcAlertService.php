<?php

namespace App\Services\Production;

use App\Http\Controllers\Production\ProductionAnalysisRequestController;
use App\Models\Production\JobOrder\JobOrder;
use App\Models\Production\ProductionAnalysis;
use App\Models\Production\ProductionAnalysisRequest;
use App\Models\Production\ProductionOutputAlert;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Output Analysis QC alerts.
 * A result outside its Job Order spec (same rule as the yellow color on the form) raises an alert
 * and notifies the users the plant is assigned to. Their remarks create a new Output Analysis
 * request; if that re-check also has a difference, a new alert starts the same flow again.
 */
class OutputQcAlertService
{
    /**
     * Create, refresh or remove the alert of an Output Analysis after it was saved.
     * An alert that already has remarks is never changed.
     */
    public function syncForAnalysis(ProductionAnalysis $analysis): ?ProductionOutputAlert
    {
        if ($analysis->production_analysis_type !== 'output') {
            return null;
        }

        $analysis->loadMissing(['jobOrders', 'items.slabs', 'plant', 'analysisRequest']);
        $jobOrderId = $analysis->jobOrders->first()?->id ?? $analysis->analysisRequest?->job_order_id;
        $checklist = $this->checklist($analysis, $jobOrderId);
        $differences = collect($checklist)->where('status', 'warn')->count();

        $alert = ProductionOutputAlert::where('production_analysis_id', $analysis->id)->latest('id')->first();

        if ($alert && !$alert->isPending()) {
            return $alert;
        }

        if ($differences === 0) {
            // Values were corrected before anyone added remarks
            $alert?->delete();
            return null;
        }

        $data = [
            'company_id' => $analysis->plant?->company_id ?? getCurrentCompany()?->id,
            'analysis_request_id' => $analysis->analysis_request_id,
            'company_location_id' => $analysis->location_id,
            'arrival_location_id' => $analysis->arrival_location_id,
            'plant_id' => $analysis->plant_id,
            'job_order_id' => $jobOrderId,
            'product_id' => $analysis->product_id,
            'checklist' => $checklist,
            'difference_count' => $differences,
        ];

        if ($alert) {
            $alert->update($data);
            return $alert;
        }

        // A re-check request points to the alert it came from
        $parent = $analysis->analysisRequest?->output_alert_id
            ? ProductionOutputAlert::find($analysis->analysisRequest->output_alert_id)
            : null;

        // Lock while numbering so two alerts on the same day don't get the same code
        $alert = DB::transaction(fn() => ProductionOutputAlert::create($data + [
            'alert_no' => ProductionOutputAlert::generateNumber(now(), $analysis->location_id),
            'production_analysis_id' => $analysis->id,
            'parent_alert_id' => $parent?->id,
            'attempt' => $parent ? $parent->attempt + 1 : 1,
            'status' => ProductionOutputAlert::STATUS_PENDING,
        ]));

        $this->notifyPlantUsers($alert);

        return $alert;
    }

    /**
     * QC Checklist of the analysis against the job order specs.
     * Max (or no type): result <= spec is ok; Min: result >= spec is ok. Otherwise a difference.
     */
    public function checklist(ProductionAnalysis $analysis, $jobOrderId): array
    {
        $values = $analysis->items->flatMap(fn($item) => $item->slabs)
            ->pluck('production_analysis_value', 'slab_type_id');

        $specs = $jobOrderId
            ? JobOrder::with('specifications.productSlabType')->find($jobOrderId)?->specifications ?? collect()
            : collect();

        return $specs
            ->filter(fn($spec) => $spec->product_slab_type_id)
            ->unique('product_slab_type_id')
            ->map(function ($spec) use ($values) {
                $result = $values[$spec->product_slab_type_id] ?? null;
                return [
                    'slab_type_id' => (int) $spec->product_slab_type_id,
                    'name' => $spec->productSlabType?->name ?? $spec->spec_name,
                    'qc_symbol' => $spec->productSlabType?->qc_symbol ?: ($spec->uom ?: '%'),
                    'spec_text' => $this->specText($spec),
                    'spec_value' => $spec->spec_value,
                    'value_type' => $spec->value_type,
                    'result' => $result,
                    'status' => $this->status($result, $spec->spec_value, $spec->value_type),
                ];
            })
            ->values()
            ->all();
    }

    private function status($result, $specValue, $valueType): string
    {
        if (!is_numeric($result) || !is_numeric($specValue)) {
            return 'none';
        }

        $ok = strtolower((string) $valueType) === 'min'
            ? (float) $result >= (float) $specValue
            : (float) $result <= (float) $specValue;

        return $ok ? 'ok' : 'warn';
    }

    private function specText($spec): string
    {
        if ($spec->spec_value === null || $spec->spec_value === '') {
            return 'No spec';
        }

        $type = $spec->value_type ? ucfirst($spec->value_type) . ' ' : '';

        return $type . $spec->spec_value . ($spec->uom ? ' ' . $spec->uom : '');
    }

    /** Active users of the alert's company who have its plant assigned. */
    public function plantUsers(ProductionOutputAlert $alert): Collection
    {
        if (!$alert->plant_id) {
            return collect();
        }

        $userIds = DB::table('company_user_role')
            ->when($alert->company_id, fn($q) => $q->where('company_id', $alert->company_id))
            ->whereNotNull('plants')
            ->get(['user_id', 'plants'])
            ->filter(fn($row) => in_array((int) $alert->plant_id, array_map('intval', (array) (json_decode($row->plants, true) ?? [])), true))
            ->pluck('user_id')
            ->unique();

        return User::whereIn('id', $userIds)->where('status', 'active')->get();
    }

    public function canRemark(ProductionOutputAlert $alert, ?User $user = null): bool
    {
        $user = $user ?: auth()->user();

        return $user && $this->plantUsers($alert)->contains('id', $user->id);
    }

    private function notifyPlantUsers(ProductionOutputAlert $alert): void
    {
        try {
            $users = $this->plantUsers($alert);
            if ($users->isEmpty()) {
                return;
            }

            $alert->loadMissing(['plant', 'jobOrder']);
            $url = route('production-output-alert.index', ['view_id' => $alert->id]);
            $recheck = $alert->attempt > 1 ? ' (Re-check #' . ($alert->attempt - 1) . ')' : '';
            $message = '<a href="' . $url . '" style="color: inherit; text-decoration: none;">'
                . 'QC Alert <strong>' . $alert->alert_no . '</strong>' . $recheck . ': Output Analysis has '
                . '<strong>' . $alert->difference_count . '</strong> QC difference(s)'
                . ' on Plant <strong>' . e($alert->plant?->name ?? '-') . '</strong>'
                . ($alert->jobOrder ? ', Job Order <strong>' . e($alert->jobOrder->job_order_no) . '</strong>' : '')
                . '. Please add remarks.</a>';

            NotificationService::send($users, $message, 'Output QC Alert', $url, auth()->user()?->id, $alert->company_id);
        } catch (\Throwable $e) {
            Log::error('Output QC Alert notification error: ' . $e->getMessage());
        }
    }

    /** Save the plant user's remarks and create the re-check Output Analysis request. */
    public function resolve(ProductionOutputAlert $alert, string $remarks): ProductionAnalysisRequest
    {
        return DB::transaction(function () use ($alert, $remarks) {
            $source = $alert->analysisRequest;
            $date = now()->toDateString();

            $request = ProductionAnalysisRequest::create([
                'request_no' => ProductionAnalysisRequestController::generateNumber($date, $alert->company_location_id),
                'request_date' => $date,
                'company_id' => $alert->company_id ?? $source?->company_id,
                'company_location_id' => $alert->company_location_id,
                'arrival_location_id' => $alert->arrival_location_id,
                'plant_id' => $alert->plant_id,
                'job_order_id' => $alert->job_order_id,
                'production_voucher_id' => $source?->production_voucher_id,
                'machine_plan_setting_id' => $source?->machine_plan_setting_id,
                'output_alert_id' => $alert->id,
                'type' => ProductionAnalysisRequest::TYPE_OUTPUT,
                'remarks' => 'Re-check for QC Alert ' . $alert->alert_no . ': ' . \Illuminate\Support\Str::limit($remarks, 200),
                'status' => 'pending',
                'is_auto_generated' => false,
                // auth()->id() returns the username in this app; created_by needs the numeric id
                'created_by' => auth()->user()?->id,
            ]);

            $alert->update([
                'status' => ProductionOutputAlert::STATUS_RESOLVED,
                'remarks' => $remarks,
                'remarked_by' => auth()->user()?->id,
                'remarked_at' => now(),
                'new_analysis_request_id' => $request->id,
            ]);

            return $request;
        });
    }
}
