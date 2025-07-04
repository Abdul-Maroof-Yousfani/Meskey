@extends('management.layouts.master')
@section('title')
    Payment Request
@endsection
@section('content')
    <div class="content-wrapper">
        <section id="extended">
            <div class="row w-100 mx-auto">
                <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6">
                    <h2 class="page-title">Payment Request {{ $isTicket ? '(Ticket)' : '' }}</h2>
                </div>
                {{-- <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6 text-right">
                    <button
                        onclick="openModal(this,'{{ route('raw-material.payment-request.create') }}','Add Payment Request (Raw Material)')"
                        type="button" class="btn btn-primary position-relative ">
                        Create Payment Request
                    </button>
                </div> --}}
            </div>
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <form id="filterForm" class="form">
                                <div class="row ">
                                    <div class="col-md-12 my-1 ">
                                        <div class="row justify-content-end text-right1">
                                            <div class="col-md-2">
                                                <label for="customers" class="form-label">Supplier</label>
                                                <select name="supplier_id" id="supplier_id" class="form-control">
                                                    <option value="">
                                                        Select Supplier</option>
                                                </select>
                                            </div>
                                            <div class="col-md-2">
                                                <label for="customers" class="form-label">Commodity</label>
                                                <select name="product_id" id="product_id" class="form-control">
                                                    <option value="">
                                                        Select Commodity</option>
                                                </select>
                                            </div>
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
                        </div>
                        <div class="card-content">
                            <div class="card-body table-responsive" id="filteredData">
                                <table class="table m-0">
                                    <thead>
                                        <tr>
                                            <th class="col-sm-2">Contract No</th>
                                            <th class="col-sm-2">Supplier</th>
                                            <th class="col-sm-1">Commodity</th>
                                            <th class="col-sm-1">Loading date</th>
                                            <th class="col-sm-2">Amounts</th>
                                            <th class="col-sm-2">Total Requested Amount</th>
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
            initializeDynamicSelect2('#supplier_id', 'suppliers', 'name', 'id', true, false, true, true);
            initializeDynamicSelect2('#product_id', 'products', 'name', 'id', true, false, true, true);
            filterationCommon(
                `{{ route($isTicket ? 'raw-material.ticket.get.payment-request' : 'raw-material.get.payment-request') }}`
            );
        });
    </script>
@endsection
