<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\Master\CompanyLocation;
use App\Models\Master\ArrivalLocation;
use App\Models\Master\Plant;
use App\Models\Master\ProductSlabType;
use App\Models\Master\ProductionMachine;
use App\Models\UnitOfMeasure;
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
        $locations = CompanyLocation::all();
        return view('management.production.production_machine_analysis.index', compact('locations'));
    }

    public function create()
    {
        $companyLocations = CompanyLocation::all();
        $units = UnitOfMeasure::all();
        $productSlabTypes = ProductSlabType::where('status', 'active')
            ->where('for_general_item', 1)
            ->get();
            
        return view('management.production.production_machine_analysis.create', compact('companyLocations', 'units', 'productSlabTypes'));
    }

    public function store(StoreProductionMachineAnalysisRequest $request)
    {
        try {
            DB::beginTransaction();

            // Create Parent Record
            $analysis = ProductionMachineAnalysis::create([
                'analysis_date' => $request->date,
                'company_location_id' => $request->company_location_id,
                'arrival_location_id' => $request->arrival_location_id,
                'plant_id' => $request->plant_id,
                'production_machine_id' => $request->production_machine_id,
                'remarks' => $request->remarks,
            ]);

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
        $item = ProductionMachineAnalysis::with(['companyLocation', 'arrivalLocation', 'plant', 'machine', 'items.unit', 'items.slabs.slabType'])
            ->findOrFail($id);
            
        $productSlabTypes = ProductSlabType::where('status', 'active')
            ->where('for_general_item', 1)
            ->get();

        return view('management.production.production_machine_analysis.show', compact('item', 'productSlabTypes'));
    }

    public function edit($id)
    {
        $item = ProductionMachineAnalysis::with(['items.slabs'])
                                    ->findOrFail($id);
        
        $companyLocations = CompanyLocation::all();
        $units = UnitOfMeasure::all();
        $productSlabTypes = ProductSlabType::where('status', 'active')
            ->where('for_general_item', 1)
            ->get();

        // Dependent dropdown data for edit view
        $arrivalLocations = ArrivalLocation::where('company_location_id', $item->company_location_id)->get();
        $plants = Plant::where('company_location_id', $item->company_location_id)
            ->where('arrival_location_id', $item->arrival_location_id)
            ->get();
        $machines = ProductionMachine::where('arrival_location_id', $item->arrival_location_id)
            ->where('plant_id', $item->plant_id)
            ->get();

        return view('management.production.production_machine_analysis.edit', compact(
            'item',
            'companyLocations', 
            'units', 
            'productSlabTypes',
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

            // Update Parent Record
            $analysis->update([
                'analysis_date' => $request->date,
                'company_location_id' => $request->company_location_id,
                'arrival_location_id' => $request->arrival_location_id,
                'plant_id' => $request->plant_id,
                'production_machine_id' => $request->production_machine_id,
                'remarks' => $request->remarks,
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
        $dateRange = $request->date_range;

        $items = ProductionMachineAnalysis::with(['companyLocation', 'arrivalLocation', 'plant', 'machine'])
            ->when($locationIdsFilter, function ($query) use ($locationIdsFilter) {
                return $query->whereIn('company_location_id', $locationIdsFilter);
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
}
