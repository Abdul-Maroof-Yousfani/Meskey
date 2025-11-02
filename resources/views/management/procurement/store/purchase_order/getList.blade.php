<table class="table m-0">
    <thead>
        <tr>
            <th class="col-sm-3">Purchase Order No </th>
            <th class="col-sm-3">Purchase Request No</th>
            <th class="col-sm-3">Purchase Quotation No</th>
            {{-- <th class="col-sm-2">Location</th> --}}
            <th class="col-sm-3">Category- item</th>
            <th class="col-sm-3">Supplier</th>
            {{-- <th class="col-sm-2">Item UOM</th> --}}
            {{-- <th class="col-sm-2">Supplier</th> --}}
            <th class="col-sm-1">Qty</th>
            <th class="col-sm-1">Rate</th>
            <th class="col-sm-1">Total Amount</th>
            <th class="col-sm-1">Item Status</th>
            <th class="col-sm-1">Action</th>
        </tr>
    </thead>
    <tbody>
    <tbody>
        {{-- @php dd($GroupedPurchaseOrder); @endphp --}}
        @if (count($GroupedPurchaseOrder) != 0)
            @foreach ($GroupedPurchaseOrder as $requestGroup)
                @php $isFirstRequestRow = true; @endphp
                @foreach ($requestGroup['items'] as $itemGroup)
                    @php $isFirstItemRow = true; @endphp
                    @foreach ($itemGroup['suppliers'] as $supplierRow)
                        @php
                            $approvalDataStatus = ucwords(
                                $supplierRow['data']
                                    ?->{$supplierRow['data']->getApprovalModule()->approval_column ?? 'am_approval_status'} ?? 'N/A'
                            );
                            $approvalStatus = ucwords($requestGroup['request_status'] ?? 'N/A');
                        @endphp
                        <tr>
                            {{-- Purchase Order No --}}
                            @if ($isFirstRequestRow)
                                <td rowspan="{{ $requestGroup['request_rowspan'] }}"
                                    style="background-color: #e3f2fd; vertical-align: middle;">
                                    <p class="m-0 font-weight-bold">
                                        #{{ $requestGroup['request_no'] }}
                                    </p>
                                </td>
                            @endif

                            {{-- Purchase Request No --}}
                            @if ($isFirstItemRow)
                                <td rowspan="{{ $itemGroup['item_rowspan'] ?? 1 }}"
                                    style="background-color: #e8f5e8; vertical-align: middle;">
                                    <p class="m-0 font-weight-bold">
                                        #{{ $requestGroup['purchase_request_no'] ?? 'N/A' }}
                                    </p>
                                </td>

                                {{-- Purchase Quotation No --}}
                                <td rowspan="{{ $itemGroup['item_rowspan'] ?? 1 }}"
                                    style="background-color: #fff3e0; vertical-align: middle;">
                                    <p class="m-0 font-weight-bold">
                                        #{{ $requestGroup['quotation_no'] ?? '-' }}
                                    </p>
                                </td>
                                @php $isFirstItemRow = false; @endphp
                            @endif
                            {{-- Item --}}
                            <td>
                                <p class="m-0 font-weight-bold">
                                    {{ optional($supplierRow['data']->category)->name }} -
                                    {{ optional($supplierRow['data']->item)->name }}
                                </p>
                            </td>

                            {{-- Supplier --}}
                            <td style="background-color: #fff3e0; vertical-align: middle;">
                                <p class="m-0 font-weight-bold">
                                    {{ optional($supplierRow['data']->supplier)->name }}
                                </p>
                            </td>

                            {{-- Unit --}}
                            {{-- <td>
                                <p class="m-0 text-right">
                                    {{ optional($supplierRow['data']->item->unitOfMeasure)->name }}
                                </p>
                            </td> --}}

                            {{-- Rate --}}
                            <td>
                                <p class="m-0 text-right">
                                    {{ $supplierRow['data']->qty }}
                                </p>
                            </td>
                            <td>
                                <p class="m-0 text-right">
                                    {{ $supplierRow['data']->rate }}
                                </p>
                            </td>
                            <td>
                                <p class="m-0 text-right">
                                    {{ $supplierRow['data']->total }}
                                </p>
                            </td>
                            {{-- Created Date --}}
                            {{-- <td>
                                <p class="m-0 white-nowrap">
                                    {{ \Carbon\Carbon::parse($supplierRow['data']->created_at)->format('Y-m-d') }}
                                    /
                                    {{ \Carbon\Carbon::parse($supplierRow['data']->created_at)->format('h:i A') }}
                                </p>
                            </td> --}}

                            {{-- Approval Status + Actions --}}
                            @if ($isFirstRequestRow)
                                <td rowspan="{{ $requestGroup['request_rowspan'] }}">
                                    @php
                                        $badgeClass = match (strtolower($approvalStatus)) {
                                            'approved' => 'badge-success',
                                            'rejected' => 'badge-danger',
                                            'pending' => 'badge-warning',
                                            'returned' => 'badge-info',
                                            default => 'badge-secondary',
                                        };
                                    @endphp
                                    <span class="badge {{ $badgeClass }}">
                                        {{ $approvalStatus }}
                                    </span>
                                </td>
                                <td rowspan="{{ $requestGroup['request_rowspan'] }}">
                                    <div class="d-flex gap-2">
                                        @php
                                            $currentApprovalStatus =
                                                $supplierRow['data']
                                                    ?->{$supplierRow['data']->getApprovalModule()->approval_column ??
                                                    'am_approval_status'};
                                            $isCurrentApproved = strtolower($currentApprovalStatus) === 'approved';
                                            $shouldDisableApproval =
                                                $requestGroup['has_approved_item'] && !$isCurrentApproved;
                                        @endphp

                                        {{-- View Approval --}}
                                        <a onclick="openModal(this, '{{ route('store.purchase-order.approvals', $supplierRow['data']->purchase_order->id) }}', 'View Purchase Order', false, '80%')"
                                            class="info p-1 text-center mr-2 position-relative" title="Approval">
                                            <i class="ft-eye font-medium-3"></i>
                                        </a>

                                        {{-- Edit/Delete (only if not approved or rejected) --}}
                                        @if($requestGroup['created_by_id'] == auth()->user()->id)

                                            @if ($requestGroup['request_status'] != 'approved' && $requestGroup['request_status'] != 'rejected')
                                                <a onclick="openModal(this, '{{ route('store.purchase-order.edit', $supplierRow['data']->purchase_order->id) }}', 'Edit Purchase Order', false, '80%')"
                                                    class="info p-1 text-center mr-2 position-relative">
                                                    <i class="ft-edit font-medium-3"></i>
                                                </a>
                                            @endif
                                        @endif
                                        @if ($requestGroup['request_status'] != 'approved' && $requestGroup['request_status'] != 'rejected')

                                            <a onclick="deletemodal('{{ route('store.purchase-order.destroy', $supplierRow['data']->purchase_order->id) }}', '{{ route('store.get.purchase-order') }}')"
                                                class="danger p-1 text-center mr-2 position-relative">
                                                <i class="ft-x font-medium-3"></i>
                                            </a>
                                        @endif
                                    </div>
                                </td>
                                @php $isFirstRequestRow = false; @endphp
                            @endif
                        </tr>
                    @endforeach
                @endforeach
            @endforeach
        @else
            <tr class="ant-table-placeholder">
                <td colspan="10" class="ant-table-cell text-center">
                    <div class="my-5">
                        <svg width="64" height="41" viewBox="0 0 64 41" xmlns="http://www.w3.org/2000/svg">
                            <g transform="translate(0 1)" fill="none" fill-rule="evenodd">
                                <ellipse fill="#f5f5f5" cx="32" cy="33" rx="32" ry="7"></ellipse>
                                <g fill-rule="nonzero" stroke="#d9d9d9">
                                    <path
                                        d="M55 12.76L44.854 1.258C44.367.474 43.656 0 42.907 0H21.093c-.749 0-1.46.474-1.947 1.257L9 12.761V22h46v-9.24z">
                                    </path>
                                    <path
                                        d="M41.613 15.931c0-1.605.994-2.93 2.227-2.931H55v18.137C55 33.26 53.68 35 52.05 35h-40.1C10.32 35 9 33.259 9 31.137V13h11.16c1.233 0 2.227 1.323 2.227 2.928v.022c0 1.605 1.005 2.901 2.237 2.901h14.752c1.232 0 2.237-1.308 2.237-2.913v-.007z"
                                        fill="#fafafa"></path>
                                </g>
                            </g>
                        </svg>
                        <p class="ant-empty-description">No data</p>
                    </div>
                </td>
            </tr>
        @endif
    </tbody>

</table>
{{-- <div id="paginationLinks">
    {{ $roles->links() }}
</div> --}}



<div class="row d-flex" id="paginationLinks">
    <div class="col-md-12 text-right">
        {{ $PurchaseOrder->links() }}
    </div>
</div>
<script>
    function approveItem(url) {
        Swal.fire({
            title: 'Are you sure?',
            text: "You are about to approve this item.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, approve it!',
            cancelButtonText: 'Cancel',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: url,
                    type: 'GET',

                    success: function (res) {
                        Swal.fire({
                            title: 'Approved!',
                            text: res.message,
                            icon: 'success',
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => {
                            location.reload();
                        });
                    },
                    error: function () {
                        Swal.fire('Error', 'Error occurred while approving item.', 'error');
                    }
                });
            }
        });
    }
</script>