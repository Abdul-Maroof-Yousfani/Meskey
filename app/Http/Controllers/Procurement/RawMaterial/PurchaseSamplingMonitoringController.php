<?php

namespace App\Http\Controllers\Procurement\RawMaterial;

use App\Http\Controllers\Controller;
use App\Http\Requests\Arrival\PurchaseSamplingResultRequest;
use Illuminate\Http\Request;
use App\Models\Arrival\{ArrivalSamplingResult, ArrivalSamplingResultForCompulsury, ArrivalTicket, PurchaseSamplingResult, PurchaseSamplingResultForCompulsury};
use App\Models\SaudaType;
use App\Models\ArrivalPurchaseOrder;
use App\Models\Master\ProductSlab;
use App\Models\Master\ProductSlabForRmPo;
use App\Models\Procurement\PurchaseOrder;
use App\Models\PurchaseSamplingRequest;
use App\Models\PurchaseTicket;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

class PurchaseSamplingMonitoringController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('management.procurement.raw_material.purchase_sampling_monitoring.index');
    }

    /**
     * Get list of categories.
     */
    public function getList(Request $request)
    {
        $samplingRequests = PurchaseSamplingRequest::where('is_done', 'yes')
            ->when($request->filled('search'), function ($q) use ($request) {
                $searchTerm = '%' . $request->search . '%';

                return $q->where(function ($sq) use ($searchTerm) {
                    $sq->orWhereHas('arrivalTicket', function ($aq) use ($searchTerm) {
                        $aq->where('unique_no', 'like', $searchTerm)
                            ->orWhere('supplier_name', 'like', $searchTerm);
                    });
                });
            })
            ->when($request->filled('sampling_type'), function ($q) use ($request) {
                return $q->where('sampling_type', 'like', $request->sampling_type);
            })
            ->where(function ($q) {
                $q->where('approved_status', 'pending')
                    ->orWhere('decision_making', 1);
            })
            ->when($request->filled('company_location_id'), function ($q) use ($request) {
                return $q->whereHas('purchaseOrder', function ($query) use ($request) {
                    $query->where('company_location_id', $request->company_location_id);
                });
            })
            ->when($request->filled('supplier_id'), function ($q) use ($request) {
                return $q->whereHas('purchaseOrder', function ($query) use ($request) {
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
            ->latest()
            // ->orderBy('created_at', 'asc')
            ->paginate(request('per_page', 25));

        return view('management.procurement.raw_material.purchase_sampling_monitoring.getList', compact('samplingRequests'));
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $samplingRequests = ArrivalSamplingRequest::where('sampling_type', 'initial')->get();
        return view('management.procurement.raw_material.purchase_sampling_monitoring.create', compact('samplingRequests'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PurchaseSamplingResultRequest $request)
    {
        $ArrivalSamplingRequest = ArrivalSamplingRequest::findOrFail($request->arrival_sampling_request_id);
        // Create main entry
        $ArrivalSamplingRequest->update([
            'remark' => $request->remarks,
            'is_done' => 'yes',
            'done_by' => auth()->user()->id,
        ]);

        // Check if arrays exist
        if (!empty($request->product_slab_type_id) && !empty($request->checklist_value)) {
            foreach ($request->product_slab_type_id as $key => $slabTypeId) {
                ArrivalSamplingResult::create([
                    'company_id' => $request->company_id,
                    'arrival_sampling_request_id' => $request->arrival_sampling_request_id,
                    'product_slab_type_id' => $slabTypeId,
                    'checklist_value' => $request->checklist_value[$key] ?? null,
                ]);
            }
        }

        return response()->json([
            'success' => 'Data stored successfully',
            'data' => [],
        ], 201);
    }


    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, $id)
    {
        $arrivalSamplingRequest = PurchaseSamplingRequest::findOrFail($id);
        $productSlabCalculations = null;
        if ($arrivalSamplingRequest->arrival_product_id) {
            $productSlabCalculations = ProductSlab::where('product_id', $arrivalSamplingRequest->arrival_product_id)->get();
        }

        $rmPoSlabs = collect();
        if ($arrivalSamplingRequest->purchaseOrder) {
            $rmPoSlabs = ProductSlabForRmPo::where('arrival_purchase_order_id', $arrivalSamplingRequest->purchaseOrder->id)
                ->where('product_id', $arrivalSamplingRequest->arrival_product_id)
                ->get()
                ->groupBy('product_slab_type_id');
        }

        $results = PurchaseSamplingResult::where('purchase_sampling_request_id', $id)->get();

        foreach ($results as $result) {
            $matchingSlabs = [];
            if ($productSlabCalculations) {
                $matchingSlabs = $productSlabCalculations->where('product_slab_type_id', $result->product_slab_type_id)
                    ->values()
                    ->all();
            }
            $result->matching_slabs = $matchingSlabs;

            $result->rm_po_slabs = $rmPoSlabs->get($result->product_slab_type_id, []);
        }

        $Compulsuryresults = PurchaseSamplingResultForCompulsury::where('purchase_sampling_request_id', $id)->get();

        $arrivalPurchaseOrders = ArrivalPurchaseOrder::where('product_id', $arrivalSamplingRequest->arrival_product_id)->get();
        $sampleTakenByUsers = User::all();
        $authUserCompany = $request->company_id;
        $saudaTypes = SaudaType::all();

        $initialRequestForInnerReq = null;
        $initialRequestResults = null;
        $initialRequestCompulsuryResults = null;

        $allInnerRequests = [];

        // if ($arrivalSamplingRequest->sampling_type == 'inner') {
        $initialRequestForInnerReq = PurchaseSamplingRequest::where('sampling_type', 'initial')
            ->where('purchase_ticket_id', $arrivalSamplingRequest->purchase_ticket_id)
            ->where('approved_status', 'approved')
            ->latest()
            ->first();

        if ($initialRequestForInnerReq) {
            $initialRequestResults = PurchaseSamplingResult::where('purchase_sampling_request_id', $initialRequestForInnerReq->id)->get();
            $initialRequestCompulsuryResults = PurchaseSamplingResultForCompulsury::where('purchase_sampling_request_id', $initialRequestForInnerReq->id)->get();
        }

        $allInnerRequests = PurchaseSamplingRequest::where('purchase_ticket_id', $arrivalSamplingRequest->purchase_ticket_id)
            ->where('approved_status', '!=', 'pending')
            ->where('id', '!=', $id)
            ->orderBy('created_at', 'asc')
            ->get();
        // }

        $innerRequestsData = [];
        foreach ($allInnerRequests as $innerReq) {
            $innerResults = PurchaseSamplingResult::where('purchase_sampling_request_id', $innerReq->id)->get();
            $innerCompulsuryResults = PurchaseSamplingResultForCompulsury::where('purchase_sampling_request_id', $innerReq->id)->get();

            $innerRequestsData[] = [
                'request' => $innerReq,
                'results' => $innerResults,
                'compulsuryResults' => $innerCompulsuryResults
            ];
        }

        return view('management.procurement.raw_material.purchase_sampling_monitoring.edit', [
            'initialRequestForInnerReq' => $initialRequestForInnerReq,
            'initialRequestResults' => $initialRequestResults,
            'initialRequestCompulsuryResults' => $initialRequestCompulsuryResults,
            'innerRequestsData' => $innerRequestsData,
            'samplingRequests' => PurchaseSamplingRequest::where('sampling_type', 'initial')->get(),
            'saudaTypes' => $saudaTypes,
            'arrivalPurchaseOrders' => $arrivalPurchaseOrders,
            'accountsOf' => User::role('Purchaser')
                ->whereHas('companies', function ($q) use ($authUserCompany) {
                    $q->where('companies.id', $authUserCompany);
                })->get(),
            'sampleTakenByUsers' => $sampleTakenByUsers,
            'results' => $results,
            'arrivalSamplingRequest' => $arrivalSamplingRequest,
            'Compulsuryresults' => $Compulsuryresults
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            // 'supplier' => 'required',
            // 'broker' => 'required',
            'stage_status' => 'required',
            'sauda_type_id' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $ArrivalSamplingRequest = PurchaseSamplingRequest::findOrFail($id);
            $reqStatus = $ArrivalSamplingRequest->approved_status;

            if ($reqStatus === 'approved' && $request->stage_status !== 'approved') {
                return response()->json([
                    'errors' => [
                        'stage_status' => ['This request is already approved, stage status must be "approved"']
                    ]
                ], 422);
            }

            $decisionMakingValue = 'off';
            $isLumpsum = 0;

            if ($ArrivalSamplingRequest->sampling_type === 'initial' && $reqStatus === 'pending' && $request->stage_status === 'resampling') {
                $decisionMakingValue = 'off';
                $isLumpsum = 0;
            } else {
                if ($reqStatus === 'approved') {
                    $decisionMakingValue = $request->decision_making ?? 'off';
                    $isLumpsum = convertToBoolean($request->is_lumpsum_deduction ?? 'off');
                } elseif ($reqStatus === 'pending') {
                    $decisionMakingValue = ($request->stage_status === 'approved')
                        ? ($request->decision_making ?? 'off')
                        : 'off';
                    $isLumpsum = convertToBoolean($request->is_lumpsum_deduction ?? 'off');
                }
            }

            $decisionMadeOn = null;
            $isDecisionMaking = convertToBoolean($decisionMakingValue);
            $isDecisionMakingReq = convertToBoolean($request->decision_making ?? 'off');

            if (!$isDecisionMakingReq && ($ArrivalSamplingRequest->purchaseOrder->decision_making ?? null) === 1) {
                $decisionMadeOn = now();
            }

            $ArrivalSamplingRequest->update([
                'remark' => $request->remarks,
                'decision_making' => $isDecisionMaking,
                'lumpsum_deduction' => (float)$request->lumpsum_deduction ?? 0.00,
                'lumpsum_deduction_kgs' => (float)$request->lumpsum_deduction_kgs ?? 0.00,
                'is_lumpsum_deduction' => $isLumpsum,
                'is_done' => 'yes',
                'done_by' => auth()->user()->id,
            ]);

            $records = PurchaseSamplingResult::where('purchase_sampling_request_id', $id)->get();

            foreach ($records as $record) {
                $record->delete();
            }

            $recordsQc = PurchaseSamplingResultForCompulsury::where('purchase_sampling_request_id', $id)->get();

            foreach ($recordsQc as $recordQc) {
                $recordQc->delete();
            }

            if (!empty($request->product_slab_type_id) && !empty($request->checklist_value)) {
                foreach ($request->product_slab_type_id as $key => $slabTypeId) {
                    PurchaseSamplingResult::create([
                        'company_id' => $request->company_id,
                        'purchase_sampling_request_id' => $id,
                        'product_slab_type_id' => $slabTypeId,
                        'checklist_value' => $request->checklist_value[$key] ?? null,
                        'suggested_deduction' => $request->suggested_deduction[$key] ?? null,
                        'applied_deduction' => $request->applied_deduction[$key] ?? null,
                    ]);
                }
            }

            if (!empty($request->compulsory_param_id)) {
                foreach ($request->compulsory_param_id as $key => $slabTypeId) {
                    PurchaseSamplingResultForCompulsury::create([
                        'company_id' => $request->company_id,
                        'purchase_sampling_request_id' => $id,
                        'arrival_compulsory_qc_param_id' => $slabTypeId,
                        'compulsory_checklist_value' => $request->compulsory_checklist_value[$key] ?? null,
                        'applied_deduction' => $request->compulsory_aapplied_deduction[$key] ?? 0,
                        'remark' => $request->remarks ?? null,
                    ]);
                }
            }

            if ($request->stage_status == 'resampling') {
                PurchaseSamplingRequest::create([
                    'company_id' => $ArrivalSamplingRequest->company_id,
                    'purchase_ticket_id'        => $ArrivalSamplingRequest->purchase_ticket_id,
                    'arrival_product_id'        => $ArrivalSamplingRequest->arrival_product_id,
                    'supplier_name'        => $ArrivalSamplingRequest->supplier_name,
                    'is_custom_qc'        => $ArrivalSamplingRequest->is_custom_qc,
                    'qc_product_id'             => $ArrivalSamplingRequest->qc_product_id,
                    'arrival_purchase_order_id' => $ArrivalSamplingRequest->arrival_purchase_order_id,
                    'sampling_type' => $ArrivalSamplingRequest->sampling_type,
                    'is_re_sampling' => 'yes',
                    'is_done' => 'no',
                    'remark' => null,
                ]);
                $ArrivalSamplingRequest->is_resampling_made = 'yes';
            }

            $updateData = [
                'lumpsum_deduction' => (float)($request->lumpsum_deduction ?? 0.00),
                'lumpsum_deduction_kgs' => (float)($request->lumpsum_deduction_kgs ?? 0.00),
                'is_lumpsum_deduction' => $isLumpsum,
                'decision_making_time' => $decisionMadeOn,
                'decision_making' => $isDecisionMaking,
                'location_transfer_status' => $request->stage_status == 'approved' ? 'pending' : null,
                'sauda_type_id' => $request->sauda_type_id,
                'arrival_purchase_order_id' => $request->arrival_purchase_order_id,
            ];

            if ($ArrivalSamplingRequest->sampling_type == 'inner') {
                $updateData['second_qc_status'] = $request->stage_status;
            } else {
                $updateData['first_qc_status'] = $request->stage_status;
            }

            $isCustomQC = $ArrivalSamplingRequest->is_custom_qc == 'yes';

            if (!$isCustomQC) {
                $ArrivalSamplingRequest->purchaseOrder()->first()->update($updateData);
            }

            $ArrivalSamplingRequest->approved_status = $request->stage_status;
            $ArrivalSamplingRequest->save();

            if ($request->stage_status == 'approved' && $reqStatus == 'pending') {
                if (!$isCustomQC) {
                    ArrivalPurchaseOrder::where('id', $ArrivalSamplingRequest->arrival_purchase_order_id)->update(['freight_status' => 'pending']);
                }

                PurchaseTicket::where('id', $ArrivalSamplingRequest->purchase_ticket_id)->update(['freight_status' => 'pending']);
            }

            return response()->json([
                'success' => 'Data stored successfully',
                'data' => [],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong!',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ArrivalTicket $arrivalTicket): JsonResponse
    {
        $arrivalTicket->delete();
        return response()->json(['success' => 'Ticket deleted successfully.'], 200);
    }
}
