{{--
    Ticket Contracts (Contract Linking) list - MOBILE card (sirf < 768px; desktop par sticky table).
    getList.blade.php isay $tickets ke loop me include karta hai:
        @include('management.procurement.raw_material.ticket_contracts.partials.mobile-card', ['row' => $row])
    Styling: mobile-ui.css section 6 (tkc-*); ⋯ sheet: public/management/assets/js/mobile-card-sheet.js
--}}
@php
    $tabaar = formatDeductionsAsString(getTicketDeductions($row));
    $tabaar = $tabaar == '' ? 'N/A' : $tabaar;

    $isRejected = ($row->first_qc_status == 'rejected');
    $isLinked = !is_null($row->arrival_purchase_order_id);
    $isVerified = ($row->is_ticket_verified == 1);

    if ($isRejected) {
        $statusText = 'Rejected';
        $statusClass = 'is-rejected';
        $tintClass = 'tkc-tint-red';
    } elseif (!$isLinked) {
        $statusText = 'Pending';
        $statusClass = 'is-pending';
        $tintClass = 'tkc-tint-orange';
    } elseif ($isLinked && !$isVerified) {
        $statusText = 'Not Verified';
        $statusClass = 'is-warn';
        $tintClass = 'tkc-tint-orange';
    } else {
        $statusText = 'Verified';
        $statusClass = 'is-approved';
        $tintClass = '';
    }

    $docStatus = 'RF';
    if (isset($row->saudaType->id)) {
        if ($row->saudaType->id == 1) {
            if ($row->document_approval_status == 'fully_approved') {
                $docStatus = 'OK';
            } elseif ($row->document_approval_status == 'half_approved') {
                $docStatus = 'P-RH';
            } else {
                $docStatus = 'RF';
            }
        } elseif ($row->saudaType->id == 2) {
            if ($row->document_approval_status == 'fully_approved') {
                $docStatus = 'TS';
            } elseif ($row->document_approval_status == 'half_approved') {
                $docStatus = 'TS-RH';
            } else {
                $docStatus = 'RF';
            }
        }
    }
    if ($isRejected) {
        $docStatus = 'RF';
    }

    $isDraftOrNoPo = (!$row->purchaseOrder || ($row->purchaseOrder->status ?? '') == 'draft');
    $primaryActionUrl = $isDraftOrNoPo
        ? route('raw-material.ticket-contracts.create', ['ticket_id' => $row->id])
        : route('raw-material.ticket-contracts.edit', $row->id);
    $primaryActionIcon = $isDraftOrNoPo ? 'ft-edit' : 'ft-eye';
    $primaryActionTitle = $isDraftOrNoPo ? 'Link Contract' : 'View Ticket Contract';

    $truckNo = $row->truck_no ?? ($row->purchaseOrder->truck_no ?? null);
    $biltyNo = $row->bilty_no ?? ($row->purchaseOrder->bilty_no ?? null);
    $commodityName = $row->qcProduct->name ?? ($row->product->name ?? 'N/A');
    $contractNo = $row->purchaseOrder->contract_no ?? null;
    $grnNo = $row->grn_unique_no ?? null;
    $saudaTypeName = $row->saudaType->name ?? null;

    $hasBiltyDoc = !empty($row->freight?->bilty_document);
    $hasWeightDoc = !empty($row->freight?->loading_weight_document);
    $hasArrivalSlip = !empty($row->arrivalSlip);
@endphp

<div class="tkc {{ $tintClass }}">
    {{-- Card Main: Tap to open link/edit --}}
    <div class="tkc-main" role="button" tabindex="0" onclick="window.location.href='{{ $primaryActionUrl }}'">
        <div class="tkc-top">
            <div class="tkc-no">#{{ $row->unique_no ?? 'N/A' }}</div>
            <span class="tkc-qc {{ $statusClass }}">{{ $statusText }}</span>
        </div>

        <div class="tkc-date">
            <i class="ft-clock"></i>
            {{ $row->created_at ? $row->created_at->format('d M Y, h:i A') : 'N/A' }}
        </div>

        <div class="tkc-commodity">{{ $commodityName }}</div>

        <div class="tkc-tags">
            @if ($contractNo)
                <span class="tkc-tag">Contract #{{ $contractNo }}</span>
            @else
                <span class="tkc-tag is-warn">Unlinked</span>
            @endif

            @if ($grnNo)
                <span class="tkc-tag is-inner">GRN #{{ $grnNo }}</span>
            @endif

            @if ($saudaTypeName)
                <span class="tkc-tag {{ $saudaTypeName == 'Thadda' ? 'is-warn' : 'is-inner' }}">{{ $saudaTypeName }}</span>
            @endif

            <span class="tkc-tag is-auto">Doc: {{ $docStatus }}</span>
        </div>

        <div class="tkc-grid">
            <div>
                <span class="tkc-label">Accounts Of</span>
                <span class="tkc-val">{{ $row->accounts_of_name ?? 'N/A' }}</span>
            </div>
            <div>
                <span class="tkc-label">Broker</span>
                <span class="tkc-val">{{ $row->broker_name ?? ($row->purchaseOrder->broker_one_name ?? 'N/A') }}</span>
            </div>
            <div>
                <span class="tkc-label">Miller</span>
                <span class="tkc-val">{{ $row->miller->name ?? 'N/A' }}</span>
            </div>
            <div>
                <span class="tkc-label">Station</span>
                <span class="tkc-val">{{ $row->station->name ?? 'N/A' }}</span>
            </div>
            <div>
                <span class="tkc-label">Loaded Wt</span>
                <span class="tkc-val">{{ $row->net_weight ?? 'N/A' }}</span>
            </div>
            <div>
                <span class="tkc-label">Arrived Wt</span>
                <span class="tkc-val">{{ $row->arrived_net_weight ?? 0 }}</span>
            </div>
            @if ($tabaar !== 'N/A')
                <div style="grid-column: span 2;">
                    <span class="tkc-label">Tabaar</span>
                    <span class="tkc-val">{{ $tabaar }}</span>
                </div>
            @endif
        </div>

        @if ($truckNo || $biltyNo)
            <div class="tkc-chips">
                @if ($truckNo)
                    <span class="tkc-chip"><i class="ft-truck"></i>{{ $truckNo }}</span>
                @endif
                @if ($biltyNo)
                    <span class="tkc-chip"><i class="ft-file-text"></i>{{ $biltyNo }}</span>
                @endif
            </div>
        @endif
    </div>

    {{-- Actions bar --}}
    <div class="tkc-actions">
        <a href="{{ $primaryActionUrl }}" class="tkc-btn action-link" title="{{ $primaryActionTitle }}" aria-label="{{ $primaryActionTitle }}">
            <i class="{{ $primaryActionIcon }}"></i>
        </a>
        <button type="button" class="tkc-btn"
            onclick="openModal(this,'{{ route('ticket.show', ['ticket' => $row->id, 'source' => 'contract']) }}','Ticket: {{ $row->unique_no }}', true, '90%')"
            title="Final QC Report" aria-label="Final QC Report">
            <i class="ft-clipboard"></i>
        </button>
        <button type="button" class="tkc-more" aria-label="More actions" title="More actions" data-title="#{{ $row->unique_no }}">
            <i class="ft-more-horizontal"></i>
        </button>
    </div>

    {{-- ⋯ bottom sheet actions --}}
    <template class="tkc-extra">
        <a href="javascript:;" class="tkc-sheet-item"
            onclick="openModal(this,'{{ route('ticket.show', ['ticket' => $row->id, 'source' => 'contract']) }}','Ticket: {{ $row->unique_no }}', true, '90%')">
            <i class="ft-clipboard"></i><span>Final QC Report</span>
        </a>
        @if ($hasBiltyDoc)
            <a href="javascript:;" class="tkc-sheet-item"
                onclick="openImageModal(['{{ asset($row->freight->bilty_document) }}'], 'Ticket: {{ $row->unique_no }}')">
                <i class="ft-image"></i><span>Bilty Document</span>
            </a>
        @endif
        @if ($hasWeightDoc)
            <a href="javascript:;" class="tkc-sheet-item"
                onclick="openImageModal(['{{ asset($row->freight->loading_weight_document) }}'], 'Ticket: {{ $row->unique_no }}')">
                <i class="ft-file-text"></i><span>Loading Weight Slip</span>
            </a>
        @endif
        @if ($hasArrivalSlip)
            <a href="javascript:;" class="tkc-sheet-item"
                onclick="openModal(this,'{{ route('arrival-slip.edit', $row->arrivalSlip->id) }}','Ticket: {{ $row->unique_no }}', true, '100%')">
                <i class="ft-file"></i><span>Arrival Slip</span>
            </a>
        @endif
    </template>
</div>
