@php
    use App\Models\Procurement\PaymentRequestData;
@endphp
<div class="payment-voucher-print pri" id="printSection">
    <!-- Header Section -->
    <div class="voucher-header mb-4">
        <div class="row" style=" width: 100% !important;">
            <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6">
                <h4 class="font-weight-bold">Voucher No.</h4>
                <h6 class="text-dark font-weight-bold">{{ $paymentVoucher->unique_no }}</h6>
            </div>
            <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6 text-right">
                <p class="mb-1"><strong>Date:</strong> {{ $paymentVoucher->pv_date->format('d-M-Y') }}</p>
                <p class="mb-1"><strong>Type:</strong> {{ formatEnumValue($paymentVoucher->voucher_type) }}</p>
            </div>
        </div>
    </div>

    <div class="mb-4">
        <div class="row" style=" width: 100% !important;">
            <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6">
                <h5 class="font-weight-bold">Paid to:</h5>
                <p><strong>Supplier:</strong> {{ $paymentVoucher->supplier->name ?? 'N/A' }}</p>
            </div>
            <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6">
                <h5 class="font-weight-bold">Reference Information</h5>
                <p><strong>Bill/Ref No.:</strong> {{ $paymentVoucher->ref_bill_no ?? 'N/A' }}</p>
                <p><strong>Bill Date:</strong>
                    {{ $paymentVoucher->bill_date ? $paymentVoucher->bill_date->format('d-M-Y') : 'N/A' }}</p>
            </div>
        </div>

        {{-- @dd($paymentVoucher->voucher_type == 'bank_payment_voucher', $bankAccount) --}}
        @if ($paymentVoucher->voucher_type == 'bank_payment_voucher' && $bankAccount)
            <div class="row">
                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                    <h5 class="font-weight-bold">Bank Details</h5>
                    <p>
                        <strong>Bank / Branch:</strong>
                        {{ trim(
                            ($bankAccount->bank_name ?? '') .
                                ($bankAccount->branch_name ?? '' ? ' / ' . $bankAccount->branch_name : '') .
                                ($bankAccount->branch_code ?? '' ? ' / ' . $bankAccount->branch_code : ''),
                        ) ?:
                            'N/A' }}
                    </p>
                    <p><strong>Account Title:</strong> {{ $bankAccount->account_title ?? 'N/A' }}</p>
                    <p><strong>Account Number:</strong> {{ $bankAccount->account_number ?? 'N/A' }}</p>
                    <p><strong>Cheque No.:</strong> {{ $paymentVoucher->cheque_no ?? 'N/A' }}</p>
                    <p><strong>Cheque Date:</strong>
                        {{ $paymentVoucher->cheque_date ? $paymentVoucher->cheque_date->format('d-M-Y') : 'N/A' }}</p>
                </div>
            </div>
        @endif
    </div>



    <div class="voucher-po mb-4 p-3 border rounded d-none">
        <h5 class=" ">Purchase Order</h5>
        <p class="mb-0">
            @if ($paymentVoucher->module_id)
                @php
                    $poNumbers = [];
                    foreach ($paymentVoucher->paymentVoucherData as $voucherData) {
                        $po = $voucherData->paymentRequest->paymentRequestData->purchaseOrder->contract_no ?? null;
                        if ($po) {
                            $poNumbers[] = $po;
                        }
                    }
                @endphp
                {{ count($poNumbers) ? implode(', ', $poNumbers) : 'N/A' }}
            @else
                N/A
            @endif
        </p>
    </div>

    <div class="voucher-requests mb-4 d-none">
        <h5 class="  mb-3">Payment Requests</h5>
        <div class="table-responsive">
            <table class="table  sale_older_tab userlittab table table-bordered sf-table-list sale-list table-hover"
                style="border-collapse: collapse; ">
                <thead class="thead-light">
                    <tr>
                        <th>#</th>
                        <th>Request No</th>
                        <th>Date</th>
                        <th class="text-right">Amount</th>
                        <th>Purpose</th>
                        <th>Type</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($paymentVoucher->paymentVoucherData as $index => $voucherData)
                        @php
                            $request = $voucherData->paymentRequest;
                            if(!$request) continue;
                            $purchaseOrder = $request->paymentRequestData->purchaseOrder ?? null;
                        @endphp
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $request->paymentRequestData->purchaseOrder->contract_no ?? 'N/A' }}</td>
                            <td>{{ $request?->created_at?->format('d-M-Y') }}</td>
                            <td class="text-right">{{ number_format($request->amount, 2) }}</td>
                            <td>{{ $request->paymentRequestData->notes ?? 'No description' }}</td>
                            <td>
                                <span
                                    class="badge badge-{{ $request->request_type == 'Payment' ? 'success' : 'warning' }}">
                                    {{ formatEnumValue($request->request_type) }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                    <tr>
                        <td colspan="3" class="text-right"><strong>Total Amount:</strong></td>
                        <td class="text-right"><strong>{{ number_format($paymentVoucher->total_amount, 2) }}</strong>
                        </td>
                        <td colspan="2"></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="voucher-transactions mb-4">
        <h5 class="mb-3">Transaction Details</h5>
        <div class="table-responsive">
            <table class="table  sale_older_tab userlittab table table-bordered sf-table-list sale-list table-hover"
                style="border-collapse: collapse; ">
                <thead class="thead-light">
                    <tr>
                        {{-- <th class="text-center">Date</th> --}}
                        <th>Contract No</th>
                        <th>Description</th>
                        <th class="text-right">Debit</th>
                        <th class="text-right">Credit</th>
                        {{-- <th class="text-right">Balance</th> --}}
                    </tr>
                </thead>
                <tbody>
                    @php
                        $balance = 0;
                    @endphp

                    @foreach ($transactions as $transaction)
                        @php
                            if ($transaction->type == 'debit') {
                                $balance += $transaction->amount;
                            } else {
                                $balance -= $transaction->amount;
                            }

                            $parts = explode('-', $transaction->payment_against);
                            $lastPart = trim(end($parts)) ?? null;

                            if ($lastPart) {
                                $paymentRequestData = PaymentRequestData::find($lastPart);
                                $purchaseOrder = $paymentRequestData->purchaseOrder;
                            }
                        @endphp
                        <tr>
                            {{-- <td class="text-center">{{ $transaction->voucher_date->format('d-m-Y') }}</td> --}}
                            <td>
                                {{-- #{{ $purchaseOrder->contract_no ?? 'N/A' }} --}}
                                {{ $transaction->account->name ?? 'N/A' }}
                            </td>
                            <td>
                                {{ $transaction->remarks }}
                                @if ($transaction->payment_against)
                                    <br><small class="text-muted">Against: {{ $transaction->payment_against }}
                                        @if ($transaction->against_reference_no)
                                            ({{ $transaction->against_reference_no }})
                                        @endif
                                        ({{ formatEnumValue($transaction->purpose) }})
                                    </small>
                                @endif
                            </td>
                            <td class="text-right">
                                {{ $transaction->type == 'debit' ? number_format($transaction->amount, 2) : '-' }}</td>
                            <td class="text-right">
                                {{ $transaction->type == 'credit' ? number_format($transaction->amount, 2) : '-' }}
                            </td>
                            {{-- <td class="text-right">{{ number_format($balance, 2) }}</td> --}}
                        </tr>
                    @endforeach
                    <tr>
                        {{-- <td class="text-center">{{ $transaction->voucher_date->format('d-m-Y') }}</td> --}}
                        <td>
                            <h5 style="margin: 0 !important;" class="my-2">
                                <strong>
                                    Total:
                                </strong>
                            </h5>
                        </td>
                        <td>
                            <strong>Total in Words: </strong>
                            {{ numberToWords($paymentVoucher->total_amount) }}.
                        </td>
                        <td class="text-right"></td>
                        <td class="text-right">
                            <strong>
                                {{ number_format($paymentVoucher->total_amount, 2) }}
                            </strong>
                        </td>
                        {{-- <td class="text-right">{{ number_format($balance, 2) }}</td> --}}
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="voucher-remarkss p-3 border">
        <h5 class="">Remarks</h5>
        <p class="mb-0">{{ $paymentVoucher->remarks ?? 'N/A' }}</p>
    </div>

    <div class="voucher-footer mt-4 pt-3 border-top">
        <div class="row">
            <div class="col-12">
                <x-approval-status :model="$data" />
            </div>
        </div>
    </div>
</div>
<div class="row bottom-button-bar">
    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
        <a type="button" class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton">Close</a>
        <button type="button" class="btn btn-info mr-2" id="printButton">
            <i class="ft-printer mr-1"></i> Print
        </button>
    </div>
</div>

<style>
    .payment-voucher-print {
        font-family: Arial, sans-serif;
        color: #333;
    }

    .voucher-header {
        border-bottom: 2px solid #000000;
        padding-bottom: 15px;
    }

    .voucher-info,
    .voucher-po,
    .voucher-remarks,
    .voucher-total {
        background-color: #f8f9fa;
    }

    .info-item {
        padding: 10px;
        background-color: white;
        border-radius: 5px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    .table thead th {
        background-color: #e9ecef;
        font-weight: 600;
    }

    .table-bordered {
        border: 1px solid #dee2e6;
    }

    .table-hover tbody tr:hover {
        background-color: rgba(0, 123, 255, 0.05);
    }

    .badge-success {
        background-color: #28a745;
    }

    .badge-warning {
        background-color: #ffc107;
        color: #212529;
    }

    p {
        margin-bottom: 0;
        padding-bottom: 2px
    }
</style>

<script>
    function printView(param1, param2, param3) {
        $(".qrCodeDiv").removeClass("hidden");

        if (param2 !== "") {
            $('.' + param2).prop('href', '');
        }

        $('.printHide').hide();

        var printContents = document.getElementById(param1).innerHTML;

        // Open new print window
        var printWindow = window.open('', '', 'height=600,width=800');

        // Define print styles
        var printStyles = `
        <style>
            @media print{
                @page{margin:1em !important;}
                body{background:white !important;}
                .row{display:flex !important;flex-wrap:wrap !important;width:100% !important;margin:0 !important;padding:0 !important;}
                [class*="col-"]{flex:0 0 50% !important;/* for col-6 */
                max-width:50% !important;box-sizing:border-box;padding:0 10px !important;}
                .voucher-header,.payment-voucher-print{width:100% !important;margin:0 !important;padding:0 !important;}
                .text-right{text-align:right !important;}
                .no-print{display:none !important;}
                .flex-head{display:flex !important;align-items:center !important;justify-content:left !important;}
                .add-main1 ul{display:flex !important;align-items:center !important;justify-content:space-evenly !important;padding:0 !important;list-style:none !important;}
                .logo img{width:67% !important;}
                .head-add1 h5{font-size:15px !important;font-weight:700 !important;margin-bottom:6px !important;}
                .head-add1 p{font-size:12px !important;margin-bottom:8px !important;}
                .add-main2{display:flex !important;align-items:center !important;justify-content:space-between !important;}
                a.btn.btn-a{border:2px solid #ddd;color:#000;}
                a.btn.btn-a:hover{box-shadow:0 2px 7px rgba(0,0,0,0.28) !important;cursor:pointer !important;background:#008749 !important;color:#fff !important;}
                .logo p{font-weight:bold !important;}
                #modal-sidebar.open{width:100% !important;}
                table td input{padding:8px 8px !important;}
                table tbody tr td{white-space:nowrap !important;}
                table td{padding:5px 5px !important;}
                .table-responsive .sale_older_tab > caption + thead > tr:first-child > th,.sale_older_tab > colgroup + thead > tr:first-child > th,.sale_older_tab > thead:first-child > tr:first-child > th,.sale_older_tab > caption + thead > tr:first-child > td,.sale_older_tab > colgroup + thead > tr:first-child > td,.sale_older_tab > thead:first-child > tr:first-child > td{border-top:0;font-size:10px !important;padding:9px 5px !important;}
                .table-responsive .sale_older_tab > thead > tr > th,.sale_older_tab > tbody > tr > th,.sale_older_tab > tfoot > tr > th,.sale_older_tab > thead > tr > td,.sale_older_tab > tbody > tr > td,.table > tfoot > tr > td{padding:2px 5px !important;font-size:10px !important;border-top:1px solid #000000 !important;border-bottom:1px solid #000000 !important;border-left:1px solid #000000 !important;border-right:1px solid #000000 !important;}
                .table{page-break-inside:avoid !important;}
                .table-bordered th,.table-bordered td{border:1px solid #E0E0E0 !important;}
                .table .thead-light th{color:#495057 !important;background-color:#e9ecef !important;border-color:#E0E0E0 !important;}
                .table th,.table td{padding:0.4rem 1rem !important;vertical-align:center !important;border-top:1px solid #E0E0E0 !important;}
                .voucher-footer{width:100% !important;margin-top:2rem !important;padding-top:1rem !important;border-top:1px solid #000 !important;}
                .voucher-footer .row{display:flex !important;flex-wrap:wrap !important;justify-content:space-between !important;text-align:center !important;}
                .voucher-footer [class*="col-"]{flex:0 0 33.333% !important;max-width:33.333% !important;box-sizing:border-box !important;padding:0 10px !important;}
                .voucher-footer p{margin:5px 0 !important;font-size:12px !important;}
            }
        </style>
    `;

        printWindow.document.write('<html><head><title>Print</title>');
        printWindow.document.write(printStyles);
        printWindow.document.write('</head><body>');
        printWindow.document.write(printContents);
        printWindow.document.write('</body></html>');
        printWindow.document.close();
        printWindow.focus();

        setTimeout(function() {
            printWindow.print();
            setTimeout(function() {
                printWindow.close();
                if (param3 !== 1) {
                    // location.reload();
                }
            }, 500);
        }, 500);
    }

    // Bind the function to the button
    document.getElementById("printButton").addEventListener("click", function() {
        printView('printSection', 'print-section', 0);
    });

    document.addEventListener('keydown', function(e) {
        if (e.ctrlKey && e.key === 'p') {
            e.preventDefault();
            printView('printSection', 'print-section', 0);
        }
    })
</script>
