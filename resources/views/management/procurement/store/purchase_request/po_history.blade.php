<div class="row">
    <div class="col-md-12">
        <div class="card card-custom">
            <div class="card-header flex-wrap border-0 pt-6 pb-0">
                <div class="card-title">
                    <h3 class="card-label">PO History for Item: <span class="text-primary">{{ $purchaseRequestData->item->name }}</span>
                    <span class="d-block text-muted pt-2 font-size-sm">Original PR Qty: {{ $purchaseRequestData->qty }} {{ $purchaseRequestData->item->unitOfMeasure->name ?? '' }}</span></h3>
                </div>
            </div>
            <div class="card-body">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>PO Date</th>
                            <th>PO Number</th>
                            <th>Supplier</th>
                            <th>PO Qty</th>
                            <th>Rate</th>
                            <th>Total</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($purchaseRequestData->purchase_order_data as $po_data)
                            <tr>
                                <td>{{ $po_data->purchase_order->purchase_date }}</td>
                                <td>{{ $po_data->purchase_order->purchase_order_no }}</td>
                                <td>{{ $po_data->supplier->name ?? 'N/A' }}</td>
                                <td>{{ $po_data->qty }}</td>
                                <td>{{ $po_data->rate }}</td>
                                <td>{{ number_format($po_data->total, 2) }}</td>
                                <td>
                                    @php
                                        $status = ucwords($po_data->purchase_order->am_approval_status ?? 'pending');
                                        $badgeClass = match (strtolower($status)) {
                                            'approved' => 'badge-success',
                                            'rejected' => 'badge-danger',
                                            'pending' => 'badge-warning',
                                            'reverted' => 'badge-info',
                                            default => 'badge-secondary',
                                        };
                                    @endphp
                                    <span class="badge {{ $badgeClass }}">{{ $status }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center">No Purchase Orders found for this item.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="row bottom-button-bar">
    <div class="col-12">
        <a type="button" class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton">Close</a>
    </div>
</div>
