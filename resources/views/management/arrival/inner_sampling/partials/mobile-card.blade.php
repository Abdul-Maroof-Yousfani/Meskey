{{--
    Inner Sampling / Re-Sampling list - MOBILE card (sirf < 768px; desktop par table).
    getList.blade.php isay $samplingRequests ke loop me include karta hai: @include(..., ['row' => $row])
    Styling: mobile-ui.css section 6 (tkc-*). Table me column / action badle to yahan bhi badlo.
--}}
@php
    $ticket = $row->arrivalTicket;
    $canView = auth()->user()->can('role-edit');
    $viewUrl = route($isResampling ? 'inner-resampling.edit' : 'inner-sampling.edit', $row->id);
    $viewTitle = $isResampling ? 'View Inner Re-Sampling' : 'View Inner Sampling';
@endphp

<div class="tkc">
    {{-- card ka main hissa: tap = View (table wale 👁 jaisa) --}}
    <div class="tkc-main" @if ($canView) role="button" tabindex="0"
        onclick="openModal(this,'{{ $viewUrl }}','{{ $viewTitle }}',true)" @endif>

        <div class="tkc-top">
            <div class="tkc-no">#{{ optional($ticket)->unique_no ?? '-' }}</div>
            @if ($isResampling)
                <span class="tkc-qc is-pending">Re-Sampling</span>
            @endif
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
                @if ($ticket->bilty_no)
                    <span class="tkc-chip"><i class="ft-file-text"></i>{{ $ticket->bilty_no }}</span>
                @endif
            </div>
        @endif
    </div>

    @if ($canView)
        <div class="tkc-actions">
            <button type="button" class="tkc-btn" onclick="openModal(this,'{{ $viewUrl }}','{{ $viewTitle }}',true)"
                title="{{ $viewTitle }}">
                <i class="ft-eye"></i><span>View</span>
            </button>
        </div>
    @endif
</div>

