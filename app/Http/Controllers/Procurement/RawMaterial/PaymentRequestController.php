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
use App\Models\Master\ProductSlab;
use App\Models\Master\ProductSlabForRmPo;
use App\Models\Master\ProductSlabType;
use App\Models\Procurement\PaymentRequest;
use App\Models\Procurement\PaymentRequestData;
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
        $paymentRequestsData = PaymentRequestData::with(['purchaseOrder', 'paymentRequests'])
            ->orderBy('created_at', 'asc')
            ->paginate(request('per_page', 25));

        return view('management.procurement.raw_material.payment_request.getList', [
            'paymentRequestsData' => $paymentRequestsData
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $data['purchaseOrders'] = ArrivalPurchaseOrder::where('sauda_type_id', 2)->get();
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
            // Prepare base data
            $requestData = $request->validated();
            $requestData['is_loading'] = $request->loading_type === 'loading';

            // Calculate remaining amount
            $requestData['remaining_amount'] = $requestData['total_amount'] -
                ($requestData['paid_amount'] ?? 0) -
                ($requestData['payment_request_amount'] ?? 0) -
                ($requestData['freight_pay_request_amount'] ?? 0);

            // Create main payment request data
            $paymentRequestData = PaymentRequestData::create($requestData);

            // Create payment request records
            $this->createPaymentRequests($paymentRequestData, $request);

            // Save sampling results if exists
            if (isset($request->sampling_results) || isset($request->compulsory_results)) {
                $this->saveSamplingResults($paymentRequestData, $request);
            }

            $message = $request->freight_pay_request_amount ?
                'Payment and freight payment requests created successfully' :
                'Payment request created successfully';

            return response()->json(['success' => $message]);
        });
    }

    private function saveSamplingResults($paymentRequest, $request)
    {
        if ($request->sampling_results) {
            foreach ($request->sampling_results as $result) {
                PaymentRequestSamplingResult::create([
                    'payment_request_data_id' => $paymentRequest->id,
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
                    'payment_request_data_id' => $paymentRequest->id,
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

    public function update(PaymentRequestRequest $request, $id)
    {
        return DB::transaction(function () use ($request, $id) {
            $paymentRequestData = PaymentRequestData::findOrFail($id);

            // Prepare update data
            $requestData = $request->validated();
            $requestData['is_loading'] = $request->loading_type === 'loading';

            // Calculate remaining amount
            $requestData['remaining_amount'] = $requestData['total_amount'] -
                ($requestData['paid_amount'] ?? 0) -
                ($requestData['payment_request_amount'] ?? 0) -
                ($requestData['freight_pay_request_amount'] ?? 0);

            // Update main data
            $paymentRequestData->update($requestData);

            // Delete existing payment requests and create new ones
            $paymentRequestData->paymentRequests()->delete();
            $this->createPaymentRequests($paymentRequestData, $request);

            // Update sampling results if exists
            if (isset($request->sampling_results)) {
                $this->updateSamplingResults($paymentRequestData, $request);
            }

            return response()->json(['success' => 'Payment request updated successfully']);
        });
    }

    protected function createPaymentRequests($paymentRequestData, $request)
    {
        // Always create payment request
        PaymentRequest::create([
            'payment_request_data_id' => $paymentRequestData->id,
            'request_type' => 'payment',
            'amount' => $request->payment_request_amount ?? 0
        ]);

        // Create freight payment request if amount exists
        if ($request->freight_pay_request_amount && $request->freight_pay_request_amount > 0) {
            PaymentRequest::create([
                'payment_request_data_id' => $paymentRequestData->id,
                'request_type' => 'freight_payment',
                'amount' => $request->freight_pay_request_amount
            ]);
        }
    }

    protected function updateSamplingResults($paymentRequestData, $request)
    {
        // Delete existing results
        $paymentRequestData->samplingResults()->delete();

        // Save sampling results
        if (isset($request->sampling_results)) {
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
                ]);
            }
        }

        // Save compulsory results
        if (isset($request->compulsory_results)) {
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

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $paymentRequestData = PaymentRequestData::with([
            'purchaseOrder',
            'samplingResults.slabType', // Eager load slabType relationship
            'paymentRequests'
        ])->findOrFail($id);

        $pRsSum = PaymentRequest::whereHas('paymentRequestData', function ($query) use ($paymentRequestData) {
            $query->where('purchase_order_id', $paymentRequestData->purchase_order_id);
        })
            ->where('request_type', 'payment')
            ->sum('amount');

        $pRsSumForFreight = PaymentRequest::whereHas('paymentRequestData', function ($query) use ($paymentRequestData) {
            $query->where('purchase_order_id', $paymentRequestData->purchase_order_id);
        })
            ->where('request_type', 'freight_payment')
            ->sum('amount');

        $paymentRequest = $paymentRequestData->paymentRequests->where('request_type', 'payment')->first();
        $freightRequest = $paymentRequestData->paymentRequests->where('request_type', 'freight_payment')->first();

        return view('management.procurement.raw_material.payment_request.edit', [
            'paymentRequestData' => $paymentRequestData,
            'paymentRequest' => $paymentRequest,
            'freightRequest' => $freightRequest,
            'pRsSum' => $pRsSum,
            'pRsSumForFreight' => $pRsSumForFreight,
            'samplingResults' => $paymentRequestData->samplingResults // Pass samplingResults to view
        ]);
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

        // Calculate sum of payment requests for this purchase order
        $pRsSum = PaymentRequest::whereHas('paymentRequestData', function ($query) use ($purchaseOrder) {
            $query->where('purchase_order_id', $purchaseOrder->id);
        })
            ->where('request_type', 'payment')
            ->sum('amount');


        $pRsSumForFreight = PaymentRequest::whereHas('paymentRequestData', function ($query) use ($purchaseOrder) {
            $query->where('purchase_order_id', $purchaseOrder->id);
        })
            ->where('request_type', 'freight_payment')
            ->sum('amount');

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
        }
        $samplingRequestResults = $samplingRequestResults->filter(function ($result) {
            return $result->applied_deduction > 0;
        });
        dd($samplingRequestResults, $rmPoSlabs);
        $html = view('management.procurement.raw_material.payment_request.snippets.requestPurchaseForm', [
            'purchaseOrders' => $purchaseOrders,
            'purchaseOrder' => $purchaseOrder,
            'samplingRequest' => $samplingRequest,
            'samplingRequestCompulsuryResults' => $samplingRequestCompulsuryResults,
            'samplingRequestResults' => $samplingRequestResults,
            'pRsSum' => $pRsSum,
            'pRsSumForFreight' => $pRsSumForFreight,
        ])->render();

        return response()->json(['success' => true, 'html' => $html]);
    }
}
