<x-sticky-table :items="$data" :leftSticky="1" :rightSticky="0" :emptyMessage="'No records found'" :pagination="false">
    @slot('head')
        <th>Date</th>
        <th>Commodity</th>
        <th>Truck Arrived</th>
        <th>Total Unloaded</th>
        <th>Full Approved</th>
        <th>Half Rejected</th>
        <th>Full rejected</th>
        <th>In Process</th>
    @endslot

    @slot('body')
        @php
            $sumTruckArrived = 0;
            $sumTotalUnloaded = 0;
            $sumFully_approved = 0;
            $sumHalfRejected = 0;
            $sumFullRejected = 0;
            $sumInProcess = 0;
        @endphp

        @foreach ($data as $row)
            @php
                $truckArrived = (int) ($row->truck_arrived ?? 0);
                $totalUnloaded = (int) ($row->total_unloaded ?? 0);
                $fully_approved = (int) ($row->fully_approved ?? 0);
                $halfRejected = (int) ($row->half_rejected ?? 0);
                $fullRejected = (int) ($row->full_rejected ?? 0);
                $inProcess = (int) ($row->in_process ?? 0);

                $sumTruckArrived += $truckArrived;
                $sumTotalUnloaded += $totalUnloaded;
                $sumHalfRejected += $halfRejected;
                $sumFullRejected += $fullRejected;
                $sumInProcess += $inProcess;
                $sumFully_approved += $fully_approved;

                $displayDate = $row->summary_date ?? 'N/A';
            @endphp
            <tr>
                <td class="font-weight-bold">{{ $displayDate }}</td>
                <td class="font-weight-bold text-dark">{{ $row->commodity_name ?? 'N/A' }}</td>
                <td class="font-weight-bold">{{ number_format($truckArrived) }}</td>
                <td>{{ number_format($totalUnloaded) }}</td>
                <td>{{ number_format($fully_approved) }}</td>
                <td>{{ number_format($halfRejected) }}</td>
                <td>{{ number_format($fullRejected) }}</td>
                <td>{{ number_format($inProcess) }}</td>
            </tr>
        @endforeach

        @if (count($data) > 0)
            <tr class="font-weight-bold bg-light">
                <td><strong>Total</strong></td>
                <td></td>
                <td><strong>{{ number_format($sumTruckArrived) }}</strong></td>
                <td><strong>{{ number_format($sumTotalUnloaded) }}</strong></td>
                <td><strong>{{ number_format($sumFully_approved) }}</strong></td>
                <td><strong>{{ number_format($sumHalfRejected) }}</strong></td>
                <td><strong>{{ number_format($sumFullRejected) }}</strong></td>
                <td><strong>{{ number_format($sumInProcess) }}</strong></td>
            </tr>
        @endif
    @endslot
</x-sticky-table>
