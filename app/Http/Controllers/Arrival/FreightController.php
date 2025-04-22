<?php

namespace App\Http\Controllers\Arrival;

use App\Http\Controllers\Controller;
use App\Http\Requests\Arrival\FreightRequest;
use App\Models\Arrival\ArrivalSamplingRequest;
use App\Models\Arrival\ArrivalTicket;
use App\Models\Arrival\ArrivalSlip;
use App\Models\Arrival\Freight;
use App\Models\Master\ArrivalLocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FreightController extends Controller
{
    public function index()
    {
        return view('management.arrival.freight.index');
    }

    public function getList(Request $request)
    {
        $freights = Freight::with('arrivalTicket')->when($request->filled('search'), function ($q) use ($request) {
            $q->where('ticket_number', 'like', '%' . $request->search . '%')
                ->orWhere('truck_number', 'like', '%' . $request->search . '%')
                ->orWhere('billy_number', 'like', '%' . $request->search . '%');
        })
            ->where('company_id', $request->company_id)
            ->latest()
            ->paginate(request('per_page', 25));

        return view('management.arrival.freight.getList', compact('freights'));
    }

    public function create()
    {
        $tickets = ArrivalTicket::where('second_weighbridge_status', 'completed')
            ->whereNotNull('qc_product')
            ->get();

        return view('management.arrival.freight.create', ['tickets' => $tickets]);
    }

    public function store(FreightRequest $request)
    {
        $data = $request->validated();
        // $ticket = ArrivalTicket::findOrFail($request->arrival_ticket_id);

        $data['arrived_weight'] = $request->company_id;
        $data['loaded_weight'] = $request->company_id;
        $data['company_id'] = $request->company_id;

        $freight = Freight::create($request->all());

        return response()->json(['success' => 'Freight created successfully.', 'data' => $freight], 201);
    }

    public function edit($id)
    {
        $freight = Freight::findOrFail($id);
        return view('management.arrival.freight.edit', compact('freight'));
    }

    public function update(FreightRequest $request, Freight $freight)
    {
        $data = $request->validated();

        $data['difference'] = $data['loaded_weight'] - $data['arrived_weight'];
        $data['net_freight'] = $data['freight_per_ton'] * ($data['loaded_weight'] / 1000);

        $freight->update($data);

        return response()->json(['success' => 'Freight updated successfully.', 'data' => $freight], 200);
    }

    public function destroy(Freight $freight)
    {
        $freight->delete();
        return response()->json(['success' => 'Freight deleted successfully.'], 200);
    }

    public function getFreightForm(Request $request)
    {
        $ticket = ArrivalTicket::with('product')->find($request->arrival_ticket_id);

        if (!$ticket) {
            return response()->json(['success' => false, 'message' => 'Ticket not found'], 404);
        }

        $html = view('management.arrival.freight.partials.freight_form', [
            'ticket' => $ticket
        ])->render();

        return response()->json([
            'success' => true,
            'html' => $html
        ]);
    }
}
