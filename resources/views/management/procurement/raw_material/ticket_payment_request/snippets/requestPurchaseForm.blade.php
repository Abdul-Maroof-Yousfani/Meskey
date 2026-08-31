@php
    $isThadda = $arrivalTicket->sauda_type_id == 2;
    calculatePaymentDetails($arrivalTicket->id, $arrivalTicket->sauda_type_id);
    $paymentDetails = calculatePaymentDetails($arrivalTicket->id, $arrivalTicket->sauda_type_id);
    $Deductionfromhelperfunction = $paymentDetails['deductions']['sampling_deduction_details'];



    // Filling Bags Calculation
    $fillingBagsNo = isset($otherDeduction) ? $otherDeduction->no_of_filling_bags : $arrivalTicket->approvals->filling_bags_no ?? 0;
    $fillingBagsRate = isset($otherDeduction) ? $otherDeduction->filling_bag_rate : $fillingBagsRate ?? 10;
    $fillingBagsAmount = $fillingBagsNo * $fillingBagsRate;


    $hasLoadingWeight = true; 

    $isSlabs = false;
    $isCompulsury = false;
    $showLumpSum = false;
    $totalAmount = 0;
    $remaining = 0;
    // dd($samplingRequest->is_lumpsum_deduction);
    if (
        isset($samplingRequest->is_lumpsum_deduction) &&
        $samplingRequest->is_lumpsum_deduction
        // && $samplingRequest->lumpsum_deduction > 0
    ) {
        $showLumpSum = true;
    }

    $bagWeight = $arrivalTicket->bag_weight ?? 0;
    $bagRate = 0;

    $totalDeductions = 0;
    $arrivedWeight = $arrivalTicket->freight->arrived_weight ?? 0;
    $loadingWeight = $arrivalTicket->net_weight ?? 0;
    $accessWeight = $arrivedWeight > $loadingWeight ? $arrivedWeight - $loadingWeight : 0;
    $exemptedWeight = $accessWeight > 100 ? 100 : $accessWeight;

    $exemptedWeight = $paymentRequestData->exempted_weight ?? $exemptedWeight;
    $billingWeight = $arrivedWeight > $loadingWeight ? ($loadingWeight + $exemptedWeight) : $arrivedWeight;
    $noOfBags = $arrivalTicket->approvals->total_bags ?? 0;
    $ratePerKg = $purchaseOrder->rate_per_kg ?? 0;
    $kantaCharges = $arrivalTicket->freight->karachi_kanta_charges ?? 0;
    $arrivalFreightAmount = $arrivalTicket->freight->gross_freight_amount ?? 0;

    if ($arrivalTicket->freight_paid_by_supplier) {
        $grossFreightAmount = $freightPaymentRequestgrossAmount;
    } else {
        $grossFreightAmount = $freightPaymentRequestgrossAmount ?? $arrivalTicket->freight->net_freight;
    }

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
    $loadingWeighbridgeSum = 0;
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
    $existingRerateOnAccessWeightKg = $otherDeduction->rerate_on_access_weight_kg ?? 0;
    $existingRerateOnAccessWeightRate = $otherDeduction->rerate_on_access_weight_rate ?? 0;
    $existingRerateOnAccessWeightAmount = $otherDeduction->rerate_on_access_weight_amount ?? 0;
    $deduction_on_weight_difference_kg = $otherDeduction->deduction_on_weight_difference_kg ?? 0;
    $deduction_on_weight_difference_amount = $otherDeduction->deduction_on_weight_difference_amount ?? 0;
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
    .togglehistorytable {
        display: none;
    }

    .togglehistory {
        cursor: pointer;
    }

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
<input type="hidden" id="loading_weight" value="{{ $billingWeight }}">
<input type="hidden" id="no_of_bags" value="{{ $noOfBags }}">
<input type="hidden" id="rate_per_kg" value="{{ $ratePerKg }}">
<input type="hidden" id="bag_rate" value="{{ $bagRate }}">
<input type="hidden" id="kanta_charges" value="0">

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
            <input type="text" class="form-control" name="contract_rate" value="{{ $purchaseOrder->rate_per_kg ?? 0 }}"
                readonly>
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

    @if ($hasLoadingWeight)
        <div id="loading-section" class="row w-100 mx-auto px-0">
            <div class="col-md-12 mb-3 d-none" bis_skin_checked="1">
                <div class="form-check form-check-inline" bis_skin_checked="1">
                    <input class="form-check-input" type="radio" name="loading_type" id="loading" value="loading"
                        checked="">
                    <label class="form-check-label" for="loading">Loading</label>
                </div>
                <div class="form-check form-check-inline" bis_skin_checked="1">
                    <input class="form-check-input" type="radio" name="loading_type" id="without_loading"
                        value="without_loading">
                    <label class="form-check-label" for="without_loading">Without Loading</label>
                </div>
                <input type="hidden" name="" value="loading">
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Truck #</label>
                    <input type="text" class="form-control" name="truck_no" value="{{ $arrivalTicket->truck_no ?? 'N/A' }}"
                        readonly>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Arrival Date</label>
                    <input type="text" class="form-control" name="loading_date"
                        value="{{ $arrivalTicket && $arrivalTicket->freight->created_at ? $arrivalTicket->freight->created_at->format('d-M-Y') : 'N/A' }}"
                        readonly>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Bilty #</label>
                    <input type="text" class="form-control" name="bilty_no" value="{{ $arrivalTicket->bilty_no ?? 'N/A' }}"
                        readonly>
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
                    <input type="text" class="form-control" name="no_of_bags" value="{{ $noOfBags }}" readonly>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Loading Weight</label>
                    <input type="text" class="form-control" name="loading_weight" value="{{ $loadingWeight }}" readonly>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Arrival Weight</label>
                    <input type="text" class="form-control" name="arrived_weight" value="{{ $arrivedWeight }}" readonly>
                </div>
            </div>

            <div class="col-md-3">
                <div class="form-group">
                    <label>Excess Weight</label>
                    <input type="text" class="form-control" name="access_weight" value="{{ $accessWeight }}" readonly>
                </div>
            </div>

            <div class="col-md-3">
                <div class="form-group">
                    <label>Exempted Weight</label>
                    <input type="number" class="form-control" name="exempted_weight" id="exempted_weight"
                        value="{{ $exemptedWeight }}" {{ $exemptedWeight == 0 ? 'readonly' : ''}}
                        max="{{ $exemptedWeight != 0 ? $accessWeight : ''}}" {{ $isApprovalPage ? 'readonly' : ''}}>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Billing Weight</label>
                    <input type="number" class="form-control" name="billing_weight" id="billing_weight"
                        value="{{ $billingWeight }}" readonly>
                </div>
            </div>

            <div class="col-md-3">
                <div class="form-group">
                    <label>Average Bag Weight</label>
                    <input type="text" class="form-control" name="avg_rate" value="{{ round($avgRate, 4) }}" readonly>
                </div>
            </div>

            @if(isset($paymentRequests) && count($paymentRequests) != 0)
                <div class="col-md-12">
                    <h6 class="header-heading-sepration togglehistory">
                        Request History ({{ count($paymentRequests) }})
                    </h6>
                    <table class="table m-0 togglehistorytable">
                        <thead>
                            <tr>
                                <th>Request Date</th>
                                <th>Amount</th>
                                <th>Remarks</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($paymentRequests as $paymentRequest)
                                <tr>
                                    <td>{{$paymentRequest->created_at}}</td>
                                    <td>{{$paymentRequest->amount}}</td>
                                    <td>{{$paymentRequest->approval->remarks ?? 'N/A'}}</td>
                                    <td>
                                        @if($paymentRequest->status == 'pending')
                                            <label class="badge badge-warning">Pending</label>
                                        @elseif($paymentRequest->status == 'approved')
                                            <label class="badge badge-success">Approved</label>
                                        @elseif($paymentRequest->status == 'rejected')
                                            <label class="badge badge-danger">Rejected</label>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif




            @if ($showLumpSum && !$isSlabs && !$isCompulsury)
                <div class="col-12">
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered table-hover">
                            <thead class="thead-light">
                                <tr>
                                    <th width="20%">Lump Sum Deduction</th>
                                    <th width="20%">Tabaar Deduction</th>
                                    <th width="20%">Deduction Amount</th>
                                </tr>
                            </thead>
                            <tbody id="sampling-results-tbody">
                                <tr
                                    data-lumpsum-amount="{{ number_format($Deductionfromhelperfunction['lumpsum']['amount_deduction'] ?? 0, 4) }}">
                                    <td>Lumpsum Deduction Rupees</td>
                                    <td>
                                        <div class="input-group mb-0">
                                            <input type="number" step="any" class="form-control editable-field"
                                                name="lump_sum_deduction_rupees" id="lump_sum_deduction_rupees"
                                                value="{{ $samplingRequest->lumpsum_deduction }}" readonly
                                                placeholder="Enter Rs./KG">
                                            <div class="input-group-append">
                                                <span class="input-group-text text-sm">Rs.</span>
                                            </div>
                                        </div>

                                        {{-- {{ number_format($samplingRequest->lumpsum_deduction, 2) }} Rs./KG --}}
                                    </td>
                                    <td>
                                        <div class="input-group mb-0">
                                            <input type="text" class="form-control" name="lump_sum_deduction_rupees_total"
                                                id="lump_sum_deduction_rupees_total"
                                                value="{{ number_format($Deductionfromhelperfunction['lumpsum']['amount_deduction'] ?? 0, 4) }}"
                                                readonly>

                                            <!-- {{ number_format($Deductionfromhelperfunction['lumpsum']['amount_deduction'] ?? 0, 2) }}Rs. -->
                                    </td>
                                </tr>
                                <tr
                                    data-lumpsum-kgamount="{{ number_format($Deductionfromhelperfunction['lumpsum']['kgs_deduction'] ?? 0, 4) }}">
                                    <td>Lumpsum Deduction KG's</td>
                                    <td>
                                        <div class="input-group mb-0">
                                            <input type="number" step="any" readonly class="form-control editable-field"
                                                name="lump_sum_deduction_kgs" id="lump_sum_deduction_kgs"
                                                value="{{ $samplingRequest->lumpsum_deduction_kgs }}"
                                                placeholder="Enter Rs./KG">
                                            <div class="input-group-append">
                                                <span class="input-group-text text-sm">KG's</span>
                                            </div>
                                        </div>

                                        <!-- {{ number_format($samplingRequest->lumpsum_deduction_kgs, 2) }} KG's -->
                                    </td>
                                    <td>


                                        <div class="input-group mb-0">
                                            <input type="text" class="form-control" name="lump_sum_deduction_kgs_total"
                                                id="lump_sum_deduction_kgs_total"
                                                value="{{ number_format($Deductionfromhelperfunction['lumpsum']['kgs_deduction'] ?? 0, 4) }}"
                                                readonly>

                                            <!-- {{ number_format($Deductionfromhelperfunction['lumpsum']['kgs_deduction'] ?? 0, 2) }} -->
                                            <!-- Rs. -->
                                    </td>
                                </tr>
                                <tr class="other-deduction-row" data-other-deduction="true">
                                    <td>Other Deduction (if any)
                                        <input type="hidden" name="other_deduction[slab_name]" value="Other Deduction">
                                    </td>
                                    <td>
                                        <div class="input-group mb-0">
                                            <input type="number" step="any" class="form-control editable-field"
                                                name="other_deduction[kg_value]" id="other_deduction_kg"
                                                value="{{ $existingOtherDeductionKg }}" placeholder="Enter Rs./KG">
                                            <div class="input-group-append">
                                                <span class="input-group-text text-sm">Rs./KG</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="input-group mb-0">
                                            <input type="text" class="form-control" name="other_deduction[kg_amount]"
                                                id="other_deduction_amount_display"
                                                value="{{ number_format($existingOtherDeductionAmount, 4) }}" readonly>
                                            <input type="hidden" class="form-control" name="other_deduction[deduction_amount]"
                                                id="other_deduction_amount" value="{{ $existingOtherDeductionAmount }}">
                                        </div>
                                    </td>
                                </tr>
                                <!-- Re-rate on Access weight Row -->
                                <tr class="other-deduction-row" data-other-deduction="true">
                                    <td><strong>Re-rate on Excess weight</strong>
                                        <input type="hidden" name="other_deduction[slab_name]" value="Other Deduction">
                                    </td>
                                    <td>N/A</td>
                                    <td>
                                        <div class="input-group mb-0">
                                            <input type="number" step="any" class="form-control editable-field"
                                                name="rerate_on_access_weight_kg" id="rerate_on_access_weight_kg"
                                                value="{{ $existingRerateOnAccessWeightKg }}" placeholder="Enter KG value">
                                            <div class="input-group-append">
                                                <span class="input-group-text text-sm">Kg</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="input-group mb-0">
                                            <input type="number" step="any" class="form-control editable-field"
                                                name="rerate_on_access_weight_rate" id="rerate_on_access_weight_rate"
                                                value="{{ $existingRerateOnAccessWeightRate }}" placeholder="Enter KG value">
                                            <div class="input-group-append">
                                                <span class="input-group-text text-sm">Rs</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="input-group mb-0">
                                            <input type="text" class="form-control" name="rerate_on_access_weight_amount"
                                                readonly id="rerate_on_access_weight_amount"
                                                value="{{ $existingRerateOnAccessWeightAmount }}">
                                        </div>
                                    </td>
                                </tr>


                                <tr class="other-deduction-row" data-other-deduction="true">
                                    <td><strong>Filling Bags</strong>
                                        <input type="hidden" name="filling_bags[slab_name]" value="Filling Bags">
                                    </td>
                                    <td>N/A</td>
                                    <td>
                                        <div class="input-group mb-0">
                                            <input type="number" step="any"
                                                class="form-control editable-field filling-bags-input"
                                                name="filling_bags_no" id="filling_bags_no"
                                                value="{{ $fillingBagsNo ?? 0 }}" placeholder="Enter number of bags">
                                            <div class="input-group-append">
                                                <span class="input-group-text text-sm">Bags</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="input-group mb-0">
                                            <input type="number" step="any"
                                                class="form-control editable-field filling-bags-rate" name="filling_bags_rate"
                                                id="filling_bags_rate" value="{{ $fillingBagsRate ?? 10 }}"
                                                placeholder="Enter rate per bag">
                                            <div class="input-group-append">
                                                <span class="input-group-text text-sm">Rs</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="input-group mb-0">
                                            <input type="text" class="form-control" name="filling_bags_amount" readonly
                                                id="filling_bags_amount"
                                                value="{{ number_format($fillingBagsAmount ?? 0, 4) }}">
                                            <input type="hidden" class="form-control" name="filling_bags_amount_hidden"
                                                id="filling_bags_amount_hidden" value="{{ $fillingBagsAmount ?? 0 }}">
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
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

                                            $calculatedValue = $deductionValue * $netWeight;
                                            if (($slab->deduction_type ?? 'amount') !== 'amount') {
                                                $calculatedValue = ($calculatedValue / 100) * $ratePerKg;
                                            }

                                            $totalAmount += $calculatedValue;
                                        @endphp

                                        <tr data-slab-id="{{ $slab->id }}">
                                            <td>{{ $slab->slabType->name }}
                                                <input type="hidden" name="sampling_results[{{ $slab->id }}][slab_type_id]"
                                                    value="{{ $slab->slabType->id }}">
                                                <input type="hidden" name="sampling_results[{{ $slab->id }}][slab_name]"
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
                                                        value="{{ $slab->suggested_deduction ?? 0 }}" placeholder="Suggested Deduction"
                                                        readonly>
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
                                                        value="{{ $deductionValue }}" placeholder="Suggested Deduction" readonly
                                                        data-slab-id="{{ $slab->id }}"
                                                        data-deduction-type="{{ $slab->deduction_type ?? 'amount' }}"
                                                        data-applied-deduction="{{ $slab->applied_deduction ?? 0 }}">
                                                    <div class="input-group-append">
                                                        <span
                                                            class="input-group-text text-sm">{{ ($slab->deduction_type ?? 'amount') == 'amount' ? 'Rs.' : "KG's" }}</span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="input-group mb-0">
                                                    <input type="text" class="form-control deduction-amount-display"
                                                        name="sampling_results[{{ $slab->id }}][deduction_amount_display]"
                                                        value="{{ number_format($calculatedValue, 4) }}" placeholder="deduction_amount"
                                                        readonly data-slab-id="{{ $slab->id }}">
                                                    <input type="hidden" class="form-control deduction-amount-hidden"
                                                        name="sampling_results[{{ $slab->id }}][deduction_amount]"
                                                        value="{{ $calculatedValue }}" placeholder="deduction_amount" readonly
                                                        data-slab-id="{{ $slab->id }}">
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
                                                <input type="hidden" name="compulsory_results[{{ $slab->id }}][qc_name]"
                                                    value="{{ $slab->qcParam->name ?? null }}">
                                                <input type="hidden" name="compulsory_results[{{ $slab->id }}][qc_param_id]"
                                                    value="{{ $slab->qcParam->id ?? null }}">
                                            </td>
                                            <td></td>
                                            <td></td>
                                            <td>
                                                <div class="input-group mb-0">
                                                    <input type="text" class="form-control compulsory-applied-deduction"
                                                        name="compulsory_results[{{ $slab->id }}][applied_deduction]"
                                                        value="{{ $slab->applied_deduction }}" placeholder="Suggested Deduction"
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
                                                    <input type="text" class="form-control compulsory-deduction-amount"
                                                        name="compulsory_results[{{ $slab->id }}][deduction_amount]"
                                                        value="{{ number_format($compulsoryCalculatedValue, 4) }}"
                                                        placeholder="deduction_amount" readonly data-compulsory-id="{{ $slab->id }}">
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                @endif

                                <!-- Other Deduction Row -->
                                <tr class="other-deduction-row" data-other-deduction="true">
                                    <td><strong>Other Deduction (if any)</strong>
                                        <input type="hidden" name="other_deduction[slab_name]" value="Other Deduction">
                                    </td>
                                    <td>N/A</td>
                                    <td>N/A</td>
                                    <td>
                                        <div class="input-group mb-0">
                                            <input type="number" step="any" class="form-control editable-field"
                                                name="other_deduction[kg_value]" id="other_deduction_kg"
                                                value="{{ $existingOtherDeductionKg }}" placeholder="Enter KG value">
                                            <div class="input-group-append">
                                                <span class="input-group-text text-sm">Rs/Kg</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="input-group mb-0">
                                            <input type="text" class="form-control" name="other_deduction[kg_amount]"
                                                id="other_deduction_amount_display"
                                                value="{{ number_format($existingOtherDeductionAmount, 4) }}" readonly>
                                            <input type="hidden" class="form-control" name="other_deduction[deduction_amount]"
                                                id="other_deduction_amount" value="{{ $existingOtherDeductionAmount }}">
                                        </div>
                                    </td>
                                </tr>

                                <!-- Re-rate on Access weight Row -->
                                <tr class="other-deduction-row" data-other-deduction="true">
                                    <td><strong>Re-rate on Excess weight</strong>
                                        <input type="hidden" name="other_deduction[slab_name]" value="Other Deduction">
                                    </td>
                                    <td>N/A</td>
                                    <td>
                                        <div class="input-group mb-0">
                                            <input type="number" step="any" class="form-control editable-field"
                                                name="rerate_on_access_weight_kg" id="rerate_on_access_weight_kg"
                                                value="{{ $existingRerateOnAccessWeightKg }}" placeholder="Enter KG value">
                                            <div class="input-group-append">
                                                <span class="input-group-text text-sm">Kg</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="input-group mb-0">
                                            <input type="number" step="any" class="form-control editable-field"
                                                name="rerate_on_access_weight_rate" id="rerate_on_access_weight_rate"
                                                value="{{ $existingRerateOnAccessWeightRate }}" placeholder="Enter KG value">
                                            <div class="input-group-append">
                                                <span class="input-group-text text-sm">Rs</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="input-group mb-0">
                                            <input type="text" class="form-control" name="rerate_on_access_weight_amount"
                                                readonly id="rerate_on_access_weight_amount"
                                                value="{{ $existingRerateOnAccessWeightAmount }}">
                                        </div>
                                    </td>
                                </tr>


                                <tr class="other-deduction-row" data-other-deduction="true">
                                    <td><strong>Filling Bags</strong>
                                        <input type="hidden" name="filling_bags[slab_name]" value="Filling Bags">
                                    </td>
                                    <td>N/A</td>
                                    <td>
                                        <div class="input-group mb-0">
                                            <input type="number" step="any"
                                                class="form-control editable-field filling-bags-input"
                                                name="filling_bags_no" id="filling_bags_no"
                                                value="{{ $fillingBagsNo ?? 0 }}" placeholder="Enter number of bags">
                                            <div class="input-group-append">
                                                <span class="input-group-text text-sm">Bags</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="input-group mb-0">
                                            <input type="number" step="any"
                                                class="form-control editable-field filling-bags-rate" name="filling_bags_rate"
                                                id="filling_bags_rate" value="{{ $fillingBagsRate ?? 10 }}"
                                                placeholder="Enter rate per bag">
                                            <div class="input-group-append">
                                                <span class="input-group-text text-sm">Rs</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="input-group mb-0">
                                            <input type="text" class="form-control" name="filling_bags_amount" readonly
                                                id="filling_bags_amount"
                                                value="{{ number_format($fillingBagsAmount ?? 0, 4) }}">
                                            <input type="hidden" class="form-control" name="filling_bags[amount_hidden]"
                                                id="filling_bags_amount_hidden" value="{{ $fillingBagsAmount ?? 0 }}">
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
                                <input type="number" step="0.01" class="form-control editable-field" name="bag_weight"
                                    id="bag_weight_input" value="{{ $bagWeight }}">
                            </td>
                            <td>
                                <input type="text" class="form-control" name="bag_weight_total" id="bag_weight_total"
                                    value="{{ $bagWeight * $noOfBags }}" readonly>
                            </td>
                            <td>
                                <input type="number" step="0.01" class="form-control editable-field"
                                    name="bag_weight_amount" id="bag_weight_amount" value="{{ $bagWeightInKgSum }}">
                                <input type="hidden" class="form-control" name="bag_weight_amount_display"
                                    id="bag_weight_amount_display" value="{{ number_format($bagWeightInKgSum, 4) }}">
                            </td>
                        </tr>
                        <tr class="d-none">
                            <td><strong>Bags Rate</strong></td>
                            <td>
                                <input type="text" class="form-control" name="bag_rate"
                                    value="{{ number_format($bagRate, 4) }}" readonly>
                            </td>
                            <td>N/A</td>
                            <td>
                                <input type="text" class="form-control" name="bag_rate_amount_display"
                                    id="bag_rate_amount_display" value="{{ number_format($bagsRateSum, 4) }}" readonly>
                                <input type="hidden" class="form-control" name="bag_rate_amount" id="bag_rate_amount"
                                    value="{{ $bagsRateSum }}" readonly>
                            </td>
                        </tr>
                        <tr class="d-none">
                            <td><strong>Loading weighbridge</strong></td>
                            <td>N/A</td>
                            <td>N/A</td>
                            <td>
                                <input type="text" class="form-control" name="loading_weighbridge_amount_display"
                                    id="loading_weighbridge_amount_display"
                                    value="{{ number_format($loadingWeighbridgeSum, 4) }}" readonly>
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
                                    value="{{ number_format($grossFreightAmount, 4) }}" readonly>
                                <input type="hidden" class="form-control" name="loading_weighbridge_amount1"
                                    id="freight_deduction_amount" value="{{ $grossFreightAmount }}" readonly>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Supplier Commision</strong></td>
                            <td>N/A</td>
                            <td>
                                <div class="input-group mb-0" bis_skin_checked="1">
                                    <input type="text" class="form-control" name=""
                                        value="{{ $purchaseOrder->supplier_commission }}" placeholder="Suggested Deduction"
                                        readonly="">
                                    <div class="input-group-append" bis_skin_checked="1">
                                        <span class="input-group-text text-sm">Rs/KG's</span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <input type="text" class="form-control" name="supplier_commission_display"
                                    id="supplier_commission_display"
                                    value="{{ number_format($purchaseOrder->supplier_commission * $arrivedWeight, 4) }}"
                                    readonly>
                                <input type="hidden" class="form-control" name="supplier_commission"
                                    id="supplier_commission"
                                    value="{{ $purchaseOrder->supplier_commission * $arrivedWeight }}" readonly>
                            </td>
                        </tr>
                        @if ($purchaseOrder->supplier_commission < 0)
                            <tr>
                                <td><strong>Broker</strong></td>
                                <td>N/A</td>
                                <td>
                                    <div class="form-group mb-0 my-1 w-100">
                                        @php
                                            $isBrokerDisabled = ($paymentRequestData->broker_id ?? null) !== null;
                                            $selectedBrokerId = $paymentRequestData->broker_id ?? '';
                                        @endphp
                                        <select name="broker_id" id="broker_id" class="form-control select_b"
                                            @disabled($isBrokerDisabled) data-commission="#broker_commission">
                                            <option value="">N/A</option>
                                            @foreach ($brokers as $broker)
                                                <option value="{{ $broker->id }}" @selected($broker->id == $selectedBrokerId)>
                                                    {{ $broker->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @if ($isBrokerDisabled)
                                            <input type="hidden" name="broker_id" value="{{ $selectedBrokerId }}">
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <input type="text" class="form-control" name="brokery_amount_display"
                                        id="brokery_amount_display"
                                        value="{{ number_format($purchaseOrder->supplier_commission * $arrivedWeight, 4) }}"
                                        readonly>
                                    <input type="hidden" class="form-control" name="brokery_amount" id="brokery_amount"
                                        value="{{ $purchaseOrder->supplier_commission * $arrivedWeight }}" readonly>
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @php
        $totalSupplierCommission = $purchaseOrder->supplier_commission * $arrivedWeight;
        $totalAmount = $ratePerKg * $loadingWeight - ($totalAmount ?? 0) + ($bagsRateSum ?? 0);
        $totalwithCommisio = $totalAmount + $totalSupplierCommission;
        $totalwithCommision = $paymentDetails['calculations']['supplier_net_amount'] ?? $totalwithCommisio
    @endphp

    <div class="col mb-3 px-0">
        <div class="row mx-auto ">
            <div class="col-md-6">
                <div class="form-group">
                    <label>Amount</label>
                    <input type="text" class="form-control" name="total_amount_display" id="total_amount_display"
                        value="{{ number_format($totalwithCommision, 4) }}" readonly>
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
                    <input type="number" step="0.01" readonly class="form-control" name="paid_amount" id="paid_amount"
                        value="{{ $paidAmount }}" placeholder="Enter paid amount">
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>Remaining {{ $totalAmount - $requestedAmount }}</label>
                    <input type="text" class="form-control" name="remaining_amount" id="remaining_amount"
                        value="{{ number_format($totalAmount - $requestedAmount, 4) }}" readonly>
                </div>
            </div>
            @if (!$isApprovalPage)
                <div class="col">
                    <div class="form-group">
                        <label>Percentage</label>
                        <input type="number" min="0" max="100" step="0.0001" class="form-control percentage-input" value="0"
                            placeholder="Enter percentage">
                    </div>
                </div>
                <div class="col">
                    <div class="form-group">
                        <label>Payment Request</label>
                        <input type="number" step="0.0001" class="form-control payment-request-input"
                            name="{{ $isApprovalPage ? '' : 'payment_request_amount' }}" value="{{ $currentPaymentAmount }}"
                            placeholder="Enter payment request">
                    </div>
                </div>
            @endif
        </div>
    </div>


<div class="col-12">
        <div class="row">
        <div class="col-md-12">
            <h6 class="header-heading-sepration toggleFreight" style="background: #0059ff26;">
                {{ $arrivalTicket->saudaType?->name }} Freight Details
            </h6>
        </div>
    </div>
    @if($arrivalTicket->saudaType?->name == 'Pohanch')
        <div class="row toggleFreightBox" style="display:none;">
            <div class="col-md-3">
                <div class="form-group">
                    <label>Attach Bilty</label>
                    @if ($arrivalTicket->freight->bilty_document)
                        <a href="{{ asset($arrivalTicket->freight->bilty_document) }}" target="_blank">
                            <img src="{{ asset($arrivalTicket->freight->bilty_document) }}" class="d-block w-100" />
                        </a>
                    @endif
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Attach Loading Weight</label>
                    @if ($arrivalTicket->freight->loading_weight_document)
                        <a href="{{ asset($arrivalTicket->freight->loading_weight_document) }}" target="_blank">
                            <img src="{{ asset($arrivalTicket->freight->loading_weight_document) }}" class="d-block w-100" />
                        </a>
                    @endif
                </div>
            </div>

            <div class="col-md-3">
                <div class="form-group">
                    <label>Other Document (Optional)</label>
                    @if ($arrivalTicket->freight->other_document)
                        <a href="{{ asset($arrivalTicket->freight->other_document) }}" target="_blank">
                            <img src="{{ asset($arrivalTicket->freight->other_document) }}" class="d-block w-100" />
                        </a>
                    @endif
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Other Document 2 (Optional)</label>
                    @if ($arrivalTicket->freight->other_document_2)
                        <a href="{{ asset($arrivalTicket->freight->other_document_2) }}" target="_blank">
                            <img src="{{ asset($arrivalTicket->freight->other_document_2) }}" class="d-block w-100" />
                        </a>
                    @endif
                </div>
            </div>

        </div>
    @endif




    @if($arrivalTicket->saudaType?->name == 'Thadda')


        <div class="row toggleFreightBox" style="display:none;">
            <div class="col-md-3">
                <div class="form-group">
                    <label>Attach Bilty</label>
                    @if ($arrivalTicket->purchaseOrder?->purchaseFreight?->bilty_slip)
                        <a href="{{ asset($arrivalTicket->purchaseOrder?->purchaseFreight?->bilty_slip) }}" target="_blank">
                            <img src="{{ asset($arrivalTicket->purchaseOrder?->purchaseFreight?->bilty_slip) }}"
                                class="d-block w-100" />
                        </a>
                    @endif
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Attach Weighbridge Slip</label>
                    @if ($arrivalTicket->purchaseOrder?->purchaseFreight?->weighbridge_slip)
                        <a href="{{ asset($arrivalTicket->purchaseOrder?->purchaseFreight?->weighbridge_slip) }}"
                            target="_blank">
                            <img src="{{ asset($arrivalTicket->purchaseOrder?->purchaseFreight?->weighbridge_slip) }}"
                                class="d-block w-100" />
                        </a>
                    @endif
                </div>
            </div>

            <div class="col-md-3">
                <div class="form-group">
                    <label>Supplier Bill</label>
                    @if ($arrivalTicket->purchaseOrder?->purchaseFreight?->supplier_bill)
                        <a href="{{ asset($arrivalTicket->purchaseOrder?->purchaseFreight?->supplier_bill) }}" target="_blank">
                            <img src="{{ asset($arrivalTicket->purchaseOrder?->purchaseFreight?->supplier_bill) }}"
                                class="d-block w-100" />
                        </a>
                    @endif
                </div>
            </div>


        </div>
    @endif
</div>
</div>
@if ($hasLoadingWeight)
    <script>
        var showLumpSum = <?= $showLumpSum ? 'true' : 'false' ?>;
        var isSlabs = <?= $isSlabs ? 'true' : 'false' ?>;
        var isCompulsury = <?= $isCompulsury ? 'true' : 'false' ?>;


        $(document).ready(function () {

            $('[data-toggle="tooltip"]').tooltip();
            $('.select_b').select2();

            const $loadingRadio = $('#loading');
            const $withoutLoadingRadio = $('#without_loading');
            const $loadingSection = $('#loading-section');
            const $contractRangeField = $('.contract-range-field');

            const originalBagWeight = parseFloat($('#original_bag_weight').val()) || 0;
            const loadingWeight = parseFloat($('input[name="billing_weight"]').val()) || 0;
            const noOfBags = parseFloat($('#no_of_bags').val()) || 0;
            const ratePerKg = parseFloat($('#rate_per_kg').val()) || 0;

            const bagRate = parseFloat($('#bag_rate').val()) || 0;
            const kantaCharges = parseFloat($('#kanta_charges').val()) || 0;
            const paidAmount = parseFloat({{ $requestedAmount }});
            const originalRequested = {{ $currentPaymentAmount }};

            // Add this function to calculate filling bags deduction
            function calculateFillingBags() {
                const noOfBags = parseFloat($('#filling_bags_no').val()) || 0;
                const rate = parseFloat($('#filling_bags_rate').val()) || 0;
                const amount = noOfBags * rate;

                $('#filling_bags_amount').val(amount.toFixed(4));
                $('#filling_bags_amount_hidden').val(amount);

                return amount;
            }

            function calculateNetWeight() {
                const loadingWeight = parseFloat($('input[name="billing_weight"]').val()) || 0;
                const currentBagWeight = parseFloat($('#bag_weight_input').val()) || 0;
                return loadingWeight - (currentBagWeight * noOfBags);
            }

            function calculateSlabDeduction(slabData, netWeight) {
                const dValCalculatedOn = slabData.calculation_base_type;
                const appliedDeduction = slabData.applied_deduction;
                const matchingSlabs = slabData.matching_slabs || [];
                const rmPoSlabs = slabData.rm_po_slabs || [];
                const val = slabData.applied_deduction;
                const deductionType = slabData.deduction_type || 'amount';
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
                        const isTiered = (mSlab.is_tiered === true || mSlab.is_tiered === 'true' || mSlab.is_tiered === 1) ? 1 : 0;
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
                if (deductionType !== 'amount') {
                    calculatedValue = (calculatedValue / 100) * ratePerKg;
                }

                return calculatedValue;
            }

            function updateOtherDeduction() {
                const loadingWeight = parseFloat($('input[name="billing_weight"]').val()) || 0;

                const otherDeductionKg = parseFloat($('#other_deduction_kg').val()) || 0;
                const bagWeightTotal = parseFloat($('#bag_weight_total').val()) || 0;
                const otherDeductionAmount = otherDeductionKg * (loadingWeight - bagWeightTotal);
                console.log('other deductioh: ' + loadingWeight);
                $('#other_deduction_amount').val(otherDeductionAmount);
                $('#other_deduction_amount_display').val(otherDeductionAmount.toFixed(4));

                return otherDeductionAmount;
            }

            function updateSamplingResultsDeductions() {
                const netWeight = calculateNetWeight();
                let totalSamplingAmount = 0;

                window.samplingData.samplingResults.forEach(slabData => {
                    const calculatedValue = calculateSlabDeduction(slabData, netWeight);
                    totalSamplingAmount += calculatedValue;

                    $(`.deduction-amount-display[data-slab-id="${slabData.id}"]`).val(calculatedValue.toFixed(4));
                    $(`.deduction-amount-hidden[data-slab-id="${slabData.id}"]`).val(calculatedValue);
                });

                window.samplingData.compulsoryResults.forEach(slabData => {
                    const calculatedValue = slabData.applied_deduction * netWeight;
                    totalSamplingAmount += calculatedValue;

                    $(`.compulsory-deduction-amount[data-compulsory-id="${slabData.id}"]`).val(calculatedValue.toFixed(4));
                });

                if (showLumpSum && !isSlabs && !isCompulsury) {
                    // console.log('show lump sum');

                    var lumpsumAmount = $('tr[data-lumpsum-amount]').data('lumpsum-amount') || 0;
                    var lumpsumKgAmount = $('tr[data-lumpsum-kgamount]').data('lumpsum-kgamount') || 0;
                    var lump_sum_deduction_rupees = $('input[name="lump_sum_deduction_rupees"]').val() || 0;
                    var lump_sum_deduction_kgs = $('input[name="lump_sum_deduction_kgs"]').val() || 0;
                    const loadingWeight = document.querySelector('input[name="billing_weight"]').value;

                    var lumpsumKgsCalculatedValue = parseFloat(lump_sum_deduction_kgs) * parseFloat(loadingWeight);
                    lumpsumKgsCalculatedValue = (lumpsumKgsCalculatedValue / 100) * ratePerKg;
                    var lumpsumRupeesCalculatedValue = parseFloat(lump_sum_deduction_rupees) * parseFloat(loadingWeight);

                    $('#lump_sum_deduction_kgs_total').val(lumpsumKgsCalculatedValue.toFixed(4));
                    $('#lump_sum_deduction_rupees_total').val(lumpsumRupeesCalculatedValue.toFixed(4));


                    // totalSamplingAmount += parseFloat(lumpsumAmount.replace(/,/g, '')) || 0;
                    // totalSamplingAmount += parseFloat(lumpsumKgAmount.replace(/,/g, '')) || 0;
                    totalSamplingAmount += parseFloat(lumpsumAmount.replace(/,/g, '')) || 0;
                    totalSamplingAmount += parseFloat(lumpsumKgsCalculatedValue) || 0;
                    // totalSamplingAmount += parseFloat(lump_sum_deduction_kgs.replace(/,/g, '')) || 0;
                }


                const otherDeductionAmount = updateOtherDeduction();
                totalSamplingAmount += otherDeductionAmount;
                console.log('dddddd:', lumpsumKgsCalculatedValue, loadingWeight, lump_sum_deduction_kgs, ratePerKg, totalSamplingAmount);

                return totalSamplingAmount;
            }

            function updateBagWeightCalculations() {
                const currentBagWeight = parseFloat($('#bag_weight_input').val()) || 0;
                const bagWeightAmount = parseFloat($('#bag_weight_amount').val()) || 0;

                const bagWeightTotal = currentBagWeight * noOfBags;
                $('#bag_weight_total').val(bagWeightTotal.toFixed(4));

                const calculatedBagWeightAmount = ratePerKg * bagWeightTotal;

                if (Math.abs(bagWeightAmount - calculatedBagWeightAmount) > 0.01) {
                    const newBagWeight = bagWeightAmount / (ratePerKg * noOfBags);
                    if (!isNaN(newBagWeight) && isFinite(newBagWeight)) {
                        $('#bag_weight_input').val(newBagWeight.toFixed(4));
                        $('#bag_weight_total').val((newBagWeight * noOfBags).toFixed(4));
                    }
                }
            }

            function updatePaymentRequestCalculations() {
                const totalAmount = parseFloat($('#total_amount').val()) || 0;
                const paidAmount = parseFloat($('#paid_amount').val()) || 0;
                const requested_amount = parseFloat($('#requested_amount').val()) || 0;
                const paymentRequestInput = $('.payment-request-input');
                const percentageInput = $('.percentage-input');

                const currentPaymentRequest = parseFloat(paymentRequestInput.val()) || 0;
                const remainingAmount = totalAmount - requested_amount;

                $('#remaining_amount').val(remainingAmount.toFixed(4));

                if (currentPaymentRequest > 0) {
                    const percentage = remainingAmount > 0 ? (currentPaymentRequest / remainingAmount) * 100 : 0;
                    percentageInput.val(percentage.toFixed(4));
                } else {
                    percentageInput.val('0');
                }

                if (currentPaymentRequest > remainingAmount) {
                    paymentRequestInput.val(remainingAmount.toFixed(4));
                    percentageInput.val('100');
                }
            }

            // ================ FIXED: Function to set billing weight ================
            function setBillingWeight(value) {
                const billingInput = document.querySelector('input[name="billing_weight"]');
                if (!billingInput) return;

                // Set value using multiple methods
                billingInput.value = value;
                billingInput.setAttribute('value', value);
                billingInput.defaultValue = value;

                // Trigger all possible events
                ['input', 'change', 'blur', 'focus', 'keyup', 'keydown', 'keypress'].forEach(eventType => {
                    billingInput.dispatchEvent(new Event(eventType, {
                        bubbles: true,
                        cancelable: true
                    }));
                });

                // Also trigger jQuery events
                $(billingInput).trigger('change').trigger('input');

                console.log('Billing weight set to:', value);
                console.log('DOM Value:', billingInput.value);
                console.log('Attribute Value:', billingInput.getAttribute('value'));
            }
            // ================ END FIX ================

            function updateAllCalculations() {
                updateBagWeightCalculations();

                const currentBagWeight = parseFloat($('#bag_weight_input').val()) || 0;
                const bagWeightAmount = parseFloat($('#bag_weight_amount').val()) || 0;

                const bagRateAmount = bagRate * noOfBags;
                $('#bag_rate_amount').val(bagRateAmount);
                $('#bag_rate_amount_display').val(bagRateAmount.toFixed(4));

                const loadingWeighbridgeAmount = kantaCharges / 2;
                $('#loading_weighbridge_amount').val(loadingWeighbridgeAmount);
                $('#loading_weighbridge_amount_display').val(loadingWeighbridgeAmount.toFixed(4));

                const totalSamplingDeductions = updateSamplingResultsDeductions();

                const deduction_on_access_weight_kg = parseFloat($('#rerate_on_access_weight_kg').val()) || 0;
                const deduction_on_access_weight_rate = parseFloat($('#rerate_on_access_weight_rate').val()) || 0;
                const deduction_on_access_weight_amount = deduction_on_access_weight_rate * deduction_on_access_weight_kg;
                $('#rerate_on_access_weight_amount').val(deduction_on_access_weight_amount.toFixed(4) || 0);


                //     Calculate filling bags deduction
                const fillingBagsAmount = calculateFillingBags();
                const loadingWeight = document.querySelector('input[name="billing_weight"]').value;

                const grossAmount = ratePerKg * loadingWeight;

                // const totalDeductionsForFormula = totalSamplingDeductions + bagWeightAmount +
                //     loadingWeighbridgeAmount + deduction_on_access_weight_amount;

                const totalDeductionsForFormula = totalSamplingDeductions + bagWeightAmount +
                    loadingWeighbridgeAmount + deduction_on_access_weight_amount + fillingBagsAmount;

                const totalAmount = grossAmount - totalDeductionsForFormula + bagRateAmount - parseInt({{ $grossFreightAmount ?? 0 }}) + {{ $totalSupplierCommission }};

                console.log('totalSamplingDeductions: ' + totalSamplingDeductions);
                console.log('bagWeightAmount: ' + bagWeightAmount);
                console.log('loadingWeighbridgeAmount: ' + loadingWeighbridgeAmount);
                console.log('deduction_on_access_weight_amount: ' + deduction_on_access_weight_amount);
                console.log('fillingBagsAmount: ' + fillingBagsAmount);
                console.log('grossAmount: ' + grossAmount);
                console.log('grossFreightAmount: ' + parseInt({{ $grossFreightAmount ?? 0 }}));
                console.log('loadingWeight: ' + parseInt(loadingWeight));
                console.log('totalSupplierCommission: ' + {{ $totalSupplierCommission }});






                $('#total_amount').val(totalAmount);
                $('#total_amount_display').val(totalAmount.toFixed(4));

                updatePaymentRequestCalculations();
                $('#bag_weight_amount_display').val(bagWeightAmount.toFixed(4));
            }

            // ================ FIXED: Exempted weight handler ================
            $(document).on('input', 'input[name="exempted_weight"]', function () {
                const loadingweight = parseFloat($('input[name="loading_weight"]').val()) || 0;
                const access_weight = parseFloat($('input[name="access_weight"]').val()) || 0;
                const exemptedWeight = parseFloat($(this).val()) || 0;

                $(this).next('.error-message').remove();

                if (exemptedWeight > access_weight) {
                    $(this).after('<small class="error-message text-danger">Exempted weight cannot be greater than Excess weight.</small>');
                    setBillingWeight(loadingweight);
                    updateSamplingResultsDeductions();
                    updateAllCalculations();
                    return;
                }

                const billingweight = loadingweight + exemptedWeight;

                // Use the fixed function
                setBillingWeight(billingweight);

                updateSamplingResultsDeductions();
                updateAllCalculations();
            });

            // Other event handlers
            $('#other_deduction_kg').on('input', function () {
                updateAllCalculations();
            });

            $('#bag_weight_input').on('input', function () {
                const currentBagWeight = parseFloat($(this).val()) || 0;
                const bagWeightAmount = ratePerKg * currentBagWeight * noOfBags;
                $('#bag_weight_amount').val(bagWeightAmount.toFixed(4));
                updateAllCalculations();
            });

            $('#bag_weight_amount').on('input', function () {
                updateAllCalculations();
            });

            $('.payment-request-input').on('input', function () {
                const totalAmount = parseFloat($('#total_amount').val()) || 0;
                const paidAmount = parseFloat($('#paid_amount').val()) || 0;
                const requested_amount = parseFloat($('#requested_amount').val()) || 0;
                const newRequested = parseFloat($(this).val()) || 0;
                const remainingAmount = totalAmount - requested_amount;

                if (newRequested > remainingAmount) {
                    $(this).val(remainingAmount.toFixed(4));
                }

                const percentageInput = $('.percentage-input');
                const finalRequested = parseFloat($(this).val()) || 0;
                const percentage = remainingAmount > 0 ? (finalRequested / remainingAmount) * 100 : 0;
                percentageInput.val(percentage.toFixed(4));

                const finalRemaining = totalAmount - (paidAmount + finalRequested);
                $('#remaining_amount').val(finalRemaining.toFixed(4));
            });

            $('.percentage-input').on('input', function () {
                let percentage = parseFloat($(this).val()) || 0;
                if (percentage > 100) {
                    percentage = 100;
                    $(this).val(100);
                }

                const totalAmount = parseFloat($('#total_amount').val()) || 0;
                const paidAmount = parseFloat($('#paid_amount').val()) || 0;
                const requested_amount = parseFloat($('#requested_amount').val()) || 0;
                const remainingAmount = totalAmount - requested_amount;

                const amount = (remainingAmount * percentage) / 100;
                $('.payment-request-input').val(amount.toFixed(4));

                const finalRemaining = totalAmount - (requested_amount + amount);
                $('#remaining_amount').val(finalRemaining.toFixed(4));
            });

            $('input[name="freight_pay_request_amount"]').on('input', function () {
                const amount = parseFloat({{ $advanceFreight }});
                const paidAmount = parseFloat({{ $pRsSumForFreight }});
                const paymentRequest = parseFloat($(this).val()) || 0;
                const remaining = (amount - paymentRequest - paidAmount);
                $('input[name="remaining_freight"]').val(remaining.toFixed(4));
            });

            const remainingAmountF = parseFloat($('input[name="remaining_freight"]').val()) || 0;
            const percentageInputF = $('.percentage-input-freight');
            const paymentRequestInputF = $('.payment-request-freifht');

            percentageInputF.on('input', function () {
                let percentage = parseFloat($(this).val()) || 0;
                if (percentage > 100) {
                    percentage = 100;
                    $(this).val(100);
                }
                const amount = (remainingAmountF * percentage) / 100;
                paymentRequestInputF.val(amount.toFixed(4));
            });

            paymentRequestInputF.on('input', function () {
                let amount = parseFloat($(this).val()) || 0;
                if (amount > remainingAmountF) {
                    amount = remainingAmountF;
                    $(this).val(remainingAmountF.toFixed(4));
                }
                const percentage = remainingAmountF > 0 ? (amount / remainingAmountF) * 100 : 0;
                percentageInputF.val(percentage.toFixed(4));
            });

            // Other handlers
            $(document).on('input', '#rerate_on_access_weight_kg, #rerate_on_access_weight_rate', function () {
                updateAllCalculations();
            });
            $(document).on('input', '#filling_bags_no, #filling_bags_rate', function () {
                updateAllCalculations();
            });


            $(".togglehistory").click(function () {
                $(".togglehistorytable").slideToggle(400);
                $(this).toggleClass("active");
            });

            // Initial calculations
            updateAllCalculations();
        });
    </script>
@endif