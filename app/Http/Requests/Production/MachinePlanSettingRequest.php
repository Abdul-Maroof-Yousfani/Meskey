<?php
// app/Http/Requests/Production/MachinePlanSettingRequest.php

namespace App\Http\Requests\Production;

use App\Models\Production\MachinePlanSetting;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Machine Plan Setting form: date, location, plant, job order(s) and the machines' time slots.
 * Create takes several job orders (one plan is saved per job order); edit takes one.
 * Machine time slots may overlap other plans (several job orders run on the same slots).
 * Breakdowns are not entered here: they are added from "Manage Logs" on the list.
 */
class MachinePlanSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isEdit = (bool) $this->route('machine_plan_setting');

        return [
            'company_id' => 'required|exists:companies,id',
            'date' => 'required|date',
            'company_location_id' => 'required|exists:company_locations,id',
            'plant_id' => 'required|exists:plants,id',
            'job_order_ids' => $isEdit ? 'nullable|array' : 'required|array|min:1',
            'job_order_ids.*' => 'nullable|exists:job_orders,id',
            'job_order_id' => $isEdit ? 'required|exists:job_orders,id' : 'nullable',
            'remarks' => 'nullable|string|max:1000',
            'all_machine_ids' => 'nullable|array',
            'all_machine_ids.*' => 'nullable|exists:production_machines,id',
            'production_machine_id' => 'nullable|array',
            'production_machine_id.*' => 'nullable|exists:production_machines,id',
            'machine_start_time' => 'nullable|array',
            'machine_end_time' => 'nullable|array',
            // Inner (slot) breakdowns and the overall plant breakdown
            'slot_breakdown_from' => 'nullable|array',
            'slot_breakdown_to' => 'nullable|array',
            'slot_breakdown_hours' => 'nullable|array',
            'slot_breakdown_remarks' => 'nullable|array',
            'breakdown_type_id' => 'nullable|array',
            'breakdown_type_id.*' => 'nullable|exists:plant_breakdown_types,id',
            'from' => 'nullable|array',
            'to' => 'nullable|array',
            'hours' => 'nullable|array',
            'breakdown_remarks' => 'nullable|array',
            'breakdown_remarks.*' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'company_location_id.required' => 'Please select a location.',
            'plant_id.required' => 'Please select a plant.',
            'job_order_ids.required' => 'Please select at least one job order.',
            'job_order_ids.min' => 'Please select at least one job order.',
            'job_order_id.required' => 'Please select the job order.',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Plant must belong to the selected location
            if ($this->filled('plant_id') && $this->filled('company_location_id')) {
                $plant = \App\Models\Master\Plant::find($this->plant_id);
                if ($plant && $plant->company_location_id && $plant->company_location_id != $this->company_location_id) {
                    $validator->errors()->add('plant_id', 'Selected plant does not belong to the selected location.');
                }
            }

            // Several job orders in one plan must all be of the same main product category
            // (sub-categories like Irri and Sella under Rice count as Rice)
            $jobOrderIds = array_filter((array) $this->input('job_order_ids', []));
            if (count($jobOrderIds) > 1) {
                $categories = \App\Models\Production\JobOrder\JobOrder::with('product.category.parent')
                    ->whereIn('id', $jobOrderIds)
                    ->get()
                    ->map(function ($jo) {
                        $main = $jo->product?->category?->rootCategory();
                        return ['id' => $main?->id, 'name' => $main?->name ?? 'No Category'];
                    })
                    ->unique('id');
                if ($categories->count() > 1) {
                    $validator->errors()->add('job_order_ids', 'All selected job orders must be of the same main product category. Selected: ' . $categories->pluck('name')->implode(', ') . '.');
                }
            }

            // At least one machine switched on, and each ON machine needs a start and end time
            $enabled = array_filter((array) $this->input('production_machine_id', []));
            if ($this->filled('plant_id') && empty($enabled)) {
                $validator->errors()->add('production_machine_id', 'Please switch on at least one machine.');
            }

            $names = \App\Models\Master\ProductionMachine::whereIn('id', $enabled)->pluck('name', 'id');
            foreach ($enabled as $machineId) {
                $starts = (array) $this->input("machine_start_time.{$machineId}", []);
                $ends = (array) $this->input("machine_end_time.{$machineId}", []);
                $name = $names[$machineId] ?? "#{$machineId}";

                $hasSlot = false;
                foreach ($starts as $i => $start) {
                    $end = $ends[$i] ?? null;
                    if (!$start && !$end) {
                        continue;
                    }
                    if (!$start || !$end) {
                        $validator->errors()->add("machine_start_time.{$machineId}", "{$name}: enter both start and end time of slot " . ($i + 1) . '.');
                    }
                    $hasSlot = true;
                }
                if (!$hasSlot) {
                    $validator->errors()->add("machine_start_time.{$machineId}", "{$name}: add a time slot or switch the machine off.");
                }
            }

            $this->validatePlantBreakdowns($validator);
            $this->validateSlotBreakdowns($validator);

            // A plan whose voucher is completed holds the voucher's actual times
            $plan = $this->editedPlan();
            if ($plan && $plan->isLocked()) {
                $validator->errors()->add('plan', 'Production Voucher ' . ($plan->productionVoucher->prod_no ?? '') . ' of this plan is already completed, so its machine times can only be changed from that voucher.');
            }
        });
    }

    /** Overall plant breakdown rows: To after From, and rows must not overlap each other. */
    private function validatePlantBreakdowns($validator): void
    {
        $taken = [];
        foreach ((array) $this->input('breakdown_type_id', []) as $i => $typeId) {
            $from = $this->input("from.{$i}");
            $to = $this->input("to.{$i}");
            if (!$typeId || !$from || !$to) {
                continue;
            }
            $n = $i + 1;
            $fromSec = strtotime($from);
            $toSec = strtotime($to);
            if ($fromSec >= $toSec) {
                $validator->errors()->add("to.{$i}", "Plant breakdown row {$n}: End time ({$to}) must be after start time ({$from}).");
                continue;
            }
            foreach ($taken as [$tFrom, $tTo, $tn, $tLabel]) {
                if (max($fromSec, $tFrom) < min($toSec, $tTo)) {
                    $validator->errors()->add("from.{$i}", "Plant breakdown ({$from} - {$to}) in row {$n} overlaps with ({$tLabel}) in row {$tn}.");
                }
            }
            $taken[] = [$fromSec, $toSec, $n, "{$from} - {$to}"];
        }
    }

    /** Inner breakdowns: inside their time slot, To after From, and no overlap within the slot. */
    private function validateSlotBreakdowns($validator): void
    {
        $toMin = fn ($t) => ((int) substr($t, 0, 2)) * 60 + (int) substr($t, 3, 2);

        foreach ((array) $this->input('slot_breakdown_from', []) as $machineId => $slots) {
            foreach ((array) $slots as $slotIdx => $froms) {
                $slotStart = $this->input("machine_start_time.{$machineId}.{$slotIdx}");
                $slotEnd = $this->input("machine_end_time.{$machineId}.{$slotIdx}");
                $pStart = $slotStart ? $toMin($slotStart) : null;
                $pEnd = $slotEnd ? $toMin($slotEnd) : null;
                if ($pStart !== null && $pEnd !== null && $pEnd < $pStart) {
                    $pEnd += 1440; // slot runs past midnight
                }

                $taken = [];
                foreach ((array) $froms as $b => $from) {
                    $to = $this->input("slot_breakdown_to.{$machineId}.{$slotIdx}.{$b}");
                    if (!$from || !$to) {
                        continue;
                    }
                    $f = $toMin($from);
                    $t = $toMin($to);
                    // In a slot that crosses midnight, times before the slot start are the next day
                    if ($pStart !== null && $pEnd > 1440) {
                        if ($f < $pStart) $f += 1440;
                        if ($t < $pStart) $t += 1440;
                    }
                    $key = "slot_breakdown_from.{$machineId}.{$slotIdx}.{$b}";
                    if ($t <= $f) {
                        $validator->errors()->add($key, "Inner breakdown end time ({$to}) must be after start time ({$from}).");
                        continue;
                    }
                    if ($pStart !== null && $pEnd !== null && ($f < $pStart || $t > $pEnd)) {
                        $validator->errors()->add($key, "Inner breakdown ({$from} - {$to}) must be within its time slot ({$slotStart} - {$slotEnd}).");
                    }
                    foreach ($taken as [$tf, $tt, $label]) {
                        if (max($f, $tf) < min($t, $tt)) {
                            $validator->errors()->add($key, "Inner breakdown ({$from} - {$to}) overlaps with ({$label}).");
                        }
                    }
                    $taken[] = [$f, $t, "{$from} - {$to}"];
                }
            }
        }
    }

    /** The plan being edited (none on create). */
    public function editedPlan(): ?MachinePlanSetting
    {
        $id = $this->route('machine_plan_setting');
        if (!$id) {
            return null;
        }

        return MachinePlanSetting::with('productionVoucher')->find(is_object($id) ? $id->id : $id);
    }
}
