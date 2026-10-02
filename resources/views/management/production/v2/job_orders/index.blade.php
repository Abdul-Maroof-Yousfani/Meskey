@extends('management.layouts.master')
@section('title')
    Job Orders (V2)
@endsection
@section('content')
    <div class="content-wrapper">
        <section id="extended">
            <div class="row w-100 mx-auto">
                <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6">
                    <h2 class="page-title">Job Orders (V2)</h2>
                </div>
                <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6 text-right">
                    <a href="{{ route('production.job-orders.create') }}" 
                        onclick="loadPageContent('{{ route('production.job-orders.create') }}')"
                        class="btn btn-primary position-relative">
                        <i class="ft-plus mr-1"></i>Create Job Order
                    </a>
                </div>
            </div>
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <form id="filterForm" class="form">
                                <input type="hidden" name="page" value="{{ request('page', 1) }}">
                                <input type="hidden" name="per_page" value="{{ request('per_page', 25) }}">
                                <div class="row">
                                    <div class="col-md-12 my-1">
                                        <div class="row justify-content-end text-right">
                                            <div class="col-md-3 text-left">
                                                <label for="company_location_id" class="form-label">Location</label>
                                                <select name="company_location_id" id="company_location_id" class="form-control select2">
                                                    <option value="">All Locations</option>
                                                    @foreach($locations as $loc)
                                                        <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-2 text-left">
                                                <label for="status" class="form-label">Status</label>
                                                <select name="status" id="status" class="form-control select2">
                                                    <option value="">All Statuses</option>
                                                    <option value="draft">Draft</option>
                                                    <option value="posted">Posted</option>
                                                    <option value="approved">Approved</option>
                                                    <option value="rejected">Rejected</option>
                                                    <option value="in_progress">In Progress</option>
                                                    <option value="completed">Completed</option>
                                                </select>
                                            </div>
                                            <div class="col-md-2 text-left">
                                                <label for="from_date" class="form-label">From Date</label>
                                                <input type="date" class="form-control" name="from_date" id="from_date">
                                            </div>
                                            <div class="col-md-2 text-left">
                                                <label for="to_date" class="form-label">To Date</label>
                                                <input type="date" class="form-control" name="to_date" id="to_date">
                                            </div>
                                            <div class="col-md-3 text-left">
                                                <label for="search" class="form-label">Search</label>
                                                <input type="text" class="form-control" id="search"
                                                    placeholder="Search here" name="search" value="">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="card-content">
                            <div class="card-body table-responsive" id="filteredData">
                                <table class="table m-0">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Job Order #</th>
                                            <th>Date</th>
                                            <th>Location</th>
                                            <th>Phases</th>
                                            <th>Export Order</th>
                                            <th>Ref No</th>
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
            filterationCommon(`{{ route('production.job-orders.getList') }}`);
        });
    </script>
@endsection
