<table class="table m-0">
    <thead>
        <tr>
            <th>Request #</th>
            <th>Date</th>
            <th>Type</th>
            <th>Company Location</th>
            <th>Arrival Location</th>
            <th>Plant</th>
            <th>Job Order</th>
            <th>Remarks</th>
            <th>Status</th>
            <th class="text-right px-4">Action</th>
        </tr>
    </thead>
    <tbody>
        @forelse($items as $item)
            <tr>
                <td><strong>{{ $item->request_no }}</strong></td>
                <td>{{ \Carbon\Carbon::parse($item->request_date)->format('d-m-Y') }}</td>
                <td>
                    <span class="badge {{ $item->type_badge_class }}">
                        {{ $item->type_name }}
                    </span>
                </td>
                <td>{{ $item->companyLocation->name ?? 'N/A' }}</td>
                <td>{{ $item->arrivalLocation->name ?? 'N/A' }}</td>
                <td>{{ $item->plant->name ?? 'N/A' }}</td>
                <td>
                    @if($item->jobOrder)
                        <span class="badge badge-light text-dark font-weight-bold">
                            {{ $item->jobOrder->job_order_no }}
                        </span>
                    @else
                        <span class="text-muted">-</span>
                    @endif
                </td>
                <td>{{ Str::limit($item->remarks ?? '-', 35) }}</td>
                <td>
                    <span class="badge {{ $item->status_badge_class }}">
                        {{ ucfirst($item->status) }}
                    </span>
                </td>
                <td class="text-right px-4">
                    <div class="btn-group" role="group">
                        @if($item->status === 'pending')
                            @php
                                $createRoute = match($item->type) {
                                    \App\Models\Production\ProductionAnalysisRequest::TYPE_INPUT => route('production-input-analysis.create') . '?analysis_request_id=' . $item->id,
                                    \App\Models\Production\ProductionAnalysisRequest::TYPE_OUTPUT => route('production-output-analysis.create') . '?analysis_request_id=' . $item->id,
                                    \App\Models\Production\ProductionAnalysisRequest::TYPE_MACHINE => route('production-machine-analysis.create') . '?analysis_request_id=' . $item->id,
                                    default => '#',
                                };
                                $modalTitle = match($item->type) {
                                    \App\Models\Production\ProductionAnalysisRequest::TYPE_INPUT => 'Create Input Analysis',
                                    \App\Models\Production\ProductionAnalysisRequest::TYPE_OUTPUT => 'Create Output Analysis',
                                    \App\Models\Production\ProductionAnalysisRequest::TYPE_MACHINE => 'Create Machine Analysis',
                                    default => 'Create Analysis',
                                };
                            @endphp
                            <button onclick="openModal(this, '{{ $createRoute }}', '{{ $modalTitle }}', false, '95%')" 
                                    title="Create {{ $item->type_name }}" 
                                    class="btn btn-sm btn-success mr-1">
                                <i class="ft-play"></i> Create
                            </button>
                        @endif

                        <button onclick="openModal(this,'{{ route('production-analysis-request.show', $item->id) }}','View Analysis Request', true, '70%')" 
                                title="View Details" 
                                class="btn btn-sm btn-info mr-1">
                            <i class="ft-eye"></i>
                        </button>

                        @if($item->status === 'pending')
                            <button onclick="openModal(this,'{{ route('production-analysis-request.edit', $item->id) }}','Edit Analysis Request', false, '80%')" 
                                    title="Edit" 
                                    class="btn btn-sm btn-primary mr-1">
                                <i class="ft-edit"></i>
                            </button>
                        @endif

                        <button onclick="deletemodal('{{ route('production-analysis-request.destroy', $item->id) }}','{{ route('get.production-analysis-request') }}')" 
                                title="Delete" 
                                class="btn btn-sm btn-danger">
                            <i class="ft-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="10" class="text-center py-4 text-muted">No analysis requests found.</td>
            </tr>
        @endforelse
    </tbody>
</table>

<div class="pagination-wrapper mt-3">
    {{ $items->links('vendor.pagination.bootstrap-4') }}
</div>
