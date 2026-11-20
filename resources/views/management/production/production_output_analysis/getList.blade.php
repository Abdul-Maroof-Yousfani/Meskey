<table class="table m-0">
    <thead>
        <tr>
            <th>Date</th>
            <th>Company Location</th>
            <th>Factory Location</th>
            <th>Plant</th>
            <th>Job Order</th>
            <th>Product</th>
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
                <td>{{ $item->jobOrders->pluck('job_order_no')->implode(', ') ?: '-' }}</td>
                <td>{{ $item->product->name ?? 'N/A' }}</td>
                <td class="text-right px-4">
                    <div class="group mx-auto">
                        <button onclick="openModal(this,'{{ route('production-output-analysis.show', $item->id) }}','View Production Output Analysis', true)" title="View" class="btn btn-sm btn-info hov waves-effect waves-light">
                            <i class="ft-eye"></i>
                        </button>
                        <button onclick="openModal(this,'{{ route('production-output-analysis.edit', $item->id) }}','Edit Production Output Analysis')" title="Edit" class="btn btn-sm btn-primary hov waves-effect waves-light mx-1">
                            <i class="ft-edit"></i>
                        </button>
                        <button onclick="deletemodal('{{ route('production-output-analysis.destroy', $item->id) }}','{{ route('get.production-output-analysis') }}')" title="Delete" class="btn btn-sm btn-danger hov waves-effect waves-light">
                            <i class="ft-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="text-center">No records found.</td>
            </tr>
        @endforelse
    </tbody>
</table>

<div class="pagination-wrapper mt-3">
    {{ $items->links('vendor.pagination.bootstrap-4') }}
</div>
