{{--
    Advance Payment Request list - MOBILE card (sirf < 768px; desktop par table).
    getList.blade.php isay $tickets ke loop me include karta hai: @include(..., ['ticket' => $ticket])
    Styling: mobile-ui.css section 6 (tkc-*); ⋯ sheet: public/management/assets/js/mobile-card-sheet.js
--}}
@php
    $cv = $ticket->calculated_values;
    $noRequests = ($cv['total_payment_sum'] ?? 0) == 0 && ($cv['total_freight_sum'] ?? 0) == 0;
    $editUrl = route('raw-material.advance-payment-request.edit', $ticket->id);
@endphp

<div class="tkc">
    {{-- card ka main hissa: tap = Manage Payment Request --}}
    <div class="tkc-main" role="button" tabindex="0"
        onclick="openModal(this,'{{ $editUrl }}','Manage Payment Request')">

        <div class="tkc-top">
            <div class="tkc-no">#{{ $ticket->contract_no ?? 'N/A' }}</div>
        </div>

        <div class="tkc-date">
            <i class="ft-clock"></i>
            {{ \Carbon\Carbon::parse($cv['created_at'])->format('d M Y, h:i A') }}
        </div>

        <div class="tkc-commodity">{{ $ticket->qcProduct->name ?? 'N/A' }}</div>

        <div class="tkc-grid">
            <div>
                <span class="tkc-label">Location</span>
                <span class="tkc-val">{{ $ticket->location->name ?? 'N/A' }}</span>
            </div>
            <div>
                <span class="tkc-label">Supplier</span>
                <span class="tkc-val">{{ $ticket->supplier->name ?? 'N/A' }}</span>
            </div>
            @unless ($noRequests)
                <div>
                    <span class="tkc-label">Total Amount</span>
                    <span class="tkc-val">{{ $cv['total_amount'] ?? 0 }}</span>
                </div>
                <div>
                    <span class="tkc-label">Remaining</span>
                    <span class="tkc-val">{{ $cv['remaining_amount'] ?? 0 }}</span>
                </div>
                <div>
                    <span class="tkc-label">Approved Payment</span>
                    <span class="tkc-val">{{ $cv['approved_payment_sum'] ?? 0 }}</span>
                </div>
                <div>
                    <span class="tkc-label">Approved Freight</span>
                    <span class="tkc-val">{{ $cv['approved_freight_sum'] ?? 0 }}</span>
                </div>
            @endunless
        </div>

        <div class="tkc-tags">
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
    </div>

    {{-- Manage bahar; Audit Logs ⋯ sheet me --}}
    <div class="tkc-actions">
        <button type="button" class="tkc-btn" onclick="openModal(this,'{{ $editUrl }}','Manage Payment Request')"
            title="Manage Payment Request">
            <i class="ft-edit"></i>
        </button>
        <button type="button" class="tkc-more" aria-label="More actions" title="More actions"
            data-title="#{{ $ticket->contract_no }}">
            <i class="ft-more-horizontal"></i>
        </button>
    </div>

    <template class="tkc-extra">
        <a href="javascript:;" class="tkc-sheet-item"
            onclick="openModal(this,'{{ route('raw-material.advance-payment-request.audit-logs', $ticket->id) }}','Audit Logs: Contract #{{ $ticket->contract_no }}', false, '55%')">
            <i class="fa fa-history"></i><span>Audit Logs</span>
        </a>
    </template>
</div>
