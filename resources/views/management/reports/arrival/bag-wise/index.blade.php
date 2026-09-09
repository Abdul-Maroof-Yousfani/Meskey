@extends('management.layouts.master')
@section('title')
    Bag Arrival Report
@endsection
@section('content')
    <div class="content-wrapper">
        <section id="extended">
            <div class="row w-100 mx-auto">
                <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6">
                    <h2 class="page-title">
                        Bag Arrival Report
                    </h2>
                </div>
                <div class="col-md-6 d-flex align-items-end justify-content-end">
                    <div class="form-group mb-0">
                        <button class="btn btn-secondary" onclick="exportToExcel('exportableTable','BagArrivalReport')">
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
                                            <div class="col-md-3">
                                                <div class="form-group mb-0">
                                                    <label>Bag:</label>
                                                    <select name="bag_type_id[]" id="bag_type_id" multiple
                                                        class="form-control selectWithoutAjax">
                                                        <option value="">Select Bag</option>
                                                        @foreach ($bagTypes as $bagType)
                                                            <option value="{{ $bagType->id }}"
                                                                {{ is_array(request('bag_type_id')) && in_array($bagType->id, request('bag_type_id')) ? 'selected' : '' }}>
                                                                {{ $bagType->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="col-md-3">
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

                                            <div class="col-md-3">
                                                <div class="form-group mb-0">
                                                    <label>Date Range:</label>
                                                    <input type="text" name="daterange" id="daterange" class="form-control"
                                                        placeholder="Select Date Range"
                                                        value="{{ request('daterange', '') }}" />
                                                </div>
                                            </div>

                                            <div class="col-md-3">
                                                <div class="form-group mb-0">
                                                    <label>Sauda Type:</label>
                                                    <select name="sauda_type_id" id="sauda_type_id"
                                                        class="form-control selectWithoutAjax">
                                                        <option value="">Select Sauda Type</option>
                                                        @foreach ($saudaTypes as $saudaType)
                                                            <option value="{{ $saudaType->id }}"
                                                                {{ request('sauda_type_id') == $saudaType->id ? 'selected' : '' }}>
                                                                {{ $saudaType->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
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
                                            <th style="width: 80px;">#</th>
                                            <th>Bag</th>
                                            <th class="text-center" style="width: 180px;">Total Tickets</th>
                                            <th class="text-right" style="width: 250px;">Filled Bags</th>
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
                `{{ route('reports.arrival.get.bag-wise') }}`
            );
            $('.selectWithoutAjax').select2();
        });
    </script>
@endsection
