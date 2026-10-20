<x-sticky-table :items="collect($galaData)" :leftSticky="1" :rightSticky="0" :emptyMessage="'No records found'" :pagination="false">
    @slot('head')
        <th class="text-left" style="min-width: 140px;">Galaa #</th>
        <th class="text-right" style="min-width: 130px;">Net Weight</th>
        <th class="text-right" style="min-width: 160px;">Average of Inner Broken</th>
        <th class="text-right" style="min-width: 180px;">Average of Inner Moisture (15% max)</th>
        <th class="text-right" style="min-width: 200px;">Damage: Creamish / grey / Tip) max 2%</th>
        <th class="text-right" style="min-width: 160px;">Damage: YELLOW max 1%</th>
        <th class="text-right" style="min-width: 160px;">Under Milled (5% max)</th>
        <th class="text-right" style="min-width: 160px;">Average of Chalky (5% max)</th>
    @endslot

    @slot('body')
        @foreach ($galaData as $row)
            <tr>
                <td class="font-weight-bold text-left">{{ $row['gala_name'] }}</td>
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
        @endforeach

        @if (count($galaData) > 0)
            <tr class="total-row font-weight-bold" style="background-color: #f8f9fa;">
                <td class="font-weight-bold text-left">Total</td>
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
