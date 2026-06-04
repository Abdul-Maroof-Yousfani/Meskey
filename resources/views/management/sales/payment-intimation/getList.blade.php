<table class="table table-hover m-0">
    <thead class="bg-light">
        <tr>
            <th width="20%">Customer</th>
            <th width="20%">Sale Order</th>
            <th width="20%">Bank</th>
            <th width="20%">Payment Deposit</th>
            <th width="20%">Action</th>
        </tr>
    </thead>
    <tbody>
        @forelse($payment_intimations as $payment_intimation)
            <tr>
                <td class="align-middle">
                    <strong>{{ $payment_intimation->customer->name ?? 'N/A' }}</strong>
                </td>
                <td class="align-middle">
                    {{ $payment_intimation->sale_order->reference_no ?? 'N/A' }}
                </td>
                <td class="align-middle">
                    {{ $payment_intimation->bank->bank_name ?? 'N/A' }}
                </td>
                <td class="align-middle">
                    {{ number_format($payment_intimation->payment_deposit, 2) }}
                </td>
                <td class="align-middle">
                    <div class="btn-group" role="group">
                        {{-- <button 
                            onclick="openModal(this,'{{ route('sales.payment-intimation.edit', $payment_intimation->id) }}','Edit Payment Intimation', false, '60%')"
                            class="btn btn-sm btn-warning" title="Edit" style="margin-right: 10px;">
                            <i class="ft-edit"></i>
                        </button> --}}
                        
                        <button onclick="deletemodal('{{ route('sales.payment-intimation.destroy', $payment_intimation->id) }}', '{{ route('sales.get.payment-intimation.list') }}')" type="button"
                                class="btn btn-sm btn-danger" title="Delete">
                            <i class="ft-trash-2"></i>
                        </button>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="5" class="text-center py-5">
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
                        <p class="text-muted mt-3">No Payment Intimations found</p>
                    </div>
                </td>
            </tr>
        @endforelse
    </tbody>
</table>

<div class="row d-flex" id="paginationLinks">
    <div class="col-md-12 text-right">
        {{ $payment_intimations->links() }}
    </div>
</div>
