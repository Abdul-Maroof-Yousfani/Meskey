<table class="table m-0">
    <thead>
        <tr>
            <th class="col-sm-3">Variable Title</th>
            <th class="col-sm-3">Linked COA Account</th>
            <th class="col-sm-3">Description</th>
            <th class="col-sm-1">Status</th>
            <th class="col-sm-2 text-right">Action</th>
        </tr>
    </thead>
    <tbody>
        @if (count($variables) != 0)
            @foreach ($variables as $key => $row)
                <tr>
                    <td>
                        <strong class="text-primary">{{ $row->title }}</strong>
                    </td>
                    <td>
                        @if($row->account)
                            <div><strong class="text-dark">{{ $row->account->name }}</strong></div>
                            <small class="badge badge-info mr-1">{{ $row->account->unique_no }}</small>
                            <small class="badge badge-light border font-weight-bold">Path: {{ $row->account->hierarchy_path }}</small>
                        @else
                            <span class="badge badge-danger">No COA Linked</span>
                        @endif
                    </td>
                    <td>
                        <p class="m-0 text-muted">
                            {{ $row->description ?: '-' }}
                        </p>
                    </td>
                    <td>
                        <span class="badge badge-{{ $row->status == 1 ? 'success' : 'warning' }}">{{ $row->status == 1 ? 'Active' : 'Pending' }}</span>
                    </td>

                    <td class="text-right">
                        <a onclick="openModal(this,'{{ route('variable.show', $row->id) }}','View Variable')"
                            class="primary p-1 text-center position-relative cursor-pointer" title="View">
                            <i class="ft-eye font-medium-3"></i>
                        </a>
                        <a onclick="openModal(this,'{{ route('variable.edit', $row->id) }}','Edit Variable')"
                            class="info p-1 text-center position-relative cursor-pointer" title="Edit">
                            <i class="ft-edit font-medium-3"></i>
                        </a>
                    </td>
                </tr>
            @endforeach
        @else
            <tr class="ant-table-placeholder">
                <td colspan="5" class="ant-table-cell text-center py-4">
                    <p class="text-muted m-0">No variables found.</p>
                </td>
            </tr>
        @endif
    </tbody>
</table>

<div class="row w-100 mx-auto justify-content-between align-items-center mt-2">
    <div class="col-md-6">
        Showing {{ $variables->firstItem() ?? 0 }} to {{ $variables->lastItem() ?? 0 }} of {{ $variables->total() }} entries
    </div>
    <div class="col-md-6 text-right">
        {{ $variables->links() }}
    </div>
</div>
