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
        $locations = get_locations();

        return view('management.sales.pre_sale_inspection.index', compact('items', 'locations'));
    }

    public function getList(Request $request)
    {
        $perPage = $request->get('per_page', 25);

        $inspections = PreSaleInspection::with([
            'location',
            'items.item',
            'items.factory',
            'items.section',
            'creator',
            'salesInquiries',
            'salesOrders'
        ])
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = '%' . strtolower($request->search) . '%';
                return $q->where(function ($sq) use ($search) {
                    $sq->whereRaw('LOWER(inspection_no) LIKE ?', [$search])
                        ->orWhereRaw('LOWER(party_name) LIKE ?', [$search])
                        ->orWhereRaw('LOWER(party_contact_no) LIKE ?', [$search])
                        ->orWhereRaw('LOWER(reference) LIKE ?', [$search])
                        ->orWhereRaw('LOWER(remarks) LIKE ?', [$search])
                        ->orWhereHas('location', function ($lq) use ($search) {
                            $lq->whereRaw('LOWER(name) LIKE ?', [$search]);
                        })
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
            ->when($request->filled('location_id') && $request->location_id != 'all', function ($q) use ($request) {
                return $q->where('location_id', $request->location_id);
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
            ->orderBy('id', 'desc')
            ->paginate($perPage);

        return view('management.sales.pre_sale_inspection.getList', compact('inspections'));
    }

    public function create()
    {
        $locations = get_locations();
        $items = Product::all();
        $arrivalLocations = ArrivalLocation::with("companyLocation")->select('id', 'name', 'company_location_id')->where('status', 'active')->get();
        $arrivalSubLocations = ArrivalSubLocation::with("arrivalLocation")->select('id', 'name', 'arrival_location_id')->where('status', 'active')->get();

        return view('management.sales.pre_sale_inspection.create', compact('locations', 'items', 'arrivalLocations', 'arrivalSubLocations'));
    }

    public function store(PreSaleInspectionRequest $request)
    {
        try {
            DB::beginTransaction();

            $inspectionNo = $request->inspection_no;
            if (empty($inspectionNo) || PreSaleInspection::where('inspection_no', $inspectionNo)->exists()) {
                $inspectionNo = self::generateUniqueNumber($request->date);
            }

            $inspection = PreSaleInspection::create([
                'inspection_no' => $inspectionNo,
                'date' => $request->date,
                'location_id' => $request->location_id,
                'party_name' => $request->party_name,
                'party_contact_no' => $request->party_contact_no,
                'reference' => $request->reference,
                'remarks' => $request->remarks ?? '',
                'status' => 'active',
                'company_id' => auth()->user()?->company_id ?? 1,
                'created_by' => auth()->user()?->id ?? 1,
            ]);

            // Save items with factory, section, and weight
            $itemIds = $request->item_id ?? [];
            $factoryIds = $request->arrival_location_id ?? [];
            $sectionIds = $request->arrival_sub_location_id ?? [];
            $weights = $request->weight ?? [];

            foreach ($itemIds as $index => $itemId) {
                if (!empty($itemId)) {
                    $inspection->items()->create([
                        'item_id' => $itemId,
                        'arrival_location_id' => !empty($factoryIds[$index]) ? $factoryIds[$index] : null,
                        'arrival_sub_location_id' => !empty($sectionIds[$index]) ? $sectionIds[$index] : null,
                        'weight' => $weights[$index] ?? 0,
                    ]);
                }
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
        $pre_sale_inspection->load('location', 'items.item', 'items.factory', 'items.section');
        $locations = get_locations();
        $items = Product::all();
        $arrivalLocations = ArrivalLocation::with("companyLocation")->select('id', 'name', 'company_location_id')->where('status', 'active')->get();
        $arrivalSubLocations = ArrivalSubLocation::with("arrivalLocation")->select('id', 'name', 'arrival_location_id')->where('status', 'active')->get();

        return view('management.sales.pre_sale_inspection.edit', compact('pre_sale_inspection', 'locations', 'items', 'arrivalLocations', 'arrivalSubLocations'));
    }

    public function update(PreSaleInspectionRequest $request, PreSaleInspection $pre_sale_inspection)
    {
        try {
            DB::beginTransaction();

            $pre_sale_inspection->update([
                'inspection_no' => $request->inspection_no,
                'date' => $request->date,
                'location_id' => $request->location_id,
                'party_name' => $request->party_name,
                'party_contact_no' => $request->party_contact_no,
                'reference' => $request->reference,
                'remarks' => $request->remarks ?? '',
                'status' => 'active',
            ]);

            // Sync items
            $pre_sale_inspection->items()->delete();

            $itemIds = $request->item_id ?? [];
            $factoryIds = $request->arrival_location_id ?? [];
            $sectionIds = $request->arrival_sub_location_id ?? [];
            $weights = $request->weight ?? [];

            foreach ($itemIds as $index => $itemId) {
                if (!empty($itemId)) {
                    $pre_sale_inspection->items()->create([
                        'item_id' => $itemId,
                        'arrival_location_id' => !empty($factoryIds[$index]) ? $factoryIds[$index] : null,
                        'arrival_sub_location_id' => !empty($sectionIds[$index]) ? $sectionIds[$index] : null,
                        'weight' => $weights[$index] ?? 0,
                    ]);
                }
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
        $pre_sale_inspection->load('location', 'items.item', 'items.factory', 'items.section', 'creator', 'salesInquiries', 'salesOrders');
        return view('management.sales.pre_sale_inspection.view', compact('pre_sale_inspection'));
    }

    public function show(PreSaleInspection $pre_sale_inspection)
    {
        return $this->view($pre_sale_inspection);
    }

    public function destroy(PreSaleInspection $pre_sale_inspection)
    {
        if ($pre_sale_inspection->salesInquiries()->exists() || $pre_sale_inspection->salesOrders()->exists()) {
            return response()->json([
                'error' => 'This Pre Sale Inspection is linked with a Sales Inquiry or Sales Order and cannot be deleted.',
                'message' => 'This Pre Sale Inspection is linked with a Sales Inquiry or Sales Order and cannot be deleted.'
            ], 422);
        }

        try {
            DB::beginTransaction();

            $pre_sale_inspection->items()->delete();
            $pre_sale_inspection->delete();

            DB::commit();

            return response()->json(['success' => 'Pre Sale Inspection deleted successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public static function generateUniqueNumber($dateParam = null)
    {
        $date = Carbon::parse($dateParam ?? date('Y-m-d'))->format('Y-m-d');
        $prefix = 'PSI-' . $date;

        $existing = PreSaleInspection::where('inspection_no', 'like', "$prefix-%")
            ->lockForUpdate()
            ->pluck('inspection_no')
            ->toArray();

        $maxNumber = 0;
        foreach ($existing as $no) {
            $parts = explode('-', $no);
            $num = (int) end($parts);
            if ($num > $maxNumber) {
                $maxNumber = $num;
            }
        }

        $newNumber = $maxNumber + 1;
        $candidate = $prefix . '-' . str_pad($newNumber, 3, '0', STR_PAD_LEFT);

        while (in_array($candidate, $existing) || PreSaleInspection::where('inspection_no', $candidate)->exists()) {
            $newNumber++;
            $candidate = $prefix . '-' . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
        }

        return $candidate;
    }

    public function getNumber(Request $request, $inspectionDate = null)
    {
        $dateParam = $inspectionDate ?? $request->inspection_date ?? $request->date ?? date('Y-m-d');
        $inspection_no = self::generateUniqueNumber($dateParam);

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
            'location',
            'items.item',
            'items.factory',
            'items.section',
        ])->findOrFail($id);

        return response()->json([
            'id' => $inspection->id,
            'inspection_no' => $inspection->inspection_no,
            'date' => $inspection->date ? $inspection->date->format('Y-m-d') : null,
            'location_id' => $inspection->location_id,
            'location_name' => $inspection->location?->name,
            'party_name' => $inspection->party_name,
            'party_contact_no' => $inspection->party_contact_no,
            'reference' => $inspection->reference,
            'items' => $inspection->items->map(function ($it) {
                return [
                    'id' => $it->id,
                    'item_id' => $it->item_id,
                    'item_name' => $it->item?->name,
                    'arrival_location_id' => $it->arrival_location_id,
                    'factory_name' => $it->factory?->name,
                    'arrival_sub_location_id' => $it->arrival_sub_location_id,
                    'section_name' => $it->section?->name,
                    'weight' => $it->weight,
                ];
            }),
            'remarks' => $inspection->remarks,
            'status' => $inspection->status ?? 'active',
        ]);
    }
}
