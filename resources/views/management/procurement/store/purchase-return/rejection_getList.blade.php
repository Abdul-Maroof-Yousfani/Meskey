<table class="table m-0" style="table-layout: fixed; width: 100%;">
    <thead>
        <tr>
            <th style="width: 10%;" class="text-left">Return No</th>
            <th style="width: 10%;" class="text-left">GRN No</th>
            <th style="width: 10%;" class="text-left">Truck No</th>
            <th style="width: 15%;" class="text-left">Supplier</th>
            <th style="width: 15%;" class="text-left">Item</th>
            <th style="width: 10%;" class="text-left">Rate</th>
            <th style="width: 12%;" class="text-left">Rejected Qty</th>
            <th style="width: 8%;" class="text-left">Status</th>
            <th style="width: 10%;" class="text-left">Action</th>
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
                            <strong>{{ $item['rate'] }}</strong>
                        </td>
                        
                        <td class="text-left">
                            <span class="badge badge-danger">{{ $item['rejected_qty'] }} {{ $item['uom'] }}</span>
                        </td>

                        @if ($isFirstRow)
                            @php
                                $status = strtolower($requestGroup['status'] ?? 'pending');
                                $badge = match(true) {
                                    $status === 'approved' => 'badge-success',
                                    $status === 'rejected' => 'badge-danger',
                                    $status === 'reverted' => 'badge-info',
                                    default => 'badge-warning',
                                };
                                $editable = !in_array($status, ['approved', 'rejected']);
                            @endphp
                            <td rowspan="{{ $requestGroup['request_rowspan'] }}" style="vertical-align: middle;">
                                <span class="badge {{ $badge }} px-2 py-1">{{ ucfirst($status) }}</span>
                            </td>
                            <td rowspan="{{ $requestGroup['request_rowspan'] }}" style="vertical-align: middle;">
                                <a class="info p-1 text-center position-relative btn-action-show" title="View"
                                    onclick="openModal(this, '{{ route('store.rejection-return.view', $requestGroup['id']) }}', 'View Return', false, '80%')">
                                    <i class="ft-eye font-medium-3"></i></a>
                                @if ($status === 'approved')
                                    <a class="info p-1 text-center position-relative" title="Gate Out" target="_blank"
                                        href="{{ route('store.rejection-return.gate-out', $requestGroup['id']) }}">
                                        <i class="ft-printer font-medium-3"></i></a>
                                @endif
                                @if ($editable)
                                    <a class="info p-1 text-center position-relative btn-action-edit" title="Edit"
                                        onclick="openModal(this,'{{ route('store.rejection-return.edit', $requestGroup['id']) }}','Edit Return',false,'80%')">
                                        <i class="ft-edit font-medium-3"></i></a>
                                    <a class="danger p-1 text-center position-relative" title="Delete"
                                        onclick="deletemodal('{{ route('store.rejection-return.destroy', $requestGroup['id']) }}','{{ route('store.rejection-return.getList') }}')">
                                        <i class="ft-x font-medium-3"></i></a>
                                @endif
                            </td>
                            @php $isFirstRow = false; @endphp
                        @endif
                    </tr>
                @endforeach
            @endforeach
        @else
            <tr>
                <td colspan="9" class="text-center py-5">
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
