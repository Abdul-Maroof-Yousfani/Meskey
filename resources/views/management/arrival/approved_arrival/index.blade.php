@extends('management.layouts.master')
@section('title')
    Half/Full Approved
@endsection
@section('content')
    <div class="content-wrapper">

        <section id="extended">
            <div class="row w-100 mx-auto">
                <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6">
                    <h2 class="page-title"> Half/Full Approved</h2>
                </div>
                <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6 text-right">
                    <button onclick="openModal(this,'{{ route('arrival-approve.create') }}','Add  Half/Full Approved')"
                        type="button" class="btn btn-primary position-relative ">
                        Create Half/Full Approved
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
                                            <th class="col-sm-2">Ticket No.</th>
                                            <th class="col-sm-1">Product</th>
                                            <th class="col-sm-1">Gala Name</th>
                                            <th class="col-sm-1">Truck No</th>
                                            <th class="col-sm-2">Bags Detail</th>
                                            {{-- <th class="col-sm-1">Bag</th>
                                            <th class="col-sm-1">Bag Condition</th>
                                            <th class="col-sm-1">Bag Packing</th> --}}
                                            <th class="col-sm-1">Approval Type</th>
                                            <th class="col-sm-1">Recv. / Rej.</th>
                                            {{-- <th class="col-sm-1">Rejections</th> --}}
                                            <th class="col-sm-1">Amanat</th>
                                            <th class="col-sm-1">Created At</th>
                                            <th class="col-sm-1">Actions</th>
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
            filterationCommon(`{{ route('get.arrival-approve') }}`)
        });
    </script>
@endsection