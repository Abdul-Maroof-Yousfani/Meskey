<table class="table m-0">
    <thead>
        <tr>
            <th class="col-sm-1">S no.</th>
            <th class="col-sm-3">Port Name</th>
            <th class="col-sm-2">Country</th>
            <th class="col-sm-2">City</th>
            <th class="col-sm-2">Type</th>
            <th class="col-sm-2">Action</th>
        </tr>
    </thead>

    <tbody>
        @if ($ports->count())
            @foreach ($ports as $key => $port)
                <tr>
                    <td>{{ $key + 1 + ($ports->currentPage() - 1) * $ports->perPage() }}</td>

                    <td>{{ $port->name }}</td>

                    <td>
                        {{ optional($port->country)->name ?? '--' }}
                    </td>

                    <td>
                        {{ optional($port->city)->name ?? '--' }}
                    </td>

                    <td>{{ $port->type }}</td>

                    <td>
                        @canAccess('port-edit')
                        <a class="info p-1 text-center position-relative"
                            onclick="openModal(this,'{{ route('ports.edit', $port->id) }}','Edit Port')">
                            <i class="ft-edit font-medium-3"></i>
                        </a>
                        @endcanAccess

                        @canAccess('port-show')
                        <a class="info p-1 text-center position-relative"
                            onclick="openModal(this,'{{ route('ports.show', $port->id) }}','Show Port')">
                            <i class="ft-eye font-medium-3"></i>
                        </a>
                        @endcanAccess

                        @canAccess('port-delete')
                        <a onclick="deletemodal('{{ route('ports.destroy', $port->id) }}','{{ route('get.port') }}')"
                            class="danger p-1 text-center mr-2 position-relative">
                            <i class="ft-x font-medium-3"></i>
                        </a>
                        @endcanAccess
                    </td>
                </tr>
            @endforeach
        @else
            <tr class="ant-table-placeholder">
                <td colspan="6" class="ant-table-cell text-center">
                    <div class="my-5">
                        <p class="ant-empty-description">No data</p>
                    </div>
                </td>
            </tr>
        @endif
    </tbody>
</table>

<div class="row d-flex" id="paginationLinks">
    <div class="col-md-12 text-right">
        {{ $ports->links() }}
    </div>
</div>
