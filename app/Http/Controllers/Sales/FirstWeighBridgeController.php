<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Sales\FirstWeighbridge;
use App\Models\Sales\DeliveryOrder;
use App\Models\Master\ArrivalTruckType;
use App\Models\Master\WeighbridgeAmount;
use App\Models\Sales\SalesOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FirstWeighBridgeController extends Controller
{
    function __construct()
    {
        // $this->middleware('check.company:sales-first-weighbridge', ['only' => ['index']]);
        // $this->middleware('check.company:sales-first-weighbridge', ['only' => ['edit']]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('management.sales.first-weighbridge.index');
    }

    /**
     * Get list of first weighbridges.
     */
    public function getList(Request $request)
    {
        
        $FirstWeighbridges = FirstWeighbridge::with(['deliveryOrder.customer', 'deliveryOrder.delivery_order_data.item'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $searchTerm = '%' . $request->search . '%';
                return $q->where(function ($sq) use ($searchTerm) {
                    $sq->where('name', 'like', $searchTerm);
                });
            })
            ->latest()
            ->paginate($request->get('per_page', 25));

        return view('management.sales.first-weighbridge.getList', compact('FirstWeighbridges'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
   
        $data = [
            'ArrivalTruckTypes' => ArrivalTruckType::where('status', 'active')->get(),
            'DeliveryOrders' => collect(), // Empty collection initially
            "SaleOrders" => SalesOrder::whereHas("delivery_orders", function($query) {
                $query->where("am_approval_status", "approved")
                        ->whereDoesntHave("firstWeighbridge");
            })
                ->where("am_approval_status", "approved")
                ->get()
        ];

        return view('management.sales.first-weighbridge.create', $data);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'delivery_order_id' => 'required|exists:delivery_order,id',
            'first_weight' => 'required|numeric',
            'truck_type_id' => 'required|exists:arrival_truck_types,id',
            'remark' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $deliveryOrder = DeliveryOrder::find($request->delivery_order_id);
        $request['created_by'] = auth()->user()->id;
        $request['company_id'] = $request->company_id;

        // Fetch weighbridge amount from WeighbridgeAmount model based on truck type and arrival location
        $weighbridgeAmount = WeighbridgeAmount::where('truck_type_id', $request->truck_type_id)
            ->where('company_location_id', $deliveryOrder->location_id)
            ->first();

        if (!$weighbridgeAmount) {
            return response()->json(['errors' => ['truck_type_id' => 'Weighbridge amount not found for selected truck type and arrival location.']], 422);
        }

        $request['weighbridge_amount'] = $weighbridgeAmount->weighbridge_amount;

        $firstWeighbridge = FirstWeighbridge::create($request->all());

        return response()->json(['success' => 'First Weighbridge created successfully.', 'data' => $firstWeighbridge], 201);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $data['FirstWeighbridge'] = FirstWeighbridge::findOrFail($id);
        $data['ArrivalTruckTypes'] = ArrivalTruckType::where('status', 'active')->get();
        $data['DeliveryOrders'] = DeliveryOrder::with('customer', 'delivery_order_data.item')
            ->where('am_approval_status', 'approved')
            ->get();
        $data['DeliveryOrder'] = DeliveryOrder::where('id', $data['FirstWeighbridge']->delivery_order_id)->first();

        return view('management.sales.first-weighbridge.edit', $data);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'delivery_order_id' => 'required|exists:delivery_order,id',
            'first_weight' => 'required|numeric',
            'truck_type_id' => 'required|exists:arrival_truck_types,id',
            'remark' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $firstWeighbridge = FirstWeighbridge::findOrFail($id);
        $deliveryOrder = DeliveryOrder::find($request->delivery_order_id);
        $request['company_id'] = $request->company_id;

        // Fetch weighbridge amount from WeighbridgeAmount model based on truck type and arrival location
        $weighbridgeAmount = WeighbridgeAmount::where('truck_type_id', $request->truck_type_id)
            ->where('company_location_id', $deliveryOrder->arrival_location_id)
            ->first();

        if (!$weighbridgeAmount) {
            return response()->json(['errors' => ['truck_type_id' => 'Weighbridge amount not found for selected truck type and arrival location.']], 422);
        }

        $request['weighbridge_amount'] = $weighbridgeAmount->weighbridge_amount;

        $firstWeighbridge->update($request->all());

        return response()->json(['success' => 'First Weighbridge updated successfully.', 'data' => $firstWeighbridge], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $firstWeighbridge = FirstWeighbridge::findOrFail($id);
        $firstWeighbridge->delete();
        return response()->json(['success' => 'First Weighbridge deleted successfully.'], 200);
    }

    public function getFirstWeighbridgeRelatedData(Request $request)
    {
        $DeliveryOrder = DeliveryOrder::with('customer', 'salesOrder', 'delivery_order_data.item', 'delivery_order_data.salesOrderData', 'arrivalLocation', 'subArrivalLocation')
            ->findOrFail($request->delivery_order_id);

        $ArrivalTruckTypes = \App\Models\Master\ArrivalTruckType::where('status', 'active')->get();

        // Render view with the delivery order data
        $html = view('management.sales.first-weighbridge.getFirstWeighbridgeRelatedData', compact('DeliveryOrder', 'ArrivalTruckTypes'))->with('FirstWeighbridge', null)->render();

        return response()->json(['success' => true, 'html' => $html]);
    }

    public function getWeighbridgeAmount(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'truck_type_id' => 'required|exists:arrival_truck_types,id',
            'delivery_order_id' => 'required|exists:delivery_order,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $deliveryOrder = DeliveryOrder::find($request->delivery_order_id);

        $weighbridgeAmount = WeighbridgeAmount::where('truck_type_id', $request->truck_type_id)
            ->where('company_location_id', $deliveryOrder->location_id)
            ->first();

        if ($weighbridgeAmount) {
            return response()->json([
                'success' => true,
                'weighbridge_amount' => $weighbridgeAmount->weighbridge_amount
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Weighbridge amount not found for selected truck type and arrival location.'
            ]);
        }
    }

    public function getDeliveryOrdersBySaleOrder(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'sale_order_id' => 'required|exists:sales_orders,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $deliveryOrders = DeliveryOrder::where('so_id', $request->sale_order_id)
            ->whereDoesntHave('firstWeighbridge')
            ->where('am_approval_status', 'approved')
            ->with('customer', 'delivery_order_data.item')
            ->get();

        return response()->json([
            'success' => true,
            'delivery_orders' => $deliveryOrders
        ]);
    }
}
