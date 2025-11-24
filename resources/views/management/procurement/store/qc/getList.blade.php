<table class="table m-0">
    <thead>
        <tr>
            <th class="col-sm-3">PO Receiving No </th>
            <th class="col-sm-3">Purchase Request No</th>
            <th class="col-sm-3">Purchase Order No</th>
            <th class="col-sm-3">Category- item</th>
            <th class="col-sm-3">Supplier</th>
            <th class="col-sm-1">Qty</th>
            <th class="col-sm-1">QC Status</th>
            <th class="col-sm-1">Action</th>
            {{-- <th class="col-sm-1">Status</th> --}}
        </tr>
    </thead>
    <tbody>
    <tbody>
        {{-- @php dd($GroupedPurchaseOrderReceiving); @endphp --}}
        @if (count($GroupedPurchaseOrderReceiving) != 0)
            @foreach ($GroupedPurchaseOrderReceiving as $requestGroup)
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

                            @if ($isFirstItemRow)
                                <td rowspan="{{ $itemGroup['item_rowspan'] ?? 1 }}"
                                    style="background-color: #e8f5e8; vertical-align: middle;">
                                    <p class="m-0 font-weight-bold">
                                        #{{ $requestGroup['purchase_request_no'] ?? 'N/A' }}
                                    </p>
                                </td>

                                <td rowspan="{{ $itemGroup['item_rowspan'] ?? 1 }}"
                                    style="background-color: #fff3e0; vertical-align: middle;">
                                    <p class="m-0 font-weight-bold">
                                        #{{ $requestGroup['purchase_order_no'] ?? '-' }}
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
                                    @php
                                        $badgeClass = match (strtolower($approvalStatus)) {
                                            'approved' => 'badge-success',
                                            'rejected' => 'badge-danger',
                                            'pending' => 'badge-warning',
                                            'returned' => 'badge-info',
                                            default => 'badge-secondary',
                                        };
                                    @endphp
                                    @if($itemGroup["qc_status"] == 'pending')
                                        <span class="badge badge-warning">
                                            Pending
                                        </span>
                                    @elseif($itemGroup["qc_status"] == 'approved')
                                        <span class="badge badge-success">
                                            Approved
                                        </span>

                                    @elseif($itemGroup["qc_status"] == 'rejected')
                                        <span class="badge badge-success">
                                            Approved
                                        </span>
                                    @else
                                        <span class="badge badge-info">
                                            Not Created
                                        </span>
                                    @endif
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
                             <td style="display: flex; flex-direction: column; justify-content: center; height: 100px;">
                            <div style="display: flex; align-items: center; justify-content: center;">
                                    <a onclick="openModal(this, '{{ route('store.qc.view', ['id' => $supplierRow['data']->id, 'grn' => $requestGroup['request_no']]) }}', 'View QC', false, '70%')"
                                        class="info p-1 text-center mr-2 position-relative" title="Approval">
                                        <i class="ft-check font-medium-3"></i>
                                    </a>
                                    <a onclick="openModal(this, '{{ route('store.qc.view', ['id' => $supplierRow['data']->id, 'grn' => $requestGroup['request_no'], 'type' => 'view']) }}', 'View QC', false, '70%')"
                                        class="info p-1 text-center mr-2 position-relative" title="Approval">
                                        <i class="ft-eye font-medium-3"></i>
                                    </a>
                                    
                                    @if($itemGroup["canUserApprove"])
                                        <a onclick="deletemodal('{{ route('store.qc.delete', $supplierRow['data']->id) }}','{{ route('store.qc.get') }}')"
                                            class="danger p-1 text-center mr-2 position-relative ">

                                            <i class="ft-x font-medium-3"></i>
                                        </a>
                                    @endif
                                </div>
                            </td>
                            @if ($isFirstRequestRow)
                                {{-- <td rowspan="{{ $requestGroup['request_rowspan'] }}">
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
                                </td> --}}
                              
                                
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