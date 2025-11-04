<table class="table m-0">
    <thead>
        <tr>
            <th>Request No</th>
            <th>Request Date</th>
            <th>Type</th>
            <th>Supplier</th>
            <th>PO Number</th>
            <th>GRN Number</th>
            <th>Amount</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @if (count($paymentRequests) != 0)
            @foreach ($paymentRequests as $paymentRequest)
                <tr>
                    <td>{{ $paymentRequest->request_no }}</td>
                    <td>{{ \Carbon\Carbon::parse($paymentRequest->request_date)->format('Y-m-d') }}</td>
                    <td>
                        <span class="badge badge-{{ $paymentRequest->payment_type == 'advance' ? 'warning' : 'info' }}">
                            {{ ucfirst(formatEnumValue($paymentRequest->payment_type)) }}
                        </span>
                    </td>
                    <td>{{ optional($paymentRequest->supplier)->name }}</td>
                    <td>{{ optional($paymentRequest->purchaseOrder)->purchase_order_no ?? ($paymentRequest->grn->purchaseOrder->purchase_order_no ?? 'N/A') }}
                    </td>
                    <td>{{ optional($paymentRequest->grn)->purchase_order_receiving_no ?? 'N/A' }}</td>
                    <td>{{ number_format($paymentRequest->amount, 2) }}</td>
                    <td>
                        <span
                            class="badge badge-{{ $paymentRequest->am_approval_status == 'approved' ? 'success' : ($paymentRequest->am_approval_status == 'pending' ? 'warning' : 'danger') }}">
                            {{ ucfirst($paymentRequest->am_approval_status) }}
                        </span>
                    </td>
                    <td>
                        {{-- @can('payment-request-edit') --}}
                        @if($requestGroup['created_by_id'] == auth()->user()->id)
                            @if($paymentRequest->am_approval_status == 'pending' || $paymentRequest->am_approval_status == 'reverted')
                                <a onclick="openModal(this,'{{ route('store.purchase-order-payment-request.edit', $paymentRequest->id) }}','Edit Payment Request',false,'80%')"
                                    class="info p-1 text-center mr-2 position-relative">
                                    <i class="ft-edit font-medium-3"></i>
                                </a>
                            @endif
                        @endif
                        {{-- @endcan --}}
                        {{-- @can('payment-request-delete') --}}
                        @if($paymentRequest->am_approval_status == 'pending' || $paymentRequest->am_approval_status == 'reverted')

                            <a onclick="deletemodal('{{ route('store.purchase-order-payment-request.destroy', $paymentRequest->id) }}','{{ route('store.get.purchase-order-payment-request') }}')"
                                class="danger p-1 text-center mr-2 position-relative">
                                <i class="ft-x font-medium-3"></i>
                            </a>
                        @endif
                        {{-- @endcan --}}
                        {{-- @can('payment-request-approve') --}}
                        {{-- @if ($paymentRequest->status == 'pending')
                        <a onclick="approvePaymentRequest('{{ route('store.purchase-order-payment-request.approve', $paymentRequest->id) }}')"
                            class="success p-1 text-center position-relative" title="Approve">
                            <i class="ft-check font-medium-3"></i>
                        </a>
                        @endif --}}
                        {{-- @endcan --}}
                        <a onclick="openModal(this, '{{ route('store.purchase-order-payment-request.approvals', $paymentRequest->id) }}', 'View Payment Request', true, '80%')"
                            class="info p-1 text-center mr-2 position-relative" title="Approval">
                            <i class="ft-eye font-medium-3"></i>
                        </a>
                    </td>
                </tr>
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
        {{ $paymentRequests->links() }}
    </div>
</div>