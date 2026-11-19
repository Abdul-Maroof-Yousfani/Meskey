{{-- ===== MOBILE: cards (Arrival Ticket list jaisa, mobile-ui.css section 6 + 14). Desktop par table. ===== --}}
<div class="dl-mobile">
    <div class="dl-mobile-bar">
        <input type="search" class="form-control dl-mobile-search" placeholder="Search..." autocomplete="off" data-keep-editable>
        <span class="dl-mobile-count">{{ count($rows) }} records</span>
    </div>
    <div class="tkc-list">
        @forelse ($rows as $row)
            @php $cells = array_values((array) $row); $cols = array_values((array) $columns); @endphp
            <div class="tkc">
                <div class="tkc-main">
                    <div class="tkc-top">
                        <div class="tkc-no">{{ is_array($cells[0] ?? null) ? ($cells[0]['badge'] ?? '-') : ($cells[0] ?? '-') }}</div>
                    </div>
                    <div class="tkc-grid">
                        @foreach ($cells as $ci => $cell)
                            @continue($ci === 0)
                            <div>
                                <span class="tkc-label">{{ $cols[$ci] ?? '' }}</span>
                                <span class="tkc-val">
                                    @if (is_array($cell) && isset($cell['badge']))
                                        <span class="badge {{ $cell['class'] }} px-2 py-1">{{ $cell['badge'] }}</span>
                                    @else
                                        {{ $cell ?? '-' }}
                                    @endif
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @empty
            <div class="tkc-empty"><i class="ft-inbox"></i> No data found for the selected criteria</div>
        @endforelse
    </div>
</div>

{{-- ===== DESKTOP: table (mobile par chhupa) ===== --}}
<div class="table-responsive dl-cards">
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
        // mobile cards search (card ka sara text) - document par ek hi dafa bind
        if (!window.dlMobileSearchReady) {
            window.dlMobileSearchReady = true;
            $(document).on('input', '.dl-mobile-search', function () {
                var q = this.value.toLowerCase().trim();
                var $wrap = $(this).closest('.dl-mobile');
                var shown = 0;
                $wrap.find('.tkc').each(function () {
                    var ok = !q || this.textContent.toLowerCase().indexOf(q) !== -1;
                    this.style.display = ok ? '' : 'none';
                    if (ok) shown++;
                });
                $wrap.find('.dl-mobile-count').text(shown + ' records');
            });
        }
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
