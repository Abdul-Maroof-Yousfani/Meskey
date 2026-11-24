{{-- One row per plan = one job order. Manage Logs adds breakdowns; Edit changes machines / times. --}}

{{-- ===== MOBILE: cards (wahi $machinePlanSettings, koi extra query nahi) - mobile-ui.css section 6 ===== --}}
<div class="tkc-list">
    @forelse ($machinePlanSettings as $row)
        @include('management.production.machine_plan_setting.partials.mobile-card', ['row' => $row])
    @empty
        <div class="tkc-empty">
            <i class="ft-inbox"></i>
            No machine plans found
        </div>
    @endforelse
</div>

{{-- ===== DESKTOP: table (mobile par chhupa; pagination dono par) ===== --}}
<div class="tk-table-wrap">
<table class="table m-0 mps-list">
    <thead>
        <tr>
            <th>Date</th>
            <th>Job Order</th>
            <th>Plant</th>
            <th>Running Machines</th>
            <th>Breakdowns</th>
            <th>Voucher</th>
            <th>Created By</th>
            <th class="text-right">Action</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($machinePlanSettings as $row)
            @php
                $onItems = $row->items->where('is_enabled', true);
                $breakdowns = $row->items->flatMap->breakdowns;
            @endphp
            <tr>
                <td class="text-nowrap">{{ $row->date->format('d-m-Y') }}</td>
                <td>
                    @if($row->jobOrder)
                        <strong>{{ $row->jobOrder->job_order_no }}</strong>
                        <div class="text-muted small">{{ $row->jobOrder->product->name ?? '' }}</div>
                    @else
                        <span class="text-muted">--</span>
                    @endif
                </td>
                <td>
                    {{ $row->plant->name ?? '--' }}
                    <div class="text-muted small">{{ $row->companyLocation->name ?? ($row->plant->companyLocation->name ?? '') }}{{ $row->plant?->arrivalLocation ? ' / ' . $row->plant->arrivalLocation->name : '' }}</div>
                </td>
                <td>
                    @forelse($onItems->groupBy('production_machine_id') as $slots)
                        <div class="small text-nowrap">
                            <strong>{{ $slots->first()->machine->name ?? '--' }}</strong>
                            <span class="text-muted">
                                {{ $slots->filter(fn ($s) => $s->start_time && $s->end_time)->map(fn ($s) => date('h:i A', strtotime($s->start_time)) . ' - ' . date('h:i A', strtotime($s->end_time)))->implode(', ') ?: 'no time' }}
                            </span>
                        </div>
                    @empty
                        <span class="text-muted small">No machine on</span>
                    @endforelse
                </td>
                <td>
                    @if($breakdowns->isNotEmpty())
                        <span class="badge badge-warning">{{ $breakdowns->count() }} &bull; {{ number_format($breakdowns->sum('hours'), 2) }} hrs</span>
                    @else
                        <span class="text-muted small">None</span>
                    @endif
                </td>
                <td>
                    @if($row->productionVoucher)
                        {{ $row->productionVoucher->prod_no }}
                    @else
                        <span class="text-muted small">--</span>
                    @endif
                </td>
                <td>
                    {{ $row->user->name ?? '--' }}
                    @if($row->remarks)
                        <div class="text-muted small" title="{{ $row->remarks }}">{{ \Illuminate\Support\Str::limit($row->remarks, 40) }}</div>
                    @endif
                </td>
                <td class="text-right text-nowrap">
                    <button type="button" class="btn btn-sm btn-outline-warning"
                            onclick="openModal(this,'{{ route('machine-plan-setting.logs', $row->id) }}','Manage Logs - {{ $row->jobOrder->job_order_no ?? 'Plan #' . $row->id }}')"
                            title="View plan and add breakdowns">
                        <i class="ft-clipboard"></i> Manage Logs
                    </button>
                    @canAccess('machine-plan-setting-edit')
                        <a onclick="openModal(this,'{{ route('machine-plan-setting.edit', $row->id) }}','Edit Machine Plan',false,'85%')"
                           class="info p-1 ml-1" title="Edit"><i class="ft-edit font-medium-3"></i></a>
                    @endcanAccess
                    @canAccess('machine-plan-setting-delete')
                        <a onclick="deletemodal('{{ route('machine-plan-setting.destroy', $row->id) }}','{{ route('get.machine-plan-setting') }}')"
                           class="danger p-1" title="Delete"><i class="ft-x font-medium-3"></i></a>
                    @endcanAccess
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="8" class="text-center text-muted py-4">No machine plans found.</td>
            </tr>
        @endforelse
    </tbody>
</table>
</div>

<div class="row d-flex" id="paginationLinks">
    <div class="col-md-12 text-right">
        {{ $machinePlanSettings->links() }}
    </div>
</div>
