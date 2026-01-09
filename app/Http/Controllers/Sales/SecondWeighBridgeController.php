<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Sales\SecondWeighbridge;
use App\Models\Sales\LoadingSlip;
use App\Models\Sales\FirstWeighbridge;
use App\Models\Sales\SalesOrder;
use App\Models\Sales\DeliveryOrder;
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
        // Get loading slips that have accepted dispatch QC but don't have a second weighbridge yet
        $LoadingSlips = LoadingSlip::whereDoesntHave('secondWeighbridge')
            ->whereHas('loadingProgramItem.dispatchQcs', function($query) {
                $query->where('status', 'accept');
            })
            ->with([
                'loadingProgramItem.loadingProgram.deliveryOrder.customer',
                'loadingProgramItem.loadingProgram.deliveryOrder.delivery_order_data.item',
                'loadingProgramItem.loadingProgram.saleOrder'
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
        // Get loading slip to check if it has delivery_order_id
        $loadingSlip = LoadingSlip::with('loadingProgramItem.firstWeighbridge', 'loadingProgramItem.loadingProgram.saleOrder')
            ->find($request->loading_slip_id);

        // Build validation rules - delivery_order_id required only if loading slip doesn't have one
        $validationRules = [
            'loading_slip_id' => 'required|exists:loading_slips,id',
            'second_weight' => 'required|numeric',
            'remark' => 'nullable|string'
        ];

        // If loading slip doesn't have delivery_order_id, it's required in the form
        if (!$loadingSlip || !$loadingSlip->delivery_order_id) {
            $validationRules['delivery_order_id'] = 'required|exists:delivery_order,id';
        }

        $validator = Validator::make($request->all(), $validationRules);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $firstWeighbridge = $loadingSlip->loadingProgramItem->firstWeighbridge;
        $delivery_order = $loadingSlip->deliveryOrder;
        $first_weight = $firstWeighbridge->first_weight;
        $second_weight = $request->second_weight;
        $net_weight = $second_weight - $first_weight;        
        // $loaded_weight = $loadingSlip->kilogram;
        $balance = 0;

        if($delivery_order) {
            $balance = get_second_weighbridge_balance($loadingSlip);
        } else {
            $balance = get_second_weighbridge_balance_by_delivery_order($request->delivery_order_id);
        }
        
        $remaining_quantities = $balance - $net_weight;
        

        // if($net_weight > $loaded_weight) {
        //     return response()->json("Net weight can not be greater than loaded weight", 422);
        // }

        if($second_weight < $first_weight) {
            return response()->json("Second Weight can not be less than First Weight", 422);
        }


        if($remaining_quantities < 0) {
            return response()->json("Your total net weight balance is: " . $balance, 422);
        }


        if (!$firstWeighbridge) {
            return response()->json(['errors' => ['loading_slip_id' => 'First weighbridge not found for this loading slip.']], 422);
        }

        // If loading slip didn't have delivery_order_id, update it now
        if (!$loadingSlip->delivery_order_id && $request->delivery_order_id) {
            $loadingSlip->update(['delivery_order_id' => $request->delivery_order_id]);
            // update sales qc, frst weighbridge, of that lloading slip, you need to update delivery_order_id
            $loadingSlip->loadingProgramItem->salesQc->update(['delivery_order_id' => $request->delivery_order_id]);
            $loadingSlip->loadingProgramItem->firstWeighbridge->update(['delivery_order_id' => $request->delivery_order_id]);
        }
        

        $request['created_by'] = auth()->user()->id;
        $request['company_id'] = $request->company_id;
        $request['first_weight'] = $firstWeighbridge->first_weight;
        $request["delivery_order_id"] = $delivery_order ? $delivery_order->id : $request->delivery_order_id;
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
        $data['SecondWeighbridge'] = SecondWeighbridge::with([
            'loadingSlip.loadingProgramItem.loadingProgram.deliveryOrder.arrivalLocation',
            'loadingSlip.loadingProgramItem.loadingProgram.deliveryOrder.subArrivalLocation',
            'loadingSlip.loadingProgramItem.loadingProgram.saleOrder'
        ])->findOrFail($id);
        $data['LoadingSlips'] = LoadingSlip::where(function($q) use ($data) {
                $q->whereDoesntHave('secondWeighbridge')
                    ->whereHas('loadingProgramItem.dispatchQcs', function($query) {
                        $query->where('status', 'accept');
                    });
            })
            ->orWhere('id', $data['SecondWeighbridge']->loading_slip_id)
            ->with([
                'loadingProgramItem.loadingProgram.deliveryOrder.customer',
                'loadingProgramItem.loadingProgram.deliveryOrder.delivery_order_data.item',
                'loadingProgramItem.loadingProgram.deliveryOrder.arrivalLocation',
                'loadingProgramItem.loadingProgram.deliveryOrder.subArrivalLocation',
                'loadingProgramItem.loadingProgram.saleOrder'
            ])
            ->get();

        // Check if loading slip has delivery_order_id
        $loadingSlip = $data['SecondWeighbridge']->loadingSlip;
        $data['needsDeliveryOrder'] = !$loadingSlip->delivery_order_id;
        $data['deliveryOrders'] = collect();

        // If loading slip doesn't have delivery_order_id, get available delivery orders
        if ($data['needsDeliveryOrder']) {
            $saleOrderId = $loadingSlip->loadingProgramItem->loadingProgram->sale_order_id ?? null;
            if ($saleOrderId) {
                $data['deliveryOrders'] = DeliveryOrder::where('so_id', $saleOrderId)
                    ->where('am_approval_status', 'approved')
                    ->with('customer')
                    ->get();
            }
        }

        return view('management.sales.second-weighbridge.edit', $data);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        // Get loading slip to check if it has delivery_order_id
        $loadingSlip = LoadingSlip::with('loadingProgramItem.firstWeighbridge')
            ->find($request->loading_slip_id);

        // Build validation rules - delivery_order_id required only if loading slip doesn't have one
        $validationRules = [
            'loading_slip_id' => 'required|exists:loading_slips,id',
            'second_weight' => 'required|numeric',
            'remark' => 'nullable|string'
        ];

        // If loading slip doesn't have delivery_order_id, it's required in the form
        if (!$loadingSlip || !$loadingSlip->delivery_order_id) {
            $validationRules['delivery_order_id'] = 'required|exists:delivery_order,id';
        }

        $validator = Validator::make($request->all(), $validationRules);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $secondWeighbridge = SecondWeighbridge::findOrFail($id);

        $firstWeighbridge = $loadingSlip->loadingProgramItem->firstWeighbridge;

        if (!$firstWeighbridge) {
            return response()->json(['errors' => ['loading_slip_id' => 'First weighbridge not found for this loading slip.']], 422);
        }

        // If loading slip didn't have delivery_order_id, update it now
        if (!$loadingSlip->delivery_order_id && $request->delivery_order_id) {
            $loadingSlip->update(['delivery_order_id' => $request->delivery_order_id]);
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
            'loadingProgramItem.loadingProgram.deliveryOrder.subArrivalLocation',
            'loadingProgramItem.loadingProgram.saleOrder.customer',
            'loadingProgramItem.loadingProgram.saleOrder.sales_order_data.item',
            'loadingProgramItem.firstWeighbridge'
        ])->findOrFail($request->loading_slip_id);

        // Check if loading slip has delivery_order_id
        $needsDeliveryOrder = !$LoadingSlip->delivery_order_id;
     
        $deliveryOrders = collect();

        // If loading slip doesn't have delivery_order_id, get available delivery orders for the sale order
        if ($needsDeliveryOrder) {
            $saleOrderId = $LoadingSlip->loadingProgramItem->loadingProgram->sale_order_id;
            if ($saleOrderId) {
                $deliveryOrders = DeliveryOrder::where('so_id', $saleOrderId)
                    ->where('am_approval_status', 'approved')
                    ->with('customer')
                    ->get();
            }
        }

        // Render view with the loading slip data
        $html = view('management.sales.second-weighbridge.getSecondWeighbridgeRelatedData', compact('LoadingSlip', 'needsDeliveryOrder', 'deliveryOrders'))->with('SecondWeighbridge', null)->render();

        return response()->json(['success' => true, 'html' => $html, 'needsDeliveryOrder' => $needsDeliveryOrder]);
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
