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

class DeliveryChallanController extends Controller
{
    public function index() {
        return view('management.sales.delivery-challan.index');
    }

    public function create() {
        $payment_terms = PaymentTerm::all();
        $customers = Customer::all();
        $items = Product::all();
        $pay_types = PayType::select('name', 'id')->where('status', 'active')->get();
        $delivery_orders = DeliveryOrder::select("delivery_order.id", "delivery_order.reference_no")
            ->join('loading_programs', 'delivery_order.id', '=', 'loading_programs.delivery_order_id')
            ->join('loading_program_items', 'loading_programs.id', '=', 'loading_program_items.loading_program_id')
            ->join('loading_slips', 'loading_program_items.id', '=', 'loading_slips.loading_program_item_id')
            ->join('sales_second_weighbridges', 'loading_slips.id', '=', 'sales_second_weighbridges.loading_slip_id')
            ->distinct()
            ->get();

        return view("management.sales.delivery-challan.create", compact("customers", "delivery_orders"));
    }

    public function store(DeliveryChallanRequest $request) {
        DB::beginTransaction();
        $do_id = $request->delivery_order_id;

        // delivery order's delivery date should not be greater than date
        $delivery_order = DeliveryOrder::find($do_id);
        if(strtotime($delivery_order->dispatch_date) < strtotime($request->date)) {
            return response()->json("Selected Delivery order is expired. Please select a different Delivery order", 422);
        }
        
        
        try {
            $arrival_location_csv = $request->arrival_location_csv;
            $storage_location_csv = $request->storage_location_csv;
          
            $delivery_challan = DeliveryChallan::create([
                "customer_id" => $request->customer_id,
                "reference_number" => $request->reference_number,
                "location_id" => $request->locations[0],
                "arrival_id" => $arrival_location_csv,
                "section_id" => $storage_location_csv,
                // 'subarrival_id' => $request->storage_id,
                "dispatch_date" => $request->date,
                "dc_no" => $request->dc_no,
                "sauda_type" => $request->sauda_type,
                "company_id" => $request->company_id,
                "labour" => $request->labour,
                "labour_amount" => $request->labour_amount,
                "transporter" => $request->transporter,
                "transporter_amount" => $request->transporter_amount,
                "inhouse-weighbridge" => $request->weighbridge,
                "weighbridge-amount" => $request->weighbridge_amount,
                "remarks" => $request->remarks,
                "created_by_id" => auth()->user()->id,
            ]);

            
            

            // dd($do_ids);
            // foreach ($do_ids as $index => $id) {
            //     $syncData[$id] = [
            //         'qty' => $request->qty[$index],
            //     ];
            // }



            $syncData = [];

            $syncData[$do_id] = [
                'qty' => $request->qty[0],
            ];

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
                    "bilty_no" => $request->bilty_no[$index],
                    "do_data_id" => $request->do_data_id[$index],
                    "bag_type" => $request->bag_type[$index],
                    "ticket_id" => $request->ticket_id[$index]
                ]);
                $createdItems[] = $dcData;
            }

            // Create Receiving Request after DC data is created
            $receivingRequest = ReceivingRequest::create([
                'delivery_challan_id' => $delivery_challan->id,
                'dc_no' => $delivery_challan->dc_no,
                'dc_date' => $delivery_challan->dispatch_date,
                'truck_number' => $request->truck_no[0] ?? null,
                'bilty' => $request->bilty_no[0] ?? null,
                'labour' => $delivery_challan->labour,
                'transporter' => $delivery_challan->transporter,
                'inhouse_weighbridge' => $delivery_challan->{'inhouse-weighbridge'} ?? null,
                'labour_amount' => $delivery_challan->labour_amount ?? 0,
                'transporter_amount' => $delivery_challan->transporter_amount ?? 0,
                'inhouse_weighbridge_amount' => $delivery_challan->{'weighbridge-amount'} ?? 0,
                'company_id' => $delivery_challan->company_id,
                'created_by_id' => $delivery_challan->created_by_id,
            ]);

            // Create Receiving Request Items for each DC item
            foreach ($createdItems as $dcData) {
                $product = Product::find($dcData->item_id);
                ReceivingRequestItem::create([
                    'receiving_request_id' => $receivingRequest->id,
                    'delivery_challan_data_id' => $dcData->id,
                    'item_id' => $dcData->item_id,
                    'item_name' => $product?->name ?? 'N/A',
                    'dispatch_weight' => $dcData->qty ?? 0,
                    'receiving_weight' => 0,
                    'difference_weight' => $dcData->qty ?? 0,
                    'seller_portion' => 0,
                    'remaining_amount' => $dcData->qty ?? 0,
                ]);
            }

            DB::commit();
        } catch(\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }

        return response()->json(["Delivery Challan has been created"]);
    }

    public function destroy(DeliveryChallan $delivery_challan) {
        $delivery_challan->delete();

        return response()->json(["message" => "Delivery Challan has been deleted!"]);
    }

    public function update(DeliveryChallanRequest $request, DeliveryChallan $delivery_challan) {

        DB::beginTransaction();
        $do_id = $request->delivery_order_id;


        // delivery order's delivery date should not be greater than date

        $delivery_order = DeliveryOrder::find($do_id);
        if(strtotime($delivery_order->dispatch_date) < strtotime($request->date)) {
            return response()->json("Selected Delivery order is expired. Please select a different Delivery order", 422);
        }

        try {

            $arrival_location_csv = $request->arrival_location_csv;
            $storage_location_csv = $request->storage_location_csv;

            $delivery_challan->update([
                "customer_id" => $request->customer_id,
                "reference_number" => $request->reference_number,
                // "location_id" => $request->locations,
                // "arrival_id" => $request->arrival_locations,
                "dispatch_date" => $request->date,
                "dc_no" => $request->dc_no,
                "sauda_type" => $request->sauda_type,
                "company_id" => $request->company_id,
                "labour" => $request->labour,
                "labour_amount" => $request->labour_amount,
                "transporter" => $request->transporter,
                "transporter_amount" => $request->transporter_amount,
                "inhouse-weighbridge" => $request->weighbridge,
                "weighbridge-amount" => $request->weighbridge_amount,
                "remarks" => $request->remarks,
                "arrival_id" => $arrival_location_csv,
                "section_id" => $storage_location_csv,
                "created_by_id" => auth()->user()->id,
                "am_approval_status" => "pending",
                "am_change_made" => 1
            ]);

            $delivery_challan->delivery_order()->sync($do_id);
            $delivery_challan->delivery_challan_data()->delete();

            foreach($request->item_id as $index => $item) {
                

                // $balance = delivery_challan_balance($request->do_data_id[$index]);

                // if($request->no_of_bags[$index] > $balance) {
                //     return response()->json("Total balance is $balance. you can not exceed this balance", 422);
                // }

                $delivery_challan->delivery_challan_data()->create([
                    "item_id" => $request->item_id[$index],
                    "qty" => $request->qty[$index],
                    "rate" => $request->rate[$index],
                    "brand_id" => $request->brand_id[$index],
                    "no_of_bags" => $request->no_of_bags[$index],
                    "bag_size" => $request->bag_size[$index],
                    "description" => $request->desc[$index] ?? "",
                    "truck_no" => $request->truck_no[$index],
                    "bilty_no" => $request->bilty_no[$index],
                    "ticket_id" => $request->ticket_id[$index],
                    "do_data_id" => $request->do_data_id[$index],
                    "bag_type" => $request->bag_type[$index]
                ]);
            }

            DB::commit();
        } catch(\Exception $e) {
            dd($e->getMessage());
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
        $delivery_challans = DeliveryChallan::latest()
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
        $query = \App\Models\Sales\LoadingProgramItem::with([
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
        // Get tickets that have:
        // 1. Dispatch QC with status = 'accept'
        // 2. Are NOT already used in any delivery challan
        $tickets = LoadingProgramItem::with([
                'loadingProgram.deliveryOrder.customer',
                'loadingProgram.deliveryOrder',
                'loadingProgram.saleOrder',
                'dispatchQc',
                'arrivalLocation',
                'subArrivalLocation',
                'loadingSlip.secondWeighbridge'
            ])
            ->whereHas("loadingSlip.secondWeighbridge")
            ->whereDoesntHave('delivery_challan_data')
            ->get()
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
            'loadingSlip.secondWeighbridge'
        ])->findOrFail($ticket_id);

        $loadingSlip = \App\Models\Sales\LoadingSlip::where("loading_program_item_id", $ticket_id)->first();

        $deliveryOrder = $loadingSlip->deliveryOrder;
        $loadingProgram = $ticket->loadingProgram;
        
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
        $loadingSlipLabour = $ticket->loadingSlip?->labour ?? null;

        $data = [
            'success' => true,
            'ticket' => [
                'id' => $ticket->id,
                'transaction_number' => $ticket->transaction_number,
                'truck_number' => $ticket->truck_number,
            ],
            'delivery_order' => [
                'id' => $deliveryOrder->id,
                'reference_no' => $deliveryOrder->reference_no,
                'sauda_type' => strtolower($deliveryOrder->sauda_type ?? ''),
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
            'loading_slip_labour' => $loadingSlipLabour
        ];

        return response()->json($data);
    }
}
