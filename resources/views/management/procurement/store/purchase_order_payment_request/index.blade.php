@extends('management.layouts.master')
@section('title')
    Purchase Order
@endsection
@section('content')
    <div class="content-wrapper">
        <section id="extended">
            <div class="row w-100 mx-auto">
                <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6">
                    <h2 class="page-title">PO Payment Request</h2>
                </div>
                <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6 text-right">
                    <button
                        onclick="openModal(this,'{{ route('store.purchase-order-payment-request.create') }}','Create Payment Request',false,'70%')"
                        type="button" class="btn btn-primary position-relative">
                        Create Payment Request
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
                                                <label for="search" class="form-label">Search</label>
                                                <input type="hidden" name="page" value="{{ request('page', 1) }}">
                                                <input type="hidden" name="per_page" value="{{ request('per_page', 25) }}">
                                                <input type="text" class="form-control" id="search"
                                                    placeholder="Search here" name="search"
                                                    value="{{ request('search', '') }}">
                                            </div>
                                            <div class="col-md-2">
                                                <label for="type" class="form-label">Payment Type</label>
                                                <select class="form-control" name="type" id="type">
                                                    <option value="">All Types</option>
                                                    <option value="advance">Advance</option>
                                                    <option value="against_receiving">Against Receiving</option>
                                                </select>
                                            </div>
                                            <div class="col-md-2">
                                                <label for="status" class="form-label">Status</label>
                                                <select class="form-control" name="status" id="status">
                                                    <option value="">All Status</option>
                                                    <option value="pending">Pending</option>
                                                    <option value="approved">Approved</option>
                                                    <option value="rejected">Rejected</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="card-content">
                            <div class="card-body table-responsive" id="filteredData">
                                <table class="table m-0">
                                    <thead>
                                        <tr>
                                            <th class="col-sm-2">Purchase Order No </th>
                                            <th class="col-sm-2">Purchase Order Date</th>
                                            <th class="col-sm-2">Location</th>
                                            <th class="col-sm-2">Category</th>
                                            <th class="col-sm-2">Item</th>
                                            <th class="col-sm-2">Item UOM</th>
                                            <th class="col-sm-2">Supplier</th>
                                            <th class="col-sm-2">Qty</th>
                                            <th class="col-sm-2">Rate</th>
                                            <th class="col-sm-2">Total Amount</th>
                                            {{-- <th class="col-sm-2">Item Status</th> --}}
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
            filterationCommon(`{{ route('store.get.purchase-order-payment-request') }}`)
        });
    </script>
@endsection
