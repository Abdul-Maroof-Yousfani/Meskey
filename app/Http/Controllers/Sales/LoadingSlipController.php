<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Sales\LoadingSlip;
use App\Models\Sales\LoadingProgramItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class LoadingSlipController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('management.sales.loading-slip.index');
    }

    /**
     * Get list of loading slips for AJAX.
     */
    public function getList(Request $request)
    {
        $LoadingSlips = LoadingSlip::with([
            'loadingProgramItem.loadingProgram.deliveryOrder.customer',
            'loadingProgramItem.loadingProgram.deliveryOrder.delivery_order_data.item',
            'createdBy'
        ])
            ->when($request->filled('search'), function ($q) use ($request) {
                $searchTerm = '%' . $request->search . '%';
                return $q->where(function ($sq) use ($searchTerm) {
                    $sq->whereHas('loadingProgramItem', function ($query) use ($searchTerm) {
                        $query->where('transaction_number', 'like', $searchTerm)
                              ->orWhere('truck_number', 'like', $searchTerm);
                    })
                    ->orWhere('customer', 'like', $searchTerm)
                    ->orWhere('commodity', 'like', $searchTerm);
                });
            })
            ->latest()
            ->paginate($request->get('per_page', 25));

        return view('management.sales.loading-slip.getList', compact('LoadingSlips'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Get available tickets that have accepted Sales QC and no loading slip
        $availableTickets = LoadingProgramItem::whereHas('salesQc', function ($query) {
            $query->where('status', 'accept');
        })
        ->whereDoesntHave('loadingSlip')
        ->with([
            'loadingProgram.deliveryOrder.customer',
            'loadingProgram.deliveryOrder.delivery_order_data.item',
            'salesQc'
        ])
        ->get();

        return view('management.sales.loading-slip.create', compact('availableTickets'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'loading_program_item_id' => 'required|exists:loading_program_items,id',
            'customer' => 'required|string|max:255',
            'commodity' => 'required|string|max:255',
            'so_qty' => 'required|numeric|min:0',
            'do_qty' => 'required|numeric|min:0',
            'factory' => 'required|string|max:255',
            'gala' => 'nullable|string|max:255',
            'no_of_bags' => 'required|integer|min:1',
            'bag_size' => 'required|numeric|min:0',
            'kilogram' => 'required|numeric|min:0',
            'remarks' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Check if loading slip already exists for this ticket
        $existingSlip = LoadingSlip::where('loading_program_item_id', $request->loading_program_item_id)->first();
        if ($existingSlip) {
            return response()->json(['errors' => ['loading_program_item_id' => ['Loading slip already exists for this ticket.']]], 422);
        }

        try {
            DB::beginTransaction();

            $loadingSlip = LoadingSlip::create([
                'loading_program_item_id' => $request->loading_program_item_id,
                'customer' => $request->customer,
                'commodity' => $request->commodity,
                'so_qty' => $request->so_qty,
                'do_qty' => $request->do_qty,
                'factory' => $request->factory,
                'gala' => $request->gala,
                'no_of_bags' => $request->no_of_bags,
                'bag_size' => $request->bag_size,
                'kilogram' => $request->kilogram,
                'remarks' => $request->remarks,
                'created_by' => auth()->user()->id
            ]);

            DB::commit();

            return response()->json(['success' => 'Loading Slip created successfully.', 'data' => $loadingSlip], 201);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['error' => 'Failed to create Loading Slip.', 'details' => $e->getMessage()], 422);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $loadingSlip = LoadingSlip::with([
            'loadingProgramItem.loadingProgram.deliveryOrder.customer',
            'loadingProgramItem.loadingProgram.deliveryOrder.delivery_order_data.item',
            'createdBy'
        ])->findOrFail($id);

        return view('management.sales.loading-slip.show', compact('loadingSlip'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $loadingSlip = LoadingSlip::with([
            'loadingProgramItem.loadingProgram.deliveryOrder.customer',
            'loadingProgramItem.loadingProgram.deliveryOrder.delivery_order_data.item',
            'createdBy'
        ])->findOrFail($id);

        return view('management.sales.loading-slip.edit', compact('loadingSlip'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'customer' => 'required|string|max:255',
            'commodity' => 'required|string|max:255',
            'so_qty' => 'required|numeric|min:0',
            'do_qty' => 'required|numeric|min:0',
            'factory' => 'required|string|max:255',
            'gala' => 'nullable|string|max:255',
            'no_of_bags' => 'required|integer|min:1',
            'bag_size' => 'required|numeric|min:0',
            'kilogram' => 'required|numeric|min:0',
            'remarks' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $loadingSlip = LoadingSlip::findOrFail($id);

        try {
            DB::beginTransaction();

            $loadingSlip->update([
                'customer' => $request->customer,
                'commodity' => $request->commodity,
                'so_qty' => $request->so_qty,
                'do_qty' => $request->do_qty,
                'factory' => $request->factory,
                'gala' => $request->gala,
                'no_of_bags' => $request->no_of_bags,
                'bag_size' => $request->bag_size,
                'kilogram' => $request->kilogram,
                'remarks' => $request->remarks
            ]);

            DB::commit();

            return response()->json(['success' => 'Loading Slip updated successfully.', 'data' => $loadingSlip], 200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['error' => 'Failed to update Loading Slip.', 'details' => $e->getMessage()], 422);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $loadingSlip = LoadingSlip::findOrFail($id);
            $loadingSlip->delete();

            return response()->json(['success' => 'Loading Slip deleted successfully.'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to delete Loading Slip.', 'details' => $e->getMessage()], 422);
        }
    }

    /**
     * Get ticket related data for AJAX.
     */
    public function getTicketRelatedData(Request $request)
    {
        $LoadingProgramItem = LoadingProgramItem::with([
            'loadingProgram.deliveryOrder.customer',
            'loadingProgram.deliveryOrder.salesOrder',
            'loadingProgram.deliveryOrder.delivery_order_data.item',
            'loadingProgram.deliveryOrder.delivery_order_data.salesOrderData',
            'loadingProgram.deliveryOrder.arrivalLocation',
            'loadingProgram.deliveryOrder.subArrivalLocation'
        ])->findOrFail($request->loading_program_item_id);

        $DeliveryOrder = $LoadingProgramItem->loadingProgram->deliveryOrder;

        // Prepare data for the form
        $data = [
            'customer' => $DeliveryOrder->customer->name ?? '',
            'commodity' => $DeliveryOrder->delivery_order_data->first()->item->name ?? '',
            'so_qty' => $DeliveryOrder->delivery_order_data->first()->salesOrderData->qty ?? 0,
            'do_qty' => $DeliveryOrder->delivery_order_data->first()->qty ?? 0,
            'factory' => $DeliveryOrder->arrivalLocation->name ?? '',
            'gala' => $DeliveryOrder->subArrivalLocation->name ?? '',
            'bag_size' => $DeliveryOrder->delivery_order_data->first()->bag_size ?? 0
        ];

        return response()->json(['success' => true, 'data' => $data]);
    }
}
