<?php

namespace App\Http\Controllers\Procurement\RawMaterial;


use App\Http\Controllers\Controller;
use App\Http\Requests\GateBuyingRequest;
use App\Models\{ArrivalPurchaseOrder, User, Product, TruckSizeRange};
use App\Models\Master\{Broker, CompanyLocation, ProductSlab, Supplier, ProductSlabForRmPo};

use App\Models\Procurement\PurchaseOrder;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;

class GateBuyingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('management.procurement.raw_material.gate_buying.index');
    }

    /**
     * Get list of categories.
     */
    public function getList(Request $request)
    {
        $arrivalPurchaseOrder = ArrivalPurchaseOrder::where('purchase_type', 'gate_buying')->when($request->filled('search'), function ($q) use ($request) {
            $searchTerm = '%' . $request->search . '%';
            return $q->where(function ($sq) use ($searchTerm) {
                $sq->where('name', 'like', $searchTerm);
            });
        })
            ->whereIn('company_location_id', getUserCurrentCompanyLocations())
            ->latest()
            ->paginate(request('per_page', 25));

        return view('management.procurement.raw_material.gate_buying.getList', compact('arrivalPurchaseOrder'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $authUserCompany = $request->company_id;
        $data['bagPackings'] = [];
        $data['truckSizeRanges'] = TruckSizeRange::where('status', 'active')->get();
        $data['products'] = Product::where('product_type', 'raw_material')->get();
        $data['accountsOf'] = User::role('Purchaser')
            ->whereHas('companies', function ($q) use ($authUserCompany) {
                $q->where('companies.id', $authUserCompany);
            })
            ->get();
        $data['companyLocations'] = CompanyLocation::whereIn('id', getUserCurrentCompanyLocations())->get();
        return view('management.procurement.raw_material.gate_buying.create', $data);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(GateBuyingRequest $request)
    {
        $data = $request->validated();
        $data = $request->all();
        $arrivalPurchaseOrder = null;
        $data['sauda_type_id'] = 1;
        if (!empty($data['broker_one'])) {
            $broker = Broker::where('name', $data['broker_one'])->first();
            $data['broker_one_id'] = $broker ? $broker->id : null;
            $data['broker_one_name'] = $data['broker_one'] ?? null;
        }

        DB::transaction(function () use ($data) {
            $arrivalPOData = collect($data)->except(['slabs'])->toArray();
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
            'success' => 'Gate Buying Created Successfully.',
            'data' => $arrivalPurchaseOrder
        ], 201);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id, Request $request )
    {

        $authUserCompany = $request->company_id;



        $data['arrivalPurchaseOrder'] = ArrivalPurchaseOrder::findOrFail($id);
        $data['bagPackings'] = [];
        $data['truckSizeRanges'] = TruckSizeRange::where('status', 'active')->get();
        $data['products'] = Product::where('product_type', 'raw_material')->get();
        $data['accountsOf'] = User::role('Purchaser')
            ->whereHas('companies', function ($q) use ($authUserCompany) {
                $q->where('companies.id', $authUserCompany);
            })
            ->get();
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
            $data['slabsHtml'] = view('management.procurement.raw_material.gate_buying.slab-form', ['slabs' => $getSlabs, 'success' => '.'])->render();
        }

        return view('management.procurement.raw_material.gate_buying.edit', $data);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(GateBuyingRequest $request, $id)
    {
        $arrivalPurchaseOrder = ArrivalPurchaseOrder::findOrFail($id);
        $data = $request->validated();
        $data = $request->all();

        DB::transaction(function () use ($data, $arrivalPurchaseOrder) {
            $arrivalPurchaseOrder->update($data);

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
            'success' => 'Gate Buying Updated Successfully.',
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

        $html = view('management.procurement.raw_material.gate_buying.slab-form', [
            'slabs' => $slabs,
            'success' => '.'
        ])->render();

        return $isView ? $html : response()->json(['html' => $html, 'success' => '.'], 200);
    }

    public function getContractNumber(Request $request)
    {
        $location = CompanyLocation::find($request->location_id);
        $date = Carbon::parse($request->contract_date)->format('Y-m-d');

        $prefix = $location->code . '-' . Carbon::parse($request->contract_date)->format('Y-m-d');

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

        return response()->json([
            'success' => true,
            'contract_no' => $contractNo
        ]);
    }

    public function getSuppliersByLocation(Request $request)
    {
        $request->validate([
            'location_id' => 'required|exists:company_locations,id'
        ]);

        $locationId = (string) $request->location_id;
        $suppliers = Supplier::whereJsonContains('company_location_ids', $locationId)->where('is_gate_buying_supplier', 'Yes')->get();

        return response()->json([
            'success' => true,
            'suppliers' => $suppliers
        ]);
    }
}
