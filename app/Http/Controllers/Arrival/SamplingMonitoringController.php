<?php

namespace App\Http\Controllers\Arrival;

use App\Http\Controllers\Controller;
use App\Http\Requests\Arrival\ArrivalInitialSamplingResultRequest;
use Illuminate\Http\Request;
use App\Models\Arrival\{ArrivalSamplingRequest, ArrivalSamplingResult, ArrivalSamplingResultForCompulsury, ArrivalTicket};
use App\Models\SaudaType;
use App\Models\ArrivalPurchaseOrder;
use App\Models\Master\ProductSlab;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

class SamplingMonitoringController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('management.arrival.sampling_monitoring.index');
    }

    /**
     * Get list of categories.
     */
    public function getList(Request $request)
    {
        $authUser = auth()->user();
        $isSuperAdmin = $authUser->user_type === 'super-admin';
        // $isSuperAdmin = true;

        $latestRequestIds = ArrivalSamplingRequest::selectRaw('MAX(id) as id')
            ->where('is_done', 'yes')
            ->groupBy('arrival_ticket_id')
            ->pluck('id');

        $query = ArrivalSamplingRequest::with(['arrivalTicket'])
            ->whereIn('id', $latestRequestIds)
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
            //->when(!$isSuperAdmin, function ($q) use ($authUser) {
            //  return $q->whereHas('arrivalTicket', function ($query) use ($authUser) {
            //    $query->where('decision_id', $authUser->id);
            //});
            //})
            ->when(!$isSuperAdmin, function ($q) use ($authUser) {
                return $q->whereHas('arrivalTicket', function ($query) use ($authUser) {
                    $query->where(function ($subQuery) use ($authUser) {
                        $subQuery->where('decision_id', $authUser->id)
                            ->orWhere('decision_id', $authUser->parent_id);
                    });
                });
            })
            ->where(function ($q) {
                $q->where('approved_status', 'pending')
                    ->orWhere(function ($q) {
                        $q->where('decision_making', 1);
                        // ->where('lumpsum_deduction', 0)
                        // ->where('lumpsum_deduction_kgs', 0);
                    });
            });

        $samplingRequests = $query->latest()
            ->paginate($request->get('per_page', 25));

        return view('management.arrival.sampling_monitoring.getList', compact('samplingRequests'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $samplingRequests = ArrivalSamplingRequest::where('sampling_type', 'initial')->get();
        return view('management.arrival.sampling_monitoring.create', compact('samplingRequests'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ArrivalInitialSamplingResultRequest $request)
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
        $arrivalSamplingRequest = ArrivalSamplingRequest::findOrFail($id);

        $slabs = ProductSlab::where('product_id', $arrivalSamplingRequest->arrival_product_id)
            ->get()
            ->groupBy('product_slab_type_id')
            ->map(function ($group) {
                return $group->sortBy('from')->first();
            });

        $productSlabCalculations = null;
        if ($arrivalSamplingRequest->arrival_product_id) {
            $productSlabCalculations = ProductSlab::where('product_id', $arrivalSamplingRequest->arrival_product_id)->get();
        }

        $results = ArrivalSamplingResult::where('arrival_sampling_request_id', $id)->get();
        foreach ($results as $result) {
            $matchingSlabs = [];
            if ($productSlabCalculations) {
                $matchingSlabs = $productSlabCalculations->where('product_slab_type_id', $result->product_slab_type_id)
                    ->values()
                    ->all();
            }
            $result->matching_slabs = $matchingSlabs;
        }

        $results->map(function ($item) use ($slabs) {
            $slab = $slabs->get($item->product_slab_type_id);
            $item->max_range = $slab ? $slab->to : null;
            return $item;
        });

        $Compulsuryresults = ArrivalSamplingResultForCompulsury::where('arrival_sampling_request_id', $id)->get();

        $arrivalPurchaseOrders = ArrivalPurchaseOrder::where('product_id', $arrivalSamplingRequest->arrivalTicket->product_id)->get();
        $sampleTakenByUsers = User::all();
        $authUserCompany = $request->company_id;
        $saudaTypes = SaudaType::all();

        $allInitialRequests = ArrivalSamplingRequest::where('sampling_type', 'initial')
            ->where('arrival_ticket_id', $arrivalSamplingRequest->arrival_ticket_id)
            ->where('approved_status', '!=', 'pending')
            ->orderBy('created_at', 'asc')
            ->get();

        $allInnerRequests = ArrivalSamplingRequest::where('sampling_type', 'inner')
            ->where('arrival_ticket_id', $arrivalSamplingRequest->arrival_ticket_id)
            ->where('approved_status', '!=', 'pending')
            ->where('id', '!=', $id)
            ->orderBy('created_at', 'asc')
            ->get();

        $initialRequestsData = [];
        foreach ($allInitialRequests as $initialReq) {
            $initialResults = ArrivalSamplingResult::where('arrival_sampling_request_id', $initialReq->id)->get();
            $initialCompulsuryResults = ArrivalSamplingResultForCompulsury::where('arrival_sampling_request_id', $initialReq->id)->get();

            $initialResults->map(function ($item) use ($slabs) {
                $slab = $slabs->get($item->product_slab_type_id);
                $item->max_range = $slab ? $slab->to : null;
                $item->deduction_type = $slab ? $slab->deduction_type : null;
                return $item;
            });

            $initialRequestsData[] = [
                'request' => $initialReq,
                'results' => $initialResults,
                'compulsuryResults' => $initialCompulsuryResults
            ];
        }

        $innerRequestsData = [];
        foreach ($allInnerRequests as $innerReq) {
            $innerResults = ArrivalSamplingResult::where('arrival_sampling_request_id', $innerReq->id)->get();
            $innerCompulsuryResults = ArrivalSamplingResultForCompulsury::where('arrival_sampling_request_id', $innerReq->id)->get();

            $innerResults->map(function ($item) use ($slabs) {
                $slab = $slabs->get($item->product_slab_type_id);
                $item->deduction_type = $slab ? $slab->deduction_type : null;
                $item->max_range = $slab ? $slab->to : null;
                return $item;
            });

            $innerRequestsData[] = [
                'request' => $innerReq,
                'results' => $innerResults,
                'compulsuryResults' => $innerCompulsuryResults
            ];
        }

        return view('management.arrival.sampling_monitoring.edit', [
            'initialRequestsData' => $initialRequestsData,
            'innerRequestsData' => $innerRequestsData,
            'samplingRequests' => ArrivalSamplingRequest::where('sampling_type', 'initial')->get(),
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
            $ArrivalSamplingRequest = ArrivalSamplingRequest::findOrFail($id);
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

            if (!$isDecisionMakingReq && $ArrivalSamplingRequest->arrivalTicket->decision_making === 1) {
                $decisionMadeOn = now();
            }

            $ArrivalSamplingRequest->update([
                'remark' => $request->remarks,
                'decision_making' => $isDecisionMaking,
                'lumpsum_deduction' => (float) $request->lumpsum_deduction ?? 0.00,
                'lumpsum_deduction_kgs' => (float) $request->lumpsum_deduction_kgs ?? 0.00,
                'is_lumpsum_deduction' => $isLumpsum,
                'is_done' => 'yes',
                'done_by' => auth()->user()->id,
            ]);

            $records = ArrivalSamplingResult::where('arrival_sampling_request_id', $id)->get();

            foreach ($records as $record) {
                $record->delete();
            }

            $recordsQc = ArrivalSamplingResultForCompulsury::where('arrival_sampling_request_id', $id)->get();

            foreach ($recordsQc as $recordQc) {
                $recordQc->delete();
            }

            if (!empty($request->product_slab_type_id) && !empty($request->checklist_value)) {
                foreach ($request->product_slab_type_id as $key => $slabTypeId) {
                    ArrivalSamplingResult::create([
                        'company_id' => $request->company_id,
                        'arrival_sampling_request_id' => $id,
                        'product_slab_type_id' => $slabTypeId,
                        'checklist_value' => $request->checklist_value[$key] ?? null,
                        'suggested_deduction' => $request->suggested_deduction[$key] ?? null,
                        'applied_deduction' => $request->applied_deduction[$key] ?? null,
                    ]);
                }
            }

            if (!empty($request->compulsory_param_id)) {
                foreach ($request->compulsory_param_id as $key => $slabTypeId) {
                    ArrivalSamplingResultForCompulsury::create([
                        'company_id' => $request->company_id,
                        'arrival_sampling_request_id' => $id,
                        'arrival_compulsory_qc_param_id' => $slabTypeId,
                        'compulsory_checklist_value' => $request->compulsory_checklist_value[$key] ?? null,
                        'applied_deduction' => $request->compulsory_aapplied_deduction[$key] ?? 0,
                        'remark' => $request->remarks ?? null,
                    ]);
                }
            }

            if ($reqStatus == 'pending') {
                if ($request->stage_status == 'resampling') {
                    ArrivalSamplingRequest::create([
                        'company_id' => $ArrivalSamplingRequest->company_id,
                        'arrival_ticket_id' => $ArrivalSamplingRequest->arrival_ticket_id,
                        'sampling_type' => $ArrivalSamplingRequest->sampling_type,
                        'is_re_sampling' => 'yes',
                        'is_done' => 'no',
                        'remark' => null,
                    ]);
                    $ArrivalSamplingRequest->is_resampling_made = 'yes';
                }
            }

            $updateData = [
                'lumpsum_deduction' => (float) ($request->lumpsum_deduction ?? 0.00),
                'lumpsum_deduction_kgs' => (float) ($request->lumpsum_deduction_kgs ?? 0.00),
                'is_lumpsum_deduction' => $isLumpsum,
                'decision_making' => $isDecisionMaking,
                'decision_making_time' => $decisionMadeOn,
                //'location_transfer_status' => $request->stage_status == 'approved' ? 'pending' : null,
                'sauda_type_id' => $request->sauda_type_id,
                // 'arrival_purchase_order_id' => $request->arrival_purchase_order_id,
            ];

            if ($ArrivalSamplingRequest->sampling_type == 'inner') {
                $updateData['second_qc_status'] = $request->stage_status;
            } else {
                if ($reqStatus !== 'approved') {
                    $updateData['first_qc_status'] = $request->stage_status;
                    $updateData['location_transfer_status'] = $request->stage_status == 'approved' ? 'pending' : null;
                }
            }

            $ArrivalSamplingRequest->arrivalTicket()->first()->update($updateData);

            if ($reqStatus == 'pending') {
                $ArrivalSamplingRequest->approved_status = $request->stage_status;
            }

            $ArrivalSamplingRequest->save();

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
