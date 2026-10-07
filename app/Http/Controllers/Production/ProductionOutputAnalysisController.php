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
            ->with(['companyLocation', 'arrivalLocation', 'plant', 'jobOrder'])
            ->latest('id')
            ->get();

        $analysisRequest = null;
        $initialArrivalLocations = collect();
        $initialPlants = collect();

        if ($request->filled('analysis_request_id')) {
            $analysisRequest = $analysisRequests->firstWhere('id', $request->analysis_request_id)
                ?? \App\Models\Production\ProductionAnalysisRequest::with(['companyLocation', 'arrivalLocation', 'plant', 'jobOrder'])->find($request->analysis_request_id);

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
        $products = Product::where('status', 'active')->get();
        $productSlabTypes = ProductSlabType::where('status', 'active')
            ->where('for_general_item', 1)
            ->get();
            
        return view('management.production.production_output_analysis.create', compact(
            'companyLocations', 
            'units', 
            'products', 
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
        try {
            DB::beginTransaction();

            $analysisReq = null;
            if ($request->filled('analysis_request_id')) {
                $analysisReq = \App\Models\Production\ProductionAnalysisRequest::find($request->analysis_request_id);
            }

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
                'product_id' => $request->product_id,
                'crop_year_id' => $request->crop_year_id,
                'brand_id' => $request->brand_id,
                'variety' => $request->variety,
                'bag_packing_id' => $request->bag_packing_id,
                'production_analysis_type' => 'output',
                'analysis_request_id' => $request->analysis_request_id ?? null,
            ]);

            // If created against an Analysis Request, mark it completed & link Job Order if present
            if ($request->filled('analysis_request_id')) {
                $analysisReq = \App\Models\Production\ProductionAnalysisRequest::find($request->analysis_request_id);
                if ($analysisReq) {
                    $analysisReq->update(['status' => 'completed']);
                    if ($analysisReq->job_order_id) {
                        DB::table('job_orders_against_production_analysis')->insertOrIgnore([
                            'job_order_id' => $analysisReq->job_order_id,
                            'production_id' => $analysis->id,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
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

            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Analysis stored successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => 'Failed to store analysis: ' . $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        $item = ProductionAnalysis::with(['location', 'arrivalLocation', 'plant', 'product', 'items.unit', 'items.slabs.slabType'])
            ->findOrFail($id);
            
        $slabTypeIds = $item->items->flatMap(fn($it) => $it->slabs)->pluck('slab_type_id')->unique();
        $productSlabTypes = ProductSlabType::whereIn('id', $slabTypeIds)->orderBy('id', 'ASC')->get();

        if ($productSlabTypes->isEmpty()) {
            $productSlabTypes = ProductSlabType::where("for_general_item", 1)->where("status", "active")->get();
        }

        return view('management.production.production_output_analysis.show', compact('item', 'productSlabTypes'));
    }

    public function edit($id)
    {
        $item = ProductionAnalysis::with(['items.slabs.slabType'])->findOrFail($id);
        
        $locationIds = getUserCurrentCompanyLocations();
        $companyLocations = CompanyLocation::whereIn('id', $locationIds)->get();
        $units = UnitOfMeasure::all();
        $products = Product::where('status', 'active')->get();

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
            ->with(['companyLocation', 'arrivalLocation', 'plant', 'jobOrder'])
            ->latest('id')
            ->get();

        $arrivalLocations = \App\Models\Master\ArrivalLocation::where('company_location_id', $item->location_id)->get();
        $plants = \App\Models\Master\Plant::where('company_location_id', $item->location_id)
            ->where('arrival_location_id', $item->arrival_location_id)
            ->get();

        return view('management.production.production_output_analysis.edit', compact(
            'item', 
            'productSlabTypes',
            'companyLocations', 
            'products',
            'units',
            'analysisRequests',
            'arrivalLocations',
            'plants'
        ));
    }

    public function update(Request $request, $id)
    {
        try {
            DB::beginTransaction();

            $analysis = ProductionAnalysis::findOrFail($id);

            $analysisReq = null;
            if ($request->filled('analysis_request_id')) {
                $analysisReq = \App\Models\Production\ProductionAnalysisRequest::find($request->analysis_request_id);
            }

            $analysis->update([
                'analysis_date' => $request->date,
                'location_id' => $request->location_id ?: $analysisReq?->company_location_id,
                'arrival_location_id' => $request->arrival_location_id ?: $analysisReq?->arrival_location_id,
                'plant_id' => $request->plant_id ?: $analysisReq?->plant_id,
                'milling_degree' => $request->milling_degree,
                'inner_stitching' => $request->inner_stitching,
                'outer_stitching' => $request->outer_stitching,
                'remarks' => $request->remarks,
                'product_id' => $request->product_id,
                'analysis_request_id' => $request->analysis_request_id ?? $analysis->analysis_request_id,
            ]);

            $analysis->jobOrders()->detach();
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

            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Analysis updated successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => 'Failed to update analysis: ' . $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            DB::beginTransaction();
            $analysis = ProductionAnalysis::findOrFail($id);
            $analysis->jobOrders()->detach();
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
        $dateRange = $request->date_range;

        $items = ProductionAnalysis::with(['location', 'arrivalLocation', 'plant'])
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
}
