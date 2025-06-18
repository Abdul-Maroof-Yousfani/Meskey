@extends('management.layouts.master')
@section('title')
    Ticket
@endsection
@section('content')
    @php
        // dd($results);
        $isLumpSumEnabled = $arrivalSamplingRequest->is_lumpsum_deduction == 1 ? true : false;
        $isLumpSumEnabledInTicket = $arrivalSamplingRequest->arrivalTicket->is_lumpsum_deduction == 1 ? true : false;
        $rupeeLumpSum = $arrivalSamplingRequest->arrivalTicket->lumpsum_deduction ?? 0;
        $kgLumpSum = $arrivalSamplingRequest->arrivalTicket->lumpsum_deduction_kgs ?? 0;

        $isDecisionMaking =
            isset($arrivalSamplingRequest) && $arrivalSamplingRequest->arrivalTicket->decision_making == 1
                ? true
                : false;

        $isDecisionMakingDisabled =
            isset($arrivalSamplingRequest) &&
            $arrivalSamplingRequest->arrivalTicket->decision_making == 0 &&
            $arrivalSamplingRequest->arrivalTicket->decision_making_time
                ? true
                : false;
        $valuesOfInitialSlabs = [];
        $suggestedValueForInner = 0;
        $suggestedValue = 0;
        $suggestedValueForInnerKgs = 0;
        $suggestedValueKgs = 0;

        $previousInnerRequest = $innerRequestsData[0] ?? null;
        $lastInnerRequest = !empty($innerRequestsData) ? $innerRequestsData[count($innerRequestsData) - 1] : null;
    @endphp
    <div class="content-wrapper">
        <section id="extended">
            <div class="row w-100 mx-auto">
                <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6">
                    <h2 class="page-title"> Arrival Summary: {{ $arrivalTicket->unique_no }}</h2>
                </div>
                <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6 text-right">
                    <button onclick="openModal(this,'{{ route('ticket.create') }}','Add Ticket')" type="button"
                        class="btn btn-primary position-relative ">
                        Create Ticket
                    </button>
                </div>
            </div>
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                        </div>
                        <div class="card-content">
                            <div class="card-body ">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="row">
                                            <div class="col-12">
                                                <h6 class="header-heading-sepration">
                                                    Ticket Detail
                                                </h6>
                                            </div>
                                            <div class="col-xs-6 col-sm-6 col-md-6">
                                                <fieldset>
                                                    <div class="input-group">
                                                        <div class="input-group-prepend">
                                                            <button class="btn btn-primary" type="button">Ticket
                                                                No#</button>
                                                        </div>
                                                        <input type="text" disabled class="form-control"
                                                            value="{{ $arrivalTicket->unique_no }}"
                                                            placeholder="Button on left">
                                                    </div>
                                                </fieldset>
                                            </div>
                                            <div class="col-xs-12 col-sm-12 col-md-12">
                                                <div class="form-group ">
                                                    <label>Product:</label>
                                                    <select name="product_id" id="product_id" class="form-control select2 "
                                                        disabled>
                                                        <option value="{{ $arrivalTicket->product->id }}">
                                                            {{ $arrivalTicket->product->name }}
                                                        </option>
                                                        <option value="">Product Name</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-xs-4 col-sm-4 col-md-4">
                                                <div class="form-group ">
                                                    <label>Supplier:</label>
                                                    <select name="supplier_name" id="supplier_name"
                                                        class="form-control select2 " disabled>
                                                        <option value="{{ $arrivalTicket->supplier_name }}">
                                                            {{ $arrivalTicket->supplier_name }}
                                                        </option>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="col-xs-4 col-sm-4 col-md-4">
                                                <div class="form-group ">
                                                    <label>Broker:</label>
                                                    <select name="broker_name" id="broker_name"
                                                        class="form-control select2 " disabled>
                                                        <option value="{{ $arrivalTicket->broker_name }}">
                                                            {{ $arrivalTicket->broker_name }}
                                                        </option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-xs-4 col-sm-4 col-md-4">
                                                <div class="form-group ">
                                                    <label>Accounts Of:</label>
                                                    <select name="accounts_of" id="accounts_of"
                                                        class="form-control select2 " disabled>
                                                        <option value="" hidden>Accounts Of</option>
                                                        @foreach ($accountsOf as $account)
                                                            <option value="{{ $account->id }}"
                                                                @selected($arrivalTicket->accounts_of_id == $account->id)>
                                                                {{ $account->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-xs-4 col-sm-4 col-md-4">
                                                <div class="form-group ">
                                                    <label>Station:</label>
                                                    <input type="text" name="station_id" placeholder="Station"
                                                        class="form-control" disabled autocomplete="off"
                                                        value="{{ $arrivalTicket->station_name ?? 'N/A' }}" />
                                                </div>
                                            </div>
                                            <div class="col-xs-4 col-sm-4 col-md-4">
                                                <div class="form-group ">
                                                    <label>Truck Type:</label>
                                                    <select name="arrival_truck_type_id" id="arrival_truck_type_id" disabled
                                                        class="form-control select2">
                                                        <option value="">Truck Type</option>

                                                        @foreach (getTableData('arrival_truck_types', ['id', 'name', 'sample_money']) as $arrival_truck_types)
                                                            <option
                                                                data-samplemoney="{{ $arrival_truck_types->sample_money ?? 0 }}"
                                                                @selected($arrivalTicket->truck_type_id == $arrival_truck_types->id)
                                                                value="{{ $arrival_truck_types->id }}">
                                                                {{ $arrival_truck_types->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-xs-4 col-sm-4 col-md-4">
                                                <div class="form-group ">
                                                    <label>Sample Money Type :</label>
                                                    <select name="sample_money_type" class="form-control" disabled>
                                                        <option value="">Select Type</option>
                                                        <option
                                                            {{ $arrivalTicket->sample_money_type == 'single' ? 'selected' : '' }}
                                                            value="single">Single</option>
                                                        <option
                                                            {{ $arrivalTicket->sample_money_type == 'double' ? 'selected' : '' }}
                                                            value="double">Double</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="col-xs-4 col-sm-4 col-md-4">
                                                <div class="form-group ">
                                                    <label>Truck No:</label>
                                                    <input type="text" name="truck_no"
                                                        value="{{ $arrivalTicket->truck_no }}" disabled
                                                        placeholder="Truck No" class="form-control" autocomplete="off" />
                                                </div>
                                            </div>
                                            <div class="col-xs-4 col-sm-4 col-md-4">
                                                <div class="form-group ">
                                                    <label>Bilty No: </label>
                                                    <input type="text" name="bilty_no"
                                                        value="{{ $arrivalTicket->bilty_no }}" disabled
                                                        placeholder="Bilty No" class="form-control" autocomplete="off" />
                                                </div>
                                            </div>
                                            <div class="col-xs-4 col-sm-4 col-md-4">
                                                <div class="form-group ">
                                                    <label>No of bags: </label>
                                                    <input type="text" name="bags" placeholder="No of bags"
                                                        class="form-control" disabled autocomplete="off"
                                                        value="{{ $arrivalTicket->bags }}" />
                                                </div>
                                            </div>
                                            <div class="col-xs-4 col-sm-4 col-md-4">
                                                <div class="form-group ">
                                                    <label>Sample Money: </label>
                                                    <input type="text" readonly name="sample_money" disabled
                                                        value="{{ $arrivalTicket->sample_money ?? 0 }}"
                                                        placeholder="No of bags" class="form-control"
                                                        autocomplete="off" />
                                                </div>
                                            </div>
                                            <div class="col-xs-4 col-sm-4 col-md-4">
                                                <div class="form-group ">
                                                    <label>Loading Date: (Optional)</label>
                                                    <input type="date" name="loading_date" disabled
                                                        value="{{ $arrivalTicket->loading_date }}" placeholder="Bilty No"
                                                        class="form-control" autocomplete="off" />
                                                </div>
                                            </div>
                                            <div class="col-xs-12 col-sm-12 col-md-12">
                                                <div class="form-group ">
                                                    <label>Remarks (Optional):</label>
                                                    <textarea name="remarks" row="2" disabled class="form-control" placeholder="Remarks">{{ $arrivalTicket->remarks }}</textarea>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-12">
                                                <h6 class="header-heading-sepration">
                                                    Weight Detail
                                                </h6>
                                            </div>
                                            <div class="col-xs-4 col-sm-4 col-md-4">
                                                <div class="form-group ">
                                                    <label>1st Weight:</label>
                                                    <input type="text" name="first_weight" disabled
                                                        placeholder="First Weight" class="form-control"
                                                        autocomplete="off" value="{{ $arrivalTicket->first_weight }}" />
                                                </div>
                                            </div>
                                            <div class="col-xs-4 col-sm-4 col-md-4">
                                                <div class="form-group ">
                                                    <label>Second Weight: </label>
                                                    <input type="text" name="second_weight" disabled
                                                        placeholder="Second Weight" class="form-control"
                                                        autocomplete="off" value="{{ $arrivalTicket->second_weight }}" />
                                                </div>
                                            </div>
                                            <div class="col-xs-4 col-sm-4 col-md-4">
                                                <div class="form-group ">
                                                    <label>Net Weight: </label>
                                                    <input type="text" name="net_weight" disabled
                                                        placeholder="Net Weight" class="form-control" autocomplete="off"
                                                        value="{{ $arrivalTicket->net_weight }}" />
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="row">
                                            <div class="col-12">
                                                <h6 class="header-heading-sepration">
                                                    QC Checklist
                                                </h6>
                                            </div>
                                        </div>

                                        <ul class="nav nav-tabs" id="qcChecklistTabs" role="tablist">
                                            @if (!empty($initialRequestsData))
                                                @foreach ($initialRequestsData as $index => $initialData)
                                                    @php
                                                        $statusLabel = '';
                                                        switch ($initialData['request']->is_re_sampling) {
                                                            case 'yes':
                                                                $statusLabel = 'Initial Resampling';
                                                                break;
                                                            case 'no':
                                                                $statusLabel = 'Initial QC';
                                                                break;
                                                            default:
                                                                $statusLabel = 'Initial QC';
                                                        }
                                                    @endphp
                                                    <li class="nav-item">
                                                        <a class="nav-link" id="initial-{{ $index }}-tab"
                                                            data-toggle="tab" href="#initial-{{ $index }}"
                                                            role="tab" aria-controls="initial-{{ $index }}"
                                                            aria-selected="false">
                                                            <div>{{ $statusLabel }}</div>
                                                            <small
                                                                class="text-muted">{{ $initialData['request']->created_at->format('M d, Y h:i A') }}</small>
                                                        </a>
                                                    </li>
                                                @endforeach
                                            @endif

                                            @foreach ($innerRequestsData as $index => $innerData)
                                                @php
                                                    $statusLabel = '';
                                                    switch ($innerData['request']->approved_status) {
                                                        case 'resampling':
                                                            if ($innerData['request']->is_re_sampling == 'yes') {
                                                                $statusLabel = 'Inner Resampling QC';
                                                            } else {
                                                                $statusLabel = 'Inner QC';
                                                            }
                                                            break;
                                                        case 'rejected':
                                                            $statusLabel = 'Inner Rejected QC';
                                                            break;
                                                        case 'approved':
                                                            $statusLabel = 'Inner Approved QC';
                                                            break;
                                                        default:
                                                            $statusLabel = 'Inner QC';
                                                    }
                                                @endphp
                                                <li class="nav-item">
                                                    <a class="nav-link" id="inner-{{ $index }}-tab"
                                                        data-toggle="tab" href="#inner-{{ $index }}"
                                                        role="tab" aria-controls="inner-{{ $index }}"
                                                        aria-selected="false">
                                                        <div>{{ $statusLabel }}</div>
                                                        <small
                                                            class="text-muted">{{ $innerData['request']->created_at->format('M d, Y h:i A') }}</small>
                                                    </a>
                                                </li>
                                            @endforeach

                                            @php
                                                $currentTabLabel = '';
                                                switch ($arrivalSamplingRequest->approved_status) {
                                                    case 'resampling':
                                                        $currentTabLabel = 'Current Resampling QC';
                                                        break;
                                                    case 'rejected':
                                                        $currentTabLabel = 'Current Rejected QC';
                                                        break;
                                                    case 'approved':
                                                        $currentTabLabel = 'Current Approved QC';
                                                        break;
                                                    default:
                                                        $currentTabLabel = ucwords(
                                                            ($arrivalSamplingRequest->sampling_type == 'inner'
                                                                ? 'Current '
                                                                : '') . $arrivalSamplingRequest->sampling_type,
                                                        );
                                                }
                                            @endphp

                                            <li class="nav-item">
                                                <a class="nav-link active" id="current-inner-tab" data-toggle="tab"
                                                    href="#current-inner" role="tab" aria-controls="current-inner"
                                                    aria-selected="true">
                                                    <div>
                                                        {{ $arrivalSamplingRequest->is_re_sampling == 'yes' ? 'Current ' . ucwords($arrivalSamplingRequest->sampling_type) . ' Resampling' : 'Current ' . $arrivalSamplingRequest->sampling_type . ' Checklist' }}
                                                    </div>
                                                    <small
                                                        class="text-muted">{{ $arrivalSamplingRequest->created_at->format('M d, Y h:i A') }}</small>
                                                </a>
                                            </li>
                                        </ul>

                                        <div class="tab-content" id="qcChecklistTabsContent">
                                            @if (!empty($initialRequestsData))
                                                @foreach ($initialRequestsData as $index => $initialData)
                                                    @php
                                                        $suggestedInitialValue = 0;
                                                        $suggestedInitialKgs = 0;
                                                    @endphp
                                                    <div class="tab-pane fade" id="initial-{{ $index }}"
                                                        role="tabpanel"
                                                        aria-labelledby="initial-{{ $index }}-tab">
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
                                                            @if (count($initialData['results']) != 0)
                                                                @foreach ($initialData['results'] as $slab)
                                                                    @php
                                                                        $previousChecklistValue = null;

                                                                        $getDeductionSuggestion = getDeductionSuggestion(
                                                                            $slab->slabType->id,
                                                                            optional(
                                                                                $arrivalSamplingRequest->arrivalTicket,
                                                                            )->qc_product,
                                                                            $slab->checklist_value,
                                                                        );
                                                                        $deductionValue =
                                                                            $initialData['request']
                                                                                ->is_lumpsum_deduction == 1
                                                                                ? 0
                                                                                : $slab->applied_deduction ?? 0;
                                                                        $suggestedDeductionType =
                                                                            $getDeductionSuggestion->deduction_type ??
                                                                            'amount';

                                                                        $suggestedDeductionType == 'amount'
                                                                            ? ($suggestedInitialValue +=
                                                                                $getDeductionSuggestion->deduction_value ??
                                                                                0)
                                                                            : ($suggestedInitialKgs +=
                                                                                $getDeductionSuggestion->deduction_value ??
                                                                                0);

                                                                        if ($index > 0) {
                                                                            foreach (
                                                                                $initialRequestsData[$index - 1][
                                                                                    'results'
                                                                                ]
                                                                                as $prevSlab
                                                                            ) {
                                                                                if (
                                                                                    $prevSlab->slabType->id ==
                                                                                    $slab->slabType->id
                                                                                ) {
                                                                                    $previousChecklistValue =
                                                                                        $prevSlab->checklist_value;
                                                                                    break;
                                                                                }
                                                                            }
                                                                        }

                                                                        $comparisonClass = '';
                                                                        if ($previousChecklistValue !== null) {
                                                                            if (
                                                                                $slab->checklist_value >
                                                                                $previousChecklistValue
                                                                            ) {
                                                                                $comparisonClass = 'checklist-increase';
                                                                            } elseif (
                                                                                $slab->checklist_value <
                                                                                $previousChecklistValue
                                                                            ) {
                                                                                $comparisonClass = 'checklist-decrease';
                                                                            } else {
                                                                                $comparisonClass = 'checklist-same';
                                                                            }
                                                                        }

                                                                        if (
                                                                            ((float) $slab->checklist_value ?? 0) >
                                                                            ((float) $slab->max_range ?? 0)
                                                                        ) {
                                                                            $comparisonClass = 'slabs-checklist-rise';
                                                                        }
                                                                    @endphp
                                                                    <div class="form-group row checklist-box">
                                                                        <label
                                                                            class="col-md-4 label-control font-weight-bold">{{ $slab->slabType->name }}</label>
                                                                        <div class="col-md-3 QcResult">
                                                                            <div class="input-group mb-0">
                                                                                <input type="text" readonly
                                                                                    class="form-control {{ $comparisonClass }}"
                                                                                    value="{{ $slab->checklist_value }}"
                                                                                    placeholder="%" disabled>
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
                                                                                <input type="text" class="form-control"
                                                                                    value="{{ $getDeductionSuggestion->deduction_value ?? 0 }}"
                                                                                    placeholder="Suggested Deduction"
                                                                                    disabled>
                                                                                <div class="input-group-append">
                                                                                    <span
                                                                                        class="input-group-text text-sm">{{ $suggestedDeductionType == 'amount' ? 'Rs.' : 'KG\'s' }}</span>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-md-2 QcResult">
                                                                            <div class="input-group mb-0">
                                                                                <input type="text"
                                                                                    class="form-control bg-white"
                                                                                    value="{{ $deductionValue }}"
                                                                                    placeholder="Deduction" disabled>
                                                                                <div class="input-group-append">
                                                                                    <span
                                                                                        class="input-group-text text-sm">{{ SLAB_TYPES_CALCULATED_ON[$slab->slabType->calculation_base_type ?? 1] }}</span>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                @endforeach
                                                            @else
                                                                <div class="alert alert-warning">No Initial Slabs Found
                                                                </div>
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
                                                            @if (count($initialData['compulsuryResults']) != 0)
                                                                @foreach ($initialData['compulsuryResults'] as $slab)
                                                                    @php
                                                                        $defaultValue = '';
                                                                        $displayCompValue =
                                                                            $slab->compulsory_checklist_value;

                                                                        if ($slab->qcParam->type == 'dropdown') {
                                                                            $options = json_decode(
                                                                                $slab->qcParam->options,
                                                                                true,
                                                                            );
                                                                            $defaultValue = $options[0] ?? '';
                                                                        }
                                                                        $compulsaryClass = '';

                                                                        if ($displayCompValue != $defaultValue) {
                                                                            $compulsaryClass =
                                                                                'slabs-checklist-changed-compulsury';
                                                                        }
                                                                    @endphp
                                                                    <div class="form-group row ">
                                                                        <label
                                                                            class="label-control font-weight-bold col-md-4"
                                                                            data-default-value="{{ $defaultValue }}">{{ $slab->qcParam->name }}</label>
                                                                        <div
                                                                            class="QcResult {{ checkIfNameExists($slab->qcParam->name) ? 'col-md-8' : 'col-md-6' }}">
                                                                            @if ($slab->qcParam->type == 'dropdown')
                                                                                <input type="text" readonly
                                                                                    class="form-control {{ $compulsaryClass }}"
                                                                                    value="{{ $slab->compulsory_checklist_value }}"
                                                                                    disabled>
                                                                            @else
                                                                                <textarea readonly class="form-control {{ $compulsaryClass }}" disabled>{{ $slab->compulsory_checklist_value }}</textarea>
                                                                            @endif
                                                                        </div>
                                                                        @if (!checkIfNameExists($slab->qcParam->name))
                                                                            <div class="col-md-2 QcResult">
                                                                                <input type="text"
                                                                                    class="form-control bg-white"
                                                                                    value="{{ $slab->applied_deduction ?? 0 }}"
                                                                                    disabled>
                                                                            </div>
                                                                        @endif
                                                                    </div>
                                                                @endforeach
                                                            @else
                                                                <div class="alert alert-warning">No Initial Compulsory
                                                                    Slabs Found</div>
                                                            @endif

                                                            <div class="form-group row">
                                                                <label class="col-md-4 label-control font-weight-bold"
                                                                    for="lumpsum-toggle-initial-{{ $index }}">Apply
                                                                    Lumpsum
                                                                    Deduction</label>
                                                                <div class="col-md-3">
                                                                    <div class="custom-control custom-switch">
                                                                        <input type="checkbox"
                                                                            name="is_lumpsum_deduction_initial"
                                                                            class="custom-control-input"
                                                                            id="lumpsum-toggle-initial-{{ $index }}"
                                                                            @checked($initialData['request']->is_lumpsum_deduction == 1) disabled>
                                                                        <label class="custom-control-label"
                                                                            for="lumpsum-toggle-initial-{{ $index }}"></label>
                                                                    </div>
                                                                </div>
                                                                <div class="col {{ $index == 0 ? '' : 'd-none' }}">
                                                                    <div class="input-group mb-1">
                                                                        <input type="text"
                                                                            id="suggessions-sum-initial-{{ $index }}"
                                                                            class="form-control"
                                                                            name="suggessions_sum_initial" disabled
                                                                            value="{{ $suggestedInitialValue ?? 0 }}"
                                                                            placeholder="Suggested Sum">

                                                                        <div class="input-group-append">
                                                                            <span
                                                                                class="input-group-text text-sm">Rs.</span>
                                                                        </div>
                                                                    </div>
                                                                    <div class="input-group mb-0">
                                                                        <input type="text"
                                                                            id="suggessions-sum-initial-kgs-{{ $index }}"
                                                                            class="form-control"
                                                                            name="suggessions_sum_initial_kgs" disabled
                                                                            value="{{ $suggestedInitialKgs ?? 0 }}"
                                                                            placeholder="Suggested Sum">
                                                                        <div class="input-group-append">
                                                                            <span
                                                                                class="input-group-text text-sm">KG's</span>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="col">
                                                                    <div class="input-group mb-1">
                                                                        <input type="text"
                                                                            id="lumpsum-value-initial-{{ $index }}"
                                                                            class="form-control"
                                                                            name="lumpsum_deduction_initial" disabled
                                                                            value="{{ $initialData['request']->lumpsum_deduction ?? 0 }}"
                                                                            placeholder="Lumpsum Deduction">
                                                                        <div class="input-group-append">
                                                                            <span
                                                                                class="input-group-text text-sm">Rs.</span>
                                                                        </div>
                                                                    </div>
                                                                    <div class="input-group mb-0">
                                                                        <input type="text"
                                                                            id="lumpsum-kgs-value-initial-{{ $index }}"
                                                                            class="form-control"
                                                                            name="lumpsum_deduction_kgs_initial" readonly
                                                                            value="{{ $initialData['request']->lumpsum_deduction_kgs ?? 0 }}"
                                                                            placeholder="Lumpsum Deduction">
                                                                        <div class="input-group-append">
                                                                            <span
                                                                                class="input-group-text text-sm">KG's</span>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="form-group row">
                                                                <label class="col-md-4 label-control font-weight-bold"
                                                                    for="decision_making_initial_{{ $index }}">Decision
                                                                    Making on Avg.</label>
                                                                <div class="col-md-3">
                                                                    <div class="custom-control custom-switch">
                                                                        <input type="checkbox"
                                                                            name="decision_making_initial"
                                                                            class="custom-control-input"
                                                                            id="decision_making_initial_{{ $index }}"
                                                                            @checked($initialData['request']->decision_making == 1) disabled>
                                                                        <label class="custom-control-label"
                                                                            for="decision_making_initial_{{ $index }}"></label>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            @endif

                                            @foreach ($innerRequestsData as $index => $innerData)
                                                <div class="tab-pane fade" id="inner-{{ $index }}"
                                                    role="tabpanel" aria-labelledby="inner-{{ $index }}-tab">
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
                                                                        foreach (
                                                                            $innerRequestsData[$index - 1]['results']
                                                                            as $prevSlab
                                                                        ) {
                                                                            if (
                                                                                $prevSlab->slabType->id ==
                                                                                $slab->slabType->id
                                                                            ) {
                                                                                $previousChecklistValue =
                                                                                    $prevSlab->checklist_value;
                                                                                break;
                                                                            }
                                                                        }
                                                                    }

                                                                    if (
                                                                        $previousChecklistValue === null &&
                                                                        !empty($initialRequestsData)
                                                                    ) {
                                                                        $lastInitialData =
                                                                            $initialRequestsData[
                                                                                count($initialRequestsData) - 1
                                                                            ];
                                                                        foreach (
                                                                            $lastInitialData['results']
                                                                            as $initialSlab
                                                                        ) {
                                                                            if (
                                                                                $initialSlab->slabType->id ==
                                                                                $slab->slabType->id
                                                                            ) {
                                                                                $previousChecklistValue =
                                                                                    $initialSlab->checklist_value;
                                                                                break;
                                                                            }
                                                                        }
                                                                    }

                                                                    $comparisonClass = '';
                                                                    if ($previousChecklistValue !== null) {
                                                                        if (
                                                                            $slab->checklist_value >
                                                                            $previousChecklistValue
                                                                        ) {
                                                                            $comparisonClass = 'checklist-increase';
                                                                        } elseif (
                                                                            $slab->checklist_value <
                                                                            $previousChecklistValue
                                                                        ) {
                                                                            $comparisonClass = 'checklist-decrease';
                                                                        } else {
                                                                            $comparisonClass = 'checklist-same';
                                                                        }
                                                                    }

                                                                    if (
                                                                        ((float) $slab->checklist_value ?? 0) >
                                                                        ((float) $slab->max_range ?? 0)
                                                                    ) {
                                                                        $comparisonClass = 'slabs-checklist-rise';
                                                                    }
                                                                @endphp
                                                                <div class="form-group row checklist-box">
                                                                    <label
                                                                        class="col-md-4 label-control font-weight-bold">{{ $slab->slabType->name }}</label>
                                                                    <div class="col-md-3 QcResult">
                                                                        <div class="input-group mb-0">
                                                                            <input type="text" readonly
                                                                                class="form-control {{ $comparisonClass }}"
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
                                                                            <input type="text" disabled
                                                                                class="form-control"
                                                                                value="{{ $slab->suggested_deduction ?? 0 }}">
                                                                            <div class="input-group-append">
                                                                                <span
                                                                                    class="input-group-text text-sm">Rs.</span>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-md-2 QcResult">
                                                                        <div class="input-group mb-0">
                                                                            <input type="text" readonly
                                                                                class="form-control"
                                                                                value="{{ $slab->applied_deduction ?? 0 }}">
                                                                            <div class="input-group-append">
                                                                                <span
                                                                                    class="input-group-text text-sm">Rs.</span>
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
                                                                    $displayCompValue =
                                                                        $slab->compulsory_checklist_value;

                                                                    if ($slab->qcParam->type == 'dropdown') {
                                                                        $options = json_decode(
                                                                            $slab->qcParam->options,
                                                                            true,
                                                                        );
                                                                        $defaultValue = $options[0] ?? '';
                                                                    }

                                                                    $compulsaryClass = '';

                                                                    if ($displayCompValue != $defaultValue) {
                                                                        $compulsaryClass =
                                                                            'slabs-checklist-changed-compulsury';
                                                                    }
                                                                @endphp
                                                                <div class="form-group row">
                                                                    <label class="label-control font-weight-bold col-md-4"
                                                                        data-default-value="{{ $defaultValue }}">{{ $slab->qcParam->name }}</label>
                                                                    <div
                                                                        class="QcResult {{ checkIfNameExists($slab->qcParam->name) ? 'col-md-8' : 'col-md-6' }}">
                                                                        @if ($slab->qcParam->type == 'dropdown')
                                                                            <input type="text"
                                                                                class="form-control {{ $compulsaryClass }}"
                                                                                value="{{ $slab->compulsory_checklist_value }}"
                                                                                readonly>
                                                                        @else
                                                                            <textarea class="form-control {{ $compulsaryClass }}" readonly>{{ $slab->compulsory_checklist_value }}</textarea>
                                                                        @endif
                                                                    </div>
                                                                    @if (!checkIfNameExists($slab->qcParam->name))
                                                                        <div class="col-md-2 QcResult">
                                                                            <input type="text" class="form-control  "
                                                                                readonly
                                                                                value="{{ $slab->applied_deduction }}">
                                                                        </div>
                                                                    @endif
                                                                </div>
                                                            @endforeach
                                                        @else
                                                            <div class="alert alert-warning">No Compulsory Slabs Found
                                                            </div>
                                                        @endif
                                                    </div>

                                                    <div class="striped-rows mt-3">
                                                        <div class="form-group row">
                                                            <label class="col-md-4 label-control font-weight-bold">Apply
                                                                Lumpsum Deduction</label>
                                                            <div class="col-md-3">
                                                                <div class="custom-control custom-switch">
                                                                    <input type="checkbox" class="custom-control-input"
                                                                        disabled @checked($innerData['request']->is_lumpsum_deduction == 1)>
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
                                                            <label class="col-md-4 label-control font-weight-bold">Decision
                                                                Making on Avg.</label>
                                                            <div class="col-md-3">
                                                                <div class="custom-control custom-switch">
                                                                    <input type="checkbox" class="custom-control-input"
                                                                        disabled @checked($innerData['request']->decision_making == 1)>
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
                                                                    ($slab->checklist_value === null ||
                                                                        $slab->checklist_value == 0) &&
                                                                    $previousInnerRequest
                                                                ) {
                                                                    foreach (
                                                                        $previousInnerRequest['results']
                                                                        as $prevSlab
                                                                    ) {
                                                                        if (
                                                                            $prevSlab->slabType->id ==
                                                                            $slab->slabType->id
                                                                        ) {
                                                                            $previousValue = $prevSlab->checklist_value;
                                                                            break;
                                                                        }
                                                                    }
                                                                }

                                                                $displayValue =
                                                                    ($previousValue ?? ($slab->checklist_value ?? 0)) -
                                                                    ($slab->relief_deduction ?? 0);

                                                                $getDeductionSuggestion = getDeductionSuggestion(
                                                                    $slab->slabType->id,
                                                                    optional($arrivalSamplingRequest->arrivalTicket)
                                                                        ->qc_product,
                                                                    $displayValue,
                                                                );

                                                                $previousDeduction = null;
                                                                if (
                                                                    ($slab->applied_deduction === null ||
                                                                        $slab->applied_deduction == 0) &&
                                                                    $previousInnerRequest
                                                                ) {
                                                                    foreach (
                                                                        $previousInnerRequest['results']
                                                                        as $prevSlab
                                                                    ) {
                                                                        if (
                                                                            $prevSlab->slabType->id ==
                                                                            $slab->slabType->id
                                                                        ) {
                                                                            $previousDeduction =
                                                                                $prevSlab->applied_deduction;
                                                                            break;
                                                                        }
                                                                    }
                                                                }
                                                                $innerDeductionValue = $isLumpSumEnabledInTicket
                                                                    ? 0
                                                                    : $previousDeduction ??
                                                                        ($slab->applied_deduction ??
                                                                            ($valuesOfInitialSlabs[
                                                                                $slab->slabType->id
                                                                            ] ??
                                                                                0));

                                                                $suggestedDeductionType =
                                                                    $getDeductionSuggestion->deduction_type ?? 'amount';

                                                                $suggestedDeductionType == 'amount'
                                                                    ? ($suggestedValue +=
                                                                        $getDeductionSuggestion->deduction_value ?? 0)
                                                                    : ($suggestedValueKgs +=
                                                                        $getDeductionSuggestion->deduction_value ?? 0);

                                                                $previousChecklistValue = null;

                                                                if (!empty($innerRequestsData)) {
                                                                    $lastInnerRequestData =
                                                                        $innerRequestsData[
                                                                            count($innerRequestsData) - 1
                                                                        ];
                                                                    foreach (
                                                                        $lastInnerRequestData['results']
                                                                        as $lastSlab
                                                                    ) {
                                                                        if (
                                                                            $lastSlab->slabType->id ==
                                                                            $slab->slabType->id
                                                                        ) {
                                                                            $previousChecklistValue =
                                                                                $lastSlab->checklist_value;
                                                                            break;
                                                                        }
                                                                    }
                                                                }

                                                                if (
                                                                    $previousChecklistValue === null &&
                                                                    !empty($initialRequestsData)
                                                                ) {
                                                                    $lastInitialData =
                                                                        $initialRequestsData[
                                                                            count($initialRequestsData) - 1
                                                                        ];
                                                                    foreach (
                                                                        $lastInitialData['results']
                                                                        as $initialSlab
                                                                    ) {
                                                                        if (
                                                                            $initialSlab->slabType->id ==
                                                                            $slab->slabType->id
                                                                        ) {
                                                                            $previousChecklistValue =
                                                                                $initialSlab->checklist_value;
                                                                            break;
                                                                        }
                                                                    }
                                                                }

                                                                $displayValue =
                                                                    ($previousValue ?? ($slab->checklist_value ?? 0)) -
                                                                    ($slab->relief_deduction ?? 0);

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

                                                                if (
                                                                    ((float) $slab->checklist_value ?? 0) >
                                                                    ((float) $slab->max_range ?? 0)
                                                                ) {
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
                                                                        <input type="text"
                                                                            class="form-control {{ $comparisonClass }}"
                                                                            name="checklist_value[]"
                                                                            value="{{ $displayValue }}" placeholder="%"
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
                                                                        <input type="text" class="form-control"
                                                                            name="suggested_deduction[]"
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
                                                                        <input type="text"
                                                                            id="deduction-{{ $slab->slabType->id }}"
                                                                            class="form-control bg-white deduction-field"
                                                                            name="applied_deduction[]"
                                                                            value="{{ $innerDeductionValue }}"
                                                                            placeholder="Deduction"
                                                                            data-matching-slabs="{{ json_encode($slab->matching_slabs) }}"
                                                                            data-calculated-on="{{ $slab->slabType->calculation_base_type }}"
                                                                            data-slab-id="{{ $slab->slabType->id }}"
                                                                            data-product-id="{{ optional($arrivalSamplingRequest->arrivalTicket)->product->id }}"
                                                                            data-checklist="{{ $displayValue }}"
                                                                            {{ $isLumpSumEnabledInTicket ? 'readonly' : '' }}>
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
                                                                $displayCompValue =
                                                                    $previousCompValue ??
                                                                    $slab->compulsory_checklist_value;
                                                                $previousCompDeduction = null;
                                                                if (
                                                                    ($slab->applied_deduction === null ||
                                                                        $slab->applied_deduction == 0) &&
                                                                    $previousInnerRequest
                                                                ) {
                                                                    foreach (
                                                                        $previousInnerRequest['compulsuryResults']
                                                                        as $prevComp
                                                                    ) {
                                                                        if (
                                                                            $prevComp->qcParam->id == $slab->qcParam->id
                                                                        ) {
                                                                            $previousCompDeduction =
                                                                                $prevComp->applied_deduction;
                                                                            break;
                                                                        }
                                                                    }
                                                                }
                                                                $compDeductionValue =
                                                                    $previousCompDeduction ??
                                                                    ($slab->applied_deduction ?? 0);

                                                                $defaultValue = '';
                                                                if ($slab->qcParam->type == 'dropdown') {
                                                                    $options = json_decode(
                                                                        $slab->qcParam->options,
                                                                        true,
                                                                    );
                                                                    $defaultValue = $options[0] ?? '';
                                                                }

                                                                $compulsaryClass = '';

                                                                if ($displayCompValue != $defaultValue) {
                                                                    $compulsaryClass =
                                                                        'slabs-checklist-changed-compulsury';
                                                                }
                                                            @endphp

                                                            <div class="form-group row">
                                                                <input type="hidden" name="compulsory_param_id[]"
                                                                    value="{{ $slab->qcParam->id }}">
                                                                <label
                                                                    class="label-control font-weight-bold col-md-4  ">{{ $slab->qcParam->name }}</label>
                                                                <div
                                                                    class="QcResult {{ checkIfNameExists($slab->qcParam->name) ? 'col-md-8' : 'col-md-6' }}">
                                                                    @if ($slab->qcParam->type == 'dropdown')
                                                                        <input type="text"
                                                                            class="form-control {{ $compulsaryClass }}"
                                                                            name="compulsory_checklist_value[]"
                                                                            value="{{ $displayCompValue }}"
                                                                            data-default-value="{{ $defaultValue }}"
                                                                            readonly>
                                                                    @else
                                                                        <textarea class="form-control {{ $compulsaryClass }}" name="compulsory_checklist_value[]" readonly>{{ $displayCompValue }}</textarea>
                                                                    @endif
                                                                </div>
                                                                @if (!checkIfNameExists($slab->qcParam->name))
                                                                    <div class="col-md-2 QcResult">
                                                                        <div class="input-group mb-0">
                                                                            <input type="text"
                                                                                id="inp-{{ $slab->qcParam->id }}"
                                                                                class="form-control bg-white deduction-field"
                                                                                name="compulsory_aapplied_deduction[]"
                                                                                value="{{ $compDeductionValue }}"
                                                                                placeholder="Deduction"
                                                                                data-slab-id="{{ $slab->qcParam->id }}"
                                                                                data-calculated-on="{{ $slab->qcParam->calculation_base_type }}"
                                                                                data-checklist="{{ $displayCompValue }}"
                                                                                {{ $isLumpSumEnabledInTicket ? 'readonly' : '' }}>
                                                                            <div class="input-group-append">
                                                                                <span
                                                                                    class="input-group-text text-sm">{{ SLAB_TYPES_CALCULATED_ON[$slab->qcParam->calculation_base_type ?? 1] }}</span>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                @else
                                                                    <input type="hidden"
                                                                        name="compulsory_aapplied_deduction[]"
                                                                        value="0">
                                                                @endif
                                                            </div>
                                                        @endforeach
                                                    @else
                                                        <div class="alert alert-warning">No Compulsory Slabs Found</div>
                                                    @endif
                                                </div>

                                                <div class="striped-rows mt-3">
                                                    <div class="form-group row">
                                                        <label class="col-md-4 label-control font-weight-bold"
                                                            for="lumpsum-toggle">Apply Lumpsum
                                                            Deduction</label>
                                                        <div class="col-md-3">
                                                            <div class="custom-control custom-switch">
                                                                <input type="checkbox" class="custom-control-input"
                                                                    id="lumpsum-toggle"
                                                                    name="{{ !$isLumpSumEnabledInTicket ? 'is_lumpsum_deduction' : 'is_lumpsum_deduction_display' }}"
                                                                    @checked($isLumpSumEnabledInTicket)
                                                                    @disabled($isLumpSumEnabledInTicket)>
                                                                @if ($isLumpSumEnabledInTicket)
                                                                    <input type="hidden" name="is_lumpsum_deduction"
                                                                        value="on">
                                                                @endif
                                                                <label class="custom-control-label"
                                                                    for="lumpsum-toggle"></label>
                                                            </div>
                                                        </div>
                                                        <div class="col">
                                                            <div class="input-group mb-2">
                                                                <input type="text" id="suggessions-sum"
                                                                    class="form-control" name="suggessions_sum" disabled
                                                                    value="{{ $suggestedValue }}"
                                                                    placeholder="Suggested Sum">
                                                                <div class="input-group-append">
                                                                    <span class="input-group-text text-sm">Rs.</span>
                                                                </div>
                                                            </div>
                                                            <div class="input-group mb-0">
                                                                <input type="text" id="suggessions-sum"
                                                                    class="form-control" name="suggessions_sum" disabled
                                                                    value="{{ $suggestedValueKgs }}"
                                                                    placeholder="Suggested Sum">
                                                                <div class="input-group-append">
                                                                    <span class="input-group-text text-sm">Kgs.</span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col">
                                                            <div class="input-group mb-2">
                                                                <input type="text" id="lumpsum-value"
                                                                    class="form-control" name="lumpsum_deduction"
                                                                    {{ $isLumpSumEnabledInTicket ? '' : 'readonly' }}
                                                                    {{-- value="{{ $arrivalSamplingRequest->lumpsum_deduction ?? ($rupeeLumpSum ?? 0) }}" --}}
                                                                    value="{{ $rupeeLumpSum ?? 0 }}"
                                                                    placeholder="Lumpsum Deduction">
                                                                <div class="input-group-append">
                                                                    <span class="input-group-text text-sm">Rs.</span>
                                                                </div>
                                                            </div>
                                                            <div class="input-group mb-0">
                                                                <input type="text" id="lumpsum-kgs-value"
                                                                    class="form-control" name="lumpsum_deduction_kgs"
                                                                    {{ $isLumpSumEnabledInTicket ? '' : 'readonly' }}
                                                                    {{-- value="{{ $arrivalSamplingRequest->lumpsum_deduction_kgs ?? ($kgLumpSum ?? 0) }}" --}} value="{{ $kgLumpSum ?? 0 }}"
                                                                    placeholder="Lumpsum Deduction">
                                                                <div class="input-group-append">
                                                                    <span class="input-group-text text-sm">KG's</span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="form-group row">
                                                        <label class="col-md-4 label-control font-weight-bold"
                                                            for="decision_making">Decision Making
                                                            on Avg.</label>
                                                        <div class="col-md-3">
                                                            <div class="custom-control custom-switch">
                                                                <input type="checkbox" name="decision_making"
                                                                    class="custom-control-input" id="decision_making"
                                                                    @checked($isDecisionMaking)
                                                                    @disabled($isDecisionMakingDisabled)>
                                                                <label class="custom-control-label"
                                                                    for="decision_making"></label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection
@section('script')
    <script>
        $(document).ready(function() {
            //filterationCommon(`{{ route('get.ticket') }}`)
        });
    </script>
@endsection
