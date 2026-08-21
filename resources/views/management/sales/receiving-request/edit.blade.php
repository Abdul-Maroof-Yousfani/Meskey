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
                <label class="font-weight-bold">Labour Amount</label>
                <input type="number" class="form-control bg-light" id="labour_details_amount" value="{{ $receivingRequest->labour_amount }}" readonly placeholder="Labour Amount">
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
                <select id="transporter" class="form-control select2" disabled>
                    <option value="">Select Transporter</option>
                    @foreach ($transporters ?? [] as $transporter)
                        <option value="{{ $transporter->id }}" @selected($receivingRequest->transporter == $transporter->id)>{{ $transporter->name }}</option>
                    @endforeach
                </select>
                <input type="hidden" name="transporter" value="{{ $receivingRequest->transporter }}">
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
                <label class="font-weight-bold">Transporter Amount</label>
                <input type="number" class="form-control editable-field" name="transporter_amount" value="{{ $receivingRequest->transporter_amount }}" step="0.01" min="0" placeholder="Transporter Amount">
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
                                'items' => []
                            ];
                        }
                        $groupedItems[$key]['dispatch_weight'] += floatval($item->dispatch_weight);
                        $groupedItems[$key]['no_of_bags'] += floatval($item->deliveryChallanData?->no_of_bags ?? 0);
                        $groupedItems[$key]['items'][] = $item->id;
                    }
                @endphp

                @foreach($groupedItems as $index => $group)
                    <tr class="item-row">
                        <td>
                            <input type="text" value="{{ $group['item_name'] }}" class="form-control form-control-sm bg-light" readonly>
                        </td>
                        <td>
                            <input type="text" value="{{ $group['bag_size'] }}" class="form-control form-control-sm bg-light" readonly>
                        </td>
                        <td>
                            <input type="number" value="{{ $group['dispatch_weight'] }}" class="form-control form-control-sm bg-light dispatch-weight" readonly>
                        </td>
                        <td>
                            <input type="number" value="{{ $group['no_of_bags'] }}" class="form-control form-control-sm bg-light no-of-bags" readonly>
                        </td>
                        <td>
                            <input type="number" value="{{ $group['unloading_labour_rate'] }}" 
                                class="form-control form-control-sm editable-field unloading-labour-rate" 
                                step="0.01" min="0"
                                onchange="calculateWeights(this)"
                                onkeyup="calculateWeights(this)">
                            @foreach($group['items'] as $itemId)
                                <input type="hidden" name="items[{{ $itemId }}][unloading_labour_rate]" class="hidden-rate" value="{{ $group['unloading_labour_rate'] }}">
                            @endforeach
                        </td>
                        <td>
                            <input type="number" value="{{ floatval($group['no_of_bags']) * floatval($group['unloading_labour_rate']) }}" class="form-control form-control-sm bg-light total-labour-amount font-weight-bold" readonly>
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
                <select name="unloading_paid_by" class="form-control select2">
                    <option value="">Select Paid By</option>
                    <option value="Customer" @selected($receivingRequest->unloading_paid_by == 'Customer')>Customer</option>
                    <option value="Transporter" @selected($receivingRequest->unloading_paid_by == 'Transporter')>Transporter</option>
                </select>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Total Labour Amt</label>
                <input type="number" class="form-control bg-light font-weight-bold" id="grand_total_labour_amount" value="{{ $receivingRequest->labour_amount }}" readonly placeholder="Total Labour Amount">
            </div>
        </div>
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
                    <input type="number" name="weighbridges[{{ $index }}][amount]" value="{{ $wb->amount }}" class="form-control editable-field wb-amount-input" step="0.01" min="0" placeholder="Weighbridge Amount" onchange="calculateWeighbridgeTotal()" onkeyup="calculateWeighbridgeTotal()">
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
                    <input type="number" name="weighbridges[0][amount]" class="form-control editable-field wb-amount-input" step="0.01" min="0" placeholder="Weighbridge Amount" onchange="calculateWeighbridgeTotal()" onkeyup="calculateWeighbridgeTotal()">
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-danger btn-sm remove-wb-btn"><i class="fa fa-trash"></i></button>
                </div>
            </div>
        @endif
    </div>

    <div class="row mt-2">
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Paid By</label>
                <select name="weighbridge_paid_by" class="form-control select2">
                    <option value="">Select Paid By</option>
                    <option value="Customer" @selected($receivingRequest->weighbridge_paid_by == 'Customer')>Customer</option>
                    <option value="Transporter" @selected($receivingRequest->weighbridge_paid_by == 'Transporter')>Transporter</option>
                </select>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Total Weighbridge Amt</label>
                <input type="number" class="form-control bg-light font-weight-bold" id="grand_total_weighbridge_amount" value="{{ $receivingRequest->weighbridge_amount }}" readonly placeholder="Total Weighbridge Amount">
            </div>
        </div>
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
                    <input type="number" name="weighbridges[${wbIndex}][amount]" class="form-control editable-field wb-amount-input" step="0.01" min="0" placeholder="Weighbridge Amount" onchange="calculateWeighbridgeTotal()" onkeyup="calculateWeighbridgeTotal()">
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
            calculateWeighbridgeTotal();
        });

        calculateOverallWeights();
        calculateWeighbridgeTotal();
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
        row.find('.hidden-rate').val(rate);

        let grandTotal = 0;
        $('.total-labour-amount').each(function() {
            grandTotal += parseFloat($(this).val()) || 0;
        });
        $('#grand_total_labour_amount').val(grandTotal.toFixed(2));
        $('#labour_details_amount').val(grandTotal.toFixed(2));
    }

    function calculateWeighbridgeTotal() {
        let wbTotal = 0;
        $('.wb-amount-input').each(function() {
            wbTotal += parseFloat($(this).val()) || 0;
        });
        $('#grand_total_weighbridge_amount').val(wbTotal.toFixed(2));
    }
</script>
