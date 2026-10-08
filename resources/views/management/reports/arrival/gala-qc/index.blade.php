@extends('management.layouts.master')
@section('title')
    Gala QC Analysis Report
@endsection
@section('content')
    <div class="content-wrapper">
        <section id="extended">
            <div class="row w-100 mx-auto">
                <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6">
                    <h2 class="page-title">
                        Gala QC Analysis Report
                    </h2>
                </div>
                <div class="col-md-6 d-flex align-items-end justify-content-end">
                    <div class="form-group mb-0">
                        <button class="btn btn-secondary" onclick="exportToExcel('exportableTable','Gala_QC_Analysis_Report')">
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
                                            {{-- Location filter (replacing Warehouse filter as instructed) --}}
                                            <div class="col-md-2">
                                                <div class="form-group mb-0">
                                                    <label>Location:</label>
                                                    <select name="company_location_id[]" id="company_location"
                                                        {{ count($locations) == 1 ? 'disabled' : 'multiple' }}
                                                        class="form-control selectWithoutAjax">
                                                        <option value="">Select Location</option>
                                                        @foreach ($locations as $location)
                                                            <option value="{{ $location->id }}"
                                                                {{ (is_array(request('company_location_id')) && in_array($location->id, request('company_location_id'))) || count($locations) == 1 ? 'selected' : '' }}>
                                                                {{ $location->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>

                                            {{-- Commodity filter --}}
                                            <div class="col-md-3">
                                                <div class="form-group mb-0">
                                                    <label>Commodity:</label>
                                                    <select name="commodity_id[]" id="commodity_id" multiple
                                                        class="form-control selectWithoutAjax">
                                                        <option value="all" {{ (!request()->has('commodity_id') || (is_array(request('commodity_id')) && in_array('all', request('commodity_id')))) ? 'selected' : '' }}>All</option>
                                                        @foreach ($commodities as $commodity)
                                                            <option value="{{ $commodity->id }}"
                                                                {{ (is_array(request('commodity_id')) && in_array($commodity->id, request('commodity_id'))) ? 'selected' : '' }}>
                                                                {{ $commodity->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>

                                            {{-- Year filter --}}
                                            <div class="col-md-2">
                                                <div class="form-group mb-0">
                                                    <label>Year:</label>
                                                    <select name="year" id="year" class="form-control selectWithoutAjax">
                                                        <option value="all">All Years</option>
                                                        @foreach ($years as $yr)
                                                            <option value="{{ $yr }}" {{ request('year') == $yr ? 'selected' : '' }}>
                                                                {{ $yr }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>

                                            {{-- Month filter --}}
                                            <div class="col-md-2">
                                                <div class="form-group mb-0">
                                                    <label>Month:</label>
                                                    <select name="month" id="month" class="form-control selectWithoutAjax">
                                                        <option value="all">All Months</option>
                                                        @foreach ($months as $num => $monthName)
                                                            <option value="{{ $num }}" {{ request('month') == $num ? 'selected' : '' }}>
                                                                {{ $monthName }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>

                                            {{-- Status filter --}}
                                            <div class="col-md-2">
                                                <div class="form-group mb-0">
                                                    <label>Status:</label>
                                                    <select name="status_id" id="status_id" class="form-control selectWithoutAjax">
                                                        <option value="all">All Statuses</option>
                                                        <option value="fully_approved" {{ request('status_id') == 'fully_approved' ? 'selected' : '' }}>OK</option>
                                                        <option value="half_approved" {{ request('status_id') == 'half_approved' ? 'selected' : '' }}>Reject Half</option>
                                                        <option value="rejected" {{ request('status_id') == 'rejected' ? 'selected' : '' }}>Reject Full</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="col-md-1 d-flex align-items-end">
                                                <div class="form-group mb-0 w-100">
                                                    <input type="submit" class="btn btn-primary w-100" name="generatebtn" value="Generate">
                                                </div>
                                            </div>
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
                                            <th>Galaa #</th>
                                            <th>Net Weight</th>
                                            @foreach ($product_slab_types as $slab)
                                                <th>{{ $slab->name }} @if(!empty($slab->qc_symbol))({{ $slab->qc_symbol }})@endif</th>
                                            @endforeach
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
        $(document).ready(function () {
            $('.selectWithoutAjax').select2();

            $('#commodity_id').on('select2:select', function (e) {
                if (e.params.data.id === 'all') {
                    $(this).val(['all']).trigger('change');
                } else {
                    let vals = $(this).val() || [];
                    vals = vals.filter(v => v !== 'all');
                    $(this).val(vals).trigger('change');
                }
            });

            $('#commodity_id').on('select2:unselect', function (e) {
                setTimeout(() => {
                    let vals = $(this).val() || [];
                    if (vals.length === 0) {
                        $(this).val(['all']).trigger('change');
                    }
                }, 1);
            });

            const runFilter = filterationCommon_withbtn(
                `{{ route('reports.arrival.get.gala-qc') }}`
            );

            $('#filterForm').on('submit', function (e) {
                e.preventDefault();
                runFilter();
            });

            runFilter();
        });
    </script>
@endsection
