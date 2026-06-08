<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sales\DeliveryOrderRequest;
use App\Models\BagType;
use App\Models\Master\ArrivalLocation;
use App\Models\Master\ArrivalSubLocation;
use App\Models\Master\CompanyLocation;
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
        // Only get customers that have delivery order records
        $customerIds = DeliveryOrder::distinct()->pluck('customer_id')->filter();
        $customers = Customer::whereIn('id', $customerIds)->get();

        // Only get items that have delivery order data records
        $itemIds = \App\Models\Sales\DeliveryOrderData::distinct()->pluck('item_id')->filter();
        $items = Product::whereIn('id', $itemIds)->get();

        // Only get sale orders that are linked to delivery orders
        $soIds = DeliveryOrder::distinct()->pluck('so_id')->filter();
        $saleOrders = SalesOrder::whereIn('id', $soIds)->select('id', 'reference_no')->get();

        return view('management.sales.delivery-order.index', compact('customers', 'items', 'saleOrders'));
    }

    public function view(int $id)
    {

        $payment_terms = PaymentTerm::select('id', 'desc')->where('status', 'active')->get();
        $customers = Customer::where("type", "local")->get();
        $delivery_order = DeliveryOrder::with(['delivery_order_data', 'receipt_vouchers.advances', 'withheld_receipt_voucher'])->find($id);
        $sales_orders = SalesOrder::where('customer_id', $delivery_order->customer_id)
            ->where('am_approval_status', 'approved')
            ->get()
            ->filter(function ($so) {
                if ($so->transporter_used == 'yes') {
                    return $so->logistics()->where('am_approval_status', 'approved')->exists();
                }
                return true;
            });
        
        $receipt_vouchers = $delivery_order->receipt_vouchers->map(function($rv) {
            if ($rv->pivot->receipt_voucher_advance_id) {
                $adv = \App\Models\ReceiptVoucherAdvance::find($rv->pivot->receipt_voucher_advance_id);
                $rv->unified_id = "adv_{$rv->pivot->receipt_voucher_advance_id}";
                $rv->unified_text = "advance (" . ($adv->net_amount ?? '0') . ")";
            } else {
                $rv->unified_id = "rv_{$rv->id}";
                $rv->unified_text = "{$rv->unique_no} ({$rv->ref_bill_no})";
            }
            $rv->remaining_amount = $rv->pivot->amount;
            return $rv;
        });
        
        $sale_order_of_delivery_order = SalesOrder::find($delivery_order->so_id);
        $latestLog = $delivery_order->approvalLogs()->with(['user', 'role'])->latest()->first();

        return view('management.sales.delivery-order.view', compact('sale_order_of_delivery_order', 'payment_terms', 'delivery_order', 'customers', 'sales_orders', 'receipt_vouchers', 'latestLog'));
    }

    public function create()
    {
        $sale_orders = SalesOrder::select('reference_no', 'id', 'transporter_used')
            ->where('am_approval_status', 'approved')
            ->get();
            
            // ->filter(function ($so) {
            //     if ($so->transporter_used == 'yes') {
            //         return $so->logistics()->where('am_approval_status', 'approved')->exists();
            //     }
            //     return true;
            // });
        $payment_terms = PaymentTerm::all();
        $customers = Customer::where("type", "local")->get();
        $items = Product::all();
        $pay_types = PayType::select('name', 'id')->where('status', 'active')->get();

        return view('management.sales.delivery-order.create', compact('payment_terms', 'customers', 'items', 'sale_orders', 'pay_types'));
    }

    public function store(DeliveryOrderRequest $request)
    {
        DB::beginTransaction();

        $withhold_rv_id = null;
        if ($request->withhold_for_rv && str_starts_with($request->withhold_for_rv, 'rv_')) {
            $withhold_rv_id = str_replace('rv_', '', $request->withhold_for_rv);
        }



        try {
            $delivery_order = DeliveryOrder::create([
                'customer_id' => $request->customer_id,
                'so_id' => $request->sale_order_id,
                'advance_amount' => $request->advance_amount ?? 0,
                'withhold_amount' => $request->withhold_amount ?? 0,
                'withhold_for_rv_id' => $withhold_rv_id,
                'dispatch_date' => $request->dispatch_date,
                'reference_no' => self::getNumber($request, null, $request->dispatch_date),
                'ref_no' => $request->ref_no,
                'payment_term_id' => $request->payment_term_id ?? (PaymentTerm::first())->id,
                'sauda_type' => $request->sauda_type,
                'location_id' => $request->location_id,
                'arrival_location_id' => is_array($request->arrival_id) ? implode(',', $request->arrival_id) : $request->arrival_id,
                'sub_arrival_location_id' => is_array($request->storage_id) ? implode(',', $request->storage_id) : $request->storage_id,
                // 'line_desc' => $request->line_desc,
                'delivery_date' => $request->delivery_date,
                'line_desc' => $request->remarks ?? "",
                'remarks' => $request->remarks ?? "",
                'company_id' => $request->company_id,
                'created_by' => auth()->user()->id,
                'am_approval_status' => 'pending',
                'so_withhold_percentage' => $request->so_withhold_percentage ?? 0,
                'so_held_amount' => $request->so_held_amount ?? 0,
            ]);

            // foreach ($locations as $location) {
            //     $delivery_order->locations()->create([
            //         'location_id' => $location,
            //     ]);
            // }

            $raw_vouchers = $request->receipt_vouchers ?? [];
            $salesOrder = SalesOrder::find($request->sale_order_id);
            
            if ($salesOrder && $salesOrder->pay_type_id == 10) {
                foreach ($raw_vouchers as $rv_val) {
                    if (str_starts_with($rv_val, 'adv_')) {
                        $adv_id = str_replace('adv_', '', $rv_val);
                        $adv = \App\Models\ReceiptVoucherAdvance::find($adv_id);
                        if ($adv) {
                            $spent = DB::table('delivery_order_receipt_voucher')
                                ->where('receipt_voucher_advance_id', $adv->id)
                                ->sum('amount');
                            $remaining = doubleval($adv->net_amount) - doubleval($spent);
                            
                            $withhold_amount = ($rv_val == $request->withhold_for_rv) ? ($request->withhold_amount ?? 0) : 0;

                            DB::table('delivery_order_receipt_voucher')->insert([
                                'delivery_order_id' => $delivery_order->id,
                                'receipt_voucher_id' => $adv->receipt_voucher_id,
                                'receipt_voucher_advance_id' => $adv->id,
                                'amount' => $remaining - $withhold_amount,
                                'withhold_amount' => $withhold_amount,
                                'last_withhold_amount' => 0,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }
                    } else {
                        $rv_id = str_replace('rv_', '', $rv_val);
                        $rv = ReceiptVoucher::with('items')->find($rv_id);
                        if ($rv) {
                            $linked_amount = $rv->items->where("reference_type", "sale_order")
                                                      ->where("reference_id", $request->sale_order_id)
                                                      ->sum("net_amount");
                            
                            $spent = DB::table('delivery_order_receipt_voucher')
                                ->join('delivery_order', 'delivery_order.id', '=', 'delivery_order_receipt_voucher.delivery_order_id')
                                ->where('delivery_order_receipt_voucher.receipt_voucher_id', $rv->id)
                                ->where('delivery_order.so_id', $request->sale_order_id)
                                ->whereNull('delivery_order_receipt_voucher.receipt_voucher_advance_id')
                                ->sum('delivery_order_receipt_voucher.amount');

                                
                            
                            $jv_spent = DB::table('journal_voucher_details')
                                ->join('journal_vouchers', 'journal_vouchers.id', '=', 'journal_voucher_details.journal_voucher_id')
                                ->whereNull('journal_vouchers.deleted_at')
                                ->whereNull('journal_voucher_details.deleted_at')
                                ->where('receipt_voucher_id', $rv->id)
                                ->sum('debit_amount');
                            
                            $spent += $jv_spent;
                            
                            $remaining = doubleval($linked_amount) - doubleval($spent);
                            $withhold_amount = ($rv_val == $request->withhold_for_rv) ? ($request->withhold_amount ?? 0) : 0;

                            DB::table('delivery_order_receipt_voucher')->insert([
                                'delivery_order_id' => $delivery_order->id,
                                'receipt_voucher_id' => $rv->id,
                                'receipt_voucher_advance_id' => null,
                                'amount' => $remaining - $withhold_amount,
                                'withhold_amount' => $withhold_amount,
                                'last_withhold_amount' => 0,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }
                    }
                }
            }


            $spent_qty = $salesOrder->delivery_orders->where("am_approval_status", "!=", "rejected")->flatMap->delivery_order_data->sum("qty");
            $total_qty = $salesOrder?->sales_order_data?->first()->qty;
            $remaining_qty = $total_qty - $spent_qty;
            

            foreach ($request->item_id as $key => $item) {
                // $balance = delivery_order_balance($request->so_data_id[$key]);

                // if($request->no_of_bags[$key] > $balance) {
                //     return response()->json("Total balance is $balance. you can not exceed this balance", 422);
                // }

                if($remaining_qty < (int)$request->qty[$key]) {
                    return response()->json("Total remaining qty(kg): $remaining_qty. you can not exceed this balance", 422);
                }

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
                    "description" => $request->desc[$key] ?? ""
                ]);
            }

            DB::commit();

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json(['error' => $e->getMessage()], 500);

        }

        return response()->json(['success' => 'Delivery Order has been created']);
    }

    public function getList(Request $request)
    {
        $perPage = $request->get('per_page', 25);

        // Eager load the inquiry + all its items + related product
        $delivery_orders = DeliveryOrder::with('salesOrder', 'delivery_order_data', 'customer')->latest()
            ->when($request->filled('search'), function ($q) use ($request) {
                $searchTerm = '%' . strtolower($request->search) . '%';
                return $q->where(function ($sq) use ($searchTerm) {
                    $sq->whereRaw('LOWER(`reference_no`) LIKE ?', [$searchTerm])
                        ->orWhereHas('delivery_order_data', function ($q) use ($searchTerm) {
                            $q->whereRaw('CAST(`qty` AS CHAR) LIKE ?', [$searchTerm])
                                ->orWhereRaw('CAST(`rate` AS CHAR) LIKE ?', [$searchTerm])
                                ->orWhereRaw('CAST(`qty` * `rate` AS CHAR) LIKE ?', [$searchTerm]);
                        });
                });
            })
            // Filter by DO No
            ->when($request->filled('do_no'), function ($q) use ($request) {
                $q->where('reference_no', 'like', '%' . $request->do_no . '%');
            })
            // Filter by SO No
            ->when($request->filled('so_id') && $request->so_id != 'all', function ($q) use ($request) {
                $q->where('so_id', $request->so_id);
            })
            // Filter by Customer
            ->when($request->filled('customer_id') && $request->customer_id != 'all', function ($q) use ($request) {
                $q->where('customer_id', $request->customer_id);
            })
            // Filter by Item (through delivery_order_data relationship)
            ->when($request->filled('item_id') && $request->item_id != 'all', function ($q) use ($request) {
                $q->whereHas('delivery_order_data', function ($sq) use ($request) {
                    $sq->where('item_id', $request->item_id);
                });
            })
            // Filter by Date Range (dispatch_date)
            ->when($request->filled('date_range'), function ($q) use ($request) {
                $dates = explode(' - ', $request->date_range);
                if (count($dates) == 2) {
                    $q->whereBetween('dispatch_date', [trim($dates[0]), trim($dates[1])]);
                }
            })
            // Filter by Status
            ->when($request->filled('status') && $request->status != 'all', function ($q) use ($request) {
                $q->where('am_approval_status', $request->status);
            })
            ->orderBy("reference_no", "desc")
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
                'created_by_id' => $delivery_order->created_by,
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

        $latestContract = DeliveryOrder::withoutGlobalScopes()->where('reference_no', 'like', "$prefix-%")
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

        $saleOrders = SalesOrder::with("locations")
            ->select('reference_no', 'id', 'pay_type_id', 'transporter_used')
            ->where('am_approval_status', 'approved')
            ->where('customer_id', $customer_id)
            ->get()
            ->filter(function ($saleOrder) {
                // if ($saleOrder->transporter_used == 'yes') {
                //     $hasApprovedLogistics = $saleOrder->logistics()
                //         ->where('am_approval_status', 'approved')
                //         ->exists();

                //     if (!$hasApprovedLogistics) {
                //         return false;
                //     }
                // }

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
                'type' => $saleOrder->pay_type_id,
            ];
        }



        return [
            "rawData" => $saleOrders,
            "processedData" => $data
        ];
        
    }

    public function getDetails(Request $request)
    {
        $so_id = $request->so_id;

        $sale_order = SalesOrder::with([
            'sales_order_data',
            'delivery_order_transactions',
            'locations',
            'factories.factory',
            'sections.section',
        ])
            ->find($so_id);

        $locationIds = $sale_order->locations()->pluck('location_id')->toArray();
        $locations = CompanyLocation::whereIn('id', $locationIds)
            ->select('id', 'name')
            ->get()
            ->map(function ($loc) {
                return [
                    'id' => $loc->id,
                    'text' => $loc->name,
                ];
            })
            ->values();

        // Map selected factories grouped by company_location_id (location)
        $factoryMap = [];
        foreach ($sale_order->factories as $factoryPivot) {
            $factory = $factoryPivot->factory;
            if (! $factory) {
                continue;
            }
            $companyLocationId = $factory->company_location_id;
            $factoryMap[$companyLocationId][] = [
                'id' => $factory->id,
                'text' => $factory->name,
                
            ];
        }

        // Map selected sections grouped by arrival_location_id (factory)
        $sectionMap = [];
        foreach ($sale_order->sections as $sectionPivot) {
            $section = $sectionPivot->section;
            if (! $section) {
                continue;
            }
            $factoryId = $section->arrival_location_id;
            $sectionMap[$factoryId][] = [
                'id' => $section->id,
                'text' => $section->name . " (" . $section?->arrivalLocation?->name  . ")",
            ];
        }

        $data = [
            'unused_amount' => $sale_order->sales_order_data()->sum(DB::raw('qty * rate')) - $sale_order->delivery_order_transactions()->sum(DB::raw('advance_amount')),
            'so_amount' => $sale_order->sales_order_data()->sum(DB::raw('qty * rate')),
            'amount_received' => $sale_order->delivery_order_transactions()->sum(DB::raw('advance_amount')),
            'sauda_type' => strtolower($sale_order->sauda_type),
            'delivery_date' => $sale_order->delivery_date,
            'payment_term_id' => $sale_order->payment_term_id,
            'pay_type_id' => $sale_order->pay_type_id,
            'locations' => $locations,
            'factory_map' => $factoryMap,
            'section_map' => $sectionMap,
        ];

        return $data;
    }

    public function get_so_items(Request $request)
    {
        $so_id = $request->so_id;

        $items = Product::select('id', 'name')->get();
        $sale_order = SalesOrder::with('delivery_order_transactions', 'locations', 'delivery_orders')
            ->find($so_id);

        $spent = $sale_order->delivery_orders
            ->where("am_approval_status", "!=", "rejected")
            ->flatMap->delivery_order_data
            ->sum('qty');
       
        $spent_qty = $sale_order->delivery_orders->where("am_approval_status", "!=", "rejected")->flatMap->delivery_order_data->sum("qty");
        $total_qty = $sale_order?->sales_order_data?->first()->qty;
        $remaining_qty = $total_qty - $spent_qty;

        $bag_types = BagType::select('id', 'name')->get();

        

        return view('management.sales.delivery-order.getItem', compact('sale_order', 'items', 'bag_types', 'spent', 'remaining_qty'));
    }

    public function get_receipt_vouchers(Request $request)
    {
        $customer_id = $request->customer_id;
        $sale_order_id = $request->sale_order_id;
        $data = [];

        // 1. Fetch Advances for the customer
        $advances = \App\Models\ReceiptVoucherAdvance::where('customer_id', $customer_id)
            ->whereHas('receiptVoucher')
            ->get()
            ->map(function ($adv) {
                // Calculate spent amount for this specific advance
                $spent = DB::table('delivery_order_receipt_voucher')
                    ->where('receipt_voucher_advance_id', $adv->id)
                    ->sum('amount');
                $adv->remaining_amount = doubleval($adv->net_amount) - doubleval($spent);
                return $adv;
            })
            ->filter(fn($adv) => $adv->remaining_amount > 0);

        foreach ($advances as $adv) {
            $data[] = [
                'id' => "adv_{$adv->id}",
                'text' => "advance ({$adv->net_amount})",
                'amount' => $adv->remaining_amount,
                'date' => $adv->receiptVoucher && $adv->receiptVoucher->rv_date 
                    ? $adv->receiptVoucher->rv_date->format('Y-m-d') 
                    : null,
            ];
        }

        // 2. Fetch Regular RVs linked to the Sale Order
        if ($sale_order_id) {
            $receipt_vouchers = ReceiptVoucher::with(['delivery_orders', 'items' => function($query) use ($sale_order_id) {
                $query->where("reference_type", "sale_order")
                      ->where("reference_id", $sale_order_id);
            }])
                ->where("customer_id", $customer_id)
                ->get();



            foreach ($receipt_vouchers as $rv) {
                $linked_amount = $rv->items->where("reference_type", "sale_order")
                                          ->where("reference_id", $sale_order_id)
                                          ->sum("net_amount");


                $spent = DB::table('delivery_order_receipt_voucher')
                    ->join('delivery_order', 'delivery_order.id', '=', 'delivery_order_receipt_voucher.delivery_order_id')
                    ->where('delivery_order_receipt_voucher.receipt_voucher_id', $rv->id)
                    ->where('delivery_order.so_id', $sale_order_id)
                    ->whereNull('delivery_order_receipt_voucher.receipt_voucher_advance_id')
                    ->sum('delivery_order_receipt_voucher.amount');


                
                $jv_spent = DB::table('journal_voucher_details')
                    ->join('journal_vouchers', 'journal_vouchers.id', '=', 'journal_voucher_details.journal_voucher_id')
                    ->whereNull('journal_vouchers.deleted_at')
                    ->whereNull('journal_voucher_details.deleted_at')
                    ->where('receipt_voucher_id', $rv->id)
                    ->sum('debit_amount');
                
                $spent += $jv_spent;
                
                $remaining = doubleval($linked_amount) - doubleval($spent);
                

                if ($remaining > 0) {
                    $data[] = [
                        'id' => "rv_{$rv->id}",
                        'text' => "{$rv->unique_no} ({$rv->ref_bill_no})",
                        'amount' => $remaining,
                        'date' => $rv->rv_date->format('Y-m-d'),
                    ];
                }
            }
        }

        return $data;
    }

    public function destroy(DeliveryOrder $delivery_order)
    {
        if($delivery_order->am_approval_status == "approved" || $delivery_order->am_approval_status == 'rejected') {
            throw new Exception("Delivery Order has been approved/rejected and cannot be updated.");
        }
        
        if($delivery_order) {
            $delivery_order->delivery_order_data()->delete();
        }

        $delivery_order->delete();

        return response()->json(['success' => 'Delivery order has been deleted!']);

    }

    public function edit(DeliveryOrder $delivery_order)
    {
        $delivery_order->load('receipt_vouchers', 'locations');
        $sale_orders = SalesOrder::with("locations")
            ->select('reference_no', 'id', 'pay_type_id', 'transporter_used')
            ->where('am_approval_status', 'approved')
            ->get();
            // ->filter(function ($so) {
            //     if ($so->transporter_used == 'yes') {
            //         return $so->logistics()->where('am_approval_status', 'approved')->exists();
            //     }
            //     return true;
            // });
        $payment_terms = PaymentTerm::all();
        $customers = Customer::where("type", "local")->get();
        $items = Product::all();
        $bag_types = BagType::select('id', 'name')->get();
           
        $sale_order_of_delivery_order = SalesOrder::find($delivery_order->so_id);


        // 1. Fetch Advances for the customer
        $advancesList = \App\Models\ReceiptVoucherAdvance::where('customer_id', $delivery_order->customer_id)
            ->whereHas('receiptVoucher')
            ->get()
            ->map(function ($adv) use ($delivery_order) {
                $spent = DB::table('delivery_order_receipt_voucher')
                    ->where('receipt_voucher_advance_id', $adv->id)
                    ->where('delivery_order_id', '!=', $delivery_order->id)
                    ->sum('amount');
                $adv->remaining_amount = doubleval($adv->net_amount) - doubleval($spent);
                $adv->unified_id = "adv_{$adv->id}";
                $adv->unified_text = "advance ({$adv->net_amount})";
                $adv->date = $adv->receiptVoucher && $adv->receiptVoucher->rv_date 
                    ? $adv->receiptVoucher->rv_date->format('Y-m-d') 
                    : null;
                return $adv;
            })
            ->filter(function ($adv) use ($delivery_order) {
                return $adv->remaining_amount > 0 || $delivery_order->receipt_vouchers->contains('pivot.receipt_voucher_advance_id', $adv->id);
            });

        // 2. Fetch Regular RVs linked to the Sale Order of this DO
        $rvsList = collect();
        if ($delivery_order->so_id) {
            $rvsList = ReceiptVoucher::with(['delivery_orders', 'items'])
                ->whereHas("items", function($query) use ($delivery_order) {
                    $query->where("reference_type", "sale_order")
                          ->where("reference_id", $delivery_order->so_id);
                })
                ->where("customer_id", $delivery_order->customer_id)
                ->get()
                ->map(function ($rv) use ($delivery_order) {
                    $linked_amount = $rv->items->where("reference_type", "sale_order")
                                              ->where("reference_id", $delivery_order->so_id)
                                              ->sum("net_amount");
                    
                    $spent = DB::table('delivery_order_receipt_voucher')
                        ->join('delivery_order', 'delivery_order.id', '=', 'delivery_order_receipt_voucher.delivery_order_id')
                        ->where('delivery_order_receipt_voucher.receipt_voucher_id', $rv->id)
                        ->where('delivery_order.so_id', $delivery_order->so_id)
                        ->whereNull('delivery_order_receipt_voucher.receipt_voucher_advance_id')
                        ->where('delivery_order_receipt_voucher.delivery_order_id', '!=', $delivery_order->id)
                        ->sum('delivery_order_receipt_voucher.amount');
                    
                    $jv_spent = DB::table('journal_voucher_details')
                        ->join('journal_vouchers', 'journal_vouchers.id', '=', 'journal_voucher_details.journal_voucher_id')
                        ->whereNull('journal_vouchers.deleted_at')
                        ->whereNull('journal_voucher_details.deleted_at')
                        ->where('receipt_voucher_id', $rv->id)
                        ->sum('debit_amount');
                    
                    $spent += $jv_spent;
                    
                    $rv->remaining_amount = doubleval($linked_amount) - doubleval($spent);
                    $rv->unified_id = "rv_{$rv->id}";
                    $rv->unified_text = "{$rv->unique_no} ({$rv->ref_bill_no})";
                    $rv->date = $rv->rv_date->format('Y-m-d');
                    return $rv;
                })
                ->filter(function ($rv) use ($delivery_order) {
                    return $rv->remaining_amount > 0 || $delivery_order->receipt_vouchers->whereNull('pivot.receipt_voucher_advance_id')->contains('id', $rv->id);
                });
        }

        $receipt_vouchers = $advancesList->concat($rvsList)->values();
        $latestLog = $delivery_order->approvalLogs()->with(['user', 'role'])->latest()->first();

        return view('management.sales.delivery-order.edit', compact('sale_order_of_delivery_order', 'payment_terms', 'customers', 'items', 'sale_orders', 'delivery_order', 'receipt_vouchers', 'bag_types', 'latestLog'));

    }

    public function update(DeliveryOrderRequest $request, DeliveryOrder $delivery_order)
    {
        DB::beginTransaction();
        $withhold_rv_id = null;


        if($delivery_order->am_approval_status == "approved" || $delivery_order->am_approval_status == 'rejected') {
            return response()->json("Delivery Order has been approved/rejected and cannot be updated.", 400);
        }

        if ($request->withhold_for_rv && str_starts_with($request->withhold_for_rv, 'rv_')) {
            $withhold_rv_id = str_replace('rv_', '', $request->withhold_for_rv);
        }

        try {
            $delivery_order->update([
                'customer_id' => $request->customer_id,
                'so_id' => $request->sale_order_id,
                'advance_amount' => $request->advance_amount ?? 0,
                'withhold_amount' => $request->withhold_amount ?? 0,
                'withhold_for_rv_id' => $withhold_rv_id,
                'dispatch_date' => $request->dispatch_date,
                'reference_no' => $request->reference_no,
                'ref_no' => $request->ref_no,
                'payment_term_id' => $request->payment_term_id ?? (PaymentTerm::first())->id,
                'sauda_type' => $request->sauda_type,
                'location_id' => $request->location_id,
                'arrival_location_id' => is_array($request->arrival_id) ? implode(',', $request->arrival_id) : $request->arrival_id,
                'sub_arrival_location_id' => is_array($request->storage_id) ? implode(',', $request->storage_id) : $request->storage_id,
                'delivery_date' => $request->delivery_date,
                'line_desc' => $request->remarks ?? "",
                'remarks' => $request->remarks ?? "",
                'am_approval_status' => 'pending',
                'am_change_made' => 1,
                'so_withhold_percentage' => $request->so_withhold_percentage ?? 0,
                'so_held_amount' => $request->so_held_amount ?? 0,
            ]);

            // $delivery_order->locations()->delete();
            // foreach ($locations as $location) {
            //     $delivery_order->locations()->create([
            //         'location_id' => $location,
            //     ]);
            // }

            $raw_vouchers = $request->receipt_vouchers ?? [];
            $delivery_order->receipt_vouchers()->detach();

            $salesOrder = SalesOrder::find($request->sale_order_id);
            if ($salesOrder && $salesOrder->pay_type_id == 10) {
                foreach ($raw_vouchers as $rv_val) {
                    if (str_starts_with($rv_val, 'adv_')) {
                        $adv_id = str_replace('adv_', '', $rv_val);
                        $adv = \App\Models\ReceiptVoucherAdvance::find($adv_id);
                        if ($adv) {
                            $spent = DB::table('delivery_order_receipt_voucher')
                                ->where('receipt_voucher_advance_id', $adv->id)
                                ->where('delivery_order_id', '!=', $delivery_order->id)
                                ->sum('amount');
                            $remaining = doubleval($adv->net_amount) - doubleval($spent);
                            
                            $withhold_amount = ($rv_val == $request->withhold_for_rv) ? ($request->withhold_amount ?? 0) : 0;

                            DB::table('delivery_order_receipt_voucher')->insert([
                                'delivery_order_id' => $delivery_order->id,
                                'receipt_voucher_id' => $adv->receipt_voucher_id,
                                'receipt_voucher_advance_id' => $adv->id,
                                'amount' => $remaining - $withhold_amount,
                                'withhold_amount' => $withhold_amount,
                                'last_withhold_amount' => 0,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }
                    } else {
                        $rv_id = str_replace('rv_', '', $rv_val);
                        $rv = ReceiptVoucher::with('items')->find($rv_id);
                        if ($rv) {
                            $linked_amount = $rv->items->where("reference_type", "sale_order")
                                                      ->where("reference_id", $request->sale_order_id)
                                                      ->sum("net_amount");
                            
                            $spent = DB::table('delivery_order_receipt_voucher')
                                ->join('delivery_order', 'delivery_order.id', '=', 'delivery_order_receipt_voucher.delivery_order_id')
                                ->where('delivery_order_receipt_voucher.receipt_voucher_id', $rv->id)
                                ->where('delivery_order.so_id', $request->sale_order_id)
                                ->whereNull('delivery_order_receipt_voucher.receipt_voucher_advance_id')
                                ->where('delivery_order_receipt_voucher.delivery_order_id', '!=', $delivery_order->id)
                                ->sum('delivery_order_receipt_voucher.amount');
                            
                            $jv_spent = DB::table('journal_voucher_details')
                                ->join('journal_vouchers', 'journal_vouchers.id', '=', 'journal_voucher_details.journal_voucher_id')
                                ->whereNull('journal_vouchers.deleted_at')
                                ->whereNull('journal_voucher_details.deleted_at')
                                ->where('receipt_voucher_id', $rv->id)
                                ->sum('debit_amount');
                            
                            $spent += $jv_spent;
                            
                            $remaining = doubleval($linked_amount) - doubleval($spent);
                            $withhold_amount = ($rv_val == $request->withhold_for_rv) ? ($request->withhold_amount ?? 0) : 0;

                            DB::table('delivery_order_receipt_voucher')->insert([
                                'delivery_order_id' => $delivery_order->id,
                                'receipt_voucher_id' => $rv->id,
                                'receipt_voucher_advance_id' => null,
                                'amount' => $remaining - $withhold_amount,
                                'withhold_amount' => $withhold_amount,
                                'last_withhold_amount' => 0,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }
                    }
                }
            }

            $salesOrder = SalesOrder::with('sales_order_data')->find($request->sale_order_id);
            $spent_qty = $salesOrder->delivery_orders()
                ->where("am_approval_status", "!=", "rejected")
                ->with('delivery_order_data')
                ->get()
                ->flatMap->delivery_order_data
                ->whereIn('so_data_id', $salesOrder->sales_order_data->pluck('id'))
                ->sum("qty");
            
            $total_qty = $salesOrder->sales_order_data->sum('qty');
            $remaining_qty = $total_qty - $spent_qty;

            // Rebuild line items
            
            $delivery_order->delivery_order_data()->delete();
            foreach ($request->item_id as $key => $item) {
                // $balance =  delivery_order_balance($request->so_data_id[$key]);
                // if($request->no_of_bags[$key] > ($balance)) {
                //     return response()->json("Total balance is $balance. you can not exceed this balance", 422);
                // }

                $current_qty = $request->current_qty[$key] ?? 0;
                if((int)$request->qty[$key] > (int)($remaining_qty + $current_qty)) {
                    return response()->json("Total KG is: $remaining_qty, you can not exceed this balance", 422);
                }

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
                    "description" => $request->desc[$key] ?? "-"
                ]);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json($e->getMessage(), 500);
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
                'text' => $subarrival_location->name . " (" . $subarrival_location?->arrivalLocation?->name  . ")",
            ];
        }

        return $data;
    }
    public function get_balance_against_second_weighbridge(Request $request) {
        $delivery_order_id = $request->delivery_order_id;

        $balance = get_second_weighbridge_balance_by_delivery_order($delivery_order_id);

        return $balance;
    }
}
