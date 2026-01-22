@php
    $paymentRequestData = $paymentRequestData ?? null;
    $purchaseOrder = $paymentRequestData->purchaseOrder ?? null;

    $paymentRequest = $paymentRequestData->paymentRequests->where('request_type', 'payment')->first();
    $freightRequest = $paymentRequestData->paymentRequests->where('request_type', 'freight_payment')->first();

    $hasLoadingWeight = $paymentRequestData->is_loading ?? false;
    $isSlabs = $paymentRequestData->samplingResults->whereNotNull('slab_type_id')->isNotEmpty();
    $isCompulsury = $paymentRequestData->samplingResults->whereNull('slab_type_id')->isNotEmpty();
    $showLumpSum = false;
@endphp
<form action="{{ route('raw-material.ticket.payment-request.update', $paymentRequestData->id) }}" method="POST"
    id="ajaxSubmit">
    @csrf
    @method('PUT')
    <input type="hidden" id="listRefresh" value="{{ route('raw-material.ticket.get.payment-request') }}" />
    <input type="hidden" name="purchase_order_id" value="{{ $purchaseOrder->id ?? '' }}">

    <div class="row">
        <div class="col-12">
            <h6 class="header-heading-sepration">
                Basic Information  ffff
            </h6>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label>Supplier Name</label>
                <input type="text" class="form-control" name="supplier_name"
                    value="{{ $paymentRequestData->supplier_name ?? ($purchaseOrder->supplier_name ?? ($purchaseOrder->supplier->name ?? 'N/A')) }}"
                    readonly>
            </div>
        </div>

        <div class="col-md-6">
            <div class="form-group">
                <label>Contract #</label>
                <input type="text" class="form-control" name="contract_no"
                    value="{{ $paymentRequestData->contract_no ?? ($purchaseOrder->contract_no ?? 'N/A') }}" readonly>
            </div>
        </div>

        <div class="col-md-6">
            <div class="form-group">
                <label>Contract Rate</label>
                <input type="text" class="form-control" name="contract_rate"
                    value="{{ $paymentRequestData->contract_rate ?? ($purchaseOrder->rate_per_kg ?? 0) }}" readonly>
            </div>
        </div>

        <div class="col-md-6 contract-range-field">
            <div class="form-group">
                <label>Contract Range</label>
                <input type="text" class="form-control" name="contract_range"
                    value="{{ $paymentRequestData->min_contract_range ?? $purchaseOrder->rate_per_kg * $purchaseOrder->min_quantity }} - {{ $paymentRequestData->max_contract_range ?? $purchaseOrder->rate_per_kg * $purchaseOrder->max_quantity }}"
                    readonly>
                <input type="hidden" name="min_contract_range"
                    value="{{ $paymentRequestData->min_contract_range ?? $purchaseOrder->rate_per_kg * $purchaseOrder->min_quantity }}">
                <input type="hidden" name="max_contract_range"
                    value="{{ $paymentRequestData->max_contract_range ?? $purchaseOrder->rate_per_kg * $purchaseOrder->max_quantity }}">
            </div>
        </div>
    </div>

    <div class="row mt-3">
        <div class="col-12">
            <h6 class="header-heading-sepration">
                Loading Information
            </h6>
        </div>
        <div class="col-md-12 mb-3">
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="loading_type" id="loading" value="loading"
                    {{ $hasLoadingWeight ? 'checked' : '' }} {{ $hasLoadingWeight ? '' : 'disabled' }}>
                <label class="form-check-label" for="loading">Loading</label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="loading_type" id="without_loading"
                    value="without_loading" {{ !$hasLoadingWeight ? 'checked' : '' }}
                    {{ $hasLoadingWeight ? '' : 'disabled' }}>
                <label class="form-check-label" for="without_loading">Without Loading</label>
            </div>
            <input type="hidden" name="loading_type" value="{{ $hasLoadingWeight ? 'loading' : 'without_loading' }}">
        </div>


        @if ($hasLoadingWeight)
            <div id="loading-section" class="row w-100 mx-auto px-0">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Truck #</label>
                        <input type="text" class="form-control" name="truck_no"
                            value="{{ $paymentRequest->truck_no ?? ($purchaseOrder->purchaseFreight->truck_no ?? 'N/A') }}"
                            readonly>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        <label>Loading Date</label>
                        <input type="text" class="form-control" name="loading_date"
                            value="{{ $paymentRequest->loading_date ? \Carbon\Carbon::parse($paymentRequest->loading_date)->format('d-M-Y') : ($purchaseOrder->purchaseFreight->loading_date ? $purchaseOrder->purchaseFreight->loading_date->format('d-M-Y') : 'N/A') }}"
                            readonly>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        <label>Bilty #</label>
                        <input type="text" class="form-control" name="bilty_no"
                            value="{{ $paymentRequest->bilty_no ?? ($purchaseOrder->purchaseFreight->bilty_no ?? 'N/A') }}"
                            readonly>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        <label>Station</label>
                        <input type="text" class="form-control" name="station"
                            value="{{ $paymentRequest->station ?? ($purchaseOrder->station_name ?? 'N/A') }}" readonly>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        <label>No of Bags</label>
                        <input type="text" class="form-control" name="no_of_bags"
                            value="{{ $paymentRequest->no_of_bags ?? ($purchaseOrder->purchaseFreight->no_of_bags ?? 0) }}"
                            readonly>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        <label>Loading Weight</label>
                        <input type="text" class="form-control" name="loading_weight"
                            value="{{ $paymentRequest->loading_weight ?? ($purchaseOrder->purchaseFreight->loading_weight ?? 0) }}"
                            readonly>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        <label>Avg Rate</label>
                        <input type="text" class="form-control" name="avg_rate"
                            value="{{ $paymentRequest->avg_rate ?? ($purchaseOrder->purchaseFreight->loading_weight ?? 0) / ($purchaseOrder->purchaseFreight->no_of_bags ?? 1) }}"
                            readonly>
                    </div>
                </div>

                @if (!$showLumpSum || $isSlabs || $isCompulsury)
                    <div class="col-12">
                        <h6 class="header-heading-sepration">
                            Sampling Results
                        </h6>
                    </div>
                    <div class="col-12">
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered table-hover">
                                <thead class="thead-light">
                                    <tr>
                                        <th width="20%">Name</th>
                                        <th width="15%">Value</th>
                                        <th width="15%">Suggested Deduction</th>
                                        <th width="15%">Tabaar Deduction</th>
                                        <th width="15%">Deduction Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($samplingResults->whereNotNull('slab_type_id') as $result)
                                        <tr>
                                            <td>{{ $result->name }}
                                                <input type="hidden"
                                                    name="sampling_results[{{ $result->id }}][slab_type_id]"
                                                    value="{{ $result->slab_type_id }}">
                                                <input type="hidden"
                                                    name="sampling_results[{{ $result->id }}][slab_name]"
                                                    value="{{ $result->name }}">
                                            </td>
                                            <td>
                                                <input type="text" readonly class="form-control"
                                                    name="sampling_results[{{ $result->id }}][checklist_value]"
                                                    value="{{ $result->checklist_value }}">
                                            </td>
                                            <td>
                                                <div class="input-group mb-0">
                                                    <input type="text" class="form-control"
                                                        name="sampling_results[{{ $result->id }}][suggested_deduction]"
                                                        value="{{ $result->suggested_deduction }}"
                                                        placeholder="Suggested Deduction" readonly>
                                                    <div class="input-group-append">
                                                        <span
                                                            class="input-group-text text-sm">{{ $result->deduction_type == 'amount' ? 'Rs.' : "KG's" }}</span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="input-group mb-0">
                                                    <input type="text" class="form-control"
                                                        name="sampling_results[{{ $result->id }}][applied_deduction]"
                                                        value="{{ $result->applied_deduction }}"
                                                        placeholder="Suggested Deduction" readonly>
                                                    <div class="input-group-append">
                                                        <span
                                                            class="input-group-text text-sm">{{ $result->slabType->qc_symbol ?? '' }}</span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="input-group mb-0">
                                                    <input type="text" class="form-control"
                                                        name="sampling_results[{{ $result->id }}][deduction_amount]"
                                                        value="{{ $result->deduction_amount }}"
                                                        placeholder="deduction_amount" readonly>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach

                                    @foreach ($samplingResults->whereNull('slab_type_id') as $result)
                                        <tr>
                                            <td>{{ $result->name }}
                                                <input type="hidden"
                                                    name="compulsory_results[{{ $result->id }}][qc_name]"
                                                    value="{{ $result->name }}">
                                                <input type="hidden"
                                                    name="compulsory_results[{{ $result->id }}][qc_param_id]"
                                                    value="{{ $result->slab_type_id }}">
                                            </td>
                                            <td></td>
                                            <td></td>
                                            <td>
                                                <div class="input-group mb-0">
                                                    <input type="text" class="form-control"
                                                        name="compulsory_results[{{ $result->id }}][applied_deduction]"
                                                        value="{{ $result->applied_deduction }}"
                                                        placeholder="Suggested Deduction" readonly>
                                                    <div class="input-group-append">
                                                        <span
                                                            class="input-group-text text-sm">{{ SLAB_TYPES_CALCULATED_ON[$result->slabType->calculation_base_type ?? 1] ?? 'Amount' }}</span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="input-group mb-0">
                                                    <input type="text" class="form-control"
                                                        name="compulsory_results[{{ $result->id }}][deduction_amount]"
                                                        value="{{ $result->deduction_amount }}"
                                                        placeholder="deduction_amount" readonly>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                <div class="col-12">
                    <table class="table table-bordered mb-4" style="min-width: 500px;">
                        <tbody>
                            <tr>
                                <td><strong>Bags weight in Kg</strong></td>
                                <td>
                                    <input type="text" class="form-control" name="bag_weight"
                                        value="{{ $paymentRequest->bag_weight ?? ($purchaseOrder->bag_weight ?? 0) }}"
                                        readonly>
                                </td>
                                <td>
                                    <input type="text" class="form-control" name="bag_weight_total"
                                        value="{{ $paymentRequest->bag_weight_total ?? ($purchaseOrder->bag_weight ?? 0) * ($purchaseOrder->purchaseFreight->no_of_bags ?? 0) }}"
                                        readonly>
                                </td>
                                <td>
                                    <input type="text" class="form-control" name="bag_weight_amount"
                                        value="{{ $paymentRequest->bag_weight_amount ?? ($purchaseOrder->rate_per_kg ?? 0) * (($purchaseOrder->bag_weight ?? 0) * ($purchaseOrder->purchaseFreight->no_of_bags ?? 0)) }}"
                                        readonly>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Bags Rate</strong></td>
                                <td>
                                    <input type="text" class="form-control" name="bag_rate"
                                        value="{{ $paymentRequest->bag_rate ?? ($purchaseOrder->bag_rate ?? 0) }}"
                                        readonly>
                                </td>
                                <td>N/A</td>
                                <td>
                                    <input type="text" class="form-control" name="bag_rate_amount"
                                        value="{{ $paymentRequest->bag_rate_amount ?? ($purchaseOrder->bag_rate ?? 0) * ($purchaseOrder->purchaseFreight->no_of_bags ?? 0) }}"
                                        readonly>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Loading weighbridge</strong></td>
                                <td>N/A</td>
                                <td>N/A</td>
                                <td>
                                    <input type="text" class="form-control" name="loading_weighbridge_amount"
                                        value="{{ $paymentRequest->loading_weighbridge_amount ?? ($purchaseOrder->purchaseFreight && $purchaseOrder->purchaseFreight->kanta_charges ? $purchaseOrder->purchaseFreight->kanta_charges / 2 : 0) }}"
                                        readonly>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Amount</label>
                        <input type="text" class="form-control" name="total_amount"
                            value="{{ $paymentRequestData->total_amount }}" readonly>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label>Paid Amount</label>
                        <input type="number" step="0.01" class="form-control" name="paid_amount"
                            value="{{ $paymentRequestData->paid_amount }}" placeholder="Enter paid amount">
                    </div>
                </div>

                <div class="col-12">
                    <hr class="border">
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label>Remaining</label>
                        <input type="text" class="form-control" name="remaining_amount"
                            value="{{ $paymentRequestData->remaining_amount }}" readonly>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label>Payment Request</label>
                        <input type="number" step="0.01" class="form-control" name="payment_request_amount"
                            value="{{ $paymentRequest->amount ?? 0 }}" placeholder="Enter payment request">
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label>Advance Freight</label>
                        <input type="text" class="form-control" name="advance_freight"
                            value="{{ $paymentRequestData->advance_freight }}" readonly>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label>Freight Pay Request</label>
                        <input type="number" step="0.01" class="form-control" name="freight_pay_request_amount"
                            value="{{ $freightRequest->amount ?? 0 }}" placeholder="Enter freight pay request">
                    </div>
                </div>
            </div>
        @else
            <div class="col-md-6">
                <div class="form-group">
                    <label>Amount</label>
                    <input type="text" class="form-control" name="total_amount"
                        value="{{ $paymentRequestData->total_amount }}" readonly>
                </div>
            </div>

            <div class="col-md-6">
                <div class="form-group">
                    <label>Paid Amount</label>
                    <input type="number" step="0.01" class="form-control" name="paid_amount"
                        value="{{ $paymentRequestData->paid_amount }}" placeholder="Enter paid amount">
                </div>
            </div>

            <div class="col-12">
                <hr class="border">
            </div>

            <div class="col-md-6">
                <div class="form-group">
                    <label>Remaining</label>
                    <input type="text" class="form-control" name="remaining_amount"
                        value="{{ $paymentRequestData->remaining_amount }}" readonly>
                </div>
            </div>

            <div class="col-md-6">
                <div class="form-group">
                    <label>Payment Request</label>
                    <input type="number" step="0.01" class="form-control" name="payment_request_amount"
                        value="{{ $paymentRequest->amount ?? 0 }}" placeholder="Enter payment request">
                </div>
            </div>

            <div class="col-md-6">
                <div class="form-group">
                    <label>Advance Freight</label>
                    <input type="text" class="form-control" name="advance_freight"
                        value="{{ $paymentRequestData->advance_freight }}" readonly>
                </div>
            </div>

            <div class="col-md-6">
                <div class="form-group">
                    <label>Freight Pay Request</label>
                    <input type="number" step="0.01" class="form-control" name="freight_pay_request_amount"
                        value="{{ $freightRequest->amount ?? 0 }}" placeholder="Enter freight pay request">
                </div>
            </div>
        @endif
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
        $('input[name="payment_request_amount"], input[name="paid_amount"], input[name="freight_pay_request_amount"]')
            .on('input', function() {
                const amount = parseFloat($('input[name="total_amount"]').val());
                const paidAmount = parseFloat($('input[name="paid_amount"]').val()) || 0;
                const paymentRequest = parseFloat($('input[name="payment_request_amount"]').val()) || 0;
                const freightRequest = parseFloat($('input[name="freight_pay_request_amount"]').val()) || 0;

                const remaining = amount - paidAmount - paymentRequest - freightRequest;
                $('input[name="remaining_amount"]').val(remaining.toFixed(2));
            });

        // Toggle loading section visibility
        const $loadingRadio = $('#loading');
        const $withoutLoadingRadio = $('#without_loading');
        const $loadingSection = $('#loading-section');
        const $contractRangeField = $('.contract-range-field');

        function toggleSections() {
            if ($loadingRadio.is(':checked')) {
                $loadingSection.show();
                $contractRangeField.show();
            } else {
                $loadingSection.hide();
                $contractRangeField.hide();
            }
        }

        toggleSections();
        $loadingRadio.on('change', toggleSections);
        $withoutLoadingRadio.on('change', toggleSections);
    });
</script>
