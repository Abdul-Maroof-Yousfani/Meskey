{{--
    Inner Sampling Request list - MOBILE card (sirf < 768px; desktop par table).
    getList.blade.php isay $ArrivalSamplingRequests ke loop me include karta hai: @include(..., ['row' => $row])
    Styling: mobile-ui.css section 6 (tkc-*). Table me column / action badle to yahan bhi badlo.
--}}
@php
    $ticket = $row->arrivalTicket;
    $locationName = $ticket->unloadingLocation->ArrivalLocation->name ?? ($ticket->unloadingLocation->arrivalLocation->name ?? null);
@endphp

<div class="tkc">
    <div class="tkc-main">
        <div class="tkc-top">
            <div class="tkc-no">#{{ optional($ticket)->unique_no ?? '-' }}</div>
        </div>

        <div class="tkc-date">
            <i class="ft-clock"></i>
            {{ \Carbon\Carbon::parse($row->created_at)->format('d M Y, h:i A') }}
        </div>

        <div class="tkc-commodity">{{ optional(optional($ticket)->qcProduct)->name ?? '—' }}</div>

        @if ($row->remark)
            <div class="tkc-remark"><i class="ft-message-square"></i>{{ $row->remark }}</div>
        @endif

        @if ($ticket)
            <div class="tkc-chips">
                <span class="tkc-chip"><i class="ft-truck"></i>{{ $ticket->truck_no ?: '—' }}</span>
                @if ($locationName)
                    <span class="tkc-chip"><i class="ft-map-pin"></i>{{ $locationName }}</span>
                @endif
            </div>
        @endif
    </div>
</div>

