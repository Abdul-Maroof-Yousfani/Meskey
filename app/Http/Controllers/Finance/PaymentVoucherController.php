<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\ArrivalPurchaseOrder;
use App\Models\Master\Account\Account;
use App\Models\PaymentVoucher;
use App\Models\Procurement\PaymentRequest;
use App\Models\PaymentVoucherData;
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
        $data['accounts'] = Account::all();
        $data['purchaseOrders'] = ArrivalPurchaseOrder::with(['product'])->latest()->get();
        return view('management.finance.payment_voucher.create', $data);
    }

    /**
     * Generate PV number
     */
    public function generatePvNumber(Request $request)
    {
        $request->validate([
            'voucher_type' => 'required|in:bank_payment_voucher,cash_payment_voucher',
            'pv_date' => 'required|date'
        ]);

        $prefix = $request->voucher_type === 'bank_payment_voucher' ? 'BPV' : 'CPV';
        $date = Carbon::parse($request->pv_date);

        $count = PaymentVoucher::whereYear('pv_date', $date->year)
            ->whereMonth('pv_date', $date->month)
            ->count() + 1;

        $uniqueNo = $prefix . '-' . $date->format('Ymd') . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);

        return response()->json([
            'success' => true,
            'pv_number' => $uniqueNo
        ]);
    }

    /**
     * Get payment requests for purchase order
     */
    public function getPaymentRequests($purchaseOrderId)
    {
        $purchaseOrder = ArrivalPurchaseOrder::with('supplier')->findOrFail($purchaseOrderId);

        $supplier = $purchaseOrder->supplier;

        $companyBankAccounts = $supplier ? $supplier->companyBankDetails : collect();
        $ownerBankAccounts = $supplier ? $supplier->ownerBankDetails : collect();

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
            ->whereHas('paymentRequestData', function ($q) use ($purchaseOrderId) {
                $q->where('purchase_order_id', $purchaseOrderId);
            })
            ->whereDoesntHave('paymentVoucherData')
            ->where('status', 'approved')
            ->get()
            ->map(function ($request) {
                return [
                    'id' => $request->id,
                    'supplier_id' => $request->paymentRequestData->purchaseOrder->supplier_id ?? null,
                    'purchaseOrder' => $request->paymentRequestData->purchaseOrder,
                    'request_no' => $request->paymentRequestData->purchaseOrder->contract_no ?? 'N/A',
                    'amount' => $request->amount,
                    'purpose' => $request->paymentRequestData->notes ?? 'No description',
                    'status' => $request->approval_status,
                    'type' => formatEnumValue($request->request_type),
                    'request_date' => $request->created_at->format('Y-m-d')
                ];
            });
        // dd($paymentRequests);
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
            'unique_no' => 'required|unique:payment_vouchers',
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
            $paymentVoucher = PaymentVoucher::create([
                'unique_no' => $request->unique_no,
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
