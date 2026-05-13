<?php

namespace App\Http\Controllers\Export;

use App\Http\Controllers\Controller;
use App\Models\Export\ExportDeliveryOrder;
use App\Models\Export\ExportLoadingSlip;
use App\Models\Master\ArrivalLocation;
use App\Models\Master\ArrivalSubLocation;
use App\Models\Sales\LoadingSlipLog;
use App\Models\Sales\LoadingProgramItem;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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
                    ->orWhere(function ($approvalQuery) {
                        $approvalQuery->where('status', 'reject')
                            ->where('am_approval_status', 'rejected');
                    });
            })
            ->whereHas('exportFirstWeighbridge')
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
            'gala' => 'required|array|min:1',
            'gala.*' => 'required|string|max:255',
            'no_of_bags' => 'required|integer|min:1',
            'empty_bags' => 'nullable|string|max:255',
            'bag_size' => 'required|numeric|min:0',
            'kilogram' => 'required|numeric|min:0',
            'remarks' => 'nullable|string',
            'company_id' => 'required|numeric',
            'seal_no' => 'required|string|max:255',
            'stacks' => 'required|array|min:1',
            'stacks.*.bag_type' => 'required|string',
            'stacks.*.packing_size' => 'required|string',
            'stacks.*.input_size' => 'required|string',
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
                ->whereHas('exportFirstWeighbridge')
                ->with($this->ticketRelations())
                ->findOrFail($request->loading_program_item_id);

            $deliveryOrders = $this->resolveDeliveryOrders($LoadingProgramItem);

            if ($deliveryOrders->isNotEmpty()) {
                $bagSummary = $this->getBagSummary($deliveryOrders);
                $remainingBags = $bagSummary['remaining_bags'];

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
                'factory' => is_array($request->factory) ? implode(', ', $request->factory) : $request->factory,
                'gala' => $this->encodeStoredMultiValue($request->gala),
                'no_of_bags' => $request->no_of_bags,
                'empty_bags' => $request->empty_bags,
                'bag_size' => $request->bag_size,
                'kilogram' => $request->kilogram,
                'delivery_order_id' => $deliveryOrders->first()?->id,
                'remarks' => $request->remarks,
                'created_by' => auth()->user()->id,
                'company_id' => $request->company_id,
                'seal_no' => $request->seal_no,
            ]);

            foreach ($request->stacks as $stack) {
                $loadingSlip->stacks()->create([
                    'bag_type' => $stack['bag_type'],
                    'packing_size' => $stack['packing_size'],
                    'input_size' => $stack['input_size'],
                ]);
            }

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
            'stacks',
        ])->findOrFail($id);

        $Orders = $this->buildOrders($loadingSlip->loadingProgramItem?->loadMissing($this->ticketRelations()));
        $selectedGalas = $this->parseStoredMultiValue($loadingSlip->gala);

        return view('management.export.loading-slip.show', compact('loadingSlip', 'Orders', 'selectedGalas'));
    }

    public function edit(string $id)
    {
        $loadingSlip = ExportLoadingSlip::with([
            'loadingProgramItem',
            'createdBy',
            'logs.editedBy',
            'stacks',
        ])->findOrFail($id);

        $loadingSlip->loadMissing(['loadingProgramItem' => fn($query) => $query->with($this->ticketRelations())]);

        $Orders = $this->buildOrders($loadingSlip->loadingProgramItem);
        $canEdit = $loadingSlip->canBeEdited();
        $rejectedDispatchQc = null;

        if ($loadingSlip->hasRejectedDispatchQc()) {
            $rejectedDispatchQc = $loadingSlip->getLatestRejectedDispatchQc();
        }

        $deliveryOrders = $this->resolveDeliveryOrders($loadingSlip->loadingProgramItem);
        $selectedGalas = $this->parseStoredMultiValue($loadingSlip->gala);
        $bagTypes = $deliveryOrders->flatMap(function ($do) {
            return $do->exportPackingItems;
        })->map(function ($item) {
            return $item->bagType->name ?? 'N/A';
        })->unique()->values();

        $packingSizes = $deliveryOrders->flatMap(function ($do) {
            return $do->exportPackingItems;
        })->map(function ($item) {
            return (string) ($item->bag_size ?? '0');
        })->unique()->values();

        return view('management.export.loading-slip.edit', compact('loadingSlip', 'Orders', 'canEdit', 'rejectedDispatchQc', 'bagTypes', 'packingSizes', 'selectedGalas'));
    }

    public function update(Request $request, string $id)
    {
        DB::beginTransaction();

        try {
            $validator = Validator::make($request->all(), [
                'customer' => 'required|string|max:255',
                'commodity' => 'required|string|max:255',
                'so_qty' => 'required|numeric|min:0',
                'do_qty' => 'required|numeric|min:0',
                'factory' => 'required|string|max:255',
                'gala' => 'required|array|min:1',
                'gala.*' => 'required|string|max:255',
                'no_of_bags' => 'required|integer|min:1',
                'empty_bags' => 'nullable|string|max:255',
                'bag_size' => 'required|numeric|min:0',
                'kilogram' => 'required|numeric|min:0',
                'remarks' => 'nullable|string',
                'seal_no' => 'required|string|max:255',
                'stacks' => 'required|array|min:1',
                'stacks.*.bag_type' => 'required|string',
                'stacks.*.packing_size' => 'required|string',
                'stacks.*.input_size' => 'required|string',
            ]);

            if ($validator->fails()) {
                DB::rollBack();
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $loadingSlip = ExportLoadingSlip::with('loadingProgramItem.exportDispatchQc')
                ->lockForUpdate()
                ->find($id);

            if (!$loadingSlip) {
                DB::rollBack();
                return response()->json([
                    'error' => 'Loading Slip already deleted or not found.'
                ], 404);
            }

            /* 
            if (!$loadingSlip->canBeEdited()) {
                DB::rollBack();
                return response()->json(['error' => 'This loading slip cannot be edited because its Dispatch QC has been accepted.'], 422);
            }
            */

            $LoadingProgramItem = $this->ticketQuery()
                ->whereHas('exportFirstWeighbridge')
                ->with($this->ticketRelations())
                ->findOrFail($loadingSlip->loading_program_item_id);
            $deliveryOrders = $this->resolveDeliveryOrders($LoadingProgramItem);

            if ($deliveryOrders->isNotEmpty()) {
                $bagSummary = $this->getBagSummary($deliveryOrders, $loadingSlip);
                $availableBags = $bagSummary['remaining_bags'];

                if ($request->no_of_bags > $availableBags) {
                    DB::rollBack();
                    return response()->json(['errors' => ['no_of_bags' => ["Your balance is $availableBags."]]], 422);
                }
            }

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
                    'gala' => $this->encodeStoredMultiValue($this->parseStoredMultiValue($loadingSlip->gala)),
                    'no_of_bags' => $loadingSlip->no_of_bags,
                    'bag_size' => $loadingSlip->bag_size,
                    'kilogram' => $loadingSlip->kilogram,
                    'remarks' => $loadingSlip->remarks,
                    'qc_remarks' => $rejectedDispatchQc->qc_remarks,
                    'edited_by' => auth()->user()->id,
                    'seal_no' => $loadingSlip->seal_no,
                    'empty_bags' => $loadingSlip->empty_bags,
                ]);
            }

            $loadingSlip->update([
                'customer' => $request->customer,
                'commodity' => $request->commodity,
                'so_qty' => $request->so_qty,
                'do_qty' => $request->do_qty,
                'factory' => is_array($request->factory) ? implode(', ', $request->factory) : $request->factory,
                'gala' => $this->encodeStoredMultiValue($request->gala),
                'no_of_bags' => $request->no_of_bags,
                'empty_bags' => $request->empty_bags,
                'bag_size' => $request->bag_size,
                'kilogram' => $request->kilogram,
                'remarks' => $request->remarks,
                'seal_no' => $request->seal_no,
            ]);

            $loadingSlip->stacks()->delete();
            foreach ($request->stacks as $stack) {
                $loadingSlip->stacks()->create([
                    'bag_type' => $stack['bag_type'],
                    'packing_size' => $stack['packing_size'],
                    'input_size' => $stack['input_size'],
                ]);
            }

            DB::commit();

            return response()->json(['success' => 'Export Loading Slip updated successfully.', 'data' => $loadingSlip], 200);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'error' => 'Failed to update Loading Slip.',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(string $id)
    {
        DB::beginTransaction();

        try {
            $loadingSlip = ExportLoadingSlip::with('loadingProgramItem.exportDispatchQc')
                ->lockForUpdate()
                ->find($id);

            if (!$loadingSlip) {
                DB::rollBack();
                return response()->json([
                    'error' => 'Loading Slip already deleted or not found.'
                ], 404);
            }

            $loadingSlip->loadingProgramItem?->exportDispatchQcs()->delete();
            $loadingSlip->delete();

            DB::commit();

            return response()->json([
                'success' => 'Export Loading Slip deleted successfully.'
            ], 200);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'error' => 'Failed to delete Loading Slip.',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    public function getTicketRelatedData(Request $request)
    {
        $LoadingProgramItem = $this->ticketQuery()
            ->with($this->ticketRelations())
            ->findOrFail($request->loading_program_item_id);

        $orders = $this->buildOrders($LoadingProgramItem);
        $summary = $this->summarizeOrders($orders);
        $deliveryOrders = $this->resolveDeliveryOrders($LoadingProgramItem)->unique('id');
        $bagSummary = $this->getBagSummary($deliveryOrders);

        return response()->json([
            'success' => true,
            'data' => [
                'orders' => $orders,
                'customer' => $summary['customer'],
                'commodity' => $summary['commodity'],
                'so_qty' => $summary['so_qty'],
                'do_qty' => $summary['do_qty'],
                'factory_names' => $summary['factory_names'],
                'gala_names' => $summary['gala_names'],
                'bag_size' => $summary['bag_size'],
                'total_bags' => $bagSummary['total_bags'],
                'used_bags' => $bagSummary['used_bags'],
                'remaining_bags' => $bagSummary['remaining_bags'],
                'is_pohanch' => false,
                'bag_types' => $deliveryOrders->flatMap(function ($do) {
                    return $do->exportPackingItems;
                })->map(function ($item) {
                    return $item->bagType->name ?? 'N/A';
                })->unique()->values(),
                'packing_sizes' => $deliveryOrders->flatMap(function ($do) {
                    return $do->exportPackingItems;
                })->map(function ($item) {
                    return (string) ($item->bag_size ?? '0');
                })->unique()->values(),
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
            'exportLoadingProgram.deliveryOrder.locations',
            'exportLoadingProgram.deliveryOrders.customer',
            'exportLoadingProgram.deliveryOrders.exportOrder.product',
            'exportLoadingProgram.deliveryOrders.exportPackingItems.bagType',
            'exportLoadingProgram.deliveryOrders.arrivalLocation',
            'exportLoadingProgram.deliveryOrders.subArrivalLocation',
            'exportLoadingProgram.deliveryOrders.locations',
            'exportLoadingProgram.exportOrder.product',
            'exportLoadingProgram.exportOrders.product',
            'deliveryOrders.customer',
            'deliveryOrders.exportOrder.product',
            'deliveryOrders.exportPackingItems.bagType',
            'deliveryOrders.arrivalLocation',
            'deliveryOrders.subArrivalLocation',
            'deliveryOrders.locations',
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

    public function resolveDeliveryOrders(LoadingProgramItem $item)
    {
        $deliveryOrderIds = collect();

        $linkedDOs = $item->exportLoadingProgram?->deliveryOrders?->where('am_approval_status', 'approved') ?? collect();
        if ($linkedDOs->isNotEmpty()) {
            $deliveryOrderIds = $deliveryOrderIds->merge($linkedDOs->pluck('id'));
        }

        if ($item->exportLoadingProgram?->deliveryOrder && $item->exportLoadingProgram->deliveryOrder->am_approval_status === 'approved') {
            $deliveryOrderIds->push($item->exportLoadingProgram->deliveryOrder->id);
        }

        $ticketDOs = $item->deliveryOrders->where('type', 'export_order')->where('am_approval_status', 'approved');
        if ($ticketDOs->isNotEmpty()) {
            $deliveryOrderIds = $deliveryOrderIds->merge($ticketDOs->pluck('id'));
        }

        $deliveryOrderIds = $deliveryOrderIds->filter()->unique()->values();

        if ($deliveryOrderIds->isEmpty()) {
            return collect();
        }

        return ExportDeliveryOrder::with(['exportPackingItems', 'loadingSlips'])
            ->whereIn('id', $deliveryOrderIds)
            ->get();
    }

    private function resolveDeliveryOrder(LoadingProgramItem $item): ?ExportDeliveryOrder
    {
        $do = $this->resolveDeliveryOrders($item)->first();
        return $do ? ExportDeliveryOrder::find($do->id) : null;
    }

    private function buildOrders(?LoadingProgramItem $item): array
    {
        if (!$item) {
            return [];
        }

        $orders = [];
        $deliveryOrders = $item->exportLoadingProgram?->deliveryOrders?->where('am_approval_status', 'approved')->values() ?? collect();

        if (
            $deliveryOrders->isEmpty()
            && $item->exportLoadingProgram?->deliveryOrder
            && $item->exportLoadingProgram->deliveryOrder->am_approval_status === 'approved'
        ) {
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
                ])->where('am_approval_status', 'approved')
                    ->whereIn('id', $exportDeliveryOrderIds)
                    ->get();
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
                'factory_names' => $this->getDeliveryOrderLocationNames($do, 'arrival_location_ids', ArrivalLocation::class),
                'gala_names' => $this->getDeliveryOrderLocationNames($do, 'sub_arrival_location_ids', ArrivalSubLocation::class),
                'bag_size' => $do->exportPackingItems->first()->bag_size ?? 0,
            ];
        }

        if (empty($orders)) {
            $exportOrders = $item->exportOrders
                ->where('am_approval_status', 'approved')
                ->values();

            if ($exportOrders->isEmpty() && $item->exportLoadingProgram?->exportOrders?->isNotEmpty()) {
                $exportOrders = $item->exportLoadingProgram->exportOrders
                    ->where('am_approval_status', 'approved')
                    ->values();
            }

            if (
                $exportOrders->isEmpty()
                && $item->exportLoadingProgram?->exportOrder
                && $item->exportLoadingProgram->exportOrder->am_approval_status === 'approved'
            ) {
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

    private function getDeliveryOrderLocationNames($deliveryOrder, string $column, string $modelClass): array
    {
        $ids = collect($deliveryOrder->locations ?? [])
            ->flatMap(function ($location) use ($column) {
                return explode(',', (string) ($location->{$column} ?? ''));
            })
            ->map(fn($id) => trim($id))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (!empty($ids)) {
            return $modelClass::whereIn('id', $ids)->pluck('name')->toArray();
        }

        return $this->getLocationNames(
            $column === 'arrival_location_ids' ? $deliveryOrder->arrival_location_id : $deliveryOrder->sub_arrival_location_id,
            $modelClass
        );
    }

    private function summarizeOrders(array $orders): array
    {
        $totalSoQty = 0;
        $totalDoQty = 0;
        $allFactories = [];
        $allGalas = [];

        foreach ($orders as $order) {
            $totalSoQty += (float) ($order['so_qty'] ?? 0);
            $totalDoQty += (float) ($order['do_qty'] ?? 0);
            $allFactories = array_merge($allFactories, $order['factory_names'] ?? []);
            $allGalas = array_merge($allGalas, $order['gala_names'] ?? []);
        }

        return [
            'customer' => $orders[0]['customer'] ?? '',
            'commodity' => $orders[0]['commodity'] ?? '',
            'so_qty' => $totalSoQty,
            'do_qty' => $totalDoQty,
            'factory_names' => array_values(array_unique($allFactories)),
            'gala_names' => array_values(array_unique($allGalas)),
            'bag_size' => $orders[0]['bag_size'] ?? 0,
        ];
    }

    private function getBagSummary(Collection $deliveryOrders, ?ExportLoadingSlip $ignoreSlip = null): array
    {
        if ($deliveryOrders->isEmpty()) {
            return [
                'total_bags' => 0,
                'used_bags' => 0,
                'remaining_bags' => 0,
            ];
        }

        $deliveryOrderIds = $deliveryOrders->pluck('id')->filter()->unique()->values();
        $totalMetricTons = (float) $deliveryOrders->sum(function ($deliveryOrder) {
            return $deliveryOrder->exportPackingItems->sum('metric_tons');
        });

        $referenceBagSize = (float) optional(
            $deliveryOrders->flatMap(function ($deliveryOrder) {
                return $deliveryOrder->exportPackingItems;
            })->first(function ($packingItem) {
                return (float) ($packingItem->bag_size ?? 0) > 0;
            })
        )->bag_size;

        $totalBags = $referenceBagSize > 0
            ? (int) floor(($totalMetricTons * 1000) / $referenceBagSize)
            : (int) floor($deliveryOrders->sum(function ($deliveryOrder) {
                return $deliveryOrder->exportPackingItems->sum('no_of_bags');
            }));

        $usedBagsQuery = ExportLoadingSlip::whereIn('delivery_order_id', $deliveryOrderIds);
        if ($ignoreSlip) {
            $usedBagsQuery->where('id', '!=', $ignoreSlip->id);
        }

        $usedBags = (int) $usedBagsQuery->sum('no_of_bags');

        return [
            'total_bags' => $totalBags,
            'used_bags' => $usedBags,
            'remaining_bags' => max($totalBags - $usedBags, 0),
        ];
    }

    private function parseStoredMultiValue($value): array
    {
        if (blank($value)) {
            return [];
        }

        if (is_array($value)) {
            return collect($value)->map(fn($item) => trim((string) $item))->filter()->values()->all();
        }

        $decoded = json_decode((string) $value, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return collect($decoded)->map(fn($item) => trim((string) $item))->filter()->values()->all();
        }

        return collect(explode(',', (string) $value))
            ->map(fn($item) => trim($item))
            ->filter()
            ->values()
            ->all();
    }

    private function encodeStoredMultiValue($value): string
    {
        return json_encode(
            collect(is_array($value) ? $value : [$value])
                ->map(fn($item) => trim((string) $item))
                ->filter()
                ->unique()
                ->values()
                ->all()
        );
    }
}
