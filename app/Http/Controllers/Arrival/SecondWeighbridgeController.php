<?php

namespace App\Http\Controllers\Arrival;

use App\Http\Controllers\Controller;

use App\Models\Arrival\ArrivalSamplingRequest;
use App\Models\Arrival\ArrivalTicket;
use App\Models\FirstWeighbridge;
use App\Models\Arrival\ArrivalLocationTransfer;
use App\Models\Arrival\SecondWeighbridge;
use App\Models\Master\ArrivalLocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SecondWeighbridgeController extends Controller
{


        
    function __construct()
    {
        $this->middleware('check.company:arrival-second-weighbridge', ['only' => ['index']]);
        $this->middleware('check.company:arrival-second-weighbridge', ['only' => ['edit']]);
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('management.arrival.second_weighbridge.index');
    }

    /**
     * Get list of categories.
     */
    public function getList(Request $request)
    {
        $authUser = auth()->user();
        $isSuperAdmin = $authUser->user_type === 'super-admin';

        $ArrivalSamplingRequests = SecondWeighbridge::with(['arrivalTicket.unloadingLocation.arrivalLocation'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $searchTerm = '%' . $request->search . '%';
                return $q->where(function ($sq) use ($searchTerm) {
                    $sq->where('name', 'like', $searchTerm);
                });
            })
            ->when(!$isSuperAdmin, function ($q) use ($authUser) {
                return $q->whereHas('arrivalTicket.unloadingLocation', function ($query) use ($authUser) {
                    $query->where('arrival_location_id', $authUser->arrival_location_id);
                });
            })
            ->latest()
            ->paginate($request->get('per_page', 25));

        return view('management.arrival.second_weighbridge.getList', compact('ArrivalSamplingRequests'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $authUser = auth()->user();
        $isSuperAdmin = $authUser->user_type === 'super-admin';

        $data = [
            'ArrivalLocations' => ArrivalLocation::where('status', 'active')->get(),
            'ArrivalTickets' => ArrivalTicket::with('unloadingLocation')
                ->where('second_weighbridge_status', 'pending')
                ->when(!$isSuperAdmin, function ($query) use ($authUser) {
                    return $query->whereHas('unloadingLocation', function ($q) use ($authUser) {
                        $q->where('arrival_location_id', $authUser->arrival_location_id);
                    });
                })
                ->get()
        ];

        return view('management.arrival.second_weighbridge.create', $data);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'arrival_ticket_id' => 'required|exists:arrival_tickets,id',
            'second_weight' => 'required|numeric',
            'remark' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $request['created_by'] = auth()->user()->id;
        $request['weight'] = $request->second_weight ?? 0;
        $arrival_locations = SecondWeighbridge::create($request->all());

        ArrivalTicket::where('id', $request->arrival_ticket_id)
            ->update(
                ['second_weighbridge_status' => 'completed', 'arrived_net_weight' => $request->weighbridge_net_weight, 'freight_status' => 'pending']
            );

        return response()->json(['success' => 'Second Weighbridge created successfully.', 'data' => $arrival_locations], 201);
    }

    /**
     * Show the form for editing the specified resource.
     */
    // public function edit($id)
    // {
    //     $arrival_location = ArrivalLocation::findOrFail($id);
    //     return view('management.master.arrival_location.edit', compact('arrival_location'));
    // }

    public function edit($id)
    {
        $data['SecondWeighbridge'] = SecondWeighbridge::findOrFail($id);
        $data['ArrivalLocations'] =  ArrivalLocation::where('status', 'active')->get();
        $data['ArrivalTickets'] =  ArrivalTicket::where('second_weighbridge_status', 'pending')->get();
        $data['ArrivalTicket'] =  ArrivalTicket::where('id',  $data['SecondWeighbridge']->arrival_ticket_id)->first();

        return view('management.arrival.second_weighbridge.edit', $data);
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

    public function getSecondWeighbridgeRelatedData(Request $request)
    {

        // dd($request);

        $ArrivalTicket = ArrivalTicket::findOrFail($request->arrival_ticket_id);
        $first = FirstWeighbridge::where('arrival_ticket_id', $request->arrival_ticket_id)->first();

        // Render view with the slabs wrapped inside a div
        $html = view('management.arrival.second_weighbridge.getSecondWeighbridgeRelatedData', compact('ArrivalTicket'))->render();

        return response()->json(['success' => true, 'html' => $html]);
    }
}
