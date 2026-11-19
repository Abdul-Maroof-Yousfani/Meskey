{{--
    Purchase Sampling Request list - MOBILE card (sirf < 768px; desktop par table).
    getList.blade.php isay $purchaseOrders ke loop me include karta hai: @include(..., ['row' => $row])
    Styling: mobile-ui.css section 6 (tkc-*). Table me column / action badle to yahan bhi badlo.
--}}
@php
    $loaded = $row->totalLoadingWeight->total_loading_weight ?? null;
    $minRem = $loaded !== null ? $row->min_quantity - $loaded : '-';
    $maxRem = $loaded !== null ? $row->max_quantity - $loaded : '-';
    $reqUrl = route('raw-material.purchase-sampling-request.createReq', ['id' => $row->id]);
@endphp

<div class="tkc">
    {{-- card ka main hissa: tap = Create Sampling Request (table wale ✎ jaisa) --}}
    <div class="tkc-main" role="button" tabindex="0"
        onclick="openModal(this,'{{ $reqUrl }}','Create Sampling Request',false)">

        <div class="tkc-top">
            <div class="tkc-no">#{{ $row->contract_no }}</div>
            @if ($row?->purchaseTicket?->unique_no)
                <span class="tkc-tag">#{{ $row->purchaseTicket->unique_no }}</span>
            @endif
        </div>

        <div class="tkc-date">
            <i class="ft-clock"></i>
            {{ \Carbon\Carbon::parse($row->created_at)->format('d M Y, h:i A') }}
        </div>

        <div class="tkc-commodity">{{ $row->product->name ?? '--' }}</div>

        <div class="tkc-grid">
            <div>
                <span class="tkc-label">Supplier</span>
                <span class="tkc-val">{{ $row->supplier->name ?? '--' }}</span>
            </div>
            <div>
                <span class="tkc-label">Decision Of</span>
                <span class="tkc-val">{{ $row->decisionOfUser->name ?? '--' }}</span>
            </div>
            <div>
                <span class="tkc-label">Order Qty</span>
                <span class="tkc-val">{{ $row->min_quantity ?? '-' }} - {{ $row->max_quantity ?? '-' }}</span>
            </div>
            <div>
                <span class="tkc-label">Remaining Qty</span>
                <span class="tkc-val">{{ $minRem }} - {{ $maxRem }}</span>
            </div>
            <div>
                <span class="tkc-label">Loaded Qty</span>
                <span class="tkc-val">{{ $loaded ?? '-' }}</span>
            </div>
            <div>
                <span class="tkc-label">Delivery Date</span>
                <span class="tkc-val">{{ $row->delivery_date ? \Carbon\Carbon::parse($row->delivery_date)->format('d M Y') : '--' }}</span>
            </div>
        </div>
    </div>

    <div class="tkc-actions">
        <button type="button" class="tkc-btn" onclick="openModal(this,'{{ $reqUrl }}','Create Sampling Request',false)"
            title="Create Sampling Request">
            <i class="ft-edit"></i><span>Create Sampling Request</span>
        </button>
    </div>
</div>
