{{--
    Purchase Orders (raw material) list - MOBILE card (sirf < 768px; desktop par sticky table).
    getList.blade.php isay loop me include karta hai: @include(..., ['row' => $row, 'st' => $poStats[$row->id]])
    $st = truck ka hisaab (getList upar EK dafa nikalta hai; table + card dono wahi use karte hain, queries double nahi).
    Styling: mobile-ui.css section 6 (tkc-*). Table me column / action badle to yahan bhi badlo.
--}}
@php
    $isClosed = ($row->status == 'completed' || $row->status == 'cancelled' || in_array($row->contract_status, ['close-contract-due-to-market-down', 'close-with-market-rate-penalty']))
        && $row->contract_status !== 'reopen-contract-closed-by-mistake';
    $isPending = !$isClosed && (in_array($row->status, ['draft', 'confirmed']) || $row->contract_status === 'reopen-contract-closed-by-mistake');
    $statusText = $isClosed ? 'Closed' : ($isPending ? 'Pending' : ucfirst((string) $row->status));
    $statusClass = $isClosed ? 'is-rejected' : ($isPending ? 'is-pending' : '');

    $amStatus = strtolower($row->am_approval_status ?? 'pending');
    $amClass = match ($amStatus) {
        'approved' => 'is-inner',
        'rejected' => 'is-danger',
        'pending' => 'is-warn',
        default => '',
    };

    $isGate = $row->purchase_type == 'gate_buying';
    $byTrucks = $row->calculation_type == 'trucks';
    $arrivedQty = $row->totalArrivedNetWeight->total_arrived_net_weight ?? 0;

    $canEdit = ($row->created_by == auth()->user()->id && $row->am_approval_status != 'approved' && $row->am_approval_status != 'rejected')
        || canAccess('procurement-raw-purchase-order-edit');
    $editUrl = route($isGate ? 'raw-material.gate-buying.edit' : 'raw-material.purchase-order.edit', $row->id);
    $editTitle = $isGate ? 'Edit Gate Buying' : 'Edit Purchase Order';
    $viewUrl = route('raw-material.purchase-order.view', $row->id);
@endphp

{{-- side wali rangeen line nahi (Closed badge + Expired tag kaafi hain) --}}
<div class="tkc {{ $row->hasExpired() ? 'tkc-tint-yellow' : '' }}">
    {{-- card ka main hissa: tap = View --}}
    <div class="tkc-main" role="button" tabindex="0"
        onclick="openModal(this,'{{ $viewUrl }}','View Purchase Order')">

        <div class="tkc-top">
            <div class="tkc-no">#{{ $row->contract_no }}</div>
            <span class="tkc-qc {{ $statusClass }}">{{ $statusText }}</span>
        </div>

        <div class="tkc-date">
            <i class="ft-calendar"></i>
            Expiry: {{ $row->delivery_date ? \Carbon\Carbon::parse($row->delivery_date)->format('d M Y') : 'N/A' }}
        </div>

        <div class="tkc-commodity">{{ $row->product->name ?? 'N/A' }}</div>

        <div class="tkc-tags">
            <span class="tkc-tag {{ $amClass }}">Approval: {{ ucfirst($amStatus) }}</span>
            @if ($row->saudaType->name ?? null)
                <span class="tkc-tag">{{ $row->saudaType->name }}</span>
            @endif
            @if ($row->is_replacement == 1)
                <span class="tkc-tag is-inner">Replacement</span>
            @endif
            @if ($row->hasExpired())
                <span class="tkc-tag is-warn">Expired</span>
            @endif
            @if ($row->defaulter == 1)
                <span class="tkc-tag is-danger">Defaulter</span>
            @endif
        </div>

        <div class="tkc-grid">
            <div>
                <span class="tkc-label">Supplier</span>
                <span class="tkc-val">{{ $isGate ? ($row->supplier_name ?? 'N/A') : ($row->supplier->name ?? 'N/A') }}</span>
            </div>
            <div>
                <span class="tkc-label">Broker</span>
                <span class="tkc-val">{{ $row->broker_one_name ?? ($row->broker_two_name ?? ($row->broker_three_name ?? 'N/A')) }}</span>
            </div>
            <div>
                <span class="tkc-label">Rate (KG)</span>
                <span class="tkc-val">{{ $row->rate_per_kg ?? 'N/A' }}</span>
            </div>
            <div>
                <span class="tkc-label">Decision Of</span>
                <span class="tkc-val">{{ $row->decisionOfUser->name ?? 'N/A' }}</span>
            </div>
            <div>
                <span class="tkc-label">Ordered Qty</span>
                <span class="tkc-val">{{ (isset($row->min_quantity) ? intval($row->min_quantity) : '-') . ' - ' . (isset($row->max_quantity) ? intval($row->max_quantity) : '-') }}</span>
            </div>
            <div>
                <span class="tkc-label">Arrived Qty</span>
                <span class="tkc-val">{{ $arrivedQty }}</span>
            </div>
            <div>
                <span class="tkc-label">Trucks (Ord / Arr / Bal)</span>
                <span class="tkc-val">
                    {{ $byTrucks ? ($row->no_of_trucks ?? 0) : 'N/A' }} / {{ $st['arrivedTrucks'] }} / {{ $byTrucks ? $st['balanceTrucks'] : 'N/A' }}
                </span>
            </div>
            <div>
                <span class="tkc-label">In Transit / Rejected</span>
                <span class="tkc-val">{{ $row->stockInTransitTickets->count() }} / {{ $st['totalRejectedTrucks'] }}</span>
            </div>
            <div>
                <span class="tkc-label">Created By</span>
                <span class="tkc-val">{{ $row->createdByUser->name ?? 'N/A' }}</span>
            </div>
        </div>

        @if ($row->remarks)
            <div class="tkc-remark"><i class="ft-message-square"></i>{{ $row->remarks }}</div>
        @endif
    </div>

    {{-- View bahar; baaki (Edit / View Trucks / Audit Logs / Delete) ⋯ sheet me (public/management/assets/js/mobile-card-sheet.js) --}}
    <div class="tkc-actions">
        <button type="button" class="tkc-btn" onclick="openModal(this,'{{ $viewUrl }}','View Purchase Order')" title="View">
            <i class="ft-eye"></i><span>View</span>
        </button>
        <button type="button" class="tkc-more" aria-label="More actions" title="More actions"
            data-title="#{{ $row->contract_no }}">
            <i class="ft-more-horizontal"></i>
        </button>
    </div>

    <template class="tkc-extra">
        @if ($canEdit)
            <a href="javascript:;" class="tkc-sheet-item" onclick="openModal(this,'{{ $editUrl }}','{{ $editTitle }}')">
                <i class="ft-edit"></i><span>{{ $editTitle }}</span>
            </a>
        @endif
        @canAccess('procurement-raw-purchase-order-view-trucks')
        <a href="javascript:;" class="tkc-sheet-item"
            onclick="openModal(this,'{{ route('raw-material.purchase-order.trucks', $row->id) }}','Trucks: Contract #{{ $row->contract_no }}', true, '55%')">
            <i class="fa fa-truck"></i><span>View Trucks</span>
        </a>
        @endcanAccess
        <a href="javascript:;" class="tkc-sheet-item"
            onclick="openModal(this,'{{ route('raw-material.purchase-order.audit-logs', $row->id) }}','Audit Logs: Contract #{{ $row->contract_no }}', false, '55%')">
            <i class="fa fa-history"></i><span>Audit Logs</span>
        </a>
        @canAccess('procurement-raw-purchase-order-delete')
        <a href="javascript:;" class="tkc-sheet-item is-danger"
            onclick="deletemodal('{{ route('raw-material.purchase-order.destroy', $row->id) }}', '{{ route('raw-material.get.purchase-order') }}')">
            <i class="ft-x"></i><span>Delete</span>
        </a>
        @endcanAccess
    </template>
</div>
