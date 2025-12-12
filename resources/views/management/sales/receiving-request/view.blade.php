<div class="p-2">
    <!-- DC Information Section -->
    <div class="row">
        <div class="col-12">
            <h6 class="header-heading-sepration">
                DC Information
            </h6>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label class="font-weight-bold">DC No</label>
                <input type="text" class="form-control bg-light" value="{{ $receivingRequest->dc_no }}" readonly placeholder="DC No">
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label class="font-weight-bold">Date</label>
                <input type="text" class="form-control bg-light" value="{{ $receivingRequest->dc_date?->format('d-M-Y') ?? 'N/A' }}" readonly placeholder="Date">
            </div>
        </div>
    </div>

    <!-- DC Details Section -->
    <div class="row">
        <div class="col-12">
            <h6 class="header-heading-sepration">
                DC Details
            </h6>
        </div>
        @php
            $labourOptions = ['1' => 'Labour 1', '2' => 'Labour 2'];
            $transporterOptions = ['1' => 'Transporter 1', '2' => 'Transporter 2'];
            $weighbridgeOptions = ['1' => 'Weighbridge 1', '2' => 'Weighbridge 2'];
        @endphp
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Labour</label>
                <input type="text" class="form-control bg-light" value="{{ $labourOptions[$receivingRequest->labour] ?? 'N/A' }}" readonly>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Transporter</label>
                <input type="text" class="form-control bg-light" value="{{ $transporterOptions[$receivingRequest->transporter] ?? 'N/A' }}" readonly>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">In-house Weighbridge</label>
                <input type="text" class="form-control bg-light" value="{{ $weighbridgeOptions[$receivingRequest->inhouse_weighbridge] ?? 'N/A' }}" readonly>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Weighbridge Amount</label>
                <input type="text" class="form-control bg-light" value="{{ number_format($receivingRequest->weighbridge_amount, 2) }}" readonly>
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label class="font-weight-bold">Labour Amount</label>
                <input type="text" class="form-control bg-light" value="{{ number_format($receivingRequest->labour_amount, 2) }}" readonly>
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label class="font-weight-bold">Transporter Amount</label>
                <input type="text" class="form-control bg-light" value="{{ number_format($receivingRequest->transporter_amount, 2) }}" readonly>
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label class="font-weight-bold">Inhouse Weighbridge Amount</label>
                <input type="text" class="form-control bg-light" value="{{ number_format($receivingRequest->inhouse_weighbridge_amount, 2) }}" readonly>
            </div>
        </div>
    </div>

    <!-- Weight Information Section -->
    <div class="row">
        <div class="col-12">
            <h6 class="header-heading-sepration">
                Weight Information
            </h6>
        </div>
    </div>

    <!-- Repeated rows for each item -->
    @foreach($receivingRequest->items as $index => $item)
        <div class="row">
            <div class="col-md-2">
                <div class="form-group">
                    <label class="font-weight-bold">Item Name</label>
                    <input type="text" value="{{ $item->item_name }}" class="form-control bg-light" readonly>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label class="font-weight-bold">Dispatch Weight</label>
                    <input type="text" value="{{ number_format($item->dispatch_weight, 2) }}" class="form-control bg-light" readonly>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label class="font-weight-bold">Receiving Weight</label>
                    <input type="text" value="{{ number_format($item->receiving_weight, 2) }}" class="form-control bg-light" readonly>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label class="font-weight-bold">Difference Weight</label>
                    <input type="text" value="{{ number_format($item->difference_weight, 2) }}" class="form-control bg-light" readonly>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label class="font-weight-bold">Seller Portion</label>
                    <input type="text" value="{{ number_format($item->seller_portion, 2) }}" class="form-control bg-light" readonly>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label class="font-weight-bold">Transporter Amount</label>
                    <input type="text" value="{{ number_format($item->remaining_amount, 2) }}" class="form-control bg-light font-weight-bold" readonly>
                </div>
            </div>
        </div>
    @endforeach

    <!-- Summary Section -->
    <div class="row">
        <div class="col-12">
            <h6 class="header-heading-sepration">
                Summary
            </h6>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Total Dispatch Weight</label>
                <input type="text" class="form-control bg-light font-weight-bold" value="{{ number_format($receivingRequest->items->sum('dispatch_weight'), 2) }}" readonly>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Total Receiving Weight</label>
                <input type="text" class="form-control bg-light font-weight-bold" value="{{ number_format($receivingRequest->items->sum('receiving_weight'), 2) }}" readonly>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Total Difference</label>
                <input type="text" class="form-control bg-light font-weight-bold" value="{{ number_format($receivingRequest->items->sum('difference_weight'), 2) }}" readonly>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Total Transporter Amount</label>
                <input type="text" class="form-control bg-light font-weight-bold text-danger" value="{{ number_format($receivingRequest->items->sum('remaining_amount'), 2) }}" readonly>
            </div>
        </div>
    </div>

    <x-approval-status :model="$receivingRequest" />

</div>
