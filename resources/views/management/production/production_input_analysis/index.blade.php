@extends('management.layouts.master')
@section('title')
    Production Input Analysis
@endsection
@section('content')
    <div class="content-wrapper">
        <section id="extended">
            <div class="row w-100 mx-auto">
                <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6">
                    <h2 class="page-title">Production Input Analysis</h2>
                </div>
                <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6 text-right">
                    <button onclick="openModal(this,'{{ route('production-input-analysis.create') }}','Add Production Input Analysis',false,'90%')" type="button"
                        class="btn btn-primary position-relative">
                        Create Input Analysis
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
                                        <div class="row">
                                            <div class="col-md-2 text-left">
                                                <label for="job_order_ids" class="form-label">Job Order Filter</label>
                                                <select name="job_order_ids[]" id="job_order_ids" class="form-control select2" multiple data-placeholder="Select Job Order(s)">
                                                    @foreach($jobOrders as $jobOrder)
                                                        <option value="{{ $jobOrder->id }}">{{ $jobOrder->job_order_no }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-2 text-left">
                                                <label for="brand_ids" class="form-label">Brand Filter</label>
                                                <select name="brand_ids[]" id="brand_ids" class="form-control select2" multiple data-placeholder="Select Brand(s)">
                                                    @foreach($brands as $brand)
                                                        <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-2 text-left">
                                                <label for="location_ids" class="form-label">Location Filter</label>
                                                <select name="location_ids[]" id="location_ids" class="form-control select2" multiple data-placeholder="Select Location(s)">
                                                    @foreach($locations as $location)
                                                        <option value="{{ $location->id }}">{{ $location->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-2 text-left">
                                                <label for="variety_search" class="form-label">Variety Search</label>
                                                <input type="text" name="variety_search" id="variety_search" class="form-control" placeholder="Search Variety">
                                            </div>
                                            <div class="col-md-2 text-left">
                                                <label for="custom_date_range" class="form-label">Date Range Filter</label>
                                                <input type="text" class="form-control" name="date_range" id="custom_date_range" value="{{ date('Y-m-d', strtotime('-30 days')) }} - {{ date('Y-m-d') }}">
                                            </div>
                                            <div class="col-md-2">
                                                <input type="hidden" name="page" value="{{ request('page', 1) }}">
                                                <input type="hidden" name="per_page" value="{{ request('per_page', 25) }}">
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
                                            <th>Date</th>
                                            <th>Job Order(s)</th>
                                            <th>Brand</th>
                                            <th>Variety</th>
                                            <th>Location</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Data will be loaded here via AJAX -->
                                    </tbody>
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
        $(document).ready(function () {
            $('.select2').select2({
                width: '100%'
            });
            $('#custom_date_range').daterangepicker({
                locale: { format: 'YYYY-MM-DD' },
                autoUpdateInput: true
            }).on('apply.daterangepicker', function(ev, picker) {
                $(this).val(picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD'));
                $('#variety_search').trigger('change').trigger('keyup');
            });
            filterationCommon(`{{ route('get.production-input-analysis') }}`)
        });
    </script>
@endsection
