<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Sales\SalesQc;
use App\Models\Sales\SalesQcAttachment;
use App\Models\Sales\LoadingProgramItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class SalesQcController extends Controller
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
        return view('management.sales.sales-qc.index');
    }

    /**
     * Get list of sales qc.
     */
    public function getList(Request $request)
    {
        $SalesQcs = SalesQc::with([
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

        return view('management.sales.sales-qc.getList', compact('SalesQcs'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Get tickets that have first weighbridge created
        $Tickets = LoadingProgramItem::whereHas('firstWeighbridge')
            ->whereDoesntHave('salesQc')
            ->with([
                'loadingProgram.deliveryOrder.customer',
                'loadingProgram.deliveryOrder.delivery_order_data.item',
                'loadingProgram.deliveryOrder'
            ])
            ->get();

        return view('management.sales.sales-qc.create', compact('Tickets'));
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
            'attachments.*' => 'file|mimes:jpeg,jpg,png,pdf,doc,docx|max:10240'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Check if the ticket already has a sales qc
        $existingSalesQc = SalesQc::where('loading_program_item_id', $request->loading_program_item_id)->first();
        if ($existingSalesQc) {
            return response()->json(['errors' => ['loading_program_item_id' => 'This ticket already has a Sales QC.']], 422);
        }

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

        // Auto-populate fields from ticket data (no matter what)
        $salesQcData = [
            'loading_program_item_id' => $request->loading_program_item_id,
            'customer' => $request->customer ?: ($DeliveryOrder->customer->name ?? ''),
            'commodity' => $request->commodity ?: ($DeliveryOrder->delivery_order_data->first()->item->name ?? ''),
            'so_qty' => $request->so_qty ?: ($DeliveryOrder->delivery_order_data->first()->salesOrderData->qty ?? 0),
            'do_qty' => $request->do_qty ?: ($DeliveryOrder->delivery_order_data->first()->qty ?? 0),
            'factory' => $request->factory ?: ($DeliveryOrder->arrivalLocation->name ?? ''),
            'gala' => $request->gala ?: ($DeliveryOrder->subArrivalLocation->name ?? ''),
            'qc_remarks' => $request->qc_remarks,
            'status' => $request->status,
            'created_by' => auth()->user()->id
        ];

        $salesQc = SalesQc::create($salesQcData);

        // Handle file attachments
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $originalName = $file->getClientOriginalName();
                $fileName = time() . '_' . uniqid() . '_' . $originalName;
                $path = $file->storeAs('sales_qc_attachments', $fileName, 'public');

                SalesQcAttachment::create([
                    'sales_qc_id' => $salesQc->id,
                    'file_path' => 'storage/' . $path,
                    'file_name' => $originalName,
                    'file_type' => $file->getClientMimeType(),
                    'file_size' => $file->getSize(),
                    'uploaded_by' => auth()->user()->id
                ]);
            }
        }

        return response()->json(['success' => 'Sales QC created successfully.', 'data' => $salesQc], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $SalesQc = SalesQc::with([
            'loadingProgramItem.loadingProgram.deliveryOrder.customer',
            'loadingProgramItem.loadingProgram.deliveryOrder.delivery_order_data.item',
            'attachments'
        ])->findOrFail($id);

        return view('management.sales.sales-qc.show', compact('SalesQc'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $SalesQc = SalesQc::with([
            'loadingProgramItem.loadingProgram.deliveryOrder.customer',
            'loadingProgramItem.loadingProgram.deliveryOrder.delivery_order_data.item',
            'attachments'
        ])->findOrFail($id);

        $Tickets = LoadingProgramItem::whereHas('firstWeighbridge')
            ->with([
                'loadingProgram.deliveryOrder.customer',
                'loadingProgram.deliveryOrder.delivery_order_data.item',
                'loadingProgram.deliveryOrder'
            ])
            ->get();

        return view('management.sales.sales-qc.edit', compact('SalesQc', 'Tickets'));
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

        // Check if the ticket already has a sales qc (excluding current one)
        $existingSalesQc = SalesQc::where('loading_program_item_id', $request->loading_program_item_id)
            ->where('id', '!=', $id)
            ->first();
        if ($existingSalesQc) {
            return response()->json(['errors' => ['loading_program_item_id' => 'This ticket already has a Sales QC.']], 422);
        }

        $salesQc = SalesQc::findOrFail($id);

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

        // Auto-populate fields from ticket data (no matter what)
        $salesQcData = [
            'loading_program_item_id' => $request->loading_program_item_id,
            'customer' => $request->customer ?: ($DeliveryOrder->customer->name ?? ''),
            'commodity' => $request->commodity ?: ($DeliveryOrder->delivery_order_data->first()->item->name ?? ''),
            'so_qty' => $request->so_qty ?: ($DeliveryOrder->delivery_order_data->first()->salesOrderData->qty ?? 0),
            'do_qty' => $request->do_qty ?: ($DeliveryOrder->delivery_order_data->first()->qty ?? 0),
            'factory' => $request->factory ?: ($DeliveryOrder->arrivalLocation->name ?? ''),
            'gala' => $request->gala ?: ($DeliveryOrder->subArrivalLocation->name ?? ''),
            'qc_remarks' => $request->qc_remarks,
            'status' => $request->status
        ];

        $salesQc->update($salesQcData);

        // Handle file attachments - delete existing and add new ones
        if ($request->hasFile('attachments')) {
            // Delete existing attachments
            foreach ($salesQc->attachments as $attachment) {
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

                SalesQcAttachment::create([
                    'sales_qc_id' => $salesQc->id,
                    'file_path' => 'storage/' . $path,
                    'file_name' => $originalName,
                    'file_type' => $file->getClientMimeType(),
                    'file_size' => $file->getSize(),
                    'uploaded_by' => auth()->user()->id
                ]);
            }
        }

        return response()->json(['success' => 'Sales QC updated successfully.', 'data' => $salesQc], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $salesQc = SalesQc::findOrFail($id);

            // Delete attachments
            foreach ($salesQc->attachments as $attachment) {
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

            $salesQc->delete();
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
            'gala' => $DeliveryOrder->subArrivalLocation->name ?? ''
        ];

        return response()->json(['success' => true, 'data' => $data]);
    }
}
