<?php

namespace App\Http\Controllers\Arrival;

use App\Http\Controllers\Controller;
use App\Http\Requests\Arrival\ArrivalTicketRequest;
use App\Models\Arrival\ArrivalTicket;
use App\Models\ArrivalPurchaseOrder;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

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
    public function create(Request $request)
    {
        $authUserCompany = $request->company_id;
        $arrivalPurchaseOrders = ArrivalPurchaseOrder::all();

        $accountsOf = User::role('Purchaser')
            ->whereHas('companies', function ($q) use ($authUserCompany) {
                $q->where('companies.id', $authUserCompany);
            })
            ->get();

        return view('management.arrival.ticket.create', ['accountsOf' => $accountsOf, 'arrivalPurchaseOrders' => $arrivalPurchaseOrders]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ArrivalTicketRequest $request)
    {
        $request->validated();
        $request = $request->all();

        $exists = ArrivalTicket::where('truck_no', $request['truck_no'])
            ->where('bilty_no', $request['bilty_no'])
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'truck_no' => ['Truck with this Bilty No already exists.'],
            ]);
        }

        $request['first_qc_status'] = 'pending';
        $request['accounts_of_id'] = $request['accounts_of'] ?? NULL;
        $request['truck_type_id'] = $request['arrival_truck_type_id'] ?? NULL;

        $UnitOfMeasure = ArrivalTicket::create($request);

        return response()->json(['success' => 'Arrival Ticket created successfully.', 'data' => $UnitOfMeasure], 201);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, $id)
    {
        $authUserCompany = $request->company_id;

        $accountsOf = User::role('Purchaser')
            ->whereHas('companies', function ($q) use ($authUserCompany) {
                $q->where('companies.id', $authUserCompany);
            })
            ->get();

        $arrivalTicket = ArrivalTicket::findOrFail($id);
        return view('management.arrival.ticket.edit', compact('arrivalTicket', 'accountsOf'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ArrivalTicketRequest $request, $id)
    {

        $arrivalTicket = ArrivalTicket::findOrFail($id);


        $data = $request->validated();
        $request['accounts_of_id'] = $request['accounts_of'] ?? NULL;
        $request['truck_type_id'] = $request['arrival_truck_type_id'] ?? NULL;
        $arrivalTicket->update($request->all());

        return response()->json(['success' => 'Arrival Ticket updated successfully.', 'data' => $arrivalTicket], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ArrivalTicket $arrivalTicket): JsonResponse
    {
        $arrivalTicket->delete();
        return response()->json(['success' => 'Arrival Ticket deleted successfully.'], 200);
    }
}
