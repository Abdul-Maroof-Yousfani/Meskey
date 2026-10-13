@extends('management.layouts.master')
@section('title')
    Journal Voucher
@endsection
@section('content')
    <div class="content-wrapper">
        <section id="extended">
            <div class="row w-100 mx-auto">
                <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6">
                    <h2 class="page-title">Journal Voucher</h2>
                </div>
                <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6 text-right">
                    <a href="{{ route('journal-voucher.create') }}" class="btn btn-primary position-relative ">
                        Create Journal Voucher
                    </a>
                </div>
            </div>
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <form id="filterForm" class="form">
                                <input type="hidden" name="page" value="{{ request('page', 1) }}">
                                <input type="hidden" name="per_page" value="{{ request('per_page', 25) }}">
                                <div class="row mx-0 align-items-end">
                                    <div class="px-1 text-left" style="width: 20%;">
                                        <label for="jv_no" class="form-label">JV No</label>
                                        <input type="text" class="form-control" id="jv_no" name="jv_no"
                                            placeholder="JV No" value="{{ request('jv_no', '') }}">
                                    </div>
                                    <div class="px-1 text-left" style="width: 20%;">
                                        <label for="daterange" class="form-label">Date / Range</label>
                                        <input type="text" class="form-control" id="daterange" name="daterange"
                                            placeholder="Select Date Range" value="{{ request('daterange', '') }}" autocomplete="off">
                                    </div>
                                    <div class="px-1 text-left" style="width: 35%;">
                                        <label for="customer_id" class="form-label">Customer</label>
                                        <select name="customer_id" id="customer_id" class="form-control select2">
                                            <option value="all">All Customers</option>
                                            @foreach ($customers ?? [] as $customer)
                                                 <option value="{{ $customer->id }}" {{ request('customer_id') == $customer->id ? 'selected' : '' }}>
                                                    {{ $customer->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="px-1 text-left" style="width: 20%;">
                                        <label for="status" class="form-label">Status</label>
                                        <select name="status" id="status" class="form-control select2">
                                             <option value="all" {{ request('status') == 'all' ? 'selected' : '' }}>All Status</option>
                                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                                            <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                                            <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                                            <option value="reverted" {{ request('status') == 'reverted' ? 'selected' : '' }}>Reverted</option>
                                        </select>
                                    </div>
                                    <div class="px-1 text-center" style="width: 5%;">
                                        <button type="button" class="btn btn-outline-secondary d-flex align-items-center justify-content-center" id="resetFilters" title="Reset Filters" style="height: 38px; width: 100%; padding: 0;">
                                            <i class="ft-rotate-ccw font-medium-1"></i>
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="card-content">
                            <div class="card-body table-responsive" id="filteredData">
                                <table class="table m-0">
                                    <thead>
                                        <tr>
                                            <th>JV No</th>
                                            <th>Date</th>
                                            <th>Customer</th>
                                            <th>Description</th>
                                            <th>Status</th>
                                            <th>Created By</th>
                                            <th>Actions</th>
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
            $('.select2').select2({ width: '100%' });

            // Initialize Date Range Picker explicitly
            $('#daterange').daterangepicker({
                autoUpdateInput: false,
                opens: 'left',
                locale: {
                    cancelLabel: 'Clear',
                    format: 'YYYY-MM-DD'
                }
            });

            $('#daterange').on('apply.daterangepicker', function(ev, picker) {
                $(this).val(picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD'));
                $(this).trigger('change');
            });

            $('#daterange').on('cancel.daterangepicker', function(ev, picker) {
                $(this).val('');
                $(this).trigger('change');
            });

            filterationCommon(`{{ route('get.journal-vouchers') }}`);

            $('#resetFilters').on('click', function() {
                $('#filterForm')[0].reset();
                $('#filterForm select').val('all').trigger('change.select2');
                $('#filterForm input[type="text"]').val('');
                $('#filterForm input[name="page"]').val(1);
                $('#daterange').val('').trigger('change');
                $('#filterForm input:first').trigger('keyup');
            });
        });
    </script>
@endsection

