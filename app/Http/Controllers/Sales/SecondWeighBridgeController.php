<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Sales\SecondWeighbridge;
use App\Models\Sales\LoadingSlip;
use App\Models\Sales\FirstWeighbridge;
use App\Models\Sales\SalesOrder;
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
        $SecondWeighbridges = SecondWeighbridge::with([
            'loadingSlip.loadingProgramItem.loadingProgram.deliveryOrder.customer',
            'loadingSlip.loadingProgramItem.loadingProgram.deliveryOrder.delivery_order_data.item',
            'truckType'
        ])
            ->when($request->filled('search'), function ($q) use ($request) {
                $searchTerm = '%' . $request->search . '%';
                return $q->where(function ($sq) use ($searchTerm) {
                    $sq->whereHas('loadingSlip.loadingProgramItem', function ($query) use ($searchTerm) {
                        $query->where('transaction_number', 'like', $searchTerm)
                              ->orWhere('truck_number', 'like', $searchTerm);
                    });
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
        // Get loading slips that don't have a second weighbridge yet
        $LoadingSlips = LoadingSlip::whereDoesntHave('secondWeighbridge')
            ->with([
                'loadingProgramItem.loadingProgram.deliveryOrder.customer',
                'loadingProgramItem.loadingProgram.deliveryOrder.delivery_order_data.item'
            ])
            ->get();

        $data = [
            'LoadingSlips' => $LoadingSlips
        ];

        return view('management.sales.second-weighbridge.create', $data);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'loading_slip_id' => 'required|exists:loading_slips,id',
            'second_weight' => 'required|numeric',
            'remark' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Get loading slip and its related first weighbridge data
        $loadingSlip = LoadingSlip::with('loadingProgramItem.firstWeighbridge')->find($request->loading_slip_id);
        $firstWeighbridge = $loadingSlip->loadingProgramItem->firstWeighbridge;

        if (!$firstWeighbridge) {
            return response()->json(['errors' => ['loading_slip_id' => 'First weighbridge not found for this loading slip.']], 422);
        }

        $request['created_by'] = auth()->user()->id;
        $request['company_id'] = $request->company_id;
        $request['first_weight'] = $firstWeighbridge->first_weight;
        $request['net_weight'] = $request->second_weight - $firstWeighbridge->first_weight;

        $secondWeighbridge = SecondWeighbridge::create($request->all());

        return response()->json(['success' => 'Second Weighbridge created successfully.', 'data' => $secondWeighbridge], 201);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $authUser = auth()->user();
        $data['SecondWeighbridge'] = SecondWeighbridge::with('loadingSlip')->findOrFail($id);
        $data['LoadingSlips'] = LoadingSlip::whereDoesntHave('secondWeighbridge')
            ->orWhere('id', $data['SecondWeighbridge']->loading_slip_id)
            ->with([
                'loadingProgramItem.loadingProgram.deliveryOrder.customer',
                'loadingProgramItem.loadingProgram.deliveryOrder.delivery_order_data.item'
            ])
            ->get();

        return view('management.sales.second-weighbridge.edit', $data);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'loading_slip_id' => 'required|exists:loading_slips,id',
            'second_weight' => 'required|numeric',
            'remark' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $secondWeighbridge = SecondWeighbridge::findOrFail($id);

        // Get loading slip and its related first weighbridge data
        $loadingSlip = LoadingSlip::with('loadingProgramItem.firstWeighbridge')->find($request->loading_slip_id);
        $firstWeighbridge = $loadingSlip->loadingProgramItem->firstWeighbridge;

        if (!$firstWeighbridge) {
            return response()->json(['errors' => ['loading_slip_id' => 'First weighbridge not found for this loading slip.']], 422);
        }

        $request['company_id'] = $request->company_id;
        $request['first_weight'] = $firstWeighbridge->first_weight;
        $request['net_weight'] = $request->second_weight - $firstWeighbridge->first_weight;

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
        $LoadingSlip = LoadingSlip::with([
            'loadingProgramItem.loadingProgram.deliveryOrder.customer',
            'loadingProgramItem.loadingProgram.deliveryOrder.salesOrder',
            'loadingProgramItem.loadingProgram.deliveryOrder.delivery_order_data.item',
            'loadingProgramItem.loadingProgram.deliveryOrder.delivery_order_data.salesOrderData',
            'loadingProgramItem.loadingProgram.deliveryOrder.arrivalLocation',
            'loadingProgramItem.loadingProgram.deliveryOrder.subArrivalLocation'
        ])->findOrFail($request->loading_slip_id);

        // Render view with the loading slip data
        $html = view('management.sales.second-weighbridge.getSecondWeighbridgeRelatedData', compact('LoadingSlip'))->with('SecondWeighbridge', null)->render();

        return response()->json(['success' => true, 'html' => $html]);
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
