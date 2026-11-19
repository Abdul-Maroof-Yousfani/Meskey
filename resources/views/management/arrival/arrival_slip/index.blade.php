@extends('management.layouts.master')
@section('title')
    Arrival Slip
@endsection
@section('content')
    <div class="content-wrapper">

        <section id="extended">
            @include('management.layouts.partials.mobile-page-head')
            <div class="row w-100 mx-auto mph-head">
                <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6 mph-title-col">
                    <div class="mph-sub">Arrival</div>
                    <h2 class="page-title"> Arrival Slips</h2>
                </div>
                {{-- mobile par filter button yahan aata hai --}}
                <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6 text-right mph-actions"></div>
                {{-- <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6 text-right">
                    <button onclick="openModal(this,'{{ route('arrival-slip.create') }}','Create Arrival Slip')"
                        type="button" class="btn btn-primary position-relative ">
                        Create Arrival Slip
                    </button>
                </div> --}}
            </div>
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <form id="filterForm" class="form" data-filter-sheet data-filter-apply>
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
                                            {{-- Search: desktop par; mobile par sheet ka "Show results" yahi kaam karta hai --}}
                                            <div class="col-md-auto text-left hide-mobile d-flex align-items-end">
                                                <button type="submit" class="btn btn-primary mb-0" title="Apply filters">
                                                    <i class="ft-search mr-1"></i> Search
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="card-content">
                            <div class="card-body table-responsive" id="filteredData">
                                <table class="table m-0 hide-mobile">
                                    <thead>
                                        <tr>
                                            <th class="col-sm-1">Ticket No </th>
                                            <th class="col-sm-3">Name </th>
                                            <th class="col-sm-4">Description</th>
                                            <th class="col-sm-1">Name </th>
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
    <link rel="stylesheet" href="https://printjs-4de6.kxcdn.com/print.min.css">
    <script src="https://printjs-4de6.kxcdn.com/print.min.js"></script>
@endsection
@section('script')
    <script>
        $(document).ready(function() {
            // Filter sirf Search / Show results / Enter par (change par nahi) - scripts.js filterationCommon_withbtn
            const runListFilter = filterationCommon_withbtn(`{{ route('get.arrival-slip') }}`);
            window.filterRunners = window.filterRunners || {};
            window.filterRunners.filterForm = runListFilter; // list refresh (save ke baad)
            $('#filterForm').on('submit', function (e) {
                e.preventDefault();
                runListFilter();
            });
            runListFilter(true); // pehla load: URL wala page
        });
    </script>
@endsection
