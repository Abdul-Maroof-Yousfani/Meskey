<!-- Action Bar (Hidden on Print) -->
<div class="d-flex justify-content-end mb-2 d-print-none bg-light p-1 rounded">
    <div>
        <button onclick="printDiv('print-area')" class="btn btn-primary btn-sm">
            <i class="ft-printer mr-1"></i> Print
        </button>
        <button type="button" class="btn btn-danger btn-sm ml-1" data-close="model">
            <i class="ft-x"></i>
        </button>
    </div>
</div>
<br>
<div class="print-ready-section" id="print-area">
    <div class="rela">
        <div class="header mb-4">
            <div class="row" style="width: 100% !important; align-items: center !important;">
                <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6">
                    <div class="logo">
                        <a class="logo-text" href="#">
                            <div class="logo-img">
                                <img class="logo-img" alt="Meskay Logo" src="/storage/company_logos/meskay-logo-20250514082251.png">
                            </div>
                        </a>
                    </div>
                </div>
                <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6 text-right">
                    <div class="addr">
                        <p class="mb-1"><strong>HEAD OFFICE:</strong></p>
                        <p class="mb-1">10th Floor, Office No. <br> 1008-1013 Saima Trade Tower,<br> Tower-B I-I Chundrigar Road,<br> Karachi-Pakistan</p>
                        <p class="mb-1"><strong>T:</strong> +92-21-32275349-51</p>
                        <p class="mb-1"><strong>F:</strong> +92-21-32275352</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-center mb-3">
            <h4 class="font-weight-bold text-uppercase" style="letter-spacing: 1px; color: #2b4380;">PRODUCTION VOUCHER</h4>
            <span class="badge badge-primary px-3 py-1 font-medium-1"># {{ $productionVoucher->prod_no }}</span>
        </div>

        <!-- Print Content -->
        <div class="pv-print-container p-0 bg-white">
            <div class="row">
                <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6">
                    <table class="table table-bordered pv-info-table mb-0">
                        <tbody>
                            <tr class="section-header">
                                <th colspan="2" class="text-center py-1">VOUCHER INFORMATION</th>
                            </tr>
                            <tr>
                                <th width="35%" class="text-uppercase bg-light">Voucher No.</th>
                                <td class="font-weight-bold">{{ $productionVoucher->prod_no }}</td>
                            </tr>
                            <tr>
                                <th class="text-uppercase bg-light">Date</th>
                                <td>{{ $productionVoucher->prod_date ? \Carbon\Carbon::parse($productionVoucher->prod_date)->format('d.m.Y') : '-' }}</td>
                            </tr>
                            <tr>
                                <th class="text-uppercase bg-light">Location</th>
                                <td>{{ $productionVoucher->location->name ?? 'N/A' }}</td>
                            </tr>
                            @if($productionVoucher->subLocation)
                            <tr>
                                <th class="text-uppercase bg-light">Sub Location</th>
                                <td>{{ $productionVoucher->subLocation->name }}</td>
                            </tr>
                            @endif
                            @if($productionVoucher->plant)
                            <tr>
                                <th class="text-uppercase bg-light">Plant</th>
                                <td>{{ $productionVoucher->plant->name }}</td>
                            </tr>
                            @endif
                            <tr>
                                <th class="text-uppercase bg-light">Supervisor</th>
                                <td>{{ $productionVoucher->supervisor->name ?? 'N/A' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6">
                    <table class="table table-bordered pv-info-table mb-0">
                        <tbody>
                            <tr class="section-header">
                                <th colspan="2" class="text-center py-1">PRODUCT & JOB DETAILS</th>
                            </tr>
                            <tr>
                                <th width="35%" class="text-uppercase bg-light">Head Product</th>
                                <td class="font-weight-bold text-primary">{{ $productionVoucher->product->name ?? 'N/A' }}</td>
                            </tr>
                            @if($productionVoucher->byProducts)
                            <tr>
                                <th class="text-uppercase bg-light">By-Product</th>
                                <td>{{ $productionVoucher->byProducts->name }}</td>
                            </tr>
                            @endif
                            <tr>
                                <th class="text-uppercase bg-light">Job Order(s)</th>
                                <td>
                                    @if($productionVoucher->jobOrders && $productionVoucher->jobOrders->count() > 0)
                                        @foreach($productionVoucher->jobOrders as $jo)
                                            <span class="badge badge-primary mr-1">{{ $jo->job_order_no }}</span>
                                        @endforeach
                                    @elseif($productionVoucher->jobOrder)
                                        <span class="badge badge-primary">{{ $productionVoucher->jobOrder->job_order_no }}</span>
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th class="text-uppercase bg-light">Total Input Qty</th>
                                <td class="font-weight-bold">{{ number_format($productionVoucher->net_total_input ?? 0, 2) }} KG</td>
                            </tr>
                            <tr>
                                <th class="text-uppercase bg-light">Total Output Qty</th>
                                <td class="font-weight-bold text-success">{{ number_format($productionVoucher->produced_qty_kg ?? 0, 2) }} KG</td>
                            </tr>
                            <tr>
                                <th class="text-uppercase bg-light">Status</th>
                                <td>
                                    <span class="badge badge-{{ $productionVoucher->status === 'completed' || $productionVoucher->status === 'approved' ? 'success' : 'warning' }}">
                                        {{ ucfirst($productionVoucher->status ?? 'Draft') }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- SECTION: INPUTS --}}
            @if($productionVoucher->inputs && $productionVoucher->inputs->count() > 0)
            <div class="row mt-3">
                <div class="col-12">
                    <table class="table table-bordered pv-info-table mb-0">
                        <thead>
                            <tr class="section-header">
                                <th colspan="4" class="text-center py-1">PRODUCTION INPUTS</th>
                            </tr>
                            <tr class="bg-light text-center">
                                <th width="35%">Product</th>
                                <th width="30%">Location</th>
                                <th width="20%">Quantity (KG)</th>
                                <th width="15%">Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $totalInput = 0; @endphp
                            @foreach($productionVoucher->inputs as $inp)
                                @php $totalInput += (float)($inp->qty ?? 0); @endphp
                                <tr>
                                    <td>{{ $inp->product->name ?? 'N/A' }}</td>
                                    <td>{{ $inp->location->name ?? 'N/A' }}</td>
                                    <td class="text-right font-weight-bold">{{ number_format($inp->qty ?? 0, 2) }}</td>
                                    <td>{{ $inp->remarks ?? '-' }}</td>
                                </tr>
                            @endforeach
                            <tr class="bg-light font-weight-bold">
                                <td colspan="2" class="text-right"><strong>Total Input:</strong></td>
                                <td class="text-right font-weight-bold">{{ number_format($totalInput, 2) }} KG</td>
                                <td></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            {{-- SECTION: OUTPUTS --}}
            @if($productionVoucher->outputs && $productionVoucher->outputs->count() > 0)
            <div class="row mt-3">
                <div class="col-12">
                    <table class="table table-bordered pv-info-table mb-0">
                        <thead>
                            <tr class="section-header">
                                <th colspan="5" class="text-center py-1">PRODUCTION OUTPUTS</th>
                            </tr>
                            <tr class="bg-light text-center">
                                <th width="30%">Product</th>
                                <th width="20%">Storage Location</th>
                                <th width="20%">Brand</th>
                                <th width="15%">Quantity (KG)</th>
                                <th width="15%">Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $totalOutput = 0; @endphp
                            @foreach($productionVoucher->outputs as $out)
                                @php $totalOutput += (float)($out->qty ?? 0); @endphp
                                <tr>
                                    <td>{{ $out->product->name ?? 'N/A' }}</td>
                                    <td>{{ $out->storageLocation->name ?? 'N/A' }}</td>
                                    <td>{{ $out->brand->name ?? '-' }}</td>
                                    <td class="text-right font-weight-bold">{{ number_format($out->qty ?? 0, 2) }}</td>
                                    <td>{{ $out->remarks ?? '-' }}</td>
                                </tr>
                            @endforeach
                            <tr class="bg-light font-weight-bold">
                                <td colspan="3" class="text-right"><strong>Total Output:</strong></td>
                                <td class="text-right font-weight-bold text-success">{{ number_format($totalOutput, 2) }} KG</td>
                                <td></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            {{-- SECTION: LABOUR & CHARGES --}}
            @if($productionVoucher->total_labour_charges > 0 || $productionVoucher->labour_charges_per_kg > 0)
            <div class="row mt-3">
                <div class="col-12">
                    <table class="table table-bordered pv-info-table mb-0">
                        <tbody>
                            <tr class="section-header">
                                <th colspan="4" class="text-center py-1">LABOUR CHARGES</th>
                            </tr>
                            <tr>
                                <th width="25%" class="bg-light">Rate / KG:</th>
                                <td width="25%">{{ number_format($productionVoucher->labour_charges_per_kg ?? 0, 4) }}</td>
                                <th width="25%" class="bg-light">Total Charges:</th>
                                <td width="25%">{{ number_format($productionVoucher->total_labour_charges ?? 0, 2) }}</td>
                            </tr>
                            <tr>
                                <th class="bg-light">Deduction:</th>
                                <td>{{ number_format($productionVoucher->labour_deduction ?? 0, 2) }}</td>
                                <th class="bg-light">Net Labour Amount:</th>
                                <td class="font-weight-bold">{{ number_format($productionVoucher->labour_net_amount ?? 0, 2) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            @if($productionVoucher->remarks || $productionVoucher->qc_remarks)
            <div class="row mt-3">
                @if($productionVoucher->remarks)
                <div class="col-6">
                    <div class="border p-2">
                        <strong>Remarks:</strong>
                        <p class="mb-0">{{ $productionVoucher->remarks }}</p>
                    </div>
                </div>
                @endif
                @if($productionVoucher->qc_remarks)
                <div class="col-6">
                    <div class="border p-2">
                        <strong>QC Remarks:</strong>
                        <p class="mb-0">{{ $productionVoucher->qc_remarks }}</p>
                    </div>
                </div>
                @endif
            </div>
            @endif

            <div class="footer-paragargh mt-4">
                <p class="mb-0">| NAUSHEHRO FEROZE | LARKANA | SHIKARPUR | TANDO ALLAHYAR | PAKPATTAN</p>
            </div>
        </div>
    </div>
</div>

@php
    $pvModule = $productionVoucher->getApprovalModule();
    $pvApprovalLogs = $pvModule ? \App\Models\ApprovalsModule\ApprovalLog::where('record_id', $productionVoucher->id)->where('module_id', $pvModule->id)->with(['user', 'role'])->orderBy('created_at', 'desc')->get() : collect();
@endphp

<div class="approval-view-wrapper mt-3">
    <div class="row">
        <div class="col-12">
            <x-approval-status :model="$productionVoucher" :list-refresh="route('get.production-voucher')"/>
        </div>
    </div>
</div>

<script>
    $(document).ready(function () {
        setTimeout(function () {
            $('.approval-view-wrapper textarea, .approval-view-wrapper input').prop('readonly', false).prop('disabled', false).removeAttr('readonly').removeAttr('disabled');
        }, 50);
    });
</script>

<style>
    .pv-info-table{border:2px solid #cdcdcd !important;border-collapse:collapse;width:100% !important;}
    .pv-info-table th,.pv-info-table td{border:1px solid #000 !important;line-height:1.25;padding:4px 10px !important;}
    .pv-info-table th{font-size:11px;background-color:#f8f9fa !important;font-weight:700;vertical-align:middle;-webkit-print-color-adjust:exact;}
    .pv-info-table td{font-size:12px;vertical-align:top;}
    .section-header th{background-color:#cdcdcd !important;color:#000000 !important;font-size:12px;letter-spacing:1px;}
    .footer-paragargh{background:#599364 !important;color:#fff !important;padding:8px 0px !important;border-radius:4px !important;text-align:center !important;width:100% !important;font-size:11px;}
    .logo-img img{width:22%;}
    @media print{
        .d-print-none{display:none !important;}
        body *{visibility:hidden;}
        #print-area,#print-area *{visibility:visible;}
        #print-area{position:absolute;left:0;top:0;width:100% !important;}
        .bg-light{background-color:#f8f9fa !important;-webkit-print-color-adjust:exact;}
        .section-header th{background-color:#333 !important;color:#fff !important;-webkit-print-color-adjust:exact;}
    }
</style>

<script>
function printDiv(divId) {
    var divToPrint = document.getElementById(divId).innerHTML;
    var newWindow = window.open('', '', 'width=900,height=650');
    newWindow.document.write(`
        <html>
        <head>
            <title>Print Production Voucher</title>
            <link rel="stylesheet" href="${document.querySelector('link[rel=stylesheet]').href}">
            <style>
                @page{margin:1em !important;}
                body{background:white !important; font-family: Arial, sans-serif;}
                .row{display:flex !important;flex-wrap:wrap !important;width:100% !important;margin:0 !important;padding:0 !important;}
                [class*="col-"]{box-sizing:border-box;padding:0 10px !important;}
                .col-6, [class*="col-6"]{flex:0 0 50% !important;max-width:50% !important;}
                .col-12{flex:0 0 100% !important;max-width:100% !important;}
                .logo-img img{width:22% !important;}
                .pv-print-container{width:100%;}
                .pv-info-table{border:2px solid #cdcdcd !important;border-collapse:collapse;width:100% !important;}
                .pv-info-table th,.pv-info-table td{border:1px solid #000 !important;line-height:1.25;padding:4px 10px !important;}
                .pv-info-table th{font-size:10px;background-color:#f8f9fa !important;font-weight:700;vertical-align:middle;-webkit-print-color-adjust:exact;}
                .pv-info-table td{font-size:10px;vertical-align:top;}
                .section-header th{background-color:#cdcdcd !important;color:#000000 !important;font-size:12px;letter-spacing:1px;}
                .addr p{text-align:right !important;}
                .footer-paragargh{background:#599364 !important;color:#fff !important;padding:8px 0px !important;border-radius:4px !important;text-align:center !important;width:100% !important;}
            </style>
        </head>
        <body>
            <div class="print-container">
                ${divToPrint}
            </div>
        </body>
        </html>
    `);
    newWindow.document.close();
    newWindow.focus();
    newWindow.print();
    newWindow.close();
}
</script>
