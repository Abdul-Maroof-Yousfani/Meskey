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
        $locationIds = getUserCurrentCompanyLocations();
        $locations = CompanyLocation::whereIn('id', $locationIds)->get();
        $arrivalLocations = \App\Models\Master\ArrivalLocation::whereIn('company_location_id', $locationIds)->get();
        $plants = \App\Models\Master\Plant::whereIn('company_location_id', $locationIds)->get();
        return view('management.production.production_input_analysis.index', compact('locations', 'arrivalLocations', 'plants'));
    }

    public function create()
    {
        $locationIds = getUserCurrentCompanyLocations();
        $companyLocations = CompanyLocation::whereIn('id', $locationIds)->get();
        
        // Pre-select if only one location is assigned
        $preSelectedLocationId = count($companyLocations) === 1 ? $companyLocations->first()->id : null;
        
        $units = UnitOfMeasure::all();
        $productSlabTypes = ProductSlabType::where('status', 'active')
            ->where('for_general_item', 1)
            ->get();
            
        return view('management.production.production_input_analysis.create', compact('companyLocations', 'units', 'productSlabTypes', 'preSelectedLocationId'));
    }

    public function store(StoreProductionInputAnalysisRequest $request)
    {
        try {
            DB::beginTransaction();

            // Create Parent Record
            $analysis = ProductionAnalysis::create([
                'analysis_date' => $request->date,
                'location_id' => $request->location_id,
                'arrival_location_id' => $request->arrival_location_id,
                'plant_id' => $request->plant_id,
                'remarks' => $request->remarks,
                'production_analysis_type' => 'input',
            ]);

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
        $item = ProductionAnalysis::with(['location', 'arrivalLocation', 'plant', 'items.unit', 'items.slabs.slabType'])
            ->findOrFail($id);
            
        // Get general slab types
        $productSlabTypes = ProductSlabType::where("for_general_item", 1)
                                            ->where("status", "active")
                                            ->get();

        return view('management.production.production_input_analysis.show', compact('item', 'productSlabTypes'));
    }

    public function edit($id)
    {
        $item = ProductionAnalysis::with(['items.slabs'])
                                    ->findOrFail($id);
        
        $locationIds = getUserCurrentCompanyLocations();
        $companyLocations = CompanyLocation::whereIn('id', $locationIds)->get();
        $units = UnitOfMeasure::all();

        $productSlabTypes = ProductSlabType::select("id", "name", "qc_symbol")
                                            ->where("for_general_item", 1)
                                            ->where("status", "active")
                                            ->get();
        
        // Dependent dropdown data for edit view
        $arrivalLocations = \App\Models\Master\ArrivalLocation::where('company_location_id', $item->location_id)->get();
        $plants = \App\Models\Master\Plant::where('company_location_id', $item->location_id)
            ->where('arrival_location_id', $item->arrival_location_id)
            ->get();

        return view('management.production.production_input_analysis.edit', compact(
            'item',
            'productSlabTypes',
            'companyLocations', 
            'units',
            'arrivalLocations',
            'plants'
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
                'location_id' => $request->location_id,
                'arrival_location_id' => $request->arrival_location_id,
                'plant_id' => $request->plant_id,
                'remarks' => $request->remarks,
            ]);

            // Sync Job Orders (Detach all regardless of presence in request as we don't use them anymore)
            $analysis->jobOrders()->detach();

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
        $locationIdsFilter = $request->location_ids;
        $arrivalLocationIdsFilter = $request->arrival_location_ids;
        $plantIdsFilter = $request->plant_ids;
        $dateRange = $request->date_range;

        $items = ProductionAnalysis::with(['location', 'arrivalLocation', 'plant'])
            ->where('production_analysis_type', 'input')
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

        return view('management.production.production_input_analysis.getList', compact('items'));
    }
}
