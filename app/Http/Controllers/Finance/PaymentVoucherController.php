<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\ArrivalPurchaseOrder;
use App\Models\BillPaymentVoucherData;
use App\Models\BrokerCompanyBankDetail;
use App\Models\BrokerOwnerBankDetail;
use App\Models\Master\Account\Account;
use App\Models\Master\Account\Transaction;
use App\Models\Master\Broker;
use App\Models\Master\Supplier;
use App\Models\Master\Tax;
use App\Models\Master\Transporter;
use App\Models\Master\Vendor;
use App\Models\JournalVoucher;
use App\Models\JournalVoucherDetail;
use App\Models\SettlementAdjustment;
use App\Models\PaymentVoucher;
use App\Models\PaymentVoucherData;
use App\Models\Procurement\Store\PurchaseBill;
use App\Models\Procurement\PaymentRequest;
use App\Models\Procurement\PaymentRequestData;
use App\Models\SupplierCompanyBankDetail;
use App\Models\SupplierOwnerBankDetail;
use App\Models\TransporterCompanyBankDetail;
use App\Models\TransporterOwnerBankDetail;
use App\Models\VendorCompanyBankDetail;
use App\Models\VendorOwnerBankDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PaymentVoucherController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('management.finance.payment_voucher.index');
    }

    public function billPaymentVoucher()
    {
        $accounts = Account::where("hierarchy_path", "like", "2-2%")->get();
        $bank_accounts = Account::where("hierarchy_path", "like", "1-1%")
            ->whereNot("hierarchy_path", "1-1")
            ->get();

        $suppliers = Supplier::where("status", "active")->get();


        return view('management.finance.payment_voucher.billPaymentVoucher', compact('accounts', 'bank_accounts', 'suppliers'));
    }
    public function editPaymentVoucher(int $id)
    {
        $payment_voucher = PaymentVoucher::find($id);

        if ($payment_voucher->module_type != 'bill_payment_voucher') {
            return back();
        }

        $accounts = Account::where("hierarchy_path", "like", "2-2%")->get();
        $bank_accounts = Account::where("hierarchy_path", "like", "1-1%")
            ->whereNot("hierarchy_path", "1-1")
            ->get();

        $suppliers = Supplier::where("status", "active")->get();



        $payment_voucher_data = BillPaymentVoucherData::where("payment_voucher_id", $payment_voucher->id)->get();
        return view("management.finance.payment_voucher.editBillPayment", compact("payment_voucher", "payment_voucher_data", "accounts", "bank_accounts", "suppliers"));
    }

    /**
     * Get list of payment vouchers.
     */
    public function getList(Request $request)
    {
        $paymentVouchers = PaymentVoucher::with(['account'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $searchTerm = '%' . $request->search . '%';

                return $q->where(function ($sq) use ($searchTerm) {
                    $sq->where('unique_no', 'like', $searchTerm)
                        ->orWhere('ref_bill_no', 'like', $searchTerm)
                        ->orWhere('cheque_no', 'like', $searchTerm);
                });
            })
            ->latest()
            ->paginate(request('per_page', 25));

        return view('management.finance.payment_voucher.getList', compact('paymentVouchers'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // $data['accounts'] = Account::where('is_operational', 'yes')->get();

        $data['suppliers'] = Supplier::whereHas('arrivalPurchaseOrders', function ($query) {
            $query->whereHas('paymentRequestData.paymentRequests', function ($q) {
                $q->where('status', 'approved')
                    ->whereDoesntHave('paymentVoucherData');
            });
        })->latest()->get();

        $data['accounts'] = Account::whereHas('parent', function ($query) {
            $query->where('name', 'Liabilities');
        })->where('is_operational', 'no')->get()->pluck('name');

        $data['accounts'] = Account::where('is_operational', 'yes')
            ->where('status', 'active')
            ->whereHas('parent', function ($q) {
                $q->where('hierarchy_path', '2')
                    ->orWhereHas('parent', function ($q2) {
                        $q2->where('hierarchy_path', '2')
                            ->orWhereHas('parent', function ($q3) {
                                $q3->where('hierarchy_path', '2');
                            });
                    });
            })
            ->get();

        return view('management.finance.payment_voucher.create', $data);
    }

    public function directPaymentVoucher()
    {
        $accounts = Account::all();
        $taxes = Tax::select('id', 'name', 'percentage')->where('status', 'active')->get();

        return view('management.finance.payment_voucher.directPaymentVoucher', compact('taxes', 'accounts'));
    }

    public function direct_payment_voucher_store(Request $request)
    {
        $request->validate([
            'voucher_type' => 'required|in:bank_payment_voucher,cash_payment_voucher',
            'pv_date' => 'required|date',
            'account_id' => 'required|exists:accounts,id',
            'ref_bill_no' => 'nullable|string',
            'bill_date' => 'nullable|date',
            'cheque_no' => 'nullable|required_if:voucher_type,bank_payment_voucher|string|max:255',
            'cheque_date' => 'nullable|required_if:voucher_type,bank_payment_voucher|date',

            'account.*' => 'required|exists:accounts,id',
            'amount.*' => 'required|numeric|min:0.01',
            'tax_id.*' => 'nullable|exists:taxes,id',
            'description.*' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $prefix = $request->voucher_type === 'bank_payment_voucher' ? 'BPV' : 'CPV';
            $datePrefix = $prefix . '-' . date('m-d-Y', strtotime($request->pv_date)) . '-';
            $uniqueNo = generateUniqueNumberByDate('payment_vouchers', $datePrefix, null, 'unique_no', false);

            $paymentVoucher = PaymentVoucher::create([
                'unique_no' => $uniqueNo,
                'pv_date' => $request->pv_date,
                'voucher_type' => $request->voucher_type,
                'account_id' => $request->account_id, // Cash/Bank account (from where payment is made)
                'ref_bill_no' => $request->ref_bill_no,
                'bill_date' => $request->bill_date,
                'cheque_no' => $request->cheque_no,
                'cheque_date' => $request->cheque_date,
                'remarks' => $request->remarks ?? null,
                'total_amount' => 0,
                'is_direct' => 1, // Flag to identify direct vouchers
            ]);

            $totalAmount = 0;

            foreach ($request->account as $index => $accountId) {
                $amount = $request->amount[$index];
                $taxId = $request->tax_id[$index] ?? null;
                $taxAmount = $request->tax_amount[$index] ?? 0;
                $netAmount = $request->net_amount[$index] ?? $amount + $taxAmount;
                $desc = $request->description[$index] ?? '';

                // Create line item (similar to PaymentVoucherData)
                PaymentVoucherData::create([
                    'payment_voucher_id' => $paymentVoucher->id,
                    'account_id' => $accountId,
                    'amount' => $amount,
                    'tax_id' => $taxId,
                    'tax_amount' => $taxAmount,
                    'net_amount' => $netAmount,
                    'description' => $desc,
                    'reference_type' => 'direct',
                    'reference_id' => null,
                ]);

                // Credit the supplier/expense account
                createTransaction(
                    $netAmount,
                    $accountId,
                    1, // adjust if you have different transaction type for payments
                    $uniqueNo,
                    'debit',
                    'no',
                    [
                        'purpose' => "$prefix-{$paymentVoucher->id}",
                        'counter_account_id' => $request->account_id,
                        'remarks' => "Direct payment - {$desc}",
                    ]
                );

                $totalAmount += $netAmount;
            }

            // Debit the cash/bank account
            createTransaction(
                $totalAmount,
                $request->account_id,
                1,
                $uniqueNo,
                'credit',
                'no',
                [
                    'purpose' => "$prefix-{$paymentVoucher->id}",
                    'remarks' => 'Direct payment voucher',
                ]
            );

            $paymentVoucher->update(['total_amount' => $totalAmount]);

            DB::commit();

            return response()->json([
                'success' => 'Direct Payment Voucher created successfully!',
                'redirect' => route('payment-voucher.index'),
            ]);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function edit_direct(PaymentVoucher $payment_voucher)
    {
        if (!$payment_voucher->is_direct) {
            abort(404);
        }

        $accounts = Account::all();
        $taxes = Tax::select('id', 'name', 'percentage')->where('status', 'active')->get();

        // Load line items
        $items = $payment_voucher->paymentVoucherData; // assuming relation name

        return view("management.finance.payment_voucher.editDirectPaymentVoucher", compact("payment_voucher", "accounts", "taxes", "items"));
    }

    public function update_direct(Request $request, PaymentVoucher $payment_voucher)
    {
        if (!$payment_voucher->is_direct) {
            abort(404);
        }

        $request->validate([
            'voucher_type' => 'required|in:bank_payment_voucher,cash_payment_voucher',
            'pv_date' => 'required|date',
            'account_id' => 'required|exists:accounts,id',
            'ref_bill_no' => 'nullable|string',
            'bill_date' => 'nullable|date',
            'cheque_no' => 'nullable|required_if:voucher_type,bank_payment_voucher|string|max:255',
            'cheque_date' => 'nullable|required_if:voucher_type,bank_payment_voucher|date',

            'account.*' => 'required|exists:accounts,id',
            'amount.*' => 'required|numeric|min:0.01',
            'tax_id.*' => 'nullable|exists:taxes,id',
            'description.*' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $payment_voucher->update([
                'pv_date' => $request->pv_date,
                'voucher_type' => $request->voucher_type,
                'account_id' => $request->account_id,
                'ref_bill_no' => $request->ref_bill_no,
                'bill_date' => $request->bill_date,
                'cheque_no' => $request->cheque_no,
                'cheque_date' => $request->cheque_date,
                'remarks' => $request->remarks ?? null,
            ]);

            // Delete old items
            $payment_voucher->paymentVoucherData()->delete();

            $totalAmount = 0;

            foreach ($request->account as $index => $accountId) {
                $amount = $request->amount[$index];
                $taxId = $request->tax_id[$index] ?? null;
                $taxAmount = $request->tax_amount[$index] ?? 0;
                $netAmount = $request->net_amount[$index] ?? $amount + $taxAmount;
                $desc = $request->description[$index] ?? '';

                PaymentVoucherData::create([
                    'payment_voucher_id' => $payment_voucher->id,
                    'account_id' => $accountId,
                    'amount' => $amount,
                    'tax_id' => $taxId,
                    'tax_amount' => $taxAmount,
                    'net_amount' => $netAmount,
                    'description' => $desc,
                    'reference_type' => 'direct',
                    'reference_id' => null,
                ]);

                $totalAmount += $netAmount;
            }

            $payment_voucher->update(['total_amount' => $totalAmount]);

            // Note: Transactions are not reversed/updated here for simplicity.
            // In production, you may want to reverse old transactions and create new ones.

            DB::commit();

            return response()->json([
                'success' => 'Direct Payment Voucher updated successfully!',
                'redirect' => route('payment-voucher.index')
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Show the specified payment voucher.
     */
    public function show($id)
    {
        $paymentVoucher = PaymentVoucher::with([
            'paymentVoucherData',
            'paymentVoucherData.paymentRequest.paymentRequestData.purchaseOrder',
            'account',
            'supplier',
            'requestAccount',
        ])->findOrFail($id);

        $transactions = Transaction::where('transaction_voucher_type_id', 1)->where('voucher_no', $paymentVoucher->unique_no)
            ->get();

        $table_name = $paymentVoucher->requestAccount->table_name ?? null;
        $bankAccount = null;
        if ($table_name == 'suppliers') {
            if ($paymentVoucher->bank_account_type === 'company') {
                $bankAccount = SupplierCompanyBankDetail::find($paymentVoucher->bank_account_id);
            } elseif ($paymentVoucher->bank_account_type === 'owner') {
                $bankAccount = SupplierOwnerBankDetail::find($paymentVoucher->bank_account_id);
            }
        } elseif ($table_name == 'brokers') {
            if ($paymentVoucher->bank_account_type === 'company') {
                $bankAccount = BrokerCompanyBankDetail::find($paymentVoucher->bank_account_id);
            } elseif ($paymentVoucher->bank_account_type === 'owner') {
                $bankAccount = BrokerOwnerBankDetail::find($paymentVoucher->bank_account_id);
            }
        } elseif ($table_name == 'vendors') {
            if ($paymentVoucher->bank_account_type === 'company') {
                $bankAccount = VendorCompanyBankDetail::find($paymentVoucher->bank_account_id);
            } elseif ($paymentVoucher->bank_account_type === 'owner') {
                $bankAccount = VendorOwnerBankDetail::find($paymentVoucher->bank_account_id);
            }
        } elseif ($table_name == 'transporters') {
            if ($paymentVoucher->bank_account_type === 'company') {
                $bankAccount = TransporterCompanyBankDetail::find($paymentVoucher->bank_account_id);
            } elseif ($paymentVoucher->bank_account_type === 'owner') {
                $bankAccount = TransporterOwnerBankDetail::find($paymentVoucher->bank_account_id);
            }
        } else {
            $bankAccount = null;
        }

        return view('management.finance.payment_voucher.show', [
            'paymentVoucher' => $paymentVoucher,
            'transactions' => $transactions,
            'bankAccount' => $bankAccount,
        ]);
    }

    public function manageApprovals($id)
    {
        $paymentVoucher = PaymentVoucher::with([
            'paymentVoucherData',
            'paymentVoucherData.paymentRequest.paymentRequestData.purchaseOrder',
            'account',
            'supplier',
        ])->findOrFail($id);

        $transactions = Transaction::where('transaction_voucher_type_id', 1)->where('voucher_no', $paymentVoucher->unique_no)
            ->get();

        $table_name = $paymentVoucher->requestAccount->table_name ?? null;
        $bankAccount = null;
        if ($table_name == 'suppliers') {
            if ($paymentVoucher->bank_account_type === 'company') {
                $bankAccount = SupplierCompanyBankDetail::find($paymentVoucher->bank_account_id);
            } elseif ($paymentVoucher->bank_account_type === 'owner') {
                $bankAccount = SupplierOwnerBankDetail::find($paymentVoucher->bank_account_id);
            }
        } elseif ($table_name == 'brokers') {
            if ($paymentVoucher->bank_account_type === 'company') {
                $bankAccount = BrokerCompanyBankDetail::find($paymentVoucher->bank_account_id);
            } elseif ($paymentVoucher->bank_account_type === 'owner') {
                $bankAccount = BrokerOwnerBankDetail::find($paymentVoucher->bank_account_id);
            }
        } elseif ($table_name == 'vendors') {
            if ($paymentVoucher->bank_account_type === 'company') {
                $bankAccount = VendorCompanyBankDetail::find($paymentVoucher->bank_account_id);
            } elseif ($paymentVoucher->bank_account_type === 'owner') {
                $bankAccount = VendorOwnerBankDetail::find($paymentVoucher->bank_account_id);
            }
        } elseif ($table_name == 'transporters') {
            if ($paymentVoucher->bank_account_type === 'company') {
                $bankAccount = TransporterCompanyBankDetail::find($paymentVoucher->bank_account_id);
            } elseif ($paymentVoucher->bank_account_type === 'owner') {
                $bankAccount = TransporterOwnerBankDetail::find($paymentVoucher->bank_account_id);
            }
        } else {
            $bankAccount = null;
        }

        return view('management.finance.payment_voucher.approvalCanvas', [
            'paymentVoucher' => $paymentVoucher,
            'data' => $paymentVoucher,
            'transactions' => $transactions,
            'bankAccount' => $bankAccount,
        ]);
    }

    /**
     * Generate PV number
     */
    public function generatePvNumber(Request $request)
    {
        $request->validate([
            'voucher_type' => 'required|in:bank_payment_voucher,cash_payment_voucher',
            'pv_date' => 'nullable|date',
        ]);

        $prefix = $request->voucher_type === 'bank_payment_voucher' ? 'BPV' : 'CPV';
        $prefixForAccounts = $request->voucher_type === 'bank_payment_voucher' ? '1-1' : '1-4';

        $accounts = Account::whereHas('parent', function ($query) use ($prefixForAccounts) {
            $query->where('hierarchy_path', $prefixForAccounts);
        })->get();

        $pvDate = $request->pv_date ? date('m-d-Y', strtotime($request->pv_date)) : date('m-d-Y');
        $datePrefix = $prefix . '-' . $pvDate . '-';
        $uniqueNo = generateUniqueNumberByDate('payment_vouchers', $datePrefix, null, 'unique_no', false);

        return response()->json([
            'success' => true,
            'pv_number' => $uniqueNo,
            'accounts' => $accounts,
        ]);
    }

    public function getPurchaseBills(string $supplierId)
    {
        $purchase_bill = PurchaseBill::with("grn", "bill_data")
            ->where("supplier_id", $supplierId)
            ->where("am_approval_status", "approved")
            ->get();

        $purchase_bill->each(function ($bill) {
            $remaining_qty = getPaymentVoucherBillBalance($bill);
            $bill->amount = $remaining_qty;
            $bill->debit_amount = getDebitNoteAmountOfBill($bill);
            $bill->return_amount = getPurchaseReturnAmountOfBill($bill);
            $bill->total_bill = totalBill($bill);
            $bill->spent_bill = spentBill($bill);
        });


        return view('management.finance.payment_voucher.purchaseBills', [
            'purchase_bills' => $purchase_bill,
        ]);
    }

    /**
     * Get payment requests for purchase order
     */
    public function getPaymentRequests($supplierId)
    {
        $supplier = Supplier::with(['companyBankDetails', 'ownerBankDetails'])->findOrFail($supplierId);

        $companyBankAccounts = $supplier->companyBankDetails ?? collect();
        $ownerBankAccounts = $supplier->ownerBankDetails ?? collect();

        $bankAccounts = collect();

        if ($companyBankAccounts) {
            foreach ($companyBankAccounts as $bank) {
                $bankAccounts->push([
                    'id' => $bank->id,
                    'type' => 'company',
                    'title' => $bank->supplier->name ?? '',
                    'account_title' => $bank->account_title ?? '',
                    'account_number' => $bank->account_number ?? '',
                    'bank_name' => $bank->bank_name ?? '',
                    'branch_name' => $bank->branch_name ?? '',
                    'branch_code' => $bank->branch_code ?? '',
                ]);
            }
        }

        if ($ownerBankAccounts) {
            foreach ($ownerBankAccounts as $bank) {
                $bankAccounts->push([
                    'id' => $bank->id,
                    'type' => 'owner',
                    'title' => $bank->supplier->name ?? '',
                    'account_title' => $bank->account_title ?? '',
                    'account_number' => $bank->account_number ?? '',
                    'bank_name' => $bank->bank_name ?? '',
                    'branch_name' => $bank->branch_name ?? '',
                    'branch_code' => $bank->branch_code ?? '',
                ]);
            }
        }
        $paymentRequests = PaymentRequest::with(['paymentRequestData', 'approvals'])
            ->whereHas('paymentRequestData.purchaseOrder', function ($q) use ($supplierId) {
                $q->where('supplier_id', $supplierId);
            })
            ->whereDoesntHave('paymentVoucherData')
            ->where('status', 'approved')
            ->get()
            ->map(function ($request) {
                return [
                    'id' => $request->id,
                    'supplier_id' => $request->paymentRequestData->purchaseOrder->supplier_id ?? '',
                    'purchaseOrder' => $request->paymentRequestData->purchaseOrder,
                    'truck_no' => $request->paymentRequestData->truck_no ?? '-',
                    'bilty_no' => $request->paymentRequestData->bilty_no ?? '-',
                    'loading_date' => $request->paymentRequestData && $request->paymentRequestData->loading_date
                        ? $request->paymentRequestData->loading_date->format('Y-m-d')
                        : '-',
                    'no_of_bags' => $request->paymentRequestData->no_of_bags,
                    'loading_weight' => $request->paymentRequestData->loading_weight,
                    'module_type' => $request->paymentRequestData->module_type,
                    'contract_no' => $request->paymentRequestData->purchaseOrder->contract_no ?? 'N/A',
                    'amount' => $request->amount,
                    'purpose' => $request->paymentRequestData->notes ?? 'No description',
                    'status' => $request->approval_status,
                    'saudaType' => $request->paymentRequestData->purchaseOrder->saudaType->name ?? '',
                    'type' => ($request->request_type),
                    'request_date' => $request->created_at
                        ? $request->created_at->format('Y-m-d')
                        : '',
                ];
            });

        return response()->json([
            'success' => true,
            'payment_requests' => $paymentRequests,
            'bank_accounts' => $bankAccounts->values(),
        ]);
    }

    /**
     * Attach GRN number and Journal Voucher (JV) adjustments to payment request data
     */
    private function attachGrnAndJvDetails(array $item, PaymentRequest $request, array &$jvPool = []): array
    {
        $grnNo = $request->paymentRequestData?->grn_no ?? null;
        $grnId = $request->paymentRequestData?->grn_id ?? null;

        $jvAdjustmentAmount = 0;
        $jvId = null;
        $jvNo = null;
        $matchingJvs = [];
        $grossAmount = (float) ($item['amount'] ?? $request->amount ?? 0);
        $needed = $grossAmount;

        if (!empty($grnNo)) {
            $details = JournalVoucherDetail::with('journalVoucher')
                ->where(function ($q) use ($grnNo, $grnId) {
                    $q->where('voucher_no', $grnNo)
                        ->orWhere('voucher_id', $grnNo);
                    if ($grnId) {
                        $q->orWhere('voucher_id', $grnId);
                    }
                })
                ->whereNull('deleted_at')
                ->whereHas('journalVoucher', function ($q) {
                    $q->whereNull('deleted_at')
                        ->where('am_approval_status', 'approved');
                })
                ->orderBy('journal_voucher_id', 'asc')
                ->orderBy('id', 'asc')
                ->get();

            $processedJvs = [];
            foreach ($details as $detail) {
                if ($needed <= 0) {
                    break;
                }

                $jv = $detail->journalVoucher;
                if (!$jv || isset($processedJvs[$jv->id])) {
                    continue;
                }
                $processedJvs[$jv->id] = true;

                if (!isset($jvPool[$jv->id])) {
                    $jvLineTotal = (float) JournalVoucherDetail::where('journal_voucher_id', $jv->id)
                        ->where(function ($q) use ($grnNo, $grnId) {
                            $q->where('voucher_no', $grnNo)
                                ->orWhere('voucher_id', $grnNo);
                            if ($grnId) {
                                $q->orWhere('voucher_id', $grnId);
                            }
                        })
                        ->whereNull('deleted_at')
                        ->sum(DB::raw('CASE WHEN debit_amount > 0 THEN debit_amount ELSE credit_amount END'));

                    $spent = (float) DB::table('settlement_adjustments')
                        ->where('reference_type', 'journal_voucher')
                        ->where('reference_id', $jv->id)
                        ->sum('amount');

                    $jvPool[$jv->id] = max(0, $jvLineTotal - $spent);
                }

                $available = $jvPool[$jv->id];

                if ($available > 0) {
                    $allocated = min($needed, $available);
                    if ($allocated > 0) {
                        $matchingJvs[] = [
                            'jv_id' => $jv->id,
                            'jv_no' => $jv->jv_no,
                            'allocated' => $allocated,
                        ];
                        $jvAdjustmentAmount += $allocated;
                        $jvPool[$jv->id] -= $allocated;
                        $needed -= $allocated;
                    }
                }
            }

            if (!empty($matchingJvs)) {
                $jvId = $matchingJvs[0]['jv_id'];
                $jvNo = implode(', ', array_unique(array_column($matchingJvs, 'jv_no')));
            }
        }

        $netAmount = max(0, $grossAmount - $jvAdjustmentAmount);

        $item['grn_no'] = $grnNo;
        $item['jv_id'] = $jvId;
        $item['jv_no'] = $jvNo;
        $item['jv_adjustment_amount'] = round($jvAdjustmentAmount, 2);
        $item['net_amount'] = round($netAmount, 2);
        $item['matching_jvs'] = $matchingJvs;

        return $item;
    }

    /**
     * Get payment requests for account
     */
    public function getAccountPaymentRequests($accountId)
    {
        $account = Account::findOrFail($accountId);

        $tableName = $account->table_name;

        $bankAccounts = collect();
        $paymentRequests = collect();
        $modelId = null;
        $jvPool = [];

        if ($tableName === 'suppliers') {

            $supplier = Supplier::with(['companyBankDetails', 'ownerBankDetails'])
                ->where('account_id', $account->id)
                ->first();

            if ($supplier) {
                $modelId = $supplier->id;
                $companyBankAccounts = $supplier->companyBankDetails ?? collect();
                $ownerBankAccounts = $supplier->ownerBankDetails ?? collect();

                if ($companyBankAccounts) {
                    foreach ($companyBankAccounts as $bank) {
                        $bankAccounts->push([
                            'id' => $bank->id,
                            'type' => 'company',
                            'title' => $bank->supplier->name ?? '',
                            'account_title' => $bank->account_title ?? '',
                            'account_number' => $bank->account_number ?? '',
                            'bank_name' => $bank->bank_name ?? '',
                            'branch_name' => $bank->branch_name ?? '',
                            'branch_code' => $bank->branch_code ?? '',
                        ]);
                    }
                }

                if ($ownerBankAccounts) {
                    foreach ($ownerBankAccounts as $bank) {
                        $bankAccounts->push([
                            'id' => $bank->id,
                            'type' => 'owner',
                            'title' => $bank->supplier->name ?? '',
                            'account_title' => $bank->account_title ?? '',
                            'account_number' => $bank->account_number ?? '',
                            'bank_name' => $bank->bank_name ?? '',
                            'branch_name' => $bank->branch_name ?? '',
                            'branch_code' => $bank->branch_code ?? '',
                        ]);
                    }
                }

                $paymentRequests = PaymentRequest::with(['paymentRequestData', 'approvals'])
                    ->where('account_id', $accountId)
                    ->whereDoesntHave('paymentVoucherData')
                    ->where('status', 'approved')
                    ->orderBy('created_at', 'asc')
                    ->orderBy('id', 'asc')
                    ->get()
                    ->map(function ($request) use (&$jvPool) {
                        return $this->attachGrnAndJvDetails([
                            'id' => $request->id,
                            'supplier_id' => $request->paymentRequestData->purchaseOrder->supplier_id ?? '',
                            'purchaseOrder' => $request->paymentRequestData->purchaseOrder,
                            'truck_no' => $request->paymentRequestData->truck_no ?? '-',
                            'bilty_no' => $request->paymentRequestData->bilty_no ?? '-',
                            'loading_date' => $request->paymentRequestData && $request->paymentRequestData->loading_date
                                ? $request->paymentRequestData->loading_date->format('Y-m-d')
                                : '-',
                            'no_of_bags' => $request->paymentRequestData->no_of_bags,
                            'loading_weight' => $request->paymentRequestData->loading_weight,
                            'module_type' => $request->delivery_challan_id ? 'sale_order' : ($request->paymentRequestData->module_type ?? 'purchase_order'),
                            'contract_no' => $request->deliveryChallan ? $request->deliveryChallan->dc_no : ($request->paymentRequestData->purchaseOrder->contract_no ?? 'N/A'),
                            'amount' => $request->amount,
                            'purpose' => $request->paymentRequestData->notes ?? 'No description',
                            'status' => $request->approval_status,
                            'saudaType' => $request->paymentRequestData->purchaseOrder->saudaType->name ?? '',
                            'type' => ($request->request_type ?? null),
                            'file' => ($request->paymentRequestData->attachment ?? null),
                            'request_date' => $request->created_at
                                ? $request->created_at->format('Y-m-d')
                                : '',
                        ], $request, $jvPool);
                    });

            }
        } elseif ($tableName === 'brokers') {
            $broker = Broker::with(['companyBankDetails', 'ownerBankDetails'])
                ->where('account_id', $account->id)
                ->first();

            if ($broker) {
                $modelId = $broker->id;
                $companyBankAccounts = $broker->companyBankDetails ?? collect();
                $ownerBankAccounts = $broker->ownerBankDetails ?? collect();

                if ($companyBankAccounts) {
                    foreach ($companyBankAccounts as $bank) {
                        $bankAccounts->push([
                            'id' => $bank->id,
                            'type' => 'company',
                            'title' => $bank->broker->name ?? '',
                            'account_title' => $bank->account_title ?? '',
                            'account_number' => $bank->account_number ?? '',
                            'bank_name' => $bank->bank_name ?? '',
                            'branch_name' => $bank->branch_name ?? '',
                            'branch_code' => $bank->branch_code ?? '',
                        ]);
                    }
                }

                if ($ownerBankAccounts) {
                    foreach ($ownerBankAccounts as $bank) {
                        $bankAccounts->push([
                            'id' => $bank->id,
                            'type' => 'owner',
                            'title' => $bank->broker->name ?? '',
                            'account_title' => $bank->account_title ?? '',
                            'account_number' => $bank->account_number ?? '',
                            'bank_name' => $bank->bank_name ?? '',
                            'branch_name' => $bank->branch_name ?? '',
                            'branch_code' => $bank->branch_code ?? '',
                        ]);
                    }
                }

                $paymentRequests = PaymentRequest::with(['paymentRequestData', 'approvals', 'deliveryChallan.delivery_challan_data'])
                    ->where('account_id', $accountId)
                    ->whereDoesntHave('paymentVoucherData')
                    ->where('status', 'approved')
                    ->orderBy('created_at', 'asc')
                    ->orderBy('id', 'asc')
                    ->get()
                    ->map(function ($request) use (&$jvPool) {
                        $dc = $request->deliveryChallan;
                        $po = $request->paymentRequestData?->purchaseOrder;
                        $firstDcData = $dc?->delivery_challan_data?->first();

                        return $this->attachGrnAndJvDetails([
                            'id' => $request->id,
                            'supplier_id' => $po->supplier_id ?? '',
                            'purchaseOrder' => $po,
                            'truck_no' => $request->paymentRequestData?->truck_no ?? $firstDcData?->truck_no ?? '-',
                            'bilty_no' => $request->paymentRequestData?->bilty_no ?? $firstDcData?->bilty_no ?? '-',
                            'loading_date' => $request->paymentRequestData && $request->paymentRequestData->loading_date
                                ? $request->paymentRequestData->loading_date->format('Y-m-d')
                                : ($dc?->dispatch_date ? date('Y-m-d', strtotime($dc->dispatch_date)) : '-'),
                            'no_of_bags' => $request->paymentRequestData?->no_of_bags ?? ($dc ? $dc->delivery_challan_data?->sum('no_of_bags') : ''),
                            'loading_weight' => $request->paymentRequestData?->loading_weight ?? ($dc ? $dc->delivery_challan_data?->sum('qty') : ''),
                            'module_type' => $dc ? 'sale_order' : ($request->paymentRequestData?->module_type ?? 'purchase_order'),
                            'contract_no' => $dc ? $dc->dc_no : ($po->contract_no ?? 'N/A'),
                            'amount' => $request->amount,
                            'purpose' => $request->paymentRequestData?->notes ?? ($dc ? "Broker commission for DC: {$dc->dc_no}" : 'No description'),
                            'status' => $request->approval_status,
                            'saudaType' => $dc ? ucfirst($dc->sauda_type ?? '') : ($po?->saudaType?->name ?? ''),
                            'type' => ($request->request_type),
                            'file' => ($request->paymentRequestData?->attachment ?? null),
                            'request_date' => $request->created_at
                                ? $request->created_at->format('Y-m-d')
                                : '',
                        ], $request, $jvPool);
                    });
            }
        } elseif ($tableName === 'vendors') {
            $broker = Vendor::with(['companyBankDetails', 'ownerBankDetails'])
                ->where('account_id', $account->id)
                ->first();

            if ($broker) {
                $modelId = $broker->id;
                $companyBankAccounts = $broker->companyBankDetails ?? collect();
                $ownerBankAccounts = $broker->ownerBankDetails ?? collect();

                if ($companyBankAccounts) {
                    foreach ($companyBankAccounts as $bank) {
                        $bankAccounts->push([
                            'id' => $bank->id,
                            'type' => 'company',
                            'title' => $bank->broker->name ?? '',
                            'account_title' => $bank->account_title ?? '',
                            'account_number' => $bank->account_number ?? '',
                            'bank_name' => $bank->bank_name ?? '',
                            'branch_name' => $bank->branch_name ?? '',
                            'branch_code' => $bank->branch_code ?? '',
                        ]);
                    }
                }

                if ($ownerBankAccounts) {
                    foreach ($ownerBankAccounts as $bank) {
                        $bankAccounts->push([
                            'id' => $bank->id,
                            'type' => 'owner',
                            'title' => $bank->broker->name ?? '',
                            'account_title' => $bank->account_title ?? '',
                            'account_number' => $bank->account_number ?? '',
                            'bank_name' => $bank->bank_name ?? '',
                            'branch_name' => $bank->branch_name ?? '',
                            'branch_code' => $bank->branch_code ?? '',
                        ]);
                    }
                }

                $paymentRequests = PaymentRequest::with(['paymentRequestData', 'approvals', 'deliveryChallan.delivery_challan_data'])
                    ->where('account_id', $accountId)
                    ->whereDoesntHave('paymentVoucherData')
                    ->where('status', 'approved')
                    ->orderBy('created_at', 'asc')
                    ->orderBy('id', 'asc')
                    ->get()
                    ->map(function ($request) use (&$jvPool) {
                        $dc = $request->deliveryChallan;
                        $po = $request->paymentRequestData?->purchaseOrder;
                        $firstDcData = $dc?->delivery_challan_data?->first();

                        return $this->attachGrnAndJvDetails([
                            'id' => $request->id,
                            'supplier_id' => $po->supplier_id ?? '',
                            'purchaseOrder' => $po ?? null,
                            'truck_no' => $request->paymentRequestData?->truck_no ?? $request->paymentRequestData?->arrivalTicket?->truck_no ?? $firstDcData?->truck_no ?? '-',
                            'bilty_no' => $request->paymentRequestData?->bilty_no ?? $request->paymentRequestData?->arrivalTicket?->bilty_no ?? $firstDcData?->bilty_no ?? '-',
                            'loading_date' => $request->paymentRequestData && $request->paymentRequestData->loading_date
                                ? $request->paymentRequestData->loading_date->format('Y-m-d')
                                : ($dc?->dispatch_date ? date('Y-m-d', strtotime($dc->dispatch_date)) : '-'),
                            'no_of_bags' => $request->paymentRequestData?->no_of_bags ?? ($dc ? $dc->delivery_challan_data?->sum('no_of_bags') : ''),
                            'loading_weight' => $request->paymentRequestData?->loading_weight ?? ($dc ? $dc->delivery_challan_data?->sum('qty') : ''),
                            'module_type' => $dc ? 'sale_order' : ($request->paymentRequestData?->module_type ?? 'purchase_order'),
                            'contract_no' => $dc ? $dc->dc_no : ($po->contract_no ?? 'N/A'),
                            'amount' => $request->amount ?? '',
                            'purpose' => $request->paymentRequestData?->notes ?? ($dc ? "Labour for DC: {$dc->dc_no}" : 'No description'),
                            'status' => $request->approval_status ?? '',
                            'saudaType' => $dc ? ucfirst($dc->sauda_type ?? '') : ($po?->saudaType?->name ?? ''),
                            'type' => ($request->request_type) ?? '',
                            'file' => ($request->paymentRequestData?->attachment ?? null),
                            'request_date' => $request->created_at
                                ? $request->created_at->format('Y-m-d')
                                : '',
                        ], $request, $jvPool);
                    });
            }
        } elseif ($tableName === 'transporters') {
            $transporter = Transporter::with(['companyBankDetails', 'ownerBankDetails'])
                ->where('account_id', $account->id)
                ->first();

            if ($transporter) {
                $modelId = $transporter->id;
                $companyBankAccounts = $transporter->companyBankDetails ?? collect();
                $ownerBankAccounts = $transporter->ownerBankDetails ?? collect();

                if ($companyBankAccounts) {
                    foreach ($companyBankAccounts as $bank) {
                        $bankAccounts->push([
                            'id' => $bank->id,
                            'type' => 'company',
                            'title' => $bank->transporter->name ?? $transporter->name ?? '',
                            'account_title' => $bank->account_title ?? '',
                            'account_number' => $bank->account_number ?? '',
                            'bank_name' => $bank->bank_name ?? '',
                            'branch_name' => $bank->branch_name ?? '',
                            'branch_code' => $bank->branch_code ?? '',
                        ]);
                    }
                }

                if ($ownerBankAccounts) {
                    foreach ($ownerBankAccounts as $bank) {
                        $bankAccounts->push([
                            'id' => $bank->id,
                            'type' => 'owner',
                            'title' => $bank->transporter->name ?? $transporter->name ?? '',
                            'account_title' => $bank->account_title ?? '',
                            'account_number' => $bank->account_number ?? '',
                            'bank_name' => $bank->bank_name ?? '',
                            'branch_name' => $bank->branch_name ?? '',
                            'branch_code' => $bank->branch_code ?? '',
                        ]);
                    }
                }

                $paymentRequests = PaymentRequest::with(['paymentRequestData', 'approvals', 'deliveryChallan.delivery_challan_data'])
                    ->where('account_id', $accountId)
                    ->whereDoesntHave('paymentVoucherData')
                    ->where('status', 'approved')
                    ->orderBy('created_at', 'asc')
                    ->orderBy('id', 'asc')
                    ->get()
                    ->map(function ($request) use (&$jvPool) {
                        $dc = $request->deliveryChallan;
                        $po = $request->paymentRequestData?->purchaseOrder;
                        $firstDcData = $dc?->delivery_challan_data?->first();

                        return $this->attachGrnAndJvDetails([
                            'id' => $request->id,
                            'supplier_id' => $po->supplier_id ?? '',
                            'purchaseOrder' => $po ?? null,
                            'truck_no' => $request->paymentRequestData?->truck_no ?? $firstDcData?->truck_no ?? '-',
                            'bilty_no' => $request->paymentRequestData?->bilty_no ?? $firstDcData?->bilty_no ?? '-',
                            'loading_date' => $request->paymentRequestData && $request->paymentRequestData->loading_date
                                ? $request->paymentRequestData->loading_date->format('Y-m-d')
                                : ($dc?->dispatch_date ? date('Y-m-d', strtotime($dc->dispatch_date)) : '-'),
                            'no_of_bags' => $request->paymentRequestData?->no_of_bags ?? ($dc ? $dc->delivery_challan_data?->sum('no_of_bags') : ''),
                            'loading_weight' => $request->paymentRequestData?->loading_weight ?? ($dc ? $dc->delivery_challan_data?->sum('qty') : ''),
                            'module_type' => $dc ? 'sale_order' : ($request->paymentRequestData?->module_type ?? 'purchase_order'),
                            'contract_no' => $dc ? $dc->dc_no : ($po->contract_no ?? 'N/A'),
                            'amount' => $request->amount ?? '',
                            'purpose' => $request->paymentRequestData?->notes ?? ($dc ? "Freight for DC: {$dc->dc_no}" : 'No description'),
                            'status' => $request->approval_status ?? '',
                            'saudaType' => $dc ? ucfirst($dc->sauda_type ?? '') : ($po?->saudaType?->name ?? ''),
                            'type' => ($request->request_type) ?? '',
                            'file' => ($request->paymentRequestData?->attachment ?? null),
                            'request_date' => $request->created_at
                                ? $request->created_at->format('Y-m-d')
                                : '',
                        ], $request, $jvPool);
                    });
            }
        } else {
            $paymentRequests = PaymentRequest::with(['paymentRequestData', 'approvals', 'deliveryChallan.delivery_challan_data'])
                ->where('account_id', $accountId)
                ->whereDoesntHave('paymentVoucherData')
                ->where('status', 'approved')
                ->orderBy('created_at', 'asc')
                ->orderBy('id', 'asc')
                ->get()
                ->map(function ($request) use (&$jvPool) {
                    $dc = $request->deliveryChallan;
                    $po = $request->paymentRequestData?->purchaseOrder;
                    $firstDcData = $dc?->delivery_challan_data?->first();

                    return $this->attachGrnAndJvDetails([
                        'id' => $request->id,
                        'supplier_id' => $po->supplier_id ?? '',
                        'purchaseOrder' => $po ?? null,
                        'truck_no' => $request->paymentRequestData?->truck_no ?? $firstDcData?->truck_no ?? '-',
                        'bilty_no' => $request->paymentRequestData?->bilty_no ?? $firstDcData?->bilty_no ?? '-',
                        'loading_date' => $request->paymentRequestData && $request->paymentRequestData->loading_date
                            ? $request->paymentRequestData->loading_date->format('Y-m-d')
                            : ($dc?->dispatch_date ? date('Y-m-d', strtotime($dc->dispatch_date)) : '-'),
                        'no_of_bags' => $request->paymentRequestData?->no_of_bags ?? ($dc ? $dc->delivery_challan_data?->sum('no_of_bags') : ''),
                        'loading_weight' => $request->paymentRequestData?->loading_weight ?? ($dc ? $dc->delivery_challan_data?->sum('qty') : ''),
                        'module_type' => $dc ? 'sale_order' : ($request->paymentRequestData?->module_type ?? 'purchase_order'),
                        'contract_no' => $dc ? $dc->dc_no : ($po->contract_no ?? 'N/A'),
                        'amount' => $request->amount ?? '',
                        'purpose' => $request->paymentRequestData?->notes ?? ($dc ? "Commission for DC: {$dc->dc_no}" : 'No description'),
                        'status' => $request->approval_status ?? '',
                        'saudaType' => $dc ? ucfirst($dc->sauda_type ?? '') : ($po?->saudaType?->name ?? ''),
                        'type' => ($request->request_type) ?? '',
                        'file' => ($request->paymentRequestData?->attachment ?? null),
                        'request_date' => $request->created_at
                            ? $request->created_at->format('Y-m-d')
                            : '',
                    ], $request, $jvPool);
                });
        }

        return response()->json([
            'success' => true,
            'payment_requests' => $paymentRequests,
            'model_id' => $modelId,
            'bank_accounts' => $bankAccounts->values(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function storebk(Request $request)
    {
        $request->validate([
            'unique_no' => 'required',
            'pv_date' => 'required|date',
            'voucher_type' => 'required|in:bank_payment_voucher,cash_payment_voucher',
            'account_id' => 'required|exists:accounts,id',
            'request_account_id' => 'required|exists:accounts,id',
            'model_id' => 'required',
            'payment_requests' => 'required|array',
            'payment_requests.*' => 'exists:payment_requests,id',
            'ref_bill_no' => 'nullable|string',
            'bill_date' => 'nullable|date',
            'model_id' => 'nullable|required_if:voucher_type,bank_payment_voucher|string',
            'bank_account_id' => 'nullable|required_if:voucher_type,bank_payment_voucher|string',
            'bank_account_type' => 'nullable|required_if:voucher_type,bank_payment_voucher|string',
            'cheque_no' => 'nullable|required_if:voucher_type,bank_payment_voucher|string',
            'cheque_date' => 'nullable|required_if:voucher_type,bank_payment_voucher|date',
            'remarks' => 'nullable|string',
        ]);

        DB::transaction(function () use ($request) {
            $prefix = $request->voucher_type === 'bank_payment_voucher' ? 'BPV' : 'CPV';

            $datePrefix = $prefix . '-' . date('m-d-Y') . '-';
            $uniqueNo = generateUniqueNumberByDate('payment_vouchers', $datePrefix, null, 'unique_no', false);
            // dd($request->all());
            $firstRequest = PaymentRequest::with('paymentRequestData.purchaseOrder')
                ->find($request->payment_requests[0]);

            $bankAccount = null;
            $bankName = '';
            $accountNumber = '';
            if ($request->bank_account_type === 'company') {
                $bankAccount = SupplierCompanyBankDetail::find($request->bank_account_id);
            } elseif ($request->bank_account_type === 'owner') {
                $bankAccount = SupplierOwnerBankDetail::find($request->bank_account_id);
            }
            if ($bankAccount) {
                $bankName = $bankAccount->bank_name ?? '';
                $accountNumber = $bankAccount->account_number ?? '';
            }

            $paymentVoucher = PaymentVoucher::create([
                'unique_no' => $uniqueNo,
                'pv_date' => $request->pv_date,
                'ref_bill_no' => $request->ref_bill_no,
                'bill_date' => $request->bill_date,
                'cheque_no' => $request->cheque_no,
                'cheque_date' => $request->cheque_date,
                'account_id' => $request->account_id,
                'bank_account_id' => $request->bank_account_id,
                'bank_account_type' => $request->bank_account_type,
                'request_account_id' => $request->request_account_id,
                'model_id' => $request->model_id,
                'module_id' => $firstRequest->paymentRequestData->purchase_order_id ?? null,
                'module_type' => 'raw_material_purchase',
                'voucher_type' => $request->voucher_type,
                'remarks' => $request->remarks,
                'total_amount' => 0,
            ]);

            $totalAmount = 0;
            $allPaymentAgainst = [];
            $allReferenceNos = [];

            // Build bank remark base (before loop, needs total — so collect first)
            $perRequestData = [];

            foreach ($request->payment_requests as $requestId) {
                $paymentRequest = PaymentRequest::findOrFail($requestId);

                $ticketNo = ($paymentRequest->paymentRequestData->module_type == 'ticket' || $paymentRequest->paymentRequestData->module_type == 'freight_payment')
                    ? $paymentRequest->paymentRequestData->arrivalTicket->unique_no
                    : $paymentRequest->paymentRequestData->purchaseTicket->unique_no;

                $truckNo = $paymentRequest->paymentRequestData->truck_no;
                $biltyNo = $paymentRequest->paymentRequestData->bilty_no;
                $paymentRequestDataId = $paymentRequest->paymentRequestData->id;

                $allPaymentAgainst[] = "$ticketNo-$paymentRequestDataId";
                $allReferenceNos[] = "$truckNo/$biltyNo";

                PaymentVoucherData::create([
                    'payment_voucher_id' => $paymentVoucher->id,
                    'payment_request_id' => $requestId,
                    'amount' => $paymentRequest->amount,
                    'description' => $paymentRequest->paymentRequestData->notes ?? 'No description',
                ]);

                $totalAmount += $paymentRequest->amount;

                // Store per-request data for individual supplier debit transactions
                $perRequestData[] = [
                    'amount' => $paymentRequest->amount,
                    'grn_no' => $paymentRequest->paymentRequestData->grn_no,
                    'request_type' => $paymentRequest->request_type,
                    'purchase_ticket_id' => $paymentRequest->paymentRequestData->purchase_ticket_id,
                    'purchase_order_id' => $paymentRequest->paymentRequestData->purchase_order_id,
                    'arrival_ticket_id' => $paymentRequest->paymentRequestData->arrival_ticket_id,
                    'arrival_ticket_no' => $paymentRequest->paymentRequestData->arrivalTicket->unique_no ?? '',
                    'purchase_ticket_no' => $paymentRequest->paymentRequestData->purchaseTicket->unique_no ?? '',
                    'purchase_order_no' => $paymentRequest->paymentRequestData->purchaseOrder->contract_no ?? 'N/A',

                    // Conditional truck_no and bilty_no based on which ticket exists
                    'truck_no' => $paymentRequest->paymentRequestData->purchase_ticket_id
                        ? ($paymentRequest->paymentRequestData->purchaseTicket->purchaseFreight->truck_no ?? 'N/A')
                        : ($paymentRequest->paymentRequestData->arrival_ticket_id
                            ? ($paymentRequest->paymentRequestData->arrivalTicket->truck_no ?? 'N/A')
                            : 'N/A'),

                    'bilty_no' => $paymentRequest->paymentRequestData->purchase_ticket_id
                        ? ($paymentRequest->paymentRequestData->purchaseTicket->purchaseFreight->bilty_no ?? 'N/A')
                        : ($paymentRequest->paymentRequestData->arrival_ticket_id
                            ? ($paymentRequest->paymentRequestData->arrivalTicket->bilty_no ?? 'N/A')
                            : 'N/A'),

                    'request_type' => $paymentRequest->request_type,
                    'ticketNo' => $ticketNo,
                    'paymentRequestDataId' => $paymentRequestDataId,
                    'truckNo' => $truckNo,
                    'biltyNo' => $biltyNo,
                    'notes' => $paymentRequest->paymentRequestData->notes ?? '',
                ];
            }


            dd($perRequestData);

            // Build remark for bank (total amount)
            $bankRemarks = "A payment of Rs. " . number_format($totalAmount, 2) . " has been made.";
            if ($bankName) {
                $bankRemarks .= " against bank '{$bankName}'";
            }
            if ($accountNumber) {
                $bankRemarks .= " with account number '{$accountNumber}'";
            }
            $bankRemarks .= $request->voucher_type === 'bank_payment_voucher' ? ' through bank transfer.' : ' in cash '
                . count($perRequestData) . ' Bill';

            $paymentAgainstStr = implode(', ', $allPaymentAgainst);
            $referenceNosStr = implode(', ', $allReferenceNos);

            // --- ONE credit on bank/cash account (total amount) ---
            createTransaction(
                $totalAmount,
                $request->account_id,
                1,
                $uniqueNo,
                'credit',
                'no',
                [
                    'purpose' => "$prefix-{$paymentVoucher->id}-{$paymentVoucher->unique_no}",
                    'payment_against' => $paymentAgainstStr,
                    'against_reference_no' => $referenceNosStr,
                    'counter_account_id' => $request->request_account_id,
                    'remarks' => $bankRemarks,
                ]
            );

            // --- Per-request DEBIT on supplier/broker account (individual amounts) ---
            foreach ($perRequestData as $item) {
                // if ($pay) {
                //     $supplierRemarks .= " against bank '{$bankName}'";
                // }
                // if ($accountNumber) {
                //     $supplierRemarks .= " with account number '{$accountNumber}'";
                // }


                $supplierRemarks = "A payment of Rs. " . number_format($item['amount'], 2) . " has been made.";
                if ($bankName) {
                    $supplierRemarks .= " against bank '{$bankName}'";
                }
                if ($accountNumber) {
                    $supplierRemarks .= " with account number '{$accountNumber}'";
                }
                $supplierRemarks .= $request->voucher_type === 'bank_payment_voucher' ? ' through bank transfer.' : ' in cash.';

                createTransaction(
                    $item['amount'],
                    $request->request_account_id,
                    1,
                    $uniqueNo,
                    'debit',
                    'no',
                    [
                        'purpose' => "$prefix-{$paymentVoucher->id}-{$paymentVoucher->unique_no}",
                        'payment_against' => $item['request_type'],
                        'against_reference_no' => "{$item['truckNo']}/{$item['biltyNo']}",
                        'counter_account_id' => $request->account_id,
                        'remarks' => $supplierRemarks,
                    ]
                );
            }

            $paymentVoucher->update(['total_amount' => $totalAmount]);
        });

        return response()->json([
            'success' => 'Payment voucher created successfully!',
            'redirect' => route('payment-voucher.index'),
        ]);
    }



    public function store(Request $request)
    {
        $request->validate([
            'unique_no' => 'required',
            'pv_date' => 'required|date',
            'voucher_type' => 'required|in:bank_payment_voucher,cash_payment_voucher',
            'account_id' => 'required|exists:accounts,id',
            'request_account_id' => 'required|exists:accounts,id',
            'model_id' => 'required',
            'payment_requests' => 'required|array',
            'payment_requests.*' => 'exists:payment_requests,id',
            'ref_bill_no' => 'nullable|string',
            'bill_date' => 'nullable|date',
            'model_id' => 'nullable|required_if:voucher_type,bank_payment_voucher|string',
            'bank_account_id' => 'nullable|required_if:voucher_type,bank_payment_voucher|string',
            'bank_account_type' => 'nullable|required_if:voucher_type,bank_payment_voucher|string',
            'cheque_no' => 'nullable|required_if:voucher_type,bank_payment_voucher|string',
            'cheque_date' => 'nullable|required_if:voucher_type,bank_payment_voucher|date',
            'remarks' => 'nullable|string',
        ]);
        DB::transaction(function () use ($request) {
            $prefix = $request->voucher_type === 'bank_payment_voucher' ? 'BPV' : 'CPV';

            $datePrefix = $prefix . '-' . date('m-d-Y') . '-';
            $uniqueNo = generateUniqueNumberByDate('payment_vouchers', $datePrefix, null, 'unique_no', false);
            // dd($request->all());
            $firstRequest = PaymentRequest::with(['paymentRequestData.purchaseOrder', 'deliveryChallan'])
                ->find($request->payment_requests[0]);

            $requestAccount = Account::find($request->request_account_id);
            $tableName = $requestAccount?->table_name ?? null;

            $bankAccount = null;
            $bankName = '';
            $accountNumber = '';
            if ($tableName == 'suppliers') {
                if ($request->bank_account_type === 'company') {
                    $bankAccount = SupplierCompanyBankDetail::find($request->bank_account_id);
                } elseif ($request->bank_account_type === 'owner') {
                    $bankAccount = SupplierOwnerBankDetail::find($request->bank_account_id);
                }
            } elseif ($tableName == 'brokers') {
                if ($request->bank_account_type === 'company') {
                    $bankAccount = BrokerCompanyBankDetail::find($request->bank_account_id);
                } elseif ($request->bank_account_type === 'owner') {
                    $bankAccount = BrokerOwnerBankDetail::find($request->bank_account_id);
                }
            } elseif ($tableName == 'vendors') {
                if ($request->bank_account_type === 'company') {
                    $bankAccount = VendorCompanyBankDetail::find($request->bank_account_id);
                } elseif ($request->bank_account_type === 'owner') {
                    $bankAccount = VendorOwnerBankDetail::find($request->bank_account_id);
                }
            } elseif ($tableName == 'transporters') {
                if ($request->bank_account_type === 'company') {
                    $bankAccount = TransporterCompanyBankDetail::find($request->bank_account_id);
                } elseif ($request->bank_account_type === 'owner') {
                    $bankAccount = TransporterOwnerBankDetail::find($request->bank_account_id);
                }
            } else {
                if ($request->bank_account_type === 'company') {
                    $bankAccount = SupplierCompanyBankDetail::find($request->bank_account_id)
                        ?? TransporterCompanyBankDetail::find($request->bank_account_id)
                        ?? VendorCompanyBankDetail::find($request->bank_account_id)
                        ?? BrokerCompanyBankDetail::find($request->bank_account_id);
                } elseif ($request->bank_account_type === 'owner') {
                    $bankAccount = SupplierOwnerBankDetail::find($request->bank_account_id)
                        ?? TransporterOwnerBankDetail::find($request->bank_account_id)
                        ?? VendorOwnerBankDetail::find($request->bank_account_id)
                        ?? BrokerOwnerBankDetail::find($request->bank_account_id);
                }
            }
            if ($bankAccount) {
                $bankName = $bankAccount->bank_name ?? '';
                $accountNumber = $bankAccount->account_number ?? '';
            }

            $moduleId = $firstRequest?->delivery_challan_id ?? ($firstRequest?->paymentRequestData?->purchase_order_id ?? null);
            $moduleType = $firstRequest?->delivery_challan_id ? 'delivery_challan' : 'raw_material_purchase';

            $paymentVoucher = PaymentVoucher::create([
                'unique_no' => $uniqueNo,
                'pv_date' => $request->pv_date,
                'ref_bill_no' => $request->ref_bill_no,
                'bill_date' => $request->bill_date,
                'cheque_no' => $request->cheque_no,
                'cheque_date' => $request->cheque_date,
                'account_id' => $request->account_id,
                'bank_account_id' => $request->bank_account_id,
                'bank_account_type' => $request->bank_account_type,
                'request_account_id' => $request->request_account_id,
                'model_id' => $request->model_id,
                'module_id' => $moduleId,
                'module_type' => $moduleType,
                'voucher_type' => $request->voucher_type,
                'remarks' => $request->remarks,
                'total_amount' => 0,
            ]);

            $totalAmount = 0;
            $allPaymentAgainst = [];
            $allReferenceNos = [];
            $perRequestData = [];

            $jvPool = [];
            $requestedIds = $request->payment_requests ?? [];
            $sortedRequests = PaymentRequest::with(['paymentRequestData', 'deliveryChallan.delivery_challan_data'])
                ->whereIn('id', $requestedIds)
                ->orderBy('created_at', 'asc')
                ->orderBy('id', 'asc')
                ->get();

            foreach ($sortedRequests as $paymentRequest) {
                $requestId = $paymentRequest->id;
                $dc = $paymentRequest->deliveryChallan;
                $ticketDetails = '';

                if ($dc) {
                    $ticketNo = $dc->dc_no;
                    $firstDcData = $dc->delivery_challan_data->first();
                    $truckNo = $paymentRequest->paymentRequestData->truck_no ?? $firstDcData?->truck_no ?? 'N/A';
                    $biltyNo = $paymentRequest->paymentRequestData->bilty_no ?? $firstDcData?->bilty_no ?? 'N/A';
                    $ticketDetails = "DC# {$dc->dc_no} VEH# {$truckNo} Bilty# {$biltyNo}";
                } elseif ($paymentRequest->paymentRequestData->purchase_ticket_id) {
                    $ticketNo = $paymentRequest->paymentRequestData->purchaseTicket->unique_no ?? 'N/A';
                    $truckNo = $paymentRequest->paymentRequestData->purchaseTicket->purchaseFreight->truck_no ?? 'N/A';
                    $biltyNo = $paymentRequest->paymentRequestData->purchaseTicket->purchaseFreight->bilty_no ?? 'N/A';
                    $ticketDetails = "Purchase Ticket #" . $ticketNo . " ";
                    $ticketDetails .= "VEH# " . $truckNo . " ";
                    $ticketDetails .= "Bilty# " . $biltyNo . " ";
                    $ticketDetails .= "PO# " . ($paymentRequest->paymentRequestData->purchaseOrder->contract_no ?? 'N/A');
                } elseif ($paymentRequest->paymentRequestData->arrival_ticket_id) {
                    $ticketNo = $paymentRequest->paymentRequestData->arrivalTicket->unique_no ?? 'N/A';
                    $truckNo = $paymentRequest->paymentRequestData->arrivalTicket->truck_no ?? 'N/A';
                    $biltyNo = $paymentRequest->paymentRequestData->arrivalTicket->bilty_no ?? 'N/A';
                    $ticketDetails = "Arrival Ticket # " . $ticketNo . " ";
                    $ticketDetails .= "VEH# " . $truckNo . " ";
                    $ticketDetails .= "Bilty No: " . $biltyNo . " ";
                    $ticketDetails .= "PO# " . ($paymentRequest->paymentRequestData->purchaseOrder->contract_no ?? 'N/A') . " ";
                    $ticketDetails .= "GRN# " . ($paymentRequest->paymentRequestData->grn_no ?? 'N/A');
                } else {
                    $ticketNo = $paymentRequest->request_no ?? 'PR';
                    $truckNo = $paymentRequest->paymentRequestData->truck_no ?? 'N/A';
                    $biltyNo = $paymentRequest->paymentRequestData->bilty_no ?? 'N/A';
                    $ticketDetails = "PR# {$paymentRequest->id} VEH# {$truckNo} Bilty# {$biltyNo}";
                }

                $paymentRequestDataId = $paymentRequest->paymentRequestData->id ?? $paymentRequest->id;

                $allPaymentAgainst[] = "$ticketNo-$paymentRequestDataId";
                $allReferenceNos[] = "$truckNo/$biltyNo";

                // Check for GRN and JV adjustments
                $grnNo = $paymentRequest->paymentRequestData?->grn_no ?? null;
                $grnId = $paymentRequest->paymentRequestData?->grn_id ?? null;
                $userAdj = isset($request->adjustment_amounts[$requestId]) ? (float) $request->adjustment_amounts[$requestId] : null;

                $appliedAdj = 0;
                $matchingJvNos = [];
                $jvDeductions = [];

                if (!empty($grnNo)) {
                    $details = JournalVoucherDetail::with('journalVoucher')
                        ->where(function ($q) use ($grnNo, $grnId) {
                            $q->where('voucher_no', $grnNo)
                                ->orWhere('voucher_id', $grnNo);
                            if ($grnId) {
                                $q->orWhere('voucher_id', $grnId);
                            }
                        })
                        ->whereNull('deleted_at')
                        ->whereHas('journalVoucher', function ($q) {
                            $q->whereNull('deleted_at')
                                ->where('am_approval_status', 'approved');
                        })
                        ->orderBy('journal_voucher_id', 'asc')
                        ->orderBy('id', 'asc')
                        ->get();

                    $maxToAdjust = ($userAdj !== null) ? min((float) $paymentRequest->amount, max(0, $userAdj)) : (float) $paymentRequest->amount;
                    $remainingToAdjust = $maxToAdjust;

                    $processedJvsThisRequest = [];
                    foreach ($details as $detail) {
                        if ($remainingToAdjust <= 0) {
                            break;
                        }

                        $jv = $detail->journalVoucher;
                        if (!$jv || isset($processedJvsThisRequest[$jv->id])) {
                            continue;
                        }
                        $processedJvsThisRequest[$jv->id] = true;

                        if (!isset($jvPool[$jv->id])) {
                            $jvLineTotal = (float) JournalVoucherDetail::where('journal_voucher_id', $jv->id)
                                ->where(function ($q) use ($grnNo, $grnId) {
                                    $q->where('voucher_no', $grnNo)
                                        ->orWhere('voucher_id', $grnNo);
                                    if ($grnId) {
                                        $q->orWhere('voucher_id', $grnId);
                                    }
                                })
                                ->whereNull('deleted_at')
                                ->sum(DB::raw('CASE WHEN debit_amount > 0 THEN debit_amount ELSE credit_amount END'));

                            $spent = (float) DB::table('settlement_adjustments')
                                ->where('reference_type', 'journal_voucher')
                                ->where('reference_id', $jv->id)
                                ->sum('amount');

                            $jvPool[$jv->id] = max(0, $jvLineTotal - $spent);
                        }

                        $available = $jvPool[$jv->id];

                        if ($available > 0) {
                            $deduct = min($remainingToAdjust, $available);
                            if ($deduct > 0) {
                                DB::table('settlement_adjustments')->insert([
                                    'reference_type' => 'journal_voucher',
                                    'reference_id' => $jv->id,
                                    'voucher_no' => $uniqueNo,
                                    'amount' => $deduct,
                                    'created_at' => now(),
                                    'updated_at' => now(),
                                ]);
                                $jvPool[$jv->id] -= $deduct;
                                $appliedAdj += $deduct;
                                $remainingToAdjust -= $deduct;
                                $matchingJvNos[] = $jv->jv_no;
                                $jvDeductions[] = [
                                    'jv_no' => $jv->jv_no,
                                    'amount' => $deduct,
                                ];
                            }
                        }
                    }
                }

                $jvNarrationParts = [];
                foreach ($jvDeductions as $jd) {
                    $jvNarrationParts[] = "{$jd['jv_no']}: Rs. " . number_format($jd['amount'], 2);
                }
                $jvNarration = !empty($jvNarrationParts) ? implode(', ', $jvNarrationParts) : null;

                $payableAmount = max(0, (float) $paymentRequest->amount - $appliedAdj);

                PaymentVoucherData::create([
                    'payment_voucher_id' => $paymentVoucher->id,
                    'payment_request_id' => $requestId,
                    'amount' => $payableAmount,
                    'net_amount' => $payableAmount,
                    'description' => $paymentRequest->paymentRequestData->notes ?? 'No description',
                ]);

                $totalAmount += $payableAmount;

                $perRequestData[] = [
                    'amount' => $payableAmount,
                    'gross_amount' => (float) $paymentRequest->amount,
                    'applied_adj' => $appliedAdj,
                    'jv_nos' => implode(', ', array_unique($matchingJvNos)),
                    'jv_narration' => $jvNarration,
                    'grn_no' => $paymentRequest->paymentRequestData->grn_no ?? null,
                    'request_type' => $paymentRequest->request_type,
                    'purchase_ticket_id' => $paymentRequest->paymentRequestData->purchase_ticket_id ?? null,
                    'purchase_order_id' => $paymentRequest->paymentRequestData->purchase_order_id ?? null,
                    'arrival_ticket_id' => $paymentRequest->paymentRequestData->arrival_ticket_id ?? null,
                    'arrival_ticket_no' => $paymentRequest->paymentRequestData->arrivalTicket->unique_no ?? '',
                    'purchase_ticket_no' => $paymentRequest->paymentRequestData->purchaseTicket->unique_no ?? '',
                    'purchase_order_no' => $paymentRequest->paymentRequestData->purchaseOrder->contract_no ?? ($dc ? $dc->dc_no : 'N/A'),
                    'truck_no' => $truckNo,
                    'bilty_no' => $biltyNo,
                    'ticket_details' => $ticketDetails, // Add this for remarks
                    'ticketNo' => $ticketNo,
                    'paymentRequestDataId' => $paymentRequestDataId,
                    'truckNo' => $truckNo,
                    'biltyNo' => $biltyNo,
                    'notes' => $paymentRequest->paymentRequestData->notes ?? '',
                ];
            }
            // dd($perRequestData);
            // Build bank remark
            $bankRemarks = "A payment of Rs. " . number_format($totalAmount, 2) . " has been made.";
            // if ($bankName) {
            //     $bankRemarks .= " against bank '{$bankName}'";
            // }
            // if ($accountNumber) {
            //     $bankRemarks .= " with account number '{$accountNumber}'";
            // }
            $bankRemarks .= $request->voucher_type === 'bank_payment_voucher' ? ' through bank transfer.' : ' in cash ' . count($perRequestData) . ' bills';

            $allJvNarrations = array_filter(array_column($perRequestData, 'jv_narration'));
            $combinedJvNarration = !empty($allJvNarrations) ? implode('; ', array_unique($allJvNarrations)) : null;

            // Create credit transaction (only if totalAmount > 0)
            if ($totalAmount > 0) {
                createTransaction(
                    $totalAmount,
                    $request->account_id,
                    1,
                    $uniqueNo,
                    'credit',
                    'no',
                    [
                        'purpose' => "$prefix-{$paymentVoucher->id}-{$paymentVoucher->unique_no}",
                        'payment_against' => implode(', ', $allPaymentAgainst),
                        'against_reference_no' => implode(', ', $allReferenceNos),
                        'counter_account_id' => $request->request_account_id,
                        'remarks' => $bankRemarks,
                        'jv_narration' => $combinedJvNarration ? substr($combinedJvNarration, 0, 255) : null,
                    ]
                );
            }

            // Create debit transactions with full ticket details (if amount > 0 or if JV adjustment applied)
            foreach ($perRequestData as $item) {
                if ($item['amount'] > 0 || !empty($item['applied_adj'])) {
                    $supplierRemarks = "A payment of Rs. " . number_format($item['amount'], 2) . " has been made for: " . $item['ticket_details'];
                    if (!empty($item['applied_adj']) && $item['applied_adj'] > 0) {
                        $supplierRemarks .= " (Gross: Rs. " . number_format($item['gross_amount'], 2) . ", JV Adj: Rs. " . number_format($item['applied_adj'], 2) . ", Net: Rs. " . number_format($item['amount'], 2) . ($item['jv_nos'] ? " against JV# {$item['jv_nos']}" : "") . ")";
                    }
                    if ($bankName) {
                        $supplierRemarks .= " against bank '{$bankName}'";
                    }
                    if ($accountNumber) {
                        $supplierRemarks .= " with account number '{$accountNumber}'";
                    }
                    $supplierRemarks .= $request->voucher_type === 'bank_payment_voucher' ? ' through bank transfer.' : ' in cash.';

                    createTransaction(
                        $item['amount'],
                        $request->request_account_id,
                        1,
                        $uniqueNo,
                        'debit',
                        'no',
                        [
                            'purpose' => "$prefix-{$paymentVoucher->id}-{$paymentVoucher->unique_no}",
                            'payment_against' => $item['request_type'],
                            'against_reference_no' => "{$item['truckNo']}/{$item['biltyNo']}",
                            'counter_account_id' => $request->account_id,
                            'remarks' => $item['ticket_details'],
                            'jv_narration' => !empty($item['jv_narration']) ? substr($item['jv_narration'], 0, 255) : null,
                            'reference_no' => $item['grn_no'] ?? null,
                            'grn_no' => $item['grn_no'] ?? null,
                        ]
                    );
                }
            }

            $paymentVoucher->update(['total_amount' => $totalAmount]);
        });

        return response()->json([
            'success' => 'Payment voucher created successfully!',
            'redirect' => route('payment-voucher.index'),
        ]);
    }


    public function updateBillPaymentVoucher(Request $request, int $id)
    {
        $request->validate([
            'unique_no' => 'required',
            'pv_date' => 'required|date',
            'voucher_type' => 'required|in:bank_payment_voucher,cash_payment_voucher',
            'supplier_id' => 'required|exists:accounts,id',
            'ref_bill_no' => 'nullable|string',
            'bill_date' => 'nullable|date',
            'bank_account_id' => 'nullable|required_if:voucher_type,bank_payment_voucher|string',
            'bank_account_type' => 'nullable|required_if:voucher_type,bank_payment_voucher|string',
            'cheque_no' => 'nullable|required_if:voucher_type,bank_payment_voucher|string',
            'cheque_date' => 'nullable|required_if:voucher_type,bank_payment_voucher|date',
            'remarks' => 'nullable|string',
        ]);

        DB::transaction(function () use ($request, $id) {
            $prefix = $request->voucher_type === 'bank_payment_voucher' ? 'BPV' : 'CPV';

            $datePrefix = $prefix . '-' . date('m-d-Y') . '-';
            $uniqueNo = generateUniqueNumberByDate('payment_vouchers', $datePrefix, null, 'unique_no', false);
            // dd($request->all());

            $bankAccount = null;
            $bankName = '';
            $accountNumber = '';
            if ($request->bank_account_type === 'company') {
                $bankAccount = SupplierCompanyBankDetail::find($request->bank_account_id);
            } elseif ($request->bank_account_type === 'owner') {
                $bankAccount = SupplierOwnerBankDetail::find($request->bank_account_id);
            }
            if ($bankAccount) {
                $bankName = $bankAccount->bank_name ?? '';
                $accountNumber = $bankAccount->account_number ?? '';
            }

            $paymentVoucher = PaymentVoucher::find($id);
            $paymentVoucher->update([
                'unique_no' => $uniqueNo,
                'pv_date' => $request->pv_date,
                'ref_bill_no' => $request->ref_bill_no,
                'bill_date' => $request->bill_date,
                'cheque_no' => $request->cheque_no,
                'cheque_date' => $request->cheque_date,
                'supplier_id' => $request->supplier_id,
                'bank_account_id' => $request->bank_account_id,
                'bank_account_type' => $request->bank_account_type,
                'request_account_id' => "-",
                'model_id' => "-",
                'module_id' => null,
                // 'module_type' => $firstRequest->paymentRequestData->module_type ?? 'raw_material_purchase',
                'module_type' => 'bill_payment_voucher',
                'voucher_type' => $request->voucher_type,
                'remarks' => $request->remarks,
                'total_amount' => 0,
            ]);

            $totalAmount = 0;
            $paymentVoucher->billPaymentVoucherData()->delete();

            foreach ($request->bill as $index => $bill) {

                $paymentVoucher->billPaymentVoucherData()->create([
                    "payment_voucher_id" => $id,
                    "purchase_bill_id" => $request->purchase_bill_id[$index],
                    "amount" => $request->amounts[$index]
                ]);

                $totalAmount += $request->amounts[$index];
            }

            $paymentVoucher->update(['total_amount' => $totalAmount]);
        });

        return response()->json([
            'success' => 'Payment voucher created successfully!',
            'redirect' => route('payment-voucher.index'),
        ]);
    }

    public function storeBill(Request $request)
    {
        $request->validate([
            'unique_no' => 'required',
            'pv_date' => 'required|date',
            'voucher_type' => 'required|in:bank_payment_voucher,cash_payment_voucher',
            'supplier_id' => 'required|exists:accounts,id',
            'ref_bill_no' => 'nullable|string',
            'bill_date' => 'nullable|date',
            'bank_account_id' => 'nullable|required_if:voucher_type,bank_payment_voucher|string',
            'bank_account_type' => 'nullable|required_if:voucher_type,bank_payment_voucher|string',
            'cheque_no' => 'nullable|required_if:voucher_type,bank_payment_voucher|string',
            'cheque_date' => 'nullable|required_if:voucher_type,bank_payment_voucher|date',
            'remarks' => 'nullable|string',
        ]);

        DB::transaction(function () use ($request) {
            $prefix = $request->voucher_type === 'bank_payment_voucher' ? 'BPV' : 'CPV';

            $datePrefix = $prefix . '-' . date('m-d-Y') . '-';
            $uniqueNo = generateUniqueNumberByDate('payment_vouchers', $datePrefix, null, 'unique_no', false);
            // dd($request->all());

            $bankAccount = null;
            $bankName = '';
            $accountNumber = '';
            if ($request->bank_account_type === 'company') {
                $bankAccount = SupplierCompanyBankDetail::find($request->bank_account_id);
            } elseif ($request->bank_account_type === 'owner') {
                $bankAccount = SupplierOwnerBankDetail::find($request->bank_account_id);
            }
            if ($bankAccount) {
                $bankName = $bankAccount->bank_name ?? '';
                $accountNumber = $bankAccount->account_number ?? '';
            }

            $paymentVoucher = PaymentVoucher::create([
                'unique_no' => $uniqueNo,
                'pv_date' => $request->pv_date,
                'ref_bill_no' => $request->ref_bill_no,
                'bill_date' => $request->bill_date,
                'cheque_no' => $request->cheque_no,
                'cheque_date' => $request->cheque_date,
                'supplier_id' => $request->supplier_id,
                'bank_account_id' => $request->bank_account_id,
                'bank_account_type' => $request->bank_account_type,
                'request_account_id' => "-",
                'model_id' => "-",
                'module_id' => null,
                // 'module_type' => $firstRequest->paymentRequestData->module_type ?? 'raw_material_purchase',
                'module_type' => 'bill_payment_voucher',
                'voucher_type' => $request->voucher_type,
                'remarks' => $request->remarks,
                'total_amount' => 0,
            ]);

            $totalAmount = 0;

            foreach ($request->bill as $index => $bill) {
                $ref_no = $request->purchase_bill_id[$index];

                $amount = number_format($request->amounts[$index], 2);

                $remarks = "A payment of Rs. {$amount} has been made.";
                if ($bankName) {
                    $remarks .= " against bank '{$bankName}'";
                }
                if ($accountNumber) {
                    $remarks .= " with account number '{$accountNumber}'";
                }
                if ($request->voucher_type === 'bank_payment_voucher') {
                    $remarks .= ' through bank transfer.';
                } else {
                    $remarks .= ' in cash.';
                }

                createTransaction(
                    $request->amounts[$index],
                    $request->bank_account_id,
                    1,
                    $uniqueNo,
                    'credit',
                    'no',
                    [
                        'purpose' => "$prefix-$paymentVoucher->id-$paymentVoucher->unique_no",
                        'payment_against' => "$ref_no",
                        'against_reference_no' => purchase_bill($request->purchase_bill_id[$index])->reference_no,
                        'counter_account_id' => $request->supplier_id,
                        'remarks' => $remarks,
                    ]
                );

                createTransaction(
                    $request->amounts[$index],
                    $request->supplier_id,
                    1,
                    $uniqueNo,
                    'debit',
                    'no',
                    [
                        'purpose' => "$prefix-$paymentVoucher->id-$paymentVoucher->unique_no",
                        'payment_against' => "$ref_no",
                        'counter_account_id' => $request->bank_account_id,
                        'against_reference_no' => purchase_bill($request->purchase_bill_id[$index])->reference_no,
                        'remarks' => $remarks,
                    ]
                );

                $purchase_bill = PurchaseBill::find($request->purchase_bill_id[$index]);
                $remaining_balance = getPaymentVoucherBillBalance($purchase_bill);
                if ($request->amounts[$index] > $remaining_balance) {
                    DB::rollBack();
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        "amounts.$index" => ["Your balance is $remaining_balance, you cannot exceed that balance."]
                    ]);
                }

                $bill_payment_voucher_data = BillPaymentVoucherData::create([
                    "payment_voucher_id" => $paymentVoucher->id,
                    "purchase_bill_id" => $request->purchase_bill_id[$index],
                    "amount" => $request->amounts[$index]
                ]);

                $totalAmount += $request->amounts[$index];
            }


            $paymentVoucher->update(['total_amount' => $totalAmount]);
        });

        return response()->json([
            'success' => 'Payment voucher created successfully!',
            'redirect' => route('payment-voucher.index'),
        ]);
    }

    public function _store(Request $request)
    {
        $request->validate([
            'unique_no' => 'required',
            'pv_date' => 'required|date',
            'voucher_type' => 'required|in:bank_payment_voucher,cash_payment_voucher',
            'account_id' => 'required|exists:accounts,id',
            'module_id' => 'required|exists:arrival_purchase_orders,id',
            'payment_requests' => 'required|array',
            'payment_requests.*' => 'exists:payment_requests,id',
            'ref_bill_no' => 'nullable|string',
            'bill_date' => 'nullable|date',
            'supplier_id' => 'nullable|required_if:voucher_type,bank_payment_voucher|string',
            'bank_account_id' => 'nullable|required_if:voucher_type,bank_payment_voucher|string',
            'bank_account_type' => 'nullable|required_if:voucher_type,bank_payment_voucher|string',
            'cheque_no' => 'nullable|required_if:voucher_type,bank_payment_voucher|string',
            'cheque_date' => 'nullable|required_if:voucher_type,bank_payment_voucher|date',
            'remarks' => 'nullable|string',
        ]);
        // dd($request->all());
        DB::transaction(function () use ($request) {
            $prefix = $request->voucher_type === 'bank_payment_voucher' ? 'BPV' : 'CPV';

            $datePrefix = $prefix . '-' . date('m-d-Y') . '-';
            $uniqueNo = generateUniqueNumberByDate('payment_vouchers', $datePrefix, null, 'unique_no', false);

            $paymentVoucher = PaymentVoucher::create([
                'unique_no' => $uniqueNo,
                'pv_date' => $request->pv_date,
                'ref_bill_no' => $request->ref_bill_no,
                'bill_date' => $request->bill_date,
                'cheque_no' => $request->cheque_no,
                'cheque_date' => $request->cheque_date,
                'account_id' => $request->account_id,
                'bank_account_id' => $request->bank_account_id,
                'bank_account_type' => $request->bank_account_type,
                'supplier_id' => $request->supplier_id,
                'module_id' => $request->module_id,
                'module_type' => 'raw_material_purchase',
                'voucher_type' => $request->voucher_type,
                'remarks' => $request->remarks,
                'total_amount' => 0,
            ]);

            $totalAmount = 0;

            foreach ($request->payment_requests as $requestId) {
                $paymentRequest = PaymentRequest::findOrFail($requestId);

                PaymentVoucherData::create([
                    'payment_voucher_id' => $paymentVoucher->id,
                    'payment_request_id' => $requestId,
                    'amount' => $paymentRequest->amount,
                    'description' => $paymentRequest->paymentRequestData->notes ?? 'No description',
                ]);

                $totalAmount += $paymentRequest->amount;
            }

            $paymentVoucher->update(['total_amount' => $totalAmount]);
        });

        return response()->json([
            'success' => 'Payment voucher created successfully!',
            'redirect' => route('payment-voucher.index'),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $paymentVoucher = PaymentVoucher::with(['paymentVoucherData.paymentRequest.paymentRequestData'])->findOrFail($id);

        $data = [
            'paymentVoucher' => $paymentVoucher,
            'accounts' => Account::all(),
            'purchaseOrders' => ArrivalPurchaseOrder::with(['product'])->latest()->get(),
            'selectedRequests' => $paymentVoucher->paymentVoucherData->pluck('payment_request_id')->toArray(),
        ];

        return view('management.finance.payment_voucher.edit', $data);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $paymentVoucher = PaymentVoucher::findOrFail($id);

        $request->validate([
            'pv_date' => 'required|date',
            'account_id' => 'required|exists:accounts,id',
            'request_account_id' => 'required|exists:accounts,id',
            'payment_requests' => 'required|array',
            'payment_requests.*' => 'exists:payment_requests,id',
            'ref_bill_no' => 'nullable|string',
            'bill_date' => 'nullable|date',
            'cheque_no' => 'nullable|required_if:voucher_type,bank_payment_voucher|string',
            'cheque_date' => 'nullable|required_if:voucher_type,bank_payment_voucher|date',
            'remarks' => 'nullable|string',
        ]);

        DB::transaction(function () use ($request, $paymentVoucher) {
            $paymentVoucher->update([
                'pv_date' => $request->pv_date,
                'ref_bill_no' => $request->ref_bill_no,
                'bill_date' => $request->bill_date,
                'cheque_no' => $request->cheque_no,
                'cheque_date' => $request->cheque_date,
                'account_id' => $request->account_id,
                'request_account_id' => $request->request_account_id,
                'bank_account_id' => $request->bank_account_id,
                'bank_account_type' => $request->bank_account_type,
                'remarks' => $request->remarks,
            ]);

            PaymentVoucherData::where('payment_voucher_id', $paymentVoucher->id)->delete();

            $totalAmount = 0;

            foreach ($request->payment_requests as $requestId) {
                $paymentRequest = PaymentRequest::findOrFail($requestId);

                PaymentVoucherData::create([
                    'payment_voucher_id' => $paymentVoucher->id,
                    'payment_request_id' => $requestId,
                    'amount' => $paymentRequest->amount,
                    'description' => $paymentRequest->paymentRequestData->notes ?? 'No description',
                ]);

                $totalAmount += $paymentRequest->amount;
            }

            $paymentVoucher->update(['total_amount' => number_format($totalAmount, 2, '.', '')]);
        });

        return response()->json([
            'success' => 'Payment voucher updated successfully!',
            'redirect' => route('payment-voucher.index'),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $paymentVoucher = PaymentVoucher::findOrFail($id);

        // Delete linked settlement adjustments to restore JV available balance
        DB::table('settlement_adjustments')->where('voucher_no', $paymentVoucher->unique_no)->delete();

        $paymentVoucher->delete();

        return response()->json([
            'success' => 'Payment voucher deleted successfully!',
        ]);
    }
}
