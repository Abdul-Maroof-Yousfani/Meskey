<div class="table-responsive">
    <table class="table table-striped table-bordered m-0" id="exportableTable">
        <thead class="thead-light">
            <tr>
                <th style="width: 80px;">#</th>
                <th>Bag</th>
                <th class="text-center" style="width: 180px;">Total Tickets</th>
                <th class="text-right" style="width: 250px;">Filled Bags</th>
            </tr>
        </thead>
        <tbody>
            @php
                $grandTotalFilledBags = 0;
                $grandTotalTickets = 0;
            @endphp
            @forelse ($data as $row)
                @php
                    $grandTotalFilledBags += (int) ($row->total_filled_bags ?? 0);
                    $grandTotalTickets += (int) ($row->total_tickets ?? 0);
                @endphp
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td class="font-weight-bold">{{ $row->bag_name ?? 'N/A' }}</td>
                    <td class="text-center">{{ number_format($row->total_tickets) }}</td>
                    <td class="text-right">{{ number_format($row->total_filled_bags) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-center py-4 text-muted">
                        No records found
                    </td>
                </tr>
            @endforelse
        </tbody>
        @if (count($data) > 0)
            <tfoot class="bg-light font-weight-bold">
                <tr>
                    <td colspan="2" class="text-left"><strong>Total:</strong></td>
                    <td class="text-center"><strong>{{ number_format($grandTotalTickets) }}</strong></td>
                    <td class="text-right"><strong>{{ number_format($grandTotalFilledBags) }}</strong></td>
                </tr>
            </tfoot>
        @endif
    </table>
</div>
