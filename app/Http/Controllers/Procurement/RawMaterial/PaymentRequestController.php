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
use App\Models\Master\Supplier;
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
        $isTicket = str()->contains(request()->route()->getName(), '.ticket.');

        return view('management.procurement.raw_material.payment_request.index', ['isTicket' => $isTicket]);
    }

    /**
     * Get list of categories.
     */
    public function getList(Request $request)
    {
        $isTicket = str()->contains(request()->route()->getName(), 'ticket.');

        if ($isTicket) {
            // Handle ticket route case
            $query = ArrivalTicket::with([
                'purchaseOrder',
                'paymentRequestData.paymentRequests',
                'paymentRequestData.paymentRequests.approvals',
                'supplier',
                'product',
                'qcProduct',
                'freight',
                'paymentRequestData' => function ($query) {
                    $query->with(['paymentRequests' => function ($q) {
                        $q->selectRaw('payment_request_data_id, request_type, status, SUM(amount) as total_amount')
                            ->groupBy('payment_request_data_id', 'request_type', 'status');
                    }]);
                }
            ])->whereHas('purchaseOrder'); // Only tickets with contracts attached

            if ($request->has('supplier_id') && $request->supplier_id != '') {
                $query->whereHas('purchaseOrder', function ($q) use ($request) {
                    $q->where('supplier_id', $request->supplier_id);
                });
            }

            if ($request->has('product_id') && $request->product_id != '') {
                $query->where('qc_product', $request->product_id);
            }

            if ($request->has('search') && $request->search != '') {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('unique_no', 'like', "%{$search}%")
                        ->orWhere('truck_no', 'like', "%{$search}%")
                        ->orWhereHas('purchaseOrder', function ($q) use ($search) {
                            $q->where('contract_no', 'like', "%{$search}%")
                                ->orWhere('ref_no', 'like', "%{$search}%");
                        })
                        ->orWhereHas('purchaseOrder.supplier', function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%");
                        })
                        ->orWhereHas('product', function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%");
                        });
                });
            }

            $tickets = $query->paginate($request->per_page ?? 10);

            $tickets->getCollection()->transform(function ($ticket) {
                $approvedPaymentSum = 0;
                $approvedFreightSum = 0;
                $totalPaymentSum = 0;
                $totalFreightSum = 0;
                $totalAmount = 0;
                $paidAmount = 0;
                $remainingAmount = 0;

                foreach ($ticket->paymentRequestData as $data) {
                    $totalAmount = $data->total_amount ?? 0;
                    $paidAmount = $data->paid_amount ?? 0;

                    foreach ($data->paymentRequests as $pRequest) {
                        if ($pRequest->request_type == 'payment') {
                            $totalPaymentSum += $pRequest->total_amount;
                            if ($pRequest->status == 'approved') {
                                $approvedPaymentSum += $pRequest->total_amount;
                            }
                        } else {
                            $totalFreightSum += $pRequest->total_amount;
                            if ($pRequest->status == 'approved') {
                                $approvedFreightSum += $pRequest->total_amount;
                            }
                        }
                    }
                }

                $remainingAmount = ($totalAmount - $approvedPaymentSum);

                $ticket->calculated_values = [
                    'total_payment_sum' => $totalPaymentSum,
                    'total_freight_sum' => $totalFreightSum,
                    'approved_payment_sum' => $approvedPaymentSum,
                    'approved_freight_sum' => $approvedFreightSum,
                    'total_amount' => $totalAmount,
                    'paid_amount' => $paidAmount,
                    'remaining_amount' => $remainingAmount,
                    'created_at' => $ticket->paymentRequestData->first()->created_at ?? $ticket->created_at
                ];

                return $ticket;
            });

            return view('management.procurement.raw_material.payment_request.getList', [
                'purchaseOrders' => $tickets,
                'isTicket' => true
            ]);
        } else {
            // Original purchase order code
            $query = ArrivalPurchaseOrder::where('sauda_type_id', 2)
                ->with([
                    'paymentRequestData.paymentRequests',
                    'paymentRequestData.paymentRequests.approvals',
                    'supplier',
                    'product',
                    'qcProduct',
                    'purchaseFreights',
                    'paymentRequestData' => function ($query) {
                        $query->with(['paymentRequests' => function ($q) {
                            $q->selectRaw('payment_request_data_id, request_type, status, SUM(amount) as total_amount')
                                ->groupBy('payment_request_data_id', 'request_type', 'status');
                        }]);
                    }
                ]);

            if ($request->has('supplier_id') && $request->supplier_id != '') {
                $query->where('supplier_id', $request->supplier_id);
            }

            if ($request->has('product_id') && $request->product_id != '') {
                $query->where('qc_product', $request->product_id);
            }

            if ($request->has('search') && $request->search != '') {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('contract_no', 'like', "%{$search}%")
                        ->orWhere('ref_no', 'like', "%{$search}%")
                        ->orWhereHas('supplier', function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%");
                        })
                        ->orWhereHas('product', function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%");
                        });
                });
            }

            $purchaseOrders = $query->paginate($request->per_page ?? 10);

            $purchaseOrders->getCollection()->transform(function ($po) {
                $approvedPaymentSum = 0;
                $approvedFreightSum = 0;
                $totalPaymentSum = 0;
                $totalFreightSum = 0;
                $totalAmount = 0;
                $paidAmount = 0;
                $remainingAmount = 0;

                foreach ($po->paymentRequestData as $data) {
                    $totalAmount = $data->total_amount ?? 0;
                    $paidAmount = $data->paid_amount ?? 0;

                    foreach ($data->paymentRequests as $pRequest) {
                        if ($pRequest->request_type == 'payment') {
                            $totalPaymentSum += $pRequest->total_amount;
                            if ($pRequest->status == 'approved') {
                                $approvedPaymentSum += $pRequest->total_amount;
                            }
                        } else {
                            $totalFreightSum += $pRequest->total_amount;
                            if ($pRequest->status == 'approved') {
                                $approvedFreightSum += $pRequest->total_amount;
                            }
                        }
                    }
                }

                $remainingAmount = ($totalAmount - $approvedPaymentSum);

                $po->calculated_values = [
                    'total_payment_sum' => $totalPaymentSum,
                    'total_freight_sum' => $totalFreightSum,
                    'approved_payment_sum' => $approvedPaymentSum,
                    'approved_freight_sum' => $approvedFreightSum,
                    'total_amount' => $totalAmount,
                    'paid_amount' => $paidAmount,
                    'remaining_amount' => $remainingAmount,
                    'created_at' => $po->paymentRequestData->first()->created_at ?? $po->created_at
                ];

                return $po;
            });

            return view('management.procurement.raw_material.payment_request.getList', [
                'purchaseOrders' => $purchaseOrders,
                'isTicket' => false
            ]);
        }
    }
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $data['purchaseOrders'] = ArrivalPurchaseOrder::where('sauda_type_id', 2)->get();
        $data['truckSizeRanges'] = TruckSizeRange::where('status', 'active')->get();
        $data['products'] = Product::where('product_type', 'raw_material')->get();
        $data['isTicket'] = str()->contains(request()->route()->getName(), '.ticket.');

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
                    'deduction_amount' => $result['deduction_amount'],
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
                    'deduction_amount' => $result['deduction_amount'],
                ]);
            }
        }

        if ($request->other_deduction && isset($request->other_deduction['kg_value']) && $request->other_deduction['kg_value'] > 0) {
            // PaymentRequestSamplingResult::create([
            //     'payment_request_data_id' => $paymentRequest->id,
            //     'slab_type_id' => null,
            //     'name' => $request->other_deduction['slab_name'] ?? 'Other Deduction',
            //     'checklist_value' => 0,
            //     'suggested_deduction' => 0,
            //     'applied_deduction' => $request->other_deduction['kg_value'],
            //     'deduction_type' => 'kg',
            //     'deduction_amount' => $request->other_deduction['deduction_amount'],
            //     'is_other_deduction' => true,
            //     'kg_value' => $request->other_deduction['kg_value']
            // ]);
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
            if (isset($request->sampling_results) || isset($request->compulsory_results) || isset($request->other_deduction)) {
                $this->updateSamplingResults($paymentRequestData, $request);
            }

            return response()->json(['success' => 'Payment request updated successfully']);
        });
    }

    protected function createPaymentRequests($paymentRequestData, $request)
    {
        // dd([
        //     'payment_request_data_id' => $paymentRequestData->id,
        //     'other_deduction_kg' => $request->other_deduction['kg_value'] ?? 0,
        //     'other_deduction_value' => $request->other_deduction['kg_amount'] ?? 0,
        //     'request_type' => 'payment',
        //     'amount' => $request->payment_request_amount ?? 0
        // ]);

        if (isset($request->arrival_purchase_order_id)) {
            $result = ArrivalPurchaseOrder::find($request->arrival_purchase_order_id)->update(['bag_weight' => $request->bag_weight]);
        }

        PaymentRequest::create([
            'payment_request_data_id' => $paymentRequestData->id,
            'other_deduction_kg' => $request->other_deduction['kg_value'] ?? 0,
            'other_deduction_value' => $request->other_deduction['kg_amount'] ?? 0,
            'request_type' => 'payment',
            'amount' => $request->payment_request_amount ?? 0
        ]);

        if ($request->freight_pay_request_amount && $request->freight_pay_request_amount > 0) {
            PaymentRequest::create([
                'payment_request_data_id' => $paymentRequestData->id,
                'request_type' => 'freight_payment',
                'other_deduction_kg' => $request->other_deduction['kg_value'] ?? 0,
                'other_deduction_value' => $request->other_deduction['kg_amount'] ?? 0,
                'amount' => $request->freight_pay_request_amount
            ]);
        }
    }

    protected function updateSamplingResults($paymentRequestData, $request)
    {
        $paymentRequestData->samplingResults()->delete();

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
                    'is_other_deduction' => false
                ]);
            }
        }

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

        // Save other deduction
        // if (isset($request->other_deduction) && isset($request->other_deduction['kg_value']) && $request->other_deduction['kg_value'] > 0) {
        //     PaymentRequestSamplingResult::create([
        //         'payment_request_data_id' => $paymentRequestData->id,
        //         'slab_type_id' => null,
        //         'name' => $request->other_deduction['slab_name'] ?? 'Other Deduction',
        //         'checklist_value' => 0,
        //         'suggested_deduction' => 0,
        //         'applied_deduction' => $request->other_deduction['kg_value'],
        //         'deduction_type' => 'kg',
        //         'deduction_amount' => $request->other_deduction['deduction_amount'],
        //         'is_other_deduction' => true,
        //         'kg_value' => $request->other_deduction['kg_value']
        //     ]);
        // }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $data['purchaseOrder'] = $purchaseOrder = ArrivalPurchaseOrder::findOrFail($id);
        $data['truckSizeRanges'] = TruckSizeRange::where('status', 'active')->get();
        $data['products'] = Product::where('product_type', 'raw_material')->get();
        $data['isTicket'] = str()->contains(request()->route()->getName(), '.ticket.');

        $pRsSum = PaymentRequest::whereHas('paymentRequestData', function ($query) use ($purchaseOrder) {
            $query->where('purchase_order_id', $purchaseOrder->id);
        })
            ->where('request_type', 'payment')
            // ->where('status', 'approved')
            ->sum('amount');

        $pRsSumForFreight = PaymentRequest::whereHas('paymentRequestData', function ($query) use ($purchaseOrder) {
            $query->where('purchase_order_id', $purchaseOrder->id);
        })
            ->where('request_type', 'freight_payment')
            // ->where('status', 'approved')
            ->sum('amount');

        $samplingRequest = null;
        $samplingRequestCompulsuryResults = collect();
        $samplingRequestResults = collect();
        $otherDeduction = null;

        if ($purchaseOrder) {
            $samplingRequest = PurchaseSamplingRequest::where('arrival_purchase_order_id', $id)
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

            $otherDeduction = PaymentRequest::whereHas('paymentRequestData', function ($query) use ($purchaseOrder) {
                $query->where('purchase_order_id', $purchaseOrder->id);
            })->select('other_deduction_kg', 'other_deduction_value')
                ->latest()
                ->first();
        }

        if (isset($request->is_debug)) {
            $orignalsamplingRequestResults = $samplingRequestResults;
            $samplingRequestResults = $samplingRequestResults->filter(function ($result) {
                return $result->applied_deduction;
            });
        }

        $data['html'] = view('management.procurement.raw_material.payment_request.snippets.requestPurchaseForm', [
            'purchaseOrder' => $purchaseOrder,
            'samplingRequest' => $samplingRequest,
            'samplingRequestCompulsuryResults' => $samplingRequestCompulsuryResults,
            'samplingRequestResults' => $samplingRequestResults,
            'pRsSum' => $pRsSum,
            'pRsSumForFreight' => $pRsSumForFreight,
            'otherDeduction' => $otherDeduction,
            'isTicket' => $data['isTicket'],
            'isRequestApprovalPage' => false
        ])->render();

        return view('management.procurement.raw_material.payment_request.create', $data);
    }

    public function editOld($id)
    {
        $paymentRequestData = PaymentRequestData::with([
            'purchaseOrder',
            'samplingResults.slabType',
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

        // Get other deduction
        // $otherDeduction = $paymentRequestData->samplingResults->where('is_other_deduction', true)->first();

        return view('management.procurement.raw_material.payment_request.edit', [
            'paymentRequestData' => $paymentRequestData,
            'paymentRequest' => $paymentRequest,
            'freightRequest' => $freightRequest,
            'pRsSum' => $pRsSum,
            'pRsSumForFreight' => $pRsSumForFreight,
            'samplingResults' => $paymentRequestData->samplingResults,
            // 'otherDeduction' => $otherDeduction
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

        return response()->json(['message' => 'Request status updated successfully!']);
    }

    public function getSlabsByPaymentRequestParams(Request $request)
    {
        $purchaseOrder = ArrivalPurchaseOrder::findOrFail($request->purchase_order_id);
        $isTicket = str()->contains(request()->route()->getName(), '.ticket.');

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
        $otherDeduction = null;

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

            $existingPaymentRequestData = PaymentRequestData::where('purchase_order_id', $request->purchase_order_id)->first();
            if ($existingPaymentRequestData) {
                // $otherDeduction = PaymentRequestSamplingResult::where('payment_request_data_id', $existingPaymentRequestData->id)
                //     ->where('is_other_deduction', true)
                //     ->first();
            }
        }

        if (isset($request->is_debug)) {
            $orignalsamplingRequestResults = $samplingRequestResults;
            $samplingRequestResults = $samplingRequestResults->filter(function ($result) {
                return $result->applied_deduction;
            });
            // dd($samplingRequestResults, $orignalsamplingRequestResults, $rmPoSlabs);
        }

        $html = view('management.procurement.raw_material.payment_request.snippets.requestPurchaseForm', [
            'purchaseOrders' => $purchaseOrders,
            'purchaseOrder' => $purchaseOrder,
            'samplingRequest' => $samplingRequest,
            'samplingRequestCompulsuryResults' => $samplingRequestCompulsuryResults,
            'samplingRequestResults' => $samplingRequestResults,
            'pRsSumForFreight' => $pRsSumForFreight,
            'otherDeduction' => $otherDeduction,
            'pRsSum' => $pRsSum,
            'isTicket' => $isTicket,
        ])->render();

        return response()->json(['success' => true, 'html' => $html]);
    }
}
