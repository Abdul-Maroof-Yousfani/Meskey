@extends('management.layouts.master')
@section('title')
    Initial Sampling
@endsection
@section('content')
    <div class="content-wrapper">

        <section id="extended">
            <div class="row w-100 mx-auto">
                <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6">
                    <h2 class="page-title"> {{ $isResampling ? 'Initial Re-Sampling' : 'Initial Sampling' }} </h2>
                </div>
                <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6 text-right">
                    <button
                        onclick="openModal(this,'{{ route($isResampling ? 'initial-resampling.create' : 'initialsampling.create') }}','{{ $isResampling ? 'Create Initial Re-Sampling' : 'Create Initial Sampling' }}')"
                        type="button" class="btn btn-primary position-relative ">
                        {{ $isResampling ? 'Create Initial Re-Sampling' : 'Create Initial Sampling' }}
                    </button>
                </div>
            </div>
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <form id="filterForm" class="form">
                                <div class="row mx-0 align-items-end flex-nowrap" style="overflow-x: auto; padding-bottom: 10px;">
                                    <div class="px-1 text-left" style="min-width: 180px; flex: 1 1 180px;">
                                        <label for="daterange" class="form-label text-nowrap">Date Range</label>
                                        <input type="text" name="daterange" class="form-control"
                                            value="{{ request('daterange', \Carbon\Carbon::now()->subYear()->format('m/d/Y') . ' - ' . \Carbon\Carbon::now()->format('m/d/Y')) }}" />
                                    </div>
                                    <div class="px-1 text-left" style="min-width: 180px; flex: 1 1 180px;">
                                        <label for="unique_no" class="form-label text-nowrap">Ticket No</label>
                                        <input type="text" class="form-control" name="unique_no" id="unique_no" placeholder="Ticket No">
                                    </div>
                                    <div class="px-1 text-left" style="min-width: 180px; flex: 1 1 180px;">
                                        <label for="commodity_f" class="form-label text-nowrap">Commodity</label>
                                        <select name="commodity" id="commodity_f" class="form-control select2">
                                            <option value="">All Commodities</option>
                                        </select>
                                    </div>
                       
    
                                    <div class="px-1 text-left" style="min-width: 180px; flex: 1 1 180px;">
                                        <label for="remark" class="form-label text-nowrap">Remark</label>
                                        <input type="text" class="form-control" name="remark" id="remark" placeholder="Remark">
                                    </div>
                                </div>
                            </form>
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
            filterationCommon(`{{ route($isResampling ? 'get.initial-resampling' : 'get.initialsampling') }}`)
            initializeDynamicSelect2('#commodity_f', 'products', 'name', 'id', true, false, true, true);
            initializeDynamicSelect2('#supplier_id_f', 'suppliers', 'name', 'id', true, false, true, true);
        });
    </script>
@endsection
