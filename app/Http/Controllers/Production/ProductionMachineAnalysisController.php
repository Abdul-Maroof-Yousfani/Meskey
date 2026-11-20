<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\Master\CompanyLocation;
use App\Models\Master\ArrivalLocation;
use App\Models\Master\Plant;
use App\Models\Master\ProductSlabType;
use App\Models\Master\ProductionMachine;
use App\Models\UnitOfMeasure;
use App\Models\Product;
use App\Models\Master\ProductSlab;
use App\Models\Production\ProductionMachineAnalysis;
use App\Models\Production\ProductionMachineAnalysisItem;
use App\Models\Production\ProductionMachineAnalysisItemSlab;
use App\Http\Requests\Production\StoreProductionMachineAnalysisRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductionMachineAnalysisController extends Controller
{
    public function index()
    {
        $locationIds = getUserCurrentCompanyLocations();
        $locations = CompanyLocation::whereIn('id', $locationIds)->get();
        $arrivalLocations = ArrivalLocation::whereIn('company_location_id', $locationIds)->get();
        $plants = Plant::whereIn('company_location_id', $locationIds)->get();
        $machines = \App\Models\Master\ProductionMachine::whereIn('arrival_location_id', $arrivalLocations->pluck('id'))->get();
        return view('management.production.production_machine_analysis.index', compact('locations', 'arrivalLocations', 'plants', 'machines'));
    }

    public function create(Request $request)
    {
        $locationIds = getUserCurrentCompanyLocations();
        $companyLocations = CompanyLocation::whereIn('id', $locationIds)->get();
        
        $analysisRequests = \App\Models\Production\ProductionAnalysisRequest::where('type', \App\Models\Production\ProductionAnalysisRequest::TYPE_MACHINE)
            ->where(function ($q) use ($request) {
                $q->where('status', 'pending');
                if ($request->filled('analysis_request_id')) {
                    $q->orWhere('id', $request->analysis_request_id);
                }
            })
            ->with(['companyLocation', 'arrivalLocation', 'plant', 'jobOrder.product', 'productionVoucher'])
            ->latest('id')
            ->get();

        $analysisRequest = null;
        $initialArrivalLocations = collect();
        $initialPlants = collect();

        if ($request->filled('analysis_request_id')) {
            $analysisRequest = $analysisRequests->firstWhere('id', $request->analysis_request_id)
                ?? \App\Models\Production\ProductionAnalysisRequest::with(['companyLocation', 'arrivalLocation', 'plant', 'jobOrder.product', 'productionVoucher'])->find($request->analysis_request_id);

            if ($analysisRequest) {
                $preSelectedLocationId = $analysisRequest->company_location_id;
                $initialArrivalLocations = ArrivalLocation::where('company_location_id', $preSelectedLocationId)->get();
                $initialPlants = Plant::where('company_location_id', $preSelectedLocationId)
                    ->where('arrival_location_id', $analysisRequest->arrival_location_id)
                    ->get();
            } else {
                $preSelectedLocationId = count($companyLocations) === 1 ? $companyLocations->first()->id : null;
            }
        } elseif ($analysisRequests->count() === 1) {
            $analysisRequest = $analysisRequests->first();
            $preSelectedLocationId = $analysisRequest->company_location_id;
            $initialArrivalLocations = ArrivalLocation::where('company_location_id', $preSelectedLocationId)->get();
            $initialPlants = Plant::where('company_location_id', $preSelectedLocationId)
                ->where('arrival_location_id', $analysisRequest->arrival_location_id)
                ->get();
        } else {
            $preSelectedLocationId = count($companyLocations) === 1 ? $companyLocations->first()->id : null;
        }
        
        $units = UnitOfMeasure::all();
        $products = getFinishGoodProducts();
        $productSlabTypes = ProductSlabType::where('status', 'active')
            ->where('for_general_item', 1)
            ->get();

        $fixedProduct = null;
        $tabData = $this->tabProductData($analysisRequest);
        $jobOrders = \App\Models\Production\JobOrder\JobOrder::latest('id')->get(['id', 'job_order_no']);
        $selectedJobOrderId = $analysisRequest?->job_order_id ?? ($analysisRequest?->jobOrderIdsForAnalysis()[0] ?? null);
            
        return view('management.production.production_machine_analysis.create', compact(
            'tabData',
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

    public function store(StoreProductionMachineAnalysisRequest $request)
    {
        try {
            DB::beginTransaction();

            $analysisReq = null;
            if ($request->filled('analysis_request_id')) {
                $analysisReq = \App\Models\Production\ProductionAnalysisRequest::find($request->analysis_request_id);
            }

            // The request's own voucher for now; once the readings are saved the analysis is linked to the voucher that fits it
            $matchingVoucherId = $analysisReq?->production_voucher_id;

            // Output tab is fixed to the job order product; the analysis keeps that product (else the input tab product)
            $outputProductId = $analysisReq?->outputProductId();
            $finalProductId = $outputProductId ?: ($request->input('items.0.product_id') ?: $request->product_id);

            // Create Parent Record
            $analysis = ProductionMachineAnalysis::create([
                'analysis_date' => $request->date,
                'company_location_id' => $request->company_location_id ?: $analysisReq?->company_location_id,
                'arrival_location_id' => $request->arrival_location_id ?: $analysisReq?->arrival_location_id,
                'plant_id' => $request->plant_id ?: $analysisReq?->plant_id,
                'production_machine_id' => $request->production_machine_id,
                'product_id' => $finalProductId,
                'remarks' => $request->remarks,
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

            // Store the Input and Output tab readings
            $this->saveItems($analysis, $request->items ?? [], $outputProductId);

            // Link to the voucher whose machines were running at the reading time (or the request's voucher)
            app(\App\Services\Production\Milling\ProductionDayPlanService::class)->syncAnalysis($analysis);

            DB::commit();

            // Schedule the plant's next auto Machine Analysis request from this reading
            try {
                app(\App\Services\Production\Milling\AutoAnalysisRequestService::class)->onAnalysisCompleted($analysis);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('AutoAnalysis onAnalysisCompleted (machine) error: ' . $e->getMessage());
            }

            return response()->json(['status' => 'success', 'message' => 'Machine Analysis stored successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => 'Failed to store machine analysis: ' . $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        $item = ProductionMachineAnalysis::with(['companyLocation', 'arrivalLocation', 'plant', 'machine', 'product', 'jobOrders', 'items.unit', 'items.product', 'items.slabs.slabType'])
            ->findOrFail($id);
            
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
            $slabTypeIds = $item->items->flatMap(fn($it) => $it->slabs)->pluck('slab_type_id')->unique();
            if ($slabTypeIds->isNotEmpty()) {
                $productSlabTypes = ProductSlabType::whereIn('id', $slabTypeIds)->orderBy('id', 'ASC')->get();
            }
        }

        if ($productSlabTypes->isEmpty()) {
            $productSlabTypes = ProductSlabType::where('status', 'active')
                ->where('for_general_item', 1)
                ->get();
        }

        return view('management.production.production_machine_analysis.show', compact('item', 'productSlabTypes'));
    }

    public function edit($id)
    {
        $item = ProductionMachineAnalysis::with(['items.slabs.slabType', 'product', 'jobOrders'])
            ->findOrFail($id);
        
        $locationIds = getUserCurrentCompanyLocations();
        $companyLocations = CompanyLocation::whereIn('id', $locationIds)->get();
        $units = UnitOfMeasure::all();
        $products = getFinishGoodProducts($item->product_id);

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
            $slabTypeIds = $item->items->flatMap(fn($it) => $it->slabs)->pluck('slab_type_id')->unique();
            if ($slabTypeIds->isNotEmpty()) {
                $productSlabTypes = ProductSlabType::whereIn('id', $slabTypeIds)->orderBy('id', 'ASC')->get();
            }
        }

        if ($productSlabTypes->isEmpty()) {
            $productSlabTypes = ProductSlabType::where('status', 'active')
                ->where('for_general_item', 1)
                ->get();
        }

        $analysisRequests = \App\Models\Production\ProductionAnalysisRequest::where('type', \App\Models\Production\ProductionAnalysisRequest::TYPE_MACHINE)
            ->where(function ($q) use ($item) {
                $q->where('status', 'pending');
                if ($item->analysis_request_id) {
                    $q->orWhere('id', $item->analysis_request_id);
                }
            })
            ->with(['companyLocation', 'arrivalLocation', 'plant', 'jobOrder.product', 'productionVoucher'])
            ->latest('id')
            ->get();

        // Dependent dropdown data for edit view
        $arrivalLocations = ArrivalLocation::where('company_location_id', $item->company_location_id)->get();
        $plants = Plant::where('company_location_id', $item->company_location_id)
            ->where('arrival_location_id', $item->arrival_location_id)
            ->get();
        $machines = ProductionMachine::where('arrival_location_id', $item->arrival_location_id)
            ->where('plant_id', $item->plant_id)
            ->get();

        $fixedProduct = null;
        $tabData = $this->tabProductData($item->analysisRequest, $item);
        $jobOrders = \App\Models\Production\JobOrder\JobOrder::latest('id')->get(['id', 'job_order_no']);
        $selectedJobOrderId = $item->jobOrders->first()?->id ?? $item->analysisRequest?->job_order_id ?? ($item->analysisRequest?->jobOrderIdsForAnalysis()[0] ?? null);

        $tabItems = [
            'input' => $item->items->firstWhere('analysis_type', 'input') ?? $item->items->first(),
            'output' => $item->items->firstWhere('analysis_type', 'output'),
        ];

        return view('management.production.production_machine_analysis.edit', compact(
            'item',
            'tabItems',
            'tabData',
            'companyLocations', 
            'units', 
            'products',
            'jobOrders',
            'selectedJobOrderId',
            'fixedProduct',
            'productSlabTypes',
            'analysisRequests',
            'arrivalLocations',
            'plants',
            'machines'
        ));
    }

    public function update(StoreProductionMachineAnalysisRequest $request, $id)
    {
        try {
            DB::beginTransaction();

            $analysis = ProductionMachineAnalysis::findOrFail($id);
            $previousRequestId = $analysis->analysis_request_id;

            $analysisReq = null;
            if ($request->filled('analysis_request_id')) {
                $analysisReq = \App\Models\Production\ProductionAnalysisRequest::find($request->analysis_request_id);
            }

            // Output tab is fixed to the job order product; the analysis keeps that product (else the input tab product)
            $outputProductId = $analysisReq?->outputProductId();
            $finalProductId = $outputProductId ?: ($request->input('items.0.product_id') ?: $request->product_id);

            // Update Parent Record
            $analysis->update([
                'analysis_date' => $request->date,
                'company_location_id' => $request->company_location_id ?: $analysisReq?->company_location_id,
                'arrival_location_id' => $request->arrival_location_id ?: $analysisReq?->arrival_location_id,
                'plant_id' => $request->plant_id ?: $analysisReq?->plant_id,
                'production_machine_id' => $request->production_machine_id,
                'product_id' => $finalProductId,
                'remarks' => $request->remarks,
                'analysis_request_id' => $request->analysis_request_id ?? $analysis->analysis_request_id,
            ]);

            // Sync Job Orders (single select on UI, stored as multi in DB)
            $jobOrderIds = $request->filled('job_order_id')
                ? [$request->job_order_id]
                : (array) $request->input('job_orders', $request->input('job_order_ids', []));
            $jobOrderIds = array_values(array_filter(array_unique($jobOrderIds)));
            $analysis->jobOrders()->sync($jobOrderIds);

            // Delete old items (which will cascade to slabs due to migration or manual delete if cascade not set)
            // But we have cascade on migration.
            $analysis->items()->delete();

            // Store the Input and Output tab readings
            $this->saveItems($analysis, $request->items ?? [], $outputProductId);

            // Link to the fitting voucher; a request this analysis moved away from goes back to pending
            app(\App\Services\Production\Milling\ProductionDayPlanService::class)->syncAnalysis($analysis, $previousRequestId);

            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Machine Analysis updated successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => 'Failed to update machine analysis: ' . $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            DB::beginTransaction();
            $analysis = ProductionMachineAnalysis::findOrFail($id);
            $requestId = $analysis->analysis_request_id;
            $analysis->jobOrders()->detach();
            $analysis->delete();

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
        $machineIdsFilter = $request->production_machine_ids;
        $dateRange = $request->date_range;

        $items = ProductionMachineAnalysis::with(['companyLocation', 'arrivalLocation', 'plant', 'machine', 'product', 'jobOrders'])
            ->when($locationIdsFilter, function ($query) use ($locationIdsFilter) {
                return $query->whereIn('company_location_id', $locationIdsFilter);
            })
            ->when($arrivalLocationIdsFilter, function ($query) use ($arrivalLocationIdsFilter) {
                return $query->whereIn('arrival_location_id', $arrivalLocationIdsFilter);
            })
            ->when($plantIdsFilter, function ($query) use ($plantIdsFilter) {
                return $query->whereIn('plant_id', $plantIdsFilter);
            })
            ->when($machineIdsFilter, function ($query) use ($machineIdsFilter) {
                return $query->whereIn('production_machine_id', $machineIdsFilter);
            })
            ->when($dateRange, function ($query) use ($dateRange) {
                $dates = explode(' - ', $dateRange);
                if (count($dates) == 2) {
                    return $query->whereBetween('analysis_date', [trim($dates[0]), trim($dates[1])]);
                }
            })
            ->orderBy('id', 'DESC')
            ->paginate($limit);

        return view('management.production.production_machine_analysis.getList', compact('items'));
    }

    /**
     * Save one item per tab (input / output) with its QC values.
     * An output tab left empty is not saved.
     */
    private function saveItems(ProductionMachineAnalysis $analysis, array $rows, ?int $outputProductId = null): void
    {
        foreach ($rows as $row) {
            $type = in_array($row['analysis_type'] ?? null, ['input', 'output'], true) ? $row['analysis_type'] : 'input';
            $params = array_filter(
                is_array($row['params'] ?? null) ? $row['params'] : [],
                fn($value) => $value !== null && $value !== ''
            );

            if (empty($params) && $type === 'output') {
                continue;
            }

            $item = ProductionMachineAnalysisItem::create([
                'machine_analysis_id' => $analysis->id,
                'analysis_type' => $type,
                'product_id' => ($type === 'output' && $outputProductId) ? $outputProductId : ($row['product_id'] ?? null),
                'analysis_time' => $row['time'],
                'unit_id' => $row['unit_id'] ?? null,
            ]);

            foreach ($params as $slabTypeId => $value) {
                ProductionMachineAnalysisItemSlab::create([
                    'machine_analysis_item_id' => $item->id,
                    'slab_type_id' => $slabTypeId,
                    'analysis_value' => $value,
                ]);
            }
        }
    }

    /**
     * Products and QC slabs of the Input / Output tabs.
     * Input: RM QC products of the request (or all products), chosen freely.
     * Output: fixed to the job order product of the request.
     */
    private function tabProductData($analysisRequest, ?ProductionMachineAnalysis $analysis = null): array
    {
        $inputItem = $analysis ? ($analysis->items->firstWhere('analysis_type', 'input') ?? $analysis->items->first()) : null;
        $outputItem = $analysis?->items->firstWhere('analysis_type', 'output');

        $rmQcIds = $analysisRequest ? $analysisRequest->rmQcProductIds() : [];

        $inputProductId = $inputItem?->product_id ?: $analysis?->product_id;
        if (!$analysis && count($rmQcIds) === 1) {
            $inputProductId = $rmQcIds[0];
        }

        $outputFixedId = $analysisRequest?->outputProductId();
        $outputProductId = $outputFixedId ?: $outputItem?->product_id;

        return [
            'inputProducts' => \App\Models\Production\ProductionAnalysisRequest::inputProductOptions($analysisRequest, $inputProductId),
            'outputProducts' => getFinishGoodProducts($outputProductId),
            'fromRmQc' => !empty($rmQcIds),
            'outputFixed' => (bool) $outputFixedId,
            'productIds' => ['input' => $inputProductId, 'output' => $outputProductId],
            'slabTypes' => [
                'input' => $this->slabTypesFor($inputProductId, $inputItem),
                'output' => $this->slabTypesFor($outputProductId, $outputItem),
            ],
        ];
    }

    /** QC slab types of a product; else the ones saved on the item; else the general ones. */
    private function slabTypesFor($productId, $item = null)
    {
        $types = collect();
        if ($productId) {
            $types = ProductSlab::with('slabType')
                ->where('product_id', $productId)
                ->get()
                ->unique('product_slab_type_id')
                ->pluck('slabType')
                ->filter()
                ->values();
        }

        if ($types->isEmpty() && $item) {
            $slabTypeIds = $item->slabs->pluck('slab_type_id')->unique();
            if ($slabTypeIds->isNotEmpty()) {
                $types = ProductSlabType::whereIn('id', $slabTypeIds)->orderBy('id', 'ASC')->get();
            }
        }

        if ($types->isEmpty()) {
            $types = ProductSlabType::where('status', 'active')->where('for_general_item', 1)->get();
        }

        return $types;
    }

    public function getSlabsByProduct(Request $request)
    {
        $productId = $request->product_id;
        $slabs = ProductSlab::with('slabType')
            ->where('product_id', $productId)
            ->get()
            ->unique('product_slab_type_id')
            ->values();

        if ($slabs->isEmpty()) {
            $productSlabTypes = ProductSlabType::where("for_general_item", 1)->where("status", "active")->get();
            $slabTypes = $productSlabTypes->map(function($type) {
                return [
                    'id' => $type->id,
                    'name' => $type->name,
                    'qc_symbol' => $type->qc_symbol,
                ];
            });
            return response()->json($slabTypes);
        }
            
        $slabTypes = $slabs->map(function($slab) {
            return [
                'id' => $slab->slabType->id,
                'name' => $slab->slabType->name,
                'qc_symbol' => $slab->slabType->qc_symbol,
            ];
        });
        
        return response()->json($slabTypes);
    }

    // Methods for dynamic population (will be used in the view's JS)
    public function getArrivalLocationsByCompanyLocation($companyLocationId)
    {
        $arrivalLocations = ArrivalLocation::where('company_location_id', $companyLocationId)->get();
        return response()->json($arrivalLocations);
    }

    public function getPlantsByArrivalLocation($companyLocationId, $arrivalLocationId)
    {
        $plants = Plant::where('company_location_id', $companyLocationId)
            ->where('arrival_location_id', $arrivalLocationId)
            ->get();
        return response()->json($plants);
    }

    public function getMachinesByPlant($arrivalLocationId, $plantId)
    {
        $machines = ProductionMachine::where('arrival_location_id', $arrivalLocationId)
            ->where('plant_id', $plantId)
            ->get();
        return response()->json($machines);
    }

    /** Product options of the selected request: input tab = RM QC products (or all), output tab = job order product. */
    public function checkFixedProduct(Request $request)
    {
        $analysisReq = $request->filled('analysis_request_id')
            ? \App\Models\Production\ProductionAnalysisRequest::find($request->analysis_request_id)
            : null;

        $rmQcIds = $analysisReq ? $analysisReq->rmQcProductIds() : [];
        $outputProduct = $analysisReq?->outputProductId() ? Product::find($analysisReq->outputProductId()) : null;

        return response()->json([
            'status' => 'success',
            'is_fixed' => false,
            'input_products' => \App\Models\Production\ProductionAnalysisRequest::inputProductOptions($analysisReq, $request->include_product_id),
            'from_rm_qc' => !empty($rmQcIds),
            'suggested_input_product_id' => count($rmQcIds) === 1 ? $rmQcIds[0] : null,
            'output_product_id' => $outputProduct?->id,
            'output_product_name' => $outputProduct?->name,
        ]);
    }
}
