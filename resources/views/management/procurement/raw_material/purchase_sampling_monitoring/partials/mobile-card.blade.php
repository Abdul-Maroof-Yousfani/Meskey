{{--
    Purchaser Approval Requests (purchase sampling monitoring) - MOBILE card (sirf < 768px; desktop par table).
    getList.blade.php isay $samplingRequests ke loop me include karta hai: @include(..., ['row' => $row])
    Styling: mobile-ui.css section 6 (tkc-*). Table me column / action badle to yahan bhi badlo.
--}}
@php
    $status = (string) $row->approved_status;
    $viewUrl = route('raw-material.sampling-monitoring.edit', $row->id);
    $isSuper = auth()->user()->user_type == 'super-admin';
@endphp

{{-- tkc-tint-*: table row ka rang (halka): pending = orange, rejected = red, approved = green, baaki = grey --}}
<div class="tkc {{ $status == 'rejected' ? 'is-rejected' : '' }} tkc-tint-{{ $status == 'pending' ? 'orange' : ($status == 'rejected' ? 'red' : ($status == 'approved' ? 'green' : 'grey')) }}">
    <div class="tkc-main" role="button" tabindex="0"
        onclick="openModal(this,'{{ $viewUrl }}','View Approval Requests (Purchase)')">

        <div class="tkc-top">
            <div class="tkc-no">#{{ $row->purchaseOrder->contract_no ?? ($row->purchaseTicket->unique_no ?? 'N/A') }}</div>
            <span class="tkc-qc is-{{ \Illuminate\Support\Str::slug($status) }}">{{ $status }}</span>
        </div>

        <div class="tkc-date">
            <i class="ft-clock"></i>
            {{ \Carbon\Carbon::parse($row->created_at)->format('d M Y, h:i A') }}
        </div>

        <div class="tkc-commodity">{{ $row->purchaseOrder->product->name ?? ($row->product->name ?? 'N/A') }}</div>

        @if ($row->is_custom_qc == 'yes')
            <div class="tkc-tags">
                <span class="tkc-tag">Without Contract</span>
            </div>
        @endif

        <div class="tkc-grid">
            <div>
                <span class="tkc-label">Supplier</span>
                <span class="tkc-val">{{ $row->purchaseOrder->supplier->name ?? ($row->supplier_name ?? 'N/A') }}</span>
            </div>
            <div>
                <span class="tkc-label">Decision Of</span>
                <span class="tkc-val">{{ $row->decisionOfUser->name ?? 'N/A' }}</span>
            </div>
        </div>

        @if ($row->remark)
            <div class="tkc-remark"><i class="ft-message-square"></i>{{ $row->remark }}</div>
        @endif
    </div>

    <div class="tkc-actions">
        <button type="button" class="tkc-btn" onclick="openModal(this,'{{ $viewUrl }}','View Approval Requests (Purchase)')" title="View">
            <i class="ft-eye"></i><span>View</span>
        </button>
        @if ($isSuper)
            <button type="button" class="tkc-btn tkc-btn-warn"
                onclick="openModal(this,'{{ route('raw-material.sampling-monitoring.edit-test', $row->id) }}','View Approval Requests (Purchase)')"
                title="Test Edit">
                <i class="fa fa-check"></i><span>Test</span>
            </button>
        @endif
    </div>
</div>
