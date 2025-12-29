@if($FirstWeighbridges->count() > 0)
    <table class="table table-striped">
        <thead>
            <tr>
                <th class="col-sm-1">DO No.</th>
                <th class="col-sm-2">Customer</th>
                <th class="col-sm-2">Commodity</th>
                <th class="col-sm-1">Weight</th>
                <th class="col-sm-2">Created</th>
                <th class="col-sm-1">Action</th>
            </tr>
        </thead>
        <tbody>
            @foreach($FirstWeighbridges as $firstWeighbridge)
                <tr>
                    <td>
                        {{ $firstWeighbridge->deliveryOrder->reference_no ?? 'N/A' }}
                    </td>
                    <td>
                        {{ $firstWeighbridge->deliveryOrder->customer->name ?? 'N/A' }}
                    </td>
                    <td>
                        {{ $firstWeighbridge->deliveryOrder->delivery_order_data->first()->item->name ?? 'N/A' }}
                    </td>
                    <td>
                        {{ $firstWeighbridge->first_weight ?? 'N/A' }}
                    </td>
                    <td>
                        {{ $firstWeighbridge->created_at->format('d-m-Y H:i') }}
                    </td>
                    <td>
                        <a onclick="openModal(this,'{{ route('sales.first-weighbridge.edit', $firstWeighbridge->id) }}','View First Weighbridge', true)"
                            class="info p-1 text-center mr-2 position-relative">
                            <i class="ft-eye font-medium-3"></i>
                        </a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Pagination -->
    <div class="d-flex justify-content-center">
        {{ $FirstWeighbridges->appends(request()->query())->links() }}
    </div>
@else
    <div class="text-center py-5">
        <h5 class="text-muted">No First Weighbridges found</h5>
    </div>
@endif
