@extends('management.layouts.master')
@section('title')
    Delivery Order
@endsection
@section('content')
    <div class="content-wrapper">
        <section id="extended">
            <div class="row w-100 mx-auto">
                <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6">
                    <h2 class="page-title">Delivery Orders</h2>
                </div>
                <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6 text-right">
                    <button
                        onclick="openModal(this,'{{ route('sales.delivery-order.create') }}','Create Delivery Order',false,'90%')"
                        type="button" class="btn btn-primary position-relative">
                        Create Delivery Order
                    </button>
                </div>
            </div>
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <form id="filterForm" class="form">
                                <input type="hidden" name="page" value="{{ request('page', 1) }}">
                                <input type="hidden" name="per_page" value="{{ request('per_page', 25) }}">
                                <div class="row mx-0">
                                    <div class="px-1 text-left" style="width: 11%;">
                                        <label for="do_no" class="form-label">DO No</label>
                                        <input type="text" class="form-control" id="do_no_for_filter"
                                            placeholder="DO No" name="do_no_for_filter"
                                            value="{{ request('do_no_for_filter', '') }}">
                                    </div>
                                    <div class="px-1 text-left" style="width: 14%;">
                                        <label for="so_id_for_filter" class="form-label">SO No</label>
                                        <select name="so_id_for_filter" id="so_id_for_filter" class="form-control select2">
                                            <option value="all">All SO</option>
                                            @foreach ($saleOrders as $so)
                                                <option value="{{ $so->id }}" {{ request('so_id_for_filter') == $so->id ? 'selected' : '' }}>
                                                    {{ $so->reference_no }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="px-1 text-left" style="width: 15%;">
                                        <label for="customer_id_for_filter" class="form-label">Customer</label>
                                        <select name="customer_id_for_filter" id="customer_id_for_filter" class="form-control select2">
                                            <option value="all">All Customers</option>
                                            @foreach ($customers as $customer)
                                                <option value="{{ $customer->id }}" {{ request('customer_id_for_filter') == $customer->id ? 'selected' : '' }}>
                                                    {{ $customer->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="px-1 text-left" style="width: 14%;">
                                        <label for="item_id_for_filter" class="form-label">Item</label>
                                        <select name="item_id_for_filter" id="item_id_for_filter" class="form-control select2">
                                            <option value="all">All Items</option>
                                            @foreach ($items as $item)
                                                <option value="{{ $item->id }}" {{ request('item_id_for_filter') == $item->id ? 'selected' : '' }}>
                                                    {{ $item->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="px-1 text-left" style="width: 14%;">
                                        <label for="date_range_for_filter" class="form-label">Date</label>
                                        <input type="text" class="form-control" name="date_range_for_filter" id="date_range_for_filter"
                                            placeholder="Select Date Range"
                                            value="{{ request('date_range_for_filter', '') }}">
                                    </div>
                                    <div class="px-1 text-left" style="width: 12%;">
                                        <label for="status_for_filter" class="form-label">Status</label>
                                        <select name="status_for_filter" id="status_for_filter" class="form-control select2">
                                            <option value="all" {{ request('status_for_filter') == 'all' ? 'selected' : '' }}>All Status</option>
                                            <option value="pending" {{ request('status_for_filter') == 'pending' ? 'selected' : '' }}>Pending</option>
                                            <option value="approved" {{ request('status_for_filter') == 'approved' ? 'selected' : '' }}>Approved</option>
                                            <option value="rejected" {{ request('status_for_filter') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                                            <option value="reverted" {{ request('status_for_filter') == 'reverted' ? 'selected' : '' }}>Reverted</option>
                                        </select>
                                    </div>
                                    <div class="px-1 text-left" style="width: 20%;">
                                        <label for="search_for_filter" class="form-label">Search</label>
                                        <input type="text" class="form-control" id="search_for_filter"
                                            placeholder="Search" name="search_for_filter"
                                            value="{{ request('search_for_filter', '') }}">
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
                                            <th class="col-3">SO NO</th>
                                            {{-- <th class="col-2">Location</th> --}}
                                            <th class="col-4">Customer</th>
                                            <th class="col-2 text-right">Qty</th>
                                            {{-- <th class="col-1 text-right">Approved Qty</th> --}}
                                            <th class="col-1">Contract Type</th>
                                            <th class="col-1">Status</th>
                                            <th class="col-1">Action</th>
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
            filterationCommon(`{{ route('sales.get.delivery-order.list') }}`)
            
            // Re-initialize select2 after any AJAX update to preserve selected value in visual UI
            $(document).on('ajaxSuccess', function() {
                $('#so_id_for_filter, #customer_id_for_filter, #item_id_for_filter, #status_for_filter').select2();
            });
        });
    </script>
@endsection