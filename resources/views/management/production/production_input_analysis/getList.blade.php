<table class="table m-0">
    <thead>
        <tr>
            <th>Date</th>
            <th>Company Location</th>
            <th>Arrival Location</th>
            <th>Plant</th>
            <th class="text-right px-4">Action</th>
        </tr>
    </thead>
    <tbody>
        @forelse($items as $item)
            <tr>
                <td>{{ \Carbon\Carbon::parse($item->analysis_date)->format('d-m-Y') }}</td>
                <td>{{ $item->location->name ?? 'N/A' }}</td>
                <td>{{ $item->arrivalLocation->name ?? 'N/A' }}</td>
                <td>{{ $item->plant->name ?? 'N/A' }}</td>
                <td class="text-right px-4">
                    <div class="group mx-auto">
                        <button onclick="openModal(this,'{{ route('production-input-analysis.show', $item->id) }}','View Production Input Analysis', true, '90%')" title="View" class="btn btn-sm btn-info hov waves-effect waves-light">
                            <i class="ft-eye"></i>
                        </button>
                        <button onclick="openModal(this,'{{ route('production-input-analysis.edit', $item->id) }}','Edit Production Input Analysis', false, '90%')" title="Edit" class="btn btn-sm btn-primary hov waves-effect waves-lightmx-1">
                            <i class="ft-edit"></i>
                        </button>
                        <button onclick="deletemodal('{{ route('production-input-analysis.destroy', $item->id) }}','{{ route('get.production-input-analysis') }}')" title="Delete" class="btn btn-sm btn-danger hov waves-effect waves-light">
                            <i class="ft-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="5" class="text-center">No records found.</td>
            </tr>
        @endforelse
    </tbody>
</table>

<div class="pagination-wrapper mt-3">
    {{ $items->links('vendor.pagination.bootstrap-4') }}
</div>
