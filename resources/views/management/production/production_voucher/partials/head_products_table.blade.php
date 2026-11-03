@php
    $allHeadCommodities = collect();
    if (isset($allHeadProducts) && $allHeadProducts) {
        $allHeadCommodities = $allHeadCommodities->merge($allHeadProducts);
    }
    if (isset($packingItemsProducts) && $packingItemsProducts) {
        $allHeadCommodities = $allHeadCommodities->merge($packingItemsProducts);
    }
    if (isset($nonPackingItemsProducts) && $nonPackingItemsProducts) {
        $allHeadCommodities = $allHeadCommodities->merge($nonPackingItemsProducts);
    }
    if (isset($headProductOutputs) && $headProductOutputs) {
        foreach ($headProductOutputs as $hpo) {
            if (is_object($hpo) && isset($hpo->product) && $hpo->product) {
                $allHeadCommodities->push($hpo->product);
            }
        }
    }
    $allHeadCommodities = $allHeadCommodities->filter(function($p) {
        return $p && ($p->product_category_flags ?? '') !== 'b2' 
                  && ($p->product_category_flags ?? '') !== 'by'
                  && ($p->product_type ?? '') !== 'general_items';
    })->unique('id')->sortBy('name');

    $renderHeadCommoditySelect = function($selectedId) use ($allHeadCommodities) {
        $html = '<select name="output_product_id[]" class="form-control select2 output-commodity-select" required>';
        $html .= '<option value="">Select Commodity</option>';
        $found = false;
        foreach ($allHeadCommodities as $item) {
            $isSel = ($selectedId == $item->id);
            if ($isSel) $found = true;
            $html .= '<option value="' . e($item->id) . '" ' . ($isSel ? 'selected' : '') . '>' . e($item->name) . '</option>';
        }
        if ($selectedId && !$found) {
            $extraProd = \App\Models\Product::find($selectedId);
            if ($extraProd) {
                $html .= '<option value="' . e($extraProd->id) . '" selected>' . e($extraProd->name) . '</option>';
            }
        }
        $html .= '</select>';
        return $html;
    };
@endphp
<div style="display:none;" class="commodity-select-template">
    <select class="form-control select2" name="output_product_id[]">
        <option value="">Select Commodity</option>
        @foreach($allHeadCommodities as $item)
            <option value="{{ $item->id }}">{{ $item->name }}</option>
        @endforeach
    </select>
</div>
<table class="table table-bordered mb-0" style="table-layout: fixed; width: 100%;">
    <thead>
        <tr>
            <th style="width: 18%; min-width: 150px;">Commodity</th>
            <th style="width: 8%; min-width: 75px;">No of Bags</th>
            <th style="width: 8%; min-width: 80px;">Packed in (KG)</th>
            <th style="width: 9%; min-width: 80px;">Qty (kg)</th>
            <th style="width: 8%; min-width: 80px;">Avg Weight/Bag</th>
            <th style="width: 7%; min-width: 65px;">Yield %</th>
            <th style="width: 11%; min-width: 105px;">Storage</th>
            <th style="width: 10%; min-width: 95px;">Brand</th>
            <th style="width: 11%; min-width: 105px;">Job Order</th>
            <th style="width: 5%; min-width: 65px;">Remarks</th>
            <th style="width: 5%; min-width: 65px; text-align: center;">Actions</th>
        </tr>
    </thead>
    <tbody>
        @php
            $existingOutputs = isset($headProductOutputs) 
                ? (is_array($headProductOutputs) ? collect($headProductOutputs) : $headProductOutputs) 
                : collect();
            $renderedRowCount = 0;
            $isVoucherSelected = (isset($productionVoucher) && $productionVoucher) 
                || request()->filled('production_voucher_id') 
                || (isset($headProductOutputs) && $existingOutputs->count() > 0);

            $jobOrdersList = isset($jobOrders) && $jobOrders ? $jobOrders : collect();
            $renderJobOrderOptions = function($selectedJobOrderId, $rowProdId) use ($jobOrdersList) {
                $html = '<option value="">Select Job Order</option>';

                $matching = collect();
                if ($rowProdId) {
                    $matching = $jobOrdersList->filter(function($jo) use ($rowProdId) {
                        return $jo->product_id == $rowProdId;
                    });
                }

                if ($selectedJobOrderId && !$matching->contains('id', $selectedJobOrderId)) {
                    $existingJo = $jobOrdersList->firstWhere('id', $selectedJobOrderId);
                    if ($existingJo && $existingJo->product_id == $rowProdId) {
                        $matching->push($existingJo);
                    }
                }

                foreach ($matching as $jobOrder) {
                    $joPacking = null;
                    if ($jobOrder->relationLoaded('packingItems') && $jobOrder->packingItems) {
                        $joPacking = $jobOrder->packingItems->first(function($pi) use ($rowProdId) {
                            return $rowProdId && $pi->jobOrder && $pi->jobOrder->product_id == $rowProdId;
                        }) ?? $jobOrder->packingItems->first();
                    }
                    $optBags = $joPacking->no_of_bags ?? '';
                    $optSize = $joPacking->bag_size ?? '';
                    $optQty = $joPacking->total_kgs ?? (($optBags && $optSize) ? ($optBags * $optSize) : '');
                    $optBrand = $joPacking->brand_id ?? '';
                    $isSelected = ($selectedJobOrderId == $jobOrder->id) ? 'selected' : '';
                    $pName = $jobOrder->product->name ?? '';
                    $displayText = $jobOrder->job_order_no . ($jobOrder->ref_no ? ' (' . $jobOrder->ref_no . ')' : '') . ($pName ? ' - ' . $pName : '');
                    $html .= '<option value="' . e($jobOrder->id) . '" data-no-of-bags="' . e($optBags) . '" data-bag-size="' . e($optSize) . '" data-qty="' . e($optQty) . '" data-brand-id="' . e($optBrand) . '" ' . $isSelected . '>' . e($displayText) . '</option>';
                }
                return $html;
            };

            $arrivalSubLocations = $arrivalSubLocations ?? collect();
            $brands = $brands ?? collect();
            $jobOrderPackings = $jobOrderPackings ?? collect();

            $isHeadProductValid = function($product) {
                if (!$product) return false;
                $flag = $product->product_category_flags ?? '';
                $type = $product->product_type ?? '';
                if ($flag === 'b2' || $flag === 'by' || $type === 'general_items') {
                    return false;
                }
                return true;
            };
        @endphp

        {{-- Mode 1: Edit Mode - Saved Voucher Outputs --}}
        @if($isVoucherSelected && $existingOutputs->count() > 0)
            @foreach($existingOutputs as $output)
                @php
                    $prod = $output->product ?? null;
                    if (!$prod && !empty($output->product_id)) {
                        $prod = \App\Models\Product::find($output->product_id);
                    }
                    if (!$isHeadProductValid($prod)) {
                        continue;
                    }
                    $renderedRowCount++;
                @endphp
                <tr>
                    <td>
                        {!! $renderHeadCommoditySelect($output->product_id) !!}
                        <input type="hidden" name="job_order_packing_item_id[]" value="{{ $output->job_order_packing_item_id ?? '' }}">
                        @if(!empty($output->id))
                            <input type="hidden" name="output_id[]" value="{{ $output->id }}">
                        @endif
                    </td>
                    <td>
                        <input type="number" name="output_no_of_bags[]" class="form-control" step="1" min="0"
                            value="{{ $output->no_of_bags ?? '' }}">
                    </td>
                    <td>
                        <input type="number" name="output_bag_size[]" class="form-control" step="0.01" min="0.01"
                            value="{{ $output->bag_size ?? '' }}">
                    </td>
                    <td>
                        <input type="number" name="output_qty[]" class="form-control" step="0.01" min="0.01"
                            value="{{ $output->qty ?? '' }}">
                    </td>
                    <td>
                        <input type="number" name="output_avg_weight_per_bag[]" class="form-control" step="0.01" min="0.01"
                            readonly value="{{ $output->avg_weight_per_bag ?? '' }}">
                    </td>
                    <td>
                        <input type="number" name="output_yield[]" class="form-control" step="0.01" min="0.01" readonly
                            value="{{ $output->yield ?? '' }}">
                    </td>
                    <td>
                        <select name="output_arrival_sub_location_id[]" class="form-control select2">
                            <option value="">Select Storage Location</option>
                            @foreach($arrivalSubLocations as $arrivalSubLocation)
                                <option value="{{ $arrivalSubLocation->id }}" {{ ($output->arrival_sub_location_id ?? '') == $arrivalSubLocation->id ? 'selected' : '' }}>
                                    {{ $arrivalSubLocation->name }}
                                </option>
                            @endforeach
                        </select>
                    </td>
                    <td>
                        <select name="output_brand_id[]" class="form-control select2">
                            <option value="">Select Brand</option>
                            @foreach($brands as $brand)
                                <option value="{{ $brand->id }}" {{ ($output->brand_id ?? '') == $brand->id ? 'selected' : '' }}>
                                    {{ $brand->name }}
                                </option>
                            @endforeach
                        </select>
                    </td>
                    <td>
                        <select name="output_job_order_id[]" class="form-control select2">
                            {!! $renderJobOrderOptions($output->job_order_id ?? '', $output->product_id ?? '') !!}
                        </select>
                    </td>
                    <td>
                        <textarea name="output_remarks[]" class="form-control" rows="1">{{ $output->remarks ?? '' }}</textarea>
                    </td>
                    <td class="text-center align-middle">
                        <button type="button" class="btn btn-sm btn-primary copythis"><i class="fa fa-copy"></i></button>
                        <button type="button" class="btn btn-sm btn-danger removethis"><i class="fa fa-trash"></i></button>
                    </td>
                </tr>
            @endforeach
        {{-- Mode 2: Create Mode - Populate from selected Job Orders and Packing Items --}}
        @elseif(!$isVoucherSelected)
            @php
                $renderedHeadJobOrderIds = [];
            @endphp
            @if(isset($jobOrderPackings) && $jobOrderPackings->count() > 0)
                @foreach($jobOrderPackings as $jobOrderPacking)
                    @php
                        $jo = $jobOrderPacking->jobOrder ?? null;
                        $prod = $jo ? ($jo->product ?? null) : null;
                        if (!$isHeadProductValid($prod)) {
                            continue;
                        }
                        $renderedRowCount++;
                        if ($jo && !empty($jo->id)) {
                            $renderedHeadJobOrderIds[] = $jo->id;
                        }
                        $targetProdId = $prod ? $prod->id : ($jo->product_id ?? '');
                        $bags = $jobOrderPacking->no_of_bags ?? '';
                        $size = $jobOrderPacking->bag_size ?? '';
                        $qty = $jobOrderPacking->total_kgs ?? (($bags && $size) ? ($bags * $size) : '');
                        $brandId = $jobOrderPacking->brand_id ?? '';
                    @endphp
                    <tr>
                        <td>
                            {!! $renderHeadCommoditySelect($targetProdId) !!}
                            <input type="hidden" name="job_order_packing_item_id[]" value="{{ $jobOrderPacking->id }}">
                        </td>
                        <td>
                            <input type="number" name="output_no_of_bags[]" class="form-control" step="1" min="0"
                                value="{{ $bags }}">
                        </td>
                        <td>
                            <input type="number" name="output_bag_size[]" class="form-control" step="0.01" min="0.01"
                                value="{{ $size }}">
                        </td>
                        <td>
                            <input type="number" name="output_qty[]" class="form-control" step="0.01" min="0.01"
                                value="{{ $qty }}">
                        </td>
                        <td>
                            <input type="number" name="output_avg_weight_per_bag[]" class="form-control" step="0.01" min="0.01"
                                readonly value="">
                        </td>
                        <td>
                            <input type="number" name="output_yield[]" class="form-control" step="0.01" min="0.01" readonly value="">
                        </td>
                        <td>
                            <select name="output_arrival_sub_location_id[]" class="form-control select2">
                                <option value="">Select Storage Location</option>
                                @foreach($arrivalSubLocations as $arrivalSubLocation)
                                    <option value="{{ $arrivalSubLocation->id }}">{{ $arrivalSubLocation->name }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td>
                            <select name="output_brand_id[]" class="form-control select2">
                                <option value="">Select Brand</option>
                                @foreach($brands as $brand)
                                    <option value="{{ $brand->id }}" {{ $brandId == $brand->id ? 'selected' : '' }}>{{ $brand->name }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td>
                            <select name="output_job_order_id[]" class="form-control select2">
                                {!! $renderJobOrderOptions($jobOrderPacking->job_order_id ?? '', $targetProdId) !!}
                            </select>
                        </td>
                        <td>
                            <textarea name="output_remarks[]" class="form-control" rows="1"></textarea>
                        </td>
                        <td class="text-center align-middle">
                            <button type="button" class="btn btn-sm btn-primary copythis"><i class="fa fa-copy"></i></button>
                            <button type="button" class="btn btn-sm btn-danger removethis"><i class="fa fa-trash"></i></button>
                        </td>
                    </tr>
                @endforeach
            @endif

            {{-- For any selected Head Job Order that had NO packing items, render a row --}}
            @if(isset($jobOrders) && $jobOrders->count() > 0)
                @foreach($jobOrders as $jo)
                    @php
                        if (in_array($jo->id, $renderedHeadJobOrderIds)) {
                            continue;
                        }
                        $prod = $jo->product ?? null;
                        if (!$isHeadProductValid($prod)) {
                            continue;
                        }
                        $renderedRowCount++;
                        $renderedHeadJobOrderIds[] = $jo->id;
                        $targetProdId = $prod ? $prod->id : ($jo->product_id ?? '');
                    @endphp
                    <tr>
                        <td>
                            {!! $renderHeadCommoditySelect($targetProdId) !!}
                            <input type="hidden" name="job_order_packing_item_id[]" value="">
                        </td>
                        <td>
                            <input type="number" name="output_no_of_bags[]" class="form-control" step="1" min="0" value="">
                        </td>
                        <td>
                            <input type="number" name="output_bag_size[]" class="form-control" step="0.01" min="0.01" value="">
                        </td>
                        <td>
                            <input type="number" name="output_qty[]" class="form-control" step="0.01" min="0.01" value="">
                        </td>
                        <td>
                            <input type="number" name="output_avg_weight_per_bag[]" class="form-control" step="0.01" min="0.01" readonly value="">
                        </td>
                        <td>
                            <input type="number" name="output_yield[]" class="form-control" step="0.01" min="0.01" readonly value="">
                        </td>
                        <td>
                            <select name="output_arrival_sub_location_id[]" class="form-control select2">
                                <option value="">Select Storage Location</option>
                                @foreach($arrivalSubLocations as $arrivalSubLocation)
                                    <option value="{{ $arrivalSubLocation->id }}">{{ $arrivalSubLocation->name }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td>
                            <select name="output_brand_id[]" class="form-control select2">
                                <option value="">Select Brand</option>
                                @foreach($brands as $brand)
                                    <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td>
                            <select name="output_job_order_id[]" class="form-control select2">
                                {!! $renderJobOrderOptions($jo->id, $targetProdId) !!}
                            </select>
                        </td>
                        <td>
                            <textarea name="output_remarks[]" class="form-control" rows="1"></textarea>
                        </td>
                        <td class="text-center align-middle">
                            <button type="button" class="btn btn-sm btn-primary copythis"><i class="fa fa-copy"></i></button>
                            <button type="button" class="btn btn-sm btn-danger removethis"><i class="fa fa-trash"></i></button>
                        </td>
                    </tr>
                @endforeach
            @endif
        @endif

        {{-- Total Row --}}
        <tr class="commodity-total-row {{ $renderedRowCount > 0 ? '' : 'd-none' }}">
            <td>
                <strong>Total</strong>
            </td>
            <td>
                <input type="number" name="commodity_total_no_of_bags[]" class="form-control" step="1" min="0" readonly>
            </td>
            <td></td>
            <td>
                <input type="number" name="commodity_total_qty[]" class="form-control" step="0.01" min="0.01" readonly>
            </td>
            <td></td>
            <td>
                <input type="number" name="total_yield[]" class="form-control" step="0.01" min="0.01" readonly>
            </td>
            <td colspan="5"></td>
        </tr>
        <tr class="ant-table-placeholder {{ $renderedRowCount > 0 ? 'd-none' : '' }}">
            <td colspan="11" class="ant-table-cell text-center py-4">
                <div class="my-1">
                    <svg width="64" height="41" viewBox="0 0 64 41" xmlns="http://www.w3.org/2000/svg">
                        <g transform="translate(0 1)" fill="none" fill-rule="evenodd">
                            <ellipse fill="#f5f5f5" cx="32" cy="33" rx="32" ry="7"></ellipse>
                            <g fill-rule="nonzero" stroke="#d9d9d9">
                                <path d="M55 12.76L44.854 1.258C44.367.474 43.656 0 42.907 0H21.093c-.749 0-1.46.474-1.947 1.257L9 12.761V22h46v-9.24z"></path>
                                <path d="M41.613 15.931c0-1.605.994-2.93 2.227-2.931H55v18.137C55 33.26 53.68 35 52.05 35h-40.1C10.32 35 9 33.259 9 31.137V13h11.16c1.233 0 2.227 1.323 2.227 2.928v.022c0 1.605 1.005 2.901 2.237 2.901h14.752c1.232 0 2.237-1.308 2.237-2.913v-.007z" fill="#fafafa"></path>
                            </g>
                        </g>
                    </svg>
                    <p class="ant-empty-description mb-0 text-muted" style="font-size: 13px;">No data</p>
                </div>
            </td>
        </tr>
    </tbody>
</table>

<template id="headProductRowTemplate">
    <tr>
        <td>
            {!! $renderHeadCommoditySelect('') !!}
            <input type="hidden" name="job_order_packing_item_id[]" value="">
        </td>
        <td>
            <input type="number" name="output_no_of_bags[]" class="form-control" step="1" min="0" value="">
        </td>
        <td>
            <input type="number" name="output_bag_size[]" class="form-control" step="0.01" min="0.01" value="">
        </td>
        <td>
            <input type="number" name="output_qty[]" class="form-control" step="0.01" min="0.01" value="">
        </td>
        <td>
            <input type="number" name="output_avg_weight_per_bag[]" class="form-control" step="0.01" min="0.01" readonly value="">
        </td>
        <td>
            <input type="number" name="output_yield[]" class="form-control" step="0.01" min="0.01" readonly value="">
        </td>
        <td>
            <select name="output_arrival_sub_location_id[]" class="form-control select2">
                <option value="">Select Storage Location</option>
                @foreach($arrivalSubLocations as $arrivalSubLocation)
                    <option value="{{ $arrivalSubLocation->id }}">{{ $arrivalSubLocation->name }}</option>
                @endforeach
            </select>
        </td>
        <td>
            <select name="output_brand_id[]" class="form-control select2">
                <option value="">Select Brand</option>
                @foreach($brands as $brand)
                    <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                @endforeach
            </select>
        </td>
        <td>
            <select name="output_job_order_id[]" class="form-control select2">
                {!! $renderJobOrderOptions('', '') !!}
            </select>
        </td>
        <td>
            <textarea name="output_remarks[]" class="form-control" rows="1"></textarea>
        </td>
        <td class="text-center align-middle">
            <button type="button" class="btn btn-sm btn-primary copythis"><i class="fa fa-copy"></i></button>
            <button type="button" class="btn btn-sm btn-danger removethis"><i class="fa fa-trash"></i></button>
        </td>
    </tr>
</template>