<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Sales\FirstWeighbridge;
use App\Models\Sales\DeliveryOrder;
use App\Models\Sales\LoadingProgramItem;
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
        
        $FirstWeighbridges = FirstWeighbridge::with([
            'loadingProgramItem.deliveryOrders.customer',
            'loadingProgramItem.deliveryOrders.delivery_order_data.item',
            'loadingProgramItem.saleOrders.customer',
            'loadingProgramItem.saleOrders.sales_order_data.item',
            'loadingProgramItem'
        ])
            ->whereHas('loadingProgramItem.loadingProgram', function ($query) {
                $query->where('type', 'sale_order');
            })
            ->when($request->filled('search'), function ($q) use ($request) {
                $searchTerm = '%' . $request->search . '%';
                return $q->where(function ($sq) use ($searchTerm) {
                    $sq->whereHas('loadingProgramItem', function ($query) use ($searchTerm) {
                        $query->where('transaction_number', 'like', $searchTerm)
                              ->orWhere('truck_number', 'like', $searchTerm);
                    });
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
            'Tickets' => LoadingProgramItem::whereHas('loadingProgram', function ($query) {
                $query->where('type', 'sale_order');
            })
                ->whereDoesntHave('firstWeighbridge')
                ->with([
                    'deliveryOrders.customer',
                    'deliveryOrders.delivery_order_data.item',
                    'saleOrders.customer',
                    'saleOrders.sales_order_data.item'
                ])
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
            'loading_program_item_id' => 'required|exists:loading_program_items,id',
            'first_weight' => 'required|numeric',
            'truck_type_id' => 'required|exists:arrival_truck_types,id',
            'remark' => 'nullable|string',
            "weighbridge_amount" => 'required|numeric'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Check if the ticket already has a first weighbridge
        $existingFirstWeighbridge = FirstWeighbridge::where('loading_program_item_id', $request->loading_program_item_id)->first();
        if ($existingFirstWeighbridge) {
            return response()->json(['errors' => ['loading_program_item_id' => 'This ticket already has a first weighbridge.']], 422);
        }

        $loadingProgramItem = LoadingProgramItem::whereHas('loadingProgram', function ($query) {
            $query->where('type', 'sale_order');
        })->with('deliveryOrders')->findOrFail($request->loading_program_item_id);
        $deliveryOrders = $loadingProgramItem->deliveryOrders;

    $request['created_by'] = auth()->user()->id;
    $request['company_id'] = $request->company_id;

    // Fetch weighbridge amount from WeighbridgeAmount model based on truck type and company location
    $companyLocationId = null;
    
    if ($deliveryOrders->isNotEmpty()) {
        $companyLocationId = $deliveryOrders->first()->location_id;
        // Keep delivery_order_id for backward compatibility if column exists, using the first one
        $request["delivery_order_id"] = $deliveryOrders->first()->id;
    } else {
        // Get company location from loading program when no delivery order
        $companyLocationIds = $loadingProgramItem->loadingProgram->company_locations ?? [];
        $companyLocationId = is_array($companyLocationIds) ? ($companyLocationIds[0] ?? null) : $companyLocationIds;
        $request["delivery_order_id"] = null;
    }
    

        if ($companyLocationId) {
            $weighbridgeAmount = WeighbridgeAmount::where('truck_type_id', $request->truck_type_id)
                ->where('company_location_id', $companyLocationId)
                ->first();

            if (!$weighbridgeAmount) {
                return response()->json(['errors' => ['truck_type_id' => 'Weighbridge amount not found for selected truck type and arrival location.']], 422);
            }

            $request['weighbridge_amount'] = $weighbridgeAmount->weighbridge_amount;
        } else {
            return response()->json(['errors' => ['truck_type_id' => 'Company location not found to fetch weighbridge amount.']], 422);
        }

        $firstWeighbridge = FirstWeighbridge::create($request->all());

        return response()->json(['success' => 'First Weighbridge created successfully.', 'data' => $firstWeighbridge], 201);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $data['FirstWeighbridge'] = FirstWeighbridge::with([
            'loadingProgramItem.saleOrders.customer',
            'loadingProgramItem.saleOrders.sales_order_data.item',
            'loadingProgramItem.saleOrders.sales_order_data.brand',
            'loadingProgramItem.deliveryOrders.customer',
            'loadingProgramItem.deliveryOrders.delivery_order_data.item',
            'loadingProgramItem.deliveryOrders.delivery_order_data.salesOrderData',
            'loadingProgramItem.deliveryOrders.arrivalLocation',
            'loadingProgramItem.deliveryOrders.subArrivalLocation'
        ])
            ->whereHas('loadingProgramItem.loadingProgram', function ($query) {
                $query->where('type', 'sale_order');
            })
            ->findOrFail($id);

        $data['ArrivalTruckTypes'] = ArrivalTruckType::where('status', 'active')->get();
        $data['DeliveryOrders'] = $data['FirstWeighbridge']->loadingProgramItem->deliveryOrders;
        $data['SalesOrders'] = $data['FirstWeighbridge']->loadingProgramItem->saleOrders;

        return view('management.sales.first-weighbridge.edit', $data);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'loading_program_item_id' => 'required|exists:loading_program_items,id',
            'first_weight' => 'required|numeric',
            'truck_type_id' => 'required|exists:arrival_truck_types,id',
            'remark' => 'nullable|string',
            "weighbridge_amount" => 'required|numeric'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Check if the ticket already has a first weighbridge (excluding current one)
        $existingFirstWeighbridge = FirstWeighbridge::where('loading_program_item_id', $request->loading_program_item_id)
            ->where('id', '!=', $id)
            ->first();
        if ($existingFirstWeighbridge) {
            return response()->json(['errors' => ['loading_program_item_id' => 'This ticket already has a first weighbridge.']], 422);
        }

        $firstWeighbridge = FirstWeighbridge::findOrFail($id);
        $loadingProgramItem = LoadingProgramItem::whereHas('loadingProgram', function ($query) {
            $query->where('type', 'sale_order');
        })->with('deliveryOrders')->findOrFail($request->loading_program_item_id);
        $deliveryOrders = $loadingProgramItem->deliveryOrders;
        $request['company_id'] = $request->company_id;

    // Fetch weighbridge amount from WeighbridgeAmount model based on truck type and company location
    $companyLocationId = null;
    
    if ($deliveryOrders->isNotEmpty()) {
        $companyLocationId = $deliveryOrders->first()->location_id;
    } else {
        // Get company location from loading program when no delivery order
        $companyLocationIds = $loadingProgramItem->loadingProgram->company_locations ?? [];
        $companyLocationId = is_array($companyLocationIds) ? ($companyLocationIds[0] ?? null) : $companyLocationIds;
    }
    

        if ($companyLocationId) {
            $weighbridgeAmount = WeighbridgeAmount::where('truck_type_id', $request->truck_type_id)
                ->where('company_location_id', $companyLocationId)
                ->first();

            if (!$weighbridgeAmount) {
                return response()->json(['errors' => ['truck_type_id' => 'Weighbridge amount not found for selected truck type and arrival location.']], 422);
            }

            $request['weighbridge_amount'] = $weighbridgeAmount->weighbridge_amount;
        } else {
            return response()->json(['errors' => ['truck_type_id' => 'Company location not found to fetch weighbridge amount.']], 422);
        }

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
        $LoadingProgramItem = LoadingProgramItem::with([
            'saleOrders.customer',
            'saleOrders.sales_order_data.item',
            'saleOrders.sales_order_data.brand',
            'deliveryOrders.customer',
            'deliveryOrders.delivery_order_data.item',
            'deliveryOrders.delivery_order_data.salesOrderData',
            'deliveryOrders.arrivalLocation',
            'deliveryOrders.subArrivalLocation'
        ])
            ->whereHas('loadingProgram', function ($query) {
                $query->where('type', 'sale_order');
            })
            ->findOrFail($request->loading_program_item_id);

        $DeliveryOrders = $LoadingProgramItem->deliveryOrders;
        $SalesOrders = $LoadingProgramItem->saleOrders;
        $ArrivalTruckTypes = \App\Models\Master\ArrivalTruckType::where('status', 'active')->get();
            
        // Render view with the delivery order data (or sale order if no delivery order)
        $html = view('management.sales.first-weighbridge.getFirstWeighbridgeRelatedData', compact('DeliveryOrders', 'SalesOrders', 'ArrivalTruckTypes', 'LoadingProgramItem'))->with('FirstWeighbridge', null)->render();

        return response()->json(['success' => true, 'html' => $html]);
    }

    public function getWeighbridgeAmount(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'truck_type_id' => 'required|exists:arrival_truck_types,id',
            'loading_program_item_id' => 'required|exists:loading_program_items,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $loadingProgramItem = LoadingProgramItem::whereHas('loadingProgram', function ($query) {
            $query->where('type', 'sale_order');
        })->with('deliveryOrders')->findOrFail($request->loading_program_item_id);
        $deliveryOrders = $loadingProgramItem->deliveryOrders;

    // Get company location from delivery order or loading program
    $companyLocationId = null;
    
    if ($deliveryOrders->isNotEmpty()) {
        $companyLocationId = $deliveryOrders->first()->location_id;
    } else {
        // Get company location from loading program when no delivery order
        $companyLocationIds = $loadingProgramItem->loadingProgram->company_locations ?? [];
        $companyLocationId = is_array($companyLocationIds) ? ($companyLocationIds[0] ?? null) : $companyLocationIds;
    }
    

        if (!$companyLocationId) {
            return response()->json([
                'success' => false,
                'message' => 'Company location not found to fetch weighbridge amount.'
            ]);
        }

        $weighbridgeAmount = WeighbridgeAmount::where('truck_type_id', $request->truck_type_id)
            ->where('company_location_id', $companyLocationId)
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
