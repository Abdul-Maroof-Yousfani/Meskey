<div>
    <!-- DC Information Section -->
    <div class="row">
        <div class="col-12">
            <h6 class="header-heading-sepration">
                DC Information
            </h6>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">DC No</label>
                <input type="text" class="form-control bg-light" value="{{ $logisticsBill->dc_no }}" readonly placeholder="DC No">
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Date</label>
                <input type="text" class="form-control bg-light" value="{{ $logisticsBill->dc_date?->format('d-M-Y') ?? 'N/A' }}" readonly placeholder="Date">
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Party (Customer)</label>
                <input type="text" class="form-control bg-light" value="{{ $logisticsBill->deliveryChallan?->customer?->name ?? 'N/A' }}" readonly>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Truck No</label>
                <input type="text" class="form-control bg-light" value="{{ $logisticsBill->truck_number ?? 'N/A' }}" readonly>
            </div>
        </div>
    </div>

    <!-- Other Details Section -->
    <div class="row">
        <div class="col-12">
            <h6 class="header-heading-sepration">
                Other Details
            </h6>
        </div>
        
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Total Dispatch Weight</label>
                <input type="text" class="form-control bg-light font-weight-bold" value="{{ number_format($logisticsBill->items->sum('dispatch_weight'), 2, '.', '') }}" readonly>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Receiving Weight (Total)</label>
                <input type="number" class="form-control bg-light font-weight-bold" value="{{ $logisticsBill->arrived_weight }}" readonly>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Weight Difference</label>
                @php
                    $diffWeight = floatval($logisticsBill->items->sum('dispatch_weight')) - floatval($logisticsBill->arrived_weight);
                @endphp
                <input type="text" class="form-control bg-light font-weight-bold {{ $diffWeight > 0 ? 'text-danger' : 'text-success' }}" value="{{ number_format($diffWeight, 2, '.', '') }}" readonly>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Exempted Weight</label>
                <input type="number" class="form-control bg-light font-weight-bold" value="{{ $logisticsBill->exempted_weight }}" readonly>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Payment Weight</label>
                <input type="number" class="form-control bg-light font-weight-bold" value="{{ $logisticsBill->payment_weight }}" readonly>
            </div>
        </div>
    </div>

    <!-- Transporter Details Section -->
    <div class="row mt-3">
        <div class="col-12">
            <h6 class="header-heading-sepration">
                Transporter Details
            </h6>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Transporter</label>
                @php
                    $transporterName = \App\Models\Master\Transporter::find($logisticsBill->transporter)?->name ?? 'N/A';
                @endphp
                <input type="text" class="form-control bg-light" value="{{ $transporterName }}" readonly>
            </div>
        </div>
        @php
            $salesOrder = $logisticsBill->deliveryChallan->delivery_order->first()?->salesOrder;
            $logistics = $salesOrder?->logistics->first();
            $logisticsItem = $logistics ? $logistics->items()->where('transporter_id', $logisticsBill->transporter)->first() : null;
            $transporterRate = $logisticsItem?->rate ?? 'N/A';
            $transporterRateType = $logisticsItem?->rate_type ? ucfirst(str_replace('_', ' ', $logisticsItem->rate_type)) : 'N/A';
        @endphp
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Transporter Rate</label>
                <input type="text" class="form-control bg-light" value="{{ $transporterRate }}" readonly>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Transporter Rate Type</label>
                <input type="text" class="form-control bg-light" value="{{ $transporterRateType }}" readonly>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Deduction</label>
                <input type="number" class="form-control bg-light font-weight-bold" value="{{ $logisticsBill->transporter_deduction ?? 0 }}" readonly>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Transporter Amount</label>
                <input type="number" class="form-control bg-light font-weight-bold" value="{{ $logisticsBill->transporter_amount }}" readonly>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Total Amount</label>
                @php
                    $netTransporterAmount = floatval($logisticsBill->transporter_amount ?? 0) - floatval($logisticsBill->transporter_deduction ?? 0);
                @endphp
                <input type="number" class="form-control bg-light font-weight-bold text-primary" value="{{ number_format($netTransporterAmount, 2, '.', '') }}" readonly>
            </div>
        </div>
    </div>

    <!-- Unloading Labour Section -->
    <div class="row mt-3">
        <div class="col-12">
            <h6 class="header-heading-sepration">
                Unloading Labour
            </h6>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered table-sm">
            <thead class="bg-light">
                <tr>
                    <th>Item Name</th>
                    <th>Bag Size</th>
                    <th>Dispatch Weight</th>
                    <th>No. of Bags</th>
                    <th>Unloading Labour Rate</th>
                    <th>Total Labour Amt</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $groupedItems = [];
                    foreach($logisticsBill->items as $item) {
                        $bagSize = $item->deliveryChallanData?->bag_size ?? 'N/A';
                        $itemName = $item->item_name;
                        $key = $itemName . '_' . $bagSize;
                        if(!isset($groupedItems[$key])) {
                            $groupedItems[$key] = [
                                'item_name' => $itemName,
                                'bag_size' => $bagSize,
                                'dispatch_weight' => 0,
                                'no_of_bags' => 0,
                                'unloading_labour_rate' => $item->unloading_labour_rate,
                            ];
                        }
                        $groupedItems[$key]['dispatch_weight'] += floatval($item->dispatch_weight);
                        $groupedItems[$key]['no_of_bags'] += floatval($item->deliveryChallanData?->no_of_bags ?? 0);
                    }
                @endphp

                @foreach($groupedItems as $index => $group)
                    <tr>
                        <td>
                            <input type="text" value="{{ $group['item_name'] }}" class="form-control form-control-sm bg-light" readonly>
                        </td>
                        <td>
                            <input type="text" value="{{ $group['bag_size'] }}" class="form-control form-control-sm bg-light" readonly>
                        </td>
                        <td>
                            <input type="number" value="{{ $group['dispatch_weight'] }}" class="form-control form-control-sm bg-light" readonly>
                        </td>
                        <td>
                            <input type="number" value="{{ $group['no_of_bags'] }}" class="form-control form-control-sm bg-light" readonly>
                        </td>
                        <td>
                            <input type="number" value="{{ $group['unloading_labour_rate'] }}" class="form-control form-control-sm bg-light" readonly>
                        </td>
                        <td>
                            <input type="number" value="{{ floatval($group['no_of_bags']) * floatval($group['unloading_labour_rate']) }}" class="form-control form-control-sm bg-light font-weight-bold" readonly>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="row mt-2">
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Paid By</label>
                <input type="text" class="form-control bg-light font-weight-bold" value="{{ $logisticsBill->unloading_paid_by ?? 'N/A' }}" readonly>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Total Labour Amt</label>
                <input type="number" class="form-control bg-light font-weight-bold" value="{{ $logisticsBill->labour_amount }}" readonly>
            </div>
        </div>
    </div>

    <!-- Weighbridges Section -->
    <div class="row mt-3">
        <div class="col-12">
            <h6 class="header-heading-sepration mb-2">Weighbridges</h6>
        </div>
    </div>
    <div id="weighbridges-container">
        @if($logisticsBill->weighbridges->count() > 0)
            @foreach($logisticsBill->weighbridges as $index => $wb)
            <div class="row weighbridge-row mb-2">
                <div class="col-md-6">
                    <input type="text" value="{{ $wb->name }}" class="form-control bg-light" readonly placeholder="Weighbridge Name">
                </div>
                <div class="col-md-6">
                    <input type="number" value="{{ $wb->amount }}" class="form-control bg-light font-weight-bold" readonly placeholder="Weighbridge Amount">
                </div>
            </div>
            @endforeach
        @else
            <div class="row weighbridge-row mb-2">
                <div class="col-12">
                    <p class="text-muted font-italic mb-0">No weighbridge records attached.</p>
                </div>
            </div>
        @endif
    </div>

    <div class="row mt-2">
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Paid By</label>
                <input type="text" class="form-control bg-light font-weight-bold" value="{{ $logisticsBill->weighbridge_paid_by ?? 'N/A' }}" readonly>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Total Weighbridge Amt</label>
                <input type="number" class="form-control bg-light font-weight-bold" value="{{ $logisticsBill->weighbridge_amount }}" readonly placeholder="Total Weighbridge Amount">
            </div>
        </div>
    </div>

    <div class="row bottom-button-bar mt-3">
        <div class="col-12">
            <a type="button" class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton">Close</a>
        </div>
    </div>
</div>
