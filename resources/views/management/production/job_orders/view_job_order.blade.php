<div class="print-ready-section" id="print-area">
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

    <!-- Print Content -->
    <div class="job-order-print-container p-0 bg-white" style="width: 100%; margin-left: 0;">
        <!-- Consolidated Data Table -->
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
                @if($jobOrder->product)
                <tr>
                    <th class="text-uppercase bg-light">Commodity</th>
                    <td class="text-uppercase font-weight-bold text-primary">{{ $jobOrder->product->name }}</td>
                </tr>
                @endif
                @if($jobOrder->crop_year_id && $jobOrder->cropYear)
                <tr>
                    <th class="text-uppercase bg-light">Crop Year</th>
                    <td>{{ $jobOrder->cropYear->name }}</td>
                </tr>
                @endif

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
                @endif

                {{-- SECTION: PACKING DETAILS --}}
                @if($jobOrder->packingItems && $jobOrder->packingItems->count() > 0)
                    @foreach($jobOrder->packingItems as $index => $item)
                        <tr class="section-header">
                            <th colspan="2" class="text-center py-1">PACKING ITEM #{{ $index + 1 }}</th>
                        </tr>
                        @if($item->companyLocation)
                        <tr>
                            <th class="text-uppercase bg-light">Location</th>
                            <td>{{ $item->companyLocation->name }}</td>
                        </tr>
                        @endif
                        @if($item->brand)
                        <tr>
                            <th class="text-uppercase bg-light">Brand</th>
                            <td>{{ $item->brand->name }}</td>
                        </tr>
                        @endif
                        @if($item->bagProduct)
                        <tr>
                            <th class="text-uppercase bg-light">Bag Type</th>
                            <td>{{ $item->bagProduct->name }}</td>
                        </tr>
                        @endif
                        @if($item->bagCondition)
                        <tr>
                            <th class="text-uppercase bg-light">Bag Condition</th>
                            <td>{{ $item->bagCondition->name }}</td>
                        </tr>
                        @endif
                        @if($item->bag_size)
                        <tr>
                            <th class="text-uppercase bg-light">Bag Size</th>
                            <td>{{ $item->bag_size }} KG</td>
                        </tr>
                        @endif
                        @if($item->bagColor)
                        <tr>
                            <th class="text-uppercase bg-light">Bag Color</th>
                            <td>{{ $item->bagColor->color }}</td>
                        </tr>
                        @endif
                        @if($item->threadColor)
                        <tr>
                            <th class="text-uppercase bg-light">Thread Color</th>
                            <td>{{ $item->threadColor->color }}</td>
                        </tr>
                        @endif
                        @if($item->stitching)
                        <tr>
                            <th class="text-uppercase bg-light">Stitching</th>
                            <td>{{ $item->stitching->name }}</td>
                        </tr>
                        @endif
                        
                        @if($item->no_of_bags > 0)
                        <tr>
                            <th class="text-uppercase bg-light">No. of Bags</th>
                            <td>{{ number_format($item->no_of_bags) }}</td>
                        </tr>
                        @endif
                        @if($item->extra_bags > 0)
                        <tr>
                            <th class="text-uppercase bg-light">Extra Bags</th>
                            <td>{{ number_format($item->extra_bags) }}</td>
                        </tr>
                        @endif
                        @if($item->empty_bags > 0)
                        <tr>
                            <th class="text-uppercase bg-light">Empty Bags</th>
                            <td>{{ number_format($item->empty_bags) }}</td>
                        </tr>
                        @endif
                        @if($item->total_bags > 0)
                        <tr>
                            <th class="text-uppercase bg-light">Total Bags</th>
                            <td class="font-weight-bold">{{ number_format($item->total_bags) }}</td>
                        </tr>
                        @endif
                        @if($item->total_kgs > 0)
                        <tr>
                            <th class="text-uppercase bg-light">Total KGs</th>
                            <td>{{ number_format($item->total_kgs, 2) }}</td>
                        </tr>
                        @endif
                        @if($item->metric_tons > 0)
                        <tr>
                            <th class="text-uppercase bg-light">Net Weight (MT)</th>
                            <td class="font-weight-bold text-success">{{ number_format($item->metric_tons, 3) }}</td>
                        </tr>
                        @endif
                        @if($item->stuffing_in_container)
                        <tr>
                            <th class="text-uppercase bg-light">Stuffing in Cont.</th>
                            <td>{{ $item->stuffing_in_container }} MT</td>
                        </tr>
                        @endif
                        @if($item->no_of_containers)
                        <tr>
                            <th class="text-uppercase bg-light">No. of Containers</th>
                            <td>{{ $item->no_of_containers }}</td>
                        </tr>
                        @endif
                        @if($item->min_weight_empty_bags)
                        <tr>
                            <th class="text-uppercase bg-light">Min Bag Wt.</th>
                            <td>{{ $item->min_weight_empty_bags }} g</td>
                        </tr>
                        @endif
                        @if($item->delivery_date)
                        <tr>
                            <th class="text-uppercase bg-light">Delivery Date</th>
                            <td>{{ $item->delivery_date->format('d.m.Y') }}</td>
                        </tr>
                        @endif

                        @php
                            $fumigations = \App\Models\Master\FumigationCompany::whereIn('id', is_array($item->fumigation_company_id) ? $item->fumigation_company_id : (json_decode($item->fumigation_company_id, true) ?? []))->pluck('name')->implode(', ');
                        @endphp
                        @if($fumigations)
                        <tr>
                            <th class="text-uppercase bg-light">Fumigation By</th>
                            <td>{{ $fumigations }}</td>
                        </tr>
                        @endif

                        @if($item->description)
                        <tr>
                            <th class="text-uppercase bg-light">Description</th>
                            <td class="small">{{ $item->description }}</td>
                        </tr>
                        @endif
                        @if($item->location_instruction)
                        <tr>
                            <th class="text-uppercase bg-light">Location Instr.</th>
                            <td class="small">{{ $item->location_instruction }}</td>
                        </tr>
                        @endif

                        {{-- SUB-SECTION: MASTER PACKING --}}
                        @if($item->subItems && $item->subItems->count() > 0)
                            @foreach($item->subItems as $subIndex => $sub)
                                <tr class="sub-section-header">
                                    <th colspan="2" class="py-0 px-2 small font-italic text-center">MASTER PACKING ({{ $subIndex + 1 }})</th>
                                </tr>
                                @if($sub->bagProduct)
                                <tr>
                                    <th class="text-uppercase bg-light small pl-3">MP Bag</th>
                                    <td class="small">{{ $sub->bagProduct->name }}</td>
                                </tr>
                                @endif
                                @if($sub->bagSize)
                                <tr>
                                    <th class="text-uppercase bg-light small pl-3">MP Bag Size</th>
                                    <td class="small">{{ $sub->bagSize->size ?? 'N/A' }}</td>
                                </tr>
                                @endif
                                @if($sub->brand)
                                <tr>
                                    <th class="text-uppercase bg-light small pl-3">MP Brand</th>
                                    <td class="small">{{ $sub->brand->name }}</td>
                                </tr>
                                @endif
                                @if($sub->bagColor)
                                <tr>
                                    <th class="text-uppercase bg-light small pl-3">MP Color</th>
                                    <td class="small">{{ $sub->bagColor->color }}</td>
                                </tr>
                                @endif
                                @if($sub->threadColor)
                                <tr>
                                    <th class="text-uppercase bg-light small pl-3">MP Thread</th>
                                    <td class="small">{{ $sub->threadColor->color }}</td>
                                </tr>
                                @endif
                                @if($sub->stitching)
                                <tr>
                                    <th class="text-uppercase bg-light small pl-3">MP Stitching</th>
                                    <td class="small">{{ $sub->stitching->name }}</td>
                                </tr>
                                @endif
                                @if($sub->no_of_primary_bags)
                                <tr>
                                    <th class="text-uppercase bg-light small pl-3">Primaries/MP</th>
                                    <td class="small">{{ $sub->no_of_primary_bags }}</td>
                                </tr>
                                @endif
                                @if($sub->total_bags)
                                <tr>
                                    <th class="text-uppercase bg-light small pl-3">Total MP Bags</th>
                                    <td class="small font-weight-bold">{{ number_format($sub->total_bags) }}</td>
                                </tr>
                                @endif
                                @if($sub->empty_bag_weight)
                                <tr>
                                    <th class="text-uppercase bg-light small pl-3">MP Bag Wt.</th>
                                    <td class="small">{{ $sub->empty_bag_weight }} g</td>
                                                </tr>
                                @endif
                            @endforeach
                        @endif
                    @endforeach
                @endif

                {{-- SECTION: OPERATIONAL DETAILS --}}
                <tr class="section-header">
                    <th colspan="2" class="text-center py-1">OPERATIONAL DETAILS</th>
                </tr>
                @if($jobOrder->loading_date)
                <tr>
                    <th class="text-uppercase bg-light">Loading Date</th>
                    <td>{{ $jobOrder->loading_date->format('d.m.Y') }}</td>
                </tr>
                @endif
                @php 
                    $inspIds = is_string($jobOrder->inspection_company_id) ? json_decode($jobOrder->inspection_company_id, true) : $jobOrder->inspection_company_id;
                    $insps = \App\Models\Master\InspectionCompany::whereIn('id', $inspIds ?? [])->pluck('name')->implode(', ');
                @endphp
                @if($insps)
                <tr>
                    <th class="text-uppercase bg-light">Inspection By</th>
                    <td>{{ $insps }}</td>
                </tr>
                @endif

                @if($jobOrder->order_description)
                <tr>
                    <th class="text-uppercase bg-light">Description</th>
                    <td class="text-justify">{!! nl2br(e($jobOrder->order_description)) !!}</td>
                </tr>
                @endif

                @if($jobOrder->remarks)
                <tr>
                    <th class="text-uppercase bg-light">Remarks</th>
                    <td class="text-justify">{!! nl2br(e($jobOrder->remarks)) !!}</td>
                </tr>
                @endif
            </tbody>
        </table>
    </div>
</div>

<style>
    .job-info-table {
        border: 2px solid #000 !important;
        border-collapse: collapse;
        width: 100% !important;
    }
    .job-info-table th, .job-info-table td {
        border: 1px solid #000 !important;
        line-height: 1.25;
        padding: 4px 10px !important;
    }
    .job-info-table th {
        font-size: 11px;
        background-color: #f8f9fa !important;
        font-weight: 700;
        vertical-align: middle;
        -webkit-print-color-adjust: exact;
    }
    .job-info-table td {
        font-size: 12px;
        vertical-align: top;
    }
    .section-header th {
        background-color: #333 !important;
        color: #fff !important;
        font-size: 12px;
        letter-spacing: 1px;
    }
    .sub-section-header th {
        background-color: #666 !important;
        color: #fff !important;
        font-size: 10px;
    }
    
    @media print {
        .d-print-none { display: none !important; }
        body * { visibility: hidden; }
        #print-area, #print-area * { visibility: visible; }
        #print-area { position: absolute; left: 0; top: 0; width: 100% !important; }
        .bg-light { background-color: #f8f9fa !important; -webkit-print-color-adjust: exact; }
        .section-header th { background-color: #333 !important; color: #fff !important; -webkit-print-color-adjust: exact; }
        .sub-section-header th { background-color: #666 !important; color: #fff !important; -webkit-print-color-adjust: exact; }
    }
</style>

<script>
    function printDiv(divId) {
        var printContents = document.getElementById(divId).innerHTML;
        var originalContents = document.body.innerHTML;
        document.body.innerHTML = printContents;
        window.print();
        location.reload(); 
    }
</script>
