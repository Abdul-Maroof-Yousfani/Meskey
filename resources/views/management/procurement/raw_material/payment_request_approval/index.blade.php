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
                                <div class="row mx-0">
                                    <div class="px-1 text-left" style="width: 15%;">
                                        <label for="contract_no" class="form-label">Ticket / Contract</label>
                                        <input type="text" class="form-control" id="contract_no"
                                            placeholder="No" name="contract_no"
                                            value="{{ request('contract_no', '') }}">
                                    </div>
                                    <div class="px-1 text-left" style="width: 15%;">
                                        <label for="supplier_id_f" class="form-label">Supplier</label>
                                        <select name="supplier_id" id="supplier_id_f"
                                            class="form-control select2">
                                            <option value="">Supplier</option>
                                        </select>
                                    </div>
                                    <div class="px-1 text-left" style="width: 10%;">
                                        <label for="sauda_type" class="form-label">Sauda Type</label>
                                        <select name="sauda_type_id" id="sauda_type_id" class="form-control select2">
                                            <option value="">Sauda</option>
                                        </select>
                                    </div>
                                    <div class="px-1 text-left" style="width: 10%;">
                                        <label for="request_type" class="form-label">Type</label>
                                        <select name="request_type" id="request_type" class="form-control select2">
                                            <option value="">Type</option>
                                            <option value="payment" {{ request('request_type') == 'payment' ? 'selected' : '' }}>Payment</option>
                                            <option value="freight_payment" {{ request('request_type') == 'freight_payment' ? 'selected' : '' }}>Freight Payment</option>
                                        </select>
                                    </div>
                                    <div class="px-1 text-left" style="width: 10%;">
                                        <label for="amount" class="form-label">Amount</label>
                                        <input type="text" class="form-control" id="amount"
                                            placeholder="Amt" name="amount"
                                            value="{{ request('amount', '') }}">
                                    </div>
                                    <div class="px-1 text-left" style="width: 10%;">
                                        <label for="status" class="form-label">Status</label>
                                        <select name="status" id="status" class="form-control select2">
                                            <option value="">Status</option>
                                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                                            <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                                            <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                                        </select>
                                    </div>
                                    <div class="px-1 text-left" style="width: 20%;">
                                         <label for="daterange" class="form-label">Created Date</label>
                                         <input type="text" name="daterange" id="daterange" class="form-control"
                                             value="{{ request('daterange', \Carbon\Carbon::now()->subMonth()->format('m/d/Y') . ' - ' . \Carbon\Carbon::now()->format('m/d/Y')) }}" />
                                         <input type="hidden" name="page" value="{{ request('page', 1) }}">
                                         <input type="hidden" name="per_page" value="{{ request('per_page', 25) }}">
                                     </div>
                                     <div class="px-1 text-left" style="width: 10%;">
                                        <!-- Actions Column -->
                                     </div>
                                </div>
                            </form>
                        </div>
                        <div class="card-content">
                            <div class="card-body table-responsive" id="filteredData">
                                <table class="table m-0">
                                    <thead>
                                        <tr>
                                            <th style="width: 15%;">Ticket No / Contract No</th>
                                            <th style="width: 15%;">Supplier</th>
                                            <th style="width: 10%;">Sauda Type</th>
                                            <th style="width: 10%;">Type</th>
                                            <th style="width: 10%;">Amount</th>
                                            <th style="width: 10%;">Status</th>
                                            <th style="width: 20%;">Created</th>
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
    <style>
        #filterForm .select2-container {
            width: 100% !important;
        }
        .select2-container--default .select2-selection--single {
            height: 38px !important;
            padding: 5px !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: normal !important;
        }
        .select2-results__options {
            max-height: 250px !important;
        }
        .form-control {
            height: 38px !important;
        }
        .px-1 label {
            font-size: 13px !important;
            margin-bottom: 5px !important;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            display: block;
        }
        .card-header {
            padding: 15px !important;
        }
        .row.mx-0 {
            margin-top: 0;
        }
        /* Specific Fix for Select2 Search Input */
        .select2-container--open .select2-dropdown .select2-search__field {
            color: #495057 !important;
            background-color: #fff !important;
            border: 1px solid #ced4da !important;
            border-radius: 4px !important;
            padding: 6px 12px !important;
            height: auto !important;
        }
    </style>
    <script>
        $(document).ready(function() {
            filterationCommon(
                `{{ route('raw-material.get.payment-request-approval') }}`
            );

            initializeDynamicSelect2('#sauda_type_id', 'sauda_types', 'name', 'id', true, false, true, true);
            initializeDynamicSelect2('#supplier_id_f', 'suppliers', 'name', 'id', true, false, true, true);

            // Local fix for unselecting/clearing dropdowns (Dynamic only)
            $('#sauda_type_id, #supplier_id_f').off('select2:clear select2:select').on('select2:clear', function(e) {
                var $el = $(this);
                setTimeout(function() {
                    $el.val(null).trigger('change');
                }, 50);
            }).on('select2:select', function(e) {
                if (e.params.data.id === 'all' || e.params.data.id === '') {
                    var $el = $(this);
                    setTimeout(function() {
                        $el.val(null).trigger('change');
                    }, 50);
                }
            });

            // Ensure static selects are initialized if not handled globally
            $('#status, #request_type').select2({
                allowClear: true,
                placeholder: "Select",
                width: '100%'
            });

            // Persist current page and per_page for AJAX refreshes
            $(document).on('click', '#paginationLinks a', function() {
                var url = $(this).attr('href');
                if (url) {
                    var page = url.split('page=')[1];
                    if (page) {
                        $('input[name="page"]').val(page);
                    }
                }
            });

            $(document).on('change', '#per_page', function() {
                $('input[name="per_page"]').val($(this).val());
                $('input[name="page"]').val(1);
            });
        });

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
    </script>
@endsection
