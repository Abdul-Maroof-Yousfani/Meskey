<table class="table m-0">
    <thead>
        <tr>
            <th class="col-2">Purchase Quotation No</th>
            <th class="col-2">Purchase Request No</th>
            <th class="col-3">Category - Item</th>
            <th class="col-1">Supplier</th>
            <th class="col-1 text-right">Qty</th>
            <th class="col-1 text-right">Rate</th>
            <th class="col-1">PQ Date</th>
            <th class="col-1">Status</th>
            <th class="col-1">Action</th>
        </tr>
    </thead>
    <tbody>
        @if(count($GroupedPurchaseQuotation) > 0)
            @foreach($GroupedPurchaseQuotation as $requestGroup)
                @php
                    $isFirstRequestRow = true;
                @endphp
                @foreach($requestGroup['items'] as $itemGroup)
                    @php
                        $isFirstItemRow = true;
                    @endphp
                    @foreach($itemGroup['suppliers'] as $supplierRow)
                        @php
                            $approvalDataStatus = ucwords(
                                $supplierRow['data']
                                    ?->{$supplierRow['data']->getApprovalModule()->approval_column ?? 'am_approval_status'}
                            );
                            $approvalStatus = ucwords($requestGroup['request_status']);
                        @endphp

                        <tr>
                            {{-- Purchase Quotation No (first row only) --}}
                            @if($isFirstRequestRow)
                                <td rowspan="{{ $requestGroup['request_rowspan'] }}">
                                    {{ $requestGroup['request_no'] }}
                                </td>
                            @endif

                            {{-- Purchase Request No (first item row only) --}}
                            @if($isFirstItemRow)
                                <td rowspan="{{ $itemGroup['item_rowspan'] ?? 1 }}">
                                    {{ $requestGroup['purchase_request_no'] }}
                                </td>
                                @php $isFirstItemRow = false; @endphp
                            @endif

                            {{-- Category - Item --}}
                            <td>
                                {{ optional($supplierRow['data']->category)->name }} -
                                {{ optional($supplierRow['data']->item)->name }}
                                @php
                                    $statusText = '';
                                    $statusColor = '';
                                    if(strtolower($approvalStatus) === 'partial approved' && strtolower($approvalDataStatus) === 'pending') {
                                        $statusText = 'Neglected';
                                        $statusColor = 'text-danger';
                                    } elseif(strtolower($approvalStatus) === 'rejected' && strtolower($approvalDataStatus) === 'pending') {
                                        $statusText = 'Rejected';
                                        $statusColor = 'text-danger';
                                    } else {
                                        $statusText = $approvalDataStatus ?: 'N/A';
                                        $statusColor = match(strtolower($statusText)) {
                                            'approved' => 'text-success',
                                            'rejected' => 'text-danger',
                                            'returned' => 'text-primary',
                                            'pending' => 'text-warning',
                                            default => 'text-muted',
                                        };
                                    }
                                @endphp
                                @if($statusText)
                                    <span class="{{ $statusColor }}" style="font-weight: 500;">
                                        ({{ $statusText }})
                                    </span>
                                @endif
                            </td>

                            {{-- Supplier --}}
                            <td>{{ optional($supplierRow['data']->supplier)->name }}</td>

                            {{-- Qty --}}
                            <td class="text-right">{{ $supplierRow['data']->qty }}</td>

                            {{-- Rate --}}
                            <td class="text-right">{{ $supplierRow['data']->rate }}</td>

                            {{-- PQ Date --}}
                            <td>
                                {{ \Carbon\Carbon::parse($supplierRow['data']->created_at)->format('Y-m-d') }} /
                                {{ \Carbon\Carbon::parse($supplierRow['data']->created_at)->format('h:i A') }}
                            </td>

                            {{-- Status (first request row only) --}}
                            @if($isFirstRequestRow)
                                <td rowspan="{{ $requestGroup['request_rowspan'] }}">
                                    @php
                                        $badgeClass = match(strtolower($approvalStatus)) {
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
                            @endif

                            {{-- Action (first request row only) --}}
                            @if($isFirstRequestRow)
                                <td rowspan="{{ $requestGroup['request_rowspan'] }}">
                                    <div class="d-flex gap-2">
                                        <a onclick="openModal(this, '{{ route('store.purchase-quotation.comparison-approvals-view', $supplierRow['data']->purchase_quotation->purchase_request_id) }}', 'Approval Voucher', false, '100%')"
                                            class="info p-1 text-center" title="Approval">
                                            <i class="ft-eye font-medium-3"></i>
                                        </a>
                                        @if(!in_array(strtolower($requestGroup['request_status']), ['approved','rejected','partial approved']))
                                            <a onclick="openModal(this,'{{ route('store.purchase-quotation.edit', $supplierRow['data']->purchase_quotation->id) }}','Edit Purchase Quotation',false,'100%')"
                                                class="info p-1 text-center">
                                                <i class="ft-edit font-medium-3"></i>
                                            </a>
                                            <a onclick="deletemodal('{{ route('purchase-quotation.comparison-list', $supplierRow['data']->purchase_quotation->id) }}','{{ route('store.get.purchase-quotation') }}')"
                                                class="danger p-1 text-center">
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
            <tr>
                <td colspan="9" class="text-center">No data</td>
            </tr>
        @endif
    </tbody>
</table>

{{-- Pagination --}}
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
