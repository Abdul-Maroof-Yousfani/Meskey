<?php

namespace App\Http\Controllers\Procurement\RawMaterial;


use App\Http\Controllers\Controller;
use App\Http\Requests\ArrivalPurchaseOrderRequest;
use App\Models\ArrivalPurchaseOrder;
use App\Models\Master\CompanyLocation;
use App\Models\Master\ProductSlab;
use App\Models\Procurement\PurchaseOrder;
use App\Models\Product;
use App\Models\ProductSlabForRmPo;
use App\Models\TruckSizeRange;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
    public function store(ArrivalPurchaseOrderRequest $request)
    {
        $data = $request->validated();
        $data = $request->all();
        $arrivalPurchaseOrder = null;

        DB::transaction(function () use ($data) {
            $arrivalPOData = collect($data)->except(['slabs', 'quantity_range', 'truck_size_range'])->toArray();

            // Rename 'truck_size_range' to match db column
            if (isset($data['truck_size_range'])) {
                $arrivalPOData['truck_size_range_id'] = $data['truck_size_range'];
            }

            $arrivalPurchaseOrder = ArrivalPurchaseOrder::create($arrivalPOData);

            foreach ($data['slabs'] as $slabId => $range) {
                ProductSlabForRmPo::create([
                    'arrival_purchase_order_id' => $arrivalPurchaseOrder->id,
                    'slab_id' => $slabId,
                    'company_id' => $data['company_id'],
                    'product_id' => $data['product_id'],
                    'product_slab_type_id' => null, // if available, set accordingly
                    'from' => $range['from'],
                    'to' => $range['to'],
                    'deduction_type' => null, // if available, set accordingly
                    'deduction_value' => null, // if available, set accordingly
                    'status' => 1, // or default status
                ]);
            }
        });

        return response()->json([
            'success' => 'Purchase Order Created Successfully.',
            'data' => $arrivalPurchaseOrder
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
        $data = ProductSlab::with('slabType')
            ->where('product_id', $request->product_id)
            ->where('company_id', $request->company_id)
            ->where('is_purchase_field', 1)
            ->get()
            ->groupBy('product_slab_type_id')
            ->map(function ($group) {
                return $group->sortBy(function ($item) {
                    return (float) $item->from;
                })->first();
            })
            ->values()
            ->map(function ($item) {
                $item['slab_type_name'] = $item->slabType->name ?? null;
                return $item;
            });

        $html = view('management.procurement.raw_material.purchase_order.slab-form', ['slabs' => $data, 'success' => '.'])->render();

        return response()->json(['html' => $html, 'success' => '.'], 200);
    }

    public function getContractNumber(Request $request)
    {
        $location = CompanyLocation::find($request->location_id);
        $date = Carbon::parse($request->contract_date)->format('Y-m-d');

        $prefix = $location->code . '-' . Carbon::parse($request->contract_date)->format('Ymd');

        $latestContract = ArrivalPurchaseOrder::where('contract_no', 'like', "$prefix-%")
            ->latest()
            ->first();

        $locationCode = $location->code ?? 'LOC';
        $datePart = Carbon::parse($date)->format('Y-m-d');

        if ($latestContract) {
            $parts = explode('-', $latestContract->contract_no);
            $lastNumber = (int)end($parts);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        $contractNo = $locationCode . '-' . $datePart . '-' . str_pad($newNumber, 3, '0', STR_PAD_LEFT);

        return response()->json([
            'success' => true,
            'contract_no' => $contractNo
        ]);
    }
}
