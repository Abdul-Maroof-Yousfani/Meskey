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
                $arrivedTrucksWithoutOwn = $contract['closed_arrivals_without_own'] ?? 0;
                $orderedTrucks = $contract['no_of_trucks'] ?? 0;
                $rejectedTrucks = $contract['rejected_trucks'] ?? 0;
                $isReplacement = $contract['is_replacement'] ?? 'No';
                if($arrivalTicket->arrival_purchase_order_id == $contract['id']){
    $owntruckminuable = $arrivalTicket->closing_trucks_qty ?? 0;
}else{
    $owntruckminuable = 0;
}
                if ($isReplacement == 'Yes') {
                    $balanceTrucks = $orderedTrucks - $arrivedTrucks;
                   // $balanceTrucksWithoutOwn = $orderedTrucks - $arrivedTrucksWithoutOwn;
                    $balanceTrucksWithoutOwn = $orderedTrucks - $arrivedTrucks - $owntruckminuable;
                } else {
                    $balanceTrucks = $orderedTrucks - $arrivedTrucks - $rejectedTrucks;
                 //   $balanceTrucksWithoutOwn = $orderedTrucks - $arrivedTrucksWithoutOwn - $rejectedTrucks;
                    $balanceTrucksWithoutOwn = $orderedTrucks - $arrivedTrucks - $owntruckminuable - $rejectedTrucks;
                }
                $arrivedQty = $contract['total_loading_weight'] ?? 0;
                $minQty = $contract['min_quantity'] ?? 0;
                $maxQty = $contract['max_quantity'] ?? 0;
                $isLinked = $contract['is_linked'] ?? false;

                $hasSelectedFreight = collect($contract['purchase_freights'] ?? [])->contains('is_selected', true);

            @endphp
            <tr class="contract-row {{ $isLinked ? 'table-info' : '' }}" data-id="{{ $contract['id'] }}">
                <td data-ajeeb="{{$owntruckminuable}}">
                    <input type="radio" name="selected_contract" value="{{ $contract['id'] }}"
                        {{ $arrivalTicket->is_ticket_verified == 1 ? 'disabled' : '' }}
                        {{ $hasSelectedFreight || $contract['id'] == ($arrivalTicket->arrival_purchase_order_id ?? '') ? 'checked' : '' }}>
                    {{ $contract['contract_no'] ?? '-' }}
                    @if ($isLinked)
                        {{-- <span class="badge badge-info ml-1">Linked</span> --}}
                    @endif
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
                <td>{{ $balanceTrucks }} <span class="d-none">{{ $balanceTrucksWithoutOwn }}</span> </td>
                <td>
                    {{ ($minQty ?? 0) - ($arrivedQty ?? 0) . ' - ' . (($maxQty ?? 0) - ($arrivedQty ?? 0)) }}
                    <span class="d-none">{{ $contract['sauda_type']['name']??'' }}</span>
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

            @if (($contract['sauda_type']['name'] ?? null) == 'Thadda')
                @if (count($contract['purchase_freights'] ?? []) > 0)
                    <tr class="freight-row" data-contract-id="{{ $contract['id'] }}" style="display: none;">
                        <td colspan="20">
                            <div class="freight-container p-3 bg-light border-left border-primary">
                                <h6 class="mb-3">
                                    <i class="fa fa-truck text-primary"></i>
                                    Purchase Freights for Contract: {{ $contract['contract_no'] }}
                                </h6>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered">
                                        <thead class="thead-light normal">
                                            <tr class="normal">
                                                <th width="5%" class="normal">Select</th>
                                                <th width="15%" class="normal">Truck No</th>
                                                <th width="15%" class="normal">Bilty No</th>
                                                <th width="12%" class="normal">Loading Weight</th>
                                                <th width="12%" class="normal">Loading Date</th>
                                                <th width="15%" class="normal">Station</th>
                                                <th width="10%" class="normal">No of Bags</th>
                                                <th width="16%" class="normal">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody class="normal">
                                            @foreach ($contract['purchase_freights'] as $freight)
                                                <tr
                                                    class="normal freight-item {{ $freight['is_selected'] ? 'table-success' : '' }}">
                                                    <td class="normal">
                                                        <input type="radio" name="selected_freight_{{ $contract['id'] }}"
                                                            value="{{ $freight['id'] }}" class="freight"
                                                            data-contract-id="{{ $contract['id'] }}"
                                                            data-truck-no="{{ $freight['truck_no'] }}"
                                                            data-bilty-no="{{ $freight['bilty_no'] }}"
                                                            {{ $freight['is_selected'] ? 'checked' : '' }}
                                                            {{ $arrivalTicket->is_ticket_verified == 1 ? 'disabled' : '' }}>

                                                    </td>
                                                    <td class="normal">{{ $freight['truck_no'] ?? 'N/A' }}</td>
                                                    <td class="normal">{{ $freight['bilty_no'] ?? 'N/A' }}</td>
                                                    <td class="normal">{{ $freight['loading_weight'] ?? 'N/A' }}</td>
                                                    <td class="normal">{{ $freight['loading_date'] ?? 'N/A' }}</td>
                                                    <td class="normal">{{ $freight['station_name'] ?? 'N/A' }}</td>
                                                    <td class="normal">{{ $freight['no_of_bags'] ?? 'N/A' }}</td>
                                                    <td class="normal">
                                                        @if ($freight['arrival_ticket_id'])
                                                            <span class="badge badge-success">Linked</span>
                                                        @elseif($freight['is_selected'])
                                                            <span class="badge badge-warning">Auto-Selected</span>
                                                        @else
                                                            <span class="badge badge-secondary">Available</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </td>
                    </tr>
                @else
                    <tr class="freight-row" data-contract-id="{{ $contract['id'] }}" style="display: none;">
                        <td colspan="15">
                            <div class="alert alert-warning m-0">
                                <i class="fa fa-exclamation-triangle"></i>
                                There is no freights available against this contract.
                            </div>
                        </td>
                    </tr>
                @endif
            @endif
        @endforeach
    @endslot
</x-sticky-table>

<style>
    .freight-container {
        margin: 5px 0;
        border-radius: 5px;
    }

    .freight-item.table-success {
        background-color: #d4edda !important;
    }

    .toggle-freights {
        transition: all 0.3s ease;
    }

    .toggle-freights.expanded i {
        transform: rotate(180deg);
    }

    .contract-row.table-info {
        background-color: #d1ecf1 !important;
    }
</style>
