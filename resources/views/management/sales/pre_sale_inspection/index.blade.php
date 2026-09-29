@extends('management.layouts.master')
@section('title')
    Pre Sale Inspection
@endsection
@section('content')
    <div class="content-wrapper">
        <section id="extended">
            <div class="row w-100 mx-auto">
                <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6">
                    <h2 class="page-title">Pre Sale Inspection</h2>
                </div>
                <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6 text-right">
                    <button
                        onclick="openModal(this,'{{ route('sales.pre-sale-inspection.create') }}','Create Pre Sale Inspection',false,'80%')"
                        type="button" class="btn btn-primary position-relative">
                        Create Pre Sale Inspection
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
                                    <div class="px-1 text-left" style="width: 20%;">
                                        <label for="inspection_no" class="form-label">Inspection No</label>
                                        <input type="text" class="form-control" placeholder="Inspection No" name="inspection_no"
                                            value="{{ request('inspection_no', '') }}">
                                    </div>
                                    <div class="px-1 text-left" style="width: 25%;">
                                        <label for="item_id" class="form-label">Item (Product)</label>
                                        <select name="item_id" id="item_id" class="form-control select2">
                                            <option value="all">All Items</option>
                                            @foreach ($items as $item)
                                                <option value="{{ $item->id }}" {{ request('item_id') == $item->id ? 'selected' : '' }}>
                                                    {{ $item->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="px-1 text-left" style="width: 25%;">
                                        <label for="date_range" class="form-label">Date Range</label>
                                        <input type="text" class="form-control" name="date_range" id="date_range"
                                            placeholder="Select Date Range"
                                            value="{{ request('date_range', '') }}">
                                    </div>
                                    <div class="px-1 text-left" style="width: 30%;">
                                        <label for="search" class="form-label">Search</label>
                                        <input type="text" class="form-control" id="search"
                                            placeholder="Search..." name="search"
                                            value="{{ request('search', '') }}">
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="card-content">
                            <div class="card-body table-responsive" id="filteredData">
                                <table class="table m-0">
                                    <thead>
                                        <tr>
                                            <th>Inspection #</th>
                                            <th>Date</th>
                                            <th>Party Name</th>
                                            <th>Party Contact</th>
                                            <th>Item</th>
                                            <th>Locations</th>
                                            <th>Factory / Section</th>
                                            <th>Action</th>
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
            filterationCommon(`{{ route('sales.get.pre-sale-inspection.list') }}`);

            $(document).on('ajaxSuccess', function() {
                $('#item_id').select2();
            });
        });
    </script>
@endsection
