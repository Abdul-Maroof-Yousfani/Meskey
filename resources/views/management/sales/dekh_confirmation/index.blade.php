@extends('management.layouts.master')
@section('title')
    Dekh Confirmation
@endsection
@section('content')
    <div class="content-wrapper">
        <section id="extended">
            <div class="row w-100 mx-auto">
                <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6">
                    <h2 class="page-title">Dekh Confirmation</h2>
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
                                    <div class="px-1 text-left" style="width: 15%;">
                                        <label for="inspection_no" class="form-label">Dekh No</label>
                                        <input type="text" class="form-control" placeholder="Dekh No" name="inspection_no"
                                            value="{{ request('inspection_no', '') }}">
                                    </div>
                                    <div class="px-1 text-left" style="width: 15%;">
                                        <label for="location_id" class="form-label">Location</label>
                                        <select name="location_id" id="location_id" class="form-control select2">
                                            <option value="all">All Locations</option>
                                            @foreach ($locations as $loc)
                                                <option value="{{ $loc->id }}" {{ request('location_id') == $loc->id ? 'selected' : '' }}>
                                                    {{ $loc->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="px-1 text-left" style="width: 15%;">
                                        <label for="completion_status" class="form-label">Completion</label>
                                        <select name="completion_status" id="completion_status" class="form-control select2">
                                            <option value="all">All Status</option>
                                            <option value="pending">Pending Completion</option>
                                            <option value="completed">Completed</option>
                                        </select>
                                    </div>
                                    <div class="px-1 text-left" style="width: 15%;">
                                        <label for="confirmation_approval_status" class="form-label">Confirmation Approval</label>
                                        <select name="confirmation_approval_status" id="confirmation_approval_status" class="form-control select2">
                                            <option value="all">All</option>
                                            <option value="pending">Pending Approval</option>
                                            <option value="approved">Approved</option>
                                            <option value="rejected">Rejected</option>
                                        </select>
                                    </div>
                                    <div class="px-1 text-left" style="width: 18%;">
                                        <label for="date_range" class="form-label">Date Range</label>
                                        <input type="text" class="form-control" name="date_range" id="date_range"
                                            placeholder="Select Date Range"
                                            value="{{ request('date_range', '') }}">
                                    </div>
                                    <div class="px-1 text-left" style="width: 22%;">
                                        <label for="search" class="form-label">Search</label>
                                        <input type="text" class="form-control" id="search"
                                            placeholder="Search Party, Vehicle, Dekh #..." name="search"
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
                                            <th>Dekh #</th>
                                            <th>Date</th>
                                            <th>Location</th>
                                            <th>Party Name & Contact</th>
                                            <th>Items & Weight</th>
                                            <th class="text-center">Completion</th>
                                            <th class="text-center">Approval</th>
                                            <th class="text-center">Vehicle No</th>
                                            <th class="text-center">Action</th>
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
            filterationCommon(`{{ route('sales.get.dekh-confirmation.list') }}`);

            $(document).on('ajaxSuccess', function() {
                $('#location_id, #completion_status, #confirmation_approval_status').select2();
            });
        });
    </script>
@endsection
