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
use Illuminate\Validation\ValidationException;
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
        // Get customers linked to these loading programs via sale orders
        $lpIds = LoadingProgram::pluck('id');
        $soIds = DB::table('loading_program_sale_order')->whereIn('loading_program_id', $lpIds)->pluck('sale_order_id');
        $customerIds = SalesOrder::whereIn('id', $soIds)->distinct()->pluck('customer_id');
        $customers = \App\Models\Master\Customer::whereIn('id', $customerIds)->get();

        // Get items linked via sale orders data
        $itemIds = \App\Models\Sales\SalesOrderData::whereIn('sale_order_id', $soIds)->distinct()->pluck('item_id');
        $items = \App\Models\Product::whereIn('id', $itemIds)->get();

        // Get arrival and sub arrival locations from loading program items
        $arrivalLocationIds = LoadingProgramItem::whereIn('loading_program_id', $lpIds)->distinct()->pluck('arrival_location_id');
        $factories = ArrivalLocation::whereIn('id', $arrivalLocationIds)->get();

        $subArrivalLocationIds = LoadingProgramItem::whereIn('loading_program_id', $lpIds)->distinct()->pluck('sub_arrival_location_id');
        $galas = ArrivalSubLocation::whereIn('id', $subArrivalLocationIds)->get();

        return view('management.sales.loading-program.index', compact('customers', 'items', 'factories', 'galas'));
    }

    /**
     * Get list of loading programs.
     */
    public function getList(Request $request)
    {
        $LoadingPrograms = LoadingProgram::with([
                'saleOrder.customer', 
                'saleOrder.sales_order_data.item',
                'saleOrders.customer',
                'saleOrders.sales_order_data.item',
                'deliveryOrder', 
                'deliveryOrders',
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
                    })->orWhereHas('saleOrders', function ($query) use ($searchTerm) {
                        $query->where('reference_no', 'like', $searchTerm);
                    })->orWhereHas('deliveryOrder', function ($query) use ($searchTerm) {
                        $query->where('reference_no', 'like', $searchTerm);
                    })->orWhereHas('deliveryOrders', function ($query) use ($searchTerm) {
                        $query->where('reference_no', 'like', $searchTerm);
                    })->orWhereHas('loadingProgramItems', function ($query) use ($searchTerm) {
                        $query->where('transaction_number', 'like', $searchTerm)
                              ->orWhere('truck_number', 'like', $searchTerm);
                    })->orWhere('id', 'like', $searchTerm);
                });
            })
            // SO No filter
            ->when($request->filled('so_no'), function ($q) use ($request) {
                $q->whereHas('saleOrders', function ($sq) use ($request) {
                    $sq->where('reference_no', 'like', '%' . $request->so_no . '%');
                })->orWhereHas('saleOrder', function ($sq) use ($request) {
                    $sq->where('reference_no', 'like', '%' . $request->so_no . '%');
                });
            })
            // DO No filter
            ->when($request->filled('do_no'), function ($q) use ($request) {
                $q->whereHas('deliveryOrders', function ($sq) use ($request) {
                    $sq->where('reference_no', 'like', '%' . $request->do_no . '%');
                })->orWhereHas('deliveryOrder', function ($sq) use ($request) {
                    $sq->where('reference_no', 'like', '%' . $request->do_no . '%');
                });
            })
            // Customer filter
            ->when($request->filled('customer_id') && $request->customer_id != 'all', function ($q) use ($request) {
                $q->whereHas('saleOrders', function ($sq) use ($request) {
                    $sq->where('customer_id', $request->customer_id);
                })->orWhereHas('saleOrder', function ($sq) use ($request) {
                    $sq->where('customer_id', $request->customer_id);
                });
            })
            // Commodity / Item filter
            ->when($request->filled('item_id') && $request->item_id != 'all', function ($q) use ($request) {
                $q->whereHas('saleOrders.sales_order_data', function ($sq) use ($request) {
                    $sq->where('item_id', $request->item_id);
                })->orWhereHas('saleOrder.sales_order_data', function ($sq) use ($request) {
                    $sq->where('item_id', $request->item_id);
                });
            })
            // Ticket No filter
            ->when($request->filled('ticket_no'), function ($q) use ($request) {
                $q->whereHas('loadingProgramItems', function ($sq) use ($request) {
                    $sq->where('transaction_number', 'like', '%' . $request->ticket_no . '%');
                });
            })
            // Truck No filter
            ->when($request->filled('truck_no'), function ($q) use ($request) {
                $q->whereHas('loadingProgramItems', function ($sq) use ($request) {
                    $sq->where('truck_number', 'like', '%' . $request->truck_no . '%');
                });
            })
            // Container No filter
            ->when($request->filled('container_no'), function ($q) use ($request) {
                $q->whereHas('loadingProgramItems', function ($sq) use ($request) {
                    $sq->where('container_number', 'like', '%' . $request->container_no . '%');
                });
            })
            // Factory filter
            ->when($request->filled('factory_id') && $request->factory_id != 'all', function ($q) use ($request) {
                $q->whereHas('loadingProgramItems', function ($sq) use ($request) {
                    $sq->where('arrival_location_id', $request->factory_id);
                });
            })
            // Gala filter
            ->when($request->filled('gala_id') && $request->gala_id != 'all', function ($q) use ($request) {
                $q->whereHas('loadingProgramItems', function ($sq) use ($request) {
                    $sq->where('sub_arrival_location_id', $request->gala_id);
                });
            })
            // Date filter
            ->when($request->filled('date_range'), function ($q) use ($request) {
                $dates = explode(' - ', $request->date_range);
                if (count($dates) == 2) {
                    $q->whereBetween('created_at', [trim($dates[0]) . ' 00:00:00', trim($dates[1]) . ' 23:59:59']);
                }
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
        $data = [
            'SaleOrders' => collect(),
            'DeliveryOrders' => collect(),
            'Brands' => \App\Models\Master\Brands::where('status', 1)->get(),
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

        $validationRules = [
            'main_company_location_id' => 'required|exists:model_location,id',
            'sale_order_id' => 'required|array|min:1',
            'sale_order_id.*' => 'exists:sales_orders,id',
            'loading_program_items' => 'required|array|min:1',
            'loading_program_items.*.truck_number' => 'required|string|distinct',
            'loading_program_items.*.brand_id' => 'nullable|exists:brands,id',
            'loading_program_items.*.arrival_location_id' => 'required|exists:arrival_locations,id',
            'loading_program_items.*.sub_arrival_location_id' => 'required|exists:arrival_sub_locations,id',
            'remark' => 'nullable|string'
        ];

        // Check if any sale order has pay_type_id != 11
        $saleOrders = SalesOrder::whereIn('id', $request->sale_order_id)->get();
        $isAnyDeliveryOrderRequired = $saleOrders->contains(function ($so) {
            return $so->pay_type_id != 11;
        });

        if ($isAnyDeliveryOrderRequired) {
            $validationRules['delivery_order_id'] = 'required|array|min:1';
            $validationRules['delivery_order_id.*'] = 'exists:delivery_order,id';
        } else {
            $validationRules['delivery_order_id'] = 'nullable|array';
            $validationRules['delivery_order_id.*'] = 'exists:delivery_order,id';
        }

        // Row-level validation for DO
        if ($request->has('loading_program_items')) {
            $allSaleOrders = SalesOrder::whereIn('id', collect($request->loading_program_items)->pluck('sale_order_id')->flatten()->unique())->get()->keyBy('id');
            
            foreach ($request->loading_program_items as $index => $itemData) {
                $itemSoIds = (array)($itemData['sale_order_id'] ?? []);
                $isItemDORequired = false;
                
                foreach ($itemSoIds as $soId) {
                    if (isset($allSaleOrders[$soId]) && $allSaleOrders[$soId]->pay_type_id != 11) {
                        $isItemDORequired = true;
                        break;
                    }
                }

                if ($isItemDORequired) {
                    $validationRules["loading_program_items.$index.delivery_order_id"] = 'required|array|min:1';
                } else {
                    $validationRules["loading_program_items.$index.delivery_order_id"] = 'nullable|array';
                }
                $validationRules["loading_program_items.$index.delivery_order_id.*"] = 'exists:delivery_order,id';
            }
        }

        $validator = Validator::make($request->all(), $validationRules);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $request['created_by'] = auth()->user()->id;

        // Get location data from delivery orders if available
        $companyLocationIds = [];
        $arrivalLocationIds = [];
        $subArrivalLocationIds = [];

        if ($request->delivery_order_id && count($request->delivery_order_id) > 0) {
            $deliveryOrders = DeliveryOrder::whereIn('id', $request->delivery_order_id)->get();
            $companyLocationIds = $deliveryOrders->flatMap(fn($do) => explode(',', $do->location_id))->filter()->unique()->toArray();
            $arrivalLocationIds = $deliveryOrders->flatMap(fn($do) => explode(',', $do->arrival_location_id))->filter()->unique()->toArray();
            $subArrivalLocationIds = $deliveryOrders->flatMap(fn($do) => explode(',', $do->sub_arrival_location_id))->filter()->unique()->toArray();
        } else {
            $mainLoc = $request->main_company_location_id;
            foreach ($saleOrders as $so) {
                foreach ($so->locations as $loc) {
                    if (!$mainLoc || $loc->location_id == $mainLoc) $companyLocationIds[] = $loc->location_id;
                }
                foreach ($so->factories as $fc) $arrivalLocationIds[] = $fc->arrival_location_id;
                foreach ($so->sections as $sec) $subArrivalLocationIds[] = $sec->arrival_sub_location_id;
            }
            $companyLocationIds = array_unique($companyLocationIds);
            $arrivalLocationIds = array_unique($arrivalLocationIds);
            $subArrivalLocationIds = array_unique($subArrivalLocationIds);
        }

        if ($request->main_company_location_id && !is_array($request->main_company_location_id) && !in_array($request->main_company_location_id, $companyLocationIds)) {
            $companyLocationIds[] = $request->main_company_location_id;
        }

        DB::beginTransaction();
        try {

            $loadingProgram = LoadingProgram::create([
                'company_id' => $request->company_id,
                'sale_order_id' => $request->sale_order_id[0], // Keep for backward compatibility if needed, but we use pivot
                'delivery_order_id' => isset($request->delivery_order_id[0]) ? $request->delivery_order_id[0] : null,
                'company_locations' => $companyLocationIds,
                'company_location_id' => $request->main_company_location_id,
                'arrival_locations' => $arrivalLocationIds,
                'sub_arrival_locations' => $subArrivalLocationIds,
                'remark' => $request->remark,
                'created_by' => $request->created_by
            ]);

            // Sync main Loading Program relationships
            $loadingProgram->saleOrders()->sync($request->sale_order_id);
            if ($request->delivery_order_id) {
                $loadingProgram->deliveryOrders()->sync($request->delivery_order_id);
            }

            if (isset($request->loading_program_items) && is_array($request->loading_program_items)) {
                foreach ($request->loading_program_items as $index => $itemData) {
                    $selected_do_ids = $itemData['delivery_order_id'] ?? [];
                    
                    // Logic for balance check if needed (re-implemented for multi-DO)
/*
                    foreach ($selected_do_ids as $do_id) {
                        $lpBalance = getLoadingProgramBalance($do_id);
                        $swbBalance = get_second_weighbridge_balance_by_delivery_order($do_id);
                        $balance = min($lpBalance, $swbBalance);
                        $qty = $itemData['qty']; // Qty is per truck, usually.
                        
                        if ($balance < $qty) {
                            DB::rollBack();
                            return response()->json([
                                "errors" => ["loading_program_items.$index.qty" => ["Your available balance (taking Second Weighbridge into account) for DO $do_id is $balance, you can not exceed that balance."]]
                            ], 422);
                        }
                    }
*/

                    $loadingProgramItem = LoadingProgramItem::create([
                        'loading_program_id' => $loadingProgram->id,
                        'transaction_number' => self::getNumber($request),
                        'truck_number' => $itemData['truck_number'],
                        'container_number' => $itemData['container_number'] ?? null,
                        'packing' => $itemData['packing'] ?? null,
                        'brand_id' => $itemData['brand_id'] ?? null,
                        'arrival_location_id' => $itemData['arrival_location_id'],
                        'sub_arrival_location_id' => $itemData['sub_arrival_location_id'],
                        'driver_name' => $itemData['driver_name'] ?? null,
                        'contact_details' => $itemData['contact_details'] ?? null,
                        'transporter_id' => $itemData['transporter_id'] ?? null,
                        'qty' => $itemData['qty'] ?? 0,
                        'delivery_order_id' => $selected_do_ids[0] ?? null, // Backward compatibility
                    ]);

                    // Sync Item relationships
                    if (isset($itemData['sale_order_id']) && is_array($itemData['sale_order_id'])) {
                        $loadingProgramItem->saleOrders()->sync($itemData['sale_order_id']);
                    }
                    if (!empty($selected_do_ids)) {
                        $loadingProgramItem->deliveryOrders()->sync($selected_do_ids);
                        
                        // Enforce SO balance check
                        $totalBalance = 0;
                        $selected_so_ids = $itemData['sale_order_id'] ?? [];
                        foreach ($selected_so_ids as $so_id) {
                            $totalBalance += getSaleOrderBalanceAgainstDC($so_id, $loadingProgramItem->id);
                        }

                        $qty = $itemData['qty'] ?? 0;
                        if ($qty > $totalBalance) {
                            DB::rollBack();
                            return response()->json([
                                "errors" => ["loading_program_items.$index.qty" => ["Suggested quantity ($qty) exceeds the total available Sales Order balance ($totalBalance)."]]
                            ], 422);
                        }
                    }
                }
            }
            DB::commit();
        } catch (Exception $e) {
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
        $LoadingProgram = LoadingProgram::with([
            'loadingProgramItems.arrivalLocation',
            'loadingProgramItems.subArrivalLocation',
            'loadingProgramItems.brand',
            'loadingProgramItems.transporter',
            'loadingProgramItems.saleOrders',
            'loadingProgramItems.deliveryOrders',
            'saleOrder.customer',
            'saleOrder.sales_order_data.item',
            'saleOrder.locations',
            'deliveryOrder',
            'saleOrders.customer',
            'saleOrders.sales_order_data.item',
            'saleOrders.sales_order_data.brand',
            'saleOrders.locations',
            'deliveryOrders.customer',
            'deliveryOrders.delivery_order_data.item',
            'deliveryOrders.delivery_order_data.brand'
        ])->findOrFail($id);

        $data['LoadingProgram'] = $LoadingProgram;
        $data['SalesOrders'] = $LoadingProgram->saleOrders->isEmpty() ? collect([$LoadingProgram->saleOrder]) : $LoadingProgram->saleOrders;
        $data['DeliveryOrders'] = $LoadingProgram->deliveryOrders->isEmpty() ? collect([$LoadingProgram->deliveryOrder])->filter() : $LoadingProgram->deliveryOrders;
        $data['Brands'] = \App\Models\Master\Brands::where('status', 1)->get();

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
            'loadingProgramItems.transporter',
            'loadingProgramItems.firstWeighbridge',
            'loadingProgramItems.saleOrders',
            'loadingProgramItems.deliveryOrders.delivery_order_data',
            'saleOrder.customer',
            'saleOrder.sales_order_data.item',
            'saleOrder.sales_order_data.brand',
            'saleOrder.locations',
            'deliveryOrder',
            'saleOrders.customer',
            'saleOrders.sales_order_data.item',
            'saleOrders.sales_order_data.brand',
            'saleOrders.locations',
            'saleOrders.factories',
            'saleOrders.sections',
            'deliveryOrders.customer',
            'deliveryOrders.delivery_order_data.item',
            'deliveryOrders.delivery_order_data.brand'
        ])->findOrFail($id);

        $SaleOrders = SalesOrder::where('am_approval_status', 'approved')
        ->get()
        ->filter(function ($sale_order) use ($data) {
            if ($sale_order->pay_type_id == 11) {
                return true;
            }
            if($data["LoadingProgram"]->saleOrders->contains('id', $sale_order->id) || $sale_order->id == $data["LoadingProgram"]->sale_order_id) {
                return true;
            }
            
            /* foreach ($sale_order->delivery_orders as $delivery_order) {
                $lpBalance = getLoadingProgramBalance($delivery_order->id); 
                $swbBalance = get_second_weighbridge_balance_by_delivery_order($delivery_order->id);
                if ($lpBalance > 0 && $swbBalance > 0) { 
                    return true;
                }
            }
            return false; */
            return true;
        });

        $currentSaleOrders = $data['LoadingProgram']->saleOrders->isEmpty()
            ? collect([$data['LoadingProgram']->saleOrder])->filter()
            : $data['LoadingProgram']->saleOrders;
        $currentDeliveryOrders = $data['LoadingProgram']->deliveryOrders->isEmpty()
            ? collect([$data['LoadingProgram']->deliveryOrder])->filter()
            : $data['LoadingProgram']->deliveryOrders;
        
        $companyLocations = [];
        $factoryLocations = [];
        $sectionLocations = [];

        $allType11 = $currentSaleOrders->isNotEmpty() && $currentSaleOrders->every(function ($saleOrder) {
            return $saleOrder->pay_type_id == 11;
        });

        if($allType11 && $currentDeliveryOrders->isEmpty()) {
            foreach($currentSaleOrders as $currentSaleOrder) {
                foreach($currentSaleOrder->locations as $location) {
                    $companyLocations[] = [
                        "id" => $location->location_id,
                        "text" => getLocation($location->location_id)?->name ?? "N/A"
                    ];
                }

                foreach($currentSaleOrder->factories as $factory) {
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
            }
        } else {
            $companyLocationIds = $currentDeliveryOrders->flatMap(function ($deliveryOrder) {
                return explode(",", (string) $deliveryOrder->location_id);
            })->filter()->unique()->toArray();
            $compLocations = CompanyLocation::whereIn("id", $companyLocationIds)->get();

            foreach($compLocations as $location) {
                $companyLocations[] = [
                    "id" => $location->location_id,
                    "text" => getLocation($location->location_id)?->name ?? "N/A"
                ];
            }

            $arrivalLocationIds = $currentDeliveryOrders->flatMap(function ($deliveryOrder) {
                return explode(",", (string) $deliveryOrder->arrival_location_id);
            })->filter()->unique()->toArray();
            $arrivalLocations = ArrivalLocation::whereIn("id", $arrivalLocationIds)->get();
            foreach($arrivalLocations as $factory) {
                $factoryLocations[] = [
                    "id" => $factory->id,
                    "text" => $factory->name
                ];
            }

            $subArrivalLocationIds = $currentDeliveryOrders->flatMap(function ($deliveryOrder) {
                return explode(",", (string) $deliveryOrder->sub_arrival_location_id);
            })->filter()->unique()->toArray();
            $subArrivalLocations = ArrivalSubLocation::whereIn("id", $subArrivalLocationIds)->get();
            foreach($subArrivalLocations as $section) {
                $sectionLocations[] = [
                    "id" => $section->id,
                    "text" => $section->name ?? "N/A"
                ];
            }
        }

        $companyLocations = collect($companyLocations)->unique('id')->values()->toArray();
        $factoryLocations = collect($factoryLocations)->unique('id')->values()->toArray();
        $sectionLocations = collect($sectionLocations)->unique('id')->values()->toArray();

        $loading_program_dos = $data["LoadingProgram"]->loadingProgramItems
            ->flatMap(function ($item) {
                $ids = $item->deliveryOrders->pluck('id')->toArray();
                if (empty($ids) && $item->delivery_order_id) {
                    $ids = [$item->delivery_order_id];
                }
                return $ids;
            })
            ->filter()
            ->unique()
            ->values()
            ->toArray();
                
        $locations = [$companyLocations, $factoryLocations, $sectionLocations];
        $deliveryOrders = DeliveryOrder::whereIn('so_id', $currentSaleOrders->pluck('id'))
            ->where('am_approval_status', 'approved')
            ->get()
            /* ->reject(function($delivery_order) use ($data, $loading_program_dos) {
                if(in_array($delivery_order->id, $loading_program_dos)) {
                    return false;
                }
                $lpBalance = getLoadingProgramBalance($delivery_order->id);
                $swbBalance = get_second_weighbridge_balance_by_delivery_order($delivery_order->id);
                return $lpBalance <= 0 || $swbBalance <= 0;
            }); */
            ;


        $deliveryOrders = $deliveryOrders->map(function($deliveryOrder) {
            $deliveryOrder->reference_no = $deliveryOrder->reference_no . " - " . getLocation($deliveryOrder->location_id)?->name;
            return $deliveryOrder;
        });

        $data['SaleOrders'] = $SaleOrders;
        $data["locations"] = $locations;
        $data['DeliveryOrders'] = $deliveryOrders;
        $data["LoadingProgramDos"] = $loading_program_dos;
        $data['Brands'] = \App\Models\Master\Brands::where('status', 1)->get();

        
        // dd($locations);
        return view('management.sales.loading-program.edit', $data);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $validationRules = [
            'main_company_location_id' => 'required|exists:model_location,id',
            'sale_order_id' => 'required|array|min:1',
            'sale_order_id.*' => 'exists:sales_orders,id',
            'loading_program_items' => 'required|array|min:1',
            'loading_program_items.*.truck_number' => 'required|string|distinct',
            'loading_program_items.*.brand_id' => 'nullable|exists:brands,id',
            'loading_program_items.*.arrival_location_id' => 'required|exists:arrival_locations,id',
            'loading_program_items.*.sub_arrival_location_id' => 'required|exists:arrival_sub_locations,id',
            'remark' => 'nullable|string'
        ];

        $saleOrders = SalesOrder::whereIn('id', $request->sale_order_id)->get();
        $isAnyDeliveryOrderRequired = $saleOrders->contains(function ($so) {
            return $so->pay_type_id != 11;
        });

        if ($isAnyDeliveryOrderRequired) {
            $validationRules['delivery_order_id'] = 'required|array|min:1';
            $validationRules['delivery_order_id.*'] = 'exists:delivery_order,id';
        } else {
            $validationRules['delivery_order_id'] = 'nullable|array';
            $validationRules['delivery_order_id.*'] = 'exists:delivery_order,id';
        }

        // Row-level validation for DO
        if ($request->has('loading_program_items')) {
            $allSaleOrders = SalesOrder::whereIn('id', collect($request->loading_program_items)->pluck('sale_order_id')->flatten()->unique())->get()->keyBy('id');
            
            foreach ($request->loading_program_items as $index => $itemData) {
                $itemSoIds = (array)($itemData['sale_order_id'] ?? []);
                $isItemDORequired = false;
                
                foreach ($itemSoIds as $soId) {
                    if (isset($allSaleOrders[$soId]) && $allSaleOrders[$soId]->pay_type_id != 11) {
                        $isItemDORequired = true;
                        break;
                    }
                }

                if ($isItemDORequired) {
                    $validationRules["loading_program_items.$index.delivery_order_id"] = 'required|array|min:1';
                } else {
                    $validationRules["loading_program_items.$index.delivery_order_id"] = 'nullable|array';
                }
                $validationRules["loading_program_items.$index.delivery_order_id.*"] = 'exists:delivery_order,id';
            }
        }

        $validator = Validator::make($request->all(), $validationRules);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $loadingProgram = LoadingProgram::findOrFail($id);

        $companyLocationIds = [];
        $arrivalLocationIds = [];
        $subArrivalLocationIds = [];

        if ($request->delivery_order_id && count($request->delivery_order_id) > 0) {
            $deliveryOrders = DeliveryOrder::whereIn('id', $request->delivery_order_id)->get();
            $companyLocationIds = $deliveryOrders->flatMap(fn($do) => explode(',', $do->location_id))->filter()->unique()->toArray();
            $arrivalLocationIds = $deliveryOrders->flatMap(fn($do) => explode(',', $do->arrival_location_id))->filter()->unique()->toArray();
            $subArrivalLocationIds = $deliveryOrders->flatMap(fn($do) => explode(',', $do->sub_arrival_location_id))->filter()->unique()->toArray();
        } else {
            $mainLoc = $request->main_company_location_id;
            $saleOrders = SalesOrder::whereIn('id', $request->sale_order_id)->get();
            foreach ($saleOrders as $so) {
                foreach ($so->locations as $loc) {
                    if (!$mainLoc || $loc->location_id == $mainLoc) $companyLocationIds[] = $loc->location_id;
                }
                foreach ($so->factories as $fc) $arrivalLocationIds[] = $fc->arrival_location_id;
                foreach ($so->sections as $sec) $subArrivalLocationIds[] = $sec->arrival_sub_location_id;
            }
            $companyLocationIds = array_unique($companyLocationIds);
            $arrivalLocationIds = array_unique($arrivalLocationIds);
            $subArrivalLocationIds = array_unique($subArrivalLocationIds);
        }

        if ($request->main_company_location_id && !is_array($request->main_company_location_id) && !in_array($request->main_company_location_id, $companyLocationIds)) {
            $companyLocationIds[] = $request->main_company_location_id;
        }

        DB::beginTransaction();
        try {
            $loadingProgram->update([
                'sale_order_id' => $request->sale_order_id[0],
                'delivery_order_id' => isset($request->delivery_order_id[0]) ? $request->delivery_order_id[0] : null,
                'company_locations' => $companyLocationIds,
                'company_location_id' => $request->main_company_location_id,
                'arrival_locations' => $arrivalLocationIds,
                'sub_arrival_locations' => $subArrivalLocationIds,
                'remark' => $request->remark
            ]);

            // Sync main Loading Program relationships
            $loadingProgram->saleOrders()->sync($request->sale_order_id);
            if ($request->delivery_order_id) {
                $loadingProgram->deliveryOrders()->sync($request->delivery_order_id);
            } else {
                $loadingProgram->deliveryOrders()->detach();
            }

            // Delete existing items and create new ones (keeping logic consistent with original and multi-sync)
            $loadingProgram->loadingProgramItems()->whereDoesntHave("firstWeighbridge")->delete();

            if (isset($request->loading_program_items) && is_array($request->loading_program_items)) {
                foreach ($request->loading_program_items as $index => $itemData) {
                    $selected_do_ids = $itemData['delivery_order_id'] ?? [];

/*
                    foreach ($selected_do_ids as $do_id) {
                        $lpBalance = getLoadingProgramBalance($do_id);
                        $swbBalance = get_second_weighbridge_balance_by_delivery_order($do_id);
                        $balance = min($lpBalance, $swbBalance);
                        $qty = $itemData['qty'];
                        if ($balance < $qty) {
                            DB::rollBack();
                            return response()->json([
                                "errors" => ["loading_program_items.$index.qty" => ["Your available balance (taking Second Weighbridge into account) for DO $do_id is $balance, you can not exceed that balance."]]
                            ], 422);
                        }
                    }
*/

                    $loadingProgramItem = LoadingProgramItem::create([
                        'loading_program_id' => $loadingProgram->id,
                        'transaction_number' => $itemData['transaction_number'] ?? self::getNumber($request),
                        'truck_number' => $itemData['truck_number'],
                        'container_number' => $itemData['container_number'] ?? null,
                        'packing' => $itemData['packing'] ?? null,
                        'brand_id' => $itemData['brand_id'] ?? null,
                        'arrival_location_id' => $itemData['arrival_location_id'],
                        'sub_arrival_location_id' => $itemData['sub_arrival_location_id'],
                        'driver_name' => $itemData['driver_name'] ?? null,
                        'contact_details' => $itemData['contact_details'] ?? null,
                        'transporter_id' => $itemData['transporter_id'] ?? null,
                        'qty' => $itemData['qty'] ?? 0,
                        'delivery_order_id' => $selected_do_ids[0] ?? null,
                    ]);

                    // Sync Item relationships
                    if (isset($itemData['sale_order_id']) && is_array($itemData['sale_order_id'])) {
                        $loadingProgramItem->saleOrders()->sync($itemData['sale_order_id']);
                    }
                    if (!empty($selected_do_ids)) {
                        $loadingProgramItem->deliveryOrders()->sync($selected_do_ids);

                        // Enforce SO balance check
                        $totalBalance = 0;
                        $selected_so_ids = $itemData['sale_order_id'] ?? [];
                        foreach ($selected_so_ids as $so_id) {
                            $totalBalance += getSaleOrderBalanceAgainstDC($so_id, $loadingProgramItem->id);
                        }

                        $qty = $itemData['qty'] ?? 0;
                        if ($qty > $totalBalance) {
                            DB::rollBack();
                            return response()->json([
                                "errors" => ["loading_program_items.$index.qty" => ["Suggested quantity ($qty) exceeds the total available Sales Order balance ($totalBalance)."]]
                            ], 422);
                        }
                    }
                }
            }
            DB::commit();
        } catch (Exception $e) {
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
        $sale_order_ids = is_array($request->sale_order_id) ? $request->sale_order_id : [$request->sale_order_id];
        $company_location_id = $request->company_location_id;

        $SalesOrders = SalesOrder::with([
            'customer', 
            'sales_order_data.item', 
            'sales_order_data.brand', 
            'locations',
            'logistics' => function($q) {
                $q->where('am_approval_status', 'approved');
            },
            'logistics.items.transporter'
        ])->whereIn('id', $sale_order_ids)->get();

        $transportersMap = [];
        foreach ($SalesOrders as $so) {
            $transporters = [];
            foreach ($so->logistics as $logistics) {
                foreach ($logistics->items as $item) {
                    if ($item->transporter) {
                        $transporters[] = [
                            'id' => $item->transporter->id,
                            'name' => $item->transporter->name,
                        ];
                    }
                }
            }
            // Unique transporters
            $transportersMap[$so->id] = [
                'transporter_used' => $so->transporter_used,
                'transporters' => collect($transporters)->unique('id')->values()->toArray()
            ];
        }
        
        $excludeItemIds = null;
        if ($request->loading_program_id) {
            $excludeItemIds = LoadingProgramItem::where('loading_program_id', $request->loading_program_id)->pluck('id')->toArray();
        }

        $DeliveryOrders = DeliveryOrder::whereIn('so_id', $sale_order_ids)
            ->where('am_approval_status', 'approved')
            ->when($company_location_id, function($q) use ($company_location_id) {
                // Filter for strictly matching location_id (prevents multiple locations)
                return $q->where('location_id', (string)$company_location_id);
            })
            ->withSum('delivery_order_data', 'qty')
            ->withSum('loadingProgramItems', 'qty')
            ->get()
            ->reject(function($delivery_order) use ($excludeItemIds) {
                $lpBalance = getLoadingProgramBalance($delivery_order->id, $excludeItemIds);
                $swbBalance = get_second_weighbridge_balance_by_delivery_order($delivery_order->id);
                return $lpBalance <= 0 || $swbBalance <= 0;
            })
            ->map(function($delivery_order) use ($request) {
                $location_name = getLocation($delivery_order->location_id)?->name ?? 'N/A';
                $delivery_order->reference_no = $delivery_order->reference_no . " - " . $location_name;
                
                $lpBalance = getLoadingProgramBalance($delivery_order->id, $request->loading_program_item_id);
                $swbBalance = get_second_weighbridge_balance_by_delivery_order($delivery_order->id);
                $delivery_order->balance = min($lpBalance, $swbBalance);
                
                return $delivery_order;
            });

        $html = view('management.sales.loading-program.getSaleOrderRelatedData', compact('SalesOrders', 'DeliveryOrders'))->render();

        // Check if any delivery order is optional (pay_type_id = 11)
        $isAnyDeliveryOrderOptional = $SalesOrders->contains(function($so) {
            return $so->pay_type_id == 11;
        });

        // Get sale order data for first SO for default population
        $firstSo = $SalesOrders->first();
        $firstSoData = $firstSo->sales_order_data->first();
        $companyLocationId = $firstSo->locations->first()?->location_id;
        
        $saleOrderData = [
            'packing' => $firstSoData->bag_size ?? null,
            'brand_id' => $firstSoData->brand_id ?? null,
            'brand_name' => $firstSoData->brand->name ?? null,
            'arrival_location_id' => $firstSo->arrival_location_id,
            'sub_arrival_location_id' => $firstSo->arrival_sub_location_id,
            'company_location_id' => $companyLocationId,
        ];

        return response()->json([
            'success' => true, 
            'html' => $html,
            'is_delivery_order_optional' => $isAnyDeliveryOrderOptional,
            'pay_type_id' => $firstSo->pay_type_id,
            'sale_order_data' => $saleOrderData,
            'transporters_map' => $transportersMap
        ]);
    }


    public function getDeliveryOrdersBySaleOrder(Request $request)
    {
        $sale_order_ids = is_array($request->sale_order_id) ? $request->sale_order_id : [$request->sale_order_id];
        $company_location_id = $request->company_location_id;

        $deliveryOrders = DeliveryOrder::whereIn('so_id', $sale_order_ids)
            ->where('am_approval_status', 'approved')
            ->when($company_location_id, function($q) use ($company_location_id) {
                // Filter for strictly matching location_id (prevents multiple locations)
                return $q->where('location_id', (string)$company_location_id);
            })
            ->with('customer', 'delivery_order_data.item', 'delivery_order_data.brand')
            ->select('id', 'reference_no', 'customer_id', 'so_id', 'location_id', 'arrival_location_id', 'sub_arrival_location_id', 'am_approval_status')
            ->get();

        $deliveryOrders = $deliveryOrders->reject(function($deliveryOrder) use ($request) {
            $lpBalance = getLoadingProgramBalance($deliveryOrder->id, $request->loading_program_item_id);
            $swbBalance = get_second_weighbridge_balance_by_delivery_order($deliveryOrder->id);
            return $lpBalance <= 0 || $swbBalance <= 0;
        });

        $deliveryOrders = $deliveryOrders->map(function($deliveryOrder) use ($request) {
            $locationIds = explode(',', $deliveryOrder->location_id);
            $locationNames = \App\Models\Master\CompanyLocation::whereIn('id', $locationIds)->pluck('name')->toArray();
            $locationNameStr = implode(', ', $locationNames);
            $deliveryOrder->reference_no = $deliveryOrder->reference_no . " - " . ($locationNameStr ?: 'N/A');

            $lpBalance = getLoadingProgramBalance($deliveryOrder->id, $request->loading_program_item_id);
            $swbBalance = get_second_weighbridge_balance_by_delivery_order($deliveryOrder->id);
            $deliveryOrder->balance = min($lpBalance, $swbBalance);

            return $deliveryOrder;
        });
        
        $SalesOrders = SalesOrder::with(['logistics' => function($q) {
            $q->where('am_approval_status', 'approved');
        }, 'logistics.items.transporter'])
        ->whereIn('id', $sale_order_ids)
        ->get();

        $transportersMap = [];
        foreach ($SalesOrders as $so) {
            $transporters = [];
            foreach ($so->logistics as $logistics) {
                foreach ($logistics->items as $item) {
                    if ($item->transporter) {
                        $transporters[] = [
                            'id' => $item->transporter->id,
                            'name' => $item->transporter->name,
                        ];
                    }
                }
            }
            // Unique transporters
            $transportersMap[$so->id] = [
                'transporter_used' => $so->transporter_used,
                'transporters' => collect($transporters)->unique('id')->values()->toArray()
            ];
        }

        return response()->json([
            'success' => true,
            'delivery_orders' => $deliveryOrders->values(),
            'transporters_map' => $transportersMap
        ]);
    }

    public function getDeliveryOrdersBySaleOrderEdit(Request $request)
    {
        $sale_order_ids = is_array($request->sale_order_id) ? $request->sale_order_id : [$request->sale_order_id];
        $company_location_id = $request->company_location_id;

        $deliveryOrders = DeliveryOrder::whereIn('so_id', $sale_order_ids)
            ->where('am_approval_status', 'approved')
            ->when($company_location_id, function($q) use ($company_location_id) {
                // Filter for strictly matching location_id (prevents multiple locations)
                return $q->where('location_id', (string)$company_location_id);
            })
            ->with('customer', 'delivery_order_data.item', 'delivery_order_data.brand')
            ->select('id', 'reference_no', 'customer_id', 'so_id', 'location_id', 'arrival_location_id', 'sub_arrival_location_id', 'am_approval_status')
            ->get();

        $lpId = $request->loading_program_id;
        $linkedDoIds = [];
        if ($lpId) {
            $linkedDoIds = LoadingProgramItem::where('loading_program_id', $lpId)
                ->with('deliveryOrders:id')
                ->get()
                ->flatMap(function ($item) {
                    $ids = $item->deliveryOrders->pluck('id')->toArray();
                    if (empty($ids) && $item->delivery_order_id) {
                        $ids = [$item->delivery_order_id];
                    }
                    return $ids;
                })
                ->filter()
                ->unique()
                ->values()
                ->toArray();
        }

        $deliveryOrders = $deliveryOrders->reject(function($deliveryOrder) use ($linkedDoIds, $request) {
            if (in_array($deliveryOrder->id, $linkedDoIds)) {
                return false;
            }

            $lpBalance = getLoadingProgramBalance($deliveryOrder->id, $request->loading_program_item_id);
            $swbBalance = get_second_weighbridge_balance_by_delivery_order($deliveryOrder->id);
            return $lpBalance <= 0 || $swbBalance <= 0;
        });

        $deliveryOrders = $deliveryOrders->map(function($deliveryOrder) use ($request) {
            $locationIds = explode(',', $deliveryOrder->location_id);
            $locationNames = \App\Models\Master\CompanyLocation::whereIn('id', $locationIds)->pluck('name')->toArray();
            $locationNameStr = implode(', ', $locationNames);
            $deliveryOrder->reference_no = $deliveryOrder->reference_no . " - " . ($locationNameStr ?: 'N/A');

            $lpBalance = getLoadingProgramBalance($deliveryOrder->id, $request->loading_program_item_id);
            $swbBalance = get_second_weighbridge_balance_by_delivery_order($deliveryOrder->id);
            $deliveryOrder->balance = min($lpBalance, $swbBalance);

            return $deliveryOrder;
        });

        $SalesOrders = SalesOrder::with(['logistics' => function($q) {
            $q->where('am_approval_status', 'approved');
        }, 'logistics.items.transporter'])
        ->whereIn('id', $sale_order_ids)
        ->get();

        $transportersMap = [];
        foreach ($SalesOrders as $so) {
            $transporters = [];
            foreach ($so->logistics as $logistics) {
                foreach ($logistics->items as $item) {
                    if ($item->transporter) {
                        $transporters[] = [
                            'id' => $item->transporter->id,
                            'name' => $item->transporter->name,
                        ];
                    }
                }
            }
            // Unique transporters
            $transportersMap[$so->id] = [
                'transporter_used' => $so->transporter_used,
                'transporters' => collect($transporters)->unique('id')->values()->toArray()
            ];
        }

        return response()->json([
            'success' => true,
            'delivery_orders' => $deliveryOrders,
            'transporters_map' => $transportersMap
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

    public function fetchSaleOrdersByLocation(Request $request)
    {
        $location_id = $request->location_id;
        $excludeItemIds = null;
        if ($request->loading_program_id) {
            $excludeItemIds = LoadingProgramItem::where('loading_program_id', $request->loading_program_id)->pluck('id')->toArray();
        }

        $SaleOrders = SalesOrder::where('am_approval_status', 'approved')
            ->whereHas('locations', function($q) use ($location_id) {
                $q->where('location_id', $location_id);
            })
            ->get()
            ->filter(function($so) use ($location_id, $excludeItemIds, $request) {
                // Keep Type 11 orders as DOs are optional for them
                if ($so->pay_type_id == 11) {
                    return true;
                }

                // If editing, always keep Sale Orders that are already part of this Loading Program
                if ($request->loading_program_id) {
                    $isPart = LoadingProgramItem::where('loading_program_id', $request->loading_program_id)
                        ->whereHas('saleOrders', function($q) use ($so) {
                            $q->where('sales_order_id', $so->id);
                        })->exists();
                    if ($isPart) return true;
                }

                // For other orders, check if they have any approved DO with remaining balance
                return DeliveryOrder::where('so_id', $so->id)
                    ->where('am_approval_status', 'approved')
                    ->where('location_id', (string)$location_id)
                    ->get()
                    ->some(function($do) use ($excludeItemIds) {
                        return getLoadingProgramBalance($do->id, $excludeItemIds) > 0 && 
                               get_second_weighbridge_balance_by_delivery_order($do->id) > 0;
                    });
            })
            ->values();

        return response()->json([
            'success' => true,
            'sale_orders' => $SaleOrders
        ]);
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

    public function getLocationsOfSaleOrder(Request $request) {
        $sale_order_ids = is_array($request->sale_order_id) ? $request->sale_order_id : [$request->sale_order_id];
        $company_location = $request->company_location;

        $factoryArrivalLocationIds = \App\Models\Procurement\Store\FactoryLocation::where('factoryable_type', \App\Models\Sales\SalesOrder::class)
            ->whereIn('factoryable_id', $sale_order_ids)
            ->pluck('arrival_location_id')
            ->unique()
            ->toArray();

        $arrivalLocations = ArrivalLocation::where("company_location_id", $company_location)
                                            ->whereIn("id", $factoryArrivalLocationIds)
                                            ->get();

        $sectionSubArrivalLocationIds = \App\Models\Procurement\Store\SectionLocation::where('sectionable_type', \App\Models\Sales\SalesOrder::class)
            ->whereIn('sectionable_id', $sale_order_ids)
            ->pluck('arrival_sub_location_id')
            ->unique()
            ->toArray();

        $subArrrivalLocations = ArrivalSubLocation::whereIn("id", $sectionSubArrivalLocationIds)
                                                    ->whereIn("arrival_location_id", $arrivalLocations->pluck("id")->toArray())
                                                    ->get();

        $arrivalLocationsDropdown = [];
        $subArrrivalLocationDropdown = [];
        foreach($arrivalLocations as $arrivalLocation) {
            $arrivalLocationsDropdown[] = [
                "id" => $arrivalLocation->id,
                "text" => $arrivalLocation->name
            ];
        }

        foreach($subArrrivalLocations as $subArrrivalLocation) {
            $subArrrivalLocationDropdown[] = [
                "id" => $subArrrivalLocation->id,
                "text" => $subArrrivalLocation->name
            ];
        }

        return [
            $arrivalLocationsDropdown,
            $subArrrivalLocationDropdown
        ];

    }
}
