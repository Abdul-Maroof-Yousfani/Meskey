<form action="{{ route('sales.receiving-request.update', $receivingRequest->id) }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    @method('PUT')

    <input type="hidden" id="listRefresh" value="{{ route('sales.get.receiving-request.list') }}" />

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
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Labour</label>
                <select name="labour" id="labour" class="form-control select2">
                    <option value="">Select Labour</option>
                    <option value="1" @selected($receivingRequest->labour == '1')>Labour 1</option>
                    <option value="2" @selected($receivingRequest->labour == '2')>Labour 2</option>
                </select>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Transporter</label>
                <select name="transporter" id="transporter" class="form-control select2">
                    <option value="">Select Transporter</option>
                    <option value="1" @selected($receivingRequest->transporter == '1')>Transporter 1</option>
                    <option value="2" @selected($receivingRequest->transporter == '2')>Transporter 2</option>
                </select>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">In-house Weighbridge</label>
                <select name="inhouse_weighbridge" id="inhouse_weighbridge" class="form-control select2">
                    <option value="">Select Weighbridge</option>
                    <option value="1" @selected($receivingRequest->inhouse_weighbridge == '1')>Weighbridge 1</option>
                    <option value="2" @selected($receivingRequest->inhouse_weighbridge == '2')>Weighbridge 2</option>
                </select>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Inhouse Weighbridge Amount</label>
                <input type="number" class="form-control editable-field" name="inhouse_weighbridge_amount" value="{{ $receivingRequest->weighbridge_amount }}" step="0.01" min="0" placeholder="Inhouse Weighbridge Amount">
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Labour Amount</label>
                <input type="number" class="form-control editable-field" name="labour_amount" value="{{ $receivingRequest->labour_amount }}" step="0.01" min="0" placeholder="Labour Amount">
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Transporter Amount</label>
                <input type="number" class="form-control editable-field" name="transporter_amount" value="{{ $receivingRequest->transporter_amount }}" step="0.01" min="0" placeholder="Transporter Amount">
            </div>
        </div>
        <div class="col-md-3">
            
            <div class="form-group">
                <label class="font-weight-bold">Weighbridge Amount</label>
                <input type="number" class="form-control editable-field" name="weighbridge_amount" value="{{ $receivingRequest->inhouse_weighbridge_amount }}" step="0.01" min="0" placeholder="Weighbridge Amount">
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
        <div class="row item-row" data-item-id="{{ $item->id }}">
            <div class="col-md-2">
                <div class="form-group">
                    <label class="font-weight-bold">Item Name</label>
                    <input type="text" value="{{ $item->item_name }}" class="form-control bg-light" readonly>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label class="font-weight-bold">Dispatch Weight</label>
                    <input type="number" value="{{ $item->dispatch_weight }}" class="form-control bg-light dispatch-weight" readonly>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label class="font-weight-bold">Receiving Weight</label>
                    <input type="number" name="items[{{ $item->id }}][receiving_weight]" 
                           value="{{ $item->receiving_weight }}" 
                           class="form-control editable-field receiving-weight" 
                           step="0.01" min="0"
                           onchange="calculateWeights(this)"
                           onkeyup="calculateWeights(this)">
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label class="font-weight-bold">Difference Weight</label>
                    <input type="number" value="{{ $item->difference_weight }}" class="form-control bg-light difference-weight" readonly>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label class="font-weight-bold">Seller Portion</label>
                    <input type="number" name="items[{{ $item->id }}][seller_portion]" 
                           value="{{ $item->seller_portion }}" 
                           class="form-control editable-field seller-portion" 
                           step="0.01" min="0"
                           onchange="calculateWeights(this)"
                           onkeyup="calculateWeights(this)">
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label class="font-weight-bold">Transporter Amount</label>
                    <input type="number" value="{{ $item->remaining_amount }}" class="form-control bg-light remaining-amount font-weight-bold" readonly>
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
                <input type="text" class="form-control bg-light font-weight-bold" id="summary_dispatch" value="{{ number_format($receivingRequest->items->sum('dispatch_weight'), 2) }}" readonly>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Total Receiving Weight</label>
                <input type="text" class="form-control bg-light font-weight-bold" id="summary_receiving" value="{{ number_format($receivingRequest->items->sum('receiving_weight'), 2) }}" readonly>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Total Difference</label>
                <input type="text" class="form-control bg-light font-weight-bold" id="summary_difference" value="{{ number_format($receivingRequest->items->sum('difference_weight'), 2) }}" readonly>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Total Transporter Amount</label>
                <input type="text" class="form-control bg-light font-weight-bold text-danger" id="summary_remaining" value="{{ number_format($receivingRequest->items->sum('remaining_amount'), 2) }}" readonly>
            </div>
        </div>
    </div>

    <div class="row bottom-button-bar">
        <div class="col-12">
            <a type="button" class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton">Close</a>
            <button type="submit" class="btn btn-primary submitbutton">Save</button>
        </div>
    </div>
</form>

<script>
    $(document).ready(function() {
        // Initialize select2 if needed
        $('.select2').select2();
    });

    function calculateWeights(element) {
        const row = $(element).closest('.item-row');
        const dispatchWeight = parseFloat(row.find('.dispatch-weight').val()) || 0;
        let receivingWeight = parseFloat(row.find('.receiving-weight').val()) || 0;
        let sellerPortion = parseFloat(row.find('.seller-portion').val()) || 0;

        // Validate receiving weight cannot be greater than dispatch weight
        if (receivingWeight > dispatchWeight) {
            Swal.fire({
                icon: 'error',
                title: 'Invalid Input',
                text: 'Receiving weight cannot be greater than dispatch weight!'
            });
            receivingWeight = dispatchWeight;
            row.find('.receiving-weight').val(dispatchWeight.toFixed(2));
        }

        // Calculate difference weight = dispatch - receiving
        const differenceWeight = dispatchWeight - receivingWeight;
        row.find('.difference-weight').val(differenceWeight.toFixed(2));

        // Validate seller portion cannot be greater than difference weight
        if (sellerPortion > differenceWeight) {
            Swal.fire({
                icon: 'error',
                title: 'Invalid Input',
                text: 'Seller portion cannot be greater than difference weight!'
            });
            sellerPortion = differenceWeight;
            row.find('.seller-portion').val(differenceWeight.toFixed(2));
        }

        // Calculate remaining amount = difference - seller portion
        const remainingAmount = differenceWeight - sellerPortion;
        row.find('.remaining-amount').val(remainingAmount.toFixed(2));

        // Update totals
        updateTotals();
    }

    function updateTotals() {
        let totalDispatch = 0;
        let totalReceiving = 0;
        let totalDifference = 0;
        let totalRemaining = 0;

        $('.item-row').each(function() {
            totalDispatch += parseFloat($(this).find('.dispatch-weight').val()) || 0;
            totalReceiving += parseFloat($(this).find('.receiving-weight').val()) || 0;
            totalDifference += parseFloat($(this).find('.difference-weight').val()) || 0;
            totalRemaining += parseFloat($(this).find('.remaining-amount').val()) || 0;
        });

        // Update summary section
        $('#summary_dispatch').val(totalDispatch.toFixed(2));
        $('#summary_receiving').val(totalReceiving.toFixed(2));
        $('#summary_difference').val(totalDifference.toFixed(2));
        $('#summary_remaining').val(totalRemaining.toFixed(2));
    }
</script>
