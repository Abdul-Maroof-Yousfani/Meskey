@extends('management.layouts.master')
@section('title')
    Permissions
@endsection
@section('content')
    <div class="content-wrapper">
        <section id="extended">
            <div class="row w-100 mx-auto">
                <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6">
                    <h2 class="page-title">Manage Permissions</h2>
                </div>
                <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6 text-right">
                    <button onclick="openModal(this,'{{ route('permission.create') }}','Add Permission')" type="button"
                        class="btn btn-primary position-relative">
                        <i class="ft-plus mr-1"></i> Create Permission
                    </button>
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
                                            <input type="hidden" name="page" value="{{ request('page', 1) }}">
                                            <div class="col-md-2 text-left">
                                                <label for="per_page" class="form-label">Show Records</label>
                                                <select name="per_page" id="per_page" class="form-control">
                                                    <option value="25" {{ request('per_page') == 25 ? 'selected' : '' }}>25</option>
                                                    <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50</option>
                                                    <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>100</option>
                                                    <option value="250" {{ request('per_page') == 250 ? 'selected' : '' }}>250</option>
                                                </select>
                                            </div>
                                            <div class="col-md-4 text-left">
                                                <label for="parent_id" class="form-label">Parent Module / Permission</label>
                                                <select name="parent_id" id="parent_id" class="form-control select2">
                                                    <option value="">All Permissions</option>
                                                    <option value="root">Root Permissions Only (No Parent)</option>
                                                    @foreach ($parents as $parentId => $parentLabel)
                                                        <option value="{{ $parentId }}">{{ $parentLabel }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-3 text-left">
                                                <label for="search" class="form-label">Search</label>
                                                <input type="text" class="form-control" id="search"
                                                    placeholder="Search name or description..." name="search"
                                                    value="{{ request('search', '') }}">
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
                                            <th class="col-sm-1">#</th>
                                            <th class="col-sm-3">Permission Name</th>
                                            <th class="col-sm-3">Parent Module</th>
                                            <th class="col-sm-2">Description</th>
                                            <th class="col-sm-1">Guard</th>
                                            <th class="col-sm-1">Roles</th>
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
            if (typeof $.fn.select2 !== 'undefined') {
                $('#parent_id').select2();
            }
            filterationCommon(`{{ route('get.permissions') }}`);
        });
    </script>
@endsection
