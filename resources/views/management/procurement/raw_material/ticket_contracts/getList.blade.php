<table class="table m-0">
    <thead>
        <tr>
            <th class="col-sm-2">Ticket No.</th>
            <th class="col-sm-2">Ticket Details</th>
            <th class="col-sm-2">Contract Details</th>
            <th class="col-sm-2">Arrival Details</th>
            <th class="col-sm-2">Created</th>
            <th class="col-sm-1">Action</th>
        </tr>
    </thead>
    <tbody>
        @if (count($tickets) != 0)
            @foreach ($tickets as $key => $row)
                <tr
                    class="{{ !$row->purchaseOrder || ($row->purchaseOrder->status ?? '') == 'draft' ? ' bg-orange ' : '  ' }} {{ $row->first_qc_status == 'rejected' ? ' bg-red ' : '' }}">
                    <td>
                        <p class="m-0">
                            #{{ $row->unique_no ?? 'N/A' }} {{ $row->purchaseOrder->status ?? '' }}<br>
                        </p>
                    </td>
                    <td>
                        <div class="div-box-b">
                            <small>
                                <strong>Broker Name:</strong> {{ $row->broker_name ?? 'N/A' }} <br>
                                <strong>Account Name:</strong> {{ $row->accounts_of_name ?? 'N/A' }} <br>
                                <strong>Truck Number:</strong> {{ $row->truck_no ?? 'N/A' }} <br>
                                <strong>Bilty Number:</strong> {{ $row->bilty_no ?? 'N/A' }} <br>
                                <strong>QC Product:</strong> {{ $row->qcProduct->name ?? 'N/A' }} <br>
                                <strong>Bags:</strong> {{ $row->bags ?? 'N/A' }} <br>
                                <strong>Net Weight:</strong> {{ $row->net_weight ?? 'N/A' }} <br>
                            </small>
                        </div>
                    </td>
                    <td>
                        @if ($row->purchaseOrder)
                            <div class="div-box-b">
                                <small>
                                    <strong>Contract No:</strong> {{ $row->purchaseOrder->contract_no ?? 'N/A' }} <br>
                                    <strong>Supplier Name:</strong> {{ $row->purchaseOrder->supplier->name ?? 'N/A' }}
                                    <br>
                                    <strong>Broker Name:</strong> {{ $row->purchaseOrder->broker_one_name ?? 'N/A' }}
                                    <br>
                                    <strong>Purchase Type:</strong>
                                    {{ formatEnumValue($row->purchaseOrder->purchase_type ?? 'N/A') }} <br>
                                    <strong>No. of Trucks:</strong> {{ $row->purchaseOrder->no_of_trucks ?? 'N/A' }}
                                    <br>
                                    <strong>Truck Number:</strong> {{ $row->purchaseOrder->truck_no ?? 'N/A' }} <br>
                                    <strong>QC Product:</strong> {{ $row->purchaseOrder->qcProduct->name ?? 'N/A' }}
                                    <br>
                                </small>
                            </div>
                        @else
                            N/A
                        @endif
                    </td>
                    <td>
                        <div class="div-box-b">
                            <small>
                                <strong>Arrival Weight:</strong>
                                {{ ($row->firstWeighbridge->weight ?? 0) - ($row->secondWeighbridge->weight ?? 0) ?? 'N/A' }}
                                <br>
                                <strong>First Weight:</strong> {{ $row->firstWeighbridge->weight ?? 'N/A' }} <br>
                                <strong>Second Weight:</strong> {{ $row->secondWeighbridge->weight ?? 'N/A' }} <br>
                                <strong>Station:</strong> {{ $row->station->name ?? 'N/A' }} <br>
                                <strong>Arrival Date:</strong>
                                {{ \Carbon\Carbon::parse($row->created_at)->format('Y-m-d') }} <br>
                                <strong>Arrival Time:</strong>
                                {{ \Carbon\Carbon::parse($row->created_at)->format('h:i A') }} <br>
                                <strong>Status:</strong>
                                {{ formatEnumValue($row->document_approval_status) ?? 'N/A' }} <br>
                            </small>
                        </div>
                    </td>
                    <td>
                        <p class="m-0">
                            {{ \Carbon\Carbon::parse($row->created_at)->format('Y-m-d') }} /
                            {{ \Carbon\Carbon::parse($row->created_at)->format('h:i A') }} <br>
                        </p>
                    </td>
                    <td>
                        @can('role-edit')
                            @if (!$row->purchaseOrder || ($row->purchaseOrder->status ?? '') == 'draft')
                                <a href="{{ route('raw-material.ticket-contracts.create', ['ticket_id' => $row->id]) }}"
                                    class="info p-1 text-center mr-2 position-relative">
                                    <i class="ft-edit font-medium-3"></i>
                                </a>
                            @else
                                <a href="{{ route('raw-material.ticket-contracts.edit', $row->id) }}"
                                    class="info p-1 text-center mr-2 position-relative">
                                    <i class="ft-eye font-medium-3"></i>
                                </a>
                            @endif
                        @endcan
                    </td>
                </tr>
            @endforeach
        @else
            <tr class="ant-table-placeholder">
                <td colspan="11" class="ant-table-cell text-center">
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
        {{ $tickets->links() }}
    </div>
</div>
