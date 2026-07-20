<table class="table table-bordered">
    <thead>
        <tr>
            <th>Type</th>
            <th>Date</th>
            <th>Document #</th>
            <th>Order Qty</th>
            <th>Commodity</th>
            <th>Customer name</th>
            <th>Trade Term</th>
            <th>Logistics Partner</th>
            <th>Total Qty</th>
            <th>Status</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        @forelse($logistics as $logistic)
            <tr>
                <td>{{ str_replace('_', ' ', ucwords($logistic->type ?? 'sale_order', '_')) }}</td>
                <td>{{ $logistic->date ?? '' }}</td>
                <td>{{ $logistic->so_no ?? '' }}</td>
                <td>{{ round($logistic->so_qty) }}</td>
                <td>{{ $logistic->commodity ?? '' }}</td>
                <td>{{ $logistic->customer ?? '' }}</td>
                <td>{{ $logistic->sauda_type }}</td>
                <td>{{ $logistic->items->map(fn($item) => $item->transporter_name ?: $item->transporter?->company_name ?: $item->transporter?->name)->filter()->unique()->implode(', ') }}
                </td>
                <td>{{ round($logistic->items->sum('qty'))  }}</td>
                <td class="text-center">
                    @php
                        $status = $logistic->am_approval_status ?? 'pending';
                        $badge = match (strtolower($status)) {
                            'approved' => 'badge-success',
                            'rejected' => 'badge-danger',
                            'pending' => 'badge-warning',
                            'reverted' => 'badge-secondary',
                        };
                    @endphp
                    <span class="badge {{ $badge }} px-2 py-1">
                        {{ ucfirst($status) }}
                    </span>
                </td>
                <td class="text-center">
                    @if(auth()->user()->id == $logistic->created_by)
                        @if(in_array(strtolower($status), ['pending', 'reverted']))
                            <button
                                onclick="openModal(this,'{{ route('sales.logistics.edit', ['logistic' => $logistic->id]) }}','Edit Logistics',false,'90%')"
                                type="button" class="btn btn-sm btn-primary" title="Edit">
                                <i class="ft-edit"></i>
                            </button>
                        @endif
                    @endif
                    <button
                        onclick="openModal(this,'{{ route('sales.logistics.show', ['logistic' => $logistic->id]) }}','View Logistics',false,'90%')"
                        type="button" class="btn btn-sm btn-info" title="View">
                        <i class="ft-eye"></i>
                    </button>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="11" class="text-center">No records found.</td>
            </tr>
        @endforelse
    </tbody>
</table>