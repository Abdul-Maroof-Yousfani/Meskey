{{--
    Approval Requests (sampling-monitoring) list - MOBILE card (sirf < 768px; desktop par table).
    getList.blade.php isay $samplingRequests ke loop me include karta hai: @include(..., ['row' => $row])
    Styling: mobile-ui.css section 6 (tkc-*). Table me column / action badle to yahan bhi badlo.
--}}
@php
    $ticket = $row->arrivalTicket;
    $status = (string) $row->approved_status;
    $viewUrl = route('sampling-monitoring.edit', $row->id);
    $testUrl = route('sampling-monitoring.edit-test', $row->id);
@endphp

{{-- tkc-tint-*: table row ka rang (halka): pending = orange, rejected = red, approved = green, baaki = grey --}}
<div class="tkc {{ $status == 'rejected' ? 'is-rejected' : '' }} tkc-tint-{{ $status == 'pending' ? 'orange' : ($status == 'rejected' ? 'red' : ($status == 'approved' ? 'green' : 'grey')) }}">
    {{-- card ka main hissa: tap = View (table wale 👁 jaisa) --}}
    <div class="tkc-main" role="button" tabindex="0"
        onclick="openModal(this,'{{ $viewUrl }}','View Approval Requests',false,'90%')">

        <div class="tkc-top">
            <div class="tkc-no">#{{ optional($ticket)->unique_no }}</div>
            {{-- pending = orange, approved = green, rejected = red --}}
            <span class="tkc-qc is-{{ \Illuminate\Support\Str::slug($status) }}">{{ $status }}</span>
        </div>

        <div class="tkc-date">
            <i class="ft-clock"></i>
            {{ \Carbon\Carbon::parse($row->created_at)->format('d M Y, h:i A') }}
        </div>

        <div class="tkc-commodity">{{ optional(optional($ticket)->qcProduct)->name ?? '—' }}</div>

        <div class="tkc-tags">
            <span class="tkc-tag {{ $row->sampling_type == 'initial' ? '' : 'is-inner' }}">
                {{ $row->sampling_type }}{{ $row->is_re_sampling == 'yes' ? ' · Resampling' : '' }}
            </span>
            @if ($row->is_auto_approved == 'yes')
                <span class="tkc-tag is-auto" title="Auto approved: slabs matched the previous QC">
                    <i class="fa fa-check-circle"></i> Auto Decision
                </span>
            @endif
        </div>

        <div class="tkc-grid">
            <div>
                <span class="tkc-label">Result Posted</span>
                <span class="tkc-val">
                    {{ $row->result_posted_at ? \Carbon\Carbon::parse($row->result_posted_at)->format('d M Y, h:i A') : '—' }}
                </span>
            </div>
            <div>
                <span class="tkc-label">Approved</span>
                <span class="tkc-val">
                    @if ($row->approved_at)
                        {{ \Carbon\Carbon::parse($row->approved_at)->format('d M Y, h:i A') }}
                        @if ($row->approved_by != null)
                            <br>{{ optional($row->approvedByUser)->name }}
                        @endif
                    @else
                        —
                    @endif
                </span>
            </div>
        </div>

        @if ($row->remark)
            <div class="tkc-remark"><i class="ft-message-square"></i>{{ $row->remark }}</div>
        @endif
    </div>

    <div class="tkc-actions">
        <button type="button" class="tkc-btn"
            onclick="openModal(this,'{{ $viewUrl }}','View Approval Requests',false,'90%')" title="View">
            <i class="ft-eye"></i><span>View</span>
        </button>
        <button type="button" class="tkc-btn tkc-btn-warn"
            onclick="openModal(this,'{{ $testUrl }}','Test Edit Approval Requests',false,'90%')" title="Test Edit">
            <i class="fa fa-check"></i><span>Test</span>
        </button>
    </div>
</div>
