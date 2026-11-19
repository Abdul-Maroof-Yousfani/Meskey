{{--
    Dashboard box -> modal list (arrival) - MOBILE card. Bilkul Arrival Ticket list wale tkc cards jaisa
    (mobile-ui.css section 6). list_data.blade.php ki table jo columns dikhati hai, wahi yahan.
    $item, $type, $conf (arrival-stats-show-confidential)
--}}
@php
    $samplingTypes = [
        'initial_sampling_done', 'resampling_required', 'inner_sampling_requested', 'inner_re_sampling_requested',
        'initial_sampling_requested', 'initial_re_sampling_requested', 'inner_sampling_pending_approval',
    ];
    $isSampling = in_array($type, $samplingTypes);
    $ticket = $isSampling ? $item->arrivalTicket : $item;

    // status badge: [text, tkc-qc class]
    $status = null;
    if ($isSampling) {
        $s = (string) $item->approved_status;
        $status = [ucfirst($s), $s === 'pending' ? 'is-pending' : ($s === 'approved' ? 'is-approved' : 'is-rejected')];
    } elseif ($type == 'second_weighbridge_pending') {
        $s = (string) $item->document_approval_status;
        if ($s !== '') {
            $status = [ucfirst(str_replace('_', ' ', $s)), $s === 'half_approved' ? 'is-pending' : 'is-approved'];
        }
    } elseif ($type == 'freight_ready') {
        $status = ['Decision: ' . ($item->decision_making ? 'Yes' : 'No'), $item->decision_making ? 'is-pending' : 'is-approved'];
    } elseif (!in_array($type, ['first_weighbridge_pending', 'half_full_approve_pending'])) {
        if ($item->first_qc_status == 'rejected') {
            $status = [ucwords($item->first_qc_status), 'is-rejected'];
        } elseif ($item->arrival_slip_status == 'generated') {
            $status = ['Slip Generated', 'is-approved'];
        } else {
            $status = ['In-Process', 'is-pending'];
        }
    }

    // grid: [label => value] (sirf jo table me bhi dikhte hain)
    $grid = [];
    if ($conf) {
        $grid['Decision Of'] = $item?->decisionBy?->name ?? 'N/A';
    }
    if ($isSampling) {
        $grid['Sampling Type'] = ucfirst((string) $item->sampling_type) ?: 'N/A';
    } else {
        if (in_array($type, ['first_weighbridge_pending', 'second_weighbridge_pending'])) {
            $grid['Location'] = $item->unloadingLocation?->arrivalLocation?->name ?? 'N/A';
        }
        if ($type == 'half_full_approve_pending') {
            $grid['First Weight'] = $item->firstWeighbridge->weight ?? 'N/A';
        }
        $grid['Station'] = $item->station->name ?? 'N/A';
        $grid['Bags'] = $item->bags ?? 'N/A';
        if ($conf && !in_array($type, ['first_weighbridge_pending', 'half_full_approve_pending', 'second_weighbridge_pending'])) {
            $grid['Accounts Of'] = $item->accountsOf->name ?? 'N/A';
        }
    }

    $showBilty = $conf && !$isSampling && !in_array($type, ['first_weighbridge_pending', 'half_full_approve_pending', 'second_weighbridge_pending']);
@endphp

<div class="tkc {{ ($status[1] ?? '') === 'is-rejected' ? 'is-rejected' : '' }}">
    <div class="tkc-main">
        <div class="tkc-top">
            <div class="tkc-no">#{{ $ticket->unique_no ?? 'N/A' }}</div>
            @if ($status)
                <span class="tkc-qc {{ $status[1] }}">{{ $status[0] }}</span>
            @endif
        </div>

        <div class="tkc-date">
            <i class="ft-clock"></i>
            {{ optional($item->created_at)->format('d M Y') }}
        </div>

        <div class="tkc-commodity">{{ $ticket->qcProduct->name ?? 'N/A' }}</div>

        @if (count($grid))
            <div class="tkc-grid">
                @foreach ($grid as $label => $value)
                    <div>
                        <span class="tkc-label">{{ $label }}</span>
                        <span class="tkc-val">{{ $value }}</span>
                    </div>
                @endforeach
            </div>
        @endif

        @if ($conf)
            <div class="tkc-chips">
                <span class="tkc-chip"><i class="ft-truck"></i>{{ $ticket->truck_no ?? '—' }}</span>
                @if ($showBilty)
                    <span class="tkc-chip"><i class="ft-file-text"></i>{{ $ticket->bilty_no ?? '—' }}</span>
                @endif
            </div>
        @endif
    </div>
</div>
