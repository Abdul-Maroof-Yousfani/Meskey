<?php

namespace App\Http\Controllers\Procurement\RawMaterial;


use App\Http\Controllers\Controller;
use App\Models\Procurement\PurchaseOrder;
use App\Models\Product;
use App\Models\TruckSizeRange;
use Illuminate\Http\Request;

class PurchaseOrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('management.procurement.raw_material.purchase_order.index');
    }

    /**
     * Get list of categories.
     */
    public function getList(Request $request)
    {
        $ArrivalApproves = PurchaseOrder::when($request->filled('search'), function ($q) use ($request) {
            $searchTerm = '%' . $request->search . '%';
            return $q->where(function ($sq) use ($searchTerm) {
                $sq->where('name', 'like', $searchTerm);
            });
        })
            ->with(['bagType', 'bagCondition', 'bagPacking', 'arrivalTicket'])
            ->latest()
            ->paginate(request('per_page', 25));
        // dd($ArrivalApproves);
        return view('management.procurement.raw_material.purchase_order.getList', compact('ArrivalApproves'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $data['bagPackings'] = [];
        $data['truckSizeRanges'] = TruckSizeRange::where('status', 'active')->get();
        $data['products'] = Product::where('product_type', 'raw_material')->get();

        return view('management.procurement.raw_material.purchase_order.create', $data);
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
    public function getMainSlabByProduct(Request $request)
    {
        dd('ddddd');
        return response()->json(['success' => 'Arrival Location deleted successfully.'], 200);
    }
}
