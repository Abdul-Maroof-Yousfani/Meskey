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
                <input type="text" class="form-control bg-light" value="{{ $receivingRequest->dc_no }}" readonly placeholder="DC No">
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Date</label>
                <input type="text" class="form-control bg-light" value="{{ $receivingRequest->dc_date?->format('d-M-Y') ?? 'N/A' }}" readonly placeholder="Date">
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Party (Customer)</label>
                <input type="text" class="form-control bg-light" value="{{ $receivingRequest->deliveryChallan?->customer?->name ?? 'N/A' }}" readonly>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Truck No</label>
                <input type="text" class="form-control bg-light" value="{{ $receivingRequest->truck_number ?? 'N/A' }}" readonly>
            </div>
        </div>
    </div>

    <!-- Details Section -->
    <div class="row">
        <div class="col-12">
            <h6 class="header-heading-sepration">
                Other Details
            </h6>
        </div>
        
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Total Dispatch Weight</label>
                <input type="text" class="form-control bg-light font-weight-bold" value="{{ number_format($receivingRequest->items->sum('dispatch_weight'), 2, '.', '') }}" readonly>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Receiving Weight (Total)</label>
                <input type="number" class="form-control bg-light" value="{{ $receivingRequest->arrived_weight }}" readonly>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Weight Difference</label>
                <input type="text" class="form-control bg-light font-weight-bold text-danger" value="{{ number_format(floatval($receivingRequest->items->sum('dispatch_weight')) - floatval($receivingRequest->arrived_weight), 2) }}" readonly>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Exempted Weight</label>
                <input type="number" class="form-control bg-light" value="{{ $receivingRequest->exempted_weight }}" readonly>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Payment Weight</label>
                <input type="number" class="form-control bg-light font-weight-bold" value="{{ $receivingRequest->payment_weight }}" readonly>
            </div>
        </div>
    </div>

    <!-- Labour Details Section -->
    <div class="row mt-3">
        <div class="col-12">
            <h6 class="header-heading-sepration">
                Labour Details
            </h6>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Labour</label>
                @php
                    $labourName = \App\Models\Master\Vendor::find($receivingRequest->labour)?->name ?? 'N/A';
                @endphp
                <input type="text" class="form-control bg-light" value="{{ $labourName }}" readonly>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Labour Amount</label>
                <input type="number" class="form-control bg-light" value="{{ $receivingRequest->labour_amount }}" readonly>
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
                    $transporterName = \App\Models\Master\Transporter::find($receivingRequest->transporter)?->name ?? 'N/A';
                @endphp
                <input type="text" class="form-control bg-light" value="{{ $transporterName }}" readonly>
            </div>
        </div>
        @php
            $salesOrder = $receivingRequest->deliveryChallan->delivery_order->first()?->salesOrder;
            $logistics = $salesOrder?->logistics->first();
            $logisticsItem = $logistics ? $logistics->items()->where('transporter_id', $receivingRequest->transporter)->first() : null;
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
                <input type="number" class="form-control bg-light" value="{{ $receivingRequest->transporter_deduction ?? 0 }}" readonly>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Transporter Amount</label>
                <input type="number" class="form-control bg-light" value="{{ $receivingRequest->transporter_amount }}" readonly>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Total Amount</label>
                @php
                    $netTransporterAmount = floatval($receivingRequest->transporter_amount ?? 0) - floatval($receivingRequest->transporter_deduction ?? 0);
                @endphp
                <input type="number" class="form-control bg-light font-weight-bold" value="{{ number_format($netTransporterAmount, 2, '.', '') }}" readonly>
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
                    foreach($receivingRequest->items as $item) {
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
                            <input type="number" value="{{ number_format(floatval($group['no_of_bags']) * floatval($group['unloading_labour_rate']), 2) }}" class="form-control form-control-sm bg-light font-weight-bold" readonly>
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
                <input type="text" class="form-control bg-light" value="{{ $receivingRequest->unloading_paid_by ?? 'N/A' }}" readonly>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Total Labour Amt</label>
                <input type="number" class="form-control bg-light font-weight-bold" value="{{ $receivingRequest->labour_amount }}" readonly>
            </div>
        </div>
    </div>

    <!-- Weighbridges Section -->
    <div class="row mt-3">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="header-heading-sepration mb-0" style="flex:1;">Weighbridges</h6>
            </div>
        </div>
    </div>
    <div>
        @if($receivingRequest->weighbridges->count() > 0)
            @foreach($receivingRequest->weighbridges as $index => $wb)
            <div class="row mb-2">
                <div class="col-md-6">
                    <label class="font-weight-bold">Weighbridge Name</label>
                    <input type="text" value="{{ $wb->name }}" class="form-control bg-light" readonly>
                </div>
                <div class="col-md-6">
                    <label class="font-weight-bold">Amount</label>
                    <input type="text" value="{{ number_format($wb->amount, 2) }}" class="form-control bg-light" readonly>
                </div>
            </div>
            @endforeach
        @else
            <div class="row">
                <div class="col-12">
                    <p class="text-muted">No Weighbridges recorded.</p>
                </div>
            </div>
        @endif
    </div>

    @if($receivingRequest->weighbridges->count() > 0)
    <div class="row mt-2">
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Paid By</label>
                <input type="text" class="form-control bg-light" value="{{ $receivingRequest->weighbridge_paid_by ?? 'N/A' }}" readonly>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Total Weighbridge Amt</label>
                <input type="text" class="form-control bg-light font-weight-bold" value="{{ number_format($receivingRequest->weighbridge_amount, 2) }}" readonly>
            </div>
        </div>
    </div>
    @endif

    <div class=" bottom-button-bar mt-3">
        <x-approval-status :model="$receivingRequest" :list-refresh="route('sales.get.receiving-request.list')"/>
    </div>
</div>
