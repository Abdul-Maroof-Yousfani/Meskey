@php
    $innerSample = request('inner_sample', '1');
@endphp
<x-sticky-table :items="$tickets" :leftSticky="1" :rightSticky="1" :emptyMessage="'No records found'" :pagination="false">
    @slot('head')
        <th>Ticket #</th>
        <th>Gate Entry Time</th>
        <th>Entry By</th>
        <th>Loading Date</th>
        <th>Total Inner Samples</th>
        <th>Total Resamples</th>
        <th>Party Ref. No</th>
        <th>Yeild</th>
        <th>Location Time</th>
        <th>Location By</th>
        <th>1st QC Time</th>
        <th>1st QC By</th>
        <th>1st Tabaar Decision Time</th>
        <th>1st Tabaar Decision By</th>
        <th>1st Weight Time</th>
        <th>1st Weight By</th>
        
        @if (in_array($innerSample, ['1', '1st', 'all']))
            <th>1st Inner QC Sample Request Time</th>
            <th>1st Inner QC Sample Request By</th>
            <th>1st Inner QC Sample Time</th>
            <th>1st Inner QC Sample By</th>
        @endif

        @if (in_array($innerSample, ['2', '2nd', 'all']))
            <th>2nd Inner QC Sample Request Time</th>
            <th>2nd Inner QC Sample Request By</th>
            <th>2nd Inner QC Sample Time</th>
            <th>2nd Inner QC Sample By</th>
        @endif

        @if (in_array($innerSample, ['3', '3rd', 'all']))
            <th>3rd Inner QC Sample Request Time</th>
            <th>3rd Inner QC Sample Request By</th>
            <th>3rd Inner QC Sample Time</th>
            <th>3rd Inner QC Sample By</th>
        @endif

        @if (in_array($innerSample, ['2', '2nd', 'all']))
            <th>2nd Tabaar Decision Time</th>
            <th>2nd Tabaar Decision By</th>
        @endif

        @if (in_array($innerSample, ['3', '3rd', 'all']))
            <th>3rd Tabaar Decision Time</th>
            <th>3rd Tabaar Decision By</th>
        @endif

        @if (in_array($innerSample, ['4', '4th', 'all']))
            <th>4th Tabaar Decision Time</th>
            <th>4th Tabaar Decision By</th>
        @endif

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
        <th>Bilty</th>
        <th>Loading Weight</th>
        <th>Arrival Slip</th>
        <th>View Complete Details</th>
    @endslot

    @slot('body')
        @foreach ($tickets as $row)
            @php
                $initialQC = $row->initialSampling ?? $row->arrivalSamplingRequests->where('sampling_type', 'initial')->first();
                $innerSamplings = $row->arrivalSamplingRequests->where('sampling_type', 'inner')->values();
                $firstInner = $innerSamplings->get(0);
                $secondInner = $innerSamplings->get(1);
                $thirdInner = $innerSamplings->get(2);

                $resamplesCount = $row->arrivalSamplingRequests->where('is_re_sampling', 'yes')->count();
                $partyRefNo = $initialQC?->party_ref_no ?? ($row->bilty_no ?? '');
                $yield = $row->purchaseOrder?->yield ?? ($row->yield ?? '');

                // 1st Tabaar Decision
                $firstTabaarTime = '';
                $firstTabaarBy = '';
                if ($row->decision_making_time) {
                    $firstTabaarTime = formatDateTime($row->decision_making_time);
                    $firstTabaarBy = $row->decisionBy?->name ?? '';
                } elseif ($initialQC && in_array($initialQC->approved_status, ['approved', 'rejected'])) {
                    $firstTabaarTime = formatDateTime($initialQC->updated_at);
                    $firstTabaarBy = $initialQC->approvedByUser?->name ?? ($row->decisionBy?->name ?? '');
                }

                // 1st Inner QC sample time
                $firstInnerSampleTime = '';
                if ($firstInner && $firstInner->takenByUser) {
                    $firstInnerSampleTime = formatDateTime($firstInner->updated_at);
                }

                // 2nd Inner QC sample time
                $secondInnerSampleTime = '';
                if ($secondInner && $secondInner->takenByUser) {
                    $secondInnerSampleTime = formatDateTime($secondInner->updated_at);
                }

                // 3rd Inner QC sample time
                $thirdInnerSampleTime = '';
                if ($thirdInner && $thirdInner->takenByUser) {
                    $thirdInnerSampleTime = formatDateTime($thirdInner->updated_at);
                }

                // 2nd Tabaar Decision
                $secondTabaarTime = '';
                $secondTabaarBy = '';
                if ($firstInner && in_array($firstInner->approved_status, ['approved', 'rejected'])) {
                    $secondTabaarTime = formatDateTime($firstInner->updated_at);
                    $secondTabaarBy = $firstInner->approvedByUser?->name ?? '';
                }

                // 3rd Tabaar Decision
                $thirdTabaarTime = '';
                $thirdTabaarBy = '';
                if ($secondInner && in_array($secondInner->approved_status, ['approved', 'rejected'])) {
                    $thirdTabaarTime = formatDateTime($secondInner->updated_at);
                    $thirdTabaarBy = $secondInner->approvedByUser?->name ?? '';
                }

                // 4th Tabaar Decision
                $fourthTabaarTime = '';
                $fourthTabaarBy = '';
                if ($thirdInner && in_array($thirdInner->approved_status, ['approved', 'rejected'])) {
                    $fourthTabaarTime = formatDateTime($thirdInner->updated_at);
                    $fourthTabaarBy = $thirdInner->approvedByUser?->name ?? '';
                }

                // Rejections
                $isFullReject = ($row->first_qc_status == 'rejected' || $row->status == 'Reject Full');
                $isHalfReject = ($row->approvals?->bag_packing_approval == 'Half Approved' || ($row->approvals?->total_rejection > 0) || $row->document_approval_status == 'half_approved' || $row->status == 'Reject Half');

                $fullRejectTime = '';
                $fullRejectBy = '';
                if ($isFullReject) {
                    $fullRejectTime = $firstTabaarTime;
                    $fullRejectBy = $firstTabaarBy;
                }

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
                <td>{{ $partyRefNo }}</td>
                <td>{{ $yield }}</td>
                <td>{{ formatDateTime($row->unloadingLocation?->created_at) }}</td>
                <td>{{ $row->unloadingLocation?->createdBy?->name ?? 'N/A' }}</td>
                <td>{{ formatDateTime($initialQC?->created_at) }}</td>
                <td>{{ $initialQC?->takenByUser?->name ?? 'N/A' }}</td>
                <td>{{ $firstTabaarTime }}</td>
                <td>{{ $firstTabaarBy }}</td>
                <td>{{ formatDateTime($row->firstWeighbridge?->created_at) }}</td>
                <td>{{ $row->firstWeighbridge?->createdBy?->name ?? 'N/A' }}</td>

                @if (in_array($innerSample, ['1', '1st', 'all']))
                    <td>{{ formatDateTime($firstInner?->created_at) }}</td>
                    <td>{{ $firstInner?->doneByUser?->name ?? ($firstInner?->creator?->name ?? 'N/A') }}</td>
                    <td>{{ $firstInnerSampleTime }}</td>
                    <td>{{ $firstInner?->takenByUser?->name ?? 'N/A' }}</td>
                @endif

                @if (in_array($innerSample, ['2', '2nd', 'all']))
                    <td>{{ formatDateTime($secondInner?->created_at) }}</td>
                    <td>{{ $secondInner?->doneByUser?->name ?? 'N/A' }}</td>
                    <td>{{ $secondInnerSampleTime }}</td>
                    <td>{{ $secondInner?->takenByUser?->name ?? 'N/A' }}</td>
                @endif

                @if (in_array($innerSample, ['3', '3rd', 'all']))
                    <td>{{ formatDateTime($thirdInner?->created_at) }}</td>
                    <td>{{ $thirdInner?->doneByUser?->name ?? 'N/A' }}</td>
                    <td>{{ $thirdInnerSampleTime }}</td>
                    <td>{{ $thirdInner?->takenByUser?->name ?? 'N/A' }}</td>
                @endif

                @if (in_array($innerSample, ['1', '1st', 'all']))
                    <td>{{ $secondTabaarTime }}</td>
                    <td>{{ $secondTabaarBy }}</td>
                @endif

                @if (in_array($innerSample, ['2', '2nd', 'all']))
                    <td>{{ $thirdTabaarTime }}</td>
                    <td>{{ $thirdTabaarBy }}</td>
                @endif

                @if (in_array($innerSample, ['3', '3rd', 'all']))
                    <td>{{ $fourthTabaarTime }}</td>
                    <td>{{ $fourthTabaarBy }}</td>
                @endif

                <td>{{ $fullRejectTime }}</td>
                <td>{{ $fullRejectBy }}</td>
                <td>{{ $halfRejectTime }}</td>
                <td>{{ $halfRejectBy }}</td>
                <td>{{ formatDateTime($row->approvals?->created_at) }}</td>
                <td>{{ $row->approvals?->creator?->name ?? 'N/A' }}</td>
                <td>{{ formatDateTime($row->secondWeighbridge?->created_at) }}</td>
                <td>{{ $row->secondWeighbridge?->createdBy?->name ?? 'N/A' }}</td>
                <td>{{ formatDateTime($row->arrivalSlip?->created_at) }}</td>
                <td>{{ $row->arrivalSlip?->creator?->name ?? 'N/A' }}</td>
                <td>{{ $biltyReturnTime }}</td>
                <td>{{ $biltyReturnBy }}</td>
                <td>{{ $hoConfirmTime }}</td>
                <td>{{ $hoConfirmBy }}</td>
                <td></td>
                <td></td>
                <td>{{ $statusText }}</td>
                <td>{{ $isCompleted ? 'Yes' : 'No' }}</td>
                <!-- Action Buttons -->
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
                <td>
                    <button class="info p-1 text-center mr-2 position-relative btn"
                        onclick="openModal(this,'{{ route('ticket.show', ['ticket' => $row->id, 'source' => 'contract']) }}','Ticket: {{ $row->unique_no }}', true, '90%')">
                        <a href="#"><i class="ft-eye font-medium-3"></i></a>
                    </button>
                </td>
            </tr>
        @endforeach
    @endslot
</x-sticky-table>
