@extends('management.layouts.master')
@section('title')
    Truck Detail Report
@endsection
@section('content')
    <div class="content-wrapper">
        <section id="extended">
            <div class="row w-100 mx-auto">
                <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6">
                    <h2 class="page-title">
                        Truck Detail Report
                    </h2>
                </div>
                <div class="col-md-6 d-flex align-items-end justify-content-end">
                    <div class="form-group mb-0">
                        <button class="btn btn-secondary" onclick="exportToExcel('exportableTable','TruckDetailReport')">
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
                                                    <select name="company_location_id[]" id="cmpany_location" {{ count($locations) == 1 ? 'disabled' : 'multiple' }} class="form-control selectWithoutAjax" >
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
                                            <th>Entry Date</th>
                                            <th>Entry Time</th>
                                            <th>Entry By</th>
                                            <th>Broker</th>
                                            <th>Supplier</th>
                                            <th>Station</th>
                                            <th>Account of</th>
                                            <th>Decision</th>
                                            <th>Bilty #</th>
                                            <th>Truck Type</th>
                                            <th>Loading Date</th>
                                            <th>No of Bags (Loaded)</th>
                                            <th>Loaded Weight (KG)</th>
                                            <th>Truck #</th>
                                            <th>Amanat</th>
                                            <th>Avg. Broken</th>
                                            <th>Avg. Moisture</th>
                                            <th>Avg. Paddy</th>
                                            <th>Avg. Damage</th>
                                            <th>QC Advice</th>
                                            <th>QC Remarks</th>
                                            <th>Unloading Instruction</th>
                                            <th>QC Analysis By</th>
                                            <th>Total Inner Samples</th>
                                            <th>Commodity</th>
                                            <th>Sauda Terms</th>
                                            <th>Status</th>
                                            <th>Tabaar Instructions</th>
                                            <th>Broken Level</th>
                                            <th>Moisture Level</th>
                                            <th>Paddy Level</th>
                                            <th>Damage Level</th>
                                            <th>Under Milled Level</th>
                                            <th>Warehouse</th>
                                            <th>Galaa #</th>
                                            <th>Location Type</th>
                                            <th>1st Weight</th>
                                            <th>1st Weight Time</th>
                                            <th>2nd Weight</th>
                                            <th>2nd Weight Time</th>
                                            <th>Freight Charges</th>
                                            <th>Labor Charges</th>
                                            <th>Unpaid Labor Charges</th>
                                            <th>Other Charges (-)</th>
                                            <th>Kanta Charges</th>
                                            <th>Weighbridge Charges</th>
                                            <th>Full Reject</th>
                                            <th>Full Reject By</th>
                                            <th>Full Reject Time</th>
                                            <th>Full Reject Comments</th>
                                            <th>Half Reject</th>
                                            <th>Half Reject By</th>
                                            <th>Half Reject Time</th>
                                            <th>Half Reject Comments</th>
                                            <th>Confirm Unloading</th>
                                            <th>Confirm Unloading By</th>
                                            <th>Confirm Unloading Time</th>
                                            <th>Confirm Unloading Comments</th>
                                            <th>Bag Packing</th>
                                            <th>Bag Type</th>
                                            <th>Filling Bags</th>
                                            <th>Total Bags</th>
                                            <th>Completion</th>
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
                `{{ route('reports.arrival.get.truck-detail') }}`
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
