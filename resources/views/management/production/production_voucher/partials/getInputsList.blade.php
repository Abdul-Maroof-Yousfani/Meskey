@if (count($inputs) != 0)
    @foreach($inputs as $input)
        <tr data-input-id="{{ $input->id }}">
            <td>{{ $input->product->name ?? 'N/A' }}</td>
            <td>{{ $input->location->name ?? 'N/A' }}</td>
            <td>{{ number_format($input->qty, 2) }}</td>
            <td>{{ $input->remarks ?? '-' }}</td>
            <td>
                <button type="button" class="btn btn-sm btn-primary" onclick="editProductionInput({{ $productionVoucher->id }}, {{ $input->id }})">
                    <i class="ft-edit"></i>
                </button>
                <button type="button" class="btn btn-sm btn-danger" onclick="deleteProductionInput({{ $productionVoucher->id }}, {{ $input->id }})">
                    <i class="ft-trash"></i>
                </button>
            </td>
        </tr>
    @endforeach
@else
    <tr>
        <td colspan="5" class="text-center">No production inputs found</td>
    </tr>
@endif

