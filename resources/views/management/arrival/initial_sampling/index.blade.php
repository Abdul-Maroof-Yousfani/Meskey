@extends('management.layouts.master')
@section('title')
    Initial Sampling
@endsection
@section('content')
    <div class="content-wrapper">

        <section id="extended">
            {{-- mph-head: mobile par app-bar (partials/mobile-page-head). Desktop same. --}}
            @include('management.layouts.partials.mobile-page-head')
            <div class="row w-100 mx-auto align-items-center mph-head">
                <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6 col-6 mph-title-col">
                    <div class="mph-sub">Arrival</div>
                    <h2 class="page-title mb-0"> {{ $isResampling ? 'Initial Re-Sampling' : 'Initial Sampling' }} </h2>
                </div>
                <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6 text-right col-6 mph-actions">
                    <!-- <button
                                        onclick="openModal(this,'{{ route($isResampling ? 'initial-resampling.create' : 'initialsampling.create') }}','{{ $isResampling ? 'Create Initial Re-Sampling' : 'Create Initial Sampling' }}')"
                                        type="button" class="btn btn-primary position-relative ">
                                        {{ $isResampling ? 'Create Initial Re-Sampling' : 'Create Initial Sampling' }}
                                    </button> -->
                    @php $createLabel = $isResampling ? 'Create Initial Re-Sampling' : 'Create Initial Sampling'; @endphp
                    <a href="{{ route($isResampling ? 'initial-resampling.create' : 'initialsampling.create') }}"
                        class="btn btn-primary position-relative mph-create" aria-label="{{ $createLabel }}"
                        title="{{ $createLabel }}">
                        <i class="ft-plus mph-create-icon"></i><span class="mph-create-text">{{ $createLabel }}</span>
                    </a>
                </div>
            </div>
            <div class="row mt-5">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            {{-- data-filter-apply: filter sirf Search (desktop) / Show results (mobile) par --}}
                            <form id="filterForm" class="form" data-filter-apply>
                                <div class="row mx-0 align-items-end flex-nowrap"
                                    style="overflow-x: auto; padding-bottom: 10px;">
                                    <div class="px-1 text-left" style="min-width: 180px; flex: 1 1 180px;">
                                        <label for="daterange" class="form-label text-nowrap">Date Range</label>
                                        <input type="text" name="daterange" class="form-control"
                                            value="{{ request('daterange', \Carbon\Carbon::now()->subYear()->format('m/d/Y') . ' - ' . \Carbon\Carbon::now()->format('m/d/Y')) }}" />
                                    </div>
                                    <div class="px-1 text-left" style="min-width: 180px; flex: 1 1 180px;">
                                        <label for="unique_no" class="form-label text-nowrap">Ticket No</label>
                                        <input type="text" class="form-control" name="unique_no" id="unique_no"
                                            placeholder="Ticket No">
                                    </div>
                                    <div class="px-1 text-left" style="min-width: 180px; flex: 1 1 180px;">
                                        <label for="commodity_f" class="form-label text-nowrap">Commodity</label>
                                        <select name="commodity" id="commodity_f" class="form-control select2">
                                            <option value="">All Commodities</option>
                                        </select>
                                    </div>


                                    <div class="px-1 text-left" style="min-width: 180px; flex: 1 1 180px;">
                                        <label for="remark" class="form-label text-nowrap">Remark</label>
                                        <input type="text" class="form-control" name="remark" id="remark"
                                            placeholder="Remark">
                                    </div>
                                    {{-- Search: desktop par; mobile par sheet ka "Show results" yahi kaam karta hai --}}
                                    <div class="px-1 text-left hide-mobile" style="flex: 0 0 auto;">
                                        <button type="submit" class="btn btn-primary mb-0" title="Apply filters">
                                            <i class="ft-search mr-1"></i> Search
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="card-content">
                            <div class="card-body table-responsive" id="filteredData">
                                <table class="table m-0 hide-mobile">
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
        $(document).ready(function () {
            // Filter sirf Search / Show results / Enter par (change par nahi) - scripts.js filterationCommon_withbtn
            const runListFilter = filterationCommon_withbtn(`{{ route($isResampling ? 'get.initial-resampling' : 'get.initialsampling') }}`);
            window.filterRunners = window.filterRunners || {};
            window.filterRunners.filterForm = runListFilter; // list refresh (save ke baad)
            $('#filterForm').on('submit', function (e) {
                e.preventDefault();
                runListFilter();
            });
            runListFilter(true); // pehla load: URL wala page
            initializeDynamicSelect2('#commodity_f', 'products', 'name', 'id', true, false, true, true);
            initializeDynamicSelect2('#supplier_id_f', 'suppliers', 'name', 'id', true, false, true, true);
        });
    </script>
@endsection