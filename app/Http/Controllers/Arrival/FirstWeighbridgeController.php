<?php

namespace App\Http\Controllers\Arrival;


use App\Http\Controllers\Controller;


use App\Models\Arrival\ArrivalSamplingRequest;
use App\Models\Arrival\ArrivalTicket;
use App\Models\Arrival\ArrivalLocationTransfer;
use App\Models\Master\ArrivalLocation;
use App\Models\FirstWeighbridge;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FirstWeighbridgeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('management.arrival.first_weighbridge.index');
    }

    /**
     * Get list of categories.
     */
    public function getList(Request $request)
    {
        $ArrivalSamplingRequests = FirstWeighbridge::when($request->filled('search'), function ($q) use ($request) {
            $searchTerm = '%' . $request->search . '%';
            return $q->where(function ($sq) use ($searchTerm) {
                $sq->where('name', 'like', $searchTerm);
            });
        })
            ->where('company_id', $request->company_id)
            ->latest()
            ->paginate(request('per_page', 25));

        return view('management.arrival.first_weighbridge.getList', compact('ArrivalSamplingRequests'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $data['ArrivalLocations'] =  ArrivalLocation::where('status', 'active')->get();
        $data['ArrivalTickets'] =  ArrivalTicket::where('first_weighbridge_status', 'pending')->get();
        return view('management.arrival.first_weighbridge.create', $data);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'arrival_ticket_id' => 'required|exists:arrival_tickets,id',
            'first_weight' => 'required|numeric',
            'remark' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $request['created_by'] = auth()->user()->id;
        $request['weight'] = $request->first_weight ?? 0;
        $arrival_locations = FirstWeighbridge::create($request->all());

        ArrivalTicket::where('id', $request->arrival_ticket_id)
            ->update(['first_weighbridge_status' => 'completed']);

        return response()->json(['success' => 'Arrival Location created successfully.', 'data' => $arrival_locations], 201);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $data['arrival_location'] = FirstWeighbridge::findOrFail($id);
        $data['ArrivalLocations'] =  ArrivalLocation::where('status', 'active')->get();
        $data['ArrivalTickets'] =  ArrivalTicket::where('first_weighbridge_status', 'pending')->get();

        return view('management.arrival.first_weighbridge.edit', $data);
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
