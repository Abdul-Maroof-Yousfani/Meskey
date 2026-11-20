{{--
    Packing items, master packing and container protection for the Job Order / Job Order Request view.
    Expects: $packingItems, $containerItems, $fallbackFumigationType, $fallbackDosage
--}}
@php
    $decodeIds = function ($v) { while (is_string($v)) { $d = json_decode($v, true); if ($d === null) break; $v = $d; } return array_values(array_filter((array) $v, fn($x) => $x !== null && $x !== '')); };
    $fmtNum = fn($v, $d = 0) => ($v !== null && $v !== '') ? number_format((float) $v, $d) : '0';
    $fmtPct = fn($v) => ((float) ($v ?? 0)) > 0 ? ' (' . rtrim(rtrim(number_format((float) $v, 2), '0'), '.') . '%)' : '';
@endphp

@foreach($packingItems as $index => $item)
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
            <tr>
                <th width="12%" class="text-uppercase bg-light">Brand</th>
                <td width="13%" class="font-weight-bold">{{ $item->brand->name ?? '-' }}</td>
                <th width="12%" class="text-uppercase bg-light">Bag Product</th>
                <td width="13%">{{ $item->bagProduct->name ?? '-' }}</td>
                <th width="12%" class="text-uppercase bg-light">Bag Condition</th>
                <td width="13%">{{ $item->bagCondition->name ?? '-' }}</td>
                <th width="12%" class="text-uppercase bg-light">Packing Size</th>
                <td width="13%">{{ $item->bag_size ? number_format($item->bag_size, 2) . ' KG' : '-' }}</td>
            </tr>
            <tr>
                <th class="text-uppercase bg-light">Bag Color</th>
                <td>{{ $item->bagColor->color ?? '-' }}</td>
                <th class="text-uppercase bg-light">Thread Color</th>
                <td>{{ $item->threadColor->color ?? '-' }}</td>
                <th class="text-uppercase bg-light">Stitching</th>
                <td>{{ $item->stitching->name ?? '-' }}</td>
                <th class="text-uppercase bg-light">Min Wt. Empty Bags</th>
                <td>{{ ((float) $item->min_weight_empty_bags) > 0 ? $item->min_weight_empty_bags . ' g' : '-' }}</td>
            </tr>
            <tr>
                <th class="text-uppercase bg-light">No. of Bags</th>
                <td class="font-weight-bold">{{ $fmtNum($item->no_of_bags) }}</td>
                <th class="text-uppercase bg-light">Extra Bags</th>
                <td>{{ $fmtNum($item->extra_bags) }}{{ $fmtPct($item->extra_bags_percentage) }}</td>
                <th class="text-uppercase bg-light">Empty Bags</th>
                <td>{{ $fmtNum($item->empty_bags) }}{{ $fmtPct($item->empty_bags_percentage) }}</td>
                <th class="text-uppercase bg-light">Total Bags</th>
                <td class="font-weight-bold text-primary">{{ $fmtNum($item->total_bags) }}</td>
            </tr>
            <tr>
                <th class="text-uppercase bg-light">Total KGs</th>
                <td class="font-weight-bold">{{ $fmtNum($item->total_kgs, 2) }}</td>
                <th class="text-uppercase bg-light">Metric Tons</th>
                <td class="font-weight-bold text-success">{{ $fmtNum($item->metric_tons, 3) }}</td>
                <th class="text-uppercase bg-light">Stuffing / Cont.</th>
                <td>{{ ((float) $item->stuffing_in_container) > 0 ? rtrim(rtrim(number_format($item->stuffing_in_container, 3), '0'), '.') . ' MT' : '-' }}</td>
                <th class="text-uppercase bg-light">Containers</th>
                <td>{{ $item->no_of_containers ?? '0' }}</td>
            </tr>
            <tr>
                <th class="text-uppercase bg-light">Delivery Date</th>
                <td>{{ $item->delivery_date ? \Carbon\Carbon::parse($item->delivery_date)->format('d.m.Y') : '-' }}</td>
                <th class="text-uppercase bg-light">Fumigation By</th>
                @php
                    $fumiIds = $decodeIds($item->fumigation_company_id);
                    if (!empty($fumiIds) && in_array('null', (array) $fumiIds)) {
                        $fumis = 'N/A';
                    } else {
                        $fumis = !empty($fumiIds) ? \App\Models\Master\FumigationCompany::whereIn('id', $fumiIds)->pluck('name')->implode(', ') : '-';
                    }
                @endphp
                <td>{{ $fumis ?: '-' }}</td>
                <th class="text-uppercase bg-light">Fumigation Type</th>
                <td>{{ $item->fumigation_type ? ucfirst($item->fumigation_type) : ($fallbackFumigationType ? ucfirst($fallbackFumigationType) : '-') }}</td>
                <th class="text-uppercase bg-light">Fumigation Dosage</th>
                <td>{{ $item->fumigation_dosage ?: ($fallbackDosage ?: '-') }}</td>
            </tr>
            <tr>
                <th class="text-uppercase bg-light">Attachment</th>
                <td colspan="7">
                    @if($item->attachment)
                        <a href="{{ asset('storage/' . $item->attachment) }}" target="_blank" class="btn btn-sm btn-outline-info py-0 px-1">
                            <i class="ft-file"></i> View
                        </a>
                    @else
                        -
                    @endif
                </td>
            </tr>
            @if(!empty($item->description) || !empty($item->location_instruction))
            <tr>
                @if(!empty($item->description))
                    <th class="text-uppercase bg-light">Description</th>
                    <td colspan="{{ !empty($item->location_instruction) ? 3 : 7 }}" class="small">{!! nl2br(e($item->description)) !!}</td>
                @endif
                @if(!empty($item->location_instruction))
                    <th class="text-uppercase bg-light">Location Instr.</th>
                    <td colspan="{{ !empty($item->description) ? 3 : 7 }}" class="small">{!! nl2br(e($item->location_instruction)) !!}</td>
                @endif
            </tr>
            @endif
        </tbody>
    </table>

    @if($item->subItems && $item->subItems->count() > 0)
    <table class="table table-bordered job-info-table mt-1 mb-0">
        <thead>
            <tr class="sub-section-header">
                <th colspan="13" class="py-1 px-2 small font-weight-bold text-center">
                    MASTER PACKING FOR ITEM #{{ $index + 1 }}
                </th>
            </tr>
            <tr>
                <th class="bg-light small">Bag Type</th>
                <th class="bg-light small">Bag Size</th>
                <th class="bg-light small">Brand</th>
                <th class="bg-light small">Color / Thread</th>
                <th class="bg-light small">Stitching</th>
                <th class="bg-light small text-center">Primary Bags / Master</th>
                <th class="bg-light small text-center">Packing Size (kg)</th>
                <th class="bg-light small text-center">No. of Bags</th>
                <th class="bg-light small text-center">Extra Bags</th>
                <th class="bg-light small text-center">Empty Bags</th>
                <th class="bg-light small text-center">Total Bags</th>
                <th class="bg-light small text-center">Empty Bag Wt.</th>
                <th class="bg-light small text-center">Attachment</th>
            </tr>
        </thead>
        <tbody>
            @foreach($item->subItems as $sub)
                <tr>
                    <td class="small">{{ $sub->bagType->name ?? $sub->bagProduct->name ?? '-' }}</td>
                    <td class="small">{{ $sub->bagSize->size ?? $sub->bagSize->name ?? '-' }}</td>
                    <td class="small">{{ $sub->brand->name ?? '-' }}</td>
                    <td class="small">
                        {{ $sub->bagColor->color ?? '-' }}
                        @if($sub->threadColor) / {{ $sub->threadColor->color }} @endif
                    </td>
                    <td class="small">{{ $sub->stitching->name ?? '-' }}</td>
                    <td class="small text-center">{{ $sub->no_of_primary_bags ?? '-' }}</td>
                    <td class="small text-center">{{ $sub->packing_size ?: (($item->bag_size ?? 0) * ($sub->no_of_primary_bags ?? 0)) }}</td>
                    <td class="small text-center">{{ $fmtNum($sub->no_of_bags) }}</td>
                    <td class="small text-center">{{ $fmtNum($sub->extra_bags) }}{{ $fmtPct($sub->extra_bags_percentage) }}</td>
                    <td class="small text-center">{{ $fmtNum($sub->empty_bags) }}{{ $fmtPct($sub->empty_bags_percentage) }}</td>
                    <td class="small text-center font-weight-bold">{{ $fmtNum($sub->total_bags) }}</td>
                    <td class="small text-center">{{ ((float) $sub->empty_bag_weight) > 0 ? $sub->empty_bag_weight . ' g' : '-' }}</td>
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

@if($containerItems && $containerItems->count() > 0)
<div class="row mb-3">
    <div class="col-12">
        <table class="table table-bordered job-info-table mb-0">
            <thead>
                <tr class="section-header">
                    <th colspan="3" class="text-center py-1">CONTAINER PROTECTION &amp; PACKING MATERIALS</th>
                </tr>
                <tr>
                    <th width="8%" class="bg-light text-center">#</th>
                    <th class="bg-light">Item</th>
                    <th width="25%" class="bg-light text-center">Qty per Container</th>
                </tr>
            </thead>
            <tbody>
                @foreach($containerItems as $cpIndex => $cp)
                <tr>
                    <td class="text-center">{{ $cpIndex + 1 }}</td>
                    <td>{{ $cp->name }}</td>
                    <td class="text-center">{{ rtrim(rtrim(number_format((float) ($cp->pivot->quantity_per_container ?? 0), 2), '0'), '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
