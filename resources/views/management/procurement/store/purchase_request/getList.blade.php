<table class="table m-0">
    <thead>

        <tr>
            <th class="col-3">Purchase Request No</th>
            {{-- <th class="col-2">Location</th> --}}
            <th class="col-4">Category</th>
            {{-- <th class="col-2 text-right">Requested Qty</th> --}}
            <th class="col-1 text-right">Qty</th>
            <th class="col-1">PR Date</th>
            <th class="col-1">Status</th>
            <th class="col-1">Action</th>
        </tr>
    </thead>
    <tbody>

        @if (count($GroupedPurchaseRequests) > 0)
            @foreach ($GroupedPurchaseRequests as $requestGroup)
                @php $isFirstRequestRow = true; @endphp
                @php $isFirstRequestRow = true; @endphp
                @foreach ($requestGroup['items'] as $itemGroup)
                    <tr>
                        @if ($isFirstRequestRow)
                            <td rowspan="{{ $requestGroup['request_rowspan'] }}"
                                style="background-color: #e3f2fd; vertical-align: middle;">
                                <p class="m-0 font-weight-bold">
                                    #{{ $requestGroup['request_no'] }}
                                </p>
                            </td>
                        @endif

                        {{-- <td style="background-color: #f7f7f7ff; vertical-align: middle;">
                            <p class="m-0 font-weight-bold">
                                {{ optional($itemGroup['item_data']->purchase_request->location)->name ?? 'N/A' }}
                            </p>
                        </td> --}}

                        <td style="background-color: #e8f5e8; vertical-align: middle;">
                            <p class="m-0 font-weight-bold">
                                {{ optional($itemGroup['item_data']->category)->name ?? 'N/A' }} -
                                {{ optional($itemGroup['item_data']->item)->name ?? 'N/A' }}
                            </p>
                        </td>

                        <td>
                            <p class="m-0 text-right">
                                {{ $itemGroup['item_data']->qty }}
                                {{ optional($itemGroup['item_data']->item->unitOfMeasure)->name ?? 'N/A' }}
                            </p>
                        </td>

                        {{-- <td>
                            <p class="m-0 text-right">
                                {{ $itemGroup['item_data']->qty }}
                            </p>
                        </td> --}}
                        <td>
                            <p class="m-0 white-nowrap">
                                {{ \Carbon\Carbon::parse($itemGroup['item_data']->created_at)->format('Y-m-d h:i A') }}
                            </p>
                        </td>

                        @if ($isFirstRequestRow)

                            <td rowspan="{{ $requestGroup['request_rowspan'] }}">
                                @php
                                    $approvalStatus = ucwords($requestGroup['request_status']);
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
                                        $currentApprovalStatus = $itemGroup['item_data']
                                            ?->{$itemGroup['item_data']->getApprovalModule()->approval_column ?? 'am_approval_status'} ?? 'pending';
                                        $isCurrentApproved = strtolower($currentApprovalStatus) === 'approved';
                                        $shouldDisableApproval = $requestGroup['has_approved_item'] && !$isCurrentApproved;
                                    @endphp

                                    @if ($shouldDisableApproval)
                                        <span class="info p-1 text-center mr-2 position-relative" style="opacity: 0.5; cursor: not-allowed;"
                                            title="Approval disabled - Another item in this request is already approved">
                                            <i class="ft-eye font-medium-3"></i>
                                        </span>
                                    @else
                                        <a onclick="openModal(this, '{{ route('store.purchase-request.approvals', $itemGroup['item_data']->id) }}', 'Approval Voucher', false, '80%')"
                                            class="info p-1 text-center mr-2 position-relative" title="Approval">
                                            <i class="ft-eye font-medium-3"></i>
                                        </a>
                                    @endif
                                    @if($requestGroup['created_by_id'] == auth()->user()->id)
                                        @if($requestGroup['request_status'] == 'pending' || $requestGroup['request_status'] == 'reverted')
                                            <a onclick="openModal(this,'{{ route('store.purchase-request.edit', $itemGroup['item_data']->id) }}','Edit Purchase Request',false,'80%')"
                                                class="info p-1 text-center mr-2 position-relative">
                                                <i class="ft-edit font-medium-3"></i>
                                            </a>
                                        @endif
                                    @endif
                                    <a onclick="deletemodal('{{ route('store.purchase-request.destroy', $itemGroup['item_data']->id) }}','{{ route('store.get.purchase-request') }}')"
                                        class="danger p-1 text-center mr-2 position-relative">
                                        <i class="ft-x font-medium-3"></i>
                                    </a>
                                </div>
                            </td>
                            @php $isFirstRequestRow = false; @endphp

                        @endif
                    </tr>
                @endforeach
            @endforeach

        @else
            <tr class="ant-table-placeholder">
                <td colspan="9" class="ant-table-cell text-center">
                    <div class="my-5">
                        <svg width="64" height="41" viewBox="0 0 64 41" xmlns="http://www.w3.org/2000/svg">
                            <g transform="translate(0 1)" fill="none" fill-rule="evenodd">
                                <ellipse fill="#f5f5f5" cx="32" cy="33" rx="32" ry="7">
                                </ellipse>
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

<div class="row d-flex" id="paginationLinks">
    <div class="col-md-12 text-right">
        {{ $PurchaseRequests->links() }}
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