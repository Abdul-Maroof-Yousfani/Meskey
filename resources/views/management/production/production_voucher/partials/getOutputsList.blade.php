@if (count($outputs) != 0)
    @foreach($outputs as $output)
        <tr data-output-id="{{ $output->id }}">
            <td>{{ $output->product->name ?? 'N/A' }}</td>
            <td>{{ number_format($output->qty, 2) }}</td>
            <td>{{ $output->storageLocation->name ?? 'N/A' }}</td>
            <td>{{ $output->brand->name ?? '-' }}</td>
            <td>{{ $output->remarks ?? '-' }}</td>
            <td>
                <button type="button" class="btn btn-sm btn-primary" onclick="editProductionOutput({{ $productionVoucher->id }}, {{ $output->id }})">
                    <i class="ft-edit"></i>
                </button>
                <button type="button" class="btn btn-sm btn-danger" onclick="deleteProductionOutput({{ $productionVoucher->id }}, {{ $output->id }})">
                    <i class="ft-trash"></i>
                </button>
            </td>
        </tr>
    @endforeach
@else
    <tr>
        <td colspan="6" class="text-center">No production outputs found</td>
    </tr>
@endif

