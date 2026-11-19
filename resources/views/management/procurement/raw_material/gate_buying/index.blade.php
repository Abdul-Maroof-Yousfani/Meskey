@extends('management.layouts.master')
@section('title')
    Gate Buying
@endsection
@section('content')
    <div class="content-wrapper">
        <section id="extended">
            {{-- mph-head: mobile par app-bar (partials/mobile-page-head). Desktop same. --}}
            @include('management.layouts.partials.mobile-page-head')
            <div class="row w-100 mx-auto align-items-center mph-head">
                <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6 mph-title-col">
                    <div class="mph-sub">Purchase Contract</div>
                    <h2 class="page-title mb-0">Gate Buying</h2>
                </div>
                <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6 text-right mph-actions">
                    <button
                        onclick="openModal(this,'{{ route('raw-material.gate-buying.create') }}','Add Gate Buying (Raw Material)')"
                        type="button" class="btn btn-primary position-relative mph-create"
                        aria-label="Create Gate Buying" title="Create Gate Buying">
                        <i class="ft-plus mph-create-icon"></i><span class="mph-create-text">Create Gate Buying</span>
                    </button>
                </div>
            </div>
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <form id="filterForm" class="form" data-filter-sheet data-filter-apply>
                                <div class="row ">
                                    <div class="col-md-12 my-1 ">
                                        <div class="row justify-content-end align-items-end">
                                            <div class="col-md-2">
                                                <div class="form-group mb-0">
                                                    <label for="search" class="form-label">Search</label>
                                                    <input type="hidden" name="page" value="{{ request('page', 1) }}">
                                                    <input type="hidden" name="per_page" value="{{ request('per_page', 25) }}">
                                                    <input type="text" class="form-control" id="search"
                                                        placeholder="Search here" name="search"
                                                        value="{{ request('search', '') }}">
                                                </div>
                                            </div>
                                            {{-- Search: desktop par; mobile par filter sheet ka "Show results" yahi kaam karta hai --}}
                                            <div class="col-md-auto text-left hide-mobile d-flex align-items-end mt-2">
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
                                            <th class="col-sm-2">Contract No</th>
                                            <th class="col-sm-2">Supplier</th>
                                            <th class="col-sm-2">Purchaser</th>
                                            <th class="col-sm-2">Contact Person</th>
                                            <th class="col-sm-2">Rate</th>
                                            <th class="col-sm-1">Created</th>
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
            // Filter sirf Search / Show results / Enter par (change par nahi) - scripts.js filterationCommon_withbtn
            const runListFilter = filterationCommon_withbtn(`{{ route('raw-material.get.gate-buying') }}`);
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
