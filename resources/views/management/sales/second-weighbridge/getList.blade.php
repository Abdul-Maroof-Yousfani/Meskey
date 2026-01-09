@if($SecondWeighbridges->count() > 0)
    <table class="table table-striped">
        <thead>
            <tr>
                <th class="col-sm-1">Ticket No.</th>
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
                        {{ $secondWeighbridge->loadingSlip->loadingProgramItem->transaction_number ?? 'N/A' }}
                    </td>
                    <td>
                        {{ $secondWeighbridge->loadingSlip->customer ?? 'N/A' }}
                    </td>
                    <td>
                        {{ $secondWeighbridge->loadingSlip->commodity ?? 'N/A' }}
                    </td>
                    <td>
                        {{ $secondWeighbridge->net_weight ?? 'N/A' }}
                    </td>
                    <td>
                        {{ $secondWeighbridge->created_at->format('d-m-Y H:i') }}
                    </td>
                    <td>
                        @php
                            $dispatchQcId = optional(
                                optional($secondWeighbridge->loadingProgramItem)->dispatchQc
                            )->id;
                        @endphp
                        <a onclick="openModal(this,'{{ route('sales.get.dispatch-qc.gate-out', $dispatchQcId) }}', 'Gate Out', true, '100%')"
                            class="success p-1 text-center mr-2 position-relative" title="Gate Out Pass" style="cursor: pointer;">
                            <i class="ft-file font-medium-3"></i>
                        </a>
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
    <div class="row d-flex" id="paginationLinks">
        <div class="col-md-12 text-right">
            {{ $SecondWeighbridges->links() }}
        </div>
    </div>
@else
    <div class="text-center py-5">
        <h5 class="text-muted">No Second Weighbridges found</h5>
    </div>
@endif
