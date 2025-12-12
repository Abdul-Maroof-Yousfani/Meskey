<table class="table m-0">
    <thead>
        <tr>
            <th class="col-sm-1">S no.</th>
            <th class="col-sm-2">Code</th>
            <th class="col-sm-2">Custom Duty</th>
            <th class="col-sm-1">Excise Duty (%)</th>
            <th class="col-sm-1">Sales Tax (%)</th>
            <th class="col-sm-1">Income Tax (%)</th>
            <th class="col-sm-1">Status</th>
            <th class="col-sm-2">Action</th>
        </tr>
    </thead>
    <tbody>
        @if (count($codes) != 0)
            @foreach ($codes as $key => $code)
                <tr>
                    <td>{{ $key + 1 }}</td>
                    <td>{{ $code->code }}</td>
                    <td>{{ number_format($code->custom_duty, 2) }}</td>
                    <td>{{ number_format($code->excise_duty, 2) }}%</td>
                    <td>{{ number_format($code->sales_tax, 2) }}%</td>
                    <td>{{ number_format($code->income_tax, 2) }}%</td>
                    <td>
                        <label class="badge bg-light-{{ $code->status == 'inactive' ? 'primary' : 'success' }}">
                            {{ ucfirst($code->status) }}
                        </label>
                    </td>
                    <td>
                        @canAccess('hs-code-edit')
                        <a class="info p-1 text-center position-relative"
                            onclick="openModal(this,'{{ route('hs-code.edit', $code->id) }}','Edit HS Code')">
                            <i class="ft-edit font-medium-3"></i>
                        </a>
                        @endcanAccess

                        @canAccess('hs-code-show')
                        <a class="info p-1 text-center position-relative"
                            onclick="openModal(this,'{{ route('hs-code.show', $code->id) }}','Show HS Code')">
                            <i class="ft-eye font-medium-3"></i>
                        </a>
                        @endcanAccess

                        @canAccess('hs-code-delete')
                        <a onclick="deletemodal('{{ route('hs-code.destroy', $code->id) }}','{{ route('get.hscode') }}')"
                            class="danger p-1 text-center mr-2 position-relative">
                            <i class="ft-x font-medium-3"></i>
                        </a>
                        @endcanAccess
                    </td>
                </tr>
            @endforeach
        @else
            <tr class="ant-table-placeholder">
                <td colspan="8" class="ant-table-cell text-center">
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
        {{ $codes->links() }}
    </div>
</div>
