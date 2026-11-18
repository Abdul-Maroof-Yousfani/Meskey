{{--
    Ticket list - MOBILE card (sirf < 768px dikhta hai; desktop par table).
    getList.blade.php isay $tickets ke loop me include karta hai: @include(..., ['row' => $row])
    NOTE: table me koi column / action badle to yahan bhi badlo.
--}}
@php
    $isRejected = $row->first_qc_status == 'rejected';
    $isLocked = (bool) $row->is_arrival_lock;
    $canMaster = canAccess('arrival-master-control');
    $hasBilty = $isRejected; // rejected par bilty return wala button aata hai
    $hasExtra = $canMaster || $hasBilty;
@endphp

<div class="tkc {{ $isRejected ? 'is-rejected' : '' }} {{ $isLocked ? 'is-locked' : '' }}">

    {{-- card ka main hissa: tap = View Ticket (table wale 👁 jaisa) --}}
    <div class="tkc-main" role="button" tabindex="0"
        onclick="openModal(this,'{{ route('ticket.edit', $row->id) }}','View Ticket', true)">

        <div class="tkc-top">
            <div class="tkc-no">
                #{{ $row->unique_no }}
                @if ($isLocked)
                    <i class="ft-lock" title="Locked Arrival Ticket"></i>
                @endif
            </div>
            {{-- rang status se: pending = orange, approved/completed = green, rejected = red --}}
            <span class="tkc-qc is-{{ \Illuminate\Support\Str::slug((string) $row->first_qc_status) }}">{{ $row->first_qc_status }}</span>
        </div>

        <div class="tkc-date">
            <i class="ft-clock"></i>
            {{ optional($row->created_at)->format('d M Y, h:i A') }}
        </div>

        <div class="tkc-commodity">{{ optional($row->product)->name ?? 'No Found' }}</div>

        <div class="tkc-grid">
            <div>
                <span class="tkc-label">Acc Of</span>
                <span class="tkc-val">{{ $row->accounts_of_name ?? 'N/A' }}</span>
            </div>
            <div>
                <span class="tkc-label">Miller</span>
                <span class="tkc-val">{{ $row->miller->name ?? 'N/A' }}</span>
            </div>
            <div>
                <span class="tkc-label">Net Weight</span>
                <span class="tkc-val">{{ $row->net_weight ?? 'N/A' }}</span>
            </div>
            <div>
                <span class="tkc-label">Station</span>
                <span class="tkc-val">{{ $row->station_name ?? ($row->station->name ?? 'N/A') }}</span>
            </div>
        </div>

        <div class="tkc-chips">
            <span class="tkc-chip"><i class="ft-truck"></i>{{ $row->truck_no ?: '—' }}</span>
            <span class="tkc-chip"><i class="ft-file-text"></i>{{ $row->bilty_no ?: '—' }}</span>
        </div>
    </div>

    {{-- actions --}}
    <div class="tkc-actions">
        <button type="button" class="tkc-btn"
            onclick="openModal(this,'{{ route('ticket.edit', $row->id) }}','View Ticket', true)" title="View Ticket">
            <i class="ft-eye"></i><span>View</span>
        </button>

        @if ($isLocked)
            <button type="button" class="tkc-btn tkc-btn-warn"
                onclick="openModal(this,'{{ route('ticket.edit', $row->id) }}','Edit Ticket', false)" title="Edit Ticket">
                <i class="ft-edit"></i><span>Edit</span>
            </button>
        @endif

        @if ($hasExtra)
            <button type="button" class="tkc-more" aria-label="More actions" title="More actions"
                data-title="#{{ $row->unique_no }}">
                <i class="ft-more-horizontal"></i>
            </button>
        @endif
    </div>

    {{-- ⋯ sheet me dikhne wale actions (chhupe hue; JS sheet me copy karta hai) --}}
    @if ($hasExtra)
        <template class="tkc-extra">
            @if ($canMaster)
                <a href="{{ route('ticket.arrival-revert', $row->id) }}" class="tkc-sheet-item is-danger">
                    <i class="ft-sliders"></i><span>Master Control</span>
                </a>
                <a href="{{ route('ticket.arrival-revert-test', $row->id) }}" class="tkc-sheet-item is-danger">
                    <i class="ft-sliders"></i><span>Master Control (Maund)</span>
                </a>
                <a href="javascript:;" class="tkc-sheet-item"
                    onclick="openModal(this,'{{ route('ticket.audit-logs', $row->id) }}?section=ticket_detail','Audit Logs: #{{ $row->unique_no }}', false, '55%')">
                    <i class="fa fa-history"></i><span>Audit Logs</span>
                </a>
            @endif

            @if ($isRejected && $row->bilty_return_confirmation == 0)
                <a href="javascript:;" class="tkc-sheet-item is-warn" onclick="confirmBiltyReturn({{ $row->id }})">
                    <i class="ft-corner-up-left"></i><span>Confirm Bilty Return</span>
                </a>
            @elseif ($isRejected && $row->bilty_return_confirmation == 1)
                <a href="javascript:;" class="tkc-sheet-item is-success"
                    onclick="openModal(this,'{{ route('ticket.get-bilty-attachments', $row->id) }}','Bilty Return Attachments', true)">
                    <i class="ft-eye"></i><span>Return Confirmed (Attachments)</span>
                </a>
            @endif
        </template>
    @endif
</div>
