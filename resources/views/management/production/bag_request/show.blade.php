<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="header-heading-sepration mb-0">Bag Request Details - {{ $bagRequest->request_number }}</h6>
                @if($bagRequest->issuances->count() > 0)
                    <span class="badge badge-pill badge-info">Issued</span>
                @endif
            </div>
            
            <div class="row mb-3">
                <div class="col-md-3">
                    <p><strong>Request Date:</strong> {{ \Carbon\Carbon::parse($bagRequest->request_date)->format('d/m/Y') }}</p>
                </div>
                <div class="col-md-3">
                    <p><strong>Gala:</strong> {{ $bagRequest->gala->name ?? 'N/A' }}</p>
                </div>
                <div class="col-md-3">
                    <p><strong>Arrival Location:</strong> {{ $bagRequest->arrivalLocation->name ?? 'N/A' }}</p>
                </div>
                <div class="col-md-3">
                    <p><strong>Status:</strong> 
                        @if($bagRequest->issuances->count() > 0)
                            <span class="text-info font-weight-bold">Already Issued</span>
                        @else
                            <span class="text-warning font-weight-bold">Pending Issuance</span>
                        @endif
                    </p>
                </div>
                <div class="col-md-12 mt-2">
                    <p><strong>Remarks:</strong> {{ $bagRequest->remarks ?? 'No remarks' }}</p>
                </div>
            </div>

            <h6 class="header-heading-sepration">Requested Items</h6>
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead class="thead-light">
                        <tr>
                            <th>Item</th>
                            <th>Brand</th>
                            <th>Packing Size (Kg)</th>
                            <th>Requested Quantity</th>
                            <th>Unit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($bagRequest->items as $item)
                            <tr>
                                <td>{{ $item->item->name ?? 'N/A' }}</td>
                                <td>{{ $item->brand->name ?? 'N/A' }}</td>
                                <td>{{ $item->display_size ?? '' }}</td>
                                <td>{{ number_format($item->quantity, 2) }}</td>
                                <td>{{ $item->unit->name ?? 'N/A' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="row mt-4 mb-2">
        <div class="col-12 text-right">
            <button type="button" class="btn btn-danger modal-sidebar-close" data-dismiss="modal">Close</button>
        </div>
    </div>
</div>
