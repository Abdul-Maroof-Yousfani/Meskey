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
                <label class="font-weight-bold">Transporter</label>
                @php
                    $transporterName = \App\Models\Master\Transporter::find($receivingRequest->transporter)?->name ?? 'N/A';
                @endphp
                <input type="text" class="form-control bg-light" value="{{ $transporterName }}" readonly>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Transporter Amount</label>
                <input type="number" class="form-control bg-light" value="{{ $receivingRequest->transporter_amount }}" readonly>
            </div>
        </div>
    </div>

    <!-- Item Information Section -->
    <div class="row mt-3">
        <div class="col-12">
            <h6 class="header-heading-sepration">
                Item Information
            </h6>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered table-sm">
            <thead class="bg-light">
                <tr>
                    <th>DO#</th>
                    <th>Item Name</th>
                    <th>Dispatch Weight</th>
                    <th>No. of Bags</th>
                    <th>Bag Size</th>
                    <th>Unloading Labour Rate</th>
                    <th>Total Labour Amt</th>
                </tr>
            </thead>
            <tbody>
                @foreach($receivingRequest->items as $index => $item)
                    @php
                        $bags = $item->deliveryChallanData?->no_of_bags ?? 0;
                        $doNo = $item->deliveryChallanData?->deliveryOrderData?->delivery_order?->reference_no ?? 'N/A';
                    @endphp
                    <tr>
                        <td>
                            <input type="text" value="{{ $doNo }}" class="form-control form-control-sm bg-light" readonly>
                        </td>
                        <td>
                            <input type="text" value="{{ $item->item_name }}" class="form-control form-control-sm bg-light" readonly>
                        </td>
                        <td>
                            <input type="number" value="{{ $item->dispatch_weight }}" class="form-control form-control-sm bg-light" readonly>
                        </td>
                        <td>
                            <input type="number" value="{{ $bags }}" class="form-control form-control-sm bg-light" readonly>
                        </td>
                        <td>
                            <input type="text" value="{{ $item->deliveryChallanData?->bag_size ?? 'N/A' }}" class="form-control form-control-sm bg-light" readonly>
                        </td>
                        <td>
                            <input type="number" value="{{ $item->unloading_labour_rate }}" class="form-control form-control-sm bg-light" readonly>
                        </td>
                        <td>
                            <input type="number" value="{{ number_format(floatval($bags) * floatval($item->unloading_labour_rate), 2) }}" class="form-control form-control-sm bg-light font-weight-bold" readonly>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
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

    <div class="mt-3">
        <x-approval-status :model="$receivingRequest" :list-refresh="route('sales.get.receiving-request.list')"/>
    </div>
</div>
