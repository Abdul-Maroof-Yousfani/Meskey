<table class="table m-0" style="width: 100%; min-width: 1710px;">
    <thead>
        <tr>
            <th style="width: 180px; min-width: 180px;">PO Receiving No</th>
            <th style="width: 180px; min-width: 180px;">Purchase Request No</th>
            <th style="width: 180px; min-width: 180px;">Purchase Order No</th>
            <th style="width: 100px; min-width: 100px;">DC No</th>
            <th style="width: 250px; min-width: 250px;">Category- item</th>
            <th style="width: 90px; min-width: 90px;">Qty</th>
            <th style="width: 90px; min-width: 90px;">Rate</th>
            <th style="width: 100px; min-width: 100px;">Total Amount</th>
            <th style="width: 110px; min-width: 110px;">QC</th>
            <th style="width: 110px; min-width: 110px;">QC Status</th>
        </tr>
    </thead>
    <tbody>
        @if (count($GroupedPurchaseOrderReceiving) != 0)
            @foreach ($GroupedPurchaseOrderReceiving as $itemKey => $requestGroup)
                @php $isFirstRequestRow = true; @endphp
                @foreach ($requestGroup['items'] as $itemGroup)
                    @php $isFirstItemRow = true; @endphp
                    
                    @foreach ($itemGroup['suppliers'] as $supplierKeys => $supplierRow)
                        @php
                            $approvalStatus = ucwords($requestGroup['request_status'] ?? 'N/A');
                        @endphp

                        {{-- Hidden modal buttons for QC --}}
                        <button style="visibility: hidden;" id="modalButton{{ $itemGroup['item_data']->id }}" onclick="openModal(this, '{{ route('store.qc.show-create', ['id' => $itemGroup['item_data']->id, 'grn' => get_grn($itemGroup['item_data']->purchase_order_receiving_id)]) }}', 'Add QC', false, '95%')">{{ $itemGroup['item_data']->id }}</button>
                        <button style="visibility: hidden;" id="modalButtonQc{{ $itemGroup['item_data']->id }}" onclick="openModal(this, '{{ route('store.qc.edit', ['id' => $itemGroup['item_data']->id, 'grn' => get_grn($itemGroup['item_data']->purchase_order_receiving_id), 'refresh_url' => route('store.purchase-qc.getList')]) }}', 'Edit QC', false, '95%')">{{ $itemGroup['item_data']->id }}</button>
                      
                        <tr>
                            {{-- PO Receiving No --}}
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

                            {{-- PR No --}}
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

                            {{-- DC No --}}
                            @if ($isFirstItemRow)
                                <td rowspan="{{ $itemGroup['item_rowspan'] ?? 1 }}"
                                    style="background-color: #fff3e0; vertical-align: middle;">
                                    <p class="m-0 font-weight-bold">
                                        {{ $requestGroup['dc_no'] ?? '-' }}
                                    </p>
                                </td>
                                @php $isFirstItemRow = false; @endphp
                            @endif

                            {{-- Category - Item --}}
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

                            {{-- Qty --}}
                            <td>
                                <p class="m-0 text-right">
                                    {{ $supplierRow['data']->qty }}
                                </p>
                            </td>

                            {{-- Rate --}}
                            <td>
                                @php
                                    $rate = $supplierRow["data"]?->purchase_order_data?->rate ?? 0;
                                    $qty = $supplierRow["data"]?->qty ?? 0;
                                @endphp
                                <p class="m-0 text-right">
                                    {{ $rate }}
                                </p>
                            </td>

                            {{-- Total --}}
                            <td>
                                <p class="m-0 text-right">
                                    {{ $qty * $rate }}
                                </p>
                            </td>

                            {{-- QC Actions --}}
                            <td>
                                @if($requestGroup['created_by_id'] == auth()->user()->id || $requestGroup["canApprove"])
                                    @if($supplierRow['data']->category_id == 38)
                                        
                                        @if($itemGroup["qc_status"] != 'approved' && $itemGroup["qc_status"] != 'rejected')
                                            @if(!$itemGroup["qc_status"] == 'pending')
                                                <button onclick="createQc('{{ $itemGroup['item_data']->id }}', '{{ $itemGroup['item_data']->id }}')" style="width: 100px;" type="button" class="btn btn-success btn-sm createQc">Create QC</button>
                                            @else
                                                <button onclick="editQc('{{ $itemGroup['item_data']->id }}', '{{ $itemGroup['item_data']->id }}')" style="width: 100px;" type="button" class="btn btn-warning btn-sm createQc">Edit QC</button>
                                            @endif
                                        @else
                                            <button style="width: 100px;" type="button" class="btn btn-warning btn-sm createQc" disabled>Edit QC</button>
                                        @endif
                                    @else
                                        <span class="badge badge-secondary">Not a bag</span>
                                    @endif
                                @else
                                    <button style="width: 100px;" type="button" class="btn btn-secondary" disabled>Edit QC</button>
                                @endif
                            </td>

                            {{-- QC Status --}}
                            <td>
                                <p class="m-0 text-right">
                                    @if($supplierRow['data']->category_id == 38)
                                        @if($itemGroup["qc_status"] == 'pending')
                                            <span class="badge badge-warning">Pending</span>
                                        @elseif($itemGroup["qc_status"] == 'approved')
                                            <span class="badge badge-success">Approved</span>
                                        @elseif($itemGroup["qc_status"] == 'rejected')
                                            <span class="badge badge-danger">Rejected</span>
                                        @else
                                            <span class="badge badge-info">Not Created</span>
                                        @endif
                                    @else
                                        <span class="badge badge-secondary">Not a bag</span>
                                    @endif
                                </p>
                            </td>
                        </tr>
                        @php $isFirstRequestRow = false; @endphp
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
                                    <path d="M55 12.76L44.854 1.258C44.367.474 43.656 0 42.907 0H21.093c-.749 0-1.46.474-1.947 1.257L9 12.761V22h46v-9.24z"></path>
                                    <path d="M41.613 15.931c0-1.605.994-2.93 2.227-2.931H55v18.137C55 33.26 53.68 35 52.05 35h-40.1C10.32 35 9 33.259 9 31.137V13h11.16c1.233 0 2.227 1.323 2.227 2.928v.022c0 1.605 1.005 2.901 2.237 2.901h14.752c1.232 0 2.237-1.308 2.237-2.913v-.007z" fill="#fafafa"></path>
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
        {{ $PurchaseOrderReceiving->links() }}
    </div>
</div>

<script>
    function createQc(id, key) {
        $("#modalButton" + key).trigger("click");
    }
    function editQc(id, key) {
        $("#modalButtonQc" + key).trigger("click");
    }
</script>
