@php
    $maxInitialQC = $maxInitialQC ?? ($tickets->map(fn($t) => $t->arrivalSamplingRequests->where('sampling_type', 'initial')->count())->max() ?: 1);
    $maxInnerQC = $maxInnerQC ?? ($tickets->map(fn($t) => $t->arrivalSamplingRequests->where('sampling_type', 'inner')->count())->max() ?: 0);
@endphp
<x-sticky-table :items="$tickets" :leftSticky="1" :rightSticky="1" :emptyMessage="'No records found'" :pagination="false">
    @slot('head')
        <th>Ticket #</th>
        <th>Gate Entry Time</th>
        <th>Entry By</th>
        <th>Loading Date</th>
        <th>Total Inner Samples</th>
        <th>Total Resamples</th>
        {{-- <th>Party Ref. No</th>
        <th>Yield</th> --}}

        {{-- Dynamic Initial QC & Tabaar Decision Columns --}}
        @for ($i = 1; $i <= $maxInitialQC; $i++)
            <th>{{ getOrdinalSuffix($i) }} QC Time</th>
            <th>{{ getOrdinalSuffix($i) }} QC By</th>
            <th>{{ getOrdinalSuffix($i) }} Tabaar Decision Time</th>
            <th>{{ getOrdinalSuffix($i) }} Tabaar Decision By</th>
        @endfor

        <th>Location Time</th>
        <th>Location By</th>
        <th>1st Weight Time</th>
        <th>1st Weight By</th>
        
        {{-- Dynamic Inner QC Sample Request & Sample Columns --}}
        @for ($j = 1; $j <= $maxInnerQC; $j++)
            <th>{{ getOrdinalSuffix($j) }} Inner QC Sample Request Time</th>
            <th>{{ getOrdinalSuffix($j) }} Inner QC Sample Request By</th>
            <th>{{ getOrdinalSuffix($j) }} Inner QC Sample Time</th>
            <th>{{ getOrdinalSuffix($j) }} Inner QC Sample By</th>
        @endfor

        {{-- Dynamic Inner Tabaar Decisions --}}
        @for ($j = 1; $j <= $maxInnerQC; $j++)
            <th>{{ getOrdinalSuffix($j + $maxInitialQC) }} Tabaar Decision Time</th>
            <th>{{ getOrdinalSuffix($j + $maxInitialQC) }} Tabaar Decision By</th>
        @endfor

        <th>Full Reject Time</th>
        <th>Full Reject By</th>
        <th>Half Reject Time</th>
        <th>Half Reject By</th>
        <th>Confirm Unloading Time</th>
        <th>Confirm Unloading By</th>
        <th>2nd Weight Time</th>
        <th>2nd Weight By</th>
        <th>Accounts Entry Time</th>
        <th>Accounts Entry By</th>
        <th>Bilty Return Time</th>
        <th>Bilty Return By</th>
        <th>HO Confirm Time</th>
        <th>HO Confirm By</th>
        <th>Admin Edit Time</th>
        <th>Admin Edit By</th>
        <th>Status</th>
        <th>Completion</th>

        <!-- Action Buttons -->
        <th>Final QC Report</th>
        <th>Bilty</th>
        <th>Loading Weight</th>
        <th>Arrival Slip</th>
    @endslot

    @slot('body')
        @foreach ($tickets as $row)
            @php
                $initialSamplings = $row->arrivalSamplingRequests->where('sampling_type', 'initial')->values();
                $innerSamplings = $row->arrivalSamplingRequests->where('sampling_type', 'inner')->values();
                $resamplesCount = $row->arrivalSamplingRequests->where('is_re_sampling', 'yes')->count();
                $firstInit = $initialSamplings->first();

                // 1st Tabaar Decision (used for full reject calculation)
                $firstTabaarTime = '';
                $firstTabaarBy = '';
                if ($row->decision_making_time && $initialSamplings->count() <= 1) {
                    $firstTabaarTime = formatDateTime($row->decision_making_time);
                    $firstTabaarBy = $row->decisionBy?->name ?? '';
                } elseif ($firstInit && in_array($firstInit->approved_status, ['approved', 'rejected', 'resampling'])) {
                    $firstTabaarTime = ($firstInit->id === $row->lastInitialSampling?->id && $row->decision_making_time)
                        ? formatDateTime($row->decision_making_time)
                        : formatDateTime($firstInit->updated_at);
                    $firstTabaarBy = $firstInit->approvedByUser?->name ?? ($row->decisionBy?->name ?? '');
                } elseif ($row->decision_making_time) {
                    $firstTabaarTime = formatDateTime($row->decision_making_time);
                    $firstTabaarBy = $row->decisionBy?->name ?? '';
                }

                // Second Tabaar Decision (from 1st inner sample, used for half reject calculation)
                $firstInner = $innerSamplings->first();
                $secondTabaarTime = '';
                $secondTabaarBy = '';
                if ($firstInner && in_array($firstInner->approved_status, ['approved', 'rejected', 'resampling'])) {
                    $secondTabaarTime = formatDateTime($firstInner->updated_at);
                    $secondTabaarBy = $firstInner->approvedByUser?->name ?? '';
                }

                // Rejections
                $isFullReject = ($row->first_qc_status == 'rejected' || $row->status == 'Reject Full');
                $isHalfReject = ($row->approvals?->bag_packing_approval == 'Half Approved' || ($row->approvals?->total_rejection > 0) || $row->document_approval_status == 'half_approved' || $row->status == 'Reject Half');

                $fullRejectTime = $isFullReject ? $firstTabaarTime : '';
                $fullRejectBy = $isFullReject ? $firstTabaarBy : '';

                $halfRejectTime = '';
                $halfRejectBy = '';
                if ($isHalfReject) {
                    if ($secondTabaarTime && $secondTabaarBy) {
                        $halfRejectTime = $secondTabaarTime;
                        $halfRejectBy = $secondTabaarBy;
                    } elseif ($row->approvals?->created_at) {
                        $halfRejectTime = formatDateTime($row->approvals->created_at);
                        $halfRejectBy = $row->approvals->creator?->name ?? '';
                    }
                }

                // Bilty Return
                $biltyReturnTime = '';
                $biltyReturnBy = '';
                if ($row->bilty_return_confirmation) {
                    $biltyReturnTime = formatDateTime($row->updated_at);
                }

                // HO Confirm
                $hoConfirmTime = '';
                $hoConfirmBy = '';
                if ($row->is_ticket_verified) {
                    $hoConfirmTime = formatDateTime($row->updated_at);
                    $hoConfirmBy = $row->ticketVerifiedBy?->name ?? 'Head Office';
                }

                // Admin Edit
                $adminEditTime = '';
                $adminEditBy = '';
                if ($row->latestAuditLog && !in_array($row->latestAuditLog->action, ['arrival_ticket_created', 'arrival_ticket_created_locked'])) {
                    $adminEditTime = formatDateTime($row->latestAuditLog->created_at);
                    $adminEditBy = $row->latestAuditLog->user?->name ?? '';
                }

                // Status & Completion
                $statusText = 'OK';
                if ($isFullReject) {
                    $statusText = 'Reject Full';
                } elseif ($isHalfReject) {
                    $statusText = 'Reject Half';
                }

                $isCompleted = ($row->freight_status == 'completed' || $row->arrival_slip_status == 'generated' || $row->status == 'completed' || $isFullReject);
            @endphp
            <tr>
                <td>#{{ $row->unique_no }}</td>
                <td>{{ formatDateTime($row->created_at) }}</td>
                <td>{{ $row->creator?->name ?? 'Main Gate' }}</td>
                <td>{{ formatDate($row->loading_date) }}</td>
                <td>{{ $innerSamplings->count() }}</td>
                <td>{{ $resamplesCount }}</td>
                {{-- <td>{{ $partyRefNo }}</td>
                <td>{{ $yield }}</td> --}}

                {{-- Dynamic Initial QC & Tabaar Decision Data --}}
                @for ($i = 0; $i < $maxInitialQC; $i++)
                    @php
                        $initItem = $initialSamplings->get($i);
                        $qcTime = $initItem ? formatDateTime($initItem->created_at) : '';
                        $qcBy = $initItem ? ($initItem->takenByUser?->name ?? '') : '';
                        $tabaarTime = '';
                        $tabaarBy = '';
                        if ($initItem) {
                            if ($i === 0 && $row->decision_making_time && $initialSamplings->count() <= 1) {
                                $tabaarTime = formatDateTime($row->decision_making_time);
                                $tabaarBy = $row->decisionBy?->name ?? ($initItem->approvedByUser?->name ?? '');
                            } elseif (in_array($initItem->approved_status, ['approved', 'rejected', 'resampling'])) {
                                $tabaarTime = ($initItem->id === $row->lastInitialSampling?->id && $row->decision_making_time)
                                    ? formatDateTime($row->decision_making_time)
                                    : formatDateTime($initItem->updated_at);
                                $tabaarBy = $initItem->approvedByUser?->name ?? ($row->decisionBy?->name ?? '');
                            } elseif ($i === 0 && $row->decision_making_time) {
                                $tabaarTime = formatDateTime($row->decision_making_time);
                                $tabaarBy = $row->decisionBy?->name ?? '';
                            }
                        }
                    @endphp
                    <td>{{ $qcTime }}</td>
                    <td>{{ $qcBy }}</td>
                    <td>{{ $tabaarTime }}</td>
                    <td>{{ $tabaarBy }}</td>
                @endfor

                <td>{{ formatDateTime($row->unloadingLocation?->created_at) }}</td>
                <td>{{ $row->unloadingLocation?->createdBy?->name ?? '' }}</td>
                <td>{{ formatDateTime($row->firstWeighbridge?->created_at) }}</td>
                <td>{{ $row->firstWeighbridge?->createdBy?->name ?? '' }}</td>

                {{-- Dynamic Inner QC Sample Request & Sample Data --}}
                @for ($j = 0; $j < $maxInnerQC; $j++)
                    @php
                        $innerItem = $innerSamplings->get($j);
                        $innerReqTime = $innerItem ? formatDateTime($innerItem->created_at) : '';
                        $innerReqBy = $innerItem ? ($innerItem->doneByUser?->name ?? ($innerItem->creator?->name ?? '')) : '';
                        $innerSampleTime = ($innerItem && ($innerItem->sample_taken_by || $innerItem->takenByUser)) ? formatDateTime($innerItem->updated_at) : '';
                        $innerSampleBy = $innerItem ? ($innerItem->takenByUser?->name ?? '') : '';
                    @endphp
                    <td>{{ $innerReqTime }}</td>
                    <td>{{ $innerReqBy }}</td>
                    <td>{{ $innerSampleTime }}</td>
                    <td>{{ $innerSampleBy }}</td>
                @endfor

                {{-- Dynamic Inner Tabaar Decisions Data --}}
                @for ($j = 0; $j < $maxInnerQC; $j++)
                    @php
                        $innerItem = $innerSamplings->get($j);
                        $innerTabaarTime = '';
                        $innerTabaarBy = '';
                        if ($innerItem && in_array($innerItem->approved_status, ['approved', 'rejected', 'resampling'])) {
                            $innerTabaarTime = formatDateTime($innerItem->updated_at);
                            $innerTabaarBy = $innerItem->approvedByUser?->name ?? '';
                        }
                    @endphp
                    <td>{{ $innerTabaarTime }}</td>
                    <td>{{ $innerTabaarBy }}</td>
                @endfor

                <td>{{ $fullRejectTime }}</td>
                <td>{{ $fullRejectBy }}</td>
                <td>{{ $halfRejectTime }}</td>
                <td>{{ $halfRejectBy }}</td>
                <td>{{ formatDateTime($row->approvals?->created_at) }}</td>
                <td>{{ $row->approvals?->creator?->name ?? '' }}</td>
                <td>{{ formatDateTime($row->secondWeighbridge?->created_at) }}</td>
                <td>{{ $row->secondWeighbridge?->createdBy?->name ?? '' }}</td>
                <td>{{ formatDateTime($row->arrivalSlip?->created_at) }}</td>
                <td>{{ $row->arrivalSlip?->creator?->name ?? '' }}</td>
                <td>{{ $biltyReturnTime }}</td>
                <td>{{ $biltyReturnBy }}</td>

                <td>{{ $hoConfirmTime }}</td>
                <td>{{ $hoConfirmBy }}</td>
                <td>{{ $adminEditTime }}</td>
                <td>{{ $adminEditBy }}</td>
                <td>{{ $statusText }}</td>
                <td>{{ $isCompleted ? 'Yes' : 'No' }}</td>
                <!-- Action Buttons -->
                <td>
                    <button class="info p-1 text-center mr-2 position-relative btn"
                        onclick="openModal(this,'{{ route('ticket.show', ['ticket' => $row->id, 'source' => 'contract']) }}','Ticket: {{ $row->unique_no }}', true, '90%')">
                        <a href="#"><i class="ft-eye font-medium-3"></i></a>
                    </button>
                </td>
                <td>
                    <button class="info p-1 text-center mr-2 position-relative btn" @disabled(
                        !$row->freight ||
                        !$row->freight?->bilty_document
                    )
                        @if ($row->freight && $row->freight?->bilty_document) onclick="openImageModal(['{{
                        asset($row->freight->bilty_document) }}'], 'Ticket: {{ $row->unique_no }}')" @endif>
                        <a href="#"><i class="ft-eye font-medium-3"></i></a>
                    </button>
                </td>
                <td>
                    <button class="info p-1 text-center mr-2 position-relative btn" @disabled(
                        !$row->freight ||
                        !$row->freight?->loading_weight_document
                    )
                        @if ($row->freight && $row->freight?->loading_weight_document) onclick="openImageModal(['{{
                        asset($row->freight->loading_weight_document) }}'], 'Ticket: {{ $row->unique_no }}')" @endif>
                        <a href="#"><i class="ft-eye font-medium-3"></i></a>
                    </button>
                </td>
                <td>
                    <button class="info p-1 text-center mr-2 position-relative btn" @disabled(!$row->arrivalSlip)
                        @if ($row->arrivalSlip) onclick="openModal(this,'{{ route('arrival-slip.edit', $row->arrivalSlip->id)
                        }}','Ticket: {{ $row->unique_no }}', true, '100%')" @endif>
                        <a href="#"><i class="ft-eye font-medium-3"></i></a>
                    </button>
                </td>
            </tr>
        @endforeach
    @endslot
</x-sticky-table>
