<x-sticky-table :items="$data" :leftSticky="1" :rightSticky="0" :emptyMessage="'No records found'" :pagination="false">
    @slot('head')
        <th>Date</th>
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

                $formattedDate = $row->summary_date ? \Carbon\Carbon::parse($row->summary_date)->format('d M y') : 'N/A';
            @endphp
            <tr>
                <td>{{ $formattedDate }}</td>
                <td>{{ $truckArrived }}</td>
                <td>{{ $totalUnloaded }}</td>
                <td>{{ $fully_approved }}</td>
                <td>{{ $halfRejected }}</td>
                <td>{{ $fullRejected }}</td>
                <td>{{ $inProcess }}</td>
            </tr>
        @endforeach

        @if (count($data) > 0)
            <tr class="font-weight-bold bg-light">
                <td><strong>Total</strong></td>
                <td><strong>{{ $sumTruckArrived }}</strong></td>
                <td><strong>{{ $sumTotalUnloaded }}</strong></td>
                <td><strong>{{ $sumFully_approved }}</strong></td>
                <td><strong>{{ $sumHalfRejected }}</strong></td>
                <td><strong>{{ $sumFullRejected }}</strong></td>
                <td><strong>{{ $sumInProcess }}</strong></td>
            </tr>
        @endif
    @endslot
</x-sticky-table>
