@extends('management.layouts.master')
@section('title')
    Receiving Request
@endsection
@section('content')
    <div class="content-wrapper">
        <section id="extended">
            <div class="row w-100 mx-auto">
                <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6">
                    <h2 class="page-title">Receiving Request</h2>
                </div>
                <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6 text-right">
                    {{-- No create button - created automatically with DC --}}
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
                                    <div class="px-1 text-left" style="width: 20%;">
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
                                    <div class="px-1 text-left" style="width: 20%;">
                                        <label for="dc_date_for_filter" class="form-label">DC Date</label>
                                        <input type="text" class="form-control" name="dc_date_for_filter" id="dc_date_for_filter"
                                            placeholder="Select Date Range"
                                            value="{{ request('dc_date_for_filter', '') }}">
                                    </div>
                                    <div class="px-1 text-left" style="width: 20%;">
                                        <label for="created_at_for_filter" class="form-label">Created At</label>
                                        <input type="text" class="form-control" name="created_at_for_filter" id="created_at_for_filter"
                                            placeholder="Select Date Range"
                                            value="{{ request('created_at_for_filter', '') }}">
                                    </div>
                                    <div class="px-1 text-left" style="width: 15%;">
                                        <label for="status_for_filter" class="form-label">Status</label>
                                        <select name="status_for_filter" id="status_for_filter" class="form-control select2">
                                            <option value="all" {{ request('status_for_filter') == 'all' ? 'selected' : '' }}>All Status</option>
                                            <option value="pending" {{ request('status_for_filter') == 'pending' ? 'selected' : '' }}>Pending</option>
                                            <option value="approved" {{ request('status_for_filter') == 'approved' ? 'selected' : '' }}>Approved</option>
                                            <option value="rejected" {{ request('status_for_filter') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                                            <option value="reverted" {{ request('status_for_filter') == 'reverted' ? 'selected' : '' }}>Reverted</option>
                                        </select>
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
                                            <th>DC No</th>
                                            <th>DC Date</th>
                                            <th>Truck Number</th>
                                            <th>Bill/T</th>
                                            <th>Items Count</th>
                                            <th>Created At</th>
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
            filterationCommon(`{{ route('sales.get.receiving-request.list') }}`)
            
            // Initialize daterangepicker on the filter input
            $('#dc_date_for_filter, #created_at_for_filter').daterangepicker({
                autoUpdateInput: false,
                locale: {
                    cancelLabel: 'Clear'
                }
            });

            $('#dc_date_for_filter, #created_at_for_filter').on('apply.daterangepicker', function(ev, picker) {
                $(this).val(picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD'));
                $(this).trigger('keyup');
            });

            $('#dc_date_for_filter, #created_at_for_filter').on('cancel.daterangepicker', function(ev, picker) {
                $(this).val('');
                $(this).trigger('keyup');
            });

            // Re-initialize select2 after any AJAX update to preserve selected value in visual UI
            $(document).on('ajaxSuccess', function() {
                $('#dc_id_for_filter, #status_for_filter').select2();
            });
        });
    </script>
@endsection
