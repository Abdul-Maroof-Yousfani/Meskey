<table class="table table-hover m-0">
    <thead class="bg-light">
        <tr>
            <th>#</th>
            <th>DC No</th>
            <th>DC Date</th>
            <th>Items Count</th>
            <th>Status</th>
            <th>Created At</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        @forelse($receivingRequests as $index => $request)
            <tr>
                <td>{{ $receivingRequests->firstItem() + $index }}</td>
                <td>
                    <strong>{{ $request->dc_no ?? 'N/A' }}</strong>
                </td>
                <td>{{ $request->dc_date ? $request->dc_date->format('d M Y') : 'N/A' }}</td>
                <td>
                    <span class="badge badge-info px-2 py-1">{{ $request->items->count() }} items</span>
                </td>
                <td>
                    @php
                        $status = $request->am_approval_status;
                        $badge = match(strtolower($status)) {
                            'approved' => 'badge-success',
                            'rejected' => 'badge-danger',
                            'pending'  => 'badge-warning',
                            default    => 'badge-secondary',
                        };
                    @endphp
                    <span class="badge {{ $badge }} px-3 py-2">
                        {{ ucfirst($status) }}
                    </span>
                </td>
                <td>{{ $request->created_at->format('d M Y H:i') }}</td>
                <td>
                    <div class="btn-group" role="group">
                        @if($request->am_approval_status !== "approved")
                            <button class="btn btn-sm btn-primary"
                                onclick="openModal(this, '{{ route('sales.receiving-request.edit', $request->id) }}', 'Edit Receiving Request', false, '80%')">
                                <i class="fa fa-edit"></i> Edit
                            </button>
                        @endif
                        <button class="btn btn-sm btn-info"
                            onclick="openModal(this, '{{ route('sales.receiving-request.view', $request->id) }}', 'View Receiving Request', false, '80%')">
                            <i class="fa fa-eye"></i> View
                        </button>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="12" class="text-center py-4">
                    <div class="text-muted">
                        <i class="fa fa-inbox fa-3x mb-2"></i>
                        <p>No receiving requests found</p>
                        <small>Receiving requests are automatically created when Delivery Challans are created.</small>
                    </div>
                </td>
            </tr>
        @endforelse
    </tbody>
</table>

<!-- Pagination -->
<div class="row d-flex" id="paginationLinks">
    <div class="col-md-12 text-right">
        {{ $receivingRequests->links() }}
    </div>
</div>
