<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\ArrivalPurchaseOrder;
use App\Models\Master\Account\Account;
use App\Models\Master\Account\Transaction;
use App\Models\Master\Supplier;
use App\Models\PaymentVoucher;
use App\Models\Procurement\PaymentRequest;
use App\Models\PaymentVoucherData;
use App\Models\Procurement\PaymentRequestData;
use App\Models\SupplierCompanyBankDetail;
use App\Models\SupplierOwnerBankDetail;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentVoucherController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('management.finance.payment_voucher.index');
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

        return view('management.finance.payment_voucher.create', $data);
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
            'supplier'
        ])->findOrFail($id);

        $transactions = Transaction::where('transaction_voucher_type_id', 1)->where('voucher_no', $paymentVoucher->unique_no)
            ->get();

        $bankAccount = null;
        if ($paymentVoucher->bank_account_type === 'company') {
            $bankAccount =  SupplierCompanyBankDetail::find($paymentVoucher->bank_account_id);
        } elseif ($paymentVoucher->bank_account_type === 'owner') {
            $bankAccount =  SupplierOwnerBankDetail::find($paymentVoucher->bank_account_id);
        }

        return view('management.finance.payment_voucher.show', [
            'paymentVoucher' => $paymentVoucher,
            'transactions' => $transactions,
            'bankAccount' => $bankAccount
        ]);
    }

    /**
     * Generate PV number
     */
    public function generatePvNumber(Request $request)
    {
        $request->validate([
            'voucher_type' => 'required|in:bank_payment_voucher,cash_payment_voucher',
            'pv_date' => 'nullable|date'
        ]);

        $prefix = $request->voucher_type === 'bank_payment_voucher' ? 'BPV' : 'CPV';
        $prefixForAccounts = $request->voucher_type === 'bank_payment_voucher' ? 'Bank' : 'Cash';

        $accounts = Account::whereHas('parent', function ($query) use ($prefixForAccounts) {
            $query->where('name', $prefixForAccounts);
        })->get();

        $pvDate = $request->pv_date ? date('m-d-Y', strtotime($request->pv_date)) : date('m-d-Y');
        $datePrefix = $prefix . '-' . $pvDate . '-';
        $uniqueNo = generateUniqueNumberByDate('payment_vouchers', $datePrefix, null, 'unique_no', false);

        return response()->json([
            'success' => true,
            'pv_number' => $uniqueNo,
            'accounts' => $accounts
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
                        : ''
                ];
            });

        return response()->json([
            'success' => true,
            'payment_requests' => $paymentRequests,
            'bank_accounts' => $bankAccounts->values()
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'unique_no' => 'required',
            'pv_date' => 'required|date',
            'voucher_type' => 'required|in:bank_payment_voucher,cash_payment_voucher',
            'account_id' => 'required|exists:accounts,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'payment_requests' => 'required|array',
            'payment_requests.*' => 'exists:payment_requests,id',
            'ref_bill_no' => 'nullable|string',
            'bill_date' => 'nullable|date',
            'supplier_id' => 'nullable|required_if:voucher_type,bank_payment_voucher|string',
            'bank_account_id' => 'nullable|required_if:voucher_type,bank_payment_voucher|string',
            'bank_account_type' => 'nullable|required_if:voucher_type,bank_payment_voucher|string',
            'cheque_no' => 'nullable|required_if:voucher_type,bank_payment_voucher|string',
            'cheque_date' => 'nullable|required_if:voucher_type,bank_payment_voucher|date',
            'remarks' => 'nullable|string'
        ]);

        DB::transaction(function () use ($request) {
            $prefix = $request->voucher_type === 'bank_payment_voucher' ? 'BPV' : 'CPV';

            $datePrefix = $prefix . '-' . date('m-d-Y') . '-';
            $uniqueNo = generateUniqueNumberByDate('payment_vouchers', $datePrefix, null, 'unique_no', false);

            $firstRequest = PaymentRequest::with('paymentRequestData.purchaseOrder')
                ->find($request->payment_requests[0]);

            $bankAccount = null;
            $bankName = '';
            $accountNumber = '';
            if ($request->bank_account_type === 'company') {
                $bankAccount =  SupplierCompanyBankDetail::find($request->bank_account_id);
            } elseif ($request->bank_account_type === 'owner') {
                $bankAccount =  SupplierOwnerBankDetail::find($request->bank_account_id);
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
                'supplier_id' => $request->supplier_id,
                'module_id' => $firstRequest->paymentRequestData->purchase_order_id ?? null,
                // 'module_type' => $firstRequest->paymentRequestData->module_type ?? 'raw_material_purchase',
                'module_type' => 'raw_material_purchase',
                'voucher_type' => $request->voucher_type,
                'remarks' => $request->remarks,
                'total_amount' => 0
            ]);

            $totalAmount = 0;

            foreach ($request->payment_requests as $requestId) {
                $paymentRequest = PaymentRequest::findOrFail($requestId);

                $ticketNo = $paymentRequest->paymentRequestData->module_type == 'ticket' || $paymentRequest->paymentRequestData->module_type == 'freight_payment' ? $paymentRequest->paymentRequestData->arrivalTicket->unique_no : $paymentRequest->paymentRequestData->purchaseTicket->unique_no;
                $truckNo = $paymentRequest->paymentRequestData->truck_no;
                $biltyNo = $paymentRequest->paymentRequestData->bilty_no;
                $supplierName = $paymentVoucher->supplier->name ?? 'Supplier';
                $amount = number_format($paymentRequest->amount, 2);

                $remarks = "A payment of Rs. {$amount} has been made to {$supplierName}";
                if ($bankName) {
                    $remarks .= " against bank '{$bankName}'";
                }
                if ($accountNumber) {
                    $remarks .= " with account number '{$accountNumber}'";
                }
                if ($request->voucher_type === 'bank_payment_voucher') {
                    $remarks .= " through bank transfer.";
                } else {
                    $remarks .= " in cash.";
                }

                $paymentRequestDataId = $paymentRequest->paymentRequestData->id;

                createTransaction(
                    $paymentRequest->amount,
                    $request->account_id,
                    1,
                    $uniqueNo,
                    'credit',
                    'no',
                    [
                        'purpose' => "$prefix-$paymentVoucher->id-$paymentVoucher->unique_no",
                        'payment_against' => "$ticketNo-$paymentRequestDataId",
                        'against_reference_no' => "$truckNo/$biltyNo",
                        'counter_account_id' => $paymentVoucher->supplier->account_id,
                        'remarks' => $remarks
                    ]
                );

                createTransaction(
                    $paymentRequest->amount,
                    $paymentVoucher->supplier->account_id,
                    1,
                    $uniqueNo,
                    'debit',
                    'no',
                    [
                        'purpose' => "$prefix-$paymentVoucher->id-$paymentVoucher->unique_no",
                        'payment_against' => "$ticketNo-$paymentRequestDataId",
                        'counter_account_id' => $request->account_id,
                        'against_reference_no' => "$truckNo/$biltyNo",
                        'remarks' => $remarks
                    ]
                );

                PaymentVoucherData::create([
                    'payment_voucher_id' => $paymentVoucher->id,
                    'payment_request_id' => $requestId,
                    'amount' => $paymentRequest->amount,
                    'description' => $paymentRequest->paymentRequestData->notes ?? 'No description'
                ]);

                $totalAmount += $paymentRequest->amount;
            }
            // dd($ticketNo);

            $paymentVoucher->update(['total_amount' => $totalAmount]);
        });

        return response()->json([
            'success' => 'Payment voucher created successfully!',
            'redirect' => route('payment-voucher.index')
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
            'remarks' => 'nullable|string'
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
                'total_amount' => 0
            ]);

            $totalAmount = 0;

            foreach ($request->payment_requests as $requestId) {
                $paymentRequest = PaymentRequest::findOrFail($requestId);

                PaymentVoucherData::create([
                    'payment_voucher_id' => $paymentVoucher->id,
                    'payment_request_id' => $requestId,
                    'amount' => $paymentRequest->amount,
                    'description' => $paymentRequest->paymentRequestData->notes ?? 'No description'
                ]);

                $totalAmount += $paymentRequest->amount;
            }

            $paymentVoucher->update(['total_amount' => $totalAmount]);
        });

        return response()->json([
            'success' => 'Payment voucher created successfully!',
            'redirect' => route('payment-voucher.index')
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
            'selectedRequests' => $paymentVoucher->paymentVoucherData->pluck('payment_request_id')->toArray()
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
            'account_id' => 'required|exists:accounts,account_id',
            'module_id' => 'required|exists:arrival_purchase_orders,id',
            'payment_requests' => 'required|array',
            'payment_requests.*' => 'exists:payment_requests,id',
            'ref_bill_no' => 'nullable|string',
            'bill_date' => 'nullable|date',
            'cheque_no' => 'nullable|required_if:voucher_type,bank_payment_voucher|string',
            'cheque_date' => 'nullable|required_if:voucher_type,bank_payment_voucher|date',
            'remarks' => 'nullable|string'
        ]);

        DB::transaction(function () use ($request, $paymentVoucher) {
            $paymentVoucher->update([
                'pv_date' => $request->pv_date,
                'ref_bill_no' => $request->ref_bill_no,
                'bill_date' => $request->bill_date,
                'cheque_no' => $request->cheque_no,
                'cheque_date' => $request->cheque_date,
                'account_id' => $request->account_id,
                'module_id' => $request->module_id,
                'remarks' => $request->remarks
            ]);

            PaymentVoucherData::where('payment_voucher_id', $paymentVoucher->id)->delete();

            $totalAmount = 0;

            foreach ($request->payment_requests as $requestId) {
                $paymentRequest = PaymentRequest::findOrFail($requestId);

                PaymentVoucherData::create([
                    'payment_voucher_id' => $paymentVoucher->id,
                    'payment_request_id' => $requestId,
                    'amount' => $paymentRequest->amount,
                    'description' => $paymentRequest->paymentRequestData->notes ?? 'No description'
                ]);

                $totalAmount += $paymentRequest->amount;
            }

            $paymentVoucher->update(['total_amount' => $totalAmount]);
        });

        return response()->json([
            'success' => 'Payment voucher updated successfully!',
            'redirect' => route('payment-voucher.index')
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $paymentVoucher = PaymentVoucher::findOrFail($id);
        $paymentVoucher->delete();

        return response()->json([
            'success' => 'Payment voucher deleted successfully!'
        ]);
    }
}
