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
                <input type="text" class="form-control bg-light font-weight-bold" id="summary_dispatch" value="{{ number_format($receivingRequest->items->sum('dispatch_weight'), 2, '.', '') }}" readonly>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Receiving Weight (Total)</label>
                <input type="number" class="form-control editable-field" name="arrived_weight" id="arrived_weight" value="{{ $receivingRequest->arrived_weight }}" step="0.01" min="0" placeholder="Receiving Weight">
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Weight Difference</label>
                <input type="text" class="form-control bg-light font-weight-bold text-danger" id="overall_weight_difference" value="0.00" readonly>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Exempted Weight</label>
                <input type="number" class="form-control editable-field" name="exempted_weight" id="exempted_weight" value="{{ $receivingRequest->exempted_weight }}" step="0.01" min="0" placeholder="Exempted Weight">
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Payment Weight</label>
                <input type="number" class="form-control bg-light font-weight-bold" id="payment_weight" value="{{ $receivingRequest->payment_weight }}" readonly placeholder="Payment Weight">
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Labour</label>
                <select id="labour" class="form-control select2" disabled>
                    <option value="">Select Labour</option>
                    @foreach ($labours ?? [] as $labour)
                        <option value="{{ $labour->id }}" @selected($receivingRequest->labour == $labour->id)>{{ $labour->name }}</option>
                    @endforeach
                </select>
                <input type="hidden" name="labour" value="{{ $receivingRequest->labour }}">
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Transporter</label>
                <select id="transporter" class="form-control select2" disabled>
                    <option value="">Select Transporter</option>
                    @foreach ($transporters ?? [] as $transporter)
                        <option value="{{ $transporter->id }}" @selected($receivingRequest->transporter == $transporter->id)>{{ $transporter->name }}</option>
                    @endforeach
                </select>
                <input type="hidden" name="transporter" value="{{ $receivingRequest->transporter }}">
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Transporter Amount</label>
                <input type="number" class="form-control editable-field" name="transporter_amount" value="{{ $receivingRequest->transporter_amount }}" step="0.01" min="0" placeholder="Transporter Amount">
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
                    <tr class="item-row" data-item-id="{{ $item->id }}">
                        <td>
                            <input type="text" value="{{ $doNo }}" class="form-control form-control-sm bg-light" readonly>
                        </td>
                        <td>
                            <input type="text" value="{{ $item->item_name }}" class="form-control form-control-sm bg-light" readonly>
                        </td>
                        <td>
                            <input type="number" value="{{ $item->dispatch_weight }}" class="form-control form-control-sm bg-light dispatch-weight" readonly>
                        </td>
                        <td>
                            <input type="number" value="{{ $bags }}" class="form-control form-control-sm bg-light no-of-bags" readonly>
                        </td>
                        <td>
                            <input type="text" value="{{ $item->deliveryChallanData?->bag_size ?? 'N/A' }}" class="form-control form-control-sm bg-light" readonly>
                        </td>
                        <td>
                            <input type="number" name="items[{{ $item->id }}][unloading_labour_rate]" 
                                value="{{ $item->unloading_labour_rate }}" 
                                class="form-control form-control-sm editable-field unloading-labour-rate" 
                                step="0.01" min="0"
                                onchange="calculateWeights(this)"
                                onkeyup="calculateWeights(this)">
                        </td>
                        <td>
                            <input type="number" value="{{ floatval($bags) * floatval($item->unloading_labour_rate) }}" class="form-control form-control-sm bg-light total-labour-amount font-weight-bold" readonly>
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
                <button type="button" class="btn btn-sm btn-success" id="addWeighbridgeBtn"><i class="fa fa-plus"></i> Add More</button>
            </div>
        </div>
    </div>
    <div id="weighbridges-container">
        @if($receivingRequest->weighbridges->count() > 0)
            @foreach($receivingRequest->weighbridges as $index => $wb)
            <div class="row weighbridge-row mb-2">
                <div class="col-md-5">
                    <input type="text" name="weighbridges[{{ $index }}][name]" value="{{ $wb->name }}" class="form-control editable-field" placeholder="Weighbridge Name">
                </div>
                <div class="col-md-5">
                    <input type="number" name="weighbridges[{{ $index }}][amount]" value="{{ $wb->amount }}" class="form-control editable-field" step="0.01" min="0" placeholder="Weighbridge Amount">
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-danger btn-sm remove-wb-btn"><i class="fa fa-trash"></i></button>
                </div>
            </div>
            @endforeach
        @else
            <div class="row weighbridge-row mb-2">
                <div class="col-md-5">
                    <input type="text" name="weighbridges[0][name]" class="form-control editable-field" placeholder="Weighbridge Name">
                </div>
                <div class="col-md-5">
                    <input type="number" name="weighbridges[0][amount]" class="form-control editable-field" step="0.01" min="0" placeholder="Weighbridge Amount">
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-danger btn-sm remove-wb-btn"><i class="fa fa-trash"></i></button>
                </div>
            </div>
        @endif
    </div>

    <div class="row bottom-button-bar mt-3">
        <div class="col-12">
            <a type="button" class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton">Close</a>
            <button type="submit" class="btn btn-primary submitbutton">Save</button>
        </div>
    </div>
</form>

<script>
    $(document).ready(function() {
        $('.select2').select2();

        $('#arrived_weight, #exempted_weight').on('input change', function() {
            calculateOverallWeights();
        });

        let wbIndex = {{ max($receivingRequest->weighbridges->count(), 1) }};
        $('#addWeighbridgeBtn').on('click', function() {
            let rowHtml = `
            <div class="row weighbridge-row mb-2">
                <div class="col-md-5">
                    <input type="text" name="weighbridges[${wbIndex}][name]" class="form-control editable-field" placeholder="Weighbridge Name">
                </div>
                <div class="col-md-5">
                    <input type="number" name="weighbridges[${wbIndex}][amount]" class="form-control editable-field" step="0.01" min="0" placeholder="Weighbridge Amount">
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-danger btn-sm remove-wb-btn"><i class="fa fa-trash"></i></button>
                </div>
            </div>`;
            $('#weighbridges-container').append(rowHtml);
            wbIndex++;
        });

        $(document).on('click', '.remove-wb-btn', function() {
            $(this).closest('.weighbridge-row').remove();
        });

        calculateOverallWeights();
    });

    function calculateOverallWeights() {
        let arrivedWeight = parseFloat($('#arrived_weight').val()) || 0;
        let exemptedWeight = parseFloat($('#exempted_weight').val()) || 0;
        let dispatchTotal = parseFloat($('#summary_dispatch').val().replace(/,/g, '')) || 0;
        
        let paymentWeight = arrivedWeight - exemptedWeight;
        $('#payment_weight').val(paymentWeight.toFixed(2));

        let overallDifference = dispatchTotal - arrivedWeight;
        $('#overall_weight_difference').val(overallDifference.toFixed(2));
    }

    function calculateWeights(element) {
        const row = $(element).closest('.item-row');
        let noOfBags = parseFloat(row.find('.no-of-bags').val()) || 0;
        let rate = parseFloat(row.find('.unloading-labour-rate').val()) || 0;

        const totalLabour = noOfBags * rate;
        row.find('.total-labour-amount').val(totalLabour.toFixed(2));
    }
</script>
