@extends('management.layouts.master')
@section('title')
Category
@endsection
@section('content')
<div class="content-wrapper">

    <section id="extended">
        <div class="row w-100 mx-auto">
            <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6">
                <h2 class="page-title"> Category</h2>
            </div>
            <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6 text-right">
                <button onclick="openModal(this,'{{ route('category.create') }}','Add Category')" type="button"
                    class="btn btn-primary position-relative ">
                    Create Category
                </button>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <form id="filterForm" class="form">
                            <div class="row ">
                                <div class="col-md-1 my-1">
                                    <label for="customers" class="form-label">Show Entries</label>
                                    <select name="per_page" class="form-control">
                                        <option value="25">25 / Page</option>
                                        <option value="50">50 / Page</option>
                                        <option value="100">100 / Page</option>
                                    </select>
                                </div>

                                <div class="col-md-11 my-1 ">
                                    <div class="row justify-content-end text-right">
                                        <div class="col-md-2">
                                            <label for="customers" class="form-label">Search</label>
                                            <input type="text" class="form-control" id="search"
                                                placeholder="Search here" name="search" value="">
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
                                        <th class="col-sm-4">Name </th>
                                        <th class="col-sm-4">Parent Category </th>
                                        <th class="col-sm-2">Action</th>
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
        filterationCommon(`{{ route('get.category') }}`)
    });
</script>
@endsection