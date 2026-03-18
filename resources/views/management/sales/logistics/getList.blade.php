<table class="table table-bordered">
    <thead>
        <tr>
            <th>Date</th>
            <th>SO #</th>
            <th>SO qty</th>
            <th>Commodity</th>
            <th>Customer name</th>
            <th>Sauda Type</th>
            <th>Transporters</th>
            <th>Total Qty</th>
            <th>Status</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        @forelse($logistics as $logistic)
            <tr>
                <td>{{ $logistic->date ?? '' }}</td>
                <td>{{ $logistic->so_no ?? '' }}</td>
                <td>{{ number_format($logistic->so_qty, 2) }}</td>
                <td>{{ $logistic->commodity ?? '' }}</td>
                <td>{{ $logistic->customer ?? '' }}</td>
                <td>{{ ucfirst($logistic->sauda_type) }}</td>
                <td>
                    {{ $logistic->items->pluck('transporter_name')->unique()->implode(', ') }}
                </td>
                <td>{{ number_format($logistic->items->sum('qty'), 2) }}</td>
                <td class="text-center">
                    @php
                        $status = $logistic->am_approval_status ?? 'pending';
                        $badge = match(strtolower($status)) {
                            'approved' => 'badge-success',
                            'rejected' => 'badge-danger',
                            'pending'  => 'badge-warning',
                            default    => 'badge-secondary',
                        };
                    @endphp
                    <span class="badge {{ $badge }} px-2 py-1">
                        {{ ucfirst($status) }}
                    </span>
                </td>
                <td class="text-center">
                    <button
                        onclick="openModal(this,'{{ route('sales.logistics.show', ['logistic' => $logistic->id]) }}','View Logistics',false,'90%')"
                        type="button" class="btn btn-sm btn-info" title="View">
                        <i class="ft-eye"></i>
                    </button>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="10" class="text-center">No records found.</td>
            </tr>
        @endforelse
    </tbody>
</table>
