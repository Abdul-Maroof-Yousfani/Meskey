<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Master\CompanyLocation;
use App\Models\Master\ProductSlab;
use App\Models\Master\ProductSlabType;
use App\Models\Production\ProductionAnalysis;
use App\Models\Production\ProductionAnalysisItem;
use App\Models\Production\ProductionAnalysisItemSlab;
use App\Models\UnitOfMeasure;
use App\Models\Product;
use App\Models\Production\JobOrder\JobOrder;
use Illuminate\Support\Facades\DB;

class ProductionOutputAnalysisController extends Controller
{
    public function index()
    {
        $locationIds = getUserCurrentCompanyLocations();
        $locations = CompanyLocation::whereIn('id', $locationIds)->get();
        $arrivalLocations = \App\Models\Master\ArrivalLocation::whereIn('company_location_id', $locationIds)->get();
        $plants = \App\Models\Master\Plant::whereIn('company_location_id', $locationIds)->get();
        return view('management.production.production_output_analysis.index', compact('locations', 'arrivalLocations', 'plants'));
    }

    public function create(Request $request)
    {
        $locationIds = getUserCurrentCompanyLocations();
        $companyLocations = CompanyLocation::whereIn('id', $locationIds)->get();
        
        $analysisRequests = \App\Models\Production\ProductionAnalysisRequest::where('type', \App\Models\Production\ProductionAnalysisRequest::TYPE_OUTPUT)
            ->where(function ($q) use ($request) {
                $q->where('status', 'pending');
                if ($request->filled('analysis_request_id')) {
                    $q->orWhere('id', $request->analysis_request_id);
                }
            })
            ->with(['companyLocation', 'arrivalLocation', 'plant', 'jobOrder', 'productionVoucher', 'outputAlert.remarkedBy'])
            ->latest('id')
            ->get();

        $analysisRequest = null;
        $initialArrivalLocations = collect();
        $initialPlants = collect();

        if ($request->filled('analysis_request_id')) {
            $analysisRequest = $analysisRequests->firstWhere('id', $request->analysis_request_id)
                ?? \App\Models\Production\ProductionAnalysisRequest::with(['companyLocation', 'arrivalLocation', 'plant', 'jobOrder', 'productionVoucher', 'outputAlert.remarkedBy'])->find($request->analysis_request_id);

            if ($analysisRequest) {
                $preSelectedLocationId = $analysisRequest->company_location_id;
                $initialArrivalLocations = \App\Models\Master\ArrivalLocation::where('company_location_id', $preSelectedLocationId)->get();
                $initialPlants = \App\Models\Master\Plant::where('company_location_id', $preSelectedLocationId)
                    ->where('arrival_location_id', $analysisRequest->arrival_location_id)
                    ->get();
            } else {
                $preSelectedLocationId = count($companyLocations) === 1 ? $companyLocations->first()->id : null;
            }
        } elseif ($analysisRequests->count() === 1) {
            $analysisRequest = $analysisRequests->first();
            $preSelectedLocationId = $analysisRequest->company_location_id;
            $initialArrivalLocations = \App\Models\Master\ArrivalLocation::where('company_location_id', $preSelectedLocationId)->get();
            $initialPlants = \App\Models\Master\Plant::where('company_location_id', $preSelectedLocationId)
                ->where('arrival_location_id', $analysisRequest->arrival_location_id)
                ->get();
        } else {
            $preSelectedLocationId = count($companyLocations) === 1 ? $companyLocations->first()->id : null;
        }
        
        $units = UnitOfMeasure::all();
        $products = getFinishGoodProducts();
        // The QC Checklist is shown only after an Analysis Request is chosen
        $productSlabTypes = $analysisRequest
            ? ProductSlabType::where('status', 'active')->where('for_general_item', 1)->get()
            : collect();

        // Output is fixed to the job order's product
        $fixedProduct = $analysisRequest?->outputProductId() ? \App\Models\Product::find($analysisRequest->outputProductId()) : null;
        $jobOrders = \App\Models\Production\JobOrder\JobOrder::latest('id')->get(['id', 'job_order_no']);
        $selectedJobOrderId = $analysisRequest?->job_order_id ?? ($analysisRequest?->jobOrderIdsForAnalysis()[0] ?? null);
            
        return view('management.production.production_output_analysis.create', compact(
            'companyLocations', 
            'units', 
            'products', 
            'jobOrders',
            'selectedJobOrderId',
            'fixedProduct',
            'productSlabTypes', 
            'preSelectedLocationId',
            'analysisRequests',
            'analysisRequest',
            'initialArrivalLocations',
            'initialPlants'
        ));
    }

    public function store(Request $request)
    {
        $this->validateQcValues($request);

        try {
            DB::beginTransaction();

            $analysisReq = null;
            if ($request->filled('analysis_request_id')) {
                $analysisReq = \App\Models\Production\ProductionAnalysisRequest::find($request->analysis_request_id);
            }

            // The request's own voucher for now; once the readings are saved the analysis is linked to the voucher that fits it
            $matchingVoucherId = $analysisReq?->production_voucher_id;

            // Output is fixed to the selected job order's product (else the request's job order product)
            $finalProductId = $this->jobOrderProductId($request->job_order_id)
                ?: ($analysisReq?->outputProductId() ?: $request->product_id);

            // Create Parent Record
            $analysis = ProductionAnalysis::create([
                'analysis_date' => $request->date,
                'location_id' => $request->location_id ?: $analysisReq?->company_location_id,
                'arrival_location_id' => $request->arrival_location_id ?: $analysisReq?->arrival_location_id,
                'plant_id' => $request->plant_id ?: $analysisReq?->plant_id,
                'milling_degree' => $request->milling_degree,
                'inner_stitching' => $request->inner_stitching,
                'outer_stitching' => $request->outer_stitching,
                'remarks' => $request->remarks,
                'product_id' => $finalProductId,
                'crop_year_id' => $request->crop_year_id,
                'brand_id' => $request->brand_id,
                'variety' => $request->variety,
                'bag_packing_id' => $request->bag_packing_id,
                'production_analysis_type' => 'output',
                'analysis_request_id' => $request->analysis_request_id ?? null,
                'production_voucher_id' => $matchingVoucherId,
            ]);

            // If created against an Analysis Request, mark it completed
            if ($request->filled('analysis_request_id')) {
                $analysisReq = \App\Models\Production\ProductionAnalysisRequest::find($request->analysis_request_id);
                if ($analysisReq) {
                    $analysisReq->update(['status' => 'completed']);
                }
            }

            // Sync Job Orders (single select on UI, stored as multi in DB)
            $jobOrderIds = $request->filled('job_order_id')
                ? [$request->job_order_id]
                : (array) $request->input('job_orders', $request->input('job_order_ids', []));
            $jobOrderIds = array_values(array_filter(array_unique($jobOrderIds)));
            if (empty($jobOrderIds) && $request->filled('analysis_request_id') && isset($analysisReq)) {
                $jobOrderIds = $analysisReq->jobOrderIdsForAnalysis();
            }
            if (!empty($jobOrderIds)) {
                $analysis->jobOrders()->sync($jobOrderIds);
            }

            // Store New Line Items
            if ($request->has('items')) {
                foreach ($request->items as $row) {
                    $item = ProductionAnalysisItem::create([
                        'production_analysis_id' => $analysis->id,
                        'analysis_time' => $row['time'],
                        'unit_id' => $row['unit_id'] ?? null,
                    ]);

                    if (isset($row['params']) && is_array($row['params'])) {
                        foreach ($row['params'] as $slabTypeId => $value) {
                            if ($value !== null && $value !== '') {
                                ProductionAnalysisItemSlab::create([
                                    'production_analysis_item_id' => $item->id,
                                    'slab_type_id' => $slabTypeId,
                                    'production_analysis_value' => $value,
                                ]);
                            }
                        }
                    }
                }
            }

            // Link to the voucher whose machines were running at the reading time (or the request's voucher)
            app(\App\Services\Production\Milling\ProductionDayPlanService::class)->syncAnalysis($analysis);

            DB::commit();

            try {
                app(\App\Services\Production\Milling\AutoAnalysisRequestService::class)->onAnalysisCompleted($analysis);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('AutoAnalysis onAnalysisCompleted (output) error: ' . $e->getMessage());
            }

            $this->syncQcAlert($analysis);

            return response()->json(['status' => 'success', 'message' => 'Analysis stored successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => 'Failed to store analysis: ' . $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        $item = ProductionAnalysis::with([
            'location', 'arrivalLocation', 'plant', 'product', 'items.unit', 'items.slabs.slabType', 'jobOrders',
            'analysisRequest.outputAlert.remarkedBy',
        ])->findOrFail($id);

        // Same checklist as the form: Job Order spec rows (colored), then any other saved values
        $jobOrderId = $item->jobOrders->first()?->id ?? $item->analysisRequest?->job_order_id;
        $checklistRows = collect(app(\App\Services\Production\OutputQcAlertService::class)->checklist($item, $jobOrderId));
        $specSlabIds = $checklistRows->pluck('slab_type_id')->all();

        $extraRows = $item->items->flatMap(fn($it) => $it->slabs)
            ->reject(fn($slab) => in_array((int) $slab->slab_type_id, $specSlabIds, true))
            ->unique('slab_type_id')
            ->map(fn($slab) => [
                'name' => $slab->slabType?->name ?? '-',
                'qc_symbol' => $slab->slabType?->qc_symbol ?: '%',
                'spec_text' => 'No spec',
                'result' => $slab->production_analysis_value,
                'status' => 'none',
            ]);

        $hasSpecs = $checklistRows->isNotEmpty();
        $checklistRows = $checklistRows->concat($extraRows)->values()->all();
        $alert = \App\Models\Production\ProductionOutputAlert::where('production_analysis_id', $item->id)->latest('id')->first();

        return view('management.production.production_output_analysis.show', compact('item', 'checklistRows', 'hasSpecs', 'alert'));
    }

    public function edit($id)
    {
        $item = ProductionAnalysis::with(['items.slabs.slabType', 'jobOrders'])->findOrFail($id);
        
        $locationIds = getUserCurrentCompanyLocations();
        $companyLocations = CompanyLocation::whereIn('id', $locationIds)->get();
        $units = UnitOfMeasure::all();
        $products = getFinishGoodProducts($item->product_id);

        // Slab type resolution logic
        $productSlabTypes = collect();
        if ($item->product_id) {
            $productSlabTypes = ProductSlab::with('slabType')
                ->where('product_id', $item->product_id)
                ->get()
                ->unique('product_slab_type_id')
                ->pluck('slabType')
                ->filter()
                ->values();
        }

        if ($productSlabTypes->isEmpty()) {
            $slabTypeIdsFromData = $item->items->flatMap(fn($it) => $it->slabs)->pluck('slab_type_id')->unique();
            if ($slabTypeIdsFromData->isNotEmpty()) {
                $productSlabTypes = ProductSlabType::whereIn('id', $slabTypeIdsFromData)->orderBy('id', 'ASC')->get();
            }
        }

        if ($productSlabTypes->isEmpty()) {
            $productSlabTypes = ProductSlabType::where("for_general_item", 1)->where("status", "active")->get();
        }

        $analysisRequests = \App\Models\Production\ProductionAnalysisRequest::where('type', \App\Models\Production\ProductionAnalysisRequest::TYPE_OUTPUT)
            ->where(function ($q) use ($item) {
                $q->where('status', 'pending');
                if ($item->analysis_request_id) {
                    $q->orWhere('id', $item->analysis_request_id);
                }
            })
            ->with(['companyLocation', 'arrivalLocation', 'plant', 'jobOrder', 'productionVoucher', 'outputAlert.remarkedBy'])
            ->latest('id')
            ->get();

        $arrivalLocations = \App\Models\Master\ArrivalLocation::where('company_location_id', $item->location_id)->get();
        $plants = \App\Models\Master\Plant::where('company_location_id', $item->location_id)
            ->where('arrival_location_id', $item->arrival_location_id)
            ->get();

        $fixedProduct = $item->analysisRequest?->outputProductId() ? \App\Models\Product::find($item->analysisRequest->outputProductId()) : null;
        $jobOrders = \App\Models\Production\JobOrder\JobOrder::latest('id')->get(['id', 'job_order_no']);
        $selectedJobOrderId = $item->jobOrders->first()?->id ?? $item->analysisRequest?->job_order_id ?? ($item->analysisRequest?->jobOrderIdsForAnalysis()[0] ?? null);

        return view('management.production.production_output_analysis.edit', compact(
            'item', 
            'productSlabTypes',
            'companyLocations', 
            'products',
            'jobOrders',
            'selectedJobOrderId',
            'fixedProduct',
            'units',
            'analysisRequests',
            'arrivalLocations',
            'plants'
        ));
    }

    public function update(Request $request, $id)
    {
        $this->validateQcValues($request);

        try {
            DB::beginTransaction();

            $analysis = ProductionAnalysis::findOrFail($id);
            $previousRequestId = $analysis->analysis_request_id;

            $analysisReq = null;
            if ($request->filled('analysis_request_id')) {
                $analysisReq = \App\Models\Production\ProductionAnalysisRequest::find($request->analysis_request_id);
            }

            // Output is fixed to the selected job order's product (else the request's job order product)
            $finalProductId = $this->jobOrderProductId($request->job_order_id)
                ?: ($analysisReq?->outputProductId() ?: $request->product_id);

            $analysis->update([
                'analysis_date' => $request->date,
                'location_id' => $request->location_id ?: $analysisReq?->company_location_id,
                'arrival_location_id' => $request->arrival_location_id ?: $analysisReq?->arrival_location_id,
                'plant_id' => $request->plant_id ?: $analysisReq?->plant_id,
                'milling_degree' => $request->milling_degree,
                'inner_stitching' => $request->inner_stitching,
                'outer_stitching' => $request->outer_stitching,
                'remarks' => $request->remarks,
                'product_id' => $finalProductId,
                'analysis_request_id' => $request->analysis_request_id ?? $analysis->analysis_request_id,
            ]);

            // Sync Job Orders (single select on UI, stored as multi in DB)
            $jobOrderIds = $request->filled('job_order_id')
                ? [$request->job_order_id]
                : (array) $request->input('job_orders', $request->input('job_order_ids', []));
            $jobOrderIds = array_values(array_filter(array_unique($jobOrderIds)));
            $analysis->jobOrders()->sync($jobOrderIds);
            $analysis->items()->delete();

            if($request->has('items')) {
                foreach ($request->items as $row) {
                    $item = ProductionAnalysisItem::create([
                        'production_analysis_id' => $analysis->id,
                        'analysis_time' => $row['time'],
                        'unit_id' => $row['unit_id'] ?? null,
                    ]);

                    if (isset($row['params']) && is_array($row['params'])) {
                        foreach ($row['params'] as $slabTypeId => $value) {
                            if ($value !== null && $value !== '') {
                                ProductionAnalysisItemSlab::create([
                                    'production_analysis_item_id' => $item->id,
                                    'slab_type_id' => $slabTypeId,
                                    'production_analysis_value' => $value,
                                ]);
                            }
                        }
                    }
                }
            }

            // Link to the fitting voucher; a request this analysis moved away from goes back to pending
            app(\App\Services\Production\Milling\ProductionDayPlanService::class)->syncAnalysis($analysis, $previousRequestId);

            DB::commit();

            $this->syncQcAlert($analysis->fresh());
            return response()->json(['status' => 'success', 'message' => 'Analysis updated successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => 'Failed to update analysis: ' . $e->getMessage()], 500);
        }
    }

    /** Raise (or refresh) the QC alert when a result is outside the Job Order spec. */
    private function syncQcAlert(ProductionAnalysis $analysis): void
    {
        try {
            app(\App\Services\Production\OutputQcAlertService::class)->syncForAnalysis($analysis);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Output QC Alert sync error: ' . $e->getMessage());
        }
    }

    /**
     * Every QC Checklist value must be filled with a number of 0 or more.
     * Runs before the try block so a validation error returns 422, not 500.
     */
    private function validateQcValues(Request $request): void
    {
        $request->validate([
            'job_order_id' => 'required|exists:job_orders,id',
            'items' => 'required|array',
            'items.*.params' => 'required|array',
            'items.*.params.*' => 'required|numeric|min:0',
        ], [
            'job_order_id.required' => 'Job Order is required.',
            'items.*.params.*.required' => 'All QC Checklist values are required (enter 0 if none).',
            'items.*.params.*.numeric' => 'QC Checklist values must be numbers.',
            'items.*.params.*.min' => 'QC Checklist values cannot be less than 0.',
        ]);
    }

    public function destroy($id)
    {
        try {
            DB::beginTransaction();
            $analysis = ProductionAnalysis::findOrFail($id);
            $analysis->jobOrders()->detach();
            $requestId = $analysis->analysis_request_id;
            $analysis->delete();

            // An alert nobody answered yet goes away with its analysis
            \App\Models\Production\ProductionOutputAlert::where('production_analysis_id', $id)
                ->where('status', \App\Models\Production\ProductionOutputAlert::STATUS_PENDING)
                ->delete();

            // Request goes back to pending since its analysis no longer exists
            \App\Models\Production\ProductionAnalysisRequest::revertToPendingIfUnused($requestId);
            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Analysis deleted successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => 'Failed to delete analysis: ' . $e->getMessage()], 500);
        }
    }

    public function getList(Request $request)
    {
        $limit = $request->per_page ?? 25;
        $locationIdsFilter = $request->location_ids;
        $arrivalLocationIdsFilter = $request->arrival_location_ids;
        $plantIdsFilter = $request->plant_ids;
        $dateRange = $request->date_range;

        $items = ProductionAnalysis::with(['location', 'arrivalLocation', 'plant', 'product', 'jobOrders'])
            ->where('production_analysis_type', 'output')
            ->when($locationIdsFilter, function ($query) use ($locationIdsFilter) {
                return $query->whereIn('location_id', $locationIdsFilter);
            })
            ->when($arrivalLocationIdsFilter, function ($query) use ($arrivalLocationIdsFilter) {
                return $query->whereIn('arrival_location_id', $arrivalLocationIdsFilter);
            })
            ->when($plantIdsFilter, function ($query) use ($plantIdsFilter) {
                return $query->whereIn('plant_id', $plantIdsFilter);
            })
            ->when($dateRange, function ($query) use ($dateRange) {
                $dates = explode(' - ', $dateRange);
                if (count($dates) == 2) {
                    return $query->whereBetween('analysis_date', [trim($dates[0]), trim($dates[1])]);
                }
            })
            ->orderBy('id', 'DESC')
            ->paginate($limit);

        return view('management.production.production_output_analysis.getList', compact('items'));
    }

    public function getSlabsByProduct(Request $request)
    {
        $productId = $request->product_id;
        $slabs = ProductSlab::with('slabType')
            ->where('product_id', $productId)
            ->get()
            ->unique('product_slab_type_id')
            ->values();
            
        $slabTypes = $slabs->map(function($slab) {
            return [
                'id' => $slab->slabType->id,
                'name' => $slab->slabType->name,
                'qc_symbol' => $slab->slabType->qc_symbol,
            ];
        });
        
        return response()->json($slabTypes);
    }

    /** Output analysis is fixed to the job order's product of the selected request. */
    public function checkFixedProduct(Request $request)
    {
        $analysisReq = $request->filled('analysis_request_id')
            ? \App\Models\Production\ProductionAnalysisRequest::find($request->analysis_request_id)
            : null;

        $product = $analysisReq?->outputProductId() ? \App\Models\Product::find($analysisReq->outputProductId()) : null;

        return response()->json([
            'status' => 'success',
            'is_fixed' => (bool) $product,
            'product_id' => $product?->id,
            'product_name' => $product?->name,
            'suggested_product_id' => null,
        ]);
    }

    /** Product of a job order (the Output analysis is fixed to it). */
    private function jobOrderProductId($jobOrderId): ?int
    {
        if (!$jobOrderId) {
            return null;
        }

        $productId = JobOrder::whereKey($jobOrderId)->value('product_id');

        return $productId ? (int) $productId : null;
    }

    /**
     * Product and specifications of the selected job order.
     * The form locks the product to it and builds the QC Checklist from these specs.
     */
    public function jobOrderDetails(Request $request)
    {
        $jobOrder = JobOrder::with(['product:id,name', 'specifications.productSlabType'])
            ->find($request->job_order_id);

        if (!$jobOrder) {
            return response()->json(['status' => 'error', 'message' => 'Job Order not found'], 404);
        }

        $specs = $jobOrder->specifications
            ->filter(fn($spec) => $spec->product_slab_type_id)
            ->unique('product_slab_type_id')
            ->map(fn($spec) => [
                'id' => (int) $spec->product_slab_type_id,
                'name' => $spec->productSlabType?->name ?? $spec->spec_name,
                'qc_symbol' => $spec->productSlabType?->qc_symbol ?: ($spec->uom ?: '%'),
                'spec_value' => $spec->spec_value,
                'value_type' => $spec->value_type,
                'uom' => $spec->uom,
            ])
            ->values();

        return response()->json([
            'status' => 'success',
            'product_id' => $jobOrder->product_id,
            'product_name' => $jobOrder->product?->name,
            'specs' => $specs,
        ]);
    }
}
