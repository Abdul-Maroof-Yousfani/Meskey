<table class="table m-0">
    <thead>
        <tr>
            <th class="col-sm-1">S no.</th>
            <th class="col-sm-4">Name</th>
            <th class="col-sm-2">Alpha 2 Code</th>
            <th class="col-sm-2">Alpha 3 Code</th>
            <th class="col-sm-1">Phone Code</th>
            <th class="col-sm-2">Action</th>
        </tr>
    </thead>
    <tbody>
        @if (count($countries) != 0)
            @foreach ($countries as $key => $country)
                <tr>
                    <td>{{ $key + 1 + ($countries->currentPage() - 1) * $countries->perPage() }}</td>
                    <td>{{ $country->name }}</td>
                    <td>{{ $country->alpha_2_code }}</td>
                    <td>{{ $country->alpha_3_code }}</td>
                    <td>{{ $country->phone_code }}</td>
                    <td>
                        @canAccess('country-edit')
                        <a class="info p-1 text-center position-relative"
                            onclick="openModal(this,'{{ route('country.edit', $country->id) }}','Edit Country')">
                            <i class="ft-edit font-medium-3"></i>
                        </a>
                        @endcanAccess

                        @canAccess('country-show')
                        <a class="info p-1 text-center position-relative"
                            onclick="openModal(this,'{{ route('country.show', $country->id) }}','Show Country')">
                            <i class="ft-eye font-medium-3"></i>
                        </a>
                        @endcanAccess

                        @canAccess('country-delete')
                        <a onclick="deletemodal('{{ route('country.destroy', $country->id) }}','{{ route('get.country') }}')"
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
        {{ $countries->links() }}
    </div>
</div>
