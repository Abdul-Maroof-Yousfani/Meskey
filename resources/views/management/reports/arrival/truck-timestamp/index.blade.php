@extends('management.layouts.master')
@section('title')
    Truck Timestamp Report
@endsection
@section('content')
    <div class="content-wrapper">
        <section id="extended">
            <div class="row w-100 mx-auto">
                <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6">
                    <h2 class="page-title">
                        Truck Timestamp Report
                    </h2>
                </div>
                <div class="col-md-6 d-flex align-items-end justify-content-end">
                    <div class="form-group mb-0">
                        <button class="btn btn-secondary" onclick="exportToExcel('exportableTable','TruckTimestampReport')">
                            <i class="fa fa-file-excel-o mr-2"></i> Export to Excel
                        </button>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <form id="filterForm" class="form">
                                <div class="row">
                                    <div class="col-md-12 my-1">
                                        <div class="row justify-content-nd text">
                                            <div class="col-md-2">
                                                <div class="form-group mb-0">
                                                    <label>Location:</label>
                                                    <select name="company_location_id[]" id="cmpany_location" {{ count($locations) == 1 ? 'disabled' : 'multiple' }} class="form-control selectWithoutAjax">
                                                        <option value="">Location</option>
                                                        @foreach ($locations as $location)
                                                            <option value="{{ $location->id }}"
                                                                {{ (is_array(request('company_location_id')) && in_array($location->id, request('company_location_id'))) || count($locations) == 1 ? 'selected' : '' }}>
                                                                {{ $location->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="form-group mb-0">
                                                    <label>Date:</label>
                                                    <input type="text" name="daterange" class="form-control"
                                                        value="{{ request('daterange', \Carbon\Carbon::now()->subMonth()->format('m/d/Y') . ' - ' . \Carbon\Carbon::now()->format('m/d/Y')) }}" />
                                                </div>
                                            </div>

                                            <div class="col-md-2">
                                                <div class="form-group mb-0">
                                                    <label>Arrival Ticket No:</label>
                                                    <input type="text" class="form-control" name="arrival_ticket_no"
                                                        placeholder="Arrival Ticket No"
                                                        value="{{ request('arrival_ticket_no', '') }}">
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="form-group mb-0">
                                                    <label>GRN No:</label>
                                                    <input type="text" class="form-control" name="grn_no"
                                                        placeholder="GRN No" value="{{ request('grn_no', '') }}">
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="form-group mb-0">
                                                    <label>Inner Sample:</label>
                                                    <select name="inner_sample" id="inner_sample" class="form-control selectWithoutAjax">
                                                        <option value="1" {{ request('inner_sample', '1') == '1' ? 'selected' : '' }}>1st</option>
                                                        <option value="2" {{ request('inner_sample') == '2' ? 'selected' : '' }}>2nd</option>
                                                        <option value="3" {{ request('inner_sample') == '3' ? 'selected' : '' }}>3rd</option>
                                                        <option value="all" {{ request('inner_sample') == 'all' ? 'selected' : '' }}>All</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row justify-content-nd text mt-2">
                                            <div class="col-md-2">
                                                <div class="form-group mb-0">
                                                    <label>Commodity:</label>
                                                    <select name="commodity_id[]" id="commodity_id" multiple
                                                        class="form-control selectWithoutAjax">
                                                        <option value="">Select Commodity</option>
                                                        @foreach ($commodities as $commodity)
                                                            <option value="{{ $commodity->id }}"
                                                                {{ is_array(request('commodity_id')) && in_array($commodity->id, request('commodity_id')) ? 'selected' : '' }}>
                                                                {{ $commodity->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="form-group mb-0">
                                                    <label>Accounts Of:</label>
                                                    <select name="supplier_id" id="supplier_id_f"
                                                        class="form-control select2">
                                                        <option value="">Accounts Of</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="form-group mb-0">
                                                    <label>Miller:</label>
                                                    <select name="miller_id" id="miller_id"
                                                        class="form-control selectWithoutAjax">
                                                        <option value="">Select Miller</option>
                                                        @foreach ($millers as $miller)
                                                            <option value="{{ $miller->id }}"
                                                                {{ request('miller_id') == $miller->id ? 'selected' : '' }}>
                                                                {{ $miller->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="form-group mb-0">
                                                    <label>Sauda Type:</label>
                                                    <select name="sauda_type_id" id="sauda_type"
                                                        class="form-control select2">
                                                        <option value="">Sauda Type Name</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="col-md-1">
                                                <div class="form-group mb-0">
                                                    <label>Truck No:</label>
                                                    <input type="text" class="form-control" name="truck_no"
                                                        placeholder="Truck No" value="{{ request('truck_no', '') }}">
                                                </div>
                                            </div>
                                            <div class="col-md-1">
                                                <div class="form-group mb-0">
                                                    <label>Bilty No:</label>
                                                    <input type="text" class="form-control" name="bilty_no"
                                                        placeholder="Bilty No" value="{{ request('bilty_no', '') }}">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row justify-content-nd text mt-2">
                                            <input type="hidden" name="page" value="{{ request('page', 1) }}">
                                            <input type="hidden" name="per_page" value="{{ request('per_page', 25) }}">
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="card-content">
                            <div class="card-body table-responsive" id="filteredData">

                                <table class="table m-0" id="exportableTable">
                                    <thead>
                                        <tr>
                                            <th>Ticket #</th>
                                            <th>Gate Entry Time</th>
                                            <th>Entry By</th>
                                            <th>Loading Date</th>
                                            <th>Total Inner Samples</th>
                                            <th>Total Resamples</th>
                                            <th>Party Ref. No</th>
                                            <th>Yeild</th>
                                            <th>Location Time</th>
                                            <th>Location By</th>
                                            <th>1st QC Time</th>
                                            <th>1st QC By</th>
                                            <th>1st Tabaar Decision Time</th>
                                            <th>1st Tabaar Decision By</th>
                                            <th>1st Weight Time</th>
                                            <th>1st Weight By</th>
                                            <th>1st Inner QC Sample Request Time</th>
                                            <th>1st Inner QC Sample Request By</th>
                                            <th>1st Inner QC Sample Time</th>
                                            <th>1st Inner QC Sample By</th>
                                            <th>2nd Inner QC Sample Request Time</th>
                                            <th>2nd Inner QC Sample Request By</th>
                                            <th>2nd Inner QC Sample Time</th>
                                            <th>2nd Inner QC Sample By</th>
                                            <th>3rd Inner QC Sample Request Time</th>
                                            <th>3rd Inner QC Sample Request By</th>
                                            <th>3rd Inner QC Sample Time</th>
                                            <th>3rd Inner QC Sample By</th>
                                            <th>2nd Tabaar Decision Time</th>
                                            <th>2nd Tabaar Decision By</th>
                                            <th>3rd Tabaar Decision Time</th>
                                            <th>3rd Tabaar Decision By</th>
                                            <th>4th Tabaar Decision Time</th>
                                            <th>4th Tabaar Decision By</th>
                                            <th>Full Reject Time</th>
                                            <th>Full Reject By</th>
                                            <th>Half Reject Time</th>
                                            <th>Half Reject By</th>
                                            <th>Confirm Unloading Time</th>
                                            <th>Confirm Unloading By</th>
                                            <th>2nd Weight Time</th>
                                            <th>2nd Weight By</th>
                                            <th>Accounts Entry Time</th>
                                            <th>Accounts Entry By</th>
                                            <th>Bilty Return Time</th>
                                            <th>Bilty Return By</th>
                                            <th>HO Confirm Time</th>
                                            <th>HO Confirm By</th>
                                            <th>Admin Edit Time</th>
                                            <th>Admin Edit By</th>
                                            <th>Status</th>
                                            <th>Completion</th>
                                            <th>Bilty</th>
                                            <th>Loading Weight</th>
                                            <th>Arrival Slip</th>
                                            <th>View Complete Details</th>
                                        </tr>
                                    </thead>
                                </table>
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
            filterationCommon(
                `{{ route('reports.arrival.get.truck-timestamp') }}`
            )

            initializeDynamicSelect2('#sauda_type', 'sauda_types', 'name', 'id', true, false, true, true);

            initializeDynamicDependentSelect2(
                '#company_location',
                '#supplier_id_f',
                'company_locations',
                'name',
                'id',
                'suppliers',
                'company_location_ids',
                'name',
                true,
                false,
                true,
                true,
            );
        });
    </script>
@endsection
