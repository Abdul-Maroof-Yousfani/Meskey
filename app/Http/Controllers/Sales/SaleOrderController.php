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
use App\Models\CustomerAdvance;
use App\Models\CustomerAdvanceAdjustment;
use DB;
use Illuminate\Http\Request;
use App\Models\ReceiptVoucher;
use App\Models\Master\Account\Transaction;
use App\Services\AuditLogService;
use Illuminate\Support\Facades\Cache;
use App\Models\User;

class SaleOrderController extends Controller
{
    protected function getSellerDropdownData($currentParentUserId = null)
    {
        $sellerUsers = User::permission('Seller')->with('parent')->get();
        $sellers = collect();
        $sellerError = null;
        $authUser = auth()->user();
        $defaultSellerId = null;

        foreach ($sellerUsers as $u) {
            if ($u->parent_user_id && $u->parent) {
                if ($u->parent->can('Seller')) {
                    $sellers->put($u->parent->id, $u->parent);
                } else {
                    if (!$sellerError) {
                        $sellerError = "Parent user ({$u->parent->name}) doen't have 'Seller' permission.";
                    }
                }
            }
        }

        // Pre-select seller for current logged-in user
        if ($authUser && $authUser->parent_user_id && $sellers->has($authUser->parent_user_id)) {
            $defaultSellerId = $authUser->parent_user_id;
        } elseif ($authUser && $sellers->has($authUser->id)) {
            $defaultSellerId = $authUser->id;
        } elseif ($sellers->count() === 1) {
            $defaultSellerId = $sellers->keys()->first();
        }

        if ($currentParentUserId && !$sellers->has($currentParentUserId)) {
            $existingParent = User::find($currentParentUserId);
            if ($existingParent) {
                if ($existingParent->can('Seller')) {
                    $sellers->put($existingParent->id, $existingParent);
                } else {
                    if (!$sellerError) {
                        $sellerError = "Parent user ({$existingParent->name}) doen't have 'Seller' permission.";
                    }
                }
            }
        }

        return [
            'sellers' => $sellers->values(),
            'sellerError' => $sellerError,
            'defaultSellerId' => $defaultSellerId,
        ];
    }

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
        $sellerData = $this->getSellerDropdownData();
        $sellers = $sellerData['sellers'];
        $sellerError = $sellerData['sellerError'];
        $defaultSellerId = $sellerData['defaultSellerId'];

        return view('management.sales.orders.create', compact('payment_terms', 'customers', 'inquiries', 'items', 'pay_types', 'bag_types', 'arrivalLocations', 'arrivalSubLocations', 'packings', 'brokers', 'sellers', 'sellerError', 'defaultSellerId'));
    }

    public function edit(int $id)
    {
        $sale_order = SalesOrder::with(['locations', 'factories', 'sections', 'sales_order_data', 'pay_type', 'sales_order_data.sale_inquiry_data', 'parent_user', 'broker'])->find($id);
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
        $sellerData = $this->getSellerDropdownData($sale_order->parent_user_id);
        $sellers = $sellerData['sellers'];
        $sellerError = $sellerData['sellerError'];
        $balanceQuantity = $this->calculateBalanceQuantity($sale_order);
        $isClosed = $sale_order->isClosed();
        return view('management.sales.orders.edit', compact('payment_terms', 'customers', 'inquiries', 'items', 'sale_order', 'pay_types', 'bag_types', 'arrivalLocations', 'arrivalSubLocations', 'packings', 'brokers', 'latestLog', 'balanceQuantity', 'isClosed', 'sellers', 'sellerError'));
    }

    public function view(Request $request, int $id)
    {
        $sale_order = SalesOrder::with('sales_order_data', 'locations', 'factories', 'sections', 'sales_order_data.sale_inquiry_data', 'pay_type', 'sale_inquiry', 'parent_user', 'broker')->find($id);
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

    public function getDoStats(Request $request, int $id)
    {
        $sale_order = SalesOrder::with([
            'delivery_orders' => function ($q) {
                $q->with([
                    'delivery_order_data.item',
                    'delivery_challans' => function ($dcQ) {
                        $dcQ->where('delivery_challans.am_approval_status', '!=', 'rejected');
                    }
                ]);
            },
            'sales_order_data.item',
            'customer'
        ])->findOrFail($id);

        $doStats = [];
        $totalDoQty = 0;
        $totalDcQty = 0;
        $totalRemainingQty = 0;

        $hasRealDos = $sale_order->delivery_orders->where('is_auto_created_from_so', '!=', 1)->count() > 0;

        foreach ($sale_order->delivery_orders as $do) {
            $doQty = (float) $do->delivery_order_data->sum('qty');
            $doDataIds = $do->delivery_order_data->pluck('id')->toArray();

            $dcQty = (float) \App\Models\Sales\DeliveryChallanData::whereIn('do_data_id', $doDataIds)
                ->whereHas('deliveryChallan', function ($q) {
                    $q->where('am_approval_status', '!=', 'rejected');
                })
                ->sum('qty');

            if ($dcQty <= 0) {
                $dcQty = (float) $do->delivery_challans()
                    ->where('delivery_challans.am_approval_status', '!=', 'rejected')
                    ->sum('delivery_challan_delivery_order.qty');
            }

            $remainingQty = max(0, $doQty - $dcQty);

            // Don't count Dummy DOs in totals when real DOs exist (avoids double-counting)
            $isDummy = (bool) $do->is_auto_created_from_so;
            if (!$isDummy || !$hasRealDos) {
                $totalDoQty += $doQty;
                $totalDcQty += $dcQty;
                $totalRemainingQty += $remainingQty;
            }

            // DC breakdown details for this DO
            $dcBreakdown = [];
            foreach ($do->delivery_challans as $dc) {
                $dcItemQty = (float) \App\Models\Sales\DeliveryChallanData::where('delivery_challan_id', $dc->id)
                    ->whereIn('do_data_id', $doDataIds)
                    ->sum('qty');
                if ($dcItemQty <= 0) {
                    $dcItemQty = (float) ($dc->pivot->qty ?? 0);
                }
                $dcBreakdown[] = [
                    'id' => $dc->id,
                    'reference_number' => $dc->reference_number ?? ('DC #' . $dc->id),
                    'dispatch_date' => $dc->dispatch_date,
                    'status' => $dc->am_approval_status,
                    'qty' => $dcItemQty,
                ];
            }

            $itemNames = $do->delivery_order_data->map(function ($itemData) {
                return $itemData->item->name ?? 'N/A';
            })->unique()->filter()->implode(', ');

            $doStats[] = [
                'id' => $do->id,
                'reference_no' => $do->reference_no,
                'is_dummy' => (bool) $do->is_auto_created_from_so,
                'do_date' => $do->do_date ?? $do->created_at,
                'dispatch_date' => $do->dispatch_date,
                'status' => $do->am_approval_status ?? 'pending',
                'items' => $itemNames ?: 'N/A',
                'do_qty' => $doQty,
                'dc_qty' => $dcQty,
                'remaining_qty' => $remainingQty,
                'dcs' => $dcBreakdown,
            ];
        }

        // SO's original total qty — this is the base for progress/remaining on SO level
        $soTotalQty = (float) $sale_order->sales_order_data->sum('qty');

        // Remaining at SO level = SO qty - total actually dispatched via DCs
        $soRemainingQty = max(0, $soTotalQty - $totalDcQty);

        return view('management.sales.orders.doStatsModal', compact(
            'sale_order',
            'doStats',
            'soTotalQty',
            'totalDoQty',
            'totalDcQty',
            'totalRemainingQty',
            'soRemainingQty'
        ));
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

        // Seller / Sell By (parent_user_id) validation
        if ($request->filled('parent_user_id')) {
            $parentUser = User::find($request->parent_user_id);
            if (!$parentUser || !$parentUser->can('Seller')) {
                return response()->json(['error' => "Parent user doen't have 'Seller' permission."], 422);
            }
            $payload['parent_user_id'] = $request->parent_user_id;
        } else {
            $payload['parent_user_id'] = null;
        }
        $payload['seller_commission_per_kg'] = $request->seller_commission_per_kg ?? 0;

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

            // Sync Unallocated Receipt Vouchers & Customer Advances
            if ($request->has('receipt_voucher_item_ids') && is_array($request->receipt_voucher_item_ids)) {
                $rvItems = ReceiptVoucherItem::with('receiptVoucher')->whereIn('id', $request->receipt_voucher_item_ids)->get();

                foreach ($rvItems as $rvItem) {
                    $rvItem->update([
                        'reference_type' => 'sale_order',
                        'reference_id' => $sales_order->id,
                    ]);

                    // Sync corresponding CustomerAdvance if exists
                    $uniqueNo = $rvItem->receiptVoucher?->unique_no;
                    if ($uniqueNo) {
                        $adv = CustomerAdvance::where('voucher_no', $uniqueNo)
                            ->where('customer_id', $sales_order->customer_id)
                            ->first();

                        if ($adv && $adv->remaining_amount > 0) {
                            $itemAmt = (float)($rvItem->net_amount > 0 ? $rvItem->net_amount : $rvItem->amount);
                            $adjAmount = min((float)$adv->remaining_amount, $itemAmt);
                            if ($adjAmount > 0) {
                                $adv->used_amount += $adjAmount;
                                $adv->remaining_amount -= $adjAmount;
                                $adv->status = ($adv->remaining_amount <= 0.01) ? 'completed' : 'partial_payment';
                                $adv->save();

                                CustomerAdvanceAdjustment::create([
                                    'customer_advance_id' => $adv->id,
                                    'voucher_no' => $sales_order->reference_no,
                                    'amount' => $adjAmount
                                ]);
                            }
                        }
                    }
                }

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
            if (!$sales_order) {
                return response()->json(['error' => 'Sale Order not found.', 'message' => 'Sale Order not found.'], 404);
            }

            $oldDeliveryDate = $sales_order->delivery_date ? Carbon::parse($sales_order->delivery_date)->format('Y-m-d') : null;
            $newDeliveryDate = !empty($request->delivery_date) ? Carbon::parse($request->delivery_date)->format('Y-m-d') : null;
            $deliveryDateChanged = $oldDeliveryDate != $newDeliveryDate;
            $contractStatusChanged = $request->has('contract_status') && ($request->contract_status ?? null) != $sales_order->contract_status;

            // Validate contract_status reopen check against balance quantity
            if (isset($request->contract_status) && in_array($request->contract_status, ['reopen-contract-closed-by-mistake', 'reopen', 'open'])) {
                $balanceQuantity = $this->calculateBalanceQuantity($sales_order);
                if ($balanceQuantity <= 0) {
                    return response()->json([
                        'error' => 'Remaining balance quantity is 0. Contract cannot be reopened.',
                        'message' => 'Remaining balance quantity is 0. Contract cannot be reopened.'
                    ], 422);
                }
            }

            $isApprovedOrAmendment = in_array(strtolower($sales_order->am_approval_status ?? ''), ['approved', 'rejected']) || $sales_order->hasPendingDeliveryDateAmendment();

            if ($isApprovedOrAmendment) {
                // Check if any restricted fields were modified
                $otherFieldsChanged = false;

                if ($request->has('customer_id') && (int)$request->customer_id !== (int)$sales_order->customer_id) {
                    $otherFieldsChanged = true;
                }
                if ($request->has('order_date') && !empty($request->order_date)) {
                    if (Carbon::parse($request->order_date)->format('Y-m-d') !== Carbon::parse($sales_order->order_date)->format('Y-m-d')) {
                        $otherFieldsChanged = true;
                    }
                }
                if ($request->has('inquiry_id')) {
                    $reqInq = !empty($request->inquiry_id) ? (int)$request->inquiry_id : null;
                    $currInq = $sales_order->inquiry_id ? (int)$sales_order->inquiry_id : null;
                    if ($reqInq !== $currInq) {
                        $otherFieldsChanged = true;
                    }
                }
                if ($request->has('sauda_type') && !empty($request->sauda_type)) {
                    if (strtolower(trim($request->sauda_type)) !== strtolower(trim($sales_order->sauda_type ?? ''))) {
                        $otherFieldsChanged = true;
                    }
                }
                if ($request->has('transporter_used')) {
                    if (strtolower(trim($request->transporter_used)) !== strtolower(trim($sales_order->transporter_used ?? 'no'))) {
                        $otherFieldsChanged = true;
                    }
                }
                if ($request->has('pay_type_id') && !empty($request->pay_type_id)) {
                    if ((int)$request->pay_type_id !== (int)$sales_order->pay_type_id) {
                        $otherFieldsChanged = true;
                    }
                }
                if ($request->has('payment_term_id') && !empty($request->payment_term_id)) {
                    if ((int)$request->payment_term_id !== (int)$sales_order->payment_term_id) {
                        $otherFieldsChanged = true;
                    }
                }
                if ($request->has('broker_id')) {
                    $reqBroker = !empty($request->broker_id) ? (int)$request->broker_id : null;
                    $currBroker = $sales_order->broker_id ? (int)$sales_order->broker_id : null;
                    if ($reqBroker !== $currBroker) {
                        $otherFieldsChanged = true;
                    }
                }
                if ($request->has('commission_per_kg')) {
                    if (abs((float)$request->commission_per_kg - (float)($sales_order->commission_per_kg ?? 0)) > 0.001) {
                        $otherFieldsChanged = true;
                    }
                }
                if ($request->has('contact_person')) {
                    if (trim((string)$request->contact_person) !== trim((string)($sales_order->contact_person ?? ''))) {
                        $otherFieldsChanged = true;
                    }
                }
                if ($request->has('remarks')) {
                    if (trim((string)$request->remarks) !== trim((string)($sales_order->remarks ?? ''))) {
                        $otherFieldsChanged = true;
                    }
                }
                if ($request->has('locations')) {
                    $existingLocs = $sales_order->locations->pluck('location_id')->map(fn($v) => (int)$v)->sort()->values()->toArray();
                    $incomingLocs = collect($request->locations)->map(fn($v) => (int)$v)->filter()->sort()->values()->toArray();
                    if ($existingLocs != $incomingLocs) {
                        $otherFieldsChanged = true;
                    }
                }
                if ($request->has('arrival_location_id')) {
                    $existingFacts = $sales_order->factories->pluck('arrival_location_id')->map(fn($v) => (int)$v)->sort()->values()->toArray();
                    $incomingFacts = collect($request->arrival_location_id)->map(fn($v) => (int)$v)->filter()->sort()->values()->toArray();
                    if ($existingFacts != $incomingFacts) {
                        $otherFieldsChanged = true;
                    }
                }
                if ($request->has('arrival_sub_location_id')) {
                    $existingSecs = $sales_order->sections->pluck('arrival_sub_location_id')->map(fn($v) => (int)$v)->sort()->values()->toArray();
                    $incomingSecs = collect($request->arrival_sub_location_id)->map(fn($v) => (int)$v)->filter()->sort()->values()->toArray();
                    if ($existingSecs != $incomingSecs) {
                        $otherFieldsChanged = true;
                    }
                }
                if ($request->has('item_id')) {
                    $existingItems = $sales_order->sales_order_data->sortBy('id')->values();
                    $incomingItems = $request->item_id;
                    if (count($incomingItems) !== $existingItems->count()) {
                        $otherFieldsChanged = true;
                    } else {
                        foreach ($incomingItems as $idx => $itemId) {
                            $ex = $existingItems[$idx] ?? null;
                            if (!$ex) { $otherFieldsChanged = true; break; }
                            if ((int)$ex->item_id !== (int)$itemId) { $otherFieldsChanged = true; break; }
                            if (abs((float)$ex->qty - (float)($request->qty[$idx] ?? 0)) > 0.001) { $otherFieldsChanged = true; break; }
                            if (abs((float)$ex->rate - (float)($request->rate[$idx] ?? 0)) > 0.001) { $otherFieldsChanged = true; break; }
                            if (isset($request->brand_id[$idx]) && (int)$ex->brand_id !== (int)$request->brand_id[$idx]) { $otherFieldsChanged = true; break; }
                            if (isset($request->bag_size[$idx]) && (int)$ex->bag_size !== (int)$request->bag_size[$idx]) { $otherFieldsChanged = true; break; }
                            if (isset($request->no_of_bags[$idx]) && abs((float)$ex->no_of_bags - (float)$request->no_of_bags[$idx]) > 0.001) { $otherFieldsChanged = true; break; }
                        }
                    }
                }

                if ($otherFieldsChanged) {
                    return response()->json([
                        'error' => 'For an approved Sale Order, only Delivery Date and Contract Status can be updated. Other fields cannot be modified.',
                        'message' => 'For an approved Sale Order, only Delivery Date and Contract Status can be updated. Other fields cannot be modified.'
                    ], 422);
                }

                if (!$deliveryDateChanged && !$contractStatusChanged) {
                    return response()->json([
                        'error' => 'No changes were detected in Delivery Date or Contract Status.',
                        'message' => 'No changes were detected in Delivery Date or Contract Status.'
                    ], 422);
                }

                // Handle Contract Status Change
                if ($contractStatusChanged) {
                    $isReopen = in_array($request->contract_status, ['reopen-contract-closed-by-mistake', 'reopen', 'open']);
                    $isCloseContract = in_array($request->contract_status, ['close-contract-due-to-market-down', 'close-with-market-rate-penalty']);
                    $newStatus = $isReopen ? 'draft' : ($isCloseContract ? 'cancelled' : $sales_order->status);

                    $oldContractStatus = $sales_order->contract_status;
                    $sales_order->contract_status = $isReopen ? 'reopen-contract-closed-by-mistake' : $request->contract_status;
                    $sales_order->status = $newStatus;
                    $sales_order->saveQuietly();

                    AuditLogService::log(
                        $sales_order,
                        'contract_status_updated',
                        "SO Contract Status updated from '{$oldContractStatus}' to '{$sales_order->contract_status}'",
                        ['contract_status' => $oldContractStatus, 'status' => $sales_order->getOriginal('status')],
                        ['contract_status' => $sales_order->contract_status, 'status' => $newStatus]
                    );
                }

                // Handle Delivery Date Change
                if ($deliveryDateChanged) {
                    if (!$sales_order->hasPendingDeliveryDateAmendment()) {
                        // First amendment on approved SO: store cache, reset to pending in new cycle
                        Cache::store('database')->forever("so_amendment_{$sales_order->id}", [
                            'delivery_date' => $newDeliveryDate,
                            'old_delivery_date' => $oldDeliveryDate,
                            'requested_by' => auth()->user()?->id ?? (is_numeric(auth()->id()) ? (int) auth()->id() : null),
                            'requested_at' => now()->toDateTimeString(),
                        ]);

                        $sales_order->createNewApprovalCycle();
                        $sales_order->am_approval_status = 'pending';
                        $sales_order->am_change_made = 1;
                        $sales_order->delivery_date = $newDeliveryDate;
                        $sales_order->save();

                        AuditLogService::log(
                            $sales_order,
                            'delivery_date_amendment_requested',
                            "Delivery date amendment requested from {$oldDeliveryDate} to {$newDeliveryDate} (awaiting approval)",
                            ['delivery_date' => $oldDeliveryDate],
                            ['delivery_date' => $newDeliveryDate]
                        );
                    } else {
                        // Already pending amendment: update proposed date and keep original old date
                        $existingAmendment = Cache::store('database')->get("so_amendment_{$sales_order->id}");
                        $originalOldDate = $existingAmendment['old_delivery_date'] ?? $oldDeliveryDate;
                        Cache::store('database')->forever("so_amendment_{$sales_order->id}", [
                            'delivery_date' => $newDeliveryDate,
                            'old_delivery_date' => $originalOldDate,
                            'requested_by' => auth()->user()?->id ?? (is_numeric(auth()->id()) ? (int) auth()->id() : null),
                            'requested_at' => now()->toDateTimeString(),
                        ]);

                        $sales_order->delivery_date = $newDeliveryDate;
                        $sales_order->saveQuietly();

                        AuditLogService::log(
                            $sales_order,
                            'delivery_date_amendment_updated',
                            "Delivery date amendment updated to {$newDeliveryDate} (awaiting approval)",
                            ['delivery_date' => $oldDeliveryDate],
                            ['delivery_date' => $newDeliveryDate]
                        );
                    }
                }

                DB::commit();
                $msg = $deliveryDateChanged
                    ? 'Sale Order delivery date amendment submitted for re-approval.'
                    : 'Sale Order Contract Status updated successfully.';
                return response()->json(['data' => $msg, 'success' => $msg]);
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

            // Seller / Sell By (parent_user_id) validation
            if ($request->has('parent_user_id')) {
                if ($request->filled('parent_user_id')) {
                    $parentUser = User::find($request->parent_user_id);
                    if (!$parentUser || !$parentUser->can('Seller')) {
                        return response()->json(['error' => "Parent user doen't have 'Seller' permission."], 422);
                    }
                    $payload['parent_user_id'] = $request->parent_user_id;
                } else {
                    $payload['parent_user_id'] = null;
                }
            }
            if ($request->has('seller_commission_per_kg')) {
                $payload['seller_commission_per_kg'] = $request->seller_commission_per_kg ?? 0;
            }

            $payload["remarks"] = !$request->remarks ? '' : $request->remarks;
            $payload["contact_person"] = !$request->contact_person ? '' : $request->contact_person;
            $payload["so_reference_no"] = !$request->so_reference_no ? '' : $request->so_reference_no;
            $payload["transporter_used"] = !$request->transporter_used ? 'no' : $request->transporter_used;
            $payload["payment_term_id"] = !$request->payment_term_id ? PaymentTerm::first()->id : $request->payment_term_id;
            $payload["commission_per_kg"] = $request->commission_per_kg ?? 0;
            $payload["receipt_voucher_item_ids"] = $request->receipt_voucher_item_ids;
            $payload["payment_on_kaanta"] = $request->has('payment_on_kaanta') ? 1 : 0;
            $payload["contract_status"] = $request->contract_status ?? $sales_order->contract_status;

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

            // Sync Unallocated Receipt Vouchers & Customer Advances
            // 1. Revert previous advance adjustments made by this SO
            $prevAdjustments = CustomerAdvanceAdjustment::where('voucher_no', $sales_order->reference_no)->get();
            foreach ($prevAdjustments as $adj) {
                $adv = CustomerAdvance::find($adj->customer_advance_id);
                if ($adv) {
                    $adv->used_amount -= $adj->amount;
                    $adv->remaining_amount += $adj->amount;
                    $adv->status = ($adv->used_amount <= 0.01) ? 'pending' : 'partial_payment';
                    $adv->save();
                }
                $adj->delete();
            }

            // Unset old items linked to this SO
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

            // 2. Set new ones & sync CustomerAdvance
            if ($request->has('receipt_voucher_item_ids') && is_array($request->receipt_voucher_item_ids)) {
                $rvItems = ReceiptVoucherItem::with('receiptVoucher')->whereIn('id', $request->receipt_voucher_item_ids)->get();

                foreach ($rvItems as $rvItem) {
                    $rvItem->update([
                        'reference_type' => 'sale_order',
                        'reference_id' => $id
                    ]);

                    // Sync corresponding CustomerAdvance if exists
                    $uniqueNo = $rvItem->receiptVoucher?->unique_no;
                    if ($uniqueNo) {
                        $adv = CustomerAdvance::where('voucher_no', $uniqueNo)
                            ->where('customer_id', $sales_order->customer_id)
                            ->first();

                        if ($adv && $adv->remaining_amount > 0) {
                            $itemAmt = (float)($rvItem->net_amount > 0 ? $rvItem->net_amount : $rvItem->amount);
                            $adjAmount = min((float)$adv->remaining_amount, $itemAmt);
                            if ($adjAmount > 0) {
                                $adv->used_amount += $adjAmount;
                                $adv->remaining_amount -= $adjAmount;
                                $adv->status = ($adv->remaining_amount <= 0.01) ? 'completed' : 'partial_payment';
                                $adv->save();

                                CustomerAdvanceAdjustment::create([
                                    'customer_advance_id' => $adv->id,
                                    'voucher_no' => $sales_order->reference_no,
                                    'amount' => $adjAmount
                                ]);
                            }
                        }
                    }
                }

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
        if (!$sales_order) {
            return response()->json(['error' => 'Sale Order not found.', 'message' => 'Sale Order not found.'], 404);
        }
        if (in_array(strtolower($sales_order->am_approval_status ?? ''), ['approved', 'rejected'])) {
            return response()->json([
                'error' => "Sales Order has been {$sales_order->am_approval_status} and cannot be deleted.",
                'message' => "Sales Order has been {$sales_order->am_approval_status} and cannot be deleted."
            ], 422);
        }
        // Revert any advance adjustments
        $prevAdjustments = CustomerAdvanceAdjustment::where('voucher_no', $sales_order->reference_no)->get();
        foreach ($prevAdjustments as $adj) {
            $adv = CustomerAdvance::find($adj->customer_advance_id);
            if ($adv) {
                $adv->used_amount -= $adj->amount;
                $adv->remaining_amount += $adj->amount;
                $adv->status = ($adv->used_amount <= 0.01) ? 'pending' : 'partial_payment';
                $adv->save();
            }
            $adj->delete();
        }

        // Reset any ReceiptVoucherItem reference
        ReceiptVoucherItem::where('reference_type', 'sale_order')
            ->where('reference_id', $id)
            ->update([
                'reference_type' => 'not-allocated',
                'reference_id' => null
            ]);

        $sales_order->sales_order_data()->delete();
        $sales_order->delete();

        return response()->json(['data' => 'Sale Order has been deleted', 'success' => 'Sale Order has been deleted']);
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
            // Filter by Contract Status
            ->when($request->filled('contract_status_f') && $request->contract_status_f != 'all', function ($q) use ($request) {
                if ($request->contract_status_f === 'closed') {
                    $q->where(function ($sq) {
                        $sq->whereIn('contract_status', ['close-contract-due-to-market-down', 'close-with-market-rate-penalty', 'closed', 'close'])
                           ->orWhereIn('status', ['cancelled', 'closed']);
                    });
                } elseif ($request->contract_status_f === 'pending') {
                    $q->where(function ($sq) {
                        $sq->whereNull('contract_status')
                           ->orWhereNotIn('contract_status', ['close-contract-due-to-market-down', 'close-with-market-rate-penalty', 'closed', 'close']);
                    })->where(function ($sq) {
                        $sq->whereNull('status')
                           ->orWhereNotIn('status', ['cancelled', 'closed']);
                    });
                }
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
                'contract_status' => $SaleOrder->contract_status,
                'is_closed' => $SaleOrder->isClosed(),
                'has_pending_amendment' => $SaleOrder->hasPendingDeliveryDateAmendment(),
                'pending_amendment' => $SaleOrder->getPendingDeliveryDateAmendment(),
            ];
        }

        return view('management.sales.orders.getList', [
            'SalesOrders' => $SalesOrders,           // for pagination
            'groupedSalesOrders' => $groupedData,  // our grouped data
        ]);
    }

    /**
     * Calculate balance remaining quantity for a Sales Order (SO qty minus dispatched DC qty).
     */
    public function calculateBalanceQuantity(SalesOrder $saleOrder): float
    {
        $soTotalQty = (float) $saleOrder->sales_order_data->sum('qty');

        $totalDcQty = 0;
        foreach ($saleOrder->delivery_orders as $do) {
            $doDataIds = $do->delivery_order_data->pluck('id')->toArray();
            $dcQty = (float) \App\Models\Sales\DeliveryChallanData::whereIn('do_data_id', $doDataIds)
                ->whereHas('deliveryChallan', function ($q) {
                    $q->where('am_approval_status', '!=', 'rejected');
                })
                ->sum('qty');

            if ($dcQty <= 0) {
                $dcQty = (float) $do->delivery_challans()
                    ->where('delivery_challans.am_approval_status', '!=', 'rejected')
                    ->sum('delivery_challan_delivery_order.qty');
            }

            $totalDcQty += $dcQty;
        }

        return max(0, $soTotalQty - $totalDcQty);
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
