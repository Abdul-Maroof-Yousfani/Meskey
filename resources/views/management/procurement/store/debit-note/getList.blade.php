<table class="table table-striped m-0">
    <thead>
        <tr>
            <th>GRN No.</th>
            <th>Bill No.</th>
            <th>Item</th>
            <th>GRN Qty</th>
            <th>Debit Note Qty</th>
            <th>Rate</th>
            <th>Amount</th>
            <th>Status</th>
            <th>Created</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        @if($debit_notes->count() > 0)
            @foreach($debit_notes as $debitNote)
                @php
                    $items = $debitNote->debit_note_data;
                    $itemCount = $items->count();
                    $rowspan = $itemCount > 0 ? $itemCount : 1;
                    $isFirstRow = true;
                @endphp

                @if($itemCount > 0)
                    @foreach($items as $item)
                        <tr>
                            {{-- Debit Note columns with rowspan (only on first row) --}}
                            @if($isFirstRow)
                                <td rowspan="{{ $rowspan }}" style="background-color: #e3f2fd; vertical-align: middle;">
                                    <p class="m-0 font-weight-bold">
                                        {{ $debitNote->grn->purchase_order_receiving_no ?? 'N/A' }}
                                    </p>
                                </td>
                                <td rowspan="{{ $rowspan }}" style="background-color: #e8f5e8; vertical-align: middle;">
                                    <p class="m-0 font-weight-bold">
                                        {{ $debitNote->bill->bill_no ?? 'N/A' }}
                                    </p>
                                </td>
                            @endif

                            {{-- Item columns (repeated for each item) --}}
                            <td>
                                <span class="badge badge-primary">{{ $item->item->name ?? 'N/A' }}</span>
                            </td>
                            <td class="text-right">
                                {{ number_format($item->grn_qty ?? 0, 2) }}
                            </td>
                            <td class="text-right">
                                {{ number_format($item->debit_note_quantity ?? 0, 2) }}
                            </td>
                            <td class="text-right">
                                {{ number_format($item->rate ?? 0, 2) }}
                            </td>
                            <td class="text-right">
                                {{ number_format($item->amount ?? 0, 2) }}
                            </td>

                            {{-- Debit Note columns with rowspan (only on first row) --}}
                            @if($isFirstRow)
                                <td rowspan="{{ $rowspan }}" style="vertical-align: middle;">
                                    @php
                                        $status = $debitNote->am_approval_status ?? 'pending';
                                        $badgeClass = match (strtolower($status)) {
                                            'approved' => 'badge-success',
                                            'rejected' => 'badge-danger',
                                            'pending' => 'badge-warning',
                                            'reverted' => 'badge-info',
                                            default => 'badge-secondary',
                                        };
                                    @endphp
                                    <span class="badge {{ $badgeClass }}">
                                        {{ ucwords($status) }}
                                    </span>
                                </td>
                                <td rowspan="{{ $rowspan }}" style="vertical-align: middle;">
                                    {{ $debitNote->created_at->format('d-m-Y H:i') }}
                                </td>
                                <td rowspan="{{ $rowspan }}" style="vertical-align: middle;">
                                    <div class="d-flex gap-1">
                                        <a onclick="openModal(this,'{{ route('store.debit-note.show', $debitNote->id) }}','View Debit Note', true)"
                                            class="info p-1 text-center mr-1 position-relative" title="View">
                                            <i class="ft-eye font-medium-3"></i>
                                        </a>

                                        @if($debitNote->am_approval_status != 'approved')
                                            <a onclick="openModal(this,'{{ route('store.debit-note.edit', $debitNote->id) }}','Edit Debit Note', false)"
                                                class="info p-1 text-center mr-1 position-relative" title="Edit" style="color: #007bff;">
                                                <i class="ft-edit font-medium-3"></i>
                                            </a>

                                            <a onclick="deletemodal('{{ route('store.debit-note.destroy', $debitNote->id) }}', '{{ route('store.get.debit-notes') }}')"
                                                class="info p-1 text-center mr-1 position-relative " style="color: red;" title="Delete">
                                                <i class="ft-x font-medium-3"></i>
                                            </a>
                                        @endif
                                    </div>
                                </td>
                                @php $isFirstRow = false; @endphp
                            @endif
                        </tr>
                    @endforeach
                @else
                    {{-- No items - show single row with N/A for item columns --}}
                    <tr>
                        <td style="background-color: #e3f2fd; vertical-align: middle;">
                            <p class="m-0 font-weight-bold">
                                {{ $debitNote->grn->purchase_order_receiving_no ?? 'N/A' }}
                            </p>
                        </td>
                        <td style="background-color: #e8f5e8; vertical-align: middle;">
                            <p class="m-0 font-weight-bold">
                                {{ $debitNote->bill->bill_no ?? 'N/A' }}
                            </p>
                        </td>
                        <td colspan="5" class="text-center text-muted">
                            No items added
                        </td>
                        <td style="vertical-align: middle;">
                            @php
                                $statuses = $debitNote->debit_note_data->pluck('am_approval_status')->unique();
                                $status = $debitNote->am_approval_status;
                                $badgeClass = match (strtolower($status)) {
                                    'approved' => 'badge-success',
                                    'rejected' => 'badge-danger',
                                    'pending' => 'badge-warning',
                                    'reverted' => 'badge-info',
                                    default => 'badge-secondary',
                                };
                            @endphp
                            <span class="badge {{ $badgeClass }}">
                                {{ $status == 'mixed' ? 'Mixed' : ucwords($status) }}
                            </span>
                        </td>
                        <td>
                            {{ $debitNote->created_at->format('d-m-Y H:i') }}
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <a onclick="openModal(this,'{{ route('store.debit-note.show', $debitNote->id) }}','View Debit Note', true)"
                                    class="info p-1 text-center mr-1 position-relative" title="View">
                                    <i class="ft-eye font-medium-3"></i>
                                </a>

                                @if($debitNote->am_approval_status != 'approved')
                                    <a onclick="openModal(this,'{{ route('store.debit-note.edit', $debitNote->id) }}','Edit Debit Note', true)"
                                        class="info p-1 text-center mr-1 position-relative" title="Edit" style="color: #007bff;">
                                        <i class="ft-edit font-medium-3"></i>
                                    </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endif
            @endforeach
        @else
            <tr class="ant-table-placeholder">
                <td colspan="10" class="ant-table-cell text-center">
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
                        <p class="ant-empty-description">No Debit Notes found</p>
                    </div>
                </td>
            </tr>
        @endif
    </tbody>
</table>

<!-- Pagination -->
<div class="row d-flex" id="paginationLinks">
    <div class="col-md-12 text-right">
        {{ $debit_notes->links() }}
    </div>
</div>