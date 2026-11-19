{{--
    Locations Transfer list - MOBILE card (sirf < 768px; desktop par table).
    getList.blade.php isay $arrival_locations ke loop me include karta hai: @include(..., ['row' => $row])
    Styling: mobile-ui.css section 6 (tkc-*). Table me column / action badle to yahan bhi badlo.
--}}
@php
    $viewUrl = route('location-transfer.edit', $row->id);
@endphp

<div class="tkc">
    {{-- card ka main hissa: tap = View (table wale 👁 jaisa) --}}
    <div class="tkc-main" role="button" tabindex="0"
        onclick="openModal(this,'{{ $viewUrl }}','View Location Transfer', true)">

        <div class="tkc-top">
            <div class="tkc-no">#{{ $row->arrivalTicket->unique_no ?? '-' }}</div>
        </div>

        <div class="tkc-date">
            <i class="ft-clock"></i>
            {{ optional($row->created_at)->format('d M Y, h:i A') }}
        </div>

        <div class="tkc-commodity"><i class="ft-map-pin"></i> {{ $row->arrivalLocation->name ?? '-' }}</div>

        <div class="tkc-grid">
            <div>
                <span class="tkc-label">Created By</span>
                <span class="tkc-val">{{ $row->createdBy->name ?? '-' }}</span>
            </div>
        </div>

        <div class="tkc-chips">
            <span class="tkc-chip"><i class="ft-truck"></i>{{ $row->arrivalTicket->truck_no ?? '—' }}</span>
        </div>
    </div>

    <div class="tkc-actions">
        <button type="button" class="tkc-btn"
            onclick="openModal(this,'{{ $viewUrl }}','View Location Transfer', true)" title="View Location Transfer">
            <i class="ft-eye"></i><span>View</span>
        </button>
    </div>
</div>
