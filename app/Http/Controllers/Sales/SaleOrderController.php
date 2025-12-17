<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sales\SalesOrderRequest;
use App\Models\BagType;
use App\Models\Master\ArrivalLocation;
use App\Models\Master\ArrivalSubLocation;
use App\Models\Master\Customer;
use App\Models\Master\PayType;
use App\Models\PaymentTerm;
use App\Models\Product;
use App\Models\Sales\SalesInquiry;
use App\Models\Sales\SalesOrder;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;

class SaleOrderController extends Controller
{
    public function index()
    {
        return view('management.sales.orders.index');
    }

    public function create()
    {
        $payment_terms = PaymentTerm::all();
        $customers = Customer::all();
        $inquiries = SalesInquiry::where('am_approval_status', 'approved')
            ->whereDoesntHave('sale_order')
            ->select('id', 'inquiry_no', 'contact_person')
            ->get();
        $items = Product::all();
        $pay_types = PayType::select('id', 'name')->where('status', 'active')->get();
        $bag_types = BagType::select('id', 'name')->where('status', 1)->get();
        $arrivalLocations = ArrivalLocation::select('id', 'name', 'company_location_id')->where('status', 'active')->get();
        $arrivalSubLocations = ArrivalSubLocation::select('id', 'name', 'arrival_location_id')->where('status', 'active')->get();

        return view('management.sales.orders.create', compact('payment_terms', 'customers', 'inquiries', 'items', 'pay_types', 'bag_types', 'arrivalLocations', 'arrivalSubLocations'));
    }

    public function edit(int $id)
    {
        $sale_order = SalesOrder::with(['locations', 'factories', 'sections', 'sales_order_data', 'pay_type', 'sales_order_data.sale_inquiry_data'])->find($id);
        $payment_terms = PaymentTerm::all();
        $customers = Customer::all();
        $inquiries = SalesInquiry::all();
        $items = Product::all();
        $pay_types = PayType::select('id', 'name')->where('status', 'active')->get();
        $bag_types = BagType::select('id', 'name')->where('status', 1)->get();
        $arrivalLocations = ArrivalLocation::select('id', 'name', 'company_location_id')->where('status', 'active')->get();
        $arrivalSubLocations = ArrivalSubLocation::select('id', 'name', 'arrival_location_id')->where('status', 'active')->get();

        return view('management.sales.orders.edit', compact('payment_terms', 'customers', 'inquiries', 'items', 'sale_order', 'pay_types', 'bag_types', 'arrivalLocations', 'arrivalSubLocations'));
    }

    public function view(Request $request, int $id)
    {
        $sale_order = SalesOrder::with('sales_order_data', 'locations', 'factories', 'sections', 'sales_order_data.sale_inquiry_data', 'pay_type', 'sale_inquiry')->find($id);
        $payment_terms = PaymentTerm::all();
        $customers = Customer::all();
        $inquiries = SalesInquiry::all();
        $items = Product::all();
        $arrivalLocations = ArrivalLocation::select('id', 'name', 'company_location_id')->where('status', 'active')->get();
        $arrivalSubLocations = ArrivalSubLocation::select('id', 'name', 'arrival_location_id')->where('status', 'active')->get();

        return view('management.sales.orders.view', compact('payment_terms', 'customers', 'inquiries', 'items', 'sale_order', 'arrivalLocations', 'arrivalSubLocations'));
    }

    public function store(SalesOrderRequest $request)
    {
        $locations = $request->locations ?? [];
        $factoryIds = $request->arrival_location_id ?? [];
        $sectionIds = $request->arrival_sub_location_id ?? [];
        $payload = $request->validated();
        $payload['arrival_location_id'] = $factoryIds[0] ?? null;
        $payload['arrival_sub_location_id'] = $sectionIds[0] ?? null;
        $payload['created_by'] = auth()->user()->id;
        $payload["remarks"] = !$request->remarks ? '' : $request->remarks;
        
        DB::beginTransaction();
        try {
            $sales_order = SalesOrder::create($payload);

            foreach ($locations as $location) {
                $sales_order->locations()->create([
                    'location_id' => $location,
                ]);
            }
            foreach ($factoryIds as $factoryId) {
                $sales_order->factories()->create([
                    'arrival_location_id' => $factoryId,
                ]);
            }
            foreach ($sectionIds as $sectionId) {
                $sales_order->sections()->create([
                    'arrival_sub_location_id' => $sectionId,
                ]);
            }
            foreach ($request->item_id as $index => $item) {
                $sales_order->sales_order_data()->create([
                    'item_id' => $request->item_id[$index],
                    'qty' => $request->qty[$index],
                    'rate' => $request->rate[$index],
                    'pack_size' => $request->pack_size[$index],
                    'brand_id' => $request->brand_id[$index],
                    'bag_type' => $request->bag_type[$index],
                    'bag_size' => $request->bag_size[$index],
                    'no_of_bags' => $request->no_of_bags[$index],
                    'description' => $request->description[$index] ?? ""
                ]);
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }

        return response()->json(['data' => 'Sale Order has been created']);
    }

    public function update(SalesOrderRequest $request, int $id)
    {
        DB::beginTransaction();
        try {
            $sales_order = SalesOrder::find($id);

            $factoryIds = $request->arrival_location_id ?? [];
            $sectionIds = $request->arrival_sub_location_id ?? [];
            $payload = $request->validated();
            $payload['arrival_location_id'] = $factoryIds[0] ?? null;
            $payload['arrival_sub_location_id'] = $sectionIds[0] ?? null;
            $payload['am_approval_status'] = 'pending';
            $payload['am_change_made'] = 1;
            $payload["remarks"] = !$request->remarks ? '' : $request->remarks;

            // Update parent sale order data
            $sales_order->update($payload);

            // Update locations
            if ($request->has('locations')) {
                $sales_order->locations()->delete();
                foreach ($request->locations as $location) {
                    $sales_order->locations()->create([
                        'location_id' => $location,
                    ]);
                }
            }
            // Update factories
            $sales_order->factories()->delete();
            foreach ($factoryIds as $factoryId) {
                $sales_order->factories()->create([
                    'arrival_location_id' => $factoryId,
                ]);
            }

            // Update sections
            $sales_order->sections()->delete();
            foreach ($sectionIds as $sectionId) {
                $sales_order->sections()->create([
                    'arrival_sub_location_id' => $sectionId,
                ]);
            }

            // Update line items
            $sales_order->sales_order_data()->delete();
            foreach ($request->item_id as $index => $item) {
                $sales_order->sales_order_data()->create([
                    'item_id' => $request->item_id[$index],
                    'qty' => $request->qty[$index],
                    'rate' => $request->rate[$index],
                    'pack_size' => $request->pack_size[$index] ?? 0,
                    'brand_id' => $request->brand_id[$index],
                    'bag_type' => $request->bag_type[$index] ?? $request->bag_type_id[$index] ?? null,
                    'bag_size' => $request->bag_size[$index],
                    'no_of_bags' => $request->no_of_bags[$index],
                    'description' => $request->description[$index]
                ]);
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }

        return response()->json(['data' => 'Sale Order has been updated']);
    }

    public function destroy(int $id)
    {
        $sales_order = SalesOrder::find($id);
        $sales_order->sales_order_data()->delete();
        $sales_order->delete();

        return response()->json(['data' => 'Sale Order has been deleted']);
    }

    public function getList(Request $request)
    {
        $perPage = $request->get('per_page', 25);

        // Eager load the inquiry + all its items + related product
        $SalesOrders = SalesOrder::with(['sale_inquiry', 'sales_order_data.item.unitOfMeasure'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $searchTerm = '%' . strtolower($request->search) . '%';
                return $q->where(function ($sq) use ($searchTerm) {
                    $sq->whereRaw('LOWER(`reference_no`) LIKE ?', [$searchTerm]);
                });
            })
            ->latest()
            ->paginate($perPage);
       
        $groupedData = [];

        foreach ($SalesOrders as $SaleOrder) {
            $so_no = $SaleOrder->reference_no;
            $items = $SaleOrder->sales_order_data;

            $itemRows = [];
            if ($items->isEmpty()) {
                $itemRows[] = [
                    'item_data' => (object)['item_id' => null, 'qty' => 0, 'rate' => 0, 'description' => 'No items'],
                    'item' => (object)['name' => 'N/A', 'unitOfMeasure' => (object)['name' => '']],
                ];
            } else {
                foreach ($items as $itemData) {
                    $itemRows[] = [
                    'item_data' => $itemData,
                        'item' => $itemData->item,
                ];
                }
            }

            $groupedData[] = [
                'sale_order' => $SaleOrder,
                'so_no' => $so_no,
                'created_by_id' => $SaleOrder->created_by ?? 1,
                'inquiry_no' => $SaleOrder?->sale_inquiry?->inquiry_no ?? "N/A",
                'delivery_date' => $SaleOrder->delivery_date,
                'id' => $SaleOrder->id,
                'customer_id' => $SaleOrder->customer_id,
                'status' => $SaleOrder->am_approval_status,
                'created_at' => $SaleOrder->created_at,
                'customer' => 2,
                'rowspan' => max(count($itemRows), 1),
                'items' => $itemRows,
            ];
        }

        return view('management.sales.orders.getList', [
            'SalesOrders' => $SalesOrders,           // for pagination
            'groupedSalesOrders' => $groupedData,  // our grouped data
        ]);
    }

    public function get_inquiries(Request $request)
    {
        $customer_id = $request->customer_id;

        $sale_inquiries = SalesInquiry::where('am_approval_status', 'approved')
            ->whereDoesntHave('sale_order')
            ->where('customer', $customer_id)
            ->select('inquiry_no', 'id')
            ->get();

        $data = [];

        foreach ($sale_inquiries as $sale_inquiry) {
            $data[] = [
                'text' => $sale_inquiry->inquiry_no,
                'id' => $sale_inquiry->id,
            ];
        }

        return $data;
    }

    public function get_inquiry_data(Request $request)
    {
        $inquiry_id = $request->inquiry_id;

        $items = Product::select('name', 'id')->get();
        $inquiry = SalesInquiry::with(['sales_inquiry_data', 'locations'])->where('id', $inquiry_id)->first();
        // Return inquiry details along with the items view
        if ($request->ajax() && $request->has('get_details')) {
            return response()->json([
                'required_date' => $inquiry->required_date,
                'customer_id' => $inquiry->customer,
                'contract_type' => $inquiry->contract_type,
                'locations' => $inquiry->locations->pluck('location_id')->toArray(),
                'token_money' => $inquiry->token_money,
                'contact_person' => $inquiry->contact_person,
                'arrival_location_id' => $inquiry->factories->pluck("arrival_location_id")->toArray(),
                'arrival_sub_location_id' => $inquiry->sections->pluck("arrival_sub_location_id")->toArray(),
                'arrival_locations' => $inquiry->factories->pluck("arrival_location_id")->toArray() ? [$inquiry->factories->pluck("arrival_location_id")->toArray()] : [],
                'arrival_sub_locations' => $inquiry->sections->pluck("arrival_sub_location_id")->toArray() ? [$inquiry->sections->pluck("arrival_sub_location_id")->toArray()] : [],
            ]);
        }

        return view('management.sales.orders.getItems', compact('inquiry', 'items'));
    }

    public function getNumber(Request $request, $locationId = null, $contractDate = null)
    {

        $date = Carbon::parse($contractDate ?? $request->contract_date)->format('Y-m-d');

        $prefix = 'SO-'.Carbon::parse($contractDate ?? $request->contract_date)->format('Y-m-d');

        $latestContract = SalesOrder::where('reference_no', 'like', "$prefix-%")
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

        $so_no = 'SO-'.$datePart.'-'.str_pad($newNumber, 3, '0', STR_PAD_LEFT);

        if (! $locationId && ! $contractDate) {
            return response()->json([
                'success' => true,
                'so_no' => $so_no,
            ]);
        }

        return $so_no;
    }
}
