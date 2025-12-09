<table class="table table-hover m-0">
    <thead class="bg-light">
        <tr>
            <th width="10%">SI No</th>
            <th width="15%">Customer</th>
            <th width="20%">Item</th>
            <th width="8%" class="text-right">Qty</th>
            <th width="8%" class="text-right">Rate</th>
            <th width="10%" class="text-right">Net Amount</th>
            <th width="10%">Invoice Date</th>
            <th width="8%">Status</th>
            <th width="11%">Action</th>
        </tr>
    </thead>
    <tbody>
        @forelse($groupedSalesInvoices as $group)
            @php $isFirstRow = true; @endphp
            @if(count($group['items']) > 0)
                @foreach($group['items'] as $itemRow)
                    <tr>
                        {{-- SI No & Customer - Show only on first row --}}
                        @if($isFirstRow)
                            <td rowspan="{{ $group['rowspan'] }}" class="align-middle text-center font-weight-bold" style="background-color: #e3f2fd;">
                                <div class="p-2">
                                    #{{ $group['si_no'] }}
                                    <br>
                                    <small class="text-muted">
                                        {{ \Carbon\Carbon::parse($group['created_at'])->format('d M Y') }}
                                    </small>
                                </div>
                            </td>

                            <td rowspan="{{ $group['rowspan'] }}" class="align-middle" style="background-color: #e3f2fd;">
                                <strong>{{ $group['customer']->name ?? 'N/A' }}</strong>
                            </td>
                        @endif

                        {{-- Item Details --}}
                        <td class="align-middle" style="background-color: #f8fff8;">
                            <strong>{{ $itemRow['item']->name ?? 'N/A' }}</strong>
                            @if($itemRow['item_data']->line_desc)
                                <br><small class="text-muted">{{ Str::limit($itemRow['item_data']->line_desc, 60) }}</small>
                            @endif
                        </td>

                        <td class="text-right align-middle">
                            {{ number_format($itemRow['item_data']->qty, 2) }}
                        </td>

                        <td class="text-right align-middle">
                            {{ number_format($itemRow['item_data']->rate, 2) }}
                        </td>

                        <td class="text-right align-middle">
                            {{ number_format($itemRow['item_data']->net_amount, 2) }}
                        </td>

                        {{-- Date & Status - Show only on first row --}}
                        @if($isFirstRow)
                            <td rowspan="{{ $group['rowspan'] }}" class="text-center align-middle">
                                {{ \Carbon\Carbon::parse($group['invoice_date'])->format('d M Y') }}
                            </td>

                            <td rowspan="{{ $group['rowspan'] }}" class="text-center align-middle">
                                @php
                                    $status = $group['status'];
                                    $badge = match(strtolower($status)) {
                                        'approved' => 'badge-success',
                                        'rejected' => 'badge-danger',
                                        'pending'  => 'badge-warning',
                                        default    => 'badge-secondary',
                                    };
                                @endphp
                                <span class="badge {{ $badge }} px-3 py-2">
                                    {{ ucfirst($status) }}
                                </span>
                            </td>

                            <td rowspan="{{ $group['rowspan'] }}" class="text-center align-middle">
                                <div class="btn-group" role="group">
                                    <a class="btn btn-sm btn-info" 
                                       onclick="openModal(this,'{{ route('sales.get.sales-invoice.view', ['sales_invoice' => $group['id']]) }}','View Sales Invoice', false, '100%')" 
                                       title="View" style="margin-right: 10px;">
                                        <i class="ft-eye"></i>
                                    </a>
                                    @if(auth()->user()->id == $group['created_by_id'] && $group['status'] === 'pending')
                                        <button 
                                            onclick="openModal(this,'{{ route('sales.sales-invoice.edit', ['sales_invoice' => $group['id']]) }}','Edit Sales Invoice', false, '100%')"
                                            class="btn btn-sm btn-warning" title="Edit" style="margin-right: 10px;">
                                            <i class="ft-edit"></i>
                                        </button>

                                        <button onclick="deletemodal('{{ route('sales.sales-invoice.destroy', ['sales_invoice' => $group['id']]) }}', '{{ route('sales.get.sales-invoice.list') }}')" 
                                                type="button"
                                                class="btn btn-sm btn-danger" title="Delete">
                                            <i class="ft-trash-2"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        @endif
                    </tr>
                    @php $isFirstRow = false; @endphp
                @endforeach
            @else
                {{-- No items, show single row --}}
                <tr>
                    <td class="align-middle text-center font-weight-bold" style="background-color: #e3f2fd;">
                        <div class="p-2">
                            #{{ $group['si_no'] }}
                            <br>
                            <small class="text-muted">
                                {{ \Carbon\Carbon::parse($group['created_at'])->format('d M Y') }}
                            </small>
                        </div>
                    </td>

                    <td class="align-middle" style="background-color: #e3f2fd;">
                        <strong>{{ $group['customer']->name ?? 'N/A' }}</strong>
                    </td>

                    <td class="align-middle text-center text-muted" colspan="3">
                        <em>No items</em>
                    </td>

                    <td class="text-center align-middle">
                        {{ \Carbon\Carbon::parse($group['invoice_date'])->format('d M Y') }}
                    </td>

                    <td class="text-center align-middle">
                        @php
                            $status = $group['status'];
                            $badge = match(strtolower($status)) {
                                'approved' => 'badge-success',
                                'rejected' => 'badge-danger',
                                'pending'  => 'badge-warning',
                                default    => 'badge-secondary',
                            };
                        @endphp
                        <span class="badge {{ $badge }} px-3 py-2">
                            {{ ucfirst($status) }}
                        </span>
                    </td>

                    <td class="text-center align-middle">
                        <div class="btn-group" role="group">
                            <a class="btn btn-sm btn-info" 
                               onclick="openModal(this,'{{ route('sales.get.sales-invoice.view', ['sales_invoice' => $group['id']]) }}','View Sales Invoice', false, '100%')" 
                               title="View" style="margin-right: 10px;">
                                <i class="ft-eye"></i>
                            </a>
                            @if(auth()->user()->id == $group['created_by_id'] && $group['status'] === 'pending')
                                <button 
                                    onclick="openModal(this,'{{ route('sales.sales-invoice.edit', ['sales_invoice' => $group['id']]) }}','Edit Sales Invoice', false, '100%')"
                                    class="btn btn-sm btn-warning" title="Edit" style="margin-right: 10px;">
                                    <i class="ft-edit"></i>
                                </button>

                                <button onclick="deletemodal('{{ route('sales.sales-invoice.destroy', ['sales_invoice' => $group['id']]) }}', '{{ route('sales.get.sales-invoice.list') }}')" 
                                        type="button"
                                        class="btn btn-sm btn-danger" title="Delete">
                                    <i class="ft-trash-2"></i>
                                </button>
                            @endif
                        </div>
                    </td>
                </tr>
            @endif
        @empty
            <tr>
                <td colspan="9" class="text-center py-5">
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
                        <p class="text-muted mt-3">No Sales Invoice found</p>
                    </div>
                </td>
            </tr>
        @endforelse
    </tbody>
</table>

<div class="row d-flex" id="paginationLinks">
    <div class="col-md-12 text-right">
        {{ $salesInvoices->links() }}
    </div>
</div>
