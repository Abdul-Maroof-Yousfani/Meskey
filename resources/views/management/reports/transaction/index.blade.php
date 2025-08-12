@extends('management.layouts.master')
@section('title')
    Accounts Ledger
@endsection
@section('content')
    <div class="content-wrapper">

        <section id="extended">
            <div class="row w-100 mx-auto">
                <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6">
                    <h2 class="page-title">Accounts Ledger</h2>
                </div>
            </div>
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <form id="filterForm">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <input type="hidden" name="page" value="{{ request('page', 1) }}">
                                            <input type="hidden" name="per_page" value="{{ request('per_page', 25) }}">
                                            <label>Account</label>
                                            <select class="form-control selectWithoutAjax" name="account_id"
                                                id="account_id">
                                                <option value="">All Accounts</option>
                                                @foreach ($accounts as $account)
                                                    <option value="{{ $account->id }}"
                                                        {{ request('account_id') == $account->id ? 'selected' : '' }}>
                                                        {{ $account->name }} ({{ $account->hierarchy_path }})
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group mb-0">
                                            <label>Date:</label>
                                            <input type="text" name="daterange" class="form-control" id="daterange"
                                                value="{{ request('daterange', \Carbon\Carbon::now()->subMonth()->format('m/d/Y') . ' - ' . \Carbon\Carbon::now()->format('m/d/Y')) }}" />
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
                                            <th class="col-sm-4">Name</th>
                                            <th class="col-sm-4">ParentCategory</th>
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
            filterationCommon(`{{ route('get.transactions-report') }}`)
        });
    </script>
@endsection
