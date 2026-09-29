<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sales\PreSaleInspectionRequest;
use App\Models\Master\ArrivalLocation;
use App\Models\Master\ArrivalSubLocation;
use App\Models\Master\CompanyLocation;
use App\Models\Product;
use App\Models\Sales\PreSaleInspection;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;

class PreSaleInspectionController extends Controller
{
    public function index()
    {
        $items = Product::all();

        return view('management.sales.pre_sale_inspection.index', compact('items'));
    }

    public function getList(Request $request)
    {
        $perPage = $request->get('per_page', 25);

        $inspections = PreSaleInspection::with([
            'items.item',
            'creator',
            'locationModels.companyLocation',
            'factoryModels.factory',
            'sectionModels.section',
            'salesInquiries'
        ])
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = '%' . strtolower($request->search) . '%';
                return $q->where(function ($sq) use ($search) {
                    $sq->whereRaw('LOWER(inspection_no) LIKE ?', [$search])
                        ->orWhereRaw('LOWER(party_name) LIKE ?', [$search])
                        ->orWhereRaw('LOWER(party_contact_no) LIKE ?', [$search])
                        ->orWhereRaw('LOWER(reference) LIKE ?', [$search])
                        ->orWhereRaw('LOWER(remarks) LIKE ?', [$search])
                        ->orWhereHas('items.item', function ($iq) use ($search) {
                            $iq->whereRaw('LOWER(name) LIKE ?', [$search]);
                        });
                });
            })
            ->when($request->filled('inspection_no'), function ($q) use ($request) {
                return $q->where('inspection_no', 'like', '%' . $request->inspection_no . '%');
            })
            ->when($request->filled('party_name'), function ($q) use ($request) {
                return $q->where('party_name', 'like', '%' . $request->party_name . '%');
            })
            ->when($request->filled('item_id') && $request->item_id != 'all', function ($q) use ($request) {
                return $q->whereHas('items', function ($iq) use ($request) {
                    $iq->where('item_id', $request->item_id);
                });
            })
            ->when($request->filled('date_range'), function ($q) use ($request) {
                $dates = explode(' - ', $request->date_range);
                if (count($dates) == 2) {
                    return $q->whereBetween('date', [trim($dates[0]), trim($dates[1])]);
                }
            })
            // ->when($request->filled('status') && $request->status != 'all', function ($q) use ($request) {
            //     return $q->where('status', $request->status);
            // })
            ->orderBy('id', 'desc')
            ->paginate($perPage);

        return view('management.sales.pre_sale_inspection.getList', compact('inspections'));
    }

    public function create()
    {
        $items = Product::all();
        $arrivalLocations = ArrivalLocation::with("companyLocation")->select('id', 'name', 'company_location_id')->where('status', 'active')->get();
        $arrivalSubLocations = ArrivalSubLocation::with("arrivalLocation")->select('id', 'name', 'arrival_location_id')->where('status', 'active')->get();

        return view('management.sales.pre_sale_inspection.create', compact('items', 'arrivalLocations', 'arrivalSubLocations'));
    }

    public function store(PreSaleInspectionRequest $request)
    {
        try {
            DB::beginTransaction();

            $inspectionNo = $request->inspection_no;
            if (empty($inspectionNo)) {
                $inspectionNo = self::getNumber($request, $request->date);
            }

            $locations = $request->locations ?? [];
            $factoryIds = $request->arrival_location_id ?? [];
            $sectionIds = $request->arrival_sub_location_id ?? [];
            $itemIds = $request->item_id ?? [];
            $weights = $request->weight ?? [];
            $firstItemId = $itemIds[0] ?? null;

            $inspection = PreSaleInspection::create([
                'inspection_no' => $inspectionNo,
                'date' => $request->date,
                'party_name' => $request->party_name,
                'party_contact_no' => $request->party_contact_no,
                'reference' => $request->reference,
                'item_id' => $firstItemId,
                'locations' => $locations,
                'factories' => $factoryIds,
                'sections' => $sectionIds,
                'arrival_location_id' => $factoryIds[0] ?? null,
                'arrival_sub_location_id' => $sectionIds[0] ?? null,
                'remarks' => $request->remarks ?? '',
                'status' => 'active',
                'company_id' => auth()->user()?->company_id ?? 1,
                'created_by' => auth()->user()?->id ?? 1,
            ]);

            // Save items
            foreach ($itemIds as $index => $itemId) {
                if (!empty($itemId)) {
                    $inspection->items()->create([
                        'item_id' => $itemId,
                        'weight' => $weights[$index] ?? 0,
                    ]);
                }
            }

            // Save polymorphic relations
            foreach ($locations as $locationId) {
                $inspection->locations()->create(['location_id' => $locationId]);
            }

            foreach ($factoryIds as $factoryId) {
                $inspection->factories()->create(['arrival_location_id' => $factoryId]);
            }

            foreach ($sectionIds as $sectionId) {
                $inspection->sections()->create(['arrival_sub_location_id' => $sectionId]);
            }

            DB::commit();

            return response()->json("Pre Sale Inspection has been created successfully");
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(["error" => $e->getMessage()], 500);
        }
    }

    public function edit(PreSaleInspection $pre_sale_inspection)
    {
        $pre_sale_inspection->load('locations', 'factories', 'sections', 'locationModels.companyLocation', 'factoryModels.factory', 'sectionModels.section', 'items.item');
        $items = Product::all();
        $arrivalLocations = ArrivalLocation::with("companyLocation")->select('id', 'name', 'company_location_id')->where('status', 'active')->get();
        $arrivalSubLocations = ArrivalSubLocation::with("arrivalLocation")->select('id', 'name', 'arrival_location_id')->where('status', 'active')->get();

        return view('management.sales.pre_sale_inspection.edit', compact('pre_sale_inspection', 'items', 'arrivalLocations', 'arrivalSubLocations'));
    }

    public function update(PreSaleInspectionRequest $request, PreSaleInspection $pre_sale_inspection)
    {
        try {
            DB::beginTransaction();

            $locations = $request->locations ?? [];
            $factoryIds = $request->arrival_location_id ?? [];
            $sectionIds = $request->arrival_sub_location_id ?? [];
            $itemIds = $request->item_id ?? [];
            $weights = $request->weight ?? [];
            $firstItemId = $itemIds[0] ?? null;

            $pre_sale_inspection->update([
                'inspection_no' => $request->inspection_no,
                'date' => $request->date,
                'party_name' => $request->party_name,
                'party_contact_no' => $request->party_contact_no,
                'reference' => $request->reference,
                'item_id' => $firstItemId,
                'locations' => $locations,
                'factories' => $factoryIds,
                'sections' => $sectionIds,
                'arrival_location_id' => $factoryIds[0] ?? null,
                'arrival_sub_location_id' => $sectionIds[0] ?? null,
                'remarks' => $request->remarks ?? '',
                'status' => 'active',
            ]);

            // Sync items
            $pre_sale_inspection->items()->delete();
            foreach ($itemIds as $index => $itemId) {
                if (!empty($itemId)) {
                    $pre_sale_inspection->items()->create([
                        'item_id' => $itemId,
                        'weight' => $weights[$index] ?? 0,
                    ]);
                }
            }

            // Sync polymorphic relations
            $pre_sale_inspection->locations()->delete();
            $pre_sale_inspection->factories()->delete();
            $pre_sale_inspection->sections()->delete();

            foreach ($locations as $locationId) {
                $pre_sale_inspection->locations()->create(['location_id' => $locationId]);
            }

            foreach ($factoryIds as $factoryId) {
                $pre_sale_inspection->factories()->create(['arrival_location_id' => $factoryId]);
            }

            foreach ($sectionIds as $sectionId) {
                $pre_sale_inspection->sections()->create(['arrival_sub_location_id' => $sectionId]);
            }

            DB::commit();

            return response()->json("Pre Sale Inspection has been updated successfully");
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(["error" => $e->getMessage()], 500);
        }
    }

    public function view(PreSaleInspection $pre_sale_inspection)
    {
        $pre_sale_inspection->load('locationModels.companyLocation', 'factoryModels.factory', 'sectionModels.section', 'items.item', 'creator');
        return view('management.sales.pre_sale_inspection.view', compact('pre_sale_inspection'));
    }

    public function show(PreSaleInspection $pre_sale_inspection)
    {
        return $this->view($pre_sale_inspection);
    }

    public function destroy(PreSaleInspection $pre_sale_inspection)
    {
        if ($pre_sale_inspection->salesInquiries()->exists()) {
            return response()->json([
                'error' => 'This Pre Sale Inspection is linked with a Sales Inquiry and cannot be deleted.',
                'message' => 'This Pre Sale Inspection is linked with a Sales Inquiry and cannot be deleted.'
            ], 422);
        }

        try {
            DB::beginTransaction();

            $pre_sale_inspection->items()->delete();
            $pre_sale_inspection->locations()->delete();
            $pre_sale_inspection->factories()->delete();
            $pre_sale_inspection->sections()->delete();
            $pre_sale_inspection->delete();

            DB::commit();

            return response()->json(['success' => 'Pre Sale Inspection deleted successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getNumber(Request $request, $inspectionDate = null)
    {
        $dateParam = $inspectionDate ?? $request->inspection_date ?? date('Y-m-d');
        $date = Carbon::parse($dateParam)->format('Y-m-d');
        $prefix = 'PSI-' . $date;

        $latest = PreSaleInspection::where('inspection_no', 'like', "$prefix-%")
            ->latest('id')
            ->first();

        if ($latest) {
            $parts = explode('-', $latest->inspection_no);
            $lastNumber = (int) end($parts);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        $inspection_no = $prefix . '-' . str_pad($newNumber, 3, '0', STR_PAD_LEFT);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'inspection_no' => $inspection_no
            ]);
        }

        return $inspection_no;
    }

    public function getInspectionData($id)
    {
        $inspection = PreSaleInspection::with([
            'items.item',
            'locationModels.companyLocation',
            'factoryModels.factory',
            'sectionModels.section'
        ])->findOrFail($id);

        $locationIds = is_array($inspection->locations) ? $inspection->locations : [];
        if (empty($locationIds)) {
            $locationIds = $inspection->locationModels->pluck('location_id')->filter()->values()->toArray();
        }

        $factoryIds = is_array($inspection->factories) ? $inspection->factories : [];
        if (empty($factoryIds)) {
            $factoryIds = $inspection->factoryModels->pluck('arrival_location_id')->filter()->values()->toArray();
        }

        $sectionIds = is_array($inspection->sections) ? $inspection->sections : [];
        if (empty($sectionIds)) {
            $sectionIds = $inspection->sectionModels->pluck('arrival_sub_location_id')->filter()->values()->toArray();
        }

        $locationData = CompanyLocation::whereIn('id', $locationIds)->get(['id', 'name']);

        return response()->json([
            'id' => $inspection->id,
            'inspection_no' => $inspection->inspection_no,
            'date' => $inspection->date ? $inspection->date->format('Y-m-d') : null,
            'party_name' => $inspection->party_name,
            'party_contact_no' => $inspection->party_contact_no,
            'reference' => $inspection->reference,
            'item_id' => $inspection->item_id,
            'items' => $inspection->items->map(function ($it) {
                return [
                    'id' => $it->id,
                    'item_id' => $it->item_id,
                    'item_name' => $it->item?->name,
                    'weight' => $it->weight,
                ];
            }),
            'remarks' => $inspection->remarks,
            'status' => $inspection->status ?? 'active',
            'location_ids' => $locationIds,
            'factory_ids' => $factoryIds,
            'section_ids' => $sectionIds,
            'location_data' => $locationData,
        ]);
    }
}
