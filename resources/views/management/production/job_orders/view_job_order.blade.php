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
            <div class="row" style=" width: 100% !important;align-items: center !important;">
                <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6">
                    <div class="logo">
                        <a class="logo-text" href="https://meskay.inplsoftwares.online">
                            <div class="logo-img">
                                <img class="logo-img" alt="Apex logo" src="/storage/company_logos/meskay-logo-20250514082251.png">
                            </div>
                        </a>
                    </div>
                </div>
                <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6 text-right">
                    <div class="addr">
                        <p class="mb-1"><strong>HEAD OFFICE:</strong></p>
                        <p class="mb-1">10th Floor, Office No. <br> 1008-1013 Salma Trade Tower,<br> Tower-B I-I Chundrigar Road,<br> Karachi-Pakistan</p>
                        <p class="mb-1"><strong>T:</strong> +92-21-32275349-51</p>
                        <p class="mb-1"><strong>F:</strong> +92-21-32275352</p>
                    </div>
                </div>
            </div>
        </div>
        <br>
        <!-- Print Content -->
        <div class="job-order-print-container p-0 bg-white">
            <!-- Consolidated Data Table -->

            <div class="row">
                <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6 ">
                    <table class="table table-bordered job-info-table mb-0">
                        <tbody>
                            {{-- SECTION: BASIC INFORMATION --}}
                            <tr class="section-header">
                                <th colspan="2" class="text-center py-1">BASIC INFORMATION</th>
                            </tr>
                            @if($jobOrder->job_order_no)
                            <tr>
                                <th width="30%" class="text-uppercase bg-light">Job Order No.</th>
                                <td class="font-weight-bold">{{ $jobOrder->job_order_no }}</td>
                            </tr>
                            @endif
                            @if($jobOrder->job_order_date)
                            <tr>
                                <th class="text-uppercase bg-light">Date</th>
                                <td>{{ $jobOrder->job_order_date->format('d.m.Y') }}</td>
                            </tr>
                            @endif
                            @if($jobOrder->ref_no)
                            <tr>
                                <th class="text-uppercase bg-light">Ref No.</th>
                                <td>{{ $jobOrder->ref_no }}</td>
                            </tr>
                            @endif
                            @if($users->count() > 0)
                            <tr>
                                <th class="text-uppercase bg-light">Attention To</th>
                                <td class="font-weight-bold">{{ $users->pluck('name')->implode(', ') }}</td>
                            </tr>
                            @endif
    
                        </tbody>
                    </table> 
                </div>
             
    
                <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6 ">
                    <table class="table table-bordered job-info-table mb-0">
                        <tbody>
                            <tr class="section-header">
                                <th colspan="2" class="text-center py-1">Commodity</th>
                            </tr>
    
                            @if($jobOrder->product)
                            <tr>
                                <td style="text-align: center;"class="text-uppercase font-weight-bold text-primary ">{{ $jobOrder->product->name }}</td>
                            </tr>
                            @endif
                        </tbody>
                    </table> 
                </div>
            </div>
          <br>
            <div class="row">
                <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6 ">
                    <table class="table table-bordered job-info-table mb-0">
                        <tbody>

                            {{-- SECTION: SPECIFICATIONS --}}
                            @php $hasSpecs = $jobOrder->specifications->count() > 0 || !empty($jobOrder->other_specifications); @endphp
                            @if($hasSpecs)
                                <tr class="section-header">
                                    <th colspan="2" class="text-center py-1">SPECIFICATIONS</th>
                                </tr>
                                @foreach($jobOrder->specifications as $spec)
                                    @if($spec->spec_value !== null && $spec->spec_value !== '')
                                    <tr>
                                        <th class="text-uppercase bg-light">{{ $spec->spec_name }}</th>
                                        <td>
                                            @if($spec->value_type == 'min') Min @elseif($spec->value_type == 'max') Max @endif
                                            {{ $spec->spec_value }}{{ $spec->uom }}
                                        </td>
                                    </tr>
                                    @endif
                                @endforeach
                                @if($jobOrder->other_specifications)
                                <tr>
                                    <th class="text-uppercase bg-light">Other Specifications</th>
                                    <td class="text-justify">{!! nl2br(e($jobOrder->other_specifications)) !!}</td>
                                </tr>
                                @endif
                                @if($jobOrder->crop_year_id && $jobOrder->cropYear)
                                <tr>
                                    <th class="text-uppercase bg-light">Crop Year</th>
                                    <td>{{ $jobOrder->cropYear->name }}</td>
                                </tr>
                                @endif
                            @endif
                        </tbody>
                    </table> 
                </div>
                <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6 "></div>

            </div>
        </div> 
        <br>
        <div class="job-order-print-container p-0 bg-white">   
            <table class="table table-bordered job-info-table mb-0">
                <tbody>
                    {{-- SECTION: PACKING DETAILS --}}
                    @if($jobOrder->packingItems && $jobOrder->packingItems->count() > 0)
                        @foreach($jobOrder->packingItems as $index => $item)
                        <tr class="section-header">
                            <th colspan="21" class="text-center py-1">PACKING ITEM #{{ $index + 1 }}</th>
                        </tr>
                        <tr>
                            <th class="text-uppercase bg-light">Location</th>
                            <th class="text-uppercase bg-light">Brand</th>
                            <th class="text-uppercase bg-light">Bag Type</th>
                            <th class="text-uppercase bg-light">Bag Condition</th>
                            <th class="text-uppercase bg-light">Bag Size</th>
                            <th class="text-uppercase bg-light">Bag Color</th>
                            <th class="text-uppercase bg-light">Thread Color</th>
                            <th class="text-uppercase bg-light">Stitching</th>
                            <th class="text-uppercase bg-light">No. of Bags</th>
                            <th class="text-uppercase bg-light">Extra Bags</th>

                            <th class="text-uppercase bg-light">Empty Bags</th>
                            <th class="text-uppercase bg-light">Total Bags</th>
                            <th class="text-uppercase bg-light">Total KGs</th>
                            <th class="text-uppercase bg-light">Net Weight (MT)</th>
                            <th class="text-uppercase bg-light">Stuffing in Cont.</th>
                            <th class="text-uppercase bg-light">No. of Containers</th>
                            <th class="text-uppercase bg-light">Min Bag Wt.</th>
                            <th class="text-uppercase bg-light">Delivery Date</th>
                            <th class="text-uppercase bg-light">Fumigation By</th>
                            <th class="text-uppercase bg-light">Description</th>
                            <th class="text-uppercase bg-light">Location Instr.</th>
                        </tr>
                        <tr>
                            @if($item->companyLocation)
                                <td>{{ $item->companyLocation->name }}</td>
                            @endif
                            @if($item->brand)
                                <td>{{ $item->brand->name }}</td>
                            @endif
                            @if($item->bagProduct)
                                <td>{{ $item->bagProduct->name }}</td>
                            @endif
                            @if($item->bagCondition)
                                <td>{{ $item->bagCondition->name }}</td>
                            @endif
                            @if($item->bag_size)
                                <td>{{ $item->bag_size }} KG</td>
                            @endif
                            @if($item->bagColor)
                                <td>{{ $item->bagColor->color }}</td>
                            @endif
                            @if($item->threadColor)
                                <td>{{ $item->threadColor->color }}</td>
                            @endif
                            @if($item->stitching)
                                <td>{{ $item->stitching->name }}</td>
                            @endif
                            
                            @if($item->no_of_bags > 0)
                                <td>{{ number_format($item->no_of_bags) }}</td>
                            @endif
                            @if($item->extra_bags > 0)
                                <td>{{ number_format($item->extra_bags) }} @if($item->extra_bags_percentage > 0) ({{ $item->extra_bags_percentage }}%) @endif</td>
                            @endif


                            
                            @if($item->empty_bags > 0)
                                <td>{{ number_format($item->empty_bags) }}</td>
                            @endif
                            @if($item->total_bags > 0)
                                <td class="font-weight-bold">{{ number_format($item->total_bags) }}</td>
                            @endif
                            @if($item->total_kgs > 0)
                                <td>{{ number_format($item->total_kgs, 2) }}</td>
                            @endif
                            @if($item->metric_tons > 0)
                                <td class="font-weight-bold text-success">{{ number_format($item->metric_tons, 3) }}</td>
                            @endif
                            @if($item->stuffing_in_container)
                                <td>{{ $item->stuffing_in_container }} MT</td>
                            @endif
                            @if($item->no_of_containers)
                                <td>{{ $item->no_of_containers }}</td>
                            @endif
                            @if($item->min_weight_empty_bags)
                                <td>{{ $item->min_weight_empty_bags }} g</td>
                            @endif
                            @if($item->delivery_date)
                                <td>{{ $item->delivery_date->format('d.m.Y') }}</td>
                            @endif
                            @php
                                $fumigations = \App\Models\Master\FumigationCompany::whereIn('id', is_array($item->fumigation_company_id) ? $item->fumigation_company_id : (json_decode($item->fumigation_company_id, true) ?? []))->pluck('name')->implode(', ');
                            @endphp
                            @if($fumigations)
                                <td>{{ $fumigations }}</td>
                            @endif
                            @if($item->description)
                                <td class="small">{{ $item->description }}</td>
                            @endif
                            @if($item->location_instruction)
                                <td class="small">{{ $item->location_instruction }}</td>
                            @endif
                        </tr>
                </tbody>
            </table>
            <br>
            <table class="table table-bordered job-info-table mb-0">
                <tbody>
                    {{-- SUB-SECTION: MASTER PACKING --}}
                    @if($item->subItems && $item->subItems->count() > 0)
                        @foreach($item->subItems as $subIndex => $sub)
                            <tr class="sub-section-header">
                                <th colspan="9" class="py-0 px-2 small font-italic text-center">MASTER PACKING ({{ $subIndex + 1 }})</th>
                            </tr>
                            <tr>
                                <th class="text-uppercase bg-light small pl-3">MP Bag</th>
                                <th class="text-uppercase bg-light small pl-3">MP Bag Size</th>
                                <th class="text-uppercase bg-light small pl-3">MP Brand</th>
                                <th class="text-uppercase bg-light small pl-3">MP Color</th>
                                <th class="text-uppercase bg-light small pl-3">MP Thread</th>
                                <th class="text-uppercase bg-light small pl-3">MP Stitching</th>
                                <th class="text-uppercase bg-light small pl-3">Primaries/MP</th>
                                <th class="text-uppercase bg-light small pl-3">Total MP Bags</th>
                                <th class="text-uppercase bg-light small pl-3">MP Bag Wt.</th>
                            </tr>
                            <tr>
                                @if($sub->bagProduct)
                                    <td class="small">{{ $sub->bagProduct->name }}</td>
                                @endif
                                @if($sub->bagSize)
                                    <td class="small">{{ $sub->bagSize->size ?? 'N/A' }}</td>
                                @endif
                                @if($sub->brand)
                                    <td class="small">{{ $sub->brand->name }}</td>
                                @endif
                                @if($sub->bagColor)
                                    <td class="small">{{ $sub->bagColor->color }}</td>
                                @endif
                                @if($sub->threadColor)
                                    <td class="small">{{ $sub->threadColor->color }}</td>
                                @endif
                                @if($sub->stitching)
                                    <td class="small">{{ $sub->stitching->name }}</td>
                                @endif
                                @if($sub->no_of_primary_bags)
                                    <td class="small">{{ $sub->no_of_primary_bags }}</td>
                                @endif
                                @if($sub->total_bags)
                                    <td class="small font-weight-bold">{{ number_format($sub->total_bags) }} @if($sub->extra_bags_percentage > 0) ({{ $sub->extra_bags_percentage }}% Extra) @endif</td>
                                @endif
                                @if($sub->empty_bag_weight)
                                <td class="small">{{ $sub->empty_bag_weight }} g</td>
                                @endif
                            </tr>
                        @endforeach
                    @endif
                    @endforeach
                    @endif
                </tbody>
            </table>
            <br>
            <table class="table table-bordered job-info-table mb-0">
                <tbody>
                    {{-- SECTION: OPERATIONAL DETAILS --}}
                    <tr class="section-header">
                        <th colspan="4" class="text-center py-1">OPERATIONAL DETAILS</th>
                    </tr>
                    <tr>
                        <th class="text-uppercase bg-light">Loading Date</th>
                        <th class="text-uppercase bg-light">Inspection By</th>
                        <th class="text-uppercase bg-light">Description</th>
                        <th class="text-uppercase bg-light">Remarks</th>
                    </tr>
                    <tr>
                        @if($jobOrder->loading_date)
                            <td>{{ $jobOrder->loading_date->format('d.m.Y') }}</td>
                        @endif
                        @php 
                            $inspIds = is_string($jobOrder->inspection_company_id) ? json_decode($jobOrder->inspection_company_id, true) : $jobOrder->inspection_company_id;
                            $insps = \App\Models\Master\InspectionCompany::whereIn('id', $inspIds ?? [])->pluck('name')->implode(', ');
                        @endphp
                        @if($insps)
                            <td>{{ $insps }}</td>
                        @endif
                        @if($jobOrder->order_description)
                            <td class="text-justify">{!! nl2br(e($jobOrder->order_description)) !!}</td>
                        @endif
                        @if($jobOrder->remarks)
                            <td class="text-justify">{!! nl2br(e($jobOrder->remarks)) !!}</td>
                        @endif
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <div class="voucher-footer-absou voucher-footer mt-4 pt-3">
      
           
        <div class="footer-paragargh">
            <p>| NAUSHEHRO FEROZE | LARKANA | SHIKARPUR | TANDO ALLAHYAR | PAKPATTAN</p>
        </div>
           
        
    </div>
</div>

<style>
    .job-info-table{border:2px solid #cdcdcd !important;border-collapse:collapse;width:100% !important;}
    .job-info-table th,.job-info-table td{border:1px solid #000 !important;line-height:1.25;padding:4px 10px !important;}
    .job-info-table th{font-size:11px;background-color:#f8f9fa !important;font-weight:700;vertical-align:middle;-webkit-print-color-adjust:exact;}
    .job-info-table td{font-size:12px;vertical-align:top;}
    .section-header th{background-color:#cdcdcd !important;color:#000000 !important;font-size:12px;letter-spacing:1px;}
    .sub-section-header th{background-color:#666 !important;color:#fff !important;font-size:10px;}
    .footer-paragargh{background:#599364 !important;color:#fff !important;padding:10px 0px !important;border-radius:4px !important;text-align:center !important;width:100% !important;}
    .logo-img img{width:22%;}
    @media print{
    .d-print-none{display:none !important;}
    body *{visibility:hidden;}
    #print-area,#print-area *{visibility:visible;}
    #print-area{position:absolute;left:0;top:0;width:100% !important;}
    .bg-light{background-color:#f8f9fa !important;-webkit-print-color-adjust:exact;}
    .section-header th{background-color:#333 !important;color:#fff !important;-webkit-print-color-adjust:exact;}
    .sub-section-header th{background-color:#666 !important;color:#fff !important;-webkit-print-color-adjust:exact;}
    }
</style>

<script>
function printDiv(divId) {
    var divToPrint = document.getElementById(divId).innerHTML;

    var newWindow = window.open('', '', 'width=900,height=650');
    newWindow.document.write(`
        <html>
        <head>
            <title>Print</title>
            <link rel="stylesheet" href="${document.querySelector('link[rel=stylesheet]').href}">
            <style>
                @page{margin:1em !important;}
                body{background:white !important;}
                .row{display:flex !important;flex-wrap:wrap !important;width:100% !important;margin:0 !important;padding:0 !important;}
                [class*="col-"]{flex:0 0 50% !important;/* for col-6 */
                max-width:50% !important;box-sizing:border-box;padding:0 10px !important;}
                .logo-img img{width:22% !important;}
                .print-container{width:100%;}
                .job-info-table{border:2px solid #cdcdcd !important;border-collapse:collapse;width:100% !important;}
                .job-info-table th,.job-info-table td{border:1px solid #000 !important;line-height:1.25;padding:4px 10px !important;}
                .job-info-table th{font-size:10px;background-color:#f8f9fa !important;font-weight:700;vertical-align:middle;-webkit-print-color-adjust:exact;}
                .job-info-table td{font-size:10px;vertical-align:top;}
                .section-header th{background-color:#cdcdcd !important;color:#000000 !important;font-size:12px;letter-spacing:1px;}
                .sub-section-header th{background-color:#666 !important;color:#fff !important;font-size:10px;}
                .addr p{text-align:right !important;}
                .rela{position:relative !important;}
                .voucher-footer-absou{position:absolute !important;bottom:0 !important;left:0 !important; border-top:none !important;width:100% !important;}
                .footer-paragargh{background:#599364 !important;color:#fff !important;padding:10px 0px !important;border-radius:4px !important;text-align:center !important;width:100% !important;}
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



