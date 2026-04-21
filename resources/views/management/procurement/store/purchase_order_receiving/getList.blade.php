<table class="table m-0" style="width: 100%; min-width: 1710px;">
    <thead>
        <tr>
            <th style="width: 180px; min-width: 180px;">PO Receiving No</th>
            <th style="width: 180px; min-width: 180px;">Purchase Request No</th>
            <th style="width: 180px; min-width: 180px;">Purchase Order No</th>
            <th style="width: 100px; min-width: 100px;">DC No</th>
            <th style="width: 250px; min-width: 250px;">Category- item</th>
            <th style="width: 200px; min-width: 200px;">Supplier</th>
            <th style="width: 90px; min-width: 90px;">Qty</th>
            <th style="width: 90px; min-width: 90px;">Rate</th>
            <th style="width: 130px; min-width: 100px;">Total Amount</th>
            <th style="min-width: 50px;">Action</th>
        </tr>
    </thead>
    <tbody>
        {{-- @php dd($GroupedPurchaseOrderReceiving); @endphp --}}
        @if (count($GroupedPurchaseOrderReceiving) != 0)
            @foreach ($GroupedPurchaseOrderReceiving as $itemKey => $requestGroup)
                @php $isFirstRequestRow = true; @endphp
                @foreach ($requestGroup['items'] as $itemGroup)
                    @php $isFirstItemRow = true; @endphp
                    
                    @foreach ($itemGroup['suppliers'] as $supplierKeys => $supplierRow)
                    @php
                            $approvalDataStatus = ucwords(
                                $supplierRow['data']
                                ?->{$supplierRow['data']->getApprovalModule()->approval_column ?? 'am_approval_status'} ?? 'N/A'
                            );
                            $approvalStatus = ucwords($requestGroup['request_status'] ?? 'N/A');
                            @endphp

                                      <button style="visibility: hidden;" id="modalButton{{ $itemGroup['item_data']->id }}" onclick="openModal(this, '{{ route('store.qc.show-create', ['id' => $itemGroup['item_data']->id, 'grn' => get_grn($itemGroup['item_data']->purchase_order_receiving_id)]) }}', 'Add QC', false, '95%')">{{ $itemGroup['item_data']->id }}</button>
                          <button style="visibility: hidden;" id="modalButtonQc{{ $itemGroup['item_data']->id }}" onclick="openModal(this, '{{ route('store.qc.edit', ['id' => $itemGroup['item_data']->id, 'grn' => get_grn($itemGroup['item_data']->purchase_order_receiving_id), 'refresh_url' => route('store.get.purchase-order-receiving')]) }}', 'Edit QC', false, '95%')">{{ $itemGroup['item_data']->id }}</button>
                      
                        <tr>
                            {{-- Purchase Order No --}}
                            @if ($isFirstRequestRow)
                                <td rowspan="{{ $requestGroup['request_rowspan'] }}"
                                    style="background-color: #e3f2fd; vertical-align: middle;">
                                    <p class="m-0 font-weight-bold">
                                        #{{ $requestGroup['request_no'] }}
                                    </p>
                                    @if($supplierRow['data']->category_id != 38)
                                        @php
                                            $badgeClass = match (strtolower($approvalStatus)) {
                                                'approved' => 'badge-success',
                                                'rejected' => 'badge-danger',
                                                'pending' => 'badge-warning',
                                                'returned' => 'badge-info',
                                                default => 'badge-secondary',
                                            };
                                        @endphp
                                        <div class="mt-1">
                                            <span class="badge {{ $badgeClass }}" style="font-size: 10px; padding: 2px 5px;">
                                                {{ $approvalStatus }}
                                            </span>
                                        </div>
                                    @endif
                                </td>
                            @endif

                            @if ($isFirstRequestRow)
                                <td rowspan="{{ $requestGroup['request_rowspan'] }}"
                                    style="background-color: #e8f5e8; vertical-align: middle;">
                                    <p class="m-0 font-weight-bold">
                                        #{{ $requestGroup['purchase_request_no'] ?? 'N/A' }}
                                    </p>
                                </td>

                                <td rowspan="{{ $requestGroup['request_rowspan'] }}"
                                    style="background-color: #fff3e0; vertical-align: middle;">
                                    <p class="m-0 font-weight-bold">
                                        #{{ $requestGroup['purchase_order_no'] ?? '-' }}
                                    </p>
                                </td>
                            @endif

                            @if ($isFirstItemRow)
                                <td rowspan="{{ $itemGroup['item_rowspan'] ?? 1 }}"
                                    style="background-color: #fff3e0; vertical-align: middle;">
                                    <p class="m-0 font-weight-bold">
                                        {{ $requestGroup['dc_no'] ?? '-' }}
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
                                @if(optional($supplierRow['data']->purchase_order_data?->purchase_request_data)->is_single_job_order)
                                    <span class="badge badge-yellow mt-1" style="font-size: 10px; padding: 3px 10px; border-radius: 20px;">With job order</span>
                                @else
                                    <span class="badge badge-secondary mt-1" style="font-size: 10px; padding: 3px 10px; border-radius: 20px;">Without job order</span>
                                @endif
                            </td>

                            {{-- Supplier --}}
                            <td style="background-color: #fff3e0; vertical-align: middle;">
                                <p class="m-0 font-weight-bold">
                                    {{ optional($supplierRow['data']->supplier)->name }}
                                </p>
                            </td>

                        
                            <td>
                                <p class="m-0 text-right">
                                    {{ $supplierRow['data']->qty }}
                                </p>
                            </td>
                            <td>
                                @php
                                    $rate = $supplierRow["data"]?->purchase_order_data?->rate ?? 0;
                                    $qty = $supplierRow["data"]?->qty ?? 0;
                                @endphp
                                <p class="m-0 text-right">
                                    {{ $rate }}
                                </p>
                            </td>
                            <td>
                                <p class="m-0 text-right">
                                    {{ $qty * $rate }}
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

                            @if ($isFirstRequestRow)
                                <td rowspan="{{ $requestGroup['request_rowspan'] }}">
                                    <div class="d-flex flex-column align-items-start" style="gap: 5px;">
                                        @php
                                            $currentApprovalStatus =
                                                $supplierRow['data']
                                                    ?->{$supplierRow['data']->getApprovalModule()->approval_column ??
                                                    'am_approval_status'};
                                            $isCurrentApproved = strtolower($currentApprovalStatus) === 'approved';
                                            $shouldDisableApproval =
                                                $requestGroup['has_approved_item'] && !$isCurrentApproved;
                                        @endphp
                                        <a onclick="openModal(this, '{{ route('store.purchase-order-receiving.approvals', $supplierRow['data']->purchase_order_receiving->id) }}', 'View GRN', false, '100%')"
                                            class="bg-info text-white p-1 text-center position-relative" title="View" style="border-radius: 4px; width: 90px;">
                                            View
                                        </a>

                                        @if($requestGroup['created_by_id'] == auth()->user()->id)

                                            @if ($requestGroup['request_status'] != 'approved' && $requestGroup['request_status'] != 'rejected')
                                                <a onclick="openModal(this, '{{ route('store.purchase-order-receiving.edit', $supplierRow['data']->purchase_order_receiving->id) }}', 'Edit GRN', false, '100%')"
                                                    class="bg-warning text-white p-1 text-center position-relative" title="Edit" style="border-radius: 4px; width: 90px;">
                                                    Edit
                                                </a>

                                                <a onclick="deletemodal('{{ route('store.purchase-order-receiving.destroy', $supplierRow['data']->purchase_order_receiving->id) }}', '{{ route('store.get.purchase-order-receiving') }}')"
                                                    class="bg-danger text-white p-1 text-center position-relative" title="Delete" style="border-radius: 4px; width: 90px;">
                                                    Delete
                                                </a>
                                            @endif
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
        {{ $PurchaseOrderReceiving->links() }}
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