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

        $hasFumigation = $this->ticketHasFumigation($ticket);

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
                'has_fumigation' => $this->ticketHasFumigation($ticket),
            ]
        ]);
    }

    /**
     * Dosage is kept only when the ticket's export order has fumigation.
     */
    private function saveDosage(LoadingProgramItem $ticket, Request $request): void
    {
        $ticket->update([
            'dosage' => $this->ticketHasFumigation($ticket) && $request->filled('dosage') ? (int) $request->dosage : null,
        ]);
    }

    /**
     * True when any export order on the ticket has a fumigation type or fumigation company.
     */
    private function ticketHasFumigation(LoadingProgramItem $ticket): bool
    {
        $ticket->loadMissing(['exportOrders', 'exportLoadingProgram.exportOrders', 'exportLoadingProgram.exportOrder']);

        $exportOrders = $ticket->exportOrders;
        if ($exportOrders->isEmpty()) {
            $exportOrders = $ticket->exportLoadingProgram?->exportOrders ?? collect();
        }
        if ($exportOrders->isEmpty() && $ticket->exportLoadingProgram?->exportOrder) {
            $exportOrders = collect([$ticket->exportLoadingProgram->exportOrder]);
        }

        return $exportOrders->contains(function ($order) {
            return filled($order->fumigation_type) || !empty(array_filter((array) $order->fumigation_by));
        });
    }
}
