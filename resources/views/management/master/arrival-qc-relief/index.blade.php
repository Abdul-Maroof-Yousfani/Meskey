@extends('management.layouts.master')
@section('title')
    Arrival QC Relief
@endsection
@section('content')
    <div class="content-wrapper">
        <section id="extended">
            <div class="row w-100 mx-auto">
                <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6">
                    <h2 class="page-title"> Arrival QC Relief Management</h2>
                </div>
                <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6 text-right">
                    <button onclick="openModal(this,'{{ route('arrival-qc-relief.create') }}','Create Arrival QC Relief', false, '70%')"
                        type="button" class="btn btn-primary position-relative ">
                        Create Arrival QC Relief
                    </button>
                </div>
            </div>
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <form id="filterForm" class="form">
                                <div class="row">
                                    <div class="col-md-12 my-1">
                                        <div class="row justify-content-end text-left">
                                            <div class="col-md-2">
                                                <label for="search" class="form-label">Search</label>
                                                <input type="hidden" name="page" value="{{ request('page', 1) }}">
                                                <input type="hidden" name="per_page" value="{{ request('per_page', 25) }}">
                                                <input type="text" class="form-control" id="search"
                                                    placeholder="Search here" name="search"
                                                    value="{{ request('search', '') }}">
                                            </div>

                                            <div class="col-xs-3 col-sm-3 col-md-3">
                                                <div class="form-group">
                                                    <label>Products:</label>
                                                    <select class="form-control select2" name="product_id" id="product_id">
                                                        <option value="">Select Product</option>
                                                        @foreach ($products as $product)
                                                            <option value="{{ $product->id }}">{{ $product->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="col-xs-3 col-sm-3 col-md-3">
                                                <div class="form-group">
                                                    <label>Locations:</label>
                                                    <select class="form-control select2" name="location_id" id="location_id">
                                                        <option value="">Select Location</option>
                                                        <option value="all">All Locations</option>
                                                        @foreach ($locations as $loc)
                                                            <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="col-xs-2 col-sm-2 col-md-2">
                                                <div class="form-group">
                                                    <label>Start Date:</label>
                                                    <input type="date" class="form-control" name="start_date" id="filter_start_date">
                                                </div>
                                            </div>

                                            <div class="col-xs-2 col-sm-2 col-md-2">
                                                <div class="form-group">
                                                    <label>End Date:</label>
                                                    <input type="date" class="form-control" name="end_date" id="filter_end_date">
                                                </div>
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
                                            <th class="col-sm-2">Product</th>
                                            <th class="col-sm-2">Location</th>
                                            <th class="col-sm-2">Valid Period</th>
                                            <th class="col-sm-4">QC Relief Parameters</th>
                                            <th class="col-sm-1">Status</th>
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
            filterationCommon(`{{ route('get.arrival-qc-relief') }}`);

            if ($.fn.select2) {
                $('.select2').select2();
            }
        });
    </script>
@endsection
