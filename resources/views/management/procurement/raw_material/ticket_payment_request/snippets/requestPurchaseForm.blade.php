@php
    $isThadda = $arrivalTicket->sauda_type_id == 2;

    $hasLoadingWeight = true;

    // $hasLoadingWeight = false;

    // if ($isThadda) {
    //     if ($purchaseOrder && $purchaseOrder->purchaseFreight && $purchaseOrder->purchaseFreight->loading_weight) {
    //         $hasLoadingWeight = true;
    //     }
    // } else {
    //     if ($arrivalTicket && $arrivalTicket->freight && $arrivalTicket->freight->arrived_weight) {
    //         $hasLoadingWeight = true;
    //     }
    // }

    $isSlabs = false;
    $isCompulsury = false;
    $showLumpSum = false;
    $totalAmount = 0;
    $remaining = 0;

    if (
        isset($samplingRequest->is_lumpsum_deduction) &&
        $samplingRequest->is_lumpsum_deduction &&
        $samplingRequest->lumpsum_deduction > 0
    ) {
        $showLumpSum = true;
    }

    $bagWeight = $arrivalTicket->bag_weight ?? 0;
    // $bagRate = $purchaseOrder->bag_rate ?? 0;
    $bagRate = 0;

    $totalDeductions = 0;
    $loadingWeight = $arrivalTicket->freight->arrived_weight ?? 0;
    $noOfBags = $arrivalTicket->bags ?? 0;
    $ratePerKg = $purchaseOrder->rate_per_kg ?? 0;
    $kantaCharges = $arrivalTicket->freight->karachi_kanta_charges ?? 0;
    $grossFreightAmount = $arrivalTicket->freight->gross_freight_amount ?? 0;
    $netWeight = $loadingWeight - $bagWeight * $noOfBags;

    foreach ($samplingRequestCompulsuryResults as $slab) {
        if (!$slab->applied_deduction) {
            continue;
        }
        $isCompulsury = true;
        $deductionValue = $slab->applied_deduction ?? 0;
        $totalDeductions += $deductionValue * $netWeight;
    }

    foreach ($samplingRequestResults as $slab) {
        if (!$slab->applied_deduction) {
            continue;
        }
        $isSlabs = true;
        $deductionValue = $slab->applied_deduction ?? 0;
        $totalDeductions += $deductionValue * $netWeight;
    }

    $avgRate = 0;
    if ($noOfBags > 0) {
        $avgRate = $loadingWeight / $noOfBags;
    }

    $bagWeightInKgSum = $ratePerKg * ($bagWeight * $noOfBags);
    $loadingWeighbridgeSum = $kantaCharges / 2;
    $bagsRateSum = $bagRate * $noOfBags;
    $requestedAmount = $requestedAmount ?? 0;
    $paidAmount = $approvedAmount ?? 0;
    $advanceFreight = $ticket->purchaseFreight->advance_freight ?? 0;
    $remainingFreight = $advanceFreight - ($pRsSumForFreight ?? 0);
    $totalDeductions += $bagsRateSum + $loadingWeighbridgeSum + $bagWeightInKgSum - $grossFreightAmount;
    $totalAmount += $bagWeightInKgSum + $loadingWeighbridgeSum;
    $grossAmount = $ratePerKg * $loadingWeight;
    $existingOtherDeductionKg = $otherDeduction->other_deduction_kg ?? 0;
    $existingOtherDeductionAmount = $otherDeduction->other_deduction_value ?? 0;
    $isApprovalPage = isset($isRequestApprovalPage) && $isRequestApprovalPage;
    $currentPaymentAmount = 0;
    $currentFreightAmount = 0;
    $isPaymentType = 0;

    if ($isApprovalPage && isset($paymentRequest)) {
        if ($paymentRequest->request_type === 'payment') {
            $currentPaymentAmount = $paymentRequest->amount;
            $isPaymentType = 1;
        } else {
            $currentFreightAmount = $paymentRequest->amount;
            $isPaymentType = 2;
        }
    }
@endphp

<style>
    .tooltip-container {
        position: relative;
        cursor: pointer;
    }

    .tooltip-content {
        display: none;
        position: absolute;
        bottom: 100%;
        left: 50%;
        transform: translateX(-50%);
        background: white;
        border: 1px solid #ddd;
        padding: 10px;
        min-width: 200px;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        z-index: 100;
    }

    .tooltip-container:hover .tooltip-content {
        display: block;
    }

    .section-title {
        color: #2c3e50;
        font-weight: 600;
        margin-bottom: 15px;
    }

    .form-control[readonly] {
        background-color: #f8f9fa;
        opacity: 1;
    }

    .editable-field {
        background-color: #fff !important;
        border-color: #27489a;
    }

    .other-deduction-row {
        background-color: #f8f9fa;
        border-left: 4px solid #27489a;
    }

    .approval-editable {
        background-color: #fff3cd !important;
        border-color: #ffc107 !important;
    }
</style>

<input type="hidden" name="purchase_order_id" value="{{ $purchaseOrder->id }}">
<input type="hidden" name="ticket_id" value="{{ $arrivalTicket->id ?? '' }}">
<input type="hidden" id="original_bag_weight" value="{{ $bagWeight }}">
<input type="hidden" id="loading_weight" value="{{ $loadingWeight }}">
<input type="hidden" id="no_of_bags" value="{{ $noOfBags }}">
<input type="hidden" id="rate_per_kg" value="{{ $ratePerKg }}">
<input type="hidden" id="bag_rate" value="{{ $bagRate }}">
<input type="hidden" id="kanta_charges" value="{{ $kantaCharges }}">

<!-- Store sampling data for JS calculations -->
<script type="text/javascript">
    window.samplingData = {
        samplingResults: [
            @foreach ($samplingRequestResults as $slab)
                @if ($slab->applied_deduction)
                    {
                        id: {{ $slab->id }},
                        applied_deduction: {{ $slab->applied_deduction ?? 0 }},
                        deduction_type: '{{ $slab->deduction_type ?? 'amount' }}',
                        calculation_base_type: {{ $slab->slabType->calculation_base_type ?? 1 }},
                        matching_slabs: @json($slab->matching_slabs ?? []),
                        rm_po_slabs: @json($slab->rm_po_slabs ?? [])
                    },
                @endif
            @endforeach
        ],
        compulsoryResults: [
            @foreach ($samplingRequestCompulsuryResults as $slab)
                @if ($slab->applied_deduction)
                    {
                        id: {{ $slab->id }},
                        applied_deduction: {{ $slab->applied_deduction ?? 0 }}
                    },
                @endif
            @endforeach
        ],
        SLAB_TYPE_PERCENTAGE: {{ SLAB_TYPE_PERCENTAGE ?? 2 }},
        existingOtherDeduction: {
            kg_value: {{ $existingOtherDeductionKg }},
            deduction_amount: {{ $existingOtherDeductionAmount }}
        }
    };
</script>

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
                value="{{ $purchaseOrder->min_quantity }} - {{ $purchaseOrder->max_quantity }}" readonly>
            <input type="hidden" name="min_contract_range" value="{{ $purchaseOrder->min_quantity }}">
            <input type="hidden" name="max_contract_range" value="{{ $purchaseOrder->max_quantity }}">
        </div>
    </div>
</div>

<div class="row mt-3">
    <div class="col-12">
        <h6 class="header-heading-sepration">
            Loading Information
        </h6>
    </div>
    <div class="col-md-12 mb-3 d-none">
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
        <input type="hidden" name="{{ $hasLoadingWeight ? '' : 'loading_type' }}"
            value="{{ $hasLoadingWeight ? 'loading' : 'without_loading' }}">
    </div>

    @if ($hasLoadingWeight)
        <div id="loading-section" class="row w-100 mx-auto px-0">
            <div class="col-md-3">
                <div class="form-group">
                    <label>Truck #</label>
                    <input type="text" class="form-control" name="truck_no"
                        value="{{ $arrivalTicket->truck_no ?? 'N/A' }}" readonly>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Arrival Date</label>
                    {{-- @dd($arrivalTicket->loading_date->format('d-M-Y')) --}}
                    <input type="text" class="form-control" name="loading_date"
                        value="{{ $arrivalTicket && $arrivalTicket->freight->created_at ? $arrivalTicket->freight->created_at->format('d-M-Y') : 'N/A' }}"
                        readonly>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Bilty #</label>
                    <input type="text" class="form-control" name="bilty_no"
                        value="{{ $arrivalTicket->bilty_no ?? 'N/A' }}" readonly>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Station</label>
                    <input type="text" class="form-control" name="station"
                        value="{{ $arrivalTicket->station_name ?? 'N/A' }}" readonly>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>No of Bags</label>
                    <input type="text" class="form-control" name="no_of_bags" value="{{ $noOfBags }}"
                        readonly>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Arrival Weight</label>
                    <input type="text" class="form-control" name="loading_weight" value="{{ $loadingWeight }}"
                        readonly>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Avg Rate</label>
                    <input type="text" class="form-control" name="avg_rate"
                        value="{{ number_format($avgRate, 2) }}" readonly>
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
                            <tbody id="sampling-results-tbody">
                                @if (count($samplingRequestResults) != 0)
                                    @foreach ($samplingRequestResults as $slab)
                                        @php
                                            if (!$slab->applied_deduction) {
                                                continue;
                                            }
                                            $dValCalculatedOn = $slab->slabType->calculation_base_type;
                                            $appliedDeduction = $slab->applied_deduction ?? 0;
                                            $matchingSlabs = $slab->matching_slabs ?? [];
                                            $val = $slab->applied_deduction;
                                            $deductionValue = 0;
                                            $sumOfMatchingValues = '';

                                            if ($dValCalculatedOn == SLAB_TYPE_PERCENTAGE && $matchingSlabs) {
                                                usort($matchingSlabs, function ($a, $b) {
                                                    return floatval($a['from']) <=> floatval($b['from']);
                                                });

                                                $rmPoSlabs = $slab->rm_po_slabs ?? [];
                                                $highestRmPoEnd = 0;
                                                foreach ($rmPoSlabs as $rmPoSlab) {
                                                    $rmPoTo = $rmPoSlab->to ? floatval($rmPoSlab->to) : 0;
                                                    if ($rmPoTo > $highestRmPoEnd) {
                                                        $highestRmPoEnd = $rmPoTo;
                                                    }
                                                }

                                                foreach ($matchingSlabs as $mSlab) {
                                                    $from = floatval($mSlab['from']);
                                                    $to = floatval($mSlab['to']);
                                                    $isTiered = intval($mSlab['is_tiered']);
                                                    $deductionVal = floatval($mSlab['deduction_value'] ?? 0);

                                                    if ($val >= $from) {
                                                        $effectiveFrom = max($from, $highestRmPoEnd + 1);
                                                        $effectiveTo = min($to, $val);

                                                        if ($effectiveFrom <= $effectiveTo) {
                                                            if ($isTiered === 1) {
                                                                $applicableAmount = $effectiveTo - $effectiveFrom + 1;
                                                                $sumOfMatchingValues .=
                                                                    "$deductionVal x $applicableAmount = " .
                                                                    $deductionVal * $applicableAmount .
                                                                    '<br>';
                                                                $deductionValue += $deductionVal * $applicableAmount;
                                                            } else {
                                                                $deductionValue += $deductionVal;
                                                                $sumOfMatchingValues .= "$deductionVal<br>";
                                                            }
                                                        }
                                                    }
                                                }

                                                if (!empty($rmPoSlabs)) {
                                                    $sumOfMatchingValues .= '<br><br>RM PO Slabs (Free Ranges):<br>';
                                                    foreach ($rmPoSlabs as $rmPoSlab) {
                                                        $sumOfMatchingValues .= "{$rmPoSlab->from} - {$rmPoSlab->to}<br>";
                                                    }
                                                    $sumOfMatchingValues .= "<br>Only values above $highestRmPoEnd are calculated";
                                                }
                                            } else {
                                                $deductionValue = $appliedDeduction;
                                            }

                                            // Calculate deduction amount based on net weight
                                            $calculatedValue = $deductionValue * $netWeight;
                                            if (($slab->deduction_type ?? 'amount') !== 'amount') {
                                                $calculatedValue = ($calculatedValue / 100) * $ratePerKg;
                                            }

                                            $totalAmount += $calculatedValue;
                                        @endphp

                                        <tr data-slab-id="{{ $slab->id }}">
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
                                                        value="{{ $slab->suggested_deduction ?? 0 }}"
                                                        placeholder="Suggested Deduction" readonly>
                                                    <div class="input-group-append">
                                                        <span
                                                            class="input-group-text text-sm">{{ ($slab->deduction_type ?? 'amount') == 'amount' ? 'Rs.' : "KG's" }}</span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="input-group mb-0">
                                                    <input type="text" class="form-control applied-deduction-input"
                                                        name="sampling_results[{{ $slab->id }}][applied_deduction]"
                                                        value="{{ $deductionValue }}"
                                                        placeholder="Suggested Deduction" {{-- {{ $isApprovalPage ? '' : 'readonly' }} --}}
                                                        readonly data-slab-id="{{ $slab->id }}"
                                                        data-deduction-type="{{ $slab->deduction_type ?? 'amount' }}"
                                                        data-applied-deduction="{{ $slab->applied_deduction ?? 0 }}">
                                                    <div class="input-group-append">
                                                        {{-- <span
                                                            class="input-group-text text-sm">{{ $slab->slabType->qc_symbol }}</span> --}}
                                                        <span
                                                            class="input-group-text text-sm">{{ ($slab->deduction_type ?? 'amount') == 'amount' ? 'Rs.' : "KG's" }}</span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="input-group mb-0">
                                                    <input type="text"
                                                        class="form-control deduction-amount-display"
                                                        name="sampling_results[{{ $slab->id }}][deduction_amount_display]"
                                                        value="{{ number_format($calculatedValue, 2) }}"
                                                        placeholder="deduction_amount" readonly
                                                        data-slab-id="{{ $slab->id }}">
                                                    <input type="hidden" class="form-control deduction-amount-hidden"
                                                        name="sampling_results[{{ $slab->id }}][deduction_amount]"
                                                        value="{{ $calculatedValue }}" placeholder="deduction_amount"
                                                        readonly data-slab-id="{{ $slab->id }}">
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

                                        @php
                                            $compulsoryCalculatedValue = ($slab->applied_deduction ?? 0) * $netWeight;
                                        @endphp

                                        <tr data-compulsory-id="{{ $slab->id }}">
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
                                                    <input type="text"
                                                        class="form-control compulsory-applied-deduction"
                                                        name="compulsory_results[{{ $slab->id }}][applied_deduction]"
                                                        value="{{ $slab->applied_deduction }}"
                                                        placeholder="Suggested Deduction" {{-- {{ $isApprovalPage ? '' : 'readonly' }} --}}
                                                        readonly data-compulsory-id="{{ $slab->id }}"
                                                        data-applied-deduction="{{ $slab->applied_deduction ?? 0 }}">
                                                    <div class="input-group-append">
                                                        <span
                                                            class="input-group-text text-sm">{{ SLAB_TYPES_CALCULATED_ON[$slab->qcParam->calculation_base_type ?? 1] }}</span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="input-group mb-0">
                                                    <input type="text"
                                                        class="form-control compulsory-deduction-amount"
                                                        name="compulsory_results[{{ $slab->id }}][deduction_amount]"
                                                        value="{{ number_format($compulsoryCalculatedValue, 2) }}"
                                                        placeholder="deduction_amount" readonly
                                                        data-compulsory-id="{{ $slab->id }}">
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                @endif

                                <!-- Other Deduction Row -->
                                <tr class="other-deduction-row" data-other-deduction="true">
                                    <td><strong>Other Deduction</strong>
                                        <input type="hidden" name="other_deduction[slab_name]"
                                            value="Other Deduction">
                                    </td>
                                    <td>N/A</td>
                                    <td>N/A</td>
                                    <td>
                                        <div class="input-group mb-0">
                                            <input type="number" step="any" class="form-control editable-field"
                                                name="other_deduction[kg_value]" id="other_deduction_kg"
                                                value="{{ $existingOtherDeductionKg }}" placeholder="Enter KG value">
                                            <div class="input-group-append">
                                                <span class="input-group-text text-sm">KG</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="input-group mb-0">
                                            <input type="text" class="form-control"
                                                name="other_deduction[kg_amount]" id="other_deduction_amount_display"
                                                value="{{ number_format($existingOtherDeductionAmount, 2) }}"
                                                readonly>
                                            <input type="hidden" class="form-control"
                                                name="other_deduction[deduction_amount]" id="other_deduction_amount"
                                                value="{{ $existingOtherDeductionAmount }}">
                                        </div>
                                    </td>
                                </tr>
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
                                <input type="number" step="0.01" class="form-control editable-field"
                                    name="bag_weight" id="bag_weight_input" value="{{ $bagWeight }}">
                            </td>
                            <td>
                                <input type="text" class="form-control" name="bag_weight_total"
                                    id="bag_weight_total" value="{{ $bagWeight * $noOfBags }}" readonly>
                            </td>
                            <td>
                                <input type="number" step="0.01" class="form-control editable-field"
                                    name="bag_weight_amount" id="bag_weight_amount" value="{{ $bagWeightInKgSum }}">
                                <input type="hidden" class="form-control" name="bag_weight_amount_display"
                                    id="bag_weight_amount_display" value="{{ number_format($bagWeightInKgSum, 2) }}">
                            </td>
                        </tr>
                        <tr class="d-none">
                            <td><strong>Bags Rate</strong></td>
                            <td>
                                <input type="text" class="form-control" name="bag_rate"
                                    value="{{ number_format($bagRate, 2) }}" readonly>
                            </td>
                            <td>N/A</td>
                            <td>
                                <input type="text" class="form-control" name="bag_rate_amount_display"
                                    id="bag_rate_amount_display" value="{{ number_format($bagsRateSum, 2) }}"
                                    readonly>
                                <input type="hidden" class="form-control" name="bag_rate_amount"
                                    id="bag_rate_amount" value="{{ $bagsRateSum }}" readonly>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Loading weighbridge</strong></td>
                            <td>N/A</td>
                            <td>N/A</td>
                            <td>
                                <input type="text" class="form-control" name="loading_weighbridge_amount_display"
                                    id="loading_weighbridge_amount_display"
                                    value="{{ number_format($loadingWeighbridgeSum, 2) }}" readonly>
                                <input type="hidden" class="form-control" name="loading_weighbridge_amount"
                                    id="loading_weighbridge_amount" value="{{ $loadingWeighbridgeSum }}" readonly>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Freight Deduction</strong></td>
                            <td>N/A</td>
                            <td>N/A</td>
                            <td>
                                <input type="text" class="form-control" name="freight_deduction_amount_display"
                                    id="freight_deduction_amount_display"
                                    value="{{ number_format($grossFreightAmount, 2) }}" readonly>
                                <input type="hidden" class="form-control" name="loading_weighbridge_amount"
                                    id="freight_deduction_amount" value="{{ $grossFreightAmount }}" readonly>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @php
        $totalAmount = $ratePerKg * $loadingWeight - ($totalAmount ?? 0) + ($bagsRateSum ?? 0);
    @endphp
    {{-- @if (!$isApprovalPage) --}}
    <div class="col mb-3 px-0">
        <div class="row mx-auto ">
            <div class="col-md-6">
                <div class="form-group">
                    <label>Amount</label>
                    <input type="text" class="form-control" name="total_amount_display" id="total_amount_display"
                        value="{{ number_format($totalAmount, 2) }}" readonly>
                    <input type="hidden" class="form-control" name="total_amount" id="total_amount"
                        value="{{ $totalAmount }}" readonly>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Requested Amount</label>
                    <input type="number" step="0.01" readonly class="form-control" name="requested_amount"
                        id="requested_amount" value="{{ $requestedAmount }}" placeholder="Enter requested amount">
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Paid Amount</label>
                    <input type="number" step="0.01" readonly class="form-control" name="paid_amount"
                        id="paid_amount" value="{{ $paidAmount }}" placeholder="Enter paid amount">
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>Remaining</label>
                    <input type="text" class="form-control" name="remaining_amount" id="remaining_amount"
                        value="{{ number_format($totalAmount - $requestedAmount, 2) }}" readonly>
                </div>
            </div>
            @if (!$isApprovalPage)
                <div class="col">
                    <div class="form-group">
                        <label>Percentage</label>
                        <input type="number" min="0" max="100" step="0.01"
                            class="form-control percentage-input" value="0" placeholder="Enter percentage">
                    </div>
                </div>
                <div class="col">
                    <div class="form-group">
                        <label>Payment Request</label>
                        <input type="number" step="0.01" class="form-control payment-request-input"
                            name="{{ $isApprovalPage ? '' : 'payment_request_amount' }}"
                            value="{{ $currentPaymentAmount }}" placeholder="Enter payment request">
                    </div>
                </div>
            @endif
        </div>
    </div>
    {{-- @endif --}}

</div>

@if ($hasLoadingWeight)
    <script>
        $(document).ready(function() {
            $('[data-toggle="tooltip"]').tooltip();

            const $loadingRadio = $('#loading');
            const $withoutLoadingRadio = $('#without_loading');
            const $loadingSection = $('#loading-section');
            const $contractRangeField = $('.contract-range-field');

            const originalBagWeight = parseFloat($('#original_bag_weight').val()) || 0;
            const loadingWeight = parseFloat($('#loading_weight').val()) || 0;
            const noOfBags = parseFloat($('#no_of_bags').val()) || 0;
            const ratePerKg = parseFloat($('#rate_per_kg').val()) || 0;
            const bagRate = parseFloat($('#bag_rate').val()) || 0;
            const kantaCharges = parseFloat($('#kanta_charges').val()) || 0;
            const paidAmount = parseFloat({{ $requestedAmount }});

            function toggleSections() {
                if ($loadingRadio.is(':checked')) {
                    $loadingSection.show();
                    $contractRangeField.show();
                } else {
                    $loadingSection.hide();
                    $contractRangeField.hide();
                }
            }

            function calculateNetWeight() {
                const currentBagWeight = parseFloat($('#bag_weight_input').val()) || 0;
                return loadingWeight - (currentBagWeight * noOfBags);
            }

            function calculateSlabDeduction(slabData, netWeight) {
                const dValCalculatedOn = slabData.calculation_base_type;
                const appliedDeduction = slabData.applied_deduction;
                const matchingSlabs = slabData.matching_slabs || [];
                const rmPoSlabs = slabData.rm_po_slabs || [];
                const val = slabData.applied_deduction;
                let deductionValue = 0;

                if (dValCalculatedOn === window.samplingData.SLAB_TYPE_PERCENTAGE && matchingSlabs.length > 0) {

                    matchingSlabs.sort((a, b) => parseFloat(a.from) - parseFloat(b.from));

                    let highestRmPoEnd = 0;
                    rmPoSlabs.forEach(rmPoSlab => {
                        const rmPoTo = rmPoSlab.to ? parseFloat(rmPoSlab.to) : 0;
                        if (rmPoTo > highestRmPoEnd) {
                            highestRmPoEnd = rmPoTo;
                        }
                    });

                    matchingSlabs.forEach(mSlab => {
                        const from = parseFloat(mSlab.from);
                        const to = parseFloat(mSlab.to);
                        const isTiered = parseInt(mSlab.is_tiered);
                        const deductionVal = parseFloat(mSlab.deduction_value || 0);

                        if (val >= from) {
                            const effectiveFrom = Math.max(from, highestRmPoEnd + 1);
                            const effectiveTo = Math.min(to, val);

                            if (effectiveFrom <= effectiveTo) {
                                if (isTiered === 1) {
                                    const applicableAmount = effectiveTo - effectiveFrom + 1;
                                    deductionValue += deductionVal * applicableAmount;
                                } else {
                                    deductionValue += deductionVal;
                                }
                            }
                        }
                    });
                } else {
                    deductionValue = appliedDeduction;
                }

                let calculatedValue = deductionValue * netWeight;
                if (slabData.deduction_type !== 'amount') {
                    calculatedValue = (calculatedValue / 100) * ratePerKg;
                }

                return calculatedValue;
            }

            function updateOtherDeduction() {
                const otherDeductionKg = parseFloat($('#other_deduction_kg').val()) || 0;
                const bagWeightTotal = parseFloat($('#bag_weight_total').val()) || 0;
                const otherDeductionAmount = otherDeductionKg * (loadingWeight - bagWeightTotal);

                $('#other_deduction_amount').val(otherDeductionAmount);
                $('#other_deduction_amount_display').val(otherDeductionAmount.toFixed(2));

                return otherDeductionAmount;
            }

            function updateSamplingResultsDeductions() {
                const netWeight = calculateNetWeight();
                let totalSamplingAmount = 0;

                window.samplingData.samplingResults.forEach(slabData => {
                    const calculatedValue = calculateSlabDeduction(slabData, netWeight);
                    totalSamplingAmount += calculatedValue;

                    $(`.deduction-amount-display[data-slab-id="${slabData.id}"]`).val(calculatedValue
                        .toFixed(2));
                    $(`.deduction-amount-hidden[data-slab-id="${slabData.id}"]`).val(calculatedValue);
                });

                window.samplingData.compulsoryResults.forEach(slabData => {
                    const calculatedValue = slabData.applied_deduction * netWeight;
                    totalSamplingAmount += calculatedValue;

                    $(`.compulsory-deduction-amount[data-compulsory-id="${slabData.id}"]`).val(
                        calculatedValue.toFixed(2));
                });

                const otherDeductionAmount = updateOtherDeduction();
                totalSamplingAmount += otherDeductionAmount;

                return totalSamplingAmount;
            }

            function updateBagWeightCalculations() {
                const currentBagWeight = parseFloat($('#bag_weight_input').val()) || 0;
                const bagWeightAmount = parseFloat($('#bag_weight_amount').val()) || 0;

                const bagWeightTotal = currentBagWeight * noOfBags;
                $('#bag_weight_total').val(bagWeightTotal.toFixed(2));

                const calculatedBagWeightAmount = ratePerKg * bagWeightTotal;

                if (Math.abs(bagWeightAmount - calculatedBagWeightAmount) > 0.01) {
                    // Amount was manually changed, calculate weight
                    const newBagWeight = bagWeightAmount / (ratePerKg * noOfBags);
                    if (!isNaN(newBagWeight) && isFinite(newBagWeight)) {
                        $('#bag_weight_input').val(newBagWeight.toFixed(4));
                        $('#bag_weight_total').val((newBagWeight * noOfBags).toFixed(2));
                    }
                }
            }

            function updateAllCalculations() {
                updateBagWeightCalculations();

                const currentBagWeight = parseFloat($('#bag_weight_input').val()) || 0;
                const bagWeightAmount = parseFloat($('#bag_weight_amount').val()) || 0;

                const bagRateAmount = bagRate * noOfBags;
                $('#bag_rate_amount').val(bagRateAmount);
                $('#bag_rate_amount_display').val(bagRateAmount.toFixed(2));

                const loadingWeighbridgeAmount = kantaCharges / 2;
                $('#loading_weighbridge_amount').val(loadingWeighbridgeAmount);
                $('#loading_weighbridge_amount_display').val(loadingWeighbridgeAmount.toFixed(2));

                const totalSamplingDeductions = updateSamplingResultsDeductions();

                const grossAmount = ratePerKg * loadingWeight;
                const totalDeductionsForFormula = totalSamplingDeductions + bagWeightAmount +
                    loadingWeighbridgeAmount;
                const totalAmount = grossAmount - totalDeductionsForFormula + bagRateAmount - parseInt(
                    {{ $grossFreightAmount ?? 0 }});

                $('#total_amount').val(totalAmount);
                $('#total_amount_display').val(totalAmount.toFixed(2));

                const remainingAmount = totalAmount - paidAmount;
                $('#remaining_amount').val(remainingAmount.toFixed(2));

                $('#bag_weight_amount_display').val(bagWeightAmount.toFixed(2));
            }

            $('#bag_weight_input').on('input', function() {
                const currentBagWeight = parseFloat($(this).val()) || 0;
                const bagWeightAmount = ratePerKg * currentBagWeight * noOfBags;
                $('#bag_weight_amount').val(bagWeightAmount.toFixed(2));
                updateAllCalculations();
            });

            $('#bag_weight_amount').on('input', function() {
                updateAllCalculations();
            });

            $('#other_deduction_kg').on('input', function() {
                updateAllCalculations();
            });

            $('input[name="payment_request_amount"]').on('input', function() {
                // const totalAmount = parseFloat($('#total_amount').val()) || 0;
                // const
                const totalAmount = parseFloat($('#total_amount').val()) || 0;
                const paidAmount = parseFloat($('#paid_amount').val()) || 0;
                // const paymentRequest = parseFloat($(this).val()) || 0;
                // const remaining = (totalAmount - paidAmount) - paymentRequest;
                // const totalAmount = 178890;  
                // const paymentRequest = 3000;
                // const paidAmount = 179282;
                // const remaining = (totalAmount - paidAmount) - paymentRequest;


                // const totalAmount = 178890;
                // const paidAmount = 179282;
                const originalRequested = {{ $currentPaymentAmount }};
                const newRequested = parseFloat($(this).val()) || 0;

                const remaining = totalAmount - (paidAmount - originalRequested + newRequested);

                // console.log({
                //     totalAmount,
                //     paymentRequest,
                //     paidAmount
                // });

                $('#remaining_amount').val(remaining.toFixed(2));
            });

            const percentageInput = $('.percentage-input');
            const paymentRequestInput = $('.payment-request-input');

            percentageInput.on('input', function() {
                let percentage = parseFloat($(this).val()) || 0;
                if (percentage > 100) {
                    percentage = 100;
                    $(this).val(100);
                }

                const totalAmount = parseFloat($('#total_amount').val()) || 0;
                const remainingAmount = totalAmount - paidAmount;
                const amount = (remainingAmount * percentage) / 100;
                paymentRequestInput.val(amount.toFixed(2));
            });

            paymentRequestInput.on('input', function() {
                const totalAmount = parseFloat($('#total_amount').val()) || 0;
                const remainingAmount = totalAmount - paidAmount;
                let amount = parseFloat($(this).val()) || 0;

                if (amount > remainingAmount) {
                    amount = remainingAmount;
                    $(this).val(remainingAmount.toFixed(2));
                }

                const percentage = remainingAmount > 0 ? (amount / remainingAmount) * 100 : 0;
                percentageInput.val(percentage.toFixed(2));
            });

            $('input[name="freight_pay_request_amount"]').on('input', function() {
                const amount = parseFloat({{ $advanceFreight }});
                const paidAmount = parseFloat({{ $pRsSumForFreight }});
                const paymentRequest = parseFloat($(this).val()) || 0;
                const remaining = (amount - paymentRequest - paidAmount);

                $('input[name="remaining_freight"]').val(remaining.toFixed(2));
            });

            const remainingAmountF = parseFloat($('input[name="remaining_freight"]').val()) || 0;
            const percentageInputF = $('.percentage-input-freight');
            const paymentRequestInputF = $('.payment-request-freifht');

            percentageInputF.on('input', function() {
                let percentage = parseFloat($(this).val()) || 0;
                if (percentage > 100) {
                    percentage = 100;
                    $(this).val(100);
                }

                const amount = (remainingAmountF * percentage) / 100;
                paymentRequestInputF.val(amount.toFixed(2));
            });

            paymentRequestInputF.on('input', function() {
                let amount = parseFloat($(this).val()) || 0;

                if (amount > remainingAmountF) {
                    amount = remainingAmountF;
                    $(this).val(remainingAmountF.toFixed(2));
                }

                const percentage = remainingAmountF > 0 ? (amount / remainingAmountF) * 100 : 0;
                percentageInputF.val(percentage.toFixed(2));
            });

            toggleSections();
            updateAllCalculations();

            $loadingRadio.on('change', toggleSections);
            $withoutLoadingRadio.on('change', toggleSections);
        });
    </script>
@endif
