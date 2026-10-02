<?php

namespace App\Http\Controllers\Production\v2;

use App\Http\Controllers\Controller;
use App\Models\Export\ExportOrder;
use App\Models\Master\CompanyLocation;
use App\Models\Master\ProductionPhase;
use App\Models\Product;
use App\Models\ProductionV2\JobOrderV2;
use App\Models\ProductionV2\ProductionPhase1;
use App\Models\ProductionV2\ProductionPhase2;
use App\Models\ProductionV2\ProductionPhase3;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class JobOrderV2Controller extends Controller
{
    public function index()
    {
        $locations = CompanyLocation::where('status', 'active')->orderBy('name')->get();
        return view('management.production.v2.job_orders.index', compact('locations'));
    }

    public function getList(Request $request)
    {
        $query = JobOrderV2::with([
            'companyLocation',
            'product',
            'exportOrder',
            'creator',
            'phase1',
            'phase2',
            'phase3'
        ])
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = '%' . $request->search . '%';
                $q->where(function ($sub) use ($search) {
                    $sub->where('job_order_no', 'like', $search)
                        ->orWhere('ref_no', 'like', $search)
                        ->orWhere('remarks', 'like', $search)
                        ->orWhere('order_description', 'like', $search);
                });
            })
            ->when($request->filled('company_location_id'), function ($q) use ($request) {
                $q->where('company_location_id', $request->company_location_id);
            })
            ->when($request->filled('status'), function ($q) use ($request) {
                $q->where('status', $request->status);
            })
            ->when($request->filled('from_date') && $request->filled('to_date'), function ($q) use ($request) {
                $q->whereBetween('job_order_date', [$request->from_date, $request->to_date]);
            });

        $jobOrders = $query->latest()->paginate($request->get('per_page', 25));
        $allPhases = ProductionPhase::where('status', 'active')->orderBy('id')->get()->keyBy('id');

        return view('management.production.v2.job_orders.getList', compact('jobOrders', 'allPhases'));
    }

    public function create()
    {
        $locations = CompanyLocation::where('status', 'active')->orderBy('name')->get();
        $products = Product::where('status', 'active')->orderBy('name')->get();
        $exportOrders = ExportOrder::where('am_approval_status', 'approved')->latest()->take(50)->get();
        $users = User::get(); // Users for attention_to
        $allPhases = ProductionPhase::where('status', 'active')->orderBy('id')->get();

        return view('management.production.v2.job_orders.create', compact(
            'locations',
            'products',
            'exportOrders',
            'users',
            'allPhases'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'company_location_id' => 'required|exists:company_locations,id',
            'product_id' => 'nullable|exists:products,id',
            'export_order_id' => 'nullable|exists:export_orders,id',
            'job_order_date' => 'required|date',
            'ref_no' => 'nullable|string|max:100',
            'attention_to' => 'nullable|array',
            'order_description' => 'nullable|string',
            'remarks' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $location = CompanyLocation::findOrFail($request->company_location_id);
            $locationPhases = (array)($location->production_phases ?? []);

            $selectedPhases = $request->input('selected_phases');
            if (!empty($selectedPhases) && is_array($selectedPhases)) {
                $phaseIds = array_values(array_intersect(array_map('intval', $selectedPhases), array_map('intval', $locationPhases)));
            } else {
                $phaseIds = array_values(array_map('intval', $locationPhases));
            }

            // Generate unique job order number if not provided or collision exists
            $jobOrderNo = $request->job_order_no;
            if (empty($jobOrderNo) || JobOrderV2::where('job_order_no', $jobOrderNo)->exists()) {
                $jobOrderNo = self::generateUniqueNumber($request->job_order_date, $location);
            }

            $jobOrder = JobOrderV2::create([
                'company_id' => Auth::user()?->company_id,
                'company_location_id' => $location->id,
                'product_id' => $request->product_id ?: null,
                'export_order_id' => $request->export_order_id ?: null,
                'job_order_no' => $jobOrderNo,
                'job_order_date' => $request->job_order_date,
                'ref_no' => $request->ref_no,
                'attention_to' => $request->attention_to ? array_values(array_filter($request->attention_to)) : null,
                'order_description' => $request->order_description,
                'remarks' => $request->remarks,
                'active_phases' => $phaseIds,
                'current_phase' => 'job_order',
                'status' => 'draft',
                'approval_status' => 'pending',
                'maker_id' => Auth::user()?->id,
            ]);

            // Phase 1 (Drying) if enabled
            if (in_array(1, $phaseIds) && $request->filled('p1_drying_mode')) {
                ProductionPhase1::create([
                    'company_id' => Auth::user()?->company_id,
                    'job_order_id' => $jobOrder->id,
                    'company_location_id' => $location->id,
                    'drying_mode' => $request->p1_drying_mode ?? 'batch',
                    'temperature' => $request->p1_temperature,
                    'moisture_level' => $request->p1_moisture_level ?? 'half_dried',
                    'status' => 'pending',
                    'remarks' => $request->p1_remarks,
                ]);
            }

            // Phase 2 (Steaming / Parboiling) if enabled
            if (in_array(2, $phaseIds) && $request->filled('p2_process_type')) {
                ProductionPhase2::create([
                    'company_id' => Auth::user()?->company_id,
                    'job_order_id' => $jobOrder->id,
                    'company_location_id' => $location->id,
                    'process_type' => $request->p2_process_type ?? 'steaming',
                    'steam_type' => $request->p2_steam_type ?? 'single_steam',
                    'parboil_soak_hours' => $request->p2_parboil_soak_hours,
                    'parboil_cook_minutes' => $request->p2_parboil_cook_minutes,
                    'parboiled_grade' => $request->p2_parboiled_grade,
                    'status' => 'pending',
                    'remarks' => $request->p2_remarks,
                ]);
            }

            // Phase 3 (Milling) if enabled
            if (in_array(3, $phaseIds) && $request->filled('p3_milling_type')) {
                ProductionPhase3::create([
                    'company_id' => Auth::user()?->company_id,
                    'job_order_id' => $jobOrder->id,
                    'company_location_id' => $location->id,
                    'milling_type' => $request->p3_milling_type ?? 'direct_milling',
                    'storage_type' => 'flat_storage',
                    'status' => 'pending',
                    'remarks' => $request->p3_remarks,
                ]);
            }

            DB::commit();

            return response()->json([
                'status' => 200,
                'message' => 'Job Order created successfully',
                'job_order' => $jobOrder,
                'redirect_url' => route('production.job-orders.index'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 500,
                'message' => 'Error creating Job Order: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function show($id)
    {
        $jobOrder = JobOrderV2::with([
            'companyLocation',
            'product',
            'exportOrder',
            'maker',
            'poster',
            'agreeor',
            'productionPhase1',
            'productionPhase2',
            'productionPhase3',
        ])->findOrFail($id);

        $activePhases = $jobOrder->getActivePhaseModels();

        return view('management.production.v2.job_orders.show', compact('jobOrder', 'activePhases'));
    }

    public function edit($id)
    {
        $jobOrder = JobOrderV2::with([
            'companyLocation',
            'product',
            'exportOrder',
            'productionPhase1',
            'productionPhase2',
            'productionPhase3',
        ])->findOrFail($id);

        $locations = CompanyLocation::where('status', 'active')->orderBy('name')->get();
        $products = Product::where('status', 'active')->orderBy('name')->get();
        $exportOrders = ExportOrder::where('am_approval_status', 'approved')->latest()->take(50)->get();
        $users = User::get(); // Users for attention_to

        $activePhases = $jobOrder->getActivePhaseModels();
        $allPhases = ProductionPhase::where('status', 'active')->orderBy('id')->get();

        return view('management.production.v2.job_orders.edit', compact(
            'jobOrder',
            'locations',
            'products',
            'exportOrders',
            'users',
            'activePhases',
            'allPhases'
        ));
    }

    public function update(Request $request, $id)
    {
        $jobOrder = JobOrderV2::findOrFail($id);

        $request->validate([
            'company_location_id' => 'required|exists:company_locations,id',
            'product_id' => 'nullable|exists:products,id',
            'export_order_id' => 'nullable|exists:export_orders,id',
            'job_order_date' => 'required|date',
            'ref_no' => 'nullable|string|max:100',
            'attention_to' => 'nullable|array',
            'order_description' => 'nullable|string',
            'remarks' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $location = CompanyLocation::findOrFail($request->company_location_id);
            $locationPhases = (array)($location->production_phases ?? []);

            $selectedPhases = $request->input('selected_phases');
            if (!empty($selectedPhases) && is_array($selectedPhases)) {
                $phaseIds = array_values(array_intersect(array_map('intval', $selectedPhases), array_map('intval', $locationPhases)));
            } else {
                $phaseIds = array_values(array_map('intval', $locationPhases));
            }

            $jobOrder->update([
                'company_location_id' => $location->id,
                'product_id' => $request->product_id ?: null,
                'export_order_id' => $request->export_order_id ?: null,
                'job_order_date' => $request->job_order_date,
                'ref_no' => $request->ref_no,
                'attention_to' => $request->attention_to ? array_values(array_filter($request->attention_to)) : null,
                'order_description' => $request->order_description,
                'remarks' => $request->remarks,
                'active_phases' => $phaseIds,
            ]);

            DB::commit();

            return response()->json([
                'status' => 200,
                'message' => 'Job Order updated successfully',
                'redirect_url' => route('production.job-orders.index'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 500,
                'message' => 'Error updating Job Order: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        $jobOrder = JobOrderV2::findOrFail($id);
        $jobOrder->delete();

        return response()->json([
            'status' => 200,
            'message' => 'Job Order deleted successfully',
        ]);
    }

    /**
     * AJAX endpoint to fetch enabled phases for a given location
     */
    public function getPhasesByLocation($locationId)
    {
        $location = CompanyLocation::find($locationId);
        if (!$location) {
            return response()->json([
                'status' => 404,
                'message' => 'Location not found',
                'phases' => []
            ]);
        }

        $phaseIds = $location->production_phases ?? [];
        $phases = ProductionPhase::whereIn('id', (array)$phaseIds)
            ->where('status', 'active')
            ->orderBy('id')
            ->get();

        return response()->json([
            'status' => 200,
            'location_id' => $location->id,
            'location_name' => $location->name,
            'location_code' => $location->code,
            'phase_ids' => $phaseIds,
            'phases' => $phases,
        ]);
    }

    /**
     * Generate unique Job Order V2 number: [LOC]-JO-V2-[NUM]-[DATE]
     */
    public static function generateUniqueNumber($dateParam = null, $locationParam = null)
    {
        try {
            $datePart = $dateParam ? Carbon::parse($dateParam)->format('m-d-Y') : date('m-d-Y');
        } catch (\Exception $e) {
            $datePart = date('m-d-Y');
        }

        $locationCode = 'LOC';
        if ($locationParam) {
            if ($locationParam instanceof CompanyLocation) {
                $locationCode = $locationParam->code ?: 'LOC';
            } elseif (is_numeric($locationParam)) {
                $loc = CompanyLocation::find($locationParam);
                $locationCode = $loc?->code ?: 'LOC';
            } elseif (is_string($locationParam)) {
                $loc = CompanyLocation::where('code', $locationParam)->first();
                $locationCode = $loc ? $loc->code : $locationParam;
            }
        }

        $locationCode = strtoupper(trim($locationCode ?: 'LOC'));
        $prefix = $locationCode . '-JO-V2';
        $searchPattern = $prefix . '-%-' . $datePart;

        $existing = JobOrderV2::where('job_order_no', 'like', $searchPattern)
            ->lockForUpdate()
            ->pluck('job_order_no')
            ->toArray();

        $maxNumber = 0;
        $regex = '/^' . preg_quote($prefix, '/') . '-(\d+)-' . preg_quote($datePart, '/') . '$/';
        foreach ($existing as $no) {
            if (preg_match($regex, $no, $matches)) {
                $num = (int)$matches[1];
                if ($num > $maxNumber) {
                    $maxNumber = $num;
                }
            }
        }

        $newNumber = $maxNumber + 1;
        $candidate = $prefix . '-' . str_pad($newNumber, 3, '0', STR_PAD_LEFT) . '-' . $datePart;

        while (in_array($candidate, $existing) || JobOrderV2::where('job_order_no', $candidate)->exists()) {
            $newNumber++;
            $candidate = $prefix . '-' . str_pad($newNumber, 3, '0', STR_PAD_LEFT) . '-' . $datePart;
        }

        return $candidate;
    }

    /**
     * AJAX endpoint to get unique Job Order Number
     */
    public function getNumber(Request $request)
    {
        $dateParam = $request->job_order_date ?? $request->date ?? date('Y-m-d');
        $locationParam = $request->location_id ?? $request->company_location_id ?? null;
        $number = self::generateUniqueNumber($dateParam, $locationParam);

        return response()->json([
            'status' => 200,
            'job_order_no' => $number,
        ]);
    }
}
