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
            ->with(['companyLocation', 'arrivalLocation', 'plant', 'jobOrder.product'])
            ->latest('id')
            ->get();

        $analysisRequest = null;
        $initialArrivalLocations = collect();
        $initialPlants = collect();

        if ($request->filled('analysis_request_id')) {
            $analysisRequest = $analysisRequests->firstWhere('id', $request->analysis_request_id)
                ?? \App\Models\Production\ProductionAnalysisRequest::with(['companyLocation', 'arrivalLocation', 'plant', 'jobOrder.product'])->find($request->analysis_request_id);

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
        if ($analysisRequest) {
            $fixedProduct = \App\Models\Production\ProductionAnalysis::getFirstProductForRun(
                $analysisRequest->arrival_location_id,
                $analysisRequest->plant_id,
                now()->format('Y-m-d'),
                $analysisRequest->request_date?->format('Y-m-d')
            );
        }
            
        return view('management.production.production_machine_analysis.create', compact(
            'companyLocations', 
            'units', 
            'products',
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

            $matchingVoucherId = \App\Models\Production\ProductionVoucher::whereDate('prod_date', $request->date)
                ->when($request->plant_id ?: $analysisReq?->plant_id, function($q, $pId) {
                    $q->where(fn($sub) => $sub->where('plant_id', $pId)->orWhereNull('plant_id'));
                })
                ->latest('id')
                ->value('id');

            $firstProduct = \App\Models\Production\ProductionAnalysis::getFirstProductForRun(
                $request->arrival_location_id ?: $analysisReq?->arrival_location_id,
                $request->plant_id ?: $analysisReq?->plant_id,
                $request->date,
                $analysisReq?->request_date?->format('Y-m-d')
            );
            $finalProductId = $firstProduct ? $firstProduct->id : $request->product_id;

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

            // Store New Line Items
            if ($request->has('items')) {
                foreach ($request->items as $row) {
                    // Create the Row Metadata (Item)
                    $item = ProductionMachineAnalysisItem::create([
                        'machine_analysis_id' => $analysis->id,
                        'analysis_time' => $row['time'],
                        'unit_id' => $row['unit_id'] ?? null,
                    ]);

                    // Save the Slab Values for this Item (Pivot)
                    if (isset($row['params']) && is_array($row['params'])) {
                        foreach ($row['params'] as $slabTypeId => $value) {
                            if ($value !== null && $value !== '') {
                                ProductionMachineAnalysisItemSlab::create([
                                    'machine_analysis_item_id' => $item->id,
                                    'slab_type_id' => $slabTypeId,
                                    'analysis_value' => $value,
                                ]);
                            }
                        }
                    }
                }
            }

            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Machine Analysis stored successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => 'Failed to store machine analysis: ' . $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        $item = ProductionMachineAnalysis::with(['companyLocation', 'arrivalLocation', 'plant', 'machine', 'product', 'items.unit', 'items.slabs.slabType'])
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
        $item = ProductionMachineAnalysis::with(['items.slabs.slabType', 'product'])
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
            ->with(['companyLocation', 'arrivalLocation', 'plant', 'jobOrder.product'])
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

        $fixedProduct = \App\Models\Production\ProductionAnalysis::getFirstProductForRun(
            $item->arrival_location_id,
            $item->plant_id,
            $item->analysis_date,
            $item->analysisRequest?->request_date?->format('Y-m-d'),
            $item->id,
            'machine'
        );

        return view('management.production.production_machine_analysis.edit', compact(
            'item',
            'companyLocations', 
            'units', 
            'products',
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

            $analysisReq = null;
            if ($request->filled('analysis_request_id')) {
                $analysisReq = \App\Models\Production\ProductionAnalysisRequest::find($request->analysis_request_id);
            }

            $firstProduct = \App\Models\Production\ProductionAnalysis::getFirstProductForRun(
                $request->arrival_location_id ?: $analysisReq?->arrival_location_id,
                $request->plant_id ?: $analysisReq?->plant_id,
                $request->date,
                $analysisReq?->request_date?->format('Y-m-d'),
                $analysis->id,
                'machine'
            );
            $finalProductId = $firstProduct ? $firstProduct->id : $request->product_id;

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

            // Delete old items (which will cascade to slabs due to migration or manual delete if cascade not set)
            // But we have cascade on migration.
            $analysis->items()->delete();

            // Store New Line Items
            if ($request->has('items')) {
                foreach ($request->items as $row) {
                    $item = ProductionMachineAnalysisItem::create([
                        'machine_analysis_id' => $analysis->id,
                        'analysis_time' => $row['time'],
                        'unit_id' => $row['unit_id'] ?? null,
                    ]);

                    if (isset($row['params']) && is_array($row['params'])) {
                        foreach ($row['params'] as $slabTypeId => $value) {
                            if ($value !== null && $value !== '') {
                                ProductionMachineAnalysisItemSlab::create([
                                    'machine_analysis_item_id' => $item->id,
                                    'slab_type_id' => $slabTypeId,
                                    'analysis_value' => $value,
                                ]);
                            }
                        }
                    }
                }
            }

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
            $analysis->delete();
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

        $items = ProductionMachineAnalysis::with(['companyLocation', 'arrivalLocation', 'plant', 'machine', 'product'])
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

    public function checkFixedProduct(Request $request)
    {
        $analysisReq = null;
        if ($request->filled('analysis_request_id')) {
            $analysisReq = \App\Models\Production\ProductionAnalysisRequest::with('jobOrder')->find($request->analysis_request_id);
        }

        $arrivalLocationId = $request->arrival_location_id ?: $analysisReq?->arrival_location_id;
        $plantId = $request->plant_id ?: $analysisReq?->plant_id;
        $date = $request->date ?: now()->format('Y-m-d');
        $requestDate = $analysisReq?->request_date?->format('Y-m-d');
        $excludeId = $request->exclude_id;
        $excludeType = $request->exclude_type ?? 'machine';

        $firstProduct = \App\Models\Production\ProductionAnalysis::getFirstProductForRun(
            $arrivalLocationId,
            $plantId,
            $date,
            $requestDate,
            $excludeId,
            $excludeType
        );

        if ($firstProduct) {
            return response()->json([
                'status' => 'success',
                'is_fixed' => true,
                'product_id' => $firstProduct->id,
                'product_name' => $firstProduct->name,
            ]);
        }

        return response()->json([
            'status' => 'success',
            'is_fixed' => false,
            'suggested_product_id' => $analysisReq?->jobOrder?->product_id,
            'product_id' => null,
        ]);
    }
}
