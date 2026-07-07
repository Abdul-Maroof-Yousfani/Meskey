<table class="table m-0">
    <thead>
        <tr>
            <th class="col-sm-1">S no.</th>
            <th class="col-sm-2">Export Order</th>
            <th class="col-sm-2">Buyer</th>
            <th class="col-sm-2">Product</th>
            <th class="col-sm-3">Remarks</th>
            <th class="col-sm-1">Date</th>
            <th class="col-sm-1">Action</th>
        </tr>
    </thead>
    <tbody>
        @if (count($addendums) != 0)
            @foreach ($addendums as $key => $addendum)
                <tr>
                    <td>{{ $addendums->firstItem() + $key }}</td>
                    <td>
                        <div>
                            <strong class="d-block">{{ $addendum->exportOrder?->voucher_no }}</strong>
                        </div>
                    </td>
                    <td>{{ $addendum->exportOrder?->buyer?->name }}</td>
                    <td>{{ $addendum->exportOrder?->product?->name }}</td>
                    <td>{{ Str::limit($addendum->remarks, 50) }}</td>
                    <td>{{ $addendum->created_at->format('d/m/Y') }}</td>
                    <td>
                        <a class="info p-1 text-center position-relative "
                            onclick="openModal(this,'{{ route('export-order-addendum.show', $addendum->id) }}','Show Addendum',false,'50%')">
                            <i class="ft-eye font-medium-3"></i></a>
                    </td>
                </tr>
            @endforeach
        @else
            <tr class="ant-table-placeholder">
                <td colspan="7" class="ant-table-cell text-center">
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
                        <p>No Data</p>
                    </div>
                </td>
            </tr>
        @endif
    </tbody>
</table>

<div class="row pt-2 align-items-center">
    <div class="col-md-6 mb-1">
        Showing {{ $addendums->firstItem() ?? 0 }} to {{ $addendums->lastItem() ?? 0 }} of
        {{ $addendums->total() }} entries
    </div>
    <div class="col-md-6 mb-1 text-right pagination-div">
        <ul class="pagination pagination-sm m-0 float-right">
            {!! $addendums->links('pagination::bootstrap-4') !!}
        </ul>
    </div>
</div>
