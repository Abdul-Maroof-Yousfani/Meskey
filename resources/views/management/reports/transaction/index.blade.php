@extends('management.layouts.master')
@section('title')
Accounts Ledger
@endsection
@section('content')
<div class="content-wrapper">

    <section id="extended">
        <div class="row w-100 mx-auto">
            <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6">
                <h2 class="page-title"> Accounts Ledger</h2>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        {{-- <form id="filterForm" class="form">
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
                        </form> --}}

<form id="filterForm">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                 <input type="hidden" name="page" value="{{ request('page', 1) }}">
                                            <input type="hidden" name="per_page" value="{{ request('per_page', 25) }}">
                                    <label>Account</label>
                                    <select class="form-control select2" name="account_id" id="account_id">
                                        <option value="">All Accounts</option>
                                        @foreach($accounts as $account)
                                            <option value="{{ $account->id }}" {{ request('account_id') == $account->id ? 'selected' : '' }}>
                                                {{ $account->name }} ({{ $account->hierarchy_path }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>From Date</label>
                                    <input type="date" name="start_date" class="form-control" value="{{ request('start_date') ?? date('Y-m-01') }}">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>To Date</label>
                                    <input type="date" name="end_date" class="form-control" value="{{ request('end_date') ?? date('Y-m-d') }}">
                                </div>
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary mr-1">Filter</button>
                                <button type="reset" class="btn btn-secondary">Reset</button>
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
                                        <th class="col-sm-4">Name </th>
                                        <th class="col-sm-4">Parent Category </th>
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
    $(document).ready(function () {
        filterationCommon(`{{ route('get.transactions-report') }}`)
    });
</script>
@endsection