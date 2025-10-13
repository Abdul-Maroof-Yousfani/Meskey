@extends('management.layouts.master')
@section('title')
    Arrival Report
@endsection
@section('content')
    <div class="content-wrapper">
        <section id="extended">
            <div class="row w-100 mx-auto">
                <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6">
                    <h2 class="page-title">
                        Arrival Report
                    </h2>
                </div>
                <div class="col-md-6 d-flex align-items-end justify-content-end">
                                                <div class="form-group mb-0">
                                                    <button class="btn btn-secondary" onclick="exportToExcel('exportableTable','ArrivalReport')"><i class="fa fa-file-excel-o mr-2"></i> Export to Excel</button>

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
                                                    <select name="company_location_id" id="company_location"
                                                        class="form-control select2">
                                                        <option value="">Location</option>
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
                                            {{-- <div class="col-md-2">
                                                <div class="form-group mb-0">
                                                    <label>GRN No:</label>
                                                    <input type="text" class="form-control" name="grn_no"
                                                        placeholder="GRN No" value="{{ request('grn_no', '') }}">
                                                </div>
                                            </div> --}}
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
                                            <th>Status</th>
                                        
                                            <th>Miller</th>
                                            <th>Broker</th>
                                            <th>A/c Of</th>
                                            <th>Truck #</th>
                                            <th>Commodity</th>
                                            <th>Party Ref.#</th>
                                            <th>Status</th>
                                            <th>Station</th>
                                            <th>Bilty #</th>
                                            <th>Loading Weight</th>
                                            <th>1st Weight</th>
                                            <th>2nd Weight</th>
                                            <th>Net Weight</th>
                                            <th>Wt. Diff.</th>
                                            {{-- <th>GRN #</th> --}}
                                            {{-- <th>Sauda Type</th>
                                            <th>Station</th> --}}
                                            <th>Bag Type</th>
                                            <th>Bag Condition</th>
                                            <th>Bag Packing</th>
                                            <th>No. Bag</th>
                                            @foreach (getTableData('product_slab_types') as $slab)
                                                <th>{{ $slab->name }}</th>
                                            @endforeach
                                            @foreach (getTableData('arrival_compulsory_qc_params') as $compulsory_slab_type)
                                                <th>{{ $compulsory_slab_type->name }}</th>
                                            @endforeach
                                            <th>Warehouse</th>
                                            <th>Gala</th>
                                            {{-- <th>Tabaar Remarks</th> --}}

                                            {{-- <th>Contract</th> --}}
                                            <th>Final QC Report</th>
                                            <th>Bilty</th>
                                            <th>Loading Weight</th>
                                            <th>Arrival Slip</th>
                                            {{-- <th>Action</th> --}}
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
                `{{ route('reports.arrival.get.arrival-history') }}`
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
