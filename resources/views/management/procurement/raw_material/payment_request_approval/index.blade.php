@extends('management.layouts.master')

@section('title')
    Payment Request Approval
@endsection

@section('content')
    <div class="content-wrapper">
        <section id="extended">
            <div class="row w-100 mx-auto">
                <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6">
                    <h2 class="page-title">Payment Request Approval</h2>
                </div>
            </div>
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <form id="filterForm" class="form">
                                <div class="row">
                                    <div class="col-md-12 my-1">
                                        <div class="row justify-content-end text-right1">
                                            <div class="col-md-2">
                                                <label for="supplier_id" class="form-label">Supplier</label>
                                                <select name="supplier_id" id="supplier_id" class="form-control">
                                                    <option value="">Select Supplier</option>
                                                </select>
                                            </div>
                                            <div class="col-md-2">
                                                <label for="status" class="form-label">Status</label>
                                                <select name="status" id="status" class="form-control">
                                                    <option value="">All Status</option>
                                                    <option value="pending">Pending</option>
                                                    <option value="approved">Approved</option>
                                                    <option value="rejected">Rejected</option>
                                                </select>
                                            </div>
                                            <div class="col-md-2">
                                                <label for="request_type" class="form-label">Type</label>
                                                <select name="request_type" id="request_type" class="form-control">
                                                    <option value="">All Types</option>
                                                    <option value="payment">Payment</option>
                                                    <option value="freight_payment">Freight Payment</option>
                                                </select>
                                            </div>
                                            <div class="col-md-2">
                                                <label for="search" class="form-label">Search</label>
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
                                            <th class="col-sm-2">Ticket No / Contract No</th>
                                            <th class="col-sm-2">Supplier</th>
                                            <th class="col-sm-2">Type</th>
                                            <th class="col-sm-1">Amount</th>
                                            <th class="col-sm-2">Status</th>
                                            <th class="col-sm-1">Created</th>
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
            initializeDynamicSelect2('#supplier_id', 'suppliers', 'name', 'id', true, false, true, true);
            filterationCommon(
                `{{ route('raw-material.get.payment-request-approval') }}`
            );


            function approveRequest(requestId, status) {
                const remarks = $('#remarks_' + requestId).val();

                $.post('{{ route('raw-material.payment-request-approval.approve') }}', {
                    _token: '{{ csrf_token() }}',
                    id: requestId,
                    status: status,
                    remarks: remarks
                }, function(response) {
                    if (response.success) {
                        toastr.success(response.message);
                        $('#approvalModal').modal('hide');
                        filterationCommon(`{{ route('raw-material.get.payment-request-approval') }}`)
                    }
                });
            }
        });
    </script>
@endsection
