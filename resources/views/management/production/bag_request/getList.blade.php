<table class="table table-hover m-0">
    <thead class="thead-light">
        <tr>
            <th width="15%">Request No#</th>
            <th width="10%">Date</th>
            <th width="15%">Gala</th>
            <th width="20%">Items</th>
            <th width="25%">Remarks</th>
            <th width="15%">Actions</th>
        </tr>
    </thead>
    <tbody>
        @if (count($requests) != 0)
            @foreach ($requests as $request)
                <tr>
                    <td><strong>{{ $request->request_number }}</strong></td>
                    <td>{{ \Carbon\Carbon::parse($request->request_date)->format('d/m/Y') }}</td>
                    <td>{{ $request->gala->name ?? 'N/A' }}</td>
                    <td>
                        <ul class="list-unstyled mb-0">
                            @foreach($request->items as $item)
                                <li>{{ $item->item->name ?? 'N/A' }} ({{ number_format($item->quantity, 2) }} {{ $item->unit->name ?? '' }})</li>
                            @endforeach
                        </ul>
                    </td>
                    <td>{{ $request->remarks }}</td>
                    <td>
                        <div class="row w-100 mx-auto">
                            <button type="button" 
                                onclick="openModal(this,'{{ route('bag-requests.show', $request->id) }}','View Bag Request',false,'80%')"
                                class="btn btn-sm btn-outline-info mr-1" title="View">
                                <i class="ft-eye"></i>
                            </button>
                            <button type="button" 
                                onclick="openModal(this,'{{ route('bag-requests.edit', $request->id) }}','Edit Bag Request',false,'80%')"
                                class="btn btn-sm btn-outline-primary mr-1" title="Edit">
                                <i class="ft-edit"></i>
                            </button>
                            @if($request->issuances->count() == 0)
                                <button type="button" 
                                    onclick="deletemodal('{{ route('bag-requests.destroy', $request->id) }}','{{ route('get.bag-requests') }}')"
                                    class="btn btn-sm btn-outline-danger" title="Delete">
                                    <i class="ft-trash"></i>
                                </button>
                            @endif
                        </div>
                    </td>
                </tr>
            @endforeach
        @else
            <tr>
                <td colspan="6" class="text-center py-4">No Bag Requests Found</td>
            </tr>
        @endif
    </tbody>
</table>

@if (count($requests) != 0)
<div class="row mt-3">
    <div class="col-md-12">
        <div class="float-right" id="paginationLinks">
            {{ $requests->links() }}
        </div>
    </div>
</div>
@endif
