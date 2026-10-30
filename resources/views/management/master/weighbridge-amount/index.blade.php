@extends('management.layouts.master')
@section('title')
    Weighbridge Amount
@endsection
@section('content')
    <div class="content-wrapper">

        <section id="extended">
            <div class="row w-100 mx-auto">
                <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6">
                    <h2 class="page-title"> Weighbridge Amount</h2>
                </div>
                <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6 text-right">
                    <button onclick="openModal(this,'{{ route('weighbridge-amount.create') }}','Add Weighbridge Amount')"
                        type="button" class="btn btn-primary position-relative ">
                        Create Weighbridge Amount
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
                                                <label for="filter_location" class="form-label">Location</label>
                                                <select class="form-control select2" name="company_location_id"
                                                    id="filter_location">
                                                    <option value="">All Locations</option>
                                                    @foreach ($CompanyLocations as $location)
                                                        <option value="{{ $location->id }}">{{ $location->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-2">
                                                <label for="filter_truck_type" class="form-label">Truck Type</label>
                                                <select class="form-control select2" name="truck_type_id" id="filter_truck_type">
                                                    <option value="">All Truck Types</option>
                                                    @foreach ($TruckTypes as $truckType)
                                                        <option value="{{ $truckType->id }}">{{ $truckType->name }}</option>
                                                    @endforeach
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
                                            <th class="col-sm-1">Location</th>
                                            <th class="col-sm-2">Truck Type</th>
                                            <th class="col-sm-2">Amount</th>
                                            <th class="col-sm-3">Description</th>
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
            $('#filter_location, #filter_truck_type').select2();
            filterationCommon(`{{ route('get.weighbridge-amount') }}`)
        });
    </script>
@endsection
