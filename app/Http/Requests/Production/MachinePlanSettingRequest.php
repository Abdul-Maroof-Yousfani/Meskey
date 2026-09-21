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

    public function rules(): array
    {
        return [
            'company_id' => 'required|exists:companies,id',
            'date' => 'required|date',
            'plant_id' => 'required|exists:plants,id',
            'production_voucher_id' => 'nullable|exists:production_vouchers,id',
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
        });
    }
}