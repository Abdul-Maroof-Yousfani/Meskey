{{--
    Purchase Sampling / Re-Sampling list - MOBILE card (sirf < 768px; desktop par table).
    getList.blade.php isay $samplingRequests ke loop me include karta hai: @include(..., ['row' => $row])
    Styling: mobile-ui.css section 6 (tkc-*). Table me column / action badle to yahan bhi badlo.
--}}
@php
    $done = $row->is_done == 'yes';
    $url = route($isResampling ? 'raw-material.purchase-resampling.edit' : 'raw-material.purchase-sampling.edit', $row->id);
    $title = $done
        ? ($isResampling ? 'View Purchase Re-Sampling' : 'View Purchase Sampling')
        : ($isResampling ? 'Create Purchase Re-Sampling' : 'Create Purchase Sampling');
    $viewOnly = $done ? 'true' : 'false';
@endphp

{{-- tkc-tint-orange: table me pending row narangi (bg-orange) hoti hai --}}
<div class="tkc {{ $done ? '' : 'tkc-tint-orange' }}">
    <div class="tkc-main" role="button" tabindex="0" onclick="openModal(this,'{{ $url }}','{{ $title }}',{{ $viewOnly }})">

        <div class="tkc-top">
            <div class="tkc-no">#{{ $row->purchaseOrder->contract_no ?? ($row->purchaseTicket->unique_no ?? 'N/A') }}</div>
            <span class="tkc-qc {{ $done ? 'is-approved' : 'is-pending' }}">{{ $done ? 'Done' : 'Pending' }}</span>
        </div>

        <div class="tkc-date">
            <i class="ft-clock"></i>
            {{ \Carbon\Carbon::parse($row->created_at)->format('d M Y, h:i A') }}
        </div>

        <div class="tkc-commodity">
            {{ $row->purchaseOrder->product->name ?? ($row->qcProduct->name ?? ($row->product->name ?? 'N/A')) }}
        </div>

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
                <span class="tkc-val">{{ $row->purchaseOrder->decisionOfUser->name ?? 'N/A' }}</span>
            </div>
        </div>

        @if ($row->remark)
            <div class="tkc-remark"><i class="ft-message-square"></i>{{ $row->remark }}</div>
        @endif
    </div>

    <div class="tkc-actions">
        <button type="button" class="tkc-btn {{ $done ? '' : 'tkc-btn-warn' }}"
            onclick="openModal(this,'{{ $url }}','{{ $title }}',{{ $viewOnly }})" title="{{ $title }}">
            <i class="{{ $done ? 'ft-eye' : 'ft-edit' }}"></i><span>{{ $done ? 'View' : 'Create Sampling' }}</span>
        </button>
    </div>
</div>
