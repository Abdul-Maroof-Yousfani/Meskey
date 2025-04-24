@extends('management.layouts.master')
@section('title')
    Account
@endsection
@section('content')
    <div class="content-wrapper">

        <section id="extended">
            <div class="row w-100 mx-auto">
                <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6">
                    <h2 class="page-title"> Arrival Summary: {{ $arrivalTicket->unique_no }}</h2>
                </div>
                <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6 text-right">
                    <button onclick="openModal(this,'{{ route('account.create') }}','Add Ticket')" type="button"
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
                                                        value="{{ $arrivalTicket->station->name ?? 'N/A' }}" />
                                                </div>
                                            </div>
                                            <div class="col-xs-4 col-sm-4 col-md-4">
                                                <div class="form-group ">
                                                    <label>Truck Type:</label>
                                                    <select name="arrival_truck_type_id" id="arrival_truck_type_id" disabled
                                                        class="form-control select2 ">
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
                                                        value="{{ $arrivalTicket->truckType->sample_money ?? 0 }}"
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
            //filterationCommon(`{{ route('get.account') }}`)
        });
    </script>
@endsection
