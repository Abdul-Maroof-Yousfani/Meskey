<table class="table m-0">
    <thead>
        <tr>
            <th class="col-sm-2">Account No.</th>
            <th class="col-sm-3">Account Name</th>
            <th class="col-sm-2">Type</th>
            <th class="col-sm-2">Parent Account</th>
            <th class="col-sm-1">Status</th>
            <th class="col-sm-2">Created</th>
            <th class="col-sm-1">Action</th>
        </tr>
    </thead>
    <tbody>
        @if (count($accounts) != 0)
            @foreach ($accounts as $account)
                <tr>
                    <td>
                        <p class="m-0">
                            #{{ $account->unique_no }} <br>
                        </p>
                    </td>
                    <td>
                        <p class="m-0">
                            {{ $account->name }} <br>
                            <small class="text-muted">{{ $account->description }}</small>
                        </p>
                    </td>
                    <td>
                        <span class="badge badge-{{ $account->account_type == 'debit' ? 'primary' : 'success' }}">
                            {{ ucfirst($account->account_type) }}
                        </span>
                    </td>
                    <td>
                        <p class="m-0">
                            {{ optional($account->parent)->name ?? 'N/A' }} <br>
                            <small class="text-muted">{{ optional($account->parent)->unique_no }}</small>
                        </p>
                    </td>
                    <td>
                        <label
                            class="badge text-uppercase m-0 {{ $account->status == 'active' ? 'badge-success' : 'badge-danger' }}">
                            {{ $account->status }}
                        </label>
                    </td>
                    <td>
                        <p class="m-0">
                            {{ \Carbon\Carbon::parse($account->created_at)->format('Y-m-d') }} <br>
                            {{ \Carbon\Carbon::parse($account->created_at)->format('H:i A') }}
                        </p>
                    </td>
                    <td>
                        {{-- @can('account-edit') --}}
                        <a onclick="openModal(this,'{{ route('account.edit', $account->id) }}','Edit Account', true)"
                            class="info p-1 text-center mr-2 position-relative">
                            <i class="ft-edit font-medium-3"></i>
                        </a>
                        {{-- @endcan
                        @can('account-delete')
                            <a onclick="deletemodal('{{ route('account.destroy', $account->id) }}','{{ route('get.account') }}')"
                                class="danger p-1 text-center mr-2 position-relative">
                                <i class="ft-trash-2 font-medium-3"></i>
                            </a>
                        @endcan --}}
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
                        <p class="ant-empty-description">No accounts found</p>
                    </div>
                </td>
            </tr>
        @endif
    </tbody>
</table>

<div class="row d-flex" id="paginationLinks">
    <div class="col-md-12 text-right">
        {{ $accounts->links() }}
    </div>
</div>
