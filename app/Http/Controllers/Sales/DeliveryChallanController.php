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
        $delivery_orders = DeliveryOrder::select("id", "reference_no")->get();

        return view("management.sales.delivery-challan.create", compact("customers", "delivery_orders"));
    }

    public function store(DeliveryChallanRequest $request) {
        DB::beginTransaction();
        $do_ids = $request->do_no;
        try {
            $delivery_challan = DeliveryChallan::create([
                "customer_id" => $request->customer_id,
                "reference_number" => $request->reference_number,
                // "location_id" => $request->locations,
                // "arrival_id" => $request->arrival_locations,
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

            $delivery_challan->delivery_order()->sync($do_ids);

            // Store delivery challan data items
            $createdItems = [];
            foreach($request->item_id as $index => $item) {
                $dcData = $delivery_challan->delivery_challan_data()->create([
                    "item_id" => $request->item_id[$index],
                    "qty" => $request->qty[$index],
                    "rate" => $request->rate[$index],
                    "brand_id" => $request->brand_id[$index],
                    "no_of_bags" => $request->no_of_bags[$index],
                    "bag_size" => $request->bag_size[$index],
                    "description" => $request->desc[$index],
                    "truck_no" => $request->truck_no[$index],
                    "bilty_no" => $request->bilty_no[$index],
                    "do_data_id" => $request->do_data_id[$index],
                    "bag_type" => $request->bag_type[$index],
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
        $do_ids = $request->do_no;
        try {

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
                "created_by_id" => auth()->user()->id
            ]);

            $delivery_challan->delivery_order()->sync($do_ids);
            $delivery_challan->delivery_challan_data()->delete();

            foreach($request->item_id as $index => $item) {
                $delivery_challan->delivery_challan_data()->create([
                    "item_id" => $request->item_id[$index],
                    "qty" => $request->qty[$index],
                    "rate" => $request->rate[$index],
                    "brand_id" => $request->brand_id[$index],
                    "no_of_bags" => $request->no_of_bags[$index],
                    "bag_size" => $request->bag_size[$index],
                    "description" => $request->desc[$index],
                    "truck_no" => $request->truck_no[$index],
                    "bilty_no" => $request->bilty_no[$index],
                    "do_data_id" => $request->do_data_id[$index],
                    "bag_type" => $request->bag_type[$index]
                ]);
            }

            DB::commit();
        } catch(\Exception $e) {
            dd($e);
        }

        return response()->json(["Delivery Challan has been created"]);

    }

    public function edit(DeliveryChallan $delivery_challan) {
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
        $arrivalLocations = ArrivalLocation::whereIn('id', $arrivalLocationIds)->get();
        $sections = ArrivalSubLocation::whereIn('id', $sectionIds)->get();

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
        $arrivalLocations = ArrivalLocation::whereIn('id', $arrivalLocationIds)->get();
        $sections = ArrivalSubLocation::whereIn('id', $sectionIds)->get();

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
            $parts = explode('-', $latestContract->reference_no);
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
            ->get()
            ->filter(function ($deliveryOrder) {
                foreach ($deliveryOrder->delivery_order_data as $data) {
                    if (delivery_challan_balance($data->id) > 0) {
                        return true;
                    }
                }
                return false;
            });

        $data = [];

        foreach($delivery_orders as $delivery_order) {
            $data[] = [
                "id" => $delivery_order->id,
                "text" => $delivery_order->reference_no,
                "location_id" => $delivery_order->location_id,
                "arrival_location_id" => $delivery_order->arrival_location_id,
                "sub_arrival_location_id" => $delivery_order->sub_arrival_location_id,
                "location_name" => get_location_name_by_id($delivery_order->location_id),
                "arrival_name" => get_arrival_name_by_id($delivery_order->arrival_location_id),
                "section_name" => get_storage_name_by_id($delivery_order->sub_arrival_location_id),
            ];
        }

        return $data;
    }

    public function getItems(Request $request) {
        $delivery_order_ids = $request->delivery_order_ids;
        $delivery_orders = DeliveryOrder::with("delivery_order_data")->whereIn("id", $delivery_order_ids)->get();
        $items = Product::select("id", "name")->get();

        return view("management.sales.delivery-challan.getItem", compact("delivery_orders", "items"));
    }
}
