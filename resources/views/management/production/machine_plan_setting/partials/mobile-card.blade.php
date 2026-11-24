{{--
    Machine Plan Setting list - MOBILE card (sirf < 768px; desktop par table).
    getList.blade.php isay $machinePlanSettings ke loop me include karta hai: @include(..., ['row' => $row])
    Styling: mobile-ui.css section 6 (tkc-*). Table me column / action badle to yahan bhi badlo.
--}}
@php
    $onItems = $row->items->where('is_enabled', true);
    $breakdowns = $row->items->flatMap->breakdowns;
    $title = $row->jobOrder->job_order_no ?? 'Plan #' . $row->id;
    $logsUrl = route('machine-plan-setting.logs', $row->id);
@endphp

<div class="tkc">
    {{-- card ka main hissa: tap = Manage Logs (table wala pehla button) --}}
    <div class="tkc-main" role="button" tabindex="0"
        onclick="openModal(this,'{{ $logsUrl }}','Manage Logs - {{ $title }}')">

        <div class="tkc-top">
            <div class="tkc-no">#{{ $title }}</div>
            @if ($breakdowns->isNotEmpty())
                <span class="tkc-tag is-warn">{{ $breakdowns->count() }} &bull; {{ number_format($breakdowns->sum('hours'), 2) }} hrs</span>
            @endif
        </div>

        <div class="tkc-date">
            <i class="ft-calendar"></i>
            {{ $row->date->format('d M Y') }}
        </div>

        <div class="tkc-commodity">{{ $row->jobOrder->product->name ?? '--' }}</div>

        <div class="tkc-grid">
            <div>
                <span class="tkc-label">Plant</span>
                <span class="tkc-val">{{ $row->plant->name ?? '--' }}</span>
            </div>
            <div>
                <span class="tkc-label">Location</span>
                <span class="tkc-val">{{ $row->companyLocation->name ?? ($row->plant->companyLocation->name ?? '--') }}{{ $row->plant?->arrivalLocation ? ' / ' . $row->plant->arrivalLocation->name : '' }}</span>
            </div>
            <div>
                <span class="tkc-label">Voucher</span>
                <span class="tkc-val">{{ $row->productionVoucher->prod_no ?? '--' }}</span>
            </div>
            <div>
                <span class="tkc-label">Created By</span>
                <span class="tkc-val">{{ $row->user->name ?? '--' }}</span>
            </div>
        </div>

        {{-- chalne wali machines + unke time --}}
        <div class="tkc-chips">
            @forelse ($onItems->groupBy('production_machine_id') as $slots)
                <span class="tkc-chip"><i class="fa fa-cogs"></i>{{ $slots->first()->machine->name ?? '--' }}
                    <small class="ml-1">{{ $slots->filter(fn ($s) => $s->start_time && $s->end_time)->map(fn ($s) => date('h:i A', strtotime($s->start_time)) . ' - ' . date('h:i A', strtotime($s->end_time)))->implode(', ') ?: 'no time' }}</small>
                </span>
            @empty
                <span class="tkc-chip"><i class="ft-power"></i>No machine on</span>
            @endforelse
        </div>

        @if ($row->remarks)
            <div class="tkc-remark">{{ \Illuminate\Support\Str::limit($row->remarks, 80) }}</div>
        @endif
    </div>

    {{-- saare actions ⋯ me (neeche se sheet khulti hai - mobile-card-sheet.js) --}}
    <div class="tkc-actions">
        <button type="button" class="tkc-more" aria-label="Actions" title="Actions" data-title="#{{ $title }}">
            <i class="ft-more-horizontal"></i>
        </button>
    </div>

    <template class="tkc-extra">
        <a href="javascript:;" class="tkc-sheet-item"
            onclick="openModal(this,'{{ $logsUrl }}','Manage Logs - {{ $title }}')">
            <i class="ft-clipboard"></i><span>Manage Logs</span>
        </a>
        @canAccess('machine-plan-setting-edit')
            <a href="javascript:;" class="tkc-sheet-item"
                onclick="openModal(this,'{{ route('machine-plan-setting.edit', $row->id) }}','Edit Machine Plan',false,'85%')">
                <i class="ft-edit"></i><span>Edit</span>
            </a>
        @endcanAccess
        @canAccess('machine-plan-setting-delete')
            <a href="javascript:;" class="tkc-sheet-item is-danger"
                onclick="deletemodal('{{ route('machine-plan-setting.destroy', $row->id) }}','{{ route('get.machine-plan-setting') }}')">
                <i class="ft-trash-2"></i><span>Delete</span>
            </a>
        @endcanAccess
    </template>
</div>
