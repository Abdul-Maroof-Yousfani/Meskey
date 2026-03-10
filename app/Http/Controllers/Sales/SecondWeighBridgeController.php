<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Sales\SecondWeighbridge;
use App\Models\Sales\SecondWeighbridgeItem;
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
        // Get loading slip
        $loadingSlip = LoadingSlip::with('loadingProgramItem.firstWeighbridge', 'loadingProgramItem.deliveryOrders.delivery_order_data')
            ->find($request->loading_slip_id);

        if (!$loadingSlip) {
            return response()->json(['errors' => ['loading_slip_id' => 'Loading slip not found.']], 422);
        }

        // Build validation rules
        $validationRules = [
            'loading_slip_id' => 'required|exists:loading_slips,id',
            'second_weight' => 'required|numeric',
            'remark' => 'nullable|string'
        ];

        // If loading slip doesn't have delivery_order_id, it might be required in the form
        if (!$loadingSlip->delivery_order_id) {
            $validationRules['delivery_order_id'] = 'required|exists:delivery_order,id';
        }

        $validator = Validator::make($request->all(), $validationRules);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $firstWeighbridge = $loadingSlip->loadingProgramItem->firstWeighbridge;
        if (!$firstWeighbridge) {
            return response()->json(['errors' => ['loading_slip_id' => 'First weighbridge not found for this loading slip.']], 422);
        }

        $first_weight = $firstWeighbridge->first_weight;
        $second_weight = $request->second_weight;
        $net_weight = $second_weight - $first_weight;

        if($second_weight < $first_weight) {
            return response()->json("Second Weight can not be less than First Weight", 422);
        }

        // Get aggregate balance for all DOs on the ticket
        $balance = get_second_weighbridge_balance($loadingSlip);
        
        if($net_weight > $balance) {
            return response()->json("Your total remaining net weight balance for all associated DOs on this ticket is: " . number_format($balance, 2), 422);
        }

        // If loading slip didn't have delivery_order_id, update it now
        if (!$loadingSlip->delivery_order_id && $request->delivery_order_id) {
            $loadingSlip->update(['delivery_order_id' => $request->delivery_order_id]);
            if ($loadingSlip->loadingProgramItem->salesQc) {
                $loadingSlip->loadingProgramItem->salesQc->update(['delivery_order_id' => $request->delivery_order_id]);
            }
            if ($loadingSlip->loadingProgramItem->firstWeighbridge) {
                $loadingSlip->loadingProgramItem->firstWeighbridge->update(['delivery_order_id' => $request->delivery_order_id]);
            }
        }

        $requestData = $request->all();
        $requestData['created_by'] = auth()->user()->id;
        $requestData['first_weight'] = $first_weight;
        $requestData['net_weight'] = $net_weight;
        $requestData['balance_kg'] = $balance - $net_weight;
        $requestData['delivery_order_id'] = $loadingSlip->delivery_order_id ?: $request->delivery_order_id;

        $secondWeighbridge = SecondWeighbridge::create($requestData);

        // FIFO Deduction logic
        $remainingWeight = $net_weight;
        $item = $loadingSlip->loadingProgramItem;
        $deliveryOrders = collect();
        if ($item && $item->deliveryOrders->isNotEmpty()) {
            $deliveryOrders = $item->deliveryOrders->sortBy('id');
        } elseif ($loadingSlip->deliveryOrder) {
            $deliveryOrders->push($loadingSlip->deliveryOrder);
        }

        foreach ($deliveryOrders as $do) {
            if ($remainingWeight <= 0) break;
            
            $doBalance = get_second_weighbridge_balance_by_delivery_order($do->id);
            if ($doBalance > 0) {
                $deduct = min($doBalance, $remainingWeight);
                SecondWeighbridgeItem::create([
                    'second_weighbridge_id' => $secondWeighbridge->id,
                    'delivery_order_id' => $do->id,
                    'net_weight' => $deduct
                ]);
                $remainingWeight -= $deduct;
            }
        }

        return response()->json(['success' => 'Second Weighbridge created successfully.', 'data' => $secondWeighbridge], 201);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $authUser = auth()->user();
        $data['SecondWeighbridge'] = SecondWeighbridge::with([
            'loadingSlip.loadingProgramItem.deliveryOrders.customer',
            'loadingSlip.loadingProgramItem.deliveryOrders.delivery_order_data.item',
            'loadingSlip.loadingProgramItem.deliveryOrders.delivery_order_data.salesOrderData',
            'loadingSlip.loadingProgramItem.deliveryOrders.arrivalLocation',
            'loadingSlip.loadingProgramItem.deliveryOrders.subArrivalLocation',
            'loadingSlip.loadingProgramItem.saleOrders.customer',
            'loadingSlip.loadingProgramItem.saleOrders.sales_order_data.item',
        ])->findOrFail($id);
        $data['LoadingSlips'] = LoadingSlip::where(function($q) use ($data) {
                $q->whereDoesntHave('secondWeighbridge')
                    ->whereHas('loadingProgramItem.dispatchQcs', function($query) {
                        $query->where('status', 'accept');
                    });
            })
            ->orWhere('id', $data['SecondWeighbridge']->loading_slip_id)
            ->with([
                'loadingProgramItem.deliveryOrders.customer',
                'loadingProgramItem.deliveryOrders.delivery_order_data.item',
                'loadingProgramItem.deliveryOrders.delivery_order_data.salesOrderData',
                'loadingProgramItem.deliveryOrders.arrivalLocation',
                'loadingProgramItem.deliveryOrders.subArrivalLocation',
                'loadingProgramItem.saleOrders.customer',
                'loadingProgramItem.saleOrders.sales_order_data.item',
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
        $secondWeighbridge = SecondWeighbridge::findOrFail($id);

        // Get loading slip
        $loadingSlip = LoadingSlip::with('loadingProgramItem.firstWeighbridge')
            ->find($request->loading_slip_id);

        if (!$loadingSlip) {
            return response()->json(['errors' => ['loading_slip_id' => 'Loading slip not found.']], 422);
        }

        // Build validation rules
        $validationRules = [
            'loading_slip_id' => 'required|exists:loading_slips,id',
            'second_weight' => 'required|numeric',
            'remark' => 'nullable|string'
        ];

        if (!$loadingSlip->delivery_order_id) {
            $validationRules['delivery_order_id'] = 'required|exists:delivery_order,id';
        }

        $validator = Validator::make($request->all(), $validationRules);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $firstWeighbridge = $loadingSlip->loadingProgramItem->firstWeighbridge;
        if (!$firstWeighbridge) {
            return response()->json(['errors' => ['loading_slip_id' => 'First weighbridge not found for this loading slip.']], 422);
        }

        $first_weight = $firstWeighbridge->first_weight;
        $second_weight = $request->second_weight;
        $net_weight = $second_weight - $first_weight;

        if($second_weight < $first_weight) {
            return response()->json("Second Weight can not be less than First Weight", 422);
        }

        // Calculate available balance (add current record's weight back to get true remaining)
        $current_balance = get_second_weighbridge_balance($loadingSlip);
        $available_balance = $current_balance + $secondWeighbridge->net_weight;

        if($net_weight > $available_balance) {
            return response()->json("Your total remaining net weight balance for all associated DOs on this ticket is: " . number_format($available_balance, 2), 422);
        }

        // If loading slip didn't have delivery_order_id, update it now
        if (!$loadingSlip->delivery_order_id && $request->delivery_order_id) {
            $loadingSlip->update(['delivery_order_id' => $request->delivery_order_id]);
        }

        $updateData = $request->all();
        $updateData['first_weight'] = $first_weight;
        $updateData['net_weight'] = $net_weight;
        $updateData['balance_kg'] = $available_balance - $net_weight;

        $secondWeighbridge->update($updateData);

        // Update FIFO Deductions
        $secondWeighbridge->items()->delete();
        $remainingWeight = $net_weight;
        $item = $loadingSlip->loadingProgramItem;
        $deliveryOrders = collect();
        if ($item && $item->deliveryOrders->isNotEmpty()) {
            $deliveryOrders = $item->deliveryOrders->sortBy('id');
        } elseif ($loadingSlip->deliveryOrder) {
            $deliveryOrders->push($loadingSlip->deliveryOrder);
        }

        foreach ($deliveryOrders as $do) {
            if ($remainingWeight <= 0) break;
            
            $doBalance = get_second_weighbridge_balance_by_delivery_order($do->id);
            if ($doBalance > 0) {
                $deduct = min($doBalance, $remainingWeight);
                SecondWeighbridgeItem::create([
                    'second_weighbridge_id' => $secondWeighbridge->id,
                    'delivery_order_id' => $do->id,
                    'net_weight' => $deduct
                ]);
                $remainingWeight -= $deduct;
            }
        }

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
            'loadingProgramItem.loadingProgram.deliveryOrder.delivery_order_data.item',
            'loadingProgramItem.loadingProgram.deliveryOrder.delivery_order_data.salesOrderData',
            'loadingProgramItem.loadingProgram.deliveryOrder.arrivalLocation',
            'loadingProgramItem.loadingProgram.deliveryOrder.subArrivalLocation',
            'loadingProgramItem.loadingProgram.saleOrder.customer',
            'loadingProgramItem.loadingProgram.saleOrder.sales_order_data.item',
            'loadingProgramItem.deliveryOrders.customer',
            'loadingProgramItem.deliveryOrders.delivery_order_data.item',
            'loadingProgramItem.deliveryOrders.delivery_order_data.salesOrderData',
            'loadingProgramItem.saleOrders.customer',
            'loadingProgramItem.saleOrders.sales_order_data.item',
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
