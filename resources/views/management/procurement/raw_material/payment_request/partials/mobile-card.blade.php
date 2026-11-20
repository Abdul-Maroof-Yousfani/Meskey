{{--
    Payment Request (Thadda) list - MOBILE card (sirf < 768px; desktop par table).
    getList.blade.php isay $tickets ke loop me include karta hai:
        @include(..., ['ticket' => $ticket, 'lastPaymentApproval' => $lastStatuses[$ticket->id]])
    ($lastStatuses getList upar EK dafa nikalta hai; table + card dono wahi use karte hain, queries double nahi)
    Styling: mobile-ui.css section 6 (tkc-*); ⋯ sheet: public/management/assets/js/mobile-card-sheet.js
--}}
@php
    $cv = $ticket->calculated_values;
    $po = $ticket->purchaseOrder;
    $pf = $ticket->purchaseFreight;
    $noRequests = ($cv['total_payment_sum'] ?? 0) == 0 && ($cv['total_freight_sum'] ?? 0) == 0;
    $editUrl = route('raw-material.payment-request.edit', $ticket->id);

    $last = $lastPaymentApproval->status ?? null;
    $lastClass = match ($last) {
        'approved' => 'is-approved',
        'pending' => 'is-pending',
        'rejected' => 'is-rejected',
        default => '',
    };
@endphp

<div class="tkc">
    {{-- card ka main hissa: tap = Manage Payment Request --}}
    <div class="tkc-main" role="button" tabindex="0"
        onclick="openModal(this,'{{ $editUrl }}','Manage Payment Request')">

        <div class="tkc-top">
            <div class="tkc-no">#{{ $ticket->unique_no ?? 'N/A' }}</div>
            @if ($last)
                <span class="tkc-qc {{ $lastClass }}" title="Last Payment Status">{{ ucfirst($last) }}</span>
            @endif
        </div>

        <div class="tkc-date">
            <i class="ft-clock"></i>
            {{ \Carbon\Carbon::parse($cv['created_at'])->format('d M Y, h:i A') }}
        </div>

        <div class="tkc-commodity">{{ $po->qcProduct->name ?? 'N/A' }}</div>

        <div class="tkc-tags">
            <span class="tkc-tag">Contract #{{ $po->contract_no ?? 'N/A' }}</span>
            @if ($noRequests)
                <span class="tkc-tag">No requests generated yet</span>
            @else
                @if (($cv['total_payment_sum'] ?? 0) > 0)
                    <span class="tkc-tag is-inner">Payment: {{ number_format($cv['total_payment_sum'], 2) }}</span>
                @endif
                @if (($cv['total_freight_sum'] ?? 0) > 0)
                    <span class="tkc-tag is-warn">Freight: {{ number_format($cv['total_freight_sum'], 2) }}</span>
                @endif
            @endif
        </div>

        <div class="tkc-grid">
            <div>
                <span class="tkc-label">Supplier</span>
                <span class="tkc-val">{{ $po->supplier->name ?? 'N/A' }}</span>
            </div>
            <div>
                <span class="tkc-label">Location</span>
                <span class="tkc-val">{{ $po->location->name ?? 'N/A' }}</span>
            </div>
            <div>
                <span class="tkc-label">Loading Date</span>
                <span class="tkc-val">{{ $pf ? \Carbon\Carbon::parse($pf->loading_date)->format('d M Y') : 'N/A' }}</span>
            </div>
            @unless ($noRequests)
                <div>
                    <span class="tkc-label">Total Amount</span>
                    <span class="tkc-val">{{ $cv['total_amount'] ?? 0 }}</span>
                </div>
                <div>
                    <span class="tkc-label">Approved Payment</span>
                    <span class="tkc-val">{{ $cv['approved_payment_sum'] ?? 0 }}</span>
                </div>
                <div>
                    <span class="tkc-label">Approved Freight</span>
                    <span class="tkc-val">{{ $cv['approved_freight_sum'] ?? 0 }}</span>
                </div>
                <div>
                    <span class="tkc-label">Remaining</span>
                    <span class="tkc-val">{{ $cv['remaining_amount'] ?? 0 }}</span>
                </div>
            @endunless
        </div>

        <div class="tkc-chips">
            <span class="tkc-chip"><i class="ft-truck"></i>{{ $pf->truck_no ?? 'N/A' }}</span>
            <span class="tkc-chip"><i class="ft-file-text"></i>{{ $pf->bilty_no ?? 'N/A' }}</span>
        </div>
    </div>

    {{-- Manage bahar (sirf icon); Audit Logs ⋯ sheet me --}}
    <div class="tkc-actions">
        <button type="button" class="tkc-btn" onclick="openModal(this,'{{ $editUrl }}','Manage Payment Request')"
            title="Manage Payment Request" aria-label="Manage Payment Request">
            <i class="ft-edit"></i>
        </button>
        <button type="button" class="tkc-more" aria-label="More actions" title="More actions"
            data-title="#{{ $ticket->unique_no }}">
            <i class="ft-more-horizontal"></i>
        </button>
    </div>

    <template class="tkc-extra">
        <a href="javascript:;" class="tkc-sheet-item"
            onclick="openModal(this,'{{ route('raw-material.payment-request.audit-logs', $ticket->id) }}','Audit Logs: Ticket #{{ $ticket->unique_no }}', false, '55%')">
            <i class="fa fa-history"></i><span>Audit Logs</span>
        </a>
    </template>
</div>
