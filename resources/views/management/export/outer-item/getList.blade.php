@if($tickets->count() > 0)
    <table class="table table-striped m-0">
        <thead>
            <tr>
                <th>Ticket No.</th>
                <th>Truck No.</th>
                <th>Total Items</th>
                <th>Total Weight (KG)</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @foreach($tickets as $ticket)
                <tr>
                    <td>{{ $ticket->transaction_number }}</td>
                    <td>{{ $ticket->truck_number }}</td>
                    <td>{{ $ticket->outerItems->count() }}</td>
                    <td>{{ number_format($ticket->outerItems->sum('total_weight'), 2) }}</td>
                    <td>
                        <a onclick="openModal(this,'{{ route('export-outer-item.edit', $ticket->id) }}','Edit Export Outer Items', false)"
                            class="warning p-1 text-center mr-2 position-relative">
                            <i class="ft-edit font-medium-3"></i>
                        </a>
                        <a onclick="deletemodal('{{ route('export-outer-item.destroy', $ticket->id) }}', '{{ route('get.export-outer-item') }}')"
                            class="danger p-1 text-center mr-2 position-relative">
                            <i class="ft-trash-2"></i>
                        </a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="row d-flex" id="paginationLinks">
        <div class="col-md-12 text-right">
            {{ $tickets->links() }}
        </div>
    </div>
@else
    <table class="table m-0">
        <tbody>
            <tr>
                <td colspan="7" class="text-center py-5">
                    <h5 class="text-muted">No Export Other Item records found</h5>
                </td>
            </tr>
        </tbody>
    </table>
@endif
