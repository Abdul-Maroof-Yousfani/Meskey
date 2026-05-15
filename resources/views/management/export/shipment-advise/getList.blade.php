<table class="table m-0">
    <thead>
        <tr>
            <th width="5%">S no.</th>
            <th width="17%">Packing List No</th>
            <th width="12%">Date</th>
            <th width="16%">Export Order No</th>
            <!-- <th width="16%">BOL No</th> -->
            <th width="14%">Customer</th>
            <th width="10%">Qty (MT)</th>
            <th width="10%">Status</th>
            <th width="10%">Action</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($shipmentAdvises as $key => $shipmentAdvise)
            @php
                $computedPreview = $shipmentAdvise->computed_preview ?? [];
            @endphp
            <tr>
                <td>{{ $shipmentAdvises->firstItem() + $key }}</td>
                <td>PL-{{ str_pad($shipmentAdvise->packing_list_id, 4, '0', STR_PAD_LEFT) }}</td>
                <td>{{ $shipmentAdvise->created_at ? $shipmentAdvise->created_at->format('d/m/Y') : 'N/A' }}
                </td>
                <td>
                    <span class="badge bg-light-info text-info">
                        {{ optional(optional($shipmentAdvise->commercialInvoice)->exportOrder)->voucher_no
                            ?? optional(optional(optional($shipmentAdvise->packingList)->commercialInvoice)->exportOrder)->voucher_no
                            ?? 'N/A' }}
                    </span>
                </td>
                <!-- <td>
                                <span class="badge bg-light-warning text-warning">
                                    {{ $computedPreview['bill_of_lading_no'] ?? (optional($shipmentAdvise->billOfLading)->bill_no ?? 'N/A') }}
                                </span>
                            </td> -->
                <td>
                    <div style="font-weight: 600; color: #444;">
                        {{ !empty($computedPreview['buyer_block']) ? strtok($computedPreview['buyer_block'], "\n") : 'N/A' }}
                    </div>
                </td>
                <td>
                    <div style="font-weight: 700; color: #2d4580;">
                        {{ number_format((float) ($computedPreview['total_quantity_mt'] ?? 0), 3) }}
                    </div>
                </td>

                <td class="text-center align-middle">
                    @php
                        $status = $shipmentAdvise->am_approval_status;
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
                    <div class="d-flex justify-content-center">
                        <a class="info p-1 text-center position-relative" title="View"
                            onclick="openModal(this,'{{ route('shipment-advise.show', $shipmentAdvise->id) }}','Show Shipment Advice',true,'95%')">
                            <i class="ft-eye font-medium-3"></i>
                        </a>
                        @if($shipmentAdvise->am_approval_status === 'approved')
                            <a class="success p-1 text-center position-relative" title="Print"
                                onclick="openModal(this,'{{ route('shipment-advise.show', $shipmentAdvise->id) }}?print=1','Print Shipment Advice',true,'95%')">
                                <i class="ft-printer font-medium-3"></i>
                            </a>
                        @endif
                        @if (auth()->user()->id == $shipmentAdvise->created_by)
                            @if($shipmentAdvise->am_approval_status === 'pending' || $shipmentAdvise->am_approval_status === 'reverted')
                                <a class="info p-1 text-center position-relative" title="Edit"
                                    onclick="openModal(this,'{{ route('shipment-advise.edit', $shipmentAdvise->id) }}','Edit Shipment Advice',false,'95%')">
                                    <i class="ft-edit font-medium-3"></i>
                                </a>
                                <a onclick="deletemodal('{{ route('shipment-advise.destroy', $shipmentAdvise->id) }}','{{ route('get.shipment-advise') }}')"
                                    class="danger p-1 text-center mr-2 position-relative" title="Delete">
                                    <i class="ft-x font-medium-3"></i>
                                </a>
                            @endif
                        @endif
                    </div>
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
            {{ $shipmentAdvises->links() }}
        </div>
    </div>
</div>
