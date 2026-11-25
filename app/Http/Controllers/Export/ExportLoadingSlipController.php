<?php

namespace App\Http\Controllers\Export;

use App\Http\Controllers\Controller;
use App\Models\Export\ExportDeliveryOrder;
use App\Models\Export\ExportLoadingSlip;
use App\Models\Export\ExportLoadingSlipItem;
use App\Models\Master\ArrivalLocation;
use App\Services\Export\ExportPackagingService;
use App\Models\Master\ArrivalSubLocation;
use App\Models\Sales\LoadingSlipLog;
use App\Models\Sales\LoadingProgramItem;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

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
            ->whereHas('exportLoadingProgram.exportOrders', function ($q) {
                $q->where(function ($q) {
                    $q->where('cancel', 0)->orWhereNull('cancel');
                });
            })
            ->whereHas('exportLoadingProgram.deliveryOrder', function ($q) {
                $q->where(function ($q) {
                    $q->where('cancel', 0)->orWhereNull('cancel');
                });
            })
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
            // Per packaging line items; the slip's bags / empty / extra / kilogram are their totals
            'items' => 'required|array|min:1',
            'items.*.delivery_order_packing_item_id' => 'required|integer',
            'items.*.no_of_bags' => 'nullable|integer|min:0',
            'items.*.empty_bags' => 'nullable|integer|min:0',
            'items.*.extra_bags' => 'nullable|integer|min:0',
            'remarks' => 'nullable|string',
            'company_id' => 'required|numeric',
            // Unique among export loading slips only (the table is shared with sales loading slips)
            'seal_no' => [
                'required', 'string', 'max:255',
                Rule::unique('loading_slips', 'seal_no')->where('type', 'export_loading_slip'),
            ],
            'stacks' => 'required|array|min:1',
            'stacks.*.bag_type' => 'required|string',
            'stacks.*.packing_size' => 'required|string',
            'stacks.*.input_size' => 'required|string',
        ], [
            'seal_no.unique' => 'This Seal No is already used in another loading slip.',
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

            $prepared = $this->prepareSlipItems($request, $deliveryOrders);
            if ($prepared['errors']) {
                DB::rollBack();
                return response()->json(['errors' => $prepared['errors']], 422);
            }
            $totals = $prepared['totals'];

            // Whole-ticket checks as well (they also count older slips saved without line items)
            if ($deliveryOrders->isNotEmpty()) {
                $bagSummary = $this->getBagSummary($deliveryOrders);
                $remainingBags = $bagSummary['remaining_bags'];

                if ($totals['no_of_bags'] > $remainingBags) {
                    DB::rollBack();
                    return response()->json(['errors' => ['no_of_bags' => ["Total bags ({$totals['no_of_bags']}) are more than the balance ($remainingBags)."]]], 422);
                }
            }

            if ($errors = $this->extraEmptyBagErrors($totals['empty_bags'], $totals['extra_bags'], $deliveryOrders)) {
                DB::rollBack();
                return response()->json(['errors' => $errors], 422);
            }

            $loadingSlip = ExportLoadingSlip::create([
                'loading_program_item_id' => $request->loading_program_item_id,
                'customer' => $request->customer,
                'commodity' => $request->commodity,
                'so_qty' => $request->so_qty,
                'do_qty' => $request->do_qty,
                'factory' => is_array($request->factory) ? implode(', ', $request->factory) : $request->factory,
                'gala' => $this->encodeStoredMultiValue($request->gala),
                'no_of_bags' => $totals['no_of_bags'],
                'empty_bags' => $totals['empty_bags'],
                'extra_bags' => $totals['extra_bags'],
                'bag_size' => $totals['bag_size'],
                'kilogram' => $totals['kilogram'],
                'delivery_order_id' => $prepared['items'][0]['delivery_order_id'] ?? $deliveryOrders->first()?->id,
                'remarks' => $request->remarks,
                'created_by' => auth()->user()->id,
                'company_id' => $request->company_id,
                'seal_no' => $request->seal_no,
            ]);

            foreach ($prepared['items'] as $item) {
                $loadingSlip->items()->create($item);
            }

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
            'items.bagType',
            'items.brand',
            'items.deliveryOrder',
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

        $extraEmptyBags = $this->getExtraEmptyBagSummary($deliveryOrders, $loadingSlip);

        // Packagings with this slip's own line items not counted as used
        $packagings = $this->packagingRows($deliveryOrders, $loadingSlip);
        $savedItems = $loadingSlip->items()->get()->keyBy('delivery_order_packing_item_id');

        // Older slip saved without line items: with a single packaging its totals belong to that packaging
        if ($savedItems->isEmpty() && count($packagings) === 1) {
            $savedItems = collect([
                $packagings[0]['delivery_order_packing_item_id'] => new ExportLoadingSlipItem([
                    'no_of_bags' => (int) $loadingSlip->no_of_bags,
                    'empty_bags' => (int) $loadingSlip->empty_bags,
                    'extra_bags' => (int) $loadingSlip->extra_bags,
                ]),
            ]);
        }

        return view('management.export.loading-slip.edit', compact('loadingSlip', 'Orders', 'canEdit', 'rejectedDispatchQc', 'bagTypes', 'packingSizes', 'selectedGalas', 'extraEmptyBags', 'packagings', 'savedItems'));
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
                // Per packaging line items; the slip's bags / empty / extra / kilogram are their totals
                'items' => 'required|array|min:1',
                'items.*.delivery_order_packing_item_id' => 'required|integer',
                'items.*.no_of_bags' => 'nullable|integer|min:0',
                'items.*.empty_bags' => 'nullable|integer|min:0',
                'items.*.extra_bags' => 'nullable|integer|min:0',
                'remarks' => 'nullable|string',
                // Unique among export loading slips, ignoring this slip itself
                'seal_no' => [
                    'required', 'string', 'max:255',
                    Rule::unique('loading_slips', 'seal_no')->where('type', 'export_loading_slip')->ignore($id),
                ],
                'stacks' => 'required|array|min:1',
                'stacks.*.bag_type' => 'required|string',
                'stacks.*.packing_size' => 'required|string',
                'stacks.*.input_size' => 'required|string',
            ], [
                'seal_no.unique' => 'This Seal No is already used in another loading slip.',
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

            if (!$loadingSlip->canBeEdited()) {
                DB::rollBack();
                return response()->json(['error' => 'This loading slip cannot be edited because its Dispatch QC has been accepted.'], 422);
            }

            $LoadingProgramItem = $this->ticketQuery()
                ->whereHas('exportFirstWeighbridge')
                ->with($this->ticketRelations())
                ->findOrFail($loadingSlip->loading_program_item_id);
            $deliveryOrders = $this->resolveDeliveryOrders($LoadingProgramItem);

            $prepared = $this->prepareSlipItems($request, $deliveryOrders, $loadingSlip);
            if ($prepared['errors']) {
                DB::rollBack();
                return response()->json(['errors' => $prepared['errors']], 422);
            }
            $totals = $prepared['totals'];

            // Whole-ticket checks as well (they also count older slips saved without line items)
            if ($deliveryOrders->isNotEmpty()) {
                $bagSummary = $this->getBagSummary($deliveryOrders, $loadingSlip);
                $availableBags = $bagSummary['remaining_bags'];

                if ($totals['no_of_bags'] > $availableBags) {
                    DB::rollBack();
                    return response()->json(['errors' => ['no_of_bags' => ["Total bags ({$totals['no_of_bags']}) are more than the balance ($availableBags)."]]], 422);
                }
            }

            if ($errors = $this->extraEmptyBagErrors($totals['empty_bags'], $totals['extra_bags'], $deliveryOrders, $loadingSlip)) {
                DB::rollBack();
                return response()->json(['errors' => $errors], 422);
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
                'no_of_bags' => $totals['no_of_bags'],
                'empty_bags' => $totals['empty_bags'],
                'extra_bags' => $totals['extra_bags'],
                'bag_size' => $totals['bag_size'],
                'kilogram' => $totals['kilogram'],
                'remarks' => $request->remarks,
                'seal_no' => $request->seal_no,
            ]);

            $loadingSlip->items()->delete();
            foreach ($prepared['items'] as $item) {
                $loadingSlip->items()->create($item);
            }

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
        $extraEmptyBags = $this->getExtraEmptyBagSummary($deliveryOrders);

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
                'available_empty_bags' => $extraEmptyBags['available_empty_bags'],
                'available_extra_bags' => $extraEmptyBags['available_extra_bags'],
                // One line per packaging: what goes on this truck
                'packagings' => $this->packagingRows($deliveryOrders),
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

    /** Bags of one DO packing row: its MT in bags of its own size (no. of bags when the size is missing). */
    private function packingItemBags($packingItem): int
    {
        $bagSize = (float) ($packingItem->bag_size ?? 0);

        return $bagSize > 0
            ? (int) floor((((float) $packingItem->metric_tons) * 1000 / $bagSize) + 1e-6)
            : (int) ($packingItem->no_of_bags ?? 0);
    }

    /**
     * Packagings (DO packing rows) of the ticket's Delivery Orders with what is left for a loading slip:
     * bags = DO row bags minus other slips' line items of that row,
     * empty / extra bags = the matching Export Order packing row minus other slips' line items of it.
     */
    private function packagingRows(Collection $deliveryOrders, ?ExportLoadingSlip $ignoreSlip = null): array
    {
        if ($deliveryOrders->isEmpty()) {
            return [];
        }

        $deliveryOrders->loadMissing(['exportPackingItems.bagType', 'exportPackingItems.brand']);

        $exportOrders = \App\Models\Export\ExportOrder::with('packingItems')
            ->whereIn('id', $deliveryOrders->pluck('export_order_id')->filter()->unique())
            ->get()
            ->keyBy('id');

        $packingItemIds = $deliveryOrders->flatMap(fn($do) => $do->exportPackingItems->pluck('id'))->unique()->values();

        $usedBagsByItem = ExportLoadingSlipItem::whereIn('delivery_order_packing_item_id', $packingItemIds)
            ->whereHas('loadingSlip')
            ->when($ignoreSlip, fn($q) => $q->where('loading_slip_id', '!=', $ignoreSlip->id))
            ->selectRaw('delivery_order_packing_item_id, SUM(no_of_bags) as bags')
            ->groupBy('delivery_order_packing_item_id')
            ->pluck('bags', 'delivery_order_packing_item_id');

        // Empty / extra bags already loaded, per Export Order packing row (any DO of those Export Orders)
        $usedEmptyExtraByEoItem = [];
        ExportLoadingSlipItem::with(['deliveryOrderPackingItem', 'deliveryOrder'])
            ->whereHas('loadingSlip')
            ->whereHas('deliveryOrder', fn($q) => $q->whereIn('export_order_id', $exportOrders->keys()))
            ->when($ignoreSlip, fn($q) => $q->where('loading_slip_id', '!=', $ignoreSlip->id))
            ->where(fn($q) => $q->where('empty_bags', '>', 0)->orWhere('extra_bags', '>', 0))
            ->get()
            ->each(function ($slipItem) use ($exportOrders, &$usedEmptyExtraByEoItem) {
                $eoItem = $this->exportOrderItemForSlipItem($exportOrders->get($slipItem->deliveryOrder?->export_order_id), $slipItem);
                if (!$eoItem) {
                    return;
                }
                $usedEmptyExtraByEoItem[$eoItem->id]['empty'] = ($usedEmptyExtraByEoItem[$eoItem->id]['empty'] ?? 0) + (int) $slipItem->empty_bags;
                $usedEmptyExtraByEoItem[$eoItem->id]['extra'] = ($usedEmptyExtraByEoItem[$eoItem->id]['extra'] ?? 0) + (int) $slipItem->extra_bags;
            });

        $rows = [];
        foreach ($deliveryOrders as $do) {
            $exportOrder = $exportOrders->get($do->export_order_id);

            foreach ($do->exportPackingItems->sortBy('id') as $packingItem) {
                $eoItem = $exportOrder
                    ? ExportPackagingService::matchExportOrderItem(
                        $exportOrder->packingItems,
                        $packingItem->bag_type_id,
                        $packingItem->brand_id,
                        $packingItem->bag_size,
                        $packingItem->bag_condition_id,
                        $packingItem->bag_color_id
                    )
                    : null;

                $totalBags = $this->packingItemBags($packingItem);
                $usedBags = (int) ($usedBagsByItem[$packingItem->id] ?? 0);
                $eoEmpty = (int) ($eoItem->empty_bags ?? 0);
                $eoExtra = (int) ($eoItem->extra_bags ?? 0);
                $usedEmpty = (int) ($usedEmptyExtraByEoItem[$eoItem->id ?? 0]['empty'] ?? 0);
                $usedExtra = (int) ($usedEmptyExtraByEoItem[$eoItem->id ?? 0]['extra'] ?? 0);
                $bagTypeName = $packingItem->bagType->name ?? (bag_type_name($packingItem->bag_type_id) ?: 'N/A');
                $bagSize = round((float) $packingItem->bag_size, 2);

                $rows[] = [
                    'delivery_order_packing_item_id' => $packingItem->id,
                    'delivery_order_id' => $do->id,
                    'do_reference' => $do->reference_no,
                    'bag_type_id' => $packingItem->bag_type_id,
                    'bag_type_name' => $bagTypeName,
                    'brand_id' => $packingItem->brand_id,
                    'brand_name' => $packingItem->brand->name ?? '',
                    'bag_size' => $bagSize,
                    'label' => trim($bagTypeName . ' ' . rtrim(rtrim(number_format($bagSize, 2), '0'), '.') . 'kg' . ($packingItem->brand?->name ? ' (' . $packingItem->brand->name . ')' : '')),
                    'total_bags' => $totalBags,
                    'used_bags' => $usedBags,
                    'remaining_bags' => max(0, $totalBags - $usedBags),
                    // Several DO rows of the same Export Order packaging share its empty / extra bags
                    'empty_extra_pool' => $eoItem ? 'eo' . $eoItem->id : 'do' . $packingItem->id,
                    'eo_empty_bags' => $eoEmpty,
                    'eo_extra_bags' => $eoExtra,
                    'available_empty_bags' => max(0, $eoEmpty - $usedEmpty),
                    'available_extra_bags' => max(0, $eoExtra - $usedExtra),
                ];
            }
        }

        return $rows;
    }

    private function exportOrderItemForSlipItem($exportOrder, ExportLoadingSlipItem $slipItem)
    {
        if (!$exportOrder) {
            return null;
        }

        $doRow = $slipItem->deliveryOrderPackingItem;

        return ExportPackagingService::matchExportOrderItem(
            $exportOrder->packingItems,
            $doRow->bag_type_id ?? $slipItem->bag_type_id,
            $doRow->brand_id ?? $slipItem->brand_id,
            $doRow->bag_size ?? $slipItem->bag_size,
            $doRow->bag_condition_id ?? null,
            $doRow->bag_color_id ?? null
        );
    }

    /**
     * Line items from the form, checked against each packaging's balance.
     * Returns errors, the rows to save and the totals that go on the loading slip.
     */
    private function prepareSlipItems(Request $request, Collection $deliveryOrders, ?ExportLoadingSlip $ignoreSlip = null): array
    {
        $packagings = collect($this->packagingRows($deliveryOrders, $ignoreSlip))->keyBy('delivery_order_packing_item_id');
        $errors = [];
        $items = [];
        $pools = [];

        foreach ((array) $request->input('items', []) as $row) {
            $packingItemId = (int) ($row['delivery_order_packing_item_id'] ?? 0);
            $bags = (int) ($row['no_of_bags'] ?? 0);
            $empty = (int) ($row['empty_bags'] ?? 0);
            $extra = (int) ($row['extra_bags'] ?? 0);

            if ($bags + $empty + $extra === 0) {
                continue;
            }

            $packaging = $packagings->get($packingItemId);
            if (!$packaging) {
                $errors['items'][] = 'A packaging on this slip does not belong to the ticket\'s Delivery Orders.';
                continue;
            }

            if ($bags > $packaging['remaining_bags']) {
                $errors['items'][] = "{$packaging['label']}: No. of Bags ({$bags}) is more than its balance ({$packaging['remaining_bags']}).";
            }

            $pool = $packaging['empty_extra_pool'];
            $pools[$pool]['label'] = $pools[$pool]['label'] ?? $packaging['label'];
            $pools[$pool]['empty'] = ($pools[$pool]['empty'] ?? 0) + $empty;
            $pools[$pool]['extra'] = ($pools[$pool]['extra'] ?? 0) + $extra;
            $pools[$pool]['available_empty'] = $packaging['available_empty_bags'];
            $pools[$pool]['available_extra'] = $packaging['available_extra_bags'];

            $items[] = [
                'delivery_order_id' => $packaging['delivery_order_id'],
                'delivery_order_packing_item_id' => $packingItemId,
                'bag_type_id' => $packaging['bag_type_id'],
                'brand_id' => $packaging['brand_id'],
                'bag_size' => $packaging['bag_size'],
                'no_of_bags' => $bags,
                'empty_bags' => $empty,
                'extra_bags' => $extra,
                'kilogram' => round($bags * (float) $packaging['bag_size'], 2),
            ];
        }

        foreach ($pools as $pool) {
            if ($pool['empty'] > $pool['available_empty']) {
                $errors['items'][] = "{$pool['label']}: Empty Bags ({$pool['empty']}) are more than available ({$pool['available_empty']}).";
            }
            if ($pool['extra'] > $pool['available_extra']) {
                $errors['items'][] = "{$pool['label']}: Extra Bags ({$pool['extra']}) are more than available ({$pool['available_extra']}).";
            }
        }

        $totalBags = (int) collect($items)->sum('no_of_bags');
        if ($totalBags < 1) {
            $errors['items'][] = 'Enter No. of Bags for at least one packaging.';
        }

        $totalKg = round((float) collect($items)->sum('kilogram'), 2);
        $sizes = collect($items)->where('no_of_bags', '>', 0)->pluck('bag_size')->map(fn($s) => (float) $s)->unique();

        return [
            'errors' => $errors,
            'items' => $items,
            'totals' => [
                'no_of_bags' => $totalBags,
                'empty_bags' => (int) collect($items)->sum('empty_bags'),
                'extra_bags' => (int) collect($items)->sum('extra_bags'),
                'kilogram' => $totalKg,
                // One packaging size: that size, mixed sizes: average kg per bag
                'bag_size' => $sizes->count() === 1 ? $sizes->first() : ($totalBags > 0 ? round($totalKg / $totalBags, 2) : 0),
            ],
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

        // Each DO row in bags of its own size (packagings can have different sizes)
        $totalBags = (int) $deliveryOrders->sum(function ($deliveryOrder) {
            return $deliveryOrder->exportPackingItems->sum(fn($packingItem) => $this->packingItemBags($packingItem));
        });

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

    /**
     * Empty / extra bags entered on the slip must not exceed what is still available.
     */
    private function extraEmptyBagErrors(int $emptyBags, int $extraBags, Collection $deliveryOrders, ?ExportLoadingSlip $ignoreSlip = null): array
    {
        $available = $this->getExtraEmptyBagSummary($deliveryOrders, $ignoreSlip);
        $errors = [];

        if ($emptyBags > $available['available_empty_bags']) {
            $errors['empty_bags'] = ["Total empty bags ({$emptyBags}) are more than available ({$available['available_empty_bags']})."];
        }
        if ($extraBags > $available['available_extra_bags']) {
            $errors['extra_bags'] = ["Total extra bags ({$extraBags}) are more than available ({$available['available_extra_bags']})."];
        }

        return $errors;
    }

    /**
     * Empty / extra bags come from the export order's Packing Details.
     * Available = export order total minus what other loading slips of that order already used.
     */
    private function getExtraEmptyBagSummary(Collection $deliveryOrders, ?ExportLoadingSlip $ignoreSlip = null): array
    {
        $exportOrderIds = $deliveryOrders->pluck('export_order_id')->filter()->unique()->values();

        if ($exportOrderIds->isEmpty()) {
            return ['available_empty_bags' => 0, 'available_extra_bags' => 0];
        }

        $exportOrders = \App\Models\Export\ExportOrder::with('packingItems')->whereIn('id', $exportOrderIds)->get();
        $totalEmpty = (int) $exportOrders->sum(fn ($order) => $order->packingItems->sum('empty_bags'));
        $totalExtra = (int) $exportOrders->sum(fn ($order) => $order->packingItems->sum('extra_bags'));

        $usedQuery = ExportLoadingSlip::whereHas('deliveryOrder', function ($q) use ($exportOrders) {
            $q->whereIn('export_order_id', $exportOrders->pluck('id'));
        });
        if ($ignoreSlip) {
            $usedQuery->where('id', '!=', $ignoreSlip->id);
        }

        $usedEmpty = (int) (clone $usedQuery)->sum('empty_bags');
        $usedExtra = (int) (clone $usedQuery)->sum('extra_bags');

        return [
            'available_empty_bags' => max($totalEmpty - $usedEmpty, 0),
            'available_extra_bags' => max($totalExtra - $usedExtra, 0),
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
