@section('title')
    View Journal Voucher
@endsection

@php
    $isReceiving = false;
    $hasOrders = false;
    $totalDebits = 0;
    $totalCredits = 0;
    foreach($journalVoucher->journalVoucherDetails as $detail) {
        if(!empty($detail->receipt_voucher_id) || !empty($detail->customer_advance_id) || (!empty($detail->sales_order_id) && empty($detail->voucher_type))) {
            $isReceiving = true;
        }
        if(!empty($detail->voucher_no)) {
            $hasOrders = true;
        }
        $totalDebits += $detail->debit_amount;
        $totalCredits += $detail->credit_amount;
    }
    $currentStatus = strtolower($journalVoucher->am_approval_status ?? 'pending');
    $badge = match ($currentStatus) {
        'approved' => 'badge-success',
        'rejected' => 'badge-danger',
        'pending' => 'badge-warning',
        'reverted' => 'badge-secondary',
        default => 'badge-secondary'
    };
@endphp

<input type="hidden" id="listRefresh" value="{{ route('get.journal-vouchers') }}" />

@if(in_array(strtolower($journalVoucher->am_approval_status ?? ''), ['approved', 'rejected']))
    @php
        $isApproved = strtolower($journalVoucher->am_approval_status ?? '') === 'approved';
        $alertClass = $isApproved ? 'alert-success' : 'alert-danger';
        $iconClass = $isApproved ? 'fa-check-circle' : 'fa-times-circle';
        $statusText = ucfirst($journalVoucher->am_approval_status ?? 'pending');
    @endphp
    <div class="alert {{ $alertClass }} px-3 py-2 mt-2 d-flex align-items-center justify-content-between" style="border-radius: 6px;">
        <div>
            <i class="fa {{ $iconClass }} me-2"></i>
            <strong>Status: {{ $statusText }}</strong> - This Journal Voucher has been finalized. Its status cannot be changed.
        </div>
    </div>
@elseif(strtolower($journalVoucher->am_approval_status ?? '') === 'reverted')
    <div class="alert alert-warning px-3 py-2 mt-2 d-flex align-items-center justify-content-between" style="border-radius: 6px;">
        <div>
            <i class="fa fa-undo me-2"></i>
            <strong>Status: Reverted</strong> - This Journal Voucher has been reverted. It cannot be approved or rejected until changes are made and resubmitted to pending.
        </div>
        <span class="badge badge-warning text-dark px-2 py-1 text-uppercase">Reverted</span>
    </div>
@endif

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="mb-0">Journal Voucher #{{ $journalVoucher->jv_no }}</h4>
                <a href="{{ route('journal-voucher.index') }}" class="btn btn-sm btn-primary">Back</a>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label><strong>JV Number:</strong></label>
                            <p>{{ $journalVoucher->jv_no }}</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label><strong>Date:</strong></label>
                            <p>{{ $journalVoucher->jv_date ? $journalVoucher->jv_date->format('d-m-Y') : 'N/A' }}</p>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label><strong>Description:</strong></label>
                            <p>{{ $journalVoucher->description ?? 'N/A' }}</p>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label><strong>Status:</strong></label>
                            <p>
                                <span class="badge {{ $badge }}">
                                    {{ ucfirst($currentStatus) }}
                                </span>
                            </p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label><strong>Created By:</strong></label>
                            <p>{{ optional($journalVoucher->createdBy)->name ?? ($journalVoucher->username ?? 'N/A') }}</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label><strong>Last Action By:</strong></label>
                            <p>{{ optional($journalVoucher->approveUser)->name ?? 'N/A' }}</p>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label><strong>Journal Entries:</strong></label>
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Account</th>
                                            @if($isReceiving)
                                                <th>Receipt Voucher</th>
                                                <th>Excess Amount</th>
                                                <th>Sales order</th>
                                            @elseif($hasOrders)
                                                <th>Order / GRN</th>
                                            @endif
                                            <th>Description</th>
                                            <th class="text-right">Debit</th>
                                            <th class="text-right">Credit</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($journalVoucher->journalVoucherDetails as $detail)
                                            <tr>
                                                <td style="word-break: break-word;">
                                                    {{ $detail->account->name ?? 'N/A' }}
                                                    <br><small class="text-muted">({{ $detail->account->unique_no ?? 'N/A' }})</small>
                                                </td>
                                                @if($isReceiving)
                                                    <td>{{ optional($detail->receiptVoucher)->unique_no ?? '—' }}</td>
                                                    <td>
                                                        @if($detail->customerAdvance)
                                                            <span class="badge badge-info">{{ $detail->customerAdvance->voucher_no }}</span>
                                                        @elseif($detail->voucher_type === 'customer_advance')
                                                            <span class="badge badge-info">{{ $detail->voucher_no }}</span>
                                                        @else
                                                            —
                                                        @endif
                                                    </td>
                                                    <td>{{ optional($detail->salesOrder)->reference_no ?? '—' }}</td>
                                                @elseif($hasOrders)
                                                    <td>{{ $detail->voucher_no ?? '—' }}</td>
                                                @endif
                                                <td style="white-space: normal; word-break: break-word; overflow-wrap: break-word;">
                                                    {{ $detail->description ?? '—' }}
                                                </td>
                                                <td class="text-right" style="white-space: nowrap;">
                                                    {{ $detail->debit_amount > 0 ? number_format($detail->debit_amount, 2) : '—' }}
                                                </td>
                                                <td class="text-right" style="white-space: nowrap;">
                                                    {{ $detail->credit_amount > 0 ? number_format($detail->credit_amount, 2) : '—' }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="{{ $isReceiving ? 5 : ($hasOrders ? 3 : 2) }}" class="text-right"><strong>Total Debits:</strong></td>
                                            <td class="text-right" style="white-space: nowrap;"><strong>{{ number_format($totalDebits, 2) }}</strong></td>
                                            <td></td>
                                        </tr>
                                        <tr>
                                            <td colspan="{{ $isReceiving ? 5 : ($hasOrders ? 3 : 2) }}" class="text-right"><strong>Total Credits:</strong></td>
                                            <td></td>
                                            <td class="text-right" style="white-space: nowrap;"><strong>{{ number_format($totalCredits, 2) }}</strong></td>
                                        </tr>
                                        <tr>
                                            <td colspan="{{ $isReceiving ? 5 : ($hasOrders ? 3 : 2) }}" class="text-right"><strong>Difference (Debit - Credit):</strong></td>
                                            <td class="text-right" style="white-space: nowrap;"><strong style="color: {{ abs($totalDebits - $totalCredits) > 0.01 ? 'red' : 'green' }}">{{ number_format($totalDebits - $totalCredits, 2) }}</strong></td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<div class="approval-view-wrapper">
    <div class="row">
        <div class="col-12">
            <x-approval-status :model="$journalVoucher" :list-refresh="route('get.journal-vouchers')" />
        </div>
    </div>
</div>

@if ($jvApprovalLogs->isNotEmpty())
    <div class="approval-table-wrapper" style="margin-top: 25px; padding-bottom: 10px !important;">
        <div class="card border" style="box-shadow: none; margin-bottom: 0 !important;">
            <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 text-dark fw-bold" style="font-size: 14px;">
                    Approval History & Comments
                </h6>
                <span class="badge badge-info">{{ $jvApprovalLogs->count() }} {{ \Illuminate\Support\Str::plural('Action', $jvApprovalLogs->count()) }}</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover mb-0" style="font-size: 13px;">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 50px;" class="text-center">#</th>
                                <th style="min-width: 160px; width: 22%;">User</th>
                                <th style="min-width: 150px; width: 18%;" class="text-center">Action</th>
                                <th style="min-width: 160px; width: 20%;">Date & Time</th>
                                <th style="min-width: 300px; width: 40%;">Comments</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($jvApprovalLogs as $index => $log)
                                @php
                                    $badgeClass = match($log->action) {
                                        'approved' => 'badge-success',
                                        'rejected' => 'badge-danger',
                                        'reverted' => 'badge-warning',
                                        'partial_approved' => 'badge-info',
                                        default => 'badge-secondary'
                                    };
                                @endphp
                                <tr>
                                    <td class="text-center align-middle">{{ $index + 1 }}</td>
                                    <td class="align-middle">
                                        <strong>{{ $log->user->name ?? 'N/A' }}</strong>
                                        @if ($log->user_id === auth()->id())
                                            <span class="badge badge-primary ms-1" style="font-size: 10px;">You</span>
                                        @endif
                                    </td>
                                    <td class="text-center align-middle">
                                        <span class="badge {{ $badgeClass }} text-uppercase px-2 py-1" style="font-size: 11px;">
                                            {{ str_replace('_', ' ', $log->action) }}
                                        </span>
                                        @if ($loop->first)
                                            <div class="mt-1">
                                                <span class="current-status-tag">
                                                    <span class="current-status-dot"></span> Current
                                                </span>
                                            </div>
                                        @endif
                                    </td>
                                    <td class="align-middle">
                                        {{ $log->created_at ? $log->created_at->format('d M, Y h:i A') : 'N/A' }}
                                    </td>
                                    <td class="align-middle">
                                        @if (!empty(trim($log->comments ?? '')))
                                            <span class="text-dark">{{ $log->comments }}</span>
                                        @else
                                            <span class="text-muted fst-italic">No comments</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endif

<div class="row bottom-button-bar mt-3 mb-2">
    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
        <a type="button" class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton" data-bs-dismiss="modal">Close</a>
        <button type="button" class="btn btn-info mr-2 me-2 print-jv-btn" id="printButtonBottom">
            <i class="ft-printer mr-1 me-1"></i> Print
        </button>
    </div>
</div>

{{-- Printable Voucher Section --}}
<div class="journal-voucher-print pri" id="printSection" style="display: none;">
    <div class="rela">
        <div class="voucher-header mb-4">
            <div class="row" style="width: 100% !important; align-items: center !important;">
                <div class="col-6 col-lg-6 col-md-6 col-sm-6 col-xs-6">
                    <div class="logo">
                        <div class="logo-img">
                            <img class="logo-img" alt="Meskay Logo" style="max-height: 60px; max-width: 220px;"
                                src="{{ asset('management/app-assets/img/meskay-logo.png') }}">
                        </div>
                    </div>
                </div>
                <div class="col-6 col-lg-6 col-md-6 col-sm-6 col-xs-6 text-right">
                    <div class="addr">
                        <p class="mb-1"><strong>HEAD OFFICE:</strong></p>
                        <p class="mb-1">10th Floor, Office No. <br> 1008-1013 Salma Trade Tower,<br> Tower-B I-I
                            Chundrigar Road,<br> Karachi-Pakistan</p>
                        <p class="mb-1"><strong>T:</strong> +92-21-32275349-51</p>
                        <p class="mb-1"><strong>F:</strong> +92-21-32275352</p>
                    </div>
                </div>
            </div>
            <!-- Header Section -->
            <div class="row mt-3" style="width: 100% !important;">
                <div class="col-6 col-lg-6 col-md-6 col-sm-6 col-xs-6">
                    <div class="voucher-supp">
                        <h4 class="font-weight-bold" style="color: #2c3e50; margin: 0;">JOURNAL VOUCHER</h4>
                        <h6 class="text-dark font-weight-bold" style="margin-top: 5px;">{{ $journalVoucher->jv_no }}</h6>
                    </div>
                </div>
                <div class="col-6 col-lg-6 col-md-6 col-sm-6 col-xs-6 text-right">
                    <div class="voucher-supp p">
                        <p class="mb-1"><strong>Date:</strong> {{ $journalVoucher->jv_date ? $journalVoucher->jv_date->format('d-m-Y') : 'N/A' }}</p>
                        <p class="mb-1"><strong>Status:</strong> {{ ucfirst($currentStatus) }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="voucher-info-box mb-3 p-2 border" style="background-color: #fbfbfb;">
            <div class="row" style="width: 100% !important;">
                <div class="col-6 col-lg-6 col-md-6 col-sm-6 col-xs-6">
                    <p class="mb-1"><strong>Created By:</strong> {{ optional($journalVoucher->createdBy)->name ?? ($journalVoucher->username ?? 'N/A') }}</p>
                </div>
                <div class="col-6 col-lg-6 col-md-6 col-sm-6 col-xs-6 text-right">
                    <p class="mb-1"><strong>Approved By:</strong> {{ optional($journalVoucher->approveUser)->name ?? 'N/A' }}</p>
                </div>
            </div>
            <div class="row mt-1" style="width: 100% !important;">
                <div class="col-12 col-lg-12 col-md-12 col-sm-12 col-xs-12">
                    <p class="mb-0"><strong>Description:</strong> {{ $journalVoucher->description ?: 'N/A' }}</p>
                </div>
            </div>
        </div>

        <div class="voucher-transactions mb-4">
            <h5 class="mb-2 font-weight-bold" style="font-size: 14px;">Journal Entries</h5>
            <div class="table-responsive">
                <table class="table sale_older_tab table-bordered" style="border-collapse: collapse; width: 100%;">
                    <thead class="thead-light">
                        <tr>
                            <th style="width: 5%;" class="text-center">#</th>
                            <th style="width: 25%;">Account</th>
                            @if($isReceiving)
                                <th style="width: 15%;">Receipt Voucher</th>
                                <th style="width: 15%;">Sales Order</th>
                            @elseif($hasOrders)
                                <th style="width: 18%;">Order / GRN</th>
                            @endif
                            <th style="width: {{ ($isReceiving || $hasOrders) ? '25%' : '42%' }};">Description</th>
                            <th class="text-right" style="width: 14%;">Debit</th>
                            <th class="text-right" style="width: 14%;">Credit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($journalVoucher->journalVoucherDetails as $index => $detail)
                            <tr>
                                <td class="text-center">{{ $index + 1 }}</td>
                                <td style="white-space: normal; word-break: break-word;">
                                    <strong>{{ $detail->account->name ?? 'N/A' }}</strong><br>
                                    <small class="text-muted">({{ $detail->account->unique_no ?? 'N/A' }})</small>
                                </td>
                                @if($isReceiving)
                                    <td>{{ optional($detail->receiptVoucher)->unique_no ?? '—' }}</td>
                                    <td>{{ optional($detail->salesOrder)->reference_no ?? '—' }}</td>
                                @elseif($hasOrders)
                                    <td>{{ $detail->voucher_no ?? '—' }}</td>
                                @endif
                                <td style="white-space: normal; word-break: break-word; overflow-wrap: break-word;">
                                    {{ $detail->description ?? '—' }}
                                </td>
                                <td class="text-right" style="white-space: nowrap;">
                                    {{ $detail->debit_amount > 0 ? number_format($detail->debit_amount, 2) : '-' }}
                                </td>
                                <td class="text-right" style="white-space: nowrap;">
                                    {{ $detail->credit_amount > 0 ? number_format($detail->credit_amount, 2) : '-' }}
                                </td>
                            </tr>
                        @endforeach
                        <tr>
                            <td colspan="{{ $isReceiving ? 4 : ($hasOrders ? 3 : 2) }}" class="text-right">
                                <strong>Total Debits:</strong>
                            </td>
                            <td></td>
                            <td class="text-right font-weight-bold" style="white-space: nowrap;">
                                <strong>{{ number_format($totalDebits, 2) }}</strong>
                            </td>
                            <td></td>
                        </tr>
                        <tr>
                            <td colspan="{{ $isReceiving ? 4 : ($hasOrders ? 3 : 2) }}" class="text-right">
                                <strong>Total Credits:</strong>
                            </td>
                            <td></td>
                            <td></td>
                            <td class="text-right font-weight-bold" style="white-space: nowrap;">
                                <strong>{{ number_format($totalCredits, 2) }}</strong>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="{{ $isReceiving ? 4 : ($hasOrders ? 3 : 2) }}" class="text-right">
                                <strong>Difference (Debit - Credit):</strong>
                            </td>
                            <td></td>
                            <td class="text-right font-weight-bold" style="white-space: nowrap;">
                                <strong style="color: {{ abs($totalDebits - $totalCredits) > 0.01 ? '#dc3545' : '#28a745' }};">
                                    {{ number_format($totalDebits - $totalCredits, 2) }}
                                </strong>
                            </td>
                            <td></td>
                        </tr>
                        @if($totalDebits > 0)
                            <tr>
                                <td colspan="{{ ($isReceiving ? 4 : ($hasOrders ? 3 : 2)) + 3 }}" style="white-space: normal; word-break: break-word;">
                                    <strong>Amount in Words: </strong>
                                    {{ trim(preg_replace('/(\s*only\s*)+$/i', '', numberToWords($totalDebits))) }} Only.
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>

        <div class="voucher-footer mt-5 pt-3 border-top">
            <div class="row" style="width: 100% !important;">
                <div class="col-4 col-lg-4 col-md-4 col-sm-4 col-xs-4 text-center">
                    <p class="mb-1">_________________________</p>
                    <p class="mb-0"><strong>Prepared By</strong></p>
                    <small class="text-muted">{{ optional($journalVoucher->createdBy)->name ?? ($journalVoucher->username ?? '') }}</small>
                </div>
                <div class="col-4 col-lg-4 col-md-4 col-sm-4 col-xs-4 text-center">
                    <p class="mb-1">_________________________</p>
                    <p class="mb-0"><strong>Checked By</strong></p>
                </div>
                <div class="col-4 col-lg-4 col-md-4 col-sm-4 col-xs-4 text-center">
                    <p class="mb-1">_________________________</p>
                    <p class="mb-0"><strong>Approved By</strong></p>
                    <small class="text-muted">{{ optional($journalVoucher->approveUser)->name ?? '' }}</small>
                </div>
            </div>
        </div>
    </div>

    <div class="voucher-footer-absou voucher-footer mt-4 pt-3">
        <div class="footer-paragargh">
            <p>NAUSHEHRO FEROZE | LARKANA | SHIKARPUR | TANDO ALLAHYAR | PAKPATTAN</p>
        </div>
    </div>
</div>

<style>
    .table td,
    .table th {
        word-break: break-word;
        overflow-wrap: break-word;
    }

    .table td.text-right,
    .table th.text-right {
        white-space: nowrap;
    }

    .approval-view-wrapper .alert-primary,
    .approval-view-wrapper .alert-warning {
        display: none !important;
    }
    .current-status-tag {
        display: inline-flex;
        align-items: center;
        gap: 3px;
        font-size: 9px;
        font-weight: 700;
        color: #047857;
        background-color: #ecfdf5;
        border: 1px solid #a7f3d0;
        padding: 1px 7px;
        border-radius: 10px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        line-height: 1.4;
    }
    .current-status-dot {
        width: 5px;
        height: 5px;
        background-color: #10b981;
        border-radius: 50%;
        display: inline-block;
    }

    .footer-paragargh {
        background: #599364;
        color: #fff;
        padding: 10px 0px;
        border-radius: 4px;
        width: 100% !important;
        text-align: center !important;
    }
</style>

<script>
    function printView(param1, param2, param3) {
        $('.printHide').hide();

        var printElement = document.getElementById(param1);
        if (!printElement) {
            console.error("Print element not found: " + param1);
            return;
        }

        var printContents = printElement.innerHTML;
        var printWindow = window.open('', '', 'height=600,width=800');
        var printStyles = `
        <style>
            @media print{
                @page{margin:1em !important;}
                *{ -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
                body{background:white !important; font-family: "Montserrat", Arial, sans-serif !important;}
                .row{display:flex !important;flex-wrap:wrap !important;width:100% !important;margin:0 !important;padding:0 !important;}
                [class*="col-"]{box-sizing:border-box !important;padding:0 8px !important;}
                .col-6, [class*="col-lg-6"], [class*="col-md-6"], [class*="col-sm-6"]{flex:0 0 50% !important;max-width:50% !important;}
                .col-4, [class*="col-lg-4"], [class*="col-md-4"], [class*="col-sm-4"]{flex:0 0 33.333% !important;max-width:33.333% !important;}
                .col-12, [class*="col-lg-12"]{flex:0 0 100% !important;max-width:100% !important;}
                .logo-img img, .logo img{max-height:60px !important; max-width:200px !important;}
                .voucher-header,.journal-voucher-print{width:100% !important;margin:0 !important;padding:0 !important;}
                .text-right{text-align:right !important;}
                .text-center{text-align:center !important;}
                .no-print{display:none !important;}
                .table-responsive{overflow:visible !important;width:100% !important;display:block !important;}
                .table{width:100% !important;max-width:100% !important;page-break-inside:auto !important;border-collapse:collapse !important;}
                .table tr{page-break-inside:avoid !important;}
                table tbody tr td{white-space:normal !important;word-break:break-word !important;overflow-wrap:break-word !important;vertical-align:top !important;}
                table tbody tr td.text-right,table thead tr th.text-right{white-space:nowrap !important;}
                table td, table th{padding:4px 6px !important;font-size:12px !important;border:1px solid #000000 !important;}
                .table .thead-light th{color:#495057 !important;background-color:#e9ecef !important;}
                .voucher-footer{width:100% !important;margin-top:2rem !important;padding-top:1rem !important;}
                .voucher-footer .row{display:flex !important;flex-wrap:wrap !important;justify-content:space-between !important;text-align:center !important;}
                .voucher-footer p{margin:5px 0 !important;font-size:12px !important;}
                .addr p{margin:0 !important;}
                .voucher-supp h4{margin:0!important;}
                .voucher-supp p{margin:0!important;}
                .rela{position:relative !important;}
                .voucher-footer-absou{margin-top:30px !important; border-top:none !important;}
                .footer-paragargh{background:#599364 !important;color:#fff !important;padding:10px 0px !important;border-radius:4px !important;text-align:center !important;width:100% !important;font-size:11px !important;}
            }
        </style>
    `;

        printWindow.document.write('<html><head><title>Journal Voucher - {{ $journalVoucher->jv_no }}</title>');
        printWindow.document.write(printStyles);
        printWindow.document.write('</head><body>');
        printWindow.document.write(printContents);
        printWindow.document.write('</body></html>');
        printWindow.document.close();
        printWindow.focus();

        setTimeout(function () {
            printWindow.print();
            setTimeout(function () {
                printWindow.close();
            }, 500);
        }, 500);
    }

    document.querySelectorAll(".print-jv-btn").forEach(function(btn) {
        btn.addEventListener("click", function () {
            printView('printSection', 'print-section', 0);
        });
    });

    document.addEventListener('keydown', function (e) {
        if (e.ctrlKey && e.key === 'p') {
            e.preventDefault();
            printView('printSection', 'print-section', 0);
        }
    });
</script>