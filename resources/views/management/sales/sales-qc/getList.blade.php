@if($SalesQcs->count() > 0)
    <table class="table m-0">
        <tbody>
            @foreach($SalesQcs as $salesQc)
                <tr>
                    <td>
                        {{ $salesQc->loadingProgramItem->transaction_number ?? 'N/A' }}
                    </td>
                    <td>
                        {{ $salesQc->loadingProgramItem->truck_number ?? 'N/A' }}
                    </td>
                    <td>
                        {{ $salesQc->customer ?? 'N/A' }}
                    </td>
                    <td>
                        {{ $salesQc->commodity ?? 'N/A' }}
                    </td>
                    <td>
                        <span class="badge badge-{{ $salesQc->status == 'accept' ? 'success' : 'danger' }}">
                            {{ ucfirst($salesQc->status) }}
                        </span>
                    </td>
                    <td>
                        {{ $salesQc->created_at->format('d-m-Y H:i') }}
                    </td>
                    <td>
                        <a onclick="openModal(this,'{{ route('sales.sales-qc.edit', $salesQc->id) }}','Edit Sales QC', false)"
                            class="warning p-1 text-center mr-2 position-relative">
                            <i class="ft-edit font-medium-3"></i>
                        </a>
                        <a onclick="openModal(this,'{{ route('sales.sales-qc.show', $salesQc->id) }}','View Sales QC', true)"
                            class="info p-1 text-center mr-2 position-relative">
                            <i class="ft-eye font-medium-3"></i>
                        </a>
                        <a onclick="deletemodal('{{ route('sales.sales-qc.destroy', $salesQc->id) }}', '{{ route('sales.get.sales-qc') }}')"
                            class="danger p-1 text-center mr-2 position-relative">
                             <i class="ft-trash-2"></i>
                         </a>
                         
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Pagination -->
    <div class="d-flex justify-content-end mt-3">
        {{ $SalesQcs->appends(request()->query())->links() }}
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
