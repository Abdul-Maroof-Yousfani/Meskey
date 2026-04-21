<table class="table table-hover m-0">
    <thead class="thead-light">
        <tr>
            <th width="15%">Issuance No#</th>
            <th width="10%">Date</th>
            <th width="15%">Bag Request No#</th>
            <th width="15%">Gala</th>
            <th width="20%">Items</th>
            <th width="15%">Actions</th>
        </tr>
    </thead>
    <tbody>
        @if (isset($issuances) && count($issuances) != 0)
            @foreach ($issuances as $issuance)
                <tr>
                    <td><strong>{{ $issuance->issuance_number }}</strong></td>
                    <td>{{ \Carbon\Carbon::parse($issuance->issuance_date)->format('d/m/Y') }}</td>
                    <td>{{ $issuance->bagRequest->request_number ?? 'N/A' }}</td>
                    <td>{{ $issuance->gala->name ?? 'N/A' }}</td>
                    <td>
                        <ul class="list-unstyled mb-0">
                            @foreach($issuance->items as $item)
                                <li>{{ $item->item->name ?? 'N/A' }} ({{ number_format($item->quantity, 2) }} {{ $item->unit->name ?? '' }})</li>
                            @endforeach
                        </ul>
                    </td>
                    <td>
                        <div class="row w-100 mx-auto">
                            <button type="button" 
                                onclick="openModal(this,'{{ route('store.bag-issuance.show', $issuance->id) }}','View Bag Issuance',false,'80%')"
                                class="btn btn-sm btn-outline-info" title="View">
                                <i class="ft-eye"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            @endforeach
        @else
            <tr>
                <td colspan="6" class="text-center py-4">No Bag Issuances Found</td>
            </tr>
        @endif
    </tbody>
</table>

@if (isset($issuances) && count($issuances) != 0)
<div class="row mt-3">
    <div class="col-md-12">
        <div class="float-right" id="paginationLinks">
            {{ $issuances->links() }}
        </div>
    </div>
</div>
@endif
