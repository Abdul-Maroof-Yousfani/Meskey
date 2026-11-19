{{--
    Freight list - MOBILE card (sirf < 768px; desktop par table).
    getList.blade.php isay $freights ke loop me include karta hai: @include(..., ['freight' => $freight])
    Styling: mobile-ui.css section 6 (tkc-*). Table me column / action badle to yahan bhi badlo.
--}}
@php
    $ticket = $freight->arrivalTicket;
    $status = (string) $freight->status;
    $viewUrl = route('freight.edit', $freight->id);
@endphp

<div class="tkc {{ $status != 'pending' && $status != 'approved' ? 'is-rejected' : '' }}">
    {{-- card ka main hissa: tap = View (table wale 👁 jaisa) --}}
    <div class="tkc-main" role="button" tabindex="0"
        onclick="openModal(this,'{{ $viewUrl }}','View Freight', true, '80%')">

        <div class="tkc-top">
            <div class="tkc-no">#{{ $ticket->unique_no ?? 'N/A' }}</div>
            {{-- pending = orange, approved = green, baaki = red --}}
            <span class="tkc-qc is-{{ in_array($status, ['pending', 'approved']) ? $status : 'rejected' }}">{{ ucfirst($status) }}</span>
        </div>

        <div class="tkc-commodity">Net Freight: {{ number_format($freight->net_freight, 2) }}</div>

        <div class="tkc-grid">
            <div>
                <span class="tkc-label">Miller</span>
                <span class="tkc-val">{{ $ticket->miller->name ?? 'N/A' }}</span>
            </div>
            <div>
                <span class="tkc-label">Accounts Of</span>
                <span class="tkc-val">{{ $ticket->accounts_of_name ?? 'N/A' }}</span>
            </div>
        </div>

        <div class="tkc-chips">
            <span class="tkc-chip"><i class="ft-truck"></i>{{ $ticket->truck_no ?? '—' }}</span>
            <span class="tkc-chip"><i class="ft-file-text"></i>{{ $ticket->bilty_no ?? '—' }}</span>
        </div>
    </div>

    <div class="tkc-actions">
        <button type="button" class="tkc-btn"
            onclick="openModal(this,'{{ $viewUrl }}','View Freight', true, '80%')" title="View Freight">
            <i class="ft-eye"></i><span>View</span>
        </button>
        <button type="button" class="tkc-btn tkc-btn-danger"
            onclick="deletemodal('{{ route('freight.destroy', $freight->id) }}','{{ route('get.freight') }}')"
            title="Delete">
            <i class="ft-x"></i><span>Delete</span>
        </button>
    </div>
</div>
