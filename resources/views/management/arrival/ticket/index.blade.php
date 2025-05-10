@extends('management.layouts.master')
@section('title')
    Ticket
@endsection
@section('content')
    <div class="content-wrapper ">
        <section id="extended">
            <div class="row w-100 mx-auto">
                <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6">
                    <h2 class="page-title"> Ticket List</h2>
                </div>
                <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6 text-right">
                    <button onclick="openModal(this,'{{ route('ticket.create') }}','Add Ticket')" type="button"
                        class="btn btn-primary position-relative ">
                        Create Ticket
                    </button>
                </div>
            </div>
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <form id="filterForm" class="form">
                                @php
                                    $today = \Carbon\Carbon::today()->format('Y-m-d');
                                    $oneMonthAgo = \Carbon\Carbon::today()->subMonth()->format('Y-m-d');
                                @endphp

                                <div class="row ">
                                    <div class="col-md-12 my-1 ">
                                        <div class="row justify-content-end text-left">
                                            <div class="col-md-2 ">
                                                <label for="from_date" class="form-label">From Date</label>
                                                <input type="date" class="form-control" id="from_date" name="from_date"
                                                    value="{{ request('from_date', $oneMonthAgo) }}">
                                            </div>
                                            <div class="col-md-2">
                                                <label for="to_date" class="form-label">To Date</label>
                                                <input type="date" class="form-control" id="to_date" name="to_date"
                                                    value="{{ request('to_date', $today) }}">
                                            </div>
                                            <div class="col-md-2">
                                                <label for="search" class="form-label">Search</label>
                                                <input type="hidden" name="page" value="{{ request('page', 1) }}">
                                                <input type="hidden" name="per_page" value="{{ request('per_page', 25) }}">
                                                <input type="text" class="form-control" id="search"
                                                    placeholder="Type Ticket No, Supplier Name " name="search"
                                                    value="{{ request('search') }}">
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
                                            <th class="col-sm-1">Ticket No. </th>
                                            <th class="col-sm-2">Commodity</th>
                                            <th class="col-sm-2">Supplier</th>
                                            <th class="col-sm-1">Truck No</th>
                                            <th class="col-sm-1">Bilty No</th>
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
            filterationCommon(`{{ route('get.ticket') }}`)
        });
    </script>
@endsection
