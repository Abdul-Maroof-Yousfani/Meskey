<x-sticky-table :items="collect($stationData)" :leftSticky="2" :rightSticky="0" :emptyMessage="'No records found'" :pagination="false">
    @slot('head')
        <th>S. No</th>
        <th>Station</th>
        <th>Total Trucks</th>
        <th>KG Received</th>
        @foreach ($product_slab_types as $slab)
            <th>{{ $slab->name }}</th>
        @endforeach
    @endslot

    @slot('body')
        @php
            $grandTotalTrucks = 0;
            $grandTotalKg = 0;
            $slabTotals = [];
            foreach ($product_slab_types as $slab) {
                // dd($slab, $product_slab_types);
                $slabTotals[$slab->id] = [];
            }
        @endphp

        @foreach ($stationData as $index => $row)
            @php
                $grandTotalTrucks += $row['total_trucks'];
                $grandTotalKg += $row['kg_received'];
            @endphp
            <tr>
                <td>{{ $index + 1 }}</td>
                <td><strong>{{ $row['station'] }}</strong></td>
                <td>{{ number_format($row['total_trucks']) }}</td>
                <td>{{ number_format($row['kg_received'], 0, '.', '') }}</td>
                @foreach ($product_slab_types as $slab)
                    @php
                        $val = $row['slab_averages'][$slab->id] ?? 0;
                        $slabSymbol = $slab->qc_symbol ?? '';
                        if ($val > 0) {
                            $slabTotals[$slab->id][] = $val;
                        }
                    @endphp
                    <td>
                        {{ $val > 0 ? (floor($val) == $val ? (int)$val : number_format($val, 1)) . $slabSymbol : 0 }}
                    </td>
                @endforeach
            </tr>
        @endforeach

        @if (count($stationData) > 0)
            <tr class="font-weight-bold bg-light">
                <td colspan="2" class="text-right"><strong>Main Total:</strong></td>
                <td><strong>{{ number_format($grandTotalTrucks) }}</strong></td>
                <td><strong>{{ number_format($grandTotalKg, 0, '.', '') }}</strong></td>
                @foreach ($product_slab_types as $slab)
                    @php
                        $overallAvg = $overallSlabAverages[$slab->id] ?? 0;
                        $slabSymbol = $slab->qc_symbol ?? '';
                    @endphp
                    <td>
                        <strong>{{ $overallAvg > 0 ? (floor($overallAvg) == $overallAvg ? (int)$overallAvg : number_format($overallAvg, 1)) . $slabSymbol : 0 }}</strong>
                    </td>
                @endforeach
            </tr>
        @endif
    @endslot
</x-sticky-table>
