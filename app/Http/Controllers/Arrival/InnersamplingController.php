<?php

namespace App\Http\Controllers\Arrival;

use App\Http\Controllers\Controller;
use App\Http\Requests\Arrival\ArrivalSamplingResultRequest;
use Illuminate\Http\Request;
use App\Models\Arrival\ArrivalSamplingRequest;
use App\Models\Arrival\ArrivalSamplingResult;
use App\Models\Arrival\ArrivalSamplingResultForCompulsury;
use App\Models\Master\ProductSlab;
use App\Models\Product;

class InnersamplingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('management.arrival.inner_sampling.index');
    }

    /**
     * Get list of categories.
     */
    public function getList(Request $request)
    {
        $samplingRequests = ArrivalSamplingRequest::with('arrivalTicket')->where('is_done', 'yes')->where('sampling_type', 'inner')->when($request->filled('search'), function ($q) use ($request) {
            $searchTerm = '%' . $request->search . '%';
            return $q->where(function ($sq) use ($searchTerm) {
                $sq->where('unique_no', 'like', $searchTerm);
                $sq->orWhere('supplier_name', 'like', $searchTerm);
            });
        })
            ->latest()
            ->paginate(request('per_page', 25));

        return view('management.arrival.inner_sampling.getList', compact('samplingRequests'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $samplingRequests = ArrivalSamplingRequest::where('sampling_type', 'inner')->get();
        $products = Product::all();

        return view('management.arrival.inner_sampling.create', compact('samplingRequests', 'products'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ArrivalSamplingResultRequest $request)
    {
        $ArrivalSamplingRequest = ArrivalSamplingRequest::findOrFail($request->arrival_sampling_request_id);
        $createdSamplingData = [];
        $createdCompulsuryData = [];
        $initialStatus = 'pending';

        if (!empty($request->product_slab_type_id) && !empty($request->checklist_value)) {
            foreach ($request->product_slab_type_id as $key => $slabTypeId) {
                $createdSamplingData[] = ArrivalSamplingResult::create([
                    'company_id' => $request->company_id,
                    'arrival_sampling_request_id' => $request->arrival_sampling_request_id,
                    'product_slab_type_id' => $slabTypeId,
                    'checklist_value' => $request->checklist_value[$key] ?? null,
                ]);
            }
        }

        if (!empty($request->arrival_compulsory_qc_param_id) && !empty($request->compulsory_checklist_value)) {
            foreach ($request->arrival_compulsory_qc_param_id as $key => $paramId) {
                $createdCompulsuryData[] = ArrivalSamplingResultForCompulsury::create([
                    'company_id' => $request->company_id,
                    'arrival_sampling_request_id' => $request->arrival_sampling_request_id,
                    'arrival_compulsory_qc_param_id' => $paramId,
                    'compulsory_checklist_value' => $request->compulsory_checklist_value[$key] ?? null,
                    'remark' => null,
                ]);
            }
        }

        $initialRequestForInnerReq = ArrivalSamplingRequest::where('sampling_type', 'initial')
            ->where('arrival_ticket_id', $ArrivalSamplingRequest->arrival_ticket_id)
            ->where('approved_status', 'approved')
            ->latest()
            ->first();

        if ($initialRequestForInnerReq) {
            $initialRequestResults = ArrivalSamplingResult::where('arrival_sampling_request_id', $initialRequestForInnerReq->id)->get();
            $initialRequestCompulsuryResults = ArrivalSamplingResultForCompulsury::where('arrival_sampling_request_id', $initialRequestForInnerReq->id)->get();

            $resultsMatch = true;
            $compulsoryResultsMatch = true;

            if (count($initialRequestResults) === count($createdSamplingData)) {
                foreach ($initialRequestResults as $index => $initialResult) {
                    if (!isset($createdSamplingData[$index])) {
                        $resultsMatch = false;
                        break;
                    }

                    if (
                        $initialResult->product_slab_type_id != $createdSamplingData[$index]->product_slab_type_id ||
                        $initialResult->checklist_value != $createdSamplingData[$index]->checklist_value
                    ) {
                        $resultsMatch = false;
                        break;
                    }
                }
            } else {
                $resultsMatch = false;
            }

            if (count($initialRequestCompulsuryResults) === count($createdCompulsuryData)) {
                foreach ($initialRequestCompulsuryResults as $index => $initialCompulsoryResult) {
                    if ($initialCompulsoryResult->qcParam->properties['is_protected_for_inner_req']) {
                        continue;
                    }

                    if (!isset($createdCompulsuryData[$index])) {
                        $compulsoryResultsMatch = false;
                        break;
                    }
                    if (
                        $initialCompulsoryResult->arrival_compulsory_qc_param_id != $createdCompulsuryData[$index]->arrival_compulsory_qc_param_id ||
                        $initialCompulsoryResult->compulsory_checklist_value != $createdCompulsuryData[$index]->compulsory_checklist_value
                    ) {
                        $compulsoryResultsMatch = false;
                        break;
                    }
                }
            } else {
                $compulsoryResultsMatch = false;
            }

            if ($resultsMatch && $compulsoryResultsMatch) {
                $initialStatus = 'approved';
            }
        }

        $ArrivalSamplingRequest->update([
            'remark' => $request->remarks,
            'is_done' => 'yes',
            'arrival_product_id' => $request->arrival_product_id,
            'party_ref_no' => $request->party_ref_no ?? NULL,
            'sample_taken_by' => $request->sample_taken_by ?? NULL,
            'done_by' => auth()->user()->id,
            'approved_status' => $initialStatus,
        ]);

        return response()->json([
            'success' => 'Data stored successfully',
            'data' => [],
        ], 201);
    }


    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $samplingRequests = ArrivalSamplingRequest::where('sampling_type', 'inner')->get();
        $products = Product::all();

        $arrivalSamplingRequest = ArrivalSamplingRequest::findOrFail($id);

        $slabs = ProductSlab::where('product_id', $arrivalSamplingRequest->arrival_product_id)
            ->get()
            ->groupBy('product_slab_type_id')
            ->map(function ($group) {
                return $group->sortBy('from')->first();
            });

        $results = ArrivalSamplingResult::where('arrival_sampling_request_id', $id)->get();

        $results->map(function ($item) use ($slabs) {
            $slab = $slabs->get($item->product_slab_type_id);
            $item->max_range = $slab ? $slab->to : null;
            return $item;
        });

        return view('management.arrival.inner_sampling.edit', compact('samplingRequests', 'products', 'results', 'arrivalSamplingRequest'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ArrivalTicketRequest $request, $id)
    {

        $arrivalTicket = ArrivalTicket::findOrFail($id);


        $data = $request->validated();
        $arrivalTicket->update($request->all());

        return response()->json(['success' => 'Ticket updated successfully.', 'data' => $arrivalTicket], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ArrivalTicket $arrivalTicket): JsonResponse
    {
        $arrivalTicket->delete();
        return response()->json(['success' => 'Ticket deleted successfully.'], 200);
    }

    public function updateStatus(Request $request)
    {

        $request->validate([
            'request_id' => 'required|exists:arrival_sampling_requests,id',
            'status' => 'required|in:approved,rejected,resampling'
        ]);

        $sampling = ArrivalSamplingRequest::find($request->request_id);

        if ($request->status == 'resampling') {

            ArrivalSamplingRequest::create([
                'company_id' => $sampling->company_id,
                'arrival_ticket_id' => $sampling->arrival_ticket_id,
                'sampling_type' => 'initial',
                'is_re_sampling' => 'yes',
                'is_done' => 'no',
                'remark' => null,
            ]);
            $sampling->is_resampling_made = 'yes';
        }



        $sampling->approved_status = $request->status;
        $sampling->save();


        //$sampling = ArrivalSamplingRequest::find($request->request_id);


        return response()->json(['message' => 'Request status updated successfully!']);
    }
}
