@extends('management.layouts.master')
@section('title')
Plant Breakdown
@endsection
@section('content')
<div class="content-wrapper">

    <section id="extended">
        <div class="row w-100 mx-auto">
            <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6">
                <h2 class="page-title"> Plant Breakdown List</h2>
            </div>
            <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6 text-right">
                <button onclick="openModal(this,'{{ route('plant-breakdown.create') }}','Add Plant Breakdown',false,'85%')"
                    type="button" class="btn btn-primary position-relative ">
                    Create Plant Breakdown
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
                                            <label for="search" class="form-label">Search</label>
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
                    </div>
                    <div class="card-content">
                        <div class="card-body table-responsive" id="filteredData">
                            <table class="table m-0">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Plant</th>
                                        <th>Production Voucher</th>
                                        <th>User</th>
                                        <th>Breakdown Items</th>
                                        <th>Action</th>
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
        filterationCommon(`{{ route('get.plant-breakdown') }}`)
    });
</script>
@endsection

