@extends('management.layouts.master')

@section('title')
    Advance Payment Request
@endsection

@section('content')
    <div class="content-wrapper">
        <section id="extended">
            {{-- mph-head: mobile par app-bar (partials/mobile-page-head). Desktop same. --}}
            @include('management.layouts.partials.mobile-page-head')
            <div class="row w-100 mx-auto align-items-center mph-head">
                <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6 mph-title-col">
                    <div class="mph-sub">Purchase Contract</div>
                    <h2 class="page-title mb-0">Advance Payment Request </h2>
                </div>
                {{-- mobile par filter button yahan aata hai --}}
                <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6 text-right mph-actions"></div>
            </div>
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <form id="filterForm" class="form" data-filter-apply>
                                <div class="row mx-0 align-items-end">
                                    <div class="px-1 text-left" style="width: 10%;">
                                        <label for="contract_no" class="form-label">Contract No</label>
                                        <input type="text" class="form-control" id="contract_no"
                                            placeholder="Contract No" name="contract_no"
                                            value="{{ request('contract_no', '') }}">
                                    </div>
                                    <div class="px-1 text-left" style="width: 12%;">
                                        <label for="company_location" class="form-label">Location</label>
                                        <select name="company_location_id" id="company_location"
                                            class="form-control">
                                            <option value="">Location</option>
                                        </select>
                                    </div>
                                    <div class="px-1 text-left" style="width: 18%;">
                                        <label for="supplier_id_f" class="form-label">Supplier</label>
                                        <select name="supplier_id" id="supplier_id_f"
                                            class="form-control">
                                            <option value="">Supplier</option>
                                        </select>
                                    </div>
                                    <div class="px-1 text-left" style="width: 10%;">
                                        <label for="product_id" class="form-label">Commodity</label>
                                        <select name="product_id" id="product_id" class="form-control">
                                            <option value="">Commodity</option>
                                        </select>
                                    </div>
                                    <div class="px-1 text-left" style="width: 14%;">
                                        <label for="amount" class="form-label">Amount</label>
                                        <input type="text" class="form-control" id="amount"
                                            placeholder="Amount" name="amount"
                                            value="{{ request('amount', '') }}">
                                    </div>
                                    <div class="px-1 text-left" style="width: 14%;">
                                        <label for="requested_amount" class="form-label">Req. Amount</label>
                                        <input type="text" class="form-control" id="requested_amount"
                                            placeholder="Req. Amt" name="requested_amount"
                                            value="{{ request('requested_amount', '') }}">
                                    </div>
                                    <div class="px-1 text-left" style="width: 12%;">
                                        <label for="daterange" class="form-label">Date</label>
                                        <input type="text" name="daterange" id="daterange" class="form-control"
                                            value="{{ request('daterange', \Carbon\Carbon::now()->subMonth()->format('m/d/Y') . ' - ' . \Carbon\Carbon::now()->format('m/d/Y')) }}" />
                                    </div>
                                    {{-- Search: desktop par; mobile par filter sheet ka "Show results" yahi kaam karta hai --}}
                                    <div class="px-1 text-left hide-mobile d-flex align-items-end mt-2" style="width: 10%;">
                                        <input type="hidden" name="page" value="{{ request('page', 1) }}">
                                        <input type="hidden" name="per_page" value="{{ request('per_page', 25) }}">
                                        <button type="submit" class="btn btn-primary mb-0 btn-block" title="Apply filters">
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
                                            <th style="width: 10%;">Contract No</th>
                                            <th style="width: 12%;">Location</th>
                                            <th style="width: 18%;">Supplier</th>
                                            <th style="width: 10%;">Commodity</th>
                                            <th style="width: 15%;">Amounts</th>
                                            <th style="width: 15%;">Total Requested Amount</th>
                                            <th style="width: 10%;">Created</th>
                                            <th style="width: 10%;">Action</th>
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
            const runListFilter = filterationCommon_withbtn(`{{ route('raw-material.get.advance-payment-request') }}`);
            window.filterRunners = window.filterRunners || {};
            window.filterRunners.filterForm = runListFilter; // list refresh (save ke baad)
            $('#filterForm').on('submit', function (e) {
                e.preventDefault();
                runListFilter();
            });
            runListFilter(true); // pehla load: URL wala page

            // initializeDynamicSelect2('#supplier_id', 'suppliers', 'name', 'id', true, false, true, true);
            initializeDynamicDependentSelect2(
                '#company_location',
                '#supplier_id_f',
                'company_locations',
                'name',
                'id',
                'suppliers',
                'company_location_ids',
                'name',
                true,
                false,
                true,
                true,
            );

            initializeDynamicSelect2('#product_id', 'products', 'name', 'id', true, false, true, true);
        });
    </script>
@endsection
