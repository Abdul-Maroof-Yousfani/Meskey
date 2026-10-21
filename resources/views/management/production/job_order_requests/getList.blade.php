<table class="table table-hover m-0">
    <thead class="thead-light">
        <tr>
            <th width="15%">Request No</th>
            <th width="10%">Date</th>
            <th width="15%">Location</th>
            <th width="15%">Product</th>
            <th width="12%">Quantity</th>
            <th width="8%">Containers</th>
            <th width="10%">Status</th>
            <th width="15%">Actions</th>
        </tr>
    </thead>
    <tbody>
        @if (count($requests) != 0)
            @foreach ($requests as $request)
                <tr>
                    <td>
                        <div>
                            <strong class="d-block">{{ $request->request_no }}</strong>
                            @if($request->ref_no)
                                <small class="text-muted">Ref: {{ $request->ref_no }}</small>
                            @endif
                        </div>
                    </td>
                    <td>{{ \Carbon\Carbon::parse($request->request_date)->format('d/m/Y') }}</td>
                    <td>
                        <span class="badge badge-light">{{ $request->companyLocation->name ?? 'N/A' }}</span>
                    </td>
                    <td>
                        <span class="text-primary">{{ $request->product->name ?? 'N/A' }}</span>
                    </td>
                    <td>
                        <div>
                            <strong>{{ number_format($request->total_kgs) }} KG</strong>
                            <small class="d-block text-muted">{{ number_format($request->total_metric_tons, 2) }} MT</small>
                            @if($request->consumed_kgs > 0)
                                <small class="d-block text-info font-weight-bold">Rem: {{ number_format($request->remaining_kgs) }} KG</small>
                            @endif
                        </div>
                    </td>
                    <td>
                        <span class="badge badge-info">{{ $request->total_containers }}</span>
                    </td>
                    <td>
                        @if($request->status === 'converted')
                            <span class="badge badge-success">Converted to JO</span>
                        @elseif($request->status === 'partial')
                            <span class="badge badge-info">Partially Converted</span>
                        @elseif($request->status === 'cancelled')
                            <span class="badge badge-danger">Cancelled</span>
                        @else
                            <span class="badge badge-warning">Pending</span>
                        @endif
                    </td>
                    <td>
                        <div class="btn-group" role="group">
                            @if($request->status === 'pending')
                                <button type="button" 
                                    onclick="openModal(this,'{{ route('job-order-requests.edit', $request->id) }}','Edit Job Order Request',false,'99%')"
                                    class="btn btn-outline-primary btn-sm position-relative" title="Edit">
                                    <i class="ft-edit"></i>
                                </button>
                                <button type="button" 
                                    onclick="deletemodal('{{ route('job-order-requests.destroy', $request->id) }}','{{ route('get.job_order_requests') }}')"
                                    class="btn btn-outline-danger btn-sm position-relative" title="Delete">
                                    <i class="ft-trash"></i>
                                </button>
                            @endif
                            <button type="button" 
                                onclick="openModal(this,'{{ route('job-order-requests.show', $request->id) }}','Job Order Request View',false,'85%')"
                                class="btn btn-outline-info btn-sm position-relative" title="View">    
                                <i class="ft-eye"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            @endforeach
        @else
            <tr>
                <td colspan="8" class="text-center py-4">No Job Order Requests found.</td>
            </tr>
        @endif
    </tbody>
</table>

<div class="row w-100 mx-auto mt-2">
    <div class="col-md-6">
        Showing {{ $requests->firstItem() ?? 0 }} to {{ $requests->lastItem() ?? 0 }} of {{ $requests->total() }} entries
    </div>
    <div class="col-md-6 text-right">
        {{ $requests->links() }}
    </div>
</div>
