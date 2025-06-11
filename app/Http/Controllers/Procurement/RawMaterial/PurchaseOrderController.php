<?php

namespace App\Http\Controllers\Procurement\RawMaterial;


use App\Http\Controllers\Controller;
use App\Http\Requests\ArrivalPurchaseOrderRequest;
use App\Models\ArrivalPurchaseOrder;
use App\Models\Master\CompanyLocation;
use App\Models\Master\ProductSlab;
use App\Models\Procurement\PurchaseOrder;
use App\Models\Product;
use App\Models\Master\ProductSlabForRmPo;
use App\Models\TruckSizeRange;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;

class PurchaseOrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('management.procurement.raw_material.purchase_order.index');
    }

    /**
     * Get list of categories.
     */
    public function getList(Request $request)
    {
        $arrivalPurchaseOrder = ArrivalPurchaseOrder::when($request->filled('search'), function ($q) use ($request) {
            $searchTerm = '%' . $request->search . '%';
            return $q->where(function ($sq) use ($searchTerm) {
                $sq->where('name', 'like', $searchTerm);
            });
        })
            ->when($request->filled('company_location_id'), function ($q) use ($request) {
                return $q->where('company_location_id', $request->company_location_id);
            })
            ->when($request->filled('sauda_type_id'), function ($q) use ($request) {
                return $q->where('sauda_type_id', $request->sauda_type_id);
            })
            ->where('purchase_type', 'regular')
            ->latest()
            ->paginate($request->get('per_page', 25));

        return view('management.procurement.raw_material.purchase_order.getList', compact('arrivalPurchaseOrder'));
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $data['bagPackings'] = [];
        $data['truckSizeRanges'] = TruckSizeRange::where('status', 'active')->get();
        $data['products'] = Product::where('product_type', 'raw_material')->get();

        return view('management.procurement.raw_material.purchase_order.create', $data);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ArrivalPurchaseOrderRequest $request)
    {
        $data = $request->validated();
        $data = $request->all();
        $arrivalPurchaseOrder = null;

        $data['contract_no'] = self::getContractNumber($request, $request->company_location_id, $request->contract_date);

        DB::transaction(function () use ($data, $request) {
            $arrivalPOData = collect($data)->except(['slabs', 'quantity_range', 'truck_size_range'])->toArray();

            $arrivalPOData['is_replacement'] = ($request->is_replacement ?? 'off') == 'on' ? true : false;

            if (isset($data['truck_size_range'])) {
                $arrivalPOData['truck_size_range_id'] = $data['truck_size_range'];
            }

            $arrivalPurchaseOrder = ArrivalPurchaseOrder::create($arrivalPOData);

            if (isset($data['slabs']) && count($data['slabs']) > 0) {
                foreach ($data['slabs'] as $slabId => $range) {
                    ProductSlabForRmPo::create([
                        'arrival_purchase_order_id' => $arrivalPurchaseOrder->id,
                        'slab_id' => $slabId,
                        'company_id' => $data['company_id'],
                        'product_id' => $data['product_id'],
                        'product_slab_type_id' => $range['product_slab_type_id'],
                        'from' => $range['from'],
                        'to' => $range['to'],
                        'deduction_type' => $range['deduction_type'],
                        'deduction_value' => null,
                        'status' => 'active',
                    ]);
                }
            }
        });

        return response()->json([
            'success' => 'Purchase Order Created Successfully.',
            'data' => $arrivalPurchaseOrder
        ], 201);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $data['arrivalPurchaseOrder'] = ArrivalPurchaseOrder::findOrFail($id);
        $data['bagPackings'] = [];
        $data['truckSizeRanges'] = TruckSizeRange::where('status', 'active')->get();
        $data['products'] = Product::where('product_type', 'raw_material')->get();

        $getSlabs = ProductSlabForRmPo::with('slabType')
            ->where('product_id', $data['arrivalPurchaseOrder']->product_id)
            ->where('company_id', $data['arrivalPurchaseOrder']->company_id)
            ->where('arrival_purchase_order_id', $id)
            ->get()
            ->groupBy('product_slab_type_id')
            ->map(function ($group) {
                return $group->sortBy(function ($item) {
                    return (float) $item->from;
                })->first();
            })
            ->values()
            ->map(function ($item) {
                $item['slab_type_name'] = $item->slabType->name ?? null;
                $item['id'] = $item->slab_id ?? null;
                return $item;
            });

        if (!count($getSlabs)) {
            $ids = [
                'product_id' => $data['arrivalPurchaseOrder']->product_id,
                'company_id' => $data['arrivalPurchaseOrder']->company_id
            ];

            $data['slabsHtml'] = $this->getMainSlabByProduct(request(), $ids, true);
        } else {
            $data['slabsHtml'] = view('management.procurement.raw_material.purchase_order.slab-form', ['slabs' => $getSlabs, 'success' => '.'])->render();
        }

        return view('management.procurement.raw_material.purchase_order.edit', $data);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ArrivalPurchaseOrderRequest $request, $id)
    {
        $arrivalPurchaseOrder = ArrivalPurchaseOrder::findOrFail($id);
        $data = $request->validated();
        $data = $request->all();

        DB::transaction(function () use ($data, $arrivalPurchaseOrder) {
            $updateData = [
                'sauda_type_id' => $data['sauda_type_id'] ?? null,
                'supplier_id' => $data['supplier_id'] ?? null,
                'supplier_commission' => $data['supplier_commission'] ?? null,
                'broker_one_id' => $data['broker_one_id'] ?? null,
                'broker_one_commission' => $data['broker_one_commission'] ?? 0,
                'broker_two_id' => $data['broker_two_id'] ?? null,
                'broker_two_commission' => $data['broker_two_commission'] ?? 0,
                'broker_three_id' => $data['broker_three_id'] ?? null,
                'broker_three_commission' => $data['broker_three_commission'] ?? 0,
                'product_id' => $data['product_id'] ?? null,
                'line_type' => $data['line_type'] ?? null,
                'bag_weight' => $data['bag_weight'] ?? null,
                'bag_rate' => $data['bag_rate'] ?? null,
                'delivery_date' => $data['delivery_date'] ?? null,
                'credit_days' => $data['credit_days'] ?? null,
                'rate_per_kg' => $data['rate_per_kg'] ?? null,
                'rate_per_mound' => $data['rate_per_mound'] ?? null,
                'rate_per_100kg' => $data['rate_per_100kg'] ?? null,
                'calculation_type' => $data['calculation_type'] ?? null,
                'is_replacement' => isset($data['is_replacement']) ? true : false,
                'weighbridge_from' => $data['weighbridge_from'] ?? null,
                'delivery_address' => $data['delivery_address'] ?? null,
                'remarks' => $data['remarks'] ?? null,
            ];

            if ($data['calculation_type'] == 'trucks') {
                $updateData['truck_size_range_id'] = $data['truck_size_range'] ?? null;
                $updateData['no_of_trucks'] = $data['no_of_trucks'] ?? null;
                $updateData['total_quantity'] = null ?? null;
            } else {
                $updateData['truck_size_range_id'] = null;
                $updateData['no_of_trucks'] = null;
                $updateData['total_quantity'] = $data['total_quantity'] ?? null;
            }

            $updateData['min_quantity'] = $data['min_quantity'] ?? null;
            $updateData['max_quantity'] = $data['max_quantity'] ?? null;
            $updateData['min_bags'] = $data['min_bags'] ?? null;
            $updateData['max_bags'] = $data['max_bags'] ?? null;

            $arrivalPurchaseOrder->update($updateData);

            ProductSlabForRmPo::where('arrival_purchase_order_id', $arrivalPurchaseOrder->id)->delete();

            if (isset($data['slabs']) && count($data['slabs']) > 0) {
                foreach ($data['slabs'] as $slabId => $range) {
                    ProductSlabForRmPo::create([
                        'arrival_purchase_order_id' => $arrivalPurchaseOrder->id,
                        'slab_id' => $slabId,
                        'company_id' => $data['company_id'],
                        'product_id' => $data['product_id'],
                        'product_slab_type_id' => $range['product_slab_type_id'],
                        'from' => $range['from'],
                        'to' => $range['to'],
                        'deduction_type' => $range['deduction_type'],
                        'deduction_value' => null,
                        'status' => 'active',
                    ]);
                }
            }
        });

        return response()->json([
            'success' => 'Purchase Order Updated Successfully.',
            'data' => $arrivalPurchaseOrder
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $arrival_location = ArrivalPurchaseOrder::findOrFail($id);
        $arrival_location->delete();
        return response()->json(['success' => 'Purchase Order deleted successfully.'], 200);
    }

    public function getMainSlabByProduct(Request $request, $ids = [], $isView = false)
    {
        $productId = $isView ? Arr::get($ids, 'product_id') : $request->product_id;
        $companyId = $isView ? Arr::get($ids, 'company_id') : $request->company_id;

        $slabs = ProductSlab::with('slabType')
            ->where('product_id', $productId)
            ->where('company_id', $companyId)
            ->where('is_purchase_field', 1)
            ->get()
            ->groupBy('product_slab_type_id')
            ->map(fn($group) => $group->sortBy(fn($item) => (float) $item->from)->first())
            ->values()
            ->map(function ($item) {
                $item['slab_type_name'] = $item->slabType->name ?? null;
                return $item;
            });

        $html = view('management.procurement.raw_material.purchase_order.slab-form', [
            'slabs' => $slabs,
            'success' => '.'
        ])->render();

        return $isView ? $html : response()->json(['html' => $html, 'success' => '.'], 200);
    }

    public function getContractNumber(Request $request, $locationId = null, $contractDate = null)
    {
        $location = CompanyLocation::find($locationId ?? $request->location_id);
        $date = Carbon::parse($contractDate ?? $request->contract_date)->format('Y-m-d');

        $prefix = $location->code . '-' . Carbon::parse($contractDate ?? $request->contract_date)->format('Y-m-d');

        $latestContract = ArrivalPurchaseOrder::where('contract_no', 'like', "$prefix-%")
            ->latest()
            ->first();

        $locationCode = $location->code ?? 'LOC';
        $datePart = Carbon::parse($date)->format('Y-m-d');

        if ($latestContract) {
            $parts = explode('-', $latestContract->contract_no);
            $lastNumber = (int) end($parts);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        $contractNo = $locationCode . '-' . $datePart . '-' . str_pad($newNumber, 3, '0', STR_PAD_LEFT);

        if (!$locationId && !$contractDate) {
            return response()->json([
                'success' => true,
                'contract_no' => $contractNo
            ]);
        }

        return $contractNo;
    }
}
