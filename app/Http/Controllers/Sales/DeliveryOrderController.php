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

        $receipt_vouchers = $delivery_order->receipt_vouchers->map(function ($rv) {
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

        // Fetch JVs that are linked to this DO
        $linked_jvs = DB::table('settlement_adjustments')
            ->where('reference_type', 'journal_voucher')
            ->where('voucher_no', $delivery_order->reference_no)
            ->get();
            
        $rv_withhold_exists = $delivery_order->receipt_vouchers->where('pivot.withhold_amount', '>', 0)->count() > 0;

        $journal_vouchers = [];
        foreach ($linked_jvs as $jv_adj) {
            $jv = DB::table('journal_vouchers')->where('id', $jv_adj->reference_id)->first();
            if ($jv) {
                $is_withheld = false;
                if (!$rv_withhold_exists && $delivery_order->withhold_amount > 0) {
                    $cust_credit = DB::table('journal_voucher_details')
                        ->where('journal_voucher_id', $jv->id)
                        ->where('acc_id', function($q) use ($delivery_order) {
                            $q->select('account_id')->from('customers')->where('id', $delivery_order->customer_id);
                        })
                        ->sum('credit_amount');
                    $other_spent = DB::table('settlement_adjustments')
                        ->where('reference_type', 'journal_voucher')
                        ->where('reference_id', $jv->id)
                        ->where('voucher_no', '!=', $delivery_order->reference_no)
                        ->sum('amount');
                    $avail = doubleval($cust_credit) - doubleval($other_spent);
                    if (abs(($avail - $jv_adj->amount) - $delivery_order->withhold_amount) < 0.01) {
                        $is_withheld = true;
                    }
                }

                $journal_vouchers[] = [
                    'id' => $jv->id,
                    'text' => $jv->jv_no . ' (Consumed: ' . $jv_adj->amount . ')',
                    'amount' => $jv_adj->amount,
                    'is_withheld' => $is_withheld,
                ];
            }
        }

        return view('management.sales.delivery-order.view', compact('sale_order_of_delivery_order', 'payment_terms', 'delivery_order', 'customers', 'sales_orders', 'receipt_vouchers', 'journal_vouchers', 'latestLog'));
    }

    public function getStats(Request $request, int $id)
    {
        $delivery_order = DeliveryOrder::with([
            'customer',
            'salesOrder',
            'delivery_order_data.item',
            'delivery_order_data.brand',
            'delivery_challans' => function ($q) {
                $q->where('delivery_challans.am_approval_status', '!=', 'rejected');
            }
        ])->findOrFail($id);

        $doQty = (float) $delivery_order->delivery_order_data->sum('qty');
        $doDataIds = $delivery_order->delivery_order_data->pluck('id')->toArray();

        $dcQty = (float) \App\Models\Sales\DeliveryChallanData::whereIn('do_data_id', $doDataIds)
            ->whereHas('deliveryChallan', function ($q) {
                $q->where('am_approval_status', '!=', 'rejected');
            })
            ->sum('qty');

        if ($dcQty <= 0) {
            $dcQty = (float) $delivery_order->delivery_challans()
                ->where('delivery_challans.am_approval_status', '!=', 'rejected')
                ->sum('delivery_challan_delivery_order.qty');
        }

        $remainingQty = max(0, $doQty - $dcQty);

        // Item-wise stats
        $itemStats = [];
        $totalItemDoQty = 0;
        $totalItemDcQty = 0;
        $totalItemRemainingQty = 0;
        $totalItemBags = 0;

        foreach ($delivery_order->delivery_order_data as $itemData) {
            $iDoQty = (float) $itemData->qty;
            $iDcQty = (float) \App\Models\Sales\DeliveryChallanData::where('do_data_id', $itemData->id)
                ->whereHas('deliveryChallan', function ($q) {
                    $q->where('am_approval_status', '!=', 'rejected');
                })
                ->sum('qty');

            $iRemQty = max(0, $iDoQty - $iDcQty);

            $totalItemDoQty += $iDoQty;
            $totalItemDcQty += $iDcQty;
            $totalItemRemainingQty += $iRemQty;
            $totalItemBags += (float) ($itemData->no_of_bags ?? 0);

            $itemStats[] = [
                'item_id' => $itemData->item_id,
                'item_name' => $itemData->item->name ?? 'N/A',
                'brand_name' => $itemData->brand->name ?? 'N/A',
                'bag_type' => bag_type_name($itemData->bag_type),
                'bag_size' => $itemData->bag_size,
                'no_of_bags' => $itemData->no_of_bags,
                'rate' => $itemData->rate,
                'do_qty' => $iDoQty,
                'dc_qty' => $iDcQty,
                'remaining_qty' => $iRemQty,
            ];
        }

        // Delivery Challans details
        $challans = [];
        foreach ($delivery_order->delivery_challans as $dc) {
            $dcDispatchedQty = (float) \App\Models\Sales\DeliveryChallanData::where('delivery_challan_id', $dc->id)
                ->whereIn('do_data_id', $doDataIds)
                ->sum('qty');
            if ($dcDispatchedQty <= 0) {
                $dcDispatchedQty = (float) ($dc->pivot->qty ?? 0);
            }

            $dcTrucks = \App\Models\Sales\DeliveryChallanData::where('delivery_challan_id', $dc->id)
                ->whereIn('do_data_id', $doDataIds)
                ->pluck('truck_no')
                ->filter()
                ->unique()
                ->implode(', ');

            $challans[] = [
                'id' => $dc->id,
                'reference_number' => $dc->reference_number ?? ('DC #' . $dc->id),
                'dc_no' => $dc->dc_no,
                'dispatch_date' => $dc->dispatch_date,
                'status' => $dc->am_approval_status ?? 'pending',
                'qty' => $dcDispatchedQty,
                'truck_no' => $dcTrucks ?: 'N/A',
            ];
        }

        return view('management.sales.delivery-order.doStatsModal', compact(
            'delivery_order',
            'doQty',
            'dcQty',
            'remainingQty',
            'itemStats',
            'challans',
            'totalItemDoQty',
            'totalItemDcQty',
            'totalItemRemainingQty',
            'totalItemBags'
        ));
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
                'jv_amount' => $request->jv_amount ?? 0,
                'withhold_amount' => $request->withhold_amount ?? 0,
                'withhold_for_rv_id' => $withhold_rv_id,
                'dispatch_date' => $request->dispatch_date, // this is DO date
                'reference_no' => $this->getNumber($request, null, $request->dispatch_date),
                'ref_no' => $request->ref_no,
                'payment_term_id' => $request->payment_term_id ?? (PaymentTerm::first())->id,
                'sauda_type' => $request->sauda_type,
                'location_id' => $request->location_id,
                'arrival_location_id' => is_array($request->arrival_id) ? implode(',', $request->arrival_id) : $request->arrival_id,
                'sub_arrival_location_id' => is_array($request->storage_id) ? implode(',', $request->storage_id) : $request->storage_id,
                // 'line_desc' => $request->line_desc,
                // 'delivery_date' => $request->delivery_date,
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
                                ->join('delivery_order', 'delivery_order.id', '=', 'delivery_order_receipt_voucher.delivery_order_id')
                                ->where('delivery_order_receipt_voucher.receipt_voucher_advance_id', $adv->id)
                                ->where('delivery_order.am_approval_status', '!=', 'rejected')
                                ->sum('delivery_order_receipt_voucher.amount');
                            $remaining = doubleval($adv->net_amount) - doubleval($spent);

                            if ($rv_val == $request->withhold_for_rv && doubleval($request->withhold_amount ?? 0) > $remaining) {
                                DB::rollBack();
                                return response()->json("Withhold amount cannot be greater than the selected voucher balance ($remaining).", 422);
                            }

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
                                ->where('delivery_order.am_approval_status', '!=', 'rejected')
                                ->sum('delivery_order_receipt_voucher.amount');



                            $jv_spent = DB::table('journal_voucher_details')
                                ->join('journal_vouchers', 'journal_vouchers.id', '=', 'journal_voucher_details.journal_voucher_id')
                                ->whereNull('journal_vouchers.deleted_at')
                                ->whereNull('journal_voucher_details.deleted_at')
                                ->where('receipt_voucher_id', $rv->id)
                                ->sum('debit_amount');

                            $spent += $jv_spent;

                            $remaining = doubleval($linked_amount) - doubleval($spent);

                            if ($rv_val == $request->withhold_for_rv && doubleval($request->withhold_amount ?? 0) > $remaining) {
                                DB::rollBack();
                                return response()->json("Withhold amount cannot be greater than the selected voucher balance ($remaining).", 422);
                            }

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

            // Handle Journal Vouchers
            $journal_vouchers = $request->journal_vouchers ?? [];
            if ($salesOrder && $salesOrder->pay_type_id == 10) {
                foreach ($journal_vouchers as $jv_val) {
                    $jv_id = str_replace('jv_', '', $jv_val);
                    
                    $total_credit = DB::table('journal_voucher_details')
                        ->where('journal_voucher_id', $jv_id)
                        ->where('acc_id', function($q) use ($request) {
                            $q->select('account_id')->from('customers')->where('id', $request->customer_id);
                        })
                        ->sum('credit_amount');
                        
                    if ($total_credit > 0) {
                        $spent = DB::table('settlement_adjustments')
                            ->where('reference_type', 'journal_voucher')
                            ->where('reference_id', $jv_id)
                            ->sum('amount');
                            
                        $remaining = doubleval($total_credit) - doubleval($spent);
                        
                        if ($remaining > 0) {
                            if ($jv_val == $request->withhold_for_rv && doubleval($request->withhold_amount ?? 0) > $remaining) {
                                DB::rollBack();
                                return response()->json("Withhold amount cannot be greater than the selected voucher balance ($remaining).", 422);
                            }

                            $withhold_amount = ($jv_val == $request->withhold_for_rv) ? doubleval($request->withhold_amount ?? 0) : 0;
                            $adjusted_amount = max(0, $remaining - $withhold_amount);

                            if ($adjusted_amount > 0 || $withhold_amount > 0) {
                                DB::table('settlement_adjustments')->insert([
                                    'reference_type' => 'journal_voucher',
                                    'reference_id' => $jv_id,
                                    'voucher_no' => $delivery_order->reference_no,
                                    'amount' => $adjusted_amount,
                                    'created_at' => now(),
                                    'updated_at' => now(),
                                ]);
                            }
                        }
                    }
                }
            }


            $spent_qty = $salesOrder->delivery_orders
                ->reject(function($do) {
                    return $do->am_approval_status === "rejected" || $do->is_auto_created_from_so;
                })
                ->flatMap->delivery_order_data
                ->sum("qty");
            $total_qty = $salesOrder?->sales_order_data?->first()->qty;
            $remaining_qty = $total_qty - $spent_qty;


            foreach ($request->item_id as $key => $item) {
                // $balance = delivery_order_balance($request->so_data_id[$key]);

                // if($request->no_of_bags[$key] > $balance) {
                //     return response()->json("Total balance is $balance. you can not exceed this balance", 422);
                // }

                if ($remaining_qty < (int) $request->qty[$key]) {
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
            ->when($request->filled('search_for_filter'), function ($q) use ($request) {
                $searchTerm = '%' . strtolower($request->search_for_filter) . '%';
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
            ->when($request->filled('do_no_for_filter'), function ($q) use ($request) {
                $q->where('reference_no', 'like', '%' . $request->do_no_for_filter . '%');
            })
            // Filter by SO No
            ->when($request->filled('so_id_for_filter') && $request->so_id_for_filter != 'all', function ($q) use ($request) {
                $q->where('so_id', $request->so_id_for_filter);
            })
            // Filter by Customer
            ->when($request->filled('customer_id_for_filter') && $request->customer_id_for_filter != 'all', function ($q) use ($request) {
                $q->where('customer_id', $request->customer_id_for_filter);
            })
            // Filter by Item (through delivery_order_data relationship)
            ->when($request->filled('item_id_for_filter') && $request->item_id_for_filter != 'all', function ($q) use ($request) {
                $q->whereHas('delivery_order_data', function ($sq) use ($request) {
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
            // Filter by Delivery Date Range (dispatch_date)
            ->when($request->filled('delivery_date_for_filter'), function ($q) use ($request) {
                $dates = explode(' - ', $request->delivery_date_for_filter);
                if (count($dates) == 2) {
                    $q->whereBetween('dispatch_date', [trim($dates[0]), trim($dates[1])]);
                }
            })
            // Filter by Status
            ->when($request->filled('status_for_filter') && $request->status_for_filter != 'all', function ($q) use ($request) {
                $q->where('am_approval_status', $request->status_for_filter);
            })
            ->orderBy("reference_no", "desc")
            ->paginate($perPage);

        $groupedData = [];

        foreach ($delivery_orders as $delivery_order) {
            // dd();
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
                'delivery_date' => $delivery_order->salesOrder->delivery_date,
                'id' => $delivery_order->id,
                'customer_id' => $delivery_order->customer_id,
                'status' => $delivery_order->am_approval_status,
                'created_at' => $delivery_order->created_at,
                'customer' => $delivery_order->customer,
                'rowspan' => count($itemRows),
                'items' => $itemRows,
            ];



        }
        // dd($groupedData);
        return view('management.sales.delivery-order.getList', [
            'DeliveryOrders' => $delivery_orders,           // for pagination
            'groupedDeliveryOrders' => $groupedData,  // our grouped data
        ]);
    }

    public function getNumber(Request $request, $locationId = null, $contractDate = null)
    {

        $date = Carbon::parse($contractDate ?? $request->contract_date)->format('Y-m-d');

        $prefix = 'DO-' . Carbon::parse($contractDate ?? $request->contract_date)->format('Y-m-d');

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

        $so_no = 'DO-' . $datePart . '-' . str_pad($newNumber, 3, '0', STR_PAD_LEFT);

        if (!$locationId && !$contractDate) {
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
    
                if (DeliveryOrder::where('so_id', $saleOrder->id)->where('is_auto_created_from_so', true)->exists()) {
                    return true;
                }

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
            if (!$factory) {
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
            if (!$section) {
                continue;
            }
            $factoryId = $section->arrival_location_id;
            $sectionMap[$factoryId][] = [
                'id' => $section->id,
                'text' => $section->name . " (" . $section?->arrivalLocation?->name . ")",
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
            ->reject(function($do) {
                return $do->am_approval_status === "rejected" || $do->is_auto_created_from_so;
            })
            ->flatMap->delivery_order_data
            ->sum('qty');

        $spent_qty = $spent;
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
            ->whereHas('receiptVoucher', function($q) {
                $q->where('am_approval_status', 'approved');
            })
            ->get()
            ->map(function ($adv) {
                // Calculate spent amount for this specific advance
                $spent = DB::table('delivery_order_receipt_voucher')
                    ->join('delivery_order', 'delivery_order.id', '=', 'delivery_order_receipt_voucher.delivery_order_id')
                    ->where('delivery_order_receipt_voucher.receipt_voucher_advance_id', $adv->id)
                    ->where('delivery_order.am_approval_status', '!=', 'rejected')
                    ->sum('delivery_order_receipt_voucher.amount');
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
            $receipt_vouchers = ReceiptVoucher::with([
                'delivery_orders',
                'items' => function ($query) use ($sale_order_id) {
                    $query->where("reference_type", "sale_order")
                        ->where("reference_id", $sale_order_id);
                }
            ])
                ->where("customer_id", $customer_id)
                ->where("am_approval_status", "approved")
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
                    ->where('delivery_order.am_approval_status', '!=', 'rejected')
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

    public function get_journal_vouchers(Request $request)
    {
        $customer_id = $request->customer_id;
        $data = [];

        if (!$customer_id) {
            return $data;
        }

        $customer = Customer::find($customer_id);
        if (!$customer || !$customer->account_id) {
            return $data;
        }

        // Fetch JVs where this customer's account has a credit balance
        $jv_details = DB::table('journal_voucher_details')
            ->join('journal_vouchers', 'journal_vouchers.id', '=', 'journal_voucher_details.journal_voucher_id')
            ->whereNull('journal_vouchers.deleted_at')
            ->whereNull('journal_voucher_details.deleted_at')
            ->where('journal_voucher_details.acc_id', $customer->account_id)
            ->where('journal_voucher_details.credit_amount', '>', 0)
            ->select('journal_vouchers.id as jv_id', 'journal_vouchers.jv_no', 'journal_vouchers.jv_date', 'journal_voucher_details.id as jv_detail_id', 'journal_voucher_details.credit_amount')
            ->get();

        $delivery_order_id = $request->delivery_order_id;
        $grouped_details = $jv_details->groupBy('jv_id');

        foreach ($grouped_details as $jv_id => $details) {
            $total_credit = $details->sum('credit_amount');
            $spent_query = DB::table('settlement_adjustments')
                ->where('reference_type', 'journal_voucher')
                ->where('reference_id', $jv_id);
                
            if ($delivery_order_id) {
                $do = DeliveryOrder::find($delivery_order_id);
                if ($do) {
                    $spent_query->where('voucher_no', '!=', $do->reference_no);
                }
            }
            
            $spent = $spent_query->sum('amount');
            $remaining = doubleval($total_credit) - doubleval($spent);

            if ($remaining > 0) {
                $first = $details->first();
                $data[] = [
                    'id' => "jv_{$jv_id}",
                    'text' => "{$first->jv_no} ({$remaining})",
                    'amount' => $remaining,
                    'date' => $first->jv_date ? date('Y-m-d', strtotime($first->jv_date)) : null,
                ];
            }
        }

        return $data;
    }

    public function destroy(DeliveryOrder $delivery_order)
    {
        if (in_array(strtolower($delivery_order->am_approval_status ?? ''), ['approved', 'rejected'])) {
            return response()->json([
                'error' => "Delivery Order has been {$delivery_order->am_approval_status} and cannot be deleted.",
                'message' => "Delivery Order has been {$delivery_order->am_approval_status} and cannot be deleted."
            ], 422);
        }

        if ($delivery_order) {
            $delivery_order->delivery_order_data()->delete();
        }

        DB::table('settlement_adjustments')
            ->where('reference_type', 'journal_voucher')
            ->where('voucher_no', $delivery_order->reference_no)
            ->delete();

        $delivery_order->delete();

        return response()->json(['success' => 'Delivery order has been deleted!']);

    }

    public function edit(DeliveryOrder $delivery_order)
    {
        $delivery_order->load('receipt_vouchers', 'locations');
        $sale_orders = SalesOrder::with("locations")
            ->select('reference_no', 'id', 'pay_type_id', 'transporter_used')
            ->where('am_approval_status', 'approved')
            ->where('customer_id', $delivery_order->customer_id)
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
                    ->join('delivery_order', 'delivery_order.id', '=', 'delivery_order_receipt_voucher.delivery_order_id')
                    ->where('delivery_order_receipt_voucher.receipt_voucher_advance_id', $adv->id)
                    ->where('delivery_order_receipt_voucher.delivery_order_id', '!=', $delivery_order->id)
                    ->where('delivery_order.am_approval_status', '!=', 'rejected')
                    ->sum('delivery_order_receipt_voucher.amount');
                $adv->remaining_amount = doubleval($adv->net_amount) - doubleval($spent);
                $adv->unified_id = "adv_{$adv->id}";
                $adv->unified_text = "advance ({$adv->net_amount})";
                $adv->date = $adv->receiptVoucher && $adv->receiptVoucher->rv_date
                    ? $adv->receiptVoucher->rv_date->format('Y-m-d')
                    : null;
                return $adv;
            })
            ->filter(function ($adv) use ($delivery_order) {
                $is_attached = $delivery_order->receipt_vouchers->contains('pivot.receipt_voucher_advance_id', $adv->id);
                $is_approved = $adv->receiptVoucher && $adv->receiptVoucher->am_approval_status === 'approved';
                return ($adv->remaining_amount > 0 && $is_approved) || $is_attached;
            });

        // 2. Fetch Regular RVs linked to the Sale Order of this DO
        $rvsList = collect();
        if ($delivery_order->so_id) {
            $rvsList = ReceiptVoucher::with(['delivery_orders', 'items'])
                ->whereHas("items", function ($query) use ($delivery_order) {
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
                        ->where('delivery_order.am_approval_status', '!=', 'rejected')
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
                    $is_attached = $delivery_order->receipt_vouchers->whereNull('pivot.receipt_voucher_advance_id')->contains('id', $rv->id);
                    $is_approved = $rv->am_approval_status === 'approved';
                    return ($rv->remaining_amount > 0 && $is_approved) || $is_attached;
                });
        }

        $receipt_vouchers = $advancesList->concat($rvsList)->values();
        $latestLog = $delivery_order->approvalLogs()->with(['user', 'role'])->latest()->first();

        // 3. Fetch all JVs with available balance + JVs linked to this DO
        $customer = Customer::find($delivery_order->customer_id);
        $journal_vouchers = [];
        
        if ($customer && $customer->account_id) {
            $jv_details = DB::table('journal_voucher_details')
                ->join('journal_vouchers', 'journal_vouchers.id', '=', 'journal_voucher_details.journal_voucher_id')
                ->whereNull('journal_vouchers.deleted_at')
                ->whereNull('journal_voucher_details.deleted_at')
                ->where('journal_voucher_details.acc_id', $customer->account_id)
                ->where('journal_voucher_details.credit_amount', '>', 0)
                ->select('journal_vouchers.id as jv_id', 'journal_vouchers.jv_no', 'journal_vouchers.jv_date', 'journal_voucher_details.id as jv_detail_id', 'journal_voucher_details.credit_amount')
                ->get();

            $linked_jvs = DB::table('settlement_adjustments')
                ->where('reference_type', 'journal_voucher')
                ->where('voucher_no', $delivery_order->reference_no)
                ->get();

            $linked_jv_map = $linked_jvs->keyBy('reference_id');
            $rv_withhold_exists = $delivery_order->receipt_vouchers->where('pivot.withhold_amount', '>', 0)->count() > 0;

            $grouped_details = $jv_details->groupBy('jv_id');

            foreach ($grouped_details as $jv_id => $details) {
                $total_credit = $details->sum('credit_amount');
                $spent = DB::table('settlement_adjustments')
                    ->where('reference_type', 'journal_voucher')
                    ->where('reference_id', $jv_id)
                    ->where('voucher_no', '!=', $delivery_order->reference_no)
                    ->sum('amount');
                
                $remaining = doubleval($total_credit) - doubleval($spent);
                $is_linked = $linked_jv_map->has($jv_id);
                
                $is_withheld = false;
                if ($is_linked && !$rv_withhold_exists && $delivery_order->withhold_amount > 0) {
                    $adj_amount = $linked_jv_map[$jv_id]->amount;
                    if (abs(($remaining - $adj_amount) - $delivery_order->withhold_amount) < 0.01) {
                        $is_withheld = true;
                    }
                }

                if ($remaining > 0 || $is_linked) {
                    $first = $details->first();
                    $journal_vouchers[] = [
                        'id' => $jv_id,
                        'text' => "{$first->jv_no} (Remaining: {$remaining})",
                        'amount' => $remaining,
                        'is_selected' => $is_linked,
                        'is_withheld' => $is_withheld,
                    ];
                }
            }
        }

        return view('management.sales.delivery-order.edit', compact('sale_order_of_delivery_order', 'payment_terms', 'customers', 'items', 'sale_orders', 'delivery_order', 'receipt_vouchers', 'journal_vouchers', 'bag_types', 'latestLog'));

    }

    public function update(DeliveryOrderRequest $request, DeliveryOrder $delivery_order)
    {
        DB::beginTransaction();
        $withhold_rv_id = null;


        if (in_array(strtolower($delivery_order->am_approval_status ?? ''), ['approved', 'rejected'])) {
            if ($request->do_status == $delivery_order->do_status || !$request->has('do_status')) {
                return response()->json([
                    'error' => "Delivery Order has been {$delivery_order->am_approval_status} and cannot be updated.",
                    'message' => "Delivery Order has been {$delivery_order->am_approval_status} and cannot be updated."
                ], 422);
            }
            
            $delivery_order->update([
                'do_status' => $request->do_status
            ]);
            DB::commit();
            return response()->json(["message" => "Delivery Order Status updated successfully.", "data" => "Delivery Order Status updated successfully."], 200);
        }

        if ($delivery_order->do_status == 'closed' && $request->do_status != 'closed') {
            return response()->json("This Delivery Order is closed and its status cannot be changed.", 400);
        }

        if ($request->withhold_for_rv && str_starts_with($request->withhold_for_rv, 'rv_')) {
            $withhold_rv_id = str_replace('rv_', '', $request->withhold_for_rv);
        }

        try {
            $delivery_order->update([
                'customer_id' => $request->customer_id,
                'so_id' => $request->sale_order_id,
                'advance_amount' => $request->advance_amount ?? 0,
                'jv_amount' => $request->jv_amount ?? 0,
                'withhold_amount' => $request->withhold_amount ?? 0,
                'withhold_for_rv_id' => $withhold_rv_id,
                'dispatch_date' => $request->dispatch_date, // this is DO date
                'reference_no' => $request->reference_no,
                'ref_no' => $request->ref_no,
                'payment_term_id' => $request->payment_term_id ?? (PaymentTerm::first())->id,
                'sauda_type' => $request->sauda_type,
                'location_id' => $request->location_id,
                'arrival_location_id' => is_array($request->arrival_id) ? implode(',', $request->arrival_id) : $request->arrival_id,
                'sub_arrival_location_id' => is_array($request->storage_id) ? implode(',', $request->storage_id) : $request->storage_id,
                // 'delivery_date' => $request->delivery_date,
                'line_desc' => $request->remarks ?? "",
                'remarks' => $request->remarks ?? "",
                'am_approval_status' => 'pending',
                'am_change_made' => 1,
                'so_withhold_percentage' => $request->so_withhold_percentage ?? 0,
                'so_held_amount' => $request->so_held_amount ?? 0,
                'do_status' => $request->do_status ?? $delivery_order->do_status,
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
                                ->join('delivery_order', 'delivery_order.id', '=', 'delivery_order_receipt_voucher.delivery_order_id')
                                ->where('delivery_order_receipt_voucher.receipt_voucher_advance_id', $adv->id)
                                ->where('delivery_order_receipt_voucher.delivery_order_id', '!=', $delivery_order->id)
                                ->where('delivery_order.am_approval_status', '!=', 'rejected')
                                ->sum('delivery_order_receipt_voucher.amount');
                            $remaining = doubleval($adv->net_amount) - doubleval($spent);

                            if ($rv_val == $request->withhold_for_rv && doubleval($request->withhold_amount ?? 0) > $remaining) {
                                DB::rollBack();
                                return response()->json("Withhold amount cannot be greater than the selected voucher balance ($remaining).", 422);
                            }

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
                                ->where('delivery_order.am_approval_status', '!=', 'rejected')
                                ->sum('delivery_order_receipt_voucher.amount');

                            $jv_spent = DB::table('journal_voucher_details')
                                ->join('journal_vouchers', 'journal_vouchers.id', '=', 'journal_voucher_details.journal_voucher_id')
                                ->whereNull('journal_vouchers.deleted_at')
                                ->whereNull('journal_voucher_details.deleted_at')
                                ->where('receipt_voucher_id', $rv->id)
                                ->sum('debit_amount');

                            $spent += $jv_spent;

                            $remaining = doubleval($linked_amount) - doubleval($spent);

                            if ($rv_val == $request->withhold_for_rv && doubleval($request->withhold_amount ?? 0) > $remaining) {
                                DB::rollBack();
                                return response()->json("Withhold amount cannot be greater than the selected voucher balance ($remaining).", 422);
                            }

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

            // Handle Journal Vouchers
            DB::table('settlement_adjustments')
                ->where('reference_type', 'journal_voucher')
                ->where('voucher_no', $delivery_order->reference_no)
                ->delete();

            $journal_vouchers = $request->journal_vouchers ?? [];
            if ($salesOrder && $salesOrder->pay_type_id == 10) {
                foreach ($journal_vouchers as $jv_val) {
                    $jv_id = str_replace('jv_', '', $jv_val);
                    
                    $total_credit = DB::table('journal_voucher_details')
                        ->where('journal_voucher_id', $jv_id)
                        ->where('acc_id', function($q) use ($request) {
                            $q->select('account_id')->from('customers')->where('id', $request->customer_id);
                        })
                        ->sum('credit_amount');
                        
                    if ($total_credit > 0) {
                        $spent = DB::table('settlement_adjustments')
                            ->where('reference_type', 'journal_voucher')
                            ->where('reference_id', $jv_id)
                            ->sum('amount');
                            
                        $remaining = doubleval($total_credit) - doubleval($spent);
                        
                        if ($remaining > 0) {
                            if ($jv_val == $request->withhold_for_rv && doubleval($request->withhold_amount ?? 0) > $remaining) {
                                DB::rollBack();
                                return response()->json("Withhold amount cannot be greater than the selected voucher balance ($remaining).", 422);
                            }

                            $withhold_amount = ($jv_val == $request->withhold_for_rv) ? doubleval($request->withhold_amount ?? 0) : 0;
                            $adjusted_amount = max(0, $remaining - $withhold_amount);

                            if ($adjusted_amount > 0 || $withhold_amount > 0) {
                                DB::table('settlement_adjustments')->insert([
                                    'reference_type' => 'journal_voucher',
                                    'reference_id' => $jv_id,
                                    'voucher_no' => $delivery_order->reference_no,
                                    'amount' => $adjusted_amount,
                                    'created_at' => now(),
                                    'updated_at' => now(),
                                ]);
                            }
                        }
                    }
                }
            }

            $salesOrder = SalesOrder::with('sales_order_data')->find($request->sale_order_id);
            $spent_qty = $salesOrder->delivery_orders()
                ->where("am_approval_status", "!=", "rejected")
                ->where(function($query) {
                    $query->whereNull("is_auto_created_from_so")
                          ->orWhere("is_auto_created_from_so", "!=", 1);
                })
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
                if ((int) $request->qty[$key] > (int) ($remaining_qty + $current_qty)) {
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
                'text' => $subarrival_location->name . " (" . $subarrival_location?->arrivalLocation?->name . ")",
            ];
        }

        return $data;
    }
    public function get_balance_against_second_weighbridge(Request $request)
    {
        $delivery_order_id = $request->delivery_order_id;

        $balance = get_second_weighbridge_balance_by_delivery_order($delivery_order_id);

        return $balance;
    }
}


