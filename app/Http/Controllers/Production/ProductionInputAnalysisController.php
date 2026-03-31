<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Production\JobOrder\JobOrder;
use App\Models\Master\Brands;
use App\Models\BagPacking;
use App\Models\Master\CompanyLocation;
use App\Models\Master\CropYear;
use App\Models\Master\ProductSlabType;
use App\Models\Production\ProductionAnalysis;
use App\Models\Production\ProductionAnalysisItem;
use App\Models\Production\ProductionAnalysisItemSlab;
use App\Models\UnitOfMeasure;
use App\Http\Requests\Production\StoreProductionInputAnalysisRequest;
use Illuminate\Support\Facades\DB;

class ProductionInputAnalysisController extends Controller
{
    public function index()
    {
        $jobOrders = JobOrder::all();
        $brands = Brands::all();
        $locations = CompanyLocation::all();
        return view('management.production.production_input_analysis.index', compact('jobOrders', 'brands', 'locations'));
    }

    public function create()
    {
        $jobOrders = JobOrder::all();
        $brands = Brands::all();
        $packings = BagPacking::all();
        $companyLocations = CompanyLocation::all();
        $cropYears = CropYear::all();
        $units = UnitOfMeasure::all();
        $productSlabTypes = ProductSlabType::select("id", "name", "qc_symbol")
                                            ->where("for_general_item", 1)
                                            ->where("status", "active")
                                            ->get(); 
        
        return view('management.production.production_input_analysis.create', compact(
            'jobOrders', 
            'brands', 
            'packings', 
            'productSlabTypes',
            'companyLocations', 
            'cropYears',
            'units'
        ));
    }

    public function store(StoreProductionInputAnalysisRequest $request)
    {
        try {
            DB::beginTransaction();

            // Create Parent Record
            $analysis = ProductionAnalysis::create([
                'analysis_date' => $request->date,
                'brand_id' => $request->brand_id,
                'bag_packing_id' => $request->packing_id,
                'location_id' => $request->location_id,
                'variety' => $request->variety,
                'crop_year_id' => $request->crop_year_id,
                'remarks' => $request->remarks,
                'production_analysis_type' => 'input',
            ]);

            // Save Job Orders (Pivot Table)
            if ($request->has('job_order_ids')) {
                foreach ($request->job_order_ids as $jobOrderId) {
                    DB::table('job_orders_against_production_analysis')->insert([
                        'job_order_id' => $jobOrderId,
                        'production_id' => $analysis->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            // Store New Line Items
            if ($request->has('items')) {
                foreach ($request->items as $row) {
                    // Create the Row Metadata (Item)
                    $item = ProductionAnalysisItem::create([
                        'production_analysis_id' => $analysis->id,
                        'analysis_time' => $row['time'],
                        'unit_id' => $row['unit_id'] ?? null,
                    ]);

                    // Save the Slab Values for this Item (Pivot)
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
        $item = ProductionAnalysis::with(['brand', 'location', 'product', 'jobOrders', 'items.unit', 'items.slabs.slabType'])
            ->findOrFail($id);
            
        // Get general slab types
        $productSlabTypes = ProductSlabType::where("for_general_item", 1)
                                            ->where("status", "active")
                                            ->get();

        return view('management.production.production_input_analysis.show', compact('item', 'productSlabTypes'));
    }

    public function edit($id)
    {
        $item = ProductionAnalysis::with(['jobOrders', 'items.slabs'])
                                    ->findOrFail($id);
        
        $jobOrders = JobOrder::all();
        $brands = Brands::all();
        $packings = BagPacking::all();
        $companyLocations = CompanyLocation::all();
        $cropYears = CropYear::all();
        $units = UnitOfMeasure::all();

        $productSlabTypes = ProductSlabType::select("id", "name", "qc_symbol")
                                            ->where("for_general_item", 1)
                                            ->where("status", "active")
                                            ->get();
        
        $selectedJobOrderIds = $item->jobOrders->pluck('id')->toArray();

        return view('management.production.production_input_analysis.edit', compact(
            'item',
            'jobOrders', 
            'brands', 
            'packings', 
            'productSlabTypes',
            'companyLocations', 
            'cropYears',
            'units',
            'selectedJobOrderIds'
        ));
    }

    public function update(StoreProductionInputAnalysisRequest $request, $id)
    {
        try {
            DB::beginTransaction();

            $analysis = ProductionAnalysis::findOrFail($id);

            // Update Parent Record
            $analysis->update([
                'analysis_date' => $request->date,
                'brand_id' => $request->brand_id,
                'bag_packing_id' => $request->packing_id,
                'location_id' => $request->location_id,
                'variety' => $request->variety,
                'crop_year_id' => $request->crop_year_id,
                'remarks' => $request->remarks,
            ]);

            // Sync Job Orders
            if ($request->has('job_order_ids')) {
                $analysis->jobOrders()->sync($request->job_order_ids);
            } else {
                $analysis->jobOrders()->sync([]);
            }

            // Delete old items (which will cascade to slabs)
            $analysis->items()->delete();

            // Store New Line Items
            if ($request->has('items')) {
                foreach ($request->items as $row) {
                    // Create the Row Metadata (Item)
                    $item = ProductionAnalysisItem::create([
                        'production_analysis_id' => $analysis->id,
                        'analysis_time' => $row['time'],
                        'unit_id' => $row['unit_id'] ?? null,
                    ]);

                    // Save the Slab Values for this Item (Pivot)
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
            
            // Delete pivot table records
            $analysis->jobOrders()->detach();
            
            // Delete related data
            ProductionAnalysisData::where('production_analysis_id', $analysis->id)->delete();
            
            // Delete parent
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
        $jobOrderIdsFilter = $request->job_order_ids;
        $brandIdsFilter = $request->brand_ids;
        $locationIdsFilter = $request->location_ids;
        $varietySearch = $request->variety_search;
        $dateRange = $request->date_range;

        $items = ProductionAnalysis::with(['brand', 'location', 'jobOrders'])
            ->where('production_analysis_type', 'input')
            ->when($brandIdsFilter, function ($query) use ($brandIdsFilter) {
                return $query->whereIn('brand_id', $brandIdsFilter);
            })
            ->when($locationIdsFilter, function ($query) use ($locationIdsFilter) {
                return $query->whereIn('location_id', $locationIdsFilter);
            })
            ->when($varietySearch, function ($query) use ($varietySearch) {
                return $query->where('variety', 'LIKE', "%$varietySearch%");
            })
            ->when($dateRange, function ($query) use ($dateRange) {
                $dates = explode(' - ', $dateRange);
                if (count($dates) == 2) {
                    return $query->whereBetween('analysis_date', [trim($dates[0]), trim($dates[1])]);
                }
            })
            ->when($jobOrderIdsFilter, function ($query) use ($jobOrderIdsFilter) {
                return $query->whereHas('jobOrders', function ($q) use ($jobOrderIdsFilter) {
                    $q->whereIn('job_orders.id', $jobOrderIdsFilter);
                });
            })
            ->orderBy('id', 'DESC')
            ->paginate($limit);

        return view('management.production.production_input_analysis.getList', compact('items'));
    }
}
