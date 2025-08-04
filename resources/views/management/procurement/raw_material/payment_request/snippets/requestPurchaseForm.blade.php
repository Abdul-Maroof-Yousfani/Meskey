@php
    $hasLoadingWeight = false;
    if ($ticket && $ticket->purchaseFreight && $ticket->purchaseFreight->loading_weight) {
        $hasLoadingWeight = true;
    }
    $purchaseOrder = $ticket->purchaseOrder ?? null;
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
    $totalDeductions = 0;
    $loadingWeight = $ticket->purchaseFreight->loading_weight ?? 0;
    $bagWeight = $ticket->bag_weight ?? 0;
    $noOfBags = $ticket->purchaseFreight->no_of_bags ?? 0;
    $ratePerKg = $purchaseOrder->rate_per_kg ?? 0;
    $bagRate = $ticket->bag_rate ?? 0;
    $kantaCharges = $ticket->purchaseFreight->kanta_charges ?? 0;
    $netWeight = $loadingWeight - $bagWeight * $noOfBags;

    $lumpsumDeduction = $samplingRequest->lumpsum_deduction ?? 0;
    $lumpsumDeductionKgs = $samplingRequest->lumpsum_deduction_kgs ?? 0;
    $lumpsumCalculatedValue = 0;
    $lumpsumKgsCalculatedValue = 0;

    if ($showLumpSum) {
        if ($lumpsumDeduction > 0) {
            $lumpsumCalculatedValue = $lumpsumDeduction * $netWeight;
            $totalDeductions += $lumpsumCalculatedValue;
        }

        if ($lumpsumDeductionKgs > 0) {
            $lumpsumKgsCalculatedValue = $lumpsumDeductionKgs * $netWeight;
            $lumpsumKgsCalculatedValue = ($lumpsumKgsCalculatedValue / 100) * $ratePerKg;
            $totalDeductions += $lumpsumKgsCalculatedValue;
        }
    } else {
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
    $totalDeductions += $bagsRateSum + $loadingWeighbridgeSum + $bagWeightInKgSum;
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

    /* .lumpsum-row {
        background-color: #e8f4fd;
        border-left: 4px solid #007bff;
    } */

    .approval-editable {
        background-color: #fff3cd !important;
        border-color: #ffc107 !important;
    }
</style>

<input type="hidden" name="purchase_order_id" value="{{ $ticket->purchase_order_id ?? '' }}">
<input type="hidden" name="ticket_id" value="{{ $ticket->id ?? '' }}">
<input type="hidden" id="original_bag_weight" value="{{ $bagWeight }}">
<input type="hidden" id="original_bag_rate" value="{{ $bagRate }}">
<input type="hidden" id="loading_weight" value="{{ $loadingWeight }}">
<input type="hidden" id="no_of_bags" value="{{ $noOfBags }}">
<input type="hidden" id="rate_per_kg" value="{{ $ratePerKg }}">
<input type="hidden" id="kanta_charges" value="{{ $kantaCharges }}">
<input type="hidden" id="show_lumpsum" value="{{ $showLumpSum ? 1 : 0 }}">
<input type="hidden" id="lumpsum_deduction" value="{{ $lumpsumDeduction }}">
<input type="hidden" id="lumpsum_deduction_kgs" value="{{ $lumpsumDeductionKgs }}">

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
        },
        lumpsum: {
            showLumpSum: {{ $showLumpSum ? 1 : 0 }},
            lumpsumDeduction: {{ $lumpsumDeduction }},
            lumpsumDeductionKgs: {{ $lumpsumDeductionKgs }}
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
            <label>Ticket Number</label>
            <input type="text" class="form-control" name="ticket_no" value="{{ $ticket->unique_no ?? 'N/A' }}"
                readonly>
        </div>
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
                value="{{ $purchaseOrder->min_quantity ?? 0 }} - {{ $purchaseOrder->max_quantity ?? 0 }}" readonly>
            <input type="hidden" name="min_contract_range" value="{{ $purchaseOrder->min_quantity ?? 0 }}">
            <input type="hidden" name="max_contract_range" value="{{ $purchaseOrder->max_quantity ?? 0 }}">
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
        <input type="hidden" name="{{ $hasLoadingWeight ? '' : 'loading_type' }}"
            value="{{ $hasLoadingWeight ? 'loading' : 'without_loading' }}">
    </div>
    @if ($hasLoadingWeight)
        <div id="loading-section" class="row w-100 mx-auto px-0">
            <div class="col-md-3">
                <div class="form-group">
                    <label>Truck #</label>
                    <input type="text" class="form-control" name="truck_no"
                        value="{{ $ticket->purchaseFreight->truck_no ?? 'N/A' }}" readonly>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Loading Date</label>
                    <input type="text" class="form-control" name="loading_date"
                        value="{{ $ticket && $ticket->purchaseFreight && $ticket->purchaseFreight->loading_date ? $ticket->purchaseFreight->loading_date->format('d-M-Y') : 'N/A' }}"
                        readonly>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Bilty #</label>
                    <input type="text" class="form-control" name="bilty_no"
                        value="{{ $ticket->purchaseFreight->bilty_no ?? 'N/A' }}" readonly>
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
                    <input type="text" class="form-control" name="no_of_bags" value="{{ $noOfBags }}"
                        readonly>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Loading Weight</label>
                    <input type="text" class="form-control" name="loading_weight" value="{{ $loadingWeight }}"
                        readonly>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Avg Rate</label>
                    <input type="text" class="form-control" name="avg_rate" value="{{ round($avgRate, 2) }}"
                        readonly>
                </div>
            </div>

            @if ($showLumpSum)
                <div class="col-12">
                    <h6 class="header-heading-sepration">
                        Lumpsum Deductions
                    </h6>
                </div>
                <div class="col-12">
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered table-hover">
                            <thead class="thead-light">
                                <tr>
                                    <th width="25%">Name</th>
                                    <th width="20%">Deduction Value</th>
                                    <th width="20%">Net Weight</th>
                                    <th width="20%">Deduction Amount</th>
                                </tr>
                            </thead>
                            <tbody id="lumpsum-results-tbody">
                                @if ($lumpsumDeduction > 0)
                                    <tr class="lumpsum-row" data-lumpsum-type="amount">
                                        <td><strong>Lumpsum Deduction (Amount)</strong>
                                            <input type="hidden" name="lumpsum[amount][name]"
                                                value="Lumpsum Deduction (Amount)">
                                        </td>
                                        <td>
                                            <div class="input-group mb-0">
                                                <input type="number" step="0.01"
                                                    class="form-control editable-field"
                                                    name="lumpsum[amount][deduction_value]"
                                                    id="lumpsum_deduction_input" value="{{ $lumpsumDeduction }}"
                                                    placeholder="Enter deduction value">
                                                <div class="input-group-append">
                                                    <span class="input-group-text text-sm">Rs.</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <input type="text" class="form-control"
                                                name="lumpsum[amount][net_weight]" id="lumpsum_net_weight_display"
                                                value="{{ number_format($netWeight, 2) }}" readonly>
                                        </td>
                                        <td>
                                            <div class="input-group mb-0">
                                                <input type="text" class="form-control"
                                                    name="lumpsum[amount][deduction_amount_display]"
                                                    id="lumpsum_amount_display"
                                                    value="{{ number_format($lumpsumCalculatedValue, 2) }}" readonly>
                                                <input type="hidden" name="lumpsum[amount][deduction_amount]"
                                                    id="lumpsum_amount_hidden" value="{{ $lumpsumCalculatedValue }}">
                                            </div>
                                        </td>
                                    </tr>
                                @endif
                                @if ($lumpsumDeductionKgs > 0)
                                    <tr class="lumpsum-row" data-lumpsum-type="kgs">
                                        <td><strong>Lumpsum Deduction (KGs)</strong>
                                            <input type="hidden" name="lumpsum[kgs][name]"
                                                value="Lumpsum Deduction (KGs)">
                                        </td>
                                        <td>
                                            <div class="input-group mb-0">
                                                <input type="number" step="0.01"
                                                    class="form-control editable-field"
                                                    name="lumpsum[kgs][deduction_value]"
                                                    id="lumpsum_deduction_kgs_input"
                                                    value="{{ $lumpsumDeductionKgs }}"
                                                    placeholder="Enter deduction value">
                                                <div class="input-group-append">
                                                    <span class="input-group-text text-sm">%</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <input type="text" class="form-control"
                                                name="lumpsum[kgs][net_weight]" id="lumpsum_kgs_net_weight_display"
                                                value="{{ number_format($netWeight, 2) }}" readonly>
                                        </td>
                                        <td>
                                            <div class="input-group mb-0">
                                                <input type="text" class="form-control"
                                                    name="lumpsum[kgs][deduction_amount_display]"
                                                    id="lumpsum_kgs_amount_display"
                                                    value="{{ number_format($lumpsumKgsCalculatedValue, 2) }}"
                                                    readonly>
                                                <input type="hidden" name="lumpsum[kgs][deduction_amount]"
                                                    id="lumpsum_kgs_amount_hidden"
                                                    value="{{ $lumpsumKgsCalculatedValue }}">
                                            </div>
                                        </td>
                                    </tr>
                                @endif
                                <tr class="other-deduction-row" data-other-deduction="true">
                                    <td><strong>Other Deduction</strong>
                                        <input type="hidden" name="other_deduction[slab_name]"
                                            value="Other Deduction">
                                    </td>
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
                                    <td>N/A</td>
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
            @else
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
                                                        placeholder="Suggested Deduction" readonly
                                                        data-slab-id="{{ $slab->id }}"
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
                                                        placeholder="Suggested Deduction" readonly
                                                        data-compulsory-id="{{ $slab->id }}"
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
                        <tr>
                            <td><strong>Bags Rate</strong></td>
                            <td>
                                <input type="number" step="0.01" class="form-control editable-field"
                                    name="bag_rate" id="bag_rate_input" value="{{ $bagRate }}">
                            </td>
                            <td>N/A</td>
                            <td>
                                <input type="number" step="0.01" class="form-control editable-field"
                                    name="bag_rate_amount" id="bag_rate_amount" value="{{ $bagsRateSum }}">
                                <input type="hidden" class="form-control" name="bag_rate_amount_display"
                                    id="bag_rate_amount_display" value="{{ number_format($bagsRateSum, 2) }}">
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
                            <td><strong>Supplier Commision</strong></td>
                            <td>N/A</td>
                            <td>
                                <div class="input-group mb-0" bis_skin_checked="1">
                                    <input type="text" class="form-control" name=""
                                        value="{{ $purchaseOrder->supplier_commission }}"
                                        placeholder="Suggested Deduction" readonly="">
                                    <div class="input-group-append" bis_skin_checked="1">
                                        <span class="input-group-text text-sm">Rs/KG's</span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <input type="text" class="form-control" name="supplier_commission_display"
                                    id="supplier_commission_display"
                                    value="{{ number_format($purchaseOrder->supplier_commission * $loadingWeight, 2) }}"
                                    readonly>
                                <input type="hidden" class="form-control" name="supplier_commission"
                                    id="supplier_commission"
                                    value="{{ $purchaseOrder->supplier_commission * $loadingWeight }}" readonly>
                            </td>
                        </tr>
                        @if ($purchaseOrder->supplier_commission * $loadingWeight < 0)
                            <tr>
                                <td><strong>Broker</strong></td>
                                <td>N/A</td>
                                <td>
                                    <div class="form-group mb-0 my-1 w-100">
                                        <select name="broker_id" id="broker_id" class="form-control select_b"
                                            @disabled(($paymentRequestData->broker_id ?? null) !== null) data-commission="#broker_commission">
                                            <option value="">N/A
                                            </option>
                                            @foreach ($brokers as $broker)
                                                <option value="{{ $broker->id }}" @selected($broker->id == ($paymentRequestData->broker_id ?? null))>
                                                    {{ $broker->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </td>
                                <td>
                                    <input type="text" class="form-control" name="brokery_amount_display"
                                        id="brokery_amount_display"
                                        value="{{ $paymentRequestData->brokery_amount ?? number_format($purchaseOrder->supplier_commission * $loadingWeight, 2) }}"
                                        readonly>
                                    <input type="hidden" class="form-control" name="brokery_amount"
                                        id="brokery_amount"
                                        value="{{ $paymentRequestData->brokery_amount ?? $purchaseOrder->supplier_commission * $loadingWeight }}"
                                        readonly>
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    @endif
    @php
        $totalSupplierCommission = $purchaseOrder->supplier_commission * $loadingWeight;

        $totalAmount = $ratePerKg * $loadingWeight - ($totalAmount ?? 0) + ($bagsRateSum ?? 0);
        $totalwithCommision = $totalAmount + $totalSupplierCommission;

    @endphp
    <div class="col mb-3 px-0">
        <div class="row mx-auto ">
            <div class="col-md-6">
                <div class="form-group">
                    <label>Amount</label>
                    <input type="text" class="form-control" name="total_amount_display" id="total_amount_display"
                        value="{{ number_format($totalwithCommision, 2) }}" readonly>
                    <input type="hidden" class="form-control" name="total_amount" id="total_amount"
                        value="{{ $totalwithCommision }}" readonly>
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
                        value="{{ $paidAmount }}" placeholder="Enter paid amount">
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
        <div class="row mx-auto">
            <div class="col-12">
                <hr class="border">
            </div>
        </div>
        <div class="row mx-auto ">
            <div class="col-md-6">
                <div class="form-group">
                    <label>Total Advance Freight</label>
                    <input type="text" class="form-control" name="advance_freight_display"
                        value="{{ number_format($advanceFreight, 2) }}" readonly>
                    <input type="hidden" class="form-control" name="advance_freight"
                        value="{{ $advanceFreight }}" readonly>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>Paid Freight</label>
                    <input type="text" class="form-control" name="paid_freight"
                        value="{{ number_format($pRsSumForFreight ?? 0, 2) }}" readonly>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>Remaining Freight</label>
                    <input type="text" class="form-control" name="remaining_freight"
                        value="{{ $remainingFreight }}" readonly>
                </div>
            </div>
            @if (!$isApprovalPage)
                <div class="col">
                    <div class="form-group">
                        <label>Percentage</label>
                        <input type="number" min="0" max="100" step="0.01"
                            class="form-control percentage-input-freight" value="0"
                            placeholder="Enter percentage">
                    </div>
                </div>
                <div class="col">
                    <div class="form-group">
                        <label>Freight Pay Request</label>
                        <input type="number" class="form-control payment-request-freifht"
                            name="{{ $isApprovalPage ? '' : 'freight_pay_request_amount' }}"
                            value="{{ $currentFreightAmount }}" placeholder="Enter freight pay request"
                            max="{{ (int) $remainingFreight }}">
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

@if ($hasLoadingWeight)
    <script>
        $(document).ready(function() {
            $('.select_b').select2();
            $('[data-toggle="tooltip"]').tooltip();
            const $loadingRadio = $('#loading');
            const $withoutLoadingRadio = $('#without_loading');
            const $loadingSection = $('#loading-section');
            const $contractRangeField = $('.contract-range-field');
            const originalBagWeight = parseFloat($('#original_bag_weight').val()) || 0;
            const originalBagRate = parseFloat($('#original_bag_rate').val()) || 0;
            const loadingWeight = parseFloat($('#loading_weight').val()) || 0;
            const noOfBags = parseFloat($('#no_of_bags').val()) || 0;
            const ratePerKg = parseFloat($('#rate_per_kg').val()) || 0;
            const kantaCharges = parseFloat($('#kanta_charges').val()) || 0;
            const paidAmount = parseFloat({{ $requestedAmount }});
            const isApprovalPage = {{ $isApprovalPage ? 1 : 0 }} ? true : false;
            const showLumpSum = {{ $showLumpSum ? 1 : 0 }} ? true : false;

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

            function calculateLumpsumDeductions() {
                const netWeight = calculateNetWeight();
                let totalLumpsumAmount = 0;

                const lumpsumDeduction = parseFloat($('#lumpsum_deduction_input').val()) || 0;
                if (lumpsumDeduction > 0) {
                    const lumpsumCalculatedValue = lumpsumDeduction * netWeight;
                    totalLumpsumAmount += lumpsumCalculatedValue;
                    $('#lumpsum_amount_display').val(lumpsumCalculatedValue.toFixed(2));
                    $('#lumpsum_amount_hidden').val(lumpsumCalculatedValue);
                    $('#lumpsum_net_weight_display').val(netWeight.toFixed(2));
                }

                const lumpsumDeductionKgs = parseFloat($('#lumpsum_deduction_kgs_input').val()) || 0;
                if (lumpsumDeductionKgs > 0) {
                    let lumpsumKgsCalculatedValue = lumpsumDeductionKgs * netWeight;
                    lumpsumKgsCalculatedValue = (lumpsumKgsCalculatedValue / 100) * ratePerKg;
                    totalLumpsumAmount += lumpsumKgsCalculatedValue;
                    $('#lumpsum_kgs_amount_display').val(lumpsumKgsCalculatedValue.toFixed(2));
                    $('#lumpsum_kgs_amount_hidden').val(lumpsumKgsCalculatedValue);
                    $('#lumpsum_kgs_net_weight_display').val(netWeight.toFixed(2));
                }

                return totalLumpsumAmount;
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

                if (showLumpSum) {
                    totalSamplingAmount = calculateLumpsumDeductions();
                } else {
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
                }

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
                    const newBagWeight = bagWeightAmount / (ratePerKg * noOfBags);
                    if (!isNaN(newBagWeight) && isFinite(newBagWeight)) {
                        $('#bag_weight_input').val(newBagWeight.toFixed(4));
                        $('#bag_weight_total').val((newBagWeight * noOfBags).toFixed(2));
                    }
                }
            }

            function updateBagRateCalculations() {
                const currentBagRate = parseFloat($('#bag_rate_input').val()) || 0;
                const bagRateAmount = parseFloat($('#bag_rate_amount').val()) || 0;
                const calculatedBagRateAmount = currentBagRate * noOfBags;

                if (Math.abs(bagRateAmount - calculatedBagRateAmount) > 0.01) {
                    const newBagRate = bagRateAmount / noOfBags;
                    if (!isNaN(newBagRate) && isFinite(newBagRate)) {
                        $('#bag_rate_input').val(newBagRate.toFixed(4));
                    }
                } else {
                    $('#bag_rate_amount').val(calculatedBagRateAmount);
                }
                $('#bag_rate_amount_display').val($('#bag_rate_amount').val());
            }

            function updateAllCalculations() {
                updateBagWeightCalculations();
                updateBagRateCalculations();

                const currentBagWeight = parseFloat($('#bag_weight_input').val()) || 0;
                const bagWeightAmount = parseFloat($('#bag_weight_amount').val()) || 0;
                const bagRateAmount = parseFloat($('#bag_rate_amount').val()) || 0;
                const loadingWeighbridgeAmount = kantaCharges / 2;

                $('#loading_weighbridge_amount').val(loadingWeighbridgeAmount);
                $('#loading_weighbridge_amount_display').val(loadingWeighbridgeAmount.toFixed(2));

                const totalSamplingDeductions = updateSamplingResultsDeductions();
                const grossAmount = ratePerKg * loadingWeight;
                const totalDeductionsForFormula = totalSamplingDeductions + bagWeightAmount +
                    loadingWeighbridgeAmount;
                const totalAmount = grossAmount - totalDeductionsForFormula + bagRateAmount +
                    {{ $totalSupplierCommission }};

                $('#total_amount').val(totalAmount);
                $('#total_amount_display').val(totalAmount.toFixed(2));

                const remainingAmount = totalAmount - paidAmount;
                $('#remaining_amount').val(remainingAmount.toFixed(2));
                $('#bag_weight_amount_display').val(bagWeightAmount.toFixed(2));
            }

            $('#lumpsum_deduction_input').on('input', function() {
                updateAllCalculations();
            });

            $('#lumpsum_deduction_kgs_input').on('input', function() {
                updateAllCalculations();
            });

            $('.remove-lumpsum').on('click', function() {
                const type = $(this).data('type');
                if (type === 'amount') {
                    $('#lumpsum_deduction_input').val(0);
                    $('tr[data-lumpsum-type="amount"]').hide();
                } else if (type === 'kgs') {
                    $('#lumpsum_deduction_kgs_input').val(0);
                    $('tr[data-lumpsum-type="kgs"]').hide();
                }
                updateAllCalculations();
            });

            $('#bag_weight_input').on('input', function() {
                const currentBagWeight = parseFloat($(this).val()) || 0;
                const bagWeightAmount = ratePerKg * currentBagWeight * noOfBags;
                $('#bag_weight_amount').val(bagWeightAmount.toFixed(2));
                updateAllCalculations();
            });

            $('#bag_weight_amount').on('input', function() {
                updateAllCalculations();
            });

            $('#bag_rate_input').on('input', function() {
                const currentBagRate = parseFloat($(this).val()) || 0;
                const bagRateAmount = currentBagRate * noOfBags;
                $('#bag_rate_amount').val(bagRateAmount);
                updateAllCalculations();
            });

            $('#bag_rate_amount').on('input', function() {
                updateAllCalculations();
            });

            $('#other_deduction_kg').on('input', function() {
                updateAllCalculations();
            });

            if (!isApprovalPage) {
                $('input[name="payment_request_amount"]').on('input', function() {
                    const totalAmount = parseFloat($('#total_amount').val()) || 0;
                    const paymentRequest = parseFloat($(this).val()) || 0;
                    const remaining = totalAmount - paymentRequest - paidAmount;
                    $('#remaining_amount').val(remaining.toFixed(2));
                });
            }

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
                const paidAmount = parseFloat({{ $pRsSumForFreight ?? 0 }});
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
