<table class="table m-0">
    <thead>
        <tr>
            <th class="col-sm-1">S no.</th>
            <th class="col-sm-6">City Name</th>
            <th class="col-sm-3">Country Code</th>
            <th class="col-sm-2">Action</th>
        </tr>
    </thead>
    <tbody>
        @if (count($cities) != 0)
            @foreach ($cities as $key => $city)
                <tr>
                    <td>{{ $key + 1 + ($cities->currentPage() - 1) * $cities->perPage() }}</td>
                    <td>{{ $city->name }}</td>
                    <td>{{ $city->country->name }} <small>({{ $city->country_code }})</small></td>
                    <td>
                        @canAccess('city-edit')
                        <a class="info p-1 text-center position-relative"
                            onclick="openModal(this,'{{ route('cities.edit', $city->id) }}','Edit City')">
                            <i class="ft-edit font-medium-3"></i>
                        </a>
                        @endcanAccess

                        @canAccess('city-show')
                        <a class="info p-1 text-center position-relative"
                            onclick="openModal(this,'{{ route('cities.show', $city->id) }}','Show City')">
                            <i class="ft-eye font-medium-3"></i>
                        </a>
                        @endcanAccess

                        @canAccess('city-delete')
                        <a onclick="deletemodal('{{ route('cities.destroy', $city->id) }}','{{ route('get.city') }}')"
                            class="danger p-1 text-center mr-2 position-relative">
                            <i class="ft-x font-medium-3"></i>
                        </a>
                        @endcanAccess
                    </td>
                </tr>
            @endforeach
        @else
            <tr class="ant-table-placeholder">
                <td colspan="4" class="ant-table-cell text-center">
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
        {{ $cities->links() }}
    </div>
</div>
