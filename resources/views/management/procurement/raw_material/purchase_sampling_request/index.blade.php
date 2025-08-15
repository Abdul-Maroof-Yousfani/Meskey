@extends('management.layouts.master')
@section('title')
    Purchase Sampling Request
@endsection
@section('content')
    <div class="content-wrapper">
        <section id="extended">
            <div class="row w-100 mx-auto">
                <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6">
                    <h2 class="page-title">Purchase Sampling Request</h2>
                </div>
                <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6 text-right">
                    <button
                        onclick="openModal(this,'{{ route('raw-material.purchase-sampling-request.create') }}','Create Purchase Sampling Request')"
                        type="button" class="btn btn-primary position-relative">
                        Create Request (Without Contract)
                    </button>
                </div>
            </div>
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <form id="filterForm" class="form">
                                <div class="row ">
                                    <div class="col-md-12 my-1 ">
                                        <div class="row justify-content-end text-right1">
                                            <div class="col-md-2">
                                                <div class="form-group">
                                                    <label>Date:</label>
                                                    <input type="text" name="daterange" class="form-control"
                                                        value="{{ request('daterange', \Carbon\Carbon::now()->subMonth()->format('m/d/Y') . ' - ' . \Carbon\Carbon::now()->format('m/d/Y')) }}" />
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="form-group ">
                                                    <label>Location:</label>
                                                    <select name="company_location_id" id="company_location"
                                                        class="form-control select2">
                                                        <option value="">Location</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="form-group ">
                                                    <label>Suppliers:</label>
                                                    <select name="supplier_id_filter" id="supplier_id_f"
                                                        class="form-control select2">
                                                        <option value="">Supplier</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <label for="customers" class="form-label">Commodity</label>
                                                <select name="product_id_filter" id="product_id_filter"
                                                    class="form-control">
                                                    <option value="">
                                                        Select Commodity</option>
                                                </select>
                                            </div>
                                            <div class="col-md-2">
                                                <label for="customers" class="form-label">Search</label>
                                                <input type="hidden" name="page" value="{{ request('page', 1) }}">
                                                <input type="hidden" name="per_page" value="{{ request('per_page', 25) }}">
                                                <input type="text" class="form-control" id="search"
                                                    placeholder="Search here" name="search"
                                                    value="{{ request('search', '') }}">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <div class="card-content">
                            <div class="card-body table-responsive" id="filteredData">
                                <table class="table m-0">
                                    <thead>
                                        <tr>
                                            <th class="col-sm-2">Contract Number</th>
                                            <th class="col-sm-2">Commodity</th>
                                            <th class="col-sm-2">Supplier Name</th>
                                            <th class="col-sm-1">Order Quantity</th>
                                            <th class="col-sm-1">Remaining Quantity</th>
                                            <th class="col-sm-1">Loaded Quantity</th>
                                            <th class="col-sm-1">Delivery Date</th>
                                            <th class="col-sm-1">Created Date</th>
                                            <th class="col-sm-1">Action</th>
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
            // initializeDynamicSelect2('#company_location_id', 'company_locations', 'name', 'id', true, false, true, true);
            // initializeDynamicSelect2('#supplier_id_filter', 'suppliers', 'name', 'id', true, false, true, true);
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

            initializeDynamicSelect2('#product_id_filter', 'products', 'name', 'id', true, false, true, true);
            filterationCommon(`{{ route('raw-material.get.purchase-sampling-request') }}`);
        });
    </script>
@endsection
