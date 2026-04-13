@extends('management.layouts.master')
@section('title')
    Rejection Return
@endsection
@section('content')
    <div class="content-wrapper">
        <section id="extended">
            <div class="row w-100 mx-auto">
                <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6">
                    <h2 class="page-title">Rejection Return</h2>
                </div>
                <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6 text-right">
                    <button
                        onclick="openModal(this,'{{ route('store.rejection-return.create') }}','Create Rejection Return',false,'80%')"
                        type="button" class="btn btn-primary position-relative">
                        Create Rejection Return
                    </button>
                </div>
            </div>
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <form id="filterForm" class="form">
                                <div class="row mx-0">
                                    <div class="px-1 text-left" style="width: 15%;">
                                        <label for="rr_no" class="form-label">Return No</label>
                                        <input type="text" class="form-control" id="rr_no"
                                            placeholder="Return No" name="rr_no"
                                            value="{{ request('rr_no', '') }}">
                                    </div>
                                    <div class="px-1 text-left" style="width: 15%;">
                                        <label for="grn_no" class="form-label">GRN No</label>
                                        <input type="text" class="form-control" id="grn_no"
                                            placeholder="GRN No" name="grn_no"
                                            value="{{ request('grn_no', '') }}">
                                    </div>
                                    <div class="px-1 text-left" style="width: 25%;">
                                        <label for="supplier_id" class="form-label">Supplier</label>
                                        <select name="supplier_id" id="supplier_id" class="form-control select2">
                                            <option value="all">All Suppliers</option>
                                            @foreach (get_supplier() as $supplier)
                                                <option value="{{ $supplier->id }}" {{ request('supplier_id') == $supplier->id ? 'selected' : '' }}>
                                                    {{ $supplier->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="px-1 text-left" style="width: 15%;">
                                        <label for="status" class="form-label">Status</label>
                                        <select name="status" id="status" class="form-control select2">
                                            <option value="all" {{ request('status') == 'all' ? 'selected' : '' }}>All Status</option>
                                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                                            <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                                            <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                                        </select>
                                    </div>
                                    <div class="px-1 text-left" style="width: 30%;">
                                        <label for="search" class="form-label">Search</label>
                                        <input type="hidden" name="page" value="{{ request('page', 1) }}">
                                        <input type="hidden" name="per_page" value="{{ request('per_page', 25) }}">
                                        <input type="text" class="form-control" id="search"
                                            placeholder="Search here..." name="search"
                                            value="{{ request('search', '') }}">
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="card-content">
                            <div class="card-body table-responsive" id="filteredData">
                                <table class="table m-0" style="table-layout: fixed; width: 100%;">
                                    <thead>
                                        <tr>
                                            <th style="width: 12%;" class="text-left">Return No</th>
                                            <th style="width: 12%;" class="text-left">GRN No</th>
                                            <th style="width: 10%;" class="text-left">Truck No</th>
                                            <th style="width: 15%;" class="text-left">Supplier</th>
                                            <th style="width: 15%;" class="text-left">Item</th>
                                            <th style="width: 10%;" class="text-left">Rejected Qty</th>
                                            <th style="width: 10%;" class="text-left">Status</th>
                                            <th style="width: 16%;" class="text-left">Action</th>
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
            filterationCommon(`{{ route('store.rejection-return.getList') }}`)
            
            $(document).on('ajaxSuccess', function() {
                $('.select2').select2();
            });
        });
    </script>
@endsection
