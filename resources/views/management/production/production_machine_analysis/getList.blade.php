<table class="table m-0">
    <thead>
        <tr>
            <th>Date</th>
            <th>Company Location</th>
            <th>Arrival Location</th>
            <th>Plant</th>
            <th>Machine</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        @forelse($items as $item)
            <tr>
                <td>{{ \Carbon\Carbon::parse($item->analysis_date)->format('d-m-Y') }}</td>
                <td>{{ $item->companyLocation->name ?? 'N/A' }}</td>
                <td>{{ $item->arrivalLocation->name ?? 'N/A' }}</td>
                <td>{{ $item->plant->name ?? 'N/A' }}</td>
                <td>{{ $item->machine->name ?? 'N/A' }}</td>
                <td>
                    <div class="btn-group">
                        <button type="button" class="btn btn-sm btn-info"
                            onclick="openModal(this,'{{ route('production-machine-analysis.show', $item->id) }}','View Machine Analysis',false,'95%')">
                            <i class="ft-eye"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-primary"
                            onclick="openModal(this,'{{ route('production-machine-analysis.edit', $item->id) }}','Edit Machine Analysis',false,'95%')">
                            <i class="ft-edit"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-danger ajaxDelete" 
                            data-url="{{ route('production-machine-analysis.destroy', $item->id) }}"
                            data-refresh="{{ route('get.production-machine-analysis') }}">
                            <i class="ft-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="text-center">No matching records found</td>
            </tr>
        @endforelse
    </tbody>
</table>

<div class="row mx-0 mt-3">
    <div class="col-sm-12 col-md-5">
        <div class="dataTables_info" role="status" aria-live="polite">
            Showing {{ $items->firstItem() }} to {{ $items->lastItem() }} of {{ $items->total() }} entries
        </div>
    </div>
    <div class="col-sm-12 col-md-7 text-right">
        <div class="dataTables_paginate paging_simple_numbers" id="pagination">
            {!! $items->links() !!}
        </div>
    </div>
</div>
