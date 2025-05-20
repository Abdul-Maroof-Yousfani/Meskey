<?php

namespace App\Http\Controllers\Procurement\RawMaterial;

use App\Http\Controllers\Controller;
use App\Http\Requests\Procurement\PurchaseFreightRequest;
use App\Models\Procurement\PurchaseFreight;
use App\Models\ArrivalPurchaseOrder;
use App\Models\BagCondition;
use App\Models\BagType;
use App\Models\Master\Station;
use Illuminate\Http\Request;

class PurchaseFreightController extends Controller
{
    public function index()
    {
        $stations = Station::all();
        $bagTypes = BagCondition::all();
        return view('management.procurement.raw_material.freight.index', compact('stations', 'bagTypes'));
    }

    public function getList(Request $request)
    {
        $arrivalPurchaseOrders = ArrivalPurchaseOrder::where('freight_status', 'pending')->where('company_id', $request->company_id)
            ->latest()
            ->paginate(request('per_page', 25));

        return view('management.procurement.raw_material.freight.getList', compact('arrivalPurchaseOrders'));
    }

    public function create(Request $request)
    {
        $stations = Station::all();
        $bagTypes = BagCondition::all();

        $purchaseOrder = ArrivalPurchaseOrder::with(['supplier', 'broker', 'product'])
            ->find($request->arrival_purchase_order_id);

        if (!$purchaseOrder) {
            return response()->json(['success' => false, 'message' => 'Purchase order not found'], 404);
        }

        $html = view('management.procurement.raw_material.freight.partials.freight_form', [
            'purchaseOrder' => $purchaseOrder,
            'stations' => $stations,
            'bagTypes' => $bagTypes
        ])->render();

        return view('management.procurement.raw_material.freight.create', compact('stations', 'bagTypes', 'html'));
    }

    public function store(PurchaseFreightRequest $request)
    {
        $data = $request->validated();
        $data['company_id'] = $request->company_id;

        $freight = PurchaseFreight::create($data);

        if ($request->arrival_purchase_order_id) {
            ArrivalPurchaseOrder::where('id', $request->arrival_purchase_order_id)
                ->update(['freight_status' => 'completed']);
        }

        return response()->json([
            'success' => 'Purchase freight created successfully.',
            'data' => $freight
        ], 201);
    }

    public function edit($id)
    {
        $freight = PurchaseFreight::with(['purchaseOrder', 'station', 'bagCondition'])->findOrFail($id);

        $stations = Station::all();
        $bagTypes = BagCondition::all();

        $purchaseOrder = ArrivalPurchaseOrder::with(['supplier', 'broker', 'product'])
            ->find($id);

        if (!$purchaseOrder) {
            return response()->json(['success' => false, 'message' => 'Purchase order not found'], 404);
        }

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
