<?php

namespace App\Http\Controllers\Procurement\RawMaterial;

use App\Http\Controllers\Controller;
use App\Http\Requests\Procurement\PurchaseFreightRequest;
use App\Models\Procurement\PurchaseFreight;
use App\Models\ArrivalPurchaseOrder;
use App\Models\BagType;
use App\Models\Master\Station;
use Illuminate\Http\Request;

class PurchaseFreightController extends Controller
{
    public function index()
    {
        $stations = Station::all();
        $bagTypes = BagType::all();
        return view('management.procurement.raw_material.freight.index', compact('stations', 'bagTypes'));
    }

    public function getList(Request $request)
    {
        $freights = PurchaseFreight::with(['purchaseOrder', 'station', 'bagType'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $q->where('truck_no', 'like', '%' . $request->search . '%')
                    ->orWhere('bilty_no', 'like', '%' . $request->search . '%')
                    ->orWhere('supplier_name', 'like', '%' . $request->search . '%');
            })
            ->where('company_id', $request->company_id)
            ->latest()
            ->paginate(request('per_page', 25));

        return view('management.procurement.raw_material.freight.getList', compact('freights'));
    }

    public function create()
    {
        $purchaseOrders = ArrivalPurchaseOrder::where('freight_status', 'pending')
            ->get();

        $stations = Station::all();
        $bagTypes = BagType::all();

        return view('management.procurement.raw_material.freight.create', [
            'purchaseOrders' => $purchaseOrders,
            'stations' => $stations,
            'bagTypes' => $bagTypes
        ]);
    }

    public function store(PurchaseFreightRequest $request)
    {
        $data = $request->validated();

        // Add company_id from the authenticated user
        $data['company_id'] = auth()->user()->company_id;

        // Create the purchase freight record
        $freight = PurchaseFreight::create($data);

        return response()->json([
            'success' => 'Purchase freight created successfully.',
            'data' => $freight
        ], 201);
    }

    public function edit($id)
    {
        $freight = PurchaseFreight::with(['purchaseOrder', 'station', 'bagType'])->findOrFail($id);
        $stations = Station::all();
        $bagTypes = BagType::all();

        return view('management.procurement.raw_material.freight.edit', compact('freight', 'stations', 'bagTypes'));
    }

    public function update(PurchaseFreightRequest $request, PurchaseFreight $purchaseFreight)
    {
        $data = $request->validated();

        // Handle file updates if needed
        $purchaseFreight->update($data);

        return response()->json([
            'success' => 'Purchase freight updated successfully.',
            'data' => $purchaseFreight
        ], 200);
    }

    public function destroy(PurchaseFreight $purchaseFreight)
    {
        $purchaseFreight->delete();
        return response()->json(['success' => 'Purchase freight deleted successfully.'], 200);
    }

    public function getPurchaseOrderDetails(Request $request)
    {
        $purchaseOrder = ArrivalPurchaseOrder::with(['supplier', 'broker', 'product'])
            ->find($request->arrival_purchase_order_id);

        if (!$purchaseOrder) {
            return response()->json(['success' => false, 'message' => 'Purchase order not found'], 404);
        }

        $html = view('management.procurement.raw_material.freight.partials.purchase_order_details', [
            'purchaseOrder' => $purchaseOrder
        ])->render();

        return response()->json([
            'success' => true,
            'html' => $html,
            'supplier_name' => $purchaseOrder->supplier->name ?? '',
            'broker_name' => $purchaseOrder->broker->name ?? '',
            'commodity' => $purchaseOrder->product->name ?? ''
        ]);
    }
}
