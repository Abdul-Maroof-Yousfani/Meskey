<?php

namespace App\Http\Controllers\Arrival;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Arrival\{ArrivalSamplingRequest, ArrivalSamplingResult, ArrivalSamplingResultForCompulsury, ArrivalTicket};
use App\Models\SaudaType;
use App\Models\ArrivalPurchaseOrder;
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
        $samplingRequests = ArrivalSamplingRequest::with('arrivalTicket')->where('is_done', 'yes')->when($request->filled('search'), function ($q) use ($request) {
            $searchTerm = '%' . $request->search . '%';
            return $q->where(function ($sq) use ($searchTerm) {
                $sq->where('unique_no', 'like', $searchTerm);
                $sq->orWhere('supplier_name', 'like', $searchTerm);
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
        $results = ArrivalSamplingResult::where('arrival_sampling_request_id', $id)->get();
        $Compulsuryresults = ArrivalSamplingResultForCompulsury::where('arrival_sampling_request_id', $id)->get();
        $arrivalPurchaseOrders = ArrivalPurchaseOrder::where('product_id', $arrivalSamplingRequest->arrivalTicket->product_id)->get();
        $sampleTakenByUsers = User::all();
        $authUserCompany = $request->company_id;
        $saudaTypes = SaudaType::all();
        $accountsOf = User::role('Purchaser')
            ->whereHas('companies', function ($q) use ($authUserCompany) {
                $q->where('companies.id', $authUserCompany);
            })
            ->get();
        $initialRequestForInnerReq = null;
        $initialRequestResults = null;
        $initialRequestCompulsuryResults = null;

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
            'supplier' => 'required',
            'broker' => 'required',
            'stage_status' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $ArrivalSamplingRequest = ArrivalSamplingRequest::findOrFail($id);
            // $ArrivalTicket = ArrivalTicket::findOrFail($ArrivalSamplingRequest->arrival_ticket_id);

            // Update main entry
            $ArrivalSamplingRequest->update([
                'remark' => $request->remarks,
                'is_done' => 'yes',
                'done_by' => auth()->user()->id,
            ]);

            // Delete existing records for this request ID
            $records = ArrivalSamplingResult::where('arrival_sampling_request_id', $id)->get();

            foreach ($records as $record) {
                $record->delete();
            }
            // Check if arrays exist before inserting new records
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

            // If resampling is required, create a new request
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

            // Update status

            $ArrivalSamplingRequest->arrivalTicket()->first()->update(['first_qc_status' => $request->stage_status, 'location_transfer_status' => 'pending', 'sauda_type_id' => $request->sauda_type_id, 'supplier_name' => $request->supplier, 'broker_name' => $request->broker, 'arrival_purchase_order_id' => $request->arrival_purchase_order_id]);
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
