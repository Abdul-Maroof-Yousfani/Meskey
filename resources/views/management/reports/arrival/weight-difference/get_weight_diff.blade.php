<x-sticky-table :items="$arrival_data" :leftSticky="0" :rightSticky="0" :emptyMessage="'No records found'" :pagination="false">
    @slot('head')
        <th>Products</th>
        <th>Sum of Loading Weight</th>
        <th>Sum of Net Weight</th>
        <th>Sum of Weight Difference</th>
    @endslot

    @slot('body')
        @php
            $totalLoadingWeight = 0;
            $totalNetWeight = 0;
            $totalWeightDiff = 0;
        @endphp

        @foreach ($arrival_data as $row)
            @php
                $loadingWeight = (float) ($row->total_net_weight ?? 0);
                $netWeight = (float) ($row->total_loading_weight ?? 0);
                $weightDiff = $loadingWeight - $netWeight;

                $totalLoadingWeight += $loadingWeight;
                $totalNetWeight += $netWeight;
                $totalWeightDiff += $weightDiff;
            @endphp
            <tr>
                <td>{{ $row->qcProduct->name ?? '' }}</td>
                <td>{{ number_format($loadingWeight, 2) }}</td>
                <td>{{ number_format($netWeight, 2) }}</td>
                <td>{{ number_format($weightDiff, 2) }}</td>
            </tr>
        @endforeach

        @if (count($arrival_data) > 0)
            <tr class="font-weight-bold bg-light">
                <td><strong>Total</strong></td>
                <td><strong>{{ number_format($totalLoadingWeight, 2) }}</strong></td>
                <td><strong>{{ number_format($totalNetWeight, 2) }}</strong></td>
                <td><strong>{{ number_format($totalWeightDiff, 2) }}</strong></td>
            </tr>
        @endif
    @endslot
</x-sticky-table>

