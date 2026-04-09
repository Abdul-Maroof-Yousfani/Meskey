<table class="table m-0" style="table-layout: fixed; width: 100%;">
    <thead>
        <tr>
            <th style="width: 12%;" class="text-left">Return No</th>
            <th style="width: 12%;" class="text-left">GRN No</th>
            <th style="width: 10%;" class="text-left">Truck No</th>
            <th style="width: 15%;" class="text-left">Supplier</th>
            <th style="width: 15%;" class="text-left">Item</th>
            <th style="width: 15%;" class="text-left">Rejected Qty</th>
            <th style="width: 16%;" class="text-left">Action</th>
        </tr>
    </thead>
    <tbody>
        @if (isset($GroupedRejectionReturns) && count($GroupedRejectionReturns) > 0)
            @foreach ($GroupedRejectionReturns as $requestGroup)
                @php $isFirstRow = true; @endphp
                @foreach ($requestGroup['items'] as $item)
                    <tr>
                        @if ($isFirstRow)
                            <td rowspan="{{ $requestGroup['request_rowspan'] }}" style="background-color: #e3f2fd; vertical-align: middle;">
                                <p class="m-0 font-weight-bold">
                                    #{{ $requestGroup['request_no'] }}
                                </p>
                            </td>
                            <td rowspan="{{ $requestGroup['request_rowspan'] }}" style="vertical-align: middle;">
                                <p class="m-0">{{ $requestGroup['grn_no'] }}</p>
                            </td>
                            <td rowspan="{{ $requestGroup['request_rowspan'] }}" style="vertical-align: middle;">
                                <p class="m-0">{{ $requestGroup['truck_no'] ?? 'N/A' }}</p>
                            </td>
                            <td rowspan="{{ $requestGroup['request_rowspan'] }}" style="vertical-align: middle;">
                                <p class="m-0 font-weight-bold">{{ $requestGroup['supplier'] }}</p>
                            </td>
                        @endif

                        <td style="background-color: #f9f9f9; vertical-align: middle;">
                            <p class="m-0"><strong>{{ $item['name'] }}</strong></p>
                        </td>

                        <td class="text-left">
                            <span class="badge badge-danger">{{ $item['rejected_qty'] }} {{ $item['uom'] }}</span>
                        </td>

                        @if ($isFirstRow)
                            <td rowspan="{{ $requestGroup['request_rowspan'] }}" style="vertical-align: middle;">
                                <div class="d-flex flex-column align-items-start" style="gap: 4px;">
                                    <a onclick="openModal(this, '{{ route('store.rejection-return.view', $requestGroup['id']) }}', 'View Return', false, '80%')"
                                        class="bg-primary text-white p-1 text-center" style="border-radius: 4px; width: 75px; cursor: pointer; font-size: 11px;">
                                        View
                                    </a>
                                    <a href="{{ route('store.rejection-return.gate-out', $requestGroup['id']) }}" target="_blank"
                                        class="bg-success text-white p-1 text-center" style="border-radius: 4px; width: 75px; cursor: pointer; font-size: 11px;">
                                        Gate Out
                                    </a>
                                    <a onclick="openModal(this,'{{ route('store.rejection-return.edit', $requestGroup['id']) }}','Edit Return',false,'80%')"
                                        class="bg-warning text-white p-1 text-center" style="border-radius: 4px; width: 75px; cursor: pointer; font-size: 11px;">
                                        Edit
                                    </a>
                                    <a onclick="deletemodal('{{ route('store.rejection-return.destroy', $requestGroup['id']) }}','{{ route('store.rejection-return.getList') }}')"
                                        class="bg-danger text-white p-1 text-center" style="border-radius: 4px; width: 75px; cursor: pointer; font-size: 11px;">
                                        Delete
                                    </a>
                                </div>
                            </td>
                            @php $isFirstRow = false; @endphp
                        @endif
                    </tr>
                @endforeach
            @endforeach
        @else
            <tr>
                <td colspan="7" class="text-center py-5">
                    <p class="text-muted">No rejection returns found.</p>
                </td>
            </tr>
        @endif
    </tbody>
</table>

@if(isset($PurchaseReturns) && method_exists($PurchaseReturns, 'links'))
<div class="row d-flex" id="paginationLinks">
    <div class="col-md-12 text-right">
        {{ $PurchaseReturns->links() }}
    </div>
</div>
@endif
