<br>
<div class="print-ready-section" id="print-area">
    <div class="rela">
        <div class="text-center mb-3">
            <h4 class="font-weight-bold text-uppercase d-inline-block border-bottom pb-1 px-4 mb-0" style="letter-spacing: 1px;">
                JOB ORDER REQUEST
            </h4>
        </div>

        <div class="job-order-print-container p-0 bg-white">
            {{-- BASIC INFORMATION & COMMODITY / SUMMARY --}}
            <div class="row mb-3">
                <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6">
                    <table class="table table-bordered job-info-table mb-0">
                        <tbody>
                            <tr class="section-header">
                                <th colspan="2" class="text-center py-1">BASIC INFORMATION</th>
                            </tr>
                            <tr>
                                <th width="35%" class="text-uppercase bg-light">Location</th>
                                <td class="font-weight-bold">{{ $request->companyLocation->name ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th class="text-uppercase bg-light">Request No.</th>
                                <td class="font-weight-bold text-primary">{{ $request->request_no }}</td>
                            </tr>
                            <tr>
                                <th class="text-uppercase bg-light">Request Date</th>
                                <td>{{ $request->request_date ? \Carbon\Carbon::parse($request->request_date)->format('d.m.Y') : 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th class="text-uppercase bg-light">Export Order No.</th>
                                <td class="font-weight-bold">{{ $request->exportOrder->voucher_no ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th class="text-uppercase bg-light">Ref No.</th>
                                <td>{{ $request->ref_no ?: '-' }}</td>
                            </tr>
                            <tr>
                                <th class="text-uppercase bg-light">Attention To</th>
                                <td class="font-weight-bold">{{ $users->count() > 0 ? $users->pluck('name')->implode(', ') : '-' }}</td>
                            </tr>
                            @php
                                $decodeIds = function ($v) { while (is_string($v)) { $d = json_decode($v, true); if ($d === null) break; $v = $d; } return array_values(array_filter((array) $v, fn($x) => $x !== null && $x !== '')); };
                                $arrivalLocs = \App\Models\Master\ArrivalLocation::whereIn('id', $decodeIds($request->arrival_locations))->pluck('name')->implode(', ');
                            @endphp
                            <tr>
                                <th class="text-uppercase bg-light">Factory Locations</th>
                                <td>{{ $arrivalLocs ?: '-' }}</td>
                            </tr>
                            <tr>
                                <th class="text-uppercase bg-light">Created By</th>
                                <td>{{ $request->creator->name ?? '-' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6">
                    <table class="table table-bordered job-info-table mb-0">
                        <tbody>
                            <tr class="section-header">
                                <th colspan="2" class="text-center py-1">COMMODITY &amp; SUMMARY</th>
                            </tr>
                            <tr>
                                <th width="35%" class="text-uppercase bg-light">Commodity</th>
                                <td class="font-weight-bold text-uppercase text-primary">{{ $request->product->name ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th class="text-uppercase bg-light">Crop Year</th>
                                <td>{{ $request->cropYear->name ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th class="text-uppercase bg-light">Total Bags</th>
                                <td class="font-weight-bold">{{ number_format($request->total_bags) }}</td>
                            </tr>
                            <tr>
                                <th class="text-uppercase bg-light">Total Quantity</th>
                                <td class="font-weight-bold">{{ number_format($request->total_kgs, 2) }} KG ({{ number_format($request->total_metric_tons, 3) }} MT)</td>
                            </tr>
                            <tr>
                                <th class="text-uppercase bg-light">Consumed / Remaining</th>
                                <td>
                                    {{ number_format($request->consumed_kgs, 2) }} KG /
                                    <span class="font-weight-bold text-success">{{ number_format($request->remaining_kgs, 2) }} KG</span>
                                </td>
                            </tr>
                            <tr>
                                <th class="text-uppercase bg-light">Containers</th>
                                <td>{{ $request->total_containers ?? 0 }}</td>
                            </tr>
                            <tr>
                                <th class="text-uppercase bg-light">Loading Date</th>
                                <td>{{ $request->loading_date ? \Carbon\Carbon::parse($request->loading_date)->format('d.m.Y') : '-' }}</td>
                            </tr>
                            <tr>
                                <th class="text-uppercase bg-light">Status</th>
                                <td>
                                    @php
                                        $statusClass = match(strtolower($request->status ?? 'pending')) {
                                            'converted', 'approved', 'completed' => 'badge-success',
                                            'rejected', 'cancelled' => 'badge-danger',
                                            default => 'badge-warning'
                                        };
                                    @endphp
                                    <span class="badge {{ $statusClass }} text-uppercase">{{ $request->status ?? 'Pending' }}</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- PRODUCT SPECIFICATIONS --}}
            @php
                $specs = $request->specifications->filter(function ($s) {
                    return $s->spec_value !== null && $s->spec_value !== '';
                })->values();
                $hasSpecs = $specs->count() > 0 || !empty($request->other_specifications);
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

                            @if($request->other_specifications)
                            <tr>
                                <th class="text-uppercase bg-light">Other Specifications</th>
                                <td colspan="3" class="text-justify">{!! nl2br(e($request->other_specifications)) !!}</td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            {{-- PACKING DETAILS, MASTER PACKING & CONTAINER PROTECTION --}}
            @include('management.production.partials.view_packing_details', [
                'packingItems' => $request->packingItems,
                'containerItems' => $request->containerProtectionItems,
                'fallbackFumigationType' => $request->fumigation_type,
                'fallbackDosage' => $request->exportOrder->fumigation_dosage ?? null,
            ])

            {{-- OPERATIONAL DETAILS --}}
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
                                <td width="30%">{{ $request->loading_date ? \Carbon\Carbon::parse($request->loading_date)->format('d.m.Y') : '-' }}</td>
                                <th width="20%" class="text-uppercase bg-light">Inspection By</th>
                                <td width="30%">
                                    @php
                                        $inspIds = $decodeIds($request->inspection_company_id);
                                        if (!empty($inspIds) && in_array('null', (array) $inspIds)) {
                                            $insps = 'N/A';
                                        } else {
                                            $insps = !empty($inspIds) ? \App\Models\Master\InspectionCompany::whereIn('id', (array) $inspIds)->pluck('name')->implode(', ') : '-';
                                        }
                                    @endphp
                                    {{ $insps ?: '-' }}
                                </td>
                            </tr>
                            @if($request->packing_description)
                            <tr>
                                <th class="text-uppercase bg-light">Packing Description</th>
                                <td colspan="3" class="text-justify">{!! nl2br(e($request->packing_description)) !!}</td>
                            </tr>
                            @endif
                            @if($request->order_description)
                            <tr>
                                <th class="text-uppercase bg-light">Order Description</th>
                                <td colspan="3" class="text-justify">{!! nl2br(e($request->order_description)) !!}</td>
                            </tr>
                            @endif
                            @if($request->remarks)
                            <tr>
                                <th class="text-uppercase bg-light">Remarks</th>
                                <td colspan="3" class="text-justify">{!! nl2br(e($request->remarks)) !!}</td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .job-info-table { border: 2px solid #cdcdcd !important; border-collapse: collapse; width: 100% !important; }
    .job-info-table th, .job-info-table td { border: 1px solid #000 !important; line-height: 1.3; padding: 5px 10px !important; }
    .job-info-table th { font-size: 11px; background-color: #f8f9fa !important; font-weight: 700; vertical-align: middle; }
    .job-info-table td { font-size: 11px; vertical-align: top; }
    .section-header th { background-color: #cdcdcd !important; color: #000000 !important; font-size: 12px; letter-spacing: 1px; font-weight: bold; }
    .sub-section-header th { background-color: #555 !important; color: #fff !important; font-size: 11px; }
</style>
