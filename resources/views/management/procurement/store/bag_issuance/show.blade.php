<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <h6 class="header-heading-sepration">Issuance Details - {{ $issuance->issuance_number }}</h6>
            <div class="row mb-3">
                <div class="col-md-4">
                    <p><strong>Issuance Date:</strong> {{ \Carbon\Carbon::parse($issuance->issuance_date)->format('d/m/Y') }}</p>
                </div>
                <div class="col-md-4">
                    <p><strong>Bag Request:</strong> {{ $issuance->bagRequest->request_number ?? 'N/A' }}</p>
                </div>
                <div class="col-md-4">
                    <p><strong>Gala:</strong> {{ $issuance->gala->name ?? 'N/A' }}</p>
                </div>
                <div class="col-md-4">
                    <p><strong>Arrival Location:</strong> {{ $issuance->arrivalLocation->name ?? 'N/A' }}</p>
                </div>
                <div class="col-md-8">
                    <p><strong>Remarks:</strong> {{ $issuance->remarks ?? 'No remarks' }}</p>
                </div>
            </div>

            <h6 class="header-heading-sepration">Issued Items</h6>
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead class="thead-light">
                        <tr>
                            <th>Item</th>
                            <th>Brand</th>
                            <th>Issued Quantity</th>
                            <th>Unit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($issuance->items as $item)
                            <tr>
                                <td>{{ $item->item->name ?? 'N/A' }}</td>
                                <td>{{ $item->brand->name ?? 'N/A' }}</td>
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
