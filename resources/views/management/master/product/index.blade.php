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
                                <div class="row ">
                                    <div class="col-md-12 my-1 ">
                                        <div class="row justify-content-end text-right">
                                            <div class="col-md-2">
                                                <label for="customers" class="form-label">Search</label>
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
                                            <th class="col-sm-1">Image </th>
                                            <th class="col-sm-3">Name </th>
                                            <th class="col-sm-4">Description</th>
                                            <th class="col-sm-2">Product Type</th>
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
        $(document).ready(function() {
            filterationCommon(`{{ route('get.product') }}`)
        });

        function check(value){

            var type = 'raw_finish';
            if(value === 'general_items'){
                type = 'general_items';
                $('.showhide').hide();
            }else{
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
                success: function(response) {
                    // Assuming response contains an array of categories
                    if (response.success && response.categories) {
                        // Clear existing options
                        $('#category_id').empty();
                        
                        // Add default option
                        $('#category_id').append('<option value="">Select a category</option>');
                        
                        // Append new category options to the select element
                        $.each(response.categories, function(index, category) {
                            $('#category_id').append(
                                `<option value="${category.id}">${category.name}</option>`
                            );
                        });
                    } else {
                        console.error('No categories found or request failed');
                        $('#category_id').html('<option value="">No categories available</option>');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', status, error);
                    $('#category_id').html('<option value="">Error loading categories</option>');
                }
            });
        }
    </script>
@endsection
