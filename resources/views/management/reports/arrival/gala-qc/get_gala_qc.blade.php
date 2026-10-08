<x-sticky-table :items="collect($galaData)" :leftSticky="1" :rightSticky="0" :emptyMessage="'No records found'" :pagination="false">
    @slot('head')
        <th class="text-left" style="min-width: 140px;">Galaa #</th>
        <th class="text-right" style="min-width: 130px;">Net Weight</th>
        @foreach ($product_slab_types as $slab)
            <th class="text-right" style="min-width: 160px;">
                {{ $slab->name }} @if(!empty($slab->qc_symbol))({{ $slab->qc_symbol }})@endif
            </th>
        @endforeach
    @endslot

    @slot('body')
        @foreach ($galaData as $row)
            <tr>
                <td class="font-weight-bold text-left">{{ $row['gala_name'] }}</td>
                <td class="text-right">{{ number_format($row['net_weight'], 0, '.', ',') }}</td>
                @foreach ($product_slab_types as $slab)
                    @php
                        $val = $row['slab_averages'][$slab->id] ?? 0;
                    @endphp
                    <td class="text-right">
                        {{ $val > 0 ? (floor($val) == $val ? (int) $val : number_format($val, 2)) : '-' }}
                    </td>
                @endforeach
            </tr>
        @endforeach

        @if (count($galaData) > 0)
            <tr class="total-row font-weight-bold">
                <td class="font-weight-bold text-left">Total</td>
                <td class="text-right font-weight-bold">{{ number_format($totalNetWeight, 0, '.', ',') }}</td>
                @foreach ($product_slab_types as $slab)
                    @php
                        $oVal = $overallSlabAverages[$slab->id] ?? 0;
                    @endphp
                    <td class="text-right font-weight-bold">
                        {{ $oVal > 0 ? (floor($oVal) == $oVal ? (int) $oVal : number_format($oVal, 2)) : '-' }}
                    </td>
                @endforeach
            </tr>
        @endif
    @endslot
</x-sticky-table>
