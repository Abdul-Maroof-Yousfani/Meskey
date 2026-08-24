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
        @forelse($logisticsBills as $index => $bill)
            <tr>
                <td>{{ $logisticsBills->firstItem() + $index }}</td>
                <td>
                    <strong>{{ $bill->deliveryChallan->customer->name ?? 'N/A' }}</strong>
                </td>
                <td>
                    {{ $bill->deliveryChallan && $bill->deliveryChallan->delivery_order->count() > 0 ? $bill->deliveryChallan->delivery_order->pluck('reference_no')->implode(', ') : ($bill->deliveryChallan->reference_number ?? 'N/A') }}
                </td>
                <td>
                    <strong>{{ $bill->dc_no ?? 'N/A' }}</strong>
                </td>
                <td>
                    @php
                        $uniqueCommodities = $bill->items->map(function($item) {
                            return $item->product?->name ?? $item->item_name ?? 'Unknown';
                        })->unique()->filter()->implode(', ');
                    @endphp
                    {{ $uniqueCommodities ?: 'N/A' }}
                </td>
                <td>
                    {{ $bill->truck_number ?? 'N/A' }}
                </td>
                <td>{{ $bill->dc_date ? $bill->dc_date->format('d M Y') : 'N/A' }}</td>
                <td>
                    <span class="badge badge-success px-3 py-2">
                        Approved
                    </span>
                </td>
                <td>
                    <div class="btn-group" role="group">
                        <button class="btn btn-sm btn-primary"
                            onclick="openModal(this, '{{ route('sales.logistics-bill.edit', $bill->id) }}', 'Edit Logistics Bill', false, '80%')">
                            <i class="fa fa-edit"></i> Edit
                        </button>
                        <button class="btn btn-sm btn-info"
                            onclick="openModal(this, '{{ route('sales.logistics-bill.view', $bill->id) }}', 'View Logistics Bill', false, '80%')">
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
                        <p>No logistics bills found</p>
                        <small>Approved receiving requests will automatically appear here as logistics bills.</small>
                    </div>
                </td>
            </tr>
        @endforelse
    </tbody>
</table>

<!-- Pagination -->
<div class="row d-flex" id="paginationLinks">
    <div class="col-md-12 text-right">
        {{ $logisticsBills->links() }}
    </div>
</div>
