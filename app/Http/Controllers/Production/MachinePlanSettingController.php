<?php
// app/Http/Controllers/Production/MachinePlanSettingController.php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Http\Requests\Production\MachinePlanSettingRequest;
use App\Models\Master\ProductionMachine;
use App\Models\Production\MachinePlanSetting;
use App\Models\Production\ProductionVoucherMachineTime;
use App\Models\Production\MachineTimeBreakdown;
use App\Models\Master\Plant;
use App\Models\Master\Machine;
use App\Models\Master\PlantBreakdownType;
use App\Models\Production\PlantBreakdown;
use App\Models\Production\PlantBreakdownItem;
use App\Models\Production\ProductionVoucher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MachinePlanSettingController extends Controller
{
    public function index()
    {
        return view('management.production.machine_plan_setting.index');
    }

    public function getList(Request $request)
    {
        $machinePlanSettings = MachinePlanSetting::with(['plant', 'productionVoucher', 'user', 'items.machine'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $searchTerm = '%' . $request->search . '%';
                return $q->where(function ($sq) use ($searchTerm) {
                    $sq->whereHas('plant', function ($q) use ($searchTerm) {
                        $q->where('name', 'like', $searchTerm);
                    })
                        ->orWhereHas('productionVoucher', function ($q) use ($searchTerm) {
                            $q->where('prod_no', 'like', $searchTerm);
                        });
                });
            })
            ->when($request->filled('company_id'), function ($q) use ($request) {
                return $q->where('company_id', $request->company_id);
            })
            ->when($request->filled('plant_id'), function ($q) use ($request) {
                return $q->where('plant_id', $request->plant_id);
            })
            ->when($request->filled('date_from'), function ($q) use ($request) {
                return $q->whereDate('date', '>=', $request->date_from);
            })
            ->when($request->filled('date_to'), function ($q) use ($request) {
                return $q->whereDate('date', '<=', $request->date_to);
            })
            ->latest('date')
            ->latest('created_at')
            ->paginate(request('per_page', 25));

        $plantBreakdowns = PlantBreakdown::with('items.breakdownType')
            ->whereIn('plant_id', $machinePlanSettings->pluck('plant_id'))
            ->whereIn('date', $machinePlanSettings->pluck('date'))
            ->get()
            ->groupBy(function ($item) {
                return $item->date->format('Y-m-d') . '_' . $item->plant_id;
            });

        return view('management.production.machine_plan_setting.getList', compact('machinePlanSettings', 'plantBreakdowns'));
    }

    public function create(Request $request)
    {
        $plants = Plant::where('status', 'active')->get();
        $date = $request->input('date', date('Y-m-d'));
        $productionVouchers = ProductionVoucher::whereDate('prod_date', $date)
            ->when($request->filled('plant_id'), function ($q) use ($request) {
                return $q->where('plant_id', $request->plant_id);
            })
            ->latest()
            ->get();
        $breakdownTypes = PlantBreakdownType::where('status', 'active')->get();
        $machines = ProductionMachine::where('status', 'active')
            ->when($request->filled('plant_id'), function ($q) use ($request) {
                return $q->where('plant_id', $request->plant_id);
            })
            ->get();

        return view('management.production.machine_plan_setting.create', compact('plants', 'productionVouchers', 'machines', 'breakdownTypes'));
    }

    public function store(MachinePlanSettingRequest $request)
    {
        DB::beginTransaction();

        try {
            $machinePlanSettingData = $request->only([
                'company_id',
                'date',
                'plant_id',
                'production_voucher_id',
                'remarks',
            ]);
            $machinePlanSettingData['user_id'] = auth()->user()->id;

            $existingPlan = MachinePlanSetting::where('company_id', $request->company_id)
                ->where('plant_id', $request->plant_id)
                ->whereDate('date', $request->date)
                ->first();

            if ($existingPlan) {
                $existingPlan->update($machinePlanSettingData);
                $machinePlanSetting = $existingPlan;
                ProductionVoucherMachineTime::where('machine_plan_setting_id', $machinePlanSetting->id)->delete();
            } else {
                $machinePlanSetting = MachinePlanSetting::create($machinePlanSettingData);
            }

            // Save machine items using ProductionVoucherMachineTime
            $enabledMachineIds = $request->input('production_machine_id', []);
            if (!is_array($enabledMachineIds)) {
                $enabledMachineIds = [];
            }

            $allMachineIds = $request->input('all_machine_ids', []);
            if (empty($allMachineIds) && $request->has('machine_start_time')) {
                $allMachineIds = array_keys($request->machine_start_time);
            }

            if (!empty($allMachineIds)) {
                foreach ($allMachineIds as $machineId) {
                    $isEnabled = in_array($machineId, $enabledMachineIds);
                    $startTimes = $request->input("machine_start_time.{$machineId}", []);
                    $endTimes = $request->input("machine_end_time.{$machineId}", []);

                    $hasValidSlot = false;
                    if (is_array($startTimes) && !empty($startTimes)) {
                        foreach ($startTimes as $index => $startTime) {
                            $endTime = $endTimes[$index] ?? null;
                            $durationMinutes = 0;
                            $hours = 0;

                            if (!empty($startTime) && !empty($endTime)) {
                                $start = \Carbon\Carbon::parse($startTime);
                                $end = \Carbon\Carbon::parse($endTime);
                                if ($end->lt($start)) {
                                    $end->addDay();
                                }
                                $durationMinutes = $start->diffInMinutes($end);
                                $hours = round($durationMinutes / 60, 2);
                            }

                            if (!empty($startTime) || !empty($endTime)) {
                                $hasValidSlot = true;
                                $machineTime = ProductionVoucherMachineTime::create([
                                    'company_id' => $request->company_id,
                                    'machine_plan_setting_id' => $machinePlanSetting->id,
                                    'production_voucher_id' => $machinePlanSetting->production_voucher_id,
                                    'production_machine_id' => $machineId,
                                    'start_time' => $startTime ?: null,
                                    'end_time' => $endTime ?: null,
                                    'duration_minutes' => $durationMinutes,
                                    'hours' => $hours,
                                    'is_enabled' => $isEnabled,
                                ]);

                                // Save child breakdowns for this slot
                                $slotBreakdownFroms = $request->input("slot_breakdown_from.{$machineId}.{$index}", []);
                                $slotBreakdownTos = $request->input("slot_breakdown_to.{$machineId}.{$index}", []);
                                $slotBreakdownHours = $request->input("slot_breakdown_hours.{$machineId}.{$index}", []);
                                $slotBreakdownRemarks = $request->input("slot_breakdown_remarks.{$machineId}.{$index}", []);

                                if (is_array($slotBreakdownFroms) && !empty($slotBreakdownFroms)) {
                                    foreach ($slotBreakdownFroms as $bIdx => $bFrom) {
                                        $bTo = !empty($slotBreakdownTos[$bIdx]) ? $slotBreakdownTos[$bIdx] : null;
                                        $bHours = !empty($slotBreakdownHours[$bIdx]) ? $slotBreakdownHours[$bIdx] : null;

                                        if (!empty($bFrom)) {
                                            if (!empty($bFrom) && !empty($bTo) && empty($bHours)) {
                                                $fTime = \Carbon\Carbon::parse($bFrom);
                                                $tTime = \Carbon\Carbon::parse($bTo);
                                                if ($tTime->lt($fTime)) {
                                                    $tTime->addDay();
                                                }
                                                $bHours = round($fTime->diffInMinutes($tTime) / 60, 2);
                                            }

                                            MachineTimeBreakdown::create([
                                                'company_id' => $request->company_id,
                                                'production_voucher_machine_time_id' => $machineTime->id,
                                                'from' => $bFrom,
                                                'to' => $bTo,
                                                'hours' => $bHours,
                                                'remarks' => $slotBreakdownRemarks[$bIdx] ?? null,
                                            ]);
                                        }
                                    }
                                }
                            }
                        }
                    }

                    if (!$hasValidSlot) {
                        ProductionVoucherMachineTime::create([
                            'company_id' => $request->company_id,
                            'machine_plan_setting_id' => $machinePlanSetting->id,
                            'production_voucher_id' => $machinePlanSetting->production_voucher_id,
                            'production_machine_id' => $machineId,
                            'start_time' => null,
                            'end_time' => null,
                            'duration_minutes' => 0,
                            'hours' => 0,
                            'is_enabled' => $isEnabled,
                        ]);
                    }
                }
            } elseif ($request->has('machines') && is_array($request->machines)) {
                foreach ($request->machines as $machineData) {
                    if (!empty($machineData['production_machine_id'])) {
                        $startTime = $machineData['start_time'] ?? null;
                        $endTime = $machineData['end_time'] ?? null;
                        $hours = $machineData['hours'] ?? null;
                        $durationMinutes = 0;

                        if (!empty($startTime) && !empty($endTime)) {
                            $fromT = \Carbon\Carbon::parse($startTime);
                            $toT = \Carbon\Carbon::parse($endTime);
                            if ($toT < $fromT) {
                                $toT->addDay();
                            }
                            $durationMinutes = $fromT->diffInMinutes($toT);
                            if (empty($hours) || $hours == 0) {
                                $hours = round($durationMinutes / 60, 2);
                            }
                        } elseif (!empty($hours)) {
                            $durationMinutes = round($hours * 60);
                        }

                        ProductionVoucherMachineTime::create([
                            'company_id' => $request->company_id,
                            'machine_plan_setting_id' => $machinePlanSetting->id,
                            'production_voucher_id' => $machinePlanSetting->production_voucher_id,
                            'production_machine_id' => $machineData['production_machine_id'],
                            'start_time' => $startTime,
                            'end_time' => $endTime,
                            'duration_minutes' => $durationMinutes,
                            'hours' => $hours ?? 0,
                            'is_enabled' => $machineData['is_enabled'] ?? false,
                            'remarks' => $machineData['remarks'] ?? null,
                        ]);
                    }
                }
            }

            // Save plant breakdown and items if breakdown items are provided
            $hasBreakdownItems = false;
            if ($request->has('breakdown_type_id') && is_array($request->breakdown_type_id)) {
                foreach ($request->breakdown_type_id as $index => $breakdownTypeId) {
                    if (!empty($breakdownTypeId) && !empty($request->from[$index])) {
                        $hasBreakdownItems = true;
                        break;
                    }
                }
            }

            if ($hasBreakdownItems) {
                $plantBreakdown = PlantBreakdown::updateOrCreate(
                    [
                        'company_id' => $request->company_id,
                        'date' => $request->date,
                        'plant_id' => $request->plant_id,
                    ],
                    [
                        'production_voucher_id' => $request->production_voucher_id,
                        'user_id' => auth()->user()->id,
                    ]
                );

                $plantBreakdown->items()->delete();

                foreach ($request->breakdown_type_id as $index => $breakdownTypeId) {
                    if (!empty($breakdownTypeId) && !empty($request->from[$index])) {
                        $fromVal = $request->from[$index];
                        $toVal = !empty($request->to[$index]) ? $request->to[$index] : null;
                        $hoursVal = !empty($request->hours[$index]) ? $request->hours[$index] : null;

                        if (!empty($fromVal) && !empty($toVal) && empty($hoursVal)) {
                            $fromTime = \Carbon\Carbon::parse($fromVal);
                            $toTime = \Carbon\Carbon::parse($toVal);
                            $hoursVal = $fromTime->diffInHours($toTime, false) + ($fromTime->diffInMinutes($toTime, false) % 60) / 60;
                        }

                        PlantBreakdownItem::create([
                            'company_id' => $request->company_id,
                            'plant_breakdown_id' => $plantBreakdown->id,
                            'breakdown_type_id' => $breakdownTypeId,
                            'from' => $fromVal,
                            'to' => $toVal,
                            'hours' => $hoursVal,
                            'remarks' => $request->breakdown_remarks[$index] ?? null,
                        ]);
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => 'Machine Plan Setting created successfully.',
                'data' => $machinePlanSetting->load('items.machine')
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Something went wrong: ' . $e->getMessage()
            ], 500);
        }
    }

    public function edit(Request $request, $id)
    {
        $machinePlanSetting = MachinePlanSetting::with(['items.machine', 'items.breakdowns'])->findOrFail($id);

        $plants = Plant::where('status', 'active')
            ->when($request->filled('company_id'), function ($q) use ($request) {
                return $q->where('company_id', $request->company_id);
            })
            ->get();

        $selectedDate = $machinePlanSetting->date ? $machinePlanSetting->date->format('Y-m-d') : null;

        $productionVouchers = ProductionVoucher::when($selectedDate, function ($q) use ($selectedDate) {
            return $q->whereDate('prod_date', $selectedDate);
        })
            ->when($request->filled('company_id'), function ($q) use ($request) {
                return $q->where('company_id', $request->company_id);
            })
            ->latest()
            ->get();

        $breakdownTypes = PlantBreakdownType::where('status', 'active')
            ->when($request->filled('company_id'), function ($q) use ($request) {
                return $q->where('company_id', $request->company_id);
            })
            ->get();

        $plantBreakdown = PlantBreakdown::with('items.breakdownType')
            ->whereDate('date', $machinePlanSetting->date)
            ->where('plant_id', $machinePlanSetting->plant_id)
            ->when($request->filled('company_id'), function ($q) use ($request) {
                return $q->where('company_id', $request->company_id);
            })
            ->first();

        $machines = ProductionMachine::where('status', 'active')
            ->when($machinePlanSetting->plant_id, function ($q) use ($machinePlanSetting) {
                return $q->where('plant_id', $machinePlanSetting->plant_id);
            })
            ->get();

        return view('management.production.machine_plan_setting.edit', compact('machinePlanSetting', 'plants', 'productionVouchers', 'machines', 'breakdownTypes', 'plantBreakdown'));
    }

    public function update(MachinePlanSettingRequest $request, $id)
    {
        $machinePlanSetting = MachinePlanSetting::findOrFail($id);

        DB::beginTransaction();

        try {
            $oldDate = $machinePlanSetting->date;
            $oldPlantId = $machinePlanSetting->plant_id;

            $machinePlanSettingData = $request->only([
                'company_id',
                'date',
                'plant_id',
                'production_voucher_id',
                'remarks',
            ]);

            $machinePlanSettingData['user_id'] = auth()->user()->id;
            $machinePlanSetting->update($machinePlanSettingData);

            // Delete existing items
            ProductionVoucherMachineTime::where('machine_plan_setting_id', $machinePlanSetting->id)->delete();

            // Save new machine items using ProductionVoucherMachineTime
            $enabledMachineIds = $request->input('production_machine_id', []);
            if (!is_array($enabledMachineIds)) {
                $enabledMachineIds = [];
            }

            $allMachineIds = $request->input('all_machine_ids', []);
            if (empty($allMachineIds) && $request->has('machine_start_time')) {
                $allMachineIds = array_keys($request->machine_start_time);
            }

            if (!empty($allMachineIds)) {
                foreach ($allMachineIds as $machineId) {
                    $isEnabled = in_array($machineId, $enabledMachineIds);
                    $startTimes = $request->input("machine_start_time.{$machineId}", []);
                    $endTimes = $request->input("machine_end_time.{$machineId}", []);

                    $hasValidSlot = false;
                    if (is_array($startTimes) && !empty($startTimes)) {
                        foreach ($startTimes as $index => $startTime) {
                            $endTime = $endTimes[$index] ?? null;
                            $durationMinutes = 0;
                            $hours = 0;

                            if (!empty($startTime) && !empty($endTime)) {
                                $start = \Carbon\Carbon::parse($startTime);
                                $end = \Carbon\Carbon::parse($endTime);
                                if ($end->lt($start)) {
                                    $end->addDay();
                                }
                                $durationMinutes = $start->diffInMinutes($end);
                                $hours = round($durationMinutes / 60, 2);
                            }

                            if (!empty($startTime) || !empty($endTime)) {
                                $hasValidSlot = true;
                                $machineTime = ProductionVoucherMachineTime::create([
                                    'company_id' => $request->company_id,
                                    'machine_plan_setting_id' => $machinePlanSetting->id,
                                    'production_voucher_id' => $machinePlanSetting->production_voucher_id,
                                    'production_machine_id' => $machineId,
                                    'start_time' => $startTime ?: null,
                                    'end_time' => $endTime ?: null,
                                    'duration_minutes' => $durationMinutes,
                                    'hours' => $hours,
                                    'is_enabled' => $isEnabled,
                                ]);

                                // Save child breakdowns for this slot
                                $slotBreakdownFroms = $request->input("slot_breakdown_from.{$machineId}.{$index}", []);
                                $slotBreakdownTos = $request->input("slot_breakdown_to.{$machineId}.{$index}", []);
                                $slotBreakdownHours = $request->input("slot_breakdown_hours.{$machineId}.{$index}", []);
                                $slotBreakdownRemarks = $request->input("slot_breakdown_remarks.{$machineId}.{$index}", []);

                                if (is_array($slotBreakdownFroms) && !empty($slotBreakdownFroms)) {
                                    foreach ($slotBreakdownFroms as $bIdx => $bFrom) {
                                        $bTo = !empty($slotBreakdownTos[$bIdx]) ? $slotBreakdownTos[$bIdx] : null;
                                        $bHours = !empty($slotBreakdownHours[$bIdx]) ? $slotBreakdownHours[$bIdx] : null;

                                        if (!empty($bFrom)) {
                                            if (!empty($bFrom) && !empty($bTo) && empty($bHours)) {
                                                $fTime = \Carbon\Carbon::parse($bFrom);
                                                $tTime = \Carbon\Carbon::parse($bTo);
                                                if ($tTime->lt($fTime)) {
                                                    $tTime->addDay();
                                                }
                                                $bHours = round($fTime->diffInMinutes($tTime) / 60, 2);
                                            }

                                            MachineTimeBreakdown::create([
                                                'company_id' => $request->company_id,
                                                'production_voucher_machine_time_id' => $machineTime->id,
                                                'from' => $bFrom,
                                                'to' => $bTo,
                                                'hours' => $bHours,
                                                'remarks' => $slotBreakdownRemarks[$bIdx] ?? null,
                                            ]);
                                        }
                                    }
                                }
                            }
                        }
                    }

                    if (!$hasValidSlot) {
                        ProductionVoucherMachineTime::create([
                            'company_id' => $request->company_id,
                            'machine_plan_setting_id' => $machinePlanSetting->id,
                            'production_voucher_id' => $machinePlanSetting->production_voucher_id,
                            'production_machine_id' => $machineId,
                            'start_time' => null,
                            'end_time' => null,
                            'duration_minutes' => 0,
                            'hours' => 0,
                            'is_enabled' => $isEnabled,
                        ]);
                    }
                }
            } elseif ($request->has('machines') && is_array($request->machines)) {
                foreach ($request->machines as $machineData) {
                    if (!empty($machineData['production_machine_id'])) {
                        $startTime = $machineData['start_time'] ?? null;
                        $endTime = $machineData['end_time'] ?? null;
                        $hours = $machineData['hours'] ?? null;
                        $durationMinutes = 0;

                        if (!empty($startTime) && !empty($endTime)) {
                            $fromT = \Carbon\Carbon::parse($startTime);
                            $toT = \Carbon\Carbon::parse($endTime);
                            if ($toT < $fromT) {
                                $toT->addDay();
                            }
                            $durationMinutes = $fromT->diffInMinutes($toT);
                            if (empty($hours) || $hours == 0) {
                                $hours = round($durationMinutes / 60, 2);
                            }
                        } elseif (!empty($hours)) {
                            $durationMinutes = round($hours * 60);
                        }

                        ProductionVoucherMachineTime::create([
                            'company_id' => $request->company_id,
                            'machine_plan_setting_id' => $machinePlanSetting->id,
                            'production_voucher_id' => $machinePlanSetting->production_voucher_id,
                            'production_machine_id' => $machineData['production_machine_id'],
                            'start_time' => $startTime,
                            'end_time' => $endTime,
                            'duration_minutes' => $durationMinutes,
                            'hours' => $hours ?? 0,
                            'is_enabled' => $machineData['is_enabled'] ?? false,
                            'remarks' => $machineData['remarks'] ?? null,
                        ]);
                    }
                }
            }

            // Save / Update plant breakdown items
            $hasBreakdownItems = false;
            if ($request->has('breakdown_type_id') && is_array($request->breakdown_type_id)) {
                foreach ($request->breakdown_type_id as $index => $breakdownTypeId) {
                    if (!empty($breakdownTypeId) && !empty($request->from[$index])) {
                        $hasBreakdownItems = true;
                        break;
                    }
                }
            }

            if ($hasBreakdownItems) {
                $plantBreakdown = PlantBreakdown::where('company_id', $request->company_id)
                    ->where(function ($q) use ($request, $oldDate, $oldPlantId) {
                        $q->where(function ($sq) use ($oldDate, $oldPlantId) {
                            $sq->whereDate('date', $oldDate)->where('plant_id', $oldPlantId);
                        })->orWhere(function ($sq) use ($request) {
                            $sq->whereDate('date', $request->date)->where('plant_id', $request->plant_id);
                        });
                    })->first();

                if (!$plantBreakdown) {
                    $plantBreakdown = PlantBreakdown::create([
                        'company_id' => $request->company_id,
                        'date' => $request->date,
                        'plant_id' => $request->plant_id,
                        'production_voucher_id' => $request->production_voucher_id,
                        'user_id' => auth()->user()->id,
                    ]);
                } else {
                    $plantBreakdown->update([
                        'date' => $request->date,
                        'plant_id' => $request->plant_id,
                        'production_voucher_id' => $request->production_voucher_id,
                        'user_id' => auth()->user()->id,
                    ]);
                }

                $plantBreakdown->items()->delete();

                foreach ($request->breakdown_type_id as $index => $breakdownTypeId) {
                    if (!empty($breakdownTypeId) && !empty($request->from[$index])) {
                        $fromVal = $request->from[$index];
                        $toVal = !empty($request->to[$index]) ? $request->to[$index] : null;
                        $hoursVal = !empty($request->hours[$index]) ? $request->hours[$index] : null;

                        if (!empty($fromVal) && !empty($toVal) && empty($hoursVal)) {
                            $fromTime = \Carbon\Carbon::parse($fromVal);
                            $toTime = \Carbon\Carbon::parse($toVal);
                            $hoursVal = $fromTime->diffInHours($toTime, false) + ($fromTime->diffInMinutes($toTime, false) % 60) / 60;
                        }

                        PlantBreakdownItem::create([
                            'company_id' => $request->company_id,
                            'plant_breakdown_id' => $plantBreakdown->id,
                            'breakdown_type_id' => $breakdownTypeId,
                            'from' => $fromVal,
                            'to' => $toVal,
                            'hours' => $hoursVal,
                            'remarks' => $request->breakdown_remarks[$index] ?? null,
                        ]);
                    }
                }
            }

            DB::commit();

            // Load the relationship separately after commit
            $machinePlanSetting->load('items.machine');

            return response()->json([
                'success' => 'Machine Plan Setting updated successfully.',
                'data' => $machinePlanSetting
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
        DB::beginTransaction();
        try {
            $machinePlanSetting = MachinePlanSetting::findOrFail($id);

            // Also delete associated PlantBreakdown if it exists
            $plantBreakdown = PlantBreakdown::where('date', $machinePlanSetting->date)
                ->where('plant_id', $machinePlanSetting->plant_id)
                ->where('company_id', $machinePlanSetting->company_id)
                ->first();

            if ($plantBreakdown) {
                $plantBreakdown->delete();
            }

            ProductionVoucherMachineTime::where('machine_plan_setting_id', $machinePlanSetting->id)->delete();

            $machinePlanSetting->delete();

            DB::commit();

            return response()->json([
                'success' => 'Machine Plan Setting deleted successfully.'
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Error deleting Machine Plan Setting: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getMachinesByPlant(Request $request)
    {
        $machines = ProductionMachine::where('status', 'active')
            ->when($request->filled('plant_id'), function ($q) use ($request) {
                return $q->where('plant_id', $request->plant_id);
            })
            ->get();

        // Check if there is an existing machine plan setting for this plant and selected date
        $existingSetting = null;
        if ($request->filled('plant_id') && $request->filled('date')) {
            $existingSetting = MachinePlanSetting::with('items.breakdowns')
                ->where('plant_id', $request->plant_id)
                ->whereDate('date', $request->date)
                ->when($request->filled('company_id'), function ($q) use ($request) {
                    return $q->where('company_id', $request->company_id);
                })
                ->first();
        }

        $machineItemsGrouped = $existingSetting ? $existingSetting->items->groupBy('production_machine_id') : collect();

        $machinesData = $machines->map(function ($machine) use ($machineItemsGrouped) {
            $items = $machineItemsGrouped->get($machine->id, collect());
            $firstItem = $items->first();
            $isEnabled = $firstItem ? (bool)$firstItem->is_enabled : false;

            $slots = [];
            if ($items->isNotEmpty()) {
                foreach ($items as $item) {
                    $bList = [];
                    if ($item->breakdowns && $item->breakdowns->isNotEmpty()) {
                        foreach ($item->breakdowns as $bd) {
                            $bList[] = [
                                'id' => $bd->id,
                                'from' => $bd->from ? substr($bd->from, 0, 5) : '',
                                'to' => $bd->to ? substr($bd->to, 0, 5) : '',
                                'hours' => $bd->hours,
                                'remarks' => $bd->remarks ?? '',
                            ];
                        }
                    }

                    $slots[] = [
                        'id' => $item->id,
                        'start_time' => $item->start_time ? substr($item->start_time, 0, 5) : '',
                        'end_time' => $item->end_time ? substr($item->end_time, 0, 5) : '',
                        'hours' => $item->hours,
                        'breakdowns' => $bList,
                    ];
                }
            } else {
                $slots[] = [
                    'id' => null,
                    'start_time' => '',
                    'end_time' => '',
                    'hours' => null,
                    'breakdowns' => [],
                ];
            }

            return [
                'id' => $machine->id,
                'name' => $machine->name,
                'is_enabled' => $isEnabled,
                'start_time' => $slots[0]['start_time'] ?? '',
                'end_time' => $slots[0]['end_time'] ?? '',
                'hours' => $slots[0]['hours'] ?? null,
                'time_slots' => $slots,
            ];
        });

        return response()->json([
            'machines' => $machinesData,
            'existing_plan' => $existingSetting ? [
                'id' => $existingSetting->id,
                'production_voucher_id' => $existingSetting->production_voucher_id,
                'remarks' => $existingSetting->remarks
            ] : null
        ]);
    }

    public function getProductionVouchersByDate(Request $request)
    {
        $vouchers = ProductionVoucher::when($request->filled('date'), function ($q) use ($request) {
            return $q->whereDate('prod_date', $request->date);
        })
            ->when($request->filled('plant_id'), function ($q) use ($request) {
                return $q->where('plant_id', $request->plant_id);
            })
            ->when($request->filled('company_id'), function ($q) use ($request) {
                return $q->where('company_id', $request->company_id);
            })
            ->latest()
            ->get(['id', 'prod_no']);

        return response()->json([
            'vouchers' => $vouchers
        ]);
    }

    public function getBreakdownsByPlantAndDate(Request $request)
    {
        $breakdown = PlantBreakdown::with('items.breakdownType')
            ->whereDate('date', $request->date)
            ->where('plant_id', $request->plant_id)
            ->when($request->filled('company_id'), function ($q) use ($request) {
                return $q->where('company_id', $request->company_id);
            })
            ->first();

        return response()->json([
            'breakdown' => $breakdown,
            'items' => $breakdown ? $breakdown->items : []
        ]);
    }
}