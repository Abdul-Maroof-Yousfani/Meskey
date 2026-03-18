{{-- resources/views/management/procurement/store/purchase-return/getList.blade.php --}}

            <table class="table table-hover m-0">
                <thead class="bg-light">
                    <tr>
                        <th width="7%">PR No</th>
                        <th width="9%">PB No</th>
                        <th width="13%">Supplier</th>
                        <th width="10%">Item</th>
                        <th width="6%" class="text-right">Qty</th>
                        <th width="6%" class="text-right">Rate</th>
                        <th width="6%" class="text-right">Disc %</th>
                        <th width="6%" class="text-right">Disc Amt</th>
                        <th width="6%" class="text-right">Amount</th>
                        <th width="5%">Status</th>
                        <th width="8%">Created</th>
                        <th width="13%">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($purchaseReturns as $purchaseReturn)
                        @php
                            $items = $purchaseReturn->purchase_return_data;
                            $itemCount = $items->count();
                            $rowspan = $itemCount > 0 ? $itemCount : 1;
                            $isFirstRow = true;
                        @endphp

                        @if($itemCount > 0)
                            @foreach($items as $item)
                                <tr>
                                    {{-- PR No - Show only on first row --}}
                                    @if($isFirstRow)
                                        <td rowspan="{{ $rowspan }}" class="align-middle text-center font-weight-bold" style="background-color: #e3f2fd;">
                                            <div class="p-2">
                                                {{ $purchaseReturn->pr_no }}
                                                <br>
                                                <small class="text-muted">
                                                    {{ \Carbon\Carbon::parse($purchaseReturn->created_at)->format('d M Y') }}
                                                </small>
                                            </div>
                                        </td>
                                    @endif

                                    {{-- Purchase Bill No --}}
                                    <td class="align-middle text-center" style="background-color: #e8f5e8;">
                                        @if($item->purchase_bill_data && $item->purchase_bill_data->purchaseBill)
                                            {{ $item->purchase_bill_data->purchaseBill->bill_no }}
                                            <br>
                                            <small class="text-muted">
                                                {{ $item->purchase_bill_data->purchaseBill->supplier->name ?? 'N/A' }}
                                            </small>
                                        @else
                                            N/A
                                        @endif
                                    </td>

                                    {{-- Supplier - Show only on first row --}}
                                    @if($isFirstRow)
                                        <td rowspan="{{ $rowspan }}" class="align-middle" style="background-color: #e3f2fd;">
                                            <strong>{{ $purchaseReturn->supplier->name ?? 'N/A' }}</strong>
                                        </td>
                                    @endif

                                    {{-- Item --}}
                                    <td class="align-middle">
                                        {{ $item->item->name ?? 'N/A' }}
                                        @if($item->description)
                                            <br><small class="text-muted">{{ $item->description }}</small>
                                        @endif
                                    </td>

                                    {{-- Qty --}}
                                    <td class="text-right align-middle">
                                        {{ number_format($item->quantity, 2) }}
                                        @if($item->packing)
                                            <br><small class="text-muted">Packing: {{ $item->packing }}</small>
                                        @endif
                                    </td>

                                    {{-- Rate --}}
                                    <td class="text-right align-middle">
                                        {{ number_format($item->rate, 2) }}
                                    </td>

                                    {{-- Discount % --}}
                                    <td class="text-right align-middle">
                                        {{ number_format($item->discount_percent, 2) }}%
                                    </td>

                                    {{-- Discount Amount --}}
                                    <td class="text-right align-middle">
                                        {{ number_format($item->discount_amount, 2) }}
                                    </td>

                                    {{-- Amount --}}
                                    <td class="text-right align-middle">
                                        {{ number_format($item->net_amount, 2) }}
                                    </td>

                                    {{-- Status - Show only on first row --}}
                                    @if($isFirstRow)
                                        <td rowspan="{{ $rowspan }}" class="text-center align-middle">
                                            @php
                                                $status = $purchaseReturn->am_approval_status;
                                                $badge = match(strtolower($status)) {
                                                    'approved' => 'badge-success',
                                                    'rejected' => 'badge-danger',
                                                    'pending'  => 'badge-warning',
                                                    default    => 'badge-secondary',
                                                };
                                            @endphp
                                            <span class="badge {{ $badge }} px-2 py-1">
                                                {{ ucfirst($status) }}
                                            </span>
                                        </td>

                                        {{-- Created - Show only on first row --}}
                                        <td rowspan="{{ $rowspan }}" class="text-center align-middle">
                                            <small class="text-muted">
                                                {{ \Carbon\Carbon::parse($purchaseReturn->created_at)->format('d M Y H:i') }}
                                            </small>
                                        </td>

                                        {{-- Action - Show only on first row --}}
                                        <td rowspan="{{ $rowspan }}" class="text-center align-middle">
                                            <div class="btn-group" role="group">
                                                <a
                                                   class="btn btn-sm btn-info" onclick="openModal(this,'{{ route('store.purchase-return.view', ['id' => $purchaseReturn->id]) }}','View Purchase Return', false, '100%')" title="View" style="margin-right: 5px;">
                                                    <i class="ft-eye"></i>
                                                </a>
                                                @if(auth()->user()->id == $purchaseReturn->created_by)
                                                    @if($purchaseReturn->am_approval_status === 'pending' || $purchaseReturn->am_approval_status === 'reverted')
                                                    <button
                                                        onclick="openModal(this,'{{ route('store.purchase-return.edit', $purchaseReturn->id) }}','Edit Purchase Return', false, '100%')"
                                                        class="btn btn-sm btn-warning" title="Edit" style="margin-right: 5px;">
                                                        <i class="ft-edit"></i>
                                                    </button>

                                                <button onclick="deletemodal('{{ route('store.purchase-return.destroy', $purchaseReturn->id) }}', '{{ route('store.get.purchase-return') }}')" type="button"
                                                        class="btn btn-sm btn-danger" title="Delete">
                                                    <i class="ft-trash-2"></i>
                                                </button>
                                                    @endif
                                                @endif
                                            </div>
                                        </td>
                                    @endif
                                </tr>
                                @php $isFirstRow = false @endphp
                            @endforeach
                        @else
                            {{-- No items case --}}
                            <tr>
                                <td class="align-middle text-center font-weight-bold" style="background-color: #e3f2fd;">
                                    <div class="p-2">
                                        {{ $purchaseReturn->pr_no }}
                                        <br>
                                        <small class="text-muted">
                                            {{ \Carbon\Carbon::parse($purchaseReturn->created_at)->format('d M Y') }}
                                        </small>
                                    </div>
                                </td>
                                <td class="align-middle text-center" style="background-color: #e8f5e8;">N/A</td>
                                <td class="align-middle" style="background-color: #e3f2fd;">
                                    <strong>{{ $purchaseReturn->supplier->name ?? 'N/A' }}</strong>
                                </td>
                                <td colspan="6" class="text-center align-middle">
                                    <em class="text-muted">No items found</em>
                                </td>
                                <td class="text-center align-middle">
                                    @php
                                        $status = $purchaseReturn->am_approval_status;
                                        $badge = match(strtolower($status)) {
                                            'approved' => 'badge-success',
                                            'rejected' => 'badge-danger',
                                            'pending'  => 'badge-warning',
                                            default    => 'badge-secondary',
                                        };
                                    @endphp
                                    <span class="badge {{ $badge }} px-2 py-1">
                                        {{ ucfirst($status) }}
                                    </span>
                                </td>
                                <td class="text-center align-middle">
                                    <small class="text-muted">
                                        {{ \Carbon\Carbon::parse($purchaseReturn->created_at)->format('d M Y H:i') }}
                                    </small>
                                </td>
                                <td class="text-center align-middle">
                                    <div class="btn-group" role="group">
                                        <a
                                           class="btn btn-sm btn-info" onclick="openModal(this,'{{ route('store.purchase-return.view', ['id' => $purchaseReturn->id]) }}','View Purchase Return', false, '100%')" title="View" style="margin-right: 5px;">
                                            <i class="ft-eye"></i>
                                        </a>
                                        @if(auth()->user()->id == $purchaseReturn->created_by)
                                            @if($purchaseReturn->am_approval_status === 'pending' || $purchaseReturn->am_approval_status === 'rejected')
                                            <button
                                                onclick="openModal(this,'{{ route('store.purchase-return.edit', $purchaseReturn->id) }}','Edit Purchase Return', false, '100%')"
                                                class="btn btn-sm btn-warning" title="Edit" style="margin-right: 5px;">
                                                <i class="ft-edit"></i>
                                            </button>

                                        <button onclick="deletemodal('{{ route('store.purchase-return.destroy', $purchaseReturn->id) }}', '{{ route('store.get.purchase-return') }}')" type="button"
                                                class="btn btn-sm btn-danger" title="Delete">
                                            <i class="ft-trash-2"></i>
                                        </button>
                                            @endif
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="10" class="text-center py-5">
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
                                    <p class="text-muted mt-3">No Purchase Return found</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

<div class="row d-flex" id="paginationLinks">
    <div class="col-md-12 text-right">
        {{ $PurchaseReturns->links() }}
    </div>
</div>

