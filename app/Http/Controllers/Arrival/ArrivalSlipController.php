<?php

namespace App\Http\Controllers\Arrival;

use App\Http\Controllers\Controller;

use App\Models\Arrival\ArrivalSamplingRequest;
use App\Models\Arrival\ArrivalSamplingResult;
use App\Models\Arrival\ArrivalSamplingResultForCompulsury;
use App\Models\Arrival\ArrivalTicket;
use App\Models\Arrival\ArrivalSlip;
use App\Models\Master\ArrivalLocation;
use App\Models\Master\ProductSlab;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;



class ArrivalSlipController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('management.arrival.arrival_slip.index');
    }

    /**
     * Get list of categories.
     */
    public function getList(Request $request)
    {
        $ArrivalSlip = ArrivalSlip::select('arrival_slips.*', 'grn_numbers.unique_no as grn_unique_no')
            ->leftJoin('grn_numbers', function ($join) {
                $join->on('arrival_slips.id', '=', 'grn_numbers.model_id')
                    ->where('grn_numbers.model_type', 'arrival-slip');
            })
            ->with(['arrivalTicket'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $searchTerm = '%' . $request->search . '%';
                return $q->where(function ($sq) use ($searchTerm) {
                    $sq->where('arrival_slips.unique_no', 'like', $searchTerm);
                });
            })
            ->where('arrival_slips.company_id', $request->company_id)
            ->latest('arrival_slips.created_at')
            ->paginate(request('per_page', 25));

        return view('management.arrival.arrival_slip.getList', compact('ArrivalSlip'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $data['ArrivalLocations'] =  ArrivalLocation::where('status', 'active')->get();
        $data['ArrivalTickets'] =  ArrivalTicket::where('arrival_slip_status', 'pending')->get();
        return view('management.arrival.arrival_slip.create', $data);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'arrival_ticket_id' => 'required|exists:arrival_tickets,id',
            'remarks' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $datePrefix = date('m-d-Y') . '-';
        $request['unique_no'] = generateUniqueNumberByDate('arrival_slips', $datePrefix, null, 'unique_no');
        $request['creator_id'] = auth()->user()->id;
        $request['remark'] = $request->note ?? '';
        $arrivalApprove = ArrivalSlip::create($request->all());

        return response()->json([
            'success' => 'Arrival Slip generated successfully.',
            'data' => $arrivalApprove
        ], 201);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $ArrivalTickets =  ArrivalTicket::where('second_weighbridge_status', 'completed')->get();
        $arrival_slip = ArrivalSlip::findOrFail($id);

        $arrivalTicket = ArrivalTicket::with([
            'product',
            'unloadingLocation.arrivalLocation',
            'arrivalSlip',
            'firstWeighbridge',
            'purchaseOrder'
        ])->findOrFail($arrival_slip->arrival_ticket_id);
        // dd(1);
        $samplingRequest = ArrivalSamplingRequest::where('arrival_ticket_id', $arrivalTicket->id)
            // ->where('sampling_type', 'initial')
            ->whereIn('approved_status', ['approved', 'rejected'])
            ->get()->last();

        $samplingRequestCompulsuryResults  = ArrivalSamplingResultForCompulsury::where('arrival_sampling_request_id', $samplingRequest->id)->get();
        $samplingRequestResults  = ArrivalSamplingResult::where('arrival_sampling_request_id', $samplingRequest->id)->get();

        $slabs = ProductSlab::where('product_id', $samplingRequest->arrival_product_id)
            ->get()
            ->groupBy('product_slab_type_id')
            ->map(function ($group) {
                return $group->sortBy('from')->first();
            });

        $samplingRequestResults->map(function ($item) use ($slabs) {
            $slab = $slabs->get($item->product_slab_type_id);
            $item->max_range = $slab ? $slab->to : null;
            $item->deduction_type = $slab ? $slab->deduction_type : null;
            return $item;
        });

        $isNotGeneratable = false;

        $isNotGeneratable = $arrivalTicket->decision_making == 1;

        return view('management.arrival.arrival_slip.edit', compact('ArrivalTickets', 'arrival_slip', 'arrivalTicket', 'isNotGeneratable', 'samplingRequest', 'samplingRequestCompulsuryResults', 'samplingRequestResults'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ArrivalLocation $arrival_location)
    {
        $data = $request->validated();
        $arrival_location->update($data);
        return response()->json(['success' => 'Arrival Slip updated successfully.', 'data' => $arrival_location], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $arrival_location = ArrivalLocation::findOrFail($id);
        $arrival_location->delete();
        return response()->json(['success' => 'Arrival Slip deleted successfully.'], 200);
    }


    public function getTicketDataForArrival(Request $request)
    {
        $arrivalTicket = ArrivalTicket::with([
            'product',
            'unloadingLocation.arrivalLocation',
            'arrivalSlip',
            'firstWeighbridge',
            'purchaseOrder'
        ])->findOrFail($request->arrival_ticket_id);

        $samplingRequest = ArrivalSamplingRequest::where('arrival_ticket_id', $request->arrival_ticket_id)
            // ->where('sampling_type', 'initial')
            ->whereIn('approved_status', ['approved', 'rejected'])
            ->get()->last();

        $samplingRequestCompulsuryResults  = ArrivalSamplingResultForCompulsury::where('arrival_sampling_request_id', $samplingRequest->id)->get();
        $samplingRequestResults  = ArrivalSamplingResult::where('arrival_sampling_request_id', $samplingRequest->id)->get();

        $slabs = ProductSlab::where('product_id', $samplingRequest->arrival_product_id)
            ->get()
            ->groupBy('product_slab_type_id')
            ->map(function ($group) {
                return $group->sortBy('from')->first();
            });

        $samplingRequestResults->map(function ($item) use ($slabs) {
            $slab = $slabs->get($item->product_slab_type_id);
            $item->max_range = $slab ? $slab->to : null;
            $item->deduction_type = $slab ? $slab->deduction_type : null;
            return $item;
        });

        $isNotGeneratable = false;

        $isNotGeneratable = $arrivalTicket->decision_making == 1;

        $html = view('management.arrival.arrival_slip.getTicketDataForArrival', compact('arrivalTicket', 'isNotGeneratable', 'samplingRequest', 'samplingRequestCompulsuryResults', 'samplingRequestResults'))->render();

        return response()->json(['success' => true, 'html' => $html, 'isNotGeneratable' => $isNotGeneratable]);
    }
}
