@extends('management.layouts.master')
@section('title')
    Shipment Company
@endsection
@section('content')
    <div class="content-wrapper">

        <section id="extended">
            <div class="row w-100 mx-auto">
                <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6">
                    <h2 class="page-title">Shipment Company</h2>
                </div>
                <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6 text-right">
                    @canAccess('shipmentcompany-create')
                    <button onclick="openModal(this,'{{ route('shipment-company.create') }}','Add Shipment Company')" type="button"
                        class="btn btn-primary position-relative ">
                        Create Shipment Company
                    </button>
                    @endcanAccess
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
                                            <th class="col-sm-1">S no.</th>
                                            <th class="col-sm-3">Name</th>
                                            <th class="col-sm-5">Description</th>
                                            <th class="col-sm-1">Status</th>
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
            filterationCommon(`{{ route('get.shipment-company') }}`)
        });
    </script>
@endsection
