@php
    $isLumpSumEnabled = $arrivalSamplingRequest->is_lumpsum_deduction == 1 ? true : false;
    $latestIsLumpsum = end($innerRequestsData)['request']->is_lumpsum_deduction ?? null;

    $isLumpSumEnabledForInitial =
        isset($initialRequestForInnerReq) && $initialRequestForInnerReq->is_lumpsum_deduction == 1 ? true : false;
    $isDecisionMaking = isset($arrivalSamplingRequest) && $arrivalSamplingRequest->decision_making == 1 ? true : false;
    $isDecisionMakingForInitial =
        isset($initialRequestForInnerReq) && $initialRequestForInnerReq->decision_making == 1 ? true : false;
    $isDecisionMakingDisabled =
        isset($arrivalSamplingRequest) &&
        $arrivalSamplingRequest->purchaseOrder->decision_making == 0 &&
        $arrivalSamplingRequest->purchaseOrder->decision_making_time
            ? true
            : false;

    $valuesOfInitialSlabs = [];
    $suggestedValueForInner = 0;
    $suggestedValue = 0;
    $suggestedValueForInnerKgs = 0;
    $suggestedValueKgs = 0;

    $previousInnerRequest = null;

    if (isset($innerRequestsData) && count($innerRequestsData)) {
        $previousInnerRequest = $innerRequestsData[count($innerRequestsData) - 1];
    } elseif (isset($initialRequestsData) && count($initialRequestsData)) {
        $previousInnerRequest = $initialRequestsData[count($initialRequestsData) - 1];
    }

@endphp

<form action="{{ route('raw-material.sampling-monitoring.update', $arrivalSamplingRequest->id) }}" method="POST"
    id="ajaxSubmit" autocomplete="off">
    @csrf
    @method('PUT')
    <input type="hidden" id="listRefresh" value="{{ route('raw-material.get.sampling-monitoring') }}" />
    <div class="row form-mar">
        <div class="col-12">
            <h6 class="header-heading-sepration">
                Contract Detail
            </h6>
        </div>

        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="row">
                <div class="col-xs-12 col-sm-12 col-md-12">
                    <div class="form-group">
                        <label>Contract No:</label>
                        <input type="text" disabled="" class="form-control"
                            value="{{ $arrivalSamplingRequest->purchaseOrder->contract_no ?? 'N/A' }}"
                            placeholder="QC Product">
                    </div>
                </div>
                <div class="col-xs-12 col-sm-12 col-md-12">
                    <div class="form-group">
                        <label>QC Product:</label>
                        <input type="text" disabled="" class="form-control"
                            value="{{ $arrivalSamplingRequest->purchaseOrder->qcProduct->name ?? 'N/A' }}"
                            placeholder="QC Product">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="slabsContainer">
        <div class="row">
            <div class="col-12">
                <h6 class="header-heading-sepration">
                    QC Checklist
                </h6>
            </div>
        </div>
        <ul class="nav nav-tabs" id="qcChecklistTabs" role="tablist">
            {{-- @if ($initialRequestForInnerReq)
                <li class="nav-item">
                    <a class="nav-link" id="initial-tab" data-toggle="tab" href="#initial" role="tab"
                        aria-controls="initial" aria-selected="false">Initial Checklist</a>
                </li>
            @endif --}}

            @foreach ($innerRequestsData as $index => $innerData)
                <li class="nav-item">
                    <a class="nav-link" id="inner-{{ $index }}-tab" data-toggle="tab"
                        href="#inner-{{ $index }}" role="tab" aria-controls="inner-{{ $index }}"
                        aria-selected="false">
                        Initial #{{ $loop->iteration }} ({{ $innerData['request']->created_at->format('M d, Y') }})
                    </a>
                </li>
            @endforeach

            <li class="nav-item">
                <a class="nav-link active" id="current-inner-tab" data-toggle="tab" href="#current-inner" role="tab"
                    aria-controls="current-inner" aria-selected="true">
                    {{ ucwords(($arrivalSamplingRequest->sampling_type == 'inner' ? 'Current ' : '') . $arrivalSamplingRequest->sampling_type) }}
                    Checklist
                </a>
            </li>
        </ul>

        <div class="tab-content" id="qcChecklistTabsContent">
            @foreach ($innerRequestsData as $index => $innerData)
                <div class="tab-pane fade" id="inner-{{ $index }}" role="tabpanel"
                    aria-labelledby="inner-{{ $index }}-tab">
                    <div class="row w-100 mx-auto">
                        <div class="col-md-4"></div>
                        <div class="col-md-3 py-2 QcResult">
                            <h6>Result</h6>
                        </div>
                        <div class="col-md-3 py-2 Suggested">
                            <h6>Suggested Deduction</h6>
                        </div>
                        <div class="col-md-2 py-2 QcResult">
                            <h6>Deduction</h6>
                        </div>
                    </div>

                    <div class="striped-rows">
                        @if (count($innerData['results']) != 0)
                            @foreach ($innerData['results'] as $slab)
                                @php
                                    $previousChecklistValue = null;

                                    if ($index > 0) {
                                        foreach ($innerRequestsData[$index - 1]['results'] as $prevSlab) {
                                            if ($prevSlab->slabType->id == $slab->slabType->id) {
                                                $previousChecklistValue = $prevSlab->checklist_value;
                                                break;
                                            }
                                        }
                                    }

                                    if ($previousChecklistValue === null && !empty($initialRequestsData)) {
                                        $lastInitialData = $initialRequestsData[count($initialRequestsData) - 1];
                                        foreach ($lastInitialData['results'] as $initialSlab) {
                                            if ($initialSlab->slabType->id == $slab->slabType->id) {
                                                $previousChecklistValue = $initialSlab->checklist_value;
                                                break;
                                            }
                                        }
                                    }

                                    $comparisonClass = '';
                                    if ($previousChecklistValue !== null) {
                                        if ($slab->checklist_value > $previousChecklistValue) {
                                            $comparisonClass = 'checklist-increase';
                                        } elseif ($slab->checklist_value < $previousChecklistValue) {
                                            $comparisonClass = 'checklist-decrease';
                                        } else {
                                            $comparisonClass = 'checklist-same';
                                        }
                                    }

                                    if (((float) $slab->checklist_value ?? 0) > ((float) $slab->max_range ?? 0)) {
                                        $comparisonClass = 'slabs-checklist-rise';
                                    }
                                @endphp
                                <div class="form-group row checklist-box">
                                    <label
                                        class="col-md-4 label-control font-weight-bold">{{ $slab->slabType->name }}</label>
                                    <div class="col-md-3 QcResult">
                                        <div class="input-group mb-0">
                                            <input type="text" readonly class="form-control {{ $comparisonClass }}"
                                                value="{{ $slab->checklist_value }}">
                                            <div class="input-group-append">
                                                <span
                                                    class="input-group-text text-sm">{{ $slab->slabType->qc_symbol }}</span>
                                            </div>
                                        </div>
                                        @if ($previousChecklistValue !== null)
                                            <span class="checklist-value-comparison">
                                                Previous: {{ $previousChecklistValue }}
                                            </span>
                                        @endif
                                    </div>
                                    <div class="col-md-3 Suggested">
                                        <div class="input-group mb-0">
                                            <input type="text" disabled class="form-control"
                                                value="{{ $slab->suggested_deduction ?? 0 }}">
                                            <div class="input-group-append">
                                                <span class="input-group-text text-sm">Rs.</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-2 QcResult">
                                        <div class="input-group mb-0">
                                            <input type="text" readonly class="form-control"
                                                value="{{ $slab->applied_deduction ?? 0 }}">
                                            <div class="input-group-append">
                                                <span class="input-group-text text-sm">Rs.</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="alert alert-warning">No Slabs Found</div>
                        @endif
                    </div>

                    <div class="row w-100 mx-auto mt-3">
                        <div class="col-md-4"></div>
                        <div class="col-md-6 py-2 QcResult">
                            <h6>Result</h6>
                        </div>
                        <div class="col-md-2 py-2 QcResult">
                            <h6>Deduction</h6>
                        </div>
                    </div>

                    <div class="striped-rows">
                        @if (count($innerData['compulsuryResults']) != 0)
                            @foreach ($innerData['compulsuryResults'] as $slab)
                                @php
                                    $defaultValue = '';
                                    $displayCompValue = $slab->compulsory_checklist_value;

                                    if ($slab->qcParam->type == 'dropdown') {
                                        $options = json_decode($slab->qcParam->options, true);
                                        $defaultValue = $options[0] ?? '';
                                    }

                                    $compulsaryClass = '';

                                    if ($displayCompValue != $defaultValue) {
                                        $compulsaryClass = 'slabs-checklist-changed-compulsury';
                                    }
                                @endphp
                                <div class="form-group row">
                                    <label class="label-control font-weight-bold col-md-4"
                                        data-default-value="{{ $defaultValue }}">{{ $slab->qcParam->name }}</label>
                                    <div
                                        class="QcResult {{ checkIfNameExists($slab->qcParam->name) ? 'col-md-8' : 'col-md-6' }}">
                                        @if ($slab->qcParam->type == 'dropdown')
                                            <input type="text" class="form-control {{ $compulsaryClass }}"
                                                value="{{ $slab->compulsory_checklist_value }}" readonly>
                                        @else
                                            <textarea class="form-control {{ $compulsaryClass }}" readonly>{{ $slab->compulsory_checklist_value }}</textarea>
                                        @endif
                                    </div>
                                    @if (!checkIfNameExists($slab->qcParam->name))
                                        <div class="col-md-2 QcResult">
                                            <input type="text" class="form-control" readonly
                                                value="{{ $slab->applied_deduction }}">
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        @else
                            <div class="alert alert-warning">No Compulsory Slabs Found</div>
                        @endif
                    </div>

                    <div class="striped-rows mt-3">
                        <div class="form-group row">
                            <label class="col-md-4 label-control font-weight-bold">Apply Lumpsum Deduction</label>
                            <div class="col-md-3">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" disabled
                                        @checked($innerData['request']->is_lumpsum_deduction == 1)>
                                    <label class="custom-control-label"></label>
                                </div>
                            </div>
                            <div class="col">
                                <div class="input-group mb-1">
                                    <input type="text" class="form-control" readonly
                                        value="{{ $innerData['request']->lumpsum_deduction ?? 0 }}">
                                    <div class="input-group-append">
                                        <span class="input-group-text text-sm">Rs.</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col">
                                <div class="input-group mb-0">
                                    <input type="text" class="form-control" readonly
                                        value="{{ $innerData['request']->lumpsum_deduction_kgs ?? 0 }}">
                                    <div class="input-group-append">
                                        <span class="input-group-text text-sm">KG's</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-md-4 label-control font-weight-bold">Decision Making on Avg.</label>
                            <div class="col-md-3">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" disabled
                                        @checked($innerData['request']->decision_making == 1)>
                                    <label class="custom-control-label"></label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach

            <div class="tab-pane fade show active" id="current-inner" role="tabpanel"
                aria-labelledby="current-inner-tab">
                <div class="row w-100 mx-auto">
                    <div class="col-md-4"></div>
                    <div class="col-md-3 py-2 QcResult">
                        <h6>Result</h6>
                    </div>
                    <div class="col-md-3 py-2 Suggested">
                        <h6>Suggested Deduction</h6>
                    </div>
                    <div class="col-md-2 py-2 QcResult">
                        <h6>Deduction</h6>
                    </div>
                </div>


                <div class="striped-rows">
                    @if (count($results) != 0)
                        @foreach ($results as $slab)
                            @php
                                $previousValue = null;
                                if (
                                    ($slab->checklist_value === null || $slab->checklist_value == 0) &&
                                    $previousInnerRequest
                                ) {
                                    foreach ($previousInnerRequest['results'] as $prevSlab) {
                                        if ($prevSlab->slabType->id == $slab->slabType->id) {
                                            $previousValue = $prevSlab->checklist_value;
                                            break;
                                        }
                                    }
                                }

                                $displayValue =
                                    ($previousValue ?? ($slab->checklist_value ?? 0)) - ($slab->relief_deduction ?? 0);

                                $getDeductionSuggestion = getDeductionSuggestion(
                                    $slab->slabType->id,
                                    optional($arrivalSamplingRequest->purchaseOrder)->qc_product,
                                    $displayValue,
                                    $arrivalSamplingRequest->purchaseOrder->id ?? null,
                                );

                                $previousDeduction = null;
                                if (
                                    ($slab->applied_deduction === null || $slab->applied_deduction == 0) &&
                                    $previousInnerRequest
                                ) {
                                    foreach ($previousInnerRequest['results'] as $prevSlab) {
                                        if ($prevSlab->slabType->id == $slab->slabType->id) {
                                            $previousDeduction = $prevSlab->applied_deduction;
                                            break;
                                        }
                                    }
                                }
                                $innerDeductionValue = $latestIsLumpsum
                                    ? 0
                                    : $previousDeduction ??
                                        ($slab->applied_deduction ?? ($valuesOfInitialSlabs[$slab->slabType->id] ?? 0));

                                $suggestedDeductionType = $getDeductionSuggestion->deduction_type ?? 'amount';

                                $suggestedDeductionType == 'amount'
                                    ? ($suggestedValue += $getDeductionSuggestion->deduction_value ?? 0)
                                    : ($suggestedValueKgs += $getDeductionSuggestion->deduction_value ?? 0);

                                $previousChecklistValue = null;

                                if (!empty($innerRequestsData)) {
                                    $lastInnerRequestData = $innerRequestsData[count($innerRequestsData) - 1];
                                    foreach ($lastInnerRequestData['results'] as $lastSlab) {
                                        if ($lastSlab->slabType->id == $slab->slabType->id) {
                                            $previousChecklistValue = $lastSlab->checklist_value;
                                            break;
                                        }
                                    }
                                }

                                if ($previousChecklistValue === null && !empty($initialRequestsData)) {
                                    $lastInitialData = $initialRequestsData[count($initialRequestsData) - 1];
                                    foreach ($lastInitialData['results'] as $initialSlab) {
                                        if ($initialSlab->slabType->id == $slab->slabType->id) {
                                            $previousChecklistValue = $initialSlab->checklist_value;
                                            break;
                                        }
                                    }
                                }

                                $displayValue =
                                    ($previousValue ?? ($slab->checklist_value ?? 0)) - ($slab->relief_deduction ?? 0);

                                $comparisonClass = '';
                                if ($previousChecklistValue !== null) {
                                    if ($displayValue > $previousChecklistValue) {
                                        $comparisonClass = 'checklist-increase';
                                    } elseif ($displayValue < $previousChecklistValue) {
                                        $comparisonClass = 'checklist-decrease';
                                    } else {
                                        $comparisonClass = 'checklist-same';
                                    }
                                }

                                if (((float) $slab->checklist_value ?? 0) > ((float) $slab->max_range ?? 0)) {
                                    $comparisonClass = 'slabs-checklist-rise';
                                }
                            @endphp
                            <div class="form-group row checklist-box">
                                <input type="hidden" name="product_slab_type_id[]"
                                    value="{{ $slab->slabType->id }}">
                                <label
                                    class="col-md-4 label-control font-weight-bold">{{ $slab->slabType->name }}</label>
                                <div class="col-md-3 QcResult">
                                    <div class="input-group mb-0">
                                        <input type="text" class="form-control {{ $comparisonClass }}"
                                            name="checklist_value[]" value="{{ $displayValue }}" placeholder="%"
                                            readonly>
                                        <div class="input-group-append">
                                            <span
                                                class="input-group-text text-sm">{{ $slab->slabType->qc_symbol }}</span>
                                        </div>
                                    </div>
                                    @if ($previousChecklistValue !== null)
                                        <span class="checklist-value-comparison">
                                            Previous: {{ $previousChecklistValue }}
                                        </span>
                                    @endif
                                </div>
                                <div class="col-md-3 Suggested">
                                    <div class="input-group mb-0">
                                        <input type="text" class="form-control" name="suggested_deduction[]"
                                            value="{{ $getDeductionSuggestion->deduction_value ?? 0 }}"
                                            placeholder="Suggested Deduction" readonly>
                                        <div class="input-group-append">
                                            <span
                                                class="input-group-text text-sm">{{ $suggestedDeductionType == 'amount' ? 'Rs.' : 'KG\'s' }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-2 QcResult">
                                    <div class="input-group mb-0">
                                        <input type="text" id="deduction-{{ $slab->slabType->id }}"
                                            class="form-control bg-white deduction-field" name="applied_deduction[]"
                                            value="{{ $innerDeductionValue }}" placeholder="Deduction"
                                            data-matching-slabs="{{ json_encode($slab->matching_slabs) }}"
                                            data-rm-po-slabs="{{ json_encode($slab->rm_po_slabs) }}"
                                            data-calculated-on="{{ $slab->slabType->calculation_base_type }}"
                                            data-slab-id="{{ $slab->slabType->id }}"
                                            data-product-id="{{ optional($arrivalSamplingRequest->purchaseOrder)->product->id }}"
                                            data-checklist="{{ $displayValue }}"
                                            {{ $latestIsLumpsum ? 'readonly' : '' }}>
                                        <div class="input-group-append">
                                            <span
                                                class="input-group-text text-sm">{{ SLAB_TYPES_CALCULATED_ON[$slab->slabType->calculation_base_type ?? 1] }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="alert alert-warning">No Slabs Found</div>
                    @endif
                </div>

                <div class="row w-100 mx-auto mt-3">
                    <div class="col-md-4"></div>
                    <div class="col-md-6 py-2 QcResult">
                        <h6>Result</h6>
                    </div>
                    <div class="col-md-2 py-2 QcResult">
                        <h6>Deduction</h6>
                    </div>
                </div>
                <div class="striped-rows">
                    @if (count($Compulsuryresults) != 0)
                        @foreach ($Compulsuryresults as $slab)
                            @php
                                $previousCompValue = null;
                                $displayCompValue = $previousCompValue ?? $slab->compulsory_checklist_value;
                                $previousCompDeduction = null;
                                if (
                                    ($slab->applied_deduction === null || $slab->applied_deduction == 0) &&
                                    $previousInnerRequest
                                ) {
                                    foreach ($previousInnerRequest['compulsuryResults'] as $prevComp) {
                                        if ($prevComp->qcParam->id == $slab->qcParam->id) {
                                            $previousCompDeduction = $prevComp->applied_deduction;
                                            break;
                                        }
                                    }
                                }
                                $compDeductionValue = $previousCompDeduction ?? ($slab->applied_deduction ?? 0);

                                $defaultValue = '';
                                if ($slab->qcParam->type == 'dropdown') {
                                    $options = json_decode($slab->qcParam->options, true);
                                    $defaultValue = $options[0] ?? '';
                                }

                                $compulsaryClass = '';

                                if ($displayCompValue != $defaultValue) {
                                    $compulsaryClass = 'slabs-checklist-changed-compulsury';
                                }
                            @endphp

                            <div class="form-group row">
                                <input type="hidden" name="compulsory_param_id[]" value="{{ $slab->qcParam->id }}">
                                <label
                                    class="label-control font-weight-bold col-md-4">{{ $slab->qcParam->name }}</label>
                                <div
                                    class="QcResult {{ checkIfNameExists($slab->qcParam->name) ? 'col-md-8' : 'col-md-6' }}">
                                    @if ($slab->qcParam->type == 'dropdown')
                                        <input type="text" class="form-control {{ $compulsaryClass }}"
                                            name="compulsory_checklist_value[]" value="{{ $displayCompValue }}"
                                            data-default-value="{{ $defaultValue }}" readonly>
                                    @else
                                        <textarea class="form-control {{ $compulsaryClass }}" name="compulsory_checklist_value[]" readonly>{{ $displayCompValue }}</textarea>
                                    @endif
                                </div>
                                @if (!checkIfNameExists($slab->qcParam->name))
                                    <div class="col-md-2 QcResult">
                                        <div class="input-group mb-0">
                                            <input type="text" id="inp-{{ $slab->qcParam->id }}"
                                                class="form-control bg-white deduction-field"
                                                name="compulsory_aapplied_deduction[]"
                                                value="{{ $compDeductionValue }}" placeholder="Deduction"
                                                data-slab-id="{{ $slab->qcParam->id }}"
                                                data-calculated-on="{{ $slab->qcParam->calculation_base_type }}"
                                                data-checklist="{{ $displayCompValue }}"
                                                {{ $latestIsLumpsum ? 'readonly' : '' }}>
                                            <div class="input-group-append">
                                                <span
                                                    class="input-group-text text-sm">{{ SLAB_TYPES_CALCULATED_ON[$slab->qcParam->calculation_base_type ?? 1] }}</span>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <input type="hidden" name="compulsory_aapplied_deduction[]" value="0">
                                @endif
                            </div>
                        @endforeach
                    @else
                        <div class="alert alert-warning">No Compulsory Slabs Found</div>
                    @endif
                </div>

                <div class="striped-rows mt-3">
                    <div class="form-group row">
                        <label class="col-md-4 label-control font-weight-bold" for="lumpsum-toggle">Apply Lumpsum
                            Deduction</label>
                        <div class="col-md-3">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="lumpsum-toggle"
                                    name="{{ !$latestIsLumpsum ? 'is_lumpsum_deduction' : 'is_lumpsum_deduction_display' }}"
                                    @checked($latestIsLumpsum) @disabled($latestIsLumpsum)>
                                @if ($latestIsLumpsum)
                                    <input type="hidden" name="is_lumpsum_deduction" value="on">
                                @endif
                                <label class="custom-control-label" for="lumpsum-toggle"></label>
                            </div>
                        </div>
                        <div class="col">
                            <div class="input-group mb-2">
                                <input type="text" id="suggessions-sum" class="form-control"
                                    name="suggessions_sum" disabled value="{{ $suggestedValue }}"
                                    placeholder="Suggested Sum">
                                <div class="input-group-append">
                                    <span class="input-group-text text-sm">Rs.</span>
                                </div>
                            </div>
                            <div class="input-group mb-0">
                                <input type="text" id="suggessions-sum" class="form-control"
                                    name="suggessions_sum" disabled value="{{ $suggestedValueKgs }}"
                                    placeholder="Suggested Sum">
                                <div class="input-group-append">
                                    <span class="input-group-text text-sm">Kgs.</span>
                                </div>
                            </div>
                        </div>
                        <div class="col">
                            <div class="input-group mb-2">
                                <input type="text" id="lumpsum-value" class="form-control"
                                    name="lumpsum_deduction" {{ $latestIsLumpsum ? '' : 'readonly' }}
                                    {{-- value="{{ $arrivalSamplingRequest->lumpsum_deduction ?? ($rupeeLumpSum ?? 0) }}" --}} value="{{ $rupeeLumpSum ?? 0 }}"
                                    placeholder="Lumpsum Deduction">
                                <div class="input-group-append">
                                    <span class="input-group-text text-sm">Rs.</span>
                                </div>
                            </div>
                            <div class="input-group mb-0">
                                <input type="text" id="lumpsum-kgs-value" class="form-control"
                                    name="lumpsum_deduction_kgs" {{ $latestIsLumpsum ? '' : 'readonly' }}
                                    {{-- value="{{ $arrivalSamplingRequest->lumpsum_deduction_kgs ?? ($kgLumpSum ?? 0) }}" --}} value="{{ $kgLumpSum ?? 0 }}"
                                    placeholder="Lumpsum Deduction">
                                <div class="input-group-append">
                                    <span class="input-group-text text-sm">KG's</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-md-4 label-control font-weight-bold" for="decision_making">Decision Making
                            on Avg.</label>
                        <div class="col-md-3">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" name="decision_making" class="custom-control-input"
                                    id="decision_making" @checked($isDecisionMaking) @disabled($isDecisionMakingDisabled)>
                                <label class="custom-control-label" for="decision_making"></label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row mt-3">
            <div class="col-12">
                <h6 class="header-heading-sepration">
                    Other Details
                </h6>
            </div>
            <div class="col-12 px-3">
                <div class="form-group ">
                    <label>Sample Taken By:</label>
                    <select name="sample_taken_by" id="sample_taken_by" class="form-control select2" disabled>
                        <option value="">Sample Taken By</option>
                        @foreach ($sampleTakenByUsers as $sampleTakenUser)
                            <option @selected($arrivalSamplingRequest->sample_taken_by == $sampleTakenUser->id) value="{{ $sampleTakenUser->id }}">
                                {{ $sampleTakenUser->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-12 px-3">
                <div class="form-group ">
                    <label>Sample Analysis By: </label>
                    <input type="text" readonly disabled name="sample_analysis_by"
                        placeholder="Sample Analysis By" class="form-control" autocomplete="off"
                        value="{{ auth()->user()->name ?? '' }}" />
                </div>
            </div>
            <div class="col-12 px-3">
                <div class="form-group ">
                    <label>Party Ref. No: </label>
                    <select name="party_ref_no" id="party_ref_no" class="form-control select2" disabled>
                        <option value="{{ $arrivalSamplingRequest->party_ref_no }}">
                            {{ $arrivalSamplingRequest->party_ref_no }}</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-xs-6 col-sm-6 col-md-6 full">
                <div class="form-group">
                    <label class="d-block">Sauda Type:</label>
                    @php
                        $isDisabled =
                            optional($arrivalSamplingRequest->purchaseOrder)->sauda_type_id ??
                            (null && optional($arrivalSamplingRequest->purchaseOrder)->id ?? null);
                    @endphp

                    @if ($isDisabled)
                        <input type="hidden" name="sauda_type_id"
                            value="{{ optional($arrivalSamplingRequest->purchaseOrder)->sauda_type_id ?? '' }}">
                        <select disabled class="form-control w-100 select2">
                        @else
                            <select name="sauda_type_id" id="sauda_type_id" class="form-control w-100 select2">
                    @endif
                    <option value="">Select Sauda Type</option>
                    @foreach ($saudaTypes as $saudaType)
                        <option @selected(optional($arrivalSamplingRequest->purchaseOrder)->sauda_type_id == $saudaType->id) value="{{ $saudaType->id }}">
                            {{ $saudaType->name }}</option>
                    @endforeach
                    </select>
                </div>
            </div>
            <div class="col-xs-6 col-sm-6 col-md-6 full">
                <div class="form-group ">
                    <label>Status:</label>
                    @if (in_array($arrivalSamplingRequest->approved_status, ['approved', 'resampling', 'rejected']))
                        <input type="hidden" name="stage_status"
                            value="{{ $arrivalSamplingRequest->approved_status }}">
                    @endif
                    <select
                        name="{{ in_array($arrivalSamplingRequest->approved_status, ['approved', 'resampling', 'rejected']) ? 'stage_status_display' : 'stage_status' }}"
                        id="stage_status" class="form-control select2" @disabled(in_array($arrivalSamplingRequest->approved_status, ['approved', 'resampling', 'rejected']))>
                        <option value="" hidden>Choose Status</option>
                        <option {{ $arrivalSamplingRequest->approved_status == 'approved' ? 'selected' : '' }}
                            value="approved">
                            Approved</option>
                        <option {{ $arrivalSamplingRequest->approved_status == 'resampling' ? 'selected' : '' }}
                            value="resampling">Request Resampling</option>
                        <option {{ $arrivalSamplingRequest->approved_status == 'rejected' ? 'selected' : '' }}
                            value="rejected">
                            Rejected</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-xs-12 col-sm-12 col-md-12">
                <div class="form-group ">
                    <label>Your Remarks (Optional):</label>
                    <textarea name="remarks" row="4" class="form-control" placeholder="Description">{{ $arrivalSamplingRequest->remark }}</textarea>
                </div>
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
        // $('#qcChecklistTabs .nav-link').not('#current-inner-tab').on('click', function(e) {
        //     e.preventDefault();
        //     return false;
        // }).addClass('disabled');

        function calculateTotal() {
            let total = 0;
            let totalKgs = 0;

            $('.deduction-field').each(function() {
                let matchingSlabs = $(this).data('matching-slabs') || [];
                let rmPoSlabs = $(this).data('rm-po-slabs') || [];
                let calculatedOn = $(this).data('calculated-on');
                let val = parseFloat($(this).val()) || 0;

                if (calculatedOn == {{ SLAB_TYPE_PERCENTAGE }}) {
                    let deductionValue = 0;

                    let highestRmPoEnd = 0;
                    rmPoSlabs.forEach(rmPoSlab => {
                        let rmPoTo = rmPoSlab.to ? parseFloat(rmPoSlab.to) : 0;
                        if (rmPoTo > highestRmPoEnd) {
                            highestRmPoEnd = rmPoTo;
                        }
                    });

                    matchingSlabs.forEach(slab => {
                        let from = parseFloat(slab.from);
                        let to = slab.to ? parseFloat(slab.to) : Infinity;
                        let isTiered = parseInt(slab.is_tiered);
                        let deductionVal = parseFloat(slab.deduction_value);

                        if (val < from) return;

                        let effectiveFrom = Math.max(from, highestRmPoEnd + 1);
                        let effectiveTo = Math.min(to, val);

                        if (effectiveFrom <= effectiveTo) {
                            if (isTiered === 1) {
                                let applicableAmount = effectiveTo - effectiveFrom + 1;
                                deductionValue += deductionVal * applicableAmount;
                            } else {
                                deductionValue += deductionVal;
                            }
                        }
                    });

                    total += deductionValue;
                } else if (calculatedOn == {{ SLAB_TYPE_KG }}) {
                    totalKgs += val || 0;
                } else {
                    total += val || 0;
                }
            });

            $('#lumpsum-value').val(total.toFixed(2));
            $('#lumpsum-kgs-value').val(totalKgs.toFixed(2));
        }

        calculateTotal();

        if ({{ $arrivalSamplingRequest->is_lumpsum_deduction == 1 ? 'true' : 'false' }}) {
            $('#lumpsum-value').val({{ $arrivalSamplingRequest->lumpsum_deduction ?? 0 }}.toFixed(2));
            $('#lumpsum-kgs-value').val({{ $arrivalSamplingRequest->lumpsum_deduction_kgs ?? 0 }}.toFixed(2));
        }

        $('.deduction-field').on('input', calculateTotal);

        $('#lumpsum-toggle').change(function() {
            if ($(this).is(':checked')) {
                Swal.fire({
                    title: 'Are you sure?',
                    text: "This will reset all individual deductions!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, apply lumpsum!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $('.deduction-field').val('0').prop('readonly', true);
                        $('#lumpsum-value').prop('readonly', false);
                        $('#lumpsum-kgs-value').prop('readonly', false);
                        calculateTotal();
                    } else {
                        $(this).prop('checked', false);
                    }
                });
            } else {
                $('.deduction-field').prop('readonly', false);
                $('#lumpsum-value').prop('readonly', true).val('0');
                $('#lumpsum-kgs-value').prop('readonly', true).val('0');
                calculateTotal();
            }
        });

        $('#arrival_sampling_request_id').change(function() {
            var samplingRequestId = $(this).val();

            if (samplingRequestId) {
                $.ajax({
                    url: '{{ route('getSlabsByProduct') }}',
                    type: 'GET',
                    data: {
                        sampling_request_id: samplingRequestId
                    },
                    dataType: 'json',
                    beforeSend: function() {
                        Swal.fire({
                            title: "Processing...",
                            text: "Please wait while fetching slabs.",
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });
                    },
                    success: function(response) {
                        Swal.close();
                        if (response.success) {
                            // Append the rendered HTML to a container element
                            $('#slabsContainer').html(response.html);
                        } else {
                            Swal.fire("No Data", "No slabs found for this product.",
                                "info");
                        }
                    },
                    error: function() {
                        Swal.close();
                        Swal.fire("Error", "Something went wrong. Please try again.",
                            "error");
                    }
                });
            }
        });

        $('.select2').select2();

        $(document).on('change', '[name="arrival_purchase_order_id"]', function() {
            let saudaTypeId = $(this).find(':selected').data('saudatypeid');

            let $saudaTypeSelect = $('#sauda_type_id');
            let $hiddenInput = $('#actual_sauda_type_id');

            if (saudaTypeId) {
                $saudaTypeSelect.val(saudaTypeId).trigger('change');
                $saudaTypeSelect.prop('disabled', true);
                $hiddenInput.val(saudaTypeId);
            } else {
                $saudaTypeSelect.prop('disabled', false).val('').trigger('change');
                $hiddenInput.val('');
            }
        });

        initializeDynamicSelect2('#party_ref_no', 'arrival_custom_sampling', 'party_ref_no', 'party_ref_no',
            true,
            false);
        //initializeDynamicSelect2('#sampling_request_id', 'arrival_sampling_requests', 'name', 'id', false, false);
        //initializeDynamicSelect2('#supplier_name', 'suppliers', 'name', 'name', true, false);
        //initializeDynamicSelect2('#broker_name', 'brokers', 'name', 'name', true, false);
        //  function initializeDynamicSelect2(selector, tableName, columnName, idColumn = 'id', enableTags = false, isMultiple = true) {
    });
</script>

<style>
    .Suggested {
        background: #00990078;
    }

    .QcResult {
        background: #8080802b;
    }

    .nav-link.disabled {
        color: #6c757d;
        pointer-events: none;
    }
</style>
