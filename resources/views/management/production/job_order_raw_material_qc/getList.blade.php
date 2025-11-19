<table class="table table-hover m-0">
    <thead class="thead-light">
        <tr>
            <th width="15%">Job Order</th>
            <th width="10%">Date</th>
            <th width="15%">Location</th>
            <th width="15%">Product</th>
            <th width="12%">Quantity</th>
            <th width="10%">Containers</th>
            <th width="8%">Status</th>
            <th width="15%">Actions</th>
        </tr>
    </thead>
    <tbody>
        @if (count($job_orders) != 0)
            @foreach ($job_orders as $job_order)

                <tr>
                    <td>
                        <div>
                            <strong class="d-block">{{ $job_order->job_order_no }}</strong>
                            @if($job_order->ref_no)
                                <small class="text-muted">Ref: {{ $job_order->ref_no }}</small>
                            @endif
                        </div>
                    </td>
                    <td>{{ \Carbon\Carbon::parse($job_order->job_order_date)->format('d/m/Y') }}</td>
                    <td>
                        <span class="badge badge-light">{{ $job_order->companyLocation->name ?? 'N/A' }}</span>
                    </td>
                    <td>
                        <span class="text-primary">{{ Str::limit($job_order->product->name ?? 'N/A', 20) }}</span>
                    </td>
                    <td>
                        <div>
                            <strong>{{ number_format($job_order->total_kgs) }} KG</strong>
                            <small class="d-block text-muted">{{ number_format($job_order->total_metric_tons, 1) }} MT</small>
                        </div>
                    </td>
                    <td>
                        <span class="badge badge-secondary">{{ $job_order->total_containers }}</span>
                    </td>
                    <td>
                        @php
                            $status = 'primary';
                            if($job_order->loading_date && now()->gt($job_order->loading_date)) {
                                $status = 'success';
                            } elseif($job_order->delivery_date && now()->gt($job_order->delivery_date)) {
                                $status = 'warning';
                            }
                        @endphp
                        <span class="badge badge-{{ $status }}">
                            {{ $status == 'success' ? 'Completed' : ($status == 'warning' ? 'In Progress' : 'Active') }}
                        </span>
                    </td>
                    <td>
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" 
                                onclick="openModal(this,'{{ route('job-orders.edit', $job_order->id) }}','Edit Job Order',false,'90%')"
                                class="btn btn-outline-primary" title="Edit">
                                <i class="ft-edit"></i>
                            </button>
                            <button type="button" 
                                onclick="deletemodal('{{ route('job-orders.destroy', $job_order->id) }}','{{ route('get.job_orders') }}')"
                                class="btn btn-outline-danger" title="Delete">
                                <i class="ft-trash"></i>
                            </button>
                            <button type="button" 
                                onclick="window.open('{{ route('job-orders.show', $job_order->id) }}', '_blank')"
                                class="btn btn-outline-info" title="View">
                                <i class="ft-eye"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            @endforeach
        @else
            <tr>
                <td colspan="8" class="text-center py-4">
                    <div class="empty-state">
                        <i class="ft-briefcase ft-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No Job Orders Found</h5>
                        <p class="text-muted mb-3">Get started by creating your first job order</p>
                        <button onclick="openModal(this,'{{ route('job-orders.create') }}','Create Job Order',false,'90%')" 
                                class="btn btn-primary">
                            <i class="ft-plus mr-1"></i> Create Job Order
                        </button>
                    </div>
                </td>
            </tr>
        @endif
    </tbody>
</table>

@if (count($job_orders) != 0)
<div class="row mt-3">
    <div class="col-md-12">
        <div class="float-right" id="paginationLinks">
            {{ $job_orders->links() }}
        </div>
    </div>
</div>
@endif