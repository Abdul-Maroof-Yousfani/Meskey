@if($LoadingSlips->count() > 0)
    <table class="table table-striped m-0">
        <thead>
            <tr>
                <th>Ticket No.</th>
                <th>Truck No.</th>
                <th>Customer</th>
                <th>Commodity</th>
                <th>No. of Bags</th>
                <th>Kilogram</th>
                <th>Dispatch QC</th>
                <th>Created</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @foreach($LoadingSlips as $loadingSlip)
                <tr>
                    <td>{{ $loadingSlip->loadingProgramItem->transaction_number ?? 'N/A' }}</td>
                    <td>{{ $loadingSlip->loadingProgramItem->truck_number ?? 'N/A' }}</td>
                    <td>{{ $loadingSlip->customer ?? 'N/A' }}</td>
                    <td>{{ $loadingSlip->commodity ?? 'N/A' }}</td>
                    <td>{{ $loadingSlip->no_of_bags }}</td>
                    <td>{{ number_format($loadingSlip->kilogram, 2) }}</td>
                    <td>
                        @php($dispatchQc = $loadingSlip->loadingProgramItem->exportDispatchQc)
                        @if($dispatchQc)
                            @php($isEditedAfterRejection = $dispatchQc->status === 'reject' && $loadingSlip->logs->where('dispatch_qc_id', $dispatchQc->id)->isNotEmpty())
                            @if($dispatchQc->status === 'accept')
                                <span class="badge badge-success">Accepted</span>
                            @elseif($dispatchQc->status === 'reject' && $isEditedAfterRejection)
                                <span class="badge badge-warning">Pending</span>
                            @elseif($dispatchQc->status === 'reject')
                                <span class="badge badge-danger">Rejected</span>
                            @else
                                <span class="badge badge-secondary">{{ ucfirst($dispatchQc->status) }}</span>
                            @endif
                        @else
                            <span class="badge badge-warning">Pending</span>
                        @endif
                    </td>
                    <td>{{ $loadingSlip->created_at->format('d-m-Y H:i') }}</td>
                    <td>
                        @if($loadingSlip->canBeEdited())
                        <a onclick="openModal(this,'{{ route('export-loading-slip.edit', $loadingSlip->id) }}','Edit Export Loading Slip', false)"
                            class="warning p-1 text-center mr-2 position-relative">
                            <i class="ft-edit font-medium-3"></i>
                        </a>
                        <a onclick="deletemodal('{{ route('export-loading-slip.destroy', $loadingSlip->id) }}', '{{ route('get.export-loading-slip') }}')"
                            class="danger p-1 text-center mr-2 position-relative">
                            <i class="ft-trash-2"></i>
                        </a>
                        @endif
                        <a onclick="openModal(this,'{{ route('export-loading-slip.show', $loadingSlip->id) }}','View Export Loading Slip', true)"
                            class="info p-1 text-center mr-2 position-relative">
                            <i class="ft-eye font-medium-3"></i>
                        </a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="row d-flex" id="paginationLinks">
        <div class="col-md-12 text-right">
            {{ $LoadingSlips->links() }}
        </div>
    </div>
@else
    <table class="table m-0">
        <tbody>
            <tr>
                <td colspan="9" class="text-center py-5">
                    <h5 class="text-muted">No Export Loading Slip records found</h5>
                </td>
            </tr>
        </tbody>
    </table>
@endif
