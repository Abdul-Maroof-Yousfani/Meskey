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
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @php
            $existingOutputs = isset($b2ProductOutputs) 
                ? (is_array($b2ProductOutputs) ? collect($b2ProductOutputs) : $b2ProductOutputs) 
                : collect();
            $renderedOutputIds = [];

            $jobOrdersList = isset($jobOrders) && $jobOrders ? $jobOrders : collect();
            $renderJobOrderOptions = function($selectedJobOrderId, $rowProdId) use ($jobOrdersList) {
                $html = '<option value="">Select Job Order</option>';
                foreach ($jobOrdersList as $jobOrder) {
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
                    $html .= '<option value="' . e($jobOrder->id) . '" data-no-of-bags="' . e($optBags) . '" data-bag-size="' . e($optSize) . '" data-qty="' . e($optQty) . '" data-brand-id="' . e($optBrand) . '" ' . $isSelected . '>' . e($jobOrder->job_order_no) . '</option>';
                }
                return $html;
            };

            $arrivalSubLocations = $arrivalSubLocations ?? collect();
            $brands = $brands ?? collect();
            $jobOrderPackings = $jobOrderPackings ?? collect();
            $b2Product = $b2Product ?? null;
        @endphp

        {{-- 1. First show B2 products with packing items --}}
        @if(isset($packingItemsProducts) && $packingItemsProducts->count() > 0)
            @foreach ($packingItemsProducts as $product)
                @php
                    $relevantPackings = $jobOrderPackings->filter(function($packing) use ($product) {
                        return $packing->jobOrder && 
                               isset($packing->jobOrder->product) && 
                               $packing->jobOrder->product->id == $product->id;
                    });
                @endphp
                
                @foreach($relevantPackings as $jobOrderPacking)
                    @php 
                        $outputs = $existingOutputs->filter(function($output) use ($jobOrderPacking) {
                            return $output && $output->job_order_packing_item_id == $jobOrderPacking->id;
                        });
                    @endphp

                    @if($outputs->count() > 0)
                        @foreach($outputs as $output)
                            @php
                                if (!empty($output->id)) {
                                    $renderedOutputIds[] = $output->id;
                                }
                                $prod = $output->product ?? $product;
                            @endphp
                            <tr>
                                <td>
                                    <strong>{{ $prod->name ?? '' }}</strong>
                                    <input type="hidden" name="output_product_id[]" value="{{ $output->product_id ?? $product->id }}">
                                    <input type="hidden" name="job_order_packing_item_id[]" value="{{ $jobOrderPacking->id }}">
                                    @if($output->id)
                                        <input type="hidden" name="output_id[]" value="{{ $output->id }}">
                                    @endif
                                </td>
                                <td>
                                    <input type="number" name="output_no_of_bags[]" class="form-control" step="1" min="0"
                                        value="{{ $output->no_of_bags ?? $jobOrderPacking->no_of_bags ?? '' }}">
                                </td>
                                <td>
                                    <input type="number" name="output_bag_size[]" class="form-control" step="0.01" min="0.01"
                                        value="{{ $output->bag_size ?? $jobOrderPacking->bag_size ?? '' }}">
                                </td>
                                <td>
                                    <input type="number" name="output_qty[]" class="form-control" step="0.01" min="0.01"
                                        value="{{ $output->qty ?? $jobOrderPacking->total_kgs ?? '' }}">
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
                                        {!! $renderJobOrderOptions($output->job_order_id ?? ($jobOrderPacking->job_order_id ?? ''), $output->product_id ?? $product->id) !!}
                                    </select>
                                </td>
                                <td>
                                    <textarea name="output_remarks[]" class="form-control" rows="1">{{ $output->remarks ?? '' }}</textarea>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-primary copythis"><i class="fa fa-copy"></i></button>
                                    <button type="button" class="btn btn-sm btn-danger removethis"><i class="fa fa-trash"></i></button>
                                </td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td>
                                @php $prod = $jobOrderPacking->jobOrder->product ?? $product; @endphp
                                <strong>{{ $prod->name ?? '' }}</strong>
                                <input type="hidden" name="output_product_id[]" value="{{ $jobOrderPacking->jobOrder->product->id ?? $product->id }}">
                                <input type="hidden" name="job_order_packing_item_id[]" value="{{ $jobOrderPacking->id }}">
                            </td>
                            <td>
                                <input type="number" name="output_no_of_bags[]" class="form-control" step="1" min="0"
                                    value="{{ $jobOrderPacking->no_of_bags ?? '' }}">
                            </td>
                            <td>
                                <input type="number" name="output_bag_size[]" class="form-control" step="0.01" min="0.01"
                                    value="{{ $jobOrderPacking->bag_size ?? '' }}">
                            </td>
                            <td>
                                <input type="number" name="output_qty[]" class="form-control" step="0.01" min="0.01"
                                    value="{{ $jobOrderPacking->total_kgs ?? (($jobOrderPacking->no_of_bags && $jobOrderPacking->bag_size) ? ($jobOrderPacking->no_of_bags * $jobOrderPacking->bag_size) : '') }}">
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
                                        <option value="{{ $brand->id }}" {{ $jobOrderPacking && $jobOrderPacking->brand_id == $brand->id ? 'selected' : '' }}>{{ $brand->name }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <select name="output_job_order_id[]" class="form-control select2">
                                    {!! $renderJobOrderOptions($jobOrderPacking->job_order_id ?? '', $jobOrderPacking->jobOrder->product->id ?? $product->id) !!}
                                </select>
                            </td>
                            <td>
                                <textarea name="output_remarks[]" class="form-control" rows="1"></textarea>
                            </td>
                            <td>
                                <button type="button" class="btn btn-sm btn-primary copythis"><i class="fa fa-copy"></i></button>
                                <button type="button" class="btn btn-sm btn-danger removethis"><i class="fa fa-trash"></i></button>
                            </td>
                        </tr>
                    @endif
                @endforeach

                @php
                    $standalonePackingOutputs = $existingOutputs->filter(function($output) use ($product, $renderedOutputIds) {
                        return $output && $output->product_id == $product->id 
                            && empty($output->job_order_packing_item_id) 
                            && (!isset($output->id) || !in_array($output->id, $renderedOutputIds));
                    });
                @endphp
                @foreach($standalonePackingOutputs as $output)
                    @php
                        if (!empty($output->id)) {
                            $renderedOutputIds[] = $output->id;
                        }
                        $prod = $output->product ?? $product;
                    @endphp
                    <tr>
                        <td>
                            <strong>{{ $prod->name ?? '' }}</strong>
                            <input type="hidden" name="output_product_id[]" value="{{ $output->product_id ?? $product->id }}">
                            <input type="hidden" name="job_order_packing_item_id[]" value="">
                            @if($output->id)
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
                                {!! $renderJobOrderOptions($output->job_order_id ?? '', $output->product_id ?? $product->id) !!}
                            </select>
                        </td>
                        <td>
                            <textarea name="output_remarks[]" class="form-control" rows="1">{{ $output->remarks ?? '' }}</textarea>
                        </td>
                        <td>
                            <button type="button" class="btn btn-sm btn-primary copythis"><i class="fa fa-copy"></i></button>
                            <button type="button" class="btn btn-sm btn-danger removethis"><i class="fa fa-trash"></i></button>
                        </td>
                    </tr>
                @endforeach
            @endforeach
        @endif

        {{-- 2. Non-packing items B2 products --}}
        @if(isset($nonPackingItemsProducts) && $nonPackingItemsProducts->count() > 0)
            @foreach($nonPackingItemsProducts as $product)
                @php
                    $productOutputs = $existingOutputs->filter(function($output) use ($product, $renderedOutputIds) {
                        return $output && $output->product_id == $product->id 
                            && (!isset($output->id) || !in_array($output->id, $renderedOutputIds));
                    });
                @endphp

                @if($productOutputs->count() > 0)
                    @foreach($productOutputs as $output)
                        @php
                            if (!empty($output->id)) {
                                $renderedOutputIds[] = $output->id;
                            }
                            $prod = $output->product ?? $product;
                        @endphp
                        <tr>
                            <td>
                                <strong>{{ $prod->name ?? '' }}</strong>
                                <input type="hidden" name="output_product_id[]" value="{{ $output->product_id ?? $product->id }}">
                                <input type="hidden" name="job_order_packing_item_id[]" value="{{ $output->job_order_packing_item_id ?? '' }}">
                                @if($output->id)
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
                                    {!! $renderJobOrderOptions($output->job_order_id ?? '', $output->product_id ?? $product->id) !!}
                                </select>
                            </td>
                            <td>
                                <textarea name="output_remarks[]" class="form-control" rows="1">{{ $output->remarks ?? '' }}</textarea>
                            </td>
                            <td>
                                <button type="button" class="btn btn-sm btn-primary copythis"><i class="fa fa-copy"></i></button>
                                <button type="button" class="btn btn-sm btn-danger removethis"><i class="fa fa-trash"></i></button>
                            </td>
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td>
                            @php $prod = $product; @endphp
                            <strong>{{ $prod->name ?? '' }}</strong>
                            <input type="hidden" name="output_product_id[]" value="{{ $product->id }}">
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
                                {!! $renderJobOrderOptions('', $product->id) !!}
                            </select>
                        </td>
                        <td>
                            <textarea name="output_remarks[]" class="form-control" rows="1"></textarea>
                        </td>
                        <td>
                            <button type="button" class="btn btn-sm btn-primary copythis"><i class="fa fa-copy"></i></button>
                            <button type="button" class="btn btn-sm btn-danger removethis"><i class="fa fa-trash"></i></button>
                        </td>
                    </tr>
                @endif
            @endforeach
        @endif

        {{-- 3. Any remaining existing outputs that were not rendered above --}}
        @php
            $remainingOutputs = $existingOutputs->filter(function($output) use ($renderedOutputIds) {
                return $output && (!isset($output->id) || !in_array($output->id, $renderedOutputIds));
            });
        @endphp
        @foreach($remainingOutputs as $output)
            @php $prod = $output->product; @endphp
            <tr>
                <td>
                    <strong>{{ $prod->name ?? '' }}</strong>
                    <input type="hidden" name="output_product_id[]" value="{{ $output->product_id }}">
                    <input type="hidden" name="job_order_packing_item_id[]" value="{{ $output->job_order_packing_item_id ?? '' }}">
                    @if($output->id)
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
                <td>
                    <button type="button" class="btn btn-sm btn-primary copythis"><i class="fa fa-copy"></i></button>
                    <button type="button" class="btn btn-sm btn-danger removethis"><i class="fa fa-trash"></i></button>
                </td>
            </tr>
        @endforeach

        {{-- 4. Total Row --}}
        @php
            $hasB2Items = (isset($packingItemsProducts) && $packingItemsProducts->count() > 0)
                || (isset($nonPackingItemsProducts) && $nonPackingItemsProducts->count() > 0)
                || ($existingOutputs->count() > 0);
        @endphp
        @if($hasB2Items)
            <tr class="b2-total-row">
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
        @else
            <tr class="ant-table-placeholder">
                <td colspan="100%" class="ant-table-cell text-center">
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
                        <p class="ant-empty-description">No B2 products available</p>
                    </div>
                </td>
            </tr>
        @endif
    </tbody>
</table>
