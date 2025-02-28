<?php

namespace App\Http\Controllers\Arrival;

use App\Http\Controllers\Controller;
use App\Models\Arrival\{ArrivalSamplingRequest, ArrivalSamplingResult};
use App\Models\Master\ProductSlab;
use Illuminate\Http\Request;
use App\Http\Requests\Arrival\ArrivalSamplingResultRequest;
use App\Models\Arrival\ArrivalTicket;

class InitialSamplingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('management.arrival.initial_sampling.index');
    }

    /**
     * Get list of categories.
     */
    public function getList(Request $request)
    {
        $samplingRequests = ArrivalSamplingRequest::with('arrivalTicket')->when($request->filled('search'), function ($q) use ($request) {
            $searchTerm = '%' . $request->search . '%';
            return $q->where(function ($sq) use ($searchTerm) {
                $sq->where('unique_no', 'like', $searchTerm);
                $sq->orWhere('supplier_name', 'like', $searchTerm);
            });
        })
            ->latest()
            ->paginate(request('per_page', 25));

        return view('management.arrival.initial_sampling.getList', compact('samplingRequests'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $samplingRequests = ArrivalSamplingRequest::where('sampling_type', 'initial')->where('is_done','no')->get();
        return view('management.arrival.initial_sampling.create', compact('samplingRequests'));
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

        return view('management.arrival.initial_sampling.edit', compact('samplingRequests', 'results','arrivalSamplingRequest'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ArrivalTicketRequest $request, $id)
    {

        $ArrivalTicket = ArrivalTicket::findOrFail($id);


        $data = $request->validated();
        $ArrivalTicket->update($request->all());

        return response()->json(['success' => 'Ticket updated successfully.', 'data' => $ArrivalTicket], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ArrivalTicket $ArrivalTicket): JsonResponse
    {
        $ArrivalTicket->delete();
        return response()->json(['success' => 'Ticket deleted successfully.'], 200);
    }
}
