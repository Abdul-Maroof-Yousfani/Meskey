<table class="table m-0">
    <thead>
        <tr>
            <th class="col-2">Purchase Request</th>
            <th class="col-2">Purchase Quotation No</th>
            <th class="col-3">Category - Item</th>
            <th class="col-3">Suppliers</th>
            <th class="col-1 text-right">UOM</th>
            <th class="col-1 text-right">Qty</th>
            <th class="col-1 text-right">Rate</th>
            <th class="col-1 text-right">Amount</th>
            <th class="col-1">Action</th>
        </tr>
    </thead>

    <tbody>
        @if (count($GroupedPurchaseQuotation) != 0)
            @php
                $previousRequestNo = null; // Track previous request number
                $previousQuotationNo = null;
                $isFirstRequestRow = true;
            @endphp
        
            @foreach ($GroupedPurchaseQuotation as $requestGroup)
                @php
                    $currentRequestNo = $requestGroup['purchase_request_no'];
                    // $totalRequestRowspan = $requestGroup['request_rowspan'];
                    $totalRequestRowspan = array_sum(
                        array_column(
                            array_filter($GroupedPurchaseQuotation, function ($row) use ($currentRequestNo) {
                                return $row['purchase_request_no'] === $currentRequestNo;
                            }),
                            'request_rowspan'
                        )
                    );

                    // $totalRequestRowspan = array_sum(array_column($GroupedPurchaseQuotation, 'request_rowspan'));
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
                                <td rowspan="{{ $requestGroup['quotaion_rowspan'] }}"
                                    style="background-color: #e8f5e8; vertical-align: middle;">
                                    <p class="m-0 font-weight-bold">
                                        {{ $totalRequestRowspan }}
                                        #{{ $requestGroup['purchase_request_no'] }}
                                    </p>
                                </td>



                                {{-- @php
                                $previousRequestNo = $currentRequestNo;
                            @endphp --}}
                            @endif

                            {{-- ✅ Other columns --}}
                            @if ($isFirstRequestRow)
                                @php
                                    $quotation_rowspan += $requestGroup['request_rowspan'];
                                @endphp
                                <td rowspan="{{ $requestGroup['request_rowspan'] }}"
                                    style="background-color: #e3f2fd; vertical-align: middle;">
                                    <p class="m-0 font-weight-bold">
                                        {{ $requestGroup['request_rowspan'] }}
                                        #{{ $requestGroup['request_no'] }}
                                    </p>
                                </td>
                            @endif

                            <td>
                                @php
                                    $statusText = '';
                                    $statusColor = '';

                                    if (
                                        strtolower($approvalStatus) === 'partial approved' &&
                                        strtolower($approvalDataStatus) === 'pending'
                                    ) {
                                        $statusText = 'Neglected';
                                        $statusColor = 'text-danger';
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
                                <td rowspan="{{ $requestGroup['quotaion_rowspan'] }}">
                                    <div class="d-flex gap-2">
                                        <a onclick="openModal(this, '{{ route('store.purchase-quotation.comparison-approvals', $supplierRow['data']->purchase_quotation->purchase_request_id) }}', 'Quotation Approval', false, '100%')"
                                            class="info p-1 text-center mr-2 position-relative" title="Approval">
                                            <i class="ft-check font-medium-3"></i>

                                        </a>
                                    </div>
                                    @if (
                                        $requestGroup['request_status'] != 'approved' &&
                                            $requestGroup['request_status'] != 'rejected' &&
                                            $requestGroup['request_status'] != 'partial approved')
                                        <div class="d-flex gap-2">
                                            <a onclick="openModal(this, '{{ route('store.purchase-quotation.edit', [$supplierRow['data']->purchase_quotation->id, 'purchase_request_id' => $supplierRow['data']->purchase_quotation->purchase_request_id]) }}', 'Quotation Edit', false, '100%')"
                                                class="info p-1 text-center mr-2 position-relative"
                                                title="View Approved">
                                                <i class="ft-edit font-medium-3"></i>

                                            </a>
                                        </div>
                                    @endif
                                    {{-- </td>
                            <td rowspan="{{ $requestGroup['quotaion_rowspan'] }}"> --}}
                                    <div class="d-flex gap-2">
                                        <a onclick="openModal(this, '{{ route('store.purchase-quotation.comparison-approvals-view', $supplierRow['data']->purchase_quotation->purchase_request_id) }}', 'Quotation Approval', false, '100%')"
                                            class="info p-1 text-center mr-2 position-relative" title="View Approved">
                                            <i class="ft-eye font-medium-3"></i>
                                        </a>
                                    </div>
                                </td>

                                @php
                                    $previousRequestNo = $currentRequestNo;
                                @endphp
                            @endif

                        </tr>
                      
                    @endforeach
                    @php $isFirstRequestRow = false; @endphp
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
