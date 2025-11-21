@extends($layout)

@section('title')
    Ticket
@endsection
@section('content')
@php
    $isLumpSumEnabled = false;
    $isLumpSumEnabledInTicket = false;
    $rupeeLumpSum = 0;
    $kgLumpSum = 0;
    $isDecisionMaking = false;
    $isDecisionMakingDisabled = false;
    $valuesOfInitialSlabs = [];
    $suggestedValueForInner = 0;
    $suggestedValue = 0;
    $suggestedValueForInnerKgs = 0;
    $suggestedValueKgs = 0;
    $previousInnerRequest = null;
    $lastInnerRequest = null;
    $currentTabLabel = 'Current QC';

    if ($arrivalSamplingRequest) {
        $isLumpSumEnabled = $arrivalSamplingRequest->is_lumpsum_deduction == 1 ? true : false;
        $isLumpSumEnabledInTicket =
            $arrivalSamplingRequest->arrivalTicket->is_lumpsum_deduction == 1 ? true : false;
        $rupeeLumpSum = $arrivalSamplingRequest->arrivalTicket->lumpsum_deduction ?? 0;
        $kgLumpSum = $arrivalSamplingRequest->arrivalTicket->lumpsum_deduction_kgs ?? 0;

        $isDecisionMaking = $arrivalSamplingRequest->arrivalTicket->decision_making == 1 ? true : false;
        $isDecisionMakingDisabled =
            $arrivalSamplingRequest->arrivalTicket->decision_making == 0 &&
            $arrivalSamplingRequest->arrivalTicket->decision_making_time
            ? true
            : false;

        $previousInnerRequest = $innerRequestsData[0] ?? null;
        $lastInnerRequest = !empty($innerRequestsData)
            ? $innerRequestsData[count($innerRequestsData) - 1]
            : null;

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
                    ($arrivalSamplingRequest->sampling_type == 'inner' ? 'Current ' : '') .
                    $arrivalSamplingRequest->sampling_type,
                );
        }
    }
@endphp

@if (!isset($source) || $source != 'contract')
    <div class="content-wrapper">
        <section id="extended">
            <div class="row w-100 mx-auto">
                <div class="col-xs-12 col-sm-12 col-md-12 col-lg-6">
                    <h2 class="page-title"> Arrival Summary: {{ $arrivalTicket->unique_no }}</h2>
                </div>
            </div>


@endif
        <form id="ajaxSubmit" action="{{ route('ticket.arrival-revert.update', $arrivalTicket->id) }}" method="POST">
            @csrf
            <div class="row pt-2">
                <div class="col-md-6">

                    <input type="hidden" id="url" value="{{ route('ticket.arrival-revert', $arrivalTicket->id) }}">
                    <div class="card">
                        <div class="card-content">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-12">
                                        <h6 class="header-heading-sepration">
                                            Ticket Detail
                                        </h6>
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-md-12">
                                        <div class="form-group">
                                            <label class="d-block">Contract Detail:</label>
                                            <select name="arrival_purchase_order_id" id="arrival_purchase_order_id"
                                                class="form-control select2">
                                                <option value="">N/A</option>
                                                @foreach ($arrivalPurchaseOrders as $order)
                                                    <option value="{{ $order->id }}"
                                                        data-product-id="{{ $order->product->id ?? '' }}"
                                                        data-product-name="{{ $order->product->name ?? '' }}"
                                                        data-supplier-id="{{ $order->supplier->name ?? '' }}"
                                                        data-supplier-name="{{ $order->supplier->name ?? '' }}"
                                                        data-created-by-id="{{ $order->created_by ?? '' }}"
                                                        data-created-by-name="{{ $order->createdByUser->name ?? '' }}"
                                                        data-sauda-type-name="{{ $order->saudaType->name ?? '' }}"
                                                        data-sauda-type-id="{{ $order->saudaType->id ?? '' }}"
                                                        data-created-at="{{ $order->created_at ?? '' }}"
                                                        @selected($arrivalTicket->arrival_purchase_order_id == $order->id)>
                                                        #{{ $order->contract_no }} - Type:
                                                        {{ $order->saudaType->name ?? 'N/A' }} - Purchase Type:
                                                        {{ formatEnumValue($order->purchase_type ?? 'N/A') }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <!-- <div class="col-xs-12 col-sm-12 col-md-12">
                                        <div class="form-group">
                                            <label>Product:</label>
                                            <input type="text" name="station_id" placeholder="Product Name"
                                                class="form-control" disabled autocomplete="off"
                                                value="{{ $arrivalTicket->product->name ?? 'N/A' }}" />
                                        </div>
                                    </div> -->
                                    <div class="col-xs-6 col-sm-6 col-md-4">
                                        <div class="form-group ">
                                            <label>Product:</label>
                                            <select name="product_id_display" id="product_id"
                                                class="form-control select2">
                                                <option value="">Product Name</option>
                                                @foreach ($products as $product)
                                                    <option value="{{ $product->id }}"
                                                        @selected($arrivalTicket->product_id == $product->id)>
                                                        {{ $product->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <input type="hidden" name="product_id" id="product_id_hidden"
                                                value="{{ $arrivalTicket->product_id }}">
                                        </div>
                                    </div>
                                    <div class="col-xs-6 col-sm-6 col-md-4">
                                        <div class="form-group">
                                            <label>Miller:</label>

                                            <label>Millers:</label>
                                            <select name="miller_name" id="miller_id" class="form-control select2">
                                                <option value="">Select Miller</option>
                                                @if ($arrivalTicket->miller)
                                                    <option value="{{ $arrivalTicket->miller->name }}" selected>
                                                        {{ $arrivalTicket->miller->name }}
                                                    </option>
                                                @endif
                                            </select>
                                            {{-- <input type="hidden" name="miller_id" id="miller_id_submit">
                                            --}}

                                            {{-- <input type="text" placeholder="Miller" class="form-control" disabled
                                                autocomplete="off"
                                                value="{{ $arrivalTicket->miller->name ?? 'N/A' }}" /> --}}
                                        </div>
                                    </div>

                                    <!-- <div class="col-xs-6 col-sm-6 col-md-4">
                                        <div class="form-group">
                                            <label>Broker:</label>
                                            <input type="text" placeholder="Broker" class="form-control" disabled
                                                autocomplete="off" value="{{ $arrivalTicket->broker_name }}" />
                                        </div>
                                    </div> -->
                                    <div class="col-xs-6 col-sm-6 col-md-4">
                                        <div class="form-group ">
                                            <label>Broker:</label>
                                            <select name="broker_name" id="broker_name" class="form-control select2">
                                                <option value="">Broker Name</option>
                                                @foreach ($suppliers as $supplier)
                                                    <option value="{{ $supplier->name }}"
                                                        @selected($arrivalTicket->broker_name == $supplier->name)>
                                                        {{ $supplier->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            
                                        </div>
                                    </div>
                                    <!-- <div class="col-xs-6 col-sm-6 col-md-4">
                                        <div class="form-group">
                                            <label>Accounts Of:</label>
                                            <input type="text" placeholder="Accounts Of" class="form-control" disabled
                                                autocomplete="off"
                                                value="{{ $arrivalTicket->accounts_of_name ?? 'N/A' }}" />
                                        </div>
                                    </div> -->
                                    <div class="col-xs-6 col-sm-6 col-md-4">
                                        <div class="form-group">
                                            <label>Accounts Of:</label>
                                            <select name="accounts_of_display" id="accounts_of" class="form-control select2">
                                                <option value="" hidden>Accounts Of</option>
                                                @foreach ($suppliers as $supplier)
                                                    <option value="{{ $supplier->name }}"
                                                        @selected($arrivalTicket->accounts_of_name == $supplier->name)>
                                                        {{ $supplier->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <input type="hidden" name="accounts_of" id="accounts_of_hidden">


                                        </div>
                                    </div>
                                    <div class="col-xs-6 col-sm-6 col-md-4">
                                        <div class="form-group">
                                            <label>Decision Of:</label>
                                            <input type="text" placeholder="Decision Of" class="form-control" disabled
                                                autocomplete="off"
                                                value="{{ $arrivalTicket->decisionBy->name ?? 'N/A' }}" />
                                        </div>
                                    </div>


                                    <div class="col-xs-6 col-sm-6 col-md-4">
                                        <div class="form-group ">
                                            <label>Station:</label>
                                            <select name="station" id="station_id" class="form-control select2">
                                                <option value="" disabled>Station</option>
                                                @if ($arrivalTicket->station_name)
                                                    <option value="{{ $arrivalTicket->station_name }}" selected>
                                                        {{ $arrivalTicket->station_name }}
                                                    </option>
                                                @endif
                                            </select>
                                        </div>
                                    </div>
                                    <!-- <div class="col-xs-6 col-sm-6 col-md-4">
                                        <div class="form-group">
                                            <label>Truck Type:</label>
                                            <input type="text" name="station_id" placeholder="Truck Type"
                                                class="form-control" disabled autocomplete="off"
                                                value="{{ $arrivalTicket->truckType->name ?? 'N/A' }}" />
                                        </div>
                                    </div> -->


                                    <div class="col-xs-6 col-sm-6 col-md-4">
                                        <div class="form-group ">
                                            <label>Truck Type:</label>
                                            <select name="truck_type_id" id="truck_type_id"
                                                class="form-control select2">
                                                <option value="">Truck Type</option>
                                                @foreach (getTableData('arrival_truck_types', ['id', 'name', 'sample_money']) as $arrival_truck_types)
                                                    <option data-samplemoney="{{ $arrival_truck_types->sample_money ?? 0 }}"
                                                        value="{{ $arrival_truck_types->id }}"
                                                        @selected($arrivalTicket->truck_type_id == $arrival_truck_types->id)>
                                                        {{ $arrival_truck_types->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-xs-6 col-sm-6 col-md-4">
                                        <div class="form-group ">
                                            <label>Sample Money Type:</label>
                                            <select name="sample_money_type" class="form-control">
                                                <option value="n/a" @selected($arrivalTicket->sample_money_type == 'n/a')>
                                                    N/A</option>
                                                <option value="single"
                                                    @selected($arrivalTicket->sample_money_type == 'single')>Single
                                                </option>
                                                <option value="double"
                                                    @selected($arrivalTicket->sample_money_type == 'double')>Double
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                    <!-- <div class="col-xs-6 col-sm-6 col-md-4">
                                        <div class="form-group">
                                            <label>Sample Money Type:</label>
                                            <input type="text" name="station_id" placeholder="Product Name"
                                                class="form-control" disabled autocomplete="off"
                                                value="{{ isset($arrivalTicket->sample_money_type) ? ucwords($arrivalTicket->sample_money_type) : 'N/A' }}" />
                                        </div>
                                    </div> -->

                                    <div class="col-xs-6 col-sm-6 col-md-4">
                                        <div class="form-group">
                                            <label>Truck No:</label>
                                            <input type="text" name="truck_no" value="{{ $arrivalTicket->truck_no }}"
                                                placeholder="Truck No" class="form-control" autocomplete="off" />
                                        </div>
                                    </div>
                                    <div class="col-xs-6 col-sm-6 col-md-4">
                                        <div class="form-group">
                                            <label>Bilty No: </label>
                                            <input type="text" name="bilty_no" value="{{ $arrivalTicket->bilty_no }}"
                                                placeholder="Bilty No" class="form-control" autocomplete="off" />
                                        </div>
                                    </div>
                                    <div class="col-xs-6 col-sm-6 col-md-4">
                                        <div class="form-group">
                                            <label>No of bags: </label>
                                            <input type="text" name="bags" placeholder="No of bags" class="form-control"
                                                autocomplete="off" value="{{ $arrivalTicket->bags }}" />
                                        </div>
                                    </div>
                                    <div class="col-xs-6 col-sm-6 col-md-4">
                                        <div class="form-group">
                                            <label>Sample Money: </label>
                                            <input type="text" readonly name="sample_money"
                                                value="{{ $arrivalTicket->sample_money ?? 0 }}" placeholder="No of bags"
                                                class="form-control" autocomplete="off" />
                                        </div>
                                    </div>
                                    <div class="col-xs-6 col-sm-6 col-md-4">
                                        <div class="form-group">
                                            <label>Loading Date:</label>
                                            <input type="date" name="loading_date"
                                                value="{{ $arrivalTicket->loading_date }}" placeholder="Bilty No"
                                                class="form-control" autocomplete="off" />
                                        </div>
                                    </div>
                                    @if ($arrivalTicket->remarks)
                                        <div class="col-xs-12 col-sm-12 col-md-12">
                                            <div class="form-group">
                                                <label>Remarks (Optional):</label>
                                                <textarea name="remarks" row="2" disabled class="form-control"
                                                    placeholder="Remarks">{{ $arrivalTicket->remarks }}</textarea>
                                            </div>
                                        </div>
                                    @endif
                                </div>

                                <div class="row">
                                    <div class="col-12">
                                        <h6 class="header-heading-sepration">
                                            Loading Weight Detail
                                        </h6>
                                    </div>
                                    <div class="col-xs-6 col-sm-6 col-md-4">
                                        <div class="form-group">
                                            <label>First Weighbridge Weight:</label>
                                            <input type="text" name="first_weight" placeholder="First Weight"
                                                class="form-control" autocomplete="off"
                                                value="{{ $arrivalTicket->first_weight }}" />
                                        </div>
                                    </div>
                                    <div class="col-xs-6 col-sm-6 col-md-4">
                                        <div class="form-group">
                                            <label>2nd Weighbridge Weight: </label>
                                            <input type="text" name="second_weight" placeholder="Second Weight"
                                                class="form-control" autocomplete="off"
                                                value="{{ $arrivalTicket->second_weight }}" />
                                        </div>
                                    </div>
                                    <div class="col-xs-6 col-sm-6 col-md-4">
                                        <div class="form-group">
                                            <label>Net Weight: </label>
                                            <input type="text" name="net_weight" disabled placeholder="Net Weight"
                                                class="form-control" autocomplete="off"
                                                value="{{ $arrivalTicket->net_weight }}" />
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-md-12 text-right">
                                        <div class="form-group">
                                            <input type="submit" value="Save Ticket" name="ticket_submit"
                                                @if($arrivalTicket->freight_status == 'completed') disabled
                                                    data-toggle="tooltip"
                                                    title="You cannot update information because freight is already created"
                                                @endif class="btn btn-primary" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-content">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-12">
                                        <h6 class="header-heading-sepration">
                                            Location Transfer
                                        </h6>
                                    </div>
                                    @if (isset($arrivalTicket->unloadingLocation))
                                        <div class="col-xs-12 col-sm-12 col-md-12">
                                            <div class="form-group">
                                                <label>Location:</label>
                                                <select class="form-control select2"
                                                    @if($arrivalTicket->freight_status == 'completed') disabled
                                                        data-toggle="tooltip"
                                                        title="You cannot update information because freight is already created"
                                                    @endif name="arrival_location_id">
                                                    <option value="">Select Location</option>
                                                    @foreach ($ArrivalLocations as $ArrivalLocation)
                                                        <option {{ $arrivalTicket->unloadingLocation->arrival_location_id == $ArrivalLocation->id ? 'selected' : '' }} value="{{ $ArrivalLocation->id }}">
                                                            {{ $ArrivalLocation->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-xs-12 col-sm-12 col-md-12 text-right">
                                            <div class="form-group">
                                                <input type="submit" value="Revert Location" name="location_transfer_revert"
                                                    @if($arrivalTicket->first_weighbridge_status == 'completed') disabled
                                                        data-toggle="tooltip"
                                                    title="Revert 1st weighbridge first to unlock Location Transfer" @endif
                                                    class="btn btn-danger"
                                                    onclick="return confirm('Are you sure you want to revert location transfer?')" />

                                                <input type="submit" value="Save Location" name="location_transfer_submit"
                                                    @if($arrivalTicket->freight_status == 'completed') disabled
                                                        data-toggle="tooltip"
                                                        title="You cannot update information because freight is already created"
                                                    @endif class="btn btn-primary" />
                                            </div>
                                        </div>
                                    @else
                                        <div class="col-12">
                                            <div class="alert bg-light-warning">
                                                <i class="fa fa-exclamation-triangle"></i> Loading not found
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-content">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-12">
                                        <h6 class="header-heading-sepration">
                                            First Weighbridge Detail
                                        </h6>
                                    </div>
                                    @if (isset($arrivalTicket->firstWeighbridge))
                                        <div class="col-xs-12 col-sm-12 col-md-12">
                                            <div class="form-group">
                                                <label>First Weighbridge Weight:</label>
                                                <input type="text" name="arrival_first_weight" placeholder="First Weight"
                                                    @if($arrivalTicket->freight_status == 'completed') disabled
                                                        data-toggle="tooltip"
                                                        title="You cannot update information because freight is already created"
                                                    @endif class="form-control" autocomplete="off"
                                                    value="{{ $arrivalTicket->firstWeighbridge->weight }}" />
                                            </div>
                                        </div>
                                        <div class="col-xs-12 col-sm-12 col-md-12 text-right">
                                            <div class="form-group">
                                                <input type="submit" @if($arrivalTicket->document_approval_status != null)
                                                    disabled data-toggle="tooltip"
                                                    title="Revert Half/Full Approved first to unlock First weighbridge"
                                                @endif value="Revert First Weighbridge" name="first_weighbridge_revert"
                                                    class="btn btn-danger"
                                                    onclick="return confirm('Are you sure you want to revert first weighbridge?')" />
                                                <input type="submit" value="Save First Weighbridge"
                                                    name="first_weighbridge_submit"
                                                    @if($arrivalTicket->freight_status == 'completed') disabled
                                                        data-toggle="tooltip"
                                                        title="You cannot update information because freight is already created"
                                                    @endif class="btn btn-primary" />

                                            </div>
                                        </div>
                                    @else
                                        <div class="col-12">
                                            <div class="alert bg-light-warning">
                                                <i class="fa fa-exclamation-triangle"></i> First Weighbridge not found
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>


                    <!-- Half/Full Approval Section -->
                    <div class="card">
                        <div class="card-content">
                            <div class="card-body">

                                <div class="row">
                                    <div class="col-12">
                                        <h6 class="header-heading-sepration">
                                            Half / Full Approved
                                        </h6>
                                    </div>
                                    @if (isset($arrivalTicket->approvals))
                                        <div class="col-xs-6 col-sm-6 col-md-6">
                                            <div class="form-group">
                                                <label>Gala Name:</label>
                                                <select class="form-control select2" name="gala_id" id="gala_id">
                                                    <option value="">Select Gala</option>
                                                    @foreach ($arrivalSubLocations as $arrivalSubLocation)
                                                        <option {{ $arrivalTicket->approvals->gala_id == $arrivalSubLocation->id ? 'selected' : '' }} value="{{ $arrivalSubLocation->id }}">
                                                            {{ $arrivalSubLocation->name }}
                                                        </option>
                                                    @endforeach
                                                </select>

                                            </div>
                                        </div>

                                        <div class="col-xs-6 col-sm-6 col-md-6">
                                            <div class="form-group">
                                                <label>Bag type:</label>
                                                <select class="form-control select2" name="bag_type_id" id="bag_type_id">
                                                    <option value="">Select Bag type</option>
                                                    @foreach ($bagTypes as $bagType)
                                                        <option {{ $arrivalTicket->approvals->bag_type_id == $bagType->id ? 'selected' : '' }} value="{{ $bagType->id }}">{{ $bagType->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-xs-6 col-sm-6 col-md-6">
                                            <div class="form-group filling-bags-field">
                                                <label>Filling Bags: </label>
                                                <input type="number" min="0" name="filling_bags_no"
                                                    placeholder="Filling Bags" class="form-control"
                                                    value="{{ $arrivalTicket->approvals->filling_bags_no }}"
                                                    autocomplete="off" />
                                            </div>
                                        </div>
                                        <div class="col-xs-6 col-sm-6 col-md-6">
                                            <div class="form-group bag-condition-field">
                                                <label>Bag Condition:</label>
                                                <select class="form-control select2" name="bag_condition_id">
                                                    <option value="">Select Condition</option>
                                                    @foreach ($bagConditions as $condition)
                                                        <option {{ $arrivalTicket->approvals->bag_condition_id == $condition->id ? 'selected' : '' }} value="{{ $condition->id }}">
                                                            {{ $condition->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-xs-6 col-sm-6 col-md-6">
                                            <div class="form-group bag-packing-field">
                                                <label>Bag Packing:</label>
                                                <select class="form-control" name="bag_packing_id">
                                                    <option value="">Select Bag Packing</option>
                                                    @foreach ($bagPackings as $packing)
                                                        <option {{ $arrivalTicket->approvals->bag_packing_id == $packing->id ? 'selected' : '' }} value="{{ $packing->id }}">{{ $packing->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>


                                        <div class="col-xs-12 col-sm-12 col-md-12">
                                            <div class="row">
                                                <div class="col-12">
                                                    <h6 class="header-heading-sepration">
                                                        Total Receivings
                                                    </h6>
                                                </div>
                                                <div class="col-xs-12 col-sm-12 col-md-12">
                                                    <div class="form-group">
                                                        <label>Total Bags : </label>
                                                        <input type="number" name="total_bags" placeholder="Total Bags"
                                                            class="form-control" oninput="calculateBags()"
                                                            value="{{ $arrivalTicket->approvals->total_bags }}"
                                                            autocomplete="off"
                                                            mddax="{{ $arrivalTicket->document_approval_status == 'half_approved' ? $arrivalTicket->bags : '' }}"" required />
                                                                                                                                                                                                        </div>
                                                                                                                                                                                                    </div>
                                                                                                                                                                                                </div>

                                                                                                                                                                                                <div
                                                                                                                                                                                                    class="
                                                            row total-rejection-section {{ $arrivalTicket->document_approval_status == 'fully_approved' ? 'd-none' : '' }}">

                                                        <div class="col-12">
                                                            <h6 class="header-heading-sepration" style="background:#ffafaf">
                                                                Total Rejection
                                                            </h6>
                                                        </div>
                                                        <div class="col-xs-12 col-sm-12 col-md-12">
                                                            <div class="form-group">
                                                                <label>Total Rejection Bags : </label>
                                                                <input type="number" readonly name="total_rejection"
                                                                    id="total_rejection" placeholder="Total Rejection Bags"
                                                                    class="form-control" autocomplete="off"
                                                                    value="{{ $arrivalTicket->document_approval_status == 'fully_approved' ? 0 : $arrivalTicket->approvals->total_rejection }}" />
                                                            </div>
                                                        </div>
                                                        <div class="col-xs-12 col-sm-12 col-md-12">
                                                            <div class="form-group">
                                                                <label>Amanat:</label>
                                                                <select class="form-control" name="amanat">
                                                                    <option value="No">No</option>
                                                                    <option value="Yes">Yes</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="row">
                                                        <div class="col-xs-12 col-sm-12 col-md-12">
                                                            <div class="form-group">
                                                                <label>Remark:</label>
                                                                <textarea name="remark" placeholder="Note"
                                                                    class="form-control"
                                                                    rows="5">{{ $arrivalTicket->approvals->remark }}</textarea>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="col-xs-12 col-sm-12 col-md-12 text-right">
                                                    <input type="submit"
                                                        @if($arrivalTicket->second_weighbridge_status == 'completed') disabled
                                                            data-toggle="tooltip"
                                                            title="You cannot update information because freight is already created"
                                                        @endif value="Revert Approval" name="half_full_approve_revert"
                                                        class="btn btn-danger"
                                                        onclick="return confirm('Are you sure you want to revert approval?')" />
                                                    <input type="submit" value="Save Half/Full"
                                                        name="half_full_approve_submit" class="btn btn-primary" />
                                                </div>


                                    @else
                                                <div class="col-12">
                                                    <div class=" alert bg-light-warning">
                                                        <i class="fa fa-exclamation-triangle"></i> Half/ Full Approved not
                                                        found
                                                    </div>
                                                </div>
                                            @endif
                                        </div>

                                    </div>
                                </div>
                            </div>


                            <div class="card">
                                <div class="card-content">
                                    <div class="card-body">

                                        <div class="row">
                                            <div class="col-12">
                                                <h6 class="header-heading-sepration">
                                                    Second Weighbridge Detail
                                                </h6>
                                            </div>
                                            @if (isset($arrivalTicket->secondWeighbridge))
                                                <div class="col-xs-6 col-sm-6 col-md-4">
                                                    <div class="form-group">
                                                        <label>First Weighbridge Weight:</label>
                                                        <input type="text" disabled placeholder="First Weight"
                                                            class="form-control" autocomplete="off"
                                                            value="{{ $arrivalTicket->firstWeighbridge->weight }}" />
                                                    </div>
                                                </div>
                                                <div class="col-xs-6 col-sm-6 col-md-4">
                                                    <div class="form-group">
                                                        <label>2nd Weighbridge Weight: </label>
                                                        <input type="text" name="arrival_second_weight"
                                                            placeholder="Second Weight" class="form-control"
                                                            autocomplete="off"
                                                            value="{{ $arrivalTicket->secondWeighbridge->weight }}" />
                                                    </div>
                                                </div>
                                                <div class="col-xs-6 col-sm-6 col-md-4">
                                                    <div class="form-group">
                                                        <label>Net Weighbridge Weight: </label>
                                                        <input type="text" name="arrival_net_weight" disabled
                                                            placeholder="Second Weight" class="form-control"
                                                            autocomplete="off"
                                                            value="{{ $arrivalTicket->firstWeighbridge->weight - $arrivalTicket->SecondWeighbridge->weight }}" />
                                                    </div>
                                                </div>
                                                <div class="col-xs-12 col-sm-12 col-md-12">
                                                    <fieldset>
                                                        <div class="input-group">
                                                            <div class="input-group-prepend">
                                                                <button class="btn btn-primary" type="button">Weight
                                                                    Difference</button>
                                                            </div>
                                                            <input type="text" id="weight_difference"
                                                                name="weight_difference" placeholder="Weight Difference"
                                                                readonly class="form-control" autocomplete="off"
                                                                value="{{ $arrivalTicket->firstWeighbridge->weight - $arrivalTicket->SecondWeighbridge->weight - $arrivalTicket->net_weight }}" />
                                                        </div>
                                                    </fieldset>
                                                </div>
                                                <div class="col-xs-6 col-sm-6 col-md-4 d-none">
                                                    <div class="form-group">
                                                        <label>Weight Difference: </label>
                                                        <input type="text" name="net_weight" disabled
                                                            placeholder="Net Weight" class="form-control" autocomplete="off"
                                                            value="{{ $arrivalTicket->firstWeighbridge->weight - $arrivalTicket->SecondWeighbridge->weight - $arrivalTicket->net_weight }}" />
                                                    </div>
                                                </div>

                                                <div class="col-xs-12 col-sm-12 col-md-12 text-right">
                                                    <div class="form-group">
                                                        <input @if($arrivalTicket->freight_status == 'completed') disabled
                                                            data-toggle="tooltip"
                                                        title="Revert freight first to unlock second weighbridge" @endif
                                                            type="submit" value="Revert Second Weighbridge"
                                                            name="second_weighbridge_revert" class="btn btn-danger"
                                                            onclick="return confirm('Are you sure you want to revert second weighbridge?')" />

                                                        <input type="submit"
                                                            @if($arrivalTicket->freight_status == 'completed') disabled
                                                                data-toggle="tooltip"
                                                                title="You cannot update information because freight is already created"
                                                            @endif value="Save Second Weighbridge"
                                                            name="second_weighbridge_submit" class="btn btn-primary" />
                                                    </div>
                                                </div>
                                            @else
                                                <div class="col-12">
                                                    <div class="alert bg-light-warning">
                                                        <i class="fa fa-exclamation-triangle"></i> Second Weighbridge not
                                                        found
                                                    </div>
                                                </div>
                                            @endif
                                        </div>

                                    </div>
                                </div>
                            </div>



                            <div class="card">
                                <div class="card-content">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-12">
                                                <h6 class="header-heading-sepration">
                                                    Arrival Freight
                                                </h6>
                                            </div>
                                            @if (isset($arrivalTicket->freight))
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label>Loaded Weight</label>
                                                        <input type="number" name="loaded_weight" class="form-control"
                                                            value="{{ $arrivalTicket->freight->arrivalTicket->net_weight ?? 'N/A' }}"
                                                            disabled />
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label>Arrived Weight</label>
                                                        <input type="number" name="arrived_weight" class="form-control"
                                                            value="{{ $arrivalTicket->freight->arrivalTicket->arrived_net_weight ?? 'N/A' }}"
                                                            disabled />
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label>Difference</label>
                                                        <input type="number" name="difference" class="form-control"
                                                            value="{{ ($arrivalTicket->freight->arrivalTicket->arrived_net_weight ?? 0) - ($arrivalTicket->freight->arrivalTicket->net_weight ?? 0) }}"
                                                            disabled />
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label>Exempted Weight</label>
                                                        <input type="number" name="exempted_weight" class="form-control"
                                                            disabled
                                                            value="{{ $arrivalTicket->freight->exempted_weight }}" />
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label>Net Shortage</label>
                                                        <input type="number" name="net_shortage" class="form-control"
                                                            disabled value="{{ $arrivalTicket->freight->net_shortage }}" />
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label>Freight per Ton</label>
                                                        <input type="number" step="0.01" name="freight_per_ton" disabled
                                                            class="form-control"
                                                            value="{{ $arrivalTicket->freight->freight_per_ton }}" />
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label>Kanta Loading Charges</label>
                                                        <input type="number" step="0.01" disabled
                                                            name="kanta_golarchi_charges" class="form-control"
                                                            value="{{ $arrivalTicket->freight->kanta_golarchi_charges }}" />
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label>Arrived Kanta Charges</label>
                                                        <input type="number" step="0.01" name="karachi_kanta_charges"
                                                            class="form-control" disabled
                                                            value="{{ $arrivalTicket->freight->karachi_kanta_charges }}" />
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label>Other (+)/Labour Charges</label>
                                                        <input type="number" step="0.01" name="other_labour_charges"
                                                            class="form-control" disabled
                                                            value="{{ $arrivalTicket->freight->other_labour_charges }}" />
                                                    </div>
                                                </div>

                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label>Other Deduction</label>
                                                        <input type="number" step="0.01" name="other_deduction"
                                                            class="form-control" disabled
                                                            value="{{ $arrivalTicket->freight->other_deduction }}" />
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label>Unpaid Labor Charges</label>
                                                        <input type="number" step="0.01" name="unpaid_labor_charges"
                                                            class="form-control" disabled
                                                            value="{{ $arrivalTicket->freight->unpaid_labor_charges }}" />
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label>Freight Written on Bilty</label>
                                                        <input type="number" step="0.01" disabled
                                                            name="freight_written_on_bilty" class="form-control"
                                                            value="{{ $arrivalTicket->freight->freight_written_on_bilty }}" />
                                                    </div>
                                                </div>

                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label>Gross Freight Amount</label>
                                                        <input type="number" step="0.01" name="gross_freight_amount"
                                                            class="form-control" disabled
                                                            value="{{ $arrivalTicket->freight->gross_freight_amount }}" />
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label>Net Freight</label>
                                                        <input type="number" step="0.01" name="net_freight"
                                                            class="form-control" disabled
                                                            value="{{ $arrivalTicket->freight->net_freight }}" />
                                                    </div>
                                                </div>
                                                <div class="col-md-6 d-none">
                                                    <div class="form-group">
                                                        <label>Status</label>
                                                        <input type="hidden" name="status" value="approved">
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label>Attach Bilty</label>
                                                        <br />
                                                        @if ($arrivalTicket->freight->bilty_document)
                                                            <a href="{{ asset($arrivalTicket->freight->bilty_document) }}"
                                                                target="_blank">View
                                                                Current File</a>
                                                        @else
                                                            <small class="text-danger">No file found</small>
                                                        @endif
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label>Attach Loading Weight</label>
                                                        <br />
                                                        @if ($arrivalTicket->freight->loading_weight_document)
                                                            <a href="{{ asset($arrivalTicket->freight->loading_weight_document) }}"
                                                                target="_blank">View
                                                                Current File</a>
                                                        @else
                                                            <small class="text-danger">No file found</small>
                                                        @endif
                                                    </div>
                                                </div>

                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label>Other Document (Optional)</label>
                                                        <br />
                                                        @if ($arrivalTicket->freight->other_document)
                                                            <a href="{{ asset($arrivalTicket->freight->other_document) }}"
                                                                target="_blank">View
                                                                Current File</a>
                                                        @else
                                                            <small class="text-danger">No file found</small>
                                                        @endif
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label>Other Document 2 (Optional)</label>
                                                        <br />
                                                        @if ($arrivalTicket->freight->other_document_2)
                                                            <a href="{{ asset($arrivalTicket->freight->other_document_2) }}"
                                                                target="_blank">View
                                                                Current File</a>
                                                        @else
                                                            <small class="text-danger">No file found</small>
                                                        @endif
                                                    </div>
                                                </div>
                                                @if($arrivalTicket->is_ticket_verified != 1)
                                                    <div class="col-md-12 text-right">
                                                        <input type="submit" value="Revert Freight" name="freight_revert"
                                                            @if($arrivalTicket->is_ticket_verified == 1) disabled
                                                                data-toggle="tooltip"
                                                                title="This arrival has been verified and is locked from reverting."
                                                            @endif class="btn btn-danger"
                                                            onclick="return confirm('Are you sure you want to revert Arrival freight?')" />
                                                    </div>
                                                @endif
                                                @if($arrivalTicket->is_ticket_verified == 1)
                                                    <div class="col-12 mt-2">
                                                        <div class="alert bg-light-success">
                                                            <i class="fa fa-check"></i> This arrival has been
                                                            verified and is locked from reverting.
                                                        </div>
                                                    </div>
                                                @endif
                                            @else
                                                <div class="col-12">
                                                    <div class="alert bg-light-warning">
                                                        <i class="fa fa-exclamation-triangle"></i> Freight not found
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                        <div class="col-md-6">
                            <!-- <form action="{{ route('sampling-monitoring.update', $arrivalSamplingRequest->id) }}" method="POST"
                    id="ajaxSubmit" autocomplete="off">
                    @csrf
                    @method('PUT') -->
                            <input type="hidden" value="{{ $arrivalSamplingRequest->id }}"
                                name="arrivalSamplingRequestid">
                            <div class="card">
                                <div class="card-content">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-xs-6 col-sm-6 col-md-6 full">
                                                <div class="form-group">
                                                    <label class="d-block">Sauda Type:</label>
                                                    @php
                                                        $isDisabled = false;

                                                    @endphp

                                                    @if ($isDisabled)
                                                        <input type="hidden" name="sauda_type_id"
                                                            value="{{ optional($arrivalSamplingRequest->arrivalTicket)->sauda_type_id ?? '' }}">
                                                        <select disabled class="form-control w-100 select2">
                                                    @else
                                                            <select name="sauda_type_id" id="sauda_type_id"
                                                                class="form-control w-100 select2">
                                                        @endif
                                                            <option value="">Select Sauda Type</option>
                                                            @foreach ($saudaTypes as $saudaType)
                                                                <option
                                                                    @selected(optional($arrivalSamplingRequest->arrivalTicket)->sauda_type_id == $saudaType->id)
                                                                    value="{{ $saudaType->id }}">
                                                                    {{ $saudaType->name }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                </div>
                                            </div>
                                            <div class="col-xs-6 col-sm-6 col-md-6 full">
                                                <div class="form-group ">
                                                    <label>Status:</label>
                                                    <!-- @if (in_array($arrivalSamplingRequest->approved_status, ['approved', 'resampling', 'rejected']))
                                                        <input type="hidden" name="stage_status"
                                                            value="{{ $arrivalSamplingRequest->approved_status }}">
                                                    @endif -->
                                                    @if($arrivalSamplingRequest->sampling_type == 'initial' && $arrivalSamplingRequest->approved_status == 'approved' && $arrivalTicket->location_transfer_status == 'transfered')

                                                        <input type="hidden" name="stage_status"
                                                            value="{{ $arrivalSamplingRequest->approved_status }}">
                                                    @endif
                                                    <select @if($arrivalSamplingRequest->sampling_type == 'initial' && $arrivalSamplingRequest->approved_status == 'approved' && $arrivalTicket->location_transfer_status == 'transfered') disabled
                                                    @elseif($arrivalSamplingRequest->sampling_type == 'inner' && $arrivalTicket->document_approval_status != null) disabled @endif
                                                        name="{{ in_array($arrivalSamplingRequest->approved_status, ['approved', 'resampling', 'rejected']) ? 'stage_status' : 'stage_status' }}"
                                                        id="stage_status" class="form-control select2" <option value=""
                                                        hidden>Choose Status</option>
                                                        <option {{ $arrivalSamplingRequest->approved_status == 'approved' ? 'selected' : '' }} value="approved">
                                                            Approved</option>
                                                        <!-- <option {{ $arrivalSamplingRequest->approved_status == 'resampling' ? 'selected' : '' }} value="resampling">Request Resampling
                                                        </option> -->
                                                        <option {{ $arrivalSamplingRequest->approved_status == 'rejected' ? 'selected' : '' }} value="rejected">
                                                            Rejected</option>
                                                    </select>
                                                    @if($arrivalSamplingRequest->sampling_type == 'initial' && $arrivalSamplingRequest->approved_status == 'approved' && $arrivalTicket->location_transfer_status == 'transfered')
                                                        <div class="col-12 alert bg-light-danger">To make changes in status,
                                                            you must revert the ticket back up to the location transfer
                                                            step.</div>
                                                    @elseif($arrivalSamplingRequest->sampling_type == 'inner' && $arrivalTicket->document_approval_status != null)
                                                        <div class="col-12 alert bg-light-danger">To make changes in
                                                            QC,
                                                            you must revert the ticket back up to the Half/Full Approved
                                                            step.</div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
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
                                                        <a class="nav-link" id="initial-{{ $index }}-tab" data-toggle="tab"
                                                            href="#initial-{{ $index }}" role="tab"
                                                            aria-controls="initial-{{ $index }}" aria-selected="false">
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
                                                            $statusLabel =
                                                                $innerData['request']->is_re_sampling == 'yes'
                                                                ? 'Inner Resampling QC'
                                                                : 'Inner QC';
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
                                                    <a class="nav-link" id="inner-{{ $index }}-tab" data-toggle="tab"
                                                        href="#inner-{{ $index }}" role="tab"
                                                        aria-controls="inner-{{ $index }}" aria-selected="false">
                                                        <div>{{ $statusLabel }}</div>
                                                        <small
                                                            class="text-muted">{{ $innerData['request']->created_at->format('M d, Y h:i A') }}</small>
                                                    </a>
                                                </li>
                                            @endforeach

                                            @if ($arrivalSamplingRequest)
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
                                            @endif
                                        </ul>

                                        @if (empty($initialRequestsData) && empty($innerRequestsData) && !$arrivalSamplingRequest)
                                            <div class="alert bg-light-warning">
                                                <i class="fa fa-exclamation-triangle"></i> No arrival slip created yet -
                                                ticket is in progress
                                            </div>
                                        @else
                                            <div class="tab-content" id="qcChecklistTabsContent">
                                                @if (!empty($initialRequestsData))
                                                    @foreach ($initialRequestsData as $index => $initialData)
                                                        @php
                                                            $suggestedInitialValue = 0;
                                                            $suggestedInitialKgs = 0;
                                                        @endphp
                                                        <div class="tab-pane fade" id="initial-{{ $index }}" role="tabpanel"
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
                                                                                optional($arrivalSamplingRequest->arrivalTicket)->qc_product,
                                                                                $slab->checklist_value,
                                                                            );
                                                                            $deductionValue =
                                                                                $initialData['request']->is_lumpsum_deduction == 1
                                                                                ? 0
                                                                                : $slab->applied_deduction ?? 0;
                                                                            $suggestedDeductionType =
                                                                                $getDeductionSuggestion->deduction_type ?? 'amount';

                                                                            $suggestedDeductionType == 'amount'
                                                                                ? ($suggestedInitialValue +=
                                                                                    $getDeductionSuggestion->deduction_value ?? 0)
                                                                                : ($suggestedInitialKgs +=
                                                                                    $getDeductionSuggestion->deduction_value ?? 0);

                                                                            if ($index > 0) {
                                                                                foreach (
                                                                                    $initialRequestsData[$index - 1]['results']
                                                                                    as $prevSlab
                                                                                ) {
                                                                                    if ($prevSlab->slabType->id == $slab->slabType->id) {
                                                                                        $previousChecklistValue = $prevSlab->checklist_value;
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
                                                                                        value="{{ $slab->checklist_value }}" placeholder="%"
                                                                                        disabled>
                                                                                    <div class="input-group-append">
                                                                                        <span
                                                                                            class="input-group-text text-sm">{{ $slab->slabType->qc_symbol }}</span>
                                                                                    </div>
                                                                                </div>
                                                                                @if ($previousChecklistValue !== null)
                                                                                    <span class="checklist-value-comparison">
                                                                                        Previous:
                                                                                        {{ $previousChecklistValue }}
                                                                                    </span>
                                                                                @endif
                                                                            </div>
                                                                            <div class="col-md-3 Suggested">
                                                                                <div class="input-group mb-0">
                                                                                    <input type="text" class="form-control"
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
                                                                                    <input type="text" class="form-control bg-white"
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
                                                                    <div class="alert bg-light-warning">No Initial Slabs Found
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
                                                                        <div class="form-group row ">
                                                                            <label class="label-control font-weight-bold col-md-4"
                                                                                data-default-value="{{ $defaultValue }}">{{ $slab->qcParam->name }}</label>
                                                                            <div
                                                                                class="QcResult {{ checkIfNameExists($slab->qcParam->name) ? 'col-md-8' : 'col-md-6' }}">
                                                                                @if ($slab->qcParam->type == 'dropdown')
                                                                                    <input type="text" readonly
                                                                                        class="form-control {{ $compulsaryClass }}"
                                                                                        value="{{ $slab->compulsory_checklist_value }}"
                                                                                        disabled>
                                                                                @else
                                                                                    <textarea readonly
                                                                                        class="form-control {{ $compulsaryClass }}"
                                                                                        disabled>{{ $slab->compulsory_checklist_value }}</textarea>
                                                                                @endif
                                                                            </div>
                                                                            @if (!checkIfNameExists($slab->qcParam->name))
                                                                                <div class="col-md-2 QcResult">
                                                                                    <input type="text" class="form-control bg-white"
                                                                                        value="{{ $slab->applied_deduction ?? 0 }}" disabled>
                                                                                </div>
                                                                            @endif
                                                                        </div>
                                                                    @endforeach
                                                                @else
                                                                    <div class="alert bg-light-warning">No Initial Compulsory
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
                                                                                @checked($initialData['request']->is_lumpsum_deduction == 1)
                                                                                disabled>
                                                                            <label class="custom-control-label"
                                                                                for="lumpsum-toggle-initial-{{ $index }}"></label>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col {{ $index == 0 ? '' : 'd-none' }}">
                                                                        <div class="input-group mb-1">
                                                                            <input type="text"
                                                                                id="suggessions-sum-initial-{{ $index }}"
                                                                                class="form-control" name="suggessions_sum_initial"
                                                                                disabled value="{{ $suggestedInitialValue ?? 0 }}"
                                                                                placeholder="Suggested Sum">

                                                                            <div class="input-group-append">
                                                                                <span class="input-group-text text-sm">Rs.</span>
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
                                                                                <span class="input-group-text text-sm">KG's</span>
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
                                                                                <span class="input-group-text text-sm">Rs.</span>
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
                                                                                <span class="input-group-text text-sm">KG's</span>
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
                                                                            <input type="checkbox" name="decision_making_initial"
                                                                                class="custom-control-input"
                                                                                id="decision_making_initial_{{ $index }}"
                                                                                @checked($initialData['request']->decision_making == 1)
                                                                                disabled>
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
                                                                            $lastInitialData =
                                                                                $initialRequestsData[count($initialRequestsData) - 1];
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
                                                                                    Previous:
                                                                                    {{ $previousChecklistValue }}
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
                                                                <div class="alert bg-light-warning">No Slabs Found</div>
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
                                                                                <input type="text"
                                                                                    class="form-control {{ $compulsaryClass }}"
                                                                                    value="{{ $slab->compulsory_checklist_value }}"
                                                                                    readonly>
                                                                            @else
                                                                                <textarea class="form-control {{ $compulsaryClass }}"
                                                                                    readonly>{{ $slab->compulsory_checklist_value }}</textarea>
                                                                            @endif
                                                                        </div>
                                                                        @if (!checkIfNameExists($slab->qcParam->name))
                                                                            <div class="col-md-2 QcResult">
                                                                                <input type="text" class="form-control  " readonly
                                                                                    value="{{ $slab->applied_deduction }}">
                                                                            </div>
                                                                        @endif
                                                                    </div>
                                                                @endforeach
                                                            @else
                                                                <div class="alert bg-light-warning">No Compulsory Slabs Found
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
                                                                            disabled
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
                                                                <label class="col-md-4 label-control font-weight-bold">Decision
                                                                    Making on Avg.</label>
                                                                <div class="col-md-3">
                                                                    <div class="custom-control custom-switch">
                                                                        <input type="checkbox" class="custom-control-input"
                                                                            disabled
                                                                            @checked($innerData['request']->decision_making == 1)>
                                                                        <label class="custom-control-label"></label>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach

                                                @if ($arrivalSamplingRequest)

                                                    <div class="tab-pane fade show active" id="current-inner" role="tabpanel"
                                                        aria-labelledby="current-inner-tab">
                                                        @if($arrivalSamplingRequest->is_done == 'no')
                                                            <div class="alert bg-light-danger row w-100 mx-auto align-items-center">
                                                                <div class="col-xs-12 col-sm-8 col-md-8">

                                                                    Sampling Request Generated --
                                                                    operation in progress
                                                                </div>
                                                                @if($arrivalSamplingRequest->sampling_type == 'inner')
                                                                    <div class="col-xs-12 col-sm-6 col-md-4 text-right">
                                                                        <div class="form-group mb-0">

                                                                            <input type="submit" value="Revert QC Request"
                                                                                name="qc_request_revert" class="btn btn-danger"
                                                                                onclick="return confirm('Are you sure you want to revert Qc?')" />

                                                                        </div>
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        @else
                                                            <div class="currntbox">
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
                                                                                    ($previousValue ?? ($slab->checklist_value ?? 0)) -
                                                                                    ($slab->relief_deduction ?? 0);

                                                                                $getDeductionSuggestion = $arrivalSamplingRequest->arrivalTicket
                                                                                    ? getDeductionSuggestion(
                                                                                        $slab->slabType->id,
                                                                                        $arrivalSamplingRequest->arrivalTicket->qc_product,
                                                                                        $displayValue,
                                                                                    )
                                                                                    : (object) [
                                                                                        'deduction_type' => 'amount',
                                                                                        'deduction_value' => 0,
                                                                                    ];

                                                                                $previousDeduction = null;
                                                                                if (
                                                                                    ($slab->applied_deduction === null ||
                                                                                        $slab->applied_deduction == 0) &&
                                                                                    $previousInnerRequest
                                                                                ) {
                                                                                    foreach ($previousInnerRequest['results'] as $prevSlab) {
                                                                                        if ($prevSlab->slabType->id == $slab->slabType->id) {
                                                                                            $previousDeduction = $prevSlab->applied_deduction;
                                                                                            break;
                                                                                        }
                                                                                    }
                                                                                }

                                                                                $innerDeductionValue = $isLumpSumEnabledInTicket
                                                                                    ? 0
                                                                                    : $previousDeduction ??
                                                                                    ($slab->applied_deduction ??
                                                                                        ($valuesOfInitialSlabs[$slab->slabType->id] ?? 0));

                                                                                $suggestedDeductionType =
                                                                                    $getDeductionSuggestion->deduction_type ?? 'amount';

                                                                                if ($suggestedDeductionType == 'amount') {
                                                                                    $suggestedValue += $getDeductionSuggestion->deduction_value ?? 0;
                                                                                } else {
                                                                                    $suggestedValueKgs += $getDeductionSuggestion->deduction_value ?? 0;
                                                                                }

                                                                                $previousChecklistValue = null;
                                                                                if (!empty($innerRequestsData)) {
                                                                                    $lastInnerRequestData =
                                                                                        $innerRequestsData[count($innerRequestsData) - 1];
                                                                                    foreach ($lastInnerRequestData['results'] as $lastSlab) {
                                                                                        if ($lastSlab->slabType->id == $slab->slabType->id) {
                                                                                            $previousChecklistValue = $lastSlab->checklist_value;
                                                                                            break;
                                                                                        }
                                                                                    }
                                                                                }

                                                                                if ($previousChecklistValue === null && !empty($initialRequestsData)) {
                                                                                    $lastInitialData =
                                                                                        $initialRequestsData[count($initialRequestsData) - 1];
                                                                                    foreach ($lastInitialData['results'] as $initialSlab) {
                                                                                        if ($initialSlab->slabType->id == $slab->slabType->id) {
                                                                                            $previousChecklistValue = $initialSlab->checklist_value;
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
                                                                                            Previous:
                                                                                            {{ $previousChecklistValue }}
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
                                                                                            data-checklist="{{ $displayValue }}" readonly {{ $isLumpSumEnabledInTicket ? 'readonly' : '' }}>
                                                                                        <div class="input-group-append">
                                                                                            <span
                                                                                                class="input-group-text text-sm">{{ SLAB_TYPES_CALCULATED_ON[$slab->slabType->calculation_base_type ?? 1] }}</span>
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        @endforeach
                                                                    @else
                                                                        <div class="alert bg-light-warning">No Slabs Found</div>
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
                                                                                    $previousCompValue ?? $slab->compulsory_checklist_value;
                                                                                $previousCompDeduction = null;
                                                                                if (
                                                                                    ($slab->applied_deduction === null ||
                                                                                        $slab->applied_deduction == 0) &&
                                                                                    $previousInnerRequest
                                                                                ) {
                                                                                    foreach ($previousInnerRequest['compulsuryResults'] as $prevComp) {
                                                                                        if ($prevComp->qcParam->id == $slab->qcParam->id) {
                                                                                            $previousCompDeduction = $prevComp->applied_deduction;
                                                                                            break;
                                                                                        }
                                                                                    }
                                                                                }
                                                                                $compDeductionValue =
                                                                                    $previousCompDeduction ?? ($slab->applied_deduction ?? 0);

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
                                                                                            data-default-value="{{ $defaultValue }}" readonly>
                                                                                    @else
                                                                                        <textarea class="form-control {{ $compulsaryClass }}"
                                                                                            name="compulsory_checklist_value[]"
                                                                                            readonly>{{ $displayCompValue }}</textarea>
                                                                                    @endif
                                                                                </div>
                                                                                @if (!checkIfNameExists($slab->qcParam->name))
                                                                                    <div class="col-md-2 QcResult">
                                                                                        <div class="input-group mb-0">
                                                                                            <input type="text" id="inp-{{ $slab->qcParam->id }}"
                                                                                                class="form-control bg-white deduction-field"
                                                                                                name="compulsory_aapplied_deduction[]"
                                                                                                value="{{ $compDeductionValue }}"
                                                                                                placeholder="Deduction"
                                                                                                data-slab-id="{{ $slab->qcParam->id }}"
                                                                                                data-calculated-on="{{ $slab->qcParam->calculation_base_type }}"
                                                                                                data-checklist="{{ $displayCompValue }}" {{ $isLumpSumEnabledInTicket ? 'readonly' : '' }}>
                                                                                            <div class="input-group-append">
                                                                                                <span
                                                                                                    class="input-group-text text-sm">{{ SLAB_TYPES_CALCULATED_ON[$slab->qcParam->calculation_base_type ?? 1] }}</span>
                                                                                            </div>
                                                                                        </div>
                                                                                    </div>
                                                                                @else
                                                                                    <input type="hidden" name="compulsory_aapplied_deduction[]"
                                                                                        value="0">
                                                                                @endif
                                                                            </div>
                                                                        @endforeach
                                                                    @else
                                                                        <div class="alert bg-light-warning">No Compulsory Slabs Found
                                                                        </div>
                                                                    @endif
                                                                </div>

                                                                <div class="striped-rows mt-3">
                                                                    <div class="form-group row">
                                                                        <label class="col-md-4 label-control font-weight-bold"
                                                                            for="lumpsum-toggle">Apply
                                                                            Lumpsum
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
                                                                                    class="form-control" name="suggessions_sum"
                                                                                    disabled value="{{ $suggestedValue }}"
                                                                                    placeholder="Suggested Sum">
                                                                                <div class="input-group-append">
                                                                                    <span
                                                                                        class="input-group-text text-sm">Rs.</span>
                                                                                </div>
                                                                            </div>
                                                                            <div class="input-group mb-0">
                                                                                <input type="text" id="suggessions-sum"
                                                                                    class="form-control" name="suggessions_sum"
                                                                                    disabled value="{{ $suggestedValueKgs }}"
                                                                                    placeholder="Suggested Sum">
                                                                                <div class="input-group-append">
                                                                                    <span
                                                                                        class="input-group-text text-sm">Kgs.</span>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        <div class="col">
                                                                            <div class="input-group mb-2">
                                                                                <input type="text" id="lumpsum-value"
                                                                                    class="form-control" name="lumpsum_deduction" {{ $isLumpSumEnabledInTicket ? '' : 'readonly' }}
                                                                                    {{--
                                                                                    value="{{ $arrivalSamplingRequest->lumpsum_deduction ?? ($rupeeLumpSum ?? 0) }}"
                                                                                    --}} value="{{ $rupeeLumpSum ?? 0 }}"
                                                                                    placeholder="Lumpsum Deduction">
                                                                                <div class="input-group-append">
                                                                                    <span
                                                                                        class="input-group-text text-sm">Rs.</span>
                                                                                </div>
                                                                            </div>
                                                                            <div class="input-group mb-0">
                                                                                <input type="text" id="lumpsum-kgs-value"
                                                                                    class="form-control"
                                                                                    name="lumpsum_deduction_kgs" {{ $isLumpSumEnabledInTicket ? '' : 'readonly' }}
                                                                                    {{--
                                                                                    value="{{ $arrivalSamplingRequest->lumpsum_deduction_kgs ?? ($kgLumpSum ?? 0) }}"
                                                                                    --}} value="{{ $kgLumpSum ?? 0 }}"
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
                                                                            for="decision_making">Decision
                                                                            Making
                                                                            on Avg.</label>
                                                                        <div class="col-md-3">
                                                                            <div class="custom-control custom-switch">
                                                                                <input type="checkbox" name="decision_making"
                                                                                    class="custom-control-input"
                                                                                    id="decision_making" @checked($isDecisionMaking)
                                                                                    disabled @disabled($isDecisionMakingDisabled)>
                                                                                <label class="custom-control-label"
                                                                                    for="decision_making"></label>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="row mt-2 w-100 mx-auto d-one">


                                                                    @if($arrivalSamplingRequest->is_done == 'yes' && $arrivalSamplingRequest->approved_status == 'pending')
                                                                        <div class="col-12 alert bg-light-danger">Sampling has been
                                                                            completed
                                                                            and
                                                                            is awaiting purchaser approval. Updates are not allowed at
                                                                            this
                                                                            stage.</div>

                                                                    @elseif($arrivalTicket->bilty_return_confirmation == 1)
                                                                        <div class="col-12 alert bg-light-danger">Editing is disabled
                                                                            because
                                                                            the Bilty return has already been confirmed.</div>


                                                                    @else

                                                                        <div class="col-xs-12 col-sm-12 col-md-12 text-right">
                                                                            <div class="form-group">

                                                                                <input type="submit" value="Save QC"
                                                                                    name="last_qc_submit"
                                                                                    @if($arrivalTicket->freight_status == 'completed')
                                                                                        disabled data-toggle="tooltip"
                                                                                        title="You cannot update information because freight is already created"
                                                                                    @endif class="btn btn-primary" />

                                                                            </div>
                                                                        </div>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        @endif
                                                    </div>

                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
        </form>
        @if (!isset($source) || $source != 'contract')


                </section>
            </div>
        @endif
<script>

    function calculateNetWeight() {
        const firstWeight = parseFloat($('#first_weight').val()) || 0;
        const secondWeight = parseFloat($('#second_weight').val()) || 0;
        const netWeight = secondWeight - firstWeight;

        $('#net_weight').val(netWeight || 0);

        if (firstWeight && secondWeight) {
            if (netWeight < 0) {
                $('#net_weight').addClass('is-invalid');
                $('#net_weight').siblings('.error-message').show();
            } else {
                $('#net_weight').removeClass('is-invalid');
                $('#net_weight').siblings('.error-message').hide();
            }
        }
    }



    function calculateSampleMoney() {
        let truckTypeSelect = $('[name="truck_type_id"]');
        let sampleMoney = truckTypeSelect.find(':selected').data('samplemoney') || 0;

        let holidayType = $('[name="sample_money_type"]').val();

        if (holidayType === 'double') {
            sampleMoney = sampleMoney * 2;
        }

        if (holidayType === 'n/a') {
            sampleMoney = 0;
        }

        $('input[name="sample_money"]').val(sampleMoney || 0);
    }

    function calculateBags() {
        let bags = parseFloat($('[name="bags"]').val()) || 0;
        let total = parseFloat($('[name="total_bags"]').val()) || 0;

        let rejection = bags - total;

        $('[name="total_rejection"]').val(rejection);
    }
    $(document).ready(function () {

        initializeDynamicSelect2('#miller_id', 'millers', 'name', 'name', true, false);
        initializeDynamicSelect2('#station_id', 'stations', 'name', 'name', true, false);


        $('#first_weight, #second_weight').on('input', function () {
            console.log('input changed');
            calculateNetWeight();
        });

        calculateSampleMoney();

        $(document).on('change', '[name="truck_type_id"]', calculateSampleMoney);

        $(document).on('change', '[name="sample_money_type"]', calculateSampleMoney);

        function toggleBagFields() {
            let selectedBagType = $('#bag_type_id option:selected').text().toLowerCase();
            if (selectedBagType.includes('bulk')) {
                $('.filling-bags-field, .bag-condition-field, .bag-packing-field').hide();
            } else {
                $('.filling-bags-field, .bag-condition-field, .bag-packing-field').show();
            }
        }

        toggleBagFields();

        $('#bag_type_id').change(function () {
            toggleBagFields();
        });
    });
</script>



<script>
    function calculateTotal() {
        let total = 0;
        let totalKgs = 0;

        $('.deduction-field').each(function () {
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
                            if (isTiered === 1) {
                                let applicableAmount = 0;
                                if (isNaN(to) || val >= to) {
                                    applicableAmount = to - from + 1;
                                } else {
                                    applicableAmount = (val - from) + 1;
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

    if (
            {{ $arrivalSamplingRequest->arrivalTicket->is_lumpsum_deduction == 0 ? 'true' : 'false' }}
        ) {
        // calculateTotal();
    }

    if ({{ $arrivalSamplingRequest->is_lumpsum_deduction == 1 ? 'true' : 'false' }}) {

        $('#lumpsum-value').val({{ $arrivalSamplingRequest->lumpsum_deduction ?? 0 }}.toFixed(2));
        $('#lumpsum-kgs-value').val({{ $arrivalSamplingRequest->lumpsum_deduction_kgs ?? 0 }}.toFixed(2));
    }

    $('.deduction-field').on('input', calculateTotal);

    $('#lumpsum-toggle').change(function () {
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

    $('#arrival_sampling_request_id').change(function () {
        var samplingRequestId = $(this).val();

        if (samplingRequestId) {
            $.ajax({
                url: '{{ route('getSlabsByProduct') }}',
                type: 'GET',
                data: {
                    sampling_request_id: samplingRequestId
                },
                dataType: 'json',
                beforeSend: function () {
                    Swal.fire({
                        title: "Processing...",
                        text: "Please wait while fetching slabs.",
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                },
                success: function (response) {
                    Swal.close();
                    if (response.success) {
                        // Append the rendered HTML to a container element
                        $('#slabsContainer').html(response.html);
                    } else {
                        Swal.fire("No Data", "No slabs found for this product.",
                            "info");
                    }
                },
                error: function () {
                    Swal.close();
                    Swal.fire("Error", "Something went wrong. Please try again.",
                        "error");
                }
            });
        }
    });

</script>

<script>
    $(document).ready(function () {



        $(document).on('change', '[name="arrival_purchase_order_id"]', function () {
            // First reset all form fields
            resetAllFormFields();

            var selectedOption = $(this).find('option:selected');
            if (selectedOption.val() === "") {
                return; // If N/A is selected, just keep all fields reset
            }

            // Now populate fields from the selected contract
            var supplierId = selectedOption.data('supplier-id');
            var productId = selectedOption.data('product-id');
            var createdById = selectedOption.data('created-by-id');
            var saudaTypeName = selectedOption.data('sauda-type-name');
            var saudaTypeId = selectedOption.data('sauda-type-id');
            var createdAt = selectedOption.data('created-at');

            // Set product selection
            if (productId) {
                $('#product_id').val(productId).trigger('change');
                $('#product_id_hidden').val(productId);
                $('#product_id').prop('disabled', true).addClass('disabled-field');
            }

            // Set broker/supplier selection
            if (supplierId) {
                $('#broker_name').val(supplierId).trigger('change');
                $('#accounts_of').val(supplierId).trigger('change');
                $('#accounts_of_hidden').val(supplierId);
                $('#accounts_of').prop('disabled', true).addClass('disabled-field');
            }

            // Set decision maker selection
            if (createdById) {
                $('#decision_id').val(createdById).trigger('change');
            }

            console.log({
                saudaTypeName
            });

            // Set Sauda Type
            if (saudaTypeName) {
                $('#sauda_type_id').val(saudaTypeId).trigger('change');

                $('#sauda_type').val(saudaTypeName);
                // If you need to store the sauda_type_id, you would need to add it to the data attributes
                $('#sauda_type_id').val(saudaTypeId);
            }

            // Set loading date
            if (createdAt) {
                //  $('input[name="loading_date"]').val(createdAt.split(' ')[0]);
            }
        });

        function resetAllFormFields() {
            // Reset product fields
            // $('#product_id').val('').trigger('change');

            $('#product_id').prop('disabled', false).removeClass('disabled-field');
            $('#accounts_of').prop('disabled', false).removeClass('disabled-field');

        }

        var selectedOption1 = $('[name="arrival_purchase_order_id"]').find('option:selected');
        if (selectedOption1.val() === "") {
            return; // If N/A is selected, just keep all fields reset
        }
        $('#product_id').prop('disabled', true).addClass('disabled-field');
        $('#accounts_of').prop('disabled', true).addClass('disabled-field');

    });
</script>
@if (!isset($source) || $source != 'contract')
    @endsection
@endif