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
                                                        value="{{ request('daterange', \Carbon\Carbon::now()->format('m/d/Y') . ' - ' . \Carbon\Carbon::now()->format('m/d/Y')) }}" />
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
                                        <div class="mt-2">
                                            <div class="form-group mb-0">
                                                <input type="submit" class="btn btn-primary" name="generatebtn" value="Generate">
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
                                            <th>Arrival Weight</th>
                                            <th>Truck #</th>
                                            <th>Amanat</th>
                                            @foreach ($product_slab_types as $slab)
                                                <th>Avg. {{ $slab->name }} </th>
                                            @endforeach
                                            {{-- <th>Avg. Broken</th>
                                            <th>Avg. Moisture</th>
                                            <th>Avg. Paddy</th>
                                            <th>Avg. Damage</th> --}}
                                            @foreach ($arrival_compulsory_qc_params as $compulsory_slab_type)
                                                <th>{{ $compulsory_slab_type->name }}</th>
                                            @endforeach
                                            {{-- <th>QC Advice</th>
                                            <th>QC Remarks</th>
                                            <th>Unloading Instruction</th> --}}
                                            <th>QC Analysis By</th>
                                            <th>Total Inner Samples</th>
                                            <th>Commodity</th>
                                            <th>Sauda Terms</th>
                                            <th>Status</th>
                                            <th>Tabaar Instructions</th>
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
                                            {{-- <th>Weighbridge Charges</th> --}}
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
                                            <th>Final QC Report</th>
                                            <th>Bilty</th>
                                            <th>Loading Weight</th>
                                            <th>Arrival Slip</th>
                                        </tr>
                                    </thead>
                                </table>
                                {{-- Empty icon: report page khulte hi load nahi hoti (Generate par aati hai), is liye
                                     yahan bhi wahi "No data" icon. Table ke bahar + sticky, taake chauri table me bhi
                                     screen ke beech dikhe. Generate par #filteredData ka poora content badal jata hai. --}}
                                <div class="text-center my-5" style="position: sticky; left: 0;">
                                    <svg width="64" height="41" viewBox="0 0 64 41" xmlns="http://www.w3.org/2000/svg">
                                        <g transform="translate(0 1)" fill="none" fill-rule="evenodd">
                                            <ellipse fill="#f5f5f5" cx="32" cy="33" rx="32" ry="7"></ellipse>
                                            <g fill-rule="nonzero" stroke="#d9d9d9">
                                                <path d="M55 12.76L44.854 1.258C44.367.474 43.656 0 42.907 0H21.093c-.749 0-1.46.474-1.947 1.257L9 12.761V22h46v-9.24z"></path>
                                                <path d="M41.613 15.931c0-1.605.994-2.93 2.227-2.931H55v18.137C55 33.26 53.68 35 52.05 35h-40.1C10.32 35 9 33.259 9 31.137V13h11.16c1.233 0 2.227 1.323 2.227 2.928v.022c0 1.605 1.005 2.901 2.237 2.901h14.752c1.232 0 2.237-1.308 2.237-2.913v-.007z" fill="#fafafa"></path>
                                            </g>
                                        </g>
                                    </svg>
                                    <p>No records found</p>
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

        $(document).ready(function () {

            const runFilter = filterationCommon_withbtn(
                `{{ route('reports.arrival.get.truck-detail') }}`
            );

            const fetchSuppliers =
                initializeDynamicDependentSelect2_withbutton(
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
                true
                );

            initializeDynamicSelect2_withbutton(
                '#sauda_type', 'sauda_types', 'name', 'id', true, false, true, true
            );


            $('#filterForm').on('submit', function (e) {

                e.preventDefault();

                // =====================================
                // ONLY BUTTON CLICK
                // =====================================

                // Filter/data request
                runFilter();

                // Dependent supplier request
                fetchSuppliers();
            });
            // runFilter();
            $(".selectWithoutAjax").select2();
        });



    </script>
@endsection
