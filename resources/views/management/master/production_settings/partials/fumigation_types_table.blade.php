<table class="table table-striped table-hover m-0">
    <thead class="thead-light">
        <tr>
            <th style="width: 80px;">#</th>
            <th>Fumigation Type Name</th>
            <th style="width: 150px;">Status</th>
            <th style="width: 150px;" class="text-center">Action</th>
        </tr>
    </thead>
    <tbody>
        @if (!empty($fumigationTypes) && count($fumigationTypes) > 0)
            @foreach ($fumigationTypes as $index => $type)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>
                        <span class="font-weight-bold text-dark text-capitalize">{{ $type }}</span>
                    </td>
                    <td>
                        <span class="badge badge-success">Active</span>
                    </td>
                    <td class="text-center">
                        <a href="javascript:void(0);"
                            onclick="openModal(this, '{{ route('production-settings.fumigation-types.edit', $index) }}', 'Edit Fumigation Type')"
                            class="info p-1 mr-2"
                            title="Edit">
                            <i class="ft-edit font-medium-3"></i>
                        </a>
                        <a href="javascript:void(0);"
                            onclick="deletemodal('{{ route('production-settings.fumigation-types.destroy', $index) }}', '{{ route('production-settings.fumigation-types.list') }}')"
                            class="danger p-1"
                            title="Delete">
                            <i class="ft-trash-2 font-medium-3"></i>
                        </a>
                    </td>
                </tr>
            @endforeach
        @else
            <tr>
                <td colspan="4" class="text-center py-4 text-muted">
                    No fumigation types configured yet.
                </td>
            </tr>
        @endif
    </tbody>
</table>
