<?php

namespace App\Http\Controllers\Export;

use App\Http\Controllers\Controller;
use App\Models\Export\ExportDeliveryOrder;
use App\Models\Export\ExportLoadingSlip;
use App\Models\Sales\LoadingSlipLog;
use App\Models\Sales\LoadingProgramItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ExportLoadingSlipController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        return view('management.export.loading-slip.index');
    }

    public function getList(Request $request)
    {
        $LoadingSlips = ExportLoadingSlip::with([
            'loadingProgramItem.exportLoadingProgram.deliveryOrders.customer',
            'loadingProgramItem.exportLoadingProgram.deliveryOrders.exportOrder.product',
            'loadingProgramItem.deliveryOrders.exportOrder.product',
            'loadingProgramItem.exportDispatchQc',
            'logs',
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
                    })
                    ->orWhere('customer', 'like', $searchTerm)
                    ->orWhere('commodity', 'like', $searchTerm);
                });
            })
            ->latest()
            ->paginate($request->get('per_page', 25));

        return view('management.export.loading-slip.getList', compact('LoadingSlips'));
    }

    public function create()
    {
        $availableTickets = $this->ticketQuery()
            ->whereHas('exportQc', function ($query) {
                $query->where('status', 'accept')
                    ->orWhere('am_approval_status', 'approved');
            })
            ->whereDoesntHave('exportLoadingSlip')
            ->with($this->ticketRelations())
            ->get();

        return view('management.export.loading-slip.create', compact('availableTickets'));
    }

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
            'labour' => 'required|in:paid,not_paid',
            'company_id' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $existingSlip = ExportLoadingSlip::where('loading_program_item_id', $request->loading_program_item_id)->first();
        if ($existingSlip) {
            return response()->json(['errors' => ['loading_program_item_id' => ['Loading slip already exists for this ticket.']]], 422);
        }

        try {
            DB::beginTransaction();

            $LoadingProgramItem = $this->ticketQuery()
                ->with($this->ticketRelations())
                ->findOrFail($request->loading_program_item_id);

            $DeliveryOrder = $this->resolveDeliveryOrder($LoadingProgramItem);

            if ($DeliveryOrder) {
                $totalBags = $DeliveryOrder->exportPackingItems->sum('no_of_bags');
                $usedBags = $DeliveryOrder->loadingSlips->sum('no_of_bags');
                $remainingBags = $totalBags - $usedBags;

                if ($request->no_of_bags > $remainingBags) {
                    return response()->json(['errors' => ['no_of_bags' => ["Your balance is $remainingBags."]]], 422);
                }
            }

            $loadingSlip = ExportLoadingSlip::create([
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
                'labour' => $request->labour,
                'company_id' => $request->company_id,
            ]);

            DB::commit();

            return response()->json(['success' => 'Export Loading Slip created successfully.', 'data' => $loadingSlip], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to create Loading Slip.', 'details' => $e->getMessage()], 422);
        }
    }

    public function show(string $id)
    {
        $loadingSlip = ExportLoadingSlip::with([
            'loadingProgramItem',
            'createdBy',
        ])->findOrFail($id);

        $Orders = $this->buildOrders($loadingSlip->loadingProgramItem?->loadMissing($this->ticketRelations()));

        return view('management.export.loading-slip.show', compact('loadingSlip', 'Orders'));
    }

    public function edit(string $id)
    {
        $loadingSlip = ExportLoadingSlip::with([
            'loadingProgramItem',
            'createdBy',
            'logs.editedBy',
        ])->findOrFail($id);

        $loadingSlip->loadMissing(['loadingProgramItem' => fn ($query) => $query->with($this->ticketRelations())]);

        $Orders = $this->buildOrders($loadingSlip->loadingProgramItem);
        $canEdit = $loadingSlip->canBeEdited();
        $rejectedDispatchQc = null;

        if ($loadingSlip->hasRejectedDispatchQc()) {
            $rejectedDispatchQc = $loadingSlip->getLatestRejectedDispatchQc();
        }

        return view('management.export.loading-slip.edit', compact('loadingSlip', 'Orders', 'canEdit', 'rejectedDispatchQc'));
    }

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
            'labour' => 'required|in:paid,not_paid',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $loadingSlip = ExportLoadingSlip::with('loadingProgramItem.exportDispatchQc')->findOrFail($id);

        if (!$loadingSlip->canBeEdited()) {
            return response()->json(['error' => 'This loading slip cannot be edited because its Dispatch QC has been accepted.'], 422);
        }

        $LoadingProgramItem = $this->ticketQuery()->with($this->ticketRelations())->findOrFail($loadingSlip->loading_program_item_id);
        $DeliveryOrder = $this->resolveDeliveryOrder($LoadingProgramItem);

        if ($DeliveryOrder) {
            $totalBags = $DeliveryOrder->exportPackingItems->sum('no_of_bags');
            $usedBags = $DeliveryOrder->loadingSlips->sum('no_of_bags');
            $availableBags = ($totalBags - $usedBags) + $loadingSlip->no_of_bags;

            if ($request->no_of_bags > $availableBags) {
                return response()->json(['errors' => ['no_of_bags' => ["Your balance is $availableBags."]]], 422);
            }
        }

        try {
            DB::beginTransaction();

            $rejectedDispatchQc = $loadingSlip->loadingProgramItem?->latestRejectedExportDispatchQc;
            if ($rejectedDispatchQc) {
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
                    // 'delivery_order_id' => $DeliveryOrder?->id,
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
                'labour' => $request->labour,
            ]);

            DB::commit();

            return response()->json(['success' => 'Export Loading Slip updated successfully.', 'data' => $loadingSlip], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to update Loading Slip.', 'details' => $e->getMessage()], 422);
        }
    }

    public function destroy(string $id)
    {
        try {
            $loadingSlip = ExportLoadingSlip::findOrFail($id);
            $loadingSlip->loadingProgramItem?->exportDispatchQcs()->delete();
            $loadingSlip->delete();

            return response()->json(['success' => 'Export Loading Slip deleted successfully.'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to delete Loading Slip.', 'details' => $e->getMessage()], 422);
        }
    }

    public function getTicketRelatedData(Request $request)
    {
        $LoadingProgramItem = $this->ticketQuery()
            ->with($this->ticketRelations())
            ->findOrFail($request->loading_program_item_id);

        $orders = $this->buildOrders($LoadingProgramItem);

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
                'bag_size' => $orders[0]['bag_size'] ?? 0,
                'is_pohanch' => false,
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
            'exportQc',
            'exportDispatchQcs',
            'exportDispatchQc',
            'latestRejectedExportDispatchQc',
            'exportLoadingSlip.logs',
        ];
    }

    private function resolveDeliveryOrder(LoadingProgramItem $item): ?ExportDeliveryOrder
    {
        $deliveryOrder = $item->exportLoadingProgram?->deliveryOrders?->first()
            ?? $item->exportLoadingProgram?->deliveryOrder
            ?? $item->deliveryOrders->where('type', 'export_order')->first();

        if (!$deliveryOrder) {
            return null;
        }

        return ExportDeliveryOrder::find($deliveryOrder->id);
    }

    private function buildOrders(?LoadingProgramItem $item): array
    {
        if (!$item) {
            return [];
        }

        $orders = [];
        $deliveryOrders = $item->exportLoadingProgram?->deliveryOrders?->values() ?? collect();

        if ($deliveryOrders->isEmpty() && $item->exportLoadingProgram?->deliveryOrder) {
            $deliveryOrders = collect([$item->exportLoadingProgram->deliveryOrder]);
        }

        if ($deliveryOrders->isEmpty()) {
            $exportDeliveryOrderIds = $item->deliveryOrders
                ->where('type', 'export_order')
                ->pluck('id')
                ->filter()
                ->values();

            if ($exportDeliveryOrderIds->isNotEmpty()) {
                $deliveryOrders = ExportDeliveryOrder::with([
                    'customer',
                    'exportOrder.product',
                    'exportOrder.packingItems',
                    'exportPackingItems.bagType',
                ])->whereIn('id', $exportDeliveryOrderIds)->get();
            }
        }

        foreach ($deliveryOrders as $do) {
            $orders[] = [
                'type' => 'DO',
                'number' => $do->reference_no,
                'customer' => $do->customer->name ?? '',
                'commodity' => $do->exportOrder->product->name ?? $do->exportPackingItems->first()?->bagType?->name ?? '',
                'so_qty' => (float) optional($do->exportOrder)->packingItems?->sum('metric_tons'),
                'do_qty' => (float) $do->exportPackingItems->sum('metric_tons'),
                'factory_names' => $this->getLocationNames($do->arrival_location_id, \App\Models\Master\ArrivalLocation::class),
                'gala_names' => $this->getLocationNames($do->sub_arrival_location_id, \App\Models\Master\ArrivalSubLocation::class),
                'bag_size' => $do->exportPackingItems->first()->bag_size ?? 0,
            ];
        }

        if (empty($orders)) {
            $exportOrders = $item->exportOrders;

            if ($exportOrders->isEmpty() && $item->exportLoadingProgram?->exportOrders?->isNotEmpty()) {
                $exportOrders = $item->exportLoadingProgram->exportOrders;
            }

            if ($exportOrders->isEmpty() && $item->exportLoadingProgram?->exportOrder) {
                $exportOrders = collect([$item->exportLoadingProgram->exportOrder]);
            }

            foreach ($exportOrders as $eo) {
                $orders[] = [
                    'type' => 'EO',
                    'number' => $eo->voucher_no ?? $eo->contract_no ?? $eo->id,
                    'customer' => $eo->buyer->name ?? '',
                    'commodity' => $eo->product->name ?? '',
                    'so_qty' => (float) $eo->packingItems->sum('metric_tons'),
                    'do_qty' => 0,
                    'factory_names' => $item->arrivalLocation ? [$item->arrivalLocation->name] : [],
                    'gala_names' => $item->subArrivalLocation ? [$item->subArrivalLocation->name] : [],
                    'bag_size' => $eo->packingItems->first()->bag_size ?? 0,
                ];
            }
        }

        if (empty($orders)) {
            $orders[] = [
                'type' => 'Ticket',
                'number' => $item->transaction_number,
                'customer' => 'N/A',
                'commodity' => 'N/A',
                'so_qty' => 0,
                'do_qty' => 0,
                'factory_names' => $item->arrivalLocation ? [$item->arrivalLocation->name] : [],
                'gala_names' => $item->subArrivalLocation ? [$item->subArrivalLocation->name] : [],
                'bag_size' => 0,
            ];
        }

        return $orders;
    }

    private function getLocationNames($ids, string $modelClass): array
    {
        if (blank($ids)) {
            return [];
        }

        $ids = is_array($ids) ? $ids : explode(',', (string) $ids);

        return $modelClass::whereIn('id', array_filter($ids))->pluck('name')->toArray();
    }
}
