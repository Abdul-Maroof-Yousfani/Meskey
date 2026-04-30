<table class="table m-0">
    <thead>
        <th width="5%">S no.</th>
        <th width="15%">CI No</th>
        <th width="10%">Date</th>
        <th width="15%">Export Order</th>
        <th width="15%">Bill Of Lading</th>
        <th width="15%">Customer</th>
        <th width="8%">Amount</th>
        <th width="7%">Status</th>
        <th width="10%">Action</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($invoices as $key => $invoice)
            @php
                $computedPreview = $invoice->computed_preview ?? [];
            @endphp
            <tr>
                <td>{{ $invoices->firstItem() + $key }}</td>
                <td>
                    {{ $invoice->invoice_no ?? $invoice->commercial_invoice_no }}
                </td>
                <td>{{ $invoice->invoice_date ? \Carbon\Carbon::parse($invoice->invoice_date)->format('d/m/Y') : 'N/A' }}
                </td>
                <td>
                    <span class="badge bg-light-info text-info">
                        {{ $invoice->exportOrder->voucher_no ?? 'N/A' }}
                    </span>
                </td>
                <td>
                    <span class="badge bg-light-warning text-warning">
                        {{ $invoice->billOfLading->bill_no ?? ($invoice->bill_of_lading_no ?? 'N/A') }}
                    </span>
                </td>
                <td>
                    <div style="font-weight: 600; color: #444;">{{ $computedPreview['customer_name'] ?? 'N/A' }}</div>
                </td>
                <td>
                    <div style="font-weight: 700; color: #2d4580;">
                        {{ $computedPreview['currency_code'] ?? 'USD' }}
                        {{ number_format((float) ($computedPreview['total_amount'] ?? 0), 2) }}
                    </div>
                </td>
                <td class="text-center align-middle">
                    @php
                        $status = $invoice->am_approval_status;
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
                            onclick="openModal(this,'{{ route('commercial-invoice.show', $invoice->id) }}','Show Commercial Invoice',true,'95%')">
                            <i class="ft-eye font-medium-3"></i>
                        </a>
                        @if($invoice->am_approval_status === 'approved')
                            <a class="success p-1 text-center position-relative" title="Print"
                                onclick="openModal(this,'{{ route('commercial-invoice.show', $invoice->id) }}?print=1','Print Commercial Invoice',true,'95%')">
                                <i class="ft-printer font-medium-3"></i>
                            </a>
                        @endif
                        @if($invoice->am_approval_status === 'pending' || $invoice->am_approval_status === 'reverted')
                            <a class="info p-1 text-center position-relative" title="Edit"
                                onclick="openModal(this,'{{ route('commercial-invoice.edit', $invoice->id) }}','Edit Commercial Invoice',false,'95%')">
                                <i class="ft-edit font-medium-3"></i>
                            </a>
                            <a onclick="deletemodal('{{ route('commercial-invoice.destroy', $invoice->id) }}','{{ route('get.commercial-invoice') }}')"
                                class="danger p-1 text-center mr-2 position-relative" title="Delete">
                                <i class="ft-x font-medium-3"></i>
                            </a>
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
            {{ $invoices->links() }}
        </div>
    </div>
</div>