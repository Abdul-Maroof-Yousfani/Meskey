<x-sticky-table :items="$contracts" :leftSticky="3" :rightSticky="1" :emptyMessage="'No purchase orders found'">
    @slot('head')
        {{-- <th>Select</th> --}}
        <th>Contract No</th>
        <th>Product</th>
        <th>Sauda Calc Type</th>
        <th>Supplier</th>
        <th>Ordered Qty</th>
        <th>Arrived Qty</th>
        <th>Remaining Qty</th>
        <th>Truck Ordered</th>
        <th>Trucks Arrived</th>
        <th>Remaining Truck</th>
        <th>Remarks</th>
        <th>Is Replacement</th>
        <th>Status</th>
    @endslot

    @slot('body')
        @foreach ($contracts as $contract)
            <tr class="contract-row" data-id="{{ $contract['id'] }}">
                {{-- <td class="text-center">
                </td> --}}
                <td>
                    <input type="radio" name="selected_contract" value="{{ $contract['id'] }}"
                        {{ $arrivalTicket->is_ticket_verified == 1 ? 'disabled' : '' }}
                        {{ $contract['id'] == ($arrivalTicket->arrival_purchase_order_id ?? '') ? 'checked' : '' }}>
                    {{ $contract['contract_no'] ?? '-' }}
                </td>
                <td>{{ $contract['qc_product_name'] ?? '-' }}</td>
                <td class="text-capitalize">{{ $contract['calculation_type'] ?? '-' }}</td>
                <td>{{ $contract['supplier']['name'] ?? '-' }}</td>
                <td>{{ ($contract['min_quantity'] ?? '-') . ' - ' . ($contract['max_quantity'] ?? '-') }}</td>
                <td>{{ $contract['total_loading_weight'] ?? '-' }}</td>
                <td>
                    @if (isset($contract['total_loading_weight']) && $contract['total_loading_weight'] !== null)
                        {{ isset($contract['min_quantity']) && $contract['min_quantity'] !== null ? $contract['min_quantity'] - $contract['total_loading_weight'] : '-' }}
                        -
                        {{ isset($contract['max_quantity']) && $contract['max_quantity'] !== null ? $contract['max_quantity'] - $contract['total_loading_weight'] : '-' }}
                    @else
                        0
                    @endif
                </td>
                <td>{{ $contract['no_of_trucks'] ?? '-' }}</td>
                {{-- <!-- <td>{{ $arrivalTicket->closing_trucks_qty == 0 ? 'N/A' : $arrivalTicket->closing_trucks_qty }}</td> --> --}}
                <td>{{ $contract['closed_arrivals'] ?? 0 }}</td>
                <td>{{ $contract['remaining_trucks'] ?? 0 }}</td>
                <td>{{ $contract['remarks'] ?? '' }}</td>
                <td>
                    @if (isset($contract['is_replacement']) && $contract['is_replacement'] == 'Yes')
                        <span class="badge badge-success">Yes</span>
                    @else
                        <span class="badge badge-warning">No</span>
                    @endif
                </td>
                <td>
                    @if (isset($contract['status']) && $contract['status'] == 'completed')
                        <span class="badge badge-success">Completed</span>
                    @else
                        <span class="badge badge-warning">{{ $contract['status'] ?? 'Pending' }}</span>
                    @endif
                </td>
            </tr>
        @endforeach
    @endslot
</x-sticky-table>
