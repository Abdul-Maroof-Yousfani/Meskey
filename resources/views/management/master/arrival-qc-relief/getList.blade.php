<table class="table m-0">
    <thead>
        <tr>
            <th class="col-sm-2">Product</th>
            <th class="col-sm-2">Location</th>
            <th class="col-sm-2">Valid Period</th>
            <th class="col-sm-4">QC Relief Parameters</th>
            <th class="col-sm-1">Status</th>
            <th class="col-sm-1">Action</th>
        </tr>
    </thead>
    <tbody>
        @if (count($groups) > 0)
            @foreach ($groups as $group)
                <tr>
                    <td>
                        <p class="m-0 font-weight-bold">
                            {{ $group->product->name ?? 'N/A' }}
                        </p>
                    </td>
                    <td>
                        <p class="m-0">
                            @if ($group->location)
                                {{ $group->location->name }}
                            @else
                                <span class="badge badge-light-info">All Locations</span>
                            @endif
                        </p>
                    </td>
                    <td>
                        <p class="m-0">
                            <span class="text-success">{{ $group->start_date ? $group->start_date->format('d-M-Y') : 'Any' }}</span>
                            <span class="text-muted mx-1">to</span>
                            <span class="text-danger">{{ $group->end_date ? $group->end_date->format('d-M-Y') : 'Any' }}</span>
                        </p>
                    </td>
                    <td>
                        <div class="d-flex flex-wrap">
                            @foreach ($group->rules->groupBy('parameter_name') as $paramName => $rules)
                                <div class="mr-2 mb-1 p-1 px-2 rounded border bg-light-neutral d-inline-block">
                                    <span class="badge badge-primary mr-1">{{ $paramName }}</span>
                                    @foreach ($rules as $rule)
                                        @php
                                            $typeLabel = ($rule->relief_type === 'minus_amount') ? 'Minus' : 'Fixed';
                                            $typeClass = ($rule->relief_type === 'minus_amount') ? 'text-warning font-weight-bold' : 'text-info font-weight-bold';
                                            $val = $rule->relief_value ?: $rule->fixed_value;
                                        @endphp
                                        <span class="small text-muted font-weight-bold mr-1">
                                            [{{ number_format($rule->from_range, 2) }} - {{ number_format($rule->to_range, 2) }} &rarr; <span class="{{ $typeClass }}">{{ $typeLabel }}: {{ number_format($val, 2) }}</span>]
                                        </span>
                                    @endforeach
                                </div>
                            @endforeach
                        </div>
                    </td>
                    <td>
                        <label class="badge bg-light-{{ $group->is_active ? 'success' : 'danger' }}">
                            {{ $group->is_active ? 'active' : 'inactive' }}
                        </label>
                    </td>
                    <td>
                        <a onclick="openModal(this,'{{ route('arrival-qc-relief.edit', $group->id) }}','Edit Arrival QC Relief', false, '70%')"
                            class="info p-1 text-center mr-2 position-relative cursor-pointer" title="Edit">
                            <i class="ft-edit font-medium-3"></i>
                        </a>
                        <a onclick="deletemodal('{{ route('arrival-qc-relief.destroy', $group->id) }}','{{ route('get.arrival-qc-relief') }}')"
                            class="danger p-1 text-center mr-2 position-relative cursor-pointer" title="Delete">
                            <i class="ft-x font-medium-3"></i>
                        </a>
                    </td>
                </tr>
            @endforeach
        @else
            <tr class="ant-table-placeholder">
                <td colspan="6" class="ant-table-cell text-center">
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
        {{ $paginator->links() }}
    </div>
</div>
