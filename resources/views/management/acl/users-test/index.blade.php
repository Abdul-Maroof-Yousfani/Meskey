@extends('management.layouts.master')
@section('title')
    Users
@endsection

@section('content')
    <div class="content-wrapper">

        <section id="extended">
            <div class="row w-100 mx-auto">

                <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6">
                    <h2 class="page-title"> Users</h2>
                </div>
                <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6 text-right">
                    <button onclick="exportUsersToExcel()" type="button"
                        class="btn btn-success position-relative mr-1">
                        <i class="ft-download mr-1"></i> Export Excel
                    </button>
                    @canAccess('user-create')
                    <button onclick="openModal(this,'{{ route('users-test.create') }}','Create User')" type="button"
                        class="btn btn-primary position-relative ">
                        Create User
                    </button>
                    @endcanAccess
                </div>
            </div>
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <form id="filterForm" class="form">
                                <div class="row ">
                                    <div class="col-md-12 my-1 ">
                                        <div class="row justify-content-end text-left">
                                            <div class="col-md-3">
                                                <div class="form-group mb-0">
                                                    <label for="company_location_id" class="form-label">Location</label>
                                                    <select name="company_location_id" id="company_location_id" class="form-control select2">
                                                        <option value="">All Locations</option>
                                                        @foreach ($locations as $location)
                                                            <option value="{{ $location->id }}">{{ $location->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="form-group mb-0">
                                                    <label for="search" class="form-label">Search</label>
                                                    <input type="hidden" name="page" value="{{ request('page', 1) }}">
                                                    <input type="hidden" name="per_page" value="{{ request('per_page', 25) }}">
                                                    <input type="text" class="form-control" id="search"
                                                        placeholder="Search here" name="search" value="">
                                                </div>
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
                                            <th class="col-sm-2">Name</th>
                                            <th class="col-sm-1">Parent</th>
                                            <th class="col-sm-1 text-center">PO Approval</th>
                                            <th class="col-sm-2">Role</th>
                                            <th class="col-sm-2">Companies Assign</th>
                                            <th class="col-sm-2">Location/Sublocation</th>
                                            <th class="col-sm-1">COA Hierarchy</th>
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
            filterationCommon(`{{ route('get.users.test') }}`);
        });

        function exportUsersToExcel() {
            const formData = $('#filterForm').serialize();
            window.location.href = `{{ route('export-users.test') }}?${formData}`;
        }
    </script>
@endsection
