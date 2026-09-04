<form action="{{ route('sales.logistics-bill.update', $logisticsBill->id) }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    @method('PUT')

    <input type="hidden" id="listRefresh" value="{{ route('sales.get.logistics-bill.list') }}" />

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

    @if(!$isXmill)
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
                <input type="text" class="form-control bg-light font-weight-bold" id="summary_dispatch" value="{{ number_format($logisticsBill->items->sum('dispatch_weight'), 2, '.', '') }}" readonly>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Receiving Weight (Total)</label>
                <input type="number" class="form-control bg-light font-weight-bold" id="arrived_weight" value="{{ $logisticsBill->arrived_weight }}" readonly placeholder="Receiving Weight">
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Weight Difference</label>
                @php
                    $diffWeight = floatval($logisticsBill->items->sum('dispatch_weight')) - floatval($logisticsBill->arrived_weight);
                @endphp
                <input type="text" class="form-control bg-light font-weight-bold {{ $diffWeight > 0 ? 'text-danger' : 'text-success' }}" id="overall_weight_difference" value="{{ number_format($diffWeight, 2, '.', '') }}" readonly>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Exempted Weight</label>
                <input type="number" class="form-control editable-field" name="exempted_weight" id="exempted_weight" value="{{ $logisticsBill->exempted_weight }}" step="0.01" min="0" placeholder="Exempted Weight">
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Payment Weight</label>
                <input type="number" class="form-control bg-light font-weight-bold" id="payment_weight" value="{{ $logisticsBill->payment_weight }}" readonly placeholder="Payment Weight">
            </div>
        </div>
    </div>
    @endif

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
                <input type="number" class="form-control editable-field font-weight-bold" id="transporter_deduction" name="transporter_deduction" value="{{ $logisticsBill->transporter_deduction ?? 0 }}" step="0.01" min="0" placeholder="Deduction">
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Other Amount</label>
                <input type="number" class="form-control editable-field font-weight-bold" id="transporter_other_amount" name="transporter_other_amount" value="{{ $logisticsBill->transporter_other_amount ?? 0 }}" step="0.01" min="0" placeholder="Other Amount">
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Demurrage & Detention Amount</label>
                <input type="number" class="form-control editable-field font-weight-bold" id="demurrage_detention_amount" name="demurrage_detention_amount" value="{{ $logisticsBill->demurrage_detention_amount ?? 0 }}" step="0.01" min="0" placeholder="Demurrage & Detention Amount">
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Transporter Amount</label>
                <input type="number" class="form-control bg-light font-weight-bold" id="transporter_amount" value="{{ $logisticsBill->transporter_amount }}" readonly placeholder="Transporter Amount">
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Total Amount</label>
                @php
                    $netTransporterAmount = floatval($logisticsBill->transporter_amount ?? 0) 
                        - floatval($logisticsBill->transporter_deduction ?? 0) 
                        + floatval($logisticsBill->transporter_other_amount ?? 0) 
                        + floatval($logisticsBill->demurrage_detention_amount ?? 0) 
                        + floatval($logisticsBill->sales_return_transporter_amount ?? 0);
                @endphp
                <input type="number" class="form-control bg-light font-weight-bold text-primary" id="transporter_total_amount" value="{{ number_format($netTransporterAmount, 2, '.', '') }}" readonly placeholder="Total Amount">
            </div>
        </div>
    </div>

    @if(!$isXmill)
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
                    <option value="Customer" @selected($logisticsBill->unloading_paid_by == 'Customer')>Customer</option>
                    <option value="Transporter" @selected($logisticsBill->unloading_paid_by == 'Transporter')>Transporter</option>
                </select>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Total Labour Amt</label>
                <input type="number" class="form-control bg-light font-weight-bold" id="grand_total_labour_amount" value="{{ $logisticsBill->labour_amount }}" readonly placeholder="Total Labour Amount">
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
                    <input type="number" value="{{ $wb->amount }}" class="form-control bg-light wb-amount-input font-weight-bold" readonly placeholder="Weighbridge Amount">
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
                <select name="weighbridge_paid_by" class="form-control select2">
                    <option value="">Select Paid By</option>
                    <option value="Customer" @selected($logisticsBill->weighbridge_paid_by == 'Customer')>Customer</option>
                    <option value="Transporter" @selected($logisticsBill->weighbridge_paid_by == 'Transporter')>Transporter</option>
                </select>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Total Weighbridge Amt</label>
                <input type="number" class="form-control bg-light font-weight-bold" id="grand_total_weighbridge_amount" value="{{ $logisticsBill->weighbridge_amount }}" readonly placeholder="Total Weighbridge Amount">
            </div>
        </div>
    </div>

    <!-- Sales Return Section -->
    <div class="row mt-3">
        <div class="col-12">
            <h6 class="header-heading-sepration mb-2">Sales Return Details</h6>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label class="font-weight-bold">Sales Return</label>
                <select name="sales_return_id" id="sales_return_id" class="form-control select2">
                    @if(isset($salesReturns) && $salesReturns->count() > 0)
                        <option value="">Select Sales Return</option>
                        @foreach($salesReturns as $sr)
                            @php
                                $srQty = $sr->sale_return_data->sum('quantity');
                            @endphp
                            <option value="{{ $sr->id }}" data-qty="{{ $srQty }}" @selected($logisticsBill->sales_return_id == $sr->id)>
                                {{ $sr->sr_no }} (Return Qty: {{ number_format($srQty, 2) }})
                            </option>
                        @endforeach
                    @else
                        <option value="" disabled selected>No Sales Return found for this DC/RR</option>
                    @endif
                </select>
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label class="font-weight-bold">Return Qty</label>
                <input type="number" class="form-control bg-light font-weight-bold" name="sales_return_qty" id="sales_return_qty" value="{{ $logisticsBill->sales_return_qty ?? 0 }}" readonly placeholder="Return Qty">
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label class="font-weight-bold">Transporter Amount</label>
                <input type="number" class="form-control font-weight-bold {{ $logisticsBill->sales_return_id ? 'editable-field' : 'bg-light' }}" name="sales_return_transporter_amount" id="sales_return_transporter_amount" value="{{ $logisticsBill->sales_return_transporter_amount ?? 0 }}" step="0.01" min="0" placeholder="Transporter Amount" {{ $logisticsBill->sales_return_id ? '' : 'readonly' }}>
            </div>
        </div>
    </div>
    @endif

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

        @if(!$isXmill)
        $('#exempted_weight').on('input change', function() {
            calculateOverallWeights();
        });

        $('#sales_return_id').on('change', function() {
            toggleSalesReturnFields();
        });

        toggleSalesReturnFields();
        calculateOverallWeights();
        @endif

        $('#transporter_deduction, #transporter_other_amount, #demurrage_detention_amount, #sales_return_transporter_amount').on('input change keyup', function() {
            calculateTransporterTotal();
        });

        calculateTransporterTotal();
    });

    function toggleSalesReturnFields() {
        let srId = $('#sales_return_id').val();
        if (srId) {
            let selectedOption = $('#sales_return_id').find('option:selected');
            let qty = parseFloat(selectedOption.data('qty')) || 0;
            $('#sales_return_qty').val(qty.toFixed(2));
            $('#sales_return_transporter_amount').prop('readonly', false).removeClass('bg-light').addClass('editable-field');
        } else {
            $('#sales_return_qty').val('0.00');
            $('#sales_return_transporter_amount').val('0.00').prop('readonly', true).addClass('bg-light').removeClass('editable-field');
        }
        calculateTransporterTotal();
    }

    function calculateTransporterTotal() {
        let amount = parseFloat($('#transporter_amount').val()) || 0;
        let deduction = parseFloat($('#transporter_deduction').val()) || 0;
        let otherAmount = parseFloat($('#transporter_other_amount').val()) || 0;
        let demurrageAmount = parseFloat($('#demurrage_detention_amount').val()) || 0;
        let srTransporterAmount = parseFloat($('#sales_return_transporter_amount').val()) || 0;

        let total = amount - deduction + otherAmount + demurrageAmount + srTransporterAmount;
        $('#transporter_total_amount').val(total.toFixed(2));
    }

    function calculateOverallWeights() {
        let arrivedWeight = parseFloat($('#arrived_weight').val()) || 0;
        let exemptedWeight = parseFloat($('#exempted_weight').val()) || 0;
        
        let paymentWeight = arrivedWeight - exemptedWeight;
        $('#payment_weight').val(paymentWeight.toFixed(2));
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
    }
</script>
