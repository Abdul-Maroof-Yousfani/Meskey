@if($SecondWeighbridges->count() > 0)
    <table class="table table-striped">
        <thead>
            <tr>
                <th class="col-sm-1">DO No.</th>
                <th class="col-sm-2">Customer</th>
                <th class="col-sm-2">Commodity</th>
                <th class="col-sm-1">Net Weight</th>
                <th class="col-sm-2">Created</th>
                <th class="col-sm-1">Action</th>
            </tr>
        </thead>
        <tbody>
            @foreach($SecondWeighbridges as $secondWeighbridge)
                <tr>
                    <td>
                        {{ $secondWeighbridge->deliveryOrder->reference_no ?? 'N/A' }}
                    </td>
                    <td>
                        {{ $secondWeighbridge->deliveryOrder->customer->name ?? 'N/A' }}
                    </td>
                    <td>
                        {{ $secondWeighbridge->deliveryOrder->delivery_order_data->first()->item->name ?? 'N/A' }}
                    </td>
                    <td>
                        {{ $secondWeighbridge->net_weight ?? 'N/A' }}
                    </td>
                    <td>
                        {{ $secondWeighbridge->created_at->format('d-m-Y H:i') }}
                    </td>
                    <td>
                        <a onclick="openModal(this,'{{ route('sales.second-weighbridge.edit', $secondWeighbridge->id) }}','View Second Weighbridge', true)"
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
        {{ $SecondWeighbridges->appends(request()->query())->links() }}
    </div>
@else
    <div class="text-center py-5">
        <h5 class="text-muted">No Second Weighbridges found</h5>
    </div>
@endif
