<?php

namespace App\Http\Controllers\Arrival;

use App\Http\Controllers\Controller;
use App\Http\Requests\Arrival\ArrivalTicketRequest;
use App\Models\Arrival\ArrivalTicket;
use App\Models\ArrivalPurchaseOrder;
use App\Models\Master\Miller;
use App\Models\Master\Supplier;
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
            $q->where(function ($sq) use ($searchTerm) {
                $sq->where('unique_no', 'like', $searchTerm)
                    ->orWhere('supplier_name', 'like', $searchTerm)
                    ->orWhere('truck_no', 'like', $searchTerm)
                    ->orWhere('bilty_no', 'like', $searchTerm);
            });
        })
            ->when($request->filled('from_date'), function ($q) use ($request) {
                $q->whereDate('created_at', '>=', $request->from_date);
            })
            ->when($request->filled('to_date'), function ($q) use ($request) {
                $q->whereDate('created_at', '<=', $request->to_date);
            })
            ->latest()
            ->paginate($request->get('per_page', 25));

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
        $requestData = $request->all();

        $previouscheck = ArrivalTicket::where('truck_no', $requestData['truck_no'])
            ->where('bilty_no', $requestData['bilty_no']);

        if ($previouscheck->exists()) {
            $viewLink = ' <a href="' . route('ticket.show', $previouscheck->first()->id) . '" target="_blank" class="text-blue-600 hover:underline">View Details</a>';
            throw ValidationException::withMessages([
                'truck_no' => ['Truck with this Bilty No already exists.' . $viewLink],
            ]);
        }

        if (!empty($requestData['accounts_of'])) {
            $supplier = Supplier::where('name', $requestData['accounts_of'])->first();
            $requestData['accounts_of_id'] = $supplier ? $supplier->id : null;
            $requestData['accounts_of_name'] = $requestData['accounts_of'];
        }

        if (!empty($requestData['broker_name'])) {
            $broker = Supplier::where('name', $requestData['broker_name'])->first();
            $requestData['broker_id'] = $broker ? $broker->id : null;
        }

        if (!empty($requestData['miller_name'])) {
            $miller = Miller::where('name', $requestData['miller_name'])->first();
            if (!$miller) {
                $miller = Miller::create(['name' => $requestData['miller_name']]);
            }
            $requestData['miller_id'] = $miller->id;
        }

        $requestData['first_qc_status'] = 'pending';
        $requestData['truck_type_id'] = $requestData['arrival_truck_type_id'] ?? null;

        $arrivalTicket = ArrivalTicket::create($requestData);

        return response()->json([
            'success' => 'Arrival Ticket created successfully.',
            'data' => $arrivalTicket
        ], 201);
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
    public function show(Request $request, $id)
    {
        $authUserCompany = $request->company_id;

        $accountsOf = User::role('Purchaser')
            ->whereHas('companies', function ($q) use ($authUserCompany) {
                $q->where('companies.id', $authUserCompany);
            })
            ->get();

        $arrivalTicket = ArrivalTicket::findOrFail($id);
        return view('management.arrival.ticket.show', compact('arrivalTicket', 'accountsOf'));
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

    public function confirmBiltyReturn(ArrivalTicket $ticket)
    {
        try {
            $updateData = [
                'bilty_return_confirmation' => 1,
                'bilty_return_reason' => request('bilty_return_reason')
            ];

            $ticket->update($updateData);

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
