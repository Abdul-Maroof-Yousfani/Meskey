<table class="table table-hover m-0">
    <thead class="thead-light">
        <tr>
            <th>QC No</th>
            <th>Date</th>
            <th>Job Order</th>
            <th>Location</th>
            <!-- <th>Mill</th> -->
            <th>Commodities</th>
            <th>Total Qty (kgs)</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @if (count($qcs) != 0)
            @foreach ($qcs as $qc)
                <tr>
                    <td>
                        <strong class="d-block">{{ $qc->qc_no }}</strong>
                        <!-- <small class="text-muted">Ref: {{ $qc->jobOrder->ref_no ?? 'N/A' }}</small> -->
                    </td>
                    <td>{{ \Carbon\Carbon::parse($qc->qc_date)->format('M d, Y') }}</td>
                    <td>
                        <span class="badge badge-primary">{{ $qc->jobOrder->job_order_no }}</span>
                    </td>
                    <td>
                        {{ $qc->location->name ?? 'N/A' }}
                    </td>
                    <!-- <td>{{ $qc->mill }}</td> -->
                    <td>
                        @php
                            $commodityIds = json_decode($qc->commodities, true) ?? [];
                            $commodityNames = \App\Models\Product::whereIn('id', $commodityIds)
                                ->pluck('name')
                                ->toArray();
                        @endphp
                        @foreach(array_slice($commodityNames, 0, 2) as $name)
                            <span class="badge badge-secondary mr-1">{{ $name }}</span>
                        @endforeach
                        @if(count($commodityNames) > 2)
                            <span class="badge badge-light">+{{ count($commodityNames) - 2 }} more</span>
                        @endif
                    </td>
                    <td>
                        @php
                            $totalQuantity = $qc->items->sum('suggested_quantity');
                        @endphp
                        <strong>{{ number_format($totalQuantity) }}</strong> kgs
                        <br>
                        <small class="text-muted">{{ number_format($totalQuantity / 1000, 2) }} MT</small>
                    </td>
                    <td>
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" 
                                onclick="openModal(this,'{{ route('job-order-rm-qc.edit', $qc->id) }}','Edit Raw Material QC',false,'90%')"
                                class="btn btn-outline-primary" title="Edit">
                                <i class="ft-edit"></i>
                            </button>
                            <!-- <button type="button" 
                                onclick="deletemodal('{{ route('job-order-rm-qc.destroy', $qc->id) }}','{{ route('get.job_order_rm_qc') }}')"
                                class="btn btn-outline-danger" title="Delete">
                                <i class="ft-trash"></i>
                            </button> -->
                            <!-- <button type="button" 
                                onclick="viewQcDetails({{ $qc->id }})"
                                class="btn btn-outline-info" title="View Details">
                                <i class="ft-eye"></i>
                            </button>
                            <button type="button" 
                                onclick="printQcReport({{ $qc->id }})"
                                class="btn btn-outline-success" title="Print Report">
                                <i class="ft-printer"></i>
                            </button> -->
                        </div>
                    </td>
                </tr>
            @endforeach
        @else
            <tr>
                <td colspan="8" class="text-center py-4">
                    <div class="empty-state">
                        <i class="ft-clipboard ft-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No Raw Material QC Found</h5>
                        <p class="text-muted mb-3">Get started by creating your first QC record</p>
                        <button onclick="openModal(this,'{{ route('job-order-rm-qc.create') }}','Create Raw Material QC',false,'90%')" 
                                class="btn btn-primary">
                            <i class="ft-plus mr-1"></i> Create QC
                        </button>
                    </div>
                </td>
            </tr>
        @endif
    </tbody>
</table>

@if (count($qcs) != 0)
<div class="row mt-3">
    <div class="col-md-12">
        <div class="float-right" id="paginationLinks">
            {{ $qcs->links() }}
        </div>
    </div>
</div>
@endif

