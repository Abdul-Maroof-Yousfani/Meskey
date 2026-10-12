@extends('management.layouts.master')
@section('title')
    Pre Sale Dekh
@endsection
@section('content')
    <div class="content-wrapper">
        <section id="extended">
            <div class="row w-100 mx-auto">
                <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6">
                    <h2 class="page-title">Pre Sale Dekh</h2>
                </div>
                @canAccess('dekh-create')
                <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6 text-right">
                    <button
                        onclick="openModal(this,'{{ route('sales.pre-sale-inspection.create') }}','Create Pre Sale Dekh',false,'80%')"
                        type="button" class="btn btn-primary position-relative">
                        Create Pre Sale Dekh
                    </button>
                </div>
                @endcanAccess
            </div>
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <form id="filterForm" class="form">
                                <input type="hidden" name="page" value="{{ request('page', 1) }}">
                                <input type="hidden" name="per_page" value="{{ request('per_page', 25) }}">
                                <div class="row mx-0">
                                    <div class="px-1 text-left" style="width: 15%;">
                                        <label for="inspection_no" class="form-label">Dekh No</label>
                                        <input type="text" class="form-control" placeholder="Inspection No" name="inspection_no"
                                            value="{{ request('inspection_no', '') }}">
                                    </div>
                                    <div class="px-1 text-left" style="width: 15%;">
                                        <label for="location_id" class="form-label">Location</label>
                                        <select name="location_id" id="location_id" class="form-control select2">
                                            <option value="all">All Locations</option>
                                            @foreach ($locations as $loc)
                                                <option value="{{ $loc->id }}" {{ request('location_id') == $loc->id ? 'selected' : '' }}>
                                                    {{ $loc->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="px-1 text-left" style="width: 15%;">
                                        <label for="arrival_location_id" class="form-label">Factory</label>
                                        <select name="arrival_location_id" id="arrival_location_id" class="form-control select2">
                                            <option value="all">All Factories</option>
                                            @foreach ($arrivalLocations ?? [] as $factory)
                                                <option value="{{ $factory->id }}" {{ request('arrival_location_id') == $factory->id ? 'selected' : '' }}>
                                                    {{ $factory->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="px-1 text-left" style="width: 17%;">
                                        <label for="item_id" class="form-label">Item (Product)</label>
                                        <select name="item_id" id="item_id" class="form-control select2">
                                            <option value="all">All Items</option>
                                            @foreach ($items as $item)
                                                <option value="{{ $item->id }}" {{ request('item_id') == $item->id ? 'selected' : '' }}>
                                                    {{ $item->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="px-1 text-left" style="width: 18%;">
                                        <label for="date_range" class="form-label">Date Range</label>
                                        <input type="text" class="form-control" name="date_range" id="date_range"
                                            placeholder="Select Date Range"
                                            value="{{ request('date_range', '') }}">
                                    </div>
                                    <div class="px-1 text-left" style="width: 20%;">
                                        <label for="search" class="form-label">Search</label>
                                        <input type="text" class="form-control" id="search"
                                            placeholder="Search..." name="search"
                                            value="{{ request('search', '') }}">
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="card-content">
                            <div class="card-body table-responsive" id="filteredData">
                                <table class="table m-0">
                                    <thead>
                                        <tr>
                                            <th>Dekh #</th>
                                            <th>Date</th>
                                            <th>Location & Factory</th>
                                            <th>Party Name & Contact</th>
                                            <th>Items, Factory, Section & Weight</th>
                                            <th class="text-center">Status</th>
                                            <th class="text-center">Action</th>
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
        $(document).ready(function () {
            filterationCommon(`{{ route('sales.get.pre-sale-inspection.list') }}`);

            $(document).on('ajaxSuccess', function() {
                $('#item_id, #location_id').select2();
            });
        });
    </script>
@endsection
