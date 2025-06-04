<?php

namespace App\Http\Controllers\Arrival;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\ArrivalLocationTransferRequest;
use App\Models\Master\ArrivalLocation;

use App\Models\Arrival\ArrivalLocationTransfer;
use App\Models\Arrival\ArrivalSamplingRequest;
use App\Models\Arrival\ArrivalSamplingResult;
use App\Models\Arrival\ArrivalSamplingResultForCompulsury;
use App\Models\Arrival\ArrivalTicket;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ArrivalLocationTransferController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('management.arrival.location_transfer.index');
    }

    /**
     * Get list of categories.
     */
    public function getList(Request $request)
    {
        $arrival_locations = ArrivalLocationTransfer::when($request->filled('search'), function ($q) use ($request) {
            $searchTerm = '%' . $request->search . '%';
            return $q->where(function ($sq) use ($searchTerm) {
                $sq->where('name', 'like', $searchTerm);
            });
        })
            ->where('company_id', $request->company_id)
            ->latest()
            ->paginate(request('per_page', 25));

        return view('management.arrival.location_transfer.getList', compact('arrival_locations'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $data['ArrivalLocations'] =  ArrivalLocation::where('status', 'active')->get();
        $data['ArrivalTickets'] =  ArrivalTicket::where('location_transfer_status', 'pending')->get();
        return view('management.arrival.location_transfer.create', $data);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ArrivalLocationTransferRequest $request)
    {
        $arrivalTicket = ArrivalTicket::findOrFail($request->arrival_ticket_id);

        if ($arrivalTicket->location_transfer_status !== 'pending') {
            return response('Location has already been transferred. Transfer cannot be performed again.', 422);
        }

        $request['creator_id'] = auth()->user()->id;

        $arrival_location = ArrivalLocationTransfer::create($request->all());

        $arrivalTicket->update([
            'location_transfer_status' => 'transfered',
            'first_weighbridge_status' => 'pending'
        ]);

        return response()->json([
            'success' => 'Location Transferred Successfully.',
            'data' => $arrival_location
        ], 201);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, $id)
    {
        $locationTransfer = ArrivalLocationTransfer::findOrFail($id);
        $initialRequestForInnerReq = ArrivalSamplingRequest::where('arrival_ticket_id', $locationTransfer->arrival_ticket_id)
            ->where('sampling_type', 'initial')
            ->where('approved_status', 'approved')
            ->get()->last();

        if (!$initialRequestForInnerReq) {
            return response()->json(['success' => false, 'message' => 'Arrival ticket not found.'], 404);
        }

        $initialRequestCompulsuryResults  = ArrivalSamplingResultForCompulsury::where('arrival_sampling_request_id', $initialRequestForInnerReq->id)->get();
        $initialRequestResults  = ArrivalSamplingResult::where('arrival_sampling_request_id', $initialRequestForInnerReq->id)->get();
        $sampleTakenByUsers = User::all();

        return view('management.master.arrival_location.edit', compact('locationTransfer', 'initialRequestCompulsuryResults', 'initialRequestResults', 'sampleTakenByUsers', 'initialRequestForInnerReq'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ArrivalLocationTransferRequest $request, ArrivalLocation $arrival_location)
    {
        $data = $request->validated();
        $arrival_location->update($data);
        return response()->json(['success' => 'Arrival Location updated successfully.', 'data' => $arrival_location], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $arrival_location = ArrivalLocation::findOrFail($id);
        $arrival_location->delete();
        return response()->json(['success' => 'Arrival Location deleted successfully.'], 200);
    }
}
