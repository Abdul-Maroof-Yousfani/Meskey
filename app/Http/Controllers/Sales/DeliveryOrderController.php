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
use App\Models\ApprovalsModule\ApprovalModuleRole;
use App\Services\AuditLogService;
use App\Models\AuditLog;
use App\Models\User;

class DeliveryOrderController extends Controller
{
    public function index()
    {
        
        abort_if(!canAccess('sales-delivery-order-list') && !auth()->user()->can('sales-delivery-order-list'), 403);
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
        abort_if(!canAccess('sales-delivery-order-list') && !auth()->user()->can('sales-delivery-order-list'), 403);

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
        abort_if(!canAccess('sales-delivery-order-list') && !auth()->user()->can('sales-delivery-order-list'), 403);
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
        abort_if(!canAccess('sales-delivery-order-create') && !auth()->user()->can('sales-delivery-order-create'), 403);
        $sale_orders = SalesOrder::select('reference_no', 'id', 'transporter_used')
            ->where('am_approval_status', 'approved')
            ->activeContract()
            ->where('delivery_date', '>=', now()->toDateString())
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
        abort_if(!canAccess('sales-delivery-order-create') && !auth()->user()->can('sales-delivery-order-create'), 403);
        DB::beginTransaction();

        $withhold_rv_id = null;
        if ($request->withhold_for_rv && str_starts_with($request->withhold_for_rv, 'rv_')) {
            $withhold_rv_id = str_replace('rv_', '', $request->withhold_for_rv);
        }

        $salesOrder = SalesOrder::find($request->sale_order_id);
        if ($salesOrder && $salesOrder->isClosed()) {
            return response()->json([
                'error' => "Sale Order #{$salesOrder->reference_no} is closed and cannot be used to create Delivery Orders.",
                'message' => "Sale Order #{$salesOrder->reference_no} is closed and cannot be used to create Delivery Orders."
            ], 422);
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
                                
                            $jv_spent = DB::table('journal_voucher_details')
                                ->join('journal_vouchers', 'journal_vouchers.id', '=', 'journal_voucher_details.journal_voucher_id')
                                ->whereNull('journal_vouchers.deleted_at')
                                ->whereNull('journal_voucher_details.deleted_at')
                                ->where('receipt_voucher_id', $adv->receipt_voucher_id)
                                ->sum('debit_amount');
                                
                            $spent += $jv_spent;
                                
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
                        ->join('journal_vouchers', 'journal_vouchers.id', '=', 'journal_voucher_details.journal_voucher_id')
                        ->whereNull('journal_vouchers.deleted_at')
                        ->whereNull('journal_voucher_details.deleted_at')
                        ->where('journal_voucher_id', $jv_id)
                        ->where('acc_id', function($q) use ($request) {
                            $q->select('account_id')->from('customers')->where('id', $request->customer_id);
                        })
                        ->where(function ($q) use ($request, $jv_id) {
                            $q->where('journal_voucher_details.sales_order_id', $request->sale_order_id)
                              ->orWhereExists(function ($sub) use ($request, $jv_id) {
                                  $sub->select(DB::raw(1))
                                      ->from('journal_voucher_details as jvd_sub')
                                      ->where('jvd_sub.journal_voucher_id', $jv_id)
                                      ->whereNull('jvd_sub.deleted_at')
                                      ->where('jvd_sub.sales_order_id', $request->sale_order_id);
                              });
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


            $firstSoData = $salesOrder?->sales_order_data?->first();
            $spent_qty = $firstSoData ? delivery_order_qty_used($firstSoData->id) : 0;
            $remaining_qty = $firstSoData ? delivery_order_qty_balance($firstSoData->id) : 0;

            foreach ($request->item_id as $key => $item) {
                $soDataId = $request->so_data_id[$key] ?? $firstSoData?->id;
                $itemRemaining = $soDataId ? delivery_order_qty_balance($soDataId) : $remaining_qty;

                if ($itemRemaining < (float) $request->qty[$key]) {
                    return response()->json("Total remaining qty(kg): $itemRemaining. you can not exceed this balance", 422);
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

            $newValues = $this->buildDeliveryOrderAuditValues($delivery_order);
            AuditLogService::log(
                $delivery_order,
                'delivery_order_created',
                "Delivery Order #{$delivery_order->reference_no} created.",
                null,
                $newValues
            );

            DB::commit();

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json(['error' => $e->getMessage()], 500);

        }

        return response()->json(['success' => 'Delivery Order has been created']);
    }

    public function getList(Request $request)
    {
        abort_if(!canAccess('sales-delivery-order-list') && !auth()->user()->can('sales-delivery-order-list'), 403);
        $authUser = auth()->user();
        $isSuperAdmin = $authUser && ($authUser->user_type === 'super-admin' || $authUser->hasRole('Admin') || $authUser->hasRole('admin'));

        $userRoleIds = (new DeliveryOrder)->getUserRoleIdsForApproval($authUser);
        $isApprover = false;
        if (!$isSuperAdmin && !empty($userRoleIds)) {
            $doModule = (new DeliveryOrder)->getApprovalModule();
            $moduleId = $doModule ? $doModule->id : 17;
            $isApprover = ApprovalModuleRole::where('module_id', $moduleId)
                ->whereIn('role_id', $userRoleIds)
                ->exists();
        }

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
            ->when(!$isSuperAdmin && !$isApprover, function ($q) use ($authUser) {
                return $q->where(function ($subQ) use ($authUser) {
                    $subQ->where('created_by', $authUser->id)
                        ->orWhereHas('salesOrder', function ($soQ) use ($authUser) {
                            $soQ->where('created_by', $authUser->id);
                        });
                });
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
        abort_if(!canAccess('sales-delivery-order-list') && !auth()->user()->can('sales-delivery-order-list') && !canAccess('sales-delivery-order-create') && !auth()->user()->can('sales-delivery-order-create'), 403);

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
        abort_if(!canAccess('sales-delivery-order-list') && !auth()->user()->can('sales-delivery-order-list') && !canAccess('sales-delivery-order-create') && !auth()->user()->can('sales-delivery-order-create'), 403);

        $customer_id = $request->customer_id;

        $saleOrders = SalesOrder::with("locations")
            ->select('reference_no', 'id', 'pay_type_id', 'transporter_used')
            ->where('am_approval_status', 'approved')
            ->activeContract()
            ->where('customer_id', $customer_id)
            ->where('delivery_date', '>=', now()->toDateString())
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
                    $qtyBalance = delivery_order_qty_balance($data->id);
                    $bagBalance = delivery_order_balance($data->id);
                    if ($qtyBalance > 0 || $bagBalance > 0) {
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
        abort_if(!canAccess('sales-delivery-order-list') && !auth()->user()->can('sales-delivery-order-list') && !canAccess('sales-delivery-order-create') && !auth()->user()->can('sales-delivery-order-create'), 403);

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
        abort_if(!canAccess('sales-delivery-order-list') && !auth()->user()->can('sales-delivery-order-list') && !canAccess('sales-delivery-order-create') && !auth()->user()->can('sales-delivery-order-create'), 403);

        $so_id = $request->so_id;

        $items = Product::select('id', 'name')->get();
        $sale_order = SalesOrder::with('delivery_order_transactions', 'locations', 'delivery_orders', 'sales_order_data')
            ->find($so_id);

        $firstSoData = $sale_order?->sales_order_data?->first();
        if ($firstSoData) {
            $spent = delivery_order_qty_used($firstSoData->id);
            $remaining_qty = delivery_order_qty_balance($firstSoData->id);
        } else {
            $spent = 0;
            $remaining_qty = 0;
        }

        $bag_types = BagType::select('id', 'name')->get();

        return view('management.sales.delivery-order.getItem', compact('sale_order', 'items', 'bag_types', 'spent', 'remaining_qty'));
    }

    public function get_receipt_vouchers(Request $request)
    {
        abort_if(!canAccess('sales-delivery-order-list') && !auth()->user()->can('sales-delivery-order-list') && !canAccess('sales-delivery-order-create') && !auth()->user()->can('sales-delivery-order-create'), 403);

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
                    
                $jv_spent = DB::table('journal_voucher_details')
                    ->join('journal_vouchers', 'journal_vouchers.id', '=', 'journal_voucher_details.journal_voucher_id')
                    ->whereNull('journal_vouchers.deleted_at')
                    ->whereNull('journal_voucher_details.deleted_at')
                    ->where('receipt_voucher_id', $adv->receipt_voucher_id)
                    ->sum('debit_amount');
                    
                $spent += $jv_spent;
                
                $adv->remaining_amount = doubleval($adv->net_amount) - doubleval($spent);
                return $adv;
            })
            ->filter(fn($adv) => $adv->remaining_amount > 0);

        foreach ($advances as $adv) {
            $data[] = [
                'id' => "adv_{$adv->id}",
                'text' => "advance ({$adv->remaining_amount})",
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
        abort_if(!canAccess('sales-delivery-order-list') && !auth()->user()->can('sales-delivery-order-list') && !canAccess('sales-delivery-order-create') && !auth()->user()->can('sales-delivery-order-create'), 403);

        $customer_id = $request->customer_id;
        $sale_order_id = $request->sale_order_id;
        $data = [];

        if (!$customer_id || !$sale_order_id) {
            return $data;
        }

        $customer = Customer::find($customer_id);
        if (!$customer || !$customer->account_id) {
            return $data;
        }

        // Fetch JVs where this customer's account has a credit balance AND is linked to this specific SO
        $jv_details = DB::table('journal_voucher_details')
            ->join('journal_vouchers', 'journal_vouchers.id', '=', 'journal_voucher_details.journal_voucher_id')
            ->whereNull('journal_vouchers.deleted_at')
            ->whereNull('journal_voucher_details.deleted_at')
            ->where('journal_voucher_details.acc_id', $customer->account_id)
            ->where('journal_voucher_details.credit_amount', '>', 0)
            ->where(function ($q) use ($sale_order_id) {
                $q->where('journal_voucher_details.sales_order_id', $sale_order_id)
                  ->orWhereExists(function ($sub) use ($sale_order_id) {
                      $sub->select(DB::raw(1))
                          ->from('journal_voucher_details as jvd_sub')
                          ->whereColumn('jvd_sub.journal_voucher_id', 'journal_vouchers.id')
                          ->whereNull('jvd_sub.deleted_at')
                          ->where('jvd_sub.sales_order_id', $sale_order_id);
                  });
            })
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
        abort_if(!canAccess('sales-delivery-order-create') && !auth()->user()->can('sales-delivery-order-create'), 403);
        if (in_array(strtolower($delivery_order->am_approval_status ?? ''), ['approved', 'rejected'])) {
            return response()->json([
                'error' => "Delivery Order has been {$delivery_order->am_approval_status} and cannot be deleted.",
                'message' => "Delivery Order has been {$delivery_order->am_approval_status} and cannot be deleted."
            ], 422);
        }

        $deletedValues = $this->buildDeliveryOrderAuditValues($delivery_order);
        AuditLogService::log(
            $delivery_order,
            'delivery_order_deleted',
            "Delivery Order #{$delivery_order->reference_no} deleted.",
            $deletedValues,
            null
        );

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
        abort_if(!canAccess('sales-delivery-order-create') && !auth()->user()->can('sales-delivery-order-create'), 403);
        $delivery_order->load('receipt_vouchers', 'locations');
        $sale_orders = SalesOrder::with("locations")
            ->select('reference_no', 'id', 'pay_type_id', 'transporter_used')
            ->where('am_approval_status', 'approved')
            ->where('customer_id', $delivery_order->customer_id)
            ->where(function ($q) use ($delivery_order) {
                // Show non-expired SOs OR the currently linked SO (even if expired)
                $q->where('delivery_date', '>=', now()->toDateString())
                  ->orWhere('id', $delivery_order->so_id);
            })
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
            $so_id = $delivery_order->so_id;
            $linked_jvs = DB::table('settlement_adjustments')
                ->where('reference_type', 'journal_voucher')
                ->where('voucher_no', $delivery_order->reference_no)
                ->get();
            $linked_jv_ids = $linked_jvs->pluck('reference_id')->toArray();

            $jv_details = DB::table('journal_voucher_details')
                ->join('journal_vouchers', 'journal_vouchers.id', '=', 'journal_voucher_details.journal_voucher_id')
                ->whereNull('journal_vouchers.deleted_at')
                ->whereNull('journal_voucher_details.deleted_at')
                ->where('journal_voucher_details.acc_id', $customer->account_id)
                ->where('journal_voucher_details.credit_amount', '>', 0)
                ->where(function ($q) use ($so_id, $linked_jv_ids) {
                    $q->where(function ($sq) use ($so_id) {
                        $sq->where('journal_voucher_details.sales_order_id', $so_id)
                           ->orWhereExists(function ($sub) use ($so_id) {
                               $sub->select(DB::raw(1))
                                   ->from('journal_voucher_details as jvd_sub')
                                   ->whereColumn('jvd_sub.journal_voucher_id', 'journal_vouchers.id')
                                   ->whereNull('jvd_sub.deleted_at')
                                   ->where('jvd_sub.sales_order_id', $so_id);
                           });
                    });
                    if (!empty($linked_jv_ids)) {
                        $q->orWhereIn('journal_vouchers.id', $linked_jv_ids);
                    }
                })
                ->select('journal_vouchers.id as jv_id', 'journal_vouchers.jv_no', 'journal_vouchers.jv_date', 'journal_voucher_details.id as jv_detail_id', 'journal_voucher_details.credit_amount')
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
        abort_if(!canAccess('sales-delivery-order-create') && !auth()->user()->can('sales-delivery-order-create'), 403);
        if ($delivery_order->salesOrder && $delivery_order->salesOrder->isClosed()) {
            return response()->json([
                'error' => "The Sale Order for this Delivery Order is closed and operations are locked.",
                'message' => "The Sale Order for this Delivery Order is closed and operations are locked."
            ], 422);
        }

        $oldAuditValues = $this->buildDeliveryOrderAuditValues($delivery_order);
        DB::beginTransaction();
        $withhold_rv_id = null;


        if (in_array(strtolower($delivery_order->am_approval_status ?? ''), ['approved', 'rejected'])) {
            if ($request->do_status == $delivery_order->do_status || !$request->has('do_status')) {
                return response()->json([
                    'error' => "Delivery Order has been {$delivery_order->am_approval_status} and cannot be updated.",
                    'message' => "Delivery Order has been {$delivery_order->am_approval_status} and cannot be updated."
                ], 422);
            }
            
            $oldStatus = $delivery_order->do_status;
            $delivery_order->update([
                'do_status' => $request->do_status
            ]);
            AuditLogService::log(
                $delivery_order,
                'delivery_order_status_updated',
                "Delivery Order #{$delivery_order->reference_no} status changed from '{$oldStatus}' to '{$request->do_status}'.",
                ['do_status' => $oldStatus],
                ['do_status' => $request->do_status]
            );
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
                        ->join('journal_vouchers', 'journal_vouchers.id', '=', 'journal_voucher_details.journal_voucher_id')
                        ->whereNull('journal_vouchers.deleted_at')
                        ->whereNull('journal_voucher_details.deleted_at')
                        ->where('journal_voucher_id', $jv_id)
                        ->where('acc_id', function($q) use ($request) {
                            $q->select('account_id')->from('customers')->where('id', $request->customer_id);
                        })
                        ->where(function ($q) use ($request, $jv_id) {
                            $q->where('journal_voucher_details.sales_order_id', $request->sale_order_id)
                              ->orWhereExists(function ($sub) use ($request, $jv_id) {
                                  $sub->select(DB::raw(1))
                                      ->from('journal_voucher_details as jvd_sub')
                                      ->where('jvd_sub.journal_voucher_id', $jv_id)
                                      ->whereNull('jvd_sub.deleted_at')
                                      ->where('jvd_sub.sales_order_id', $request->sale_order_id);
                              });
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
            $firstSoData = $salesOrder?->sales_order_data?->first();
            $spent_qty = $firstSoData ? delivery_order_qty_used($firstSoData->id) : 0;
            $remaining_qty = $firstSoData ? delivery_order_qty_balance($firstSoData->id) : 0;

            // Rebuild line items

            $delivery_order->delivery_order_data()->delete();
            foreach ($request->item_id as $key => $item) {
                $soDataId = $request->so_data_id[$key] ?? $firstSoData?->id;
                $itemRemaining = $soDataId ? delivery_order_qty_balance($soDataId) : $remaining_qty;
                $current_qty = $request->current_qty[$key] ?? 0;
                if ((float) $request->qty[$key] > (float) ($itemRemaining + $current_qty)) {
                    return response()->json("Total KG is: $itemRemaining, you can not exceed this balance", 422);
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

            $delivery_order->refresh();
            $newAuditValues = $this->buildDeliveryOrderAuditValues($delivery_order);

            $changedOld = [];
            $changedNew = [];
            foreach ($newAuditValues as $k => $v) {
                $o = $oldAuditValues[$k] ?? null;
                if ($o != $v) {
                    $changedOld[$k] = $o;
                    $changedNew[$k] = $v;
                }
            }

            if (!empty($changedOld) || !empty($changedNew)) {
                AuditLogService::log(
                    $delivery_order,
                    'delivery_order_updated',
                    "Delivery Order #{$delivery_order->reference_no} updated.",
                    $changedOld,
                    $changedNew
                );
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
        abort_if(!canAccess('sales-delivery-order-list') && !auth()->user()->can('sales-delivery-order-list') && !canAccess('sales-delivery-order-create') && !auth()->user()->can('sales-delivery-order-create'), 403);

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
        abort_if(!canAccess('sales-delivery-order-list') && !auth()->user()->can('sales-delivery-order-list') && !canAccess('sales-delivery-order-create') && !auth()->user()->can('sales-delivery-order-create'), 403);

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
        abort_if(!canAccess('sales-delivery-order-list') && !auth()->user()->can('sales-delivery-order-list') && !canAccess('sales-delivery-order-create') && !auth()->user()->can('sales-delivery-order-create'), 403);

        $delivery_order_id = $request->delivery_order_id;

        $balance = get_second_weighbridge_balance_by_delivery_order($delivery_order_id);

        return $balance;
    }

    /**
     * Build structured representation of Delivery Order for audit logging.
     */
    protected function buildDeliveryOrderAuditValues(DeliveryOrder $deliveryOrder)
    {
        $deliveryOrder->loadMissing([
            'customer',
            'salesOrder',
            'arrivalLocation',
            'subArrivalLocation',
            'delivery_order_data.item',
            'delivery_order_data.brand',
            'receipt_vouchers'
        ]);

        $itemProducts = [];
        $itemQtys = [];
        $itemRates = [];
        $itemBags = [];
        $itemBagTypes = [];
        $itemBagSizes = [];
        $itemBrands = [];
        $totalAmount = 0;
        $totalQty = 0;

        foreach ($deliveryOrder->delivery_order_data as $row) {
            $pName = $row->item ? $row->item->name : "Item #{$row->item_id}";
            $q = (float) $row->qty;
            $r = (float) $row->rate;
            $lineAmt = round($q * $r, 2);
            $totalAmount += $lineAmt;
            $totalQty += $q;

            $itemProducts[] = $pName;
            $itemQtys[] = number_format($q, 2);
            $itemRates[] = number_format($r, 2);
            $itemBags[] = $row->no_of_bags ?? '-';
            $itemBagTypes[] = $row->bag_type ?? '-';
            $itemBagSizes[] = $row->bag_size ?? '-';
            $itemBrands[] = $row->brand?->name ?? '-';
        }

        $rvList = [];
        foreach ($deliveryOrder->receipt_vouchers as $rv) {
            $amt = $rv->pivot->amount ?? 0;
            $rvList[] = "{$rv->unique_no} (Rs. " . number_format((float) $amt, 2) . ")";
        }

        $factoryNames = [];
        if ($deliveryOrder->arrival_location_id) {
            $fIds = explode(',', $deliveryOrder->arrival_location_id);
            $factoryNames = \App\Models\Master\ArrivalLocation::whereIn('id', $fIds)->pluck('name')->toArray();
        }

        $sectionNames = [];
        if ($deliveryOrder->sub_arrival_location_id) {
            $sIds = explode(',', $deliveryOrder->sub_arrival_location_id);
            $sectionNames = \App\Models\Master\ArrivalSubLocation::whereIn('id', $sIds)->pluck('name')->toArray();
        }

        $companyLoc = $deliveryOrder->location_id ? \App\Models\Master\CompanyLocation::find($deliveryOrder->location_id)?->name : null;
        $term = $deliveryOrder->payment_term_id ? \App\Models\PaymentTerm::find($deliveryOrder->payment_term_id)?->name : null;
        $withholdRv = $deliveryOrder->withhold_for_rv_id ? \App\Models\ReceiptVoucher::find($deliveryOrder->withhold_for_rv_id)?->unique_no : null;

        return [
            'reference_no'           => $deliveryOrder->reference_no,
            'ref_no'                 => $deliveryOrder->ref_no,
            'dispatch_date'          => $deliveryOrder->dispatch_date ? Carbon::parse($deliveryOrder->dispatch_date)->format('Y-m-d') : null,
            'customer'               => $deliveryOrder->customer?->name,
            'sales_order'            => $deliveryOrder->salesOrder?->reference_no,
            'location'               => $companyLoc,
            'factory'                => !empty($factoryNames) ? implode(', ', $factoryNames) : null,
            'section'                => !empty($sectionNames) ? implode(', ', $sectionNames) : null,
            'advance_amount'         => (float) ($deliveryOrder->advance_amount ?? 0),
            'jv_amount'              => (float) ($deliveryOrder->jv_amount ?? 0),
            'withhold_amount'        => (float) ($deliveryOrder->withhold_amount ?? 0),
            'withhold_for_rv'        => $withholdRv,
            'so_withhold_percentage' => (float) ($deliveryOrder->so_withhold_percentage ?? 0),
            'so_held_amount'         => (float) ($deliveryOrder->so_held_amount ?? 0),
            'sauda_type'             => $deliveryOrder->sauda_type,
            'payment_term'           => $term,
            'am_approval_status'     => $deliveryOrder->am_approval_status,
            'do_status'              => $deliveryOrder->do_status,
            'remarks'                => $deliveryOrder->remarks,
            'receipt_vouchers'       => !empty($rvList) ? implode(', ', $rvList) : null,
            'items_product'          => !empty($itemProducts) ? implode(', ', $itemProducts) : null,
            'items_qty'              => !empty($itemQtys) ? implode(', ', $itemQtys) : null,
            'items_rate'             => !empty($itemRates) ? implode(', ', $itemRates) : null,
            'items_bags'             => !empty($itemBags) ? implode(', ', $itemBags) : null,
            'items_bag_type'         => !empty($itemBagTypes) ? implode(', ', $itemBagTypes) : null,
            'items_bag_size'         => !empty($itemBagSizes) ? implode(', ', $itemBagSizes) : null,
            'items_brand'            => !empty($itemBrands) ? implode(', ', $itemBrands) : null,
            'total_qty'              => (float) $totalQty,
            'total_amount'           => (float) $totalAmount,
            'items_count'            => $deliveryOrder->delivery_order_data->count(),
        ];
    }

    /**
     * Get audit logs for a delivery order.
     */
    public function getAuditLogs(Request $request, $id)
    {
        $deliveryOrder = DeliveryOrder::with(['customer', 'salesOrder'])->findOrFail($id);

        $logs = AuditLog::with('user')
            ->where(function ($q) use ($deliveryOrder) {
                $q->where(function ($sq) use ($deliveryOrder) {
                    $sq->whereIn('model_type', [DeliveryOrder::class, 'DeliveryOrder', 'App\Models\Sales\DeliveryOrder'])
                        ->where('model_id', $deliveryOrder->id);
                })
                ->orWhere('old_values', 'like', '%"delivery_order_id":' . $deliveryOrder->id . '%')
                ->orWhere('old_values', 'like', '%"delivery_order_id":"' . $deliveryOrder->id . '"%')
                ->orWhere('new_values', 'like', '%"delivery_order_id":' . $deliveryOrder->id . '%')
                ->orWhere('new_values', 'like', '%"delivery_order_id":"' . $deliveryOrder->id . '"%');
            })
            ->orderBy('created_at', 'desc')
            ->get();

        $hasCreateLog = $logs->contains(function ($l) {
            return str_contains($l->action, 'create') || str_contains($l->action, 'store');
        });

        if (!$hasCreateLog) {
            $virtualValues = $this->buildDeliveryOrderAuditValues($deliveryOrder);
            $virtualCreateLog = new AuditLog([
                'action' => 'delivery_order_created',
                'description' => "Delivery Order #{$deliveryOrder->reference_no} created.",
                'user_id' => $deliveryOrder->created_by,
                'created_at' => $deliveryOrder->created_at,
                'old_values' => null,
                'new_values' => $virtualValues,
            ]);
            $creator = User::find($deliveryOrder->created_by);
            $virtualCreateLog->setRelation('user', $creator);
            $logs->push($virtualCreateLog);
        }

        $fieldLabels = [
            'reference_no'           => 'DO Reference No',
            'ref_no'                 => 'Ref / Manual No',
            'dispatch_date'          => 'DO Date',
            'customer'               => 'Customer',
            'customer_id'            => 'Customer',
            'sales_order'            => 'Sale Order',
            'so_id'                  => 'Sale Order',
            'location'               => 'Company Location',
            'location_id'            => 'Company Location',
            'factory'                => 'Factory / Arrival Location',
            'arrival_location_id'    => 'Factory / Arrival Location',
            'section'                => 'Section / Storage Location',
            'sub_arrival_location_id'=> 'Section / Storage Location',
            'advance_amount'         => 'Advance Consumed',
            'jv_amount'              => 'JV Amount',
            'withhold_amount'        => 'Withhold Amount',
            'withhold_for_rv'        => 'Withhold For RV',
            'withhold_for_rv_id'     => 'Withhold For RV',
            'so_withhold_percentage' => 'SO Withhold %',
            'so_held_amount'         => 'SO Held Amount',
            'sauda_type'             => 'Sauda Type',
            'payment_term'           => 'Payment Term',
            'payment_term_id'        => 'Payment Term',
            'am_approval_status'     => 'Approval Status',
            'do_status'              => 'DO Status',
            'remarks'                => 'Remarks',
            'receipt_vouchers'       => 'Linked Receipt Vouchers',
            'items_product'          => 'Products',
            'items_qty'              => 'Quantities (Kgs)',
            'items_rate'             => 'Rates',
            'items_bags'             => 'No of Bags',
            'items_bag_type'         => 'Bag Types',
            'items_bag_size'         => 'Bag Sizes',
            'items_brand'            => 'Brands',
            'total_qty'              => 'Total Qty (Kgs)',
            'total_amount'           => 'Total Amount',
            'items_count'            => 'Items Count',
        ];

        $relationCache = [];
        $resolveRelationValue = function ($key, $val) use (&$relationCache) {
            if ($val === null || $val === '') {
                return null;
            }
            if (is_array($val)) {
                return json_encode($val);
            }

            if (in_array($key, ['customer_id', 'customer']) && (is_numeric($val) || (is_string($val) && ctype_digit((string) $val)))) {
                $cacheKey = "customer_{$val}";
                if (isset($relationCache[$cacheKey])) return $relationCache[$cacheKey];
                $c = Customer::find($val);
                $res = $c ? $c->name : $val;
                $relationCache[$cacheKey] = $res;
                return $res;
            }

            if (in_array($key, ['so_id', 'sales_order']) && (is_numeric($val) || (is_string($val) && ctype_digit((string) $val)))) {
                $cacheKey = "so_{$val}";
                if (isset($relationCache[$cacheKey])) return $relationCache[$cacheKey];
                $so = SalesOrder::find($val);
                $res = $so ? $so->reference_no : $val;
                $relationCache[$cacheKey] = $res;
                return $res;
            }

            if (in_array($key, ['payment_term_id', 'payment_term']) && (is_numeric($val) || (is_string($val) && ctype_digit((string) $val)))) {
                $cacheKey = "payment_term_{$val}";
                if (isset($relationCache[$cacheKey])) return $relationCache[$cacheKey];
                $term = PaymentTerm::find($val);
                $res = $term ? $term->name : $val;
                $relationCache[$cacheKey] = $res;
                return $res;
            }

            if ($key === 'do_status') {
                return ucfirst($val);
            }

            if ($key === 'am_approval_status') {
                return ucfirst($val);
            }

            if (in_array($key, ['advance_amount', 'jv_amount', 'withhold_amount', 'so_held_amount', 'total_amount']) && is_numeric($val)) {
                return 'Rs. ' . number_format((float) $val, 2);
            }

            if ($key === 'total_qty' && is_numeric($val)) {
                return number_format((float) $val, 2) . ' Kgs';
            }

            return $val;
        };

        $flattenArray = function ($arr, $prefix = '') use (&$flattenArray) {
            $result = [];
            if (!is_array($arr)) return $result;
            foreach ($arr as $key => $value) {
                $newKey = $prefix === '' ? $key : "{$prefix}.{$key}";
                if (is_array($value) && !empty($value) && !isset($value[0])) {
                    $result = array_merge($result, $flattenArray($value, $newKey));
                } else {
                    $result[$newKey] = $value;
                }
            }
            return $result;
        };

        $filteredLogs = [];
        $skipKeys = ['id', 'deleted_at', 'created_at', 'updated_at', 'company_id', 'am_change_made'];

        foreach ($logs as $log) {
            $rawOld = is_array($log->old_values) ? $log->old_values : (!empty($log->old_values) ? json_decode($log->old_values, true) : []);
            if (is_string($rawOld)) $rawOld = json_decode($rawOld, true);

            $rawNew = is_array($log->new_values) ? $log->new_values : (!empty($log->new_values) ? json_decode($log->new_values, true) : []);
            if (is_string($rawNew)) $rawNew = json_decode($rawNew, true);

            $old = $flattenArray($rawOld);
            $new = $flattenArray($rawNew);

            $parsedChanges = [];
            $allKeys = array_unique(array_merge(array_keys($old ?? []), array_keys($new ?? [])));

            foreach ($allKeys as $key) {
                if (in_array($key, $skipKeys)) continue;

                $oldVal = $old[$key] ?? null;
                $newVal = $new[$key] ?? null;

                if ($oldVal != $newVal) {
                    $oldResolved = $resolveRelationValue($key, $oldVal);
                    $newResolved = $resolveRelationValue($key, $newVal);

                    $parsedChanges[] = [
                        'key'     => $key,
                        'label'   => $fieldLabels[$key] ?? ucwords(str_replace('_', ' ', $key)),
                        'old'     => is_array($oldResolved) ? json_encode($oldResolved) : $oldResolved,
                        'new'     => is_array($newResolved) ? json_encode($newResolved) : $newResolved,
                        'raw_old' => $oldVal,
                        'raw_new' => $newVal,
                    ];
                }
            }

            if (count($parsedChanges) > 1) {
                $parsedChanges = array_values(array_filter($parsedChanges, fn($c) => !in_array($c['key'], ['updated_at', 'created_at'])));
            }

            $log->parsed_changes = $parsedChanges;
            $filteredLogs[] = $log;
        }

        $logs = $filteredLogs;
        $title = "Audit Logs: Delivery Order #{$deliveryOrder->reference_no}";

        return view('management.sales.delivery-order.partials.audit_logs_content', compact('deliveryOrder', 'logs', 'title'));
    }
}


