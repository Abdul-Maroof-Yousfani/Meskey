@if($DispatchQcs->count() > 0)
    <table class="table table-striped m-0">
        <thead>
            <tr>
                <th>Ticket No.</th>
                <th>Truck No.</th>
                <th>Customer</th>
                <th>Commodity</th>
                <th>Status</th>
                <th>Created</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @foreach($DispatchQcs as $dispatchQc)
                <tr>
                    <td>
                        {{ $dispatchQc->loadingProgramItem->transaction_number ?? 'N/A' }}
                    </td>
                    <td>
                        {{ $dispatchQc->loadingProgramItem->truck_number ?? 'N/A' }}
                    </td>
                    <td>
                        {{ $dispatchQc->customer ?? 'N/A' }}
                    </td>
                    <td>
                        {{ $dispatchQc->commodity ?? 'N/A' }}
                    </td>
                    <td>
                        <span class="badge badge-{{ $dispatchQc->status == 'accept' ? 'success' : 'danger' }}">
                            {{ ucfirst($dispatchQc->status) }}
                        </span>
                    </td>
                    <td>
                        {{ $dispatchQc->created_at->format('d-m-Y H:i') }}
                    </td>
                    <td>
                        <a onclick="openModal(this,'{{ route('sales.dispatch-qc.show', $dispatchQc->id) }}','View Dispatch QC', true)"
                            class="info p-1 text-center mr-2 position-relative" title="View">
                            <i class="ft-eye font-medium-3"></i>
                        </a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Pagination -->
    <div class="row d-flex" id="paginationLinks">
        <div class="col-md-12 text-right">
            {{ $DispatchQcs->links() }}
        </div>
    </div>
@else
    <table class="table m-0">
        <tbody>
            <tr>
                <td colspan="7" class="text-center py-5">
                    <h5 class="text-muted">No Dispatch QC records found</h5>
                </td>
            </tr>
        </tbody>
    </table>
@endif
