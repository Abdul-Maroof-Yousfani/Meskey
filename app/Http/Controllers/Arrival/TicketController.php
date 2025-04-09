<?php

namespace App\Http\Controllers\Arrival;

use App\Http\Controllers\Controller;
use App\Http\Requests\Arrival\ArrivalTicketRequest;
use App\Models\Arrival\ArrivalTicket;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('management.arrival.ticket.index');
    }

    /**
     * Get list of categories.
     */
    public function getList(Request $request)
    {
        $UnitOfMeasures = ArrivalTicket::when($request->filled('search'), function ($q) use ($request) {
            $searchTerm = '%' . $request->search . '%';
            return $q->where(function ($sq) use ($searchTerm) {
                $sq->where('unique_no', 'like', $searchTerm);
                $sq->orWhere('supplier_name', 'like', $searchTerm);
            });
        })
            ->latest()
            ->paginate(request('per_page', 25));

        return view('management.arrival.ticket.getList', compact('UnitOfMeasures'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('management.arrival.ticket.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ArrivalTicketRequest $request)
    {
        $request->validated();
        $request= $request->all();
        $request['first_qc_status'] = 'pending';
        $UnitOfMeasure = ArrivalTicket::create($request);

        return response()->json(['success' => 'Arrival Ticket created successfully.', 'data' => $UnitOfMeasure], 201);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $ArrivalTicket = ArrivalTicket::findOrFail($id);
        return view('management.arrival.ticket.edit', compact('ArrivalTicket'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ArrivalTicketRequest $request, $id)
    {

        $ArrivalTicket = ArrivalTicket::findOrFail($id);


        $data = $request->validated();
        $ArrivalTicket->update($request->all());

        return response()->json(['success' => 'Arrival Ticket updated successfully.', 'data' => $ArrivalTicket], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ArrivalTicket $ArrivalTicket): JsonResponse
    {
        $ArrivalTicket->delete();
        return response()->json(['success' => 'Arrival Ticket deleted successfully.'], 200);
    }
}
