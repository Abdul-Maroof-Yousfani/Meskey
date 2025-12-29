<?php

namespace App\Http\Controllers\Arrival;


use App\Http\Controllers\Controller;
use App\Http\Requests\Master\FirstWeighbridgeRequest;
use App\Models\Arrival\ArrivalSamplingRequest;
use App\Models\Arrival\ArrivalTicket;
use App\Models\Arrival\ArrivalLocationTransfer;
use App\Models\Master\ArrivalLocation;
use App\Models\FirstWeighbridge;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FirstWeighbridgeController extends Controller
{



    
    function __construct()
    {
        $this->middleware('check.company:arrival-first-weighbridge', ['only' => ['index']]);
    }
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
        $authUser = auth()->user();
        $isSuperAdmin = $authUser->user_type === 'super-admin';

        $query = FirstWeighbridge::with(['arrivalTicket.unloadingLocation.arrivalLocation'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $searchTerm = '%' . $request->search . '%';
                return $q->where(function ($sq) use ($searchTerm) {
                    $sq->where('name', 'like', $searchTerm);
                });
            })
            ->where('company_id', $request->company_id)
            // ->when(!$isSuperAdmin, function ($q) use ($authUser) {
            //     return $q->whereHas('arrivalTicket.unloadingLocation', function ($query) use ($authUser) {
            //         $query->where('arrival_location_id', $authUser->arrival_location_id);
            //     });
            // });
            ->whereHas('arrivalTicket.unloadingLocation', function ($q) {
                $q->whereIn('arrival_location_id', getUserCurrentCompanyArrivalLocations());
            });

        $ArrivalSamplingRequests = $query->latest()
            ->paginate($request->get('per_page', 25));

        return view('management.arrival.first_weighbridge.getList', compact('ArrivalSamplingRequests'));
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
                ->where('first_weighbridge_status', 'pending')
                ->whereHas('unloadingLocation', function ($q) {
                    $q->whereIn('arrival_location_id', getUserCurrentCompanyArrivalLocations());
                })
                // ->when(!$isSuperAdmin, function ($query) use ($authUser) {
                //     return $query->whereHas('unloadingLocation', function ($q) use ($authUser) {
                //         $q->where('arrival_location_id', $authUser->arrival_location_id);
                //     });
                // })
            ->get()
        ];

        return view('management.arrival.first_weighbridge.create', $data);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(FirstWeighbridgeRequest $request)
    {
        $arrivalTicket = ArrivalTicket::findOrFail($request->arrival_ticket_id);

        if ($arrivalTicket->first_weighbridge_status !== 'pending') {
            return response('First weighbridge has already been completed and cannot be performed again.', 422);
        }

        $request['created_by'] = auth()->user()->id;
        $request['weight'] = $request->first_weight ?? 0;
        $arrival_locations = FirstWeighbridge::create($request->all());

        $arrivalTicket->update([
            'first_weighbridge_status' => 'completed'
        ]);

        return response()->json(['success' => 'First weighbridge created successfully.', 'data' => $arrival_locations], 201);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $data['firstWeighbridge'] = FirstWeighbridge::findOrFail($id);
        $data['arrivalTickets'] = ArrivalTicket::where('first_weighbridge_status', 'completed')->get();

        return view('management.arrival.first_weighbridge.edit', $data);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(FirstWeighbridgeRequest $request, ArrivalLocation $arrival_location)
    {
        $arrival_location->update($request->all());
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


    public function getFirstWeighbridgeRelatedData(Request $request)
    {

        $ArrivalTicket = ArrivalTicket::findOrFail($request->arrival_ticket_id);


        // Render view with the slabs wrapped inside a div
        $html = view('management.arrival.first_weighbridge.getFirstWeighbridgeRelatedData', compact('ArrivalTicket'))->render();

        return response()->json(['success' => true, 'html' => $html]);
    }
}
