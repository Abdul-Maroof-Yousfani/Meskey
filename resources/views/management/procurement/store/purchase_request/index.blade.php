@extends('management.layouts.master')
@section('title')
    Purchase Request
@endsection
@section('content')
    <div class="content-wrapper">
        <section id="extended">
            <div class="row w-100 mx-auto">
                <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6">
                    <h2 class="page-title">Purchase Request </h2>
                </div>
                <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6 text-right">
                    <button
                        onclick="openModal(this,'{{ route('store.purchase-request.create') }}','Add Purchase Request',false,'100%')"
                        type="button" class="btn btn-primary position-relative">
                        Create Purchase Request
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
                                            <div class="col-md-2">
                                                <label for="category_id" class="form-label">Category</label>
                                                <select name="category_id" id="category_id" class="form-control select2">
                                                    <option value="all">All Categories</option>
                                                    @foreach ($categories as $category)
                                                        <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                                                            {{ $category->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-2">
                                                <label for="status" class="form-label">Status</label>
                                                <select name="status" id="status" class="form-control">
                                                    <option value="all" {{ request('status') == 'all' ? 'selected' : '' }}>All Status</option>
                                                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                                                    <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                                                    <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                                                    <option value="reverted" {{ request('status') == 'reverted' ? 'selected' : '' }}>Reverted</option>
                                                </select>
                                            </div>
                                            <div class="col-md-3">
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
                                            <th class="col-3">Purchase Request No</th>
                                            {{-- <th class="col-2">Location</th> --}}
                                            <th class="col-4">Category</th>
                                            <th class="col-2 text-right">Qty</th>
                                            {{-- <th class="col-1 text-right">Approved Qty</th> --}}
                                            <th class="col-1">PR Date</th>
                                            <th class="col-1">Status</th>
                                            <th class="col-2">Action</th>
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
            filterationCommon(`{{ route('store.get.purchase-request') }}`)
            
            // Re-initialize select2 after any AJAX update to preserve selected value in visual UI
            $(document).on('ajaxSuccess', function() {
                $('#category_id, #item_id').select2();
            });
        });
    </script>
@endsection