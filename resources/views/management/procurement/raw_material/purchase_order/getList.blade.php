<x-sticky-table :items="$arrivalPurchaseOrder" :leftSticky="3" :rightSticky="1" :emptyMessage="'No purchase orders found'" :pagination="$arrivalPurchaseOrder->links()">
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
        <th>Action</th>
    @endslot

    @slot('body')
        @foreach ($arrivalPurchaseOrder as $row)
            @php
                // $arrivedTrucks = $row->totalClosingTrucksQty->total_closing_trucks_qty ?? 0;
                $arrivedTrucks = $row->arrivalTickets()->sum('closing_trucks_qty');

                $rejectedTrucks = $row->rejectedArrivalTickets->count();
                $orderedTrucks = $row->no_of_trucks ?? 0;
                if ($row->is_replacement == 0) {
                    $balanceTrucks = $orderedTrucks - $arrivedTrucks - $rejectedTrucks;
                } else {
                    $balanceTrucks = $orderedTrucks - $arrivedTrucks;
                }
            @endphp
            <tr>
                <td>
                    #{{ $row->contract_no }}
                </td>
                <td>{{ $row->product->name ?? 'N/A' }}</td>
                <td>{{ $row->purchase_type == 'gate_buying' ? $row->supplier_name ?? 'N/A' : $row->supplier->name ?? 'N/A' }}
                </td>
                <td>{{ $row->broker_one_name ?? ($row->broker_two_name ?? ($row->broker_three_name ?? 'N/A')) }}</td>
                <td>{{ $row->createdByUser->name ?? 'N/A' }}</td>
                <td>
                    {{ $row->rate_per_kg ?? 'N/A' }}
                    <div class="d-none div-box-b">
                        <small>
                            <strong>KG:</strong> {{ $row->rate_per_kg ?? 0 }}<br>
                            <strong>Mound:</strong> {{ $row->rate_per_mound ?? 0 }}<br>
                            <strong>100KG:</strong> {{ $row->rate_per_100kg ?? 0 }}
                        </small>
                    </div>
                </td>
                <td>{{ $row->delivery_date ? \Carbon\Carbon::parse($row->delivery_date)->format('d-m-Y') : 'N/A' }}</td>
                <td>
                    @if (isset($row->saudaType->name) && $row->saudaType->name == 'Thadda')
                        <span class="badge badge-primary">{{ $row->saudaType->name }}</span>
                    @else
                        <span class="badge badge-secondary">{{ $row->saudaType->name ?? '' }}</span>
                    @endif
                </td>
                <td>
                    <span class="badge badge-{{ $row->is_replacement == 1 ? 'success' : 'warning' }}">
                        {{ $row->is_replacement == 1 ? 'Yes' : 'No' }}
                    </span>
                </td>
                <td>{{ $row->remarks ?? 'N/A' }}</td>
                <td>{{ $row->no_of_trucks ?? 0 }}</td>
                <td>{{ (isset($row->min_quantity) ? intval($row->min_quantity) : '-') . ' - ' . (isset($row->max_quantity) ? intval($row->max_quantity) : '-') }}
                </td>
                <td>{{ $arrivedTrucks }}</td>
                <td>{{ $row->totalArrivedNetWeight->total_arrived_net_weight ?? 0 }}</td>
                <td>{{ $balanceTrucks }}</td>
                <td>
                    {{ (($row->min_quantity ?? 0) - ($row->totalArrivedNetWeight->total_arrived_net_weight ?? 0) ?? '-') . ' - ' . (($row->max_quantity ?? 0) - ($row->totalArrivedNetWeight->total_arrived_net_weight ?? 0) ?? '-') }}
                </td>
                <td>{{ $row->stockInTransitTickets->count() }}</td>
                <td>{{ $rejectedTrucks }}</td>
                <td>
                    <a onclick="openModal(this,'{{ route($row->purchase_type == 'gate_buying' ? 'raw-material.gate-buying.edit' : 'raw-material.purchase-order.edit', $row->id) }}','{{ $row->purchase_type == 'gate_buying' ? 'Edit Gate Buying' : 'Edit Purchase Order' }}')"
                        class="info p-1 text-center mr-2 position-relative">
                        <i class="ft-edit font-medium-3"></i>
                    </a>
                    <a onclick="deletemodal('{{ route('raw-material.purchase-order.destroy', $row->id) }}','{{ route('raw-material.get.purchase-order') }}')"
                        class="danger p-1 text-center mr-2 position-relative">
                        <i class="ft-x font-medium-3"></i>
                    </a>
                </td>
            </tr>
        @endforeach
    @endslot
</x-sticky-table>
