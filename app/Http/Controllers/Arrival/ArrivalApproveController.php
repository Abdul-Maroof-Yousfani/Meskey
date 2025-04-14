<?php

namespace App\Http\Controllers\Arrival;

use App\Http\Controllers\Controller;
use App\Models\ArrivalApprove;
use App\Models\Arrival\ArrivalTicket;
use Illuminate\Http\Request;

class ArrivalApproveController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('management.arrival.approved_arrival.index');
    }

    /**
     * Get list of categories.
     */
    public function getList(Request $request)
    {
        $ArrivalSamplingRequests = ArrivalApprove::when($request->filled('search'), function ($q) use ($request) {
            $searchTerm = '%' . $request->search . '%';
            return $q->where(function ($sq) use ($searchTerm) {
                $sq->where('name', 'like', $searchTerm);
            });
        })
            ->where('company_id', $request->company_id)

            ->latest()
            ->paginate(request('per_page', 25));

        return view('management.arrival.approved_arrival.getList', compact('ArrivalSamplingRequests'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $data['ArrivalTickets'] =  ArrivalTicket::where('first_weighbridge_status', 'completed')->get();
        return view('management.arrival.approved_arrival.create', $data);
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
