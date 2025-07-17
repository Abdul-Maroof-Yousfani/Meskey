<?php

namespace App\Http\Controllers\Procurement\Store;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use App\Models\Procurement\Store\PurchaseRequest;
use App\Models\Sales\JobOrder;
use Illuminate\Support\Facades\Validator;

class PurchaseRequestController extends Controller
{
    public function index()
    {
        return view('management.procurement.store.purchase_request.index');
    }

     /**
     * Get list of categories.
     */
    public function getList(Request $request)
    {
        $PurchaseRequests = PurchaseRequest::when($request->filled('search'), function ($q) use ($request) {
            $searchTerm = '%' . $request->search . '%';
            return $q->where(function ($sq) use ($searchTerm) {
                $sq->where('name', 'like', $searchTerm);
            });
        })
        ->latest()
        ->paginate(request('per_page', 25));

        return view('management.procurement.store.purchase_request.getList', compact('PurchaseRequests'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $categories = Category::select('id', 'name')->where('category_type','general_items')->get();
        $job_orders = JobOrder::select('id', 'name')->get();
        return view('management.procurement.store.create',compact('categories','job_orders'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'rare_per_kg' => 'required|string',
            'quantity' => 'required|string',
            'amanat' => 'required|in:Yes,No',
            'remarks' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $request['creator_id'] = auth()->user()->id;
        $request['remark'] = $request->note ?? '';
        $arrivalApprove = PurchaseRequest::create($request->all());


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
        $arrivalApprove = PurchaseRequest::with(['arrivalTicket', 'bagType', 'bagCondition', 'bagPacking'])
            ->findOrFail($id);

        $arrivalTickets = ArrivalTicket::where('first_weighbridge_status', 'completed')->get();
        $bagTypes = BagType::all();
        $bagConditions = BagCondition::all();
        $bagPackings = BagPacking::all();

        return view('management.procurement.store.edit', compact(
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

        return response()->json([
            'success' => 'Arrival Approval updated successfully.',
            'data' => []
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $arrival_location = PurchaseRequest::findOrFail($id);
        $arrival_location->delete();
        return response()->json(['success' => 'Arrival Location deleted successfully.'], 200);
    }

}
