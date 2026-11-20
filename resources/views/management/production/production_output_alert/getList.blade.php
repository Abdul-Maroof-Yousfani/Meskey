<table class="table m-0">
    <thead>
        <tr>
            <th>Alert #</th>
            <th>Date</th>
            <th>Plant</th>
            <th>Job Order</th>
            <th>Product</th>
            <th>Differences</th>
            <th>Status</th>
            <th>Re-check Request</th>
            <th class="text-right px-4">Action</th>
        </tr>
    </thead>
    <tbody>
        @forelse($items as $alert)
            <tr>
                <td>
                    {{ $alert->alert_no }}
                    @if($alert->attempt > 1)
                        <span class="badge badge-secondary ml-1" title="Raised again after a re-check">Re-check #{{ $alert->attempt - 1 }}</span>
                    @endif
                </td>
                <td>{{ $alert->created_at->format('d-m-Y h:i A') }}</td>
                <td>{{ $alert->plant->name ?? 'N/A' }}</td>
                <td>{{ $alert->jobOrder->job_order_no ?? '-' }}</td>
                <td>{{ $alert->product->name ?? 'N/A' }}</td>
                <td><span class="badge badge-warning">{{ $alert->difference_count }}</span></td>
                <td>
                    @if($alert->isPending())
                        <span class="badge badge-danger">Pending Remarks</span>
                    @else
                        <span class="badge badge-success">Remarks Submitted</span>
                    @endif
                </td>
                <td>
                    @if($alert->newAnalysisRequest)
                        {{ $alert->newAnalysisRequest->request_no }}
                        <span class="badge {{ $alert->newAnalysisRequest->status_badge_class }}">{{ ucfirst(str_replace('_', ' ', $alert->newAnalysisRequest->status)) }}</span>
                    @else
                        -
                    @endif
                </td>
                <td class="text-right px-4">
                    <button onclick="openModal(this,'{{ route('production-output-alert.show', $alert->id) }}','Output QC Alert')" title="View" class="btn btn-sm btn-info hov waves-effect waves-light">
                        <i class="ft-eye"></i>
                    </button>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="9" class="text-center">No records found.</td>
            </tr>
        @endforelse
    </tbody>
</table>

<div class="pagination-wrapper mt-3">
    {{ $items->links('vendor.pagination.bootstrap-4') }}
</div>
