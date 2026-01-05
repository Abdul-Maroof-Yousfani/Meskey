@if($LoadingPrograms->count() > 0)
    <table class="table table-striped">
        <thead>
            <tr>
                <th class="col-sm-1">SO No.</th>
                <th class="col-sm-1">DO No.</th>
                <th class="col-sm-2">Customer</th>
                <th class="col-sm-2">Commodity</th>
                <th class="col-sm-1">Items</th>
                <th class="col-sm-2">Created</th>
                <th class="col-sm-1">Action</th>
            </tr>
        </thead>
        <tbody>
            @foreach($LoadingPrograms as $loadingProgram)
                <tr>
                    <td>
                        {{ $loadingProgram->saleOrder->reference_no ?? 'N/A' }}
                    </td>
                    <td>
                        {{ $loadingProgram->deliveryOrder->reference_no ?? 'N/A' }}
                    </td>
                    <td>
                        {{ $loadingProgram->saleOrder->customer->name ?? 'N/A' }}
                    </td>
                    <td>
                        {{ $loadingProgram->saleOrder->sales_order_data->first()->item->name ?? 'N/A' }}
                    </td>
                    <td>
                        {{ $loadingProgram->loadingProgramItems->count() }}
                    </td>
                    <td>
                        {{ $loadingProgram->created_at->format('d-m-Y H:i') }}
                    </td>
                    <td>
                        <a onclick="openModal(this,'{{ route('sales.loading-program.edit', $loadingProgram->id) }}','Edit Loading Program', false)"
                            class="warning p-1 text-center mr-2 position-relative">
                            <i class="ft-edit font-medium-3"></i>
                        </a>
                        <a onclick="openModal(this,'{{ route('sales.loading-program.show', $loadingProgram->id) }}','View Loading Program', true)"
                            class="info p-1 text-center mr-2 position-relative">
                            <i class="ft-eye font-medium-3"></i>
                        </a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Pagination -->
    <div class="d-flex justify-content-end">
        {{ $LoadingPrograms->appends(request()->query())->links() }}
    </div>
@else
    <div class="text-center py-5">
        <h5 class="text-muted">No Loading Programs found</h5>
    </div>
@endif

