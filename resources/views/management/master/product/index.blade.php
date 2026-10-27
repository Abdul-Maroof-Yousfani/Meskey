@extends('management.layouts.master')
@section('title')
    Products
@endsection
@section('content')
    <div class="content-wrapper">

        <section id="extended">
            <div class="row w-100 mx-auto">
                <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6">
                    <h2 class="page-title"> Products List</h2>
                </div>
                <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6 text-right">
                    <button onclick="openModal(this,'{{ route('product.create') }}','Add Product')" type="button"
                        class="btn btn-primary position-relative ">
                        Create Product
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
                                <div class="row align-items-end">
                                    <div class="col-md-3 col-sm-6 mb-1">
                                        <label for="category_id_filter" class="form-label font-weight-bold">Category</label>
                                        <select name="category_id" id="category_id_filter" class="form-control select2">
                                            <option value="all">All Categories</option>
                                            @foreach ($categories as $category)
                                                <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                                                    {{ $category->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3 col-sm-6 mb-1">
                                        <label for="parent_id_filter" class="form-label font-weight-bold">Parent</label>
                                        <select name="parent_id" id="parent_id_filter" class="form-control select2">
                                            <option value="all">All Parents</option>
                                            @foreach ($parentProducts as $parentProduct)
                                                <option value="{{ $parentProduct->id }}" {{ request('parent_id') == $parentProduct->id ? 'selected' : '' }}>
                                                    {{ $parentProduct->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4 col-sm-6 mb-1">
                                        <label for="search" class="form-label font-weight-bold">Search</label>
                                        <input type="text" class="form-control" id="search"
                                            placeholder="Search by name, code, barcode..." name="search"
                                            value="{{ request('search', '') }}">
                                    </div>
                                    <div class="mb-1">
                                        <button type="button" id="resetBtn" class="btn btn-outline-secondary btn-block">
                                            <i class="ft-rotate-ccw"></i>
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="card-content">
                            <div class="card-body table-responsive" id="filteredData">
                                <table class="table m-0">
                                    <thead>
                                        <tr>
                                            <th class="col-sm-1">Image </th>
                                            <th class="col-sm-1">Type </th>
                                            <th class="col-sm-3">Name </th>
                                            <th class="col-sm-3">Parent</th>
                                            <th class="col-sm-1">Product Type</th>
                                            <th class="col-sm-1">Category</th>
                                            <th class="col-sm-1">Status</th>
                                            <th class="col-sm-2">Created</th>
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
            $('#category_id_filter, #parent_id_filter').select2();

            filterationCommon(`{{ route('get.product') }}`);

            // Reset page to 1 when any filter changes
            $('#filterForm').on('change keyup', 'input:not([name="page"]):not([name="per_page"]), select', function() {
                $('#filterForm input[name="page"]').val(1);
            });

            // Re-initialize select2 after any AJAX update to preserve selected value in visual UI
            $(document).on('ajaxSuccess', function() {
                $('#category_id_filter, #parent_id_filter').select2();
            });

            // Reset button handler
            $('#resetBtn').on('click', function () {
                $('#search').val('');
                $('#filterForm input[name="page"]').val(1);
                $('#category_id_filter').val('all').trigger('change');
                $('#parent_id_filter').val('all').trigger('change');
            });
        });

        function check(value) {

            var type = 'raw_finish';
            if (value === 'general_items') {
                type = 'general_items';
                $('.showhide').hide();
            } else {
                $('.showhide').show();
            }

            filter_categories(type);
        }

        function filter_categories(type) {
            $.ajax({
                url: '{{route('get.categories')}}', // Replace with your actual API endpoint
                type: 'GET',
                data: { category_type: type },
                dataType: 'json',
                success: function (response) {
                    // Assuming response contains an array of categories
                    if (response.success && response.categories) {
                        // Clear existing options
                        $('#category_id').empty();

                        // Add default option
                        $('#category_id').append('<option value="">Select a category</option>');

                        // Append new category options to the select element
                        $.each(response.categories, function (index, category) {
                            $('#category_id').append(
                                `<option value="${category.id}">${category.name}</option>`
                            );
                        });
                    } else {
                        console.error('No categories found or request failed');
                        $('#category_id').html('<option value="">No categories available</option>');
                    }
                },
                error: function (xhr, status, error) {
                    console.error('AJAX Error:', status, error);
                    $('#category_id').html('<option value="">Error loading categories</option>');
                }
            });
        }
    </script>
@endsection