<!-- <div class="mb-2">
    <span>
        <strong>Date Range:</strong> {{ $daterange }}
    </span>
    @if ($accountName)
        &nbsp;&nbsp;&nbsp; | &nbsp;&nbsp;&nbsp;
        <span>
            <strong>Account:</strong> {{ ucwords($accountName) }}
        </span>
    @endif
</div> -->

<div class="mb-2">
    <button onclick="exportToExcel()" class="btn btn-success btn-sm">
        <i class="fas fa-file-excel"></i> Export to Excel
    </button>
    <!-- <button onclick="exportToCSV()" class="btn btn-info btn-sm">
        <i class="fas fa-file-csv"></i> Export to CSV
    </button> -->
    <button onclick="showSummary()" class="btn btn-warning btn-sm">
        <i class="fas fa-chart-bar"></i> Summary
    </button>
</div>

<div class="table-responsive" style="max-height: 65vh; overflow-y: auto;">
    <table class="table table-striped table-bordered table-hover mb-0" id="transactionTable">
        <thead
            style="position: sticky; top: 0; z-index: 10; background: #f8f9fa; box-shadow: 0 2px 2px -1px rgba(0,0,0,0.1);">
            <tr>
                <th class="text-center">Date</th>
                <th>Voucher No</th>
                <th>Account</th>
                <th>Counter Acc.</th>
                <th>Description</th>
                <th class="text-right">Debit</th>
                <th class="text-right">Credit</th>
                <th class="text-right">Balance</th>
            </tr>
            <tr style="background: darkseagreen;">
                <th colspan="8" class="text-center" style="background: darkseagreen;font-size: 14px;">
                    Filter:
                    <span>
                        <strong>Date Range:</strong> {{ $daterange }}
                    </span>
                    @if ($accountName)
                        &nbsp;&nbsp;&nbsp; | &nbsp;&nbsp;&nbsp;
                        <span>
                            <strong>Account:</strong> {{ ucwords($accountName) }}
                        </span>
                    @endif

                </th>

            </tr>
        </thead>
        <tbody>
            @if (count($transactions) > 0)
                @php
                    $balance = 0;
                    $openingBalance = $openingBalance ?? 0;
                    $balance += $openingBalance;
                    $totalDebit = 0;
                    $totalCredit = 0;
                @endphp

                <tr class="font-weight-bold" style="background-color: #e8f4fd;">
                    <td class="text-center">
                        {{ date('d-m-Y', strtotime(request('start_date') ?? date('Y-m-01'))) }}
                    </td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td class="text-left font-italic">Opening Balance</td>
                    <td class="text-right">{{ $openingBalance >= 0 ? number_format($openingBalance, 2) : '-' }}</td>
                    <td class="text-right">{{ $openingBalance < 0 ? number_format(abs($openingBalance), 2) : '-' }}</td>
                    <td class="text-right">{{ number_format($balance, 2) }}</td>
                </tr>

                @foreach ($transactions as $transaction)
                    @php
                        if ($transaction->type == 'debit') {
                            $balance += $transaction->amount;
                            $totalDebit += $transaction->amount;
                        } else {
                            $balance -= $transaction->amount;
                            $totalCredit += $transaction->amount;
                        }
                    @endphp
                    <tr>
                        <td class="text-center">{{ $transaction->voucher_date->format('d-m-Y') }}</td>
                        <td>
                            {{ $transaction->voucher_no }}
                        </td>
                        <td class="text-center">
                            @if ($transaction->account_id)
                                @php
                                    $startDate = $transaction->voucher_date->format('m/d/Y');
                                    $endDate = \Carbon\Carbon::now()->format('m/d/Y');
                                    $daterange = urlencode($startDate . ' - ' . $endDate);
                                @endphp
                                <a href="{{ url('transactions/report') }}?account_id={{ $transaction->account_id }}&daterange={{ $daterange }}&_f"
                                    target="_blank">
                                    {{ $transaction->account->name ?? 'N/A' }}
                                </a>
                            @else
                                <span> N/A </span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if ($transaction->counter_account_id)
                                @php
                                    $startDate = $transaction->voucher_date->format('m/d/Y');
                                    $endDate = \Carbon\Carbon::now()->format('m/d/Y');
                                    $daterange = urlencode($startDate . ' - ' . $endDate);
                                @endphp
                                <a href="{{ url('transactions/report') }}?account_id={{ $transaction->counter_account_id }}&daterange={{ $daterange }}&_f"
                                    target="_blank">
                                    {{ $transaction->counterAccount->name ?? 'N/A' }}
                                </a>
                            @else
                                <span title="Counter Account"> N/A </span>
                            @endif
                        </td>
                        <td>
                            {{ $transaction->remarks }}
                            @if ($transaction->payment_against)
                                <br><small class="text-muted">Against: {{ $transaction->payment_against }}
                                    @if ($transaction->against_reference_no)
                                        ({{ $transaction->against_reference_no }})
                                    @endif
                                    ({{ formatEnumValue($transaction->purpose) }})

                                    @if ($transaction->grn_no)
                                        | GRN: {{ $transaction->grn_no }}
                                    @endif
                                </small>
                            @endif
                        </td>
                        <td class="text-right">
                            {{ $transaction->type == 'debit' ? number_format($transaction->amount, 2) : '-' }}
                        </td>
                        <td class="text-right">
                            {{ $transaction->type == 'credit' ? number_format($transaction->amount, 2) : '-' }}
                        </td>
                        <td class="text-right">{{ number_format($balance, 2) }}</td>
                    </tr>
                @endforeach
            @else
                <tr class="ant-table-placeholder">
                    <td colspan="11" class="ant-table-cell text-center">
                        <div class="my-5">
                            <svg width="64" height="41" viewBox="0 0 64 41" xmlns="http://www.w3.org/2000/svg">
                                <g transform="translate(0 1)" fill="none" fill-rule="evenodd">
                                    <ellipse fill="#f5f5f5" cx="32" cy="33" rx="32" ry="7">
                                    </ellipse>
                                    <g fill-rule="nonzero" stroke="#d9d9d9">
                                        <path
                                            d="M55 12.76L44.854 1.258C44.367.474 43.656 0 42.907 0H21.093c-.749 0-1.46.474-1.947 1.257L9 12.761V22h46v-9.24z">
                                        </path>
                                        <path
                                            d="M41.613 15.931c0-1.605.994-2.93 2.227-2.931H55v18.137C55 33.26 53.68 35 52.05 35h-40.1C10.32 35 9 33.259 9 31.137V13h11.16c1.233 0 2.227 1.323 2.227 2.928v.022c0 1.605 1.005 2.901 2.237 2.901h14.752c1.232 0 2.237-1.308 2.237-2.913v-.007z"
                                            fill="#fafafa"></path>
                                    </g>
                                </g>
                            </svg>
                            <p class="ant-empty-description">No data</p>
                        </div>
                    </td>
                </tr>
            @endif
        </tbody>
        @if (count($transactions) > 0)
            @php
                $difference = $totalDebit - $totalCredit;
                $isBalanced = abs($difference) < 0.01; // Tolerance for floating point
                $footerColor = $isBalanced ? '#d4edda' : '#f8d7da';
                $borderColor = $isBalanced ? '#c3e6cb' : '#f5c6cb';
                $textColor = $isBalanced ? '#155724' : '#721c24';
            @endphp
            <tfoot
                style="position: sticky; bottom: 0; z-index: 10; background: {{ $footerColor }}; border-top: 3px solid {{ $borderColor }}; box-shadow: 0 -2px 2px -1px rgba(0,0,0,0.1);">
                <tr style="font-size: 1.05em;">
                    <td colspan="5" class="text-left font-weight-bold" style="color: {{ $textColor }};">
                        <span style="font-size: 1.1em;">TOTAL</span>
                        @if (!$isBalanced)
                            <span class="ml-2 badge badge-danger" style="font-size: 0.8em;">
                                ⚠ IMBALANCE: {{ number_format(abs($difference), 2) }}
                            </span>
                        @else
                            <span class="ml-2 badge badge-success" style="font-size: 0.8em;">
                                ✓ BALANCED
                            </span>
                        @endif
                    </td>
                    <td class="text-right font-weight-bold" style="color: {{ $textColor }};">
                        {{ number_format($totalDebit, 2) }}
                    </td>
                    <td class="text-right font-weight-bold" style="color: {{ $textColor }};">
                        {{ number_format($totalCredit, 2) }}
                    </td>
                    <td class="text-right font-weight-bold" style="color: {{ $textColor }};">
                        {{ number_format($balance, 2) }}
                    </td>
                </tr>
                <!-- @if (!$isBalanced)
                                                    <tr style="background-color: #f8d7da;">
                                                        <td colspan="8" class="text-center" style="color: #721c24; font-weight: bold; padding: 5px;">
                                                            <span style="font-size: 1.1em;">
                                                                ⚠ IMBALANCE DETECTED: Debits ({{ number_format($totalDebit, 2) }}) ≠ Credits
                                                                ({{ number_format($totalCredit, 2) }})
                                                                | Difference: {{ number_format(abs($difference), 2) }}
                                                            </span>
                                                        </td>
                                                    </tr>
                                                @endif -->
            </tfoot>
        @endif
    </table>
</div>


<script>
    $(function () {
        $('[data-toggle="tooltip"]').tooltip();
    });

    // SweetAlert2 Summary
    function showSummary() {
        // Get totals from the footer
        var totalDebit = {{ $totalDebit ?? 0 }};
        var totalCredit = {{ $totalCredit ?? 0 }};
        var balance = {{ $balance ?? 0 }};
        var isBalanced = {{ isset($isBalanced) ? ($isBalanced ? 'true' : 'false') : 'true' }};
        var difference = {{ $difference ?? 0 }};
        var transactionCount = {{ count($transactions) }};

        Swal.fire({
            title: '📊 Transaction Summary',
            html: `
                <div style="text-align: left; font-size: 14px;">
                    <div style="display: flex; justify-content: space-between; padding: 8px; border-bottom: 1px solid #dee2e6;">
                        <span><strong>Total Transactions:</strong></span>
                        <span>${transactionCount}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; padding: 8px; border-bottom: 1px solid #dee2e6;">
                        <span><strong>Total Debit:</strong></span>
                        <span style="color: #28a745; font-weight: bold;">${totalDebit.toFixed(2)}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; padding: 8px; border-bottom: 1px solid #dee2e6;">
                        <span><strong>Total Credit:</strong></span>
                        <span style="color: #dc3545; font-weight: bold;">${totalCredit.toFixed(2)}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; padding: 8px; border-bottom: 1px solid #dee2e6;">
                        <span><strong>Balance:</strong></span>
                        <span style="font-weight: bold;">${balance.toFixed(2)}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; padding: 8px; ${isBalanced ? 'background: #d4edda; color: #155724;' : 'background: #f8d7da; color: #721c24;'} border-radius: 4px; margin-top: 8px;">
                        <span><strong>Status:</strong></span>
                        <span style="font-weight: bold;">${isBalanced ? '✅ Balanced' : '⚠️ Imbalanced (Difference: ' + Math.abs(difference).toFixed(2) + ')'}</span>
                    </div>
                </div>
            `,
            icon: isBalanced ? 'success' : 'warning',
            confirmButtonColor: isBalanced ? '#28a745' : '#dc3545',
            confirmButtonText: 'OK'
        });
    }

    // Export to Excel function with colored rows and column widths
    function exportToExcel() {
        // Show loading alert
        Swal.fire({
            title: '⏳ Generating Excel...',
            text: 'Please wait while we prepare your export',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        setTimeout(function () {
            try {
                var table = document.getElementById('transactionTable');
                var cloneTable = table.cloneNode(true);

                // Remove anchor tags and keep text only
                var links = cloneTable.querySelectorAll('a');
                links.forEach(function (link) {
                    var text = link.textContent;
                    link.parentNode.replaceChild(document.createTextNode(text), link);
                });

                // Remove small tags but keep text
                var smallTags = cloneTable.querySelectorAll('small');
                smallTags.forEach(function (small) {
                    var text = small.textContent;
                    small.parentNode.replaceChild(document.createTextNode(text), small);
                });

                // Remove badges
                var badges = cloneTable.querySelectorAll('.badge');
                badges.forEach(function (badge) {
                    badge.remove();
                });

                // Create a new table with clean data
                var cleanTable = document.createElement('table');
                cleanTable.innerHTML = cloneTable.innerHTML;

                // Convert to worksheet
                var ws = XLSX.utils.table_to_sheet(cleanTable);

                // Set column widths
                ws['!cols'] = [
                    { wch: 15 }, // Date
                    { wch: 15 }, // Voucher No
                    { wch: 25 }, // Account
                    { wch: 25 }, // Counter Acc.
                    { wch: 35 }, // Description
                    { wch: 15 }, // Debit
                    { wch: 15 }, // Credit
                    { wch: 15 }  // Balance
                ];

                // Apply colors to rows using cell styles
                var range = XLSX.utils.decode_range(ws['!ref']);

                // Style header row
                for (var C = range.s.c; C <= range.e.c; C++) {
                    var address = XLSX.utils.encode_cell({ r: 0, c: C });
                    if (!ws[address]) continue;
                    if (!ws[address].s) ws[address].s = {};
                    ws[address].s.fill = { fgColor: { rgb: "F8F9FA" } };
                    ws[address].s.font = { bold: true, color: { rgb: "000000" } };
                }

                // Style filter row (row 1)
                for (var C = range.s.c; C <= range.e.c; C++) {
                    var address = XLSX.utils.encode_cell({ r: 1, c: C });
                    if (!ws[address]) continue;
                    if (!ws[address].s) ws[address].s = {};
                    ws[address].s.fill = { fgColor: { rgb: "8FBC8F" } };
                    ws[address].s.font = { bold: true, color: { rgb: "000000" } };
                }

                // Style opening balance row
                var rows = cloneTable.querySelectorAll('tr');
                var rowIndex = 2; // Start after header rows
                var openingBalanceRow = cloneTable.querySelector('tr[style*="background-color: #e8f4fd"]');
                if (openingBalanceRow) {
                    for (var C = range.s.c; C <= range.e.c; C++) {
                        var address = XLSX.utils.encode_cell({ r: rowIndex, c: C });
                        if (!ws[address]) continue;
                        if (!ws[address].s) ws[address].s = {};
                        ws[address].s.fill = { fgColor: { rgb: "E8F4FD" } };
                        ws[address].s.font = { bold: true };
                    }
                    rowIndex++;
                }

                // Style transaction rows with alternating colors
                var transactionRows = cloneTable.querySelectorAll('tbody tr:not([style*="background-color: #e8f4fd"])');
                var isEven = true;
                transactionRows.forEach(function (row) {
                    if (row.style && row.style.backgroundColor) {
                        // Skip rows with custom background
                        return;
                    }
                    var color = isEven ? "FFFFFF" : "F8F9FA";
                    for (var C = range.s.c; C <= range.e.c; C++) {
                        var address = XLSX.utils.encode_cell({ r: rowIndex, c: C });
                        if (!ws[address]) continue;
                        if (!ws[address].s) ws[address].s = {};
                        ws[address].s.fill = { fgColor: { rgb: color } };
                    }
                    rowIndex++;
                    isEven = !isEven;
                });

                // Style footer row (total)
                var footerRows = cloneTable.querySelectorAll('tfoot tr');
                footerRows.forEach(function (row) {
                    var color = row.style && row.style.backgroundColor ?
                        row.style.backgroundColor.replace('#', '') : 'D4EDDA';
                    for (var C = range.s.c; C <= range.e.c; C++) {
                        var address = XLSX.utils.encode_cell({ r: rowIndex, c: C });
                        if (!ws[address]) continue;
                        if (!ws[address].s) ws[address].s = {};
                        ws[address].s.fill = { fgColor: { rgb: color.toUpperCase() } };
                        ws[address].s.font = { bold: true };
                    }
                    rowIndex++;
                });

                // Create workbook
                var wb = XLSX.utils.book_new();
                XLSX.utils.book_append_sheet(wb, ws, 'Transactions');

                // Generate filename with date
                var filename = 'transactions_report_' + new Date().toISOString().slice(0, 10) + '.xlsx';

                // Save file
                XLSX.writeFile(wb, filename);

                // Close loading and show success
                Swal.fire({
                    title: '✅ Success!',
                    text: 'File exported successfully as ' + filename,
                    icon: 'success',
                    timer: 3000,
                    timerProgressBar: true
                });
            } catch (error) {
                Swal.fire({
                    title: '❌ Error!',
                    text: 'Failed to export file: ' + error.message,
                    icon: 'error'
                });
            }
        }, 500);
    }

    // Export to CSV function
    function exportToCSV() {
        Swal.fire({
            title: '⏳ Generating CSV...',
            text: 'Please wait while we prepare your export',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        setTimeout(function () {
            try {
                var table = document.getElementById('transactionTable');
                var cloneTable = table.cloneNode(true);

                // Remove anchor tags and keep text only
                var links = cloneTable.querySelectorAll('a');
                links.forEach(function (link) {
                    var text = link.textContent;
                    link.parentNode.replaceChild(document.createTextNode(text), link);
                });

                // Remove small tags
                var smallTags = cloneTable.querySelectorAll('small');
                smallTags.forEach(function (small) {
                    var text = small.textContent;
                    small.parentNode.replaceChild(document.createTextNode(text), small);
                });

                // Remove badges
                var badges = cloneTable.querySelectorAll('.badge');
                badges.forEach(function (badge) {
                    badge.remove();
                });

                // Clean the table
                var cleanTable = document.createElement('table');
                cleanTable.innerHTML = cloneTable.innerHTML;

                // Convert to worksheet
                var ws = XLSX.utils.table_to_sheet(cleanTable);

                // Set column widths
                ws['!cols'] = [
                    { wch: 15 }, // Date
                    { wch: 15 }, // Voucher No
                    { wch: 25 }, // Account
                    { wch: 25 }, // Counter Acc.
                    { wch: 35 }, // Description
                    { wch: 15 }, // Debit
                    { wch: 15 }, // Credit
                    { wch: 15 }  // Balance
                ];

                // Create workbook
                var wb = XLSX.utils.book_new();
                XLSX.utils.book_append_sheet(wb, ws, 'Transactions');

                // Generate filename with date
                var filename = 'transactions_report_' + new Date().toISOString().slice(0, 10) + '.csv';

                // Save as CSV
                XLSX.writeFile(wb, filename, { bookType: 'csv' });

                Swal.fire({
                    title: '✅ Success!',
                    text: 'File exported successfully as ' + filename,
                    icon: 'success',
                    timer: 3000,
                    timerProgressBar: true
                });
            } catch (error) {
                Swal.fire({
                    title: '❌ Error!',
                    text: 'Failed to export file: ' + error.message,
                    icon: 'error'
                });
            }
        }, 500);
    }
</script>

<!-- Include SheetJS library for Excel export -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<!-- Include SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">