@extends('management.layouts.master')
@section('title')
    Payment Voucher
@endsection

@section('content')
    <div class="content-wrapper">
        <section id="extended">
            <div class="row w-100 mx-auto">
                <div class="col-xs-5 col-sm-5 col-md-5 col-lg-5">
                    <h2 class="page-title">Bank/Cash Payment Voucher</h2>
                </div>
                <div class="col-xs-7 col-sm-7 col-md-7 col-lg-7 text-right">
                    <!-- Create Regular Payment Voucher -->
                    @canAccess('bill-payment-voucher')
                    <a href="{{ route('bill.payment-voucher') }}" class="btn btn-warning position-relative mr-2">
                        <i class="ft-plus"></i> Create Bill Payment Voucher for Purchase
                    </a>
                    @endcanAccess
                    @canAccess('payment-voucher')
                    <a href="{{ route('payment-voucher.create') }}" class="btn btn-primary position-relative mr-2">
                        <i class="ft-plus"></i> Create Payment Voucher
                    </a>
                    @endcanAccess
                    @canAccess('direct-payment-voucher')
                    <!-- Create Direct Payment Voucher -->
                    <a href="{{ route('direct.payment-voucher') }}" class="btn btn-success position-relative">
                        <i class="ft-plus-circle"></i> Create Direct Payment Voucher
                    </a>
                    @endcanAccess
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <form id="filterForm" class="form">
                                <div class="row">
                                    <div class="col-md-12 my-1">
                                        <div class="row justify-content-end text-right">
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
                                            <th>PV No</th>
                                            <th>Date</th>
                                            <th>Status</th>
                                            <th>Type</th>
                                            <th>Account</th>
                                            <th>Bill/Ref No</th>
                                            <th>Cheque No</th>
                                            <th>Amount</th>
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
        $(document).ready(function () {
            filterationCommon(`{{ route('get.payment-vouchers') }}`);

            // Automatically open approval modal if open_approval query param is passed (from notification)
            var urlParams = new URLSearchParams(window.location.search);
            var openApprovalId = urlParams.get('open_approval');
            if (openApprovalId) {
                var isApprovalTriggered = false;

                function cleanUrlApprovalParam() {
                    try {
                        var currentUrl = new URL(window.location.href);
                        if (currentUrl.searchParams.has('open_approval')) {
                            currentUrl.searchParams.delete('open_approval');
                            var newSearch = currentUrl.searchParams.toString();
                            var cleanUrl = currentUrl.pathname + (newSearch ? '?' + newSearch : '');
                            window.history.replaceState(null, '', cleanUrl);
                        }
                    } catch (e) {
                        console.error('Error removing open_approval param', e);
                    }
                }

                function triggerApprovalModal() {
                    if (isApprovalTriggered) return;
                    var $btn = $('#approval-btn-' + openApprovalId);
                    if ($btn.length) {
                        isApprovalTriggered = true;
                        cleanUrlApprovalParam();
                        $btn[0].click();
                    } else if ($('#filteredData table tbody tr').length > 0 && !$('#filteredData table tbody tr').hasClass('loading')) {
                        isApprovalTriggered = true;
                        cleanUrlApprovalParam();
                        var dummyBtn = $('<button></button>');
                        var approvalUrl = "{{ url('finance/payment-voucher/approvals') }}/" + openApprovalId;
                        if (typeof openModal === 'function') {
                            openModal(dummyBtn, approvalUrl, 'Approval Voucher', false, '80%');
                        }
                    }
                }

                $(document).on('hidden.bs.modal', function () {
                    cleanUrlApprovalParam();
                });

                // Listen for AJAX data loading completion from filterationCommon
                $(document).ajaxSuccess(function (event, xhr, settings) {
                    if (settings.url && settings.url.indexOf('get-payment-voucher') !== -1) {
                        setTimeout(triggerApprovalModal, 250);
                    }
                });

                // Fallback timeouts in case of fast or delayed rendering
                setTimeout(triggerApprovalModal, 600);
                setTimeout(triggerApprovalModal, 1200);
            }
        });
    </script>
@endsection