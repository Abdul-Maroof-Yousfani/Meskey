@extends('management.layouts.master')
@section('title')
    Sampling & Workflow Turnaround Time Analysis
@endsection
@section('content')
    <div class="content-wrapper">
        <section id="extended">
            <div class="row w-100 mx-auto">
                <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6">
                    <h2 class="page-title">
                        Sampling & Workflow Turnaround Time Analysis
                    </h2>
                </div>
                <div class="col-md-6 d-flex align-items-end justify-content-end">
                    <div class="form-group mb-0">
                        <button class="btn btn-secondary" onclick="exportToExcel('exportableTable','Sampling_Workflow_Turnaround_Time_Analysis')">
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
                                                    <label>Warehouse:</label>
                                                    <select name="warehouse_id[]" id="warehouse_id"
                                                        {{ count($warehouses) == 1 ? 'disabled' : 'multiple' }}
                                                        class="form-control selectWithoutAjax">
                                                        <option value="">Select Warehouse</option>
                                                        @foreach ($warehouses as $warehouse)
                                                            <option value="{{ $warehouse->id }}"
                                                                {{ (is_array(request('warehouse_id')) && in_array($warehouse->id, request('warehouse_id'))) || count($warehouses) == 1 ? 'selected' : '' }}>
                                                                {{ $warehouse->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="form-group mb-0">
                                                    <label>Date Range:</label>
                                                    <input type="text" name="daterange" class="form-control"
                                                        value="{{ request('daterange', \Carbon\Carbon::now()->subMonth()->format('m/d/Y') . ' - ' . \Carbon\Carbon::now()->format('m/d/Y')) }}" />
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="form-group mb-0">
                                                    <label>Station:</label>
                                                    <select name="station_id" id="station_id"
                                                        class="form-control selectWithoutAjax">
                                                        <option value="">Select Station</option>
                                                        @foreach ($stations as $station)
                                                            <option value="{{ $station->id }}"
                                                                {{ request('station_id') == $station->id ? 'selected' : '' }}>
                                                                {{ $station->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-2">
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
                                            <div class="col-md-2">
                                                <div class="form-group mb-0">
                                                    <label>Status:</label>
                                                    <select name="status_id" id="status_id"
                                                        class="form-control selectWithoutAjax">
                                                        <option value="">Select Status</option>
                                                        <option value="half_approved" {{ request('status_id') == 'half_approved' ? 'selected' : '' }}>Reject Half</option>
                                                        <option value="rejected" {{ request('status_id') == 'rejected' ? 'selected' : '' }}>Reject Full</option>
                                                        <option value="fully_approved" {{ request('status_id') == 'fully_approved' ? 'selected' : '' }}>OK</option>
                                                    </select>
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
                                            <th>Status</th>
                                            <th>Gate -> 1st QC</th>
                                            <th>1st QC -> 1st Dec</th>
                                            <th>1st Dec -> Loc</th>
                                            <th>Loc -> 1st Weight</th>
                                            <th>Inner Sample -> 2nd Dec</th>
                                            <th>2nd Weight -> Accounts</th>
                                            <th>Accounts -> HO Confirm</th>
                                            <th>Total Elapsed Time</th>
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
                `{{ route('reports.arrival.get.sampling-workflow') }}`
            );

            $('#filterForm').on('submit', function (e) {
                e.preventDefault();
                runFilter();
            });

            runFilter();
        });
    </script>
@endsection
