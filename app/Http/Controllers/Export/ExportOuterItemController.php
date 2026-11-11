<?php

namespace App\Http\Controllers\Export;

use App\Http\Controllers\Controller;
use App\Models\Export\ExportOuterItem;
use App\Models\Sales\LoadingProgramItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ExportOuterItemController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        return view('management.export.outer-item.index');
    }

    public function getList(Request $request)
    {
        $tickets = LoadingProgramItem::whereHas('outerItems')
            ->with(['brand', 'outerItems', 'exportLoadingSlip'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $searchTerm = '%' . $request->search . '%';
                $q->where('transaction_number', 'like', $searchTerm)
                    ->orWhere('truck_number', 'like', $searchTerm);
            })
            ->latest()
            ->paginate($request->get('per_page', 25));

        return view('management.export.outer-item.getList', compact('tickets'));
    }

    public function create()
    {
        // Available tickets are those which have a loading slip but NO outer items yet
        $availableTickets = LoadingProgramItem::whereHas('exportLoadingProgram.exportOrders', function ($q) {
                $q->where(function ($q) {
                    $q->where('cancel', 0)->orWhereNull('cancel');
                });
            })
            ->whereHas('exportLoadingProgram.deliveryOrder', function ($q) {
                $q->where(function ($q) {
                    $q->where('cancel', 0)->orWhereNull('cancel');
                });
            })
            ->whereHas('exportLoadingSlip')
            ->whereDoesntHave('outerItems')
            ->with(['exportLoadingSlip', 'brand'])
            ->get();

        $itemOptions = [
            'Craft paper',
            'Dry bag',
            'Empty bag weight',
            'Extra bag weight',
            'Slica jel'
        ];

        return view('management.export.outer-item.create', compact('availableTickets', 'itemOptions'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'loading_program_item_id' => 'required|exists:loading_program_items,id',
            'dosage' => 'nullable|integer',
            'items' => 'required|array|min:1',
            'items.*.item_name' => 'required|string',
            'items.*.weight' => 'required|numeric|min:0',
            'items.*.qty' => 'required|numeric|min:0',
            'items.*.total_weight' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if ($dosageError = $this->dosageLimitError(LoadingProgramItem::findOrFail($request->loading_program_item_id), $request)) {
            return response()->json(['errors' => ['dosage' => [$dosageError]]], 422);
        }

        try {
            DB::beginTransaction();

            foreach ($request->items as $item) {
                ExportOuterItem::create([
                    'loading_program_item_id' => $request->loading_program_item_id,
                    'item_name' => $item['item_name'],
                    'weight' => $item['weight'],
                    'qty' => $item['qty'],
                    'total_weight' => $item['total_weight'],
                    'created_by' => auth()->user()->id,
                ]);
            }

            $this->saveDosage(LoadingProgramItem::findOrFail($request->loading_program_item_id), $request);

            DB::commit();
            return response()->json(['success' => 'Outer Items saved successfully.'], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to save Outer Items.', 'details' => $e->getMessage()], 422);
        }
    }

    public function edit($id)
    {
        $ticket = LoadingProgramItem::with(['outerItems', 'exportLoadingSlip'])->findOrFail($id);
        
        $itemOptions = [
            'Craft paper',
            'Dry bag',
            'Empty bag weight',
            'Extra bag weight',
            'Slica jel'
        ];

        // Dosage input only when fumigation exists and other trucks haven't used up the export order's dosage
        $hasFumigation = $this->ticketHasFumigation($ticket) && !$this->dosageFull($ticket);

        return view('management.export.outer-item.edit', compact('ticket', 'itemOptions', 'hasFumigation'));
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'dosage' => 'nullable|integer',
            'items' => 'required|array|min:1',
            'items.*.item_name' => 'required|string',
            'items.*.weight' => 'required|numeric|min:0',
            'items.*.qty' => 'required|numeric|min:0',
            'items.*.total_weight' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if ($dosageError = $this->dosageLimitError(LoadingProgramItem::findOrFail($id), $request)) {
            return response()->json(['errors' => ['dosage' => [$dosageError]]], 422);
        }

        try {
            DB::beginTransaction();

            $ticket = LoadingProgramItem::findOrFail($id);
            $ticket->outerItems()->delete();

            foreach ($request->items as $item) {
                ExportOuterItem::create([
                    'loading_program_item_id' => $id,
                    'item_name' => $item['item_name'],
                    'weight' => $item['weight'],
                    'qty' => $item['qty'],
                    'total_weight' => $item['total_weight'],
                    'created_by' => auth()->user()->id,
                ]);
            }

            $this->saveDosage($ticket, $request);

            DB::commit();
            return response()->json(['success' => 'Outer Items updated successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to update Outer Items.', 'details' => $e->getMessage()], 422);
        }
    }

    public function destroy($id)
    {
        try {
            $ticket = LoadingProgramItem::findOrFail($id);
            $ticket->outerItems()->delete();
            return response()->json(['success' => 'Outer Items deleted successfully.'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to delete Outer Items.'], 422);
        }
    }

    public function getTicketData($id)
    {
        $ticket = LoadingProgramItem::with(['exportLoadingSlip', 'brand'])->findOrFail($id);
        return response()->json([
            'success' => true,
            'data' => [
                'truck_number' => $ticket->truck_number,
                'container_number' => $ticket->container_number,
                'has_fumigation' => $this->ticketHasFumigation($ticket) && !$this->dosageFull($ticket),
            ]
        ]);
    }

    /**
     * Dosage is kept only when the ticket's export order has fumigation.
     */
    private function saveDosage(LoadingProgramItem $ticket, Request $request): void
    {
        $ticket->update([
            'dosage' => $this->ticketHasFumigation($ticket) && !$this->dosageFull($ticket) && $request->filled('dosage') ? (int) $request->dosage : null,
        ]);
    }

    /**
     * True when any export order on the ticket has a fumigation type or fumigation company.
     */
    private function ticketHasFumigation(LoadingProgramItem $ticket): bool
    {
        return $this->ticketExportOrders($ticket)->contains(function ($order) {
            return filled($order->fumigation_type) || !empty(array_filter((array) $order->fumigation_by));
        });
    }

    /**
     * True when other trucks of the same export order(s) have already used up the
     * export order's fumigation dosage (e.g. EO dosage 10, first truck got 10).
     */
    private function dosageFull(LoadingProgramItem $ticket): bool
    {
        $remaining = $this->dosageRemaining($ticket);

        return $remaining !== null && $remaining <= 0;
    }

    /**
     * Dosage still available for this ticket: EO dosage minus what other trucks of the
     * same export order already used. Null when the EO has no numeric dosage (no limit).
     */
    private function dosageRemaining(LoadingProgramItem $ticket): ?float
    {
        $orders = $this->ticketExportOrders($ticket)->filter(function ($order) {
            return is_numeric($order->fumigation_dosage) && (float) $order->fumigation_dosage > 0;
        });

        if ($orders->isEmpty()) {
            return null;
        }

        return $orders->map(function ($order) use ($ticket) {
            $used = LoadingProgramItem::where('id', '!=', $ticket->id)
                ->whereNotNull('dosage')
                ->where(function ($q) use ($order) {
                    $q->whereHas('exportOrders', fn ($eo) => $eo->where('export_orders.id', $order->id))
                        ->orWhereHas('exportLoadingProgram', function ($lp) use ($order) {
                            $lp->where('export_order_id', $order->id)
                                ->orWhereHas('exportOrders', fn ($eo) => $eo->where('export_orders.id', $order->id));
                        });
                })
                ->sum('dosage');

            return (float) $order->fumigation_dosage - (float) $used;
        })->min();
    }

    /**
     * Error message when the entered dosage is more than what is left on the export order.
     */
    private function dosageLimitError(LoadingProgramItem $ticket, Request $request): ?string
    {
        if (!$request->filled('dosage') || !$this->ticketHasFumigation($ticket)) {
            return null;
        }

        $remaining = $this->dosageRemaining($ticket);
        if ($remaining === null || (float) $request->dosage <= $remaining) {
            return null;
        }

        return 'Dosage limit exceeded. Only ' . max(0, $remaining) . ' dosage is remaining for this export order.';
    }

    private function ticketExportOrders(LoadingProgramItem $ticket)
    {
        $ticket->loadMissing(['exportOrders', 'exportLoadingProgram.exportOrders', 'exportLoadingProgram.exportOrder']);

        $exportOrders = $ticket->exportOrders;
        if ($exportOrders->isEmpty()) {
            $exportOrders = $ticket->exportLoadingProgram?->exportOrders ?? collect();
        }
        if ($exportOrders->isEmpty() && $ticket->exportLoadingProgram?->exportOrder) {
            $exportOrders = collect([$ticket->exportLoadingProgram->exportOrder]);
        }

        return $exportOrders;
    }
}
