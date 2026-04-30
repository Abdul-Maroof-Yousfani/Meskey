@if($FirstWeighbridges->count() > 0)
    <table class="table table-striped">
        <thead>
            <tr>
                <th class="col-sm-1">Ticket No.</th>
                <th class="col-sm-1">Truck No.</th>
                <th class="col-sm-2">Buyer</th>
                <th class="col-sm-2">Commodity</th>
                <th class="col-sm-1">Weight(KG)</th>
                <th class="col-sm-2">Created</th>
                <th class="col-sm-1">Action</th>
            </tr>
        </thead>
        <tbody>
            @foreach($FirstWeighbridges as $firstWeighbridge)
                @php
                    $ticket = $firstWeighbridge->loadingProgramItem;
                    $deliveryOrder = $ticket->exportLoadingProgram?->deliveryOrders?->where('am_approval_status', 'approved')->first()
                        ?? (($ticket->exportLoadingProgram?->deliveryOrder && $ticket->exportLoadingProgram->deliveryOrder->am_approval_status === 'approved')
                            ? $ticket->exportLoadingProgram->deliveryOrder
                            : null)
                        ?? $ticket->deliveryOrders->where('type', 'export_order')->where('am_approval_status', 'approved')->first();
                    $exportOrder = $deliveryOrder?->exportOrder
                        ?? $ticket->exportOrders->where('am_approval_status', 'approved')->first()
                        ?? $ticket->exportLoadingProgram?->exportOrders?->where('am_approval_status', 'approved')->first()
                        ?? $ticket->exportLoadingProgram?->exportOrder;
                @endphp
                <tr>
                    <td>{{ $firstWeighbridge->loadingProgramItem->transaction_number ?? 'N/A' }}</td>
                    <td>{{ $firstWeighbridge->loadingProgramItem->truck_number ?? 'N/A' }}</td>
                    <td>{{ $deliveryOrder?->customer?->name ?? 'N/A' }}</td>
                    <td>{{ $deliveryOrder?->exportOrder?->product?->name ?? $exportOrder?->product?->name ?? 'N/A' }}</td>
                    <td>{{ $firstWeighbridge->first_weight ?? 'N/A' }}</td>
                    <td>{{ $firstWeighbridge->created_at->format('d-m-Y H:i') }}</td>
                    <td>
                        <a onclick="openModal(this,'{{ route('export-first-weighbridge.edit', $firstWeighbridge->id) }}','View Export First Weighbridge', true)"
                            class="info p-1 text-center mr-2 position-relative">
                            <i class="ft-eye font-medium-3"></i>
                        </a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="row d-flex" id="paginationLinks">
        <div class="col-md-12 text-right">
            {{ $FirstWeighbridges->links() }}
        </div>
    </div>
@else
    <div class="text-center py-5">
        <h5 class="text-muted">No Export First Weighbridges found</h5>
    </div>
@endif
