<style>
    .sticky-table tbody tr.location-head-row th {
        position: static;
        z-index: auto;
        background-color: #d6ddec;
        border-bottom: 2px solid #e0e0e0;
    }
    .layout-dark .sticky-table tbody tr.location-head-row th {
        background-color: #101010;
        border-bottom-color: #474748;
    }
</style>
<x-sticky-table :items="$arrival_data" :leftSticky="0" :rightSticky="0" :emptyMessage="'No records found'" :pagination="false">
    @slot('head')
        <th>Products</th>
        <th>No. of Trucks</th>
        <th>Sum of Loading Weight</th>
        <th>Sum of Net Weight</th>
        <th>Sum of Weight Difference</th>
    @endslot

    @slot('body')
        @php
            $totalTrucks = 0;
            $totalLoadingWeight = 0;
            $totalNetWeight = 0;
            $totalWeightDiff = 0;
        @endphp

        @foreach ($arrival_data as $row)
            @php
                $loadingWeight = (float) ($row->total_net_weight ?? 0);
                $netWeight = (float) ($row->total_loading_weight ?? 0);
                $weightDiff = $loadingWeight - $netWeight;

                $totalTrucks += (int) $row->total_trucks;
                $totalLoadingWeight += $loadingWeight;
                $totalNetWeight += $netWeight;
                $totalWeightDiff += $weightDiff;
            @endphp
            <tr>
                <td>{{ $row->qcProduct->name ?? '' }}</td>
                <td>{{ number_format($row->total_trucks) }}</td>
                <td>{{ number_format($loadingWeight, 2) }}</td>
                <td>{{ number_format($netWeight, 2) }}</td>
                <td>{{ number_format($weightDiff, 2) }}</td>
            </tr>
        @endforeach

        {{-- Location wise: each location as a heading, its sub locations below --}}
        @foreach ($location_data ?? [] as $location)
            @php
                $subLocations = $location['sub_locations'];
                $locLoading = $subLocations->sum('net_weight');
                $locNet = $subLocations->sum('loading_weight');
            @endphp
            {{-- Location row styled like the table header (th), but not sticky on scroll --}}
            <tr class="location-head-row">
                <th>{{ $location['name'] }}</th>
                <th>{{ number_format($subLocations->sum('trucks')) }}</th>
                <th>{{ number_format($locLoading, 2) }}</th>
                <th>{{ number_format($locNet, 2) }}</th>
                <th>{{ number_format($locLoading - $locNet, 2) }}</th>
            </tr>
            @foreach ($subLocations as $sub)
                <tr>
                    <td class="pl-4">{{ $sub['name'] }}</td>
                    <td>{{ number_format($sub['trucks']) }}</td>
                    <td>{{ number_format($sub['net_weight'], 2) }}</td>
                    <td>{{ number_format($sub['loading_weight'], 2) }}</td>
                    <td>{{ number_format($sub['net_weight'] - $sub['loading_weight'], 2) }}</td>
                </tr>
            @endforeach
        @endforeach

        @if (count($arrival_data) > 0)
            <tr class="font-weight-bold bg-light">
                <td><strong>Total</strong></td>
                <td><strong>{{ number_format($totalTrucks) }}</strong></td>
                <td><strong>{{ number_format($totalLoadingWeight, 2) }}</strong></td>
                <td><strong>{{ number_format($totalNetWeight, 2) }}</strong></td>
                <td><strong>{{ number_format($totalWeightDiff, 2) }}</strong></td>
            </tr>
        @endif
    @endslot
</x-sticky-table>
