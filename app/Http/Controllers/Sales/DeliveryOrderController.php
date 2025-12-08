<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sales\DeliveryOrderRequest;
use App\Models\BagType;
use App\Models\Master\ArrivalLocation;
use App\Models\Master\ArrivalSubLocation;
use App\Models\Master\Customer;
use App\Models\Master\PayType;
use App\Models\PaymentTerm;
use App\Models\Product;
use App\Models\ReceiptVoucher;
use App\Models\Sales\DeliveryOrder;
use App\Models\Sales\SalesOrder;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DeliveryOrderController extends Controller
{
    public function index()
    {
        return view('management.sales.delivery-order.index');
    }

    public function view(int $id)
    {

        $payment_terms = PaymentTerm::select('id', 'desc')->where('status', 'active')->get();
        $customers = Customer::all();
        $delivery_order = DeliveryOrder::with('delivery_order_data', 'receipt_vouchers', 'withheld_receipt_voucher')->find($id);
        $sales_orders = SalesOrder::where('customer_id', $delivery_order->customer_id)->where('am_approval_status', 'approved')->get();
        $receipt_vouchers = $delivery_order->receipt_vouchers;

        return view('management.sales.delivery-order.view', compact('payment_terms', 'delivery_order', 'customers', 'sales_orders', 'receipt_vouchers'));
    }

    public function create()
    {
        $sale_orders = SalesOrder::select('reference_no', 'id')->where('am_approval_status', 'approved')->get();
        $payment_terms = PaymentTerm::all();
        $customers = Customer::all();
        $items = Product::all();
        $pay_types = PayType::select('name', 'id')->where('status', 'active')->get();

        return view('management.sales.delivery-order.create', compact('payment_terms', 'customers', 'items', 'sale_orders', 'pay_types'));
    }

    public function store(DeliveryOrderRequest $request)
    {
        DB::beginTransaction();

        $receipt_vouchers = ReceiptVoucher::whereIn('id', $request?->receipt_vouchers ?? [])->get();
        // $locations = $request->locations;

        try {
            $delivery_order = DeliveryOrder::create([
                'customer_id' => $request->customer_id,
                'so_id' => $request->sale_order_id,
                'advance_amount' => $request->advance_amount ?? 0,
                'withhold_amount' => $request->withhold_amount ?? 0,
                'withhold_for_rv_id' => $request->withhold_for_rv,
                'dispatch_date' => $request->dispatch_date,
                'reference_no' => $request->reference_no,
                'payment_term_id' => $request->payment_term_id ?? (PaymentTerm::first())->id,
                'sauda_type' => $request->sauda_type,
                'location_id' => $request->location_id,
                'arrival_location_id' => $request->arrival_id,
                'sub_arrival_location_id' => $request->storage_id ?? (ArrivalSubLocation::first())->id,
                'line_desc' => $request->line_desc,
                'company_id' => $request->company_id
            ]);

            // foreach ($locations as $location) {
            //     $delivery_order->locations()->create([
            //         'location_id' => $location,
            //     ]);
            // }

            // Run it if user intends to apply withhold amounts

            $salesOrder = SalesOrder::find($request->sale_order_id);
            if ($salesOrder->payment_term_id == 8) {
                foreach ($receipt_vouchers as $rv) {
                    $last_withheld_amount = $rv->withhold_amount;

                    // if ($rv->id == $request->withhold_for_rv) {
                    //     $rv->withhold_amount = $request->withhold_amount;
                    //     $rv->remaining_amount = $rv->total_amount - $rv->withhold_amount;
                    // } else {
                    //     $rv->withhold_amount = 0;
                    //     $rv->remaining_amount = $rv->total_amount - $rv->withhold_amount;
                    // }

                    // $rv->save();

                    $withhold_amount = 0;
                    $spent_amount = $rv->delivery_orders?->sum(fn ($do) => $do->pivot->amount) ?? 0;
                    $remaining_amount = $rv->total_amount - $spent_amount;

                    if ($rv->id == $request->withhold_for_rv) {
                        $withhold_amount = $request->withhold_amount;
                    }

                    $syncData[$rv->id] = [
                        'amount' => $remaining_amount - $withhold_amount,
                        'withhold_amount' => $rv->withhold_amount,
                        'last_withhold_amount' => $last_withheld_amount,
                    ];

                    $rv->save();
                }

                $delivery_order->receipt_vouchers()->syncWithoutDetaching($syncData);
            }

            foreach ($request->item_id as $key => $item) {
                $delivery_order->delivery_order_data()->create([
                    'item_id' => $request->item_id[$key],
                    'qty' => $request->qty[$key],
                    'rate' => $request->rate[$key],
                    'brand_id' => $request->brand_id[$key],
                    'bag_type' => $request->bag_type[$key],
                    'bag_size' => $request->bag_size[$key],
                    'no_of_bags' => $request->no_of_bags[$key],
                    'pack_size' => $request->pack_size[$key],
                    'so_data_id' => $request->so_data_id[$key],
                    "description" => $request->desc[$key]
                ]);
            }

            DB::commit();

        } catch (\Exception $e) {

            DB::rollBack();
            dd($e);

            return response()->json(['error' => 'Something bad happened']);

        }

        return response()->json(['success' => 'Delivery Order has been created']);
    }

    public function getList(Request $request)
    {
        $perPage = $request->get('per_page', 25);

        // Eager load the inquiry + all its items + related product
        $delivery_orders = DeliveryOrder::latest()
            ->paginate($perPage);

        $groupedData = [];

        foreach ($delivery_orders as $delivery_order) {
            $so_no = $delivery_order->reference_no;
            $items = $delivery_order->delivery_order_data;

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
                'sale_order' => $delivery_order,
                'so_no' => $so_no,
                'created_by_id' => 1,
                'delivery_date' => $delivery_order->delivery_date,
                'id' => $delivery_order->id,
                'customer_id' => $delivery_order->customer_id,
                'status' => $delivery_order->am_approval_status,
                'created_at' => $delivery_order->created_at,
                'customer' => $delivery_order->customer,
                'rowspan' => count($itemRows),
                'items' => $itemRows,
            ];
        }

        return view('management.sales.delivery-order.getList', [
            'DeliveryOrders' => $delivery_orders,           // for pagination
            'groupedDeliveryOrders' => $groupedData,  // our grouped data
        ]);
    }

    public function getNumber(Request $request, $locationId = null, $contractDate = null)
    {

        $date = Carbon::parse($contractDate ?? $request->contract_date)->format('Y-m-d');

        $prefix = 'DO-'.Carbon::parse($contractDate ?? $request->contract_date)->format('Y-m-d');

        $latestContract = DeliveryOrder::where('reference_no', 'like', "$prefix-%")
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

        $so_no = 'DO-'.$datePart.'-'.str_pad($newNumber, 3, '0', STR_PAD_LEFT);

        if (! $locationId && ! $contractDate) {
            return response()->json([
                'success' => true,
                'so_no' => $so_no,
            ]);
        }

        return $so_no;
    }

    public function getSo(Request $request)
    {
        $customer_id = $request->customer_id;

        $saleOrders = SalesOrder::select('reference_no', 'id', 'payment_term_id')
            ->where('am_approval_status', 'approved')
            ->where('customer_id', $customer_id)
            ->get()
            ->filter(function ($saleOrder) {
                foreach ($saleOrder->sales_order_data as $data) {
                    $balance = delivery_order_balance($data->id);
                    if ($balance > 0) {
                        return true;
                    }
                }

                return false;
            });

        $data = [];

        foreach ($saleOrders as $saleOrder) {
            $data[] = [
                'text' => $saleOrder->reference_no,
                'id' => $saleOrder->id,
                'type' => $saleOrder->payment_term_id,
            ];
        }

        return $data;
    }

    public function getDetails(Request $request)
    {
        $so_id = $request->so_id;

        $sale_order = SalesOrder::with('sales_order_data', 'delivery_order_transactions', 'locations')
            ->find($so_id);

        $data = [
            'unused_amount' => $sale_order->sales_order_data()->sum(DB::raw('qty * rate')) - $sale_order->delivery_order_transactions()->sum(DB::raw('advance_amount')),
            'so_amount' => $sale_order->sales_order_data()->sum(DB::raw('qty * rate')),
            'amount_received' => $sale_order->delivery_order_transactions()->sum(DB::raw('advance_amount')),
            'sauda_type' => strtolower($sale_order->sauda_type),
            'payment_term_id' => $sale_order->payment_term_id,
            'locations' => $sale_order->locations()->pluck('location_id')->toArray(),
        ];

        return $data;
    }

    public function get_so_items(Request $request)
    {
        $so_id = $request->so_id;

        $items = Product::select('id', 'name')->get();
        $sale_order = SalesOrder::with('delivery_order_transactions', 'locations')
            ->find($so_id);

        $bag_types = BagType::select('id', 'name')->get();

        return view('management.sales.delivery-order.getItem', compact('sale_order', 'items', 'bag_types'));
    }

    public function get_receipt_vouchers(Request $request)
    {
        $customer_id = $request->customer_id;

        $receipt_vouchers = ReceiptVoucher::with('delivery_orders')->select('remaining_amount', 'id', 'unique_no', 'withhold_amount', 'total_amount', 'ref_bill_no')
            ->where('customer_id', $customer_id)
            ->get()
            ->map(function ($receipt_voucher) {
                $sum = $receipt_voucher->delivery_orders->sum(fn ($do) => $do->pivot->amount);
                $receipt_voucher->spent_amount = $sum;

                return $receipt_voucher;
            });

        $data = [];

        foreach ($receipt_vouchers as $receipt_voucher) {
            $remaining_amount = $receipt_voucher->total_amount - $receipt_voucher['spent_amount'];

            if ($remaining_amount <= 0) {
                continue;
            }

            $data[] = [
                'id' => $receipt_voucher->id,
                'text' => "{$receipt_voucher->unique_no} ($receipt_voucher->ref_bill_no)",
                'amount' => $remaining_amount,
            ];
        }

        return $data;
    }

    public function destroy(DeliveryOrder $delivery_order)
    {

        $delivery_order->delete();

        return response()->json(['success' => 'Delivery order has been deleted!']);

    }

    public function edit(DeliveryOrder $delivery_order)
    {
        $sale_orders = SalesOrder::select('reference_no', 'id')->where('am_approval_status', 'approved')->get();
        $payment_terms = PaymentTerm::all();
        $customers = Customer::all();
        $items = Product::all();
        $bag_types = BagType::select('id', 'name')->get();

        $receipt_vouchers = ReceiptVoucher::select('total_amount', 'id', 'unique_no', 'withhold_amount')
            ->whereIn('ref_bill_no', $sale_orders->pluck('reference_no')->toArray())
            ->get()
            ->reject(function ($receipt_voucher) {
                return $receipt_voucher->withhold_amount <= 0;
            });

        return view('management.sales.delivery-order.edit', compact('payment_terms', 'customers', 'items', 'sale_orders', 'delivery_order', 'receipt_vouchers', 'bag_types'));

    }

    public function update(DeliveryOrderRequest $request, DeliveryOrder $delivery_order)
    {
        DB::beginTransaction();
        $receipt_vouchers = ReceiptVoucher::whereIn('id', $request?->receipt_vouchers ?? [])->get();
        $locations = $request->locations;

        try {
            $delivery_order->update([
                'customer_id' => $request->customer_id,
                'so_id' => $request->sale_order_id,
                'advance_amount' => $request->advance_amount ?? 0,
                'withhold_amount' => $request->withhold_amount ?? 0,
                'withhold_for_rv_id' => $request->withhold_for_rv,
                'dispatch_date' => $request->dispatch_date,
                'reference_no' => $request->reference_no,
                'payment_term_id' => $request->payment_term_id,
                'sauda_type' => $request->sauda_type,
                'line_desc' => $request->line_desc,
                'location_id' => $request->location_id,
                'arrival_location_id' => $request->arrival_id,
                'sub_arrival_location_id' => $request->storage_id,
                'company_id' => $request->company_id
            ]);

            // $delivery_order->locations()->delete();
            // foreach ($locations as $location) {
            //     $delivery_order->locations()->create([
            //         'location_id' => $location,
            //     ]);
            // }

            $delivery_order->receipt_vouchers()->detach();

            $syncData = [];

            foreach ($receipt_vouchers as $rv) {
                $last_withheld_amount = $rv->withhold_amount;

                $spent_amount = $rv->delivery_orders?->sum(fn ($do) => $do->pivot->amount) ?? 0;
                $remaining_amount = $rv->total_amount - $spent_amount;

                $withhold_amount = ($rv->id == $request->withhold_for_rv)
                    ? $request->withhold_amount
                    : 0;

                $syncData[$rv->id] = [
                    'amount' => $remaining_amount - $withhold_amount,
                    'withhold_amount' => $rv->withhold_amount,
                    'last_withhold_amount' => $last_withheld_amount,
                ];
            }

            $delivery_order->receipt_vouchers()->syncWithoutDetaching($syncData);

            // Rebuild line items
            $delivery_order->delivery_order_data()->delete();
            foreach ($request->item_id as $key => $item) {
                $delivery_order->delivery_order_data()->create([
                    'item_id' => $request->item_id[$key],
                    'qty' => $request->qty[$key],
                    'rate' => $request->rate[$key],
                    'brand_id' => $request->brand_id[$key],
                    'pack_size' => 0,
                    'no_of_bags' => $request->no_of_bags[$key],
                    'bag_size' => $request->bag_size[$key],
                    'bag_type' => $request->bag_type[$key],
                    "so_data_id" => $request->so_data_id[$key],
                    "description" => $request->desc[$key] ?? ""
                ]);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            dd($e);
        }

        return response()->json(['success' => 'Delivery Order has been updated']);
    }

    public function get_arrivals(Request $request)
    {
        $company_id = $request->location_id;

        $arrival_locations = ArrivalLocation::where('company_location_id', $company_id)->get();

        $data = [];

        foreach ($arrival_locations as $arrival_location) {
            $data[] = [
                'id' => $arrival_location->id,
                'text' => $arrival_location->name,
            ];
        }

        return $data;
    }

    public function get_storages(Request $request)
    {
        $arrival_id = $request->arrival_id;
        $subarrival_locations = ArrivalSubLocation::where('arrival_location_id', $arrival_id)->get();

        $data = [];

        foreach ($subarrival_locations as $subarrival_location) {
            $data[] = [
                'id' => $subarrival_location->id,
                'text' => $subarrival_location->name,
            ];
        }

        return $data;
    }
}
