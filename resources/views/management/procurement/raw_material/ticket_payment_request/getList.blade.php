<table class="table m-0">
    <thead>
        <tr>
            <th style="width: 10%;">Ticket No / Contract No</th>
            <th style="width: 8%;">Bilty No</th>
            <th style="width: 8%;">Truck No</th>
            <th style="width: 8%;">Location</th>
            <th style="width: 12%;">Accounts Of</th>
            <th style="width: 8%;">Commodity</th>
            <th style="width: 8%;">Loading date</th>
            <th style="width: 12%;">Amounts</th>
            <th style="width: 10%;">Tot. Req. Amt.</th>
            <th style="width: 8%;">Created</th>
            <th style="width: 8%;">Action</th>
        </tr>
    </thead>
    <tbody>
        @if (count($tickets) != 0)
            @foreach ($tickets as $ticket)
                @php
                    // $lastPaymentApproval = lastpaymentStatus($ticket->id, 'freight');
                    $lastPaymentApproval = lastpaymentStatus($ticket->id, 'payment');
                    // dd($lastPaymentApproval);
                @endphp
                <tr>
                    <td>#{{ $ticket->unique_no }} <br>
                        #{{ $ticket->purchaseOrder->contract_no }}
                    </td>
                    <td>{{ $ticket->bilty_no ?? 'N/A' }}</td>
                    <td>{{ $ticket->truck_no ?? 'N/A' }}</td>
                    <td>{{ $ticket->location->name ?? 'N/A' }}</td>
                    <td>{{ $ticket->broker_name ?? 'N/A' }} <br>{{ $ticket->purchaseOrder->supplier->name ?? 'N/A' }}</td>
                    <td>{{ $ticket->qcProduct->name ?? 'N/A' }}</td>
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
                                    <strong>Approved Freight:</strong>
                                    @if ($ticket->freight_paid_by_supplier == 1)
                                        Paid By Supplier
                                    @else
                                        {{ $ticket->calculated_values['approved_freight_sum'] ?? 0 }}<br>
                                    @endif
                                    <strong>Remaining Amount:</strong>
                                    {{ $ticket->calculated_values['remaining_amount'] ?? 0 }}<br>
                                </small>
                            @endif
                        </div>
                    </td>
                    <td>
                        @if(isset($lastPaymentApproval))
                            <span
                                class="d-inline-block mb-1 badge  {{ $lastPaymentApproval->status == 'approved' ? 'badge-success' : ($lastPaymentApproval->status == 'pending' ? 'badge-warning' : ($lastPaymentApproval->status == 'rejected' ? 'badge-danger' : 'badge-secondary')) }}">
                                {{ $lastPaymentApproval->status ?? '' }}
                            </span>
                        @endif
                        @if ($ticket->calculated_values['total_payment_sum'] == 0 && $ticket->calculated_values['total_freight_sum'] == 0)
                            <span class="text-muted"> N/A </span>
                        @else
                            @if ($ticket->calculated_values['total_payment_sum'] > 0)
                                <span class="badge badge-success mb-1">
                                    Payment: {{ number_format($ticket->calculated_values['total_payment_sum'], 2) }}
                                </span><br>
                            @endif
                            @if ($ticket->calculated_values['total_freight_sum'] > 0)
                                @if ($ticket->freight_paid_by_supplier == 1)
                                    <span class="badge badge-warning mb-1">Freight: Paid By Supplier</span>
                                @else
                                    <span class="badge badge-warning">
                                        Freight: {{ number_format($ticket->calculated_values['total_freight_sum'], 2) }}
                                    </span>
                                @endif

                            @endif
                        @endif
                    </td>
                    <td>
                        {{ \Carbon\Carbon::parse($ticket->calculated_values['created_at'])->format('Y-m-d') }} <br>
                        {{ \Carbon\Carbon::parse($ticket->calculated_values['created_at'])->format('H:i A') }}
                    </td>
                    <td>
                        <a onclick="openModal(this,'{{ route('raw-material.ticket.payment-request.edit', $ticket->id) }}','Manage Payment Request')"
                            class="info p-1 text-center mr-2 position-relative">
                            <i class="ft-edit font-medium-3"></i>
                        </a>
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