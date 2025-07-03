<table class="table m-0">
    <thead>
        <tr>
            <th class="col-sm-1">#</th>
            <th class="col-sm-2">Contract No</th>
            <th class="col-sm-2">Supplier</th>
            <th class="col-sm-2">Type</th>
            <th class="col-sm-1">Amount</th>
            <th class="col-sm-2">Status</th>
            <th class="col-sm-1">Created</th>
            <th class="col-sm-1">Action</th>
        </tr>
    </thead>
    <tbody>
        @if (count($paymentRequests) != 0)
            @foreach ($paymentRequests as $request)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>#{{ $request->paymentRequestData->purchaseOrder->contract_no ?? 'N/A' }}</td>
                    <td>{{ $request->paymentRequestData->supplier_name ?? 'N/A' }}</td>
                    <td>
                        <span class="badge badge-{{ $request->request_type == 'payment' ? 'success' : 'warning' }}">
                            {{ formatEnumValue($request->request_type) }}
                        </span>
                    </td>
                    <td>{{ number_format($request->amount, 2) }}</td>
                    <td>
                        @if ($request->status == 'approved')
                            <span class="badge badge-success">Approved</span>
                        @else
                            <span class="badge badge-info">Pending</span>
                        @endif
                        {{-- @if ($request->approvals->count() > 0)
                            <span
                                class="badge badge-{{ $request->approvals->first()->status == 'approved' ? 'success' : 'danger' }}">
                                {{ ucfirst($request->approvals->first()->status) }}
                            </span>
                        @else
                            <span class="badge badge-info">Pending</span>
                        @endif --}}
                    </td>
                    <td>{{ $request->created_at->format('Y-m-d H:i') }}</td>
                    <td>
                        @can('role-edit')
                            {{-- <a onclick="openModal(this,'{{ route('raw-material.payment-request-approval.edit', $request->id) }}','Manage Payment Request'{{ $request->approvals->count() > 0 ? ', true' : '' }})"
                                class="info p-1 text-center mr-2 position-relative"> --}}
                            <a onclick="openModal(this,'{{ route('raw-material.payment-request-approval.edit', $request->id) }}','Manage Payment Request'{{ $request->status == 'approved' ? ', true' : '' }})"
                                class="info p-1 text-center mr-2 position-relative">
                                <i class="ft-edit font-medium-3"></i>
                            </a>
                        @endcan

                    </td>
                </tr>
            @endforeach
        @else
            <tr class="ant-table-placeholder">
                <td colspan="8" class="ant-table-cell text-center">
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
                        <p class="ant-empty-description">No payment requests found</p>
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
