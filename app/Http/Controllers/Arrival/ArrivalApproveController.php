<?php

namespace App\Http\Controllers\Arrival;

use App\Http\Controllers\Controller;
use App\Models\ArrivalApprove;
use App\Models\Arrival\ArrivalTicket;
use App\Models\BagCondition;
use App\Models\BagPacking;
use App\Models\BagType;
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
        $ArrivalApproves = ArrivalApprove::when($request->filled('search'), function ($q) use ($request) {
            $searchTerm = '%' . $request->search . '%';
            return $q->where(function ($sq) use ($searchTerm) {
                $sq->where('name', 'like', $searchTerm);
            });
        })
            ->with(['bagType', 'bagCondition', 'bagPacking', 'arrivalTicket'])
            ->latest()
            ->paginate(request('per_page', 25));
        // dd($ArrivalApproves);
        return view('management.arrival.approved_arrival.getList', compact('ArrivalApproves'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
      //  $data['ArrivalTickets'] = ArrivalTicket::where('first_weighbridge_status', 'completed')->where('document_approval_status', null)
    //    ->leftJoin('arrival_sampling_requests','arrival_ticket_id','arrival_tickets.id')
  //      ->where('arrival_sampling_requests.sampling_type','inner')->where('is_done','no')
//        ->get();


$data['ArrivalTickets'] = ArrivalTicket::where('first_weighbridge_status', 'completed')
    ->whereNull('document_approval_status')
    ->leftJoin('arrival_sampling_requests', function($join) {
        $join->on('arrival_tickets.id', '=', 'arrival_sampling_requests.arrival_ticket_id')
             ->where('sampling_type', 'inner')
             ->where('is_done', 'no')
             ->where('approved_status', 'pending');
    })
    ->whereNull('arrival_sampling_requests.id') // This excludes tickets that matched the join conditions
    ->select('arrival_tickets.*')
    ->get();
        $data['bagTypes'] = BagType::all();
        $data['bagConditions'] = BagCondition::all();
        $data['bagPackings'] = BagPacking::all();

        return view('management.arrival.approved_arrival.create', $data);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'arrival_ticket_id' => 'required|exists:arrival_tickets,id',
            'gala_name' => 'required|string',
            'truck_no' => 'required|string',
            'filling_bags_no' => 'required|integer',
            'bag_type_id' => 'required|exists:bag_types,id',
            'bag_condition_id' => 'required|exists:bag_conditions,id',
            'bag_packing_id' => 'required|exists:bag_packings,id',
            'bag_packing_approval' => 'required|in:Half Approved,Full Approved',
            'total_bags' => 'required|integer',
            'total_rejection' => 'nullable|integer',
            'amanat' => 'required|in:Yes,No',
            'note' => 'nullable|string'
        ]);



        // Add conditional validation for total_rejection
        $validator->sometimes('total_rejection', 'required|integer|min:1', function ($input) {
            return $input->bag_packing_approval === 'Half Approved' || isset($input->is_rejected_ticket);
        });

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $request['creator_id'] = auth()->user()->id;
        $request['remark'] = $request->note ?? '';

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
            'filling_bags_no' => 'required|integer',
            'bag_type_id' => 'required|exists:bag_types,id',
            'bag_condition_id' => 'required|exists:bag_conditions,id',
            'bag_packing_id' => 'required|exists:bag_packings,id',
            'bag_packing_approval' => 'required|in:Half Approved,Full Approved',
            'total_bags' => 'required|integer',
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
