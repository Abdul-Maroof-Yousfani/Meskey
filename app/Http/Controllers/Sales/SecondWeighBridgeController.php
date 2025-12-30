<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Sales\SecondWeighbridge;
use App\Models\Sales\DeliveryOrder;
use App\Models\Sales\FirstWeighbridge;
use App\Models\Sales\SalesOrder;
use App\Models\Master\ArrivalTruckType;
use App\Models\Master\WeighbridgeAmount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SecondWeighBridgeController extends Controller
{
    function __construct()
    {
        // $this->middleware('check.company:sales-second-weighbridge', ['only' => ['index']]);
        // $this->middleware('check.company:sales-second-weighbridge', ['only' => ['edit']]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('management.sales.second-weighbridge.index');
    }

    /**
     * Get list of second weighbridges.
     */
    public function getList(Request $request)
    {
        $SecondWeighbridges = SecondWeighbridge::with(['deliveryOrder.customer', 'deliveryOrder.delivery_order_data.item'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $searchTerm = '%' . $request->search . '%';
                return $q->where(function ($sq) use ($searchTerm) {
                    $sq->where('name', 'like', $searchTerm);
                });
            })
            ->latest()
            ->paginate($request->get('per_page', 25));

        return view('management.sales.second-weighbridge.getList', compact('SecondWeighbridges'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {

        $data = [
            'ArrivalTruckTypes' => ArrivalTruckType::where('status', 'active')->get(),
            'DeliveryOrders' => collect(), // Empty collection initially
            'SaleOrders' => SalesOrder::whereHas('delivery_orders', function($query) {
                $query->where('am_approval_status', 'approved')
                      ->whereHas('firstWeighbridge')
                      ->whereDoesntHave('secondWeighbridge');
            })->where('am_approval_status', 'approved')->get()
        ];

        return view('management.sales.second-weighbridge.create', $data);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'delivery_order_id' => 'required|exists:delivery_order,id',
            'second_weight' => 'required|numeric',
            'truck_type_id' => 'required|exists:arrival_truck_types,id',
            'remark' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Get first weighbridge data to calculate net weight
        $firstWeighbridge = FirstWeighbridge::where('delivery_order_id', $request->delivery_order_id)->first();
        if (!$firstWeighbridge) {
            return response()->json(['errors' => ['delivery_order_id' => 'First weighbridge not found for this delivery order.']], 422);
        }

        $deliveryOrder = DeliveryOrder::find($request->delivery_order_id);
        $request['created_by'] = auth()->user()->id;
        $request['company_id'] = $request->company_id;
        $request['first_weight'] = $firstWeighbridge->first_weight;
        $request['net_weight'] = $request->second_weight - $firstWeighbridge->first_weight;

        // Fetch weighbridge amount from WeighbridgeAmount model based on truck type and arrival location
        $weighbridgeAmount = WeighbridgeAmount::where('truck_type_id', $request->truck_type_id)
            ->where('company_location_id', $deliveryOrder->location_id)
            ->first();

        if (!$weighbridgeAmount) {
            return response()->json(['errors' => ['truck_type_id' => 'Weighbridge amount not found for selected truck type and arrival location.']], 422);
        }

        $request['weighbridge_amount'] = $weighbridgeAmount->weighbridge_amount;

        $secondWeighbridge = SecondWeighbridge::create($request->all());

        return response()->json(['success' => 'Second Weighbridge created successfully.', 'data' => $secondWeighbridge], 201);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $authUser = auth()->user();
        $data['SecondWeighbridge'] = SecondWeighbridge::findOrFail($id);
        $data['ArrivalTruckTypes'] = ArrivalTruckType::where('status', 'active')->get();
        $data['DeliveryOrders'] = DeliveryOrder::with('customer', 'delivery_order_data.item')
            ->where('am_approval_status', 'approved')
            ->get();
        $data['DeliveryOrder'] = DeliveryOrder::where('id', $data['SecondWeighbridge']->delivery_order_id)->first();

        return view('management.sales.second-weighbridge.edit', $data);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'delivery_order_id' => 'required|exists:delivery_order,id',
            'second_weight' => 'required|numeric',
            'truck_type_id' => 'required|exists:arrival_truck_types,id',
            'remark' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $secondWeighbridge = SecondWeighbridge::findOrFail($id);

        // Get first weighbridge data to calculate net weight
        $firstWeighbridge = FirstWeighbridge::where('delivery_order_id', $request->delivery_order_id)->first();
        if (!$firstWeighbridge) {
            return response()->json(['errors' => ['delivery_order_id' => 'First weighbridge not found for this delivery order.']], 422);
        }

        $deliveryOrder = DeliveryOrder::find($request->delivery_order_id);
        $request['company_id'] = $request->company_id;
        $request['first_weight'] = $firstWeighbridge->first_weight;
        $request['net_weight'] = $request->second_weight - $firstWeighbridge->first_weight;

        // Fetch weighbridge amount from WeighbridgeAmount model based on truck type and arrival location
        $weighbridgeAmount = WeighbridgeAmount::where('truck_type_id', $request->truck_type_id)
            ->where('company_location_id', $deliveryOrder->arrival_location_id)
            ->first();

        if (!$weighbridgeAmount) {
            return response()->json(['errors' => ['truck_type_id' => 'Weighbridge amount not found for selected truck type and arrival location.']], 422);
        }

        $request['weighbridge_amount'] = $weighbridgeAmount->weighbridge_amount;

        $secondWeighbridge->update($request->all());

        return response()->json(['success' => 'Second Weighbridge updated successfully.', 'data' => $secondWeighbridge], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $secondWeighbridge = SecondWeighbridge::findOrFail($id);
        $secondWeighbridge->delete();
        return response()->json(['success' => 'Second Weighbridge deleted successfully.'], 200);
    }

    public function getSecondWeighbridgeRelatedData(Request $request)
    {
        $DeliveryOrder = DeliveryOrder::with('customer', 'salesOrder', 'delivery_order_data.item', 'delivery_order_data.salesOrderData', 'arrivalLocation', 'subArrivalLocation')
            ->findOrFail($request->delivery_order_id);

        $ArrivalTruckTypes = \App\Models\Master\ArrivalTruckType::where('status', 'active')->get();

        // Render view with the delivery order data
        $html = view('management.sales.second-weighbridge.getSecondWeighbridgeRelatedData', compact('DeliveryOrder', 'ArrivalTruckTypes'))->with('SecondWeighbridge', null)->render();

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
            ->where('am_approval_status', 'approved')
            ->whereHas('firstWeighbridge')
            ->whereDoesntHave('secondWeighbridge')
            ->with('customer', 'delivery_order_data.item')
            ->get();

        return response()->json([
            'success' => true,
            'delivery_orders' => $deliveryOrders
        ]);
    }
}
