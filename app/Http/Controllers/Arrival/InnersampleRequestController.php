<?php

namespace App\Http\Controllers\Arrival;

use App\Http\Controllers\Controller;
use App\Models\Arrival\ArrivalSamplingRequest;
use App\Models\Arrival\ArrivalTicket;
use App\Models\Arrival\ArrivalLocationTransfer;
use App\Models\Master\ArrivalLocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;


class InnersampleRequestController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('management.arrival.inner_sample_request.index');
    }

    /**
     * Get list of categories.
     */
    public function getList(Request $request)
    {
        $authUser = auth()->user();
        $isSuperAdmin = $authUser->user_type === 'super-admin';

        $ArrivalSamplingRequests = ArrivalSamplingRequest::where('sampling_type', 'inner')->where('is_re_sampling', 'no')->when($request->filled('search'), function ($q) use ($request) {
            $searchTerm = '%' . $request->search . '%';
            return $q->where(function ($sq) use ($searchTerm) {
                $sq->where('name', 'like', $searchTerm);
            });
        })
            // ->when(!$isSuperAdmin, function ($q) use ($authUser) {
            //     return $q->whereHas('arrivalTicket.unloadingLocation', function ($query) use ($authUser) {
            //         $query->where('arrival_location_id', $authUser->arrival_location_id);
            //     });
            // })
            ->whereHas('arrivalTicket.unloadingLocation', function ($q) {
                $q->whereIn('arrival_location_id', getUserCurrentCompanyArrivalLocations());
            })
            ->where('company_id', $request->company_id)
            ->latest()
            ->paginate(request('per_page', 25));

        return view('management.arrival.inner_sample_request.getList', compact('ArrivalSamplingRequests'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $authUser = auth()->user();
        $isSuperAdmin = $authUser->user_type === 'super-admin';
        $data['ArrivalLocations'] =  ArrivalLocation::where('status', 'active')->get();

        $data['ArrivalTickets'] = ArrivalTicket::where('first_weighbridge_status', 'completed')
            ->where(function ($q) {
                $q->where('document_approval_status', '!=', 'fully_approved')
                    ->where('document_approval_status', '!=', 'half_approved')
                    ->orWhereNull('document_approval_status');
            })
            ->leftJoin('arrival_sampling_requests', function ($join) {
                $join->on('arrival_tickets.id', '=', 'arrival_sampling_requests.arrival_ticket_id')
                    ->where('sampling_type', 'inner')
                    ->where('approved_status', 'pending')
                    ->where('arrival_sampling_requests.deleted_at', null);
            })
            // ->when(!$isSuperAdmin, function ($q) use ($authUser) {
            //     return $q->whereHas('unloadingLocation', function ($query) use ($authUser) {
            //         $query->where('arrival_location_id', $authUser->arrival_location_id);
            //     });
            // })
            ->whereHas('unloadingLocation', function ($q) {
                $q->whereIn('arrival_location_id', getUserCurrentCompanyArrivalLocations());
            })
            ->whereNull('arrival_sampling_requests.id')
            ->select('arrival_tickets.*')
            ->distinct()
            ->get();

        return view('management.arrival.inner_sample_request.create', $data);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ticket_id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $ticket = ArrivalTicket::findOrFail($request->ticket_id);

        if ($ticket->document_approval_status === 'fully_approved' || $ticket->document_approval_status === 'half_approved') {
            return response('This truck has already been approved.', 422);
        }

        $existingRequest = ArrivalSamplingRequest::where('arrival_ticket_id', $request->ticket_id)
            ->where('sampling_type', 'inner')
            ->where('approved_status', 'pending')
            ->first();

        if ($existingRequest) {
            return response('A pending inner sampling request already exists for this ticket.', 422);
        }

        $arrivalSampleReq = ArrivalSamplingRequest::create([
            'company_id'       => $request->company_id,
            'arrival_ticket_id' => $request->ticket_id,
            'sampling_type'    => 'inner',
            'is_re_sampling'   => 'no',
            'is_done'          => 'no',
            'remark'           => null,
        ]);

        return response()->json(['success' => 'Inner Sampling Request created successfully.', 'data' => $arrivalSampleReq], 201);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $arrival_location = ArrivalLocation::findOrFail($id);
        return view('management.master.arrival_location.edit', compact('arrival_location'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ArrivalLocationRequest $request, ArrivalLocation $arrival_location)
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
