<table class="table m-0">
    <thead>
        <tr>
            <th>S no.</th>
            <th>Export Order</th>
            <th>Buyer</th>
            <th>Documents</th>
            <th>Created By</th>
            <th>Created At</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($filings as $key => $filing)
            <tr>
                <td>{{ $filings->firstItem() + $key }}</td>
                <td>
                    {{ $filing->exportOrder->voucher_no ?? '-' }}
                    @if ($filing->exportOrder?->contract_no)
                        <br><small class="text-muted">{{ $filing->exportOrder->contract_no }}</small>
                    @endif
                </td>
                <td>{{ $filing->exportOrder->buyer->name ?? '-' }}</td>
                <td><span class="badge bg-light-primary">{{ $filing->documents_count }}</span></td>
                <td>{{ $filing->createdBy->name ?? '-' }}</td>
                <td>{{ $filing->created_at?->format('d-m-Y h:i A') }}</td>
                <td>
                    <a class="info p-1 text-center position-relative"
                        onclick="openModal(this,'{{ route('export-filing.show', $filing->id) }}','View Filing', true)">
                        <i class="ft-eye font-medium-3"></i></a>
                    <a onclick="deletemodal('{{ route('export-filing.destroy', $filing->id) }}','{{ route('get.export-filing') }}')"
                        class="danger p-1 text-center mr-2 position-relative">
                        <i class="ft-x font-medium-3"></i>
                    </a>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="text-center">
                    <div class="my-5">
                        <p class="ant-empty-description">No data</p>
                    </div>
                </td>
            </tr>
        @endforelse
    </tbody>
</table>

<div class="row d-flex" id="paginationLinks">
    <div class="col-md-12 text-right">
        {{ $filings->links() }}
    </div>
</div>
