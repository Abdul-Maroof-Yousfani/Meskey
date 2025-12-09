<table class="table table-hover m-0">
    <thead class="thead-light">
        <tr>
            <th>Prod. No</th>
            <th>Date</th>
            <th>Job Order</th>
            <th>Location</th>
            <th>Produced QTY (kg)</th>
            <th>Supervisor</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @if (count($productionVouchers) != 0)
            @foreach ($productionVouchers as $voucher)
                <tr>
                    <td>
                        <strong class="d-block">{{ $voucher->prod_no }}</strong>
                    </td>
                    <td>{{ \Carbon\Carbon::parse($voucher->prod_date)->format('M d, Y') }}</td>
                    <td>
                        <span class="badge badge-primary">{{ $voucher->jobOrder->job_order_no ?? 'N/A' }}</span>
                    </td>
                    <td>
                        {{ $voucher->location->name ?? 'N/A' }}
                    </td>
                    <td>
                        <strong>{{ number_format($voucher->produced_qty_kg, 2) }}</strong> kgs
                        <br>
                        <small class="text-muted">{{ number_format($voucher->produced_qty_kg / 1000, 2) }} MT</small>
                    </td>
                    <td>
                        {{ $voucher->supervisor->name ?? '-' }}
                    </td>
                    <td>
                        @if($voucher->status == 'draft')
                            <span class="badge badge-warning">Draft</span>
                        @elseif($voucher->status == 'completed')
                            <span class="badge badge-success">Completed</span>
                        @elseif($voucher->status == 'approved')
                            <span class="badge badge-info">Approved</span>
                        @endif
                    </td>
                    <td>
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" 
                                onclick="openModal(this,'{{ route('production-voucher.edit', $voucher->id) }}','Edit Production Voucher',false,'90%')"
                                class="btn btn-outline-primary" title="Edit">
                                <i class="ft-edit"></i>
                            </button>
                            <button type="button" 
                                onclick="deletemodal('{{ route('production-voucher.destroy', $voucher->id) }}','{{ route('get.production-voucher') }}')"
                                class="btn btn-outline-danger" title="Delete">
                                <i class="ft-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            @endforeach
        @else
            <tr>
                <td colspan="8" class="text-center py-4">
                    <div class="empty-state">
                        <i class="ft-clipboard ft-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No Production Voucher Found</h5>
                        <p class="text-muted mb-3">Get started by creating your first production voucher</p>
                        <button onclick="openModal(this,'{{ route('production-voucher.create') }}','Create Production Voucher',false,'90%')" 
                                class="btn btn-primary">
                            <i class="ft-plus mr-1"></i> Create Production Voucher
                        </button>
                    </div>
                </td>
            </tr>
        @endif
    </tbody>
</table>

@if (count($productionVouchers) != 0)
<div class="row mt-3">
    <div class="col-md-12">
        <div class="float-right" id="paginationLinks">
            {{ $productionVouchers->links() }}
        </div>
    </div>
</div>
@endif
