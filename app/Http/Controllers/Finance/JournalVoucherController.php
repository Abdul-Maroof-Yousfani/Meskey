<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\JournalVoucher;
use App\Models\JournalVoucherDetail;
use App\Models\Master\Account\Account;
use App\Models\Master\Account\Transaction;
use App\Models\Master\Account\TransactionVoucherType;
use App\Models\Master\GrnNumber;
use App\Models\Master\Supplier;
use App\Models\Master\Vendor;
use App\Models\Procurement\PaymentRequest;
use App\Models\ReceiptVoucher;
use App\Models\ReceiptVoucherItem;
use App\Models\Sales\SalesOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class JournalVoucherController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $customers = \App\Models\Master\Customer::orderBy('name')->get(['id', 'name']);
        return view('management.finance.journal_voucher.index', compact('customers'));
    }

    /**
     * Get list of journal vouchers.
     */
    public function getList(Request $request)
    {
        $journalVouchers = JournalVoucher::with([
                'journalVoucherDetails.account',
                'journalVoucherDetails.receiptVoucher.customer',
                'journalVoucherDetails.customerAdvance.customer',
                'journalVoucherDetails.salesOrder.customer',
                'journalVoucherDetails.deliveryOrder.customer',
                'journalVoucherDetails.referenceJournalVoucher',
                'createdBy'
            ])
            ->when($request->filled('jv_no'), function ($q) use ($request) {
                $q->where('jv_no', 'like', '%' . trim($request->jv_no) . '%');
            })
            ->when($request->filled('daterange') || $request->filled('date'), function ($q) use ($request) {
                $rawDate = trim($request->daterange ?: $request->date);
                if (str_contains($rawDate, ' - ')) {
                    $parts = explode(' - ', $rawDate);
                    if (count($parts) == 2) {
                        try {
                            $start = \Carbon\Carbon::parse(trim($parts[0]))->format('Y-m-d');
                            $end = \Carbon\Carbon::parse(trim($parts[1]))->format('Y-m-d');
                            $q->whereDate('jv_date', '>=', $start)
                              ->whereDate('jv_date', '<=', $end);
                        } catch (\Exception $e) {}
                    }
                } else {
                    try {
                        $single = \Carbon\Carbon::parse($rawDate)->format('Y-m-d');
                        $q->whereDate('jv_date', $single);
                    } catch (\Exception $e) {}
                }
            })
            ->when($request->filled('customer_id') && $request->customer_id !== 'all', function ($q) use ($request) {
                $customerId = $request->customer_id;
                $customer = \App\Models\Master\Customer::find($customerId);
                $custAccId = $customer?->account_id;

                $q->whereHas('journalVoucherDetails', function ($dq) use ($customerId, $custAccId) {
                    $dq->where(function ($sq) use ($customerId, $custAccId) {
                        $sq->whereHas('receiptVoucher', function ($rq) use ($customerId) {
                            $rq->where('customer_id', $customerId);
                        })
                        ->orWhereHas('customerAdvance', function ($caq) use ($customerId) {
                            $caq->where('customer_id', $customerId);
                        })
                        ->orWhereHas('salesOrder', function ($soq) use ($customerId) {
                            $soq->where('customer_id', $customerId);
                        })
                        ->orWhereHas('deliveryOrder', function ($doq) use ($customerId) {
                            $doq->where('customer_id', $customerId);
                        });
                        if ($custAccId) {
                            $sq->orWhere('acc_id', $custAccId);
                        }
                    });
                });
            })
            ->when($request->filled('status') && $request->status !== 'all', function ($q) use ($request) {
                $q->where('am_approval_status', $request->status);
            })
            ->when($request->filled('search'), function ($q) use ($request) {
                $searchTerm = '%' . trim($request->search) . '%';
                return $q->where(function ($sq) use ($searchTerm) {
                    $sq->where('jv_no', 'like', $searchTerm)
                        ->orWhere('description', 'like', $searchTerm)
                        ->orWhereHas('journalVoucherDetails.account', function ($aq) use ($searchTerm) {
                            $aq->where('table_name', 'customers')->where('name', 'like', $searchTerm);
                        })
                        ->orWhereHas('journalVoucherDetails.receiptVoucher.customer', function ($cq) use ($searchTerm) {
                            $cq->where('name', 'like', $searchTerm);
                        })
                        ->orWhereHas('journalVoucherDetails.customerAdvance.customer', function ($cq) use ($searchTerm) {
                            $cq->where('name', 'like', $searchTerm);
                        })
                        ->orWhereHas('journalVoucherDetails.salesOrder.customer', function ($cq) use ($searchTerm) {
                            $cq->where('name', 'like', $searchTerm);
                        })
                        ->orWhereHas('journalVoucherDetails.deliveryOrder.customer', function ($cq) use ($searchTerm) {
                            $cq->where('name', 'like', $searchTerm);
                        })
                        ->orWhereHas('journalVoucherDetails.deliveryOrder', function ($doq) use ($searchTerm) {
                            $doq->where('reference_no', 'like', $searchTerm);
                        });
                });
            })
            ->latest()
            ->paginate(request('per_page', 25));

        return view('management.finance.journal_voucher.getList', compact('journalVouchers'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $data["accounts"] = Account::where("is_operational", "yes")->get();
        $data["receiptVouchers"] = collect([]);
        $data["salesOrders"] = collect([]);

        return view('management.finance.journal_voucher.create', $data);
    }

    /**
     * Fetch account related data (RVs, SOs, GRNs) for a given account.
     */
    public function fetchAccountRelatedData($accountId, $excludeJvId = null)
    {
        // dd($accountId);
        if (!$accountId) {
            return [
                'table_name' => null,
                'customer_id' => null,
                'customer_name' => null,
                'receipt_vouchers' => collect([]),
                'customer_advances' => collect([]),
                'sales_orders' => collect([]),
                'grns' => collect([])
            ];
        }

        $account = Account::find($accountId);
        $tableName = strtolower($account->table_name ?? '');

        if ($tableName === 'customers') {
            $customer = \App\Models\Master\Customer::where('account_id', $accountId)->first();
            if (!$customer && $account) {
                if ($account->model_id) {
                    $customer = \App\Models\Master\Customer::find($account->model_id);
                }
                if (!$customer) {
                    $customer = \App\Models\Master\Customer::where('name', $account->name)->first();
                }
            }

            if (!$customer) {
                return [
                    'table_name' => 'customers',
                    'customer_id' => null,
                    'customer_name' => null,
                    'receipt_vouchers' => collect([]),
                    'customer_advances' => collect([]),
                    'sales_orders' => collect([]),
                    'grns' => collect([])
                ];
            }

            $saleBalances = $this->saleBalances();
            $receiptVouchers = ReceiptVoucher::where('receipt_vouchers.customer_id', $customer->id)
                ->where('receipt_vouchers.am_approval_status', 'approved')
                ->whereNull('receipt_vouchers.deleted_at')
                ->get()
                ->map(function($rv) use ($saleBalances, $excludeJvId) {
                    $remaining = $saleBalances->rvRemaining($rv->id, ['jv' => $excludeJvId]);
                    return [
                        'id' => $rv->id,
                        'unique_no' => $rv->unique_no,
                        'total_amount' => (float) $rv->total_amount,
                        'remaining_amount' => $remaining,
                        'formatted_remaining' => number_format($remaining, 2),
                        'text' => $rv->unique_no . ' (Rem: ' . number_format($remaining, 2) . ')'
                    ];
                })
                ->filter(function($rv) {
                    return $rv['remaining_amount'] > 0.01;
                })
                ->values();

            $excludeJvNo = null;
            if ($excludeJvId) {
                $excludeJv = JournalVoucher::find($excludeJvId);
                $excludeJvNo = $excludeJv ? $excludeJv->jv_no : null;
            }

            $customerAdvances = \App\Models\CustomerAdvance::where('customer_id', $customer->id)
                ->where(function ($q) {
                    $q->where('source_type', '!=', 'excess_payment')
                      ->orWhereExists(function ($sub) {
                          $sub->select(DB::raw(1))
                              ->from('receipt_vouchers')
                              ->whereColumn('receipt_vouchers.unique_no', 'customer_advances.voucher_no')
                              ->whereNull('receipt_vouchers.deleted_at')
                              ->where('receipt_vouchers.am_approval_status', 'approved');
                      });
                })
                ->where(function ($q) use ($excludeJvNo) {
                    $q->whereIn('status', ['pending', 'partial_payment']);
                    if ($excludeJvNo) {
                        $q->orWhereHas('adjustments', function ($adjQuery) use ($excludeJvNo) {
                            $adjQuery->where('voucher_no', $excludeJvNo);
                        });
                    }
                })
                ->get()
                ->map(function ($adv) use ($excludeJvNo) {
                    $remaining = (float) $adv->remaining_amount;
                    if ($excludeJvNo) {
                        $adjustment = \App\Models\CustomerAdvanceAdjustment::where('customer_advance_id', $adv->id)
                            ->where('voucher_no', $excludeJvNo)
                            ->sum('amount');
                        $remaining += (float) $adjustment;
                    }

                    return [
                        'id' => $adv->id,
                        'voucher_no' => $adv->voucher_no,
                        'source_type' => $adv->source_type,
                        'remaining_amount' => round($remaining, 2),
                        'formatted_remaining' => number_format($remaining, 2),
                        'text' => $adv->voucher_no . ' (Rem: ' . number_format($remaining, 2) . ')',
                        'type' => 'customer_advance'
                    ];
                })
                ->filter(function ($item) {
                    return $item['remaining_amount'] > 0.01;
                })
                ->values();

            // SOs / DOs already on the JV being edited stay listed so its rows keep their selection
            $usedInThisJv = $excludeJvId
                ? JournalVoucherDetail::where('journal_voucher_id', $excludeJvId)->whereNull('deleted_at')->get(['sales_order_id', 'delivery_order_id'])
                : collect();
            $salesOrders = SalesOrder::where('customer_id', $customer->id)
                ->where('am_approval_status', 'approved')
                ->select('id', 'reference_no', 'status')
                ->latest('id')
                ->get()
                ->map(function($so) use ($excludeJvId, $saleBalances, $usedInThisJv) {
                    $summary = $saleBalances->summary((int) $so->id, ['jv' => $excludeJvId]);
                    // A closed SO with nothing left can't give or take anything: don't offer it
                    if ($summary['so_closed'] && $summary['net'] <= 0.01 && !$usedInThisJv->contains('sales_order_id', $so->id)) {
                        return null;
                    }
                    $remAdvance = $summary['free'];
                    $statusLabel = $so->status ? ' (' . ucfirst($so->status) . ')' : '';
                    return [
                        'id' => $so->id,
                        'reference_no' => $so->reference_no,
                        'unique_no' => $so->reference_no,
                        'remaining_amount' => $remAdvance,
                        'formatted_remaining' => number_format($remAdvance, 2),
                        'text' => $so->reference_no . $statusLabel . ($remAdvance > 0 ? ' (Rem: Rs. ' . number_format($remAdvance, 2) . ')' : ''),
                        'type' => 'sales_order'
                    ];
                })
                ->filter()
                ->values();

            // Customer's Approved Credit JVs
            $custAccIds = [$account->id];
            if ($customer && $customer->account_id && !in_array($customer->account_id, $custAccIds)) {
                $custAccIds[] = $customer->account_id;
            }

            $jvDetails = JournalVoucherDetail::with(['journalVoucher'])
                ->whereIn('acc_id', $custAccIds)
                ->where('credit_amount', '>', 0)
                ->whereHas('journalVoucher', function ($q) {
                    $q->where('am_approval_status', 'approved');
                })
                ->when($excludeJvId, function ($q) use ($excludeJvId) {
                    $q->where('journal_voucher_id', '!=', $excludeJvId);
                })
                ->latest('id')
                ->get();

            $availableJvs = [];
            foreach ($jvDetails as $detail) {
                $jv = $detail->journalVoucher;
                if (!$jv) continue;

                $creditAmount = (float) $detail->credit_amount;

                $spentQuery = DB::table('settlement_adjustments')
                    ->where('reference_type', 'journal_voucher')
                    ->where('reference_id', $jv->id);

                if ($excludeJvNo) {
                    $spentQuery->where('voucher_no', '!=', $excludeJvNo);
                }
                $settlementSpent = (float) $spentQuery->sum('amount');

                $refundQuery = DB::table('payment_voucher_data')
                    ->join('payment_vouchers', 'payment_vouchers.id', '=', 'payment_voucher_data.payment_voucher_id')
                    ->whereNull('payment_vouchers.deleted_at')
                    ->whereNull('payment_voucher_data.deleted_at')
                    ->where('payment_vouchers.am_approval_status', '!=', 'rejected')
                    ->where(function ($q) use ($detail, $jv) {
                        $q->where('payment_voucher_data.journal_voucher_detail_id', $detail->id)
                          ->orWhere('payment_voucher_data.journal_voucher_id', $jv->id);
                    });
                $pvDataSpent = (float) $refundQuery->sum('net_amount');

                $otherJvSpentQuery = JournalVoucherDetail::join('journal_vouchers', 'journal_vouchers.id', '=', 'journal_voucher_details.journal_voucher_id')
                    ->whereNull('journal_vouchers.deleted_at')
                    ->whereNull('journal_voucher_details.deleted_at')
                    ->where('journal_vouchers.am_approval_status', '!=', 'rejected')
                    ->where(function ($q) use ($jv, $detail) {
                        $q->where('journal_voucher_details.reference_jv_id', $jv->id)
                          ->orWhere('journal_voucher_details.reference_jv_detail_id', $detail->id);
                    });
                if ($excludeJvId) {
                    $otherJvSpentQuery->where('journal_vouchers.id', '!=', $excludeJvId);
                }
                $jvSpent = (float) $otherJvSpentQuery->sum('journal_voucher_details.debit_amount');

                $alreadySpent = max($settlementSpent, $pvDataSpent, $jvSpent);
                $remainingAmount = max(0, $creditAmount - $alreadySpent);

                if ($remainingAmount > 0.01) {
                    $jvDateFormatted = $jv->jv_date ? $jv->jv_date->format('d-M-Y') : 'N/A';
                    $availableJvs[] = [
                        'id' => $jv->id,
                        'detail_id' => $detail->id,
                        'jv_no' => $jv->jv_no,
                        'credit_amount' => $creditAmount,
                        'remaining_amount' => round($remainingAmount, 2),
                        'formatted_remaining' => number_format($remainingAmount, 2),
                        'description' => $detail->description ?: ($jv->description ?: ''),
                        'text' => "{$jv->jv_no} ({$jvDateFormatted}) - Rem: Rs. " . number_format($remainingAmount, 2),
                        'type' => 'journal_voucher'
                    ];
                }
            }

            return [
                'table_name' => 'customers',
                'customer_id' => $customer->id,
                'customer_name' => $customer->name,
                'receipt_vouchers' => $receiptVouchers,
                'customer_advances' => $customerAdvances,
                'sales_orders' => $salesOrders,
                'journal_vouchers' => $availableJvs,
                'grns' => collect([])
            ];
        }

        if ($tableName === 'suppliers') {
            $supplier = Supplier::where('account_id', $accountId)->first();
            if (!$supplier && $account) {
                if ($account->model_id) {
                    $supplier = Supplier::find($account->model_id);
                }
                if (!$supplier) {
                    $supplier = Supplier::where('name', $account->name)->first();
                }
            }

            if (!$supplier) {
                return [
                    'table_name' => 'suppliers',
                    'supplier_id' => null,
                    'supplier_name' => null,
                    'receipt_vouchers' => collect([]),
                    'customer_advances' => collect([]),
                    'sales_orders' => collect([]),
                    'journal_vouchers' => collect([]),
                    'grns' => collect([])
                ];
            }

            // Direct query using supplier_id from grn_numbers table
            $data = GrnNumber::where('supplier_id', $supplier->id)
                ->whereNotNull('unique_no')
                ->whereHas('paymentRequestData.paymentRequests', function ($pr) {
                    $pr->where('status', 'approved')
                    ->where(function ($q) {
                        $q->whereDoesntHave('paymentVoucherData')
                            ->orWhereDoesntHave('paymentVoucherData.paymentVoucher');
                    });
                })
                ->latest('id')
                ->get()
                ->map(function ($grn) use ($excludeJvId, $accountId) {
                    $limitInfo = $this->getGrnAvailableLimit($grn->id, $excludeJvId, $accountId);
                    $remaining = $limitInfo['available_amount'] ?? 0;
                    return [
                        'id' => $grn->id,
                        'unique_no' => $grn->unique_no,
                        'text' => $grn->unique_no . ($remaining > 0 ? ' (Rem: ' . number_format($remaining, 2) . ')' : ''),
                        'type' => 'grn',
                        'remaining_amount' => $remaining
                    ];
                })
                ->filter(function ($grn) {
                    return ($grn['remaining_amount'] ?? 0) > 0.01;
                })
                ->values();

            return [
                'table_name' => 'suppliers',
                'supplier_id' => $supplier->id,
                'supplier_name' => $supplier->name,
                'customer_id' => null,
                'customer_name' => null,
                'receipt_vouchers' => collect([]),
                'customer_advances' => collect([]),
                'sales_orders' => collect([]),
                'journal_vouchers' => collect([]),
                'grns' => $data
            ];
        }

        if ($tableName === 'vendors' || $tableName === 'vendor') {
            $vendor = Vendor::where('account_id', $accountId)->first();
            if (!$vendor && $account) {
                if ($account->model_id) {
                    $vendor = Vendor::find($account->model_id);
                }
                if (!$vendor) {
                    $vendor = Vendor::where('name', $account->name)->first();
                }
            }

            if (!$vendor) {
                return [
                    'table_name' => 'vendors',
                    'vendor_id' => null,
                    'vendor_name' => null,
                    'supplier_id' => null,
                    'supplier_name' => null,
                    'customer_id' => null,
                    'customer_name' => null,
                    'receipt_vouchers' => collect([]),
                    'customer_advances' => collect([]),
                    'sales_orders' => collect([]),
                    'journal_vouchers' => collect([]),
                    'grns' => collect([])
                ];
            }

            $vendorId = $vendor->id;
            $vendorAccIds = array_values(array_unique(array_filter([$accountId, $vendor->account_id])));

            $prs = PaymentRequest::where(function($q) use ($vendorAccIds, $vendorId) {
                    $q->whereIn('account_id', $vendorAccIds)
                      ->orWhereHas('paymentRequestData', function($prdQ) use ($vendorId, $vendorAccIds) {
                          $prdQ->where('labour_vendor_id', $vendorId)
                               ->orWhere(function($sub) use ($vendorId) {
                                   $sub->where('payment_to_type', 'vendors')->where('payment_to', $vendorId);
                               })
                               ->orWhereIn('account_id', $vendorAccIds);
                      });
                })
                ->where('status', 'approved')
                ->where(function ($q) {
                    $q->whereDoesntHave('paymentVoucherData')
                      ->orWhereDoesntHave('paymentVoucherData.paymentVoucher');
                })
                ->get();

            $prdIds = $prs->pluck('payment_request_data_id')->filter()->unique();
            $prds = DB::table('payment_request_datas')->whereIn('id', $prdIds)->get();

            $directGrnNos = $prds->pluck('grn_no')->filter()->unique();
            $directGrnIds = $prds->pluck('grn_id')->filter()->unique();
            $ticketIds = $prds->pluck('ticket_id')->merge($prds->pluck('arrival_ticket_id'))->filter()->unique();

            $slips = DB::table('arrival_slips')->whereIn('arrival_ticket_id', $ticketIds)->get();
            $slipGrnNos = $slips->pluck('unique_no')->filter()->unique();
            $slipIds = $slips->pluck('id')->filter()->unique();

            $fpTicketIds = DB::table('freight_payments')->where('vendor_id', $vendorId)->pluck('arrival_ticket_id')->filter()->unique();
            if ($fpTicketIds->isNotEmpty()) {
                $fpSlips = DB::table('arrival_slips')->whereIn('arrival_ticket_id', $fpTicketIds)->get();
                $slipGrnNos = $slipGrnNos->merge($fpSlips->pluck('unique_no'))->unique();
                $slipIds = $slipIds->merge($fpSlips->pluck('id'))->unique();
            }

            $grns = GrnNumber::whereNotNull('unique_no')
                ->where(function($q) use ($slipIds, $slipGrnNos, $directGrnNos, $directGrnIds) {
                    $matched = false;
                    if ($slipIds->isNotEmpty()) {
                        $q->orWhere(function ($sub) use ($slipIds) {
                            $sub->whereIn('model_id', $slipIds)->where('model_type', 'arrival-slip');
                        });
                        $matched = true;
                    }
                    if ($slipGrnNos->isNotEmpty()) {
                        $q->orWhereIn('unique_no', $slipGrnNos);
                        $matched = true;
                    }
                    if ($directGrnNos->isNotEmpty()) {
                        $q->orWhereIn('unique_no', $directGrnNos);
                        $matched = true;
                    }
                    if ($directGrnIds->isNotEmpty()) {
                        $q->orWhereIn('id', $directGrnIds);
                        $matched = true;
                    }
                    if (!$matched) {
                        $q->whereRaw('0 = 1');
                    }
                })
                ->latest('id')
                ->get()
                ->map(function ($grn) use ($excludeJvId, $accountId) {
                    $limitInfo = $this->getGrnAvailableLimit($grn->id, $excludeJvId, $accountId);
                    $remaining = $limitInfo['available_amount'] ?? 0;
                    return [
                        'id' => $grn->id,
                        'unique_no' => $grn->unique_no,
                        'text' => $grn->unique_no . ($remaining > 0 ? ' (Rem: ' . number_format($remaining, 2) . ')' : ''),
                        'type' => 'grn',
                        'remaining_amount' => $remaining
                    ];
                })
                ->filter(function ($grn) {
                    return ($grn['remaining_amount'] ?? 0) > 0.01;
                })
                ->values();

            // Vendor's Approved Credit JVs
            $excludeJvNo = null;
            if ($excludeJvId) {
                $excludeJv = JournalVoucher::find($excludeJvId);
                $excludeJvNo = $excludeJv ? $excludeJv->jv_no : null;
            }

            $jvDetails = JournalVoucherDetail::with(['journalVoucher'])
                ->whereIn('acc_id', $vendorAccIds)
                ->where('credit_amount', '>', 0)
                ->whereHas('journalVoucher', function ($q) {
                    $q->where('am_approval_status', 'approved');
                })
                ->when($excludeJvId, function ($q) use ($excludeJvId) {
                    $q->where('journal_voucher_id', '!=', $excludeJvId);
                })
                ->latest('id')
                ->get();

            $availableJvs = [];
            foreach ($jvDetails as $detail) {
                $jv = $detail->journalVoucher;
                if (!$jv) continue;

                $creditAmount = (float) $detail->credit_amount;

                $spentQuery = DB::table('settlement_adjustments')
                    ->where('reference_type', 'journal_voucher')
                    ->where('reference_id', $jv->id);

                if ($excludeJvNo) {
                    $spentQuery->where('voucher_no', '!=', $excludeJvNo);
                }
                $settlementSpent = (float) $spentQuery->sum('amount');

                $refundQuery = DB::table('payment_voucher_data')
                    ->join('payment_vouchers', 'payment_vouchers.id', '=', 'payment_voucher_data.payment_voucher_id')
                    ->whereNull('payment_vouchers.deleted_at')
                    ->whereNull('payment_voucher_data.deleted_at')
                    ->where('payment_vouchers.am_approval_status', '!=', 'rejected')
                    ->where(function ($q) use ($detail, $jv) {
                        $q->where('payment_voucher_data.journal_voucher_detail_id', $detail->id)
                          ->orWhere('payment_voucher_data.journal_voucher_id', $jv->id);
                    });
                $pvDataSpent = (float) $refundQuery->sum('net_amount');

                $otherJvSpentQuery = JournalVoucherDetail::join('journal_vouchers', 'journal_vouchers.id', '=', 'journal_voucher_details.journal_voucher_id')
                    ->whereNull('journal_vouchers.deleted_at')
                    ->whereNull('journal_voucher_details.deleted_at')
                    ->where('journal_vouchers.am_approval_status', '!=', 'rejected')
                    ->where(function ($q) use ($jv, $detail) {
                        $q->where('journal_voucher_details.reference_jv_id', $jv->id)
                          ->orWhere('journal_voucher_details.reference_jv_detail_id', $detail->id);
                    });
                if ($excludeJvId) {
                    $otherJvSpentQuery->where('journal_vouchers.id', '!=', $excludeJvId);
                }
                $jvSpent = (float) $otherJvSpentQuery->sum('journal_voucher_details.debit_amount');

                $alreadySpent = max($settlementSpent, $pvDataSpent, $jvSpent);
                $remainingAmount = max(0, $creditAmount - $alreadySpent);

                if ($remainingAmount > 0.01) {
                    $jvDateFormatted = $jv->jv_date ? $jv->jv_date->format('d-M-Y') : 'N/A';
                    $availableJvs[] = [
                        'id' => $jv->id,
                        'detail_id' => $detail->id,
                        'jv_no' => $jv->jv_no,
                        'credit_amount' => $creditAmount,
                        'remaining_amount' => round($remainingAmount, 2),
                        'formatted_remaining' => number_format($remainingAmount, 2),
                        'description' => $detail->description ?: ($jv->description ?: ''),
                        'text' => "{$jv->jv_no} ({$jvDateFormatted}) - Rem: Rs. " . number_format($remainingAmount, 2),
                        'type' => 'journal_voucher'
                    ];
                }
            }

            return [
                'table_name' => 'vendors',
                'vendor_id' => $vendor->id,
                'vendor_name' => $vendor->name,
                'supplier_id' => null,
                'supplier_name' => null,
                'customer_id' => null,
                'customer_name' => null,
                'receipt_vouchers' => collect([]),
                'customer_advances' => collect([]),
                'sales_orders' => collect([]),
                'journal_vouchers' => $availableJvs,
                'grns' => $grns
            ];
        }

        return [
            'table_name' => $tableName,
            'customer_id' => null,
            'customer_name' => null,
            'receipt_vouchers' => collect([]),
            'customer_advances' => collect([]),
            'sales_orders' => collect([]),
            'journal_vouchers' => collect([]),
            'grns' => collect([])
        ];
    }

    /**
     * AJAX endpoint to get account related RVs and SOs.
     */
    public function getAccountRelatedData(Request $request)
    {
        $data = $this->fetchAccountRelatedData($request->acc_id, $request->jv_id);
        return response()->json(array_merge(['success' => true], $data));
    }

    /**
     * AJAX endpoint to get Delivery Orders and direct returnable advance for a Sales Order.
     */
    public function getSoDeliveryOrders(Request $request, $soId)
    {
        $salesOrder = SalesOrder::with(['sales_order_data.item'])->findOrFail($soId);
        $excludeJvId = $request->query('exclude_jv_id');

        $deliveryOrders = \App\Models\Sales\DeliveryOrder::with(['delivery_order_data.item'])
            ->where('so_id', $soId)
            ->where('am_approval_status', '!=', 'rejected')
            ->where(function ($q) {
                $q->whereNull('is_auto_created_from_so')
                    ->orWhere('is_auto_created_from_so', '!=', 1);
            })
            ->get();

        $usedDoIds = $excludeJvId
            ? JournalVoucherDetail::where('journal_voucher_id', $excludeJvId)->whereNull('deleted_at')->whereNotNull('delivery_order_id')->pluck('delivery_order_id')->all()
            : [];

        $dos = [];
        foreach ($deliveryOrders as $do) {
            $totalRemainingAmount = $this->getDeliveryOrderRemainingAdvance($do, $excludeJvId);
            // A closed DO (or a DO of a closed SO) with nothing left can't give or take anything: don't offer it
            if ($totalRemainingAmount <= 0.01 && $this->saleBalances()->isDoReleased($do->id) && !in_array($do->id, $usedDoIds)) {
                continue;
            }
            $firstRate = (float) ($do->delivery_order_data->first()->rate ?? 1);
            $totalRemainingQty = $firstRate > 0 ? round($totalRemainingAmount / $firstRate, 2) : 0;

            $doTotalValue = 0;
            $doTotalQty = 0;
            foreach ($do->delivery_order_data as $dod) {
                $doTotalQty += (float) $dod->qty;
                $doTotalValue += (float) $dod->qty * (float) $dod->rate;
            }
            $unadvancedCapacity = max(0, $doTotalValue - $totalRemainingAmount);

            $hasLp = (!$this->saleBalances()->isDoReleased($do->id) && delivery_order_has_loading_program($do->id));
            $statusText = $do->do_status === 'closed' ? ' [Closed]' : ($do->am_approval_status === 'pending' ? ' [Pending Approval]' : '');
            if ($hasLp) {
                $statusText .= ' [Active LP]';
            }

            if ($totalRemainingAmount > 0.01) {
                $displayText = ($do->reference_no ?: ('DO #' . $do->id)) . " (Rem Advance: Rs. " . number_format($totalRemainingAmount, 2) . "){$statusText}";
            } else {
                $displayText = ($do->reference_no ?: ('DO #' . $do->id)) . " (Capacity: Rs. " . number_format($unadvancedCapacity, 2) . "){$statusText}";
            }

            $dos[] = [
                'id' => $do->id,
                'reference_no' => $do->reference_no ?: ('DO #' . $do->id),
                'do_status' => $do->do_status,
                'am_approval_status' => $do->am_approval_status,
                'has_lp' => $hasLp,
                'do_qty' => $doTotalQty,
                'dc_qty' => 0,
                'remaining_qty' => round($totalRemainingQty, 2),
                'remaining_amount' => round($totalRemainingAmount, 2),
                'unadvanced_capacity' => round($unadvancedCapacity, 2),
                'formatted_remaining' => number_format($totalRemainingAmount, 2),
                'text' => $displayText
            ];
        }

        // Direct SO returnable advance
        $soTotalQty = 0;
        foreach ($salesOrder->sales_order_data as $soItem) {
            $soTotalQty += (float) $soItem->qty;
        }
        $firstSoRate = (float) ($salesOrder->sales_order_data->first()->rate ?? 1);
        $soRemainingAmount = $this->getSalesOrderRemainingAdvance($salesOrder, $excludeJvId);
        $soRemainingQty = $firstSoRate > 0 ? round($soRemainingAmount / $firstSoRate, 2) : 0;

        return response()->json([
            'success' => true,
            'so_id' => $salesOrder->id,
            'so_reference_no' => $salesOrder->reference_no,
            'so_status' => $salesOrder->status,
            'so_total_qty' => $soTotalQty,
            'so_remaining_qty' => round($soRemainingQty, 2),
            'so_remaining_amount' => round($soRemainingAmount, 2),
            'so_formatted_remaining' => number_format($soRemainingAmount, 2),
            'has_dos' => count($dos) > 0,
            'delivery_orders' => $dos
        ]);
    }

    private function saleBalances(): \App\Services\SaleAdvanceBalanceService
    {
        return app(\App\Services\SaleAdvanceBalanceService::class);
    }

    /**
     * Total advance left on the Sales Order and all its DOs (received - dispatched - refunds - JV debits).
     */
    public function getSalesOrderTotalNetAdvance($soId, $excludeJvId = null, $excludeVoucherNo = null, $excludePvId = null)
    {
        $soId = is_object($soId) ? $soId->id : $soId;

        return $this->saleBalances()->netAdvance((int) $soId, ['jv' => $excludeJvId, 'pv_no' => $excludeVoucherNo, 'pv' => $excludePvId]);
    }

    /**
     * What a JV can take directly from the Sales Order: the part of the advance no open DO is holding.
     */
    public function getSalesOrderRemainingAdvance($salesOrder, $excludeJvId = null)
    {
        $soId = is_object($salesOrder) ? $salesOrder->id : $salesOrder;

        return $this->saleBalances()->freeAdvance((int) $soId, ['jv' => $excludeJvId]);
    }

    /**
     * What a JV can take from a Delivery Order: its own remaining advance. 0 for an open DO with a Loading Program.
     */
    public function getDeliveryOrderRemainingAdvance($deliveryOrder, $excludeJvId = null)
    {
        $doId = is_object($deliveryOrder) ? $deliveryOrder->id : $deliveryOrder;

        return $this->saleBalances()->doJvAvailable($doId, ['jv' => $excludeJvId]);
    }

    /**
     * Room left on the Sales Order for advance transferred in by a JV credit.
     */
    public function getSalesOrderCreditCapacity($soId, $excludeJvId = null)
    {
        return $this->saleBalances()->soCreditCapacity((int) $soId, $excludeJvId);
    }

    /**
     * Advance received on a Delivery Order (attached RVs and credit JVs), used to cap incoming credits.
     * Refunds and debits lower the DO's value by the same amount, so they don't make room for more credit.
     */
    public function getDeliveryOrderActualAdvanceReceived($deliveryOrder, $excludeJvId = null)
    {
        $do = is_object($deliveryOrder) ? $deliveryOrder : \App\Models\Sales\DeliveryOrder::find($deliveryOrder);
        if (!$do) return 0;

        $attachedRvAmount = (float) DB::table('delivery_order_receipt_voucher as dorv')
            ->join('receipt_vouchers as rv', 'rv.id', '=', 'dorv.receipt_voucher_id')
            ->whereNull('rv.deleted_at')
            ->where('rv.am_approval_status', 'approved')
            ->where('dorv.delivery_order_id', $do->id)
            ->sum('dorv.amount');

        $creditJvs = (float) DB::table('journal_voucher_details')
            ->join('journal_vouchers', 'journal_vouchers.id', '=', 'journal_voucher_details.journal_voucher_id')
            ->whereNull('journal_vouchers.deleted_at')
            ->whereNull('journal_voucher_details.deleted_at')
            ->where('journal_vouchers.am_approval_status', '!=', 'rejected')
            ->when($excludeJvId, function ($q) use ($excludeJvId) {
                $q->where('journal_vouchers.id', '!=', $excludeJvId);
            })
            ->where('journal_voucher_details.delivery_order_id', $do->id)
            ->sum('journal_voucher_details.credit_amount');

        return $attachedRvAmount + $creditJvs;
    }
    /**
     * Calculate remaining credit balance of a Journal Voucher.
     */
    public function getJournalVoucherRemainingCredit($jvIdOrDetailId, $excludeJvId = null, $detailId = null)
    {
        $jv = JournalVoucher::find($jvIdOrDetailId);
        if ($jv) {
            $jvDetail = JournalVoucherDetail::with('journalVoucher')
                ->where('journal_voucher_id', $jv->id)
                ->when($detailId, function($q) use ($detailId) {
                    $q->where('id', $detailId);
                })
                ->where('credit_amount', '>', 0)
                ->first();
        } else {
            $jvDetail = JournalVoucherDetail::with('journalVoucher')
                ->where('id', $jvIdOrDetailId)
                ->where('credit_amount', '>', 0)
                ->first();
            $jv = $jvDetail?->journalVoucher;
        }

        if (!$jvDetail || !$jv) {
            return 0;
        }
        $creditAmount = (float) $jvDetail->credit_amount;

        $excludeJvNo = null;
        if ($excludeJvId) {
            $exJv = JournalVoucher::find($excludeJvId);
            $excludeJvNo = $exJv ? $exJv->jv_no : null;
        }

        $spentQuery = DB::table('settlement_adjustments')
            ->where('reference_type', 'journal_voucher')
            ->where('reference_id', $jv->id);

        if ($excludeJvNo) {
            $spentQuery->where('voucher_no', '!=', $excludeJvNo);
        }
        $settlementSpent = (float) $spentQuery->sum('amount');

        $refundQuery = DB::table('payment_voucher_data')
            ->join('payment_vouchers', 'payment_vouchers.id', '=', 'payment_voucher_data.payment_voucher_id')
            ->whereNull('payment_vouchers.deleted_at')
            ->whereNull('payment_voucher_data.deleted_at')
            ->where('payment_vouchers.am_approval_status', '!=', 'rejected')
            ->where(function ($q) use ($jvDetail, $jv) {
                $q->where('payment_voucher_data.journal_voucher_detail_id', $jvDetail->id)
                  ->orWhere('payment_voucher_data.journal_voucher_id', $jv->id);
            });
        $pvDataSpent = (float) $refundQuery->sum('net_amount');

        $otherJvSpentQuery = JournalVoucherDetail::join('journal_vouchers', 'journal_vouchers.id', '=', 'journal_voucher_details.journal_voucher_id')
            ->whereNull('journal_vouchers.deleted_at')
            ->whereNull('journal_voucher_details.deleted_at')
            ->where('journal_vouchers.am_approval_status', '!=', 'rejected')
            ->where(function ($q) use ($jv, $jvDetail) {
                $q->where('journal_voucher_details.reference_jv_id', $jv->id)
                  ->orWhere('journal_voucher_details.reference_jv_detail_id', $jvDetail->id);
            });
        if ($excludeJvId) {
            $otherJvSpentQuery->where('journal_vouchers.id', '!=', $excludeJvId);
        }
        $jvSpent = (float) $otherJvSpentQuery->sum('journal_voucher_details.debit_amount');

        $alreadySpent = max($settlementSpent, $pvDataSpent, $jvSpent);
        return round(max(0, $creditAmount - $alreadySpent), 2);
    }

    /**
     * Calculate available approved amount for a GRN after adjusting for Journal Vouchers.
     */
    public function getGrnAvailableLimit($grnIdOrNo, $excludeJvId = null, $accountId = null)
    {
        $grn = GrnNumber::where('id', $grnIdOrNo)
            ->orWhere('unique_no', $grnIdOrNo)
            ->first();

        if (!$grn) {
            return ['success' => false, 'message' => 'GRN was not found.'];
        }

        $ticketId = null;
        if ($grn->model_type === 'arrival-slip' && $grn->model_id) {
            $slip = DB::table('arrival_slips')->where('id', $grn->model_id)->first();
            $ticketId = $slip?->arrival_ticket_id;
        }

        $accountIds = [];
        $vendorId = null;
        $supplierId = null;

        if ($accountId) {
            $accountIds = [$accountId];
            $acc = Account::find($accountId);
            if ($acc) {
                $tableName = strtolower($acc->table_name ?? '');
                if ($tableName === 'vendors' || $tableName === 'vendor') {
                    $v = Vendor::where('account_id', $accountId)->first()
                        ?? ($acc->model_id ? Vendor::find($acc->model_id) : null)
                        ?? Vendor::where('name', $acc->name)->first();
                    if ($v) {
                        $vendorId = $v->id;
                        if ($v->account_id && !in_array($v->account_id, $accountIds)) {
                            $accountIds[] = $v->account_id;
                        }
                    }
                } elseif ($tableName === 'suppliers') {
                    $s = Supplier::where('account_id', $accountId)->first()
                        ?? ($acc->model_id ? Supplier::find($acc->model_id) : null)
                        ?? Supplier::where('name', $acc->name)->first();
                    if ($s) {
                        $supplierId = $s->id;
                        if ($s->account_id && !in_array($s->account_id, $accountIds)) {
                            $accountIds[] = $s->account_id;
                        }
                    }
                }
            }
        }

        // 1. Sum of all related payment_requests where status = 'approved' and (payment_vouchers or payment_voucher_data not exists)
        $totalApprovedPrAmount = (float) PaymentRequest::whereHas('paymentRequestData', function ($q) use ($grn, $ticketId) {
                $q->where('grn_no', $grn->unique_no);
                if ($ticketId) {
                    $q->orWhere('ticket_id', $ticketId)
                      ->orWhere('arrival_ticket_id', $ticketId);
                }
            })
            ->when(!empty($accountIds), function ($q) use ($accountIds, $vendorId, $supplierId) {
                $q->where(function ($sub) use ($accountIds, $vendorId, $supplierId) {
                    $sub->whereIn('payment_requests.account_id', $accountIds)
                        ->orWhere(function ($nullAcc) use ($accountIds, $vendorId, $supplierId) {
                            $nullAcc->whereNull('payment_requests.account_id')
                                    ->whereHas('paymentRequestData', function ($prd) use ($accountIds, $vendorId, $supplierId) {
                                        $prd->whereIn('account_id', $accountIds);
                                        if ($vendorId) {
                                            $prd->orWhere('labour_vendor_id', $vendorId)
                                                ->orWhere(function($p) use ($vendorId) {
                                                    $p->where('payment_to_type', 'vendors')->where('payment_to', $vendorId);
                                                });
                                        }
                                        if ($supplierId) {
                                            $prd->orWhere(function($p) use ($supplierId) {
                                                $p->where('payment_to_type', 'suppliers')->where('payment_to', $supplierId);
                                            });
                                        }
                                    });
                        });
                });
            })
            ->where('status', 'approved')
            ->where(function ($q) {
                $q->whereDoesntHave('paymentVoucherData')
                  ->orWhereDoesntHave('paymentVoucherData.paymentVoucher');
            })
            ->sum('amount');

        // 2. Check if there are Journal Vouchers on related record (adjusting for same JV if provided)
        $jvDetailsQuery = DB::table('journal_voucher_details')
            ->join('journal_vouchers', 'journal_vouchers.id', '=', 'journal_voucher_details.journal_voucher_id')
            ->whereNull('journal_vouchers.deleted_at')
            ->whereNull('journal_voucher_details.deleted_at')
            ->where(function ($q) {
                $q->whereNull('journal_vouchers.am_approval_status')
                  ->orWhere('journal_vouchers.am_approval_status', '!=', 'rejected');
            })
            ->where(function ($q) use ($grn) {
                $q->where('journal_voucher_details.voucher_id', $grn->id)
                  ->orWhere('journal_voucher_details.voucher_no', $grn->unique_no);
            })
            ->when(!empty($accountIds), function ($q) use ($accountIds) {
                $q->whereIn('journal_voucher_details.acc_id', $accountIds);
            });

        if ($excludeJvId) {
            $jvDetailsQuery->where('journal_vouchers.id', '!=', $excludeJvId);
        }

        $jvConsumedAmount = (float) $jvDetailsQuery->sum('journal_voucher_details.debit_amount');
        $availableLimit = max(0, round($totalApprovedPrAmount - $jvConsumedAmount, 2));

        return [
            'success' => true,
            'grn_id' => $grn->id,
            'unique_no' => $grn->unique_no,
            'acc_id' => $accountId,
            'total_approved_amount' => $totalApprovedPrAmount,
            'jv_consumed_amount' => $jvConsumedAmount,
            'available_amount' => $availableLimit,
            'max_amount' => $availableLimit
        ];
    }

    /**
     * AJAX endpoint to check that debit amount does not exceed GRN approved payment request balance.
     */
    public function checkGrnLimit(Request $request)
    {
        $grnId = $request->grn_id ?: $request->order_id ?: $request->voucher_no ?: $request->voucher_id;
        $jvId = $request->jv_id;
        $accId = $request->acc_id ?: $request->account_id;
        $debitAmount = $request->has('debit_amount') && $request->debit_amount !== '' && $request->debit_amount !== null
            ? (float) $request->debit_amount
            : null;

        if (!$grnId) {
            return response()->json([
                'success' => false,
                'message' => 'GRN ID or Number is required.'
            ], 422);
        }

        $limitInfo = $this->getGrnAvailableLimit($grnId, $jvId, $accId);

        if (!$limitInfo['success']) {
            return response()->json($limitInfo, 404);
        }

        $availableAmount = $limitInfo['available_amount'];
        $isExceeded = ($debitAmount !== null && round($debitAmount, 2) > round($availableAmount + 0.001, 2));

        return response()->json(array_merge($limitInfo, [
            'is_exceeded' => $isExceeded,
            'formatted_available' => number_format($availableAmount, 2),
            'formatted_total_approved' => number_format($limitInfo['total_approved_amount'], 2),
            'formatted_jv_consumed' => number_format($limitInfo['jv_consumed_amount'], 2),
            'message' => $isExceeded
                ? "Debit amount (" . number_format($debitAmount, 2) . ") exceeds GRN ({$limitInfo['unique_no']}) approved balance of " . number_format($availableAmount, 2)
                : null
        ]));
    }

    /**
     * Error when a JV debit on a Receipt Voucher is more than the RV still has. Names the JVs that already
     * adjusted the RV and the amount that can still be adjusted.
     */
    private function rvAdjustableError($rvId, float $debitAmount, $excludeJvId = null, $lineNo = null): ?string
    {
        $available = $this->saleBalances()->rvRemaining($rvId, ['jv' => $excludeJvId]);
        if (round($debitAmount, 2) <= round($available + 0.01, 2)) {
            return null;
        }

        $rvNo = DB::table('receipt_vouchers')->where('id', $rvId)->value('unique_no') ?: "RV #{$rvId}";
        $usedIn = DB::table('journal_voucher_details')
            ->join('journal_vouchers', 'journal_vouchers.id', '=', 'journal_voucher_details.journal_voucher_id')
            ->where('journal_voucher_details.receipt_voucher_id', $rvId)
            ->where('journal_voucher_details.debit_amount', '>', 0)
            ->whereNull('journal_voucher_details.deleted_at')
            ->whereNull('journal_vouchers.deleted_at')
            ->where(function ($q) {
                $q->whereNull('journal_vouchers.am_approval_status')
                  ->orWhere('journal_vouchers.am_approval_status', '!=', 'rejected');
            })
            ->when($excludeJvId, fn ($q) => $q->where('journal_vouchers.id', '!=', $excludeJvId))
            ->groupBy('journal_vouchers.jv_no')
            ->select('journal_vouchers.jv_no', DB::raw('SUM(journal_voucher_details.debit_amount) as total'))
            ->get()
            ->map(fn ($r) => "{$r->jv_no}: " . number_format($r->total, 2))
            ->implode(', ');

        return ($lineNo ? "Line {$lineNo}: " : '')
            . "The entered amount (" . number_format($debitAmount, 2) . ") for Receipt Voucher {$rvNo} exceeds its remaining adjustable balance of "
            . number_format($available, 2) . "."
            . ($usedIn ? " Already adjusted in {$usedIn}." : '');
    }

    /**
     * Validate Journal Entries for account ownership and RV balance limits.
     */
    protected function validateJournalEntries(Request $request, $excludeJvId = null)
    {
        $seenRvIds = [];
        $advTotalUsage = [];
        $seenAdvIds = [];
        $seenSoIds = [];
        $seenDoIds = [];
        $soCreditUsage = [];
        $doCreditUsage = [];
        $jvTotalUsage = [];
        $seenRefJvIds = [];
        $seenOrderIds = [];

        foreach ($request->details as $index => $detail) {
            $debitAmount = isset($detail['debit_amount']) ? (float) $detail['debit_amount'] : 0;
            $creditAmount = isset($detail['credit_amount']) ? (float) $detail['credit_amount'] : 0;

            if (!empty($detail['receipt_voucher_id'])) {
                $rvId = $detail['receipt_voucher_id'];
                $rv = ReceiptVoucher::find($rvId);
                if (!$rv) {
                    return "Line " . ($index + 1) . ": Receipt Voucher was not found.";
                }

                if (in_array($rvId, $seenRvIds)) {
                    return "Line " . ($index + 1) . ": Receipt Voucher {$rv->unique_no} cannot be selected multiple times.";
                }
                $seenRvIds[] = $rvId;

                if ($creditAmount > 0) {
                    return "Line " . ($index + 1) . ": Receipt Voucher {$rv->unique_no} cannot be credited. Receipt Vouchers can only be debited (withdrawn/adjusted).";
                }

                $customer = \App\Models\Master\Customer::where('account_id', $detail['acc_id'])->first();
                if (!$customer) {
                    $account = Account::find($detail['acc_id']);
                    if ($account && $account->table_name === 'customers') {
                        if ($account->model_id) $customer = \App\Models\Master\Customer::find($account->model_id);
                        if (!$customer) $customer = \App\Models\Master\Customer::where('name', $account->name)->first();
                    }
                }
                if (!$customer || $rv->customer_id != $customer->id) {
                    return "Line " . ($index + 1) . ": Receipt Voucher {$rv->unique_no} does not belong to the selected account.";
                }

                // An RV may be adjusted in more than one JV, but only up to what is still left on it
                if ($debitAmount > 0) {
                    $rvError = $this->rvAdjustableError($rvId, $debitAmount, $excludeJvId, $index + 1);
                    if ($rvError) {
                        return $rvError;
                    }
                }
            }

            if (!empty($detail['customer_advance_id'])) {
                $advId = $detail['customer_advance_id'];
                $adv = \App\Models\CustomerAdvance::find($advId);
                if (!$adv) {
                    return "Line " . ($index + 1) . ": Customer Advance was not found.";
                }

                if (in_array($advId, $seenAdvIds)) {
                    return "Line " . ($index + 1) . ": Excess Payment {$adv->voucher_no} cannot be selected multiple times.";
                }
                $seenAdvIds[] = $advId;

                if ($creditAmount > 0) {
                    return "Line " . ($index + 1) . ": Excess Payment {$adv->voucher_no} cannot be credited. Excess payments can only be debited.";
                }

                $customer = \App\Models\Master\Customer::where('account_id', $detail['acc_id'])->first();
                if (!$customer) {
                    $account = Account::find($detail['acc_id']);
                    if ($account && $account->table_name === 'customers') {
                        if ($account->model_id) $customer = \App\Models\Master\Customer::find($account->model_id);
                        if (!$customer) $customer = \App\Models\Master\Customer::where('name', $account->name)->first();
                    }
                }
                if (!$customer || $adv->customer_id != $customer->id) {
                    return "Line " . ($index + 1) . ": Excess Payment {$adv->voucher_no} does not belong to the selected account.";
                }

                if ($debitAmount > 0) {
                    $advTotalUsage[$advId] = ($advTotalUsage[$advId] ?? 0) + $debitAmount;
                }
            }

            if (!empty($detail['delivery_order_id'])) {
                $doId = $detail['delivery_order_id'];
                $do = \App\Models\Sales\DeliveryOrder::find($doId);
                if (!$do) {
                    return "Line " . ($index + 1) . ": Delivery Order was not found.";
                }
                if ($debitAmount > 0 && !$this->saleBalances()->isDoReleased($do->id) && delivery_order_has_loading_program($do->id)) {
                    return "Line " . ($index + 1) . ": Adjustment cannot be made for Delivery Order ({$do->reference_no}) because it is active and has an associated Loading Program. Please close the DO first if you wish to adjust.";
                }

                if (in_array($doId, $seenDoIds)) {
                    return "Line " . ($index + 1) . ": Delivery Order {$do->reference_no} cannot be selected multiple times.";
                }
                $seenDoIds[] = $doId;

                $customer = \App\Models\Master\Customer::where('account_id', $detail['acc_id'])->first();
                if (!$customer) {
                    $account = Account::find($detail['acc_id']);
                    if ($account && $account->table_name === 'customers') {
                        if ($account->model_id) $customer = \App\Models\Master\Customer::find($account->model_id);
                        if (!$customer) $customer = \App\Models\Master\Customer::where('name', $account->name)->first();
                    }
                }
                if (!$customer || $do->customer_id != $customer->id) {
                    return "Line " . ($index + 1) . ": Delivery Order {$do->reference_no} does not belong to the selected account.";
                }

                if ($creditAmount > 0) {
                    $doCreditUsage[$doId] = ($doCreditUsage[$doId] ?? 0) + $creditAmount;
                }
            }

            if (!empty($detail['reference_jv_id'])) {
                $refJvId = $detail['reference_jv_id'];
                $refJv = JournalVoucher::find($refJvId);
                if (!$refJv) {
                    return "Line " . ($index + 1) . ": Journal Voucher was not found.";
                }

                if (in_array($refJvId, $seenRefJvIds)) {
                    return "Line " . ($index + 1) . ": Journal Voucher {$refJv->jv_no} cannot be selected multiple times.";
                }
                $seenRefJvIds[] = $refJvId;

                if ($debitAmount > 0) {
                    $jvTotalUsage[$refJvId] = ($jvTotalUsage[$refJvId] ?? 0) + $debitAmount;
                }
            }

            if (!empty($detail['sales_order_id']) && empty($detail['delivery_order_id'])) {
                $soId = $detail['sales_order_id'];
                $so = SalesOrder::find($soId);
                if (!$so) {
                    return "Line " . ($index + 1) . ": Sales Order was not found.";
                }

                if (in_array($soId, $seenSoIds)) {
                    return "Line " . ($index + 1) . ": Sales Order {$so->reference_no} cannot be selected multiple times.";
                }
                $seenSoIds[] = $soId;

                $account = Account::find($detail['acc_id']);
                $tableName = strtolower($account->table_name ?? '');

                if ($tableName === 'vendors' || $tableName === 'vendor') {
                    $vendorRelated = $this->fetchAccountRelatedData($detail['acc_id'], $excludeJvId);
                    $vendorSoIds = collect($vendorRelated['sales_orders'] ?? [])->pluck('id')->toArray();
                    if (!in_array($soId, $vendorSoIds)) {
                        return "Line " . ($index + 1) . ": Sales Order {$so->reference_no} does not belong to the selected vendor account.";
                    }
                } else {
                    $customer = \App\Models\Master\Customer::where('account_id', $detail['acc_id'])->first();
                    if (!$customer) {
                        if ($account && $account->table_name === 'customers') {
                            if ($account->model_id) $customer = \App\Models\Master\Customer::find($account->model_id);
                            if (!$customer) $customer = \App\Models\Master\Customer::where('name', $account->name)->first();
                        }
                    }
                    if (!$customer || $so->customer_id != $customer->id) {
                        return "Line " . ($index + 1) . ": Sales Order {$so->reference_no} does not belong to the selected account.";
                    }

                    if ($creditAmount > 0) {
                        $soCreditUsage[$soId] = ($soCreditUsage[$soId] ?? 0) + $creditAmount;
                    }
                }
            }

            $rawOrderId = $detail['order_id'] ?? null;
            if (!empty($rawOrderId)) {
                $orderKey = ($detail['acc_id'] ?? '0') . '_' . $rawOrderId;
                if (in_array($orderKey, $seenOrderIds)) {
                    return "Line " . ($index + 1) . ": The selected Order/GRN cannot be selected multiple times for the same account.";
                }
                $seenOrderIds[] = $orderKey;
            }
        }

        foreach ($advTotalUsage as $advId => $totalEntered) {
            $adv = \App\Models\CustomerAdvance::find($advId);
            $available = (float) $adv->remaining_amount;
            if ($excludeJvId) {
                $excludeJv = JournalVoucher::find($excludeJvId);
                if ($excludeJv) {
                    $prevAdj = \App\Models\CustomerAdvanceAdjustment::where('customer_advance_id', $advId)
                        ->where('voucher_no', $excludeJv->jv_no)
                        ->sum('amount');
                    $available += (float) $prevAdj;
                }
            }
            if (round($totalEntered, 2) > round($available + 0.01, 2)) {
                return "The entered amount (" . number_format($totalEntered, 2) . ") for Excess Payment {$adv->voucher_no} exceeds its available balance of " . number_format($available, 2) . ".";
            }
        }

        // All debit lines of this JV are checked together against the shared SO / DO / RV balances
        $balanceError = $this->saleBalances()->validateJvDebits($request->details, $excludeJvId);
        if ($balanceError) {
            return $balanceError;
        }

        // Credit Adjustments (transferring advance INTO Customer B's SO or DO)
        foreach ($soCreditUsage as $soId => $totalCreditEntered) {
            $so = SalesOrder::find($soId);
            if (!$so) continue;

            $remainingOrderCapacity = $this->getSalesOrderCreditCapacity($soId, $excludeJvId);

            if (round($totalCreditEntered, 2) > round($remainingOrderCapacity + 0.01, 2)) {
                return "The entered credit amount (" . number_format($totalCreditEntered, 2) . ") for Sales Order {$so->reference_no} exceeds its remaining unadvanced order amount of " . number_format($remainingOrderCapacity, 2) . ".";
            }
        }
        foreach ($doCreditUsage as $doId => $totalCreditEntered) {
            $do = \App\Models\Sales\DeliveryOrder::find($doId);
            if (!$do) continue;

            $doTotalValue = 0;
            foreach ($do->delivery_order_data as $dod) {
                $doTotalValue += (float)$dod->qty * (float)$dod->rate;
            }

            if ($doTotalValue > 0) {
                $currentDoAdvance = $this->getDeliveryOrderActualAdvanceReceived($do, $excludeJvId);
                $remainingDoCapacity = max(0, $doTotalValue - $currentDoAdvance);

                if (round($totalCreditEntered, 2) > round($remainingDoCapacity + 0.01, 2)) {
                    return "The entered credit amount (" . number_format($totalCreditEntered, 2) . ") for Delivery Order {$do->reference_no} exceeds its remaining unadvanced order amount of " . number_format($remainingDoCapacity, 2) . ".";
                }
            }
        }

        foreach ($jvTotalUsage as $refJvId => $totalEntered) {
            $refJv = JournalVoucher::find($refJvId);
            if (!$refJv) continue;

            $remJvAmount = $this->getJournalVoucherRemainingCredit($refJvId, $excludeJvId);
            if (round($totalEntered, 2) > round($remJvAmount + 0.01, 2)) {
                return "The entered amount (" . number_format($totalEntered, 2) . ") for Journal Voucher {$refJv->jv_no} exceeds its available balance of " . number_format($remJvAmount, 2) . ".";
            }
        }

        // Validate GRN debit limits
        $grnTotalUsage = [];
        foreach ($request->details as $index => $detail) {
            $orderId = $detail['order_id'] ?? $detail['voucher_id'] ?? $detail['voucher_no'] ?? null;
            $voucherType = strtolower(trim($detail['voucher_type'] ?? ''));
            $debitAmount = isset($detail['debit_amount']) ? (float) $detail['debit_amount'] : 0;
            $accId = $detail['acc_id'] ?? null;
            $account = $accId ? Account::find($accId) : null;
            $tableName = strtolower($account->table_name ?? '');

            $isGrn = ($voucherType === 'grn') || (in_array($tableName, ['suppliers', 'vendors', 'vendor']) && !empty($orderId));
            if ($isGrn && !empty($orderId)) {
                $grn = GrnNumber::where('id', $orderId)->orWhere('unique_no', $orderId)->first();
                if (!$grn) {
                    return "Line " . ($index + 1) . ": GRN was not found.";
                }
                $usageKey = $grn->id . '_' . ($accId ?: '0');
                if (!isset($grnTotalUsage[$usageKey])) {
                    $grnTotalUsage[$usageKey] = [
                        'grn_id' => $grn->id,
                        'acc_id' => $accId,
                        'debit' => 0
                    ];
                }
                $grnTotalUsage[$usageKey]['debit'] += $debitAmount;
            }
        }

        foreach ($grnTotalUsage as $usage) {
            $grnId = $usage['grn_id'];
            $accId = $usage['acc_id'];
            $totalEnteredDebit = $usage['debit'];
            if ($totalEnteredDebit > 0) {
                $limitInfo = $this->getGrnAvailableLimit($grnId, $excludeJvId, $accId);
                if ($limitInfo['success']) {
                    $remaining = $limitInfo['available_amount'];
                    if (round($totalEnteredDebit, 2) > round($remaining + 0.01, 2)) {
                        return "The entered debit amount (" . number_format($totalEnteredDebit, 2) . ") for GRN {$limitInfo['unique_no']} exceeds its available approved balance of " . number_format($remaining, 2) . ".";
                    }
                }
            }
        }

        return null;
    }

    /**
     * Generate JV number
     */
    public function generateJvNumber(Request $request)
    {
        $request->validate([
            'jv_date' => 'nullable|date'
        ]);

        $prefix = 'JV';
        $jvDate = $request->jv_date ? date('m-d-Y', strtotime($request->jv_date)) : date('m-d-Y');
        $datePrefix = $prefix . '-' . $jvDate . '-';
        $uniqueNo = generateUniqueNumberByDate('journal_vouchers', $datePrefix, null, 'jv_no', false);

        return response()->json([
            'success' => true,
            'jv_number' => $uniqueNo
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // dd($request->all());
        $request->validate([
            'jv_date' => 'required|date',
            'jv_no' => 'required|string',
            'description' => 'nullable|string',
            'details' => 'required|array|min:2',
            'details.*.acc_id' => 'required|exists:accounts,id',
            'details.*.receipt_voucher_id' => 'nullable|exists:receipt_vouchers,id',
            'details.*.customer_advance_id' => 'nullable|exists:customer_advances,id',
            'details.*.sales_order_id' => 'nullable|exists:sales_orders,id',
            'details.*.delivery_order_id' => 'nullable|exists:delivery_order,id',
            'details.*.reference_jv_id' => 'nullable|exists:journal_vouchers,id',
            'details.*.reference_jv_detail_id' => 'nullable|exists:journal_voucher_details,id',
            'details.*.order_id' => 'nullable',
            'details.*.voucher_id' => 'nullable',
            'details.*.voucher_no' => 'nullable|string',
            'details.*.voucher_type' => 'nullable|string',
            'details.*.description' => 'nullable|string',
            'details.*.debit_amount' => 'nullable|numeric|min:0',
            'details.*.credit_amount' => 'nullable|numeric|min:0',
        ]);

        // Validate that total debits equal total credits
        $totalDebits = 0;
        $totalCredits = 0;

        foreach ($request->details as $index => $detail) {
            $debitAmount = isset($detail['debit_amount']) ? (float) $detail['debit_amount'] : 0;
            $creditAmount = isset($detail['credit_amount']) ? (float) $detail['credit_amount'] : 0;

            $debitAmount = round($debitAmount, 2);
            $creditAmount = round($creditAmount, 2);

            if ($debitAmount <= 0 && $creditAmount <= 0) {
                return response()->json([
                    'error' => 'Each line item must have either a debit or credit amount greater than zero.'
                ], 422);
            }

            if ($debitAmount > 0 && $creditAmount > 0) {
                return response()->json([
                    'error' => 'Each line item can only have either a debit or a credit amount, not both.'
                ], 422);
            }

            $totalDebits += $debitAmount;
            $totalCredits += $creditAmount;
        }

        if (abs($totalDebits - $totalCredits) > 0.01) {
            return response()->json([
                'error' => 'Total debits must equal total credits. Debits: ' . number_format($totalDebits, 2) . ', Credits: ' . number_format($totalCredits, 2)
            ], 422);
        }

        $validationError = $this->validateJournalEntries($request);
        if ($validationError) {
            return response()->json([
                'error' => $validationError,
                'message' => $validationError,
                'errors' => ['receipt_voucher' => [$validationError]]
            ], 422);
        }

        try {
            DB::transaction(function () use ($request, $totalDebits, $totalCredits) {
                // Concurrency safety: lock each selected RV, then re-check its balance so two tabs can not over-adjust it
                $rvDebits = [];
                foreach ($request->details as $detail) {
                    if (!empty($detail['receipt_voucher_id']) && (float) ($detail['debit_amount'] ?? 0) > 0) {
                        $rvDebits[$detail['receipt_voucher_id']] = ($rvDebits[$detail['receipt_voucher_id']] ?? 0) + round((float) $detail['debit_amount'], 2);
                    }
                }
                foreach ($rvDebits as $rvId => $rvDebit) {
                    ReceiptVoucher::where('id', $rvId)->lockForUpdate()->first();
                    $rvError = $this->rvAdjustableError($rvId, $rvDebit, null);
                    if ($rvError) {
                        throw new \Exception($rvError);
                    }
                }

                $username = Auth::user()->name ?? Auth::user()->email ?? 'System';

                // Regenerate JV number if empty or already taken by another tab
                $jvNo = $request->jv_no;
                if (empty($jvNo) || JournalVoucher::where('jv_no', $jvNo)->exists()) {
                    $prefix = 'JV';
                    $jvDate = $request->jv_date ? date('m-d-Y', strtotime($request->jv_date)) : date('m-d-Y');
                    $datePrefix = $prefix . '-' . $jvDate . '-';
                    $jvNo = generateUniqueNumberByDate('journal_vouchers', $datePrefix, null, 'jv_no', false);
                }

                $journalVoucher = JournalVoucher::create([
                    'jv_date' => $request->jv_date,
                    'jv_no' => $jvNo,
                    'description' => $request->description,
                    'username' => $username,
                    'status' => 'active',
                    'jv_status' => 'approved',
                    'am_approval_status' => 'pending',
                    'am_change_made' => 1,
                    'created_by' => Auth::user()->id ?? 1,
                    'approve_user_id' => null,
                    'company_id' => Auth::user()?->current_company_id ?? 1
                ]);



                foreach ($request->details as $detail) {
                    $debitAmount = isset($detail['debit_amount']) ? (float) $detail['debit_amount'] : 0;
                    $creditAmount = isset($detail['credit_amount']) ? (float) $detail['credit_amount'] : 0;

                    $debitAmount = round($debitAmount, 2);
                    $creditAmount = round($creditAmount, 2);

                    $voucherId = $detail['voucher_id'] ?? null;
                    $voucherNo = $detail['voucher_no'] ?? null;
                    $voucherType = $detail['voucher_type'] ?? null;
                    $salesOrderId = $detail['sales_order_id'] ?? null;
                    $deliveryOrderId = $detail['delivery_order_id'] ?? null;
                    $referenceJvId = $detail['reference_jv_id'] ?? null;
                    $referenceJvDetailId = $detail['reference_jv_detail_id'] ?? null;
                    $receiptVoucherId = $detail['receipt_voucher_id'] ?? null;
                    $customerAdvanceId = $detail['customer_advance_id'] ?? null;
                    $orderId = $detail['order_id'] ?? null;

                    if ($customerAdvanceId) {
                        $adv = \App\Models\CustomerAdvance::find($customerAdvanceId);
                        if ($adv) {
                            $voucherId = $adv->id;
                            $voucherNo = $adv->voucher_no;
                            $voucherType = 'customer_advance';

                            $adjAmount = max($debitAmount, $creditAmount);
                            if ($adjAmount > 0) {
                                if ($adjAmount > $adv->remaining_amount) {
                                    throw new \Exception("Cannot consume more than the remaining amount of Excess Payment {$adv->voucher_no}.");
                                }
                                $adv->used_amount += $adjAmount;
                                $adv->remaining_amount -= $adjAmount;
                                $adv->status = ($adv->remaining_amount <= 0.01) ? 'completed' : 'partial_payment';
                                $adv->save();

                                \App\Models\CustomerAdvanceAdjustment::create([
                                    'customer_advance_id' => $adv->id,
                                    'voucher_no' => $journalVoucher->jv_no,
                                    'amount' => $adjAmount
                                ]);
                            }
                        }
                    }

                    if ($deliveryOrderId) {
                        $do = \App\Models\Sales\DeliveryOrder::find($deliveryOrderId);
                        if ($do) {
                            if ($debitAmount > 0 && !$this->saleBalances()->isDoReleased($do->id) && delivery_order_has_loading_program($do->id)) {
                                throw new \Exception("Adjustment cannot be made for Delivery Order ({$do->reference_no}) because it is active and has an associated Loading Program. Please close the DO first if you wish to adjust.");
                            }
                            $voucherId = $do->id;
                            $voucherNo = $do->reference_no ?: ('DO #' . $do->id);
                            $voucherType = 'delivery_order';
                            if (!$salesOrderId && $do->so_id) {
                                $salesOrderId = $do->so_id;
                            }
                        }
                    }

                    if ($referenceJvId) {
                        $refJv = JournalVoucher::find($referenceJvId);
                        if ($refJv) {
                            $voucherId = $refJv->id;
                            $voucherNo = $refJv->jv_no;
                            $voucherType = 'journal_voucher';

                            if (!$referenceJvDetailId) {
                                $refDet = JournalVoucherDetail::where('journal_voucher_id', $refJv->id)
                                    ->where('credit_amount', '>', 0)
                                    ->first();
                                $referenceJvDetailId = $refDet?->id;
                            }

                            $adjAmount = max($debitAmount, $creditAmount);
                            if ($adjAmount > 0) {
                                DB::table('settlement_adjustments')->insert([
                                    'reference_type' => 'journal_voucher',
                                    'reference_id' => $refJv->id,
                                    'voucher_no' => $journalVoucher->jv_no,
                                    'amount' => $adjAmount,
                                    'created_at' => now(),
                                    'updated_at' => now(),
                                ]);
                            }
                        }
                    }

                    if ($orderId && (empty($voucherId) || empty($voucherNo) || empty($voucherType))) {
                        $account = Account::find($detail['acc_id']);
                        $tableName = strtolower($account->table_name ?? '');

                        if ($tableName === 'customers') {
                            $so = \App\Models\Sales\SalesOrder::find($orderId);
                            if ($so) {
                                $voucherId = $so->id;
                                $voucherNo = $so->reference_no ?? $so->unique_no;
                                $voucherType = 'sales_order';
                                $salesOrderId = $salesOrderId ?: $so->id;
                            }
                        } elseif ($tableName === 'suppliers' || $tableName === 'vendors' || $tableName === 'vendor') {
                            $grn = \App\Models\Master\GrnNumber::where('id', $orderId)
                                ->orWhere('unique_no', $orderId)
                                ->first();
                            if ($grn) {
                                $voucherId = $grn->id;
                                $voucherNo = $grn->unique_no;
                                $voucherType = 'grn';
                            }
                        }
                    }

                    if ($salesOrderId && empty($voucherId)) {
                        $so = \App\Models\Sales\SalesOrder::find($salesOrderId);
                        if ($so) {
                            $voucherId = $so->id;
                            $voucherNo = $so->reference_no ?? $so->unique_no;
                            $voucherType = 'sales_order';
                        }
                    }

                    JournalVoucherDetail::create([
                        'journal_voucher_id' => $journalVoucher->id,
                        'acc_id' => $detail['acc_id'],
                        'receipt_voucher_id' => $receiptVoucherId,
                        'customer_advance_id' => $customerAdvanceId,
                        'sales_order_id' => $salesOrderId,
                        'delivery_order_id' => $deliveryOrderId,
                        'reference_jv_id' => $referenceJvId,
                        'reference_jv_detail_id' => $referenceJvDetailId,
                        'voucher_id' => $voucherId,
                        'voucher_no' => $voucherNo,
                        'voucher_type' => $voucherType,
                        'debit_amount' => $debitAmount,
                        'credit_amount' => $creditAmount,
                        'description' => $detail['description'] ?? null,
                        'username' => $username,
                        'status' => 'active',
                        'timestamp' => now()
                    ]);

                }
            });
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'message' => $e->getMessage(),
                'errors' => ['receipt_voucher' => [$e->getMessage()]]
            ], 422);
        }

        return response()->json([
            'success' => 'Journal voucher created successfully!',
            'redirect' => route('journal-voucher.index')
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $journalVoucher = JournalVoucher::with([
            'journalVoucherDetails.account',
            'journalVoucherDetails.receiptVoucher',
            'journalVoucherDetails.customerAdvance',
            'journalVoucherDetails.salesOrder',
            'journalVoucherDetails.deliveryOrder',
            'journalVoucherDetails.referenceJournalVoucher',
            'approveUser',
            'deleteUser',
            'createdBy'
        ])->findOrFail($id);

        $jvModule = $journalVoucher->getApprovalModule();
        $jvApprovalLogs = $jvModule ? \App\Models\ApprovalsModule\ApprovalLog::where('record_id', $journalVoucher->id)
            ->where('module_id', $jvModule->id)
            ->with(['user', 'role'])
            ->orderBy('created_at', 'desc')
            ->get() : collect();

        return view('management.finance.journal_voucher.show', compact('journalVoucher', 'jvApprovalLogs'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $journalVoucher = JournalVoucher::with(['journalVoucherDetails.account', 'journalVoucherDetails.deliveryOrder', 'journalVoucherDetails.referenceJournalVoucher'])->findOrFail($id);

        // Prevent editing approved or rejected vouchers
        $currentApprovalStatus = strtolower($journalVoucher->am_approval_status ?? 'pending');
        if (in_array($currentApprovalStatus, ['approved', 'rejected'])) {
            return redirect()->route('journal-voucher.index')
                ->with('error', "Cannot edit a journal voucher that has been {$currentApprovalStatus}.");
        }

        $accounts = Account::where("is_operational", "yes")->get();

        $rowRvs = [];
        $rowAdvances = [];
        $rowSos = [];
        $rowDos = [];
        $rowJvs = [];
        $rowOrders = [];
        foreach ($journalVoucher->journalVoucherDetails as $index => $detail) {
            $related = $this->fetchAccountRelatedData($detail->acc_id, $id);
            $rowRvs[$index] = collect($related['receipt_vouchers']);
            $rowAdvances[$index] = collect($related['customer_advances']);
            $rowSos[$index] = collect($related['sales_orders']);
            $rowJvs[$index] = collect($related['journal_vouchers']);
            $rowOrders[$index] = collect([]);
            $rowDos[$index] = collect([]);

            $tableName = strtolower($detail->account->table_name ?? ($related['table_name'] ?? ''));

            if ($tableName === 'customers') {
                $rowOrders[$index] = collect($related['sales_orders']);
                $orderId = $detail->voucher_id ?: $detail->sales_order_id;
                if ($orderId && !$rowOrders[$index]->contains('id', $orderId)) {
                    $currSo = $detail->salesOrder ?: \App\Models\Sales\SalesOrder::find($orderId);
                    if ($currSo) {
                        $rowOrders[$index]->prepend([
                            'id' => $currSo->id,
                            'reference_no' => $currSo->reference_no,
                            'unique_no' => $currSo->reference_no,
                            'text' => $currSo->reference_no,
                            'type' => 'sales_order'
                        ]);
                    }
                }
            } elseif ($tableName === 'suppliers' || $tableName === 'vendors' || $tableName === 'vendor') {
                $rowOrders[$index] = collect($related['grns']);
                $orderId = $detail->voucher_id;
                if ($orderId && !$rowOrders[$index]->contains('id', $orderId)) {
                    $currGrn = $detail->grn ?: \App\Models\Master\GrnNumber::find($orderId);
                    if (!$currGrn && $detail->voucher_no) {
                        $currGrn = \App\Models\Master\GrnNumber::where('unique_no', $detail->voucher_no)->first();
                    }
                    if ($currGrn) {
                        $limitInfo = $this->getGrnAvailableLimit($currGrn->id, $id, $detail->acc_id);
                        $remaining = $limitInfo['available_amount'] ?? 0;
                        $rowOrders[$index]->prepend([
                            'id' => $currGrn->id,
                            'unique_no' => $currGrn->unique_no,
                            'text' => $currGrn->unique_no . ($remaining > 0 ? ' (Rem: ' . number_format($remaining, 2) . ')' : ''),
                            'type' => 'grn',
                            'remaining_amount' => $remaining
                        ]);
                    }
                }
            }

            // Ensure current selected RV is present even if remaining is 0 or consumed
            if ($detail->receipt_voucher_id && !$rowRvs[$index]->contains('id', $detail->receipt_voucher_id)) {
                $currRv = $detail->receiptVoucher;
                if ($currRv) {
                    $currAmount = (float) max($detail->debit_amount, $detail->credit_amount);
                    $rowRvs[$index]->prepend([
                        'id' => $currRv->id,
                        'unique_no' => $currRv->unique_no,
                        'total_amount' => (float) $currRv->total_amount,
                        'remaining_amount' => $currAmount,
                        'formatted_remaining' => number_format($currAmount, 2),
                        'text' => $currRv->unique_no . ' (Current: ' . number_format($currAmount, 2) . ')'
                    ]);
                }
            }

            // Ensure current selected Advance is present even if remaining is 0 or consumed
            $advId = $detail->customer_advance_id ?: ($detail->voucher_type === 'customer_advance' ? $detail->voucher_id : null);
            if ($advId && !$rowAdvances[$index]->contains('id', $advId)) {
                $currAdv = \App\Models\CustomerAdvance::find($advId);
                if ($currAdv) {
                    $currAmount = (float) max($detail->debit_amount, $detail->credit_amount);
                    $totalAvail = (float) $currAdv->remaining_amount + $currAmount;
                    $rowAdvances[$index]->prepend([
                        'id' => $currAdv->id,
                        'voucher_no' => $currAdv->voucher_no,
                        'source_type' => $currAdv->source_type,
                        'remaining_amount' => $totalAvail,
                        'formatted_remaining' => number_format($totalAvail, 2),
                        'text' => $currAdv->voucher_no . ' (Current: ' . number_format($totalAvail, 2) . ')',
                        'type' => 'customer_advance'
                    ]);
                }
            }

            // Ensure current selected SO is present
            if ($detail->sales_order_id && !$rowSos[$index]->contains('id', $detail->sales_order_id)) {
                $currSo = $detail->salesOrder;
                if ($currSo) {
                    $rowSos[$index]->prepend([
                        'id' => $currSo->id,
                        'reference_no' => $currSo->reference_no,
                        'unique_no' => $currSo->reference_no,
                        'text' => $currSo->reference_no,
                        'type' => 'sales_order'
                    ]);
                }
            }

            // Load DOs for this line's SO
            if ($detail->sales_order_id) {
                try {
                    $soDosResp = $this->getSoDeliveryOrders(request(), $detail->sales_order_id);
                    $soDosJson = $soDosResp->getData(true);
                    if (!empty($soDosJson['delivery_orders'])) {
                        $rowDos[$index] = collect($soDosJson['delivery_orders']);
                    }
                } catch (\Exception $e) {}
            }

            if ($detail->delivery_order_id && !$rowDos[$index]->contains('id', $detail->delivery_order_id)) {
                $currDo = $detail->deliveryOrder ?: \App\Models\Sales\DeliveryOrder::find($detail->delivery_order_id);
                if ($currDo) {
                    $currAmount = (float) max($detail->debit_amount, $detail->credit_amount);
                    $rowDos[$index]->prepend([
                        'id' => $currDo->id,
                        'reference_no' => $currDo->reference_no ?: ('DO #' . $currDo->id),
                        'remaining_amount' => $currAmount,
                        'formatted_remaining' => number_format($currAmount, 2),
                        'text' => ($currDo->reference_no ?: ('DO #' . $currDo->id)) . " (Current: Rs. " . number_format($currAmount, 2) . ")",
                    ]);
                }
            }

            // Ensure current selected Reference JV is present
            if ($detail->reference_jv_id && !$rowJvs[$index]->contains('id', $detail->reference_jv_id)) {
                $currRefJv = $detail->referenceJournalVoucher ?: JournalVoucher::find($detail->reference_jv_id);
                if ($currRefJv) {
                    $currAmount = (float) max($detail->debit_amount, $detail->credit_amount);
                    $rowJvs[$index]->prepend([
                        'id' => $currRefJv->id,
                        'detail_id' => $detail->reference_jv_detail_id,
                        'jv_no' => $currRefJv->jv_no,
                        'credit_amount' => $currAmount,
                        'remaining_amount' => $currAmount,
                        'formatted_remaining' => number_format($currAmount, 2),
                        'description' => $currRefJv->description ?: '',
                        'text' => "{$currRefJv->jv_no} (Current: Rs. " . number_format($currAmount, 2) . ")",
                        'type' => 'journal_voucher'
                    ]);
                }
            }
        }

        $data = [
            'journalVoucher' => $journalVoucher,
            'accounts' => $accounts,
            'rowRvs' => $rowRvs,
            'rowAdvances' => $rowAdvances,
            'rowSos' => $rowSos,
            'rowDos' => $rowDos,
            'rowJvs' => $rowJvs,
            'rowOrders' => $rowOrders,
            'receiptVouchers' => collect([]),
            'customerAdvances' => collect([]),
            'salesOrders' => collect([])
        ];

        return view('management.finance.journal_voucher.edit', $data);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $journalVoucher = JournalVoucher::findOrFail($id);

        // Prevent updating approved or rejected vouchers
        $currentApprovalStatus = strtolower($journalVoucher->am_approval_status ?? 'pending');
        if (in_array($currentApprovalStatus, ['approved', 'rejected'])) {
            return response()->json([
                'error' => "Cannot update a journal voucher that has been {$currentApprovalStatus}."
            ], 422);
        }

        $request->validate([
            'jv_date' => 'required|date',
            'description' => 'nullable|string',
            'details' => 'required|array|min:2',
            'details.*.acc_id' => 'required|exists:accounts,id',
            'details.*.receipt_voucher_id' => 'nullable|exists:receipt_vouchers,id',
            'details.*.customer_advance_id' => 'nullable|exists:customer_advances,id',
            'details.*.sales_order_id' => 'nullable|exists:sales_orders,id',
            'details.*.delivery_order_id' => 'nullable|exists:delivery_order,id',
            'details.*.reference_jv_id' => 'nullable|exists:journal_vouchers,id',
            'details.*.reference_jv_detail_id' => 'nullable|exists:journal_voucher_details,id',
            'details.*.order_id' => 'nullable',
            'details.*.voucher_id' => 'nullable',
            'details.*.voucher_no' => 'nullable|string',
            'details.*.voucher_type' => 'nullable|string',
            'details.*.description' => 'nullable|string',
            'details.*.debit_amount' => 'nullable|numeric|min:0',
            'details.*.credit_amount' => 'nullable|numeric|min:0',
        ]);

        // Validate that total debits equal total credits
        $totalDebits = 0;
        $totalCredits = 0;

        foreach ($request->details as $detail) {
            $debitAmount = isset($detail['debit_amount']) ? (float) $detail['debit_amount'] : 0;
            $creditAmount = isset($detail['credit_amount']) ? (float) $detail['credit_amount'] : 0;

            if ($debitAmount <= 0 && $creditAmount <= 0) {
                return response()->json([
                    'error' => 'Each line item must have either a debit or credit amount greater than zero.'
                ], 422);
            }

            if ($debitAmount > 0 && $creditAmount > 0) {
                return response()->json([
                    'error' => 'Each line item can only have either a debit or a credit amount, not both.'
                ], 422);
            }

            $totalDebits += $debitAmount;
            $totalCredits += $creditAmount;
        }

        if (abs($totalDebits - $totalCredits) > 0.01) {
            return response()->json([
                'error' => 'Total debits must equal total credits. Debits: ' . number_format($totalDebits, 2) . ', Credits: ' . number_format($totalCredits, 2)
            ], 422);
        }

        $validationError = $this->validateJournalEntries($request, $journalVoucher->id);
        if ($validationError) {
            return response()->json([
                'error' => $validationError,
                'message' => $validationError,
                'errors' => ['receipt_voucher' => [$validationError]]
            ], 422);
        }

        try {
            DB::transaction(function () use ($request, $journalVoucher, $totalDebits, $totalCredits) {
                // Concurrency safety: lock each selected RV, then re-check its balance so two tabs can not over-adjust it
                $rvDebits = [];
                foreach ($request->details as $detail) {
                    if (!empty($detail['receipt_voucher_id']) && (float) ($detail['debit_amount'] ?? 0) > 0) {
                        $rvDebits[$detail['receipt_voucher_id']] = ($rvDebits[$detail['receipt_voucher_id']] ?? 0) + round((float) $detail['debit_amount'], 2);
                    }
                }
                foreach ($rvDebits as $rvId => $rvDebit) {
                    ReceiptVoucher::where('id', $rvId)->lockForUpdate()->first();
                    $rvError = $this->rvAdjustableError($rvId, $rvDebit, $journalVoucher->id);
                    if ($rvError) {
                        throw new \Exception($rvError);
                    }
                }

                $username = Auth::user()->name ?? Auth::user()->email ?? 'System';

                $journalVoucher->update([
                    'jv_date' => $request->jv_date,
                    'description' => $request->description,
                    'username' => $username,
                    'status' => 'active',
                    'jv_status' => 'approved',
                    'am_approval_status' => 'pending',
                    'am_change_made' => 1,
                    'company_id' => Auth::user()?->current_company_id ?? $journalVoucher->company_id
                ]);

                // Revert old customer advance adjustments for this JV
                $prevAdjustments = \App\Models\CustomerAdvanceAdjustment::where('voucher_no', $journalVoucher->jv_no)->get();
                foreach ($prevAdjustments as $adj) {
                    $adv = \App\Models\CustomerAdvance::find($adj->customer_advance_id);
                    if ($adv) {
                        $adv->used_amount -= $adj->amount;
                        $adv->remaining_amount += $adj->amount;
                        $adv->status = ($adv->used_amount <= 0.01) ? 'pending' : 'partial_payment';
                        $adv->save();
                    }
                    $adj->delete();
                }

                // Revert previous settlement_adjustments for this JV
                DB::table('settlement_adjustments')->where('voucher_no', $journalVoucher->jv_no)->delete();

                // Delete old details
                JournalVoucherDetail::where('journal_voucher_id', $journalVoucher->id)->delete();

                // Delete old transactions if any existed
                Transaction::where('voucher_no', $journalVoucher->jv_no)
                    ->where('purpose', 'like', "journal-voucher-{$journalVoucher->id}%")
                    ->delete();



                // Create new details
                foreach ($request->details as $detail) {
                    $debitAmount = isset($detail['debit_amount']) ? (float) $detail['debit_amount'] : 0;
                    $creditAmount = isset($detail['credit_amount']) ? (float) $detail['credit_amount'] : 0;

                    $debitAmount = round($debitAmount, 2);
                    $creditAmount = round($creditAmount, 2);

                    $voucherId = $detail['voucher_id'] ?? null;
                    $voucherNo = $detail['voucher_no'] ?? null;
                    $voucherType = $detail['voucher_type'] ?? null;
                    $salesOrderId = $detail['sales_order_id'] ?? null;
                    $deliveryOrderId = $detail['delivery_order_id'] ?? null;
                    $referenceJvId = $detail['reference_jv_id'] ?? null;
                    $referenceJvDetailId = $detail['reference_jv_detail_id'] ?? null;
                    $receiptVoucherId = $detail['receipt_voucher_id'] ?? null;
                    $customerAdvanceId = $detail['customer_advance_id'] ?? null;
                    $orderId = $detail['order_id'] ?? null;

                    if ($customerAdvanceId) {
                        $adv = \App\Models\CustomerAdvance::find($customerAdvanceId);
                        if ($adv) {
                            $voucherId = $adv->id;
                            $voucherNo = $adv->voucher_no;
                            $voucherType = 'customer_advance';

                            $adjAmount = max($debitAmount, $creditAmount);
                            if ($adjAmount > 0) {
                                if ($adjAmount > $adv->remaining_amount) {
                                    throw new \Exception("Cannot consume more than the remaining amount of Excess Payment {$adv->voucher_no}.");
                                }
                                $adv->used_amount += $adjAmount;
                                $adv->remaining_amount -= $adjAmount;
                                $adv->status = ($adv->remaining_amount <= 0.01) ? 'completed' : 'partial_payment';
                                $adv->save();

                                \App\Models\CustomerAdvanceAdjustment::create([
                                    'customer_advance_id' => $adv->id,
                                    'voucher_no' => $journalVoucher->jv_no,
                                    'amount' => $adjAmount
                                ]);
                            }
                        }
                    }

                    if ($deliveryOrderId) {
                        $do = \App\Models\Sales\DeliveryOrder::find($deliveryOrderId);
                        if ($do) {
                            if ($debitAmount > 0 && !$this->saleBalances()->isDoReleased($do->id) && delivery_order_has_loading_program($do->id)) {
                                throw new \Exception("Adjustment cannot be made for Delivery Order ({$do->reference_no}) because it is active and has an associated Loading Program. Please close the DO first if you wish to adjust.");
                            }
                            $voucherId = $do->id;
                            $voucherNo = $do->reference_no ?: ('DO #' . $do->id);
                            $voucherType = 'delivery_order';
                            if (!$salesOrderId && $do->so_id) {
                                $salesOrderId = $do->so_id;
                            }
                        }
                    }

                    if ($referenceJvId) {
                        $refJv = JournalVoucher::find($referenceJvId);
                        if ($refJv) {
                            $voucherId = $refJv->id;
                            $voucherNo = $refJv->jv_no;
                            $voucherType = 'journal_voucher';

                            if (!$referenceJvDetailId) {
                                $refDet = JournalVoucherDetail::where('journal_voucher_id', $refJv->id)
                                    ->where('credit_amount', '>', 0)
                                    ->first();
                                $referenceJvDetailId = $refDet?->id;
                            }

                            $adjAmount = max($debitAmount, $creditAmount);
                            if ($adjAmount > 0) {
                                DB::table('settlement_adjustments')->insert([
                                    'reference_type' => 'journal_voucher',
                                    'reference_id' => $refJv->id,
                                    'voucher_no' => $journalVoucher->jv_no,
                                    'amount' => $adjAmount,
                                    'created_at' => now(),
                                    'updated_at' => now(),
                                ]);
                            }
                        }
                    }

                    if ($orderId && (empty($voucherId) || empty($voucherNo) || empty($voucherType))) {
                        $account = Account::find($detail['acc_id']);
                        $tableName = strtolower($account->table_name ?? '');

                        if ($tableName === 'customers') {
                            $so = \App\Models\Sales\SalesOrder::find($orderId);
                            if ($so) {
                                $voucherId = $so->id;
                                $voucherNo = $so->reference_no ?? $so->unique_no;
                                $voucherType = 'sales_order';
                                $salesOrderId = $salesOrderId ?: $so->id;
                            }
                        } elseif ($tableName === 'suppliers' || $tableName === 'vendors' || $tableName === 'vendor') {
                            $grn = \App\Models\Master\GrnNumber::where('id', $orderId)
                                ->orWhere('unique_no', $orderId)
                                ->first();
                            if ($grn) {
                                $voucherId = $grn->id;
                                $voucherNo = $grn->unique_no;
                                $voucherType = 'grn';
                            }
                        }
                    }

                    if ($salesOrderId && empty($voucherId)) {
                        $so = \App\Models\Sales\SalesOrder::find($salesOrderId);
                        if ($so) {
                            $voucherId = $so->id;
                            $voucherNo = $so->reference_no ?? $so->unique_no;
                            $voucherType = 'sales_order';
                        }
                    }

                    JournalVoucherDetail::create([
                        'journal_voucher_id' => $journalVoucher->id,
                        'acc_id' => $detail['acc_id'],
                        'receipt_voucher_id' => $receiptVoucherId,
                        'customer_advance_id' => $customerAdvanceId,
                        'sales_order_id' => $salesOrderId,
                        'delivery_order_id' => $deliveryOrderId,
                        'reference_jv_id' => $referenceJvId,
                        'reference_jv_detail_id' => $referenceJvDetailId,
                        'voucher_id' => $voucherId,
                        'voucher_no' => $voucherNo,
                        'voucher_type' => $voucherType,
                        'debit_amount' => $debitAmount,
                        'credit_amount' => $creditAmount,
                        'description' => $detail['description'] ?? null,
                        'username' => $username,
                        'status' => 'active',
                        'timestamp' => now()
                    ]);

                }
            });
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'message' => $e->getMessage(),
                'errors' => ['receipt_voucher' => [$e->getMessage()]]
            ], 422);
        }

        return response()->json([
            'success' => 'Journal voucher updated successfully!',
            'redirect' => route('journal-voucher.index')
        ]);
    }

    /**
     * Approve journal voucher and create transactions
     */
    public function approve(Request $request, $id)
    {
        $journalVoucher = JournalVoucher::findOrFail($id);

        if (!$journalVoucher->canApprove()) {
            return response()->json([
                'error' => 'You are not authorized to approve this journal voucher or it cannot be approved.'
            ], 422);
        }

        if (method_exists($journalVoucher, 'validateBeforeApproval')) {
            $validationError = $journalVoucher->validateBeforeApproval();
            if ($validationError) {
                return response()->json([
                    'error' => $validationError,
                    'message' => $validationError
                ], 422);
            }
        }

        $result = $journalVoucher->approve($request->comments ?? 'Approved');
        if ($result) {
            return response()->json([
                'success' => 'Journal voucher approved successfully and transactions created!'
            ]);
        }

        return response()->json([
            'error' => 'An error occurred while approving the journal voucher.'
        ], 422);
    }

    /**
     * Reject journal voucher
     */
    public function reject(Request $request, $id)
    {
        $journalVoucher = JournalVoucher::findOrFail($id);

        if (!$journalVoucher->canApprove()) {
            return response()->json([
                'error' => 'You are not authorized to reject this journal voucher or it cannot be rejected.'
            ], 422);
        }

        $result = $journalVoucher->reject($request->comments ?? 'Rejected');
        if ($result) {
            return response()->json([
                'success' => 'Journal voucher rejected successfully!'
            ]);
        }

        return response()->json([
            'error' => 'An error occurred while rejecting the journal voucher.'
        ], 422);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $journalVoucher = JournalVoucher::findOrFail($id);

        // Prevent deleting approved or rejected vouchers
        $currentApprovalStatus = strtolower($journalVoucher->am_approval_status ?? 'pending');
        if (in_array($currentApprovalStatus, ['approved', 'rejected'])) {
            return response()->json([
                'error' => "Cannot delete a journal voucher that has been {$currentApprovalStatus}."
            ], 422);
        }

        // Revert any customer advance adjustments made by this voucher
        $prevAdjustments = \App\Models\CustomerAdvanceAdjustment::where('voucher_no', $journalVoucher->jv_no)->get();
        foreach ($prevAdjustments as $adj) {
            $adv = \App\Models\CustomerAdvance::find($adj->customer_advance_id);
            if ($adv) {
                $adv->used_amount -= $adj->amount;
                $adv->remaining_amount += $adj->amount;
                $adv->status = ($adv->used_amount <= 0.01) ? 'pending' : 'partial_payment';
                $adv->save();
            }
            $adj->delete();
        }

        // Revert any settlement adjustments made by this voucher
        DB::table('settlement_adjustments')->where('voucher_no', $journalVoucher->jv_no)->delete();



        $journalVoucher->update([
            'delete_user_id' => optional(Auth::user())->id
        ]);

        $journalVoucher->delete();

        return response()->json([
            'success' => 'Journal voucher deleted successfully!'
        ]);
    }
}