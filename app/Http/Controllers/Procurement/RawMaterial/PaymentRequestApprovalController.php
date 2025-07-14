<?php

namespace App\Http\Controllers\Procurement\RawMaterial;

use App\Http\Controllers\Controller;
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
use App\Models\Master\ProductSlab;
use App\Models\Master\ProductSlabForRmPo;
use App\Models\PurchaseSamplingRequest;
use App\Models\PurchaseTicket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
            'paymentRequestData.purchaseTicket.purchaseOrder',
            'paymentRequestData.purchaseTicket.purchaseFreight',
            'approvals.approver'
        ])
            // ->whereHas('paymentRequestData', function ($q) {
            //     $q->whereHas('purchaseTicket', function ($q) {
            //         $q->whereHas('purchaseOrder', function ($q) {
            //             // $q->where('sauda_type_id', 2);
            //         });
            //     });
            // })
            // ->orderByDesc(
            //     PaymentRequest::select('id')
            //         ->whereColumn('payment_request_data_id', 'payment_requests.payment_request_data_id')
            //         ->orderBy('payment_request_data_id', 'desc')
            //         ->limit(1)
            // )
            ->orderBy('created_at', 'desc')
            ->paginate(25);

        return view('management.procurement.raw_material.payment_request_approval.getList', [
            'paymentRequests' => $paymentRequests
        ]);
    }

    public function store(PaymentRequestApprovalRequest $request)
    {
        return DB::transaction(function () use ($request) {
            $paymentRequest = PaymentRequest::findOrFail($request->payment_request_id);
            $paymentRequestData = $paymentRequest->paymentRequestData;

            $moduleType = $paymentRequestData->module_type;
            $ticket = $moduleType === 'ticket' ? $paymentRequestData->arrivalTicket : $paymentRequestData->purchaseTicket;
            $purchaseOrder = $ticket->purchaseOrder;
            $saudaType = $moduleType === 'ticket' ? 'pohanch' : 'thadda';

            $truckNo = $paymentRequestData->arrivalTicket->truck_no ?? $paymentRequestData->purchaseTicket->purchaseFreight->truck_no ?? 'N/A';
            $biltyNo = $paymentRequestData->arrivalTicket->bilty_no ?? $paymentRequestData->purchaseTicket->purchaseFreight->bilty_no ?? 'N/A';
            // dd($ticket->id, $ticket->unique_no, $purchaseOrder->supplier->account_id);
            if ($request->status === 'approved') {
                createTransaction(
                    (float)($request->payment_request_amount),
                    $purchaseOrder->supplier->account_id,
                    1, // for Purchase Order
                    $purchaseOrder->contract_no,
                    // $moduleType === 'ticket' ? $ticket->id : $paymentRequestData->purchase_order_id,
                    // $moduleType === 'ticket' ? $ticket->unique_no : $purchaseOrder->contract_no,
                    'credit',
                    'yes',
                    [
                        'payment_against' => "$saudaType-purchase",
                        'against_reference_no' => "$truckNo - $biltyNo",
                        'remarks' => 'Recording accounts payable for ' . ucwords($saudaType) . ' purchase. Amount to be paid to supplier.'
                    ]
                );
            }

            if ($request->has('total_amount') || $request->has('bag_weight')) {
                $this->updatePaymentRequestData($paymentRequestData, $request);
            }

            if ($request->has('sampling_results') || $request->has('compulsory_results') || $request->has('other_deduction')) {
                $this->updateSamplingResults($paymentRequestData, $request);
            }

            if ($request->has('payment_request_amount')) {
                $paymentRequest->update(['amount' => $request->payment_request_amount]);
            }

            if ($request->has('freight_pay_request_amount') && $request->freight_pay_request_amount > 0 && $moduleType !== 'ticket') {
                $freightRequest = PaymentRequest::where('payment_request_data_id', $paymentRequestData->id)
                    ->where('request_type', 'freight_payment')
                    ->first();

                if ($freightRequest) {
                    $freightRequest->update(['amount' => $request->freight_pay_request_amount]);
                } else {
                    PaymentRequest::create([
                        'payment_request_data_id' => $paymentRequestData->id,
                        'request_type' => 'freight_payment',
                        'other_deduction_kg' => $request->other_deduction['kg_value'] ?? 0,
                        'other_deduction_value' => $request->other_deduction['kg_amount'] ?? 0,
                        'amount' => $request->freight_pay_request_amount
                    ]);
                }
            }

            if ($request->has('bag_weight')) {
                $dataToUpdate = ['bag_weight' => $request->bag_weight];

                if ($moduleType !== 'ticket') {
                    $dataToUpdate = ['bag_weight' => (float) $request->bag_weight];
                }

                $ticket->update($dataToUpdate);
            }

            if ($request->has('other_deduction')) {
                $paymentRequest->update([
                    'other_deduction_kg' => $request->other_deduction['kg_value'] ?? 0,
                    'other_deduction_value' => $request->other_deduction['kg_amount'] ?? 0,
                ]);
            }

            PaymentRequestApproval::create([
                'payment_request_id' => $request->payment_request_id,
                'payment_request_data_id' => $paymentRequest->payment_request_data_id,
                'ticket_id' => $paymentRequestData->ticket_id,
                'purchase_order_id' => $purchaseOrder->id,
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

    private function updateSamplingResults($paymentRequestData, $request)
    {
        $paymentRequestData->samplingResults()->delete();

        if ($request->has('sampling_results')) {
            foreach ($request->sampling_results as $result) {
                PaymentRequestSamplingResult::create([
                    'payment_request_data_id' => $paymentRequestData->id,
                    'slab_type_id' => $result['slab_type_id'] ?? null,
                    'name' => $result['slab_name'] ?? '',
                    'checklist_value' => $result['checklist_value'] ?? 0,
                    'suggested_deduction' => $result['suggested_deduction'] ?? 0,
                    'applied_deduction' => $result['applied_deduction'] ?? 0,
                    'deduction_type' => $result['deduction_type'] ?? 'amount',
                    'deduction_amount' => $result['deduction_amount'] ?? 0,
                    'is_other_deduction' => false
                ]);
            }
        }

        if ($request->has('compulsory_results')) {
            foreach ($request->compulsory_results as $result) {
                PaymentRequestSamplingResult::create([
                    'payment_request_data_id' => $paymentRequestData->id,
                    'qc_param_id' => $result['qc_param_id'] ?? null,
                    'name' => $result['qc_name'] ?? '',
                    'applied_deduction' => $result['applied_deduction'] ?? 0,
                    'deduction_amount' => $result['deduction_amount'] ?? 0,
                ]);
            }
        }
    }

    public function edit($paymentRequestId)
    {
        $paymentRequest = PaymentRequest::with([
            // 'paymentRequestData',
            'paymentRequestData.purchaseOrder',
            'paymentRequestData.purchaseTicket.purchaseFreight',
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
        $ticket = null;

        if ($moduleType == 'ticket') {
            $ticket = $paymentRequest->paymentRequestData->arrivalTicket;
        } else {
            $ticket = $paymentRequest->paymentRequestData->purchaseTicket;
        }

        $purchaseOrder = $ticket->purchaseOrder;

        // dd($ticket, $purchaseOrder);

        $requestedAmount = PaymentRequest::whereHas('paymentRequestData', fn($q) => $q->where('ticket_id', $ticket->id))
            ->where('request_type', 'payment')
            ->where('module_type', $moduleType)->sum('amount');

        $approvedAmount = PaymentRequest::whereHas('paymentRequestData', fn($q) => $q->where('ticket_id', $ticket->id))
            ->where('request_type', 'payment')
            ->where('module_type', $moduleType)->where('status', 'approved')->sum('amount');

        $pRsSumForFreight = PaymentRequest::whereHas('paymentRequestData', function ($query) use ($ticket) {
            $query->where('ticket_id', $ticket->id);
        })
            ->where('request_type', 'freight_payment')
            ->where('module_type', $moduleType)
            ->sum('amount');

        $samplingRequest = null;
        $samplingRequestCompulsuryResults = collect();
        $samplingRequestResults = collect();
        $otherDeduction = null;

        if ($moduleType == 'ticket') {
            if ($ticket) {
                $samplingRequest = ArrivalSamplingRequest::where('arrival_ticket_id', $ticket->id)
                    ->whereIn('approved_status', ['approved', 'rejected'])
                    ->latest()
                    ->first();

                if ($samplingRequest) {
                    $rmPoSlabs = collect();

                    $samplingRequestCompulsuryResults = ArrivalSamplingResultForCompulsury::where('arrival_sampling_request_id', $samplingRequest->id)->get();
                    $samplingRequestResults = ArrivalSamplingResult::where('arrival_sampling_request_id', $samplingRequest->id)->get();

                    $productSlabCalculations = null;
                    if ($samplingRequest->arrival_product_id) {
                        $productSlabCalculations = ProductSlab::where('product_id', $samplingRequest->arrival_product_id)->get();
                    }

                    foreach ($samplingRequestResults as &$result) {
                        $matchingSlabs = [];
                        $result->rm_po_slabs = $rmPoSlabs->get($result->product_slab_type_id, []);

                        if ($productSlabCalculations) {
                            $matchingSlabs = $productSlabCalculations->where('product_slab_type_id', $result->product_slab_type_id)
                                ->values()
                                ->all();
                            if (!empty($matchingSlabs)) {
                                $result->deduction_type = $matchingSlabs[0]->deduction_type;
                            }
                        }
                        $result->matching_slabs = $matchingSlabs;
                    }
                }

                $otherDeduction = PaymentRequest::whereHas('paymentRequestData', function ($query) use ($ticket, $moduleType) {
                    $query->where('ticket_id', $ticket->id);
                    $query->where('module_type', $moduleType);
                })->select('other_deduction_kg', 'other_deduction_value')
                    ->latest()
                    ->first();
            }
            $requestPurchaseForm = view('management.procurement.raw_material.ticket_payment_request.snippets.requestPurchaseForm', [
                'arrivalTicket' => $ticket,
                'ticket' => $ticket,
                'purchaseOrder' => $purchaseOrder,
                'samplingRequest' => $samplingRequest,
                'samplingRequestCompulsuryResults' => $samplingRequestCompulsuryResults,
                'samplingRequestResults' => $samplingRequestResults,
                'requestedAmount' => $requestedAmount,
                'approvedAmount' => $approvedAmount,
                'pRsSumForFreight' => $pRsSumForFreight,
                'otherDeduction' => $otherDeduction,
                'isRequestApprovalPage' => true,
                'paymentRequest' => $paymentRequest
            ])->render();
        } else {
            if ($ticket) {
                $samplingRequest = PurchaseSamplingRequest::where('purchase_ticket_id', $ticket->id)
                    ->whereIn('approved_status', ['approved', 'rejected'])
                    ->latest()
                    ->first();

                if ($samplingRequest) {
                    $rmPoSlabs = collect();
                    if ($purchaseOrder && $samplingRequest->arrival_product_id) {
                        $rmPoSlabs = ProductSlabForRmPo::where('arrival_purchase_order_id', $purchaseOrder->id)
                            ->where('product_id', $samplingRequest->arrival_product_id)
                            ->get()
                            ->groupBy('product_slab_type_id');
                    }

                    $samplingRequestCompulsuryResults = PurchaseSamplingResultForCompulsury::where('purchase_sampling_request_id', $samplingRequest->id)->get();
                    $samplingRequestResults = PurchaseSamplingResult::where('purchase_sampling_request_id', $samplingRequest->id)->get();

                    $productSlabCalculations = null;
                    if ($samplingRequest->arrival_product_id) {
                        $productSlabCalculations = ProductSlab::where('product_id', $samplingRequest->arrival_product_id)->get();
                    }

                    foreach ($samplingRequestResults as &$result) {
                        $matchingSlabs = [];
                        $result->rm_po_slabs = $rmPoSlabs->get($result->product_slab_type_id, []);

                        if ($productSlabCalculations) {
                            $matchingSlabs = $productSlabCalculations->where('product_slab_type_id', $result->product_slab_type_id)
                                ->values()
                                ->all();
                            if (!empty($matchingSlabs)) {
                                $result->deduction_type = $matchingSlabs[0]->deduction_type;
                            }
                        }
                        $result->matching_slabs = $matchingSlabs;
                    }
                }

                $otherDeduction = PaymentRequest::whereHas('paymentRequestData', function ($query) use ($ticket, $moduleType) {
                    $query->where('ticket_id', $ticket->id);
                    $query->where('module_type', $moduleType);
                })->select('other_deduction_kg', 'other_deduction_value')
                    ->latest()
                    ->first();
            }
            $requestPurchaseForm = view('management.procurement.raw_material.payment_request.snippets.requestPurchaseForm', [
                'ticket' => $ticket,
                'purchaseOrder' => $purchaseOrder,
                'samplingRequest' => $samplingRequest,
                'samplingRequestCompulsuryResults' => $samplingRequestCompulsuryResults,
                'samplingRequestResults' => $samplingRequestResults,
                'requestedAmount' => $requestedAmount,
                'approvedAmount' => $approvedAmount,
                'pRsSumForFreight' => $pRsSumForFreight,
                'otherDeduction' => $otherDeduction,
                'isRequestApprovalPage' => true,
                'paymentRequest' => $paymentRequest
            ])->render();
        }

        return view('management.procurement.raw_material.payment_request_approval.snippets.approvalForm', [
            'paymentRequest' => $paymentRequest,
            'ticket' => $ticket,
            'isUpdated' => $isUpdated,
            'requestPurchaseForm' => $requestPurchaseForm,
            'approval' => $approval
        ]);
    }
}
