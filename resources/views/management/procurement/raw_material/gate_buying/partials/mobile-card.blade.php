{{--
    Gate Buying list - MOBILE card (sirf < 768px; desktop par table).
    getList.blade.php isay $arrivalPurchaseOrder ke loop me include karta hai: @include(..., ['row' => $row])
    Styling: mobile-ui.css section 6 (tkc-*). Table me column / action badle to yahan bhi badlo.
--}}
@php
    $canEdit = auth()->user()->can('role-edit');
    $editUrl = route('raw-material.gate-buying.edit', $row->id);
@endphp

<div class="tkc">
    <div class="tkc-main" @if ($canEdit) role="button" tabindex="0"
        onclick="openModal(this,'{{ $editUrl }}','Edit Purchase Order')" @endif>

        <div class="tkc-top">
            <div class="tkc-no">#{{ $row->contract_no }}</div>
        </div>

        <div class="tkc-date">
            <i class="ft-clock"></i>
            {{ \Carbon\Carbon::parse($row->created_at)->format('d M Y, h:i A') }}
        </div>

        <div class="tkc-commodity">{{ $row->product->name ?? 'N/A' }}</div>

        <div class="tkc-grid">
            <div>
                <span class="tkc-label">Supplier</span>
                <span class="tkc-val">{{ $row->supplier->name ?? 'N/A' }}</span>
            </div>
            <div>
                <span class="tkc-label">Purchaser</span>
                <span class="tkc-val">{{ $row->decisionOfUser->name ?? 'N/A' }}</span>
            </div>
            <div>
                <span class="tkc-label">Contact Person</span>
                <span class="tkc-val">{{ $row->contact_person_name ?? 'N/A' }}</span>
            </div>
            <div>
                <span class="tkc-label">Rate / KG</span>
                <span class="tkc-val">{{ $row->rate_per_kg ?? 0 }}</span>
            </div>
            <div>
                <span class="tkc-label">Rate / Mound</span>
                <span class="tkc-val">{{ $row->rate_per_mound ?? 0 }}</span>
            </div>
            <div>
                <span class="tkc-label">Rate / 100KG</span>
                <span class="tkc-val">{{ $row->rate_per_100kg ?? 0 }}</span>
            </div>
        </div>
    </div>

    @if ($canEdit || auth()->user()->can('role-delete'))
        <div class="tkc-actions">
            @if ($canEdit)
                <button type="button" class="tkc-btn tkc-btn-warn" onclick="openModal(this,'{{ $editUrl }}','Edit Purchase Order')" title="Edit">
                    <i class="ft-edit"></i><span>Edit</span>
                </button>
            @endif
            @can('role-delete')
                <button type="button" class="tkc-btn tkc-btn-danger tkc-btn-icon"
                    onclick="deletemodal('{{ route('raw-material.gate-buying.destroy', $row->id) }}','{{ route('raw-material.get.purchase-order') }}')"
                    title="Delete" aria-label="Delete">
                    <i class="ft-x"></i>
                </button>
            @endcan
        </div>
    @endif
</div>
