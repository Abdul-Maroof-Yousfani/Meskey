<?php

namespace App\Http\Controllers\Procurement\RawMaterial;

use App\Http\Controllers\Controller;
use App\Http\Requests\Procurement\AdvancePaymentRequestApprovalRequest;
use App\Http\Requests\Procurement\PaymentRequestApprovalRequest;
use App\Models\Arrival\ArrivalSamplingRequest;
use App\Models\Arrival\ArrivalSamplingResult;
use App\Models\Arrival\ArrivalSamplingResultForCompulsury;
use App\Models\Arrival\PurchaseSamplingResult;
use App\Models\Arrival\PurchaseSamplingResultForCompulsury;
use App\Models\Procurement\PaymentRequest;
use App\Models\Procurement\PaymentRequestApproval;
use App\Models\Procurement\PaymentRequestData;
use App\Models\Procurement\PaymentRequestSamplingResult;
use App\Models\ArrivalPurchaseOrder;
use App\Models\Master\Account\Account;
use App\Models\Master\Account\Transaction;
use App\Models\Master\Broker;
use App\Models\Master\ProductSlab;
use App\Models\Master\ProductSlabForRmPo;
use App\Models\PurchaseSamplingRequest;
use App\Models\PurchaseTicket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AdvancePaymentRequestApprovalController extends Controller
{
    public function index()
    {
        return view('management.procurement.raw_material.payment_request_approval.index');
    }

    public function getList(Request $request)
    {
        $paymentRequests = PaymentRequest::with([
            'paymentRequestData.purchaseOrder',
            'paymentRequestData.purchaseTicket.purchaseOrder',
            'paymentRequestData.purchaseTicket.purchaseFreight',
            'paymentRequestData.arrivalTicket.freight',
            'approvals.approver'
        ])
            ->when($request->filled('company_location_id'), function ($q) use ($request) {
                return $q->whereHas('paymentRequestData.purchaseOrder', function ($query) use ($request) {
                    $query->where('company_location_id', $request->company_location_id);
                });
            })
            ->when($request->filled('supplier_id'), function ($q) use ($request) {
                return $q->whereHas('paymentRequestData.purchaseOrder', function ($query) use ($request) {
                    $query->where('supplier_id', $request->supplier_id);
                });
            })
            ->when($request->filled('daterange'), function ($q) use ($request) {
                $dates = explode(' - ', $request->daterange);
                $startDate = \Carbon\Carbon::createFromFormat('m/d/Y', trim($dates[0]))->format('Y-m-d');
                $endDate = \Carbon\Carbon::createFromFormat('m/d/Y', trim($dates[1]))->format('Y-m-d');

                return $q->whereDate('created_at', '>=', $startDate)
                    ->whereDate('created_at', '<=', $endDate);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(25);

        $paymentRequests->getCollection()->transform(function ($request) {
            $freightData = null;

            if ($request->module_type === 'purchase_order') {
                $freightData = optional($request->paymentRequestData)->purchaseTicket->purchaseFreight ?? null;
            } else {
                $freightData = optional($request->paymentRequestData)->arrivalTicket->freight ?? null;
            }

            $request->freight_data = $freightData;

            return $request;
        });

        return view('management.procurement.raw_material.payment_request_approval.getList', [
            'paymentRequests' => $paymentRequests
        ]);
    }

    public function store(AdvancePaymentRequestApprovalRequest $request)
    {
        return DB::transaction(function () use ($request) {
            $paymentRequest = PaymentRequest::findOrFail($request->payment_request_id);
            $paymentRequestData = $paymentRequest->paymentRequestData;

            $purchaseOrder = $paymentRequestData->purchaseOrder;

            if ($request->has('total_amount') || $request->has('bag_weight')) {
                $this->updatePaymentRequestData($paymentRequestData, $request);
            }

            if ($request->has('payment_request_amount')) {
                $paymentRequest->update(['amount' => $request->payment_request_amount]);
            }

            PaymentRequestApproval::create([
                'payment_request_id' => $request->payment_request_id,
                'payment_request_data_id' => $paymentRequest->payment_request_data_id,
                'ticket_id' => Null,
                'purchase_order_id' => $purchaseOrder->id,
                'approver_id' => auth()->user()->id,
                'status' => $request->status,
                'remarks' => $request->remarks,
                'amount' => $paymentRequest->amount,
                'request_type' => $paymentRequest->request_type
            ]);

            $paymentRequest->update(['status' => $request->status]);

            return response()->json([
                'success' => 'Payment request ' . $request->status . ' successfully!'
            ]);
        });
    }

    private function updatePaymentRequestData($paymentRequestData, $request)
    {
        $updateData = [];

        if ($request->has('total_amount')) {
            $updateData['total_amount'] = $request->total_amount;
        }

        if ($request->has('supplier_name')) {
            $updateData['supplier_name'] = $request->supplier_name;
        }

        if ($request->has('bag_weight')) {
            $updateData['bag_weight'] = $request->bag_weight;
        }

        if ($request->has('bag_rate')) {
            $updateData['bag_rate'] = $request->bag_rate;
        }

        if ($request->has('bag_weight_amount')) {
            $updateData['bag_weight_amount'] = $request->bag_weight_amount;
        }

        if ($request->has('loading_weighbridge_amount')) {
            $updateData['loading_weighbridge_amount'] = $request->loading_weighbridge_amount;
        }

        if ($request->has('bag_rate_amount')) {
            $updateData['bag_rate_amount'] = $request->bag_rate_amount;
        }

        if (!empty($updateData)) {
            $paymentRequestData->update($updateData);
        }
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

        $moduleType = $paymentRequest->paymentRequestData->module_type;

        $purchaseOrder =  $paymentRequest->paymentRequestData->purchaseOrder;

        $requestedAmount = PaymentRequest::whereHas('paymentRequestData', function ($q) use ($paymentRequest, $purchaseOrder) {
            $q->where('is_advance_payment', 1)
                ->where('purchase_order_id', $purchaseOrder);
        })
            ->where('request_type', 'payment')
            ->where('module_type', $moduleType)
            ->sum('amount');

        $approvedAmount = PaymentRequest::whereHas('paymentRequestData', function ($q) use ($paymentRequest, $purchaseOrder) {
            $q->where('is_advance_payment', 1)
                ->where('purchase_order_id', $purchaseOrder);
        })
            ->where('request_type', 'payment')
            ->where('module_type', $moduleType)
            ->where('status', 'approved')
            ->sum('amount');

        $paymentRequestData = $paymentRequest->paymentRequestData;

        $requestPurchaseForm = view('management.procurement.raw_material.advance_payment_request.snippets.requestPurchaseForm', [
            'purchaseOrder' => $purchaseOrder,
            'paymentRequestData' => $paymentRequestData,
            'requestedAmount' => $requestedAmount,
            'approvedAmount' => $approvedAmount,
            'isRequestApprovalPage' => true,
            'paymentRequest' => $paymentRequest
        ])->render();

        return view('management.procurement.raw_material.payment_request_approval.snippets.approvalForm', [
            'paymentRequest' => $paymentRequest,
            'isUpdated' => $isUpdated,
            'requestPurchaseForm' => $requestPurchaseForm,
            'approval' => $approval
        ]);
    }
}
