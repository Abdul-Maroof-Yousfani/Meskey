<style>
    /* Commodity Total Row */
    .sticky-table tbody tr.commodity-total-row td {
        background-color: #f8f9fa !important;
        font-weight: 600 !important;
        border-bottom: 2px solid #dee2e6 !important;
    }
    .sticky-table tbody tr.commodity-total-row:hover td {
        background-color: #edf2f7 !important;
    }

    /* Grand Total Row at bottom */
    .sticky-table tbody tr.grand-total-row td {
        background-color: #cbd5e1 !important;
        color: #0f172a !important;
        font-weight: 700 !important;
        border-top: 3px solid #64748b !important;
    }
    .sticky-table tbody tr.grand-total-row:hover td {
        background-color: #94a3b8 !important;
    }

    /* Row-wise Hover Effect */
    .sticky-table tbody tr td {
        transition: background-color 0.15s ease-in-out;
    }
    .sticky-table tbody tr.data-row:hover td:not([rowspan]) {
        background-color: #f1f5f9 !important;
    }

    /* Keep multi-row spanning cells white */
    .sticky-table tbody td[rowspan] {
        background-color: #ffffff !important;
        vertical-align: middle !important;
    }
    .sticky-table tbody td[rowspan]:hover {
        background-color: #f8fafc !important;
    }
</style>

<x-sticky-table :items="collect($reportRows ?? $data)" :leftSticky="3" :rightSticky="0" :emptyMessage="'No records found'" :pagination="false">
    @slot('head')
        <th class="text-left" style="min-width: 140px;">Date</th>
        <th class="text-left" style="min-width: 180px;">Commodity</th>
        <th class="text-left" style="min-width: 140px;">Purchaser</th>
        <th class="text-right" style="min-width: 120px;">Truck Arrived</th>
        <th class="text-right" style="min-width: 120px;">Total Unloaded</th>
        <th class="text-right" style="min-width: 120px;">Full Approved</th>
        <th class="text-right" style="min-width: 120px;">Half Rejected</th>
        <th class="text-right" style="min-width: 120px;">Full rejected</th>
        <th class="text-right" style="min-width: 120px;">In Process</th>
    @endslot

    @slot('body')
        @php
            $rows = $reportRows ?? $data;
        @endphp
        @foreach ($rows as $index => $row)
            @php
                $isTotal = $row['is_total'] ?? false;
            @endphp
            @if ($isTotal)
                <tr class="commodity-total-row font-weight-bold">
                    <td class="text-left font-weight-bold text-dark">
                        {{ $row['purchaser_name'] }}
                    </td>
                    <td class="text-right font-weight-bold">{{ number_format($row['truck_arrived']) }}</td>
                    <td class="text-right font-weight-bold">{{ number_format($row['total_unloaded']) }}</td>
                    <td class="text-right font-weight-bold">{{ number_format($row['fully_approved']) }}</td>
                    <td class="text-right font-weight-bold">{{ number_format($row['half_rejected']) }}</td>
                    <td class="text-right font-weight-bold">{{ number_format($row['full_rejected']) }}</td>
                    <td class="text-right font-weight-bold">{{ number_format($row['in_process']) }}</td>
                </tr>
            @else
                <tr class="data-row">
                    @if (isset($commodityRowspans[$index]))
                        <td rowspan="{{ $commodityRowspans[$index] }}" class="font-weight-bold text-left">
                            {{ $row['summary_date'] }}
                        </td>
                        <td rowspan="{{ $commodityRowspans[$index] }}" class="font-weight-bold text-left text-dark">
                            {{ $row['commodity_name'] }}
                        </td>
                    @endif
                    <td class="text-left text-dark">
                        {{ $row['purchaser_name'] }}
                    </td>
                    <td class="text-right font-weight-bold">{{ number_format($row['truck_arrived']) }}</td>
                    <td class="text-right">{{ number_format($row['total_unloaded']) }}</td>
                    <td class="text-right">{{ number_format($row['fully_approved']) }}</td>
                    <td class="text-right">{{ number_format($row['half_rejected']) }}</td>
                    <td class="text-right">{{ number_format($row['full_rejected']) }}</td>
                    <td class="text-right">{{ number_format($row['in_process']) }}</td>
                </tr>
            @endif
        @endforeach

        @if (count($rows) > 0)
            <tr class="grand-total-row font-weight-bold">
                <td colspan="3" class="text-left font-weight-bold text-dark">Grand Total</td>
                <td class="text-right font-weight-bold">{{ number_format($grandTotals['truck_arrived'] ?? 0) }}</td>
                <td class="text-right font-weight-bold">{{ number_format($grandTotals['total_unloaded'] ?? 0) }}</td>
                <td class="text-right font-weight-bold">{{ number_format($grandTotals['fully_approved'] ?? 0) }}</td>
                <td class="text-right font-weight-bold">{{ number_format($grandTotals['half_rejected'] ?? 0) }}</td>
                <td class="text-right font-weight-bold">{{ number_format($grandTotals['full_rejected'] ?? 0) }}</td>
                <td class="text-right font-weight-bold">{{ number_format($grandTotals['in_process'] ?? 0) }}</td>
            </tr>
        @endif
    @endslot
</x-sticky-table>
