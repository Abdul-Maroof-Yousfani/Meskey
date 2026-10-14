@extends('management.layouts.master')
@section('title')
    Drying Job Orders
@endsection
@section('content')
    <div class="content-wrapper">
        <section id="extended">
            <div class="row w-100 mx-auto">
                <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6">
                    <h2 class="page-title">Drying Job Orders</h2>
                </div>
                <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6 text-right">
                    <a href="{{ route('production-drying-job-order.create') }}" onclick="loadPageContent('{{ route('production-drying-job-order.create') }}')"
                        class="btn btn-primary position-relative">
                        <i class="ft-plus mr-1"></i>Create Drying Job Order
                    </a>
                </div>
            </div>
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <form id="filterForm" class="form">
                                <div class="row">
                                    <div class="col-md-12 my-1">
                                        <div class="row justify-content-end text-right">
                                            <div class="col-md-3">
                                                <input type="hidden" name="page" value="{{ request('page', 1) }}">
                                                <input type="hidden" name="per_page" value="{{ request('per_page', 25) }}">
                                                <label for="search" class="form-label">Search</label>
                                                <input type="text" class="form-control" id="search"
                                                    placeholder="Search Job Order / Ref / EO / Crop Year..." name="search" value="">
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
                                            <th>Job Order No#</th>
                                            <th>Date</th>
                                            <th>Location</th>
                                            <th>Commodity</th>
                                            <th>Crop Year</th>
                                            <th>Moisture Level</th>
                                            <th>Specs & Recipe</th>
                                            <th>Status</th>
                                            <th>Actions</th>
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
            filterationCommon(`{{ route('get.production-drying-job-order') }}`);
        });
    </script>
@endsection
