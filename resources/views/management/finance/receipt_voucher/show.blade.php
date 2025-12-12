<div class="receipt-voucher-print pri" id="printSection">
    <div class="voucher-header mb-4">
        <div class="row" style="width: 100% !important;">
            <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6">
                <h4 class="font-weight-bold">Voucher No.</h4>
                <h6 class="text-dark font-weight-bold">{{ $receiptVoucher->unique_no }}</h6>
            </div>
            <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6 text-right">
                <p class="mb-1"><strong>Date:</strong> {{ optional($receiptVoucher->rv_date)->format('d-M-Y') }}</p>
                <p class="mb-1"><strong>Type:</strong> {{ formatEnumValue($receiptVoucher->voucher_type) }}</p>
            </div>
        </div>
    </div>

    <div class="mb-4">
        <div class="row" style="width: 100% !important;">
            <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6">
                <h5 class="font-weight-bold">Received From:</h5>
                <p><strong>Customer:</strong> {{ $receiptVoucher->customer->name ?? 'N/A' }}</p>
                <p><strong>Account:</strong> {{ $receiptVoucher->account->account_name ?? $receiptVoucher->account->name ?? 'N/A' }}</p>
            </div>
            <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6">
                <h5 class="font-weight-bold">Reference Information</h5>
                <p><strong>Bill/Ref No.:</strong> {{ $receiptVoucher->ref_bill_no ?? 'N/A' }}</p>
                <p><strong>Bill Date:</strong> {{ $receiptVoucher->bill_date ? $receiptVoucher->bill_date->format('d-M-Y') : 'N/A' }}</p>
                <p><strong>Cheque No.:</strong> {{ $receiptVoucher->cheque_no ?? 'N/A' }}</p>
                <p><strong>Cheque Date:</strong> {{ $receiptVoucher->cheque_date ? $receiptVoucher->cheque_date->format('d-M-Y') : 'N/A' }}</p>
            </div>
        </div>
    </div>

    <div class="voucher-requests mb-4">
        <h5 class="mb-3">Documents</h5>
        <div class="table-responsive">
            <table class="table sale_older_tab userlittab table table-bordered sf-table-list sale-list table-hover" style="border-collapse: collapse;">
                <thead class="thead-light">
                    <tr>
                        <th>#</th>
                        <th>Type</th>
                        <th>Document No</th>
                        <th>Customer</th>
                        <th class="text-right">Amount</th>
                        <th class="text-right">Tax Amount</th>
                        <th class="text-right">Net Amount</th>
                        <th>Line Desc</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $index => $item)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $item['type'] }}</td>
                            <td>{{ $item['doc_no'] }}</td>
                            <td>{{ $item['customer'] }}</td>
                            <td class="text-right">{{ number_format($item['amount'], 2) }}</td>
                            <td class="text-right">{{ number_format($item['tax_amount'], 2) }}</td>
                            <td class="text-right">{{ number_format($item['net_amount'], 2) }}</td>
                            <td>{{ $item['line_desc'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted">No line items</td>
                        </tr>
                    @endforelse
                    <tr>
                        <td colspan="6" class="text-right"><strong>Total Amount:</strong></td>
                        <td class="text-right"><strong>{{ number_format($receiptVoucher->total_amount, 2) }}</strong></td>
                        <td></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="voucher-remarkss p-3 border">
        <h5 class="">Remarks</h5>
        <p class="mb-0">{{ $receiptVoucher->remarks ?? 'N/A' }}</p>
    </div>

    <div class="voucher-footer mt-4 pt-3 border-top">
        <div class="row">
            <div class="col-lg-4 col-md-4 col-sm-4 col-xs-4 text-center">
                <p class="mb-1">_________________________</p>
                <p class="mb-0">Prepared By</p>
            </div>
            <div class="col-lg-4 col-md-4 col-sm-4 col-xs-4 text-center">
                <p class="mb-1">_________________________</p>
                <p class="mb-0">Checked By</p>
            </div>
            <div class="col-lg-4 col-md-4 col-sm-4 col-xs-4 text-center">
                <p class="mb-1">_________________________</p>
                <p class="mb-0">Approved By</p>
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
    .receipt-voucher-print {
        font-family: Arial, sans-serif;
        color: #333;
    }

    .voucher-header {
        border-bottom: 2px solid #000000;
        padding-bottom: 15px;
    }

    .voucher-remarkss {
        background-color: #f8f9fa;
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

    p {
        margin-bottom: 0;
        padding-bottom: 2px;
    }
</style>

<script>
    function printView(param1, param2, param3) {
        $('.printHide').hide();

        var printContents = document.getElementById(param1).innerHTML;
        var printWindow = window.open('', '', 'height=600,width=800');
        var printStyles = `
        <style>
            @media print{
                @page{margin:1em !important;}
                body{background:white !important;}
                .row{display:flex !important;flex-wrap:wrap !important;width:100% !important;margin:0 !important;padding:0 !important;}
                [class*="col-"]{flex:0 0 50% !important;max-width:50% !important;box-sizing:border-box;padding:0 10px !important;}
                .voucher-header,.receipt-voucher-print{width:100% !important;margin:0 !important;padding:0 !important;}
                .text-right{text-align:right !important;}
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
            }, 500);
        }, 500);
    }

    document.getElementById("printButton").addEventListener("click", function() {
        printView('printSection', 'print-section', 0);
    });

    document.addEventListener('keydown', function(e) {
        if (e.ctrlKey && e.key === 'p') {
            e.preventDefault();
            printView('printSection', 'print-section', 0);
        }
    });
</script>

