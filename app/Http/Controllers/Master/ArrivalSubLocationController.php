<?php

namespace App\Http\Controllers\Master;


use App\Http\Controllers\Controller;
use App\Models\Arrival\ArrivalSamplingResult;
use App\Models\Arrival\ArrivalSamplingResultForCompulsury;
use App\Models\Master\ArrivalLocation;
use App\Models\Master\ArrivalSubLocation;
use App\Models\Arrival\ArrivalSamplingRequest;
use Illuminate\Http\Request;
use App\Http\Requests\Master\ArrivalSubLocationRequest;
use App\Models\Master\CompanyLocation;
use App\Models\User;

class ArrivalSubLocationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('management.master.arrival_sub_location.index');
    }

    /**
     * Get list of categories.
     */
    public function getList(Request $request)
    {
        $arrival_locations = ArrivalSubLocation::with('companyLocation', 'arrivalLocation')
        ->when($request->filled('search'), function ($q) use ($request) {
            $searchTerm = '%' . $request->search . '%';
            return $q->where(function ($sq) use ($searchTerm) {
                $sq->where('name', 'like', $searchTerm);
            });
        })
            ->where('company_id', $request->company_id)

            ->latest()
            ->paginate(request('per_page', 25));

        return view('management.master.arrival_sub_location.getList', compact('arrival_locations'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $companyLocations = CompanyLocation::where('status', 'active')->get();
        $arrivalLocations = ArrivalLocation::where('status', 'active')->get();
        return view('management.master.arrival_sub_location.create', compact('companyLocations', 'arrivalLocations'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ArrivalSubLocationRequest $request)
    {
        $data = $request->validated();
        $arrival_locations = ArrivalSubLocation::create($request->all());

        return response()->json(['success' => 'Arrival Sub Location created successfully.', 'data' => $arrival_locations], 201);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $arrivalSubLocation = ArrivalSubLocation::findOrFail($id);
        $companyLocations = CompanyLocation::where('status', 'active')->get();
        $arrivalLocations = ArrivalLocation::where('status', 'active')->get();
        return view('management.master.arrival_sub_location.edit', compact('arrivalSubLocation', 'companyLocations', 'arrivalLocations'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ArrivalSubLocationRequest $request, ArrivalSubLocation $arrival_sub_location)
    {
        $data = $request->validated();
        $data = $request->all();
        $arrival_sub_location->update($data);
        return response()->json(['success' => 'Arrival Sub Location updated successfully.', 'data' => $arrival_sub_location], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $arrival_sub_location = ArrivalSubLocation::findOrFail($id);
        $arrival_sub_location->delete();
        return response()->json(['success' => 'Arrival Sub Location deleted successfully.'], 200);
    }


    public function getInitialSamplingResultByTicketId(Request $request)
    {
        $initialRequestForInnerReq = ArrivalSamplingRequest::where('arrival_ticket_id', $request->arrival_ticket_id)
            ->where('sampling_type', 'initial')
            ->where('approved_status', 'approved')
            ->get()->last();

        // Check if related arrivalTicket exists
        if (!$initialRequestForInnerReq) {
            return response()->json(['success' => false, 'message' => 'Arrival ticket not found.'], 404);
        }

        $initialRequestCompulsuryResults  = ArrivalSamplingResultForCompulsury::where('arrival_sampling_request_id', $initialRequestForInnerReq->id)->get();
        $initialRequestResults  = ArrivalSamplingResult::where('arrival_sampling_request_id', $initialRequestForInnerReq->id)->get();
        $sampleTakenByUsers = User::all();

        // Render view with the slabs wrapped inside a div
        $html = view('management.arrival.location_transfer.getInitialQcDetail', compact('initialRequestCompulsuryResults', 'sampleTakenByUsers', 'initialRequestForInnerReq', 'initialRequestResults', 'initialRequestCompulsuryResults', 'initialRequestResults'))->render();
        // dd($html);

        return response()->json(['success' => true, 'html' => $html]);
    }
}
