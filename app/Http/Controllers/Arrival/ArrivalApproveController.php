<?php

namespace App\Http\Controllers\Arrival;

use App\Http\Controllers\Controller;
use App\Models\ArrivalApprove;
use App\Models\Arrival\ArrivalTicket;
use App\Models\BagCondition;
use App\Models\BagPacking;
use App\Models\BagType;
use App\Models\Master\ArrivalSubLocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

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
        $authUser = auth()->user();
        $isSuperAdmin = $authUser->user_type === 'super-admin';

        $ArrivalApproves = ArrivalApprove::when($request->filled('search'), function ($q) use ($request) {
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
            ->with(['bagType', 'bagCondition', 'bagPacking', 'arrivalTicket'])
            ->latest()
            ->paginate(request('per_page', 25));
        return view('management.arrival.approved_arrival.getList', compact('ArrivalApproves'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $authUser = auth()->user();
        $isSuperAdmin = $authUser->user_type === 'super-admin';

        $data['ArrivalTickets'] = ArrivalTicket::where('first_weighbridge_status', 'completed')
            ->whereNull('document_approval_status')
            ->leftJoin('arrival_sampling_requests', function ($join) {
                $join->on('arrival_tickets.id', '=', 'arrival_sampling_requests.arrival_ticket_id')
                    ->where('sampling_type', 'inner')
                    ->where('approved_status', 'pending')
                    ->where('arrival_sampling_requests.deleted_at', null);
            })
            ->whereNull('arrival_sampling_requests.id')
            ->when(!$isSuperAdmin, function ($q) use ($authUser) {
                return $q->whereHas('unloadingLocation', function ($query) use ($authUser) {
                    $query->where('arrival_location_id', $authUser->arrival_location_id);
                });
            })
            ->select('arrival_tickets.*')
            ->get();

        $data['bagTypes'] = BagType::all();
        $data['bagConditions'] = BagCondition::all();
        $data['bagPackings'] = BagPacking::all();
        $data['arrivalSubLocations'] = ArrivalSubLocation::where('status', 'Active')
         ->when(auth()->user()->user_type != 'super-admin', function ($q) {
                return $q->where('arrival_location_id', auth()->user()->arrival_location_id);
            })
        ->get();

        return view('management.arrival.approved_arrival.create', $data);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $ticket = ArrivalTicket::where('id', $request->arrival_ticket_id)
            ->where('first_weighbridge_status', 'completed')
            ->whereNull('document_approval_status')
            ->whereNotExists(function ($query) {
                $query->select('id')
                    ->from('arrival_sampling_requests')
                    ->whereColumn('arrival_ticket_id', 'arrival_tickets.id')
                    ->where('sampling_type', 'inner')
                    ->where('approved_status', 'pending')
                    ->where('arrival_sampling_requests.deleted_at', null);
            })
            ->first();

        if (!$ticket) {
            return response()->json([
                'errors' => ['arrival_ticket_id' => ['This ticket is not eligible for approval. Please check if it has completed first weighbridge and has no pending inner sampling requests.']]
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'arrival_ticket_id' => 'required|exists:arrival_tickets,id',
            'gala_id' => 'required|exists:arrival_sub_locations,id',
            'truck_no' => 'required|string',
            'bag_type_id' => 'required|exists:bag_types,id',
            // 'filling_bags_no' => 'required|integer',
            // 'bag_condition_id' => 'required|exists:bag_conditions,id',
            // 'bag_packing_id' => 'required|exists:bag_packings,id',
            'bag_packing_approval' => 'required|in:Half Approved,Full Approved',
            'total_bags' => 'required|integer|min:1',
            'total_rejection' => 'nullable|integer',
            'amanat' => 'required|in:Yes,No',
            'note' => 'nullable|string'
        ]);

        $gala_name = ArrivalSubLocation::where('id', $request->gala_id)->value('name');
        
        $validator->sometimes('total_rejection', 'required|integer|min:1', function ($input) {
            return $input->bag_packing_approval === 'Half Approved' || isset($input->is_rejected_ticket);
        });

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $request['creator_id'] = auth()->user()->id;
        $request['remark'] = $request->note ?? '';
        $request['gala_name'] = $gala_name;

        $arrivalApprove = ArrivalApprove::create($request->all());

        ArrivalTicket::where('id', $request->arrival_ticket_id)
            ->update(['document_approval_status' => $request->bag_packing_approval == 'Half Approved' ? 'half_approved' : 'fully_approved', 'second_weighbridge_status' => 'pending']);

        return response()->json([
            'success' => 'Arrival Approval created successfully.',
            'data' => $arrivalApprove
        ], 201);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $arrivalApprove = ArrivalApprove::with(['arrivalTicket', 'bagType', 'bagCondition', 'bagPacking'])
            ->findOrFail($id);

        $arrivalTickets = ArrivalTicket::where('first_weighbridge_status', 'completed')->get();
        $bagTypes = BagType::all();
        $bagConditions = BagCondition::all();
        $bagPackings = BagPacking::all();

        return view('management.arrival.approved_arrival.edit', compact(
            'arrivalApprove',
            'arrivalTickets',
            'bagTypes',
            'bagConditions',
            'bagPackings'
        ));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'arrival_ticket_id' => 'required|exists:arrival_tickets,id',
            'gala_name' => 'required|string',
            'truck_no' => 'required|string',
            'bag_type_id' => 'required|exists:bag_types,id',
            // 'filling_bags_no' => 'required|integer',
            // 'bag_condition_id' => 'required|exists:bag_conditions,id',
            // 'bag_packing_id' => 'required|exists:bag_packings,id',
            'bag_packing_approval' => 'required|in:Half Approved,Full Approved',
            'total_bags' => 'required|integer|min:1',
            'total_rejection' => 'nullable|integer',
            'amanat' => 'required|in:Yes,No',
            'note' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $arrivalApprove = ArrivalApprove::findOrFail($id);
        $request['remark'] = $request->note ?? '';
        $arrivalApprove->update($request->all());

        ArrivalTicket::where('id', $request->arrival_ticket_id)
            ->update(['document_approval_status' => $request->bag_packing_approval == 'Half Approved' ? 'half_approved' : 'full_approved', 'second_weighbridge_status' => 'pending']);

        return response()->json([
            'success' => 'Arrival Approval updated successfully.',
            'data' => $arrivalApprove
        ], 200);
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
