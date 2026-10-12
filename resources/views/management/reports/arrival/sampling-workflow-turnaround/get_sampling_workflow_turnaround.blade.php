@php
    $maxInitialQC = $maxInitialQC ?? ($tickets->map(fn($t) => $t->arrivalSamplingRequests->where('sampling_type', 'initial')->count())->max() ?: 1);
    $maxInnerQC = $maxInnerQC ?? ($tickets->map(fn($t) => $t->arrivalSamplingRequests->where('sampling_type', 'inner')->count())->max() ?: 1);

    if (!function_exists('getWorkflowMinutes')) {
        function getWorkflowMinutes($start, $end) {
            if (!$start || !$end) {
                return 0;
            }
            try {
                $startDate = \Carbon\Carbon::parse($start);
                $endDate = \Carbon\Carbon::parse($end);
                if ($endDate->lessThan($startDate)) {
                    return 0;
                }
                return (int) round($startDate->diffInMinutes($endDate));
            } catch (\Exception $e) {
                return 0;
            }
        }
    }

    if (!function_exists('formatWorkflowMinutes')) {
        function formatWorkflowMinutes($totalMinutes) {
            if ($totalMinutes < 0) {
                return '-';
            }
            $hours = intdiv($totalMinutes, 60);
            $minutes = $totalMinutes % 60;

            if ($hours > 0) {
                return "{$hours}h {$minutes}m";
            }
            return "{$minutes}m";
        }
    }

    if (!function_exists('formatWorkflowDuration')) {
        function formatWorkflowDuration($start, $end) {
            if (!$start || !$end) {
                return '-';
            }
            try {
                $startDate = \Carbon\Carbon::parse($start);
                $endDate = \Carbon\Carbon::parse($end);
                if ($endDate->lessThan($startDate)) {
                    return '-';
                }
                $totalMinutes = (int) round($startDate->diffInMinutes($endDate));
                $hours = intdiv($totalMinutes, 60);
                $minutes = $totalMinutes % 60;

                if ($hours > 0) {
                    return "{$hours}h {$minutes}m";
                }
                return "{$minutes}m";
            } catch (\Exception $e) {
                return '-';
            }
        }
    }
@endphp

<style>
    .sticky-table-scroller {
        max-height: 65vh;
        overflow-y: auto;
    }
    .sticky-table thead th {
        position: sticky;
        top: 0;
        z-index: 10;
        background: #f8f9fa !important;
        box-shadow: 0 2px 2px -1px rgba(0,0,0,0.1);
    }
    .sticky-table thead th.sticky-col-left {
        z-index: 15 !important;
    }
</style>

<x-sticky-table :items="$tickets" :leftSticky="1" :rightSticky="0" :emptyMessage="'No records found'" :pagination="false">
    @slot('head')
        <th>Ticket #</th>
        <th>Status</th>
        <th>Gate -> 1st QC</th>
        @for ($i = 1; $i <= $maxInitialQC; $i++)
            @php
                $isResampling = $tickets->contains(function ($t) use ($i) {
                    $item = $t->arrivalSamplingRequests->where('sampling_type', 'initial')->values()->get($i - 1);
                    return $item && (strtolower($item->is_re_sampling ?? '') === 'yes' || $item->is_re_sampling === 1 || $item->is_re_sampling === true);
                });
            @endphp
            <th>{{ getOrdinalSuffix($i) }} QC {{ $isResampling ? '(resampling) ' : '' }}-> {{ getOrdinalSuffix($i) }} Dec</th>
        @endfor
        <th>{{ $maxInitialQC > 1 ? 'Last Dec -> Loc' : '1st Dec -> Loc' }}</th>
        <th>Loc -> 1st Weight</th>
        @for ($j = 1; $j <= $maxInnerQC; $j++)
            @php
                $isInnerResampling = $tickets->contains(function ($t) use ($j) {
                    $item = $t->arrivalSamplingRequests->where('sampling_type', 'inner')->values()->get($j - 1);
                    return $item && (strtolower($item->is_re_sampling ?? '') === 'yes' || $item->is_re_sampling === 1 || $item->is_re_sampling === true);
                });
            @endphp
            <th>{{ $maxInnerQC > 1 ? getOrdinalSuffix($j) . ' ' : '' }}Inner Sample {{ $isInnerResampling ? '(resampling) ' : '' }}-> 2nd Dec</th>
        @endfor
        <th>2nd Weight -> Accounts</th>
        <th>Accounts -> HO Confirm</th>
        <th>Total Elapsed Time</th>
    @endslot

    @slot('body')
        @foreach ($tickets as $row)
            @php
                $initialSamplings = $row->arrivalSamplingRequests->where('sampling_type', 'initial')->values();
                $innerSamplings = $row->arrivalSamplingRequests->where('sampling_type', 'inner')->values();
                $firstInit = $initialSamplings->first();
                $lastInit = $initialSamplings->last();
                $firstInner = $innerSamplings->first();
                $lastInner = $innerSamplings->last();

                // Status calculation
                $isFullReject = ($row->first_qc_status == 'rejected' || $row->status == 'Reject Full');
                $isHalfReject = ($row->approvals?->bag_packing_approval == 'Half Approved' 
                    || ($row->approvals?->total_rejection > 0) 
                    || $row->document_approval_status == 'half_approved' 
                    || $row->status == 'Reject Half');

                $statusText = 'OK';
                if ($isFullReject) {
                    $statusText = 'Reject Full';
                } elseif ($isHalfReject) {
                    $statusText = 'Reject Half';
                }

                // 1. Gate Entry
                $gateTime = $row->created_at;

                // 2. 1st QC
                $firstQcTime = $firstInit?->result_posted_at;

                // Dynamic Initial QC calculations
                $qcDurations = [];
                $lastDecTime = null;

                for ($i = 0; $i < $maxInitialQC; $i++) {
                    $initItem = $initialSamplings->get($i);
                    $qcTime = $initItem?->result_posted_at;
                    $decTime = null;

                    if ($initItem) {
                        if ($i === 0 && $row->decision_making_time && $initialSamplings->count() <= 1) {
                            $decTime = $row->decision_making_time;
                        } elseif ($initItem->approved_at) {
                            $decTime = ($initItem->id === $row->lastInitialSampling?->id && $row->decision_making_time)
                                ? $row->decision_making_time
                                : $initItem->approved_at;
                        } elseif (in_array($initItem->approved_status, ['approved', 'rejected', 'resampling'])) {
                            $decTime = ($initItem->id === $row->lastInitialSampling?->id && $row->decision_making_time)
                                ? $row->decision_making_time
                                : ($initItem->decision_making_time ?? $initItem->updated_at);
                        } elseif ($i === 0 && $row->decision_making_time) {
                            $decTime = $row->decision_making_time;
                        }

                        if ($decTime) {
                            $lastDecTime = $decTime;
                        }
                    }

                    $qcDurations[] = [
                        'formatted' => formatWorkflowDuration($qcTime, $decTime),
                        'minutes' => getWorkflowMinutes($qcTime, $decTime),
                        'has_stage' => ($qcTime || $decTime),
                    ];
                }

                if (!$lastDecTime) {
                    $lastDecTime = $row->decision_making_time ?? ($firstInit?->approved_at ?? $firstInit?->updated_at);
                }

                // 4. Location (Loc)
                $locTime = (!$isFullReject && $row->unloadingLocation) ? $row->unloadingLocation->created_at : null;

                // 5. 1st Weight
                $firstWeightTime = (!$isFullReject && $row->firstWeighbridge) ? $row->firstWeighbridge->created_at : null;

                // 6. Dynamic Inner Sample calculations
                $innerDurations = [];
                for ($j = 0; $j < $maxInnerQC; $j++) {
                    $innerItem = $innerSamplings->get($j);
                    $innerQcTime = (!$isFullReject && $innerItem) ? $innerItem->result_posted_at : null;
                    $innerDecTime = null;

                    if (!$isFullReject && $innerItem) {
                        if ($innerItem->approved_at) {
                            $innerDecTime = $innerItem->approved_at;
                        } elseif (in_array($innerItem->approved_status, ['approved', 'rejected', 'resampling'])) {
                            $innerDecTime = $innerItem->decision_making_time ?? $innerItem->updated_at;
                        }
                    }

                    $innerDurations[] = [
                        'formatted' => $isFullReject ? '-' : formatWorkflowDuration($innerQcTime, $innerDecTime),
                        'minutes' => $isFullReject ? 0 : getWorkflowMinutes($innerQcTime, $innerDecTime),
                        'has_stage' => ($innerQcTime || $innerDecTime),
                    ];
                }

                // 7. 2nd Weight (second_weighbridges.created_at)
                $secondWeightTime = (!$isFullReject && $row->secondWeighbridge) ? $row->secondWeighbridge->created_at : null;

                // 8. Accounts (arrival_slips.created_at)
                $accountsTime = (!$isFullReject && $row->arrivalSlip) ? $row->arrivalSlip->created_at : null;

                // 9. HO Confirm (arrival_tickets.verify_at)
                $hoConfirmTime = (!$isFullReject) ? ($row->verify_at ?? $row->verified_at) : null;

                // Formatted Durations
                $durGateToQc = formatWorkflowDuration($gateTime, $firstQcTime);
                $durDecToLoc = $isFullReject ? '-' : formatWorkflowDuration($lastDecTime, $locTime);
                $durLocTo1stWeight = $isFullReject ? '-' : formatWorkflowDuration($locTime, $firstWeightTime);
                $dur2ndWeightToAccounts = $isFullReject ? '-' : formatWorkflowDuration($secondWeightTime, $accountsTime);
                $durAccountsToHo = $isFullReject ? '-' : formatWorkflowDuration($accountsTime, $hoConfirmTime);

                $primaryTotal = formatWorkflowDuration($gateTime, $hoConfirmTime);
                if ($primaryTotal !== '-') {
                    $durTotal = $primaryTotal;
                } else {
                    $hasAnyInitStage = false;
                    $qcMinutesTotal = 0;
                    foreach ($qcDurations as $qcDur) {
                        if ($qcDur['has_stage']) {
                            $hasAnyInitStage = true;
                        }
                        $qcMinutesTotal += $qcDur['minutes'];
                    }

                    $hasAnyInnerStage = false;
                    $innerMinutesTotal = 0;
                    foreach ($innerDurations as $inDur) {
                        if ($inDur['has_stage']) {
                            $hasAnyInnerStage = true;
                        }
                        $innerMinutesTotal += $inDur['minutes'];
                    }

                    $hasAnyStage = ($firstQcTime || $hasAnyInitStage || $locTime || $firstWeightTime || $hasAnyInnerStage || $secondWeightTime || $accountsTime);
                    if ($hasAnyStage) {
                        $stageMinutes = 
                            getWorkflowMinutes($gateTime, $firstQcTime)
                            + $qcMinutesTotal
                            + ($isFullReject ? 0 : getWorkflowMinutes($lastDecTime, $locTime))
                            + ($isFullReject ? 0 : getWorkflowMinutes($locTime, $firstWeightTime))
                            + ($isFullReject ? 0 : $innerMinutesTotal)
                            + ($isFullReject ? 0 : getWorkflowMinutes($secondWeightTime, $accountsTime))
                            + ($isFullReject ? 0 : getWorkflowMinutes($accountsTime, $hoConfirmTime));

                        $durTotal = formatWorkflowMinutes($stageMinutes);
                    } else {
                        $durTotal = '-';
                    }
                }
            @endphp
            <tr>
                <td>{{ $row->unique_no }}</td>
                <td><strong>{{ $statusText }}</strong></td>
                <td>{{ $durGateToQc }}</td>
                @foreach ($qcDurations as $qcDur)
                    <td>{{ $qcDur['formatted'] }}</td>
                @endforeach
                <td>{{ $durDecToLoc }}</td>
                <td>{{ $durLocTo1stWeight }}</td>
                @foreach ($innerDurations as $innerDur)
                    <td>{{ $innerDur['formatted'] }}</td>
                @endforeach
                <td>{{ $dur2ndWeightToAccounts }}</td>
                <td>{{ $durAccountsToHo }}</td>
                <td>{{ $durTotal }}</td>
            </tr>
            @endforeach
    @endslot
</x-sticky-table>
