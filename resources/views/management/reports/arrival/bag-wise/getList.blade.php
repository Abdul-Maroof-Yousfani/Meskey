<div class="table-responsive">
    <table class="table table-bordered m-0" id="exportableTable">
        <thead class="thead-light">
            <tr>
                <th style="width: 60px;" class="text-center">#</th>
                <th style="width: 220px;">Bag Type</th>
                <th>Packing</th>
                <th class="text-center" style="width: 140px;">Total Tickets</th>
                <th class="text-right" style="width: 160px;">Filled Bags</th>
                {{-- <th class="text-right" style="width: 160px;">Total Bags</th> --}}
                {{-- <th class="text-right" style="width: 200px;">Total Net Weight (kg)</th> --}}
            </tr>
        </thead>
        <tbody>
            @php $sNo = 1; @endphp
            @forelse ($groupedData as $group)
                @php $packingCount = count($group['packings']); @endphp
                @foreach ($group['packings'] as $index => $packing)
                    <tr>
                        @if ($index === 0)
                            <td rowspan="{{ $packingCount }}" class="text-center font-weight-bold"
                                style="background-color: #f8f9fa; vertical-align: middle;">
                                {{ $sNo++ }}
                            </td>
                            <td rowspan="{{ $packingCount }}" class="font-weight-bold text-dark"
                                style="background-color: #e8f5e8; vertical-align: middle; font-size: 14px;">
                                <div class="d-flex align-items-center">
                                    <span class="mr-1" style="color: #2e7d32; font-size: 16px;">&bull;</span>
                                    <span>{{ $group['bag_type_name'] }}</span>
                                </div>
                            </td>
                        @endif
                        <td style="vertical-align: middle;">
                            <span style="font-size: 12px; font-weight: 500; padding: 4px 10px;">
                                {{ $packing['packing_name'] }}
                            </span>
                        </td>
                        <td class="text-center" style="vertical-align: middle;">
                            {{ number_format($packing['ticket_count']) }}
                        </td>
                        <td class="text-right font-weight-bold" style="vertical-align: middle;">
                            {{ number_format($packing['total_filled_bags']) }}
                        </td>
                        {{-- <td class="text-right font-weight-bold" style="vertical-align: middle;">
                            {{ number_format($packing['total_bags']) }}
                        </td> --}}
                        {{-- <td class="text-right" style="vertical-align: middle;">
                            {{ number_format($packing['total_net_weight'], 2) }}
                        </td> --}}
                    </tr>
                @endforeach
                {{-- Subtotal row for this Bag Type --}}
                <tr class="font-weight-bold" style="background-color: #f1f8e9; border-bottom: 2px solid #a5d6a7;">
                    <td colspan="3" class="text-right font-weight-bold">
                        <strong>Subtotal ({{ $group['bag_type_name'] }}):</strong>
                    </td>
                    <td class="text-center font-weight-bold">
                        <strong>{{ number_format($group['total_tickets']) }}</strong>
                    </td>
                    <td class="text-right font-weight-bold">
                        <strong>{{ number_format($group['subtotal_filled_bags']) }}</strong>
                    </td>
                    {{-- <td class="text-right font-weight-bold">
                        <strong>{{ number_format($group['subtotal_bags']) }}</strong>
                    </td> --}}
                    {{-- <td class="text-right font-weight-bold">
                        <strong>{{ number_format($group['subtotal_net_weight'], 2) }}</strong>
                    </td> --}}
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center py-4 text-muted">
                        No records found
                    </td>
                </tr>
            @endforelse
        </tbody>
        @if (count($groupedData) > 0)
            <tfoot style="background-color: #e0f2f1; border-top: 2px solid #004d40;">
                <tr class="font-weight-bold" style="font-size: 14px;">
                    <td colspan="3" class="text-left font-weight-bold" style="color: #004d40; vertical-align: middle;">
                        <strong>Grand Total:</strong>
                    </td>
                    <td class="text-center font-weight-bold" style="color: #004d40; vertical-align: middle;">
                        <strong>{{ number_format($grandTotalTickets) }}</strong>
                    </td>
                    <td class="text-right font-weight-bold" style="color: #004d40; vertical-align: middle;">
                        <strong>{{ number_format($grandTotalFilledBags) }}</strong>
                    </td>
                    {{-- <td class="text-right font-weight-bold" style="color: #004d40; vertical-align: middle;">
                        <strong>{{ number_format($grandTotalBags) }}</strong>
                    </td> --}}
                    {{-- <td class="text-right font-weight-bold" style="color: #004d40; vertical-align: middle;">
                        <strong>{{ number_format($grandTotalNetWeight, 2) }}</strong>
                    </td> --}}
                </tr>
            </tfoot>
        @endif
    </table>
</div>
