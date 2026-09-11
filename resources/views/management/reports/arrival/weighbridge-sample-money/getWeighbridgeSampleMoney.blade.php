<x-sticky-table :items="$tickets" :leftSticky="1" :rightSticky="0" :emptyMessage="'No records found'" :pagination="false">
    @slot('head')
        <th>Ticket #</th>
        <th>Entry Date</th>
        <th>Entry Time</th>
        <th>Entry By</th>
        <th>Truck Type</th>
        <th>First Weight</th>
        <th>Second Weight</th>
        <th>Weighbridge Amount</th>
        <th>Sample Amount</th>
        {{-- <th>Bilty</th>
        <th>Loading Weight</th>
        <th>Arrival Slip</th>
        <th>View Complete Details</th> --}}
    @endslot

    @slot('body')
        @php
            $totalFirstWeight = 0;
            $totalSecondWeight = 0;
            $totalWeighbridgeAmount = 0;
            $totalSampleAmount = 0;
        @endphp

        @foreach ($tickets as $row)
            @php
                $isWeighed = !empty($row->first_weight) || !empty($row->second_weight) || $row->firstWeighbridge;
                $weighbridgeAmount = 0;
                if ($isWeighed && $row->freight) {
                    $weighbridgeAmount = $row->freight?->karachi_kanta_charges ?? 0;
                }

                $sampleAmount = $row->sample_money ?? 0;

                $totalFirstWeight += (float) ($row->first_weight ?? 0);
                $totalSecondWeight += (float) ($row->second_weight ?? 0);
                $totalWeighbridgeAmount += (float) $weighbridgeAmount;
                $totalSampleAmount += (float) $sampleAmount;
            @endphp
            <tr>
                <td>#{{ $row->unique_no }}</td>
                <td>{{ $row->created_at ? $row->created_at->format('d-M-y') : 'N/A' }}</td>
                <td>{{ $row->created_at ? $row->created_at->format('g:i:s A') : 'N/A' }}</td>
                <td>{{ $row->creator?->name ?? 'Main Gate' }}</td>
                <td>{{ $row->truckType?->name ?? 'N/A' }}</td>
                <td>{{ $row->firstWeighbridge?->weight ? number_format((float)$row->firstWeighbridge->weight, 0, '.', '') : '0' }}</td>
                <td>{{ $row->secondWeighbridge?->weight ? number_format((float)$row->secondWeighbridge->weight, 0, '.', '') : '0' }}</td>
                <td>{{ $weighbridgeAmount }}</td>
                <td>{{ $sampleAmount }}</td>
                <!-- Action Buttons -->
                {{-- <td>
                    <button class="info p-1 text-center mr-2 position-relative btn" @disabled(
                        !$row->freight ||
                        !$row->freight?->bilty_document
                    )
                        @if ($row->freight && $row->freight?->bilty_document) onclick="openImageModal(['{{
                        asset($row->freight->bilty_document) }}'], 'Ticket: {{ $row->unique_no }}')" @endif>
                        <a href="#"><i class="ft-eye font-medium-3"></i></a>
                    </button>
                </td>
                <td>
                    <button class="info p-1 text-center mr-2 position-relative btn" @disabled(
                        !$row->freight ||
                        !$row->freight?->loading_weight_document
                    )
                        @if ($row->freight && $row->freight?->loading_weight_document) onclick="openImageModal(['{{
                        asset($row->freight->loading_weight_document) }}'], 'Ticket: {{ $row->unique_no }}')" @endif>
                        <a href="#"><i class="ft-eye font-medium-3"></i></a>
                    </button>
                </td>
                <td>
                    <button class="info p-1 text-center mr-2 position-relative btn" @disabled(!$row->arrivalSlip)
                        @if ($row->arrivalSlip) onclick="openModal(this,'{{ route('arrival-slip.edit', $row->arrivalSlip->id)
                        }}','Ticket: {{ $row->unique_no }}', true, '100%')" @endif>
                        <a href="#"><i class="ft-eye font-medium-3"></i></a>
                    </button>
                </td>
                <td>
                    <button class="info p-1 text-center mr-2 position-relative btn"
                        onclick="openModal(this,'{{ route('ticket.show', ['ticket' => $row->id, 'source' => 'contract']) }}','Ticket: {{ $row->unique_no }}', true, '90%')">
                        <a href="#"><i class="ft-eye font-medium-3"></i></a>
                    </button>
                </td> --}}
            </tr>
        @endforeach

        @if ($tickets->count() > 0)
            <tr class="font-weight-bold bg-light">
                <td colspan="5" class="text-right"><strong>Total:</strong></td>
                <td><strong>{{ number_format($totalFirstWeight, 0, '.', '') }}</strong></td>
                <td><strong>{{ number_format($totalSecondWeight, 0, '.', '') }}</strong></td>
                <td><strong>{{ number_format($totalWeighbridgeAmount, 0, '.', '') }}</strong></td>
                <td><strong>{{ number_format($totalSampleAmount, 0, '.', '') }}</strong></td>
            </tr>
        @endif
    @endslot
</x-sticky-table>
