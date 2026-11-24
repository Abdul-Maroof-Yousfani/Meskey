<?php
// app/Http/Controllers/Production/MachinePlanSettingController.php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Http\Requests\Production\MachinePlanSettingRequest;
use App\Models\Master\CompanyLocation;
use App\Models\Master\Plant;
use App\Models\Master\PlantBreakdownType;
use App\Models\Master\ProductionMachine;
use App\Models\Production\JobOrder\JobOrder;
use App\Models\Production\MachinePlanSetting;
use App\Models\Production\MachineTimeBreakdown;
use App\Models\Production\PlantBreakdown;
use App\Models\Production\PlantBreakdownItem;
use App\Models\Production\ProductionVoucher;
use App\Models\Production\ProductionVoucherMachineTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Machine Plan Setting: which machines of a plant run, and when, for a job order on a date.
 * Saving with several job orders creates one plan per job order (same machines and times).
 * Plans may share the same time slots. Breakdowns are added afterwards from "Manage Logs".
 */
class MachinePlanSettingController extends Controller
{
    public function index()
    {
        $companyLocations = $this->userCompanyLocations();

        return view('management.production.machine_plan_setting.index', compact('companyLocations'));
    }

    public function getList(Request $request)
    {
        $plans = MachinePlanSetting::with([
            'plant.arrivalLocation', 'plant.companyLocation', 'companyLocation', 'productionVoucher', 'user',
            'jobOrder.product', 'items.machine', 'items.breakdowns',
        ])
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%' . $request->search . '%';
                $q->where(function ($sq) use ($term) {
                    $sq->whereHas('jobOrder', fn ($j) => $j->where('job_order_no', 'like', $term))
                        ->orWhereHas('plant', fn ($p) => $p->where('name', 'like', $term))
                        ->orWhereHas('productionVoucher', fn ($v) => $v->where('prod_no', 'like', $term));
                });
            })
            ->when($request->filled('company_id'), fn ($q) => $q->where('company_id', $request->company_id))
            ->when($request->filled('company_location_id'), function ($q) use ($request) {
                $q->where(function ($sq) use ($request) {
                    $sq->where('company_location_id', $request->company_location_id)
                        ->orWhere(function ($old) use ($request) {
                            // Older records saved before location was captured
                            $old->whereNull('company_location_id')
                                ->whereHas('plant', fn ($p) => $p->where('company_location_id', $request->company_location_id));
                        });
                });
            })
            ->when($request->filled('plant_id'), fn ($q) => $q->where('plant_id', $request->plant_id))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('date', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('date', '<=', $request->date_to))
            ->latest('date')
            ->latest('id')
            ->paginate(request('per_page', 25));

        return view('management.production.machine_plan_setting.getList', ['machinePlanSettings' => $plans]);
    }

    public function create(Request $request)
    {
        $companyLocations = $this->userCompanyLocations();
        $plants = Plant::with('arrivalLocation')->where('status', 'active')->get();
        $breakdownTypes = PlantBreakdownType::where('status', 'active')->get(['id', 'name']);

        return view('management.production.machine_plan_setting.create', compact('companyLocations', 'plants', 'breakdownTypes'));
    }

    /** One plan per selected job order, each with the same machines and time slots. */
    public function store(MachinePlanSettingRequest $request)
    {
        $jobOrderIds = array_values(array_unique(array_filter(array_map('intval', (array) $request->job_order_ids))));

        DB::beginTransaction();
        try {
            foreach ($jobOrderIds as $jobOrderId) {
                $plan = MachinePlanSetting::create([
                    'company_id' => $request->company_id,
                    'company_location_id' => $request->company_location_id,
                    'date' => $request->date,
                    'plant_id' => $request->plant_id,
                    'job_order_id' => $jobOrderId,
                    'remarks' => $request->remarks,
                    'user_id' => auth()->user()->id,
                ]);

                $this->saveMachineSlots($plan, $request);
            }

            // Overall plant breakdown: one per plant and day, shared by the day's plans
            $this->savePlantBreakdown($plan, $request);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['error' => 'Something went wrong: ' . $e->getMessage()], 500);
        }

        $this->refreshAutoAnalysis();

        $count = count($jobOrderIds);
        return response()->json([
            'success' => $count > 1
                ? "{$count} Machine Plans created (one for each job order)."
                : 'Machine Plan created successfully.',
        ], 201);
    }

    public function edit(Request $request, $id)
    {
        $machinePlanSetting = MachinePlanSetting::with(['items.machine', 'productionVoucher', 'jobOrder'])->findOrFail($id);

        $companyLocations = $this->userCompanyLocations();
        $plants = Plant::with('arrivalLocation')->where('status', 'active')->get();

        // Older records may not have a location stored; fall back to the plant's location
        $selectedLocationId = $machinePlanSetting->company_location_id ?: optional($machinePlanSetting->plant)->company_location_id;

        $breakdownTypes = PlantBreakdownType::where('status', 'active')->get(['id', 'name']);

        return view('management.production.machine_plan_setting.edit', compact('machinePlanSetting', 'companyLocations', 'plants', 'selectedLocationId', 'breakdownTypes'));
    }

    public function update(MachinePlanSettingRequest $request, $id)
    {
        $plan = MachinePlanSetting::findOrFail($id);

        DB::beginTransaction();
        try {
            $plan->update([
                'company_location_id' => $request->company_location_id,
                'date' => $request->date,
                'plant_id' => $request->plant_id,
                'job_order_id' => $request->job_order_id,
                'remarks' => $request->remarks,
            ]);

            $this->saveMachineSlots($plan, $request);
            $this->savePlantBreakdown($plan, $request);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['error' => 'Something went wrong: ' . $e->getMessage()], 500);
        }

        $this->refreshAutoAnalysis();

        return response()->json(['success' => 'Machine Plan updated successfully.'], 200);
    }

    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $plan = MachinePlanSetting::with('productionVoucher')->findOrFail($id);

            // Its machine rows are the completed voucher's actual times: keep them
            if ($plan->isLocked()) {
                DB::rollBack();
                return response()->json([
                    'error' => 'This plan cannot be deleted: its Production Voucher ' . $plan->productionVoucher->prod_no . ' is already completed.',
                ], 422);
            }

            // The plant breakdown is shared by the day's plans: delete it only with the last plan
            $otherPlansExist = MachinePlanSetting::where('plant_id', $plan->plant_id)
                ->whereDate('date', $plan->date)
                ->where('id', '!=', $plan->id)
                ->exists();
            if (!$otherPlansExist) {
                PlantBreakdown::whereDate('date', $plan->date)->where('plant_id', $plan->plant_id)
                    ->where('company_id', $plan->company_id)->get()
                    ->each(function ($breakdown) {
                        $breakdown->items()->delete();
                        $breakdown->delete();
                    });
            }

            $itemIds = ProductionVoucherMachineTime::where('machine_plan_setting_id', $plan->id)->pluck('id');
            MachineTimeBreakdown::whereIn('production_voucher_machine_time_id', $itemIds)->delete();
            ProductionVoucherMachineTime::whereIn('id', $itemIds)->delete();

            $plan->delete();

            DB::commit();
            return response()->json(['success' => 'Machine Plan deleted successfully.'], 200);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['error' => 'Error deleting Machine Plan: ' . $e->getMessage()], 500);
        }
    }

    /** Read-only plan with its machine slots; only breakdowns can be entered here. */
    public function logs($id)
    {
        $plan = MachinePlanSetting::with([
            'plant.arrivalLocation', 'companyLocation', 'jobOrder.product', 'productionVoucher', 'user',
            'items' => fn ($q) => $q->orderBy('production_machine_id')->orderBy('start_time'),
            'items.machine', 'items.breakdowns' => fn ($q) => $q->orderBy('from'),
        ])->findOrFail($id);

        $plantBreakdown = PlantBreakdown::with('items')
            ->whereDate('date', $plan->date)
            ->where('plant_id', $plan->plant_id)
            ->first();

        $breakdownTypes = PlantBreakdownType::where('status', 'active')->get(['id', 'name']);

        return view('management.production.machine_plan_setting.logs', compact('plan', 'plantBreakdown', 'breakdownTypes'));
    }

    public function saveLogs(Request $request, $id)
    {
        $plan = MachinePlanSetting::with('items.machine')->findOrFail($id);
        $items = $plan->items->keyBy('id');

        $rows = collect((array) $request->input('breakdowns', []))
            ->map(fn ($list, $itemId) => collect((array) $list)->filter(fn ($r) => !empty($r['from']) || !empty($r['to']))->values())
            ->filter(fn ($list, $itemId) => $items->has($itemId));
        $plantRows = collect((array) $request->input('plant_breakdowns', []))
            ->filter(fn ($r) => !empty($r['breakdown_type_id']) || !empty($r['from']) || !empty($r['to']))
            ->values();

        // Slot times can be corrected here too (slots[item_id][start_time|end_time])
        $submittedSlots = collect((array) $request->input('slots', []))->filter(fn ($s, $itemId) => $items->has($itemId));
        $slotTimes = $items->map(function ($item) use ($submittedSlots) {
            $s = $submittedSlots->get($item->id);
            return $s
                ? [$s['start_time'] ?? null, $s['end_time'] ?? null]
                : [$item->start_time ? substr($item->start_time, 0, 5) : null, $item->end_time ? substr($item->end_time, 0, 5) : null];
        });

        $validator = Validator::make([], []);
        $validator->after(function ($v) use ($rows, $items, $plantRows, $submittedSlots, $slotTimes) {
            foreach ($submittedSlots as $itemId => $s) {
                if (empty($s['start_time']) || empty($s['end_time'])) {
                    $v->errors()->add("slots.{$itemId}", ($items[$itemId]->machine->name ?? 'Machine') . ': enter both start and end time of the slot.');
                }
            }

            foreach ($rows as $itemId => $list) {
                $item = $items[$itemId];
                [$slotStartStr, $slotEndStr] = $slotTimes[$itemId];
                $label = ($item->machine->name ?? 'Machine') . ' (' . ($slotStartStr ?: '--') . ' - ' . ($slotEndStr ?: '--') . ')';
                [$slotStart, $slotEnd] = self::range($slotStartStr, $slotEndStr);
                $taken = [];

                foreach ($list as $i => $row) {
                    $n = $i + 1;
                    if (empty($row['from']) || empty($row['to'])) {
                        $v->errors()->add("breakdowns.{$itemId}", "{$label}: breakdown {$n} needs both From and To.");
                        continue;
                    }
                    [$from, $to] = self::range($row['from'], $row['to'], $slotStart);
                    if ($to <= $from) {
                        $v->errors()->add("breakdowns.{$itemId}", "{$label}: breakdown {$n} To must be after From.");
                        continue;
                    }
                    if ($slotStart !== null && ($from < $slotStart || $to > $slotEnd)) {
                        $v->errors()->add("breakdowns.{$itemId}", "{$label}: breakdown {$n} ({$row['from']} - {$row['to']}) must be within the machine's time slot.");
                    }
                    foreach ($taken as [$tFrom, $tTo, $tn]) {
                        if ($from < $tTo && $to > $tFrom) {
                            $v->errors()->add("breakdowns.{$itemId}", "{$label}: breakdown {$n} overlaps breakdown {$tn}.");
                        }
                    }
                    $taken[] = [$from, $to, $n];
                }
            }

            foreach ($plantRows as $i => $row) {
                $n = $i + 1;
                if (empty($row['breakdown_type_id']) || empty($row['from']) || empty($row['to'])) {
                    $v->errors()->add('plant_breakdowns', "Plant breakdown {$n}: select the type and enter From and To.");
                    continue;
                }
                [$from, $to] = self::range($row['from'], $row['to']);
                if ($to <= $from) {
                    $v->errors()->add('plant_breakdowns', "Plant breakdown {$n}: To must be after From.");
                }
            }
        });

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            foreach ($submittedSlots as $itemId => $s) {
                $minutes = self::minutesBetween($s['start_time'], $s['end_time']);
                $items[$itemId]->update([
                    'start_time' => $s['start_time'],
                    'end_time' => $s['end_time'],
                    'duration_minutes' => $minutes,
                    'hours' => round($minutes / 60, 2),
                ]);
            }

            // Machine breakdowns: each slot's list is replaced with what was submitted
            MachineTimeBreakdown::whereIn('production_voucher_machine_time_id', $items->keys())->delete();
            foreach ($rows as $itemId => $list) {
                foreach ($list as $row) {
                    MachineTimeBreakdown::create([
                        'company_id' => $plan->company_id,
                        'production_voucher_machine_time_id' => $itemId,
                        'from' => $row['from'],
                        'to' => $row['to'],
                        'hours' => self::hoursBetween($row['from'], $row['to']),
                        'remarks' => $row['remarks'] ?? null,
                    ]);
                }
            }

            // Plant breakdown: one per plant and day, shared by the day's plans
            $plantBreakdown = PlantBreakdown::whereDate('date', $plan->date)->where('plant_id', $plan->plant_id)->first();
            if ($plantRows->isNotEmpty() && !$plantBreakdown) {
                $plantBreakdown = PlantBreakdown::create([
                    'company_id' => $plan->company_id,
                    'date' => $plan->date,
                    'plant_id' => $plan->plant_id,
                    'production_voucher_id' => $plan->production_voucher_id,
                    'user_id' => auth()->user()->id,
                ]);
            }
            if ($plantBreakdown) {
                $plantBreakdown->items()->delete();
                foreach ($plantRows as $row) {
                    PlantBreakdownItem::create([
                        'company_id' => $plan->company_id,
                        'plant_breakdown_id' => $plantBreakdown->id,
                        'breakdown_type_id' => $row['breakdown_type_id'],
                        'from' => $row['from'],
                        'to' => $row['to'],
                        'hours' => self::hoursBetween($row['from'], $row['to']),
                        'remarks' => $row['remarks'] ?? null,
                    ]);
                }
                $plantBreakdown->update(['user_id' => auth()->user()->id]);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['error' => 'Something went wrong: ' . $e->getMessage()], 500);
        }

        return response()->json(['success' => 'Logs saved successfully.'], 200);
    }

    /**
     * Machines of a plant for the form: each with its ON/OFF state and time slots.
     * With plan_id the plan's saved slots are used; otherwise the plant's Shift Time,
     * with required machines ON and the others OFF.
     */
    public function getMachinesByPlant(Request $request)
    {
        $plant = $request->filled('plant_id') ? Plant::find($request->plant_id) : null;
        if (!$plant) {
            return response()->json(['machines' => []]);
        }

        $shift = [
            'start_time' => $plant->shift_start_time ? substr($plant->shift_start_time, 0, 5) : '09:00',
            'end_time' => $plant->shift_end_time ? substr($plant->shift_end_time, 0, 5) : '21:00',
        ];

        $plan = $request->filled('plan_id') ? MachinePlanSetting::with('items.breakdowns')->find($request->plan_id) : null;
        $savedByMachine = $plan && (int) $plan->plant_id === (int) $plant->id
            ? $plan->items->sortBy('id')->groupBy('production_machine_id')
            : collect();

        $machines = ProductionMachine::where('status', 'active')->where('plant_id', $plant->id)->orderBy('id')->get()
            ->map(function ($machine) use ($savedByMachine, $shift) {
                $saved = $savedByMachine->get($machine->id, collect());
                $slots = $saved
                    ->filter(fn ($i) => $i->start_time || $i->end_time)
                    ->map(fn ($i) => [
                        'start_time' => $i->start_time ? substr($i->start_time, 0, 5) : '',
                        'end_time' => $i->end_time ? substr($i->end_time, 0, 5) : '',
                        'breakdowns' => $i->breakdowns->sortBy('from')->map(fn ($b) => [
                            'from' => $b->from ? substr($b->from, 0, 5) : '',
                            'to' => $b->to ? substr($b->to, 0, 5) : '',
                            'hours' => $b->hours,
                            'remarks' => $b->remarks,
                        ])->values()->all(),
                    ])
                    ->values()->all();

                return [
                    'id' => $machine->id,
                    'name' => $machine->name,
                    'is_enabled' => $saved->isNotEmpty() ? (bool) $saved->first()->is_enabled : (bool) $machine->is_required,
                    'slots' => $slots ?: [$shift + ['breakdowns' => []]],
                ];
            });

        return response()->json(['machines' => $machines, 'shift' => $shift]);
    }

    public function getJobOrdersByLocation(Request $request)
    {
        if (!$request->filled('company_location_id')) {
            return response()->json(['jobOrders' => []]);
        }

        $jobOrders = $this->jobOrdersForLocation($request->company_location_id, $request->company_id)
            ->map(function ($jo) {
                // Several job orders in one plan must share the product's main category
                // (e.g. Irri and Sella both fall under Rice; Wheat stays with Wheat)
                $mainCategory = $jo->product?->category?->rootCategory();

                return [
                    'id' => $jo->id,
                    'text' => $jo->job_order_no . ($jo->ref_no ? ' (' . $jo->ref_no . ')' : '') . ($jo->product ? ' - ' . $jo->product->name : ''),
                    'category_id' => $mainCategory?->id,
                    'category_name' => $mainCategory?->name,
                ];
            })
            ->values();

        return response()->json(['jobOrders' => $jobOrders]);
    }

    public function getProductionVouchersByDate(Request $request)
    {
        $vouchers = ProductionVoucher::when($request->filled('date'), fn ($q) => $q->whereDate('prod_date', $request->date))
            ->when($request->filled('plant_id'), fn ($q) => $q->where('plant_id', $request->plant_id))
            ->when($request->filled('company_id'), fn ($q) => $q->where('company_id', $request->company_id))
            ->latest()
            ->get(['id', 'prod_no']);

        return response()->json(['vouchers' => $vouchers]);
    }

    /** Used by the Production Voucher screens. */
    public function getBreakdownsByPlantAndDate(Request $request)
    {
        $breakdown = PlantBreakdown::with('items.breakdownType')
            ->whereDate('date', $request->date)
            ->where('plant_id', $request->plant_id)
            ->when($request->filled('company_id'), fn ($q) => $q->where('company_id', $request->company_id))
            ->first();

        return response()->json([
            'breakdown' => $breakdown,
            'items' => $breakdown ? $breakdown->items : [],
        ]);
    }

    /**
     * Write the plan's machine rows from the form. Existing rows are reused in order
     * (so their breakdowns stay); extra old rows and machines no longer on the plant are removed.
     */
    private function saveMachineSlots(MachinePlanSetting $plan, Request $request): void
    {
        $allIds = array_values(array_unique(array_filter(array_map('intval', (array) $request->input('all_machine_ids', [])))));
        $enabledIds = array_map('intval', (array) $request->input('production_machine_id', []));

        $existing = ProductionVoucherMachineTime::where('machine_plan_setting_id', $plan->id)->orderBy('id')->get()->groupBy('production_machine_id');
        $keepIds = [];

        foreach ($allIds as $machineId) {
            $isEnabled = in_array($machineId, $enabledIds, true);
            $starts = array_values((array) $request->input("machine_start_time.{$machineId}", []));
            $ends = array_values((array) $request->input("machine_end_time.{$machineId}", []));

            $slots = [];
            foreach ($starts as $i => $start) {
                $end = $ends[$i] ?? null;
                if ($start || $end) {
                    $slots[] = [$start ?: null, $end ?: null, $i]; // $i: the slot's position on the form (its breakdowns use it)
                }
            }
            if (!$slots) {
                $slots[] = [null, null, null]; // machine is listed on the plan even without a time
            }

            $old = $existing->get($machineId, collect())->values();
            foreach ($slots as $i => [$start, $end, $formIndex]) {
                $minutes = ($start && $end) ? self::minutesBetween($start, $end) : 0;
                $data = [
                    'company_id' => $plan->company_id,
                    'machine_plan_setting_id' => $plan->id,
                    'production_voucher_id' => $plan->production_voucher_id,
                    'production_machine_id' => $machineId,
                    'job_order_ids' => $plan->job_order_id ? [(int) $plan->job_order_id] : null,
                    'start_time' => $start,
                    'end_time' => $end,
                    'duration_minutes' => $minutes,
                    'hours' => round($minutes / 60, 2),
                    'is_enabled' => $isEnabled,
                ];

                if ($row = $old->get($i)) {
                    $row->update($data);
                } else {
                    $row = ProductionVoucherMachineTime::create($data);
                }
                $keepIds[] = $row->id;

                // Inner breakdowns of this slot (slot_breakdown_*[machine][slot][])
                MachineTimeBreakdown::where('production_voucher_machine_time_id', $row->id)->delete();
                if ($formIndex !== null) {
                    $this->saveSlotBreakdowns($plan, $row->id, $request, $machineId, $formIndex);
                }
            }
        }

        $removeIds = $existing->flatten()->pluck('id')->diff($keepIds);
        if ($removeIds->isNotEmpty()) {
            MachineTimeBreakdown::whereIn('production_voucher_machine_time_id', $removeIds)->delete();
            ProductionVoucherMachineTime::whereIn('id', $removeIds)->delete();
        }
    }

    private function saveSlotBreakdowns(MachinePlanSetting $plan, int $itemId, Request $request, int $machineId, int $slotIndex): void
    {
        $froms = (array) $request->input("slot_breakdown_from.{$machineId}.{$slotIndex}", []);
        $tos = (array) $request->input("slot_breakdown_to.{$machineId}.{$slotIndex}", []);
        $remarks = (array) $request->input("slot_breakdown_remarks.{$machineId}.{$slotIndex}", []);

        foreach ($froms as $b => $from) {
            $to = $tos[$b] ?? null;
            if (!$from) {
                continue;
            }
            MachineTimeBreakdown::create([
                'company_id' => $plan->company_id,
                'production_voucher_machine_time_id' => $itemId,
                'from' => $from,
                'to' => $to ?: null,
                'hours' => $to ? self::hoursBetween($from, $to) : null,
                'remarks' => $remarks[$b] ?? null,
            ]);
        }
    }

    /** Overall plant breakdown rows of the form (breakdown_type_id[], from[], to[], breakdown_remarks[]). */
    private function savePlantBreakdown(MachinePlanSetting $plan, Request $request): void
    {
        $rows = [];
        foreach ((array) $request->input('breakdown_type_id', []) as $i => $typeId) {
            $from = $request->input("from.{$i}");
            if ($typeId && $from) {
                $to = $request->input("to.{$i}") ?: null;
                $rows[] = [
                    'breakdown_type_id' => $typeId,
                    'from' => $from,
                    'to' => $to,
                    'hours' => $to ? self::hoursBetween($from, $to) : null,
                    'remarks' => $request->input("breakdown_remarks.{$i}"),
                ];
            }
        }

        $breakdown = PlantBreakdown::whereDate('date', $plan->date)->where('plant_id', $plan->plant_id)->first();
        if (!$rows && !$breakdown) {
            return;
        }
        if (!$breakdown) {
            $breakdown = PlantBreakdown::create([
                'company_id' => $plan->company_id,
                'date' => $plan->date,
                'plant_id' => $plan->plant_id,
                'production_voucher_id' => $plan->production_voucher_id,
                'user_id' => auth()->user()->id,
            ]);
        }

        // The form shows the day's saved rows, so what comes back replaces them
        $breakdown->items()->delete();
        foreach ($rows as $row) {
            PlantBreakdownItem::create($row + ['company_id' => $plan->company_id, 'plant_breakdown_id' => $breakdown->id]);
        }
        $breakdown->update(['user_id' => auth()->user()->id]);
    }

    /** Minutes from start to end; an end before the start runs past midnight. */
    private static function minutesBetween($start, $end): int
    {
        [$from, $to] = self::range($start, $end);
        return (int) ($to - $from);
    }

    private static function hoursBetween($start, $end): float
    {
        return round(self::minutesBetween($start, $end) / 60, 2);
    }

    /**
     * [from, to] in minutes of the day; an end before the start runs past midnight.
     * With $after, times earlier than it are taken as the next day (a slot that crosses midnight).
     */
    private static function range($start, $end, ?int $after = null): array
    {
        if (!$start || !$end) {
            return [null, null];
        }
        $toMin = fn ($t) => ((int) substr($t, 0, 2)) * 60 + (int) substr($t, 3, 2);
        $from = $toMin($start);
        $to = $toMin($end);
        if ($after !== null && $from < $after) {
            $from += 1440;
            $to += 1440;
        }
        if ($to < $from) {
            $to += 1440;
        }
        return [$from, $to];
    }

    private function refreshAutoAnalysis(): void
    {
        try {
            app(\App\Services\Production\Milling\AutoAnalysisRequestService::class)->checkAndGenerateRequests();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('AutoAnalysis checkAndGenerateRequests error on machine plan save: ' . $e->getMessage());
        }
    }

    /**
     * Same location list (and order) as the Job Order form:
     * super-admins see all active locations, others only their assigned ones.
     */
    private function userCompanyLocations()
    {
        $user = auth()->user();
        $locationIds = getUserCurrentCompanyLocations();

        if ($user && ($user->user_type === 'super-admin' || empty($locationIds))) {
            return CompanyLocation::where('status', 'active')->get();
        }

        return CompanyLocation::whereIn('id', $locationIds)->where('status', 'active')->get();
    }

    private function jobOrdersForLocation($locationId, $companyId = null)
    {
        $companyId = $companyId ?: (auth()->user()->company_id ?? null);

        return JobOrder::with('product.category.parent')
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereIn('status', [1, 'active'])
            ->where(function ($sub) use ($locationId) {
                $sub->where('company_location_id', $locationId)
                    ->orWhereHas('packingItems', fn ($pi) => $pi->where('company_location_id', $locationId));
            })
            ->orderBy('job_order_no', 'desc')
            ->get();
    }
}
