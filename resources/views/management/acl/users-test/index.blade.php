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
                                        <div class="row justify-content-end text-right">
                                            <div class="col-md-2">
                                                <label for="customers" class="form-label">Search</label>
                                                <input type="hidden" name="page" value="{{ request('page', 1) }}">
                                                <input type="hidden" name="per_page" value="{{ request('per_page', 25) }}">
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
                                            <th class="col-sm-1">Image</th>
                                            <th class="col-sm-2">Name</th>
                                            <th class="col-sm-2">Username</th>
                                            <th class="col-sm-3">Role</th>
                                            <th class="col-sm-3">Companies Assign</th>
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
        $(document).ready(function() {
            filterationCommon(`{{ route('get.users.test') }}`)
        });
    </script>
@endsection
