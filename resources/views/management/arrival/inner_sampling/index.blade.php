@extends('management.layouts.master')
@section('title')
    Inner Sampling
@endsection
@section('content')
    <div class="content-wrapper">

        <section id="extended">
            @include('management.layouts.partials.mobile-page-head')
            <div class="row w-100 mx-auto mph-head">
                <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6 mph-title-col">
                    <div class="mph-sub">Arrival</div>
                    <h2 class="page-title"> {{ $isResampling ? 'Inner Re-Sampling' : 'Inner Sampling' }}</h2>
                </div>
                <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6 text-right mph-actions">
                    <!-- <button
                                        onclick="openModal(this,'{{ route($isResampling ? 'inner-resampling.create' : 'inner-sampling.create') }}','{{ $isResampling ? 'Create Inner Re-Sampling' : 'Create Inner Sampling' }}')"
                                        type="button" class="btn btn-primary position-relative">
                                        {{ $isResampling ? 'Create Inner Re-Sampling' : 'Create Inner Sampling' }}
                                    </button> -->
                    @php $createLabel = $isResampling ? 'Create Inner Re-Sampling' : 'Create Inner Sampling'; @endphp
                    <a href="{{ route($isResampling ? 'inner-resampling.create' : 'inner-sampling.create') }}"
                        class="btn btn-primary position-relative mph-create" aria-label="{{ $createLabel }}"
                        title="{{ $createLabel }}">
                        <i class="ft-plus mph-create-icon"></i><span class="mph-create-text">{{ $createLabel }}</span>
                    </a>
                </div>
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
                                                    placeholder="Type Ticket No, Supplier Name " name="search"
                                                    value="{{ request('search') }}">
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


                            {{-- <a href="{{ route('export-roles') }}" class="btn btn-warning">Export Roles</a> --}}
                        </div>
                        <div class="card-content">
                            <div class="card-body table-responsive" id="filteredData">
                                <table class="table m-0 hide-mobile">
                                    <thead>
                                        <tr>
                                            <th class="col-sm-2">Ticket No. </th>
                                            <th class="col-sm-2">Product</th>
                                            {{-- <th class="col-sm-2">Arroval Status</th> --}}
                                            <th class="col-sm-4">Remark</th>
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
            const runListFilter = filterationCommon_withbtn(`{{ route($isResampling ? 'get.inner-resampling' : 'get.inner-sampling') }}`);
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