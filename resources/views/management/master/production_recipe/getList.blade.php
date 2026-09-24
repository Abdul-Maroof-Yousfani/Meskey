<table class="table m-0">
    <thead>
        <tr>
            <th class="col-sm-2">Recipe Name</th>
            <th class="col-sm-2">Commodity</th>
            <th class="col-sm-1">Crop Year</th>
            <th class="col-sm-3">Parameters (Key / Value)</th>
            <th class="col-sm-1">Status</th>
            <th class="col-sm-2">Created</th>
            <th class="col-sm-1">Action</th>
        </tr>
    </thead>
    <tbody>
        @if (count($production_recipes) != 0)
            @foreach ($production_recipes as $row)
                <tr>
                    <td>
                        <p class="m-0 font-weight-bold">
                            {{ $row->name }}
                        </p>
                        @if ($row->description)
                            <small class="text-muted">{{ \Illuminate\Support\Str::limit($row->description, 50) }}</small>
                        @endif
                    </td>
                    <td>
                        <p class="m-0">
                            {{ $row->commodity->name ?? '--' }}
                        </p>
                    </td>
                    <td>
                        <p class="m-0">
                            {{ $row->cropYear->name ?? '--' }}
                        </p>
                    </td>
                    <td>
                        @if ($row->items->isNotEmpty())
                            <div class="d-flex flex-wrap">
                                @foreach ($row->items as $item)
                                    @if (!empty($item->key))
                                        <span class="mr-1 mb-1" title="slug: {{ $item->slug }}">
                                            <span class="text-primary font-weight-bold">{{ $item->key }}:</span> {{ $item->value }}
                                            @if (!empty($item->type) && $item->type !== 'text')
                                                <small class="p-0 font-small-1">({{ $item->type }})</small>
                                            @endif
                                        </span>
                                    @endif
                                @endforeach
                            </div>
                        @else
                            <span class="text-muted font-small-2">--</span>
                        @endif
                    </td>
                    <td>
                        <label class="badge bg-light-{{ $row->status == 'inactive' ? 'danger' : 'success' }}">
                            {{ ucfirst($row->status) }}
                        </label>
                    </td>
                    <td>
                        {!! dateFormatHtml($row->created_at) !!}
                    </td>
                    <td>
                        <a onclick="openModal(this,'{{ route('production-recipe.edit', $row->id) }}','Edit Production Recipe')"
                            class="info p-1 text-center mr-2 position-relative" title="Edit Production Recipe">
                            <i class="ft-edit font-medium-3"></i>
                        </a>
                        <a onclick="deletemodal('{{ route('production-recipe.destroy', $row->id) }}','{{ route('get.production-recipe') }}')"
                            class="danger p-1 text-center mr-2 position-relative" title="Delete Production Recipe">
                            <i class="ft-x font-medium-3"></i>
                        </a>
                    </td>
                </tr>
            @endforeach
        @else
            <tr class="ant-table-placeholder">
                <td colspan="7" class="ant-table-cell text-center">
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
                        <p class="ant-empty-description text-muted mt-2">No production recipes found</p>
                    </div>
                </td>
            </tr>
        @endif
    </tbody>
</table>

<div class="row d-flex" id="paginationLinks">
    <div class="col-md-12 text-right">
        {{ $production_recipes->links() }}
    </div>
</div>
