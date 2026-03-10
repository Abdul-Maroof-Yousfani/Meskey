<table class="table table-bordered">
    <thead>
        <tr>
            <th>SO #</th>
            <th>SO qty</th>
            <th>Date</th>
            <th>Rate Type</th>
            <th>Rate</th>
            <th>Transporter</th>
            <th>Qty to deliver</th>
            <th>Commodity</th>
            <th>Customer name</th>
            <th>Sauda Type</th>
            <!-- <th>Address</th> -->
            <th>Status</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        @forelse($logistics as $item)
            <tr>
                <td>{{ $item->logistics->so_no ?? '' }}</td>
                <td>{{ $item->logistics->so_qty ?? '' }}</td>
                <td>{{ $item->logistics->date ?? '' }}</td>
                <td>{{ $item->rate_type }}</td>
                <td>{{ number_format($item->rate, 2) }}</td>
                <td>{{ $item->transporter }}</td>
                <td>{{ number_format($item->qty, 2) }}</td>
                <td>{{ $item->logistics->commodity ?? '' }}</td>
                <td>{{ $item->logistics->customer ?? '' }}</td>
                <td>{{ $item->logistics->sauda_type ?? '' }}</td>
                <!-- <td>{{ $item->logistics->delivery_address ?? '' }}</td> -->
                <td class="text-center">
                    @php
                        $status = $item->logistics->am_approval_status ?? 'pending';
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
                        onclick="openModal(this,'{{ route('sales.logistics.show', ['logistic' => $item->logistics_id]) }}','View Logistics',false,'90%')"
                        type="button" class="btn btn-sm btn-info" title="View">
                        <i class="ft-eye"></i>
                    </button>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="13" class="text-center">No records found.</td>
            </tr>
        @endforelse
    </tbody>
</table>
