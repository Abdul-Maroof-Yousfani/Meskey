<?php

namespace App\Http\Controllers\Procurement\RawMaterial;


use App\Http\Controllers\Controller;
use App\Models\ArrivalPurchaseOrder;
use App\Models\PurchaseSamplingRequest;
use Illuminate\Http\Request;
use App\Models\Master\ArrivalLocation;
use App\Models\Arrival\ArrivalTicket;
use App\Models\PurchaseTicket;
use Illuminate\Support\Facades\Validator;

class PurchaseSamplingRequestController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('management.procurement.raw_material.purchase_sampling_request.index');
    }


    public function getList(Request $request)
    {
        $purchaseOrders = ArrivalPurchaseOrder::where('sauda_type_id', 2)
            ->when($request->filled('supplier_id'), function ($query) use ($request) {
                $query->where('supplier_id', $request->supplier_id);
            })
            ->when($request->filled('product_id'), function ($query) use ($request) {
                $query->where('product_id', $request->product_id);
            })
            ->when($request->filled('company_location_id'), function ($query) use ($request) {
                $query->where('company_location_id', $request->company_location_id);
            })
            ->when($request->filled('search'), function ($query) use ($request) {
                $query->where(function ($q) use ($request) {
                    $q->where('contract_no', 'like', '%' . $request->search . '%')
                        ->orWhereHas('supplier', function ($q) use ($request) {
                            $q->where('name', 'like', '%' . $request->search . '%');
                        });
                });
            })
            ->with(['supplier', 'product', 'location'])
            ->latest()
            ->paginate(request('per_page', 25));

        return view('management.procurement.raw_material.purchase_sampling_request.getList', compact(
            'purchaseOrders'
        ));
    }
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $data['purchaseOrders'] = ArrivalPurchaseOrder::
            // whereDoesntHave('purchaseSamplingRequests')->
            where('sauda_type_id', 2)
            ->get();

        return view('management.procurement.raw_material.purchase_sampling_request.create', $data);
    }

    public function createRequest(Request $request)
    {
        if (!$request->has('id')) {
            return response()->json(['error' => 'ID is required'], 422);
        }

        $purchaseOrder = ArrivalPurchaseOrder::find($request->id);

        if (!$purchaseOrder) {
            return response()->json(['error' => 'Purchase Order not found'], 404);
        }

        $data['purchaseOrder'] = $purchaseOrder;
        $data['ind'] = 1;

        return view('management.procurement.raw_material.purchase_sampling_request.create', $data);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $isIndividual = isset($request->is_ind) && $request->is_ind == 1 ? true : false;
        $isCustomQc = convertToBoolean($request->is_custom_qc ?? 'off');

        $validator = Validator::make($request->all(), [
            'purchase_contract_id' => $isCustomQc ? 'nullable' : 'required',
            'product_id' => $isCustomQc ? 'required' : 'nullable',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $datePrefix = date('m-d-Y') . '-';
        $unique_no = generateUniqueNumberByDate('purchase_tickets', $datePrefix, null, 'unique_no');

        $purchaseTicket = PurchaseTicket::create([
            'unique_no' => $unique_no,
            'company_id' => $request->company_id,
            'purchase_order_id' => $request->purchase_contract_id ?? null,
            'product_id' => $request->product_id ?? null,
            'is_custom_qc' => $isCustomQc ? 'yes' : 'no',
            'qc_status' => 'pending',
            'freight_status' => 'pending',
        ]);

        $arrivalSampleReq = null;

        // if ($isIndividual) {
        $arrivalSampleReq = PurchaseSamplingRequest::create([
            'company_id'       => $request->company_id,
            'purchase_ticket_id'       => $purchaseTicket->id,
            'arrival_purchase_order_id' => $request->purchase_contract_id ?? null,
            'supplier_name' => $request->supplier_name ?? null,
            'address' => $request->address ?? null,
            'is_custom_qc' => $isCustomQc ? 'yes' : 'no',
            'sampling_type'    => 'initial',
            'is_re_sampling'   => 'no',
            'is_done'          => 'no',
            'remark'           => null,
        ]);
        // }

        return response()->json(['success' => 'Inner Sampling Request created successfully.', 'data' => $arrivalSampleReq], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(PurchaseSamplingRequest $purchaseSamplingRequest)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(PurchaseSamplingRequest $purchaseSamplingRequest)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PurchaseSamplingRequest $purchaseSamplingRequest)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PurchaseSamplingRequest $purchaseSamplingRequest)
    {
        //
    }
}
