<table class="table m-0">
    <thead>
        <tr>
            <th width="5%">S no.</th>
            <th width="15%">Bill No</th>
            <th width="15%">Bill Date</th>
            <th width="15%">Delivery Challan</th>
            <th width="15%">Delivery Order</th>
            <th width="15%">Customer</th>
            <th width="10%">Carrier</th>
            <th width="10%">Status</th>
            <th width="10%">Action</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($billOfLadings as $key => $billOfLading)
            <tr>
                <td>{{ $billOfLadings->firstItem() + $key }}</td>
                <td>{{ $billOfLading->bill_no }}</td>
                <td>{{ $billOfLading->bill_date ? \Carbon\Carbon::parse($billOfLading->bill_date)->format('d/m/Y') : 'N/A' }}
                </td>
                <td>{{ $billOfLading->snapshot_data['delivery_challan_no'] ?? ($billOfLading->exportDeliveryChallan->dc_no ?? 'N/A') }}
                </td>
                <td>{{ $billOfLading->snapshot_data['delivery_order_no'] ?? ($billOfLading->deliveryOrder->reference_no ?? 'N/A') }}
                </td>
                <td>{{ $billOfLading->snapshot_data['consignee_name'] ?? ($billOfLading->exportDeliveryChallan->customer->name ?? 'N/A') }}
                </td>
                <td>{{ $billOfLading->carrier_name ?? 'N/A' }}</td>
                <td class="text-center align-middle">
                    @php
                        $status = $billOfLading->am_approval_status;
                        $badge = match (strtolower($status)) {
                            'approved' => 'badge-success',
                            'rejected' => 'badge-danger',
                            'pending' => 'badge-warning',
                            'reverted' => 'badge-secondary',
                        };
                    @endphp
                    <span class="badge {{ $badge }} px-3 py-2">
                        {{ ucfirst($status) }}
                    </span>
                </td>
                <td>
                    <a class="info p-1 text-center position-relative"
                        onclick="openModal(this,'{{ route('bill-of-lading.show', $billOfLading->id) }}','Show Bill Of Lading',true,'95%')">
                        <i class="ft-eye font-medium-3"></i>
                    </a>
                    @if($billOfLading->am_approval_status === 'approved')
                        <a class="success p-1 text-center position-relative"
                            onclick="openModal(this,'{{ route('bill-of-lading.show', $billOfLading->id) }}?print=1','Print Bill Of Lading',true,'95%')">
                            <i class="ft-printer font-medium-3"></i>
                        </a>
                    @endif
                    @if($billOfLading->am_approval_status === 'pending' || $billOfLading->am_approval_status === 'reverted')
                        <a class="info p-1 text-center position-relative"
                            onclick="openModal(this,'{{ route('bill-of-lading.edit', $billOfLading->id) }}','Edit Bill Of Lading',false,'95%')">
                            <i class="ft-edit font-medium-3"></i>
                        </a>
                        <a onclick="deletemodal('{{ route('bill-of-lading.destroy', $billOfLading->id) }}','{{ route('get.bill-of-lading') }}')"
                            class="danger p-1 text-center mr-2 position-relative">
                            <i class="ft-x font-medium-3"></i>
                        </a>
                    @endif
                </td>
            </tr>
        @empty
            <tr class="ant-table-placeholder">
                <td colspan="8" class="ant-table-cell text-center">
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
        @endforelse
    </tbody>
</table>

<div class="row d-flex" id="paginationLinks">
    <div class="col-md-12 text-right">
        <div>
            {{ $billOfLadings->links() }}
        </div>
    </div>
</div>