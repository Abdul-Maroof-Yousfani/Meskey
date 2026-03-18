@extends('management.layouts.master')
@section('title')
    Proforma
@endsection
@section('content')
    <div class="content-wrapper">

        <section id="extended">
            <div class="row w-100 mx-auto">
                <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6">
                    <h2 class="page-title">Select Export Order For Proforma</h2>
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
                                                <label for="customers" class="form-label">Search</label>
                                                <input type="hidden" name="page" value="{{ request('page', 1) }}">
                                                <input type="hidden" name="per_page" value="{{ request('per_page', 25) }}">
                                                <input type="text" class="form-control" id="search"
                                                    placeholder="Search here" name="search" value="">
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
                                            <th width="5%">S no.</th>
                                            <th width="16%">Export Order No</th>
                                            <th width="16%">Contract No</th>
                                            <th width="16%">Mode of Terms/Payment</th>
                                            <th width="16%">Export Order Date</th>
                                            <th width="16%">Customer</th>
                                            <th width="15%">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @if (count($export_orders) != 0)
                                            @foreach ($export_orders as $key => $export)
                                                <tr>
                                                    <td>{{ $key + 1 }}</td>
                                                    <td>{{ $export->voucher_no ?? '' }} </td>
                                                    <td>{{ $export->contract_no ?? '' }}</td>
                                                    <td>{{ $export->modeOfTerm?->name ?? 'N/A' }}</td>
                                                    <td>{{ \Carbon\Carbon::parse($export->voucher_date)->format('d/m/Y') }}</td>
                                                    <td>{{ $export->buyer?->name ?? 'N/A' }}</td>
                                                    <td>
                                                        @canAccess('export-order-show')
                                                        <a class="info p-1 text-center position-relative "
                                                            onclick="openModal(this,'{{ route('export-order.show', $export->id) }}','Show Export Order',false,'90%')">
                                                            <i class="ft-eye font-medium-3"></i></a>
                                                        @endcanAccess
                                                        @canAccess('create-proforma')
                                                        <a class="info p-1 text-center position-relative "
                                                            onclick="openModal(this,'{{ route('proforma.create', $export->id) }}','Create Proforma',false,'90%')">
                                                            <i class="ft-plus font-medium-3"></i></a>
                                                        @endcanAccess
                                                    </td>
                                                </tr>
                                            @endforeach
                                        @else
                                            <tr class="ant-table-placeholder">
                                                <td colspan="11" class="ant-table-cell text-center">
                                                    <div class="my-5">
                                                        <svg width="64" height="41" viewBox="0 0 64 41"
                                                            xmlns="http://www.w3.org/2000/svg">
                                                            <g transform="translate(0 1)" fill="none"
                                                                fill-rule="evenodd">
                                                                <ellipse fill="#f5f5f5" cx="32" cy="33"
                                                                    rx="32" ry="7">
                                                                </ellipse>
                                                                <g fill-rule="nonzero" stroke="#d9d9d9">
                                                                    <path
                                                                        d="M55 12.76L44.854 1.258C44.367.474 43.656 0 42.907 0H21.093c-.749 0-1.46.474-1.947 1.257L9 12.761V22h46v-9.24z">
                                                                    </path>
                                                                    <path
                                                                        d="M41.613 15.931c0-1.605.994-2.93 2.227-2.931H55v18.137C55 33.26 53.68 35 52.05 35h-40.1C10.32 35 9 33.259 9 31.137V13h11.16c1.233 0 2.227 1.323 2.227 2.928v.022c0 1.605 1.005 2.901 2.237 2.901h14.752c1.232 0 2.237-1.308 2.237-2.913v-.007z"
                                                                        fill="#fafafa"></path>
                                                                </g>
                                                            </g>
                                                        </svg>
                                                        <p class="ant-empty-description">No data</p>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endif
                                    </tbody>
                                </table>

                                <div class="row d-flex" id="paginationLinks">
                                    <div class="col-md-12 text-right">
                                        <div id="">
                                            {{ $export_orders->links() }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection
