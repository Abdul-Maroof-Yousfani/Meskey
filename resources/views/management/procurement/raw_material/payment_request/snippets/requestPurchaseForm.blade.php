@php
    $hasLoadingWeight = false;
    if ($purchaseOrder && $purchaseOrder->purchaseFreight && $purchaseOrder->purchaseFreight->loading_weight) {
        $hasLoadingWeight = true;
    }

    $isSlabs = false;
    $isCompulsury = false;
    $showLumpSum = false;

    if (
        isset($samplingRequest->is_lumpsum_deduction) &&
        $samplingRequest->is_lumpsum_deduction &&
        $samplingRequest->lumpsum_deduction > 0
    ) {
        $showLumpSum = true;
    }

    $totalDeductions = 0;

    foreach ($samplingRequestCompulsuryResults as $slab) {
        if (!$slab->applied_deduction) {
            continue;
        }
        $isCompulsury = true;

        $deductionValue = $slab->applied_deduction ?? 0;
        $loadingWeight = $purchaseOrder->purchaseFreight->loading_weight ?? 0;
        $bagWeight = $purchaseOrder->bag_weight ?? 0;
        $noOfBags = $purchaseOrder->purchaseFreight?->no_of_bags ?? 0;
        $netWeight = $loadingWeight - $bagWeight * $noOfBags;

        $totalDeductions += $deductionValue * $netWeight;
    }

    foreach ($samplingRequestResults as $slab) {
        if (!$slab->applied_deduction) {
            continue;
        }
        $isSlabs = true;

        $deductionValue = $slab->applied_deduction ?? 0;
        $loadingWeight = $purchaseOrder->purchaseFreight->loading_weight ?? 0;
        $bagWeight = $purchaseOrder->bag_weight ?? 0;
        $noOfBags = $purchaseOrder->purchaseFreight?->no_of_bags ?? 0;
        $netWeight = $loadingWeight - $bagWeight * $noOfBags;

        $totalDeductions += $deductionValue * $netWeight;
    }

    $avgRate = 0;
    if ($purchaseOrder->purchaseFreight?->no_of_bags > 0) {
        $avgRate =
            ($purchaseOrder->purchaseFreight->loading_weight ?? 0) / $purchaseOrder->purchaseFreight?->no_of_bags;
    }

    $bagWeightInKgSum =
        ($purchaseOrder->rate_per_kg ?? 0) *
        (($purchaseOrder->bag_weight ?? 0) * ($purchaseOrder->purchaseFreight->no_of_bags ?? 0));
    $loadingWeighbridgeSum =
        $purchaseOrder->purchaseFreight && $purchaseOrder->purchaseFreight->kanta_charges
            ? $purchaseOrder->purchaseFreight->kanta_charges / 2
            : 0;
    $bagsRateSum = ($purchaseOrder->bag_rate ?? 0) * ($purchaseOrder->purchaseFreight->no_of_bags ?? 0);

    $totalDeductions += $bagsRateSum + $loadingWeighbridgeSum + $bagWeightInKgSum;

    $amount = ($purchaseOrder->purchaseFreight->loading_weight ?? 0) * $purchaseOrder->rate_per_kg - $totalDeductions;

    $paidAmount = $pRsSum;
    $remaining = $amount - $paidAmount;

    $advanceFreight = $purchaseOrder->purchaseFreight->advance_freight ?? 0;
@endphp

<input type="hidden" name="purchase_order_id" value="{{ $purchaseOrder->id }}">

<div class="row">
    <div class="col-12">
        <h6 class="header-heading-sepration">
            Basic Information
        </h6>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label>Supplier Name</label>
            <input type="text" class="form-control" name="supplier_name"
                value="{{ $purchaseOrder->supplier_name ?? ($purchaseOrder->supplier->name ?? 'N/A') }}" readonly>
        </div>
    </div>

    <div class="col-md-6">
        <div class="form-group">
            <label>Contract #</label>
            <input type="text" class="form-control" name="contract_no"
                value="{{ $purchaseOrder->contract_no ?? 'N/A' }}" readonly>
        </div>
    </div>

    <div class="col-md-6">
        <div class="form-group">
            <label>Contract Rate</label>
            <input type="text" class="form-control" name="contract_rate"
                value="{{ $purchaseOrder->rate_per_kg ?? 0 }}" readonly>
        </div>
    </div>

    <div class="col-md-6 contract-range-field">
        <div class="form-group">
            <label>Contract Range</label>
            <input type="text" class="form-control" name="contract_range"
                value="{{ ($purchaseOrder->rate_per_kg ?? 0) * $purchaseOrder->min_quantity }} - {{ ($purchaseOrder->rate_per_kg ?? 0) * $purchaseOrder->max_quantity }}"
                readonly>

            <input type="hidden" name="min_contract_range"
                value="{{ ($purchaseOrder->rate_per_kg ?? 0) * $purchaseOrder->min_quantity }}">
            <input type="hidden" name="max_contract_range"
                value="{{ ($purchaseOrder->rate_per_kg ?? 0) * $purchaseOrder->max_quantity }}">
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
    </div>

    @if ($hasLoadingWeight)
        <div id="loading-section" class="row w-100 mx-auto px-0">
            <div class="col-md-3">
                <div class="form-group">
                    <label>Truck #</label>
                    <input type="text" class="form-control" name="truck_no"
                        value="{{ $purchaseOrder->purchaseFreight->truck_no ?? 'N/A' }}" readonly>
                </div>
            </div>

            <div class="col-md-3">
                <div class="form-group">
                    <label>Loading Date</label>
                    <input type="text" class="form-control" name="loading_date"
                        value="{{ $purchaseOrder && $purchaseOrder->purchaseFreight->loading_date ? $purchaseOrder->purchaseFreight->loading_date->format('d-M-Y') : 'N/A' }}"
                        readonly>
                </div>
            </div>

            <div class="col-md-3">
                <div class="form-group">
                    <label>Bilty #</label>
                    <input type="text" class="form-control" name="bilty_no"
                        value="{{ $purchaseOrder->purchaseFreight->bilty_no ?? 'N/A' }}" readonly>
                </div>
            </div>

            <div class="col-md-3">
                <div class="form-group">
                    <label>Station</label>
                    <input type="text" class="form-control" name="station"
                        value="{{ $purchaseOrder->station_name ?? 'N/A' }}" readonly>
                </div>
            </div>

            <div class="col-md-3">
                <div class="form-group">
                    <label>No of Bags</label>
                    <input type="text" class="form-control" name="no_of_bags"
                        value="{{ $purchaseOrder->purchaseFreight->no_of_bags ?? 0 }}" readonly>
                </div>
            </div>

            <div class="col-md-3">
                <div class="form-group">
                    <label>Loading Weight</label>
                    <input type="text" class="form-control" name="loading_weight"
                        value="{{ $purchaseOrder->purchaseFreight->loading_weight ?? 0 }}" readonly>
                </div>
            </div>

            <div class="col-md-3">
                <div class="form-group">
                    <label>Avg Rate</label>
                    <input type="text" class="form-control" name="avg_rate" value="{{ number_format($avgRate, 2) }}"
                        readonly>
                </div>
            </div>

            @if ($showLumpSum && !$isSlabs && !$isCompulsury)
            @else
                <div class="col-12" bis_skin_checked="1">
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
                                @if (count($samplingRequestResults) != 0)
                                    @foreach ($samplingRequestResults as $slab)
                                        @php
                                            if (!$slab->applied_deduction) {
                                                continue;
                                            }

                                            $getDeductionSuggestion = getDeductionSuggestion(
                                                $slab->slabType->id,
                                                $purchaseOrder->qc_product ?? $purchaseOrder->product_id,
                                                $slab->checklist_value,
                                            );
                                            $suggestedDeductionType =
                                                $getDeductionSuggestion->deduction_type ?? 'amount';
                                        @endphp
                                        <tr>
                                            <td>{{ $slab->slabType->name }}
                                                <input type="hidden"
                                                    name="sampling_results[{{ $slab->id }}][slab_type_id]"
                                                    value="{{ $slab->slabType->id }}">
                                                <input type="hidden"
                                                    name="sampling_results[{{ $slab->id }}][slab_name]"
                                                    value="{{ $slab->slabType->name }}">
                                            </td>
                                            <td>
                                                <input type="text" readonly class="form-control"
                                                    name="sampling_results[{{ $slab->id }}][checklist_value]"
                                                    value="{{ $slab->checklist_value }}">
                                            </td>
                                            <td>
                                                <div class="input-group mb-0">
                                                    <input type="text" class="form-control"
                                                        name="sampling_results[{{ $slab->id }}][suggested_deduction]"
                                                        value="{{ $getDeductionSuggestion->deduction_value }}"
                                                        placeholder="Suggested Deduction" readonly>
                                                    <div class="input-group-append">
                                                        <span
                                                            class="input-group-text text-sm">{{ $suggestedDeductionType == 'amount' ? 'Rs.' : "KG's" }}</span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="input-group mb-0">
                                                    <input type="text" class="form-control"
                                                        name="sampling_results[{{ $slab->id }}][applied_deduction]"
                                                        value="{{ $slab->applied_deduction ?? 0 }}"
                                                        placeholder="Suggested Deduction" readonly>
                                                    <div class="input-group-append">
                                                        <span
                                                            class="input-group-text text-sm">{{ $slab->slabType->qc_symbol }}</span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="input-group mb-0">
                                                    <input type="text" class="form-control"
                                                        name="sampling_results[{{ $slab->id }}][deduction_amount]"
                                                        value="{{ ($slab->applied_deduction ?? 0) * (($purchaseOrder->purchaseFreight->loading_weight ?? 0) - ($purchaseOrder->bag_weight ?? 0) * ($purchaseOrder->purchaseFreight->no_of_bags ?? 0)) }}"
                                                        placeholder="deduction_amount" readonly>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                @endif

                                @if ($isCompulsury)
                                    @foreach ($samplingRequestCompulsuryResults as $slab)
                                        @if (!$slab->applied_deduction)
                                            @continue
                                        @endif
                                        <tr>
                                            <td>{{ $slab->qcParam->name ?? 'Compulsory' }}
                                                <input type="hidden"
                                                    name="compulsory_results[{{ $slab->id }}][qc_name]"
                                                    value="{{ $slab->qcParam->name ?? null }}">
                                                <input type="hidden"
                                                    name="compulsory_results[{{ $slab->id }}][qc_param_id]"
                                                    value="{{ $slab->qcParam->id ?? null }}">
                                            </td>
                                            <td></td>
                                            <td></td>
                                            <td>
                                                <div class="input-group mb-0">
                                                    <input type="text" class="form-control"
                                                        name="compulsory_results[{{ $slab->id }}][applied_deduction]"
                                                        value="{{ $slab->applied_deduction }}"
                                                        placeholder="Suggested Deduction" readonly>
                                                    <div class="input-group-append">
                                                        <span
                                                            class="input-group-text text-sm">{{ SLAB_TYPES_CALCULATED_ON[$slab->qcParam->calculation_base_type ?? 1] }}</span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="input-group mb-0">
                                                    <input type="text" class="form-control"
                                                        name="compulsory_results[{{ $slab->id }}][deduction_amount]"
                                                        value="{{ ($slab->applied_deduction ?? 0) * (($purchaseOrder->purchaseFreight->loading_weight ?? 0) - ($purchaseOrder->bag_weight ?? 0) * ($purchaseOrder->purchaseFreight->no_of_bags ?? 0)) }}"
                                                        placeholder="deduction_amount" readonly>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                @endif
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
                                    value="{{ $purchaseOrder->bag_weight ?? 0 }}" readonly>
                            </td>
                            <td>
                                <input type="text" class="form-control" name="bag_weight_total"
                                    value="{{ ($purchaseOrder->bag_weight ?? 0) * ($purchaseOrder->purchaseFreight->no_of_bags ?? 0) }}"
                                    readonly>
                            </td>
                            <td>
                                <input type="text" class="form-control" name="bag_weight_amount"
                                    value="{{ $bagWeightInKgSum }}" readonly>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Bags Rate</strong></td>
                            <td>
                                <input type="text" class="form-control" name="bag_rate"
                                    value="{{ $purchaseOrder->bag_rate ?? 0 }}" readonly>
                            </td>
                            <td>N/A</td>
                            <td>
                                <input type="text" class="form-control" name="bag_rate_amount"
                                    value="{{ $bagsRateSum }}" readonly>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Loading weighbridge</strong></td>
                            <td>N/A</td>
                            <td>N/A</td>
                            <td>
                                <input type="text" class="form-control" name="loading_weighbridge_amount"
                                    value="{{ $loadingWeighbridgeSum }}" readonly>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>Amount</label>
                    <input type="text" class="form-control" name="total_amount" value="{{ $amount }}"
                        readonly>
                </div>
            </div>

            <div class="col-md-6">
                <div class="form-group">
                    <label>Paid Amount</label>
                    <input type="number" step="0.01" readonly class="form-control" name="paid_amount"
                        value="{{ $paidAmount }}" placeholder="Enter paid amount">
                </div>
            </div>
            <div class="col-12">
                <hr class="border">
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>Remaining</label>
                    <input type="text" class="form-control" name="remaining_amount" value="{{ $remaining }}"
                        readonly>
                </div>
            </div>

            <div class="col-md-6">
                <div class="form-group">
                    <label>Payment Request</label>
                    <input type="number" step="0.01" class="form-control" name="payment_request_amount"
                        value="{{ 0 }}" placeholder="Enter payment request">
                </div>
            </div>

            <div class="col-md-6">
                <div class="form-group">
                    <label>Advance Freight</label>
                    <input type="text" class="form-control" name="advance_freight" value="{{ $advanceFreight }}"
                        readonly>
                </div>
            </div>

            <div class="col-md-6">
                <div class="form-group">
                    <label>Freight Pay Request</label>
                    <input type="number" step="0.01" class="form-control" name="freight_pay_request_amount"
                        value="{{ 0 }}" placeholder="Enter freight pay request">
                </div>
            </div>
        </div>
    @endif
</div>
{{-- </form> --}}

<style>
    .section-title {
        color: #2c3e50;
        font-weight: 600;
        margin-bottom: 15px;
    }

    .form-control[readonly] {
        background-color: #f8f9fa;
        opacity: 1;
    }
</style>

@if ($hasLoadingWeight)
    <script>
        $(document).ready(function() {
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

            // $('input[name="paid_amount"]').on('input', function() {
            //     const amount = parseFloat({{ $amount }});
            //     const paidAmount = parseFloat($(this).val()) || 0;
            //     const remaining = amount - paidAmount;
            //     $('input[name="remaining_amount"]').val(remaining.toFixed(2));
            // });

            $('input[name="payment_request_amount"]').on('input', function() {
                const amount = parseFloat({{ $amount }});
                const paidAmount = parseFloat({{ $pRsSum }});
                const paymentRequest = parseFloat($(this).val()) || 0;
                const remaining = (amount - paymentRequest - paidAmount);

                $('input[name="remaining_amount"]').val(remaining.toFixed(2));
            });
        });
    </script>
@endif
