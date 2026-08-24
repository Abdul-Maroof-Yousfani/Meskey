@extends('management.layouts.master')
@section('title')
    Logistics Bill
@endsection
@section('content')
    <div class="content-wrapper">
        <section id="extended">
            <div class="row w-100 mx-auto">
                <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6">
                    <h2 class="page-title">Logistics Bill</h2>
                </div>
                <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6 text-right">
                </div>
            </div>
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <form id="filterForm" class="form">
                                <input type="hidden" name="page" value="{{ request('page', 1) }}">
                                <input type="hidden" name="per_page" value="{{ request('per_page', 25) }}">
                                <div class="row mx-0">
                                    <div class="px-1 text-left" style="width: 25%;">
                                        <label for="dc_id_for_filter" class="form-label">DC No</label>
                                        <select name="dc_id_for_filter" id="dc_id_for_filter" class="form-control select2">
                                            <option value="all">All DC</option>
                                            @foreach ($deliveryChallans as $dc)
                                                <option value="{{ $dc->id }}" {{ request('dc_id_for_filter') == $dc->id ? 'selected' : '' }}>
                                                    {{ $dc->dc_no }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="px-1 text-left" style="width: 25%;">
                                        <label for="dc_date_for_filter" class="form-label">DC Date</label>
                                        <input type="text" class="form-control" name="dc_date_for_filter" id="dc_date_for_filter"
                                            placeholder="Select Date Range"
                                            value="{{ request('dc_date_for_filter', '') }}">
                                    </div>
                                    <div class="px-1 text-left" style="width: 25%;">
                                        <label for="created_at_for_filter" class="form-label">Created At</label>
                                        <input type="text" class="form-control" name="created_at_for_filter" id="created_at_for_filter"
                                            placeholder="Select Date Range"
                                            value="{{ request('created_at_for_filter', '') }}">
                                    </div>
                                    <div class="px-1 text-left" style="width: 25%;">
                                        <label for="search_for_filter" class="form-label">Search</label>
                                        <input type="text" class="form-control" id="search_for_filter"
                                            placeholder="Search" name="search_for_filter"
                                            value="{{ request('search_for_filter', '') }}">
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="card-content">
                            <div class="card-body table-responsive" id="filteredData">
                                <table class="table m-0">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Party (Customer)</th>
                                            <th>DO#</th>
                                            <th>DC#</th>
                                            <th>Commodity Name</th>
                                            <th>Truck#</th>
                                            <th>Dispatch Date</th>
                                            <th>Status</th>
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
        $(document).ready(function () {
            filterationCommon(`{{ route('sales.get.logistics-bill.list') }}`)
            
            $('#dc_date_for_filter, #created_at_for_filter').daterangepicker({
                autoUpdateInput: false,
                locale: {
                    cancelLabel: 'Clear'
                }
            });

            $('#dc_date_for_filter, #created_at_for_filter').on('apply.daterangepicker', function(ev, picker) {
                $(this).val(picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD'));
                $(this).trigger('change');
            });

            $('#dc_date_for_filter, #created_at_for_filter').on('cancel.daterangepicker', function(ev, picker) {
                $(this).val('');
                $(this).trigger('change');
            });
        });
    </script>
@endsection
