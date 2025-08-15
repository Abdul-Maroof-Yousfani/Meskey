@extends('management.layouts.master')
@section('title')
    Purchaser Approval
@endsection
@section('content')
    <div class="content-wrapper">
        <section id="extended">
            <div class="row w-100 mx-auto">
                <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6">
                    <h2 class="page-title">Purchaser Approval Requests</h2>
                </div>
            </div>
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <form id="filterForm" class="form">
                                @php
                                    $today = \Carbon\Carbon::today()->format('Y-m-d');
                                    $oneMonthAgo = \Carbon\Carbon::today()->subMonth()->format('Y-m-d');
                                @endphp

                                <div class="row ">
                                    <div class="col-md-12 my-1 ">
                                        <div class="row justify-content-end text-right0">
                                            <div class="col-md-2">
                                                <div class="form-group">
                                                    <label>Date:</label>
                                                    <input type="text" name="daterange" class="form-control"
                                                        value="{{ request('daterange', \Carbon\Carbon::now()->subMonth()->format('m/d/Y') . ' - ' . \Carbon\Carbon::now()->format('m/d/Y')) }}" />
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="form-group">
                                                    <label>Location:</label>
                                                    <select name="company_location_id" id="company_location"
                                                        class="form-control select2">
                                                        <option value="">Location</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="form-group">
                                                    <label>Suppliers:</label>
                                                    <select name="supplier_id" id="supplier_id_f"
                                                        class="form-control select2">
                                                        <option value="">Supplier</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-2 text-left">
                                                <label for="from_date" class="form-label">Sampling Type</label>
                                                <select class="form-control" name="sampling_type">
                                                    <option value="">All</option>
                                                    <option value="initial">Initial</option>
                                                    <option value="inner">Inner</option>
                                                </select>
                                            </div>
                                            {{-- <div class="col-md-2 text-left">
                                                <label for="from_date" class="form-label">From Date</label>
                                                <input type="date" class="form-control" id="from_date" name="from_date"
                                                    value="{{ request('from_date', $oneMonthAgo) }}">
                                            </div>
                                            <div class="col-md-2 text-left">
                                                <label for="to_date" class="form-label">To Date</label>
                                                <input type="date" class="form-control" id="to_date" name="to_date"
                                                    value="{{ request('to_date', $today) }}">
                                            </div> --}}
                                            <div class="col-md-2 text-left">
                                                <label for="search" class="form-label">Search</label>
                                                <input type="hidden" name="page" value="{{ request('page', 1) }}">
                                                <input type="hidden" name="per_page" value="{{ request('per_page', 25) }}">
                                                <input type="text" class="form-control" id="search"
                                                    placeholder="Type Ticket No, Supplier Name " name="search"
                                                    value="{{ request('search') }}">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </form>


                            {{-- <a href="{{ route('export-roles') }}" class="btn btn-warning">Export Roles</a> --}}
                        </div>
                        <div class="card-content">
                            <div class="card-body table-responsive" id="filteredData">
                                <table class="table m-0">
                                    <thead>
                                        <tr>
                                            <th class="col-sm-1">Ticket No. </th>
                                            <th class="col-sm-2">Commodity</th>
                                            <th class="col-sm-2">Supplier</th>
                                            <th class="col-sm-1">Truck No</th>
                                            <th class="col-sm-1">Bilty No</th>
                                            <th class="col-sm-2">Created</th>
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

            filterationCommon(`{{ route('raw-material.get.sampling-monitoring') }}`)
        });
    </script>
@endsection
