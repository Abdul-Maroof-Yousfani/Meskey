{{--
    Arrival Slip list - MOBILE card (sirf < 768px; desktop par table).
    getList.blade.php isay $ArrivalSlip ke loop me include karta hai: @include(..., ['row' => $row])
    Styling: mobile-ui.css section 6 (tkc-*). Table me column / action badle to yahan bhi badlo.
--}}
@php
    $ticket = $row->arrivalTicket;
    $viewUrl = route('arrival-slip.edit', $row->id);
@endphp

<div class="tkc">
    {{-- card ka main hissa: tap = View (table wale 👁 jaisa) --}}
    <div class="tkc-main" role="button" tabindex="0"
        onclick="openModal(this,'{{ $viewUrl }}','View Arrival Slip', true, '100%')">

        <div class="tkc-top">
            <div class="tkc-no">#{{ $ticket->unique_no ?? 'N/A' }}</div>
            @if ($row->grn_unique_no)
                <span class="tkc-tag">GRN: {{ $row->grn_unique_no }}</span>
            @endif
        </div>

        <div class="tkc-date">
            <i class="ft-clock"></i>
            {{ \Carbon\Carbon::parse($row->created_at)->format('d M Y, h:i A') }}
        </div>

        <div class="tkc-commodity">{{ $ticket->product->name ?? 'N/A' }}</div>

        <div class="tkc-grid">
            <div>
                <span class="tkc-label">Acc Of</span>
                <span class="tkc-val">{{ $ticket->accountsOf->name ?? 'N/A' }}</span>
            </div>
            <div>
                <span class="tkc-label">Unloading Location</span>
                <span class="tkc-val">{{ $ticket->unloadingLocation->ArrivalLocation->name ?? 'N/A' }}</span>
            </div>
        </div>

        <div class="tkc-chips">
            <span class="tkc-chip"><i class="ft-truck"></i>{{ $ticket->truck_no ?? '—' }}</span>
            <span class="tkc-chip"><i class="ft-file-text"></i>{{ $ticket->bilty_no ?? '—' }}</span>
        </div>
    </div>

    <div class="tkc-actions">
        <button type="button" class="tkc-btn"
            onclick="openModal(this,'{{ $viewUrl }}','View Arrival Slip', true, '100%')" title="View Arrival Slip">
            <i class="ft-eye"></i><span>View</span>
        </button>
    </div>
</div>
