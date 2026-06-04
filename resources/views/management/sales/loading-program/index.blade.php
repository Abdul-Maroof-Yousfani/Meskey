@extends('management.layouts.master')
@section('title')
    Loading Program
@endsection
@section('content')
    <div class="content-wrapper">

        <section id="extended">
            <div class="row w-100 mx-auto">
                <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6">
                    <h2 class="page-title"> Loading Program</h2>
                </div>
                <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6 text-right">
                    <button onclick="openModal(this,'{{ route('sales.loading-program.create') }}','Add Loading Program', false, '90%')"
                        type="button" class="btn btn-primary position-relative ">
                        Create Loading Program
                    </button>
                </div>
            </div>
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <form id="filterForm" class="form">
                                <input type="hidden" name="page" value="{{ request('page', 1) }}">
                                <input type="hidden" name="per_page" value="{{ request('per_page', 25) }}">
                                
                                {{-- First Row --}}
                                <div class="row mx-0 mt-1">
                                    <div class="px-1 text-left" style="width: 20%;">
                                        <label for="so_no" class="form-label">SO No</label>
                                        <input type="text" class="form-control" id="so_no"
                                            placeholder="SO No" name="so_no"
                                            value="{{ request('so_no', '') }}">
                                    </div>
                                    <div class="px-1 text-left" style="width: 20%;">
                                        <label for="do_no" class="form-label">DO No</label>
                                        <input type="text" class="form-control" id="do_no"
                                            placeholder="DO No" name="do_no"
                                            value="{{ request('do_no', '') }}">
                                    </div>
                                    <div class="px-1 text-left" style="width: 20%;">
                                        <label for="customer_id" class="form-label">Customer</label>
                                        <select name="customer_id" id="customer_id" class="form-control select2">
                                            <option value="all">All Customers</option>
                                            @foreach ($customers as $customer)
                                                <option value="{{ $customer->id }}" {{ request('customer_id') == $customer->id ? 'selected' : '' }}>
                                                    {{ $customer->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="px-1 text-left" style="width: 20%;">
                                        <label for="item_id" class="form-label">Commodity</label>
                                        <select name="item_id" id="item_id" class="form-control select2">
                                            <option value="all">All Commodities</option>
                                            @foreach ($items as $item)
                                                <option value="{{ $item->id }}" {{ request('item_id') == $item->id ? 'selected' : '' }}>
                                                    {{ $item->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="px-1 text-left" style="width: 20%;">
                                        <label for="date_range" class="form-label">Date</label>
                                        <input type="text" class="form-control" name="date_range" id="date_range"
                                            placeholder="Select Date Range"
                                            value="{{ request('date_range', '') }}">
                                    </div>
                                </div>
                                
                                {{-- Second Row --}}
                                <div class="row mx-0 mt-1 mb-1">
                                    <div class="px-1 text-left" style="width: 20%;">
                                        <label for="ticket_no" class="form-label">Ticket No</label>
                                        <input type="text" class="form-control" id="ticket_no"
                                            placeholder="Ticket No" name="ticket_no"
                                            value="{{ request('ticket_no', '') }}">
                                    </div>
                                    <div class="px-1 text-left" style="width: 20%;">
                                        <label for="truck_no" class="form-label">Truck No</label>
                                        <input type="text" class="form-control" id="truck_no"
                                            placeholder="Truck No" name="truck_no"
                                            value="{{ request('truck_no', '') }}">
                                    </div>
                                    <div class="px-1 text-left" style="width: 20%;">
                                        <label for="container_no" class="form-label">Container No</label>
                                        <input type="text" class="form-control" id="container_no"
                                            placeholder="Container No" name="container_no"
                                            value="{{ request('container_no', '') }}">
                                    </div>
                                    <div class="px-1 text-left" style="width: 20%;">
                                        <label for="factory_id" class="form-label">Factory</label>
                                        <select name="factory_id" id="factory_id" class="form-control select2">
                                            <option value="all">All Factories</option>
                                            @foreach ($factories as $factory)
                                                <option value="{{ $factory->id }}" {{ request('factory_id') == $factory->id ? 'selected' : '' }}>
                                                    {{ $factory->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="px-1 text-left" style="width: 20%;">
                                        <label for="gala_id" class="form-label">Gala</label>
                                        <select name="gala_id" id="gala_id" class="form-control select2">
                                            <option value="all">All Galas</option>
                                            @foreach ($galas as $gala)
                                                <option value="{{ $gala->id }}" {{ request('gala_id') == $gala->id ? 'selected' : '' }}>
                                                    {{ $gala->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="card-content">
                            <div class="card-body table-responsive" id="filteredData">
                                <table class="table m-0">
                                    <thead>
                                        <tr>
                                            <th>SO No.</th>
                                            <th>DO No.</th>
                                            <th>Customer</th>
                                            <th>Commodity</th>
                                            <th>Ticket No.</th>
                                            <th>Truck No.</th>
                                            <th>Container No.</th>
                                            <th>Factory</th>
                                            <th>Gala</th>
                                            <th>Suggested Qty</th>
                                            <th>Created</th>
                                            <th>Action</th>
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
            filterationCommon(`{{ route('sales.get.loading-program') }}`)
            
            // Re-initialize select2 after any AJAX update to preserve selected value in visual UI
            $(document).on('ajaxSuccess', function() {
                $('#customer_id, #item_id, #factory_id, #gala_id').select2();
            });
        });
    </script>
@endsection












