<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Sales\DeliveryOrder;
use App\Models\Sales\DispatchQc;
use App\Models\Sales\DispatchQcAttachment;
use App\Models\Sales\LoadingProgramItem;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Validator;

class DispatchQCController extends Controller
{
     function __construct()
    {
        // $this->middleware('check.company:sales-sales-qc', ['only' => ['index']]);
        // $this->middleware('check.company:sales-sales-qc', ['only' => ['edit']]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('management.sales.dispatch-qc.index');
    }

    /**
     * Get list of sales qc.
     */
    public function getList(Request $request)
    {
        $DispatchQcs = DispatchQc::with([
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
                    ->orWhere('status', 'like', $searchTerm);
                });
            })
            ->latest()
            ->paginate($request->get('per_page', 25));

        return view('management.sales.dispatch-qc.getList', compact('DispatchQcs'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Get tickets that have loading slip and either:
        // 1. No dispatch QC at all, OR
        // 2. Latest QC is rejected AND loading slip was edited after that specific rejection
        $Tickets = LoadingProgramItem::whereHas('loadingSlip')
            ->whereDoesntHave('dispatchQcs', function($q) {
                // Exclude tickets that have an accepted QC
                $q->where('status', 'accept');
            })
            ->with([
                'loadingProgram.deliveryOrder.customer',
                'loadingProgram.deliveryOrder.delivery_order_data.item',
                'loadingProgram.deliveryOrder',
                'dispatchQcs',
                'loadingSlip.logs'
            ])
            ->get()
            ->filter(function($ticket) {
                // If no dispatch QC exists, ticket is eligible
                if ($ticket->dispatchQcs->isEmpty()) {
                    return true;
                }
                
                // Get the latest rejected QC
                $latestRejectedQc = $ticket->dispatchQcs
                    ->where('status', 'reject')
                    ->sortByDesc('created_at')
                    ->first();
                
                if (!$latestRejectedQc) {
                    return false;
                }
                
                // Check if loading slip was edited after this specific rejection
                // (there must be a log entry with this dispatch_qc_id)
                $hasEditedAfterRejection = $ticket->loadingSlip->logs
                    ->where('dispatch_qc_id', $latestRejectedQc->id)
                    ->isNotEmpty();
                
                return $hasEditedAfterRejection;
            })
            ->values();

        return view('management.sales.dispatch-qc.create', compact('Tickets'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'loading_program_item_id' => 'required|exists:loading_program_items,id',
            'customer' => 'required|string',
            'commodity' => 'required|string',
            'so_qty' => 'required|numeric',
            'do_qty' => 'required|numeric',
            'factory' => 'required|string',
            'gala' => 'required|string',
            'qc_remarks' => 'nullable|string',
            'status' => 'required|in:accept,reject',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|mimes:jpeg,jpg,png,pdf,doc,docx|max:10240',
            'company_id' => "required|numeric"
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Check if the ticket already has an accepted dispatch QC
        $loadingProgramItem = LoadingProgramItem::with(['dispatchQcs', 'loadingSlip.logs'])->findOrFail($request->loading_program_item_id);
        
        if ($loadingProgramItem->hasAcceptedDispatchQc()) {
            return response()->json(['errors' => ['loading_program_item_id' => 'This ticket already has an accepted Dispatch QC.']], 422);
        }
        
        // Check if there's a rejected QC but loading slip hasn't been edited yet
        $latestRejectedQc = $loadingProgramItem->dispatchQcs
            ->where('status', 'reject')
            ->sortByDesc('created_at')
            ->first();
            
        if ($latestRejectedQc) {
            // Check if loading slip was edited after this specific rejection
            $loadingSlip = $loadingProgramItem->loadingSlip;
            $hasEditedAfterRejection = $loadingSlip && $loadingSlip->logs
                ->where('dispatch_qc_id', $latestRejectedQc->id)
                ->isNotEmpty();
            
            if (!$hasEditedAfterRejection) {
                return response()->json(['errors' => ['loading_program_item_id' => 'Please edit the loading slip first before creating a new Dispatch QC.']], 422);
            }
        }

        // Get ticket data to auto-populate fields
        $LoadingProgramItem = LoadingProgramItem::with([
            'loadingProgram.deliveryOrder.customer',
            'loadingProgram.deliveryOrder.salesOrder',
            'loadingProgram.deliveryOrder.delivery_order_data.item',
            'loadingProgram.deliveryOrder.delivery_order_data.salesOrderData',
            'loadingProgram.deliveryOrder.arrivalLocation',
            'loadingProgram.deliveryOrder.subArrivalLocation',
            'loadingProgram.saleOrder.customer',
            'loadingProgram.saleOrder.sales_order_data.item',
            'arrivalLocation',
            'subArrivalLocation'
        ])->findOrFail($request->loading_program_item_id);

        DB::beginTransaction();
        try {
            $DeliveryOrder = DeliveryOrder::find($loadingProgramItem->delivery_order_id);
            $SaleOrder = $LoadingProgramItem->loadingProgram->saleOrder;
    
            // Auto-populate fields from ticket data (no matter what)
            if ($DeliveryOrder) {
                $dispatchQcData = [
                    'loading_program_item_id' => $request->loading_program_item_id,
                    'customer' => $request->customer ?: ($DeliveryOrder->customer->name ?? ''),
                    'commodity' => $request->commodity ?: ($DeliveryOrder->delivery_order_data->first()->item->name ?? ''),
                    'so_qty' => $request->so_qty ?: ($DeliveryOrder->delivery_order_data->first()->salesOrderData->qty ?? 0),
                    'do_qty' => $request->do_qty ?: ($DeliveryOrder->delivery_order_data->first()->qty ?? 0),
                    'factory' => $request->factory ?: ($LoadingProgramItem->arrivalLocation->name ?? ''),
                    'gala' => $request->gala ?: ($LoadingProgramItem->subArrivalLocation->name ?? ''),
                    'qc_remarks' => $request->qc_remarks,
                    'status' => $request->status,
                    'delivery_order_id' => $DeliveryOrder->id,
                    'created_by' => auth()->user()->id,
                    'company_id' => $request->company_id
                ];
            } else {
                $dispatchQcData = [
                    'loading_program_item_id' => $request->loading_program_item_id,
                    'customer' => $request->customer ?: ($SaleOrder->customer->name ?? ''),
                    'commodity' => $request->commodity ?: ($SaleOrder->sales_order_data->first()->item->name ?? ''),
                    'so_qty' => $request->so_qty ?: ($SaleOrder->sales_order_data->first()->qty ?? 0),
                    'do_qty' => $request->do_qty ?: 0,
                    'factory' => $request->factory ?: ($LoadingProgramItem->arrivalLocation->name ?? ''),
                    'gala' => $request->gala ?: ($LoadingProgramItem->subArrivalLocation->name ?? ''),
                    'qc_remarks' => $request->qc_remarks,
                    'status' => $request->status,
                    'delivery_order_id' => null,
                    'created_by' => auth()->user()->id
                ];
            }
    
            $dispatchQc = DispatchQc::create($dispatchQcData);
    
            // Handle file attachments
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $originalName = $file->getClientOriginalName();
                    $fileName = time() . '_' . uniqid() . '_' . $originalName;
                    $path = $file->storeAs('sales_qc_attachments', $fileName, 'public');
    
                    DispatchQcAttachment::create([
                        'dispatch_qc_id' => $dispatchQc->id,
                        'file_path' => 'storage/' . $path,
                        'file_name' => $originalName,
                        'file_type' => $file->getClientMimeType(),
                        'file_size' => $file->getSize(),
                        'uploaded_by' => auth()->user()->id
                    ]);
                }
            }
            DB::commit();
        } catch(Exception $e) {
            DB::rollBack();
            return response()->json($e->getMessage(), 500);
        }

        return response()->json(['success' => 'Dispatch QC created successfully.', 'data' => $dispatchQc], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $DispatchQc = DispatchQc::with([
            'loadingProgramItem.loadingProgram.deliveryOrder.customer',
            'loadingProgramItem.loadingProgram.deliveryOrder.delivery_order_data.item',
            'attachments'
        ])->findOrFail($id);


        return view('management.sales.dispatch-qc.show', compact('DispatchQc'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $DispatchQc = DispatchQc::with([
            'loadingProgramItem.loadingProgram.deliveryOrder.customer',
            'loadingProgramItem.loadingProgram.deliveryOrder.delivery_order_data.item',
            'loadingProgramItem.loadingProgram.deliveryOrder.arrivalLocation',
            'loadingProgramItem.loadingProgram.deliveryOrder.subArrivalLocation',
            'loadingProgramItem.loadingProgram.saleOrder.customer',
            'loadingProgramItem.loadingProgram.saleOrder.sales_order_data.item',
            'loadingProgramItem.arrivalLocation',
            'loadingProgramItem.subArrivalLocation',
            'attachments'
        ])->findOrFail($id);

        $Tickets = LoadingProgramItem::whereHas('loadingSlip')
            ->with([
                'loadingProgram.deliveryOrder.customer',
                'loadingProgram.deliveryOrder.delivery_order_data.item',
                'loadingProgram.deliveryOrder',
                'loadingProgram.saleOrder.customer'
            ])
            ->get();

        return view('management.sales.dispatch-qc.edit', compact('DispatchQc', 'Tickets'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'loading_program_item_id' => 'required|exists:loading_program_items,id',
            'customer' => 'nullable|string',
            'commodity' => 'nullable|string',
            'so_qty' => 'nullable|numeric',
            'do_qty' => 'nullable|numeric',
            'factory' => 'nullable|string',
            'gala' => 'nullable|string',
            'qc_remarks' => 'nullable|string',
            'status' => 'required|in:accept,reject',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|mimes:jpeg,jpg,png,pdf,doc,docx|max:10240'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Check if the ticket already has an accepted dispatch QC (excluding current one)
        $existingAcceptedQc = DispatchQc::where('loading_program_item_id', $request->loading_program_item_id)
            ->where('id', '!=', $id)
            ->where('status', 'accept')
            ->first();
        if ($existingAcceptedQc) {
            return response()->json(['errors' => ['loading_program_item_id' => 'This ticket already has an accepted Dispatch QC.']], 422);
        }

        $dispatchQc = DispatchQc::findOrFail($id);

        // Get ticket data to auto-populate fields
        $LoadingProgramItem = LoadingProgramItem::with([
            'loadingProgram.deliveryOrder.customer',
            'loadingProgram.deliveryOrder.salesOrder',
            'loadingProgram.deliveryOrder.delivery_order_data.item',
            'loadingProgram.deliveryOrder.delivery_order_data.salesOrderData',
            'loadingProgram.deliveryOrder.arrivalLocation',
            'loadingProgram.deliveryOrder.subArrivalLocation',
            'loadingProgram.saleOrder.customer',
            'loadingProgram.saleOrder.sales_order_data.item',
            'arrivalLocation',
            'subArrivalLocation'
        ])->findOrFail($request->loading_program_item_id);

        $DeliveryOrder = DeliveryOrder::find($LoadingProgramItem->delivery_order_id);
        $SaleOrder = $LoadingProgramItem->loadingProgram->saleOrder;

        // Auto-populate fields from ticket data (no matter what)
        if ($DeliveryOrder) {
            $dispatchQcData = [
                'loading_program_item_id' => $request->loading_program_item_id,
                'customer' => $request->customer ?: ($DeliveryOrder->customer->name ?? ''),
                'commodity' => $request->commodity ?: ($DeliveryOrder->delivery_order_data->first()->item->name ?? ''),
                'so_qty' => $request->so_qty ?: ($DeliveryOrder->delivery_order_data->first()->salesOrderData->qty ?? 0),
                'do_qty' => $request->do_qty ?: ($DeliveryOrder->delivery_order_data->first()->qty ?? 0),
                'factory' => $request->factory ?: ($LoadingProgramItem->arrivalLocation->name ?? ''),
                'gala' => $request->gala ?: ($LoadingProgramItem->subArrivalLocation->name ?? ''),
                'qc_remarks' => $request->qc_remarks,
                'delivery_order_id' => $DeliveryOrder->id,
                'status' => $request->status
            ];
        } else {
            $dispatchQcData = [
                'loading_program_item_id' => $request->loading_program_item_id,
                'customer' => $request->customer ?: ($SaleOrder->customer->name ?? ''),
                'commodity' => $request->commodity ?: ($SaleOrder->sales_order_data->first()->item->name ?? ''),
                'so_qty' => $request->so_qty ?: ($SaleOrder->sales_order_data->first()->qty ?? 0),
                'do_qty' => $request->do_qty ?: 0,
                'factory' => $request->factory ?: ($LoadingProgramItem->arrivalLocation->name ?? ''),
                'gala' => $request->gala ?: ($LoadingProgramItem->subArrivalLocation->name ?? ''),
                'qc_remarks' => $request->qc_remarks,
                'delivery_order_id' => null,
                'status' => $request->status
            ];
        }

        $dispatchQc->update($dispatchQcData);

        // Handle file attachments - delete existing and add new ones
        if ($request->hasFile('attachments')) {
            // Delete existing attachments
            foreach ($dispatchQc->attachments as $attachment) {
                // Delete file from storage
                if (Storage::exists(str_replace('storage/', 'public/', $attachment->file_path))) {
                    Storage::delete(str_replace('storage/', 'public/', $attachment->file_path));
                }
                // Delete database record
                $attachment->delete();
            }

            // Add new attachments
            foreach ($request->file('attachments') as $file) {
                $originalName = $file->getClientOriginalName();
                $fileName = time() . '_' . uniqid() . '_' . $originalName;
                $path = $file->storeAs('sales_qc_attachments', $fileName, 'public');

                DispatchQcAttachment::create([
                    'dispatch_qc_id' => $dispatchQc->id,
                    'file_path' => 'storage/' . $path,
                    'file_name' => $originalName,
                    'file_type' => $file->getClientMimeType(),
                    'file_size' => $file->getSize(),
                    'uploaded_by' => auth()->user()->id
                ]);
            }
        }

        return response()->json(['success' => 'Sales QC updated successfully.', 'data' => $dispatchQc], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $dispatchQc = DispatchQc::findOrFail($id);

            // Delete attachments
            foreach ($dispatchQc->attachments as $attachment) {
                try {
                    $filePath = str_replace('storage/', 'public/', $attachment->file_path);
                    if (Storage::exists($filePath)) {
                        Storage::delete($filePath);
                    }
                } catch (\Exception $e) {
                    // Log the error but continue with deletion
                    \Log::error('Failed to delete attachment file: ' . $attachment->file_path . ' - ' . $e->getMessage());
                }
                $attachment->delete();
            }

            $dispatchQc->delete();
            return response()->json(['success' => 'Sales QC deleted successfully.'], 200);
        } catch (\Exception $e) {
            \Log::error('Sales QC deletion failed: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to delete Sales QC.', 'details' => $e->getMessage()], 422);
        }
    }

    /**
     * Get ticket related data for Sales QC.
     */
    public function getTicketRelatedData(Request $request)
    {
        $LoadingProgramItem = LoadingProgramItem::with([
            'loadingProgram.deliveryOrder.customer',
            'loadingProgram.deliveryOrder.salesOrder',
            'loadingProgram.deliveryOrder.delivery_order_data.item',
            'loadingProgram.deliveryOrder.delivery_order_data.salesOrderData',
            'loadingProgram.deliveryOrder.arrivalLocation',
            'loadingProgram.deliveryOrder.subArrivalLocation',
            'loadingProgram.saleOrder.customer',
            'loadingProgram.saleOrder.sales_order_data.item',
            'arrivalLocation',
            'subArrivalLocation'
        ])->findOrFail($request->loading_program_item_id);

        $DeliveryOrder = $LoadingProgramItem->loadingProgram->deliveryOrder;
        $SaleOrder = $LoadingProgramItem->loadingProgram->saleOrder;

        // Get factory and gala from loading program item
        $factoryName = $LoadingProgramItem->arrivalLocation->name ?? '';
        $galaName = $LoadingProgramItem->subArrivalLocation->name ?? '';

        // Prepare data for the form
        if ($DeliveryOrder) {
            $data = [
                'customer' => $DeliveryOrder->customer->name ?? '',
                'commodity' => $DeliveryOrder->delivery_order_data->first()->item->name ?? '',
                'so_qty' => $DeliveryOrder->delivery_order_data->first()->salesOrderData->qty ?? 0,
                'do_qty' => $DeliveryOrder->delivery_order_data->first()->qty ?? 0,
                'factory' => $LoadingProgramItem->arrival_location_id ?? '',
                'gala' => $LoadingProgramItem->sub_arrival_location_id ?? '',
                'factory_names' => $factoryName ? [$factoryName] : [],
                'gala_names' => $galaName ? [$galaName] : []
            ];
        } else {
            $data = [
                'customer' => $SaleOrder->customer->name ?? '',
                'commodity' => $SaleOrder->sales_order_data->first()->item->name ?? '',
                'so_qty' => $SaleOrder->sales_order_data->first()->qty ?? 0,
                'do_qty' => 0,
                'factory' => $LoadingProgramItem->arrival_location_id ?? '',
                'gala' => $LoadingProgramItem->sub_arrival_location_id ?? '',
                'factory_names' => $factoryName ? [$factoryName] : [],
                'gala_names' => $galaName ? [$galaName] : []
            ];
        }

        return response()->json(['success' => true, 'data' => $data]);
    }

    /**
     * Get Gate Out Pass view for a Dispatch QC.
     */
    public function get_gate_out(int $id) {
        $DispatchQc = DispatchQc::with([
            'loadingProgramItem.loadingProgram.deliveryOrder.customer',
            'loadingProgramItem.loadingProgram.deliveryOrder.delivery_order_data.item',
            'loadingProgramItem.loadingProgram.deliveryOrder.delivery_order_data.salesOrderData',
            'loadingProgramItem.loadingProgram.saleOrder.sales_order_data',
            'loadingProgramItem.arrivalLocation',
            'loadingProgramItem.subArrivalLocation',
            'loadingProgramItem.loadingSlip.secondWeighbridge',
            'createdBy'
        ])->findOrFail($id);

        // Only allow gate out for accepted dispatch QC
        if ($DispatchQc->status !== 'accept') {
            return response()->json(['error' => 'Gate Out Pass is only available for accepted Dispatch QC.'], 403);
        }

        return view('management.sales.dispatch-qc.gate-out', compact('DispatchQc'));
    }
}
