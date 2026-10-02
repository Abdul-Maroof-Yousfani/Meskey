<table class="table table-hover m-0">
    <thead class="thead-light">
        <tr>
            <th width="15%">Job Order #</th>
            <th width="10%">Date</th>
            <th width="14%">Location</th>
            <th width="20%">Phases</th>
            <th width="12%">Export Order</th>
            <th width="10%">Ref No</th>
            <th width="9%">Status</th>
            <th width="10%">Actions</th>
        </tr>
    </thead>
    <tbody>
        @if (count($jobOrders) != 0)
            @foreach ($jobOrders as $jo)
                <tr>
                    <td>
                        <div>
                            <strong class="d-block">{{ $jo->job_order_no }}</strong>
                            @if($jo->product)
                                <small class="text-muted">{{ $jo->product->name }}</small>
                            @endif
                        </div>
                    </td>
                    <td>{{ $jo->job_order_date ? \Carbon\Carbon::parse($jo->job_order_date)->format('d/m/Y') : 'N/A' }}</td>
                    <td>
                        <span class="badge badge-light">{{ $jo->companyLocation->name ?? 'N/A' }}</span>
                    </td>
                    <td>
                        @php
                            $phaseIds = (array)($jo->active_phases ?? ($jo->companyLocation->production_phases ?? []));
                        @endphp
                        @if(empty($phaseIds))
                            <span class="text-muted small">-</span>
                        @else
                            @foreach($phaseIds as $pId)
                                @php
                                    $pObj = $allPhases[$pId] ?? null;
                                @endphp
                                @if($pObj)
                                    <span class="badge badge-light mr-1">{{ $pObj->name }}</span>
                                @endif
                            @endforeach
                        @endif
                    </td>
                    <td>
                        @if($jo->exportOrder)
                            {{ $jo->exportOrder->voucher_no ?? ('#' . $jo->export_order_id) }}
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>
                    <td>{{ $jo->ref_no ?: '-' }}</td>
                    <td>
                        <span class="badge badge-secondary">{{ strtoupper(str_replace('_', ' ', $jo->status)) }}</span>
                    </td>
                    <td>
                        <div role="group">
                            <a href="{{ route('production.job-orders.edit', $jo->id) }}" 
                               onclick="loadPageContent('{{ route('production.job-orders.edit', $jo->id) }}')"
                               class="btn btn-outline-primary" title="Edit">
                                <i class="ft-edit"></i>
                            </a>
                            <button type="button" 
                                    onclick="deletemodal('{{ route('production.job-orders.destroy', $jo->id) }}','{{ route('production.job-orders.getList') }}')"
                                    class="btn btn-outline-danger" title="Delete">
                                <i class="ft-trash"></i>
                            </button>
                            <a href="{{ route('production.job-orders.show', $jo->id) }}" 
                               onclick="loadPageContent('{{ route('production.job-orders.show', $jo->id) }}')"
                               class="btn btn-outline-info" title="View">
                                <i class="ft-eye"></i>
                            </a>
                        </div>
                    </td>
                </tr>
            @endforeach
        @else
            <tr>
                <td colspan="8" class="text-center py-4">
                    <div class="empty-state">
                        <i class="ft-briefcase ft-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No Job Orders Found</h5>
                        <p class="text-muted mb-3">Get started by creating your first job order</p>
                        <a href="{{ route('production.job-orders.create') }}" 
                           onclick="loadPageContent('{{ route('production.job-orders.create') }}')" 
                           class="btn btn-primary">
                            <i class="ft-plus mr-1"></i> Create Job Order
                        </a>
                    </div>
                </td>
            </tr>
        @endif
    </tbody>
</table>

@if (count($jobOrders) != 0)
<div class="row mt-3">
    <div class="col-md-12">
        <div class="float-right" id="paginationLinks">
            {{ $jobOrders->links() }}
        </div>
    </div>
</div>
@endif
