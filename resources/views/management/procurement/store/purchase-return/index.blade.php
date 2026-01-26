@extends('management.layouts.master')
@section('title')
    Purchase Return
@endsection
@section('content')
    <div class="content-wrapper">
        <section id="extended">
            <div class="row w-100 mx-auto">
                <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6">
                    <h2 class="page-title">Purchase Return</h2>
                </div>
                <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6 text-right">
                    <button
                        onclick="openModal(this,'{{ route('store.purchase-return.create') }}','Create Purchase Return',false,'100%')"
                        type="button" class="btn btn-primary position-relative">
                        Create Purchase Return
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
                                                <label for="suppliers" class="form-label">Search</label>
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
                                            <th width="7%">PR No</th>
                                            <th width="9%">PB No</th>
                                            <th width="13%">Supplier</th>
                                            <th width="10%">Item</th>
                                            <th width="6%" class="text-right">Qty</th>
                                            <th width="6%" class="text-right">Rate</th>
                                            <th width="6%" class="text-right">Disc %</th>
                                            <th width="6%" class="text-right">Disc Amt</th>
                                            <th width="6%" class="text-right">Amount</th>
                                            <th width="5%">Status</th>
                                            <th width="8%">Created</th>
                                            <th width="13%">Action</th>
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
            filterationCommon(`{{ route('store.get.purchase-return') }}`)
        });
    </script>
@endsection

