{{--
    Output QC Alerts list - MOBILE card (sirf < 768px; desktop par table).
    getList.blade.php isay $items ke loop me include karta hai: @include(..., ['alert' => $alert])
    Styling: mobile-ui.css section 6 (tkc-*). Table me column / action badle to yahan bhi badlo.
--}}
@php
    $isPending = $alert->isPending();
    $viewUrl = route('production-output-alert.show', $alert->id);
@endphp

{{-- pending remarks = halka laal card --}}
<div class="tkc {{ $isPending ? 'tkc-tint-red' : '' }}">
    <div class="tkc-main" role="button" tabindex="0"
        onclick="openModal(this,'{{ $viewUrl }}','Output QC Alert')">

        <div class="tkc-top">
            <div class="tkc-no">
                #{{ $alert->alert_no }}
                @if ($alert->attempt > 1)
                    <span class="tkc-tag">Re-check #{{ $alert->attempt - 1 }}</span>
                @endif
            </div>
            <span class="tkc-qc {{ $isPending ? 'is-rejected' : 'is-completed' }}">{{ $isPending ? 'Pending Remarks' : 'Remarks Submitted' }}</span>
        </div>

        <div class="tkc-date">
            <i class="ft-clock"></i>
            {{ $alert->created_at->format('d M Y, h:i A') }}
        </div>

        <div class="tkc-commodity">{{ $alert->product->name ?? 'N/A' }}</div>

        <div class="tkc-grid">
            <div>
                <span class="tkc-label">Plant</span>
                <span class="tkc-val">{{ $alert->plant->name ?? 'N/A' }}</span>
            </div>
            <div>
                <span class="tkc-label">Job Order</span>
                <span class="tkc-val">{{ $alert->jobOrder->job_order_no ?? '-' }}</span>
            </div>
            <div>
                <span class="tkc-label">Differences</span>
                <span class="tkc-val">{{ $alert->difference_count }}</span>
            </div>
            <div>
                <span class="tkc-label">Re-check Request</span>
                <span class="tkc-val">
                    {{ $alert->newAnalysisRequest->request_no ?? '-' }}
                    @if ($alert->newAnalysisRequest)
                        <small class="d-block">{{ ucfirst(str_replace('_', ' ', $alert->newAnalysisRequest->status)) }}</small>
                    @endif
                </span>
            </div>
        </div>
    </div>

    <div class="tkc-actions">
        <button type="button" class="tkc-btn"
            onclick="openModal(this,'{{ $viewUrl }}','Output QC Alert')" title="View">
            <i class="ft-eye"></i><span>View</span>
        </button>
    </div>
</div>
