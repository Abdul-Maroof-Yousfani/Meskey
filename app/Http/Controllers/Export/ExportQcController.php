<?php

namespace App\Http\Controllers\Export;

use App\Http\Controllers\Controller;
use App\Models\Export\ExportQc;
use App\Models\Export\ExportQcAttachment;
use App\Models\Sales\LoadingProgramItem;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ExportQcController extends Controller
{
    public function index()
    {
        return view('management.export.export-qc.index');
    }

    public function getList(Request $request)
    {
        $ExportQcs = ExportQc::with([
            'loadingProgramItem.exportLoadingProgram.deliveryOrders.customer',
            'loadingProgramItem.exportLoadingProgram.deliveryOrders.exportOrder.product',
            'loadingProgramItem.deliveryOrders.exportOrder.product',
            'createdBy',
        ])
            ->whereHas('loadingProgramItem.exportLoadingProgram', function ($query) {
                $query->where('type', 'export_order');
            })
            ->when($request->filled('search'), function ($q) use ($request) {
                $searchTerm = '%' . $request->search . '%';

                return $q->where(function ($sq) use ($searchTerm) {
                    $sq->whereHas('loadingProgramItem', function ($query) use ($searchTerm) {
                        $query->where('transaction_number', 'like', $searchTerm)
                            ->orWhere('truck_number', 'like', $searchTerm);
                    })->orWhere('status', 'like', $searchTerm);
                });
            })
            ->latest()
            ->paginate($request->get('per_page', 25));

        return view('management.export.export-qc.getList', compact('ExportQcs'));
    }

    public function create()
    {
        $Tickets = $this->ticketQuery()
            ->whereHas('exportFirstWeighbridge')
            ->whereDoesntHave('exportQc')
            ->with($this->ticketRelations())
            ->get();

        return view('management.export.export-qc.create', compact('Tickets'));
    }

    public function store(Request $request)
    {
        DB::beginTransaction();

        try {
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
                'company_id' => 'required|numeric',
            ]);

            if ($validator->fails()) {
                DB::rollBack();
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $existingExportQc = ExportQc::where('loading_program_item_id', $request->loading_program_item_id)->first();
            if ($existingExportQc) {
                DB::rollBack();
                return response()->json(['errors' => ['loading_program_item_id' => 'This ticket already has an Export QC.']], 422);
            }

            $LoadingProgramItem = $this->ticketQuery()
                ->with($this->ticketRelations())
                ->findOrFail($request->loading_program_item_id);

            $exportQc = ExportQc::create(
                $this->makeQcPayload($request, $LoadingProgramItem) + [
                    'created_by' => auth()->user()->id,
                    'company_id' => $request->company_id,
                ]
            );

            $this->storeAttachments($exportQc, $request);

            DB::commit();

            return response()->json(['success' => 'Export QC created successfully.', 'data' => $exportQc], 201);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => 'Something went wrong',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show(string $id)
    {
        $ExportQc = ExportQc::with([
            'loadingProgramItem',
            'attachments',
        ])->findOrFail($id);

        $Orders = $this->buildOrdersFromTicket(
            $ExportQc->loadingProgramItem?->loadMissing($this->ticketRelations())
        );

        return view('management.export.export-qc.show', compact('ExportQc', 'Orders'));
    }

    public function edit(string $id)
    {
        $ExportQc = ExportQc::with([
            'loadingProgramItem',
            'attachments',
        ])->findOrFail($id);

        $ExportQc->loadMissing(['loadingProgramItem' => fn($query) => $query->with($this->ticketRelations())]);

        $Tickets = $this->ticketQuery()
            ->whereHas('exportFirstWeighbridge')
            ->where(function ($query) use ($ExportQc) {
                $query->whereDoesntHave('exportQc')
                    ->orWhere('id', $ExportQc->loading_program_item_id);
            })
            ->with($this->ticketRelations())
            ->get();

        $Orders = $this->buildOrdersFromTicket($ExportQc->loadingProgramItem);

        return view('management.export.export-qc.edit', compact('ExportQc', 'Tickets', 'Orders'));
    }

    public function update(Request $request, string $id)
    {
        DB::beginTransaction();

        try {
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
                'attachments.*' => 'file|mimes:jpeg,jpg,png,pdf,doc,docx|max:10240',
            ]);

            if ($validator->fails()) {
                DB::rollBack();
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $exportQc = ExportQc::with('attachments')
                ->lockForUpdate()
                ->findOrFail($id);

            if (!$exportQc) {
                DB::rollBack();
                return response()->json([
                    'success' => 'Export QC already deleted or not found.'
                ], 404);
            }

            $existingExportQc = ExportQc::where('loading_program_item_id', $request->loading_program_item_id)
                ->where('id', '!=', $id)
                ->first();

            if ($existingExportQc) {
                DB::rollBack();
                return response()->json([
                    'errors' => ['loading_program_item_id' => 'This ticket already has an Export QC.']
                ], 422);
            }

            $LoadingProgramItem = $this->ticketQuery()
                ->with($this->ticketRelations())
                ->findOrFail($request->loading_program_item_id);

            $exportQc->update($this->makeQcPayload($request, $LoadingProgramItem));

            if ($request->hasFile('attachments')) {
                foreach ($exportQc->attachments as $attachment) {
                    if (Storage::exists(str_replace('storage/', 'public/', $attachment->file_path))) {
                        Storage::delete(str_replace('storage/', 'public/', $attachment->file_path));
                    }
                    $attachment->delete();
                }

                $this->storeAttachments($exportQc, $request);
            }

            DB::commit();

            return response()->json(['success' => 'Export QC updated successfully.', 'data' => $exportQc], 200);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(string $id)
    {
        DB::beginTransaction();

        try {
            $exportQc = ExportQc::with('attachments')
                ->lockForUpdate()
                ->findOrFail($id);

            if (!$exportQc) {
                DB::rollBack();

                return response()->json([
                    'success' => 'Export QC already deleted or not found.'
                ], 404);
            }

            foreach ($exportQc->attachments as $attachment) {
                try {
                    $filePath = str_replace('storage/', 'public/', $attachment->file_path);
                    if (Storage::exists($filePath)) {
                        Storage::delete($filePath);
                    }

                    $attachment->delete();

                } catch (\Exception $e) {
                    \Log::error('Failed to delete attachment file: ' . $attachment->file_path . ' - ' . $e->getMessage());
                }
            }

            $exportQc->delete();

            DB::commit();

            return response()->json(['success' => 'Export QC deleted successfully.'], 200);
        } catch (\Exception $e) {

            DB::rollBack();

            \Log::error('Export QC deletion failed: ' . $e->getMessage());

            return response()->json(['error' => 'Failed to delete Export QC.', 'details' => $e->getMessage()], 422);
        }
    }

    public function getTicketRelatedData(Request $request)
    {
        $LoadingProgramItem = $this->ticketQuery()
            ->with($this->ticketRelations())
            ->findOrFail($request->loading_program_item_id);

        $orders = $this->buildOrdersFromTicket($LoadingProgramItem);

        return response()->json([
            'success' => true,
            'data' => [
                'orders' => $orders,
                'customer' => $orders[0]['customer'] ?? '',
                'commodity' => $orders[0]['commodity'] ?? '',
                'so_qty' => $orders[0]['so_qty'] ?? 0,
                'do_qty' => $orders[0]['do_qty'] ?? 0,
                'factory_names' => $orders[0]['factory_names'] ?? [],
                'gala_names' => $orders[0]['gala_names'] ?? [],
            ],
        ]);
    }

    private function ticketQuery()
    {
        return LoadingProgramItem::whereHas('exportLoadingProgram', function ($query) {
            $query->where('type', 'export_order');
        });
    }

    private function ticketRelations(): array
    {
        return [
            'exportLoadingProgram.deliveryOrder.customer',
            'exportLoadingProgram.deliveryOrder.exportOrder.product',
            'exportLoadingProgram.deliveryOrder.exportPackingItems.bagType',
            'exportLoadingProgram.deliveryOrder.arrivalLocation',
            'exportLoadingProgram.deliveryOrder.subArrivalLocation',
            'exportLoadingProgram.deliveryOrders.customer',
            'exportLoadingProgram.deliveryOrders.exportOrder.product',
            'exportLoadingProgram.deliveryOrders.exportPackingItems.bagType',
            'exportLoadingProgram.deliveryOrders.arrivalLocation',
            'exportLoadingProgram.deliveryOrders.subArrivalLocation',
            'exportLoadingProgram.exportOrder.product',
            'exportLoadingProgram.exportOrders.product',
            'deliveryOrders.customer',
            'deliveryOrders.exportOrder.product',
            'deliveryOrders.exportPackingItems.bagType',
            'deliveryOrders.arrivalLocation',
            'deliveryOrders.subArrivalLocation',
            'exportOrders.product',
            'arrivalLocation',
            'subArrivalLocation',
        ];
    }

    private function buildOrdersFromTicket(?LoadingProgramItem $LoadingProgramItem): array
    {
        if (!$LoadingProgramItem) {
            return [];
        }

        $orders = [];
        $deliveryOrders = $LoadingProgramItem->deliveryOrders
            ->where('type', 'export_order')
            ->where('am_approval_status', 'approved')
            ->values();

        if ($deliveryOrders->isEmpty() && $LoadingProgramItem->exportLoadingProgram?->deliveryOrders?->isNotEmpty()) {
            $deliveryOrders = $LoadingProgramItem->exportLoadingProgram->deliveryOrders
                ->where('am_approval_status', 'approved')
                ->values();
        }

        if (
            $deliveryOrders->isEmpty()
            && $LoadingProgramItem->exportLoadingProgram?->deliveryOrder
            && $LoadingProgramItem->exportLoadingProgram->deliveryOrder->am_approval_status === 'approved'
        ) {
            $deliveryOrders = collect([$LoadingProgramItem->exportLoadingProgram->deliveryOrder]);
        }

        foreach ($deliveryOrders as $deliveryOrder) {
            $orders[] = [
                'type' => 'DO',
                'number' => $deliveryOrder->reference_no,
                'customer' => $deliveryOrder->customer->name ?? '',
                'commodity' => $deliveryOrder->exportOrder->product->name
                    ?? $deliveryOrder->exportPackingItems->first()?->bagType?->name
                    ?? '',
                'so_qty' => $this->getExportOrderQty($deliveryOrder->exportOrder),
                'do_qty' => $this->getExportDeliveryOrderQty($deliveryOrder),
                'factory_names' => $this->getLocationNames($deliveryOrder->arrival_location_id, \App\Models\Master\ArrivalLocation::class),
                'gala_names' => $this->getLocationNames($deliveryOrder->sub_arrival_location_id, \App\Models\Master\ArrivalSubLocation::class),
            ];
        }

        if (empty($orders)) {
            $exportOrders = $LoadingProgramItem->exportOrders
                ->where('am_approval_status', 'approved')
                ->values();

            if ($exportOrders->isEmpty() && $LoadingProgramItem->exportLoadingProgram?->exportOrders?->isNotEmpty()) {
                $exportOrders = $LoadingProgramItem->exportLoadingProgram->exportOrders
                    ->where('am_approval_status', 'approved')
                    ->values();
            }

            if (
                $exportOrders->isEmpty()
                && $LoadingProgramItem->exportLoadingProgram?->exportOrder
                && $LoadingProgramItem->exportLoadingProgram->exportOrder->am_approval_status === 'approved'
            ) {
                $exportOrders = collect([$LoadingProgramItem->exportLoadingProgram->exportOrder]);
            }

            foreach ($exportOrders as $exportOrder) {
                $orders[] = [
                    'type' => 'EO',
                    'number' => $exportOrder->voucher_no ?? $exportOrder->contract_no ?? $exportOrder->id,
                    'customer' => $exportOrder->buyer->name ?? '',
                    'commodity' => $exportOrder->product->name ?? '',
                    'so_qty' => $this->getExportOrderQty($exportOrder),
                    'do_qty' => 0,
                    'factory_names' => $LoadingProgramItem->arrivalLocation ? [$LoadingProgramItem->arrivalLocation->name] : [],
                    'gala_names' => $LoadingProgramItem->subArrivalLocation ? [$LoadingProgramItem->subArrivalLocation->name] : [],
                ];
            }
        }

        if (empty($orders)) {
            $orders[] = [
                'type' => 'Ticket',
                'number' => $LoadingProgramItem->transaction_number,
                'customer' => 'N/A',
                'commodity' => 'N/A',
                'so_qty' => 0,
                'do_qty' => 0,
                'factory_names' => $LoadingProgramItem->arrivalLocation ? [$LoadingProgramItem->arrivalLocation->name] : [],
                'gala_names' => $LoadingProgramItem->subArrivalLocation ? [$LoadingProgramItem->subArrivalLocation->name] : [],
            ];
        }

        return $orders;
    }

    private function makeQcPayload(Request $request, LoadingProgramItem $LoadingProgramItem): array
    {
        $orders = $this->buildOrdersFromTicket($LoadingProgramItem);
        $primaryOrder = $orders[0] ?? null;
        $deliveryOrderId = optional(
            $LoadingProgramItem->deliveryOrders->where('type', 'export_order')->first()
            ?? $LoadingProgramItem->exportLoadingProgram?->deliveryOrders?->first()
            ?? $LoadingProgramItem->exportLoadingProgram?->deliveryOrder
        )->id;

        return [
            'loading_program_item_id' => $request->loading_program_item_id,
            'customer' => $request->customer ?: ($primaryOrder['customer'] ?? ''),
            'commodity' => $request->commodity ?: ($primaryOrder['commodity'] ?? ''),
            'so_qty' => $request->so_qty ?: ($primaryOrder['so_qty'] ?? 0),
            'do_qty' => $request->do_qty ?: ($primaryOrder['do_qty'] ?? 0),
            'factory' => $request->factory ?: implode(', ', $primaryOrder['factory_names'] ?? []),
            'gala' => $request->gala ?: implode(', ', $primaryOrder['gala_names'] ?? []),
            'qc_remarks' => $request->qc_remarks,
            'status' => $request->status,
            'delivery_order_id' => $deliveryOrderId,
        ];
    }

    private function storeAttachments(ExportQc $exportQc, Request $request): void
    {
        if (!$request->hasFile('attachments')) {
            return;
        }

        foreach ($request->file('attachments') as $file) {
            $originalName = $file->getClientOriginalName();
            $fileName = time() . '_' . uniqid() . '_' . $originalName;
            $path = $file->storeAs('sales_qc_attachments', $fileName, 'public');

            ExportQcAttachment::create([
                'sales_qc_id' => $exportQc->id,
                'file_path' => 'storage/' . $path,
                'file_name' => $originalName,
                'file_type' => $file->getClientMimeType(),
                'file_size' => $file->getSize(),
                'uploaded_by' => auth()->user()->id,
            ]);
        }
    }

    private function getLocationNames($ids, string $modelClass): array
    {
        if (blank($ids)) {
            return [];
        }

        $ids = is_array($ids) ? $ids : explode(',', (string) $ids);

        return $modelClass::whereIn('id', array_filter($ids))->pluck('name')->toArray();
    }

    private function getExportOrderQty($exportOrder): float
    {
        if (!$exportOrder) {
            return 0;
        }

        return (float) $exportOrder->packingItems->sum(function ($item) {
            return $item->metric_tons ?? 0;
        });
    }

    private function getExportDeliveryOrderQty($deliveryOrder): float
    {
        if (!$deliveryOrder) {
            return 0;
        }

        return (float) $deliveryOrder->exportPackingItems->sum(function ($item) {
            return $item->metric_tons ?? 0;
        });
    }
}
