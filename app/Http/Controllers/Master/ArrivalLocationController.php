<?php

namespace App\Http\Controllers\Master;


use App\Http\Controllers\Controller;
use App\Models\Arrival\ArrivalSamplingResult;
use App\Models\Arrival\ArrivalSamplingResultForCompulsury;
use App\Models\Master\ArrivalLocation;
use App\Models\Arrival\ArrivalSamplingRequest;
use Illuminate\Http\Request;
use App\Http\Requests\Master\ArrivalLocationRequest;
use App\Models\Master\CompanyLocation;
use App\Models\User;

class ArrivalLocationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('management.master.arrival_location.index');
    }

    /**
     * Get list of categories.
     */
    public function getList(Request $request)
    {
        $arrival_locations = ArrivalLocation::with('companyLocation')
        ->when($request->filled('search'), function ($q) use ($request) {
            $searchTerm = '%' . $request->search . '%';
            return $q->where(function ($sq) use ($searchTerm) {
                $sq->where('name', 'like', $searchTerm);
            });
        })
            ->where('company_id', $request->company_id)

            ->latest()
            ->orderBy('company_location_id')
            ->paginate(request('per_page', 25));

        return view('management.master.arrival_location.getList', compact('arrival_locations'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $companyLocations = CompanyLocation::where('status', 'active')->get();
        return view('management.master.arrival_location.create', compact('companyLocations'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ArrivalLocationRequest $request)
    {
        $data = $request->validated();
        $arrival_locations = ArrivalLocation::create($request->all());

        return response()->json(['success' => 'Arrival Location created successfully.', 'data' => $arrival_locations], 201);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $arrivalLocation = ArrivalLocation::findOrFail($id);
        $companyLocations = CompanyLocation::where('status', 'active')->get();
        return view('management.master.arrival_location.edit', compact('arrivalLocation', 'companyLocations'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ArrivalLocationRequest $request, ArrivalLocation $arrival_location)
    {
        $data = $request->validated();
        $data = $request->all();
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
