@extends('management.layouts.master')
@section('title')
    Production Output Analysis
@endsection
@section('content')
    <div class="content-wrapper">
        <section id="extended">
            <div class="row w-100 mx-auto">
                <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6">
                    <h2 class="page-title">Production Output Analysis</h2>
                </div>
                <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6 text-right">
                    <button onclick="openModal(this,'{{ route('production-output-analysis.create') }}','Add Production Output Analysis',false,'90%')" type="button"
                        class="btn btn-primary position-relative">
                        Create Output Analysis
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
                                            <div class="col-md-3 text-left">
                                                <label for="location_ids" class="form-label">Company Location</label>
                                                <select name="location_ids[]" id="location_ids" class="form-control select2-filter" multiple data-placeholder="Select Location(s)">
                                                    @foreach($locations as $location)
                                                        <option value="{{ $location->id }}">{{ $location->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-3 text-left">
                                                <label for="arrival_location_ids" class="form-label">Arrival Location</label>
                                                <select name="arrival_location_ids[]" id="arrival_location_ids" class="form-control select2-filter" multiple data-placeholder="Select Arrival Location(s)">
                                                    @foreach($arrivalLocations as $arrival)
                                                        <option value="{{ $arrival->id }}">{{ $arrival->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-3 text-left">
                                                <label for="plant_ids" class="form-label">Plant</label>
                                                <select name="plant_ids[]" id="plant_ids" class="form-control select2-filter" multiple data-placeholder="Select Plant(s)">
                                                    @foreach($plants as $plant)
                                                        <option value="{{ $plant->id }}">{{ $plant->name }}</option>
                                                    @endforeach
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
                        </div>
                        <div class="card-content">
                            <div class="card-body table-responsive" id="filteredData" style="margin-top: 30px;">
                                {{-- Loaded via AJAX --}}
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
            filterationCommon(`{{ route('get.production-output-analysis') }}`)
            
            $('.select2-filter').select2({
                width: '100%'
            });

            $('#custom_date_range').daterangepicker({
                locale: { format: 'YYYY-MM-DD' },
                autoUpdateInput: true
            }).on('apply.daterangepicker', function(ev, picker) {
                $(this).val(picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD'));
                $('#location_ids').trigger('change');
            });
        });
    </script>
@endsection
