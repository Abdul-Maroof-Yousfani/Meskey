@if($ExportQcs->count() > 0)
    <table class="table table-striped m-0">
        <thead>
            <tr>
                <th>Ticket No.</th>
                <th>Truck No.</th>
                <th>Customer</th>
                <th>Commodity</th>
                <th>Qc Status</th>
                <th>Approval Status</th>
                <th>Created</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @foreach($ExportQcs as $exportQc)
                <tr>
                    <td>
                        {{ $exportQc->loadingProgramItem->transaction_number ?? 'N/A' }}
                    </td>
                    <td>
                        {{ $exportQc->loadingProgramItem->truck_number ?? 'N/A' }}
                    </td>
                    <td>
                        {{ $exportQc->customer ?? 'N/A' }}
                    </td>
                    <td>
                        {{ $exportQc->commodity ?? 'N/A' }}
                    </td>
                    <td>
                        <span class="badge badge-{{ $exportQc->status == 'accept' ? 'success' : 'danger' }}">
                            {{ $exportQc->status === 'accept' ? 'Accepted' : 'Rejected' }}
                        </span>

                    </td>
                    <td>
                        @if($exportQc->status === 'reject')
                            @php
                                $apprStatus = $exportQc->am_approval_status ?? 'pending';
                                $apprBadge = match(strtolower((string)$apprStatus)) {
                                    'approved' => 'badge-success',
                                    'rejected' => 'badge-danger',
                                    'reverted' => 'badge-secondary',
                                    'pending'  => 'badge-warning',
                                    default    => 'badge-warning',
                                };
                            @endphp
                            <span class="badge {{ $apprBadge }} px-2 py-1">
                                {{ ucfirst($apprStatus) }}
                            </span>
                        @else
                            -
                        @endif
                    </td>
                    <td>
                        {{ $exportQc->created_at->format('d-m-Y H:i') }}
                    </td>
                    <td>
                        @if(!optional($exportQc->loadingProgramItem)->loadingSlip && $exportQc->status === 'accept')
                            {{-- <a onclick="openModal(this,'{{ route('export.export-qc.edit', $exportQc->id) }}','Edit Export QC', false)"
                                class="warning p-1 text-center mr-2 position-relative">
                                <i class="ft-edit font-medium-3"></i>
                            </a>
                            <a onclick="deletemodal('{{ route('export.export-qc.destroy', $exportQc->id) }}', '{{ route('export.get.export-qc') }}')"
                                class="danger p-1 text-center mr-2 position-relative">
                                <i class="ft-trash-2"></i>
                            </a> --}}
                        @endif
                        <a onclick="openModal(this,'{{ route('export-qc.show', $exportQc->id) }}','View Export QC', true)"
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
            {{ $ExportQcs->links() }}
        </div>
    </div>
@else
    <table class="table m-0">
        <tbody>
            <tr>
                <td colspan="7" class="text-center py-5">
                    <h5 class="text-muted">No Export QC records found</h5>
                </td>
            </tr>
        </tbody>
    </table>
@endif
