@extends('management.layouts.master')
@section('title')
    Stock in Transit Vehicles
@endsection
@section('content')
    <div class="content-wrapper">
        <section id="extended">
            <div class="row w-100 mx-auto">
                <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6">
                    <h2 class="page-title">Stock in Transit Vehicles</h2>
                </div>
            </div>
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <form id="filterForm" class="form">
                                <div class="row mx-0">
                                    <div class="px-1 text-left" style="width: 12%;">
                                        <label for="daterange" class="form-label">Date</label>
                                        <input type="text" name="daterange" id="daterange" class="form-control"
                                            value="{{ request('daterange', \Carbon\Carbon::now()->subMonth()->format('m/d/Y') . ' - ' . \Carbon\Carbon::now()->format('m/d/Y')) }}" />
                                    </div>
                                    <div class="px-1 text-left" style="width: 10%;">
                                        <label for="company_location" class="form-label">Location</label>
                                        <select name="company_location_id" id="company_location"
                                            class="form-control select2">
                                            <option value="">Location</option>
                                        </select>
                                    </div>
                                    <div class="px-1 text-left" style="width: 10%;">
                                        <label for="contract_no" class="form-label">Contract No</label>
                                        <input type="text" name="contract_no" id="contract_no" class="form-control"
                                            placeholder="Contract" value="{{ request('contract_no', '') }}" />
                                    </div>
                                    <div class="px-1 text-left" style="width: 12%;">
                                        <label for="supplier_id_f" class="form-label">Supplier</label>
                                        <select name="supplier_id" id="supplier_id_f"
                                            class="form-control select2">
                                            <option value="">Supplier</option>
                                        </select>
                                    </div>
                                    <div class="px-1 text-left" style="width: 12%;">
                                        <label for="broker_id_f" class="form-label">Broker</label>
                                        <select name="broker_id" id="broker_id_f"
                                            class="form-control select2">
                                            <option value="">Broker</option>
                                        </select>
                                    </div>
                                    <div class="px-1 text-left" style="width: 12%;">
                                        <label for="search" class="form-label">Product</label>
                                        <input type="text" class="form-control" id="search"
                                            placeholder="Product" name="search"
                                            value="{{ request('search', '') }}">
                                    </div>
                                    <div class="px-1 text-left" style="width: 12%;">
                                        <label class="form-label">Vehicle (Truck/Bilty)</label>
                                        <div class="d-flex">
                                            <input type="text" name="truck_no" id="truck_no" class="form-control mr-1"
                                                placeholder="Truck" value="{{ request('truck_no', '') }}" />
                                            <input type="text" name="bilty_no" id="bilty_no" class="form-control"
                                                placeholder="Bilty" value="{{ request('bilty_no', '') }}" />
                                        </div>
                                    </div>
                                    <div class="px-1 text-left" style="width: 10%;">
                                        <label for="status_f" class="form-label">Status</label>
                                        <select name="status" id="status_f" class="form-control select2">
                                            <option value="">Status</option>
                                            <option value="pending">Pending</option>
                                            <option value="completed">Completed</option>
                                        </select>
                                    </div>
                                    <div class="px-1 text-left" style="width: 10%;">
                                        <label class="form-label">Actions</label>
                                        <input type="hidden" name="page" value="{{ request('page', 1) }}">
                                        <input type="hidden" name="per_page"
                                            value="{{ request('per_page', 25) }}">
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="card-content">
                            <div class="card-body table-responsive" id="filteredData">
                                <table class="table m-0">
                                    <thead>
                                        <tr>
                                            <th style="width: 12%;">Date</th>
                                            <th style="width: 10%;">Location</th>
                                            <th style="width: 10%;">Contract No</th>
                                            <th style="width: 12%;">Supplier</th>
                                            <th style="width: 12%;">Broker</th>
                                            <th style="width: 12%;">Product</th>
                                            <th style="width: 12%;">Vehicle No (Truck/Bilty)</th>
                                            <th style="width: 10%;">Status</th>
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
            
            initializeDynamicSelect2('#broker_id_f', 'brokers', 'name', 'id', true, false, true, true);

            filterationCommon(`{{ route('raw-material.get.sit-vehicle') }}`)
        });
    </script>
@endsection
