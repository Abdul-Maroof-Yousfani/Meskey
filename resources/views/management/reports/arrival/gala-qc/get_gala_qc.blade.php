<style>
    /* Full row background color for Gala Total Row */
    .sticky-table tbody tr.gala-total-row td {
        background-color: #e2e8f0 !important;
        color: #0f172a !important;
        font-weight: 700 !important;
        border-top: 2px solid #cbd5e1 !important;
        border-bottom: 2px solid #94a3b8 !important;
    }
    .sticky-table tbody tr.gala-total-row:hover td {
        background-color: #cbd5e1 !important;
    }

    /* Product Total Row */
    .sticky-table tbody tr.product-total-row td {
        background-color: #f8f9fa !important;
        font-weight: 600 !important;
        border-bottom: 2px solid #dee2e6 !important;
    }
    .sticky-table tbody tr.product-total-row:hover td {
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

<x-sticky-table :items="collect($reportRows ?? $galaData)" :leftSticky="3" :rightSticky="0" :emptyMessage="'No records found'" :pagination="false">
    @slot('head')
        <th class="text-left" style="min-width: 140px;">Galaa #</th>
        <th class="text-left" style="min-width: 180px;">Commodity</th>
        <th class="text-left" style="min-width: 140px;">QC Advice</th>
        <th class="text-right" style="min-width: 130px;">Net Weight</th>
        <th class="text-right" style="min-width: 160px;">Average of Inner Broken</th>
        <th class="text-right" style="min-width: 180px;">Average of Inner Moisture (15% max)</th>
        <th class="text-right" style="min-width: 200px;">Damage: Creamish / grey / Tip) max 2%</th>
        <th class="text-right" style="min-width: 160px;">Damage: YELLOW max 1%</th>
        <th class="text-right" style="min-width: 160px;">Under Milled (5% max)</th>
        <th class="text-right" style="min-width: 160px;">Average of Chalky (5% max)</th>
    @endslot

    @slot('body')
        @php
            $rows = $reportRows ?? $galaData;
        @endphp
        @foreach ($rows as $index => $row)
            @php
                $isGalaTotal = $row['is_gala_total'] ?? false;
                $isProductTotal = $row['is_total'] ?? false;
            @endphp
            @if ($isGalaTotal)
                <tr class="gala-total-row font-weight-bold">
                    <td colspan="3" class="text-left text-dark">
                        {{ $row['gala_name'] }} Total
                    </td>
                    <td class="text-right">{{ number_format($row['net_weight'], 0, '.', ',') }}</td>
                    <td class="text-right">
                        {{ $row['averages']['inner_broken'] > 0 ? number_format($row['averages']['inner_broken'], 2) : '-' }}
                    </td>
                    <td class="text-right">
                        {{ $row['averages']['inner_moisture'] > 0 ? number_format($row['averages']['inner_moisture'], 2) : '-' }}
                    </td>
                    <td class="text-right">
                        {{ $row['averages']['damage_creamish'] > 0 ? number_format($row['averages']['damage_creamish'], 2) : '-' }}
                    </td>
                    <td class="text-right">
                        {{ $row['averages']['damage_yellow'] > 0 ? number_format($row['averages']['damage_yellow'], 2) : '-' }}
                    </td>
                    <td class="text-right">
                        {{ $row['averages']['under_milled'] > 0 ? number_format($row['averages']['under_milled'], 2) : '-' }}
                    </td>
                    <td class="text-right">
                        {{ $row['averages']['chalky'] > 0 ? number_format($row['averages']['chalky'], 2) : '-' }}
                    </td>
                </tr>
            @elseif ($isProductTotal)
                <tr class="product-total-row font-weight-bold">
                    <td class="text-left font-weight-bold text-dark">
                        {{ $row['qc_advice'] }}
                    </td>
                    <td class="text-right font-weight-bold">{{ number_format($row['net_weight'], 0, '.', ',') }}</td>
                    <td class="text-right font-weight-bold">
                        {{ $row['averages']['inner_broken'] > 0 ? number_format($row['averages']['inner_broken'], 2) : '-' }}
                    </td>
                    <td class="text-right font-weight-bold">
                        {{ $row['averages']['inner_moisture'] > 0 ? number_format($row['averages']['inner_moisture'], 2) : '-' }}
                    </td>
                    <td class="text-right font-weight-bold">
                        {{ $row['averages']['damage_creamish'] > 0 ? number_format($row['averages']['damage_creamish'], 2) : '-' }}
                    </td>
                    <td class="text-right font-weight-bold">
                        {{ $row['averages']['damage_yellow'] > 0 ? number_format($row['averages']['damage_yellow'], 2) : '-' }}
                    </td>
                    <td class="text-right font-weight-bold">
                        {{ $row['averages']['under_milled'] > 0 ? number_format($row['averages']['under_milled'], 2) : '-' }}
                    </td>
                    <td class="text-right font-weight-bold">
                        {{ $row['averages']['chalky'] > 0 ? number_format($row['averages']['chalky'], 2) : '-' }}
                    </td>
                </tr>
            @else
                <tr class="data-row">
                    @if (isset($galaRowspans[$index]))
                        <td rowspan="{{ $galaRowspans[$index] }}" class="font-weight-bold text-left">
                            {{ $row['gala_name'] }}
                        </td>
                    @endif
                    @if (isset($commodityRowspans[$index]))
                        <td rowspan="{{ $commodityRowspans[$index] }}" class="font-weight-bold text-left text-dark">
                            {{ $row['commodity'] }}
                        </td>
                    @endif
                    <td class="text-left text-dark">
                        {{ $row['qc_advice'] }}
                    </td>
                    <td class="text-right">{{ number_format($row['net_weight'], 0, '.', ',') }}</td>
                    <td class="text-right">
                        {{ $row['averages']['inner_broken'] > 0 ? number_format($row['averages']['inner_broken'], 2) : '-' }}
                    </td>
                    <td class="text-right">
                        {{ $row['averages']['inner_moisture'] > 0 ? number_format($row['averages']['inner_moisture'], 2) : '-' }}
                    </td>
                    <td class="text-right">
                        {{ $row['averages']['damage_creamish'] > 0 ? number_format($row['averages']['damage_creamish'], 2) : '-' }}
                    </td>
                    <td class="text-right">
                        {{ $row['averages']['damage_yellow'] > 0 ? number_format($row['averages']['damage_yellow'], 2) : '-' }}
                    </td>
                    <td class="text-right">
                        {{ $row['averages']['under_milled'] > 0 ? number_format($row['averages']['under_milled'], 2) : '-' }}
                    </td>
                    <td class="text-right">
                        {{ $row['averages']['chalky'] > 0 ? number_format($row['averages']['chalky'], 2) : '-' }}
                    </td>
                </tr>
            @endif
        @endforeach

        @if (count($rows) > 0)
            <tr class="grand-total-row total-row font-weight-bold">
                <td colspan="3" class="font-weight-bold text-left">Grand Total</td>
                <td class="text-right font-weight-bold">{{ number_format($totalNetWeight, 0, '.', ',') }}</td>
                <td class="text-right font-weight-bold">
                    {{ $overallAverages['inner_broken'] > 0 ? number_format($overallAverages['inner_broken'], 2) : '-' }}
                </td>
                <td class="text-right font-weight-bold">
                    {{ $overallAverages['inner_moisture'] > 0 ? number_format($overallAverages['inner_moisture'], 2) : '-' }}
                </td>
                <td class="text-right font-weight-bold">
                    {{ $overallAverages['damage_creamish'] > 0 ? number_format($overallAverages['damage_creamish'], 2) : '-' }}
                </td>
                <td class="text-right font-weight-bold">
                    {{ $overallAverages['damage_yellow'] > 0 ? number_format($overallAverages['damage_yellow'], 2) : '-' }}
                </td>
                <td class="text-right font-weight-bold">
                    {{ $overallAverages['under_milled'] > 0 ? number_format($overallAverages['under_milled'], 2) : '-' }}
                </td>
                <td class="text-right font-weight-bold">
                    {{ $overallAverages['chalky'] > 0 ? number_format($overallAverages['chalky'], 2) : '-' }}
                </td>
            </tr>
        @endif
    @endslot
</x-sticky-table>
