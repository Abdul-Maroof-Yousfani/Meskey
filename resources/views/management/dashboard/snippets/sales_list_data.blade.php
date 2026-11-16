<div class="table-responsive">
    <table class="table table-striped table-hover" id="salesDashboardTable">
        <thead class="thead-dark">
            <tr>
                <th>S.No</th>
                @foreach ($columns as $column)
                    <th>{{ $column }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $i => $row)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    @foreach ($row as $cell)
                        <td>
                            @if (is_array($cell) && isset($cell['badge']))
                                <span class="badge {{ $cell['class'] }} px-2 py-1">{{ $cell['badge'] }}</span>
                            @else
                                {{ $cell ?? '-' }}
                            @endif
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<script>
    $(document).ready(function() {
        $('#salesDashboardTable').DataTable({
            "paging": false,
            "searching": true,
            "ordering": false,
            "dom": '<"top"i>frt',
            "language": {
                "emptyTable": "No data found for the selected criteria",
                "zeroRecords": "No matching records found",
                "search": "Search:",
                "info": "Total Entries: _TOTAL_",
                "infoEmpty": "Total Entries: 0",
                "infoFiltered": "(filtered from _MAX_ total records)"
            }
        });
    });
</script>
