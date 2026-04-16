@extends('management.layouts.master')

@section('title')
    Payment Request
@endsection

@section('content')
    <div class="content-wrapper">
        <section id="extended">
            <div class="row w-100 mx-auto">
                <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6">
                    <h2 class="page-title">Payment Request</h2>
                </div>
            </div>
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <form id="filterForm" class="form">
                                <div class="row mx-0">
                                    <div class="px-1 text-left" style="width: 15%;">
                                        <label for="contract_no" class="form-label">Contract No</label>
                                        <input type="text" class="form-control" id="contract_no"
                                            placeholder="Contract No" name="contract_no"
                                            value="{{ request('contract_no', '') }}">
                                    </div>
                                    <div class="px-1 text-left" style="width: 10%;">
                                        <label for="company_location" class="form-label">Location</label>
                                        <select name="company_location_id" id="company_location"
                                            class="form-control">
                                            <option value="">Location</option>
                                        </select>
                                    </div>
                                    <div class="px-1 text-left" style="width: 15%;">
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
                                    <div class="px-1 text-left" style="width: 10%;">
                                        <label for="loading_date" class="form-label">Loading Date</label>
                                        <input type="date" class="form-control" id="loading_date" name="loading_date"
                                            value="{{ request('loading_date', '') }}">
                                    </div>
                                    <div class="px-1 text-left" style="width: 15%;">
                                        <label for="amount" class="form-label">Amount</label>
                                        <input type="text" class="form-control" id="amount"
                                            placeholder="Amount" name="amount"
                                            value="{{ request('amount', '') }}">
                                    </div>
                                    <div class="px-1 text-left" style="width: 10%;">
                                         <label for="requested_amount" class="form-label">Req. Amt</label>
                                         <input type="text" class="form-control" id="requested_amount"
                                             placeholder="Req Amt" name="requested_amount"
                                             value="{{ request('requested_amount', '') }}">
                                     </div>
                                     <div class="px-1 text-left" style="width: 5%;">
                                         <label class="form-label" style="visibility: hidden;">Created</label>
                                     </div>
                                     <div class="px-1 text-left" style="width: 10%;">
                                         <label for="daterange" class="form-label">Date</label>
                                         <input type="text" name="daterange" id="daterange" class="form-control"
                                             value="{{ request('daterange', \Carbon\Carbon::now()->subMonth()->format('m/d/Y') . ' - ' . \Carbon\Carbon::now()->format('m/d/Y')) }}" />
                                         <input type="hidden" name="page" value="{{ request('page', 1) }}">
                                         <input type="hidden" name="per_page" value="{{ request('per_page', 25) }}">
                                     </div>
                                </div>
                            </form>
                        </div>
                        <div class="card-content">
                            <div class="card-body table-responsive" id="filteredData">
                                <table class="table m-0">
                                    <thead>
                                        <tr>
                                            <th style="width: 15%;">Ticket No / Contract No</th>
                                            <th style="width: 10%;">Location</th>
                                            <th style="width: 15%;">Supplier</th>
                                            <th style="width: 10%;">Commodity</th>
                                            <th style="width: 10%;">Loading date</th>
                                            <th style="width: 15%;">Amounts</th>
                                            <th style="width: 10%;">Tot. Req. Amt.</th>
                                            <th style="width: 5%;">Created</th>
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
            filterationCommon(
                `{{ route('raw-material.get.payment-request') }}`
            );

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

            // Persist current page and per_page for AJAX refreshes
            $(document).on('click', '#paginationLinks a', function() {
                var url = $(this).attr('href');
                if (url) {
                    var page = url.split('page=')[1];
                    if (page) {
                        $('input[name="page"]').val(page);
                    }
                }
            });

            $(document).on('change', '#per_page', function() {
                $('input[name="per_page"]').val($(this).val());
                $('input[name="page"]').val(1);
            });
        });
    </script>
@endsection
