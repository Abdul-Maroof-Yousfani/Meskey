{{--
    Half/Full Approved list - MOBILE card (sirf < 768px; desktop par table).
    getList.blade.php isay $ArrivalApproves ke loop me include karta hai: @include(..., ['approval' => $approval])
    Styling: mobile-ui.css section 6 (tkc-*). Table me column / action badle to yahan bhi badlo.
--}}
@php
    $ticket = $approval->arrivalTicket;
    $isFull = $approval->bag_packing_approval == 'Full Approved';
    $viewUrl = route('arrival-approve.edit', $approval->id);
@endphp

<div class="tkc">
    {{-- card ka main hissa: tap = View (table wale 👁 jaisa) --}}
    <div class="tkc-main" role="button" tabindex="0"
        onclick="openModal(this,'{{ $viewUrl }}','Edit Arrival Approval', true)">

        <div class="tkc-top">
            <div class="tkc-no">#{{ $ticket->unique_no ?? '-' }}</div>
            {{-- Full = green, Half = orange --}}
            <span class="tkc-qc {{ $isFull ? 'is-approved' : 'is-pending' }}">{{ $approval->bag_packing_approval }}</span>
        </div>

        <div class="tkc-date">
            <i class="ft-clock"></i>
            {{ \Carbon\Carbon::parse($approval->created_at)->format('d M Y, h:i A') }}
        </div>

        <div class="tkc-commodity">{{ $ticket->product->name ?? '-' }}</div>

        <div class="tkc-grid">
            <div>
                <span class="tkc-label">Gala</span>
                <span class="tkc-val">{{ $approval->gala_name ?? '--' }}</span>
            </div>
            <div>
                <span class="tkc-label">Recv. / Rej.</span>
                <span class="tkc-val">{{ $approval->total_bags ?? '--' }} / {{ $approval->total_rejection ?? '0' }}</span>
            </div>
            <div>
                <span class="tkc-label">Filling Bags</span>
                <span class="tkc-val">{{ $approval->filling_bags_no ?? '--' }}</span>
            </div>
            <div>
                <span class="tkc-label">Bag Type</span>
                <span class="tkc-val">{{ $approval->bagType->name ?? '--' }}</span>
            </div>
            <div>
                <span class="tkc-label">Bag Condition</span>
                <span class="tkc-val">{{ $approval->bagCondition->name ?? '--' }}</span>
            </div>
            <div>
                <span class="tkc-label">Bag Packing</span>
                <span class="tkc-val">{{ $approval->bagPacking->name ?? '--' }}</span>
            </div>
        </div>

        <div class="tkc-chips">
            <span class="tkc-chip"><i class="ft-truck"></i>{{ $approval->truck_no ?? '—' }}</span>
            <span class="tkc-tag {{ $approval->amanat == 'Yes' ? 'is-danger' : '' }}">Amanat: {{ $approval->amanat }}</span>
        </div>
    </div>

    <div class="tkc-actions">
        <button type="button" class="tkc-btn"
            onclick="openModal(this,'{{ $viewUrl }}','Edit Arrival Approval', true)" title="View">
            <i class="ft-eye"></i><span>View</span>
        </button>
    </div>
</div>
