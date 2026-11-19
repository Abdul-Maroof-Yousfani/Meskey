{{--
    First Weighbridge list - MOBILE card (sirf < 768px; desktop par table).
    getList.blade.php isay $ArrivalSamplingRequests ke loop me include karta hai: @include(..., ['row' => $row])
    Styling: mobile-ui.css section 6 (tkc-*). Table me column / action badle to yahan bhi badlo.
--}}
@php
    $viewUrl = route('first-weighbridge.edit', $row->id);
@endphp

<div class="tkc">
    {{-- card ka main hissa: tap = View (table wale 👁 jaisa) --}}
    <div class="tkc-main" role="button" tabindex="0"
        onclick="openModal(this,'{{ $viewUrl }}','View First Weighbridge', true)">

        <div class="tkc-top">
            <div class="tkc-no">#{{ $row->arrivalTicket->unique_no ?? '-' }}</div>
        </div>

        <div class="tkc-date">
            <i class="ft-clock"></i>
            {{ \Carbon\Carbon::parse($row->created_at)->format('d M Y, h:i A') }}
        </div>

        <div class="tkc-commodity">{{ $row->arrivalTicket->qcProduct->name ?? '-' }}</div>

        <div class="tkc-grid">
            <div>
                <span class="tkc-label">Weight</span>
                <span class="tkc-val">{{ $row->weight ?? '-' }}</span>
            </div>
            <div>
                <span class="tkc-label">Arrival Location</span>
                <span class="tkc-val">{{ $row->arrivalTicket->unloadingLocation->arrivalLocation->name ?? '-' }}</span>
            </div>
        </div>

        <div class="tkc-chips">
            <span class="tkc-chip"><i class="ft-truck"></i>{{ $row->arrivalTicket->truck_no ?? '—' }}</span>
        </div>
    </div>

    <div class="tkc-actions">
        <button type="button" class="tkc-btn"
            onclick="openModal(this,'{{ $viewUrl }}','View First Weighbridge', true)" title="View First Weighbridge">
            <i class="ft-eye"></i><span>View</span>
        </button>
    </div>
</div>
