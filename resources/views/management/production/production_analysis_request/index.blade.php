@extends('management.layouts.master')
@section('title')
    Production Analysis Request
@endsection
@section('content')
    <div class="content-wrapper">
        <section id="extended">
            <div class="row w-100 mx-auto">
                <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6">
                    <h2 class="page-title">Production Analysis Request</h2>
                </div>
                <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6 text-right">
                    <button onclick="openModal(this,'{{ route('production-analysis-request.create') }}','Add Production Analysis Request',false,'80%')" type="button"
                        class="btn btn-primary position-relative">
                        <i class="ft-plus mr-1"></i> Create Analysis Request
                    </button>
                </div>
            </div>
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <form id="filterForm" class="form">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="row">
                                            <div class="col-md-2 text-left">
                                                <label for="custom_date_range" class="form-label">Date</label>
                                                <input type="text" class="form-control" name="date_range" id="custom_date_range" value="{{ date('Y-m-d', strtotime('-30 days')) }} - {{ date('Y-m-d') }}">
                                            </div>
                                            <div class="col-md-2 text-left">
                                                <label for="type_filter" class="form-label">Analysis Type</label>
                                                <select name="type" id="type_filter" class="form-control select2-filter">
                                                    <option value="">All Types</option>
                                                    @foreach($types as $key => $label)
                                                        <option value="{{ $key }}">{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-2 text-left">
                                                <label for="location_ids" class="form-label">Company Location</label>
                                                <select name="location_ids[]" id="location_ids" class="form-control select2-filter" multiple data-placeholder="Select Location(s)">
                                                    @foreach($locations as $location)
                                                        <option value="{{ $location->id }}">{{ $location->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-2 text-left">
                                                <label for="arrival_location_ids" class="form-label">Arrival Location</label>
                                                <select name="arrival_location_ids[]" id="arrival_location_ids" class="form-control select2-filter" multiple data-placeholder="Select Arrival(s)">
                                                    @foreach($arrivalLocations as $arrival)
                                                        <option value="{{ $arrival->id }}">{{ $arrival->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-2 text-left">
                                                <label for="plant_ids" class="form-label">Plant</label>
                                                <select name="plant_ids[]" id="plant_ids" class="form-control select2-filter" multiple data-placeholder="Select Plant(s)">
                                                    @foreach($plants as $plant)
                                                        <option value="{{ $plant->id }}">{{ $plant->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-2 text-left">
                                                <label for="status_filter" class="form-label">Status</label>
                                                <select name="status" id="status_filter" class="form-control select2-filter">
                                                    <option value="">All Statuses</option>
                                                    <option value="pending">Pending</option>
                                                    <option value="completed">Completed</option>
                                                    <option value="cancelled">Cancelled</option>
                                                </select>
                                            </div>
                                            <div class="col-md-1">
                                                <input type="hidden" name="page" value="{{ request('page', 1) }}">
                                                <input type="hidden" name="per_page" value="{{ request('per_page', 25) }}">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </form>
                            <div class="row mt-2">
                                <div class="col-md-4">
                                    <input type="text" class="form-control" id="search" name="search" placeholder="Search by request #, job order, remarks...">
                                </div>
                            </div>
                        </div>
                        <div class="card-content">
                            <div class="card-body">
                                <div id="filteredData" class="table-responsive">
                                    <!-- Content loaded via AJAX -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <script>
        $(document).ready(function() {
            if ($.fn.select2) {
                $('.select2-filter').select2({ width: '100%' });
            }

            function fetchData(page = 1) {
                let formData = $('#filterForm').serializeArray();
                formData.push({ name: 'page', value: page });
                formData.push({ name: 'search', value: $('#search').val() });

                $.ajax({
                    url: "{{ route('get.production-analysis-request') }}",
                    type: "POST",
                    data: formData,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    beforeSend: function() {
                        $('#filteredData').html('<div class="text-center py-5"><i class="fa fa-spinner fa-spin fa-2x"></i></div>');
                    },
                    success: function(response) {
                        $('#filteredData').html(response);
                    },
                    error: function() {
                        $('#filteredData').html('<div class="alert alert-danger">Error loading data.</div>');
                    }
                });
            }

            fetchData();

            $('#filterForm select, #custom_date_range').on('change', function() {
                fetchData(1);
            });

            let searchTimer;
            $('#search').on('keyup', function() {
                clearTimeout(searchTimer);
                searchTimer = setTimeout(function() {
                    fetchData(1);
                }, 400);
            });

            $('#resetFilters').on('click', function() {
                $('#filterForm')[0].reset();
                $('#search').val('');
                if ($.fn.select2) {
                    $('.select2-filter').val(null).trigger('change');
                }
                fetchData(1);
            });

            $(document).on('click', '.pagination a', function(e) {
                e.preventDefault();
                let page = $(this).attr('href').split('page=')[1];
                fetchData(page);
            });
        });
    </script>
@endsection
