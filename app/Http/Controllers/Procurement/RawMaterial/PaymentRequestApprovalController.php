<?php

namespace App\Http\Controllers\Procurement\RawMaterial;

use App\Http\Controllers\Controller;
use App\Models\Procurement\PaymentRequest;
use App\Models\Procurement\PaymentRequestApproval;
use App\Models\Procurement\PaymentRequestData;
use App\Models\ArrivalPurchaseOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PaymentRequestApprovalController extends Controller
{
    public function index()
    {
        return view('management.procurement.raw_material.payment_request_approval.index');
    }

    public function getList(Request $request)
    {
        $paymentRequests = PaymentRequest::with([
            'paymentRequestData.purchaseOrder',
            'approvals.approver'
        ])
            ->whereHas('paymentRequestData', function ($q) {
                $q->whereHas('purchaseOrder', function ($q) {
                    $q->where('sauda_type_id', 2);
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('management.procurement.raw_material.payment_request_approval.getList', [
            'paymentRequests' => $paymentRequests
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'remarks' => 'nullable|string',
            'payment_request_id' => 'required|exists:payment_requests,id',
            'status' => 'required|in:approved,rejected'
        ]);

        $paymentRequest = PaymentRequest::findOrFail($request->payment_request_id);

        PaymentRequestApproval::create([
            'payment_request_id' => $request->payment_request_id,
            'payment_request_data_id' => $paymentRequest->payment_request_data_id,
            'purchase_order_id' => $paymentRequest->paymentRequestData->purchase_order_id,
            'approver_id' => Auth::id(),
            'status' => $request->status,
            'remarks' => $request->remarks,
            'amount' => $paymentRequest->amount,
            'request_type' => $paymentRequest->request_type
        ]);

        $paymentRequest->update(['status' => $request->status]);

        return response()->json([
            'success' => 'Payment request ' . $request->status . ' successfully!'
        ]);
    }

    public function edit($paymentRequestId)
    {
        $paymentRequest = PaymentRequest::with([
            'paymentRequestData.purchaseOrder',
        ])->findOrFail($paymentRequestId);

        $isUpdated = 0;
        $approval = null;

        if (!$paymentRequest->canBeApproved()) {
            $approval = PaymentRequestApproval::where('payment_request_id', $paymentRequestId)
                ->latest()
                ->first();

            $isUpdated = 1;
        }

        return view('management.procurement.raw_material.payment_request_approval.snippets.approvalForm', [
            'paymentRequest' => $paymentRequest,
            'isUpdated' => $isUpdated,
            'approval' => $approval
        ]);
    }
}
