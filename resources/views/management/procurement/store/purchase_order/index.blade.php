@extends('management.layouts.master')
@section('title')
    Purchase Order
@endsection
@section('content')
    <div class="content-wrapper">
        <section id="extended">
            <div class="row w-100 mx-auto">
                <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6">
                    <h2 class="page-title">Purchase Order</h2>
                </div>
                <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6 text-right">
                    <button
                        onclick="openModal(this,'{{ route('store.purchase-order.create') }}','Add Purchase Order',false,'100%')"
                        type="button" class="btn btn-primary position-relative">
                        Create Purchase Order
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
                                                <label for="item_id" class="form-label">Item</label>
                                                <select name="item_id" id="item_id" class="form-control select2">
                                                    <option value="all">All Items</option>
                                                    @foreach($items as $item)
                                                        <option value="{{ $item->id }}">{{ $item->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-2 text-left">
                                                <label for="category_id" class="form-label">Category</label>
                                                <select name="category_id" id="category_id" class="form-control select2">
                                                    <option value="all">All Categories</option>
                                                    @foreach($categories as $category)
                                                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-2 text-left">
                                                <label for="status" class="form-label">Status</label>
                                                <select name="status" id="status" class="form-control">
                                                    <option value="all">All Status</option>
                                                    <option value="pending">Pending</option>
                                                    <option value="approved">Approved</option>
                                                    <option value="rejected">Rejected</option>
                                                    <option value="reverted">Reverted</option>
                                                </select>
                                            </div>
                                            <div class="col-md-2 text-left">
                                                <label for="search" class="form-label">Search</label>
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
                                            <th class="col-sm-1">Rate</th>
                                            <th class="col-sm-1">Total Amount</th>
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
            filterationCommon(`{{ route('store.get.purchase-order') }}`)
        });
    </script>
@endsection