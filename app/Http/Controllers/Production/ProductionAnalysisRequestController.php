<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\Master\ArrivalLocation;
use App\Models\Master\CompanyLocation;
use App\Models\Master\Plant;
use App\Models\Production\JobOrder\JobOrder;
use App\Models\Production\ProductionAnalysisRequest;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductionAnalysisRequestController extends Controller
{
    /**
     * Display a listing of analysis requests.
     */
    public function index()
    {
        $user = auth()->user();
        $locationIds = getUserCurrentCompanyLocations();

        if ($user && ($user->user_type === 'super-admin' || empty($locationIds))) {
            $locations = CompanyLocation::where('status', 'active')->get();
            $arrivalLocations = ArrivalLocation::where('status', 'active')->orWhereNull('status')->get();
            $plants = Plant::where('status', 'active')->orWhereNull('status')->get();
        } else {
            $locations = CompanyLocation::whereIn('id', $locationIds)->where('status', 'active')->get();
            $arrivalLocations = ArrivalLocation::whereIn('company_location_id', $locationIds)->get();
            $plants = Plant::whereIn('company_location_id', $locationIds)->get();
        }

        $types = ProductionAnalysisRequest::getTypes();

        return view('management.production.production_analysis_request.index', compact(
            'locations',
            'arrivalLocations',
            'plants',
            'types'
        ));
    }

    /**
     * Get paginated and filtered list of analysis requests via AJAX.
     */
    public function getList(Request $request)
    {
        $user = auth()->user();
        $locationIds = getUserCurrentCompanyLocations();

        $query = ProductionAnalysisRequest::with([
            'companyLocation',
            'arrivalLocation',
            'plant',
            'jobOrder',
            'creator',
            'productionAnalysis',
            'machineAnalysis',
        ]);

        if ($user && $user->user_type !== 'super-admin' && !empty($locationIds)) {
            $query->whereIn('company_location_id', $locationIds);
        }

        // Date range filter
        if ($request->filled('date_range')) {
            $dates = explode(' - ', $request->date_range);
            if (count($dates) === 2) {
                $startDate = Carbon::parse(trim($dates[0]))->startOfDay();
                $endDate = Carbon::parse(trim($dates[1]))->endOfDay();
                $query->whereBetween('request_date', [$startDate, $endDate]);
            }
        }

        // Multi location filter
        if ($request->filled('location_ids')) {
            $query->whereIn('company_location_id', (array) $request->location_ids);
        }

        // Arrival location filter
        if ($request->filled('arrival_location_ids')) {
            $query->whereIn('arrival_location_id', (array) $request->arrival_location_ids);
        }

        // Plant filter
        if ($request->filled('plant_ids')) {
            $query->whereIn('plant_id', (array) $request->plant_ids);
        }

        // Type filter
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Search filter
        if ($request->filled('search')) {
            $search = '%' . trim($request->search) . '%';
            $query->where(function ($q) use ($search) {
                $q->where('request_no', 'like', $search)
                  ->orWhere('remarks', 'like', $search)
                  ->orWhereHas('jobOrder', function ($jq) use ($search) {
                      $jq->where('job_order_no', 'like', $search)
                         ->orWhere('ref_no', 'like', $search);
                  });
            });
        }

        $items = $query->latest('id')->paginate($request->input('per_page', 25));

        return view('management.production.production_analysis_request.getList', compact('items'));
    }

    /**
     * Show the form for creating a new analysis request.
     */
    public function create(Request $request)
    {
        $user = auth()->user();
        $locationIds = getUserCurrentCompanyLocations();

        // Get role-wise company locations
        if ($user && ($user->user_type === 'super-admin' || empty($locationIds))) {
            $companyLocations = CompanyLocation::where('status', 'active')->get();
        } else {
            $companyLocations = CompanyLocation::whereIn('id', $locationIds)->where('status', 'active')->get();
        }

        // 1st Company Location selected by default
        $preSelectedLocationId = $companyLocations->first()?->id;

        $types = ProductionAnalysisRequest::getTypes();
        // Generate location-wise request number
        $requestNo = $this->generateNumber(date('Y-m-d'), $preSelectedLocationId);

        // Pre-populate initial arrival locations & job orders for the 1st selected location
        $arrivalLocations = collect();
        $jobOrders = collect();
        if ($preSelectedLocationId) {
            $arrivalLocations = ArrivalLocation::where('company_location_id', $preSelectedLocationId)
                ->where('status', 'active')
                ->orWhereNull('status')
                ->get();

            $jobOrders = JobOrder::where('status', 'active')
                ->whereHas('packingItems', function ($q) use ($preSelectedLocationId) {
                    $q->where('company_location_id', $preSelectedLocationId);
                })
                ->latest('id')
                ->get();
        }

        return view('management.production.production_analysis_request.create', compact(
            'companyLocations',
            'preSelectedLocationId',
            'arrivalLocations',
            'jobOrders',
            'types',
            'requestNo'
        ));
    }

    /**
     * Store a newly created analysis request in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'request_date' => 'required|date',
            'company_location_id' => 'required|exists:company_locations,id',
            'arrival_location_id' => 'required|exists:arrival_locations,id',
            'plant_id' => 'required|exists:plants,id',
            'type' => 'required|in:' . implode(',', array_keys(ProductionAnalysisRequest::getTypes())),
            'job_order_id' => 'nullable|exists:job_orders,id',
            'remarks' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $companyLocation = CompanyLocation::findOrFail($request->company_location_id);
            // Generate request number location-wise
            $requestNo = $this->generateNumber($request->request_date, $request->company_location_id);

            $analysisRequest = ProductionAnalysisRequest::create([
                'request_no' => $requestNo,
                'request_date' => $request->request_date,
                'company_id' => $companyLocation->company_id ?? (auth()->user()->company_id ?? 1),
                'company_location_id' => $request->company_location_id,
                'arrival_location_id' => $request->arrival_location_id,
                'plant_id' => $request->plant_id,
                'job_order_id' => $request->job_order_id,
                'type' => $request->type,
                'remarks' => $request->remarks,
                'status' => 'pending',
                'created_by' => auth()->user()?->id ?? 1,
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Analysis Request created successfully (' . $analysisRequest->request_no . ')',
                'data' => $analysisRequest,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create Analysis Request: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified analysis request.
     */
    public function show($id)
    {
        $item = ProductionAnalysisRequest::with([
            'companyLocation',
            'arrivalLocation',
            'plant',
            'jobOrder',
            'creator',
            'productionAnalysis',
            'machineAnalysis',
        ])->findOrFail($id);

        return view('management.production.production_analysis_request.show', compact('item'));
    }

    /**
     * Show the form for editing the specified analysis request.
     */
    public function edit($id)
    {
        $item = ProductionAnalysisRequest::findOrFail($id);

        $user = auth()->user();
        $locationIds = getUserCurrentCompanyLocations();
        if ($user && ($user->user_type === 'super-admin' || empty($locationIds))) {
            $companyLocations = CompanyLocation::where('status', 'active')->get();
        } else {
            $companyLocations = CompanyLocation::whereIn('id', $locationIds)->where('status', 'active')->get();
        }

        $arrivalLocations = ArrivalLocation::where('company_location_id', $item->company_location_id)
            ->where('status', 'active')
            ->orWhereNull('status')
            ->get();

        $plants = Plant::where('company_location_id', $item->company_location_id)
            ->where('arrival_location_id', $item->arrival_location_id)
            ->get();

        $jobOrders = JobOrder::where('status', 'active')
            ->whereHas('packingItems', function ($q) use ($item) {
                $q->where('company_location_id', $item->company_location_id);
            })
            ->latest('id')
            ->get();

        $types = ProductionAnalysisRequest::getTypes();

        return view('management.production.production_analysis_request.edit', compact(
            'item',
            'companyLocations',
            'arrivalLocations',
            'plants',
            'jobOrders',
            'types'
        ));
    }

    /**
     * Update the specified analysis request in storage.
     */
    public function update(Request $request, $id)
    {
        $analysisRequest = ProductionAnalysisRequest::findOrFail($id);

        $request->validate([
            'request_date' => 'required|date',
            'company_location_id' => 'required|exists:company_locations,id',
            'arrival_location_id' => 'required|exists:arrival_locations,id',
            'plant_id' => 'required|exists:plants,id',
            'type' => 'required|in:' . implode(',', array_keys(ProductionAnalysisRequest::getTypes())),
            'job_order_id' => 'nullable|exists:job_orders,id',
            'remarks' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $companyLocation = CompanyLocation::findOrFail($request->company_location_id);

            // If location changed, regenerate request number location-wise
            $requestNo = $analysisRequest->request_no;
            if ($analysisRequest->company_location_id != $request->company_location_id) {
                $requestNo = $this->generateNumber($request->request_date, $request->company_location_id);
            }

            $analysisRequest->update([
                'request_no' => $requestNo,
                'request_date' => $request->request_date,
                'company_id' => $companyLocation->company_id ?? $analysisRequest->company_id,
                'company_location_id' => $request->company_location_id,
                'arrival_location_id' => $request->arrival_location_id,
                'plant_id' => $request->plant_id,
                'job_order_id' => $request->job_order_id,
                'type' => $request->type,
                'remarks' => $request->remarks,
                'updated_by' => auth()->user()?->id ?? 1,
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Analysis Request updated successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update Analysis Request: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified analysis request from storage.
     */
    public function destroy($id)
    {
        try {
            $analysisRequest = ProductionAnalysisRequest::findOrFail($id);
            $analysisRequest->update(['deleted_by' => auth()->user()?->id ?? 1]);
            $analysisRequest->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Analysis Request deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete Analysis Request: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Alias for generateUniqueNumber
     */
    public static function generateNumber($dateParam = null, $locationParam = null): string
    {
        try {
            $datePart = $dateParam ? Carbon::parse($dateParam)->format('m-d-Y') : date('m-d-Y');
        } catch (\Exception $e) {
            $datePart = date('m-d-Y');
        }

        if (empty($locationParam)) {
            $locationParam = request('location_id') ?? request('company_location_id') ?? auth()->user()?->company_location_id;
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
        $prefix = $locationCode . '-AR';

        $searchPattern = $prefix . '-%-' . $datePart;

        $existing = ProductionAnalysisRequest::withTrashed()
            ->where('request_no', 'like', $searchPattern)
            ->lockForUpdate()
            ->pluck('request_no')
            ->toArray();

        $maxNumber = 0;
        $regex = '/^' . preg_quote($prefix, '/') . '-(\d+)-' . preg_quote($datePart, '/') . '$/';
        foreach ($existing as $no) {
            if (preg_match($regex, $no, $matches)) {
                $num = (int) $matches[1];
                if ($num > $maxNumber) {
                    $maxNumber = $num;
                }
            }
        }

        $newNumber = $maxNumber + 1;
        $candidate = $prefix . '-' . str_pad($newNumber, 3, '0', STR_PAD_LEFT) . '-' . $datePart;

        while (in_array($candidate, $existing) || ProductionAnalysisRequest::withTrashed()->where('request_no', $candidate)->exists()) {
            $newNumber++;
            $candidate = $prefix . '-' . str_pad($newNumber, 3, '0', STR_PAD_LEFT) . '-' . $datePart;
        }

        return $candidate;
    }

    /**
     * AJAX endpoint to fetch auto-generated request number on date or location change
     */
    public function getNumber(Request $request)
    {
        $date = $request->input('date', date('Y-m-d'));
        $locationId = $request->input('company_location_id', $request->input('location_id'));
        $number = $this->generateNumber($date, $locationId);

        return response()->json([
            'status' => 'success',
            'request_no' => $number,
        ]);
    }

    /**
     * AJAX endpoint: Get arrival locations for a company location
     */
    public function getArrivalLocationsByCompanyLocation($companyLocationId)
    {
        $arrivalLocations = ArrivalLocation::where('company_location_id', $companyLocationId)
            ->where('status', 'active')
            ->orWhereNull('status')
            ->get(['id', 'name']);

        return response()->json($arrivalLocations);
    }

    /**
     * AJAX endpoint: Get plants for company location and arrival location
     */
    public function getPlantsByArrivalLocation($companyId, $arrivalId)
    {
        $plants = Plant::where('company_location_id', $companyId)
            ->where('arrival_location_id', $arrivalId)
            ->get(['id', 'name']);

        return response()->json($plants);
    }

    /**
     * AJAX endpoint: Get active Job Orders for a company location
     */
    public function getJobOrdersByCompanyLocation($companyLocationId)
    {
        $jobOrders = JobOrder::where('status', 'active')
            ->whereHas('packingItems', function ($q) use ($companyLocationId) {
                $q->where('company_location_id', $companyLocationId);
            })
            ->latest('id')
            ->get(['id', 'job_order_no', 'ref_no']);

        return response()->json($jobOrders);
    }
}
