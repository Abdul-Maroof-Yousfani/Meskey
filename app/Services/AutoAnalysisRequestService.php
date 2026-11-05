<?php

namespace App\Services;

use App\Http\Controllers\Production\ProductionAnalysisRequestController;
use App\Models\Master\Plant;
use App\Models\Production\JobOrder\JobOrder;
use App\Models\Production\MachinePlanSetting;
use App\Models\Production\PlantBreakdown;
use App\Models\Production\ProductionAnalysis;
use App\Models\Production\ProductionAnalysisRequest;
use App\Models\Production\ProductionVoucher;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AutoAnalysisRequestService
{
    /**
     * Check all active plants and automatically generate Input/Output Analysis Requests
     * if Machine Plan Setting is running and no active breakdown exists.
     *
     * @param Carbon|null $now
     * @return array Summary of operations
     */
    public function checkAndGenerateRequests(?Carbon $now = null): array
    {
        $now = $now ?: now();
        $todayDate = $now->format('Y-m-d');

        $plants = Plant::with(['companyLocation', 'arrivalLocation'])
            ->where('status', 'active')
            ->where('auto_analysis_enabled', true)
            ->get();

        $created = [];
        $skipped = [];

        foreach ($plants as $plant) {
            // 1. Check if plant is currently active and running in Machine Plan Setting
            $runningInfo = $this->isPlantRunning($plant, $now);
            if (!$runningInfo['is_running']) {
                $skipped[] = [
                    'plant_id' => $plant->id,
                    'plant_name' => $plant->name,
                    'reason' => $runningInfo['reason'],
                ];
                continue;
            }

            // 2. Check if an active breakdown currently exists for this plant
            if ($this->hasActiveBreakdown($plant, $now)) {
                $skipped[] = [
                    'plant_id' => $plant->id,
                    'plant_name' => $plant->name,
                    'reason' => 'Plant currently under active breakdown',
                ];
                continue;
            }

            // 3. Process both Input and Output Analysis Types
            $types = [
                ProductionAnalysisRequest::TYPE_INPUT,
                ProductionAnalysisRequest::TYPE_OUTPUT,
            ];

            foreach ($types as $type) {
                // If the plant requires it, wait until the last request of this type is completed
                if ($plant->require_last_completed) {
                    $lastRequest = $this->getLastRequest($plant, $type);
                    if ($lastRequest && $lastRequest->status !== 'completed') {
                        $skipped[] = [
                            'plant_id' => $plant->id,
                            'plant_name' => $plant->name,
                            'type' => $type,
                            'reason' => "Waiting for last request #{$lastRequest->request_no} to be completed (status: {$lastRequest->status})",
                        ];
                        continue;
                    }
                }

                // Check if an analysis request is due based on min/max interval
                if ($this->isAnalysisDue($plant, $type, $runningInfo, $now)) {
                    $request = $this->createAnalysisRequest($plant, $type, $runningInfo, $now);
                    if ($request) {
                        $created[] = [
                            'plant_id' => $plant->id,
                            'plant_name' => $plant->name,
                            'type' => $type,
                            'request_no' => $request->request_no,
                        ];
                    }
                } else {
                    $nextCol = ($type === ProductionAnalysisRequest::TYPE_INPUT) ? 'next_input_analysis_at' : 'next_output_analysis_at';
                    $plant->refresh();
                    $targetTime = $plant->$nextCol ? Carbon::parse($plant->$nextCol)->format('h:i A') : 'later';
                    $skipped[] = [
                        'plant_id' => $plant->id,
                        'plant_name' => $plant->name,
                        'type' => $type,
                        'reason' => "Scheduled for {$targetTime} (time interval not reached yet)",
                    ];
                }
            }
        }

        return [
            'checked_at' => $now->toDateTimeString(),
            'total_plants_checked' => $plants->count(),
            'created_count' => count($created),
            'created' => $created,
            'skipped' => $skipped,
        ];
    }

    /**
     * Latest analysis request (manual or auto) of the given type for a plant.
     */
    public function getLastRequest(Plant $plant, string $requestType): ?ProductionAnalysisRequest
    {
        return ProductionAnalysisRequest::where('plant_id', $plant->id)
            ->where('type', $requestType)
            ->latest('id')
            ->first();
    }

    /**
     * Check if a plant is currently in running status based on MachinePlanSetting.
     */
    public function isPlantRunning(Plant $plant, ?Carbon $atTime = null): array
    {
        $atTime = $atTime ?: now();
        $currentTime = $atTime->format('H:i:s');
        $todayDate = $atTime->format('Y-m-d');

        // A plant can have several plans (each with its own Production Voucher) on one day;
        // the one with a machine running right now is the one requests are made against.
        $plans = MachinePlanSetting::with(['items.machine', 'items.breakdowns'])
            ->where('plant_id', $plant->id)
            ->whereDate('date', $todayDate)
            ->latest('id')
            ->get();

        if ($plans->isEmpty()) {
            return [
                'is_running' => false,
                'reason' => 'No Machine Plan Setting created for today',
                'plan' => null,
                'machine' => null,
                'active_slot' => null,
            ];
        }

        $lastResult = null;
        foreach ($plans as $plan) {
            $lastResult = $this->runningInfoForPlan($plan, $currentTime);
            if ($lastResult['is_running']) {
                return $lastResult;
            }
        }

        return $lastResult;
    }

    /** Running state of one plan at the given time (H:i:s). */
    private function runningInfoForPlan(MachinePlanSetting $plan, string $currentTime): array
    {
        $enabledItems = $plan->items->where('is_enabled', true);
        if ($enabledItems->isEmpty()) {
            return [
                'is_running' => false,
                'reason' => 'No machines enabled in Machine Plan Setting',
                'plan' => $plan,
                'machine' => null,
                'active_slot' => null,
            ];
        }

        $runningItem = null;
        $slotText = null;

        foreach ($enabledItems as $item) {
            $start = $item->start_time;
            $end = $item->end_time;

            if (!empty($start) && !empty($end)) {
                if ($end >= $start) {
                    if ($currentTime >= $start && $currentTime <= $end) {
                        $runningItem = $item;
                        $slotText = "{$start} - {$end}";
                        break;
                    }
                } else {
                    // Overnight running slot
                    if ($currentTime >= $start || $currentTime <= $end) {
                        $runningItem = $item;
                        $slotText = "{$start} - {$end} (overnight)";
                        break;
                    }
                }
            } elseif (!empty($start) && empty($end)) {
                if ($currentTime >= $start) {
                    $runningItem = $item;
                    $slotText = "From {$start}";
                    break;
                }
            } else {
                // Enabled for the day with open time
                $runningItem = $item;
                $slotText = 'Full Day';
                break;
            }
        }

        if (!$runningItem) {
            return [
                'is_running' => false,
                'reason' => "Current time ({$currentTime}) is outside scheduled machine running hours",
                'plan' => $plan,
                'machine' => null,
                'active_slot' => null,
            ];
        }

        return [
            'is_running' => true,
            'reason' => 'Plant is currently running',
            'plan' => $plan,
            'machine' => $runningItem,
            'active_slot' => $slotText,
        ];
    }

    /**
     * Check if the plant or running machine currently has an active breakdown.
     */
    public function hasActiveBreakdown(Plant $plant, ?Carbon $atTime = null): bool
    {
        $atTime = $atTime ?: now();
        $currentTime = $atTime->format('H:i:s');
        $todayDate = $atTime->format('Y-m-d');

        // 1. Check PlantBreakdown items
        $plantBreakdowns = PlantBreakdown::with('items')
            ->where('plant_id', $plant->id)
            ->whereDate('date', $todayDate)
            ->get();

        foreach ($plantBreakdowns as $pb) {
            foreach ($pb->items as $item) {
                $from = $item->from;
                $to = $item->to;

                if (!empty($from)) {
                    if (empty($to)) {
                        // Ongoing breakdown without end time
                        if ($currentTime >= $from) {
                            return true;
                        }
                    } else {
                        if ($to >= $from) {
                            if ($currentTime >= $from && $currentTime <= $to) {
                                return true;
                            }
                        } else {
                            if ($currentTime >= $from || $currentTime <= $to) {
                                return true;
                            }
                        }
                    }
                }
            }
        }

        // 2. Check MachineTimeBreakdown on enabled machines of the day's plans
        $plans = MachinePlanSetting::with(['items.breakdowns'])
            ->where('plant_id', $plant->id)
            ->whereDate('date', $todayDate)
            ->latest('id')
            ->get();

        foreach ($plans as $plan) {
            $runningMachines = $plan->items->where('is_enabled', true);
            foreach ($runningMachines as $mItem) {
                foreach ($mItem->breakdowns as $b) {
                    $from = $b->from;
                    $to = $b->to;
                    if (!empty($from)) {
                        if (empty($to)) {
                            if ($currentTime >= $from) {
                                return true;
                            }
                        } else {
                            if ($to >= $from) {
                                if ($currentTime >= $from && $currentTime <= $to) {
                                    return true;
                                }
                            } else {
                                if ($currentTime >= $from || $currentTime <= $to) {
                                    return true;
                                }
                            }
                        }
                    }
                }
            }
        }

        return false;
    }

    /**
     * Get the reference timestamp for a plant's analysis schedule today:
     * 1. Most recent analysis request created today (pending or completed)
     * 2. Most recent completed analysis today
     * 3. Machine start time
     */
    public function getReferenceTimestamp(Plant $plant, string $requestType, array $runningInfo, Carbon $atTime): Carbon
    {
        $isInput = ($requestType === ProductionAnalysisRequest::TYPE_INPUT);
        $analysisType = $isInput ? 'input' : 'output';

        $lastRequest = ProductionAnalysisRequest::where('plant_id', $plant->id)
            ->where('type', $requestType)
            ->whereDate('request_date', $atTime->toDateString())
            ->latest('id')
            ->first();

        $lastAnalysis = ProductionAnalysis::where('plant_id', $plant->id)
            ->where('production_analysis_type', $analysisType)
            ->whereDate('analysis_date', $atTime->toDateString())
            ->latest('id')
            ->first();

        $referenceTimestamp = null;
        if ($lastRequest) {
            $referenceTimestamp = $lastRequest->auto_generated_at
                ? Carbon::parse($lastRequest->auto_generated_at)
                : Carbon::parse($lastRequest->created_at);
        }

        if ($lastAnalysis) {
            $lastTimeStr = $lastAnalysis->items()->max('analysis_time') ?? $lastAnalysis->created_at->format('H:i:s');
            $anaTime = Carbon::parse($atTime->toDateString() . ' ' . $lastTimeStr);
            if (!$referenceTimestamp || $anaTime->gt($referenceTimestamp)) {
                $referenceTimestamp = $anaTime;
            }
        }

        if (!$referenceTimestamp) {
            $startTimeStr = $runningInfo['machine']->start_time ?? ($runningInfo['plan'] ? $runningInfo['plan']->created_at->format('H:i:s') : '00:00:00');
            $referenceTimestamp = Carbon::parse($atTime->toDateString() . ' ' . $startTimeStr);
        }

        return $referenceTimestamp;
    }

    /**
     * Recalculates and adjusts the next scheduled auto analysis timestamps
     * when plant interval settings are modified or plant running state changes.
     */
    public function recalculatePlantSchedule(Plant $plant, ?Carbon $atTime = null): void
    {
        $atTime = $atTime ?: now();
        $runningInfo = $this->isPlantRunning($plant, $atTime);

        // If plant is not running or auto analysis is disabled, clear next scheduled timestamps
        if (!$runningInfo['is_running'] || !$plant->auto_analysis_enabled) {
            $plant->update([
                'next_input_analysis_at' => null,
                'next_output_analysis_at' => null,
            ]);
            return;
        }

        $updates = [];
        foreach ([ProductionAnalysisRequest::TYPE_INPUT, ProductionAnalysisRequest::TYPE_OUTPUT] as $type) {
            $isInput = ($type === ProductionAnalysisRequest::TYPE_INPUT);
            $min = $isInput ? $plant->getInputMinInterval() : $plant->getOutputMinInterval();
            $max = $isInput ? $plant->getInputMaxInterval() : $plant->getOutputMaxInterval();
            if ($max < $min) {
                $max = $min;
            }
            $nextCol = $isInput ? 'next_input_analysis_at' : 'next_output_analysis_at';

            $refTime = $this->getReferenceTimestamp($plant, $type, $runningInfo, $atTime);
            $elapsedMinutes = $refTime->diffInMinutes($atTime, false);

            if ($elapsedMinutes >= $max) {
                // Maximum interval already reached or passed -> mark due immediately
                $updates[$nextCol] = $atTime;
            } elseif ($elapsedMinutes >= $min) {
                // Minimum interval already reached -> mark due immediately
                $updates[$nextCol] = $atTime;
            } else {
                // Less than min minutes elapsed -> schedule remaining random minutes
                $remainingMin = max(1, $min - $elapsedMinutes);
                $remainingMax = max($remainingMin, $max - $elapsedMinutes);
                $randomMinutes = rand($remainingMin, $remainingMax);
                $updates[$nextCol] = (clone $atTime)->addMinutes($randomMinutes);
            }
        }

        if (!empty($updates)) {
            $plant->update($updates);
            $plant->refresh();
        }
    }

    /**
     * Determine whether an analysis request is due for a plant and type.
     */
    public function isAnalysisDue(Plant $plant, string $requestType, array $runningInfo, ?Carbon $atTime = null): bool
    {
        $atTime = $atTime ?: now();
        $isInput = ($requestType === ProductionAnalysisRequest::TYPE_INPUT);

        $min = $isInput ? $plant->getInputMinInterval() : $plant->getOutputMinInterval();
        $max = $isInput ? $plant->getInputMaxInterval() : $plant->getOutputMaxInterval();
        if ($max < $min) {
            $max = $min;
        }

        $nextDueCol = $isInput ? 'next_input_analysis_at' : 'next_output_analysis_at';
        $refTime = $this->getReferenceTimestamp($plant, $requestType, $runningInfo, $atTime);
        $elapsedMinutes = $refTime->diffInMinutes($atTime, false);

        // A) If the elapsed time since last event already exceeds max interval, it is overdue
        if ($elapsedMinutes >= $max) {
            return true;
        }

        // B) If a scheduled timestamp exists in the database
        if ($plant->$nextDueCol) {
            $scheduledDue = Carbon::parse($plant->$nextDueCol);

            // If scheduled time has arrived or passed, it's due
            if ($atTime->gte($scheduledDue)) {
                return true;
            }

            // Stale schedule check:
            // If scheduledDue is farther in the future than max interval from now,
            // or if it exceeds max interval from refTime, it is from an older larger interval setting!
            if ($scheduledDue->gt($atTime->copy()->addMinutes($max)) || $scheduledDue->gt($refTime->copy()->addMinutes($max))) {
                if ($elapsedMinutes >= $min) {
                    return true;
                }
                // Reschedule to adhere to the current smaller interval
                $remainingMin = max(1, $min - $elapsedMinutes);
                $remainingMax = max($remainingMin, $max - $elapsedMinutes);
                $newDue = (clone $atTime)->addMinutes(rand($remainingMin, $remainingMax));
                $plant->update([$nextDueCol => $newDue]);
                return false;
            }

            // Scheduled in the future within the valid window
            return false;
        }

        // C) If no target schedule timestamp exists yet:
        if ($elapsedMinutes >= $min) {
            return true;
        }

        // Generate next due time between min and max minutes
        $randomMinutes = rand($min, $max);
        $targetDue = (clone $refTime)->addMinutes($randomMinutes);

        // Save computed schedule so subsequent checks adhere to this window
        $plant->update([$nextDueCol => $targetDue]);

        return $atTime->gte($targetDue);
    }

    /**
     * Create the auto-generated ProductionAnalysisRequest record.
     */
    protected function createAnalysisRequest(Plant $plant, string $type, array $runningInfo, Carbon $now): ?ProductionAnalysisRequest
    {
        try {
            DB::beginTransaction();

            $requestNo = ProductionAnalysisRequestController::generateNumber(
                $now->toDateString(),
                $plant->company_location_id
            );

            $isInput = ($type === ProductionAnalysisRequest::TYPE_INPUT);
            $min = $isInput ? $plant->getInputMinInterval() : $plant->getOutputMinInterval();
            $max = $isInput ? $plant->getInputMaxInterval() : $plant->getOutputMaxInterval();
            if ($max < $min) {
                $max = $min;
            }
            $currentInterval = rand($min, $max);

            // Requests are made against the running plan's Production Voucher; older plans get their draft now
            $plan = $runningInfo['plan'] ?? null;
            if ($plan && !$plan->production_voucher_id) {
                app(MachinePlanDraftVoucherService::class)->syncForPlan($plan);
                $plan->refresh();
            }

            $analysisRequest = ProductionAnalysisRequest::create([
                'request_no' => $requestNo,
                'request_date' => $now->toDateString(),
                'company_id' => $plant->company_id ?? 1,
                'company_location_id' => $plant->company_location_id,
                'arrival_location_id' => $plant->arrival_location_id,
                'plant_id' => $plant->id,
                'job_order_id' => null, // Job orders come from the Production Voucher
                'production_voucher_id' => $plan->production_voucher_id ?? null,
                'machine_plan_setting_id' => $plan->id ?? null,
                'type' => $type,
                'remarks' => 'Auto-generated for running plant (' . $plant->name . ')',
                'status' => 'pending',
                'is_auto_generated' => true,
                'auto_generated_at' => $now,
                'auto_scheduled_interval' => $currentInterval,
                // auth()->id() returns the username in this app; created_by needs the numeric id
                'created_by' => auth()->user()?->id ?? 1,
            ]);

            // Immediately schedule the NEXT target due time so subsequent auto requests continue without waiting on pending status
            $nextInterval = rand($min, $max);
            $nextDue = (clone $now)->addMinutes($nextInterval);
            $nextDueCol = $isInput ? 'next_input_analysis_at' : 'next_output_analysis_at';
            $plant->update([$nextDueCol => $nextDue]);

            DB::commit();

            Log::info("Auto Analysis Request created: {$requestNo} for plant {$plant->name} ({$type})");
            return $analysisRequest;
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("Failed to auto create Analysis Request for plant {$plant->id}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Hook invoked when an Input or Output Production Analysis is performed/stored.
     * Computes the next scheduled due time between min and max minutes from analysis time.
     */
    public function onAnalysisCompleted(ProductionAnalysis $analysis): ?Carbon
    {
        $plant = $analysis->plant;
        if (!$plant || !$plant->auto_analysis_enabled) {
            return null;
        }

        $type = $analysis->production_analysis_type; // 'input' or 'output'
        $isInput = ($type === 'input');

        $min = $isInput ? $plant->getInputMinInterval() : $plant->getOutputMinInterval();
        $max = $isInput ? $plant->getInputMaxInterval() : $plant->getOutputMaxInterval();
        if ($max < $min) {
            $max = $min;
        }

        // Determine analysis time (e.g. 2:00 PM)
        $timeStr = $analysis->items()->max('analysis_time') ?? now()->format('H:i:s');
        $analysisDate = $analysis->analysis_date ? $analysis->analysis_date->format('Y-m-d') : now()->format('Y-m-d');
        $analysisDateTime = Carbon::parse("{$analysisDate} {$timeStr}");

        $randomMinutes = rand($min, $max);
        $nextDue = (clone $analysisDateTime)->addMinutes($randomMinutes);

        $col = $isInput ? 'next_input_analysis_at' : 'next_output_analysis_at';
        $plant->update([$col => $nextDue]);

        Log::info("Scheduled next {$type} analysis for {$plant->name} at {$nextDue->toDateTimeString()} ({$randomMinutes}m after {$timeStr})");
        return $nextDue;
    }

    /**
     * Get live running & schedule diagnostic details for a plant (used in UI).
     */
    public function getPlantLiveStatus(Plant $plant, ?Carbon $atTime = null): array
    {
        $atTime = $atTime ?: now();
        $todayDate = $atTime->format('Y-m-d');

        $runningInfo = $this->isPlantRunning($plant, $atTime);
        $hasBreakdown = $this->hasActiveBreakdown($plant, $atTime);

        $pendingInput = ProductionAnalysisRequest::where('plant_id', $plant->id)
            ->where('type', ProductionAnalysisRequest::TYPE_INPUT)
            ->whereDate('request_date', $todayDate)
            ->where('status', 'pending')
            ->latest('id')
            ->first();

        $pendingInputCount = ProductionAnalysisRequest::where('plant_id', $plant->id)
            ->where('type', ProductionAnalysisRequest::TYPE_INPUT)
            ->whereDate('request_date', $todayDate)
            ->where('status', 'pending')
            ->count();

        $pendingOutput = ProductionAnalysisRequest::where('plant_id', $plant->id)
            ->where('type', ProductionAnalysisRequest::TYPE_OUTPUT)
            ->whereDate('request_date', $todayDate)
            ->where('status', 'pending')
            ->latest('id')
            ->first();

        $pendingOutputCount = ProductionAnalysisRequest::where('plant_id', $plant->id)
            ->where('type', ProductionAnalysisRequest::TYPE_OUTPUT)
            ->whereDate('request_date', $todayDate)
            ->where('status', 'pending')
            ->count();

        $statusBadge = 'idle';
        $statusLabel = 'Plan Idle';
        $badgeClass = 'badge-secondary';

        if ($hasBreakdown) {
            $statusBadge = 'breakdown';
            $statusLabel = 'Breakdown';
            $badgeClass = 'badge-danger';
        } elseif ($runningInfo['is_running']) {
            $statusBadge = 'running';
            $statusLabel = 'Running';
            $badgeClass = 'badge-success';
        } elseif ($runningInfo['plan']) {
            $statusBadge = 'scheduled';
            $statusLabel = 'Plan Scheduled';
            $badgeClass = 'badge-warning';
        }

        $isInputDueNow = false;
        if ($runningInfo['is_running'] && $plant->auto_analysis_enabled) {
            $inMax = $plant->getInputMaxInterval();
            $refInput = $this->getReferenceTimestamp($plant, ProductionAnalysisRequest::TYPE_INPUT, $runningInfo, $atTime);
            $elapsedIn = $refInput->diffInMinutes($atTime, false);
            if ($elapsedIn >= $inMax || ($plant->next_input_analysis_at && $plant->next_input_analysis_at->lte($atTime))) {
                $isInputDueNow = true;
            }
        }

        $isOutputDueNow = false;
        if ($runningInfo['is_running'] && $plant->auto_analysis_enabled) {
            $outMax = $plant->getOutputMaxInterval();
            $refOutput = $this->getReferenceTimestamp($plant, ProductionAnalysisRequest::TYPE_OUTPUT, $runningInfo, $atTime);
            $elapsedOut = $refOutput->diffInMinutes($atTime, false);
            if ($elapsedOut >= $outMax || ($plant->next_output_analysis_at && $plant->next_output_analysis_at->lte($atTime))) {
                $isOutputDueNow = true;
            }
        }

        // Not "due" while waiting for the last request to be completed
        if ($plant->require_last_completed) {
            $lastIn = $this->getLastRequest($plant, ProductionAnalysisRequest::TYPE_INPUT);
            if ($lastIn && $lastIn->status !== 'completed') {
                $isInputDueNow = false;
            }
            $lastOut = $this->getLastRequest($plant, ProductionAnalysisRequest::TYPE_OUTPUT);
            if ($lastOut && $lastOut->status !== 'completed') {
                $isOutputDueNow = false;
            }
        }

        return [
            'is_running' => $runningInfo['is_running'],
            'has_breakdown' => $hasBreakdown,
            'status_badge' => $statusBadge,
            'status_label' => $statusLabel,
            'badge_class' => $badgeClass,
            'active_slot' => $runningInfo['active_slot'],
            'reason' => $runningInfo['reason'],
            'pending_input' => $pendingInput,
            'pending_input_count' => $pendingInputCount,
            'pending_output' => $pendingOutput,
            'pending_output_count' => $pendingOutputCount,
            'next_input_due' => $plant->next_input_analysis_at ? $plant->next_input_analysis_at->format('h:i A') : null,
            'is_input_due_now' => $isInputDueNow,
            'next_output_due' => $plant->next_output_analysis_at ? $plant->next_output_analysis_at->format('h:i A') : null,
            'is_output_due_now' => $isOutputDueNow,
        ];
    }
}
