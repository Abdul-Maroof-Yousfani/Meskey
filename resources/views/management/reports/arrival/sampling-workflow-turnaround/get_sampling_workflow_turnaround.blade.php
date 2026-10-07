@php
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

<x-sticky-table :items="$tickets" :leftSticky="1" :rightSticky="0" :emptyMessage="'No records found'" :pagination="false">
    @slot('head')
        <th>Ticket #</th>
        <th>Status</th>
        <th>Gate -> 1st QC</th>
        <th>1st QC -> 1st Dec</th>
        <th>1st Dec -> Loc</th>
        <th>Loc -> 1st Weight</th>
        <th>Inner Sample -> 2nd Dec</th>
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

                // 3. 1st Decision (1st Dec)
                $firstDecTime = $firstInit?->approved_at;
                $lastDecTime = $lastInit?->approved_at ?? $firstDecTime;

                // 4. Location (Loc)
                $locTime = (!$isFullReject && $row->unloadingLocation) ? $row->unloadingLocation->created_at : null;

                // 5. 1st Weight
                $firstWeightTime = (!$isFullReject && $row->firstWeighbridge) ? $row->firstWeighbridge->created_at : null;

                // 6. Inner Sample & 2nd Dec (approved_at - result_posted_at for type inner)
                $innerSampleTime = (!$isFullReject && $firstInner) ? $firstInner->result_posted_at : null;
                $secondDecTime = (!$isFullReject && ($lastInner || $firstInner)) ? ($lastInner?->approved_at ?? $firstInner?->approved_at) : null;

                // 7. 2nd Weight (second_weighbridges.created_at)
                $secondWeightTime = (!$isFullReject && $row->secondWeighbridge) ? $row->secondWeighbridge->created_at : null;

                // 8. Accounts (arrival_slips.created_at)
                $accountsTime = (!$isFullReject && $row->arrivalSlip) ? $row->arrivalSlip->created_at : null;

                // 9. HO Confirm (arrival_tickets.verify_at)
                $hoConfirmTime = (!$isFullReject) ? ($row->verify_at ?? $row->verified_at) : null;

                // Formatted Durations
                $durGateToQc = formatWorkflowDuration($gateTime, $firstQcTime);
                $durQcToDec = formatWorkflowDuration($firstQcTime, $firstDecTime);
                $durDecToLoc = $isFullReject ? '-' : formatWorkflowDuration($lastDecTime, $locTime);
                $durLocTo1stWeight = $isFullReject ? '-' : formatWorkflowDuration($locTime, $firstWeightTime);
                $durInnerTo2ndDec = $isFullReject ? '-' : formatWorkflowDuration($innerSampleTime, $secondDecTime);
                $dur2ndWeightToAccounts = $isFullReject ? '-' : formatWorkflowDuration($secondWeightTime, $accountsTime);
                $durAccountsToHo = $isFullReject ? '-' : formatWorkflowDuration($accountsTime, $hoConfirmTime);
                $durTotal = formatWorkflowDuration($gateTime, $hoConfirmTime);
            @endphp
            <tr>
                <td>{{ $row->unique_no }}</td>
                <td><strong>{{ $statusText }}</strong></td>
                <td>{{ $durGateToQc }}</td>
                <td>{{ $durQcToDec }}</td>
                <td>{{ $durDecToLoc }}</td>
                <td>{{ $durLocTo1stWeight }}</td>
                <td>{{ $durInnerTo2ndDec }}</td>
                <td>{{ $dur2ndWeightToAccounts }}</td>
                <td>{{ $durAccountsToHo }}</td>
                <td>{{ $durTotal }}</td>
            </tr>
        @endforeach
    @endslot
</x-sticky-table>
