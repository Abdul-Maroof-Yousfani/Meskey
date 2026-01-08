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
        @if(isset($headProduct) && $headProduct)
                <tr>
                    <td>
                        <strong>{{ $headProduct->name }}</strong>
                        <input type="hidden" name="output_product_id[]" value="{{ $headProduct->id }}">
                    </td>   
                    <td>
                        <input type="number" name="output_no_of_bags[]" class="form-control" step="1" min="0" required>
                    </td>
                    <td>
                        <input type="number" name="output_bag_size[]" class="form-control" step="0.01" min="0.01" required>
                    </td>
                    <td>
                        <input type="number" name="output_qty[]" class="form-control" step="0.01" min="0.01" required>
                    </td>
                    <td>
                        <input type="number" name="output_avg_weight_per_bag[]" class="form-control" step="0.01" min="0.01" readonly>
                    </td>
                    <td>
                        <input type="number" name="output_yield[]" class="form-control" step="0.01" min="0.01" readonly>
                    </td>
                    <td>
                        <select name="output_arrival_sub_location_id[]" class="form-control select2" required>
                            <option value="">Select Storage Location</option>
                            @foreach($arrivalSubLocations as $arrivalSubLocation)
                                <option value="{{ $arrivalSubLocation->id }}">{{ $arrivalSubLocation->name }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td>
                        <select name="output_brand_id[]" class="form-control select2" required>
                            <option value="">Select Brand</option>
                            @foreach($brands as $brand)
                                <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td>
                        <select name="output_job_order_id[]" class="form-control select2" required>
                            <option value="">Select Job Order</option>
                            @foreach($jobOrders as $jobOrder)
                                <option value="{{ $jobOrder->id }}">{{ $jobOrder->job_order_no }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td>
                        <textarea name="output_remarks[]" class="form-control" rows="1"></textarea>
                    </td>
                    <td><button class="btn btn-sm btn-primary copythis"><i class="fa fa-copy"></i></button>
                        <button class="btn btn-sm btn-danger removethis"><i class="fa fa-trash"></i></button>
                    </td>
                </tr>
                <tr>
                    <td>
                        <strong>Total</strong>
                    </td>
                    <td>
                        <input type="number" name="commodity_total_qty[]" class="form-control" step="0.01" min="0.01" readonly>
                    </td>
                    <td>
                        <input type="number" name="commodity_total_no_of_bags[]" class="form-control" step="1" min="0" readonly>
                    </td>
                    <td></td>
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