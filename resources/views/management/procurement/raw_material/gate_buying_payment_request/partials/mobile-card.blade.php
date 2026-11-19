{{--
    Generate Bill (Gate Buying) list - MOBILE card (sirf < 768px; desktop par table).
    getList.blade.php isay $tickets ke loop me include karta hai: @include(..., ['ticket' => $ticket])
    Styling: mobile-ui.css section 6 (tkc-*). Table me column / action badle to yahan bhi badlo.
--}}
@php
    $cv = $ticket->calculated_values;
    $noRequests = ($cv['total_payment_sum'] ?? 0) == 0 && ($cv['total_freight_sum'] ?? 0) == 0;
    $editUrl = route('raw-material.gate-buy.payment-request.edit', $ticket->id);
@endphp

<div class="tkc">
    {{-- card ka main hissa: tap = Generate Bill (table wale ✎ jaisa) --}}
    <div class="tkc-main" role="button" tabindex="0"
        onclick="openModal(this,'{{ $editUrl }}','Generate Bill (Gate Buying)')">

        <div class="tkc-top">
            <div class="tkc-no">#{{ $ticket->unique_no }}</div>
            <span class="tkc-tag">#{{ $ticket->purchaseOrder->contract_no ?? '' }}</span>
        </div>

        <div class="tkc-date">
            <i class="ft-clock"></i>
            {{ \Carbon\Carbon::parse($cv['created_at'])->format('d M Y, h:i A') }}
        </div>

        <div class="tkc-commodity">{{ $ticket->qcProduct->name ?? 'N/A' }}</div>

        <div class="tkc-grid">
            <div>
                <span class="tkc-label">Broker</span>
                <span class="tkc-val">{{ $ticket->broker_name ?? 'N/A' }}</span>
            </div>
            <div>
                <span class="tkc-label">Supplier</span>
                <span class="tkc-val">{{ $ticket->purchaseOrder->supplier->name ?? 'N/A' }}</span>
            </div>
            <div>
                <span class="tkc-label">Loading Date</span>
                <span class="tkc-val">{{ $ticket ? \Carbon\Carbon::parse($ticket->loading_date)->format('d M Y') : 'N/A' }}</span>
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
                    <span class="tkc-label">Remaining</span>
                    <span class="tkc-val">{{ $cv['remaining_amount'] ?? 0 }}</span>
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

    <div class="tkc-actions">
        <button type="button" class="tkc-btn"
            onclick="openModal(this,'{{ $editUrl }}','Generate Bill (Gate Buying)')" title="Generate Bill">
            <i class="ft-edit"></i><span>Generate Bill</span>
        </button>
    </div>
</div>
