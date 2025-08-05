@extends('management.layouts.master')
@section('title')
    Initial Sampling
@endsection
@section('content')
    <div class="content-wrapper">

        <section id="extended">
            <div class="row w-100 mx-auto">
                <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6">
                    <h2 class="page-title">
                        {{ str()->contains(request()->route()->getName(), 'verified') ? 'Verified Tickets' : 'Ticket Selection' }}
                    </h2>
                </div>
            </div>
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <form id="filterForm" class="form">
                                <div class="row">
                                    <div class="col-md-12 my-1">
                                        <div class="row justify-content-nd text">
                                            <div class="col-md-2">
                                                <div class="form-group mb-0">
                                                    <label>Location:</label>
                                                    <select name="company_location_id" id="company_location"
                                                        class="form-control select2">
                                                        <option value="">Location</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="form-group mb-0">
                                                    <label>Date:</label>
                                                    <input type="text" name="daterange" class="form-control"
                                                        value="{{ \Carbon\Carbon::now()->subMonth()->format('m/d/Y') }} - {{ \Carbon\Carbon::now()->format('m/d/Y') }}" />
                                                </div>
                                            </div>

                                            <div class="col-md-2">
                                                <div class="form-group mb-0">
                                                    <label>Arrival Ticket No:</label>
                                                    <input type="text" class="form-control" name="arrival_ticket_no"
                                                        placeholder="Arrival Ticket No"
                                                        value="{{ request('arrival_ticket_no', '') }}">
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="form-group mb-0">
                                                    <label>GRN No:</label>
                                                    <input type="text" class="form-control" name="grn_no"
                                                        placeholder="GRN No" value="{{ request('grn_no', '') }}">
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row justify-content-nd text mt-2">
                                            <div class="col-md-2">
                                                <div class="form-group mb-0">
                                                    <label>Commodity:</label>
                                                    <select name="commodity_id" id="commodity_id"
                                                        class="form-control select2">
                                                        <option value="">Select Commodity</option>
                                                        @foreach ($commodities as $commodity)
                                                            <option value="{{ $commodity->id }}"
                                                                {{ request('commodity_id') == $commodity->id ? 'selected' : '' }}>
                                                                {{ $commodity->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="form-group mb-0">
                                                    <label>Accounts Of:</label>
                                                    <select name="supplier_id" id="supplier_id_f"
                                                        class="form-control select2">
                                                        <option value="">Accounts Of</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="form-group mb-0">
                                                    <label>Miller:</label>
                                                    <select name="miller_id" id="miller_id" class="form-control select2">
                                                        <option value="">Select Miller</option>
                                                        @foreach ($millers as $miller)
                                                            <option value="{{ $miller->id }}"
                                                                {{ request('miller_id') == $miller->id ? 'selected' : '' }}>
                                                                {{ $miller->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="form-group mb-0">
                                                    <label>Sauda Type:</label>
                                                    <select name="sauda_type_id" id="sauda_type"
                                                        class="form-control select2">
                                                        <option value="">Sauda Type Name</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="col-md-1">
                                                <div class="form-group mb-0">
                                                    <label>Truck No:</label>
                                                    <input type="text" class="form-control" name="truck_no"
                                                        placeholder="Truck No" value="{{ request('truck_no', '') }}">
                                                </div>
                                            </div>
                                            <div class="col-md-1">
                                                <div class="form-group mb-0">
                                                    <label>Bilty No:</label>
                                                    <input type="text" class="form-control" name="bilty_no"
                                                        placeholder="Bilty No" value="{{ request('bilty_no', '') }}">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row justify-content-nd text mt-2">
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
                                            <th class="col-sm-1">Ticket No. </th>
                                            <th class="col-sm-2">Commodity</th>
                                            <th class="col-sm-2">Supplier</th>
                                            <th class="col-sm-1">Truck No</th>
                                            <th class="col-sm-1">Bilty No</th>
                                            <th class="col-sm-2">Created</th>
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
            filterationCommon(
                `{{ route(str()->contains(request()->route()->getName(), 'verified') ? 'raw-material.get.verified-contracts' : 'raw-material.get.ticket-contracts') }}`
            )

            initializeDynamicSelect2('#sauda_type', 'sauda_types', 'name', 'id', true, false, true, true);

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
        });
    </script>
@endsection
