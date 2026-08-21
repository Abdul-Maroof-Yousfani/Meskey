<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sales\DeliveryChallanRequest;
use App\Models\Master\Customer;
use App\Models\Master\PayType;
use App\Models\Master\CompanyLocation;
use App\Models\Master\ArrivalLocation;
use App\Models\Master\ArrivalSubLocation;
use App\Models\PaymentTerm;
use App\Models\Product;
use App\Models\Sales\DeliveryChallan;
use App\Models\Sales\DeliveryOrder;
use App\Models\Sales\LoadingProgram;
use App\Models\Sales\LoadingProgramItem;
use App\Models\Sales\ReceivingRequest;
use App\Models\Sales\ReceivingRequestItem;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Master\Vendor;

class DeliveryChallanController extends Controller
{
    public function index() {
        // Only get customers that have delivery challan records
        $customerIds = DeliveryChallan::distinct()->pluck('customer_id')->filter();
        $customers = Customer::whereIn('id', $customerIds)->get();

        // Only get items that have delivery challan data records attached to an existing DC
        $itemIds = \App\Models\Sales\DeliveryChallanData::whereIn('delivery_challan_id', function($query) {
                $query->select('id')->from('delivery_challans');
            })
            ->distinct()
            ->pluck('item_id')
            ->filter();
        $items = Product::whereIn('id', $itemIds)->get();

        // Only get delivery orders that are linked to existing delivery challans
        $doIds = DB::table('delivery_challan_delivery_order')
            ->whereIn('delivery_challan_id', function($query) {
                $query->select('id')->from('delivery_challans');
            })
            ->distinct()
            ->pluck('delivery_order_id')
            ->filter();
        $deliveryOrders = DeliveryOrder::whereIn('id', $doIds)->select('id', 'reference_no')->get();

        return view('management.sales.delivery-challan.index', compact('customers', 'items', 'deliveryOrders'));
    }

    public function create() {
        $payment_terms = PaymentTerm::all();
        $customers = Customer::all();
        $items = Product::all();
        $pay_types = PayType::select('name', 'id')->where('status', 'active')->get();
        $delivery_orders = DeliveryOrder::select("delivery_order.id", "delivery_order.reference_no")
            ->where('delivery_order.do_status', 'active')
            ->join('loading_programs', 'delivery_order.id', '=', 'loading_programs.delivery_order_id')
            ->join('loading_program_items', 'loading_programs.id', '=', 'loading_program_items.loading_program_id')
            ->join('loading_slips', 'loading_program_items.id', '=', 'loading_slips.loading_program_item_id')
            ->join('sales_second_weighbridges', 'loading_slips.id', '=', 'sales_second_weighbridges.loading_slip_id')
            ->distinct()
            ->get();
        $labours = Vendor::all();

        $transporters = \App\Models\Master\Transporter::all();
        return view("management.sales.delivery-challan.create", compact("customers", "delivery_orders", "transporters", "labours"));
    }

    public function store(DeliveryChallanRequest $request) {
        DB::beginTransaction();
        $do_id = $request->delivery_order_id;

        // delivery order's delivery date should not be greater than date
        $delivery_order = DeliveryOrder::find($do_id);
        // if(strtotime($delivery_order->dispatch_date) <= strtotime($request->date)) {
        //     return response()->json("Selected Delivery order is expired. Please select a different Delivery order", 422);
        // }
        
        
        try {
            $arrival_location_csv = $request->arrival_location_csv;
            $storage_location_csv = $request->storage_location_csv;
          
            // Calculate total qty
            $total_qty = 0;
            if(is_array($request->qty)) {
                $total_qty = array_sum($request->qty);
            }

            // Auto calculate labour rate and amount based on matched rules
            $labour_rate = ($request->labour_rate === 'N/A' || $request->labour_rate === null) ? 0 : (float)$request->labour_rate;
            $labour_amount = $request->labour_amount ? (float)$request->labour_amount : 0;

            if ($request->labour) {
                $arrival_id = explode(',', $arrival_location_csv)[0] ?? null;
                $first_item_id = $request->item_id[0] ?? null;
                $first_bag_size = $request->bag_size[0] ?? null;
                
                if ($first_item_id && $first_bag_size && $arrival_id) {
                    $product = Product::find($first_item_id);
                    $category_id = $product ? $product->category_id : null;
                    
                    $clean_packing = is_numeric($first_bag_size) ? $first_bag_size : trim(explode(',', (string)$first_bag_size)[0]);
                    
                    $bag_packing = \App\Models\BagPacking::select("id")
                        ->where(function($q) use ($clean_packing) {
                            $q->where("name", $clean_packing . " kg")
                              ->orWhere("name", $clean_packing . "KG")
                              ->orWhere("name", "like", $clean_packing . "%");
                        })
                        ->where(function($q) {
                            $q->where("status", 1)->orWhere("status", 'active');
                        })
                        ->first();
                    
                    if ($category_id && $bag_packing) {
                        $rateObj = \App\Models\Master\LabourRate::where("category_id", $category_id)
                            ->where("factory_id", $arrival_id)
                            ->where("bag_packing_id", $bag_packing->id)
                            ->where(function($q) {
                                $q->where("status", 1)->orWhere("status", 'active');
                            })
                            ->first();
                            
                        if ($rateObj) {
                            $labour_rate = $rateObj->rate;
                        }
                    }
                }
                
                if ($labour_rate > 0) {
                    $labour_amount = $total_qty * $labour_rate;
                }
            }

            // Auto calculate transporter amount based on Logistics
            $transporter_amount = $request->transporter_amount ? (float)$request->transporter_amount : 0;
            
            if ($request->transporter) {
                $transporter_rate = 0;
                $delivery_order = DeliveryOrder::find($do_id);
                
                if ($delivery_order && $delivery_order->so_id) {
                    $logistics = \App\Models\Sales\Logistics::where('type', 'sale_order')
                        ->where('sale_order_id', $delivery_order->so_id)
                        ->first();
                        
                    if ($logistics) {
                        $logisticsItem = \App\Models\Sales\LogisticsItem::where('logistics_id', $logistics->id)
                            ->where('transporter_id', $request->transporter)
                            ->first();
                            
                        if ($logisticsItem) {
                            $transporter_rate = (float)$logisticsItem->rate;
                        }
                    }
                }
                
                if ($transporter_rate > 0) {
                    $transporter_amount = $total_qty * $transporter_rate;
                }
            }

            $delivery_challan = DeliveryChallan::create([
                "customer_id" => $request->customer_id,
                "reference_number" => self::getNumber($request, null, $request->date),
                "location_id" => $request->locations[0],
                "arrival_id" => $arrival_location_csv,
                "section_id" => $storage_location_csv,
                // 'subarrival_id' => $request->storage_id,
                "dispatch_date" => $request->date,
                "dc_no" => $request->dc_no,
                "sauda_type" => $request->sauda_type,
                "labour_status" => $request->labour_status ?? 'paid',
                "company_id" => $request->company_id,
                "labour" => $request->labour,
                "labour_amount" => $labour_amount,
                "transporter" => $request->transporter,
                "transporter_amount" => $transporter_amount,
                "inhouse-weighbridge" => $request->weighbridge,
                "weighbridge-amount" => $request->weighbridge_amount,
                "remarks" => $request->remarks,
                'labour_rate' => $labour_rate,
                "created_by_id" => auth()->user()->id,
            ]);

            
            

            $do_ids = explode(',', $do_id);
            $syncData = [];

            foreach ($do_ids as $id) {
                if (empty($id)) continue;
                
                // Calculate total qty for this DO from the grid items
                $total_qty_for_do = 0;
                if ($request->has('do_data_id')) {
                    foreach ($request->do_data_id as $index => $do_data_id) {
                        $do_data = \App\Models\Sales\DeliveryOrderData::find($do_data_id);
                        if ($do_data && $do_data->delivery_order_id == $id) {
                            $total_qty_for_do += $request->qty[$index];
                        }
                    }
                } else {
                    $total_qty_for_do = $request->qty[0] ?? 0;
                }

                $syncData[$id] = [
                    'qty' => $total_qty_for_do,
                ];
            }

            $delivery_challan->delivery_order()->sync($syncData);

            // Store delivery challan data items
            $createdItems = [];
            foreach($request->item_id as $index => $item) {


                // if($request->no_of_bags[$index] > $balance) {
                //     return response()->json("Total balance is $balance. you can not exceed this balance", 422);
                // }

                $dcData = $delivery_challan->delivery_challan_data()->create([
                    "item_id" => $request->item_id[$index],
                    "qty" => $request->qty[$index],
                    "rate" => $request->rate[$index],
                    "brand_id" => $request->brand_id[$index],
                    "no_of_bags" => $request->no_of_bags[$index],
                    "bag_size" => $request->bag_size[$index],
                    "description" => $request->desc[$index] ?? "",
                    "truck_no" => $request->truck_no[$index],
                    "container_number" => $request->container_number[$index],
                    "do_data_id" => $request->do_data_id[$index],
                    "bag_type" => $request->bag_type[$index],
                    "ticket_id" => $request->ticket_id[$index]
                ]);
                $createdItems[] = $dcData;
            }

            DB::commit();
        } catch(\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }

        return response()->json(["Delivery Challan has been created"]);
    }

    public function destroy(DeliveryChallan $delivery_challan) {
        if($delivery_challan->am_approval_status == "approved" || $delivery_challan->am_approval_status == 'rejected') {
            return response()->json("Delivery Challan has been approved/rejected and cannot be updated.", 400);
        }
        $delivery_challan->receivingRequest()->delete();
        $delivery_challan->delete();

        return response()->json(["message" => "Delivery Challan has been deleted!"]);
    }

    public function update(DeliveryChallanRequest $request, DeliveryChallan $delivery_challan) {

        DB::beginTransaction();
        $do_id = $request->delivery_order_id;


        // delivery order's delivery date should not be greater than date

        $delivery_order = DeliveryOrder::find($do_id);
        // if(strtotime($delivery_order->dispatch_date) < strtotime($request->date)) {
        //     return response()->json("Selected Delivery order is expired. Please select a different Delivery order", 422);
        // }

        if($delivery_challan->am_approval_status == "approved" || $delivery_challan->am_approval_status == 'rejected') {
            return response()->json("Delivery Challan has been approved/rejected and cannot be updated.", 400);
        }

        try {

            $arrival_location_csv = $request->arrival_location_csv;
            $storage_location_csv = $request->storage_location_csv;

            // Calculate total qty
            $total_qty = 0;
            if(is_array($request->qty)) {
                $total_qty = array_sum($request->qty);
            }

            // Auto calculate labour rate and amount based on matched rules
            $labour_rate = ($request->labour_rate === 'N/A' || $request->labour_rate === null) ? 0 : (float)$request->labour_rate;
            $labour_amount = $request->labour_amount ? (float)$request->labour_amount : 0;

            if ($request->labour) {
                $arrival_id = explode(',', $arrival_location_csv)[0] ?? null;
                $first_item_id = $request->item_id[0] ?? null;
                $first_bag_size = $request->bag_size[0] ?? null;

                if ($first_item_id && $first_bag_size && $arrival_id) {
                    $product = Product::find($first_item_id);
                    $category_id = $product ? $product->category_id : null;
                    
                    $clean_packing = is_numeric($first_bag_size) ? $first_bag_size : trim(explode(',', (string)$first_bag_size)[0]);
                    
                    $bag_packing = \App\Models\BagPacking::select("id")
                        ->where(function($q) use ($clean_packing) {
                            $q->where("name", $clean_packing . " kg")
                              ->orWhere("name", $clean_packing . "KG")
                              ->orWhere("name", "like", $clean_packing . "%");
                        })
                        ->where(function($q) {
                            $q->where("status", 1)->orWhere("status", 'active');
                        })
                        ->first();
                    
                    if ($category_id && $bag_packing) {
                        $rateObj = \App\Models\Master\LabourRate::where("category_id", $category_id)
                            ->where("factory_id", $arrival_id)
                            ->where("bag_packing_id", $bag_packing->id)
                            ->where(function($q) {
                                $q->where("status", 1)->orWhere("status", 'active');
                            })
                            ->first();
                            
                        if ($rateObj) {
                            $labour_rate = $rateObj->rate;
                        }
                    }
                }
                
                if ($labour_rate > 0) {
                    $labour_amount = $total_qty * $labour_rate;
                }
            }

            // Auto calculate transporter amount based on Logistics
            $transporter_amount = $request->transporter_amount ? (float)$request->transporter_amount : 0;
            
            if ($request->transporter) {
                $transporter_rate = 0;
                $delivery_order = DeliveryOrder::find($do_id);
                
                if ($delivery_order && $delivery_order->so_id) {
                    $logistics = \App\Models\Sales\Logistics::where('type', 'sale_order')
                        ->where('sale_order_id', $delivery_order->so_id)
                        ->first();
                        
                    if ($logistics) {
                        $logisticsItem = \App\Models\Sales\LogisticsItem::where('logistics_id', $logistics->id)
                            ->where('transporter_id', $request->transporter)
                            ->first();
                            
                        if ($logisticsItem) {
                            $transporter_rate = (float)$logisticsItem->rate;
                        }
                    }
                }
                
                if ($transporter_rate > 0) {
                    $transporter_amount = $total_qty * $transporter_rate;
                }
            }

            $delivery_challan->update([
                "customer_id" => $request->customer_id,
                "reference_number" => $request->reference_number,
                // "location_id" => $request->locations,
                // "arrival_id" => $request->arrival_locations,
                "dispatch_date" => $request->date,
                "dc_no" => $request->dc_no,
                "sauda_type" => $request->sauda_type,
                "labour_status" => $request->labour_status ?? $delivery_challan->labour_status,
                "company_id" => $request->company_id,
                "labour" => $request->labour,
                "labour_amount" => $labour_amount,
                "transporter" => $request->transporter,
                "transporter_amount" => $transporter_amount,
                "inhouse-weighbridge" => $request->weighbridge,
                "weighbridge-amount" => $request->weighbridge_amount,
                "remarks" => $request->remarks,
                "arrival_id" => $arrival_location_csv,
                "section_id" => $storage_location_csv,
                'labour_rate' => $labour_rate,
                "created_by_id" => auth()->user()->id,
                "am_approval_status" => "pending",
                "am_change_made" => 1
            ]);

            $do_ids = explode(',', $do_id);
            $syncData = [];

            foreach ($do_ids as $id) {
                if (empty($id)) continue;
                
                // Calculate total qty for this DO from the grid items
                $total_qty_for_do = 0;
                if ($request->has('do_data_id')) {
                    foreach ($request->do_data_id as $index => $do_data_id) {
                        $do_data = \App\Models\Sales\DeliveryOrderData::find($do_data_id);
                        if ($do_data && $do_data->delivery_order_id == $id) {
                            $total_qty_for_do += $request->qty[$index];
                        }
                    }
                } else {
                    $total_qty_for_do = $request->qty[0] ?? 0;
                }

                $syncData[$id] = [
                    'qty' => $total_qty_for_do,
                ];
            }

            $delivery_challan->delivery_order()->sync($syncData);
            $delivery_challan->delivery_challan_data()->delete();

            $createdItems = [];
            foreach($request->item_id as $index => $item) {
                $dcData = $delivery_challan->delivery_challan_data()->create([
                    "item_id" => $request->item_id[$index],
                    "qty" => $request->qty[$index],
                    "rate" => $request->rate[$index],
                    "brand_id" => $request->brand_id[$index],
                    "no_of_bags" => $request->no_of_bags[$index],
                    "bag_size" => $request->bag_size[$index],
                    "description" => $request->desc[$index] ?? "",
                    "truck_no" => $request->truck_no[$index],
                    "container_number" => $request->container_number[$index],
                    "ticket_id" => $request->ticket_id[$index],
                    "do_data_id" => $request->do_data_id[$index],
                    "bag_type" => $request->bag_type[$index]
                ]);
                $createdItems[] = $dcData;
            }

            DB::commit();
        } catch(\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }

        return response()->json(["Delivery Challan has been created"]);

    }

    public function edit(DeliveryChallan $delivery_challan) {
        $delivery_challan->load("delivery_order.delivery_order_data", "delivery_challan_data");
        $customers = Customer::all();
        $delivery_orders = $delivery_challan->delivery_order;
        $locationIds = $delivery_orders->pluck('location_id')->filter()->unique();

        $arrivalLocationIds = $delivery_orders->pluck('arrival_location_id')->filter()->unique();
        
        $sectionIds = $delivery_orders->pluck('sub_arrival_location_id')->filter()->unique();

        $locations = CompanyLocation::whereIn('id', $locationIds)->get();
        $arrivalLocations = ArrivalLocation::whereIn('id', explode(",", $delivery_challan->arrival_id))->get();
        $sections = ArrivalSubLocation::whereIn('id', explode(",", $delivery_challan->section_id))->get();

        return view("management.sales.delivery-challan.edit", [
            "customers" => $customers,
            "delivery_orders" => $delivery_orders,
            "delivery_challan" => $delivery_challan,
            "locations" => $locations,
            "arrivalLocations" => $arrivalLocations,
            "sections" => $sections,
            "locationIds" => $locationIds,
            "arrivalLocationIds" => $arrivalLocationIds,
            "sectionIds" => $sectionIds,
            "transporters" => \App\Models\Master\Transporter::all(),
        ]);
    }

    public function view(DeliveryChallan $delivery_challan) {
        $delivery_challan->load("delivery_order.delivery_order_data");
        $payment_terms = PaymentTerm::all();
        $customers = Customer::all();
        $items = Product::all();
        $pay_types = PayType::select('name', 'id')->where('status', 'active')->get();

        $delivery_orders = $delivery_challan->delivery_order;

        $locationIds = $delivery_orders->pluck('location_id')->filter()->unique();


        $arrivalLocationIds = $delivery_orders->pluck('arrival_location_id')->filter()->unique();
        
        $sectionIds = $delivery_orders->pluck('sub_arrival_location_id')->filter()->unique();

        $locations = CompanyLocation::whereIn('id', $locationIds)->get();
        $arrivalLocations = ArrivalLocation::whereIn('id', explode(",", $delivery_challan->arrival_id))->get();
        $sections = ArrivalSubLocation::whereIn('id', explode(",", $delivery_challan->section_id))->get();

        return view("management.sales.delivery-challan.view", [
            "customers" => $customers,
            "delivery_orders" => $delivery_orders,
            "delivery_challan" => $delivery_challan,
            "locations" => $locations,
            "arrivalLocations" => $arrivalLocations,
            "sections" => $sections,
            "locationIds" => $locationIds,
            "arrivalLocationIds" => $arrivalLocationIds,
            "sectionIds" => $sectionIds,
        ]);
    }

    public function getList(Request $request) {
        $perPage = $request->get('per_page', 25);

        // Eager load the inquiry + all its items + related product
        $delivery_challans = DeliveryChallan::with(['delivery_challan_data.loadingProgramItem.acceptedDispatchQc'])
            // Filter by DO No
            ->when($request->filled('do_id_for_filter') && $request->do_id_for_filter != 'all', function ($q) use ($request) {
                $q->whereHas('delivery_order', function ($sq) use ($request) {
                    $sq->where('delivery_order_id', $request->do_id_for_filter);
                });
            })
            // Filter by Customer
            ->when($request->filled('customer_id_for_filter') && $request->customer_id_for_filter != 'all', function ($q) use ($request) {
                $q->where('customer_id', $request->customer_id_for_filter);
            })
            // Filter by Item (through delivery_challan_data relationship)
            ->when($request->filled('item_id_for_filter') && $request->item_id_for_filter != 'all', function ($q) use ($request) {
                $q->whereHas('delivery_challan_data', function ($sq) use ($request) {
                    $sq->where('item_id', $request->item_id_for_filter);
                });
            })
            // Filter by Date Range
            ->when($request->filled('date_range_for_filter'), function ($q) use ($request) {
                $dates = explode(' - ', $request->date_range_for_filter);
                if (count($dates) == 2) {
                    $q->whereBetween('dispatch_date', [trim($dates[0]), trim($dates[1])]);
                }
            })
            // Filter by Status
            ->when($request->filled('status_for_filter') && $request->status_for_filter != 'all', function ($q) use ($request) {
                $q->where('am_approval_status', $request->status_for_filter);
            })
            // Custom Search
            ->when($request->filled('search_for_filter'), function ($q) use ($request) {
                $searchTerm = '%' . strtolower($request->search_for_filter) . '%';
                return $q->where(function ($sq) use ($searchTerm) {
                    $sq->whereRaw('LOWER(`dc_no`) LIKE ?', [$searchTerm])
                       ->orWhereHas('delivery_challan_data', function ($q) use ($searchTerm) {
                            $q->whereRaw('CAST(`qty` AS CHAR) LIKE ?', [$searchTerm])
                                ->orWhereRaw('CAST(`rate` AS CHAR) LIKE ?', [$searchTerm])
                                ->orWhereRaw('CAST(`qty` * `rate` AS CHAR) LIKE ?', [$searchTerm]);
                        });
                });
            })
            ->latest()
            ->paginate($perPage);

        $groupedData = [];

        foreach ($delivery_challans as $delivery_challan) {
            $so_no = $delivery_challan->dc_no;
            $items = $delivery_challan->delivery_challan_data;

            if ($items->isEmpty()) {
                continue;
            }

            $itemRows = [];
            foreach ($items as $itemData) {
                $itemRows[] = [
                    'item_data' => $itemData,
                    'accepted_qc_id' => $itemData->loadingProgramItem->acceptedDispatchQc->id ?? null,
                ];
            }

            $groupedData[] = [
                'sale_order' => $delivery_challan,
                'so_no' => $so_no,
                'created_by_id' => $delivery_challan->created_by_id,
                'delivery_date' => $delivery_challan->delivery_date,
                'id' => $delivery_challan->id,
                'customer_id' => $delivery_challan->customer_id,
                'status' => $delivery_challan->am_approval_status,
                'created_at' => $delivery_challan->created_at,
                'customer' => $delivery_challan->customer,
                'rowspan' => count($itemRows),
                'items' => $itemRows,
             ];
        }

        return view('management.sales.delivery-challan.getList', [
            'DeliveryChallans' => $delivery_challans,           // for pagination
            'groupedDeliveryChallans' => $groupedData,  // our grouped data
        ]);
    }

    public function getNumber(Request $request, $locationId = null, $contractDate = null)
    {

        $date = Carbon::parse($contractDate ?? $request->contract_date)->format('Y-m-d');

        $prefix = 'DC-'.Carbon::parse($contractDate ?? $request->contract_date)->format('Y-m-d');

        $latestContract = DeliveryChallan::where('dc_no', 'like', "$prefix-%")
            ->latest()
            ->first();

        $datePart = Carbon::parse($date)->format('Y-m-d');
        

        if ($latestContract) {
            $parts = explode('-', $latestContract->dc_no);
            $lastNumber = (int) end($parts);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        $dc_no = 'DC-'.$datePart.'-'.str_pad($newNumber, 3, '0', STR_PAD_LEFT);
        
        if (! $locationId && ! $contractDate) {
            return response()->json([
                'success' => true,
                'dc_no' => $dc_no,
            ]);
        }

        return $dc_no;
    }

    public function get_delivery_orders(Request $request) {
        $customer_id = $request->customer_id;

        if (!$customer_id) {
            return [];
        }

        $delivery_orders = DeliveryOrder::with("delivery_order_data")
            ->where("customer_id", $customer_id)
            ->where("am_approval_status", "approved")
            ->where(function ($q) use ($request) {
                $q->where('do_status', 'active');
                if ($request->delivery_challan_id) {
                    $q->orWhereHas('delivery_challans', function ($sq) use ($request) {
                        $sq->where('delivery_challans.id', $request->delivery_challan_id);
                    });
                }
            })
            ->whereHas('loadingPrograms.loadingProgramItems', function($query) {
                // Ticket must have a loading slip with second weighbridge
                $query->whereHas('loadingSlip.secondWeighbridge')
                    // AND ticket must NOT be used in any delivery challan
                    ->whereDoesntHave('delivery_challan_data');
            })
            ->get();

       
        $data = [];

        foreach($delivery_orders as $delivery_order) {
            // Get arrival location names for comma-separated IDs
            $arrivalNames = [];
            if ($delivery_order->arrival_location_id) {
                $arrivalIds = explode(',', $delivery_order->arrival_location_id);
                $arrivalNames = ArrivalLocation::whereIn('id', $arrivalIds)->pluck('name', 'id')->toArray();
            }

            // Get section names for comma-separated IDs
            $sectionNames = [];
            if ($delivery_order->sub_arrival_location_id) {
                $sectionIds = explode(',', $delivery_order->sub_arrival_location_id);
                $sectionNames = ArrivalSubLocation::whereIn('id', $sectionIds)->pluck('name', 'id')->toArray();
            }

            $data[] = [
                "id" => $delivery_order->id,
                "text" => $delivery_order->reference_no,
                "location_id" => $delivery_order->location_id,
                "arrival_location_id" => $delivery_order->arrival_location_id,
                "sub_arrival_location_id" => $delivery_order->sub_arrival_location_id,
                "location_name" => get_location_name_by_id($delivery_order->location_id),
                "arrival_names" => $arrivalNames, // Array of id => name
                "section_names" => $sectionNames, // Array of id => name
            ];
        }

        return $data;
    }

    public function getItems(Request $request) {
        $delivery_order_ids = $request->delivery_order_ids;
        $delivery_orders = DeliveryOrder::with("delivery_order_data")->whereIn("id", $delivery_order_ids)->get();
        $items = Product::select("id", "name")->get();

        $delivery_orders = $delivery_orders->map(function($delivery_order) {
            $delivery_challan = $delivery_order->delivery_challans;
            $spent = $delivery_challan->sum("pivot.qty");
            $delivery_order->spent = $spent;
            return $delivery_order;
        });


        // return view("management.sales.delivery-challan.getItem", compact("delivery_orders", "items"));
    }


    public function getItemsByTickets(Request $request) {
        $ticket_id = $request->ticket_id;
        $loading_programs = LoadingProgramItem::with([
            "loadingProgram.deliveryOrder.delivery_order_data",
            "loadingSlip.secondWeighbridge"
        ])->where("id", $ticket_id)->get();
        $items = Product::select("id", "name")->get();

        return view("management.sales.delivery-challan.getItem", compact("loading_programs", "items"));
    }

    public function getTickets(Request $request) {
        $delivery_order_ids = $request->delivery_order_ids;
        $delivery_challan_id = $request->delivery_challan_id; // For edit mode - include tickets from this DC

        if (empty($delivery_order_ids)) {
            return response()->json(['tickets' => []]);
        }

        // Get tickets (loading program items) that belong to selected DOs and have second weighbridges
        // First get all loading program items that belong to selected DOs
        $query = LoadingProgramItem::with([
                'loadingProgram.deliveryOrder',
                'dispatchQc'
            ])
            ->whereHas("dispatchQc")
            ->whereHas('loadingProgram', function($q) use ($delivery_order_ids) {
                $q->whereIn('delivery_order_id', $delivery_order_ids);
            });

        // Exclude tickets that are already used in other delivery challans (but include tickets from current DC being edited)
        if ($delivery_challan_id) {
            $query->where(function($q) use ($delivery_challan_id) {
                $q->whereDoesntHave("delivery_challan_data")
                  ->orWhereHas("delivery_challan_data", function($subQ) use ($delivery_challan_id) {
                      $subQ->where("delivery_challan_id", $delivery_challan_id);
                  });
            });
        } else {
            $query->whereDoesntHave("delivery_challan_data");
        }

        $allTickets = $query->get();

        // Filter to only include tickets that have second weighbridges
        $tickets = $allTickets->filter(function($ticket) {
            return $ticket->loadingSlip && $ticket->loadingSlip->secondWeighbridge;
        })->map(function($ticket) {
            return [
                'id' => $ticket->id,
                'text' => $ticket->transaction_number . ' -- ' . $ticket->truck_number
            ];
        });

        // Debug: Log the count and details
        \Log::info('Delivery Challan getTickets called', [
            'delivery_order_ids' => $delivery_order_ids,
            'delivery_challan_id' => $delivery_challan_id,
            'all_tickets_found' => $allTickets->count(),
            'filtered_tickets_with_second_weighbridge' => $tickets->count(),
            'tickets' => $tickets->toArray()
        ]);

        return response()->json(['tickets' => $tickets]);
    }

    /**
     * Get tickets with accepted Dispatch QC for initial selection in Delivery Challan
     */
    public function getTicketsWithDispatchQc(Request $request) {
        $delivery_challan_id = $request->delivery_challan_id;

        $query = LoadingProgramItem::with([
                'loadingProgram.deliveryOrder.customer',
                'loadingProgram.deliveryOrder',
                'loadingProgram.saleOrder',
                'dispatchQc',
                'arrivalLocation',
                'subArrivalLocation',
                'loadingSlip.secondWeighbridge'
            ])
            ->whereHas("loadingSlip.secondWeighbridge");

        if ($delivery_challan_id) {
            $query->where(function($q) use ($delivery_challan_id) {
                $q->whereDoesntHave('delivery_challan_data')
                  ->orWhereHas('delivery_challan_data', function($subQ) use ($delivery_challan_id) {
                      $subQ->where('delivery_challan_id', $delivery_challan_id);
                  });
            });
        } else {
            $query->whereDoesntHave('delivery_challan_data');
        }

        $tickets = $query->get()
            ->map(function($ticket) {
                return [
                    'id' => $ticket->id,
                    'text' => $ticket->transaction_number . ' -- ' . $ticket->truck_number,
                    'transaction_number' => $ticket->transaction_number,
                    'truck_number' => $ticket->truck_number
                ];
            });

        return response()->json(['tickets' => $tickets]);
    }

    /**
     * Get ticket data for auto-filling Delivery Challan form
     */
    public function getTicketDataForDC(Request $request) {
        $ticket_id = $request->ticket_id;

        if (!$ticket_id) {
            return response()->json(['error' => 'No ticket selected'], 400);
        }

        $ticket = LoadingProgramItem::with([
            'loadingProgram.deliveryOrder.customer',
            'loadingProgram.deliveryOrder',
            'loadingProgram.saleOrder',
            'loadingProgram',
            'dispatchQc',
            'arrivalLocation',
            'subArrivalLocation',
            'loadingSlip.secondWeighbridge',
            'transporter'
        ])->findOrFail($ticket_id);

        $loadingSlip = \App\Models\Sales\LoadingSlip::where("loading_program_item_id", $ticket_id)->first();

        if (!$loadingSlip) {
            return response()->json(['error' => 'Loading slip not found for this ticket'], 404);
        }

        $deliveryOrder = $loadingSlip->deliveryOrder;
        if (!$deliveryOrder) {
            return response()->json(['error' => 'Delivery order not found for this loading slip'], 404);
        }

        $loadingProgram = $ticket->loadingProgram;
        if (!$loadingProgram) {
            return response()->json(['error' => 'Loading program not found for this ticket'], 404);
        }
        
        // Get location names from loading program (for company locations)
        $companyLocationIds = $loadingProgram->company_locations ?? [];

        // Get location names
        $companyLocations = [];
        if (!empty($companyLocationIds)) {
            $companyLocations = CompanyLocation::whereIn('id', $companyLocationIds)
                ->get()
                ->map(fn($loc) => ['id' => $loc->id, 'text' => $loc->name])
                ->toArray();
        }

        

        // Use the ticket's own arrival location (Factory) and sub arrival location (Gala)
        $arrivalLocations = [];
        $arrivalLocationIds = [];
        if ($ticket->arrival_location_id) {
            $arrivalLocationIds = [$ticket->arrival_location_id];
            $arrivalLoc = $ticket->arrivalLocation;
            if ($arrivalLoc) {
                $arrivalLocations = [['id' => $arrivalLoc->id, 'text' => $arrivalLoc->name]];
            }
        }

        $packing = $ticket->packing;
        // Handle comma-separated values to find a single numeric packing for bag lookup
        $clean_packing = is_numeric($packing) ? $packing : trim(explode(',', (string)$packing)[0]);

        $bag_packing = \App\Models\BagPacking::select("id")
                                    ->where(function($q) use ($clean_packing) {
                                        $q->where("name", $clean_packing . " kg")
                                          ->orWhere("name", $clean_packing . "KG")
                                          ->orWhere("name", "like", $clean_packing . "%");
                                    })
                                    ->where(function($q) {
                                        $q->where("status", 1)->orWhere("status", 'active');
                                    })
                                    ->first();

        $arrival_location_id = $ticket->arrival_location_id;
        $delivery_order_id = $ticket->delivery_order_id ?? ($deliveryOrder ? $deliveryOrder->id : null);
        $delivery_order = $delivery_order_id ? DeliveryOrder::find($delivery_order_id) : $deliveryOrder;
        
        $category_id = null;
        if ($delivery_order && $delivery_order->delivery_order_data->isNotEmpty()) {
            $product = Product::find($delivery_order->delivery_order_data[0]->item_id);
            $category_id = $product ? $product->category_id : null;
        }

        $labour_rate = null;
        if ($category_id && $arrival_location_id && $bag_packing) {
            $labour_rate = \App\Models\Master\LabourRate::select("id", "rate")
                                        ->where("category_id", $category_id)
                                        ->where("factory_id", $arrival_location_id)
                                        ->where("bag_packing_id", $bag_packing->id)
                                        ->where(function($q) {
                                            $q->where("status", 1)->orWhere("status", 'active');
                                        })
                                        ->first();
        }
       
        $subArrivalLocations = [];
        $subArrivalLocationIds = [];
        if ($ticket->sub_arrival_location_id) {
            $subArrivalLocationIds = [$ticket->sub_arrival_location_id];
            $subArrivalLoc = $ticket->subArrivalLocation;
            if ($subArrivalLoc) {
                $subArrivalLocations = [['id' => $subArrivalLoc->id, 'text' => $subArrivalLoc->name]];
            }
        }


        // Get loading slip labour
        $loadingSlipLabour = $loadingSlip?->labour ?? null;

        $deliveryOrders = collect();
        if ($ticket->deliveryOrders->isNotEmpty()) {
            $deliveryOrders = $ticket->deliveryOrders;
        } elseif ($deliveryOrder) {
            $deliveryOrders->push($deliveryOrder);
        }

        $dos = [];
        foreach ($deliveryOrders as $d) {
            $dos[] = [
                'id' => $d->id,
                'reference_no' => $d->reference_no,
                'sauda_type' => strtolower($d->sauda_type ?? ''),
                'remarks' => $d->remarks ?? '',
                'transporter_used' => strtolower($d->salesOrder->transporter_used ?? ''),
            ];
        }

        $data = [
            'success' => true,
            'rate' => $labour_rate ? $labour_rate->rate : "N/A",
            'ticket' => [
                'id' => $ticket->id,
                'transaction_number' => $ticket->transaction_number,
                'truck_number' => $ticket->truck_number,
            ],
            'delivery_orders' => $dos,
            'delivery_order' => [
                'id' => $deliveryOrder->id,
                'reference_no' => $deliveryOrder->reference_no,
                'sauda_type' => strtolower($deliveryOrder->sauda_type ?? ''),
                'remarks' => $deliveryOrder->remarks ?? '',
                'transporter_used' => strtolower($deliveryOrder->salesOrder->transporter_used ?? ''),
            ],
            'customer' => [
                'id' => $deliveryOrder->customer->id ?? null,
                'name' => $deliveryOrder->customer->name ?? 'N/A',
            ],
            'locations' => [
                'company_locations' => $companyLocations,
                'company_location_ids' => $companyLocationIds,
                'arrival_locations' => $arrivalLocations,
                'arrival_location_ids' => $arrivalLocationIds,
                'sub_arrival_locations' => $subArrivalLocations,
                'sub_arrival_location_ids' => $subArrivalLocationIds,
            ],
            'loading_slip_labour' => $loadingSlipLabour,
            'is_labour_editable' => (strtolower($deliveryOrder->sauda_type ?? '') == 'x-mill' || strtolower($deliveryOrder->sauda_type ?? '') == 'xmill'),
            'transporter' => [
                'id' => $ticket->transporter_id,
                'name' => $ticket->transporter->name ?? 'N/A'
            ]
        ];

        return response()->json($data);
    }
}
