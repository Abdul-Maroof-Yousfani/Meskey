{{--
    Stock in Transit Vehicles list - MOBILE card (sirf < 768px; desktop par table).
    getList.blade.php isay $purchaseTickets ke loop me include karta hai: @include(..., ['ticket' => $ticket])
    Styling: mobile-ui.css section 6 (tkc-*). Table me column / action badle to yahan bhi badlo.
--}}
@php
    $po = $ticket->purchaseOrder;
    $pf = $ticket->purchaseFreight;

    // table jaisa hisaab: freight bane ke baad division ke ghante guzar gaye aur arrival nahi hua -> laal
    $isOverdue = false;
    if ($po && $po->division && $pf && $pf->created_at) {
        $allowedTime = \Carbon\Carbon::parse($pf->created_at)->addHours($po->division->hours ?? 0);
        $isOverdue = \Carbon\Carbon::now()->greaterThan($allowedTime);
    }

    $status = $ticket->freight_status ?? 'pending';
    $editUrl = route('raw-material.sit-vehicle.edit', [$ticket->id]);
@endphp

{{-- tkc-tint-red: table me late (overdue) row laal (bg-red) hoti hai --}}
<div class="tkc {{ $isOverdue ? 'tkc-tint-red' : '' }}">
    <div class="tkc-main" role="button" tabindex="0" onclick="openModal(this,'{{ $editUrl }}','Manage SIT Vehicle', true)">

        <div class="tkc-top">
            <div class="tkc-no">#{{ $po->contract_no ?? 'N/A' }}</div>
            <span class="tkc-qc {{ $status == 'pending' ? 'is-pending' : 'is-approved' }}">{{ ucfirst($status) }}</span>
        </div>

        <div class="tkc-date">
            <i class="ft-clock"></i>
            {{ $ticket->created_at->format('d M Y, h:i A') }}
        </div>

        <div class="tkc-commodity">{{ $po->qcProduct->name ?? ($po->product->name ?? 'N/A') }}</div>

        <div class="tkc-tags">
            <span class="tkc-tag">#{{ $ticket->unique_no ?? 'N/A' }}</span>
            @if ($isOverdue)
                <span class="tkc-tag is-danger" title="The arrival for this vehicle has not been recorded yet.">
                    <i class="fa fa-exclamation-triangle"></i> Arrival not recorded
                </span>
            @endif
        </div>

        <div class="tkc-grid">
            <div>
                <span class="tkc-label">Location</span>
                <span class="tkc-val">{{ $po->location->name ?? 'N/A' }}</span>
            </div>
            <div>
                <span class="tkc-label">Supplier</span>
                <span class="tkc-val">{{ $po->supplier->name ?? 'N/A' }}</span>
            </div>
            <div>
                <span class="tkc-label">Broker</span>
                <span class="tkc-val">{{ $po->broker_one_name ?? ($po->broker_two_name ?? ($po->broker_three_name ?? 'N/A')) }}</span>
            </div>
        </div>

        <div class="tkc-chips">
            <span class="tkc-chip"><i class="ft-truck"></i>{{ $pf->truck_no ?? 'N/A' }}</span>
            <span class="tkc-chip"><i class="ft-file-text"></i>{{ $pf->bilty_no ?? 'N/A' }}</span>
        </div>
    </div>

    <div class="tkc-actions">
        <button type="button" class="tkc-btn" onclick="openModal(this,'{{ $editUrl }}','Manage SIT Vehicle', true)"
            title="Manage SIT Vehicle" aria-label="Manage SIT Vehicle">
            <i class="ft-edit"></i>
        </button>
    </div>
</div>
