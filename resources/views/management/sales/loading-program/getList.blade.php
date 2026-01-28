<table class="table table-striped m-0">
    <thead>
        <tr>
            <th>SO No.</th>
            <th>DO No.</th>
            <th>Customer</th>
            <th>Commodity</th>
            <th>Ticket No.</th>
            <th>Truck No.</th>
            <th>Container No.</th>
            <th>Factory</th>
            <th>Gala</th>
            <th>Suggested Qty</th>
            <th>Created</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        @if($LoadingPrograms->count() > 0)
            @foreach($LoadingPrograms as $loadingProgram)
                @php
                    $tickets = $loadingProgram->loadingProgramItems;
                    $ticketCount = $tickets->count();
                    $rowspan = $ticketCount > 0 ? $ticketCount : 1;
                    $isFirstRow = true;
                @endphp

                @if($ticketCount > 0)
                    @foreach($tickets as $ticket)
                        <tr>
                            {{-- Parent columns with rowspan (only on first row) --}}
                            @if($isFirstRow)
                                <td rowspan="{{ $rowspan }}" style="background-color: #e3f2fd; vertical-align: middle;">
                                    <p class="m-0 font-weight-bold">
                                        {{ $loadingProgram->saleOrder->reference_no ?? 'N/A' }}
                                    </p>
                                </td>
                                <td rowspan="{{ $rowspan }}" style="background-color: #e8f5e8; vertical-align: middle;">
                                    <p class="m-0 font-weight-bold">
                                        {{ $loadingProgram->deliveryOrder->reference_no ?? 'N/A' }}
                                    </p>
                                </td>
                                <td rowspan="{{ $rowspan }}" style="vertical-align: middle;">
                                    {{ $loadingProgram->saleOrder->customer->name ?? 'N/A' }}
                                </td>
                                <td rowspan="{{ $rowspan }}" style="vertical-align: middle;">
                                    {{ $loadingProgram->saleOrder?->sales_order_data->first()->item->name ?? 'N/A' }}
                                </td>
                            @endif

                            {{-- Ticket columns (repeated for each ticket) --}}
                            <td style="background-color: #fff3e0;">
                                <span class="badge badge-primary">{{ $ticket->transaction_number ?? 'N/A' }}</span>
                            </td>
                            <td>
                                {{ $ticket->truck_number ?? 'N/A' }}
                            </td>
                            <td>
                                {{ $ticket->container_number ?? '-' }}
                            </td>
                            <td>
                                {{ $ticket->arrivalLocation->name ?? 'N/A' }}
                            </td>
                            <td>
                                {{ $ticket->subArrivalLocation->name ?? 'N/A' }}
                            </td>
                            <td class="text-right">
                                {{ number_format($ticket->qty ?? 0, 2) }}
                            </td>

                            {{-- Parent columns with rowspan (only on first row) --}}
                            @if($isFirstRow)
                                <td rowspan="{{ $rowspan }}" style="vertical-align: middle;">
                                    {{ $loadingProgram->created_at->format('d-m-Y H:i') }}
                                </td>
                                <td rowspan="{{ $rowspan }}" style="vertical-align: middle;">
                                    <div class="d-flex gap-1">
                                        @if($loadingProgram?->loadingProgramItems()->whereDoesntHave("firstWeighbridge")->count() > 0)
                                        <a onclick="openModal(this,'{{ route('sales.loading-program.edit', $loadingProgram->id) }}','Edit Loading Program', false)"
                                                class="warning p-1 text-center mr-1 position-relative" title="Edit">
                                                <i class="ft-edit font-medium-3"></i>
                                            </a>
                                        @endif
                                        <a onclick="openModal(this,'{{ route('sales.loading-program.show', $loadingProgram->id) }}','View Loading Program', true)"
                                            class="info p-1 text-center mr-1 position-relative" title="View">
                                            <i class="ft-eye font-medium-3"></i>
                                        </a>
                                    </div>
                                </td>
                                @php $isFirstRow = false; @endphp
                            @endif
                        </tr>
                    @endforeach
                @else
                    {{-- No tickets - show single row with N/A for ticket columns --}}
                    <tr>
                        <td style="background-color: #e3f2fd; vertical-align: middle;">
                            <p class="m-0 font-weight-bold">
                                {{ $loadingProgram->saleOrder->reference_no ?? 'N/A' }}
                            </p>
                        </td>
                        <td style="background-color: #e8f5e8; vertical-align: middle;">
                            <p class="m-0 font-weight-bold">
                                {{ $loadingProgram->deliveryOrder->reference_no ?? 'N/A' }}
                            </p>
                        </td>
                        <td>
                            {{ $loadingProgram->saleOrder->customer->name ?? 'N/A' }}
                        </td>
                        <td>
                            {{ $loadingProgram->saleOrder->sales_order_data->first()->item->name ?? 'N/A' }}
                        </td>
                        <td colspan="6" class="text-center text-muted">
                            No tickets added
                        </td>
                        <td>
                            {{ $loadingProgram->created_at->format('d-m-Y H:i') }}
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                    <a onclick="openModal(this,'{{ route('sales.loading-program.edit', $loadingProgram->id) }}','Edit Loading Program', false)"
                                        class="warning p-1 text-center mr-1 position-relative" title="Edit">
                                        <i class="ft-edit font-medium-3"></i>
                                    </a>
                                <a onclick="openModal(this,'{{ route('sales.loading-program.show', $loadingProgram->id) }}','View Loading Program', true)"
                                    class="info p-1 text-center mr-1 position-relative" title="View">
                                    <i class="ft-eye font-medium-3"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                @endif
            @endforeach
        @else
            <tr class="ant-table-placeholder">
                <td colspan="12" class="ant-table-cell text-center">
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
                        <p class="ant-empty-description">No Loading Programs found</p>
                    </div>
                </td>
            </tr>
        @endif
    </tbody>
</table>

<!-- Pagination -->
<div class="row d-flex" id="paginationLinks">
    <div class="col-md-12 text-right">
        {{ $LoadingPrograms->links() }}
    </div>
</div>
