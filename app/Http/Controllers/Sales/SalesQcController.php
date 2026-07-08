<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Sales\DeliveryOrder;
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
            'attachments.*' => 'file|mimes:jpeg,jpg,png,pdf,doc,docx|max:10240',
            'company_id' => 'required|numeric'
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

        $DeliveryOrder = DeliveryOrder::find($LoadingProgramItem->delivery_order_id);
        $SaleOrder = $LoadingProgramItem->loadingProgram->saleOrder;

        // Auto-populate fields from ticket data (no matter what)
        if ($DeliveryOrder) {
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
                'delivery_order_id' => $DeliveryOrder->id,
                'created_by' => auth()->user()->id,
                "company_id" => $request->company_id
            ];
        }
        else {
            $salesQcData = [
                'loading_program_item_id' => $request->loading_program_item_id,
                'customer' => $request->customer ?: ($SaleOrder->customer->name ?? ''),
                'commodity' => $request->commodity ?: ($SaleOrder->sales_order_data->first()->item->name ?? ''),
                'so_qty' => $request->so_qty ?: ($SaleOrder->sales_order_data->first()->qty ?? 0),
                'do_qty' => 0,
                'factory' => $request->factory ?: ($SaleOrder->arrivalLocation->name ?? ''),
                'gala' => $request->gala ?: ($SaleOrder->subArrivalLocation->name ?? ''),
                'qc_remarks' => $request->qc_remarks,
                'status' => $request->status,
                'delivery_order_id' => null,
                'created_by' => auth()->user()->id
            ];
        }
        

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

        // Update process status on LP Item
        if ($salesQc->status == 'accept') {
            $LoadingProgramItem->update(['process_status' => 'Sales QC Accepted']);
        } elseif ($salesQc->status == 'reject') {
            $LoadingProgramItem->update(['process_status' => 'Sales QC Rejected']);
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
            'loadingProgramItem.loadingProgram.saleOrder.customer',
            'loadingProgramItem.loadingProgram.saleOrder.sales_order_data.item',
            'loadingProgramItem.arrivalLocation',
            'loadingProgramItem.subArrivalLocation',
            'loadingProgramItem.deliveryOrders.customer',
            'loadingProgramItem.deliveryOrders.delivery_order_data.item',
            'loadingProgramItem.deliveryOrders.delivery_order_data.salesOrderData',
            'loadingProgramItem.saleOrders.customer',
            'loadingProgramItem.saleOrders.sales_order_data.item',
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
            'loadingProgramItem.loadingProgram.deliveryOrder.delivery_order_data.salesOrderData',
            'loadingProgramItem.loadingProgram.deliveryOrder.arrivalLocation',
            'loadingProgramItem.loadingProgram.deliveryOrder.subArrivalLocation',
            'loadingProgramItem.loadingProgram.saleOrder.customer',
            'loadingProgramItem.loadingProgram.saleOrder.sales_order_data.item',
            'loadingProgramItem.loadingProgram.saleOrder.sales_order_data.salesOrderData',
            'loadingProgramItem.deliveryOrders.customer',
            'loadingProgramItem.deliveryOrders.delivery_order_data.item',
            'loadingProgramItem.deliveryOrders.delivery_order_data.salesOrderData',
            'loadingProgramItem.saleOrders.customer',
            'loadingProgramItem.saleOrders.sales_order_data.item',
            'loadingProgramItem.arrivalLocation',
            'loadingProgramItem.subArrivalLocation',
            'attachments'
        ])->findOrFail($id);

        $Tickets = LoadingProgramItem::whereHas('firstWeighbridge')
            ->with([
                'loadingProgram.deliveryOrder.customer',
                'loadingProgram.deliveryOrder.delivery_order_data.item',
                'loadingProgram.deliveryOrder',
                'loadingProgram.saleOrder.customer'
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

        // $DeliveryOrder = $LoadingProgramItem->loadingProgram->deliveryOrder;
        $DeliveryOrder = $LoadingProgramItem->delivery_order_id;
        $SaleOrder = $LoadingProgramItem->loadingProgram->saleOrder;
        // Auto-populate fields from ticket data (no matter what)
        if ($DeliveryOrder) {
            $salesQcData = [
                'loading_program_item_id' => $request->loading_program_item_id,
                'customer' => $request->customer ?: ($DeliveryOrder->customer->name ?? ''),
                'commodity' => $request->commodity ?: ($DeliveryOrder->delivery_order_data->first()->item->name ?? ''),
                'so_qty' => $request->so_qty ?: ($DeliveryOrder->delivery_order_data->first()->salesOrderData->qty ?? 0),
                'do_qty' => $request->do_qty ?: ($DeliveryOrder->delivery_order_data->first()->qty ?? 0),
                'factory' => $request->factory ?: ($DeliveryOrder->arrivalLocation->name ?? ''),
                'gala' => $request->gala ?: ($DeliveryOrder->subArrivalLocation->name ?? ''),
                'qc_remarks' => $request->qc_remarks,
                'delivery_order_id' => $DeliveryOrder->id,
                'status' => $request->status
            ];
        } else {
            $salesQcData = [
                'loading_program_item_id' => $request->loading_program_item_id,
                'customer' => $request->customer ?: ($SaleOrder->customer->name ?? ''),
                'commodity' => $request->commodity ?: ($SaleOrder->sales_order_data->first()->item->name ?? ''),
                'so_qty' => $request->so_qty ?: ($SaleOrder->sales_order_data->first()->qty ?? 0),
                'do_qty' => 0,
                'factory' => $request->factory ?: ($SaleOrder->arrivalLocation->name ?? ''),
                'gala' => $request->gala ?: ($SaleOrder->subArrivalLocation->name ?? ''),
                'qc_remarks' => $request->qc_remarks,
                'delivery_order_id' => null,
                'status' => $request->status
            ];
        }
    

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

        // Update process status on LP Item
        if ($salesQc->status == 'accept') {
            $LoadingProgramItem->update(['process_status' => 'Sales QC Accepted']);
        } elseif ($salesQc->status == 'reject') {
            $LoadingProgramItem->update(['process_status' => 'Sales QC Rejected']);
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
            'loadingProgram.deliveryOrder.delivery_order_data.item',
            'loadingProgram.deliveryOrder.delivery_order_data.salesOrderData',
            'loadingProgram.saleOrder.customer',
            'loadingProgram.saleOrder.sales_order_data.item',
            'deliveryOrders.customer',
            'deliveryOrders.delivery_order_data.item',
            'deliveryOrders.delivery_order_data.salesOrderData',
            'saleOrders.customer',
            'saleOrders.sales_order_data.item',
            'arrivalLocation',
            'subArrivalLocation'
        ])->findOrFail($request->loading_program_item_id);

        $orders = [];

        // Handle Delivery Orders from many-to-many relationship
        if ($LoadingProgramItem->deliveryOrders->isNotEmpty()) {
            foreach ($LoadingProgramItem->deliveryOrders as $do) {
                $factoryNames = [];
                $galaNames = [];
                
                if ($do->arrival_location_id) {
                    $factoryNames = \App\Models\Master\ArrivalLocation::whereIn('id', explode(',', $do->arrival_location_id))->pluck('name')->toArray();
                }
                if ($do->sub_arrival_location_id) {
                    $galaNames = \App\Models\Master\ArrivalSubLocation::whereIn('id', explode(',', $do->sub_arrival_location_id))->pluck('name')->toArray();
                }

                $orders[] = [
                    'type' => 'DO',
                    'number' => $do->reference_no,
                    'is_auto' => $do->is_auto_created_from_so,
                    'customer' => $do->customer->name ?? '',
                    'commodity' => $do->delivery_order_data->first()->item->name ?? '',
                    'so_qty' => $do->delivery_order_data->sum(function($d) { return $d->salesOrderData->qty ?? 0; }),
                    'do_qty' => $do->delivery_order_data->sum('qty'),
                    'factory_names' => $factoryNames,
                    'gala_names' => $galaNames
                ];
            }
        } 
        // Fallback to single delivery order if exists
        elseif ($LoadingProgramItem->loadingProgram && $LoadingProgramItem->loadingProgram->deliveryOrder) {
            $do = $LoadingProgramItem->loadingProgram->deliveryOrder;
            $factoryNames = [];
            $galaNames = [];
            
            if ($do->arrival_location_id) {
                $factoryNames = \App\Models\Master\ArrivalLocation::whereIn('id', explode(',', $do->arrival_location_id))->pluck('name')->toArray();
            }
            if ($do->sub_arrival_location_id) {
                $galaNames = \App\Models\Master\ArrivalSubLocation::whereIn('id', explode(',', $do->sub_arrival_location_id))->pluck('name')->toArray();
            }

            $orders[] = [
                'type' => 'DO',
                'number' => $do->reference_no,
                'is_auto' => $do->is_auto_created_from_so,
                'customer' => $do->customer->name ?? '',
                'commodity' => $do->delivery_order_data->first()->item->name ?? '',
                'so_qty' => $do->delivery_order_data->sum(function($d) { return $d->salesOrderData->qty ?? 0; }),
                'do_qty' => $do->delivery_order_data->sum('qty'),
                'factory_names' => $factoryNames,
                'gala_names' => $galaNames
            ];
        }

        // Handle Sale Orders if no DOs or as additional info
        if ($LoadingProgramItem->saleOrders->isNotEmpty()) {
            foreach ($LoadingProgramItem->saleOrders as $so) {
                if (empty($orders)) {
                    $orders[] = [
                        'type' => 'SO',
                        'number' => $so->reference_no,
                        'customer' => $so->customer->name ?? '',
                        'commodity' => $so->sales_order_data->first()->item->name ?? '',
                        'so_qty' => $so->sales_order_data->sum('qty'),
                        'do_qty' => 0,
                        'factory_names' => $LoadingProgramItem->arrivalLocation ? [$LoadingProgramItem->arrivalLocation->name] : [],
                        'gala_names' => $LoadingProgramItem->subArrivalLocation ? [$LoadingProgramItem->subArrivalLocation->name] : []
                    ];
                }
            }
        } elseif (empty($orders) && $LoadingProgramItem->loadingProgram && $LoadingProgramItem->loadingProgram->saleOrder) {
            $so = $LoadingProgramItem->loadingProgram->saleOrder;
            $orders[] = [
                'type' => 'SO',
                'number' => $so->reference_no,
                'customer' => $so->customer->name ?? '',
                'commodity' => $so->sales_order_data->first()->item->name ?? '',
                'so_qty' => $so->sales_order_data->sum('qty'),
                'do_qty' => 0,
                'factory_names' => $LoadingProgramItem->arrivalLocation ? [$LoadingProgramItem->arrivalLocation->name] : [],
                'gala_names' => $LoadingProgramItem->subArrivalLocation ? [$LoadingProgramItem->subArrivalLocation->name] : []
            ];
        }

        // If still empty, use defaults from LoadingProgramItem directly
        if (empty($orders)) {
             $orders[] = [
                'type' => 'Ticket',
                'number' => $LoadingProgramItem->transaction_number,
                'customer' => 'N/A',
                'commodity' => 'N/A',
                'so_qty' => 0,
                'do_qty' => 0,
                'factory_names' => $LoadingProgramItem->arrivalLocation ? [$LoadingProgramItem->arrivalLocation->name] : [],
                'gala_names' => $LoadingProgramItem->subArrivalLocation ? [$LoadingProgramItem->subArrivalLocation->name] : []
            ];
        }

        return response()->json([
            'success' => true, 
            'data' => [
                'orders' => $orders,
                'customer' => $orders[0]['customer'],
                'commodity' => $orders[0]['commodity'],
                'so_qty' => $orders[0]['so_qty'],
                'do_qty' => $orders[0]['do_qty'],
                'factory_names' => $orders[0]['factory_names'],
                'gala_names' => $orders[0]['gala_names']
            ]
        ]);
    }
}
