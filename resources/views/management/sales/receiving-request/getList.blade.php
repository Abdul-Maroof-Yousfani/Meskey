<table class="table table-hover m-0">
    <thead class="bg-light">
        <tr>
            <th>#</th>
            <th>Party (Customer)</th>
            <th>DO#</th>
            <th>DC#</th>
            <th>Commodity Name</th>
            <th>Truck#</th>
            <th>Dispatch Date</th>
            <th>Status</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        @forelse($receivingRequests as $index => $request)
            <tr>
                <td>{{ $receivingRequests->firstItem() + $index }}</td>
                <td>
                    <strong>{{ $request->deliveryChallan->customer->name ?? 'N/A' }}</strong>
                </td>
                <td>
                    {{ $request->deliveryChallan && $request->deliveryChallan->delivery_order->count() > 0 ? $request->deliveryChallan->delivery_order->pluck('reference_no')->implode(', ') : ($request->deliveryChallan->reference_number ?? 'N/A') }}
                </td>
                <td>
                    <strong>{{ $request->dc_no ?? 'N/A' }}</strong>
                </td>
                <td>
                    @php
                        $uniqueCommodities = $request->items->map(function($item) {
                            return $item->product?->name ?? $item->item_name ?? 'Unknown';
                        })->unique()->filter()->implode(', ');
                    @endphp
                    {{ $uniqueCommodities ?: 'N/A' }}
                </td>
                <td>
                    {{ $request->truck_number ?? 'N/A' }}
                </td>
                <td>{{ $request->dc_date ? $request->dc_date->format('d M Y') : 'N/A' }}</td>
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
                <td colspan="9" class="text-center py-4">
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
