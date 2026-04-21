@extends('management.layouts.master')
@section('title')
Brokers
@endsection
@section('content')
<div class="content-wrapper">

    <section id="extended">
        <div class="row w-100 mx-auto">
            <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6">
                <h2 class="page-title"> Brokers List</h2>
            </div>
            <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6 text-right">
                <button onclick="openModal(this,'{{ route('broker.create') }}','Add Broker')"
                    type="button" class="btn btn-primary position-relative ">
                    Create Broker
                </button>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <form id="filterForm" class="form">
                            <div class="row mx-0">
                                <div class="px-1 text-left" style="width: 10%;">
                                    <label for="unique_no" class="form-label">S No.</label>
                                    <input type="text" class="form-control" id="unique_no"
                                        placeholder="S No." name="unique_no"
                                        value="{{ request('unique_no', '') }}">
                                </div>
                                <div class="px-1 text-left" style="width: 18%;">
                                    <label for="broker_name" class="form-label">Broker</label>
                                    <input type="text" class="form-control" id="broker_name"
                                        placeholder="Broker Name" name="broker_name"
                                        value="{{ request('broker_name', '') }}">
                                </div>
                                <div class="px-1 text-left" style="width: 18%;">
                                    <label for="company_name" class="form-label">Company</label>
                                    <input type="text" class="form-control" id="company_name"
                                        placeholder="Company Name" name="company_name"
                                        value="{{ request('company_name', '') }}">
                                </div>
                                <div class="px-1 text-left" style="width: 20%;">
                                    <label for="address" class="form-label">Address</label>
                                    <input type="text" class="form-control" id="address"
                                        placeholder="Address" name="address"
                                        value="{{ request('address', '') }}">
                                </div>
                                <div class="px-1 text-left" style="width: 12%;">
                                    <label for="is_for_sales" class="form-label">Type</label>
                                    <select name="is_for_sales" id="is_for_sales" class="form-control select2">
                                        <option value="all" {{ request('is_for_sales') == 'all' ? 'selected' : '' }}>All Type</option>
                                        <option value="1" {{ request('is_for_sales') == '1' ? 'selected' : '' }}>For Sales</option>
                                        <option value="0" {{ request('is_for_sales') == '0' ? 'selected' : '' }}>Not for Sales</option>
                                    </select>
                                </div>
                                <div class="px-1 text-left" style="width: 12%;">
                                    <label for="date_range" class="form-label">Created</label>
                                    <input type="text" class="form-control" name="date_range" id="date_range" 
                                        placeholder="Select Date"
                                        value="{{ request('date_range', date('Y-m-d', strtotime('-1 year')) . ' - ' . date('Y-m-d')) }}">
                                </div>
                                <div class="px-1 text-left" style="width: 10%;">
                                    <label for="search" class="form-label">Search</label>
                                    <input type="hidden" name="page" value="{{ request('page', 1) }}">
                                    <input type="hidden" name="per_page" value="{{ request('per_page', 25) }}">
                                    <input type="text" class="form-control" id="search"
                                        placeholder="Global Search" name="search"
                                        value="{{ request('search', '') }}">
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="card-content">
                        <div class="card-body table-responsive" id="filteredData">
                            <table class="table m-0" style="table-layout: fixed; width: 100%;">
                                <thead>
                                    <tr>
                                        <th style="width: 10%;">S No.</th>
                                        <th style="width: 18%;">Broker</th>
                                        <th style="width: 18%;">Company</th>
                                        <th style="width: 20%;">Address</th>
                                        <th style="width: 12%;">Type</th>
                                        <th style="width: 12%;">Created</th>
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
    $(document).ready(function () {
        filterationCommon(`{{ route('get.broker') }}`)
        
        // Re-initialize select2 after any AJAX update
        $(document).on('ajaxSuccess', function() {
            $('#is_for_sales').select2();
        });
    });
</script>
@endsection