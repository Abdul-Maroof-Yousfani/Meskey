<table class="table m-0">
    <thead>
        <tr>
            <th style="width: 5%;">#</th>
            <th style="width: 25%;">Permission Name</th>
            <th style="width: 20%;">Parent Module</th>
            <th style="width: 20%;">Description</th>
            <th style="width: 8%;">Guard</th>
            <th style="width: 7%;">Roles</th>
            <th style="width: 10%;">Created</th>
            <th style="width: 5%;">Action</th>
        </tr>
    </thead>
    <tbody>
        @if (count($permissions) != 0)
            @foreach ($permissions as $key => $row)
                <tr>
                    <td>
                        <span class="text-muted font-small-2">#{{ $row->id }}</span>
                    </td>
                    <td>
                        <p class="m-0 font-weight-bold">
                            <span class="badge " style="font-size: 0.9rem;">
                                {{ $row->name }}
                            </span>
                        </p>
                        @if ($row->children_count > 0)
                            <small class="text-muted">
                                <i class="ft-corner-down-right"></i> {{ $row->children_count }} sub-permission(s)
                            </small>
                        @endif
                    </td>
                    <td>
                        @if ($row->parent)
                            <span class="badge bg-light-info text-white font-small-2">
                                <i class="ft-folder mr-1"></i>{{ $row->parent->name }}
                            </span>
                        @else
                            <span class="badge bg-light-secondary text-secondary font-small-2">
                                <i class="ft-star mr-1"></i>Root Module
                            </span>
                        @endif
                    </td>
                    <td>
                        <p class="m-0 text-muted font-small-2">
                            {{ $row->description ?: '--' }}
                        </p>
                    </td>
                    <td>
                        <span class="badge bg-light-warning text-warning font-small-2">
                            {{ $row->guard_name }}
                        </span>
                    </td>
                    <td>
                        <span class="badge font-small-2" title="{{ $row->roles_count }} Assigned Role(s)">
                            <i class="ft-shield mr-1"></i>{{ $row->roles_count }}
                        </span>
                    </td>
                    <td>
                        {!! dateFormatHtml($row->created_at) !!}
                    </td>
                    <td>
                        <div class="d-flex align-items-center">
                            <a onclick="openModal(this,'{{ route('permission.edit', $row->id) }}','Edit Permission')"
                                class="info p-1 text-center mr-1 position-relative" title="Edit Permission" style="cursor: pointer;">
                                <i class="ft-edit font-medium-3"></i>
                            </a>
                            <a onclick="deletemodal('{{ route('permission.destroy', $row->id) }}','{{ route('get.permissions') }}')"
                                class="danger p-1 text-center position-relative" title="Delete Permission" style="cursor: pointer;">
                                <i class="ft-trash font-medium-3"></i>
                            </a>
                        </div>
                    </td>
                </tr>
            @endforeach
        @else
            <tr class="ant-table-placeholder">
                <td colspan="8" class="ant-table-cell text-center">
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
                        <p class="ant-empty-description text-muted mt-2">No permissions found</p>
                    </div>
                </td>
            </tr>
        @endif
    </tbody>
</table>

<div class="row d-flex mt-2" id="paginationLinks">
    <div class="col-md-6 text-left">
        <span class="text-muted font-small-2">
            Showing {{ $permissions->firstItem() ?? 0 }} to {{ $permissions->lastItem() ?? 0 }} of {{ $permissions->total() }} permissions
        </span>
    </div>
    <div class="col-md-6 text-right">
        {{ $permissions->links() }}
    </div>
</div>
