<?php

namespace App\Http\Controllers\Procurement\RawMaterial;


use App\Http\Controllers\Controller;
use App\Models\ArrivalPurchaseOrder;
use App\Models\PurchaseSamplingRequest;
use Illuminate\Http\Request;
use App\Models\Master\ArrivalLocation;
use App\Models\Arrival\ArrivalTicket;
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
        $ArrivalSamplingRequests = PurchaseSamplingRequest::where('sampling_type', 'initial')->when($request->filled('search'), function ($q) use ($request) {
            $searchTerm = '%' . $request->search . '%';
            return $q->where(function ($sq) use ($searchTerm) {
                $sq->where('name', 'like', $searchTerm);
            });
        })
            ->where('company_id', $request->company_id)

            ->latest()
            ->paginate(request('per_page', 25));

        return view('management.procurement.raw_material.purchase_sampling_request.getList', compact('ArrivalSamplingRequests'));
    }
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $data['purchaseOrders'] = ArrivalPurchaseOrder::where('sauda_type_id', 2)->get();
        return view('management.procurement.raw_material.purchase_sampling_request.create', $data);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'purchase_contract_id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // $arrival_locations = ArrivalLocation::create($request->all());

        $arrivalSampleReq = PurchaseSamplingRequest::create([
            'company_id'       => $request->company_id,
            'arrival_purchase_order_id' => $request->purchase_contract_id,
            'sampling_type'    => 'initial',
            'is_re_sampling'   => 'no',
            'is_done'          => 'no',
            'remark'           => null,
        ]);

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
