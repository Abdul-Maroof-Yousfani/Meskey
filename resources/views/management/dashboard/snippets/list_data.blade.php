<div class="table-responsive">
    <table class="table table-striped table-hover">
        <thead class="thead-dark">
            <tr>
                @if (in_array($type, ['new_tickets', 'location_transfer_pending', 'rejected_tickets', 'freight_ready']))
                    <th>Ticket #</th>
                    <th>Product</th>
                    <th>Truck No</th>
                    <th>Bilty No</th>
                    <th>Station</th>
                    <th>Accounts Of</th>
                    <th>Bags</th>
                    <th>Created</th>
                @elseif(in_array($type, [
                        'initial_sampling_done',
                        'resampling_required',
                        'inner_sampling_requested',
                        'inner_sampling_pending_approval',
                    ]))
                    <th>Ticket #</th>
                    <th>Product</th>
                    <th>Truck No</th>
                    <th>Sampling Type</th>
                    <th>Status</th>
                    <th>Created</th>
                @elseif($type == 'first_weighbridge_pending')
                    <th>Ticket #</th>
                    <th>Product</th>
                    <th>Truck No</th>
                    <th>Location</th>
                    <th>Station</th>
                    <th>Bags</th>
                    <th>Created</th>
                @elseif($type == 'half_full_approve_pending')
                    <th>Ticket #</th>
                    <th>Product</th>
                    <th>Truck No</th>
                    <th>First Weight</th>
                    <th>Station</th>
                    <th>Bags</th>
                    <th>Created</th>
                @elseif($type == 'second_weighbridge_pending')
                    <th>Ticket #</th>
                    <th>Product</th>
                    <th>Truck No</th>
                    <th>Approval Status</th>
                    <th>Station</th>
                    <th>Bags</th>
                    <th>Created</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @forelse($data as $item)
                <tr>
                    @if (in_array($type, ['new_tickets', 'location_transfer_pending', 'rejected_tickets', 'freight_ready']))
                        <td>{{ $item->unique_no ?? 'N/A' }}</td>
                        <td>{{ $item->product->name ?? 'N/A' }}</td>
                        <td>{{ $item->truck_no ?? 'N/A' }}</td>
                        <td>{{ $item->bilty_no ?? 'N/A' }}</td>
                        <td>{{ $item->station->name ?? 'N/A' }}</td>
                        <td>{{ $item->accountsOf->name ?? 'N/A' }}</td>
                        <td>{{ $item->bags ?? 'N/A' }}</td>
                        <td>{{ $item->created_at->format('d/m/Y') }}</td>
                    @elseif(in_array($type, [
                            'initial_sampling_done',
                            'resampling_required',
                            'inner_sampling_requested',
                            'inner_sampling_pending_approval',
                        ]))
                        <td>{{ $item->arrivalTicket->unique_no ?? 'N/A' }}</td>
                        <td>{{ $item->arrivalTicket->product->name ?? 'N/A' }}</td>
                        <td>{{ $item->arrivalTicket->truck_no ?? 'N/A' }}</td>
                        <td>{{ ucfirst($item->sampling_type) ?? 'N/A' }}</td>
                        <td>
                            <span
                                class="badge badge-{{ $item->approved_status === 'pending' ? 'warning' : ($item->approved_status === 'approved' ? 'success' : 'danger') }}">
                                {{ ucfirst($item->approved_status) ?? 'N/A' }}
                            </span>
                        </td>
                        <td>{{ $item->created_at->format('d/m/Y') }}</td>
                    @elseif($type == 'first_weighbridge_pending')
                        <td>{{ $item->unique_no ?? 'N/A' }}</td>
                        <td>{{ $item->product->name ?? 'N/A' }}</td>
                        <td>{{ $item->truck_no ?? 'N/A' }}</td>
                        <td>{{ $item->unloadingLocation->location_name ?? 'N/A' }}</td>
                        <td>{{ $item->station->name ?? 'N/A' }}</td>
                        <td>{{ $item->bags ?? 'N/A' }}</td>
                        <td>{{ $item->created_at->format('d/m/Y') }}</td>
                    @elseif($type == 'half_full_approve_pending')
                        <td>{{ $item->unique_no ?? 'N/A' }}</td>
                        <td>{{ $item->product->name ?? 'N/A' }}</td>
                        <td>{{ $item->truck_no ?? 'N/A' }}</td>
                        <td>{{ $item->firstWeighbridge->first_weight ?? 'N/A' }}</td>
                        <td>{{ $item->station->name ?? 'N/A' }}</td>
                        <td>{{ $item->bags ?? 'N/A' }}</td>
                        <td>{{ $item->created_at->format('d/m/Y') }}</td>
                    @elseif($type == 'second_weighbridge_pending')
                        <td>{{ $item->unique_no ?? 'N/A' }}</td>
                        <td>{{ $item->product->name ?? 'N/A' }}</td>
                        <td>{{ $item->truck_no ?? 'N/A' }}</td>
                        <td>
                            <span
                                class="badge badge-{{ $item->document_approval_status === 'half_approved' ? 'warning' : 'success' }}">
                                {{ ucfirst(str_replace('_', ' ', $item->document_approval_status)) ?? 'N/A' }}
                            </span>
                        </td>
                        <td>{{ $item->station->name ?? 'N/A' }}</td>
                        <td>{{ $item->bags ?? 'N/A' }}</td>
                        <td>{{ $item->created_at->format('d/m/Y') }}</td>
                    @endif
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center">No data found for the selected criteria.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if ($data->hasPages())
    <div class="d-flex justify-content-center">
        {{ $data->links() }}
    </div>
@endif
