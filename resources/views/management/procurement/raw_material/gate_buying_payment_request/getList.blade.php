<table class="table m-0">
    <thead>
        <tr>
            <th class="col-sm-2">Contract No</th>
            <th class="col-sm-2">Supplier</th>
            <th class="col-sm-2">Commodity</th>
            <th class="col-sm-1">Loading date</th>
            <th class="col-sm-2">Amounts</th>
            <th class="col-sm-1">Tot. Req. Amt.</th>
            <th class="col-sm-1">Created</th>
            <th class="col-sm-1">Action</th>
        </tr>
    </thead>
    <tbody>
        @if (count($tickets) != 0)
            @foreach ($tickets as $ticket)
                <tr>
                    <td>#{{ $ticket->unique_no }} <br>
                        #{{ $ticket->purchaseOrder->contract_no }}
                    </td>
                    {{-- @dd($ticket->purchaseFreights) --}}
                    <td>{{ $ticket->broker_name ?? 'N/A' }} <br>{{ $ticket->purchaseOrder->supplier->name ?? 'N/A' }}
                    </td>
                    <td>{{ $ticket->qcProduct->name ?? 'N/A' }}
                        {{-- <br>{{ $ticket->purchaseOrder->qcProduct->name ?? 'N/A' }} --}}
                    </td>
                    <td>
                        {{ $ticket ? \Carbon\Carbon::parse($ticket->loading_date)->format('Y-m-d') : 'N/A' }}
                    </td>
                    <td>
                        <div class="div-box-b">
                            @if ($ticket->calculated_values['total_payment_sum'] == 0 && $ticket->calculated_values['total_freight_sum'] == 0)
                                <span class="text-muted"> No requests generated yet</span>
                            @else
                                <small>
                                    <strong>Total Amount:</strong> {{ $ticket->calculated_values['total_amount'] ?? 0 }}
                                    <br>
                                    {{-- <strong>Paid Amount:</strong> {{ $ticket->calculated_values['paid_amount'] ?? 0 }} <br>
                                    --}}
                                    <strong>Approved Payment:</strong>
                                    {{ $ticket->calculated_values['approved_payment_sum'] ?? 0 }}<br>
                                    <strong>Remaining Amount:</strong>
                                    {{ $ticket->calculated_values['remaining_amount'] ?? 0 }}<br>
                                </small>
                            @endif
                        </div>
                    </td>
                    <td>
                        @if ($ticket->calculated_values['total_payment_sum'] == 0 && $ticket->calculated_values['total_freight_sum'] == 0)
                            <span class="text-muted"> N/A </span>
                        @else
                            @if ($ticket->calculated_values['total_payment_sum'] > 0)
                                <span class="badge badge-success mb-1">
                                    Payment: {{ number_format($ticket->calculated_values['total_payment_sum'], 2) }}
                                </span><br>
                            @endif
                            @if ($ticket->calculated_values['total_freight_sum'] > 0)
                                <span class="badge badge-warning">
                                    Freight: {{ number_format($ticket->calculated_values['total_freight_sum'], 2) }}
                                </span>
                            @endif
                        @endif
                    </td>
                    <td>
                        {{ \Carbon\Carbon::parse($ticket->calculated_values['created_at'])->format('Y-m-d') }} <br>
                        {{ \Carbon\Carbon::parse($ticket->calculated_values['created_at'])->format('H:i A') }}
                    </td>
                    <td>
                        <a onclick="openModal(this,'{{ route('raw-material.gate-buy.payment-request.edit', $ticket->id) }}','Generate Bill (Gate Buying)')"
                            class="info p-1 text-center mr-2 position-relative">
                            <i class="ft-edit font-medium-3"></i>
                        </a>
                    </td>
                </tr>
            @endforeach
        @else
            <tr class="ant-table-placeholder">
                <td colspan="7" class="ant-table-cell text-center">
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
