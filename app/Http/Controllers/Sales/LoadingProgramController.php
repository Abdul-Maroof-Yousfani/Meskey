<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Master\ArrivalLocation;
use App\Models\Master\ArrivalSubLocation;
use App\Models\Master\CompanyLocation;
use App\Models\Sales\DeliveryOrderData;
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
                'loadingProgramItems.subArrivalLocation',
                'loadingProgramItems.firstWeighbridge'
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
                                    ->get()
                                    ->filter(function ($sale_order) {
                                        if ($sale_order->pay_type_id == 11) {
                                            return true;
                                        }
                                        
                                        foreach ($sale_order->delivery_orders as $delivery_order) {
                                            $balance = get_second_weighbridge_balance_by_delivery_order($delivery_order->id); 
                                            if ($balance > 0) {
                                                return true;
                                            }
                                        }

                                        return false;
                                    });

        // $SaleOrders = $SaleOrders->reject(function($sale_order) {
        //     if($sale_order->pay_type_id == 11) return false;
            
        //     $delivery_orders = $sale_order->delivery_orders;

        //     foreach($delivery_orders as $delivery_order) {
        //         $balance = get_second_weighbridge_balance_by_delivery_order($delivery_order->id);
        //         if($balance > 0) return false;
        //     }

        //     return true;
        // });
        
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
            $validationRules['delivery_order_id'] = 'required|min:1';
            $validationRules['delivery_order_id.*'] = 'exists:delivery_order,id';
            $validationRules['loading_program_items.*.delivery_order_id'] = 'required|min:1|exists:delivery_order,id';
        } else {
            $validationRules['delivery_order_id'] = 'nullable';
            $validationRules['delivery_order_id.*'] = 'exists:delivery_order,id';
            $validationRules['loading_program_items.*.delivery_order_id'] = 'nullable|exists:delivery_order,id';
        }

        $validator = Validator::make($request->all(), $validationRules);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }


        $request['created_by'] = auth()->user()->id;
        $request['company_id'] = $request->company_id;

        // Get location data from delivery order if available, otherwise from sale order
        $companyLocationIds = [];
        $arrivalLocationIds = [];
        $subArrivalLocationIds = [];

        if ($request->delivery_order_id && is_array($request->delivery_order_id) && count($request->delivery_order_id) > 0) {
            $deliveryOrders = DeliveryOrder::whereIn('id', $request->delivery_order_id)->get();
            $companyLocationIds = $deliveryOrders->pluck('location_id')->unique()->toArray();
            $arrivalLocationIds = $deliveryOrders->pluck('arrival_location_id')->filter()->unique()->toArray();
            $subArrivalLocationIds = $deliveryOrders->pluck('sub_arrival_location_id')->filter()->unique()->toArray();
        } else {
            $saleOrderWithLocations = SalesOrder::with('locations')->find($request->sale_order_id);
            if ($saleOrderWithLocations) {
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
                'delivery_order_id' => is_array($request->delivery_order_id) ? ($request->delivery_order_id[0] ?? null) : ($request->delivery_order_id ?: null),
                'company_locations' => $companyLocationIds,
                'arrival_locations' => $arrivalLocationIds,
                'sub_arrival_locations' => $subArrivalLocationIds,
                'remark' => $request->remark,
                'created_by' => $request->created_by
            ]);
    
            if (isset($request->loading_program_items) && is_array($request->loading_program_items)) {
                foreach ($request->loading_program_items as $index => $itemData) {
                    $itemData['loading_program_id'] = $loadingProgram->id;
                    $itemData['transaction_number'] = self::getNumber($request);
                    $itemData["delivery_order_id"] = $request->loading_program_items[$index]['delivery_order_id'];
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

        $data["LoadingProgram"]->loadingProgramItems->filter(function($loadingProgramItem) {
            // $loadingProgramItem->transaction_number = 
        });
 

        $SaleOrders = SalesOrder::where('am_approval_status', 'approved')
        ->get()
        ->filter(function ($sale_order) {
            if ($sale_order->pay_type_id == 11) {
                return true;
            }
            
            foreach ($sale_order->delivery_orders as $delivery_order) {
                $balance = get_second_weighbridge_balance_by_delivery_order($delivery_order->id); 
                if ($balance > 0) { 
                    return true;
                }
            }
            return false;
        });

        $currentSaleOrder = $data['LoadingProgram']->saleOrder;
        $currentDeliveryOrder = $data['LoadingProgram']->deliveryOrder;
        
        $companyLocations = [];
        $factoryLocations = [];
        $sectionLocations = [];

              

        if($currentSaleOrder->pay_type_id == 11 && !$data['LoadingProgram']->delivery_order_id) {

            // For company locations

            foreach($currentSaleOrder->locations as $location) {
                $companyLocations[] = [
                    "id" => $location->location_id,
                    "text" => getLocation($location->location_id)?->name ?? "N/A"
                ];
            }

            foreach($currentSaleOrder->factories as $factory) {
                // dd($factory);
                $factoryLocations[] = [
                    "id" => $factory->arrival_location_id,
                    "text" => getArrivalLocations($factory->arrival_location_id)?->name ?? "N/A"
                ];
            }

            foreach($currentSaleOrder->sections as $section) {
                $sectionLocations[] = [
                    "id" => $section->arrival_sub_location_id,
                    "text" => subArrivalLocationId($section->arrival_sub_location_id)?->name ?? "N/A"
                ];

            }

        } else {
            $companyLocationIds = explode(",", $currentDeliveryOrder->location_id);
            $compLocations = CompanyLocation::whereIn("id", $companyLocationIds)->get();

            foreach($compLocations as $location) {
                $companyLocations[] = [
                    "id" => $location->location_id,
                    "text" => $location->name
                ];
            }

            $arrivalLocationIds = explode(",", $currentDeliveryOrder->arrival_location_id);
            $arrivalLocations = ArrivalLocation::whereIn("id", $arrivalLocationIds)->get();
            foreach($arrivalLocations as $factory) {
                $factoryLocations[] = [
                    "id" => $factory->id,
                    "text" => $factory->name
                ];
            }

            $subArrivalLocationIds = explode(",", $currentDeliveryOrder->sub_arrival_location_id);
            $subArrivalLocations = ArrivalSubLocation::whereIn("id", $subArrivalLocationIds)->get();
            foreach($subArrivalLocations as $section) {
                $sectionLocations[] = [
                    "id" => $section->id,
                    "text" => $section->name ?? "N/A"
                ];
            }
        }

        $locations = [$companyLocations, $factoryLocations, $sectionLocations];

        $data['SaleOrders'] = $SaleOrders;
        $data["locations"] = $locations;
        $data['DeliveryOrders'] = DeliveryOrder::where('so_id', $currentSaleOrder->id)
            // ->whereDoesntHave('loadingProgram')
            // ->with("loadingPrograms")
            ->withSum("saleSecondWeighbridge", "net_weight")
            ->withSum("delivery_order_data", "qty")
            ->where('am_approval_status', 'approved')
            ->get()
            ->reject(function($delivery_order) {
                return $delivery_order->sale_second_weighbridge_sum_net_weight < $delivery_order->sale_second_weighbridge_sum_net_weight;
            });

        
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

        // dd($saleOrder->pay_type_id);

        // Make delivery_order_id required only if pay_type_id is not 11
        if (!$isDeliveryOrderOptional) {
            $validationRules['delivery_order_id'] = 'required|min:1';
            $validationRules['delivery_order_id.*'] = 'exists:delivery_order,id';
            $validationRules['loading_program_items.*.delivery_order_id'] = 'required|min:1|exists:delivery_order,id';
        } else {
            $validationRules['delivery_order_id'] = 'nullable';
            $validationRules['delivery_order_id.*'] = 'exists:delivery_order,id';
            $validationRules['loading_program_items.*.delivery_order_id'] = 'nullable|exists:delivery_order,id';
        }

        $validator = Validator::make($request->all(), $validationRules);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
            }

        $loadingProgram = LoadingProgram::findOrFail($id);

        // Check if delivery order already has a loading program (excluding current one)
        // if ($request->delivery_order_id) {
        //     $existingLoadingProgram = LoadingProgram::where('delivery_order_id', $request->delivery_order_id)
        //         ->where('id', '!=', $id)
        //         ->first();
        //     if ($existingLoadingProgram) {
        //         return response()->json(['errors' => ['delivery_order_id' => 'Loading program already exists for this delivery order.']], 422);
        //     }
        // }
        // Get location data from delivery order if available, otherwise from sale order
        $companyLocationIds = [];
        $arrivalLocationIds = [];
        $subArrivalLocationIds = [];

        if ($request->delivery_order_id && is_array($request->delivery_order_id) && count($request->delivery_order_id) > 0) {
            $deliveryOrders = DeliveryOrder::whereIn('id', $request->delivery_order_id)->get();
            $companyLocationIds = $deliveryOrders->pluck('location_id')->unique()->toArray();
            $arrivalLocationIds = $deliveryOrders->pluck('arrival_location_id')->filter()->unique()->toArray();
            $subArrivalLocationIds = $deliveryOrders->pluck('sub_arrival_location_id')->filter()->unique()->toArray();
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
                'delivery_order_id' => is_array($request->delivery_order_id) ? ($request->delivery_order_id[0] ?? null) : ($request->delivery_order_id ?: null),
                'company_locations' => $companyLocationIds,
                'arrival_locations' => $arrivalLocationIds,
                'sub_arrival_locations' => $subArrivalLocationIds,
                'remark' => $request->remark
            ]);
    
            // Delete existing items and create new ones
            $loadingProgram->loadingProgramItems()->whereDoesntHave("firstWeighbridge")->delete();
    
            if (isset($request->loading_program_items) && is_array($request->loading_program_items)) {
                foreach ($request->loading_program_items as $index => $itemData) {
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
    public function getDo(Request $request) {
        $do_id = $request->do_id;
        $delivery_order_data = DeliveryOrderData::where("delivery_order_id", $do_id)->first();
        
        if(!$delivery_order_data) return '';

        return $delivery_order_data->qty;
    }

    public function getLocations(Request $request) {
        $so_id = $request->so_id;
        $sale_order = SalesOrder::with("factories", "sections")->find($so_id);

        $factories = [
            [
                "id" => "",
                "text" => "Select Location"
            ]
        ];
        $sections = [
            [
                "id" => "",
                "text" => "Select Sub Location"
            ]
        ];

        foreach($sale_order->factories as $factory) {
            $arrival_location_id = $factory->arrival_location_id;

            $factories[] = [
                "id" => $arrival_location_id,
                "text" => getArrivalLocations($arrival_location_id)?->name
            ];
        }

        foreach($sale_order->sections as $section) {
            $section_id = $section->arrival_sub_location_id;

            $sections[] = [
                "id" => $section_id,
                "text" => subArrivalLocationId($section_id)?->name
            ];
        }
            
      
        return [$factories, $sections];
    }
}
