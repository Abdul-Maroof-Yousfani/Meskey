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
                        <button class="btn btn-secondary"
                            onclick="exportToExcel('exportableTable','Gala_QC_Analysis_Report')">
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
                                                        <option value="all"
                                                            {{ !request()->has('commodity_id') || (is_array(request('commodity_id')) && in_array('all', request('commodity_id'))) ? 'selected' : '' }}>
                                                            All</option>
                                                        @foreach ($commodities as $commodity)
                                                            <option value="{{ $commodity->id }}"
                                                                {{ is_array(request('commodity_id')) && in_array($commodity->id, request('commodity_id')) ? 'selected' : '' }}>
                                                                {{ $commodity->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>

                                            {{-- Status filter --}}
                                            <div class="col-md-2">
                                                <div class="form-group mb-0">
                                                    <label>Status:</label>
                                                    @php
                                                        $defaultStatuses = ['ok', 'ts'];
                                                        $selectedStatuses = request()->has('status_id')
                                                            ? (array) request('status_id')
                                                            : $defaultStatuses;
                                                    @endphp
                                                    <select name="status_id[]" id="status_id" multiple
                                                        class="form-control selectWithoutAjax"
                                                        data-placeholder="Select Status">
                                                        <option value="ok"
                                                            {{ in_array('ok', $selectedStatuses) ? 'selected' : '' }}>OK
                                                        </option>
                                                        <option value="ts"
                                                            {{ in_array('ts', $selectedStatuses) ? 'selected' : '' }}>TS
                                                        </option>
                                                        <option value="half_approved"
                                                            {{ in_array('half_approved', $selectedStatuses) ? 'selected' : '' }}>
                                                            Reject Half</option>
                                                        {{-- <option value="rejected"
                                                            {{ in_array('rejected', $selectedStatuses) ? 'selected' : '' }}>
                                                            Reject Full</option> --}}
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="col-md-2">
                                                <div class="form-group mb-0">
                                                    <label>Date Filter:</label>
                                                    <select name="date_filter" id="date_filter"
                                                        class="form-control selectWithoutAjax">
                                                        <option value="last_7_days"
                                                            {{ request('date_filter', 'last_7_days') == 'last_7_days' ? 'selected' : '' }}>
                                                            Last 7 days
                                                        </option>
                                                        <option value="last_30_days"
                                                            {{ request('date_filter') == 'last_30_days' ? 'selected' : '' }}>
                                                            Last 30 day
                                                        </option>
                                                        <option value="today"
                                                            {{ request('date_filter') == 'today' ? 'selected' : '' }}>
                                                            Today
                                                        </option>
                                                        <option value="custom"
                                                            {{ request('date_filter') == 'custom' ? 'selected' : '' }}>
                                                            Custom
                                                        </option>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="col-md-2" id="daterange_wrapper"
                                                style="{{ request('date_filter') == 'custom' ? '' : 'display: none;' }}">
                                                <div class="form-group mb-0">
                                                    <label>Date Range:</label>
                                                    <input type="text" name="daterange" id="daterange"
                                                        class="form-control"
                                                        value="{{ request('daterange', \Carbon\Carbon::now()->subMonth()->format('m/d/Y') . ' - ' . \Carbon\Carbon::now()->format('m/d/Y')) }}"
                                                        {{ request('date_filter') == 'custom' ? '' : 'disabled' }} />
                                                </div>
                                            </div>

                                            <div class="col-md-1 d-flex align-items-end">
                                                <div class="form-group mb-0 w-100">
                                                    <input type="submit" class="btn btn-primary w-100" name="generatebtn"
                                                        value="Generate">
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
                                            <th>Commodity</th>
                                            <th>QC Advice</th>
                                            <th>Net Weight</th>
                                            <th>Average of Inner Broken</th>
                                            <th>Average of Inner Moisture (15% max)</th>
                                            <th>Damage: Creamish / grey / Tip) max 2%</th>
                                            <th>Damage: YELLOW max 1%</th>
                                            <th>Under Milled (5% max)</th>
                                            <th>Average of Chalky (5% max)</th>
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
            $('.selectWithoutAjax').select2();

            $('#date_filter').on('change', function() {
                var selected = $(this).val();
                if (selected === 'custom') {
                    $('#daterange').prop('disabled', false);
                    $('#daterange_wrapper').slideDown(200);
                } else {
                    $('#daterange').prop('disabled', true);
                    $('#daterange_wrapper').slideUp(200);
                }
            });

            // Commodity "All" logic
            $('#commodity_id').on('select2:select', function(e) {
                if (e.params.data.id === 'all') {
                    $(this).val(['all']).trigger('change');
                } else {
                    let vals = $(this).val() || [];
                    vals = vals.filter(v => v !== 'all');
                    $(this).val(vals).trigger('change');
                }
            });

            $('#commodity_id').on('select2:unselect', function(e) {
                setTimeout(() => {
                    let vals = $(this).val() || [];
                    if (vals.length === 0) {
                        $(this).val(['all']).trigger('change');
                    }
                }, 1);
            });

            // Ensure status defaults to OK and TS if not already set
            let $statusSelect = $('#status_id');
            if (!$statusSelect.val() || $statusSelect.val().length === 0) {
                $statusSelect.val(['ok', 'ts']).trigger('change.select2');
            }

            const runFilter = filterationCommon_withbtn(
                `{{ route('reports.arrival.get.gala-qc') }}`
            );

            $('#filterForm').on('submit', function(e) {
                e.preventDefault();
                runFilter();
            });

            runFilter();
        });
    </script>
@endsection
