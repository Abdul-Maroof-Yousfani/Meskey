<?php

namespace App\Http\Controllers\Export;

use App\Http\Controllers\Controller;
use App\Models\Export\ExportDispatchQc;
use App\Models\Export\ExportDispatchQcAttachment;
use App\Models\Sales\LoadingProgramItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ExportDispatchQcController extends Controller
{
    public function index()
    {
        return view('management.export.dispatch-qc.index');
    }

    public function getList(Request $request)
    {
        $DispatchQcs = ExportDispatchQc::with([
            'loadingProgramItem',
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

        return view('management.export.dispatch-qc.getList', compact('DispatchQcs'));
    }

    public function create()
    {
        $Tickets = $this->ticketQuery()
            ->whereHas('exportLoadingSlip')
            ->with($this->ticketRelations())
            ->get()
            ->filter(function ($ticket) {
                return $this->canCreateDispatchQcForTicket($ticket);
            })
            ->values();

        return view('management.export.dispatch-qc.create', compact('Tickets'));
    }

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
            'company_id' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $LoadingProgramItem = $this->ticketQuery()
            ->with(array_merge($this->ticketRelations(), ['exportDispatchQcs', 'exportLoadingSlip']))
            ->findOrFail($request->loading_program_item_id);

        if (!$this->canCreateDispatchQcForTicket($LoadingProgramItem)) {
            return response()->json([
                'errors' => ['loading_program_item_id' => 'This ticket is not eligible for Export Dispatch QC right now.']
            ], 422);
        }

        DB::beginTransaction();

        try {
            $dispatchQc = ExportDispatchQc::create(
                $this->makeDispatchQcPayload($request, $LoadingProgramItem) + [
                    'created_by' => auth()->user()->id,
                    'company_id' => $request->company_id,
                ]
            );

            $this->storeAttachments($dispatchQc, $request);

            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();

            return response()->json($exception->getMessage(), 500);
        }

        return response()->json(['success' => 'Export Dispatch QC created successfully.', 'data' => $dispatchQc], 201);
    }

    public function show(string $id)
    {
        $DispatchQc = ExportDispatchQc::with([
            'loadingProgramItem',
            'attachments',
        ])->findOrFail($id);

        $DispatchQc->loadMissing(['loadingProgramItem' => fn ($query) => $query->with($this->ticketRelations())]);
        $Orders = $this->buildOrdersFromTicket($DispatchQc->loadingProgramItem);

        return view('management.export.dispatch-qc.show', compact('DispatchQc', 'Orders'));
    }

    public function edit(string $id)
    {
        $DispatchQc = ExportDispatchQc::with([
            'loadingProgramItem',
            'attachments',
        ])->findOrFail($id);

        $DispatchQc->loadMissing(['loadingProgramItem' => fn ($query) => $query->with($this->ticketRelations())]);

        $Tickets = $this->ticketQuery()
            ->whereHas('exportLoadingSlip')
            ->with(array_merge($this->ticketRelations(), ['exportDispatchQcs', 'exportLoadingSlip']))
            ->get()
            ->filter(function ($ticket) use ($DispatchQc) {
                return $ticket->id == $DispatchQc->loading_program_item_id || $this->canCreateDispatchQcForTicket($ticket);
            })
            ->values();

        $Orders = $this->buildOrdersFromTicket($DispatchQc->loadingProgramItem);

        return view('management.export.dispatch-qc.edit', compact('DispatchQc', 'Tickets', 'Orders'));
    }

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
            'attachments.*' => 'file|mimes:jpeg,jpg,png,pdf,doc,docx|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $dispatchQc = ExportDispatchQc::with('attachments')->findOrFail($id);

        $LoadingProgramItem = $this->ticketQuery()
            ->with(array_merge($this->ticketRelations(), ['exportDispatchQcs', 'exportLoadingSlip']))
            ->findOrFail($request->loading_program_item_id);

        $hasAnotherAcceptedQc = ExportDispatchQc::where('loading_program_item_id', $request->loading_program_item_id)
            ->where('id', '!=', $dispatchQc->id)
            ->where('status', 'accept')
            ->exists();

        if ($hasAnotherAcceptedQc) {
            return response()->json([
                'errors' => ['loading_program_item_id' => 'This ticket already has an accepted Export Dispatch QC.']
            ], 422);
        }

        $dispatchQc->update($this->makeDispatchQcPayload($request, $LoadingProgramItem));

        if ($request->hasFile('attachments')) {
            foreach ($dispatchQc->attachments as $attachment) {
                if (Storage::exists(str_replace('storage/', 'public/', $attachment->file_path))) {
                    Storage::delete(str_replace('storage/', 'public/', $attachment->file_path));
                }

                $attachment->delete();
            }

            $this->storeAttachments($dispatchQc, $request);
        }

        return response()->json(['success' => 'Export Dispatch QC updated successfully.', 'data' => $dispatchQc], 200);
    }

    public function destroy(string $id)
    {
        try {
            $dispatchQc = ExportDispatchQc::with('attachments')->findOrFail($id);

            foreach ($dispatchQc->attachments as $attachment) {
                try {
                    $filePath = str_replace('storage/', 'public/', $attachment->file_path);
                    if (Storage::exists($filePath)) {
                        Storage::delete($filePath);
                    }
                } catch (\Exception $exception) {
                    \Log::error('Failed to delete attachment file: ' . $attachment->file_path . ' - ' . $exception->getMessage());
                }

                $attachment->delete();
            }

            $dispatchQc->delete();

            return response()->json(['success' => 'Export Dispatch QC deleted successfully.'], 200);
        } catch (\Exception $exception) {
            \Log::error('Export Dispatch QC deletion failed: ' . $exception->getMessage());

            return response()->json(['error' => 'Failed to delete Export Dispatch QC.', 'details' => $exception->getMessage()], 422);
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
            'exportLoadingProgram.exportOrder.buyer',
            'exportLoadingProgram.exportOrders.product',
            'exportLoadingProgram.exportOrders.buyer',
            'deliveryOrders.customer',
            'deliveryOrders.exportOrder.product',
            'deliveryOrders.exportOrder.buyer',
            'deliveryOrders.exportPackingItems.bagType',
            'deliveryOrders.arrivalLocation',
            'deliveryOrders.subArrivalLocation',
            'exportOrders.product',
            'exportOrders.buyer',
            'arrivalLocation',
            'subArrivalLocation',
            'exportLoadingSlip',
            'exportLoadingSlip.logs',
        ];
    }

    private function buildOrdersFromTicket(?LoadingProgramItem $LoadingProgramItem): array
    {
        if (!$LoadingProgramItem) {
            return [];
        }

        $orders = [];
        $deliveryOrders = $LoadingProgramItem->deliveryOrders->where('type', 'export_order')->values();

        if ($deliveryOrders->isEmpty() && $LoadingProgramItem->exportLoadingProgram?->deliveryOrders?->isNotEmpty()) {
            $deliveryOrders = $LoadingProgramItem->exportLoadingProgram->deliveryOrders->values();
        }

        if ($deliveryOrders->isEmpty() && $LoadingProgramItem->exportLoadingProgram?->deliveryOrder) {
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
            $exportOrders = $LoadingProgramItem->exportOrders;

            if ($exportOrders->isEmpty() && $LoadingProgramItem->exportLoadingProgram?->exportOrders?->isNotEmpty()) {
                $exportOrders = $LoadingProgramItem->exportLoadingProgram->exportOrders;
            }

            if ($exportOrders->isEmpty() && $LoadingProgramItem->exportLoadingProgram?->exportOrder) {
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

    private function makeDispatchQcPayload(Request $request, LoadingProgramItem $LoadingProgramItem): array
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

    private function canCreateDispatchQcForTicket(LoadingProgramItem $ticket): bool
    {
        if ($ticket->hasAcceptedExportDispatchQc()) {
            return false;
        }

        if ($ticket->exportDispatchQcs->isEmpty()) {
            return true;
        }

        $latestRejectedQc = $ticket->exportDispatchQcs
            ->where('status', 'reject')
            ->sortByDesc('created_at')
            ->first();

        if (!$latestRejectedQc) {
            return false;
        }

        if (!$ticket->exportLoadingSlip) {
            return false;
        }

        return $ticket->exportLoadingSlip->logs
            ->where('dispatch_qc_id', $latestRejectedQc->id)
            ->isNotEmpty();
    }

    private function storeAttachments(ExportDispatchQc $dispatchQc, Request $request): void
    {
        if (!$request->hasFile('attachments')) {
            return;
        }

        foreach ($request->file('attachments') as $file) {
            $originalName = $file->getClientOriginalName();
            $fileName = time() . '_' . uniqid() . '_' . $originalName;
            $path = $file->storeAs('sales_qc_attachments', $fileName, 'public');

            ExportDispatchQcAttachment::create([
                'dispatch_qc_id' => $dispatchQc->id,
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
