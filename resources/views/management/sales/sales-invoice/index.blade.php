@extends('management.layouts.master')
@section('title')
    Sales Invoice
@endsection
@section('content')
    <div class="content-wrapper">
        <section id="extended">
            <div class="row w-100 mx-auto">
                <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6">
                    <h2 class="page-title">Sales Invoice</h2>
                </div>
                <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6 text-right">
                    <button
                        onclick="openModal(this,'{{ route('sales.sales-invoice.create') }}','Create Sales Invoice',false,'100%')"
                        type="button" class="btn btn-primary position-relative">
                        Create Sales Invoice
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
                                <div class="row mx-0">
                                    <div class="px-1 text-left" style="width: 15%;">
                                        <label for="si_id_for_filter" class="form-label">SI No</label>
                                        <select name="si_id_for_filter" id="si_id_for_filter" class="form-control select2">
                                            <option value="all">All SI</option>
                                            @foreach ($salesInvoices as $si)
                                                <option value="{{ $si->id }}" {{ request('si_id_for_filter') == $si->id ? 'selected' : '' }}>
                                                    {{ $si->si_no }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="px-1 text-left" style="width: 17%;">
                                        <label for="customer_id_for_filter" class="form-label">Customer</label>
                                        <select name="customer_id_for_filter" id="customer_id_for_filter" class="form-control select2">
                                            <option value="all">All Customers</option>
                                            @foreach ($customers as $customer)
                                                <option value="{{ $customer->id }}" {{ request('customer_id_for_filter') == $customer->id ? 'selected' : '' }}>
                                                    {{ $customer->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="px-1 text-left" style="width: 16%;">
                                        <label for="item_id_for_filter" class="form-label">Item</label>
                                        <select name="item_id_for_filter" id="item_id_for_filter" class="form-control select2">
                                            <option value="all">All Items</option>
                                            @foreach ($items as $item)
                                                <option value="{{ $item->id }}" {{ request('item_id_for_filter') == $item->id ? 'selected' : '' }}>
                                                    {{ $item->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="px-1 text-left" style="width: 15%;">
                                        <label for="invoice_date_for_filter" class="form-label">Invoice Date</label>
                                        <input type="text" class="form-control" name="invoice_date_for_filter" id="invoice_date_for_filter"
                                            placeholder="Select Date Range"
                                            value="{{ request('invoice_date_for_filter', '') }}">
                                    </div>
                                    <div class="px-1 text-left" style="width: 12%;">
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
                                            <th width="10%">SI No</th>
                                            <th width="15%">Customer</th>
                                            <th width="20%">Item</th>
                                            <th width="8%" class="text-right">Qty</th>
                                            <th width="8%" class="text-right">Rate</th>
                                            <th width="10%" class="text-right">Net Amount</th>
                                            <th width="10%">Invoice Date</th>
                                            <th width="8%">Status</th>
                                            <th width="11%">Action</th>
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
            filterationCommon(`{{ route('sales.get.sales-invoice.list') }}`)
            
            // Initialize daterangepicker on the filter input
            $('#invoice_date_for_filter').daterangepicker({
                autoUpdateInput: false,
                locale: {
                    cancelLabel: 'Clear'
                }
            });

            $('#invoice_date_for_filter').on('apply.daterangepicker', function(ev, picker) {
                $(this).val(picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD'));
                $(this).trigger('keyup');
            });

            $('#invoice_date_for_filter').on('cancel.daterangepicker', function(ev, picker) {
                $(this).val('');
                $(this).trigger('keyup');
            });

            // Re-initialize select2 after any AJAX update to preserve selected value in visual UI
            $(document).on('ajaxSuccess', function() {
                $('#si_id_for_filter, #customer_id_for_filter, #item_id_for_filter, #status_for_filter').select2();
            });
        });
    </script>
@endsection
