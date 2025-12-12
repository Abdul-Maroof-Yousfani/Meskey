<table class="table m-0">
    <thead>
        <tr>
            <th>RV No</th>
            <th>Date</th>
            <th>Type</th>
            <th>Account</th>
            <th>Bill/Ref No</th>
            <th>Cheque No</th>
            <th>Amount</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @if (count($receiptVouchers) != 0)
            @foreach ($receiptVouchers as $voucher)
                <tr>
                    <td>{{ $voucher->unique_no }}</td>
                    <td>{{ optional($voucher->rv_date)->format('d-m-Y') }}</td>
                    <td>{{ ucfirst(str_replace('_', ' ', $voucher->voucher_type)) }}</td>
                    <td>{{ $voucher->account->account_name ?? $voucher->account->name ?? 'N/A' }}</td>
                    <td>{{ $voucher->ref_bill_no ?? 'N/A' }}</td>
                    <td>{{ $voucher->cheque_no ?? 'N/A' }}</td>
                    <td>{{ number_format($voucher->total_amount, 2) }}</td>
                    <td>
                        <a onclick="openModal(this, '{{ route('receipt-voucher.show', $voucher->id) }}', 'View Receipt Voucher', true, '80%')"
                            class="info p-1 text-center mr-2 position-relative" title="View">
                            <i class="ft-eye font-medium-3"></i>
                        </a>
                        <a class="info p-1 text-center mr-2 position-relative"
                            href="{{ route('receipt-voucher.edit', $voucher->id) }}" title="Edit">
                            <i class="ft-edit font-medium-3"></i>
                        </a>
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
                        <p class="ant-empty-description">No data</p>
                    </div>
                </td>
            </tr>
        @endif
    </tbody>
</table>

<div class="row d-flex" id="paginationLinks">
    <div class="col-md-12 text-right">
        {{ $receiptVouchers->links() }}
    </div>
</div>
    

