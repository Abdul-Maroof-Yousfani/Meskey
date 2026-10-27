@php
    $allByCommodities = collect();
    if (isset($byProducts) && $byProducts) {
        $allByCommodities = $allByCommodities->merge($byProducts);
    }
    if (isset($byProductOutputs) && $byProductOutputs) {
        foreach ($byProductOutputs as $bpo) {
            if (is_object($bpo) && isset($bpo->product) && $bpo->product) {
                $allByCommodities->push($bpo->product);
            }
        }
    }
    $allByCommodities = $allByCommodities->unique('id')->sortBy('name');

    $renderByCommoditySelect = function($selectedId) use ($allByCommodities) {
        $html = '<select name="output_product_id[]" class="form-control select2 output-commodity-select" required>';
        $html .= '<option value="">Select Commodity</option>';
        $found = false;
        foreach ($allByCommodities as $item) {
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
        @foreach($allByCommodities as $item)
            <option value="{{ $item->id }}">{{ $item->name }}</option>
        @endforeach
    </select>
</div>
<table class="table table-bordered">
    <thead>
        <tr>
            <th class="col-2">Commodity</th>
            <th class="col-1">No of Bags</th>
            <th class="col-1">Packed in (KG)</th>
            <th class="col-1">Qty (kg)</th>
            <th class="col-1">Avg Weight/Bag</th>
            <th class="col-1">Yield %</th>
            <th class="col-1">Storage </th>
            <th class="col-1">Brand</th>
            <th class="col-1">Job Order</th>
            <th class="col-1">Remarks</th>
            <th class="col-1">Actions</th>
        </tr>
    </thead>
    <tbody>
        @php
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
        @endphp
        @if(isset($byProducts) && $byProducts && count($byProducts) > 0)
            @foreach($byProducts as $product)
                @php
                    $existingOutputs = isset($byProductOutputs) && $byProductOutputs ? $byProductOutputs->where('product_id', $product->id)->values() : collect();
                  
                    $outputCount = $existingOutputs->count() > 0 ? $existingOutputs->count() : 1;
                @endphp
                @for($i = 0; $i < $outputCount; $i++)
                    @php
                        $output = $existingOutputs->count() > 0 ? $existingOutputs->get($i) : null;
                    @endphp
                  
                    @if(isset($jobOrderPackings) && $jobOrderPackings->count() != 0)
                        @php
                            $filteredJobOrderPackings = ($jobOrderPackings instanceof \Illuminate\Database\Eloquent\Builder)
                                ? (clone $jobOrderPackings)->whereHas('jobOrder', function ($query) use ($product) {
                                    $query->where('product_id', $product->id);
                                })->get()
                                : $jobOrderPackings->filter(function ($item) use ($product) {
                                    return $item->jobOrder && $item->jobOrder->product_id == $product->id;
                                });
                        @endphp
                        @foreach ($filteredJobOrderPackings as $jobOrderPacking)
                            <tr>
                                <td>
                                    {!! $renderByCommoditySelect($output ? ($output->product_id ?? $product->id) : $product->id) !!}
                                    <input type="hidden" name="job_order_packing_item_id[]" value="{{ $jobOrderPacking->id }}">
                                </td>
                                <td>
                                    <input type="number" name="output_no_of_bags[]" class="form-control" step="1" min="0"
                                        value="{{ $output ? $output->no_of_bags : '' }}">
                                </td>
                                <td>
                                    <input type="number" name="output_bag_size[]" class="form-control" step="0.01" min="0.01"
                                        value="{{ $jobOrderPacking ? $jobOrderPacking->bag_size : '' }}">
                                </td>
                                <td>
                                    <input type="number" name="output_qty[]" class="form-control" step="0.01" min="0.01"
                                        value="{{ $output ? $output->qty : '' }}">
                                </td>
                                <td>
                                    <input type="number" name="output_avg_weight_per_bag[]" class="form-control" step="0.01" min="0.01"
                                        readonly value="{{ $output ? $output->avg_weight_per_bag : '' }}">
                                </td>
                                <td>
                                    <input type="number" name="output_yield[]" class="form-control" step="0.01" min="0.01" readonly
                                        value="{{ $output ? $output->yield : '' }}">
                                </td>
                                <td>
                                    <select name="output_arrival_sub_location_id[]" class="form-control select2">
                                        <option value="">Select Storage Location</option>
                                        @foreach($arrivalSubLocations as $arrivalSubLocation)
                                            <option value="{{ $arrivalSubLocation->id }}" {{ $output && $output->arrival_sub_location_id == $arrivalSubLocation->id ? 'selected' : '' }}>
                                                {{ $arrivalSubLocation->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <select name="output_brand_id[]" class="form-control select2">
                                        <option value="">Select Brand</option>
                                        @foreach($brands as $brand)
                                            <option value="{{ $brand->id }}" {{ $jobOrderPacking && $jobOrderPacking->brand_id == $brand->id ? 'selected' : '' }}>
                                                {{ $brand->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <select name="output_job_order_id[]" class="form-control select2">
                                        {!! $renderJobOrderOptions($output->job_order_id ?? ($jobOrderPacking->job_order_id ?? ''), $output ? ($output->product_id ?? $product->id) : $product->id) !!}
                                    </select>
                                </td>
                                <td>
                                    <textarea name="output_remarks[]" class="form-control"
                                        rows="1">{{ $output ? $output->remarks : '' }}</textarea>
                                </td>
                                <td><button type="button" class="btn btn-sm btn-primary copythis"><i class="fa fa-copy"></i></button>
                                    <button type="button" class="btn btn-sm btn-danger removethis"><i class="fa fa-trash"></i></button>
                                </td>
                            </tr>
                        @endforeach
                    @endif
                    <tr>
                        <td>
                            {!! $renderByCommoditySelect($output ? ($output->product_id ?? $product->id) : $product->id) !!}
                        </td>
                        <td>
                            <input type="number" name="output_no_of_bags[]" class="form-control" step="1" min="0"
                                value="{{ $output ? $output->no_of_bags : '' }}">
                        </td>
                        <td>
                            <input type="number" name="output_bag_size[]" class="form-control" step="0.01" min="0.01"
                                value="{{ $output ? $output->bag_size : '' }}">
                        </td>
                        <td>
                            <input type="number" name="output_qty[]" class="form-control" step="0.01" min="0.01"
                                value="{{ $output ? $output->qty : '' }}">
                        </td>
                        <td>
                            <input type="number" name="output_avg_weight_per_bag[]" class="form-control" step="0.01" min="0.01"
                                readonly value="{{ $output ? $output->avg_weight_per_bag : '' }}">
                        </td>
                        <td>
                            <input type="number" name="output_yield[]" class="form-control" step="0.01" min="0.01" readonly
                                value="{{ $output ? $output->yield : '' }}">
                        </td>
                        <td>
                            <select name="output_arrival_sub_location_id[]" class="form-control select2">
                                <option value="">Select Storage Location</option>
                                @foreach($arrivalSubLocations as $arrivalSubLocation)
                                    <option value="{{ $arrivalSubLocation->id }}" {{ $output && $output->arrival_sub_location_id == $arrivalSubLocation->id ? 'selected' : '' }}>
                                        {{ $arrivalSubLocation->name }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td>
                            <select name="output_brand_id[]" class="form-control select2">
                                <option value="">Select Brand</option>
                                @foreach($brands as $brand)
                                    <option value="{{ $brand->id }}" {{ $output && $output->brand_id == $brand->id ? 'selected' : '' }}>
                                        {{ $brand->name }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td>
                            <select name="output_job_order_id[]" class="form-control select2">
                                {!! $renderJobOrderOptions($output->job_order_id ?? '', $output ? ($output->product_id ?? $product->id) : $product->id) !!}
                            </select>
                        </td>
                        <td>
                            <textarea name="output_remarks[]" class="form-control"
                                rows="1">{{ $output ? $output->remarks : '' }}</textarea>
                        </td>
                        <td><button type="button" class="btn btn-sm btn-primary copythis"><i class="fa fa-copy"></i></button>
                            <button type="button" class="btn btn-sm btn-danger removethis"><i class="fa fa-trash"></i></button>
                        </td>
                    </tr>
                @endfor
                <tr class="d-none">
                    <td>
                        <strong>Commodity Total</strong>
                    </td>
                    <td>
                        <input type="number" name="commodity_total_no_of_bags[]" class="form-control" step="1" min="0" readonly>
                    </td>
                    <td></td>
                    <td>
                        <input type="number" name="commodity_total_qty[]" class="form-control" step="0.01" min="0.01" readonly>
                    </td>

                    <!-- <td></td> -->
                    <td></td>
                    <td>
                        <input type="number" name="total_yield[]" class="form-control" step="0.01" min="0.01" readonly>
                    </td>
                    <td colspan="5">
                    </td>

                </tr>
            @endforeach
            <tr class="table-success">
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

                <!-- <td></td> -->
                <td></td>
                <td>
                    <input type="number" name="total_yield[]" class="form-control" step="0.01" min="0.01" readonly>
                </td>
                <td colspan="5">
                </td>

            </tr>
        @else
            <tr class="ant-table-placeholder">
                <td colspan="100%" class="ant-table-cell text-center">
                    <div class="my-1">
                        <svg width="64" height="41" viewBox="0 0 64 41" xmlns="http://www.w3.org/2000/svg">
                            <g transform="translate(0 1)" fill="none" fill-rule="evenodd">
                                <ellipse fill="#f5f5f5" cx="32" cy="33" rx="32" ry="7">
                                </ellipse>
                                <g fill-rule="nonzero" stroke="#d9d9d9">
                                    <path
                                        d="M55 12.76L44.854 1.258C44.367.474 43.656 0 42.907 0H21.093c-.749 0-1.46.474-1.947 1.257L9 12.761V22h46v-9.24z">
                                    </path>
                                    <path
                                        d="M41.613 15.931c0-1.605.994-2.93 2.227-2.931H55v18.137C55 33.26 53.68 35 52.05 35h-40.1C10.32 35 9 33.259 9 31.137V13h11.16c1.233 0 2.227 1.323 2.227 2.928v.022c0 1.605 1.005 2.901 2.237 2.901h14.752c1.232 0 2.237-1.308 2.237-2.913v-.007z"
                                        fill="#fafafa"></path>
                                </g>
                            </g>
                        </svg>
                        <p class="ant-empty-description">No data</p>
                    </div>
                </td>
            </tr>
        @endif
    </tbody>
</table>