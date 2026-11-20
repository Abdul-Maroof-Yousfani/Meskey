{{--
    Freight Payment Request list - MOBILE card (sirf < 768px; desktop par table).
    getList.blade.php isay $tickets ke loop me include karta hai:
        @include(..., ['ticket' => $ticket, 'lastPaymentApproval' => $lastStatuses[$mId]])
    Styling: mobile-ui.css section 6 (tkc-*); ⋯ sheet: public/management/assets/js/mobile-card-sheet.js
--}}
@php
    $m = $ticket['model'];
    $mId = $m->id ?? null;
    $po = $ticket['purchaseOrder'] ?? null;
    $contractNo = $po->contract_no ?? 'N/A';
    $uniqueNo = $ticket['unique_no'] ?? 'N/A';
    $supplierName = $po->supplier?->name ?? ($m->accountsOf?->name ?? 'N/A');
    $productName = $po->qcProduct?->name ?? ($ticket['qcProduct']?->name ?? 'N/A');
    $locationName = $m->location->name ?? 'N/A';
    $truckNo = $m->truck_no ?? 'N/A';
    $biltyNo = $m->bilty_no ?? 'N/A';

    $isThadda = ($ticket['type'] ?? '') == 'thadda';
    $typeLabel = $isThadda ? 'Thadda' : 'Pohanch';

    $loadingDate = $isThadda
        ? ($ticket['purchaseFreight'] ? \Carbon\Carbon::parse($ticket['purchaseFreight']->loading_date)->format('d M Y') : 'N/A')
        : ($ticket['freight'] ? \Carbon\Carbon::parse($ticket['freight']->first()->loading_date ?? '')->format('d M Y') : 'N/A');

    $cv = $ticket['calculated_values'] ?? [];
    $totalAmount = $cv['total_amount'] ?? 0;
    $paymentSum = $cv['total_payment_sum'] ?? 0;
    $freightSum = $cv['total_freight_sum'] ?? 0;
    $noRequests = ($paymentSum == 0 && $freightSum == 0);

    $reqStatus = strtolower($ticket['requestStatus'] ?? 'pending');
    $isApproved = ($reqStatus === 'approved');

    $last = $lastPaymentApproval->status ?? null;
    $lastClass = match ($last) {
        'approved' => 'is-approved',
        'pending' => 'is-pending',
        'rejected' => 'is-rejected',
        default => '',
    };

    $hasPrData = $m->paymentRequestData?->isNotEmpty();
    $paidBySupplier = $hasPrData && $m->paymentRequestData->contains('is_paid_by_supplier', true);

    $editUrl = route('raw-material.freight-request.edit', $mId);
    $auditLogsUrl = route('raw-material.freight-request.audit-logs', $mId);
@endphp

<div class="tkc">
    {{-- card ka main hissa: tap = Manage Freight Payment Request --}}
    <div class="tkc-main" role="button" tabindex="0"
        onclick="openModal(this,'{{ $editUrl }}','Manage Freight Payment Request', false, '80%')">

        <div class="tkc-top">
            <div class="tkc-no">#{{ $uniqueNo }}</div>
            @if ($last)
                <span class="tkc-qc {{ $lastClass }}" title="Last Payment Status">{{ ucfirst($last) }}</span>
            @endif
        </div>

        @if (!empty($cv['created_at']))
            <div class="tkc-date">
                <i class="ft-clock"></i>
                {{ \Carbon\Carbon::parse($cv['created_at'])->format('d M Y, h:i A') }}
            </div>
        @endif

        <div class="tkc-commodity">{{ $productName }}</div>

        <div class="tkc-tags">
            <span class="tkc-tag">Contract #{{ $contractNo }}</span>
            <span class="tkc-tag {{ $isThadda ? 'is-inner' : '' }}">{{ $typeLabel }}</span>
            @if ($noRequests)
                <span class="tkc-tag">No requests generated yet</span>
            @else
                @if ($paymentSum > 0)
                    <span class="tkc-tag is-inner">Payment: {{ number_format($paymentSum, 2) }}</span>
                @endif
                @if ($freightSum > 0)
                    <span class="tkc-tag is-warn">Freight: {{ number_format($freightSum, 2) }}</span>
                @endif
            @endif
            @if ($hasPrData)
                <span class="tkc-tag {{ $paidBySupplier ? 'is-inner' : '' }}">
                    {{ $paidBySupplier ? 'Paid by Supplier' : 'Not Paid by Supplier' }}
                </span>
            @endif
        </div>

        <div class="tkc-grid">
            <div>
                <span class="tkc-label">Supplier</span>
                <span class="tkc-val">{{ $supplierName }}</span>
            </div>
            <div>
                <span class="tkc-label">Location</span>
                <span class="tkc-val">{{ $locationName }}</span>
            </div>
            <div>
                <span class="tkc-label">Loading Date</span>
                <span class="tkc-val">{{ $loadingDate }}</span>
            </div>
            <div>
                <span class="tkc-label">Total Amount</span>
                <span class="tkc-val">{{ $totalAmount }}</span>
            </div>
            <div>
                <span class="tkc-label">Approval Status</span>
                <span class="tkc-val">{{ ucfirst($reqStatus) }}</span>
            </div>
            <div>
                <span class="tkc-label">Type</span>
                <span class="tkc-val">{{ $typeLabel }}</span>
            </div>
        </div>

        <div class="tkc-chips">
            <span class="tkc-chip"><i class="ft-truck"></i>{{ $truckNo }}</span>
            <span class="tkc-chip"><i class="ft-file-text"></i>{{ $biltyNo }}</span>
        </div>
    </div>

    {{-- Manage bahar (sirf icon); Audit Logs ⋯ sheet me --}}
    <div class="tkc-actions">
        <button type="button" class="tkc-btn"
            onclick="openModal(this,'{{ $editUrl }}','Manage Freight Payment Request', false, '80%')"
            title="Manage Freight Payment Request" aria-label="Manage Freight Payment Request">
            <i class="ft-edit"></i>
        </button>
        <button type="button" class="tkc-more" aria-label="More actions" title="More actions"
            data-title="#{{ $uniqueNo }}">
            <i class="ft-more-horizontal"></i>
        </button>
    </div>

    <template class="tkc-extra">
        <a href="javascript:;" class="tkc-sheet-item"
            onclick="openModal(this,'{{ $auditLogsUrl }}','Audit Logs: Ticket #{{ $uniqueNo }}', false, '55%')">
            <i class="fa fa-history"></i><span>Audit Logs</span>
        </a>
    </template>
</div>
