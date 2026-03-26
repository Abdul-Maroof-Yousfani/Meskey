@extends('management.layouts.master')
@section('title')
    Goods Received Note
@endsection
@section('content')
    <div class="content-wrapper">
        <section id="extended">
            <div class="row w-100 mx-auto">
                <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6">
                    <h2 class="page-title">Goods Received Note</h2>
                </div>
                <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6 text-right">
                    <button
                        onclick="openModal(this,'{{ route('store.purchase-order-receiving.create') }}','Add GRN',false,'100%')"
                        type="button" class="btn btn-primary position-relative">
                        Create GRN
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
                                            <div class="col-md-2 text-left">
                                                <label for="filter_supplier_id" class="form-label">Supplier</label>
                                                <select name="supplier_id" id="filter_supplier_id" class="form-control select2">
                                                    <option value="all">All Suppliers</option>
                                                    @foreach($suppliers as $supplier)
                                                        <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-2 text-left">
                                                <label for="purchase_order_receiving_no" class="form-label">PO Receiving No.</label>
                                                <input type="text" class="form-control" id="purchase_order_receiving_no"
                                                    placeholder="Search Receive No." name="purchase_order_receiving_no"
                                                    value="{{ request('purchase_order_receiving_no', '') }}">
                                            </div>
                                            <div class="col-md-2 text-left">
                                                <label for="purchase_order_no" class="form-label">Order No.</label>
                                                <input type="text" class="form-control" id="purchase_order_no"
                                                    placeholder="Search Order No." name="purchase_order_no"
                                                    value="{{ request('purchase_order_no', '') }}">
                                            </div>
                                            <div class="col-md-2 text-left">
                                                <label for="purchase_request_no" class="form-label">Request No.</label>
                                                <input type="text" class="form-control" id="purchase_request_no"
                                                    placeholder="Search Request No." name="purchase_request_no"
                                                    value="{{ request('purchase_request_no', '') }}">
                                            </div>
                                            <div class="col-md-2 text-left">
                                                <label for="search" class="form-label">General Search</label>
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
                                            <th class="col-sm-3">Purchase Order No </th>
                                            <th class="col-sm-3">Purchase Request No</th>
                                            <th class="col-sm-3">Purchase Quotation No</th>
                                            {{-- <th class="col-sm-2">Location</th> --}}
                                            <th class="col-sm-3">Category- item</th>
                                            <th class="col-sm-3">Supplier</th>
                                            {{-- <th class="col-sm-2">Item UOM</th> --}}
                                            {{-- <th class="col-sm-2">Supplier</th> --}}
                                            <th class="col-sm-1">Qty</th>
                                            {{-- <th class="col-sm-1">Rate</th> --}}
                                            {{-- <th class="col-sm-1">Total Amount</th> --}}
                                            <th class="col-sm-1">Item Status</th>
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
        $(document).ready(function () {
            $('.select2').select2();
            filterationCommon(`{{ route('store.get.purchase-order-receiving') }}`)
        });
    </script>
@endsection