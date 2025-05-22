<?php

namespace App\Http\Controllers\Procurement\RawMaterial;

use App\Http\Controllers\Controller;
use App\Http\Requests\Procurement\PaymentRequestRequest;
use App\Models\Arrival\ArrivalSamplingRequest;
use App\Models\Arrival\ArrivalSamplingResult;
use App\Models\Arrival\ArrivalSamplingResultForCompulsury;
use App\Models\Arrival\ArrivalTicket;
use App\Models\Arrival\PurchaseSamplingResult;
use App\Models\Arrival\PurchaseSamplingResultForCompulsury;
use App\Models\ArrivalPurchaseOrder;
use App\Models\Master\ArrivalCompulsoryQcParam;
use App\Models\Master\ProductSlabType;
use App\Models\Procurement\PaymentRequest;
use App\Models\Procurement\PaymentRequestSamplingResult;
use App\Models\Product;
use Illuminate\Http\Request;
use App\Models\PurchaseSamplingRequest;
use App\Models\TruckSizeRange;
use Illuminate\Support\Facades\DB;

class PaymentRequestController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('management.procurement.raw_material.payment_request.index');
    }

    /**
     * Get list of categories.
     */
    public function getList(Request $request)
    {
        $pRs = PaymentRequest::orderBy('created_at', 'asc')
            ->paginate(request('per_page', 25));

        return view('management.procurement.raw_material.payment_request.getList', compact('pRs'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $data['purchaseOrders'] = ArrivalPurchaseOrder::get();
        $data['truckSizeRanges'] = TruckSizeRange::where('status', 'active')->get();
        $data['products'] = Product::where('product_type', 'raw_material')->get();

        return view('management.procurement.raw_material.payment_request.create', $data);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PaymentRequestRequest $request)
    {
        return DB::transaction(function () use ($request) {
            $requestData = $request->validated();
            $requestData['is_loading'] = $request->loading_type === 'loading';

            if ($request->freight_pay_request_amount && $request->freight_pay_request_amount > 0) {
                $paymentRequest = PaymentRequest::create(array_merge($requestData, [
                    'request_type' => 'payment'
                ]));

                $freightRequest = PaymentRequest::create(array_merge($requestData, [
                    'request_type' => 'freight_payment',
                    'payment_request_amount' => $request->freight_pay_request_amount
                ]));

                $this->saveSamplingResults($paymentRequest, $request);
                $this->saveSamplingResults($freightRequest, $request);

                $message = 'Payment and freight payment requests created successfully';
            } else {
                $paymentRequest = PaymentRequest::create(array_merge($requestData, [
                    'request_type' => 'payment'
                ]));

                $this->saveSamplingResults($paymentRequest, $request);
                $message = 'Payment request created successfully';
            }

            return response()->json(['success' => $message]);
        });
    }

    private function saveSamplingResults($paymentRequest, $request)
    {
        if ($request->sampling_results) {
            foreach ($request->sampling_results as $result) {
                PaymentRequestSamplingResult::create([
                    'payment_request_id' => $paymentRequest->id,
                    'slab_type_id' => $result['slab_type_id'],
                    'name' => $result['slab_name'],
                    'checklist_value' => $result['checklist_value'],
                    'suggested_deduction' => $result['suggested_deduction'],
                    'applied_deduction' => $result['applied_deduction'],
                    'deduction_type' => $result['suggested_deduction'] > 0 ? 'amount' : 'percentage',
                    'deduction_amount' => $result['deduction_amount']
                ]);
            }
        }

        // Save compulsory sampling results
        if ($request->compulsory_results) {
            foreach ($request->compulsory_results as $result) {
                PaymentRequestSamplingResult::create([
                    'payment_request_id' => $paymentRequest->id,
                    'slab_type_id' => $result['qc_param_id'],
                    'name' => $result['qc_name'],
                    'checklist_value' => 0,
                    'suggested_deduction' => 0,
                    'applied_deduction' => $result['applied_deduction'],
                    'deduction_type' => 'amount',
                    'deduction_amount' => $result['deduction_amount']
                ]);
            }
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $paymentRequest = PaymentRequest::with(['purchaseOrder', 'samplingResults'])->findOrFail($id);
        $pRsSum = PaymentRequest::where('purchase_order_id', $paymentRequest->purchase_order_id)
            ->where('request_type', $paymentRequest->request_type)
            ->sum('payment_request_amount');

        return view('management.procurement.raw_material.payment_request.edit', compact('paymentRequest', 'pRsSum'));
    }

    public function update(PaymentRequestRequest $request, $id)
    {
        return DB::transaction(function () use ($request, $id) {
            $paymentRequest = PaymentRequest::findOrFail($id);
            $requestData = $request->validated();
            $requestData['is_loading'] = $request->loading_type === 'loading';

            $paymentRequest->update($requestData);

            // if ($request->sampling_results) {
            //     foreach ($request->sampling_results as $id => $result) {
            //         $updateData = $result;
            //         $updateData['name'] = $result['slab_name'];
            //         unset($updateData['slab_name']);

            //         PaymentRequestSamplingResult::where('id', $id)
            //             ->where('payment_request_id', $paymentRequest->id)
            //             ->update($updateData);
            //     }
            // }

            // if ($request->compulsory_results) {
            //     foreach ($request->compulsory_results as $id => $result) {
            //         PaymentRequestSamplingResult::where('id', $id)
            //             ->where('payment_request_id', $paymentRequest->id)
            //             ->update($result);
            //     }
            // }

            return response()->json(['success' => 'Payment request updated successfully']);
        });
    }

    public function updateStatus(Request $request)
    {

        $request->validate([
            'request_id' => 'required|exists:arrival_sampling_requests,id',
            'status' => 'required|in:approved,rejected,resampling'
        ]);

        $sampling = PurchaseSamplingRequest::find($request->request_id);

        if ($request->status == 'resampling') {

            PurchaseSamplingRequest::create([
                'company_id' => $sampling->company_id,
                'purchase_contract' => $sampling->purchase_contract,
                'sampling_type' => 'initial',
                'is_re_sampling' => 'yes',
                'is_done' => 'no',
                'remark' => null,
            ]);
            $sampling->is_resampling_made = 'yes';
        }



        $sampling->approved_status = $request->status;
        $sampling->save();


        //$sampling = PurchaseSamplingRequest::find($request->request_id);


        return response()->json(['message' => 'Request status updated successfully!']);
    }

    // public function getSlabsByProduct(Request $request)
    // {
    //     if (!isset($request->po_id)) {
    //         return response()->json(['success' => false, 'html' => '']);
    //     }

    //     $arrivalSamplingRequest = ArrivalSamplingRequest::findOrFail($request->po_id);
    //     $compulsoryParams  = ArrivalCompulsoryQcParam::get();

    //     $html = view('management.procurement.raw_material.payment_request.snippets.requestPurchaseForm', compact('slabs', 'compulsoryParams'))->render();

    //     return response()->json(['success' => true, 'html' => $html]);
    // }


    public function getSlabsByPaymentRequestParams(Request $request)
    {
        $purchaseOrder = ArrivalPurchaseOrder::findOrFail($request->purchase_order_id);

        $pRsSum = PaymentRequest::where('purchase_order_id', $purchaseOrder->id)
            ->where('request_type', 'payment')
            ->sum('payment_request_amount');

        $purchaseOrders = ArrivalPurchaseOrder::where('freight_status', 'completed')->get();

        $samplingRequest = null;
        $samplingRequestCompulsuryResults = collect();
        $samplingRequestResults = collect();

        if ($purchaseOrder) {
            $samplingRequest = PurchaseSamplingRequest::where('arrival_purchase_order_id', $request->purchase_order_id)
                ->whereIn('approved_status', ['approved', 'rejected'])
                ->latest()
                ->first();

            if ($samplingRequest) {
                $samplingRequestCompulsuryResults = PurchaseSamplingResultForCompulsury::where('purchase_sampling_request_id', $samplingRequest->id)->get();
                $samplingRequestResults = PurchaseSamplingResult::where('purchase_sampling_request_id', $samplingRequest->id)->get();
            }
        }

        $html = view('management.procurement.raw_material.payment_request.snippets.requestPurchaseForm', [
            'purchaseOrders' => $purchaseOrders,
            'purchaseOrder' => $purchaseOrder,
            'samplingRequest' => $samplingRequest,
            'samplingRequestCompulsuryResults' => $samplingRequestCompulsuryResults,
            'samplingRequestResults' => $samplingRequestResults,
            'pRsSum' => $pRsSum
        ])->render();

        return response()->json(['success' => true, 'html' => $html]);
    }
}
