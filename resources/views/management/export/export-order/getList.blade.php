<table class="table m-0">
    <thead>
        <tr>
            <th width="5%">S no.</th>
            <th width="15%">Export Order</th>
            <th width="15%">Voucher Date</th>
            <th width="15%">Marking/Labeling</th>
            <th width="15%">Product</th>
            <th width="10%">Broker</th>
            <th width="10%">Currency</th>
            <th width="15%">Action</th>
        </tr>
    </thead>
    <tbody>
        @if (count($export_orders) != 0)
            @foreach ($export_orders as $key => $export)
                <tr>
                    <td>{{ $key + 1 }}</td>
                    <td>
                        <div>
                            <strong class="d-block">{{ $export->voucher_no }}</strong>
                            @if ($export->contract_no)
                                <small class="text-muted">Contract: {{ $export->contract_no }}</small>
                            @endif
                        </div>
                    </td>
                    <td>{{ \Carbon\Carbon::parse($export->voucher_date)->format('d/m/Y') }}</td>
                    <td>{{ $export->marking_labeling ?? '' }}</td>
                    <td>{{ Str::limit($export->product->name ?? 'N/A', 30) }}</td>
                    <td>{{ Str::limit($export->broker->name ?? 'N/A', 30) }}</td>
                    <td>
                        <div>
                            <strong class="d-block">{{ $export->currency->currency_code ?? '' }}</strong>
                            @if ($export->currency->rate)
                                <small class="text-muted">Rate: {{ $export->currency->rate }}</small>
                            @endif
                        </div>
                    </td>
                    <td>
                        @canAccess('bank-edit')
                        <a class="info p-1 text-center position-relative "
                            onclick="openModal(this,'{{ route('export-order.edit', $export->id) }}','Edit Export Order',false,'90%')">
                            <i class="ft-edit font-medium-3"></i></a>
                        @endcanAccess
                        @canAccess('bank-show')
                        <a class="info p-1 text-center position-relative "
                            onclick="openModal(this,'{{ route('export-order.show', $export->id) }}','Show Export Order',false,'90%')">
                            <i class="ft-eye font-medium-3"></i></a>
                        @endcanAccess
                        @canAccess('bank-delete')
                        <a onclick="deletemodal('{{ route('export-order.destroy', $export->id) }}','{{ route('get.export-order') }}')"
                            class="danger p-1 text-center mr-2 position-relative ">

                            <i class="ft-x font-medium-3"></i>
                        </a>
                        @endcanAccess
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
        <div id="">
            {{ $export_orders->links() }}
        </div>
    </div>
</div>
