<?php
// app/Http/Requests/Production/MachinePlanSettingRequest.php

namespace App\Http\Requests\Production;

use Illuminate\Foundation\Http\FormRequest;

class MachinePlanSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function messages(): array
    {
        return [
            'company_location_id.required' => 'Please select a location.',
        ];
    }

    public function rules(): array
    {
        return [
            'company_id' => 'required|exists:companies,id',
            'date' => 'required|date',
            'company_location_id' => 'required|exists:company_locations,id',
            'plant_id' => 'required|exists:plants,id',
            'machine_job_order_id' => 'nullable|array',
            'machine_job_order_id.*' => 'nullable|array',
            'machine_job_order_id.*.*' => 'nullable|exists:job_orders,id',
            'production_voucher_id' => 'nullable|exists:production_vouchers,id',
            'existing_plan_id' => 'nullable|integer',
            'create_new_plan' => 'nullable|in:0,1',
            'remarks' => 'nullable|string',
            'production_machine_id' => 'nullable|array',
            'production_machine_id.*' => 'nullable|exists:production_machines,id',
            'all_machine_ids' => 'nullable|array',
            'all_machine_ids.*' => 'nullable|exists:production_machines,id',
            'machine_start_time' => 'nullable|array',
            'machine_end_time' => 'nullable|array',
            'machines' => 'nullable|array',
            'machines.*.production_machine_id' => 'nullable|exists:production_machines,id',
            'machines.*.start_time' => 'nullable',
            'machines.*.end_time' => 'nullable',
            'machines.*.hours' => 'nullable|numeric|min:0|max:24',
            'machines.*.is_enabled' => 'boolean',
            'machines.*.remarks' => 'nullable|string',
            'breakdown_type_id' => 'nullable|array',
            'breakdown_type_id.*' => 'nullable|exists:plant_breakdown_types,id',
            'from' => 'nullable|array',
            'from.*' => 'nullable',
            'to' => 'nullable|array',
            'to.*' => 'nullable',
            'hours' => 'nullable|array',
            'hours.*' => 'nullable|numeric|min:0',
            'breakdown_remarks' => 'nullable|array',
            'breakdown_remarks.*' => 'nullable|string|max:1000',
            'slot_breakdown_from' => 'nullable|array',
            'slot_breakdown_to' => 'nullable|array',
            'slot_breakdown_hours' => 'nullable|array',
            'slot_breakdown_remarks' => 'nullable|array',
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

            // Each machine card takes at most 3 Head, 3 B2 and 3 By Product job orders
            $limit = \App\Http\Controllers\Production\MachinePlanSettingController::MACHINE_JOB_ORDER_LIMIT;
            $perMachine = (array) $this->input('machine_job_order_id', []);
            $allIds = collect($perMachine)->flatten()->filter()->unique()->values();
            if ($allIds->isNotEmpty()) {
                $jobOrders = \App\Models\Production\JobOrder\JobOrder::with('product:id,product_category_flags')
                    ->whereIn('id', $allIds)->get()->keyBy('id');
                $machineNames = \App\Models\Master\ProductionMachine::whereIn('id', array_keys($perMachine))->pluck('name', 'id');

                foreach ($perMachine as $machineId => $ids) {
                    $counts = collect((array) $ids)->filter()->unique()
                        ->map(fn ($id) => $jobOrders->get($id))->filter()
                        ->countBy(fn ($jo) => \App\Http\Controllers\Production\MachinePlanSettingController::jobOrderGroup($jo));

                    foreach ($counts as $group => $count) {
                        if ($count > $limit) {
                            $name = $machineNames[$machineId] ?? "#{$machineId}";
                            $validator->errors()->add(
                                "machine_job_order_id.{$machineId}",
                                "Machine '{$name}': only {$limit} {$group} job orders can be selected ({$count} selected)."
                            );
                        }
                    }
                }
            }

            // Overall plant breakdown validation
            if ($this->has('breakdown_type_id') && is_array($this->breakdown_type_id)) {
                $intervals = [];
                foreach ($this->breakdown_type_id as $index => $typeId) {
                    $from = $this->from[$index] ?? null;
                    $to = $this->to[$index] ?? null;

                    if (!empty($typeId) && !empty($from) && !empty($to)) {
                        $fromTime = strtotime($from);
                        $toTime = strtotime($to);

                        if ($fromTime >= $toTime) {
                            $validator->errors()->add("to.$index", "Breakdown row " . ($index + 1) . ": End time ($to) must be after start time ($from).");
                        }

                        foreach ($intervals as $prevIndex => $prev) {
                            if (max($fromTime, $prev['from']) < min($toTime, $prev['to'])) {
                                $validator->errors()->add("from.$index", "Breakdown interval ($from - $to) in row " . ($index + 1) . " overlaps with interval ({$prev['fromStr']} - {$prev['toStr']}) in row " . ($prevIndex + 1) . ".");
                            }
                        }

                        $intervals[] = [
                            'from' => $fromTime,
                            'to' => $toTime,
                            'fromStr' => $from,
                            'toStr' => $to,
                        ];
                    }
                }
            }

            // Machine slot breakdown validations (within parent time slot)
            if ($this->has('slot_breakdown_from') && is_array($this->slot_breakdown_from)) {
                foreach ($this->slot_breakdown_from as $machineId => $slotBreakdowns) {
                    if (!is_array($slotBreakdowns)) continue;

                    foreach ($slotBreakdowns as $slotIdx => $froms) {
                        if (!is_array($froms)) continue;

                        $parentStart = $this->machine_start_time[$machineId][$slotIdx] ?? null;
                        $parentEnd = $this->machine_end_time[$machineId][$slotIdx] ?? null;

                        $parentStartSec = $parentStart ? strtotime("1970-01-01 $parentStart:00") : null;
                        $parentEndSec = $parentEnd ? strtotime("1970-01-01 $parentEnd:00") : null;
                        if ($parentStartSec && $parentEndSec && $parentEndSec < $parentStartSec) {
                            $parentEndSec += 86400; // Next day
                        }

                        $slotIntervals = [];

                        foreach ($froms as $bIdx => $bFrom) {
                            $bTo = $this->slot_breakdown_to[$machineId][$slotIdx][$bIdx] ?? null;

                            if (!empty($bFrom)) {
                                $bFromSec = strtotime("1970-01-01 $bFrom:00");
                                $bToSec = !empty($bTo) ? strtotime("1970-01-01 $bTo:00") : null;

                                if ($bToSec && $bToSec < $bFromSec) {
                                    $bToSec += 86400;
                                }

                                // Check parent start boundary
                                if ($parentStartSec && $bFromSec < $parentStartSec) {
                                    $validator->errors()->add("slot_breakdown_from.{$machineId}.{$slotIdx}.{$bIdx}", "Child breakdown start time ($bFrom) cannot be earlier than Time Slot start ($parentStart).");
                                }

                                // Check parent end boundary
                                if ($parentEndSec && $bToSec && $bToSec > $parentEndSec) {
                                    $validator->errors()->add("slot_breakdown_to.{$machineId}.{$slotIdx}.{$bIdx}", "Child breakdown end time ($bTo) cannot be later than Time Slot end ($parentEnd).");
                                }

                                if ($bToSec) {
                                    if ($bFromSec >= $bToSec) {
                                        $validator->errors()->add("slot_breakdown_to.{$machineId}.{$slotIdx}.{$bIdx}", "Breakdown end time ($bTo) must be after start time ($bFrom).");
                                    }

                                    foreach ($slotIntervals as $prev) {
                                        if (max($bFromSec, $prev['from']) < min($bToSec, $prev['to'])) {
                                            $validator->errors()->add("slot_breakdown_from.{$machineId}.{$slotIdx}.{$bIdx}", "Breakdown interval ($bFrom - $bTo) overlaps with ({$prev['fromStr']} - {$prev['toStr']}).");
                                        }
                                    }

                                    $slotIntervals[] = [
                                        'from' => $bFromSec,
                                        'to' => $bToSec,
                                        'fromStr' => $bFrom,
                                        'toStr' => $bTo,
                                    ];
                                }
                            }
                        }
                    }
                }
            }

            if (!$this->filled('plant_id') || !$this->filled('date')) {
                return;
            }

            // A plan whose voucher is completed holds the voucher's actual times: change them from the voucher
            $targetPlan = $this->targetPlan();
            if ($targetPlan && $targetPlan->isLocked()) {
                $prodNo = $targetPlan->productionVoucher->prod_no ?? '';
                $message = "Production Voucher {$prodNo} of this plan is already completed, so its machine times can only be changed from that voucher.";
                if (!$this->route('machine_plan_setting')) {
                    $message .= " Tick 'Create as New Voucher' to add another plan for this day.";
                }
                $validator->errors()->add('plan', $message);
                return;
            }

            // Machine times must not overlap each other or the plant's other plans that day
            $conflicts = app(\App\Services\Production\Milling\ProductionDayPlanService::class)->timeConflicts(
                (int) $this->plant_id,
                $this->date,
                \App\Services\Production\Milling\ProductionDayPlanService::slotsFromRequest($this, true),
                $targetPlan?->id,
                array_filter([$targetPlan?->production_voucher_id])
            );
            foreach (array_slice($conflicts, 0, 6) as $message) {
                $validator->errors()->add('machine_times', $message);
            }
        });
    }

    /**
     * The existing plan this save writes to: the edited plan, the plan picked on the create screen,
     * or the plant's latest plan that day. None when 'Create as New Voucher' is ticked.
     */
    public function targetPlan(): ?\App\Models\Production\MachinePlanSetting
    {
        $query = \App\Models\Production\MachinePlanSetting::with('productionVoucher');

        if ($id = $this->route('machine_plan_setting')) {
            return $query->find(is_object($id) ? $id->id : $id);
        }

        if ($this->boolean('create_new_plan') || !$this->filled('plant_id') || !$this->filled('date')) {
            return null;
        }

        $samePlantDay = (clone $query)->where('plant_id', $this->plant_id)->whereDate('date', $this->date);

        if ($this->filled('existing_plan_id')) {
            $plan = (clone $samePlantDay)->find($this->existing_plan_id);
            if ($plan) {
                return $plan;
            }
        }

        return $samePlantDay->latest('id')->first();
    }
}