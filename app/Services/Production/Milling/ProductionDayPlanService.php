<?php

namespace App\Services\Production\Milling;

use App\Models\Master\ProductionMachine;
use App\Models\Production\MachinePlanSetting;
use App\Models\Production\PlantBreakdown;
use App\Models\Production\ProductionAnalysis;
use App\Models\Production\ProductionAnalysisRequest;
use App\Models\Production\ProductionMachineAnalysis;
use App\Models\Production\ProductionVoucher;
use App\Models\Production\ProductionVoucherMachineTime;
use Carbon\Carbon;

/**
 * A plant can run several Machine Plans on one day, each with its own Production Voucher.
 * This service keeps their machine times apart and links the day's analysis records
 * to the voucher whose machines were running at that time.
 */
class ProductionDayPlanService
{
    private const DAY = 1440;

    // ---------------------------------------------------------------------
    // Time helpers (minutes of the day)
    // ---------------------------------------------------------------------

    /** Slot as [start, end) minutes. Overnight slots end after 1440; a missing time means the rest of the day. */
    public static function slotRange(?string $start, ?string $end): array
    {
        $s = self::toMinutes($start);
        $e = self::toMinutes($end);

        if ($s === null && $e === null) {
            return [0, self::DAY];
        }
        if ($s === null) {
            return [0, $e ?: self::DAY];
        }
        if ($e === null) {
            return [$s, self::DAY];
        }
        if ($e <= $s) {
            $e += self::DAY;
        }

        return [$s, $e];
    }

    /** Same rule as the auto-request cron: an overnight slot also covers the early hours of its date. */
    public static function rangesOverlap(array $a, array $b): bool
    {
        foreach ([-self::DAY, 0, self::DAY] as $shift) {
            if ($a[0] < $b[1] + $shift && $b[0] + $shift < $a[1]) {
                return true;
            }
        }
        return false;
    }

    /** Minutes between a time and a slot (0 when the time is inside it). */
    public static function distanceToRange(int $minute, array $range): int
    {
        $best = PHP_INT_MAX;
        foreach ([0, self::DAY] as $shift) {
            $m = $minute + $shift;
            if ($m >= $range[0] && $m < $range[1]) {
                return 0;
            }
            $best = min($best, abs($m - $range[0]), abs($m - $range[1]));
        }
        return $best;
    }

    public static function formatRange(array $range): string
    {
        $fmt = fn ($m) => sprintf('%02d:%02d', intdiv($m % self::DAY, 60), $m % 60);
        return ($range[0] === 0 && $range[1] === self::DAY) ? 'Full Day' : $fmt($range[0]) . '–' . $fmt($range[1]);
    }

    public static function toMinutes(?string $time): ?int
    {
        if ($time === null || trim((string) $time) === '') {
            return null;
        }
        $parts = explode(':', trim((string) $time));
        return ((int) $parts[0]) * 60 + (int) ($parts[1] ?? 0);
    }

    /**
     * Enabled machine slots from a submitted Machine Plan or Production Voucher form.
     * On a plan, an enabled machine without times runs the full day (as the cron reads it).
     */
    public static function slotsFromRequest($request, bool $enabledWithoutTimeIsFullDay): array
    {
        $enabled = array_unique(array_map('intval', array_filter((array) $request->input('production_machine_id', []))));
        $starts = (array) $request->input('machine_start_time', []);
        $ends = (array) $request->input('machine_end_time', []);

        $slots = [];
        foreach ($enabled as $machineId) {
            $mStarts = (array) ($starts[$machineId] ?? []);
            $mEnds = (array) ($ends[$machineId] ?? []);
            $hasTime = false;

            foreach (array_unique(array_merge(array_keys($mStarts), array_keys($mEnds))) as $i) {
                $st = $mStarts[$i] ?? null;
                $en = $mEnds[$i] ?? null;
                if (empty($st) && empty($en)) {
                    continue;
                }
                $hasTime = true;
                $slots[] = ['machine_id' => $machineId, 'range' => self::slotRange($st, $en)];
            }

            if (!$hasTime && $enabledWithoutTimeIsFullDay) {
                $slots[] = ['machine_id' => $machineId, 'range' => [0, self::DAY]];
            }
        }

        return $slots;
    }

    // ---------------------------------------------------------------------
    // Machine time conflicts
    // ---------------------------------------------------------------------

    /**
     * Messages for slots that overlap each other (same machine) or any running slot of the plant's
     * other plans / vouchers on that date. Only one plan runs a plant at a time, so analyses taken
     * at a given time belong to exactly one voucher.
     */
    public function timeConflicts(int $plantId, $date, array $slots, ?int $excludePlanId = null, array $excludeVoucherIds = []): array
    {
        if (empty($slots) || !$date) {
            return [];
        }

        $date = Carbon::parse($date)->toDateString();
        $excludeVoucherIds = array_values(array_filter(array_map('intval', $excludeVoucherIds)));
        $messages = [];

        $names = ProductionMachine::whereIn('id', collect($slots)->pluck('machine_id'))->pluck('name', 'id');
        $nameOf = fn ($id) => $names[$id] ?? ('Machine #' . $id);

        // 1. Two slots of the same machine on this form
        foreach ($slots as $i => $a) {
            foreach ($slots as $j => $b) {
                if ($j <= $i || $a['machine_id'] !== $b['machine_id']) {
                    continue;
                }
                if (self::rangesOverlap($a['range'], $b['range'])) {
                    $messages[] = "Machine '{$nameOf($a['machine_id'])}': time slots " . self::formatRange($a['range'])
                        . ' and ' . self::formatRange($b['range']) . ' overlap.';
                }
            }
        }

        // 2. Running slots of the plant's other plans and vouchers that day
        $otherPlanIds = MachinePlanSetting::where('plant_id', $plantId)
            ->whereDate('date', $date)
            ->when($excludePlanId, fn ($q) => $q->where('id', '!=', $excludePlanId))
            ->pluck('id');

        $otherVoucherIds = ProductionVoucher::where('plant_id', $plantId)
            ->whereDate('prod_date', $date)
            ->when(!empty($excludeVoucherIds), fn ($q) => $q->whereNotIn('id', $excludeVoucherIds))
            ->pluck('id');

        if ($otherPlanIds->isEmpty() && $otherVoucherIds->isEmpty()) {
            return array_values(array_unique($messages));
        }

        $rows = ProductionVoucherMachineTime::with(['machine:id,name', 'productionVoucher:id,prod_no'])
            ->where('is_enabled', true)
            ->where(function ($q) use ($otherPlanIds, $otherVoucherIds) {
                $q->whereIn('machine_plan_setting_id', $otherPlanIds)
                    ->orWhereIn('production_voucher_id', $otherVoucherIds);
            })
            ->when($excludePlanId, function ($q) use ($excludePlanId) {
                $q->where(fn ($s) => $s->whereNull('machine_plan_setting_id')->orWhere('machine_plan_setting_id', '!=', $excludePlanId));
            })
            ->when(!empty($excludeVoucherIds), function ($q) use ($excludeVoucherIds) {
                $q->where(fn ($s) => $s->whereNull('production_voucher_id')->orWhereNotIn('production_voucher_id', $excludeVoucherIds));
            })
            ->get();

        foreach ($slots as $slot) {
            foreach ($rows as $row) {
                $other = self::slotRange($row->start_time, $row->end_time);
                if (!self::rangesOverlap($slot['range'], $other)) {
                    continue;
                }
                $owner = $row->productionVoucher?->prod_no
                    ?? ($row->machine_plan_setting_id ? 'Machine Plan #' . $row->machine_plan_setting_id : 'another voucher');
                $messages[] = "Machine '{$nameOf($slot['machine_id'])}' " . self::formatRange($slot['range'])
                    . " overlaps {$owner} (" . ($row->machine->name ?? 'machine') . ' ' . self::formatRange($other) . '). '
                    . 'A plant can run only one plan at a time.';
            }
        }

        return array_values(array_unique($messages));
    }

    // ---------------------------------------------------------------------
    // Plan choice for a Production Voucher
    // ---------------------------------------------------------------------

    /** A plan is free for a voucher when it has no voucher yet, an untouched draft, or this voucher. */
    public static function planIsAvailable(MachinePlanSetting $plan, ?int $voucherId = null): bool
    {
        if (!$plan->production_voucher_id || ($voucherId && $plan->production_voucher_id == $voucherId)) {
            return true;
        }
        $voucher = $plan->relationLoaded('productionVoucher') ? $plan->productionVoucher : $plan->productionVoucher()->first();
        return !$voucher || $voucher->is_draft;
    }

    /** Options for the voucher form's plan picker: id, label, available. */
    public static function planOptions($plans, ?int $voucherId = null): array
    {
        return collect($plans)->map(function ($p) use ($voucherId) {
            $available = self::planIsAvailable($p, $voucherId);
            $times = $p->items->where('is_enabled', true)
                ->map(fn ($i) => self::formatRange(self::slotRange($i->start_time, $i->end_time)))
                ->unique()->implode(', ');
            $voucherNo = $p->productionVoucher->prod_no ?? null;

            return [
                'id' => $p->id,
                'available' => $available,
                'label' => 'Plan #' . $p->id
                    . ($voucherNo ? ' – ' . $voucherNo : '')
                    . ($times ? ' (' . $times . ')' : '')
                    . ($available ? '' : ' – completed'),
            ];
        })->values()->all();
    }

    /**
     * The Machine Plan a voucher is saved against.
     * Returns ['plan' => ?MachinePlanSetting, 'error' => ?string, 'plans' => Collection].
     */
    public function planChoiceForVoucher(?int $plantId, $date, ?int $requestedPlanId = null, ?int $voucherId = null): array
    {
        $result = ['plan' => null, 'error' => null, 'plans' => collect()];
        if (!$plantId || !$date) {
            return $result;
        }

        $plans = MachinePlanSetting::with(['productionVoucher', 'items'])
            ->where('plant_id', $plantId)
            ->whereDate('date', Carbon::parse($date)->toDateString())
            ->orderBy('id')
            ->get();
        $result['plans'] = $plans;

        if ($plans->isEmpty()) {
            return $result;
        }

        if ($requestedPlanId) {
            $plan = $plans->firstWhere('id', $requestedPlanId);
            if (!$plan) {
                $result['error'] = 'The selected Machine Plan does not belong to this plant and date.';
            } elseif (!self::planIsAvailable($plan, $voucherId)) {
                $result['error'] = "Machine Plan #{$plan->id} already has a completed voucher ({$plan->productionVoucher->prod_no}).";
            } else {
                $result['plan'] = $plan;
            }
            return $result;
        }

        // The voucher's own plan, when it is still on this plant and date
        if ($voucherId && ($own = $plans->firstWhere('production_voucher_id', $voucherId))) {
            $result['plan'] = $own;
            return $result;
        }

        $available = $plans->filter(fn ($p) => self::planIsAvailable($p, $voucherId))->values();
        $formattedDate = Carbon::parse($date)->format('d-m-Y');

        if ($available->count() === 1) {
            $result['plan'] = $available->first();
        } elseif ($available->isEmpty()) {
            $result['error'] = "Every Machine Plan of this plant on {$formattedDate} already has a completed voucher. "
                . "Create a new plan (tick 'Create as New Voucher') in Machine Plan Setting first.";
        } else {
            $result['error'] = "This plant has {$available->count()} open Machine Plans on {$formattedDate}. Select which plan this voucher is for.";
        }

        return $result;
    }

    /**
     * Put a voucher on a plan. An untouched draft voucher of that plan is merged into it
     * (its analysis requests and analyses move over) and then removed.
     */
    public function attachVoucherToPlan(ProductionVoucher $voucher, MachinePlanSetting $plan): void
    {
        if ($plan->production_voucher_id == $voucher->id) {
            return;
        }

        $old = $plan->production_voucher_id ? ProductionVoucher::find($plan->production_voucher_id) : null;
        if ($old && !$old->is_draft) {
            throw new \RuntimeException("Machine Plan #{$plan->id} already belongs to voucher {$old->prod_no}.");
        }

        if ($old) {
            foreach ([ProductionAnalysisRequest::class, ProductionAnalysis::class, ProductionMachineAnalysis::class, PlantBreakdown::class, ProductionVoucherMachineTime::class] as $model) {
                $model::where('production_voucher_id', $old->id)->update(['production_voucher_id' => $voucher->id]);
            }
            $old->jobOrders()->detach();
            $old->delete();
        }

        $plan->update(['production_voucher_id' => $voucher->id]);
        ProductionVoucherMachineTime::where('machine_plan_setting_id', $plan->id)->update(['production_voucher_id' => $voucher->id]);
    }

    /**
     * Take a voucher off its plan (its plant, date or plan changed). The plan keeps its machine rows,
     * gets a fresh draft voucher, and the requests / analyses made against the plan go with it.
     */
    public function releaseVoucherFromPlan(ProductionVoucher $voucher, MachinePlanSetting $plan): void
    {
        $plan->update(['production_voucher_id' => null]);
        ProductionVoucherMachineTime::where('machine_plan_setting_id', $plan->id)->update(['production_voucher_id' => null]);

        $draft = app(MachinePlanDraftVoucherService::class)->syncForPlan($plan->fresh(['plant', 'items']));
        $draftId = $draft?->id;

        $requestIds = ProductionAnalysisRequest::where('production_voucher_id', $voucher->id)
            ->where('machine_plan_setting_id', $plan->id)
            ->pluck('id');

        if ($requestIds->isNotEmpty()) {
            ProductionAnalysisRequest::whereIn('id', $requestIds)->update(['production_voucher_id' => $draftId]);
            ProductionAnalysis::where('production_voucher_id', $voucher->id)->whereIn('analysis_request_id', $requestIds)->update(['production_voucher_id' => $draftId]);
            ProductionMachineAnalysis::where('production_voucher_id', $voucher->id)->whereIn('analysis_request_id', $requestIds)->update(['production_voucher_id' => $draftId]);
        }
    }

    /**
     * Records linked to the voucher but taken on another plant or date are unlinked,
     * then linked again to the voucher that fits them.
     */
    public function relinkMismatchedRecords(ProductionVoucher $voucher): void
    {
        $date = $voucher->prod_date ? Carbon::parse($voucher->prod_date)->toDateString() : null;
        $groups = [];

        $sets = [
            [ProductionAnalysisRequest::class, 'request_date'],
            [ProductionAnalysis::class, 'analysis_date'],
            [ProductionMachineAnalysis::class, 'analysis_date'],
            [PlantBreakdown::class, 'date'],
        ];

        foreach ($sets as [$model, $dateCol]) {
            $mismatched = $model::where('production_voucher_id', $voucher->id)
                ->where(function ($q) use ($voucher, $date, $dateCol) {
                    $q->whereNull('plant_id')
                        ->orWhere('plant_id', '!=', $voucher->plant_id);
                    if ($date) {
                        $q->orWhereDate($dateCol, '!=', $date);
                    }
                })
                ->get(['id', 'plant_id', $dateCol]);

            foreach ($mismatched as $row) {
                if ($row->plant_id && $row->$dateCol) {
                    $groups[$row->plant_id . '|' . Carbon::parse($row->$dateCol)->toDateString()] = true;
                }
            }

            if ($mismatched->isNotEmpty()) {
                $model::whereIn('id', $mismatched->pluck('id'))->update(['production_voucher_id' => null]);
            }
        }

        foreach (array_keys($groups) as $key) {
            [$plantId, $groupDate] = explode('|', $key);
            $this->linkFreeRecords((int) $plantId, $groupDate);
        }
    }

    // ---------------------------------------------------------------------
    // Linking the day's records to the right voucher
    // ---------------------------------------------------------------------

    /** Running windows of each voucher of a plant on a date: [voucher_id => [[start, end], ...]]. */
    public function voucherWindows(int $plantId, $date): array
    {
        $date = Carbon::parse($date)->toDateString();

        $plans = MachinePlanSetting::with('items')
            ->where('plant_id', $plantId)
            ->whereDate('date', $date)
            ->whereNotNull('production_voucher_id')
            ->get();

        $voucherIds = ProductionVoucher::where('plant_id', $plantId)
            ->whereDate('prod_date', $date)
            ->pluck('id')
            ->merge($plans->pluck('production_voucher_id'))
            ->unique()
            ->values();

        $windows = [];
        foreach ($voucherIds as $vid) {
            $windows[$vid] = [];
        }

        $rows = ProductionVoucherMachineTime::where('is_enabled', true)
            ->where(function ($q) use ($voucherIds, $plans) {
                $q->whereIn('production_voucher_id', $voucherIds)
                    ->orWhereIn('machine_plan_setting_id', $plans->pluck('id'));
            })
            ->get(['production_voucher_id', 'machine_plan_setting_id', 'start_time', 'end_time']);

        $planVoucher = $plans->pluck('production_voucher_id', 'id');
        foreach ($rows as $row) {
            $vid = $row->production_voucher_id ?: ($planVoucher[$row->machine_plan_setting_id] ?? null);
            if ($vid && array_key_exists($vid, $windows)) {
                $windows[$vid][] = self::slotRange($row->start_time, $row->end_time);
            }
        }

        return $windows;
    }

    /**
     * Voucher of a plant whose machines were running at that time on that date.
     * One voucher that day takes everything; between runs the nearest run wins.
     */
    public function voucherIdForPlantAt(?int $plantId, $date, ?string $time, ?array $windows = null): ?int
    {
        if (!$plantId || !$date) {
            return null;
        }

        $windows = $windows ?? $this->voucherWindows($plantId, $date);
        if (empty($windows)) {
            return null;
        }
        if (count($windows) === 1) {
            return (int) array_key_first($windows);
        }

        $minute = self::toMinutes($time);
        if ($minute === null) {
            return null;
        }

        $bestId = null;
        $bestDistance = PHP_INT_MAX;
        foreach ($windows as $vid => $ranges) {
            foreach ($ranges as $range) {
                $d = self::distanceToRange($minute, $range);
                if ($d < $bestDistance || ($d === $bestDistance && $vid > $bestId)) {
                    $bestDistance = $d;
                    $bestId = (int) $vid;
                }
            }
        }

        return $bestId;
    }

    /** Voucher for an analysis request: its plan's voucher first, otherwise by the time it was made. */
    public function voucherIdForRequest(ProductionAnalysisRequest $request, ?array $windows = null): ?int
    {
        if ($request->machine_plan_setting_id) {
            $planVoucherId = MachinePlanSetting::where('id', $request->machine_plan_setting_id)->value('production_voucher_id');
            if ($planVoucherId) {
                return (int) $planVoucherId;
            }
        }

        $time = ($request->auto_generated_at ?? $request->created_at)?->format('H:i');
        return $this->voucherIdForPlantAt($request->plant_id, $request->request_date, $time, $windows);
    }

    /** Voucher for an analysis: its request's voucher first, otherwise by its first reading time. */
    public function voucherIdForAnalysis($analysis, ?array $windows = null): ?int
    {
        if ($analysis->analysis_request_id) {
            $requestVoucherId = ProductionAnalysisRequest::where('id', $analysis->analysis_request_id)->value('production_voucher_id');
            if ($requestVoucherId) {
                return (int) $requestVoucherId;
            }
        }

        $time = $analysis->items()->min('analysis_time') ?: $analysis->created_at?->format('H:i');
        return $this->voucherIdForPlantAt($analysis->plant_id, $analysis->analysis_date, $time, $windows);
    }

    /**
     * After an analysis (input / output / machine) is saved: link it to the voucher that fits it,
     * mark its request completed, and send a request it moved away from back to pending.
     */
    public function syncAnalysis($analysis, ?int $previousRequestId = null): void
    {
        $analysis->update(['production_voucher_id' => $this->voucherIdForAnalysis($analysis)]);

        if ($analysis->analysis_request_id && $analysis->analysis_request_id != $previousRequestId) {
            ProductionAnalysisRequest::where('id', $analysis->analysis_request_id)->update(['status' => 'completed']);
        }

        if ($previousRequestId && $previousRequestId != $analysis->analysis_request_id) {
            ProductionAnalysisRequest::revertToPendingIfUnused($previousRequestId);
        }
    }

    /**
     * Link the unlinked requests, analyses and plant breakdown of a plant on a date to their vouchers.
     * Only records of that exact plant are taken (records without a plant are left alone).
     */
    public function linkFreeRecords(int $plantId, $date, ?int $breakdownVoucherId = null): void
    {
        $date = Carbon::parse($date)->toDateString();
        $windows = $this->voucherWindows($plantId, $date);
        if (empty($windows)) {
            return;
        }

        // Requests first: analyses follow their request's voucher
        ProductionAnalysisRequest::where('plant_id', $plantId)
            ->whereDate('request_date', $date)
            ->whereNull('production_voucher_id')
            ->get()
            ->each(function ($req) use ($windows) {
                if ($vid = $this->voucherIdForRequest($req, $windows)) {
                    $req->update(['production_voucher_id' => $vid]);
                }
            });

        foreach ([ProductionAnalysis::class, ProductionMachineAnalysis::class] as $model) {
            $model::where('plant_id', $plantId)
                ->whereDate('analysis_date', $date)
                ->whereNull('production_voucher_id')
                ->get()
                ->each(function ($analysis) use ($windows) {
                    if ($vid = $this->voucherIdForAnalysis($analysis, $windows)) {
                        $analysis->update(['production_voucher_id' => $vid]);
                    }
                });
        }

        // The plant breakdown is for the whole plant that day; it goes to the voucher being saved
        if ($breakdownVoucherId) {
            PlantBreakdown::where('plant_id', $plantId)
                ->whereDate('date', $date)
                ->whereNull('production_voucher_id')
                ->update(['production_voucher_id' => $breakdownVoucherId]);
        }
    }
}
