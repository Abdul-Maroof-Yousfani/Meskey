<table class="table m-0" style="table-layout: fixed; width: 100%;">
    <thead>
        <tr>
            <th style="width: 12%;">Purchase Request</th>
            <th style="width: 12%;">Purchase Quotation No</th>
            <th style="width: 18%;">Category - Item</th>
            <th style="width: 18%;">Suppliers</th>
            <th style="width: 8%;">UOM</th>
            <th style="width: 8%;">Qty</th>
            <th style="width: 8%;">Rate</th>
            <th style="width: 8%;">Amount</th>
            <th style="width: 8%;">Action</th>
        </tr>
    </thead>


    <tbody>
        @if (count($GroupedPurchaseQuotation) != 0)
            @php
                $previousRequestNo = null; 
            @endphp
            @foreach ($GroupedPurchaseQuotation as $requestGroup)
                @php
                    $is_editing_allowed = false;
                    $currentRequestNo = $requestGroup['purchase_request_no'];

                    // Check if ANY item in ANY quotation belonging to this PR is pending or reverted
                    foreach ($GroupedPurchaseQuotation as $row) {
                        if ($row['purchase_request_no'] === $currentRequestNo) {
                            foreach ($row['items'] as $itemGroup) {
                                foreach ($itemGroup['suppliers'] as $supplierRowData) {
                                    $status = strtolower(
                                        $supplierRowData['data']?->{$supplierRowData['data']->getApprovalModule()->approval_column ?? 'am_approval_status'} ?? ''
                                    );
                                    if (in_array($status, ['pending', 'reverted'])) {
                                        $is_editing_allowed = true;
                                        break 3;
                                    }
                                }
                            }
                        }
                    }

                    $totalRequestRowspan = array_sum(
                        array_column(
                            array_filter($GroupedPurchaseQuotation, function ($row) use ($currentRequestNo) {
                                return $row['purchase_request_no'] === $currentRequestNo;
                            }),
                            'request_rowspan'
                        )
                    );
                @endphp

                @php $isFirstRequestRow = true; @endphp
                {{-- @php
                    $requestGroup['quotaion_rowspan'] = 0;
                @endphp --}}
                @foreach ($requestGroup['items'] as $itemGroup)
                  
                    @php $isFirstItemRow = true; @endphp
                      
                    @foreach ($itemGroup['suppliers'] as $supplierRow)
                        @php
                            $approvalDataStatus = ucwords(
                                $supplierRow['data']
                                    ?->{$supplierRow['data']->getApprovalModule()->approval_column ??
                                        'am_approval_status'},
                            );
                            $approvalStatus = ucwords($requestGroup['request_status']);

                            $quotation_rowspan = 0;
                        @endphp

                        <tr>
                            @if ($previousRequestNo !== $currentRequestNo)
                                <td rowspan="{{ $totalRequestRowspan }}"
                                    style="background-color: #e8f5e8; vertical-align: middle;">
                                    <p class="m-0 font-weight-bold">
                                        #{{ $requestGroup['purchase_request_no'] }}
                                    </p>
                                </td>



                                {{-- @php
                                $previousRequestNo = $currentRequestNo;
                            @endphp --}}
                            @endif

                            {{-- ✅ Other columns --}}
                            @if ($isFirstRequestRow)
                                <td rowspan="{{ $requestGroup['request_rowspan'] }}"
                                    style="background-color: #e3f2fd; vertical-align: middle;">
                                    <p class="m-0 font-weight-bold">
                                        #{{ $requestGroup['request_no'] }}
                                    </p>
                                </td>
                                @php $isFirstRequestRow = false; @endphp
                            @endif

                            <td>
                                @php
                                    $statusText = '';
                                    $statusColor = '';

                                    if (
                                        strtolower($approvalStatus) === 'partial approved' &&
                                        strtolower($approvalDataStatus) === 'pending'
                                    ) {
                                        $statusText = 'Pending';
                                        $statusColor = 'text-warning';
                                    } elseif (
                                        strtolower($approvalStatus) === 'rejected' &&
                                        strtolower($approvalDataStatus) === 'pending'
                                    ) {
                                        $statusText = 'Rejected';
                                        $statusColor = 'text-danger';
                                    } else {
                                        $statusText = $approvalDataStatus ?: 'N/A';
                                        $statusColor = match (strtolower($statusText)) {
                                            'approved' => 'text-success',
                                            'rejected' => 'text-danger',
                                            'neglected' => 'text-warning',
                                            'returned' => 'text-primary',
                                            'pending' => 'text-warning',
                                            default => 'text-muted',
                                        };
                                    }
                                @endphp

                                <p class="m-0 font-weight-bold">
                                    {{ optional($supplierRow['data']->category)->name }} -
                                    {{ optional($supplierRow['data']->item)->name }}
                                    @if ($statusText)
                                        <span class="{{ $statusColor }}" style="font-weight: 500;">
                                            ({{ $statusText }})
                                        </span>
                                    @endif
                                </p>
                                @if(optional($supplierRow['data']->purchase_request)->is_single_job_order)
                                    <span class="badge badge-yellow mt-1" style="font-size: 10px; padding: 3px 10px; border-radius: 20px;">With job order</span>
                                @else
                                    <span class="badge badge-secondary mt-1" style="font-size: 10px; padding: 3px 10px; border-radius: 20px;">Without job order</span>
                                @endif
                            </td>


                            <td style="background-color: #fff3e0; vertical-align: middle;">
                                <p class="m-0 font-weight-bold">
                                    {{ optional($supplierRow['data']->supplier)->name }}
                                </p>
                            </td>

                            <td>
                                <p class="m-0 text-right">
                                    {{ optional($supplierRow['data']->item->unitOfMeasure)->name }}
                                </p>
                            </td>

                            <td>
                                <p class="m-0 text-right">{{ $supplierRow['data']->qty }}</p>
                            </td>

                            <td>
                                <p class="m-0 text-right">{{ $supplierRow['data']->rate }}</p>
                            </td>

                            <td>
                                <p class="m-0 text-right">{{ $supplierRow['data']->total }}</p>
                            </td>

                            @if ($previousRequestNo !== $currentRequestNo)
                                <td rowspan="{{ $totalRequestRowspan }}" style="vertical-align: middle;">
                                    <div class="d-flex flex-column" style="gap: 10px;">
                                        <a onclick="openModal(this, '{{ route('store.purchase-quotation.comparison-approvals', ['id' => $supplierRow['data']->purchase_quotation->purchase_request_id, 'listRefresh' => route('store.purchase-quotation.comparison')]) }}', 'Quotation Approval', false, '100%')"
                                            class="bg-info text-white p-1 text-center position-relative" title="Approval" style="border-radius: 4px; min-width: 70px;">
                                            Approval
                                        </a>
                                        <a onclick="openModal(this, '{{ route('store.purchase-quotation.dataForComparison', $supplierRow['data']->purchase_quotation->purchase_request_id) }}', 'View Comparison', false, '100%')"
                                            class="bg-primary text-white p-1 text-center position-relative" title="Compare" style="border-radius: 4px; min-width: 70px;">
                                            Compare
                                        </a>
                                        @if($requestGroup['created_by_id'] == auth()->user()->id && $is_editing_allowed)
                                            <a onclick="openModal(this, '{{ route('store.purchase-quotation.edit', [$supplierRow['data']->purchase_quotation->id, 'purchase_request_id' => $supplierRow['data']->purchase_quotation->purchase_request_id]) }}', 'Quotation Edit', false, '100%')"
                                                class="bg-warning text-white p-1 text-center position-relative" title="Edit" style="border-radius: 4px; min-width: 70px;">
                                                Edit
                                            </a>
                                        @endif
                                    </div>
                                </td>
                                @php
                                    $previousRequestNo = $currentRequestNo;
                                @endphp
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
        {{ $PurchaseQuotation->links() }}
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
                    success: function(res) {
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
                    error: function() {
                        Swal.fire('Error', 'Error occurred while approving item.', 'error');
                    }
                });
            }
        });
    }
</script>
