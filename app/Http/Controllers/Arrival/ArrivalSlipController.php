<?php

namespace App\Http\Controllers\Arrival;

use App\Http\Controllers\Controller;

use App\Models\Arrival\ArrivalSamplingRequest;
use App\Models\Arrival\ArrivalSamplingResult;
use App\Models\Arrival\ArrivalSamplingResultForCompulsury;
use App\Models\Arrival\ArrivalTicket;
use App\Models\Arrival\ArrivalSlip;
use App\Models\Master\ArrivalLocation;
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
        $ArrivalSlip = ArrivalSlip::when($request->filled('search'), function ($q) use ($request) {
            $searchTerm = '%' . $request->search . '%';
            return $q->where(function ($sq) use ($searchTerm) {
                $sq->where('name', 'like', $searchTerm);
            });
        })
            ->where('company_id', $request->company_id)

            ->latest()
            ->paginate(request('per_page', 25));

        return view('management.arrival.arrival_slip.getList', compact('ArrivalSlip'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $data['ArrivalLocations'] =  ArrivalLocation::where('status', 'active')->get();
        $data['ArrivalTickets'] =  ArrivalTicket::where('second_weighbridge_status', 'completed')->get();
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

        $samplingRequest = ArrivalSamplingRequest::where('arrival_ticket_id', $arrivalTicket->id)
            // ->where('sampling_type', 'initial')
            ->where('approved_status', 'approved')
            ->get()->last();

        $samplingRequestCompulsuryResults  = ArrivalSamplingResultForCompulsury::where('arrival_sampling_request_id', $samplingRequest->id)->get();
        $samplingRequestResults  = ArrivalSamplingResult::where('arrival_sampling_request_id', $samplingRequest->id)->get();

        $isNotGeneratable = false;

        if ($arrivalTicket->decision_making == 1) {
            $isNotGeneratable = ($arrivalTicket->lumpsum_deduction == 0.00 && $arrivalTicket->lumpsum_deduction_kgs == 0.00);
        }

        return view('management.arrival.arrival_slip.edit', compact('ArrivalTickets', 'arrival_slip', 'arrivalTicket', 'isNotGeneratable', 'samplingRequest', 'samplingRequestCompulsuryResults', 'samplingRequestResults'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ArrivalLocation $arrival_location)
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
            ->where('approved_status', 'approved')
            ->get()->last();

        $samplingRequestCompulsuryResults  = ArrivalSamplingResultForCompulsury::where('arrival_sampling_request_id', $samplingRequest->id)->get();
        $samplingRequestResults  = ArrivalSamplingResult::where('arrival_sampling_request_id', $samplingRequest->id)->get();

        $isNotGeneratable = false;

        if ($arrivalTicket->decision_making == 1) {
            $isNotGeneratable = ($arrivalTicket->lumpsum_deduction == 0.00 && $arrivalTicket->lumpsum_deduction_kgs == 0.00);
        }

        $html = view('management.arrival.arrival_slip.getTicketDataForArrival', compact('arrivalTicket', 'isNotGeneratable', 'samplingRequest', 'samplingRequestCompulsuryResults', 'samplingRequestResults'))->render();

        return response()->json(['success' => true, 'html' => $html, 'isNotGeneratable' => $isNotGeneratable]);
    }
}
