<?php

namespace App\Http\Controllers\Arrival;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Arrival\ArrivalSamplingRequest;

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
        $samplingRequests = ArrivalSamplingRequest::where('sampling_type', 'initial')->where('is_done', 'no')->get();
        return view('management.arrival.inner_sampling.create', compact('samplingRequests'));
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
    public function edit($id)
    {
        $samplingRequests = ArrivalSamplingRequest::where('sampling_type', 'initial')->get();

        $arrivalSamplingRequest = ArrivalSamplingRequest::findOrFail($id);
        $results = ArrivalSamplingResult::where('arrival_sampling_request_id', $id)->get();

        return view('management.arrival.initial_sampling.edit', compact('samplingRequests', 'results', 'arrivalSamplingRequest'));
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
