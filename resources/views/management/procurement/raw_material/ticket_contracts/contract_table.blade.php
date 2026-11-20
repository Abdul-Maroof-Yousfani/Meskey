{{-- ===== MOBILE: Contract Cards (sirf mobile par dikhenge) ===== --}}
<div class="tkc-list tkc-contract-cards">
    @forelse ($contracts as $contract)
        @php
            $arrivedTrucks = $contract['closed_arrivals'] ?? 0;
            $arrivedTrucksWithoutOwn = $contract['closed_arrivals_without_own'] ?? 0;
            $orderedTrucks = $contract['no_of_trucks'] ?? 0;
            $rejectedTrucks = $contract['rejected_trucks'] ?? 0;
            $isReplacement = $contract['is_replacement'] ?? 'No';
            if ($arrivalTicket->arrival_purchase_order_id == $contract['id']) {
                $owntruckminuable = $arrivalTicket->closing_trucks_qty ?? 0;
            } else {
                $owntruckminuable = 0;
            }
            if ($isReplacement == 'Yes') {
                $balanceTrucks = $orderedTrucks - $arrivedTrucks;
                $balanceTrucksWithoutOwn = $orderedTrucks - $arrivedTrucks + $owntruckminuable;
            } else {
                $balanceTrucks = $orderedTrucks - $arrivedTrucks - $rejectedTrucks;
                $balanceTrucksWithoutOwn = $orderedTrucks - $arrivedTrucks + $owntruckminuable - $rejectedTrucks;
            }
            $arrivedQty = $contract['total_loading_weight'] ?? 0;
            $minQty = $contract['min_quantity'] ?? 0;
            $maxQty = $contract['max_quantity'] ?? 0;
            $isLinked = $contract['is_linked'] ?? false;
            $hasSelectedFreight = collect($contract['purchase_freights'] ?? [])->contains('is_selected', true);
            $isSelected = $hasSelectedFreight || $contract['id'] == ($arrivalTicket->arrival_purchase_order_id ?? '');
            $isThadda = ($contract['sauda_type']['name'] ?? null) == 'Thadda';
        @endphp

        <div class="tkc-contract-card {{ $isLinked ? 'is-linked' : '' }} {{ $isSelected ? 'is-selected' : '' }}"
             data-contract-id="{{ $contract['id'] }}">
            <div class="tkc-card-header">
                <label class="tkc-card-radio-label mb-0">
                    <input type="radio" 
                           name="mobile_selected_contract" 
                           value="{{ $contract['id'] }}" 
                           class="tkc-contract-radio"
                           {{ $arrivalTicket->is_ticket_verified == 1 ? 'disabled' : '' }}
                           {{ $isSelected ? 'checked' : '' }}>
                    <span>#{{ $contract['contract_no'] ?? '-' }}</span>
                </label>
                <div class="d-flex align-items-center">
                    @if ($isLinked)
                        <span class="badge badge-info mr-1">Linked</span>
                    @endif
                    <span class="badge badge-{{ ($contract['status'] ?? '') == 'completed' ? 'success' : 'warning' }}">
                        {{ ($contract['status'] ?? '') == 'completed' ? 'Completed' : (($contract['status'] ?? '') == 'draft' ? 'Pending' : ($contract['status'] ?? 'Pending')) }}
                    </span>
                </div>
            </div>

            <div class="tkc-commodity font-weight-bold text-primary mb-1">
                {{ $contract['qc_product_name'] ?? 'N/A' }}
            </div>

            <div class="tkc-tags mb-2">
                <span class="tkc-tag"><i class="ft-user mr-1"></i>{{ $contract['supplier']['name'] ?? 'N/A' }}</span>
                <span class="tkc-tag {{ $isThadda ? 'is-warn' : 'is-inner' }}">{{ $contract['sauda_type']['name'] ?? 'N/A' }}</span>
                <span class="tkc-tag is-inner">Rate: {{ $contract['rate_per_kg'] ?? 0 }}/kg</span>
                <span class="tkc-tag {{ $isReplacement == 'Yes' ? 'is-approved' : '' }}">Rep: {{ $isReplacement }}</span>
                @if (isset($contract['delivery_date']))
                    <span class="tkc-tag"><i class="ft-calendar mr-1"></i>Exp: {{ \Carbon\Carbon::parse($contract['delivery_date'])->format('d M Y') }}</span>
                @endif
            </div>

            <div class="tkc-grid">
                <div>
                    <span class="tkc-label">Ordered</span>
                    <span class="tkc-val">{{ $contract['calculation_type'] != 'quantity' ? $contract['no_of_trucks'] . ' Trk' : 'Qty Wise' }} ({{ intval($minQty) }}-{{ intval($maxQty) }} kg)</span>
                </div>
                <div>
                    <span class="tkc-label">Arrived</span>
                    <span class="tkc-val">{{ $arrivedTrucks }} Trk ({{ $arrivedQty }} kg)</span>
                </div>
                <div>
                    <span class="tkc-label">Balance</span>
                    <span class="tkc-val font-weight-bold {{ $balanceTrucks > 0 ? 'text-success' : '' }}">
                        {{ $contract['calculation_type'] != 'quantity' ? $balanceTrucks . ' Trk' : 'N/A' }}
                        <small class="text-muted d-block">({{ ($minQty ?? 0) - ($arrivedQty ?? 0) }} - {{ ($maxQty ?? 0) - ($arrivedQty ?? 0) }} kg)</small>
                    </span>
                </div>
                <div>
                    <span class="tkc-label">Transit / Rej</span>
                    <span class="tkc-val">Transit: {{ $contract['stock_in_transit_trucks'] ?? 0 }} | Rej: {{ $rejectedTrucks }}</span>
                </div>
                <div>
                    <span class="tkc-label">Broker</span>
                    <span class="tkc-val">{{ $contract['broker_one_name'] ?? ($contract['broker_two_name'] ?? ($contract['broker_three_name'] ?? 'N/A')) }}</span>
                </div>
                <div>
                    <span class="tkc-label">Decision Of</span>
                    <span class="tkc-val">{{ $contract['created_by_user']['name'] ?? 'N/A' }}</span>
                </div>
                @if (!empty($contract['remarks']) && $contract['remarks'] !== 'N/A')
                    <div style="grid-column: span 2;">
                        <span class="tkc-label">Remarks</span>
                        <span class="tkc-val">{{ $contract['remarks'] }}</span>
                    </div>
                @endif
            </div>

            <div class="d-flex justify-content-between align-items-center mt-2 pt-2 border-top">
                <small class="text-muted text-capitalize"><i class="ft-info mr-1"></i>{{ $contract['calculation_type'] ?? '-' }}</small>
                <button type="button" class="btn btn-sm btn-outline-info"
                    onclick="openModal(this,'{{ route('raw-material.purchase-order.trucks', $contract['id']) }}','Trucks: Contract #{{ $contract['contract_no'] ?? '' }}', true, '55%')">
                    <i class="fa fa-truck mr-1"></i> View Trucks
                </button>
            </div>

            {{-- Thadda Freight Selection for Mobile --}}
            @if ($isThadda)
                <div class="m-freight-section" data-contract-id="{{ $contract['id'] }}" style="{{ $isSelected ? '' : 'display: none;' }}">
                    <div class="m-freight-title"><i class="fa fa-truck text-primary"></i> Purchase Freights</div>
                    @if (count($contract['purchase_freights'] ?? []) > 0)
                        @foreach ($contract['purchase_freights'] as $freight)
                            <div class="m-freight-item {{ $freight['is_selected'] ? 'is-selected' : '' }}">
                                <label class="m-freight-radio-label mb-0">
                                    <input type="radio" 
                                           name="mobile_selected_freight_{{ $contract['id'] }}" 
                                           value="{{ $freight['id'] }}" 
                                           class="m-freight-radio"
                                           data-contract-id="{{ $contract['id'] }}"
                                           {{ $freight['is_selected'] ? 'checked' : '' }}
                                           {{ $arrivalTicket->is_ticket_verified == 1 ? 'disabled' : '' }}>
                                    <div class="w-100 ml-1">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <strong>Truck #{{ $freight['truck_no'] ?? 'N/A' }}</strong>
                                            @if ($freight['arrival_ticket_id'])
                                                <span class="badge badge-success">Linked</span>
                                            @elseif($freight['is_selected'])
                                                <span class="badge badge-warning">Auto-Selected</span>
                                            @else
                                                <span class="badge badge-secondary">Available</span>
                                            @endif
                                        </div>
                                        <div class="text-muted small mt-1">
                                            Bilty: {{ $freight['bilty_no'] ?? 'N/A' }} &bull; Wt: {{ $freight['loading_weight'] ?? 'N/A' }} kg &bull; Date: {{ $freight['loading_date'] ?? 'N/A' }}
                                        </div>
                                    </div>
                                </label>
                            </div>
                        @endforeach
                    @else
                        <div class="alert alert-warning py-1 px-2 m-0 small">
                            <i class="fa fa-exclamation-triangle mr-1"></i> No freights available against this contract.
                        </div>
                    @endif
                </div>
            @endif
        </div>
    @empty
        <div class="tkc-empty"><i class="ft-inbox"></i> No purchase orders found</div>
    @endforelse
</div>

{{-- ===== DESKTOP: Sticky Table (mobile par chhupa rehta hai) ===== --}}
<div class="tk-table-wrap">
<x-sticky-table :items="$contracts" :leftSticky="3" :rightSticky="2" :emptyMessage="'No purchase orders found'">
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
        <th>Action</th>
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
                    $balanceTrucksWithoutOwn = $orderedTrucks - $arrivedTrucks + $owntruckminuable;
                } else {
                    $balanceTrucks = $orderedTrucks - $arrivedTrucks - $rejectedTrucks;
                 //   $balanceTrucksWithoutOwn = $orderedTrucks - $arrivedTrucksWithoutOwn - $rejectedTrucks;
                    $balanceTrucksWithoutOwn = $orderedTrucks - $arrivedTrucks + $owntruckminuable - $rejectedTrucks;
                }
                $arrivedQty = $contract['total_loading_weight'] ?? 0;
                $minQty = $contract['min_quantity'] ?? 0;
                $maxQty = $contract['max_quantity'] ?? 0;
                $isLinked = $contract['is_linked'] ?? false;

                $hasSelectedFreight = collect($contract['purchase_freights'] ?? [])->contains('is_selected', true);

            @endphp
            <tr class="contract-row {{ $isLinked ? 'table-info' : '' }}" data-id="{{ $contract['id'] }}" data-contractcaltype="{{$contract['calculation_type']}}">
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
                <td>{{ $contract['calculation_type'] != 'quantity' ? $contract['no_of_trucks'] : 'N/A | Quantity Wise' }}</td>
                <td>
                    {{ (isset($contract['min_quantity']) ? intval($contract['min_quantity']) : '-') .
                        ' - ' .
                        (isset($contract['max_quantity']) ? intval($contract['max_quantity']) : '-') }}
                </td>
                <td>{{ $arrivedTrucks }}</td>
                <td>{{ $arrivedQty }}</td>
                <td class="balancetruck">{{ $contract['calculation_type'] != 'quantity' ? $balanceTrucks : 'N/A | Quantity Wise' }} <span class="d-none">{{ $balanceTrucksWithoutOwn }}</span> </td>
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
                <td>
                    <a onclick="openModal(this,'{{ route('raw-material.purchase-order.trucks', $contract['id']) }}','Trucks: Contract #{{ $contract['contract_no'] ?? '' }}', true, '55%')"
                        class="warning p-1 text-center position-relative" title="View Trucks" style="cursor: pointer;">
                        <i class="fa fa-truck font-medium-3"></i>
                    </a>
                </td>
            </tr>

            @if (($contract['sauda_type']['name'] ?? null) == 'Thadda')
                @if (count($contract['purchase_freights'] ?? []) > 0)
                    <tr class="freight-row" data-contract-id="{{ $contract['id'] }}" style="display: none;">
                        <td colspan="21">
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
</div>

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

    /* Mobile Contract Card styling */
    .tkc-contract-cards {
        display: none;
    }
    @media (max-width: 767.98px) {
        .tkc-contract-cards {
            display: block !important;
        }
        .tkc-contract-card {
            border: 2px solid #e2e8f0;
            border-radius: 14px;
            margin-bottom: 12px;
            padding: 12px 14px;
            background: #fff;
            transition: all .2s ease;
            position: relative;
        }
        .tkc-contract-card.is-selected {
            border-color: #27489a;
            background: #f8faff;
            box-shadow: 0 4px 14px rgba(39, 72, 154, 0.12);
        }
        .tkc-contract-card.is-linked {
            border-color: #17a2b8;
        }
        .tkc-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            margin-bottom: 8px;
        }
        .tkc-card-radio-label {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 0;
            font-weight: 700;
            font-size: 15px;
            color: #1e293b;
            cursor: pointer;
        }
        .tkc-card-radio-label input[type="radio"] {
            width: 18px;
            height: 18px;
            accent-color: #27489a;
        }
        .m-freight-section {
            margin-top: 10px;
            padding: 10px;
            border-radius: 10px;
            background: #f1f5f9;
            border: 1px solid #cbd5e1;
        }
        .m-freight-title {
            font-size: 13px;
            font-weight: 700;
            color: #334155;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .m-freight-item {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 8px 10px;
            margin-bottom: 6px;
            transition: border-color .15s;
        }
        .m-freight-item.is-selected {
            border-color: #10b981;
            background: #f0fdf4;
        }
        .m-freight-radio-label {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            margin: 0;
            font-size: 12.5px;
            cursor: pointer;
            width: 100%;
        }
        .m-freight-radio-label input[type="radio"] {
            margin-top: 3px;
            accent-color: #10b981;
        }
    }
</style>
