<x-sticky-table :items="$contracts" :leftSticky="3" :rightSticky="1" :emptyMessage="'No purchase orders found'">
    @slot('head')
        <th>Contract #</th>
        <th>Commodity</th>
        <th>Supplier Name</th>
        <th>Broker</th>
        <th>Decision Of</th>
        <th>Rate</th>
        <th>Expiry Date</th>
        <th>Sauda Type</th>
        <th>Replacement</th>
        <th>Remarks</th>
        <th>No of Trucks</th>
        <th>Ordered QTY</th>
        <th>Arrived Trucks</th>
        <th>Arrived QTY</th>
        <th>Balance Trucks</th>
        <th>Balance Quantity</th>
        <th>Stock in Transit Trucks</th>
        <th>Rejected Trucks</th>
        <th>Sauda Calc Type</th>
        <th>Status</th>
    @endslot

    @slot('body')
        @foreach ($contracts as $contract)
            @php
                $arrivedTrucks = $contract['closed_arrivals'] ?? 0;
                $orderedTrucks = $contract['no_of_trucks'] ?? 0;
                $rejectedTrucks = $contract['rejected_trucks'] ?? 0;
                $isReplacement = $contract['is_replacement'] ?? 'No';

                if ($isReplacement == 'Yes') {
                    $balanceTrucks = $orderedTrucks - $arrivedTrucks;
                } else {
                    $balanceTrucks = $orderedTrucks - $arrivedTrucks - $rejectedTrucks;
                }

                $arrivedQty = $contract['total_loading_weight'] ?? 0;
                $minQty = $contract['min_quantity'] ?? 0;
                $maxQty = $contract['max_quantity'] ?? 0;
            @endphp
            <tr class="contract-row" data-id="{{ $contract['id'] }}">
                <td>
                    <input type="radio" name="selected_contract" value="{{ $contract['id'] }}"
                        {{ $arrivalTicket->is_ticket_verified == 1 ? 'disabled' : '' }}
                        {{ $contract['id'] == ($arrivalTicket->arrival_purchase_order_id ?? '') ? 'checked' : '' }}>
                    {{ $contract['contract_no'] ?? '-' }}
                </td>
                <td>{{ $contract['qc_product_name'] ?? 'N/A' }}</td>
                <td>{{ $contract['supplier']['name'] ?? 'N/A' }}</td>
                <td>{{ $contract['broker_one_name'] ?? ($contract['broker_two_name'] ?? ($contract['broker_three_name'] ?? 'N/A')) }}
                </td>
                <td>{{ $contract['created_by_user']['name'] ?? 'N/A' }}</td>
                <td>
                    {{ $contract['rate_per_kg'] ?? 'N/A' }}
                    <div class="d-none div-box-b">
                        <small>
                            <strong>KG:</strong> {{ $contract['rate_per_kg'] ?? 0 }}<br>
                            <strong>Mound:</strong> {{ $contract['rate_per_mound'] ?? 0 }}<br>
                            <strong>100KG:</strong> {{ $contract['rate_per_100kg'] ?? 0 }}
                        </small>
                    </div>
                </td>
                <td>{{ isset($contract['delivery_date']) ? \Carbon\Carbon::parse($contract['delivery_date'])->format('d-m-Y') : 'N/A' }}
                </td>
                <td>
                    @if (isset($contract['sauda_type']['name']) && $contract['sauda_type']['name'] == 'Thadda')
                        <span class="badge badge-primary">{{ $contract['sauda_type']['name'] }}</span>
                    @else
                        <span class="badge badge-secondary">{{ $contract['sauda_type']['name'] ?? '' }}</span>
                    @endif
                </td>
                <td>
                    <span class="badge badge-{{ $isReplacement == 'Yes' ? 'success' : 'warning' }}">
                        {{ $isReplacement }}
                    </span>
                </td>
                <td>{{ $contract['remarks'] ?? 'N/A' }}</td>
                <td>{{ $contract['no_of_trucks'] ?? 0 }}</td>
                <td>
                    {{ (isset($contract['min_quantity']) ? intval($contract['min_quantity']) : '-') .
                        ' - ' .
                        (isset($contract['max_quantity']) ? intval($contract['max_quantity']) : '-') }}
                </td>
                <td>{{ $arrivedTrucks }}</td>
                <td>{{ $arrivedQty }}</td>
                <td>{{ $balanceTrucks }}</td>
                <td>
                    {{ ($minQty ?? 0) - ($arrivedQty ?? 0) . ' - ' . (($maxQty ?? 0) - ($arrivedQty ?? 0)) }}
                </td>
                <td>{{ $contract['stock_in_transit_trucks'] ?? 0 }}</td>
                <td>{{ $rejectedTrucks }}</td>
                <td class="text-capitalize">{{ $contract['calculation_type'] ?? '-' }}</td>
                <td>
                    @if (isset($contract['status']) && $contract['status'] == 'completed')
                        <span class="badge badge-success">Completed</span>
                    @else
                        <span
                            class="badge badge-warning">{{ $contract['status'] == 'draft' ? 'Pending' : $contract['status'] }}</span>
                    @endif
                </td>
            </tr>
        @endforeach
    @endslot
</x-sticky-table>
