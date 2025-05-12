@php
    $isLumpSumEnabled = $arrivalSamplingRequest->is_lumpsum_deduction == 1 ? true : false;
    $isLumpSumEnabledForInitial =
        isset($initialRequestForInnerReq) && $initialRequestForInnerReq->is_lumpsum_deduction == 1 ? true : false;
    $isDecisionMaking = isset($arrivalSamplingRequest) && $arrivalSamplingRequest->decision_making == 1 ? true : false;
    $isDecisionMakingForInitial =
        isset($initialRequestForInnerReq) && $initialRequestForInnerReq->decision_making == 1 ? true : false;
    $valuesOfInitialSlabs = [];
    $suggestedValueForInner = 0;
    $suggestedValue = 0;

    $previousInnerRequest = $innerRequestsData[0] ?? null;
@endphp

<form action="{{ route('sampling-monitoring.update', $arrivalSamplingRequest->id) }}" method="POST" id="ajaxSubmit"
    autocomplete="off">
    @csrf
    @method('PUT')
    <input type="hidden" id="listRefresh" value="{{ route('get.sampling-monitoring') }}" />
    <div class="row form-mar">
        <div class="col-12">
            <h6 class="header-heading-sepration">
                Ticket Detail
            </h6>
        </div>

        <div class="col-xs-6 col-sm-6 col-md-6">
            <fieldset data-toggle="collapse" href="#collapse11" aria-expanded="false" aria-controls="collapse11">
                <div class="input-group">
                    <div class="input-group-prepend">
                        <button class="btn btn-primary" type="button">
                            Ticket No.
                            <i class="fa fa-chevron-down ml-2 toggle-icon" aria-hidden="true"></i>
                        </button>
                    </div>
                    <input type="text" disabled class="form-control"
                        value="{{ optional($arrivalSamplingRequest->arrivalTicket)->unique_no }}"
                        placeholder="Ticket No.">
                </div>
            </fieldset>
        </div>

        <div class="col-xs-12 col-sm-12 col-md-12">
            <div id="collapse11" role="tabpanel" aria-labelledby="headingCollapse11" class="collapse " style="">
                <div class="row form-mar">
                    <div class="col-xs-4 col-sm-4 col-md-12">
                        <div class="form-group">
                            <label>Ticket Product:</label>
                            <input type="text" disabled="" class="form-control"
                                value=" {{ optional(optional($arrivalSamplingRequest->arrivalTicket)->product)->name }}"
                                placeholder="Ticket Product">
                        </div>
                    </div>
                    <div class="col-xs-4 col-sm-4 col-md-4">
                        <div class="form-group">
                            <label>Supplier:</label>
                            <input type="text" class="form-control" name="supplier"
                                value="{{ optional($arrivalSamplingRequest->arrivalTicket)->supplier_name }}" disabled>
                        </div>
                    </div>
                    <div class="col-xs-4 col-sm-4 col-md-4">
                        <div class="form-group">
                            <label>Broker:</label>
                            <input type="text" class="form-control" name="broker"
                                value="{{ optional($arrivalSamplingRequest->arrivalTicket)->broker_name }}" disabled>
                        </div>
                    </div>
                    <div class="col-xs-4 col-sm-4 col-md-4 full">
                        <div class="form-group">
                            <label class="d-block">Contract Detail:</label>
                            <select name="arrival_purchase_order_id" id="arrival_purchase_order_id"
                                class="form-control select2" disabled>
                                <option value="">N/A</option>
                                @foreach ($arrivalPurchaseOrders as $arrivalPurchaseOrder)
                                    <option data-saudatypeid="{{ $arrivalPurchaseOrder->sauda_type_id }}"
                                        data-brokerid="{{ $arrivalPurchaseOrder->broker->id ?? '' }}"
                                        data-brokername="{{ $arrivalPurchaseOrder->broker->name ?? '' }}"
                                        data-supplierid="{{ $arrivalPurchaseOrder->supplier->id ?? '' }}"
                                        data-suppliername="{{ $arrivalPurchaseOrder->supplier->name ?? '' }}"
                                        value="{{ $arrivalPurchaseOrder->id }}" @selected(optional($arrivalSamplingRequest->arrivalTicket)->arrival_purchase_order_id == $arrivalPurchaseOrder->id)>
                                        {{ $arrivalPurchaseOrder->unique_no }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-xs-4 col-sm-4 col-md-4 full">
                        <div class="form-group">
                            <label class="d-block">Sauda Type:</label>
                            <input type="hidden" name="sauda_type_id" id="actual_sauda_type_id"
                                value="{{ optional($arrivalSamplingRequest->arrivalTicket)->sauda_type_id ?? '' }}">
                            <select disabled name="sauda_type_id_display" id="sauda_type_id"
                                @disabled(optional($arrivalSamplingRequest->arrivalTicket)->sauda_type_id ??
                                        (null && optional($arrivalSamplingRequest->arrivalTicket)->arrival_purchase_order_id ?? null)) class="form-control w-100 select2">
                                <option value="">N/A</option>
                                @foreach ($saudaTypes as $saudaType)
                                    <option @selected(optional($arrivalSamplingRequest->arrivalTicket)->sauda_type_id == $saudaType->id) value="{{ $saudaType->id }}">
                                        {{ $saudaType->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="col-xs-4 col-sm-4 col-md-4">
                        <div class="form-group ">
                            <label class="d-block">Decisioned Of:</label>
                            <input type="text" name="accounts_of" disabled
                                value="{{ optional($arrivalSamplingRequest->arrivalTicket)->decisionBy->name ?? '' }}"
                                placeholder="Truck No" class="form-control" autocomplete="off" />
                        </div>
                    </div>

                    <div class="col-xs-4 col-sm-4 col-md-4">
                        <div class="form-group ">
                            <label class="d-block">Accounts Of:</label>
                            <input type="text" name="accounts_of" disabled
                                value="{{ optional($arrivalSamplingRequest->arrivalTicket)->accounts_of_id ?? '' }}"
                                placeholder="Truck No" class="form-control" autocomplete="off" />
                        </div>
                    </div>

                    <div class="col-xs-4 col-sm-4 col-md-4">
                        <div class="form-group ">
                            <label>Station:</label>
                            <input type="text" name="station_id" placeholder="Station" class="form-control" disabled
                                autocomplete="off"
                                value="{{ optional($arrivalSamplingRequest->arrivalTicket)->station->name ?? 'N/A' }}" />
                        </div>
                    </div>
                    <div class="col-xs-4 col-sm-4 col-md-4">
                        <div class="form-group ">
                            <label>Truck Type:</label>
                            <input type="text" name="arrival_truck_type_id" placeholder="Truck Type" disabled
                                class="form-control" autocomplete="off"
                                value="{{ optional($arrivalSamplingRequest->arrivalTicket)->truckType->name ?? '' }}" />
                        </div>
                    </div>
                    <div class="col-xs-4 col-sm-4 col-md-4">
                        <div class="form-group ">
                            <label>Truck No:</label>
                            <input type="text" name="truck_no"
                                value="{{ optional($arrivalSamplingRequest->arrivalTicket)->truck_no }}" disabled
                                placeholder="Truck No" class="form-control" autocomplete="off" />
                        </div>
                    </div>
                    <div class="col-xs-4 col-sm-4 col-md-4">
                        <div class="form-group ">
                            <label>Bilty No: </label>
                            <input type="text" name="bilty_no" disabled
                                value="{{ optional($arrivalSamplingRequest->arrivalTicket)->bilty_no }}"
                                placeholder="Bilty No" class="form-control" autocomplete="off" />
                        </div>
                    </div>
                    <div class="col-xs-4 col-sm-4 col-md-4">
                        <div class="form-group ">
                            <label>No of bags: </label>
                            <input type="text" name="bags" placeholder="No of bags" class="form-control"
                                disabled autocomplete="off"
                                value="{{ optional($arrivalSamplingRequest->arrivalTicket)->bags }}" />
                        </div>
                    </div>
                    <div class="col-xs-4 col-sm-4 col-md-4">
                        <div class="form-group ">
                            <label>Sample Money Type: </label>
                            <select name="sample_money_type" class="form-control" disabled>
                                <option
                                    {{ optional($arrivalSamplingRequest->arrivalTicket)->sample_money_type == 'n/a' ? 'selected' : '' }}
                                    value="n/a">N/A</option>
                                <option
                                    {{ optional($arrivalSamplingRequest->arrivalTicket)->sample_money_type == 'single' ? 'selected' : '' }}
                                    value="single">Single</option>
                                <option
                                    {{ optional($arrivalSamplingRequest->arrivalTicket)->sample_money_type == 'double' ? 'selected' : '' }}
                                    value="double">Double</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-xs-4 col-sm-4 col-md-4">
                        <div class="form-group ">
                            <label>Sample Money: </label>
                            <input type="text" readonly name="sample_money"
                                value="{{ optional($arrivalSamplingRequest->arrivalTicket)->truckType->sample_money ?? 0 }}"
                                placeholder="No of bags" class="form-control" autocomplete="off" />
                        </div>
                    </div>
                    <div class="col-xs-4 col-sm-4 col-md-4">
                        <div class="form-group ">
                            <label>LOading Date: (Optional)</label>
                            <input type="date" name="loading_date" disabled
                                value="{{ optional($arrivalSamplingRequest->arrivalTicket)->loading_date }}"
                                placeholder="Bilty No" class="form-control" autocomplete="off" />
                        </div>
                    </div>

                    <div class="col-xs-12 col-sm-12 col-md-12">
                        <div class="form-group ">
                            <label>Ticket Remarks:</label>
                            <textarea name="remarks" row="2" class="form-control" disabled placeholder="Remarks">{{ optional($arrivalSamplingRequest->arrivalTicket)->remarks }}</textarea>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">

                <div class="col-xs-12 col-sm-12 col-md-12">
                    <div class="form-group">
                        <label>QC Product:</label>
                        <input type="text" disabled="" class="form-control"
                            value="{{ $arrivalSamplingRequest->arrivalProduct->name ?? '' }}"
                            placeholder="QC Product">
                    </div>
                </div>
                <div class="col-xs-12 col-sm-12 col-md-12">
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
            @if ($initialRequestForInnerReq)
                <li class="nav-item">
                    <a class="nav-link" id="initial-tab" data-toggle="tab" href="#initial" role="tab"
                        aria-controls="initial" aria-selected="false">Initial Checklist</a>
                </li>
            @endif

            @foreach ($innerRequestsData as $index => $innerData)
                <li class="nav-item">
                    <a class="nav-link" id="inner-{{ $index }}-tab" data-toggle="tab"
                        href="#inner-{{ $index }}" role="tab" aria-controls="inner-{{ $index }}"
                        aria-selected="false">
                        Inner #{{ $loop->iteration }} ({{ $innerData['request']->created_at->format('M d, Y') }})
                    </a>
                </li>
            @endforeach

            <li class="nav-item">
                <a class="nav-link active" id="current-inner-tab" data-toggle="tab" href="#current-inner"
                    role="tab" aria-controls="current-inner" aria-selected="true">
                    {{ ucwords(($arrivalSamplingRequest->sampling_type == 'inner' ? 'Current ' : '') . $arrivalSamplingRequest->sampling_type) }}
                    Checklist
                </a>
            </li>
        </ul>

        <div class="tab-content" id="qcChecklistTabsContent">
            @if ($initialRequestForInnerReq && $initialRequestResults && $initialRequestCompulsuryResults)
                <div class="tab-pane fade" id="initial" role="tabpanel" aria-labelledby="initial-tab">
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
                        @if (count($initialRequestResults) != 0)
                            @foreach ($initialRequestResults as $slab)
                                <?php
                                $getDeductionSuggestion = getDeductionSuggestion($slab->slabType->id, optional($arrivalSamplingRequest->arrivalTicket)->qc_product, $slab->checklist_value);
                                $deductionValue = $isLumpSumEnabledForInitial ? 0 : $slab->applied_deduction ?? 0;
                                $valuesOfInitialSlabs[$slab->slabType->id] = $deductionValue;
                                $suggestedDeductionType = $getDeductionSuggestion->deduction_type ?? 'amount';
                                $suggestedValueForInner += $getDeductionSuggestion->deduction_value ?? 0;
                                ?>
                                <div class="form-group row">
                                    <input type="hidden" name="initial_product_slab_type_id[]"
                                        value="{{ $slab->slabType->id }}">
                                    <label class="col-md-4 label-control font-weight-bold"
                                        for="striped-form-1">{{ $slab->slabType->name }}</label>
                                    <div class="col-md-3 QcResult">
                                        <div class="input-group mb-0">
                                            <input type="text" id="striped-form-1" readonly class="form-control"
                                                name="initial_checklist_value[]" value="{{ $slab->checklist_value }}"
                                                placeholder="%" disabled>
                                            <div class="input-group-append">
                                                <span class="input-group-text text-sm">%</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3 Suggested">
                                        <div class="input-group mb-0">
                                            <input type="text" id="striped-form-1" readonly class="form-control"
                                                name="initial_suggested_value[]"
                                                value="{{ $getDeductionSuggestion->deduction_value ?? 0 }}"
                                                placeholder="Suggested Deduction" disabled>
                                            <div class="input-group-append">
                                                <span
                                                    class="input-group-text text-sm">{{ $suggestedDeductionType == 'amount' ? 'Rs.' : 'KG\'s' }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-2 QcResult">
                                        <div class="input-group mb-0">
                                            <input type="text" id="deduction-{{ $slab->slabType->id }}"
                                                class="form-control bg-white" name="initial_applied_deduction[]"
                                                value="{{ $deductionValue }}" placeholder="Deduction"
                                                data-calculated-on="{{ $slab->slabType->calculation_base_type }}"
                                                data-slab-id="{{ $slab->slabType->id }}" disabled>
                                            <div class="input-group-append">
                                                <span
                                                    class="input-group-text text-sm">{{ SLAB_TYPES_CALCULATED_ON[$slab->slabType->calculation_base_type ?? 1] }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="alert alert-warning">
                                No Initial Slabs Found
                            </div>
                        @endif
                    </div>
                    <br>
                    <div class="row w-100 mx-auto">
                        <div class="col-md-4"></div>
                        <div class="col-md-6 py-2 QcResult">
                            <h6>Result</h6>
                        </div>
                        <div class="col-md-2 py-2 QcResult">
                            <h6>Deduction</h6>
                        </div>
                    </div>
                    <div class="striped-rows">
                        @if (count($initialRequestCompulsuryResults) != 0)
                            @foreach ($initialRequestCompulsuryResults as $slab)
                                <div class="form-group row">
                                    <input type="hidden" name="initial_compulsory_param_id[]"
                                        value="{{ $slab->qcParam->id }}">
                                    <label class="label-control font-weight-bold col-md-4"
                                        for="striped-form-1">{{ $slab->qcParam->name }}</label>
                                    <div
                                        class="QcResult {{ checkIfNameExists($slab->qcParam->name) ? 'col-md-8' : 'col-md-6' }}">
                                        @if ($slab->qcParam->type == 'dropdown')
                                            <input type="text" id="striped-form-1" readonly class="form-control"
                                                name="initial_compulsory_checklist_value[]"
                                                value="{{ $slab->compulsory_checklist_value }}" placeholder="%"
                                                disabled>
                                        @else
                                            <textarea type="text" id="striped-form-1" readonly class="form-control"
                                                name="initial_compulsory_checklist_value[]" placeholder="%" disabled>{{ $slab->compulsory_checklist_value }}</textarea>
                                        @endif
                                    </div>
                                    @if (!checkIfNameExists($slab->qcParam->name))
                                        <div class="col-md-2 QcResult">
                                            <input type="text" id="striped-form-1" class="form-control bg-white"
                                                placehold name="initial_compulsory_aapplied_deduction[]"
                                                value="{{ $slab->applied_deduction ?? 0 }}" placeholder="Deduction"
                                                disabled>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        @else
                            <div class="alert alert-warning">
                                No Initial Compulsory Slabs Found
                            </div>
                        @endif
                        <div class="form-group row">
                            <label class="col-md-4 label-control font-weight-bold" for="lumpsum-toggle-initial">Apply
                                Lumpsum
                                Deduction</label>
                            <div class="col-md-3">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" name="is_lumpsum_deduction_initial"
                                        class="custom-control-input" id="lumpsum-toggle-initial"
                                        @checked($isLumpSumEnabledForInitial) disabled>
                                    <label class="custom-control-label" for="lumpsum-toggle-initial"></label>
                                </div>
                            </div>
                            <div class="col">
                                <div class="input-group mb-0">
                                    <input type="text" id="suggessions-sum-initial" class="form-control"
                                        name="suggessions_sum_initial" disabled
                                        value="{{ $suggestedValueForInner ?? 0 }}" placeholder="Suggested Sum">
                                    <div class="input-group-append">
                                        <span class="input-group-text text-sm">Rs.</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col">
                                <div class="input-group mb-0">
                                    <input type="text" id="lumpsum-value-initial" class="form-control"
                                        name="lumpsum_deduction_initial" disabled
                                        value="{{ $initialRequestForInnerReq->lumpsum_deduction ?? 0 }}"
                                        placeholder="Lumpsum Deduction">
                                    <div class="input-group-append">
                                        <span class="input-group-text text-sm">Rs.</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-md-4 label-control font-weight-bold"
                                for="lumpsum-kgs-value-initial">Lumpsum
                                Deduction</label>
                            <div class="col-md-8">
                                <div class="input-group mb-0">
                                    <input type="text" id="lumpsum-kgs-value-initial" class="form-control"
                                        name="lumpsum_deduction_kgs_initial" readonly
                                        value="{{ $initialRequestForInnerReq->lumpsum_deduction_kgs ?? 0 }}"
                                        placeholder="Lumpsum Deduction">
                                    <div class="input-group-append">
                                        <span class="input-group-text text-sm">KG's</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-md-4 label-control font-weight-bold"
                                for="decision_making_initial">Decision
                                Making on Avg.</label>
                            <div class="col-md-3">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" name="decision_making_initial"
                                        class="custom-control-input" id="decision_making_initial"
                                        @checked($isDecisionMakingForInitial) disabled>
                                    <label class="custom-control-label" for="decision_making_initial"></label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

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
                                <div class="form-group row">
                                    <label
                                        class="col-md-4 label-control font-weight-bold">{{ $slab->slabType->name }}</label>
                                    <div class="col-md-3 QcResult">
                                        <div class="input-group mb-0">
                                            <input type="text" readonly class="form-control"
                                                value="{{ $slab->checklist_value }}">
                                            <div class="input-group-append">
                                                <span class="input-group-text text-sm">%</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3 Suggested">
                                        <div class="input-group mb-0">
                                            <input type="text" readonly class="form-control"
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
                                {{-- <div class="form-group row">
                                    <label
                                        class="label-control font-weight-bold col-md-4">{{ $slab->qcParam->name }}</label>
                                    <div class="col-md-6 QcResult">
                                        <input type="text" readonly class="form-control"
                                            value="{{ $slab->compulsory_checklist_value }}">
                                    </div>
                                    <div class="col-md-2 QcResult">
                                        <input type="text" readonly class="form-control"
                                            value="{{ $slab->applied_deduction ?? 0 }}">
                                    </div>
                                </div> --}}
                                <div class="form-group row">
                                    <input type="hidden" readonly value="{{ $slab->qcParam->id }}">
                                    <label
                                        class="label-control font-weight-bold col-md-4">{{ $slab->qcParam->name }}</label>
                                    <div
                                        class="QcResult {{ checkIfNameExists($slab->qcParam->name) ? 'col-md-8' : 'col-md-6' }}">
                                        @if ($slab->qcParam->type == 'dropdown')
                                            <input type="text" class="form-control"
                                                value="{{ $slab->compulsory_checklist_value }}" readonly>
                                        @else
                                            <textarea class="form-control" readonly>{{ $slab->compulsory_checklist_value }}</textarea>
                                        @endif
                                    </div>
                                    @if (!checkIfNameExists($slab->qcParam->name))
                                        <div class="col-md-2 QcResult">
                                            <div class="input-group mb-0">
                                                <input type="text" id="inp-{{ $slab->qcParam->id }}"
                                                    class="form-control bg-white deduction-field" readonly
                                                    value="{{ $slab->applied_deduction }}" placeholder="Deduction"
                                                    data-slab-id="{{ $slab->qcParam->id }}"
                                                    data-calculated-on="{{ $slab->qcParam->calculation_base_type }}"
                                                    data-checklist="{{ $slab->compulsory_checklist_value }}">
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
                            <label class="col-md-4 label-control font-weight-bold">Apply Lumpsum Deduction</label>
                            <div class="col-md-3">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" disabled
                                        @checked($innerData['request']->is_lumpsum_deduction == 1)>
                                    <label class="custom-control-label"></label>
                                </div>
                            </div>
                            <div class="col">
                                <div class="input-group mb-0">
                                    <input type="text" class="form-control" readonly
                                        value="{{ $innerData['request']->lumpsum_deduction ?? 0 }}">
                                    <div class="input-group-append">
                                        <span class="input-group-text text-sm">Rs.</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label class="col-md-4 label-control font-weight-bold"> Deduction (KG's)</label>
                            <div class="col-md-8">
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
                                    optional($arrivalSamplingRequest->arrivalTicket)->qc_product,
                                    $displayValue,
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
                                $innerDeductionValue = $isLumpSumEnabled
                                    ? 0
                                    : $previousDeduction ??
                                        ($slab->applied_deduction ?? ($valuesOfInitialSlabs[$slab->slabType->id] ?? 0));

                                $suggestedDeductionType = $getDeductionSuggestion->deduction_type ?? 'amount';
                                $suggestedValue += $getDeductionSuggestion->deduction_value ?? 0;
                            @endphp

                            <div class="form-group row">
                                <input type="hidden" name="product_slab_type_id[]"
                                    value="{{ $slab->slabType->id }}">
                                <label
                                    class="col-md-4 label-control font-weight-bold">{{ $slab->slabType->name }}</label>
                                <div class="col-md-3 QcResult">
                                    <div class="input-group mb-0">
                                        <input type="text" class="form-control" name="checklist_value[]"
                                            value="{{ $displayValue }}" placeholder="%" readonly>
                                        <div class="input-group-append">
                                            <span class="input-group-text text-sm">%</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 Suggested">
                                    <div class="input-group mb-0">
                                        <input type="text" class="form-control"
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
                                            data-calculated-on="{{ $slab->slabType->calculation_base_type }}"
                                            data-slab-id="{{ $slab->slabType->id }}"
                                            data-product-id="{{ optional($arrivalSamplingRequest->arrivalTicket)->product->id }}"
                                            data-checklist="{{ $displayValue }}"
                                            {{ $isLumpSumEnabled ? 'readonly' : '' }}>
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
                                // if (
                                //     ($slab->compulsory_checklist_value === null ||
                                //         $slab->compulsory_checklist_value == '') &&
                                //     $previousInnerRequest
                                // ) {
                                //     foreach ($previousInnerRequest['compulsuryResults'] as $prevComp) {
                                //         if ($prevComp->qcParam->id == $slab->qcParam->id) {
                                //             $previousCompValue = $prevComp->compulsory_checklist_value;
                                //             break;
                                //         }
                                //     }
                                // }
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
                            @endphp

                            <div class="form-group row">
                                <input type="hidden" name="compulsory_param_id[]" value="{{ $slab->qcParam->id }}">
                                <label
                                    class="label-control font-weight-bold col-md-4">{{ $slab->qcParam->name }}</label>
                                <div
                                    class="QcResult {{ checkIfNameExists($slab->qcParam->name) ? 'col-md-8' : 'col-md-6' }}">
                                    @if ($slab->qcParam->type == 'dropdown')
                                        <input type="text" class="form-control"
                                            name="compulsory_checklist_value[]" value="{{ $displayCompValue }}"
                                            readonly>
                                    @else
                                        <textarea class="form-control" name="compulsory_checklist_value[]" readonly>{{ $displayCompValue }}</textarea>
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
                                                data-checklist="{{ $displayCompValue }}">
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
                                <input type="checkbox" name="is_lumpsum_deduction" class="custom-control-input"
                                    id="lumpsum-toggle" @checked($isLumpSumEnabled)>
                                <label class="custom-control-label" for="lumpsum-toggle"></label>
                            </div>
                        </div>
                        <div class="col">
                            <div class="input-group mb-0">
                                <input type="text" id="suggessions-sum" class="form-control"
                                    name="suggessions_sum" disabled value="{{ $suggestedValue }}"
                                    placeholder="Suggested Sum">
                                <div class="input-group-append">
                                    <span class="input-group-text text-sm">Rs.</span>
                                </div>
                            </div>
                        </div>
                        <div class="col">
                            <div class="input-group mb-0">
                                <input type="text" id="lumpsum-value" class="form-control"
                                    name="lumpsum_deduction" {{ $isLumpSumEnabled ? '' : 'readonly' }}
                                    value="{{ $arrivalSamplingRequest->lumpsum_deduction ?? 0 }}"
                                    placeholder="Lumpsum Deduction">
                                <div class="input-group-append">
                                    <span class="input-group-text text-sm">Rs.</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-md-4 label-control font-weight-bold" for="lumpsum-kgs-value">
                            Deduction (KG's)</label>
                        <div class="col-md-8">
                            <div class="input-group mb-0">
                                <input type="text" id="lumpsum-kgs-value" class="form-control"
                                    name="lumpsum_deduction_kgs" readonly
                                    value="{{ $arrivalSamplingRequest->lumpsum_deduction_kgs ?? 0 }}"
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
                                    id="decision_making" @checked($isDecisionMaking)>
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
    </div>

    <div class="row">
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group ">
                <label>Your Remarks (Optional):</label>
                <textarea name="remarks" row="4" class="form-control" placeholder="Description">{{ $arrivalSamplingRequest->remark }}</textarea>
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
                let matchingSlabs = $(this).data('matching-slabs');
                let calculatedOn = $(this).data('calculated-on');
                let slabId = $(this).data('slab-id');
                let val = parseFloat($(this).val()) || 0;

                if (calculatedOn == {{ SLAB_TYPE_PERCENTAGE }}) {
                    let deductionValue = 0;

                    if (matchingSlabs && matchingSlabs.length > 0) {
                        matchingSlabs.sort((a, b) => parseFloat(a.from) - parseFloat(b.from));

                        for (let slab of matchingSlabs) {
                            let from = parseFloat(slab.from);
                            let to = parseFloat(slab.to);
                            let isTiered = parseInt(slab.is_tiered);
                            let deductionVal = parseFloat(slab.deduction_value);

                            if (val >= from) {
                                // if (val >= from && (isNaN(to) || val <= to)) {
                                if (isTiered === 1) {
                                    let applicableAmount = 0;
                                    if (isNaN(to) || val >= to) {
                                        applicableAmount = to - from;
                                    } else {
                                        applicableAmount = val - from;
                                    }
                                    deductionValue += deductionVal * applicableAmount;
                                } else {
                                    deductionValue += deductionVal;
                                }
                            }
                        }
                    }

                    total += deductionValue;
                } else if (calculatedOn == {{ SLAB_TYPE_KG }}) {
                    totalKgs += (val) || 0;
                } else {
                    total += (val) || 0;
                }
            });

            $('#lumpsum-value').val(total.toFixed(2));
            $('#lumpsum-kgs-value').val(totalKgs.toFixed(2));
        }

        calculateTotal();

        if ({{ $arrivalSamplingRequest->is_lumpsum_deduction == 1 ? 'true' : 'false' }}) {
            $('#lumpsum-value').val({{ $arrivalSamplingRequest->lumpsum_deduction ?? 0 }}.toFixed(2));
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
                        calculateTotal();
                    } else {
                        $(this).prop('checked', false);
                    }
                });
            } else {
                $('.deduction-field').prop('readonly', false);
                $('#lumpsum-value').prop('readonly', true).val('0');
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
