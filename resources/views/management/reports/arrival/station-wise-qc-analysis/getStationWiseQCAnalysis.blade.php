<x-sticky-table :items="collect($stationData)" :leftSticky="2" :rightSticky="0" :emptyMessage="'No records found'"
    :pagination="false">
    @slot('head')
    {{-- <th>S. No</th> --}}
    <th>Station</th>
    <th>COMMODITY</th>
    <th>Total Trucks</th>
    <th>Total full unload</th>
    <th>Total half rejected </th>
    <th>Total full rejected</th>
    <th>KG Received</th>
    @foreach ($product_slab_types as $slab)
        <th>{{ $slab->name }} ({{ $slab->qc_symbol ?? '' }})</th>
    @endforeach
    @endslot

    @slot('body')
    @php
        $grandTotalTrucks = 0;
        $grandTotalFullUnload = 0;
        $grandTotalHalfRejected = 0;
        $grandTotalFullRejected = 0;
        $grandTotalKg = 0;
        $slabTotals = [];
        foreach ($product_slab_types as $slab) {
            // dd($slab, $product_slab_types);
            $slabTotals[$slab->id] = [];
        }

        $stationData = array_values($stationData);
        $stationRowspans = [];
        $totalStationRows = count($stationData);
        for ($i = 0; $i < $totalStationRows; $i++) {
            $stationName = $stationData[$i]['station'] ?? '';
            if ($i === 0 || ($stationData[$i - 1]['station'] ?? '') !== $stationName) {
                $span = 1;
                while ($i + $span < $totalStationRows && ($stationData[$i + $span]['station'] ?? '') === $stationName) {
                    $span++;
                }
                $stationRowspans[$i] = $span;
            }
        }
    @endphp

    @foreach ($stationData as $index => $row)
        @php
            $grandTotalTrucks += $row['total_trucks'];
            $grandTotalFullUnload += ($row['total_full_unload'] ?? 0);
            $grandTotalHalfRejected += ($row['total_half_rejected'] ?? 0);
            $grandTotalFullRejected += ($row['total_full_rejected'] ?? 0);
            $grandTotalKg += $row['kg_received'];
        @endphp
        <tr>
            {{-- <td>{{ $index + 1 }}</td> --}}
            @if (isset($stationRowspans[$index]))
                <td rowspan="{{ $stationRowspans[$index] }}" style="background-color: #fff; vertical-align: middle;">
                    <strong>{{ $row['station'] }}</strong>
                </td>
            @endif
            <td>{{ $row['commodity'] }}</td>
            <td>{{ number_format($row['total_trucks']) }}</td>
            <td>{{ number_format($row['total_full_unload'] ?? 0) }}</td>
            <td>{{ number_format($row['total_half_rejected'] ?? 0) }}</td>
            <td>{{ number_format($row['total_full_rejected'] ?? 0) }}</td>
            <td>{{ number_format($row['kg_received'], 0, '.', '') }}</td>
            @foreach ($product_slab_types as $slab)
                @php
                    $val = $row['slab_averages'][$slab->id] ?? 0;
                    // $slabSymbol = $slab->qc_symbol ?? '';
                    // $val > 0 ? (floor($val) == $val ? (int) $val : number_format($val, 3)) . $slabSymbol : 0
                    if ($val > 0) {
                        $slabTotals[$slab->id][] = $val;
                    }
                @endphp
                <td>
                    {{ $val > 0 ? (floor($val) == $val ? (int) $val : number_format($val, 3)) : 0 }}
                </td>
            @endforeach
        </tr>
    @endforeach

    @if (count($stationData) > 0)
        <tr class="font-weight-bold bg-light">
            <td colspan="2" class="text-right"><strong>Main Total:</strong></td>
            <td><strong>{{ number_format($grandTotalTrucks) }}</strong></td>
            <td><strong>{{ number_format($grandTotalFullUnload) }}</strong></td>
            <td><strong>{{ number_format($grandTotalHalfRejected) }}</strong></td>
            <td><strong>{{ number_format($grandTotalFullRejected) }}</strong></td>
            <td><strong>{{ number_format($grandTotalKg, 0, '.', '') }}</strong></td>
            @foreach ($product_slab_types as $slab)
                @php
                    $overallAvg = $overallSlabAverages[$slab->id] ?? 0;
                    // $slabSymbol = $slab->qc_symbol ?? '';
                    // $overallAvg > 0 ? (floor($overallAvg) == $overallAvg ? (int) $overallAvg : number_format($overallAvg, 3)) . $slabSymbol : 0
                @endphp
                <td>
                    <strong>{{ $overallAvg > 0 ? (floor($overallAvg) == $overallAvg ? (int) $overallAvg : number_format($overallAvg, 3)) : 0 }}</strong>
                </td>
            @endforeach
        </tr>
    @endif
    @endslot
</x-sticky-table>