<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sales\DeliveryChallanRequest;
use App\Models\Master\Customer;
use App\Models\Master\PayType;
use App\Models\PaymentTerm;
use App\Models\Product;
use App\Models\Sales\DeliveryChallan;
use App\Models\Sales\DeliveryOrder;
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
                "location_id" => $request->locations,
                "arrival_id" => $request->arrival_locations,
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
                    "bag_type" => $request->bag_type[$index],
                    
                ]);
            }



            DB::commit();
        } catch(\Exception $e) {
            dd($e);
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
                "location_id" => $request->locations,
                "arrival_id" => $request->arrival_locations,
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
        $delivery_orders = DeliveryOrder::select("id", "reference_no")->get();

        $delivery_orders = DeliveryOrder::select("id", "reference_no")->where("customer_id", $delivery_challan->customer_id)
                                            ->where("location_id", $delivery_challan->location_id)
                                            ->where("arrival_location_id", $delivery_challan->arrival_id)
                                            ->where("am_approval_status", "approved")
                                            ->get();

        return view("management.sales.delivery-challan.edit", compact("customers", "delivery_orders", "delivery_challan"));
    }

    public function view(DeliveryChallan $delivery_challan) {
        $delivery_challan->load("delivery_order.delivery_order_data");
        $payment_terms = PaymentTerm::all();
        $customers = Customer::all();
        $items = Product::all();
        $pay_types = PayType::select('name', 'id')->where('status', 'active')->get();
        $delivery_orders = DeliveryOrder::select("id", "reference_no")->get();

        $delivery_orders = DeliveryOrder::select("id", "reference_no")->where("customer_id", $delivery_challan->customer_id)
                                            ->where("location_id", $delivery_challan->location_id)
                                            ->where("arrival_location_id", $delivery_challan->arrival_id)
                                            ->where("am_approval_status", "approved")
                                            ->get();

        return view("management.sales.delivery-challan.view", compact("customers", "delivery_orders", "delivery_challan"));
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
        $location_id = $request->company_location_id;
        $arrival_location_id = $request->arrival_location_id;

        $delivery_orders = DeliveryOrder::with("delivery_order_data")
                                            ->where("customer_id", $customer_id)
                                            ->where("location_id", $location_id)
                                            ->where("arrival_location_id", $arrival_location_id)
                                            ->where("am_approval_status", "approved")
                                            ->get()
                                            ->filter(function ($saleOrder) {
                                                foreach ($saleOrder->delivery_order_data as $data) {
                                                    $balance = delivery_challan_balance($data->id);
                                                    if ($balance > 0) {
                                                        return true;
                                                    }
                                                }

                                                return false;
                                            });

        $data = [];

        foreach($delivery_orders as $delivery_order) {
            $data[] = [
                "id" => $delivery_order->id,
                "text" => $delivery_order->reference_no
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
