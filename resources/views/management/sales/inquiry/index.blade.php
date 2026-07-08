@extends('management.layouts.master')
@section('title')
    Sales Inquiry
@endsection
@section('content')
    <div class="content-wrapper">
        <section id="extended">
            <div class="row w-100 mx-auto">
                <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6">
                    <h2 class="page-title">Sales Inquiry </h2>
                </div>
                <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6 text-right">
                    <button
                        onclick="openModal(this,'{{ route('sales.sales-inquiry.create') }}','Create Sales Inquiry',false,'90%')"
                        type="button" class="btn btn-primary position-relative">
                        Create Sales Inquiry
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
                                    <div class="px-1 text-left" style="width: 13%;">
                                        <label for="inquiry_no" class="form-label">Inquiry No</label>
                                        <input type="text" class="form-control" id="inquiry_no"
                                            placeholder="Inquiry No" name="inquiry_no"
                                            value="{{ request('inquiry_no', '') }}">
                                    </div>
                                    <div class="px-1 text-left" style="width: 18%;">
                                        <label for="customer_id" class="form-label">Customer</label>
                                        <select name="customer_id" id="customer_id" class="form-control select2">
                                            <option value="all">All Customers</option>
                                            @foreach ($customers as $customer)
                                                <option value="{{ $customer->id }}" {{ request('customer_id') == $customer->id ? 'selected' : '' }}>
                                                    {{ $customer->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="px-1 text-left" style="width: 20%;">
                                        <label for="item_id" class="form-label">Item</label>
                                        <select name="item_id" id="item_id" class="form-control select2">
                                            <option value="all">All Items</option>
                                            @foreach ($items as $item)
                                                <option value="{{ $item->id }}" {{ request('item_id') == $item->id ? 'selected' : '' }}>
                                                    {{ $item->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="px-1 text-left" style="width: 16%;">
                                        <label for="date_range" class="form-label">Date</label>
                                        <input type="text" class="form-control" name="date_range" id="date_range"
                                            placeholder="Select Date Range"
                                            value="{{ request('date_range', '') }}">
                                    </div>
                                    <div class="px-1 text-left" style="width: 13%;">
                                        <label for="status" class="form-label">Status</label>
                                        <select name="status" id="status" class="form-control select2">
                                            <option value="all" {{ request('status') == 'all' ? 'selected' : '' }}>All Status</option>
                                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                                            <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                                            <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                                            <option value="reverted" {{ request('status') == 'reverted' ? 'selected' : '' }}>Reverted</option>
                                        </select>
                                    </div>
                                    <div class="px-1 text-left" style="width: 20%;">
                                        <label for="search" class="form-label">Search</label>
                                        <input type="text" class="form-control" id="search"
                                            placeholder="Search" name="search"
                                            value="{{ request('search', '') }}">
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
                                            <th class="col-3">Inquiry No</th>
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
            filterationCommon(`{{ route('sales.get.sales-inquiry.list') }}`)
            
            // Re-initialize select2 after any AJAX update to preserve selected value in visual UI
            $(document).on('ajaxSuccess', function() {
                $('#customer_id, #item_id, #status').select2();
            });
        });
    </script>
@endsection