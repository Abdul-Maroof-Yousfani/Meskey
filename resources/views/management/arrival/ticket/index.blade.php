@extends('management.layouts.master')
@section('title')
    Arrival Ticket
@endsection
@section('content')
    <div class="content-wrapper ">
        <section id="extended">
            <div class="row w-100 mx-auto">
                <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6">
                    <h2 class="page-title"> Ticket List</h2>
                </div>
                <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6 text-right">
                    <button onclick="openModal(this,'{{ route('ticket.create') }}','Add Ticket')" type="button"
                        class="btn btn-primary position-relative ">
                        Create Ticket
                    </button>
                </div>
            </div>
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <form id="filterForm" class="form">
                                @php
                                    $today = \Carbon\Carbon::today()->format('Y-m-d');
                                    $oneMonthAgo = \Carbon\Carbon::today()->subMonth()->format('Y-m-d');
                                @endphp

                                <div class="row ">
                                    <div class="col-md-12 my-1 ">
                                        <div class="row justify-content-start text-left">
                                            <div class="col-md-1">
                                                <div class="form-group mb-0">
                                                    <label>Location:</label>
                                                    <select name="company_location_id" id="company_location"
                                                        class="form-control select22">
                                                        <option value="">Location</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="form-group mb-0">
                                                    <label>Date Range:</label>
                                                    <input type="text" name="daterange" class="form-control"
                                                        value="{{ request('daterange', \Carbon\Carbon::now()->subMonth()->format('m/d/Y') . ' - ' . \Carbon\Carbon::now()->format('m/d/Y')) }}" />
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="form-group mb-0">
                                                    <label>Accounts Of:</label>
                                                    <select name="supplier_id" id="supplier_id_f"
                                                        class="form-control select2">
                                                        <option value="">All</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-1">
                                                <div class="form-group mb-0">
                                                    <label>QC Status:</label>
                                                    <select name="qc_status" id="qc_status_f" class="form-control selectWithoutAjax">
                                                        <option value="">All</option>
                                                        <option value="pending">Pending</option>
                                                        <option value="approved">Approved</option>
                                                        <option value="rejected">Rejected</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-1">
                                                <label class="form-label">Ticket No.</label>
                                                <input type="text" class="form-control" placeholder="Ticket#" name="unique_no" value="{{ request('unique_no') }}">
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label">Commodity</label>
                                                <select name="product_id" id="commodity_f" class="form-control select2">
                                                    <option value="">Commodity</option>
                                                </select>
                                            </div>
                                            <div class="col-md-1">
                                                <label class="form-label">Miller</label>
                                                <select name="miller_id" id="miller_id_f" class="form-control select2">
                                                    <option value="">All</option>
                                                </select>
                                            </div>
                                            <div class="col-md-1">
                                                <label class="form-label">Truck No</label>
                                                <input type="text" class="form-control" placeholder="Truck#" name="truck_no" value="{{ request('truck_no') }}">
                                            </div>
                                            <div class="col-md-1">
                                                <label class="form-label">Bilty No</label>
                                                <input type="text" class="form-control" placeholder="Bilty#" name="bilty_no" value="{{ request('bilty_no') }}">
                                            </div>
                                            
                                            <input type="hidden" name="page" value="{{ request('page', 1) }}">
                                            <input type="hidden" name="per_page" value="{{ request('per_page', 25) }}">
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
                                            <th class="col-sm-2">Ticket No. </th>
                                            <th class="col-sm-3">Commodity</th>
                                            <th class="col-sm-3">Miller</th>
                                            <th class="col-sm-1">Truck No</th>
                                            <th class="col-sm-1">Bilty No</th>
                                            <th class="col-sm-1">First QC</th>
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
            $('#qc_status_f').select2();
            filterationCommon(`{{ route('get.ticket') }}`);
            initializeDynamicSelect2('#commodity_f', 'products', 'name', 'id', true, false, true, true);
            initializeDynamicSelect2('#miller_id_f', 'millers', 'name', 'id', true, false, true, true);
            
            // Custom Dependent Select for Arrival Ticket to include "All Accounts"
            const $locationEl = $('#company_location');
            const $supplierEl = $('#supplier_id_f');

            $locationEl.select2({
                ajax: {
                    url: "/dynamic-dependent-fetch-data",
                    type: "GET",
                    dataType: "json",
                    delay: 250,
                    data: function (params) {
                        return {
                            search: params.term || "",
                            table: 'company_locations',
                            column: 'name',
                            idColumn: 'id',
                            enableTags: true,
                            targetTable: 'suppliers',
                            targetColumn: 'company_location_ids',
                            fetchMode: "source",
                        };
                    },
                    processResults: function (data) {
                        return { results: data.items };
                    },
                },
                minimumInputLength: 0,
                placeholder: "Location",
                allowClear: true
            });

            $supplierEl.select2({
                ajax: {
                    url: "/dynamic-dependent-fetch-data",
                    dataType: "json",
                    delay: 250,
                    data: function (params) {
                        return {
                            search: params.term,
                            table: 'suppliers',
                            column: 'name',
                            idColumn: "id",
                            targetTable: 'suppliers',
                            targetColumn: 'company_location_ids',
                            fetchMode: "target",
                            sourceId: $locationEl.val(),
                        };
                    },
                    processResults: function (data) {
                        let res = data.items;
                        res.unshift({ id: "", text: "All Accounts" });
                        return { results: res };
                    },
                },
                minimumInputLength: 0,
                placeholder: "All Accounts",
                allowClear: true
            });

            $locationEl.on("change", function () {
                const selectedId = $(this).val();
                $supplierEl.val(null).trigger("change");

                if (selectedId) {
                    $.ajax({
                        url: "/dynamic-dependent-fetch-data",
                        data: {
                            table: 'suppliers',
                            column: 'name',
                            fetchMode: "target",
                            sourceId: selectedId,
                        },
                        success: function (data) {
                            const options = data.items.map(
                                (item) => new Option(item.text, item.id)
                            );
                            options.unshift(new Option("All Accounts", ""));
                            $supplierEl.empty().append(options).trigger("change");
                        },
                    });
                }
            });
        });
    </script>
@endsection
