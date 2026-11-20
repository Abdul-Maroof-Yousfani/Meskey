{{--
    Loading Management (Freight) list - MOBILE card (sirf < 768px; desktop par table).
    getList.blade.php isay $purchaseTickets ke loop me include karta hai: @include(..., ['ticket' => $ticket])
    Styling: mobile-ui.css section 6 (tkc-*). Table me column / action badle to yahan bhi badlo.
--}}
@php
    $po = $ticket->purchaseOrder;
    $contractNo = $po->contract_no ?? 'N/A';
    $uniqueNo = $ticket->unique_no ?? 'N/A';
    $supplierName = $po->supplier->name ?? 'N/A';
    $brokerName = $po->broker_one_name ?? ($po->broker_two_name ?? ($po->broker_three_name ?? 'N/A'));
    $productName = $po->qcProduct->name ?? ($po->product->name ?? 'N/A');
    $status = strtolower($ticket->freight_status ?? 'pending');
    $isPending = ($status === 'pending');
    $statusText = ucfirst($status);
    $statusClass = $isPending ? 'is-pending' : 'is-inner';
    $actionUrl = route('raw-material.freight.create', ['purchase_ticket_id' => $ticket->id]);
@endphp

<div class="tkc">
    {{-- card ka main hissa: tap = Create Loading modal --}}
    <div class="tkc-main" role="button" tabindex="0"
        onclick="openModal(this,'{{ $actionUrl }}','Create Loading', false)">

        <div class="tkc-top">
            <div class="tkc-no">
                #{{ $contractNo }} <span class="tkc-sub-no" style="font-size: 11px; color: #8a8a99; font-weight: normal;">(Ticket #{{ $uniqueNo }})</span>
            </div>
            <span class="tkc-qc {{ $statusClass }}">{{ $statusText }}</span>
        </div>

        @if ($ticket->created_at)
            <div class="tkc-date">
                <i class="ft-clock"></i>
                {{ \Carbon\Carbon::parse($ticket->created_at)->format('d M Y, h:i A') }}
            </div>
        @endif

        <div class="tkc-commodity">{{ $productName }}</div>

        <div class="tkc-grid">
            <div>
                <span class="tkc-label">Supplier</span>
                <span class="tkc-val">{{ $supplierName }}</span>
            </div>
            <div>
                <span class="tkc-label">Broker</span>
                <span class="tkc-val">{{ $brokerName }}</span>
            </div>
        </div>
    </div>

    <div class="tkc-actions">
        <button type="button" class="tkc-btn"
            onclick="openModal(this,'{{ $actionUrl }}','Create Loading', false)" title="Create Loading">
            <i class="ft-edit"></i><span>Loading</span>
        </button>
    </div>
</div>

