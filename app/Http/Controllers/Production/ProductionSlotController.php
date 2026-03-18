<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\Production\ProductionSlot;
use App\Models\Production\ProductionSlotBreak;
use App\Models\Production\ProductionVoucher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductionSlotController extends Controller
{
    public function index()
    {
        return view('management.production.production_slot.index');
    }

    public function getList(Request $request)
    {
        $query = ProductionSlot::with(['productionVoucher', 'breaks']);

        // Filter by production voucher
        if ($request->filled('production_voucher_id')) {
            $query->where('production_voucher_id', $request->production_voucher_id);
        }

        // Filter by date
        if ($request->filled('date')) {
            $query->whereDate('date', $request->date);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Apply sorting
        $sortField = $request->get('sort', 'date');
        $sortDirection = $request->get('direction', 'desc');

        if (in_array($sortField, ['date', 'start_time', 'status'])) {
            $query->orderBy($sortField, $sortDirection);
        } else {
            $query->orderBy('date', 'desc')->orderBy('start_time', 'desc');
        }

        // Paginate results
        $productionSlots = $query->paginate(request('per_page', 25));

        // Get production vouchers for filter dropdown
        $productionVouchers = ProductionVoucher::where('status', '!=', 'cancelled')
            ->orderBy('prod_date', 'desc')
            ->get();

        return view('management.production.production_slot.getList', compact(
            'productionSlots',
            'productionVouchers'
        ));
    }

    public function create()
    {
        $productionVouchers = ProductionVoucher::where('status', '!=', 'cancelled')
            ->orderBy('prod_date', 'desc')
            ->get();

        return view('management.production.production_slot.create', compact('productionVouchers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'production_voucher_id' => 'required|exists:production_vouchers,id',
            'date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after:start_time',
            'status' => 'nullable|in:active,completed,cancelled',
            'remarks' => 'nullable|string|max:1000',
            'breaks' => 'nullable|array',
            'breaks.*.break_in' => 'required_with:breaks|date_format:H:i',
            'breaks.*.break_out' => 'nullable|date_format:H:i',
            'breaks.*.reason' => 'nullable|string|max:500'
        ]);

        DB::beginTransaction();

        try {
            $slotData = $request->only([
                'production_voucher_id',
                'date',
                'start_time',
                'end_time',
                'status',
                'remarks'
            ]);

            $slotData['status'] = $slotData['status'] ?? 'active';

            $productionSlot = ProductionSlot::create($slotData);

            // Save breaks if provided
            if ($request->has('breaks') && is_array($request->breaks)) {
                foreach ($request->breaks as $breakData) {
                    if (!empty($breakData['break_in'])) {
                        ProductionSlotBreak::create([
                            'production_slot_id' => $productionSlot->id,
                            'break_in' => $breakData['break_in'],
                            'break_out' => $breakData['break_out'] ?? null,
                            'reason' => $breakData['reason'] ?? null
                        ]);
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => 'Production Slot created successfully.',
                'data' => $productionSlot->load('breaks')
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Something went wrong: ' . $e->getMessage()
            ], 500);
        }
    }

    public function edit($id)
    {
        $productionSlot = ProductionSlot::with(['productionVoucher', 'breaks'])
            ->findOrFail($id);

        $productionVouchers = ProductionVoucher::where('status', '!=', 'cancelled')
            ->orderBy('prod_date', 'desc')
            ->get();

        return view('management.production.production_slot.edit', compact(
            'productionSlot',
            'productionVouchers'
        ));
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'production_voucher_id' => 'required|exists:production_vouchers,id',
            'date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after:start_time',
            'status' => 'nullable|in:active,completed,cancelled',
            'remarks' => 'nullable|string|max:1000',
            'breaks' => 'nullable|array',
            'breaks.*.id' => 'nullable|exists:production_slot_breaks,id',
            'breaks.*.break_in' => 'required_with:breaks|date_format:H:i',
            'breaks.*.break_out' => 'nullable|date_format:H:i',
            'breaks.*.reason' => 'nullable|string|max:500'
        ]);

        $productionSlot = ProductionSlot::findOrFail($id);

        DB::beginTransaction();

        try {
            $slotData = $request->only([
                'production_voucher_id',
                'date',
                'start_time',
                'end_time',
                'status',
                'remarks'
            ]);

            $productionSlot->update($slotData);

            // Handle breaks - update existing, create new, delete removed
            if ($request->has('breaks')) {
                $existingBreakIds = [];
                
                foreach ($request->breaks as $breakData) {
                    if (!empty($breakData['break_in'])) {
                        if (isset($breakData['id']) && $breakData['id']) {
                            // Update existing break
                            $break = ProductionSlotBreak::where('production_slot_id', $productionSlot->id)
                                ->find($breakData['id']);
                            if ($break) {
                                $break->update([
                                    'break_in' => $breakData['break_in'],
                                    'break_out' => $breakData['break_out'] ?? null,
                                    'reason' => $breakData['reason'] ?? null
                                ]);
                                $existingBreakIds[] = $break->id;
                            }
                        } else {
                            // Create new break
                            $newBreak = ProductionSlotBreak::create([
                                'production_slot_id' => $productionSlot->id,
                                'break_in' => $breakData['break_in'],
                                'break_out' => $breakData['break_out'] ?? null,
                                'reason' => $breakData['reason'] ?? null
                            ]);
                            $existingBreakIds[] = $newBreak->id;
                        }
                    }
                }

                // Delete breaks that are not in the request
                ProductionSlotBreak::where('production_slot_id', $productionSlot->id)
                    ->whereNotIn('id', $existingBreakIds)
                    ->delete();
            }

            DB::commit();

            return response()->json([
                'success' => 'Production Slot updated successfully.',
                'data' => $productionSlot->load('breaks')
            ], 200);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Something went wrong: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        $productionSlot = ProductionSlot::findOrFail($id);
        $productionSlot->delete();

        return response()->json([
            'success' => 'Production Slot deleted successfully.'
        ], 200);
    }

    // Break management methods
    public function storeBreak(Request $request, $slotId)
    {
        $validated = $request->validate([
            'break_in' => 'required|date_format:H:i',
            'break_out' => 'nullable|date_format:H:i',
            'reason' => 'nullable|string|max:500'
        ]);

        $productionSlot = ProductionSlot::findOrFail($slotId);

        $break = ProductionSlotBreak::create([
            'production_slot_id' => $productionSlot->id,
            'break_in' => $request->break_in,
            'break_out' => $request->break_out,
            'reason' => $request->reason
        ]);

        return response()->json([
            'success' => 'Break added successfully.',
            'data' => $break
        ], 201);
    }

    public function updateBreak(Request $request, $slotId, $breakId)
    {
        $validated = $request->validate([
            'break_in' => 'required|date_format:H:i',
            'break_out' => 'nullable|date_format:H:i',
            'reason' => 'nullable|string|max:500'
        ]);

        $break = ProductionSlotBreak::where('production_slot_id', $slotId)
            ->findOrFail($breakId);

        $break->update($request->only(['break_in', 'break_out', 'reason']));

        return response()->json([
            'success' => 'Break updated successfully.',
            'data' => $break
        ], 200);
    }

    public function destroyBreak($slotId, $breakId)
    {
        $break = ProductionSlotBreak::where('production_slot_id', $slotId)
            ->findOrFail($breakId);
        $break->delete();

        return response()->json([
            'success' => 'Break deleted successfully.'
        ], 200);
    }
}
