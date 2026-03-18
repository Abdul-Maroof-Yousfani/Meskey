@if($WeighbridgeAmounts->count() > 0)
    <table class="table table-striped">
        <thead>
            <tr>
                <th class="col-sm-1">Location</th>
                <th class="col-sm-2">Truck Type</th>
                <th class="col-sm-2">Amount</th>
                <th class="col-sm-3">Description</th>
                <th class="col-sm-2">Created</th>
                <th class="col-sm-1">Action</th>
            </tr>
        </thead>
        <tbody>
            @foreach($WeighbridgeAmounts as $weighbridgeAmount)
                <tr>
                    <td>
                        {{ $weighbridgeAmount->companyLocation->name ?? 'N/A' }}
                    </td>
                    <td>
                        {{ $weighbridgeAmount->truckType->name ?? 'N/A' }}
                    </td>
                    <td>
                        {{ number_format($weighbridgeAmount->weighbridge_amount, 2) }}
                    </td>
                    <td>
                        <small>{{ Str::limit($weighbridgeAmount->description ?? '--', 50) }}</small>
                    </td>
                    <td>
                        {{ $weighbridgeAmount->created_at->format('d-m-Y H:i') }}
                    </td>
                    <td>
                        <div class="btn-group">
                          
                            <a class="dropdown-item" href="javascript:void(0)" onclick="openModal(this,'{{ route('weighbridge-amount.edit', $weighbridgeAmount->id) }}','Edit Weighbridge Amount')">
                                <i class="ft-edit-2"></i> Edit
                            </a>
                            <a
                                href="javascript:void(0)"
                                onclick="deletemodal('{{ route('weighbridge-amount.destroy', $weighbridgeAmount->id) }}', '{{ route('get.weighbridge-amount') }}')"
                                class="dropdown-item text-danger">
                                <i class="ft-trash-2"></i> Delete
                            </a>


                        
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Pagination -->
    <div class="d-flex justify-content-center">
        {{ $WeighbridgeAmounts->appends(request()->query())->links() }}
    </div>
@else
    <div class="text-center py-5">
        <h5 class="text-muted">No Weighbridge Amounts found</h5>
    </div>
@endif
