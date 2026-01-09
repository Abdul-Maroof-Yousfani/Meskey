<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Master\CompanyLocation;
use App\Models\Sales\LoadingProgram;
use App\Models\Sales\LoadingProgramItem;
use App\Models\Sales\LoadingSlip;
use App\Models\Sales\SalesOrder;
use App\Models\Sales\DeliveryOrder;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class LoadingProgramController extends Controller
{
    function __construct()
    {
        // $this->middleware('check.company:sales-loading-program', ['only' => ['index']]);
        // $this->middleware('check.company:sales-loading-program', ['only' => ['edit']]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('management.sales.loading-program.index');
    }

    /**
     * Get list of loading programs.
     */
    public function getList(Request $request)
    {
        $LoadingPrograms = LoadingProgram::with([
                'saleOrder.customer', 
                'saleOrder.sales_order_data.item',
                'deliveryOrder', 
                'createdBy',
                'loadingProgramItems.arrivalLocation',
                'loadingProgramItems.subArrivalLocation'
            ])
            ->when($request->filled('search'), function ($q) use ($request) {
                $searchTerm = '%' . $request->search . '%';
                return $q->where(function ($sq) use ($searchTerm) {
                    $sq->whereHas('saleOrder', function ($query) use ($searchTerm) {
                        $query->where('reference_no', 'like', $searchTerm);
                    })->orWhereHas('deliveryOrder', function ($query) use ($searchTerm) {
                        $query->where('reference_no', 'like', $searchTerm);
                    })->orWhereHas('loadingProgramItems', function ($query) use ($searchTerm) {
                        $query->where('transaction_number', 'like', $searchTerm)
                              ->orWhere('truck_number', 'like', $searchTerm);
                    })->orWhere('id', 'like', $searchTerm);
                });
            })
            ->latest()
            ->paginate($request->get('per_page', 25));

        return view('management.sales.loading-program.getList', compact('LoadingPrograms'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $SaleOrders = SalesOrder::where('am_approval_status', 'approved')
                                    // ->whereHas('delivery_orders', function ($query) {
                                    //     $query->where("am_approval_status", "approved")->whereDoesntHave('loadingProgram');
                                    // })
                                    // ->withCount(['delivery_orders' => function ($query) {
                                    //     $query->where("am_approval_status", "approved")->whereDoesntHave('loadingProgram');
                                    // }])
                                    ->get();

        $data = [
            'SaleOrders' => $SaleOrders,
            'DeliveryOrders' => collect(), // Empty collection initially
        ];

        return view('management.sales.loading-program.create', $data);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Debug: Log the incoming data
        \Log::info('Loading Program Store Data:', $request->all());

        
        // Check if sale order has pay_type_id = 11 (delivery order not required)
        $saleOrder = SalesOrder::find($request->sale_order_id);
        $isDeliveryOrderOptional = $saleOrder && $saleOrder->pay_type_id == 11;

        $validationRules = [
            'sale_order_id' => 'required|exists:sales_orders,id',
            'loading_program_items' => 'required|array|min:1',
            'loading_program_items.*.truck_number' => 'required|string',
            'loading_program_items.*.brand_id' => 'nullable|exists:brands,id',
            'loading_program_items.*.arrival_location_id' => 'required|exists:arrival_locations,id',
            'loading_program_items.*.sub_arrival_location_id' => 'required|exists:arrival_sub_locations,id',
            'remark' => 'nullable|string'
        ];

        // dd($saleOrder->pay_type_id);

        // Make delivery_order_id required only if pay_type_id is not 11
        if (!$isDeliveryOrderOptional) {
            $validationRules['delivery_order_id'] = 'required|exists:delivery_order,id';
        } else {
            $validationRules['delivery_order_id'] = 'nullable|exists:delivery_order,id';
        }

        $validator = Validator::make($request->all(), $validationRules);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Check if delivery order already has a loading program
        // $existingLoadingProgram = LoadingProgram::where('delivery_order_id', $request->delivery_order_id)->first();
        // if ($existingLoadingProgram) {
        //     return response()->json(['errors' => ['delivery_order_id' => 'Loading program already exists for this delivery order.']], 422);
        // }

        $request['created_by'] = auth()->user()->id;
        $request['company_id'] = $request->company_id;

        // Get location data from delivery order if available, otherwise from sale order
        $companyLocationIds = [];
        $arrivalLocationIds = [];
        $subArrivalLocationIds = [];

        if ($request->delivery_order_id) {
            $deliveryOrder = DeliveryOrder::findOrFail($request->delivery_order_id);
            $companyLocationIds = [$deliveryOrder->location_id];
            $arrivalLocationIds = [$deliveryOrder->arrival_location_id];
            $subArrivalLocationIds = [$deliveryOrder->sub_arrival_location_id];
        } else {
            // Get locations from Sale Order when delivery order is not selected
            $saleOrderWithLocations = SalesOrder::with('locations')->find($request->sale_order_id);
            if ($saleOrderWithLocations) {
                // Get company location from sale order's locations relationship
                $companyLocationId = $saleOrderWithLocations->locations->first()?->location_id;
                if ($companyLocationId) {
                    $companyLocationIds = [$companyLocationId];
                }
                // Get arrival and sub-arrival locations from sale order
                if ($saleOrderWithLocations->arrival_location_id) {
                    $arrivalLocationIds = [$saleOrderWithLocations->arrival_location_id];
                }
                if ($saleOrderWithLocations->arrival_sub_location_id) {
                    $subArrivalLocationIds = [$saleOrderWithLocations->arrival_sub_location_id];
                }
            }
        }

        DB::beginTransaction();
        try {
            $loadingProgram = LoadingProgram::create([
                'company_id' => $request->company_id,
                'sale_order_id' => $request->sale_order_id,
                'delivery_order_id' => $request->delivery_order_id ?: null,
                'company_locations' => $companyLocationIds,
                'arrival_locations' => $arrivalLocationIds,
                'sub_arrival_locations' => $subArrivalLocationIds,
                'remark' => $request->remark,
                'created_by' => $request->created_by
            ]);
    
            // Create loading program items
            if (isset($request->loading_program_items) && is_array($request->loading_program_items)) {
                foreach ($request->loading_program_items as $itemData) {
                    $itemData['loading_program_id'] = $loadingProgram->id;
                    $itemData['transaction_number'] = self::getNumber($request);
                    LoadingProgramItem::create($itemData);
                }
            }
            DB::commit();
        } catch(Exception $e) {
            DB::rollback();
            return response()->json($e->getMessage(), 500);
        } 

        return response()->json(['success' => 'Loading Program created successfully.', 'data' => $loadingProgram], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $data['LoadingProgram'] = LoadingProgram::with([
            'loadingProgramItems.arrivalLocation',
            'loadingProgramItems.subArrivalLocation',
            'saleOrder.customer',
            'saleOrder.sales_order_data.item',
            'saleOrder.locations',
            'deliveryOrder'
        ])->findOrFail($id);

        return view('management.sales.loading-program.show', $data);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $data['LoadingProgram'] = LoadingProgram::with([
            'loadingProgramItems.arrivalLocation',
            'loadingProgramItems.subArrivalLocation',
            'saleOrder.customer',
            'saleOrder.sales_order_data.item',
            'saleOrder.sales_order_data.brand',
            'saleOrder.locations',
            'deliveryOrder'
        ])->findOrFail($id);

        $data['SaleOrders'] = SalesOrder::where('am_approval_status', 'approved')->get();
        $data['DeliveryOrders'] = DeliveryOrder::where('so_id', $data['LoadingProgram']->sale_order_id)
            ->where('am_approval_status', 'approved')
            ->where(function($query) use ($data) {
                $query->whereDoesntHave('loadingProgram')
                      ->orWhere('id', $data['LoadingProgram']->delivery_order_id);
            })
            ->get();

        return view('management.sales.loading-program.edit', $data);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        // Check if sale order has pay_type_id = 11 (delivery order not required)
        $saleOrder = SalesOrder::find($request->sale_order_id);
        $isDeliveryOrderOptional = $saleOrder && $saleOrder->pay_type_id == 11;

        $validationRules = [
            'sale_order_id' => 'required|exists:sales_orders,id',
            'loading_program_items' => 'required|array|min:1',
            'loading_program_items.*.truck_number' => 'required|string',
            'loading_program_items.*.brand_id' => 'nullable|exists:brands,id',
            'loading_program_items.*.arrival_location_id' => 'required|exists:arrival_locations,id',
            'loading_program_items.*.sub_arrival_location_id' => 'required|exists:arrival_sub_locations,id',
            'remark' => 'nullable|string'
        ];

        // Make delivery_order_id required only if pay_type_id is not 11
        if (!$isDeliveryOrderOptional) {
            $validationRules['delivery_order_id'] = 'required|exists:delivery_order,id';
        } else {
            $validationRules['delivery_order_id'] = 'nullable|exists:delivery_order,id';
        }

        $validator = Validator::make($request->all(), $validationRules);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $loadingProgram = LoadingProgram::findOrFail($id);

        // Check if delivery order already has a loading program (excluding current one)
        if ($request->delivery_order_id) {
            $existingLoadingProgram = LoadingProgram::where('delivery_order_id', $request->delivery_order_id)
                ->where('id', '!=', $id)
                ->first();
            if ($existingLoadingProgram) {
                return response()->json(['errors' => ['delivery_order_id' => 'Loading program already exists for this delivery order.']], 422);
            }
        }

        // Get location data from delivery order if available, otherwise from sale order
        $companyLocationIds = [];
        $arrivalLocationIds = [];
        $subArrivalLocationIds = [];

        if ($request->delivery_order_id) {
            $deliveryOrder = DeliveryOrder::findOrFail($request->delivery_order_id);
            $companyLocationIds = [$deliveryOrder->location_id];
            $arrivalLocationIds = [$deliveryOrder->arrival_location_id];
            $subArrivalLocationIds = [$deliveryOrder->sub_arrival_location_id];
        } else {
            // Get locations from Sale Order when delivery order is not selected
            $saleOrderWithLocations = SalesOrder::with('locations')->find($request->sale_order_id);
            if ($saleOrderWithLocations) {
                // Get company location from sale order's locations relationship
                $companyLocationId = $saleOrderWithLocations->locations->first()?->location_id;
                if ($companyLocationId) {
                    $companyLocationIds = [$companyLocationId];
                }
                // Get arrival and sub-arrival locations from sale order
                if ($saleOrderWithLocations->arrival_location_id) {
                    $arrivalLocationIds = [$saleOrderWithLocations->arrival_location_id];
                }
                if ($saleOrderWithLocations->arrival_sub_location_id) {
                    $subArrivalLocationIds = [$saleOrderWithLocations->arrival_sub_location_id];
                }
            }
        }

        DB::beginTransaction();
        try {
            $loadingProgram->update([
                'sale_order_id' => $request->sale_order_id,
                'delivery_order_id' => $request->delivery_order_id ?: null,
                'company_locations' => $companyLocationIds,
                'arrival_locations' => $arrivalLocationIds,
                'sub_arrival_locations' => $subArrivalLocationIds,
                'remark' => $request->remark
            ]);
    
            // Delete existing items and create new ones
            $loadingProgram->loadingProgramItems()->delete();
    
            if (isset($request->loading_program_items) && is_array($request->loading_program_items)) {
                foreach ($request->loading_program_items as $itemData) {
                    $itemData['loading_program_id'] = $loadingProgram->id;
                    if(!$itemData['transaction_number']) {
                        $itemData["transaction_number"] = self::getNumber($request);
                    }
                    LoadingProgramItem::create($itemData);
                }
            }
            DB::commit();
        } catch(Exception $e) {
            DB::rollBack();
            return response()->json($e->getMessage(), 500);
        }

        return response()->json(['success' => 'Loading Program updated successfully.', 'data' => $loadingProgram], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $loadingProgram = LoadingProgram::findOrFail($id);
        $loadingProgram->delete();
        return response()->json(['success' => 'Loading Program deleted successfully.'], 200);
    }

    public function getSaleOrderRelatedData(Request $request)
    {
        $SalesOrder = SalesOrder::with('customer', 'sales_order_data.item', 'sales_order_data.brand', 'locations')->findOrFail($request->sale_order_id);
        
        $DeliveryOrders = DeliveryOrder::where('so_id', $request->sale_order_id)
            // ->whereDoesntHave('loadingProgram')
            // ->with("loadingPrograms")
            ->withSum("saleSecondWeighbridge", "net_weight")
            ->withSum("delivery_order_data", "qty")
            ->where('am_approval_status', 'approved')
            ->get()
            ->reject(function($delivery_order) {
                return $delivery_order->sale_second_weighbridge_sum_net_weight < $delivery_order->sale_second_weighbridge_sum_net_weight;
            });

      
        // Summed up value is in delivery_order_data_sum_qty

        // dd($DeliveryOrders);
        // Render view with the sales order data
        $html = view('management.sales.loading-program.getSaleOrderRelatedData', compact('SalesOrder', 'DeliveryOrders'))->render();

        // Check if delivery order is optional (pay_type_id = 11)
        $isDeliveryOrderOptional = $SalesOrder->pay_type_id == 11;

        // Get sale order data for packing, brand, factory, gala, and company location
        $firstSoData = $SalesOrder->sales_order_data->first();
        
        // Get company location from sale order's locations relationship
        $companyLocationId = $SalesOrder->locations->first()?->location_id;
        
        $saleOrderData = [
            'packing' => $firstSoData->bag_size ?? null,
            'brand_id' => $firstSoData->brand_id ?? null,
            'brand_name' => $firstSoData->brand->name ?? null,
            'arrival_location_id' => $SalesOrder->arrival_location_id,
            'sub_arrival_location_id' => $SalesOrder->arrival_sub_location_id,
            'company_location_id' => $companyLocationId,
        ];

        return response()->json([
            'success' => true, 
            'html' => $html,
            'is_delivery_order_optional' => $isDeliveryOrderOptional,
            'pay_type_id' => $SalesOrder->pay_type_id,
            'sale_order_data' => $saleOrderData
        ]);
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
            // ->whereDoesntHave(relation: 'loadingProgram')
            ->where('am_approval_status', 'approved')
            ->with('customer', 'delivery_order_data.item', 'delivery_order_data.brand')
            ->select('id', 'reference_no', 'customer_id', 'so_id', 'location_id', 'arrival_location_id', 'sub_arrival_location_id', 'am_approval_status')
            ->get();

        

            

        return response()->json([
            'success' => true,
            'delivery_orders' => $deliveryOrders
        ]);
    }

    public function getDeliveryOrdersBySaleOrderEdit(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'sale_order_id' => 'required|exists:sales_orders,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $deliveryOrders = DeliveryOrder::where('so_id', $request->sale_order_id)
            ->where('am_approval_status', 'approved')
            ->with('customer', 'delivery_order_data.item', 'delivery_order_data.brand')
            ->select('id', 'reference_no', 'customer_id', 'so_id', 'location_id', 'arrival_location_id', 'sub_arrival_location_id', 'am_approval_status')
            ->get();

        return response()->json([
            'success' => true,
            'delivery_orders' => $deliveryOrders
        ]);
    }

    public function getNumber(Request $request, $locationId = null, $contractDate = null)
    {

        $date = Carbon::parse($contractDate ?? $request->contract_date)->format('Y-m-d');

        $prefix = Carbon::parse($contractDate ?? $request->contract_date)->format('Y-m-d');

        $latestContract = LoadingProgramItem::select("id", "transaction_number")->where('transaction_number', 'like', "$prefix-%")
            ->get();
        $latestContract = !$latestContract->count() ? null : $latestContract[$latestContract->count() - 1];
        
        $datePart = Carbon::parse($date)->format('Y-m-d');

        if ($latestContract) {
            $parts = explode('-', $latestContract->transaction_number);
            $lastNumber = (int) end($parts);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        $ticket_no = $datePart.'-'.str_pad($newNumber, 3, '0', STR_PAD_LEFT);

        // if (! $locationId && ! $contractDate) {
        //     return response()->json([
        //         'success' => true,
        //         'ticket_no' => $purchase_request_no,
        //     ]);
        // }

        return $ticket_no;
    }
}
