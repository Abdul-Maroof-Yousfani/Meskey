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
use App\Models\ReceiptVoucher;
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
        return view('management.finance.journal_voucher.index');
    }

    /**
     * Get list of journal vouchers.
     */
    public function getList(Request $request)
    {
        $journalVouchers = JournalVoucher::with(['journalVoucherDetails.account', 'createdBy'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $searchTerm = '%' . $request->search . '%';
                return $q->where(function ($sq) use ($searchTerm) {
                    $sq->where('jv_no', 'like', $searchTerm)
                        ->orWhere('description', 'like', $searchTerm);
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
        if (!$accountId) {
            return [
                'table_name' => null,
                'customer_id' => null,
                'customer_name' => null,
                'receipt_vouchers' => collect([]),
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
                    'sales_orders' => collect([]),
                    'grns' => collect([])
                ];
            }

            $doConsumedSum = DB::table('delivery_order_receipt_voucher as dorv')
                ->join('delivery_order as do_tbl', 'do_tbl.id', '=', 'dorv.delivery_order_id')
                ->where('do_tbl.am_approval_status', '!=', 'rejected')
                ->select('dorv.receipt_voucher_id', DB::raw('SUM(dorv.amount) as consumed_amount'))
                ->groupBy('dorv.receipt_voucher_id');

            $jvConsumedSum = DB::table('journal_voucher_details')
                ->join('journal_vouchers', 'journal_vouchers.id', '=', 'journal_voucher_details.journal_voucher_id')
                ->whereNull('journal_vouchers.deleted_at')
                ->whereNull('journal_voucher_details.deleted_at')
                ->when($excludeJvId, function ($q) use ($excludeJvId) {
                    $q->where('journal_vouchers.id', '!=', $excludeJvId);
                })
                ->select('receipt_voucher_id', DB::raw('SUM(debit_amount) as consumed_amount'))
                ->whereNotNull('receipt_voucher_id')
                ->groupBy('receipt_voucher_id');

            $receiptVouchers = ReceiptVoucher::leftJoinSub($doConsumedSum, 'do_consumption', function ($join) {
                    $join->on('receipt_vouchers.id', '=', 'do_consumption.receipt_voucher_id');
                })
                ->leftJoinSub($jvConsumedSum, 'jv_consumption', function ($join) {
                    $join->on('receipt_vouchers.id', '=', 'jv_consumption.receipt_voucher_id');
                })
                ->where('receipt_vouchers.customer_id', $customer->id)
                ->whereNull('receipt_vouchers.deleted_at')
                ->select('receipt_vouchers.*', 
                    DB::raw('COALESCE(do_consumption.consumed_amount, 0) as do_consumed'),
                    DB::raw('COALESCE(jv_consumption.consumed_amount, 0) as jv_consumed')
                )
                ->get()
                ->map(function($rv) {
                    // Use the SO-linked net_amount as the base (not total_amount which may include excess)
                    $soLinkedAmount = DB::table('receipt_voucher_items')
                        ->where('receipt_voucher_id', $rv->id)
                        ->whereIn('reference_type', ['sale_order', 'sales_invoice'])
                        ->sum('net_amount');

                    // If no items found, fall back to total_amount
                    $baseAmount = $soLinkedAmount > 0 ? $soLinkedAmount : (float) $rv->total_amount;

                    $remaining = round($baseAmount - ($rv->do_consumed + $rv->jv_consumed), 2);
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

            $salesOrders = SalesOrder::where('customer_id', $customer->id)
                ->select('id', 'reference_no')
                ->latest('id')
                ->get()
                ->map(function($so) {
                    return [
                        'id' => $so->id,
                        'reference_no' => $so->reference_no,
                        'unique_no' => $so->reference_no,
                        'text' => $so->reference_no,
                        'type' => 'sales_order'
                    ];
                });

            return [
                'table_name' => 'customers',
                'customer_id' => $customer->id,
                'customer_name' => $customer->name,
                'receipt_vouchers' => $receiptVouchers,
                'sales_orders' => $salesOrders,
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
                    'sales_orders' => collect([]),
                    'grns' => collect([])
                ];
            }

            // Direct query using supplier_id from grn_numbers table
            $data = GrnNumber::where('supplier_id', $supplier->id)
                ->whereNotNull('unique_no')
                // ->whereDoesntHave('paymentRequestData.paymentRequests.paymentVoucherData', function ($q) {
                //     $q->whereHas('paymentVoucher');
                // })
                ->latest('id')
                ->get()
                ->map(function ($grn) {
                    return [
                        'id' => $grn->id,
                        'unique_no' => $grn->unique_no,
                        'text' => $grn->unique_no,
                        'type' => 'grn'
                    ];
                });

            return [
                'table_name' => 'suppliers',
                'supplier_id' => $supplier->id,
                'supplier_name' => $supplier->name,
                'customer_id' => null,
                'customer_name' => null,
                'receipt_vouchers' => collect([]),
                'sales_orders' => collect([]),
                'grns' => $data
            ];
        }

        return [
            'table_name' => $tableName,
            'customer_id' => null,
            'customer_name' => null,
            'receipt_vouchers' => collect([]),
            'sales_orders' => collect([]),
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
     * Validate Journal Entries for account ownership and RV balance limits.
     */
    protected function validateJournalEntries(Request $request, $excludeJvId = null)
    {
        $rvTotalUsage = [];
        $seenRvIds = [];

        foreach ($request->details as $index => $detail) {
            $debitAmount = isset($detail['debit_amount']) ? (float) $detail['debit_amount'] : 0;
            $creditAmount = isset($detail['credit_amount']) ? (float) $detail['credit_amount'] : 0;
            $lineAmount = max($debitAmount, $creditAmount);

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

                // Check if Receiving is already created for this Receipt Voucher
                $existingJv = DB::table('journal_voucher_details')
                    ->join('journal_vouchers', 'journal_vouchers.id', '=', 'journal_voucher_details.journal_voucher_id')
                    ->where('journal_voucher_details.receipt_voucher_id', $rvId)
                    ->whereNull('journal_voucher_details.deleted_at')
                    ->whereNull('journal_vouchers.deleted_at')
                    ->where(function ($q) {
                        $q->whereNull('journal_vouchers.am_approval_status')
                          ->orWhere('journal_vouchers.am_approval_status', '!=', 'rejected');
                    })
                    ->where(function ($q) {
                        $q->whereNull('journal_vouchers.jv_status')
                          ->orWhere('journal_vouchers.jv_status', '!=', 'rejected');
                    })
                    ->when($excludeJvId, function ($q) use ($excludeJvId) {
                        $q->where('journal_vouchers.id', '!=', $excludeJvId);
                    })
                    ->select('journal_vouchers.id', 'journal_vouchers.jv_no')
                    ->first();

                if ($existingJv) {
                    return "Receiving has already been created for Receipt Voucher {$rv->unique_no} (in Journal Voucher {$existingJv->jv_no}).";
                }

                $rvTotalUsage[$rvId] = ($rvTotalUsage[$rvId] ?? 0) + $lineAmount;
            }

            if (!empty($detail['sales_order_id'])) {
                $soId = $detail['sales_order_id'];
                $so = SalesOrder::find($soId);
                if (!$so) {
                    return "Line " . ($index + 1) . ": Sales Order was not found.";
                }

                $customer = \App\Models\Master\Customer::where('account_id', $detail['acc_id'])->first();
                if (!$customer) {
                    $account = Account::find($detail['acc_id']);
                    if ($account && $account->table_name === 'customers') {
                        if ($account->model_id) $customer = \App\Models\Master\Customer::find($account->model_id);
                        if (!$customer) $customer = \App\Models\Master\Customer::where('name', $account->name)->first();
                    }
                }
                if (!$customer || $so->customer_id != $customer->id) {
                    return "Line " . ($index + 1) . ": Sales Order {$so->reference_no} does not belong to the selected account.";
                }
            }
        }

        foreach ($rvTotalUsage as $rvId => $totalEntered) {
            $rv = ReceiptVoucher::find($rvId);
            $doConsumed = DB::table('delivery_order_receipt_voucher as dorv')
                ->join('delivery_order as do_tbl', 'do_tbl.id', '=', 'dorv.delivery_order_id')
                ->where('do_tbl.am_approval_status', '!=', 'rejected')
                ->where('dorv.receipt_voucher_id', $rvId)
                ->sum('dorv.amount');

            $jvConsumed = DB::table('journal_voucher_details')
                ->join('journal_vouchers', 'journal_vouchers.id', '=', 'journal_voucher_details.journal_voucher_id')
                ->whereNull('journal_vouchers.deleted_at')
                ->whereNull('journal_voucher_details.deleted_at')
                ->where(function ($q) {
                    $q->whereNull('journal_vouchers.am_approval_status')
                      ->orWhere('journal_vouchers.am_approval_status', '!=', 'rejected');
                })
                ->where(function ($q) {
                    $q->whereNull('journal_vouchers.jv_status')
                      ->orWhere('journal_vouchers.jv_status', '!=', 'rejected');
                })
                ->when($excludeJvId, function ($q) use ($excludeJvId) {
                    $q->where('journal_vouchers.id', '!=', $excludeJvId);
                })
                ->where('receipt_voucher_id', $rvId)
                ->sum('debit_amount');

            // Use SO-linked net_amount as base (consistent with fetchAccountRelatedData)
            $soLinkedAmount = DB::table('receipt_voucher_items')
                ->where('receipt_voucher_id', $rvId)
                ->whereIn('reference_type', ['sale_order', 'sales_invoice'])
                ->sum('net_amount');
            $baseAmount = $soLinkedAmount > 0 ? $soLinkedAmount : (float) $rv->total_amount;

            $remainingAmount = round($baseAmount - ($doConsumed + $jvConsumed), 2);
            if (round($totalEntered, 2) > round($remainingAmount + 0.01, 2)) {
                return "The entered amount (" . number_format($totalEntered, 2) . ") for Receipt Voucher {$rv->unique_no} exceeds its remaining balance of " . number_format($remainingAmount, 2) . ".";
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
            'details.*.sales_order_id' => 'nullable|exists:sales_orders,id',
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
            DB::transaction(function () use ($request) {
                // Concurrency safety check: Ensure no other tab has created Receiving for any selected RV
                foreach ($request->details as $detail) {
                    if (!empty($detail['receipt_voucher_id'])) {
                        $rvId = $detail['receipt_voucher_id'];
                        $alreadyCreated = DB::table('journal_voucher_details')
                            ->join('journal_vouchers', 'journal_vouchers.id', '=', 'journal_voucher_details.journal_voucher_id')
                            ->where('journal_voucher_details.receipt_voucher_id', $rvId)
                            ->whereNull('journal_voucher_details.deleted_at')
                            ->whereNull('journal_vouchers.deleted_at')
                            ->where(function ($q) {
                                $q->whereNull('journal_vouchers.am_approval_status')
                                  ->orWhere('journal_vouchers.am_approval_status', '!=', 'rejected');
                            })
                            ->where(function ($q) {
                                $q->whereNull('journal_vouchers.jv_status')
                                  ->orWhere('journal_vouchers.jv_status', '!=', 'rejected');
                            })
                            ->lockForUpdate()
                            ->exists();

                        if ($alreadyCreated) {
                            $rv = ReceiptVoucher::find($rvId);
                            $rvNo = $rv ? $rv->unique_no : $rvId;
                            throw new \Exception("Receiving is already created for Receipt Voucher {$rvNo}.");
                        }
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
                    'jv_status' => 'pending',
                    'am_approval_status' => 'pending',
                    'am_change_made' => 1,
                    'created_by' => Auth::user()->id,
                    'approve_user_id' => null,
                    'company_id' => Auth::user()->current_company_id ?? null
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
                    $receiptVoucherId = $detail['receipt_voucher_id'] ?? null;
                    $orderId = $detail['order_id'] ?? null;

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
                        } elseif ($tableName === 'suppliers') {
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
                        'sales_order_id' => $salesOrderId,
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
            'journalVoucherDetails.salesOrder',
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
        $journalVoucher = JournalVoucher::with(['journalVoucherDetails.account'])->findOrFail($id);

        // Prevent editing approved or rejected vouchers
        $currentApprovalStatus = strtolower($journalVoucher->am_approval_status ?? $journalVoucher->jv_status ?? '');
        if (in_array($currentApprovalStatus, ['approved', 'rejected'])) {
            return redirect()->route('journal-voucher.index')
                ->with('error', "Cannot edit a journal voucher that has been {$currentApprovalStatus}.");
        }

        $accounts = Account::where("is_operational", "yes")->get();

        $rowRvs = [];
        $rowSos = [];
        $rowOrders = [];
        foreach ($journalVoucher->journalVoucherDetails as $index => $detail) {
            $related = $this->fetchAccountRelatedData($detail->acc_id, $id);
            $rowRvs[$index] = collect($related['receipt_vouchers']);
            $rowSos[$index] = collect($related['sales_orders']);
            $rowOrders[$index] = collect([]);

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
            } elseif ($tableName === 'suppliers') {
                $rowOrders[$index] = collect($related['grns']);
                $orderId = $detail->voucher_id;
                if ($orderId && !$rowOrders[$index]->contains('id', $orderId)) {
                    $currGrn = $detail->grn ?: \App\Models\Master\GrnNumber::find($orderId);
                    if (!$currGrn && $detail->voucher_no) {
                        $currGrn = \App\Models\Master\GrnNumber::where('unique_no', $detail->voucher_no)->first();
                    }
                    if ($currGrn) {
                        $rowOrders[$index]->prepend([
                            'id' => $currGrn->id,
                            'unique_no' => $currGrn->unique_no,
                            'text' => $currGrn->unique_no,
                            'type' => 'grn'
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
        }

        $data = [
            'journalVoucher' => $journalVoucher,
            'accounts' => $accounts,
            'rowRvs' => $rowRvs,
            'rowSos' => $rowSos,
            'rowOrders' => $rowOrders,
            'receiptVouchers' => collect([]),
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
        $currentApprovalStatus = strtolower($journalVoucher->am_approval_status ?? $journalVoucher->jv_status ?? '');
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
            'details.*.sales_order_id' => 'nullable|exists:sales_orders,id',
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
            DB::transaction(function () use ($request, $journalVoucher) {
                // Concurrency safety check
                foreach ($request->details as $detail) {
                    if (!empty($detail['receipt_voucher_id'])) {
                        $rvId = $detail['receipt_voucher_id'];
                        $alreadyCreated = DB::table('journal_voucher_details')
                            ->join('journal_vouchers', 'journal_vouchers.id', '=', 'journal_voucher_details.journal_voucher_id')
                            ->where('journal_voucher_details.receipt_voucher_id', $rvId)
                            ->where('journal_vouchers.id', '!=', $journalVoucher->id)
                            ->whereNull('journal_voucher_details.deleted_at')
                            ->whereNull('journal_vouchers.deleted_at')
                            ->where(function ($q) {
                                $q->whereNull('journal_vouchers.am_approval_status')
                                  ->orWhere('journal_vouchers.am_approval_status', '!=', 'rejected');
                            })
                            ->where(function ($q) {
                                $q->whereNull('journal_vouchers.jv_status')
                                  ->orWhere('journal_vouchers.jv_status', '!=', 'rejected');
                            })
                            ->lockForUpdate()
                            ->exists();

                        if ($alreadyCreated) {
                            $rv = ReceiptVoucher::find($rvId);
                            $rvNo = $rv ? $rv->unique_no : $rvId;
                            throw new \Exception("Receiving is already created for Receipt Voucher {$rvNo}.");
                        }
                    }
                }

                $username = Auth::user()->name ?? Auth::user()->email ?? 'System';

                $journalVoucher->update([
                    'jv_date' => $request->jv_date,
                    'description' => $request->description,
                    'username' => $username,
                    'status' => 'active',
                    'jv_status' => 'pending',
                    'am_approval_status' => 'pending',
                    'am_change_made' => 1,
                    'company_id' => Auth::user()->current_company_id ?? $journalVoucher->company_id
                ]);

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
                    $receiptVoucherId = $detail['receipt_voucher_id'] ?? null;
                    $orderId = $detail['order_id'] ?? null;

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
                        } elseif ($tableName === 'suppliers') {
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
                        'sales_order_id' => $salesOrderId,
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
        $currentApprovalStatus = strtolower($journalVoucher->am_approval_status ?? $journalVoucher->jv_status ?? '');
        if (in_array($currentApprovalStatus, ['approved', 'rejected'])) {
            return response()->json([
                'error' => "Cannot delete a journal voucher that has been {$currentApprovalStatus}."
            ], 422);
        }

        $journalVoucher->update([
            'delete_user_id' => optional(Auth::user())->id
        ]);

        $journalVoucher->delete();

        return response()->json([
            'success' => 'Journal voucher deleted successfully!'
        ]);
    }
}