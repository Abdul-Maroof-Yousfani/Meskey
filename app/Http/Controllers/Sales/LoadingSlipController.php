<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Sales\LoadingSlip;
use App\Models\Sales\LoadingSlipLog;
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
            'loadingProgramItem.dispatchQc',
            'logs',
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
            $query->where('status', 'accept')
                    ->orWhere("am_approval_status", "approved");
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
            'remarks' => 'nullable|string',
            'labour' => "required|in:paid,not_paid"
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

                    // Get ticket data to auto-populate fields
            $LoadingProgramItem = LoadingProgramItem::with([
                'loadingProgram.deliveryOrder.customer',
                'loadingProgram.deliveryOrder.salesOrder',
                'loadingProgram.deliveryOrder.delivery_order_data.item',
                'loadingProgram.deliveryOrder.delivery_order_data.salesOrderData',
                'loadingProgram.deliveryOrder.arrivalLocation',
                'loadingProgram.deliveryOrder.subArrivalLocation'
            ])->findOrFail($request->loading_program_item_id);

            $DeliveryOrder = $LoadingProgramItem->loadingProgram->deliveryOrder;
            $no_of_bags = $request->no_of_bags;
            
            // Only check balance if delivery order exists
            if ($DeliveryOrder) {
            $total_no_of_bags = $DeliveryOrder->delivery_order_data->sum('no_of_bags');
            $used_no_of_bags = $DeliveryOrder->loadingSlips->sum("no_of_bags");
            $remaining_no_of_bags = $total_no_of_bags - $used_no_of_bags;

            // if(!$remaining_no_of_bags) {
            //     return response()->json('You do not have any balance.', 422);
            // }

            // if($no_of_bags > $remaining_no_of_bags) {
            //     return response()->json('Your balance is '.$remaining_no_of_bags.'.', 422);
            // }
            }

            
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
                'delivery_order_id' => $DeliveryOrder?->id,
                'remarks' => $request->remarks,
                'created_by' => auth()->user()->id,
                'labour' => $request->labour
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
            'loadingProgramItem.loadingProgram.deliveryOrder.arrivalLocation',
            'loadingProgramItem.loadingProgram.deliveryOrder.subArrivalLocation',
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
            'loadingProgramItem.loadingProgram.deliveryOrder.arrivalLocation',
            'loadingProgramItem.loadingProgram.deliveryOrder.subArrivalLocation',
            'loadingProgramItem.dispatchQc',
            'createdBy',
            'logs'
        ])->findOrFail($id);

        // Check if there's a rejected dispatch QC
        $rejectedDispatchQc = null;
        $canEdit = $loadingSlip->canBeEdited();
        
        if ($loadingSlip->hasRejectedDispatchQc()) {
            $rejectedDispatchQc = $loadingSlip->getLatestRejectedDispatchQc();
        }

        return view('management.sales.loading-slip.edit', compact('loadingSlip', 'rejectedDispatchQc', 'canEdit'));
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
            'remarks' => 'nullable|string',
            'labour' => "required|in:paid,not_paid"
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }


        $loadingSlip = LoadingSlip::with('loadingProgramItem.dispatchQc')->findOrFail($id);
        
        // Check if editing is allowed
        if (!$loadingSlip->canBeEdited()) {
            return response()->json(['error' => 'This loading slip cannot be edited because its Dispatch QC has been accepted.'], 422);
        }
        
        $DeliveryOrder = $loadingSlip->deliveryOrder;
        
        // Only check bag balance if delivery order exists
        if ($DeliveryOrder) {
            $total_no_of_bags = $DeliveryOrder->delivery_order_data->sum('no_of_bags');
            $used_no_of_bags = $DeliveryOrder->loadingSlips->sum("no_of_bags");
           
            // if($no_of_bags > ($remaining_no_of_bags + $loadingSlip->no_of_bags)) {
            //     return response()->json('Your balance is '.($remaining_no_of_bags + $loadingSlip->no_of_bags).'.', 422);
            // }
        }

        try {
            DB::beginTransaction();

            // Check if there's a rejected dispatch QC - if so, log the old data
            $rejectedDispatchQc = $loadingSlip->loadingProgramItem?->latestRejectedDispatchQc;
            if ($rejectedDispatchQc) {
                // Store old data in logs (keep rejected QC record for history)
                LoadingSlipLog::create([
                    'loading_slip_id' => $loadingSlip->id,
                    'dispatch_qc_id' => $rejectedDispatchQc->id,
                    'customer' => $loadingSlip->customer,
                    'commodity' => $loadingSlip->commodity,
                    'so_qty' => $loadingSlip->so_qty,
                    'do_qty' => $loadingSlip->do_qty,
                    'factory' => $loadingSlip->factory,
                    'gala' => $loadingSlip->gala,
                    'no_of_bags' => $loadingSlip->no_of_bags,
                    'bag_size' => $loadingSlip->bag_size,
                    'kilogram' => $loadingSlip->kilogram,
                    'remarks' => $loadingSlip->remarks,
                    'labour' => $loadingSlip->labour,
                    'qc_remarks' => $rejectedDispatchQc->qc_remarks,
                    'edited_by' => auth()->user()->id,
                    "delivery_order_id" => $DeliveryOrder?->id
                ]);
                
            }

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
                'remarks' => $request->remarks,
                'labour' => $request->labour
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
        $SaleOrder = $LoadingProgramItem->loadingProgram->saleOrder;
        // Prepare data for the form
        if ($DeliveryOrder) {
            $data = [
                'customer' => $DeliveryOrder->customer->name ?? '',
                'commodity' => $DeliveryOrder->delivery_order_data->first()->item->name ?? '',
                'so_qty' => $DeliveryOrder->delivery_order_data->first()->salesOrderData->qty ?? 0,
                'do_qty' => $DeliveryOrder->delivery_order_data->first()->qty ?? 0,
                'factory' => $LoadingProgramItem->arrival_location_id ?? '',
                'gala' => $LoadingProgramItem->sub_arrival_location_id ?? '',
                'factory_names' => $LoadingProgramItem->arrival_location_id ?
                    \App\Models\Master\ArrivalLocation::whereIn('id', explode(',', $LoadingProgramItem->arrival_location_id))->pluck('name')->toArray() : [],
                'gala_names' => $LoadingProgramItem->sub_arrival_location_id ?
                    \App\Models\Master\ArrivalSubLocation::whereIn('id', explode(',', $LoadingProgramItem->sub_arrival_location_id))->pluck('name')->toArray() : [],
                'bag_size' => $DeliveryOrder->delivery_order_data->first()->bag_size ?? 0
            ];
        } else {
            $data = [
                'customer' => $SaleOrder->customer->name ?? '',
                'commodity' => $SaleOrder->sales_order_data->first()->item->name ?? '',
                'so_qty' => $SaleOrder->sales_order_data->first()->qty ?? 0,
                'do_qty' => 0,
                'factory' => $LoadingProgramItem->arrival_location_id ?? '',
                'gala' => $LoadingProgramItem->sub_arrival_location_id ?? '',
                'factory_names' => $LoadingProgramItem->arrival_location_id ?
                    \App\Models\Master\ArrivalLocation::whereIn('id', explode(',', $LoadingProgramItem->arrival_location_id))->pluck('name')->toArray() : [],
                'gala_names' => $LoadingProgramItem->sub_arrival_location_id ?
                    \App\Models\Master\ArrivalSubLocation::whereIn('id', explode(',', $LoadingProgramItem->sub_arrival_location_id))->pluck('name')->toArray() : [],
                'bag_size' => $SaleOrder->sales_order_data->first()->bag_size ?? 0
            ];
        }


        return response()->json(['success' => true, 'data' => $data]);
    }
}
