@extends('management.layouts.master')
@section('title')
    Export Delivery Challan
@endsection
@section('content')
    <div class="content-wrapper">
        <section id="extended">
            <div class="row w-100 mx-auto">
                <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6">
                    <h2 class="page-title">Export Delivery Challan</h2>
                </div>
                <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6 text-right">
                    <a href="{{ route('export-delivery-challan.daily-dispatch') }}"
                        class="btn btn-secondary position-relative me-2">
                        Daily Export Dispatch
                    </a>
                    <button
                        onclick="openModal(this,'{{ route('export-delivery-challan.create') }}','Create Export Delivery Challan',false,'60%')"
                        type="button" class="btn btn-primary position-relative">
                        Create Export Delivery Challan
                    </button>
                </div>
            </div>
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <form id="filterForm" class="form">
                                <div class="row ">
                                    <div class="col-md-12 my-1 ">
                                        <div class="row justify-content-end text-right">
                                            <div class="col-md-2">
                                                <label for="customers" class="form-label">Search</label>
                                                <input type="hidden" name="page" value="{{ request('page', 1) }}">
                                                <input type="hidden" name="per_page" value="{{ request('per_page', 25) }}">
                                                <input type="text" class="form-control" id="search"
                                                    placeholder="Search here" name="search"
                                                    value="{{ request('search', '') }}">
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
                                            <th width="12%">DC No</th>
                                            <th width="18%">Customer</th>
                                            <th width="25%">Commodity/Product</th>
                                            <th width="10%" class="text-right">Qty(MT)</th>
                                            <th width="10%" class="text-right">Rate</th>
                                            <th width="10%" class="text-right">Amount</th>
                                            <th width="10%">Date</th>
                                            <th width="8%">Status</th>
                                            <th width="7%">Action</th>
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
            filterationCommon(`{{ route('get.export-delivery-challan.list') }}`)
        });
    </script>
@endsection