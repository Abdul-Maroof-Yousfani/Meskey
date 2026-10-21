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

<div class="print-ready-section" id="print-area">
    <div class="rela">
        {{-- Header Section --}}
        <div class="header mb-3">
            <div class="row" style="width: 100% !important; align-items: center !important;">
                <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6">
                    <div class="logo">
                        <a class="logo-text" href="https://meskay.inplsoftwares.online">
                            <div class="logo-img">
                                <img class="logo-img" alt="Meskay Logo" src="{{ asset('management/app-assets/img/meskay-logo.png') }}" style="max-height: 70px;">
                            </div>
                        </a>
                    </div>
                </div>
                <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6 text-right">
                    <div class="addr">
                        <p class="mb-1 font-weight-bold">HEAD OFFICE:</p>
                        <p class="mb-1">10th Floor, Office No. 1008-1013 Saima Trade Tower,<br> Tower-B I-I Chundrigar Road, Karachi-Pakistan</p>
                        <p class="mb-1"><strong>T:</strong> +92-21-32275349-51 &nbsp;|&nbsp; <strong>F:</strong> +92-21-32275352</p>
                    </div>
                </div>
            </div>
            <div class="text-center mt-2 mb-1">
                <h4 class="font-weight-bold text-uppercase d-inline-block border-bottom pb-1 px-4 mb-0" style="letter-spacing: 1px;">
                    JOB ORDER
                </h4>
            </div>
        </div>

        <!-- Print Content Container -->
        <div class="job-order-print-container p-0 bg-white">
            {{-- ROW 1: BASIC INFORMATION & COMMODITY / SUMMARY --}}
            <div class="row mb-3">
                {{-- Left Column: Basic Information --}}
                <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6">
                    <table class="table table-bordered job-info-table mb-0">
                        <tbody>
                            <tr class="section-header">
                                <th colspan="2" class="text-center py-1">BASIC INFORMATION</th>
                            </tr>
                            <tr>
                                <th width="35%" class="text-uppercase bg-light">Location</th>
                                <td class="font-weight-bold">{{ $jobOrder->companyLocation->name ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th class="text-uppercase bg-light">Job Order No.</th>
                                <td class="font-weight-bold text-primary">{{ $jobOrder->job_order_no }}</td>
                            </tr>
                            <tr>
                                <th class="text-uppercase bg-light">Job Order Date</th>
                                <td>{{ $jobOrder->job_order_date ? $jobOrder->job_order_date->format('d.m.Y') : 'N/A' }}</td>
                            </tr>
                            @if($jobOrder->export_order_id && $jobOrder->exportOrder)
                            <tr>
                                <th class="text-uppercase bg-light">Export Order No.</th>
                                <td class="font-weight-bold">{{ $jobOrder->exportOrder->voucher_no }}</td>
                            </tr>
                            @endif
                            @if($jobOrder->job_order_request_id && $jobOrder->jobOrderRequest)
                            <tr>
                                <th class="text-uppercase bg-light">Request No.</th>
                                <td class="font-weight-bold">{{ $jobOrder->jobOrderRequest->request_no }}</td>
                            </tr>
                            @endif
                            <tr>
                                <th class="text-uppercase bg-light">Ref No.</th>
                                <td>{{ $jobOrder->ref_no ?: '-' }}</td>
                            </tr>
                            <tr>
                                <th class="text-uppercase bg-light">Attention To</th>
                                <td class="font-weight-bold">{{ $users->count() > 0 ? $users->pluck('name')->implode(', ') : '-' }}</td>
                            </tr>
                            @php
                                $arrivalLocs = $jobOrder->arrivalLocationRecords()->pluck('name')->implode(', ');
                            @endphp
                            @if($arrivalLocs)
                            <tr>
                                <th class="text-uppercase bg-light">Arrival Locations</th>
                                <td>{{ $arrivalLocs }}</td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>

                {{-- Right Column: Commodity & Order Totals --}}
                <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6">
                    <table class="table table-bordered job-info-table mb-0">
                        <tbody>
                            <tr class="section-header">
                                <th colspan="2" class="text-center py-1">COMMODITY &amp; SUMMARY</th>
                            </tr>
                            <tr>
                                <th width="35%" class="text-uppercase bg-light">Commodity</th>
                                <td class="font-weight-bold text-uppercase text-primary">{{ $jobOrder->product->name ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th class="text-uppercase bg-light">Crop Year</th>
                                <td>{{ $jobOrder->cropYear->name ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th class="text-uppercase bg-light">Total Bags</th>
                                <td class="font-weight-bold">{{ number_format($jobOrder->total_bags) }}</td>
                            </tr>
                            <tr>
                                <th class="text-uppercase bg-light">Total Quantity</th>
                                <td class="font-weight-bold">{{ number_format($jobOrder->total_kgs, 2) }} KG</td>
                            </tr>
                            <tr>
                                <th class="text-uppercase bg-light">Net Weight (MT)</th>
                                <td class="font-weight-bold text-success">{{ number_format($jobOrder->total_metric_tons, 3) }} MT</td>
                            </tr>
                            <tr>
                                <th class="text-uppercase bg-light">Containers</th>
                                <td>{{ $jobOrder->total_containers ?? 0 }}</td>
                            </tr>
                            <tr>
                                <th class="text-uppercase bg-light">Loading Date</th>
                                <td>{{ $jobOrder->loading_date ? $jobOrder->loading_date->format('d.m.Y') : '-' }}</td>
                            </tr>
                            <tr>
                                <th class="text-uppercase bg-light">Approval Status</th>
                                <td>
                                    @php
                                        $statusClass = match(strtolower($jobOrder->am_approval_status ?? 'pending')) {
                                            'approved' => 'badge-success',
                                            'rejected' => 'badge-danger',
                                            'reverted' => 'badge-info',
                                            default => 'badge-warning'
                                        };
                                    @endphp
                                    <span class="badge {{ $statusClass }} text-uppercase">{{ $jobOrder->am_approval_status ?? 'Pending' }}</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- ROW 2: SPECIFICATIONS (FULL WIDTH 4-COLUMN BALANCED) --}}
            @php
                $specs = $jobOrder->specifications->filter(function($s) {
                    return $s->spec_value !== null && $s->spec_value !== '';
                })->values();
                $hasSpecs = $specs->count() > 0 || !empty($jobOrder->other_specifications);
            @endphp

            @if($hasSpecs)
            <div class="row mb-3">
                <div class="col-12">
                    <table class="table table-bordered job-info-table mb-0">
                        <thead>
                            <tr class="section-header">
                                <th colspan="4" class="text-center py-1">PRODUCT SPECIFICATIONS</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if($specs->count() > 0)
                                @for($i = 0; $i < ceil($specs->count() / 2); $i++)
                                    @php
                                        $s1 = $specs->get($i * 2);
                                        $s2 = $specs->get($i * 2 + 1);
                                    @endphp
                                    <tr>
                                        <th width="20%" class="text-uppercase bg-light">{{ $s1->spec_name }}</th>
                                        <td width="30%">
                                            @if($s1->value_type == 'min') Min @elseif($s1->value_type == 'max') Max @endif
                                            {{ $s1->spec_value }} {{ $s1->uom }}
                                        </td>
                                        @if($s2)
                                            <th width="20%" class="text-uppercase bg-light">{{ $s2->spec_name }}</th>
                                            <td width="30%">
                                                @if($s2->value_type == 'min') Min @elseif($s2->value_type == 'max') Max @endif
                                                {{ $s2->spec_value }} {{ $s2->uom }}
                                            </td>
                                        @else
                                            <th width="20%" class="bg-light"></th>
                                            <td width="30%"></td>
                                        @endif
                                    </tr>
                                @endfor
                            @endif

                            @if($jobOrder->other_specifications)
                            <tr>
                                <th class="text-uppercase bg-light">Other Specifications</th>
                                <td colspan="3" class="text-justify">{!! nl2br(e($jobOrder->other_specifications)) !!}</td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            {{-- ROW 3: PACKING DETAILS & MASTER PACKING --}}
            @if($jobOrder->packingItems && $jobOrder->packingItems->count() > 0)
                @foreach($jobOrder->packingItems as $index => $item)
                <div class="packing-item-block mb-3">
                    <table class="table table-bordered job-info-table mb-0">
                        <thead>
                            <tr class="section-header">
                                <th colspan="8" class="text-center py-1">
                                    PACKING ITEM #{{ $index + 1 }}
                                    @if($item->companyLocation)
                                        - <span class="text-dark">{{ $item->companyLocation->name }}</span>
                                    @endif
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            {{-- Row 1: Primary Material Info --}}
                            <tr>
                                <th width="12%" class="text-uppercase bg-light">Brand</th>
                                <td width="13%" class="font-weight-bold">{{ $item->brand->name ?? '-' }}</td>
                                <th width="12%" class="text-uppercase bg-light">Bag Product</th>
                                <td width="13%">{{ $item->bagProduct->name ?? '-' }}</td>
                                <th width="12%" class="text-uppercase bg-light">Bag Condition</th>
                                <td width="13%">{{ $item->bagCondition->name ?? '-' }}</td>
                                <th width="12%" class="text-uppercase bg-light">Bag Size</th>
                                <td width="13%">{{ $item->bag_size ? number_format($item->bag_size, 2) . ' KG' : '-' }}</td>
                            </tr>
                            {{-- Row 2: Color, Stitching, Specs --}}
                            <tr>
                                <th class="text-uppercase bg-light">Bag Color</th>
                                <td>{{ $item->bagColor->color ?? '-' }}</td>
                                <th class="text-uppercase bg-light">Thread Color</th>
                                <td>{{ $item->threadColor->color ?? '-' }}</td>
                                <th class="text-uppercase bg-light">Stitching</th>
                                <td>{{ $item->stitching->name ?? '-' }}</td>
                                <th class="text-uppercase bg-light">Min Bag Wt.</th>
                                <td>{{ $item->min_weight_empty_bags ? $item->min_weight_empty_bags . ' g' : '-' }}</td>
                            </tr>
                            {{-- Row 3: Quantities --}}
                            <tr>
                                <th class="text-uppercase bg-light">No. of Bags</th>
                                <td class="font-weight-bold">{{ $item->no_of_bags ? number_format($item->no_of_bags) : '0' }}</td>
                                <th class="text-uppercase bg-light">Extra Bags</th>
                                <td>
                                    {{ $item->extra_bags ? number_format($item->extra_bags) : '0' }}
                                    @if(($item->extra_bags_percentage ?? 0) > 0)
                                        ({{ $item->extra_bags_percentage }}%)
                                    @endif
                                </td>
                                <th class="text-uppercase bg-light">Empty Bags</th>
                                <td>{{ $item->empty_bags ? number_format($item->empty_bags) : '0' }}</td>
                                <th class="text-uppercase bg-light">Total Bags</th>
                                <td class="font-weight-bold text-primary">{{ $item->total_bags ? number_format($item->total_bags) : '0' }}</td>
                            </tr>
                            {{-- Row 4: Weights & Shipping --}}
                            <tr>
                                <th class="text-uppercase bg-light">Total KGs</th>
                                <td class="font-weight-bold">{{ $item->total_kgs ? number_format($item->total_kgs, 2) : '0.00' }}</td>
                                <th class="text-uppercase bg-light">Net Wt. (MT)</th>
                                <td class="font-weight-bold text-success">{{ $item->metric_tons ? number_format($item->metric_tons, 3) : '0.000' }}</td>
                                <th class="text-uppercase bg-light">Stuffing/Cont.</th>
                                <td>{{ $item->stuffing_in_container ? $item->stuffing_in_container . ' MT' : '-' }}</td>
                                <th class="text-uppercase bg-light">Containers</th>
                                <td>{{ $item->no_of_containers ?? '0' }}</td>
                            </tr>
                            {{-- Row 5: Logistics, Dates, Attachment --}}
                            <tr>
                                <th class="text-uppercase bg-light">Delivery Date</th>
                                <td>{{ $item->delivery_date ? $item->delivery_date->format('d.m.Y') : '-' }}</td>
                                <th class="text-uppercase bg-light">Fumigation By</th>
                                @php
                                    $fumiIds = is_array($item->fumigation_company_id) ? $item->fumigation_company_id : (json_decode($item->fumigation_company_id, true) ?? []);
                                    $fumis = !empty($fumiIds) ? \App\Models\Master\FumigationCompany::whereIn('id', $fumiIds)->pluck('name')->implode(', ') : '-';
                                @endphp
                                <td colspan="3">{{ $fumis }}</td>
                                <th class="text-uppercase bg-light">Attachment</th>
                                <td>
                                    @if($item->attachment)
                                        <a href="{{ asset('storage/' . $item->attachment) }}" target="_blank" class="btn btn-sm btn-outline-info py-0 px-1">
                                            <i class="ft-file"></i> View
                                        </a>
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                            @if($item->description || $item->location_instruction)
                            <tr>
                                @if($item->description)
                                    <th class="text-uppercase bg-light">Description</th>
                                    <td colspan="{{ $item->location_instruction ? 3 : 7 }}" class="small">{!! nl2br(e($item->description)) !!}</td>
                                @endif
                                @if($item->location_instruction)
                                    <th class="text-uppercase bg-light">Location Instr.</th>
                                    <td colspan="{{ $item->description ? 3 : 7 }}" class="small">{!! nl2br(e($item->location_instruction)) !!}</td>
                                @endif
                            </tr>
                            @endif
                        </tbody>
                    </table>

                    {{-- SUB-SECTION: MASTER PACKING --}}
                    @if($item->subItems && $item->subItems->count() > 0)
                    <table class="table table-bordered job-info-table mt-1 mb-0">
                        <thead>
                            <tr class="sub-section-header">
                                <th colspan="12" class="py-1 px-2 small font-weight-bold text-center">
                                    MASTER PACKING FOR ITEM #{{ $index + 1 }}
                                </th>
                            </tr>
                            <tr>
                                <th class="bg-light small">MP Bag</th>
                                <th class="bg-light small">Bag Size</th>
                                <th class="bg-light small">Brand</th>
                                <th class="bg-light small">Color / Thread</th>
                                <th class="bg-light small">Stitching</th>
                                <th class="bg-light small text-center">Primaries/MP</th>
                                <th class="bg-light small text-center">Pack Size (kg)</th>
                                <th class="bg-light small text-center">No. of Bags</th>
                                <th class="bg-light small text-center">Extra (Qty/%)</th>
                                <th class="bg-light small text-center">Total Bags</th>
                                <th class="bg-light small text-center">Bag Wt.</th>
                                <th class="bg-light small text-center">Attachment</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($item->subItems as $subIndex => $sub)
                                <tr>
                                    <td class="small">{{ $sub->bagType->name ?? $sub->bagProduct->name ?? '-' }}</td>
                                    <td class="small">{{ $sub->bagSize->size ?? '-' }}</td>
                                    <td class="small">{{ $sub->brand->name ?? '-' }}</td>
                                    <td class="small">
                                        {{ $sub->bagColor->color ?? '-' }}
                                        @if($sub->threadColor) / {{ $sub->threadColor->color }} @endif
                                    </td>
                                    <td class="small">{{ $sub->stitching->name ?? '-' }}</td>
                                    <td class="small text-center">{{ $sub->no_of_primary_bags ?? '-' }}</td>
                                    <td class="small text-center">
                                        {{ $sub->packing_size ?: (($item->bag_size ?? 0) * ($sub->no_of_primary_bags ?? 0)) }}
                                    </td>
                                    <td class="small text-center">{{ $sub->no_of_bags ? number_format($sub->no_of_bags) : '-' }}</td>
                                    <td class="small text-center">
                                        {{ $sub->extra_bags ? number_format($sub->extra_bags) : '0' }}
                                        @if(($sub->extra_bags_percentage ?? 0) > 0)
                                            ({{ $sub->extra_bags_percentage }}%)
                                        @endif
                                    </td>
                                    <td class="small text-center font-weight-bold">{{ $sub->total_bags ? number_format($sub->total_bags) : '-' }}</td>
                                    <td class="small text-center">{{ $sub->empty_bag_weight ? $sub->empty_bag_weight . ' g' : '-' }}</td>
                                    <td class="small text-center">
                                        @if($sub->attachment)
                                            <a href="{{ asset('storage/' . $sub->attachment) }}" target="_blank" class="btn btn-sm btn-outline-info py-0 px-1">
                                                <i class="ft-file"></i> View
                                            </a>
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @endif
                </div>
                @endforeach
            @endif

            {{-- ROW 4: OPERATIONAL DETAILS --}}
            <div class="row mb-3">
                <div class="col-12">
                    <table class="table table-bordered job-info-table mb-0">
                        <thead>
                            <tr class="section-header">
                                <th colspan="4" class="text-center py-1">OPERATIONAL DETAILS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <th width="20%" class="text-uppercase bg-light">Loading Date</th>
                                <td width="30%">{{ $jobOrder->loading_date ? $jobOrder->loading_date->format('d.m.Y') : '-' }}</td>
                                <th width="20%" class="text-uppercase bg-light">Inspection By</th>
                                <td width="30%">
                                    @php 
                                        $inspIds = is_string($jobOrder->inspection_company_id) ? json_decode($jobOrder->inspection_company_id, true) : $jobOrder->inspection_company_id;
                                        $insps = !empty($inspIds) ? \App\Models\Master\InspectionCompany::whereIn('id', $inspIds)->pluck('name')->implode(', ') : '-';
                                    @endphp
                                    {{ $insps }}
                                </td>
                            </tr>
                            @if($jobOrder->packing_description)
                            <tr>
                                <th class="text-uppercase bg-light">Packing Description</th>
                                <td colspan="3" class="text-justify">{!! nl2br(e($jobOrder->packing_description)) !!}</td>
                            </tr>
                            @endif
                            @if($jobOrder->order_description)
                            <tr>
                                <th class="text-uppercase bg-light">Order Description</th>
                                <td colspan="3" class="text-justify">{!! nl2br(e($jobOrder->order_description)) !!}</td>
                            </tr>
                            @endif
                            @if($jobOrder->remarks)
                            <tr>
                                <th class="text-uppercase bg-light">Remarks</th>
                                <td colspan="3" class="text-justify">{!! nl2br(e($jobOrder->remarks)) !!}</td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Footer Section --}}
    <div class="voucher-footer-absou voucher-footer mt-4 pt-3">
        <div class="footer-paragargh">
            <p class="mb-0">| NAUSHEHRO FEROZE | LARKANA | SHIKARPUR | TANDO ALLAHYAR | PAKPATTAN</p>
        </div>
    </div>
</div>

{{-- Approval Component Section --}}
@php
    $joModule = $jobOrder->getApprovalModule();
    $joApprovalLogs = $joModule ? \App\Models\ApprovalsModule\ApprovalLog::where('record_id', $jobOrder->id)->where('module_id', $joModule->id)->with(['user', 'role'])->orderBy('created_at', 'desc')->get() : collect();
@endphp

<div class="approval-view-wrapper mt-3">
    <div class="row">
        <div class="col-12">
            <x-approval-status :model="$jobOrder" :list-refresh="route('get.job_orders')"/>
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
    .job-info-table { border: 2px solid #cdcdcd !important; border-collapse: collapse; width: 100% !important; }
    .job-info-table th, .job-info-table td { border: 1px solid #000 !important; line-height: 1.3; padding: 5px 10px !important; }
    .job-info-table th { font-size: 11px; background-color: #f8f9fa !important; font-weight: 700; vertical-align: middle; -webkit-print-color-adjust: exact; }
    .job-info-table td { font-size: 11px; vertical-align: top; }
    .section-header th { background-color: #cdcdcd !important; color: #000000 !important; font-size: 12px; letter-spacing: 1px; font-weight: bold; }
    .sub-section-header th { background-color: #555 !important; color: #fff !important; font-size: 11px; -webkit-print-color-adjust: exact; }
    .footer-paragargh { background: #599364 !important; color: #fff !important; padding: 10px 0px !important; border-radius: 4px !important; text-align: center !important; width: 100% !important; -webkit-print-color-adjust: exact; }
    .logo-img img { width: 22%; }
    @media print {
        .d-print-none { display: none !important; }
        body * { visibility: hidden; }
        #print-area, #print-area * { visibility: visible; }
        #print-area { position: absolute; left: 0; top: 0; width: 100% !important; }
        .bg-light { background-color: #f8f9fa !important; -webkit-print-color-adjust: exact; }
        .section-header th { background-color: #333 !important; color: #fff !important; -webkit-print-color-adjust: exact; }
        .sub-section-header th { background-color: #555 !important; color: #fff !important; -webkit-print-color-adjust: exact; }
        .footer-paragargh { background: #599364 !important; color: #fff !important; -webkit-print-color-adjust: exact; }
    }
</style>

<script>
function printDiv(divId) {
    var divToPrint = document.getElementById(divId).innerHTML;

    var newWindow = window.open('', '', 'width=950,height=700');
    newWindow.document.write(`
        <html>
        <head>
            <base href="{{ url('/') }}/">
            <title>Job Order - {{ $jobOrder->job_order_no }}</title>
            <link rel="stylesheet" href="{{ asset('management/app-assets/css/bootstrap.css') }}">
            <link rel="stylesheet" href="${document.querySelector('link[rel=stylesheet]') ? document.querySelector('link[rel=stylesheet]').href : ''}">
            <style>
                @page { margin: 10mm !important; size: auto; }
                body { background: white !important; font-family: Arial, sans-serif; }
                .row { display: flex !important; flex-wrap: wrap !important; width: 100% !important; margin: 0 !important; padding: 0 !important; }
                .col-lg-6, .col-md-6, .col-sm-6, .col-xs-6, .col-6 { flex: 0 0 50% !important; max-width: 50% !important; box-sizing: border-box; padding: 0 8px !important; }
                .col-12, .col-lg-12, .col-md-12, .col-sm-12 { flex: 0 0 100% !important; max-width: 100% !important; box-sizing: border-box; padding: 0 8px !important; }
                .logo-img img { width: 22% !important; max-height: 60px !important; }
                .print-container { width: 100%; }
                .job-info-table { border: 2px solid #cdcdcd !important; border-collapse: collapse; width: 100% !important; }
                .job-info-table th, .job-info-table td { border: 1px solid #000 !important; line-height: 1.25; padding: 4px 8px !important; }
                .job-info-table th { font-size: 10px; background-color: #f8f9fa !important; font-weight: 700; vertical-align: middle; -webkit-print-color-adjust: exact; }
                .job-info-table td { font-size: 10px; vertical-align: top; }
                .section-header th { background-color: #cdcdcd !important; color: #000000 !important; font-size: 11px; letter-spacing: 1px; -webkit-print-color-adjust: exact; }
                .sub-section-header th { background-color: #555 !important; color: #fff !important; font-size: 10px; -webkit-print-color-adjust: exact; }
                .addr p { text-align: right !important; font-size: 10px; }
                .footer-paragargh { background: #599364 !important; color: #fff !important; padding: 8px 0px !important; border-radius: 4px !important; text-align: center !important; width: 100% !important; -webkit-print-color-adjust: exact; font-size: 10px; }
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
    setTimeout(function() {
        newWindow.print();
        newWindow.close();
    }, 250);
}
</script>
