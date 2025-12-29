<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Sales\SecondWeighbridge;
use App\Models\Sales\DeliveryOrder;
use App\Models\Sales\FirstWeighbridge;
use App\Models\Master\ArrivalTruckType;
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
            'DeliveryOrders' => DeliveryOrder::with('customer', 'delivery_order_data.item')
                ->where('am_approval_status', 'approved')
                ->whereHas('firstWeighbridge')
                ->whereDoesntHave('secondWeighbridge')
                ->get()
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
        $request['weighbridge_amount'] = ArrivalTruckType::find($request->truck_type_id)->weighbridge_amount;

        $secondWeighbridge = SecondWeighbridge::create($request->all());

        return response()->json(['success' => 'Second Weighbridge created successfully.', 'data' => $secondWeighbridge], 201);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $data['SecondWeighbridge'] = SecondWeighbridge::where('company_id', $authUser->company_id)->findOrFail($id);
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
        $request['weighbridge_amount'] = ArrivalTruckType::find($request->truck_type_id)->weighbridge_amount;

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
}
