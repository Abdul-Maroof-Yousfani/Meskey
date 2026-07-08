<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sales\SalesOrderRequest;
use App\Models\BagPacking;
use App\Models\BagType;
use App\Models\Master\ArrivalLocation;
use App\Models\Master\ArrivalSubLocation;
use App\Models\Master\CompanyLocation;
use App\Models\Master\Customer;
use App\Models\Master\PayType;
use App\Models\PaymentTerm;
use App\Models\Product;
use App\Models\Master\Broker;
use App\Models\Sales\SalesInquiry;
use App\Models\Sales\SalesOrder;
use Carbon\Carbon;
use App\Models\ReceiptVoucherItem;
use DB;
use Illuminate\Http\Request;
use App\Models\ReceiptVoucher;
use App\Models\Master\Account\Transaction;

class SaleOrderController extends Controller
{
    public function index()
    {
        $customerIds = SalesOrder::distinct()->pluck('customer_id')->filter();
        $customers = Customer::whereIn('id', $customerIds)->get();

        $itemIds = \App\Models\Sales\SalesOrderData::distinct()->pluck('item_id')->filter();
        $items = Product::whereIn('id', $itemIds)->get();

        $locationIds = \App\Models\Procurement\Store\Location::where('locationable_type', SalesOrder::class)
            ->distinct()->pluck('location_id')->filter();
        $companyLocations = CompanyLocation::whereIn('id', $locationIds)->get();

        $inquiryIds = SalesOrder::distinct()->pluck('inquiry_id')->filter();
        $saleInquiries = SalesInquiry::whereIn('id', $inquiryIds)->select('id', 'inquiry_no')->get();

        return view('management.sales.orders.index', compact('customers', 'items', 'companyLocations', 'saleInquiries'));
    }

    public function create()
    {
        $payment_terms = PaymentTerm::all();
        $customers = Customer::where("type", "local")->get();
        $inquiries = SalesInquiry::where('am_approval_status', 'approved')
            ->whereDoesntHave('sale_order', function ($query) {
                $query->whereNot("am_approval_status", "rejected");
            })
            ->select('id', 'inquiry_no', 'contact_person')
            ->get();
        $items = Product::all();
        $pay_types = PayType::select('id', 'name')->where('status', 'active')->get();
        $bag_types = BagType::select('id', 'name')->where('status', 1)->get();
        $arrivalLocations = ArrivalLocation::with("companyLocation")->select('id', 'name', 'company_location_id')->where('status', 'active')->get();
        $arrivalSubLocations = ArrivalSubLocation::with("arrivalLocation")->select('id', 'name', 'arrival_location_id')->where('status', 'active')->get();

        $packings = BagPacking::all()->filter(function ($packing) {
            return preg_match('/\d+/', $packing->name);
        })->map(function ($packing) {
            preg_match('/\d+/', $packing->name, $matches);
            return $matches[0];
        })->unique()->sort()->values();

        $brokers = Broker::where('status', 'active')
            ->where('is_for_sales', 1)
            ->get();
        return view('management.sales.orders.create', compact('payment_terms', 'customers', 'inquiries', 'items', 'pay_types', 'bag_types', 'arrivalLocations', 'arrivalSubLocations', 'packings', 'brokers'));
    }

    public function edit(int $id)
    {
        $sale_order = SalesOrder::with(['locations', 'factories', 'sections', 'sales_order_data', 'pay_type', 'sales_order_data.sale_inquiry_data'])->find($id);
        $payment_terms = PaymentTerm::all();
        $customers = Customer::where("type", "local")->get();
        $inquiries = SalesInquiry::all();
        $items = Product::all();
        $pay_types = PayType::select('id', 'name')->where('status', 'active')->get();
        $bag_types = BagType::select('id', 'name')->where('status', 1)->get();
        $arrivalLocations = ArrivalLocation::with("companyLocation")->select('id', 'name', 'company_location_id')->where('status', 'active')->get();
        $arrivalSubLocations = ArrivalSubLocation::with("arrivalLocation")->select('id', 'name', 'arrival_location_id')->where('status', 'active')->get();

        $packings = BagPacking::all()->filter(function ($packing) {
            return preg_match('/\d+/', $packing->name);
        })->map(function ($packing) {
            preg_match('/\d+/', $packing->name, $matches);
            return $matches[0];
        })->unique()->sort()->values();

        $latestLog = $sale_order->approvalLogs()->with(['user', 'role'])->latest()->first();
        $brokers = Broker::where('status', 'active')
            ->where('is_for_sales', 1)
            ->get();
        return view('management.sales.orders.edit', compact('payment_terms', 'customers', 'inquiries', 'items', 'sale_order', 'pay_types', 'bag_types', 'arrivalLocations', 'arrivalSubLocations', 'packings', 'brokers', 'latestLog'));
    }

    public function view(Request $request, int $id)
    {
        $sale_order = SalesOrder::with('sales_order_data', 'locations', 'factories', 'sections', 'sales_order_data.sale_inquiry_data', 'pay_type', 'sale_inquiry')->find($id);
        $payment_terms = PaymentTerm::all();
        $customers = Customer::where("type", "local")->get();
        $inquiries = SalesInquiry::all();
        $items = Product::all();
        $arrivalLocations = ArrivalLocation::with("companyLocation")->select('id', 'name', 'company_location_id')->where('status', 'active')->get();
        $arrivalSubLocations = ArrivalSubLocation::with("arrivalLocation")->select('id', 'name', 'arrival_location_id')->where('status', 'active')->get();

        $packings = BagPacking::all()->filter(function ($packing) {
            return preg_match('/\d+/', $packing->name);
        })->map(function ($packing) {
            preg_match('/\d+/', $packing->name, $matches);
            return $matches[0];
        })->unique()->sort()->values();

        $brokers = Broker::where('status', 'active')
            ->where('is_for_sales', 1)
            ->get();
        $latestLog = $sale_order->approvalLogs()->with(['user', 'role'])->latest()->first();

        return view('management.sales.orders.view', compact('payment_terms', 'customers', 'inquiries', 'items', 'sale_order', 'arrivalLocations', 'arrivalSubLocations', 'packings', 'brokers', 'latestLog'));
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
        $payload['parent_user_id'] = auth()->user()->parent_user_id ?? auth()->user()->id;
        $payload["remarks"] = !$request->remarks ? '' : $request->remarks;
        $payload["reference_no"] = self::getNumber($request, null, $request->order_date);
        $payload["contact_person"] = !$request->contact_person ? '' : $request->contact_person;
        $payload["so_reference_no"] = !$request->so_reference_no ? '' : $request->so_reference_no;
        $payload["transporter_used"] = !$request->transporter_used ? 'no' : $request->transporter_used;
        $payload["payment_term_id"] = !$request->payment_term_id ? PaymentTerm::first()->id : $request->payment_term_id;
        $payload["commission_per_kg"] = $request->commission_per_kg ?? 0;
        $payload["receipt_voucher_item_ids"] = $request->receipt_voucher_item_ids ?? null;
        $payload["payment_on_kaanta"] = $request->has('payment_on_kaanta') ? 1 : 0;

        $soTotal = array_sum($request->amount ?? []);
        if ($request->pay_type_id == 10 && $request->receipt_voucher_item_ids) { // Advanced

            $rvTotal = ReceiptVoucherItem::whereIn('id', $request->receipt_voucher_item_ids)->sum('amount');
            if ($soTotal > $rvTotal) {
                return response()->json(['error' => "The total Sale Order amount ($soTotal) exceeds the selected Receipt Voucher total ($rvTotal)."], 400);
            }
        }


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
                    'minimum_qty' => $request->minimum_qty[$index],
                    'rate' => $request->rate[$index],
                    'pack_size' => $request->pack_size[$index],
                    'brand_id' => $request->brand_id[$index],
                    'bag_type' => $request->bag_type[$index],
                    'bag_size' => $request->bag_size[$index],
                    'no_of_bags' => $request->no_of_bags[$index],
                    'description' => $request->description[$index] ?? "",
                    "rate_per_mond" => $request->rate_per_mond[$index]
                ]);
            }

            // Sync Unallocated Receipt Vouchers
            if ($request->has('receipt_voucher_item_ids')) {
                ReceiptVoucherItem::whereIn('id', $request->receipt_voucher_item_ids)->update([
                    'reference_type' => 'sale_order',
                    'reference_id' => $sales_order->id,
                ]);

                // Update associated transactions to have the SO reference no
                Transaction::whereIn('receipt_voucher_item_id', $request->receipt_voucher_item_ids)
                    ->update([
                        'voucher_no' => DB::raw('payment_against')
                    ]);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }

        return response()->json(['data' => 'Sale Order has been created']);
    }

    public function generateRvNumber($voucher_type, $rv_date)
    {


        $prefix = $voucher_type === 'bank_payment_voucher' ? 'BRV' : 'CRV';
        $prefixForAccounts = $voucher_type === 'bank_payment_voucher' ? '1-1' : '1-4';

        $accounts = Account::whereHas('parent', function ($query) use ($prefixForAccounts) {
            $query->where('hierarchy_path', $prefixForAccounts);
        })->get();

        $rvDate = $rv_date ? date('m-d-Y', strtotime($rv_date)) : date('m-d-Y');
        $datePrefix = $prefix . '-' . $rvDate . '-';
        $uniqueNo = generateUniqueNumberByDate('receipt_vouchers', $datePrefix, null, 'unique_no', false);

        return response()->json([
            'success' => true,
            'rv_number' => $uniqueNo,
            'accounts' => $accounts
        ]);
    }

    public function getUnallocatedReceiptVouchers(Request $request)
    {
        $customer_id = $request->customer_id;
        $sale_order_id = $request->sale_order_id;

        if (!$customer_id) {
            return response()->json([]);
        }

        $receiptVoucherItems = ReceiptVoucherItem::where("customer_id", $customer_id)
            ->where(function ($query) use ($sale_order_id) {
                $query->where("reference_type", "not-allocated");
                if ($sale_order_id) {
                    $query->orWhere(function ($q) use ($sale_order_id) {
                        $q->where("reference_type", "sale_order")
                            ->where("reference_id", $sale_order_id);
                    });
                }
            })
            ->get();

        return response()->json($receiptVoucherItems);
    }

    public function update(SalesOrderRequest $request, int $id)
    {
        DB::beginTransaction();
        try {
            $sales_order = SalesOrder::find($id);

            if ($sales_order->am_approval_status == "approved" || $sales_order->am_approval_status == 'rejected') {
                return response()->json("Sales Order has been approved/rejected and cannot be updated.", 400);
            }



            $soTotal = array_sum($request->amount ?? []);
            if ($request->pay_type_id == 10) { // Advanced
                if (!is_null($request->receipt_voucher_item_ids)) {
                    $rvTotal = ReceiptVoucherItem::whereIn('id', $request->receipt_voucher_item_ids)->sum('amount');
                    if ($soTotal > $rvTotal) {
                        return response()->json(['error' => "The total Sale Order amount ($soTotal) exceeds the selected Receipt Voucher total ($rvTotal)."], 400);
                    }
                }
            }


            $factoryIds = $request->arrival_location_id ?? [];
            $sectionIds = $request->arrival_sub_location_id ?? [];
            $payload = $request->validated();
            $payload['arrival_location_id'] = $factoryIds[0] ?? null;
            $payload['arrival_sub_location_id'] = $sectionIds[0] ?? null;
            $payload['am_approval_status'] = 'pending';
            $payload['am_change_made'] = 1;
            // $payload['parent_user_id'] = auth()->user()->parent_user_id ?? auth()->user()->id;
            $payload["remarks"] = !$request->remarks ? '' : $request->remarks;
            $payload["contact_person"] = !$request->contact_person ? '' : $request->contact_person;
            $payload["so_reference_no"] = !$request->so_reference_no ? '' : $request->so_reference_no;
            $payload["transporter_used"] = !$request->transporter_used ? 'no' : $request->transporter_used;
            $payload["payment_term_id"] = !$request->payment_term_id ? PaymentTerm::first()->id : $request->payment_term_id;
            $payload["commission_per_kg"] = $request->commission_per_kg ?? 0;
            $payload["receipt_voucher_item_ids"] = $request->receipt_voucher_item_ids;
            $payload["payment_on_kaanta"] = $request->has('payment_on_kaanta') ? 1 : 0;

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
                    'minimum_qty' => $request->minimum_qty[$index],
                    'rate' => $request->rate[$index],
                    'pack_size' => $request->pack_size[$index] ?? 0,
                    'brand_id' => $request->brand_id[$index],
                    'bag_type' => $request->bag_type[$index] ?? $request->bag_type_id[$index] ?? null,
                    'bag_size' => $request->bag_size[$index],
                    'no_of_bags' => $request->no_of_bags[$index],
                    'description' => $request->description[$index] ?? "",
                    "rate_per_mond" => $request->rate_per_mond[$index]
                ]);
            }

            // Sync Unallocated Receipt Vouchers
            // 1. Unset old ones linked to this SO
            $oldItemIds = ReceiptVoucherItem::where('reference_type', 'sale_order')
                ->where('reference_id', $id)
                ->pluck('id');

            Transaction::whereIn('receipt_voucher_item_id', $oldItemIds)
                ->update(['voucher_no' => '-']);

            ReceiptVoucherItem::where('reference_type', 'sale_order')
                ->where('reference_id', $id)
                ->update([
                    'reference_type' => 'not-allocated',
                    'reference_id' => null
                ]);

            // 2. Set new ones
            if ($request->has('receipt_voucher_item_ids')) {
                ReceiptVoucherItem::whereIn('id', $request->receipt_voucher_item_ids)->update([
                    'reference_type' => 'sale_order',
                    'reference_id' => $id
                ]);

                // Update associated transactions to have the SO reference no

                Transaction::whereIn('receipt_voucher_item_id', $request->receipt_voucher_item_ids)
                    ->update([
                        'voucher_no' => DB::raw('payment_against')
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
        if ($sales_order->am_approval_status == "approved" || $sales_order->am_approval_status == 'rejected') {
            return response()->json("Sales Order has been approved/rejected and cannot be updated.", 400);
        }
        $sales_order->sales_order_data()->delete();
        $sales_order->delete();


        return response()->json(['data' => 'Sale Order has been deleted']);
    }

    public function getList(Request $request)
    {
        $perPage = $request->get('per_page', 25);

        // Eager load the inquiry + all its items + related product
        $SalesOrders = SalesOrder::with(['sale_inquiry', 'sales_order_data.item.unitOfMeasure', 'locations.companyLocation', 'broker'])
            ->when($request->filled('search_for_filter'), function ($q) use ($request) {
                $searchTerm = '%' . strtolower($request->search_for_filter) . '%';
                return $q->where(function ($sq) use ($searchTerm) {
                    $sq->whereRaw('LOWER(`reference_no`) LIKE ?', [$searchTerm])
                        ->orWhereHas('sales_order_data', function ($q) use ($searchTerm) {
                            $q->whereRaw('CAST(`qty` AS CHAR) LIKE ?', [$searchTerm])
                                ->orWhereRaw('CAST(`rate` AS CHAR) LIKE ?', [$searchTerm])
                                ->orWhereRaw('CAST(`qty` * `rate` AS CHAR) LIKE ?', [$searchTerm]);
                        });
                });
            })
            // Filter by SO No
            ->when($request->filled('so_no_for_filter'), function ($q) use ($request) {
                $q->where('reference_no', 'like', '%' . $request->so_no_for_filter . '%');
            })
            // Filter by Sale Inquiry No
            ->when($request->filled('inquiry_id_for_filter') && $request->inquiry_id_for_filter != 'all', function ($q) use ($request) {
                $q->where('inquiry_id', $request->inquiry_id_for_filter);
            })
            // Filter by Customer
            ->when($request->filled('customer_id_for_filter') && $request->customer_id_for_filter != 'all', function ($q) use ($request) {
                $q->where('customer_id', $request->customer_id_for_filter);
            })
            // Filter by Location (via morph relationship)
            ->when($request->filled('location_id_for_filter') && $request->location_id_for_filter != 'all', function ($q) use ($request) {
                $q->whereHas('locations', function ($sq) use ($request) {
                    $sq->where('location_id', $request->location_id_for_filter);
                });
            })
            // Filter by Item (through sales_order_data relationship)
            ->when($request->filled('item_id_for_filter') && $request->item_id_for_filter != 'all', function ($q) use ($request) {
                $q->whereHas('sales_order_data', function ($sq) use ($request) {
                    $sq->where('item_id', $request->item_id_for_filter);
                });
            })
            // Filter by Created At Date Range
            ->when($request->filled('created_at_for_filter'), function ($q) use ($request) {
                $dates = explode(' - ', $request->created_at_for_filter);
                if (count($dates) == 2) {
                    $q->whereBetween('created_at', [trim($dates[0]) . ' 00:00:00', trim($dates[1]) . ' 23:59:59']);
                }
            })
            // Filter by Delivery Date Range (delivery_date)
            ->when($request->filled('delivery_date_for_filter'), function ($q) use ($request) {
                $dates = explode(' - ', $request->delivery_date_for_filter);
                if (count($dates) == 2) {
                    $q->whereBetween('delivery_date', [trim($dates[0]), trim($dates[1])]);
                }
            })
            // Filter by Status
            ->when($request->filled('status_for_filter') && $request->status_for_filter != 'all', function ($q) use ($request) {
                $q->where('am_approval_status', $request->status_for_filter);
            })
            ->orderBy("reference_no", "desc")
            ->latest()
            ->paginate($perPage);

        $groupedData = [];

        foreach ($SalesOrders as $SaleOrder) {
            $so_no = $SaleOrder->reference_no;
            $items = $SaleOrder->sales_order_data;

            $itemRows = [];
            if ($items->isEmpty()) {
                $itemRows[] = [
                    'item_data' => (object) ['item_id' => null, 'qty' => 0, 'rate' => 0, 'description' => 'No items'],
                    'item' => (object) ['name' => 'N/A', 'unitOfMeasure' => (object) ['name' => '']],
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
            ->whereDoesntHave('sale_order', function ($query) {
                $query->whereNot("am_approval_status", "rejected");
            })
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


        $company_locations = [];
        foreach ($inquiry->locations as $location) {
            $location = CompanyLocation::select("id", 'name')->where("status", "active")->find($location->location_id);
            $company_locations[] = [
                "text" => $location->name,
                "id" => $location->id
            ];
        }

        $factory_locations = [];

        foreach ($inquiry->factories as $factory) {
            $arrival = ArrivalLocation::select("id", "name")->where("status", "active")->find($factory->arrival_location_id);
            $factory_locations[] = [
                "text" => $arrival->name,
                "id" => $arrival->id
            ];
        }

        $section_locations = [];
        foreach ($inquiry->sections as $section) {
            $section = ArrivalSubLocation::select("id", "name")->where("status", "active")->find($section->arrival_sub_location_id);
            $section_locations[] = [
                "text" => $section->name,
                "id" => $section->id
            ];
        }

        // Return inquiry details along with the items view
        if ($request->ajax() && $request->has('get_details')) {
            return response()->json([
                'required_date' => $inquiry->required_date,
                'customer_id' => $inquiry->customer,
                'contract_type' => $inquiry->contract_type,
                'locations' => $company_locations,
                'token_money' => $inquiry->token_money,
                'contact_person' => $inquiry->contact_person,
                'arrival_location_id' => $inquiry->factories->pluck("arrival_location_id")->toArray(),
                'arrival_sub_location_id' => $inquiry->sections->pluck("arrival_sub_location_id")->toArray(),
                'arrival_locations' => $factory_locations,
                'arrival_sub_locations' => $section_locations,
                'remarks' => $inquiry->remarks
            ]);
        }

        $packings = BagPacking::all()->filter(function ($packing) {
            return preg_match('/\d+/', $packing->name);
        })->map(function ($packing) {
            preg_match('/\d+/', $packing->name, $matches);
            return $matches[0];
        })->unique()->sort()->values();
        return view('management.sales.orders.getItems', compact('inquiry', 'items', 'packings'));
    }

    public function getNumber(Request $request, $locationId = null, $contractDate = null)
    {

        $date = Carbon::parse($contractDate ?? $request->contract_date)->format('Y-m-d');

        $prefix = 'SO-' . Carbon::parse($contractDate ?? $request->contract_date)->format('Y-m-d');

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

        $so_no = 'SO-' . $datePart . '-' . str_pad($newNumber, 3, '0', STR_PAD_LEFT);

        if (!$locationId && !$contractDate) {
            return response()->json([
                'success' => true,
                'so_no' => $so_no,
            ]);
        }

        return $so_no;
    }
}
