<?php

namespace App\Http\Controllers\Procurement\RawMaterial;

use App\Http\Controllers\Controller;
use App\Http\Requests\Procurement\PurchaseFreightRequest;
use App\Models\Procurement\PurchaseFreight;
use App\Models\ArrivalPurchaseOrder;
use App\Models\BagCondition;
use App\Models\BagType;
use App\Models\Master\Station;
use App\Models\PurchaseTicket;
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
        $purchaseTickets = PurchaseTicket::when('purchaseOrderLoadedQuantity')->where('freight_status', 'pending')
            ->when($request->filled('company_location_id'), function ($q) use ($request) {
                return $q->whereHas('purchaseOrder', function ($query) use ($request) {
                    $query->where('company_location_id', $request->company_location_id);
                });
            })
            ->whereHas('purchaseOrder', function ($query) {
                $query->whereIn('company_location_id', getUserCurrentCompanyLocations());
            })
            ->when($request->filled('supplier_id'), function ($q) use ($request) {
                return $q->whereHas('purchaseOrder', function ($query) use ($request) {
                    $query->where('supplier_id', $request->supplier_id);
                });
            })
            ->when($request->filled('daterange'), function ($q) use ($request) {
                $dates = explode(' - ', $request->daterange);
                $startDate = \Carbon\Carbon::createFromFormat('m/d/Y', trim($dates[0]))->format('Y-m-d');
                $endDate = \Carbon\Carbon::createFromFormat('m/d/Y', trim($dates[1]))->format('Y-m-d');

                return $q->whereDate('created_at', '>=', $startDate)
                    ->whereDate('created_at', '<=', $endDate);
            })
            ->where('company_id', $request->company_id)
            ->whereNotNull('purchase_order_id')
            ->latest()
            ->paginate($request->get('per_page', 25));

        return view('management.procurement.raw_material.freight.getList', compact('purchaseTickets'));
    }

    public function create(Request $request)
    {
        $stations = Station::all();
        $bagTypes = BagCondition::all();

        $ticket = PurchaseTicket::with(['purchaseOrder', 'purchaseOrder.supplier', 'purchaseOrder.broker', 'purchaseOrder.product'])
            ->find($request->purchase_ticket_id);

        if (!$ticket) {
            return response()->json(['success' => false, 'message' => 'Purchase order not found'], 404);
        }

        return view('management.procurement.raw_material.freight.create', compact('stations', 'bagTypes', 'ticket', 'bagTypes'));
    }

    public function store(PurchaseFreightRequest $request)
    {
        $data = $request->validated();
        $data['company_id'] = $request->company_id;

        if (!empty($data['station'])) {
            $station = Station::where('name', $data['station'])->first();
            $data['station_id'] = $station ? $station->id : null;
            $data['station_name'] = $data['station'];
        }

        $freight = PurchaseFreight::create($data);

        PurchaseTicket::where('id', $request->purchase_ticket_id)
            ->update(['freight_status' => 'completed']);

        return response()->json([
            'success' => 'Purchase Loading created successfully.',
            'data' => $freight
        ], 201);
    }

    public function edit($id)
    {
        $ticket = PurchaseTicket::with(['purchaseOrder'])->findOrFail($id);
        $freight = PurchaseFreight::with(['purchaseOrder', 'station', 'bagType'])
            ->where('purchase_ticket_id', $ticket->id)
            ->where('arrival_purchase_order_id', $ticket->purchase_order_id)
            ->firstOrFail();

        $stations = Station::all();
        $bagTypes = BagCondition::all();

        return view('management.procurement.raw_material.freight.edit', compact('ticket', 'freight', 'stations', 'bagTypes'));
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
