@if($SalesQcs->count() > 0)
    <table class="table table-striped m-0">
        <thead>
            <tr>
                <th>Ticket No.</th>
                <th>Truck No.</th>
                <!-- <th>Customer</th> -->
                <th>Commodity</th>
                <th>Status</th>
                <th>Created</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @foreach($SalesQcs as $salesQc)
                <tr>
                    <td>
                        {{ $salesQc->loadingProgramItem->transaction_number ?? 'N/A' }}
                    </td>
                    <td>
                        {{ $salesQc->loadingProgramItem->truck_number ?? 'N/A' }}
                    </td>
                    <!-- <td>
                                {{ $salesQc->customer ?? 'N/A' }}
                            </td> -->
                    <td>
                        {{ $salesQc->commodity ?? 'N/A' }}
                    </td>
                    <td>
                        @if($salesQc->status === 'reject')
                            @php
                                $apprStatus = $salesQc->am_approval_status ?? 'pending';
                            @endphp
                            @if($apprStatus === 'pending')
                                <span class="badge badge-warning px-2 py-1">Pending for Approval</span>
                            @elseif($apprStatus === 'approved')
                                <span class="badge badge-danger px-2 py-1">Rejected</span>
                            @else
                                <span class="badge badge-danger px-2 py-1">Rejected</span>
                            @endif
                        @else
                            <span class="badge badge-success px-2 py-1">Accepted</span>
                        @endif
                    </td>
                    <td>
                        {{ $salesQc->created_at->format('d-m-Y H:i') }}
                    </td>
                    <td>
                        @if(!optional($salesQc->loadingProgramItem)->loadingSlip && $salesQc->status === 'accept')
                            {{-- <a
                                onclick="openModal(this,'{{ route('sales.sales-qc.edit', $salesQc->id) }}','Edit Sales QC', false)"
                                class="warning p-1 text-center mr-2 position-relative">
                                <i class="ft-edit font-medium-3"></i>
                            </a>
                            <a onclick="deletemodal('{{ route('sales.sales-qc.destroy', $salesQc->id) }}', '{{ route('sales.get.sales-qc') }}')"
                                class="danger p-1 text-center mr-2 position-relative">
                                <i class="ft-trash-2"></i>
                            </a> --}}
                        @endif
                        <a onclick="openModal(this,'{{ route('sales.sales-qc.show', $salesQc->id) }}','View Sales QC', true)"
                            class="info p-1 text-center mr-2 position-relative">
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
            {{ $SalesQcs->links() }}
        </div>
    </div>
@else
    <table class="table m-0">
        <tbody>
            <tr>
                <td colspan="7" class="text-center py-5">
                    <h5 class="text-muted">No Sales QC records found</h5>
                </td>
            </tr>
        </tbody>
    </table>
@endif