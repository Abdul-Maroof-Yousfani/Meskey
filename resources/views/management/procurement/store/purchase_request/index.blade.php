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
                                <div class="row mx-0">
                                    <div class="px-1 text-left" style="width: 13%;">
                                        <label for="pr_no" class="form-label">PR No</label>
                                        <input type="text" class="form-control" id="pr_no"
                                            placeholder="PR No" name="pr_no"
                                            value="{{ request('pr_no', '') }}">
                                    </div>
                                    <div class="px-1 text-left" style="width: 15%;">
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
                                    <div class="px-1 text-left" style="width: 22%;">
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
                                    <div class="px-1 text-left" style="width: 10%;">
                                        <label for="qty" class="form-label">Qty</label>
                                        <input type="text" class="form-control" id="qty"
                                            placeholder="Qty" name="qty"
                                            value="{{ request('qty', '') }}">
                                    </div>
                                    <div class="px-1 text-left" style="width: 13%;">
                                        <label for="date_range" class="form-label">PR Date</label>
                                        <input type="text" class="form-control" name="date_range" id="date_range" 
                                            placeholder="Select Date Range"
                                            value="{{ request('date_range', '') }}">
                                    </div>
                                    <div class="px-1 text-left" style="width: 15%;">
                                        <label for="status" class="form-label">Status</label>
                                        <select name="status" id="status" class="form-control select2">
                                            <option value="all" {{ request('status') == 'all' ? 'selected' : '' }}>All Status</option>
                                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                                            <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                                            <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                                            <option value="reverted" {{ request('status') == 'reverted' ? 'selected' : '' }}>Reverted</option>
                                        </select>
                                    </div>
                                    <div class="px-1 text-left" style="width: 12%;">
                                        <label for="search" class="form-label">Search</label>
                                        <input type="hidden" name="page" value="{{ request('page', 1) }}">
                                        <input type="hidden" name="per_page" value="{{ request('per_page', 25) }}">
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
                                <table class="table m-0" style="table-layout: fixed; width: 100%;">
                                    <thead>
                                        <tr>
                                            <th style="width: 13%;">Purchase Request No</th>
                                            <th style="width: 15%;">Category</th>
                                            <th style="width: 22%;">Item</th>
                                            <th style="width: 10%;">Qty</th>
                                            <th style="width: 13%;">PR Date</th>
                                            <th style="width: 15%;">Status</th>
                                            <th style="width: 12%;">Action</th>
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
                $('#category_id, #item_id, #status').select2();
            });

            $('#category_id').on('change', function() {
                let category_id = $(this).val();
                let $itemSelect = $('#item_id');
                
                if (category_id && category_id !== 'all') {
                    $.ajax({
                        url: '{{ route('store.purchase-request.get-products-json') }}',
                        type: 'GET',
                        data: { category_id: category_id },
                        success: function(response) {
                            if (response.success) {
                                $itemSelect.empty();
                                $itemSelect.append('<option value="all">All Items</option>');
                                $.each(response.products, function(index, item) {
                                    $itemSelect.append('<option value="' + item.id + '">' + item.name + '</option>');
                                });
                                $itemSelect.select2();
                            }
                        }
                    });
                } else if (category_id === 'all') {
                    // Optionally reset items to all items or just leave it
                }
            });

            // Select first category on load if not already selected
            if ($('#category_id').val() == 'all' || !$('#category_id').val()) {
                let firstCat = $('#category_id option:eq(1)').val();
                if (firstCat && firstCat !== 'all') {
                    $('#category_id').val(firstCat).trigger('change');
                }
            }
        });
    </script>
@endsection