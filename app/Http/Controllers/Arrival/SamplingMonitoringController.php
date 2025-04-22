<?php

namespace App\Http\Controllers\Arrival;

use App\Http\Controllers\Controller;
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
    $samplingRequests = ArrivalSamplingRequest::with('arrivalTicket')
        ->where('is_done', 'yes')
        ->when($request->filled('search'), function ($q) use ($request) {
            $searchTerm = '%' . $request->search . '%';

            return $q->where(function ($sq) use ($searchTerm,$request) {
                $sq->orWhereHas('arrivalTicket', function ($aq) use ($searchTerm) {
                        $aq->where('unique_no', 'like', $searchTerm)
                    ->orWhere('supplier_name', 'like', $searchTerm);
                                        });
            });
        })
                ->when($request->filled('sampling_type'), function ($q) use ($request) {
return $q->where(function ($sq) use ($request) {
    $sq->where('sampling_type', 'like', $request->sampling_type);
                    
});

                })

        ->latest()
        ->paginate(request('per_page', 25));

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
    public function store(ArrivalSamplingResultRequest $request)
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
        $samplingRequests = ArrivalSamplingRequest::where('sampling_type', 'initial')->get();

        $arrivalSamplingRequest = ArrivalSamplingRequest::findOrFail($id);

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

        $Compulsuryresults = ArrivalSamplingResultForCompulsury::where('arrival_sampling_request_id', $id)->get();
        $arrivalPurchaseOrders = ArrivalPurchaseOrder::where('product_id', $arrivalSamplingRequest->arrivalTicket->product_id)->get();
        $sampleTakenByUsers = User::all();
        $authUserCompany = $request->company_id;
        $saudaTypes = SaudaType::all();
        $initialRequestForInnerReq = null;
        $initialRequestResults = null;
        $initialRequestCompulsuryResults = null;
        $accountsOf = User::role('Purchaser')
            ->whereHas('companies', function ($q) use ($authUserCompany) {
                $q->where('companies.id', $authUserCompany);
            })
            ->get();

        if ($arrivalSamplingRequest->sampling_type == 'inner') {
            $initialRequestForInnerReq = ArrivalSamplingRequest::where('sampling_type', 'initial')
                ->where('arrival_ticket_id', $arrivalSamplingRequest->arrival_ticket_id)
                ->where('approved_status', 'approved')
                ->latest()
                ->first();

            if ($initialRequestForInnerReq) {
                $initialRequestResults = ArrivalSamplingResult::where('arrival_sampling_request_id', $initialRequestForInnerReq->id)->get();
                $initialRequestCompulsuryResults = ArrivalSamplingResultForCompulsury::where('arrival_sampling_request_id', $initialRequestForInnerReq->id)->get();
            }
        }

        return view('management.arrival.sampling_monitoring.edit', compact('initialRequestForInnerReq', 'initialRequestResults', 'initialRequestCompulsuryResults', 'samplingRequests', 'saudaTypes', 'arrivalPurchaseOrders', 'accountsOf', 'sampleTakenByUsers', 'results', 'arrivalSamplingRequest', 'Compulsuryresults'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            // 'supplier' => 'required',
            // 'broker' => 'required',
            'stage_status' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $ArrivalSamplingRequest = ArrivalSamplingRequest::findOrFail($id);
            // $ArrivalTicket = ArrivalTicket::findOrFail($ArrivalSamplingRequest->arrival_ticket_id);

            $isLumpsum = ($request->is_lumpsum_deduction ?? 'off') == 'on' ? 1 : 0;
            $isDecisionMaking = ($request->decision_making ?? 'off') == 'on' ? 1 : 0;

            $ArrivalSamplingRequest->update([
                'remark' => $request->remarks,
                'decision_making' => $isDecisionMaking,
                'lumpsum_deduction' => (float)$request->lumpsum_deduction ?? 0.00,
                'lumpsum_deduction_kgs' => (float)$request->lumpsum_deduction_kgs ?? 0.00,
                'is_lumpsum_deduction' => $isLumpsum,
                'is_done' => 'yes',
                'done_by' => auth()->user()->id,
            ]);

            $records = ArrivalSamplingResult::where('arrival_sampling_request_id', $id)->get();

            foreach ($records as $record) {
                $record->delete();
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

            if ($request->stage_status == 'resampling') {
                ArrivalSamplingRequest::create([
                    'company_id' => $ArrivalSamplingRequest->company_id,
                    'arrival_ticket_id' => $ArrivalSamplingRequest->arrival_ticket_id,
                    'sampling_type' => 'initial',
                    'is_re_sampling' => 'yes',
                    'is_done' => 'no',
                    'remark' => null,
                ]);
                $ArrivalSamplingRequest->is_resampling_made = 'yes';
            }

            $ArrivalSamplingRequest->arrivalTicket()->first()->update(['first_qc_status' => $request->stage_status, 'decision_making' => $isDecisionMaking, 'location_transfer_status' => 'pending', 'sauda_type_id' => $request->sauda_type_id, 'arrival_purchase_order_id' => $request->arrival_purchase_order_id]);
            $ArrivalSamplingRequest->approved_status = $request->stage_status;
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
