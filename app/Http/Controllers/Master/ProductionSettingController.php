<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Master\CompanyLocation;
use App\Models\Master\Plant;
use App\Models\Production\ProductionAnalysisRequest;
use App\Services\Production\Milling\AutoAnalysisRequestService;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ProductionSettingController extends Controller
{
    /**
     * Display the production settings dashboard / tabs.
     */
    public function index(Request $request)
    {
        $fumigationTypes = Setting::getFumigationTypes();

        $user = auth()->user();
        $locationIds = getUserCurrentCompanyLocations();
        $locationPlantsQuery = CompanyLocation::with([
            'city',
            'plants' => function ($q) {
                $q->orderBy('name');
            },
            'plants.arrivalLocation'
        ])->where('status', 'active');

        if ($user && $user->user_type !== 'super-admin' && !empty($locationIds)) {
            $locationPlantsQuery->whereIn('id', $locationIds);
        }

        $locationPlants = $locationPlantsQuery->orderBy('name')->get();

        $service = app(AutoAnalysisRequestService::class);
        $plantStatuses = [];
        foreach ($locationPlants as $loc) {
            foreach ($loc->plants as $p) {
                $plantStatuses[$p->id] = $service->getPlantLiveStatus($p);
            }
        }

        $autoHistoryCountToday = ProductionAnalysisRequest::where('is_auto_generated', true)
            ->whereDate('request_date', now()->toDateString())
            ->count();

        $historyQuery = ProductionAnalysisRequest::with([
            'companyLocation.city',
            'arrivalLocation',
            'plant',
            'jobOrder',
            'productionVoucher',
            'machinePlanSetting.items.machine',
            'productionAnalysis'
        ])
        ->where('is_auto_generated', true);

        if ($user && $user->user_type !== 'super-admin' && !empty($locationIds)) {
            $historyQuery->whereIn('company_location_id', $locationIds);
        }

        $historyRequests = $historyQuery->latest('id')->paginate(15);

        return view('management.master.production_settings.index', compact('fumigationTypes', 'locationPlants', 'plantStatuses', 'autoHistoryCountToday', 'historyRequests'));
    }

    /**
     * Get the HTML table / list for fumigation types (for AJAX refreshes).
     */
    public function getFumigationTypesList(Request $request)
    {
        $fumigationTypes = Setting::getFumigationTypes();
        return view('management.master.production_settings.partials.fumigation_types_table', compact('fumigationTypes'));
    }

    /**
     * Show modal to create a new fumigation type.
     */
    public function createFumigationType()
    {
        return view('management.master.production_settings.modals.add_fumigation_type');
    }

    /**
     * Store a newly created fumigation type.
     */
    public function storeFumigationType(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:100',
        ]);

        $name = trim($request->name);
        $types = Setting::getFumigationTypes();

        foreach ($types as $existing) {
            if (strcasecmp($existing, $name) === 0) {
                return response()->json([
                    'catchError' => 'The fumigation type "' . $name . '" already exists.'
                ], 422);
            }
        }

        $types[] = $name;
        Setting::setFumigationTypes($types);

        return response()->json([
            'success' => 'Fumigation type added successfully.',
            'data' => [
                'name' => $name,
                'types' => $types
            ]
        ], 201);
    }

    /**
     * Show modal to edit an existing fumigation type.
     */
    public function editFumigationType($index)
    {
        $types = Setting::getFumigationTypes();
        $index = (int) $index;

        if (!isset($types[$index])) {
            abort(404, 'Fumigation type not found.');
        }

        $type = $types[$index];
        return view('management.master.production_settings.modals.edit_fumigation_type', compact('type', 'index'));
    }

    /**
     * Update an existing fumigation type.
     */
    public function updateFumigationType(Request $request, $index): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:100',
        ]);

        $index = (int) $index;
        $name = trim($request->name);
        $types = Setting::getFumigationTypes();

        if (!isset($types[$index])) {
            return response()->json([
                'catchError' => 'Fumigation type not found.'
            ], 404);
        }

        foreach ($types as $idx => $existing) {
            if ($idx !== $index && strcasecmp($existing, $name) === 0) {
                return response()->json([
                    'catchError' => 'Another fumigation type with the name "' . $name . '" already exists.'
                ], 422);
            }
        }

        $types[$index] = $name;
        Setting::setFumigationTypes($types);

        return response()->json([
            'success' => 'Fumigation type updated successfully.',
            'data' => [
                'name' => $name,
                'types' => $types
            ]
        ], 200);
    }

    /**
     * Delete a fumigation type.
     */
    public function destroyFumigationType($index): JsonResponse
    {
        $index = (int) $index;
        $types = Setting::getFumigationTypes();

        if (!isset($types[$index])) {
            return response()->json([
                'catchError' => 'Fumigation type not found.'
            ], 404);
        }

        $deletedName = $types[$index];
        unset($types[$index]);
        $types = array_values($types);

        Setting::setFumigationTypes($types);

        return response()->json([
            'success' => 'Fumigation type "' . $deletedName . '" deleted successfully.',
            'data' => [
                'types' => $types
            ]
        ], 200);
    }

    /**
     * Get the HTML table / list for location-wise plants auto analysis settings (for AJAX refreshes).
     */
    public function getPlantAnalysisSettingsTable(Request $request)
    {
        $user = auth()->user();
        $locationIds = getUserCurrentCompanyLocations();
        $locationPlantsQuery = CompanyLocation::with([
            'city',
            'plants' => function ($q) {
                $q->orderBy('name');
            },
            'plants.arrivalLocation'
        ])->where('status', 'active');

        if ($user && $user->user_type !== 'super-admin' && !empty($locationIds)) {
            $locationPlantsQuery->whereIn('id', $locationIds);
        }

        $locationPlants = $locationPlantsQuery->orderBy('name')->get();

        $service = app(AutoAnalysisRequestService::class);
        $plantStatuses = [];
        foreach ($locationPlants as $loc) {
            foreach ($loc->plants as $p) {
                $plantStatuses[$p->id] = $service->getPlantLiveStatus($p);
            }
        }

        return view('management.master.production_settings.partials.plant_analysis_table', compact('locationPlants', 'plantStatuses'));
    }

    /**
     * Update auto analysis interval settings for a single plant.
     */
    public function updatePlantAnalysisSetting(Request $request, Plant $plant): JsonResponse
    {
        $request->validate([
            'auto_analysis_enabled' => 'required|in:0,1',
            'require_last_completed' => 'nullable|in:0,1',
            'input_min_interval' => 'required|integer|min:1|max:1440',
            'input_max_interval' => 'required|integer|gte:input_min_interval|max:1440',
            'output_min_interval' => 'required|integer|min:1|max:1440',
            'output_max_interval' => 'required|integer|gte:output_min_interval|max:1440',
        ], [
            'input_max_interval.gte' => 'Input max interval must be greater than or equal to min interval.',
            'output_max_interval.gte' => 'Output max interval must be greater than or equal to min interval.',
        ]);

        $plant->update([
            'auto_analysis_enabled' => (bool) $request->auto_analysis_enabled,
            'require_last_completed' => (bool) $request->require_last_completed,
            'input_min_interval' => (int) $request->input_min_interval,
            'input_max_interval' => (int) $request->input_max_interval,
            'output_min_interval' => (int) $request->output_min_interval,
            'output_max_interval' => (int) $request->output_max_interval,
            'min_analysis_interval' => (int) $request->input_min_interval,
            'max_analysis_interval' => (int) $request->input_max_interval,
        ]);

        // Immediately recalculate schedule so updated time range takes effect right away
        $service = app(AutoAnalysisRequestService::class);
        $service->recalculatePlantSchedule($plant);

        return response()->json([
            'success' => "Interval settings updated successfully for {$plant->name}.",
            'data' => $plant
        ], 200);
    }

    /**
     * Bulk update auto analysis interval settings for multiple plants across locations.
     */
    public function bulkUpdatePlantAnalysisSettings(Request $request): JsonResponse
    {
        $plantsData = $request->input('plants', []);

        if (empty($plantsData) || !is_array($plantsData)) {
            return response()->json([
                'catchError' => 'No plant data received.'
            ], 422);
        }

        $service = app(AutoAnalysisRequestService::class);
        $updatedCount = 0;
        foreach ($plantsData as $plantId => $data) {
            $plant = Plant::find($plantId);
            if (!$plant) {
                continue;
            }

            $inMin = isset($data['input_min']) ? max(1, (int) $data['input_min']) : 20;
            $inMax = isset($data['input_max']) ? max($inMin, (int) $data['input_max']) : 60;
            $outMin = isset($data['output_min']) ? max(1, (int) $data['output_min']) : 20;
            $outMax = isset($data['output_max']) ? max($outMin, (int) $data['output_max']) : 60;
            $enabled = !empty($data['auto_analysis_enabled']) ? 1 : 0;
            $requireCompleted = !empty($data['require_last_completed']);

            $plant->update([
                'auto_analysis_enabled' => (bool) $enabled,
                'require_last_completed' => $requireCompleted,
                'input_min_interval' => $inMin,
                'input_max_interval' => $inMax,
                'output_min_interval' => $outMin,
                'output_max_interval' => $outMax,
                'min_analysis_interval' => $inMin,
                'max_analysis_interval' => $inMax,
            ]);

            // Immediately recalculate schedule so updated time range takes effect right away
            $service->recalculatePlantSchedule($plant);

            $updatedCount++;
        }

        return response()->json([
            'success' => "Settings updated successfully for {$updatedCount} plant(s).",
            'updated_count' => $updatedCount,
        ], 200);
    }

    /**
     * Manual trigger to run AutoAnalysisRequest check on demand.
     */
    public function triggerAutoCheck(Request $request): JsonResponse
    {
        try {
            $service = app(AutoAnalysisRequestService::class);
            $result = $service->checkAndGenerateRequests();

            $msg = "Checked {$result['total_plants_checked']} plant(s). Created {$result['created_count']} new request(s).";
            if ($result['created_count'] > 0) {
                $details = collect($result['created'])->map(fn($c) => "{$c['request_no']} ({$c['plant_name']})")->join(', ');
                $msg .= " Generated: {$details}";
            } elseif (!empty($result['skipped'])) {
                $skipDetails = collect($result['skipped'])->map(function ($s) {
                    $type = !empty($s['type']) ? ' (' . (str_contains($s['type'], 'input') ? 'Input' : 'Output') . ')' : '';
                    return "{$s['plant_name']}{$type}: {$s['reason']}";
                })->unique()->join(' | ');
                $msg .= " Status: {$skipDetails}";
            }

            return response()->json([
                'success' => $msg,
                'result' => $result,
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'catchError' => 'Failed to trigger auto check: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get the HTML table / list for auto analysis requests generation history.
     */
    public function getAutoAnalysisHistory(Request $request)
    {
        $user = auth()->user();
        $locationIds = getUserCurrentCompanyLocations();

        $query = ProductionAnalysisRequest::with([
            'companyLocation.city',
            'arrivalLocation',
            'plant',
            'jobOrder',
            'productionVoucher',
            'machinePlanSetting.items.machine',
            'productionAnalysis'
        ])
        ->where('is_auto_generated', true);

        if ($user && $user->user_type !== 'super-admin' && !empty($locationIds)) {
            $query->whereIn('company_location_id', $locationIds);
        }

        if ($request->filled('date_range')) {
            $dates = explode(' - ', $request->date_range);
            if (count($dates) === 2) {
                $query->whereBetween('request_date', [trim($dates[0]), trim($dates[1])]);
            }
        } elseif (!$request->has('all_dates') && !$request->filled('search')) {
            // Default to last 7 days
            $query->whereDate('request_date', '>=', now()->subDays(6)->toDateString());
        }

        if ($request->filled('plant_id')) {
            $query->where('plant_id', $request->plant_id);
        }

        if ($request->filled('location_id')) {
            $query->where('company_location_id', $request->location_id);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('request_no', 'LIKE', "%{$search}%")
                  ->orWhere('remarks', 'LIKE', "%{$search}%")
                  ->orWhereHas('plant', fn($pq) => $pq->where('name', 'LIKE', "%{$search}%"))
                  ->orWhereHas('jobOrder', fn($jq) => $jq->where('job_order_no', 'LIKE', "%{$search}%"));
            });
        }

        $historyRequests = $query->latest('id')->paginate(15);

        return view('management.master.production_settings.partials.auto_history_table', compact('historyRequests'));
    }
}
