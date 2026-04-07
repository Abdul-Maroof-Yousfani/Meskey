<form action="{{ route('job-orders.update', $jobOrder->id) }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    @method('PUT')
    <input type="hidden" id="listRefresh" value="{{ route('get.job_orders') }}" />

    <div class="row form-mar">
        <!-- Basic Information -->
        <div class="col-md-12">
            <h6 class="header-heading-sepration">Basic Information</h6>
            <div class="row">
                <!-- Row 1: Export Order | Job Order No | Job Order Date -->
                <div class="col-md-4">
                    <fieldset>
                        <label>Export Order:</label>
                        <div class="input-group">
                            <select name="export_order_id" class="form-control select2" id="exportOrderSelect">
                                <option value="">Select Export Order</option>
                                @foreach($exportOrders as $eo)
                                    <option value="{{ $eo->id }}" {{ $jobOrder->export_order_id == $eo->id ? 'selected' : '' }}>
                                        {{ $eo->voucher_no }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </fieldset>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Job Order No:</label>
                        <input type="text" readonly name="job_order_no" class="form-control"
                            value="{{ $jobOrder->job_order_no }}">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Job Order Date:</label>
                        <input readonly type="date" name="job_order_date" class="form-control"
                            value="{{ $jobOrder->job_order_date->format('Y-m-d') }}">
                    </div>
                </div>

                <!-- Row 2: Ref No | Attention To -->
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Ref No:</label>
                        <input type="text" name="ref_no" class="form-control" value="{{ $jobOrder->ref_no }}">
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="form-group">
                        <label>Attention To:</label>
                        <select name="attention_to[]" class="form-control select2" multiple>
                            <option value="">Select Users</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" {{ in_array($user->id, json_decode($jobOrder->attention_to, true) ?? []) ? 'selected' : '' }}>
                                    {{ $user->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Row 3: Remarks | Order Description -->
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Remarks:</label>
                        <textarea name="remarks" class="form-control" rows="5">{{ $jobOrder->remarks }}</textarea>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Order Description:</label>
                        <textarea name="order_description" class="form-control" rows="5">{{ $jobOrder->order_description }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- Product Selection -->
        <div class="col-md-12">
            <div class="form-group">
                <label>Commodity/Product:</label>
                <select name="product_id" class="form-control select2" id="mainProductSelect">
                    <option value="">Select Product</option>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}" {{ $jobOrder->product_id == $product->id ? 'selected' : '' }}>
                            {{ $product->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <!-- Specifications Section -->
        <div class="col-md-12" id="specificationsSection"
            style="display: {{ $jobOrder->specifications->count() ? 'block' : 'none' }};">
            <h6 class="header-heading-sepration">Specifications</h6>
            <div id="productSpecs">
                @if($jobOrder->specifications->count())
                    <div class="specifications-table">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead class="thead-dark">
                                    <tr>
                                        <th width="40%">Specification Name</th>
                                        <th width="30%">Value</th>
                                        <th width="30%">UOM</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($jobOrder->specifications as $index => $spec)
                                        <tr>
                                            <td>
                                                <strong>{{ $spec->spec_name }}</strong>
                                                <input type="hidden" name="specifications[{{ $index }}][product_slab_type_id]"
                                                    value="{{ $spec->product_slab_type_id }}">
                                                <input type="hidden" name="specifications[{{ $index }}][spec_name]"
                                                    value="{{ $spec->spec_name }}">
                                                <input type="hidden" name="specifications[{{ $index }}][uom]"
                                                    value="{{ $spec->uom }}">
                                            </td>
                                            <td>
                                            <fieldset>
                                                <div class="input-group">
                                                    <input type="text" name="specifications[{{ $index }}][spec_value]"  value="{{ $spec->spec_value ?? 0 }}"
                                                        class="form-control form-control-sm spec-value-input" placeholder="Enter value">
                                                    <div class="input-group-prepend">
                                                        <button class="btn btn-secondary" type="button">{{ $spec->productSlabType->qc_symbol ?? 'N/A' }}</button>
                                                    </div>
                                                </div>
                                            </fieldset>
                                                <!-- <input type="text" name="specifications[{{ $index }}][spec_value]"
                                                    value="{{ $spec->spec_value }}"
                                                    class="form-control form-control-sm spec-value-input"
                                                    placeholder="Enter value"> -->
                                            </td>
                                            <td>
                                                <select name="specifications[{{ $index }}][value_type]" class="form-control">
                                                    <option {{ $spec->value_type == 'min' ? 'selected' : ''}} value="min">Minimum</option>                             
                                                    <option  {{ $spec->value_type == 'max' ? 'selected' : ''}} value="max">Maximum</option>                             
                                                </select>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @else
                    <div class="alert bg-light-warning mb-2 alert-light-warning" role="alert">
                        <i class="ft-info mr-1"></i>
                        <strong>No specifications found!</strong> Please select a commodity first!
                    </div>
                @endif
            </div>
        </div>


          <!-- CropYear Selection -->
          <div class="col-md-12">
            <div class="form-group">
                <label>Crop Year:</label>
                <select name="crop_year_id" class="form-control select2" id="productSelect">
                    <option value="">Select Crop Year</option>
                    @foreach($cropYears as $cropYear)
                        <option {{ $jobOrder->crop_year_id == $cropYear->id ? 'selected' : '' }} value="{{ $cropYear->id }}">{{ $cropYear->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-md-12">
            <div class="form-group">
                <label>Other Specification:</label>
                <textarea name="other_specifications" class="form-control" rows="4">{{ $jobOrder->other_specifications }}</textarea>
            </div>
        </div>

        <!-- Packing Details -->
        <div class="col-md-12">
            <h6 class="header-heading-sepration d-flex justify-content-between align-items-center">Packing Details
                <button type="button" class="btn btn-sm btn-success" id="addPackingItem">Add More Packing Item</button>
            </h6>

            <div id="export-order-quantity-info" class="mb-3" style="display: none;"></div>

            <div id="packingItems">
                @foreach($jobOrder->packingItems as $packingIndex => $packingItem)
                    <div class="packing-item row border-bottom pb-3 mb-3 w-100 mx-auto">
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Location:</label>
                            <select name="packing_items[{{ $packingIndex }}][company_location_id]"  class="form-control" >
                                <option value="">Select Location</option>
                                @foreach($companyLocations as $location)
                                    <option value="{{ $location->id }}" {{ $packingItem->company_location_id == $location->id ? 'selected' : '' }}>
                                        {{ $location->name }}
                                    </option>
                                @endforeach
                            </select>
                            <!-- <input type="hidden" value="{{ $packingItem->company_location_id }}" name="packing_items[{{ $packingIndex }}][company_location_id]"> -->
                        </div>
                    </div>
                    <div class="col-md-2">
                            <div class="form-group">
                                <label>Brand:</label>
                                <select name="packing_items[{{ $packingIndex }}][brand_id]" class="form-control select2">
                                    <option value="">Select Brand</option>
                                    @foreach($brands as $brand)
                                        <option value="{{ $brand->id }}" {{ $packingItem->brand_id == $brand->id ? 'selected' : '' }}>
                                            {{ $brand->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <!-- <div class="col-md-2">
                            <div class="form-group">
                                <label>Bag Type:</label>
                                <select name="packing_items[{{ $packingIndex }}][bag_type_id]" class="form-control">
                                    <option value="">Select Bag Type</option>
                                    @foreach($bagTypes as $bagType)
                                        <option value="{{ $bagType->id }}" {{ $packingItem->bag_type_id == $bagType->id ? 'selected' : '' }}>
                                            {{ $bagType->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div> -->
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Bag Product:</label>
                                <select name="packing_items[{{ $packingIndex }}][bag_product_id]" class="form-control select2">
                                    <option value="">Select Bag Product</option>
                                    @foreach($bagProducts as $bagProduct)
                                        <option value="{{ $bagProduct->id }}" {{ $packingItem->bag_product_id == $bagProduct->id ? 'selected' : '' }}>
                                            {{ $bagProduct->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Bag Condition:</label>
                                <select name="packing_items[{{ $packingIndex }}][bag_condition_id]" class="form-control select2">
                                    <option value="">Select Condition</option>
                                    @foreach($bagConditions as $condition)
                                        <option value="{{ $condition->id }}" {{ $packingItem->bag_condition_id == $condition->id ? 'selected' : '' }}>
                                            {{ $condition->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Bag Color:</label>
                                <select name="packing_items[{{ $packingIndex }}][bag_color_id]" class="form-control select2">
                                    <option value="">Select Color</option>
                                    @foreach($bagColors as $color)
                                        <option value="{{ $color->id }}" {{ $packingItem->bag_color_id == $color->id ? 'selected' : '' }}>
                                            {{ $color->color }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Thread Color:</label>
                                <select name="packing_items[{{ $packingIndex }}][thread_color_id]" class="form-control select2">
                                    <option value="">Select Color</option>
                                    @foreach($bagColors as $color)
                                        <option value="{{ $color->id }}" {{ $packingItem->thread_color_id == $color->id ? 'selected' : '' }}>
                                            {{ $color->color }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-1">
                            <div class="form-group">
                                <label>Stitching:</label>
                                <select name="packing_items[{{ $packingIndex }}][stitching_id]" class="form-control select2">
                                    <option value="">Select Stitching</option>
                                    @foreach($stitchings as $stitching)
                                        <option value="{{ $stitching->id }}" {{ $packingItem->stitching_id == $stitching->id ? 'selected' : '' }}>{{ $stitching->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-1">
                            <div class="form-group">
                                <label>Packing Size (kg):</label>
                                <input type="number" name="packing_items[{{ $packingIndex }}][bag_size]"
                                    class="form-control bag-size" step="0.01" value="{{ $packingItem->bag_size }}">
                            </div>
                        </div>
                        <div class="col-md-1">
                            <div class="form-group">
                                <label>No. of Bags:</label>
                                <input type="number" name="packing_items[{{ $packingIndex }}][no_of_bags]"
                                    class="form-control no-of-bags" value="{{ $packingItem->no_of_bags }}">
                            </div>
                        </div>
                        <div class="col-md-1">
                            <div class="form-group">
                                <label>Extra Bags:</label>
                                <input type="number" name="packing_items[{{ $packingIndex }}][extra_bags]"
                                    class="form-control extra-bags" value="{{ $packingItem->extra_bags }}">
                            </div>
                        </div>
                        <div class="col-md-1">
                            <div class="form-group">
                                <label>Extra Bags %:</label>
                                <input type="number" name="packing_items[{{ $packingIndex }}][extra_bags_percentage]"
                                    class="form-control extra-bags-percentage" step="0.01" value="{{ $packingItem->extra_bags_percentage ?? 0 }}">
                            </div>
                        </div>
                        <div class="col-md-1">
                            <div class="form-group">
                                <label>Empty Bags:</label>
                                <input type="number" name="packing_items[{{ $packingIndex }}][empty_bags]"
                                    class="form-control empty-bags" value="{{ $packingItem->empty_bags }}">
                            </div>
                        </div>
                        <div class="col-md-1">
                            <div class="form-group">
                                <label>Total Bags:</label>
                                <input type="number" name="packing_items[{{ $packingIndex }}][total_bags]"
                                    class="form-control total-bags" readonly value="{{ $packingItem->total_bags }}">
                            </div>
                        </div>
                        <div class="col-md-1">
                            <div class="form-group">
                                <label>Total KGs:</label>
                                <input type="number" name="packing_items[{{ $packingIndex }}][total_kgs]"
                                    class="form-control total-kgs" step="0.01" readonly
                                    value="{{ $packingItem->total_kgs }}">
                            </div>
                        </div>
                        <div class="col-md-1">
                            <div class="form-group">
                                <label>Metric Tons:</label>
                                <input type="number" name="packing_items[{{ $packingIndex }}][metric_tons]"
                                    class="form-control metric-tons" step="0.01" readonly
                                    value="{{ $packingItem->metric_tons }}">
                            </div>
                        </div>
                        <div class="col-md-1">
                            <div class="form-group">
                                <label>Stuffing (MTs):</label>
                                <input type="number" name="packing_items[{{ $packingIndex }}][stuffing_in_container]"
                                    class="form-control stuffing" step="0.01"
                                    value="{{ $packingItem->stuffing_in_container }}">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>No. of Containers:</label>
                                <input type="number" name="packing_items[{{ $packingIndex }}][no_of_containers]"
                                    class="form-control containers" value="{{ $packingItem->no_of_containers ?? 0 }}">
                            </div>
                        </div>
                       
                       
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Min Weight Empty Bags (g):</label>
                                <input type="number" name="packing_items[{{ $packingIndex }}][min_weight_empty_bags]"
                                    class="form-control min-weight" step="0.01"
                                    value="{{ $packingItem->min_weight_empty_bags }}">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Delivery Date:</label>
                                <input type="date" name="packing_items[{{ $packingIndex }}][delivery_date]" class="form-control"
                                    value="{{ $packingItem->delivery_date ? $packingItem->delivery_date->format('Y-m-d') : '' }}">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Fumigation By:</label>
                                <select name="packing_items[{{ $packingIndex }}][fumigation_company_id][]" class="form-control select2" multiple>
                                    <option value="">Select Fumigation Company</option>
                                    @foreach($fumigationCompanies as $company)
                                        <option value="{{ $company->id }}" {{ in_array($company->id, $packingItem->fumigation_company_id ?? []) ? 'selected' : '' }}>
                                            {{ $company->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- Master Packing Section -->
                        <div class="col-md-12 mt-4">
                            <div class="card border-primary shadow-sm">
                                <div class="header-heading-sepration rounded-0 d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0 font-weight-bold">Master Packing</h6>
                                    <button type="button" class="btn btn-sm btn-primary add-sub-packing-item" data-index="{{ $packingIndex }}">
                                        <i class="ft-plus"></i> Add Master Packing Item
                                    </button>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive special">
                                        <table class="table table-bordered table-sm mb-0">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th class="col-2">Bag Type/Product</th>
                                                    <th>Packing Size (kg)</th>
                                                    <th>No of Primary Bags fit in master bag</th>
                                                    <th>No. of Bags</th>
                                                    <th>Empty Bags</th>
                                                    <th>Extra Bags</th>
                                                    <th>Extra Bags %</th>
                                                    <th>Empty Bag Weight (g)</th>
                                                    <th>Total Bags</th>
                                                    <th class="col-1">Stitching</th>
                                                    <th class="col-1">Bag Color</th>
                                                    <th class="col-1">Brand</th>
                                                    <th class="col-1">Thread Color</th>
                                                    <th>Attachment</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody class="sub-packing-items-container" data-index="{{ $packingIndex }}">
                                                @foreach($packingItem->subItems as $subIndex => $subItem)
                                                    <tr class="sub-packing-item-row">
                                                        <td class="col-2">
                                                            <input type="hidden" class="packing-item-ref" name="packing_items[{{ $packingIndex }}][sub_items][{{ $subIndex }}][job_order_packing_item_id]" value="{{ $subItem->id }}">
                                                            <select name="packing_items[{{ $packingIndex }}][sub_items][{{ $subIndex }}][bag_product_id]" class="form-control form-control-sm select2 sub-bag-product">
                                                                <option value="">Select Bag Type/Product</option>
                                                                @foreach($bagProducts as $bagProduct)
                                                                    <option value="{{ $bagProduct->id }}" {{ $subItem->bag_product_id == $bagProduct->id ? 'selected' : '' }}>{{ $bagProduct->name }}</option>
                                                                @endforeach
                                                            </select>
                                                        </td>
                                                        <td>
                                                            <select name="packing_items[{{ $packingIndex }}][sub_items][{{ $subIndex }}][bag_size_id]" class="form-control form-control-sm select2 sub-bag-size">
                                                                <option value="">Select Size</option>
                                                                @foreach($sizes as $size)
                                                                    <option value="{{ $size->id }}" {{ $subItem->bag_size_id == $size->id ? 'selected' : '' }}>{{ $size->size }}</option>
                                                                @endforeach
                                                            </select>
                                                        </td>
                                                        <td>
                                                            <input type="number" name="packing_items[{{ $packingIndex }}][sub_items][{{ $subIndex }}][no_of_primary_bags]" 
                                                                class="form-control form-control-sm sub-no-of-primary-bags" placeholder="Enter No of Primary Bags fit in master bag" value="{{ $subItem->no_of_primary_bags ?? 0 }}">
                                                        </td>
                                                        <td>
                                                            <input type="number" name="packing_items[{{ $packingIndex }}][sub_items][{{ $subIndex }}][no_of_bags]" 
                                                                class="form-control form-control-sm sub-no-of-bags" readonly placeholder="Auto calc" value="{{ $subItem->no_of_bags }}">
                                                        </td>
                                                        <td>
                                                            <input type="number" name="packing_items[{{ $packingIndex }}][sub_items][{{ $subIndex }}][empty_bags]" 
                                                                class="form-control form-control-sm sub-empty-bags" value="{{ $subItem->empty_bags ?? 0 }}" min="0">
                                                        </td>
                                                        <td>
                                                            <input type="number" name="packing_items[{{ $packingIndex }}][sub_items][{{ $subIndex }}][extra_bags]" 
                                                                class="form-control form-control-sm sub-extra-bags" value="{{ $subItem->extra_bags ?? 0 }}" min="0">
                                                        </td>
                                                        <td>
                                                            <input type="number" name="packing_items[{{ $packingIndex }}][sub_items][{{ $subIndex }}][extra_bags_percentage]" 
                                                                class="form-control form-control-sm sub-extra-bags-percentage" value="{{ $subItem->extra_bags_percentage ?? 0 }}" min="0" step="0.01">
                                                        </td>
                                                        <td>
                                                            <input type="number" name="packing_items[{{ $packingIndex }}][sub_items][{{ $subIndex }}][empty_bag_weight]" 
                                                                class="form-control form-control-sm sub-empty-bag-weight" value="{{ $subItem->empty_bag_weight ?? 0 }}" min="0" step="0.01">
                                                        </td>
                                                        <td>
                                                            <input type="number" name="packing_items[{{ $packingIndex }}][sub_items][{{ $subIndex }}][total_bags]" 
                                                                class="form-control form-control-sm sub-total-bags" readonly value="{{ $subItem->total_bags ?? 0 }}">
                                                        </td>
                                                        <td>
                                                            <select name="packing_items[{{ $packingIndex }}][sub_items][{{ $subIndex }}][stitching_id]" class="form-control form-control-sm select2 sub-stitching">
                                                                <option value="">Select Stitching</option>
                                                                @foreach($stitchings as $stitching)
                                                                    <option value="{{ $stitching->id }}" {{ $subItem->stitching_id == $stitching->id ? 'selected' : '' }}>{{ $stitching->name }}</option>
                                                                @endforeach
                                                            </select>
                                                        </td>
                                                        <td class="col-1">
                                                            <select name="packing_items[{{ $packingIndex }}][sub_items][{{ $subIndex }}][bag_color_id]" class="form-control form-control-sm select2 sub-bag-color">
                                                                <option value="">Select Color</option>
                                                                @foreach($bagColors as $color)
                                                                    <option value="{{ $color->id }}" {{ $subItem->bag_color_id == $color->id ? 'selected' : '' }}>{{ $color->color }}</option>
                                                                @endforeach
                                                            </select>
                                                        </td>
                                                        <td class="col-1">
                                                            <select name="packing_items[{{ $packingIndex }}][sub_items][{{ $subIndex }}][brand_id]" class="form-control form-control-sm select2 sub-brand">
                                                                <option value="">Select Brand</option>
                                                                @foreach($brands as $brand)
                                                                    <option value="{{ $brand->id }}" {{ $subItem->brand_id == $brand->id ? 'selected' : '' }}>{{ $brand->name }}</option>
                                                                @endforeach
                                                            </select>
                                                        </td>
                                                        <td class="col-1">
                                                            <select name="packing_items[{{ $packingIndex }}][sub_items][{{ $subIndex }}][thread_color_id]" class="form-control form-control-sm select2 sub-thread-color">
                                                                <option value="">Select Color</option>
                                                                @foreach($bagColors as $color)
                                                                    <option value="{{ $color->id }}" {{ $subItem->thread_color_id == $color->id ? 'selected' : '' }}>{{ $color->color }}</option>
                                                                @endforeach
                                                            </select>
                                                        </td>
                                                        <td>
                                                            @if($subItem->attachment)
                                                                <a href="{{ asset('storage/' . $subItem->attachment) }}" target="_blank" class="btn btn-sm btn-info">View</a>
                                                            @endif
                                                            <input type="file" name="packing_items[{{ $packingIndex }}][sub_items][{{ $subIndex }}][attachment]" 
                                                                class="form-control form-control-sm sub-attachment">
                                                        </td>
                                                        <td>
                                                            <button type="button" class="btn btn-sm btn-danger remove-sub-packing-item">Remove</button>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-1">
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <button type="button"
                                    class="btn btn-sm btn-danger remove-packing-item form-control">Remove</button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Operational Details -->
        <div class="col-md-12">
            <h6 class="header-heading-sepration">Operational Details</h6>
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Inspection By:</label>
                        <select name="inspection_company_id[]" class="form-control select2" multiple>
                            <option value="">Select Inspection Company</option>
                            @foreach($inspectionCompanies as $company)
                                <option value="{{ $company->id }}" {{ in_array($company->id, json_decode($jobOrder->inspection_company_id, true) ?? []) ? 'selected' : '' }}>
                                    {{ $company->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <!-- <div class="col-md-4">
                    <div class="form-group">
                        <label>Fumigation By:</label>
                        <select name="fumigation_company_id[]" class="form-control select2" multiple>
                            <option value="">Select Fumigation Company</option>
                            @foreach($fumigationCompanies as $company)
                                <option value="{{ $company->id }}" {{ in_array($company->id, json_decode($jobOrder->fumigation_company_id, true) ?? []) ? 'selected' : '' }}>
                                    {{ $company->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div> -->
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Load From/Location:</label>
                        <select name="arrival_locations[]" class="form-control select2" multiple>
                            @foreach($arrivalLocations as $location)
                                <option value="{{ $location->id }}" {{ in_array($location->id, json_decode($jobOrder->arrival_locations, true) ?? []) ? 'selected' : '' }}>
                                    {{ $location->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Loading Date:</label>
                        <input type="date" name="loading_date" class="form-control"
                            value="{{ $jobOrder->loading_date ? $jobOrder->loading_date->format('Y-m-d') : '' }}">
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="form-group">
                        <label>Packing Description:</label>
                        <textarea name="packing_description" class="form-control" rows="4"
                            rows="1">{{ $jobOrder->packing_description }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- Container Protection & Packing Materials -->
        <div class="col-md-12" id="containerProtectionSection" >
            <h6 class="header-heading-sepration d-flex justify-content-between align-items-center">
                Container Protection & Packing Materials
                <button type="button" class="btn btn-sm btn-success" id="addContainerProtectionItem">
                    <i class="ft-plus"></i> Add More
                </button>
            </h6>
            <div id="containerProtectionItems">
                @foreach($jobOrder->containerProtectionItems as $index => $product)
                    <div class="container-protection-item row border-bottom pb-3 mb-3 w-100 mx-auto">
                        <div class="col-md-5">
                            <div class="form-group">
                                <label>Product:</label>
                                <select name="container_protection_items[{{ $index }}][product_id]" class="form-control select2 container-protection-product">
                                    <option value="">Select Product</option>
                                    @foreach($containerProtectionProducts as $containerProduct)
                                        <option value="{{ $containerProduct->id }}" {{ $product->id == $containerProduct->id ? 'selected' : '' }}>{{ $containerProduct->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Quantity Per Container:</label>
                                <input type="number" name="container_protection_items[{{ $index }}][quantity_per_container]" 
                                    class="form-control container-protection-quantity" step="0.01" min="0" placeholder="Enter Quantity" value="{{ $product->pivot->quantity_per_container ?? 0 }}">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <button type="button" class="btn btn-sm btn-danger remove-container-protection-item form-control">
                                    Remove
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="row bottom-button-bar">
        <div class="col-12">
            <a type="button" class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton">Close</a>
            <button type="submit" class="btn btn-primary submitbutton">Update Job Order</button>
        </div>
    </div>
</form>

<!-- Hidden Template for Container Protection & Packing Materials -->
<div class="container-protection-item-template d-none">
    <div class="container-protection-item row border-bottom pb-3 mb-3 w-100 mx-auto">
        <div class="col-md-5">
            <div class="form-group">
                <label>Product:</label>
                <select name="container_protection_items[INDEX][product_id]" class="form-control select2 container-protection-product">
                    <option value="">Select Product</option>
                    @foreach($containerProtectionProducts as $product)
                        <option value="{{ $product->id }}">{{ $product->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label>Quantity Per Container:</label>
                <input type="number" name="container_protection_items[INDEX][quantity_per_container]" 
                    class="form-control container-protection-quantity" step="0.01" min="0" placeholder="Enter Quantity">
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label>&nbsp;</label>
                <button type="button" class="btn btn-sm btn-danger remove-container-protection-item form-control">
                    Remove
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Hidden Template for Sub Packing Item -->
<table class="sub-packing-item-template d-none">
    <tbody>
        <tr class="sub-packing-item-row">
            <td class="col-2">
                <input type="hidden" class="packing-item-ref" name="packing_items[INDEX][sub_items][SUB_INDEX][job_order_packing_item_id]" value="">
                <select name="packing_items[INDEX][sub_items][SUB_INDEX][bag_product_id]" class="form-control form-control-sm select2 sub-bag-product">
                    <option value="">Select Bag Type/Product</option>
                    @foreach($bagProducts as $bagProduct)
                        <option value="{{ $bagProduct->id }}">{{ $bagProduct->name }}</option>
                    @endforeach
                </select>
            </td>
            <td>
                <select name="packing_items[INDEX][sub_items][SUB_INDEX][bag_size_id]" class="form-control form-control-sm select2 sub-bag-size">
                    <option value="">Select Size</option>
                    @foreach($sizes as $size)
                        <option value="{{ $size->id }}">{{ $size->size }}</option>
                    @endforeach
                </select>
            </td>
            <td>
                <input type="number" name="packing_items[INDEX][sub_items][SUB_INDEX][no_of_primary_bags]" 
                    class="form-control form-control-sm sub-no-of-primary-bags" placeholder="Enter No of Primary Bags fit in master bag">
            </td>
            <td>
                <input type="number" name="packing_items[INDEX][sub_items][SUB_INDEX][no_of_bags]" 
                    class="form-control form-control-sm sub-no-of-bags" readonly placeholder="Auto calc">
            </td>
            <td>
                <input type="number" name="packing_items[INDEX][sub_items][SUB_INDEX][empty_bags]" 
                    class="form-control form-control-sm sub-empty-bags" value="0" min="0">
            </td>
            <td>
                <input type="number" name="packing_items[INDEX][sub_items][SUB_INDEX][extra_bags]" 
                    class="form-control form-control-sm sub-extra-bags" value="0" min="0">
            </td>
            <td>
                <input type="number" name="packing_items[INDEX][sub_items][SUB_INDEX][extra_bags_percentage]" 
                    class="form-control form-control-sm sub-extra-bags-percentage" value="0" min="0" step="0.01">
            </td>
            <td>
                <input type="number" name="packing_items[INDEX][sub_items][SUB_INDEX][empty_bag_weight]" 
                    class="form-control form-control-sm sub-empty-bag-weight" value="0" min="0" step="0.01">
            </td>
            <td>
                <input type="number" name="packing_items[INDEX][sub_items][SUB_INDEX][total_bags]" 
                    class="form-control form-control-sm sub-total-bags" readonly value="0">
            </td>
            <td>
                <select name="packing_items[INDEX][sub_items][SUB_INDEX][stitching_id]" class="form-control form-control-sm select2 sub-stitching">
                    <option value="">Select Stitching</option>
                    @foreach($stitchings as $stitching)
                        <option value="{{ $stitching->id }}">{{ $stitching->name }}</option>
                    @endforeach
                </select>
            </td>
            <td class="col-1">
                <select name="packing_items[INDEX][sub_items][SUB_INDEX][bag_color_id]" class="form-control form-control-sm select2 sub-bag-color">
                    <option value="">Select Color</option>
                    @foreach($bagColors as $color)
                        <option value="{{ $color->id }}">{{ $color->color }}</option>
                    @endforeach
                </select>
            </td>
            <td class="col-1">
                <select name="packing_items[INDEX][sub_items][SUB_INDEX][brand_id]" class="form-control form-control-sm select2 sub-brand">
                    <option value="">Select Brand</option>
                    @foreach($brands as $brand)
                        <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                    @endforeach
                </select>
            </td>
            <td class="col-1">
                <select name="packing_items[INDEX][sub_items][SUB_INDEX][thread_color_id]" class="form-control form-control-sm select2 sub-thread-color">
                    <option value="">Select Color</option>
                    @foreach($bagColors as $color)
                        <option value="{{ $color->id }}">{{ $color->color }}</option>
                    @endforeach
                </select>
            </td>
            <td>
                <input type="file" name="packing_items[INDEX][sub_items][SUB_INDEX][attachment]" 
                    class="form-control form-control-sm sub-attachment">
            </td>
            <td>
                <button type="button" class="btn btn-sm btn-danger remove-sub-packing-item">Remove</button>
            </td>
        </tr>
    </tbody>
</table>

<script>
    $(document).ready(function () {
        // Remove all existing event handlers to prevent multiple bindings when modal is loaded multiple times
        // Clean up both create and edit handlers to prevent duplication when switching between modals
        $(document).off('.jobOrderEdit .jobOrderCreate');
        $('#productSelect').off('.jobOrderEdit .jobOrderCreate');
        $('input[name="job_order_date"]').off('.jobOrderEdit .jobOrderCreate');
        
        // Destroy any existing Select2 instances to prevent multiple initializations
        $('.select2').each(function() {
            if ($(this).data('select2')) {
                $(this).select2('destroy');
            }
        });
        
        // Initialize Select2 for all multi-selects (excluding template)
        $('.select2').not('.sub-packing-item-template .select2').select2();
        
        // Initialize Select2 for existing sub items in tables with proper dropdownParent
        $('.sub-packing-items-container').each(function() {
            var tableContainer = $(this).closest('.table-responsive');
            $(this).find('select.select2').each(function() {
                var $select = $(this);
                // Destroy existing select2 if any
                if ($select.data('select2')) {
                    $select.select2('destroy');
                }
                // Initialize with proper dropdownParent
                $select.select2({
                    dropdownParent: tableContainer.length ? tableContainer : $('body')
                });
            });
        });

        // Initialize state for edit view
        if($('#exportOrderSelect').val()) {
            $('#exportOrderSelect').trigger('change');
            
            $('#mainProductSelect').prop('disabled', true);
            $('#mainProductSelect').next('.select2-container').css('pointer-events', 'none');
            if($('#hidden_product_id').length === 0) {
                $('<input>').attr({
                    type: 'hidden',
                    id: 'hidden_product_id',
                    name: 'product_id',
                    value: $('#mainProductSelect').val()
                }).appendTo('form#ajaxSubmit');
            }
        }

        let isInitialLoad = true;
        // Export Order selection change
        $('#exportOrderSelect').off('change.jobOrderEdit').on('change.jobOrderEdit', function() {
            let id = $(this).val();
            if(!id) {
                $('#mainProductSelect').prop('disabled', false);
                $('#mainProductSelect').next('.select2-container').css('pointer-events', '');
                $('#hidden_product_id').remove();
                return;
            }

            $.get('{{ url("production/get-export-order-details") }}/' + id, function(data) {
                if (data.ref_no) {
                    $('input[name="ref_no"]').val(data.ref_no);
                }
                if (data.other_specifications) {
                    $('textarea[name="other_specifications"]').val(data.other_specifications);
                }

                // Show quantity info
                let totalMt = data.total_eo_mt;
                let consumedMt = data.consumed_mt;
                let remainingMt = data.remaining_mt;
                
                let alertHtml = `
                    <div class="mb-2" style="color: #d9534f; font-weight: 500;">
                        <i class="ft-info mr-1"></i>
                        Export Order Quantity Info: Total: ${totalMt} MT | Consumed: ${consumedMt} MT | Remaining: ${remainingMt} MT
                    </div>
                `;
                $('#export-order-quantity-info').html(alertHtml).show();

                // Set and lock product
                if (data.product_id) {
                    $('#mainProductSelect').val(data.product_id).trigger('change');
                    $('#mainProductSelect').prop('disabled', true);
                    $('#mainProductSelect').next('.select2-container').css('pointer-events', 'none');
                }
                
                if($('#hidden_product_id').length === 0) {
                    $('<input>').attr({
                        type: 'hidden',
                        id: 'hidden_product_id',
                        name: 'product_id',
                        value: data.product_id
                    }).appendTo('form#ajaxSubmit');
                } else {
                    $('#hidden_product_id').val(data.product_id);
                }

                setTimeout(function() {
                    buildSpecsTable(data.specifications);
                }, 900);

                if (!isInitialLoad && data.packing_items && data.packing_items.length > 0) {
                    addPackingRowsFromExportOrder(data.packing_items);
                }
                isInitialLoad = false;
            }).fail(function(xhr) {
                console.error('Export Order Details Error:', xhr.status, xhr.responseText);
                alert('Failed to load export order details. Please check console.');
            });
        });

        function buildSpecsTable(specs) {
            if(!specs || specs.length === 0) return;
            
            let html = '<div class="table-responsive"><table class="table table-bordered table-striped"><thead class="thead-dark"><tr><th width="40%">Specification Name</th><th width="30%">Value</th><th width="30%">UOM</th></tr></thead><tbody>';
            specs.forEach(function(spec, idx) {
                let specName = spec.product_slab_type ? spec.product_slab_type.name : spec.spec_name;
                let uom = spec.product_slab_type ? spec.product_slab_type.qc_symbol : spec.uom;
                let valueType = spec.value_type || 'min';

                html += `<tr>
                    <td>
                        <strong>${specName}</strong>
                        <input type="hidden" name="specifications[${idx}][product_slab_type_id]" value="${spec.product_slab_type_id}">
                        <input type="hidden" name="specifications[${idx}][spec_name]" value="${spec.spec_name}">
                        <input type="hidden" name="specifications[${idx}][uom]" value="${spec.uom}">
                    </td>
                    <td>
                        <fieldset>
                            <div class="input-group">
                                <input type="text" name="specifications[${idx}][spec_value]" value="${spec.spec_value || 0}" class="form-control form-control-sm spec-value-input" placeholder="Enter value">
                                <div class="input-group-prepend">
                                    <button class="btn btn-secondary" type="button">${uom || 'N/A'}</button>
                                </div>
                            </div>
                        </fieldset>
                    </td>
                    <td>
                        <select name="specifications[${idx}][value_type]" class="form-control">
                            <option value="min" ${valueType === 'min' ? 'selected' : ''}>Minimum</option>
                            <option value="max" ${valueType === 'max' ? 'selected' : ''}>Maximum</option>
                        </select>
                    </td>
                </tr>`;
            });
            html += '</tbody></table></div>';
            $('#productSpecs').html(html);
        }

        function addPackingRowsFromExportOrder(items) {
            let container = $('#packingItems');
            let templateRow = container.find('.packing-item').first().clone();
            
            container.empty();

            items.forEach(function(item, index) {
                let row = templateRow.clone();
                row.find('.select2-container').remove();
                row.find('select').removeClass('select2-hidden-accessible').show();
                row.find('option').removeAttr('data-select2-id');
                
                row.attr('data-index', index);

                // Update names
                row.find('input, select, textarea').each(function() {
                    let name = $(this).attr('name');
                    if(name) {
                        name = name.replace(/\[\d+\]/, `[${index}]`);
                        $(this).attr('name', name);
                    }
                });

                // Helper to find bag product ID by name
                function findBagProductIdByName(name, selectElement) {
                    if (!name) return null;
                    let id = null;
                    selectElement.find('option').each(function() {
                        if ($(this).text().trim().toLowerCase() === name.trim().toLowerCase()) {
                            id = $(this).val();
                            return false;
                        }
                    });
                    return id;
                }

                // Fill values
                row.find(`select[name="packing_items[${index}][brand_id]"]`).val(item.brand_id);
                
                // Bag Type/Product mapping
                let bagProductSelect = row.find(`select[name="packing_items[${index}][bag_product_id]"]`);
                let mappedId = findBagProductIdByName(item.bag_type_name, bagProductSelect);
                if (mappedId) {
                    bagProductSelect.val(mappedId);
                } else {
                    bagProductSelect.val(item.bag_product_id); // Fallback to ID
                }

                row.find(`select[name="packing_items[${index}][bag_condition_id]"]`).val(item.bag_condition_id);
                row.find(`select[name="packing_items[${index}][bag_color_id]"]`).val(item.bag_color_id);
                row.find(`select[name="packing_items[${index}][thread_color_id]"]`).val(item.thread_color_id);
                row.find(`select[name="packing_items[${index}][stitching_id]"]`).val(item.stitching_id);
                
                row.find(`input[name="packing_items[${index}][bag_size]"]`).val(item.bag_size);
                row.find(`input[name="packing_items[${index}][no_of_bags]"]`).val(item.no_of_bags);
                row.find(`input[name="packing_items[${index}][extra_bags]"]`).val(item.extra_bags);
                row.find(`input[name="packing_items[${index}][extra_bags_percentage]"]`).val(item.extra_bags_percentage);
                row.find(`input[name="packing_items[${index}][empty_bags]"]`).val(item.empty_bags);
                row.find(`input[name="packing_items[${index}][total_bags]"]`).val(item.total_bags);
                row.find(`input[name="packing_items[${index}][total_kgs]"]`).val(item.total_kgs);
                row.find(`input[name="packing_items[${index}][metric_tons]"]`).val(item.metric_tons);
                row.find(`input[name="packing_items[${index}][stuffing_in_container]"]`).val(item.stuffing_in_container);
                row.find(`input[name="packing_items[${index}][no_of_containers]"]`).val(item.no_of_containers);
                row.find(`input[name="packing_items[${index}][min_weight_empty_bags]"]`).val(item.min_weight_empty_bags);
                
                // Clear sub items container
                let subContainer = row.find('.sub-packing-items-container');
                subContainer.attr('data-index', index);
                subContainer.empty();
                row.find('.add-sub-packing-item').attr('data-index', index);

                container.append(row);
                
                // Clean up any select2 leftovers and re-initialize
                row.find('.select2-container').remove();
                row.find('select').removeClass('select2-hidden-accessible').removeAttr('data-select2-id').show();
                row.find('select.select2').select2({width: '100%'});

                // Trigger percentage calculation manually
                if (item.no_of_bags > 0 && item.extra_bags > 0) {
                    let perc = (item.extra_bags / item.no_of_bags) * 100;
                    row.find(`input[name="packing_items[${index}][extra_bags_percentage]"]`).val(perc.toFixed(2));
                }
                
                // Trigger total bags calculation
                row.find('.no-of-bags').trigger('input');

                // Inside sub items
                if(item.sub_items && item.sub_items.length > 0) {
                    item.sub_items.forEach(function(sub, sIdx) {
                        // Replace placeholders securely - SUB_INDEX first so INDEX doesn't partially match it!
                        subRowHtml = subRowHtml.replace(/\[SUB_INDEX\]/g, '[' + sIdx + ']').replace(/\[INDEX\]/g, '[' + index + ']');
                        let subRow = $(subRowHtml);
                        
                        // Sub Bag Type/Product mapping
                        let subBagProductSelect = subRow.find(`select[name="packing_items[${index}][sub_items][${sIdx}][bag_product_id]"]`);

                        let subMappedId = findBagProductIdByName(sub.bag_type_name, subBagProductSelect);
                        if (subMappedId) {
                            subBagProductSelect.val(subMappedId);
                        } else {
                            subBagProductSelect.val(sub.bag_product_id);
                        }

                        subRow.find(`select[name="packing_items[${index}][sub_items][${sIdx}][bag_size_id]"]`).val(sub.bag_size_id);
                        subRow.find(`select[name="packing_items[${index}][sub_items][${sIdx}][stitching_id]"]`).val(sub.stitching_id);
                        subRow.find(`select[name="packing_items[${index}][sub_items][${sIdx}][bag_color_id]"]`).val(sub.bag_color_id);
                        subRow.find(`select[name="packing_items[${index}][sub_items][${sIdx}][brand_id]"]`).val(sub.brand_id);
                        subRow.find(`select[name="packing_items[${index}][sub_items][${sIdx}][thread_color_id]"]`).val(sub.thread_color_id);
                        
                        subRow.find(`input[name="packing_items[${index}][sub_items][${sIdx}][no_of_primary_bags]"]`).val(sub.no_of_primary_bags);
                        subRow.find(`input[name="packing_items[${index}][sub_items][${sIdx}][no_of_bags]"]`).val(sub.no_of_bags);
                        subRow.find(`input[name="packing_items[${index}][sub_items][${sIdx}][empty_bags]"]`).val(sub.empty_bags);
                        subRow.find(`input[name="packing_items[${index}][sub_items][${sIdx}][extra_bags]"]`).val(sub.extra_bags);
                        subRow.find(`input[name="packing_items[${index}][sub_items][${sIdx}][extra_bags_percentage]"]`).val(sub.extra_bags_percentage);
                        subRow.find(`input[name="packing_items[${index}][sub_items][${sIdx}][empty_bag_weight]"]`).val(sub.empty_bag_weight);
                        subRow.find(`input[name="packing_items[${index}][sub_items][${sIdx}][total_bags]"]`).val(sub.total_bags);

                        subContainer.append(subRow);
                        subRow.find('select.select2').select2({width: '100%'});
                        
                        // Trigger sub total bags calculation
                        subRow.find('.sub-no-of-bags').trigger('input');
                    });
                }
            });
            reindexPackingItems();
        }

        // Product selection change
        $('#productSelect').off('change.jobOrderEdit').on('change.jobOrderEdit', function () {
            var productId = $(this).val();
            if (productId) {
                $.get('{{ route("get.product_specs", "") }}/' + productId, function (data) {
                    $('#productSpecs').html(data);
                    $('#specificationsSection').show();
                });
            } else {
                $('#specificationsSection').hide();
            }
        });

        // Add more packing items using clone
        $(document).off('click.jobOrderEdit', '#addPackingItem').on('click.jobOrderEdit', '#addPackingItem', function (e) {
            e.preventDefault();
            addNewPackingItem();
        });

        // Add new packing item function
        var isAddingItem = false;
        function addNewPackingItem() {
            if (isAddingItem) return;
            isAddingItem = true;
            
            var firstItem = $('.packing-item').first();
            if (!firstItem.length) {
                isAddingItem = false;
                return;
            }

            // Capture original values before cloning
            var originalValues = {};
            firstItem.find('select').each(function () {
                var $select = $(this);
                originalValues[$select.attr('name')] = $select.val();
            });

            // Clone the item
            var newItem = firstItem.clone(); 
            
            // Clean up select2 from clone
            newItem.find('.select2-container').remove();
            newItem.find('select').removeClass('select2-hidden-accessible').removeAttr('data-select2-id').show();
            newItem.find('option').removeAttr('data-select2-id');
            // Update indexes
            var newIndex = $('.packing-item').length;
            newItem.find('input, select').each(function () {
                var name = $(this).attr('name');
                if (name) {
                    name = name.replace(/\[\d+\]/g, '[' + newIndex + ']'); // Use global flag for nested replaces
                    $(this).attr('name', name);
                    $(this).val(''); // Clear values
                }
            });

            // Update data-index for sub items container and button
            newItem.find('.sub-packing-items-container').attr('data-index', newIndex);
            newItem.find('.add-sub-packing-item').attr('data-index', newIndex);
            
            // Clear sub items container
            newItem.find('.sub-packing-items-container').empty();

            // Clear specific values and IDs
            newItem.find('input[type="hidden"].packing-item-ref').remove(); // Important: remove the ID of the cloned item
            newItem.find('.bag-size, .no-of-bags').val('');
            newItem.find('.extra-bags, .empty-bags, .containers, .stuffing, .extra-bags-percentage, .min-weight').val('0');
            newItem.find('.total-bags, .total-kgs, .metric-tons').val('0');

            // Handle Select2 in the new item
            newItem.find('select').each(function () {
                var $select = $(this);
                // Remove existing Select2 structure if any was cloned
                $select.siblings('.select2-container').remove();
                $select.next('.select2-container').remove();
                $select.removeClass('select2-hidden-accessible').show();
                $select.prop('selectedIndex', 0);
                $select.val('');
            });

            // Add to container
            $('#packingItems').append(newItem);

            // Re-initialize Select2 for the cloned item
            newItem.find('select.select2').each(function () {
                $(this).select2();
            });

            // Re-initialize Select2 for the original item (if it was destroyed or affected)
            firstItem.find('select.select2').each(function () {
                if (!$(this).data('select2')) {
                    $(this).select2();
                }
            });
            
            isAddingItem = false;
        }

        // Remove packing item
        $(document).off('click.jobOrderEdit', '.remove-packing-item').on('click.jobOrderEdit', '.remove-packing-item', function () {
            if ($('.packing-item').length > 1) {
                $(this).closest('.packing-item').remove();
                // Re-index remaining items
                reindexPackingItems();
            }
        });

        // Add Sub Packing Item
        $(document).off('click.jobOrderEdit', '.add-sub-packing-item').on('click.jobOrderEdit', '.add-sub-packing-item', function (e) {
            e.preventDefault();
            var packingItem = $(this).closest('.packing-item');
            // Get packing index from first input/select name attribute
            var firstInput = packingItem.find('input[name*="packing_items"], select[name*="packing_items"]').first();
            var nameAttr = firstInput.attr('name');
            var packingIndexMatch = nameAttr ? nameAttr.match(/packing_items\[(\d+)\]/) : null;
            var packingIndex = packingIndexMatch ? packingIndexMatch[1] : packingItem.index();
            
            var container = packingItem.find('.sub-packing-items-container'); // This is tbody
            var templateRow = $('.sub-packing-item-template').find('.sub-packing-item-row').first();
            
            if (!templateRow.length) {
                console.error('Template row not found!');
                return;
            }
            
            var subIndex = container.find('.sub-packing-item-row').length;
            
            // Clone the tr from template
            var newRow = templateRow.clone();
            
            // Replace INDEX and SUB_INDEX in all inputs/selects
            newRow.find('input, select').each(function () {
                var name = $(this).attr('name');
                if (name) {
                    name = name.replace(/\[SUB_INDEX\]/g, '[' + subIndex + ']');
                    name = name.replace(/\[INDEX\]/g, '[' + packingIndex + ']');
                    $(this).attr('name', name);
                }
            });
            
            // Clear values
            newRow.find('input[type="text"], input[type="number"]').not('[readonly]').val('');
            // Default sub item numeric fields to 0
            newRow.find('.sub-empty-bags, .sub-extra-bags, .sub-extra-bags-percentage, .sub-empty-bag-weight, .sub-no-of-primary-bags').val('0');
            newRow.find('input[type="number"][readonly]').val('0');
            newRow.find('select').prop('selectedIndex', 0);
            newRow.find('input[type="file"]').val('');
            
            // Append tr to tbody
            container.append(newRow);
            
            // Initialize Select2 for new selects
            newRow.find('select.select2').each(function() {
                var $select = $(this);
                // Remove any existing Select2 initialization
                if ($select.data('select2')) {
                    $select.select2('destroy');
                }
                // Initialize Select2
                $select.select2({
                    dropdownParent: $select.closest('.table-responsive').length ? $select.closest('.table-responsive') : $('body')
                });
            });
            
            // Calculate no of bags based on packing item's total kgs
            calculateSubItemNoOfBags(newRow, packingItem);
        });

        // Remove Sub Packing Item
        $(document).off('click.jobOrderEdit', '.remove-sub-packing-item').on('click.jobOrderEdit', '.remove-sub-packing-item', function () {
            $(this).closest('.sub-packing-item-row').remove();
        });

        // Calculate No. of Bags for sub item when no_of_primary_bags changes
        $(document).off('input.jobOrderEdit', '.sub-no-of-primary-bags').on('input.jobOrderEdit', '.sub-no-of-primary-bags', function () {
            var subRow = $(this).closest('.sub-packing-item-row');
            var packingItem = subRow.closest('.packing-item');
            calculateSubItemNoOfBags(subRow, packingItem);
        });

        // Calculate No. of Bags for sub item when packing item's no_of_bags changes
        $(document).off('input.jobOrderEdit', '.no-of-bags').on('input.jobOrderEdit', '.no-of-bags', function () {
            var packingItem = $(this).closest('.packing-item');
            packingItem.find('.sub-packing-item-row').each(function () {
                calculateSubItemNoOfBags($(this), packingItem);
            });
        });

        // Calculate total bags for sub item
        $(document).off('input.jobOrderEdit', '.sub-no-of-bags, .sub-empty-bags, .sub-extra-bags').on('input.jobOrderEdit', '.sub-no-of-bags, .sub-empty-bags, .sub-extra-bags', function () {
            var subRow = $(this).closest('.sub-packing-item-row');
            var noOfBags = parseInt(subRow.find('.sub-no-of-bags').val()) || 0;
            var emptyBags = parseInt(subRow.find('.sub-empty-bags').val()) || 0;
            var extraBags = parseInt(subRow.find('.sub-extra-bags').val()) || 0;

            // If no-of-bags changed, update extra bags from percentage
            if ($(this).hasClass('sub-no-of-bags')) {
                var percentageVal = subRow.find('.sub-extra-bags-percentage').val();
                if (percentageVal !== '' && noOfBags > 0) {
                    var percentage = parseFloat(percentageVal) || 0;
                    extraBags = Math.round((percentage / 100) * noOfBags);
                    subRow.find('.sub-extra-bags').val(extraBags);
                }
            }

            // Update percentage only if extra-bags was changed directly
            if ($(this).hasClass('sub-extra-bags') && noOfBags > 0 && !$(this).hasClass('is-calculating')) {
                if ($(this).val() === '') {
                    subRow.find('.sub-extra-bags-percentage').val('');
                } else {
                    var percentage = (extraBags / noOfBags) * 100;
                    subRow.find('.sub-extra-bags-percentage').val(percentage.toFixed(2));
                }
            }

            var totalBags = noOfBags + emptyBags + extraBags;
            subRow.find('.sub-total-bags').val(totalBags);
        });

        // Calculate extra bags for sub item from percentage
        $(document).off('input.jobOrderEdit', '.sub-extra-bags-percentage').on('input.jobOrderEdit', '.sub-extra-bags-percentage', function () {
            var subRow = $(this).closest('.sub-packing-item-row');
            var noOfBags = parseInt(subRow.find('.sub-no-of-bags').val()) || 0;
            var val = $(this).val();

            if (val === '') {
                subRow.find('.sub-extra-bags').addClass('is-calculating').val('').trigger('input').removeClass('is-calculating');
                return;
            }

            var percentage = parseFloat(val) || 0;
            if (noOfBags > 0) {
                var extraBags = Math.round((percentage / 100) * noOfBags);
                subRow.find('.sub-extra-bags').addClass('is-calculating').val(extraBags).trigger('input').removeClass('is-calculating');
            }
        });

        // Function to calculate no of bags from packing item's no_of_bags / no_of_primary_bags
        function calculateSubItemNoOfBags(subRow, packingItem) {
            var noOfBagsPrimary = parseInt(packingItem.find('.no-of-bags').val()) || 0;
            var noOfPrimaryBags = parseInt(subRow.find('.sub-no-of-primary-bags').val()) || 0;

            if (noOfBagsPrimary > 0 && noOfPrimaryBags > 0) {
                var noOfBags = Math.floor(noOfBagsPrimary / noOfPrimaryBags);
                subRow.find('.sub-no-of-bags').val(noOfBags);
                // Trigger total bags calculation
                subRow.find('.sub-no-of-bags').trigger('input');
            } else {
                subRow.find('.sub-no-of-bags').val('0');
            }
        }

        // Auto-calculate totals
        $(document).off('input.jobOrderEdit', '.bag-size, .no-of-bags, .extra-bags, .empty-bags').on('input.jobOrderEdit', '.bag-size, .no-of-bags, .extra-bags, .empty-bags', function () {
            var item = $(this).closest('.packing-item');

            // If no-of-bags changed, update extra-bags from percentage if percentage exists
            if ($(this).hasClass('no-of-bags')) {
                var noOfBags = parseInt($(this).val()) || 0;
                var percentageVal = item.find('.extra-bags-percentage').val();
                if (percentageVal !== '' && noOfBags > 0) {
                    var percentage = parseFloat(percentageVal) || 0;
                    var extraBags = Math.round((percentage / 100) * noOfBags);
                    item.find('.extra-bags').addClass('is-calculating').val(extraBags).removeClass('is-calculating');
                }
            }

            // If extra-bags changed, update percentage
            if ($(this).hasClass('extra-bags') && !$(this).hasClass('is-calculating')) {
                var noOfBags = parseInt(item.find('.no-of-bags').val()) || 0;
                var extraBags = parseInt($(this).val());
                if (noOfBags > 0) {
                    if ($(this).val() === '') {
                        item.find('.extra-bags-percentage').val('');
                    } else {
                        var percentage = (extraBags / noOfBags) * 100;
                        item.find('.extra-bags-percentage').val(percentage.toFixed(2));
                    }
                }
            }

            calculateTotals(item);
        });

        // Calculate extra bags from percentage
        $(document).off('input.jobOrderEdit', '.extra-bags-percentage').on('input.jobOrderEdit', '.extra-bags-percentage', function () {
            var item = $(this).closest('.packing-item');
            var noOfBags = parseInt(item.find('.no-of-bags').val()) || 0;
            var val = $(this).val();

            if (val === '') {
                item.find('.extra-bags').addClass('is-calculating').val('').trigger('input').removeClass('is-calculating');
                return;
            }

            var percentage = parseFloat(val) || 0;
            if (noOfBags > 0) {
                var extraBags = Math.round((percentage / 100) * noOfBags);
                item.find('.extra-bags').addClass('is-calculating').val(extraBags).trigger('input').removeClass('is-calculating');
            }
        });

        // Update master packing items when packing item's bag type changes
        $(document).off('change.jobOrderEdit', 'select[name*="packing_items"][name*="[bag_type_id]"]:not([name*="[sub_items]"])').on('change.jobOrderEdit', 'select[name*="packing_items"][name*="[bag_type_id]"]:not([name*="[sub_items]"])', function () {
            var item = $(this).closest('.packing-item');
            // Trigger recalculation of totals which will update sub items
            calculateTotals(item);
        });

        // Auto-calculate stuffing based on metric tons and containers
        $(document).off('input.jobOrderEdit', '.metric-tons, .containers').on('input.jobOrderEdit', '.metric-tons, .containers', function () {
            var item = $(this).closest('.packing-item');
            calculateStuffing(item);
        });

        // Auto-calculate containers based on metric tons and stuffing
        $(document).off('input.jobOrderEdit', '.metric-tons, .stuffing').on('input.jobOrderEdit', '.metric-tons, .stuffing', function () {
            var item = $(this).closest('.packing-item');
            calculateContainers(item);
        });

        function calculateStuffing(item) {
            var metricTons = parseFloat(item.find('.metric-tons').val()) || 0;
            var containers = parseInt(item.find('.containers').val()) || 0;

            if (containers > 0 && metricTons > 0) {
                var stuffingPerContainer = metricTons / containers;
                item.find('.stuffing').val(stuffingPerContainer.toFixed(3));
            }
        }

        function calculateContainers(item) {
            var metricTons = parseFloat(item.find('.metric-tons').val()) || 0;
            var stuffing = parseFloat(item.find('.stuffing').val()) || 0;

            if (stuffing > 0 && metricTons > 0) {
                var containers = Math.ceil(metricTons / stuffing);
                item.find('.containers').val(containers);
            }
        }

        // Modified existing calculateTotals function to include stuffing calculation
        function calculateTotals(item) {
            var bagSize = parseFloat(item.find('.bag-size').val()) || 0;
            var noOfBags = parseInt(item.find('.no-of-bags').val()) || 0;
            var extraBags = parseInt(item.find('.extra-bags').val()) || 0;
            var emptyBags = parseInt(item.find('.empty-bags').val()) || 0;

            // Calculate totals
            var totalBags = noOfBags + extraBags + emptyBags;
            var totalKgs = noOfBags * bagSize;
            var metricTons = totalKgs / 1000;

            // Update fields
            item.find('.total-bags').val(totalBags);
            item.find('.total-kgs').val(totalKgs.toFixed(2));
            item.find('.metric-tons').val(metricTons.toFixed(3));

            // Auto-calculate stuffing if containers are specified
            var containers = parseInt(item.find('.containers').val()) || 0;
            if (containers > 0) {
                calculateStuffing(item);
            }
            
            // Update all master packing items (sub items) when total kgs changes
            item.find('.sub-packing-item-row').each(function() {
                calculateSubItemNoOfBags($(this), item);
            });
        }


        function calculateTotalsbk(item) {
            var bagSize = parseFloat(item.find('.bag-size').val()) || 0;
            var noOfBags = parseInt(item.find('.no-of-bags').val()) || 0;
            var extraBags = parseInt(item.find('.extra-bags').val()) || 0;
            var emptyBags = parseInt(item.find('.empty-bags').val()) || 0;

            // Calculate totals
            var totalBags = noOfBags + extraBags + emptyBags;
            var totalKgs = noOfBags * bagSize;
            var metricTons = totalKgs / 1000;

            // Update fields
            item.find('.total-bags').val(totalBags);
            item.find('.total-kgs').val(totalKgs.toFixed(2));
            item.find('.metric-tons').val(metricTons.toFixed(3));
        }

        function reindexPackingItems() {
            $('.packing-item').each(function (index) {
                var packingItem = $(this);
                
                // Update data-index for sub items container and button
                packingItem.find('.sub-packing-items-container').attr('data-index', index);
                packingItem.find('.add-sub-packing-item').attr('data-index', index);
                
                // Get old index from first input/select
                var firstInput = packingItem.find('input[name*="packing_items"], select[name*="packing_items"]').first();
                var oldName = firstInput.attr('name');
                var oldIndexMatch = oldName ? oldName.match(/packing_items\[(\d+)\]/) : null;
                var oldIndex = oldIndexMatch ? oldIndexMatch[1] : null;
                
                if (oldIndex !== null && oldIndex != index) {
                    // Update all input/select names (including sub items)
                    packingItem.find('input, select').each(function () {
                        var name = $(this).attr('name');
                        if (name && name.includes('packing_items[' + oldIndex + ']')) {
                            // Replace only the packing item index, keep sub item index as is
                            name = name.replace('packing_items[' + oldIndex + ']', 'packing_items[' + index + ']');
                            $(this).attr('name', name);
                        }
                    });
                }
            });
        }

        // Initial calculation for all items
        $('.packing-item').each(function () {
            calculateTotals($(this));
        });

        // Container Protection & Packing Materials
        // Add Container Protection Item
        $(document).off('click.jobOrderEdit', '#addContainerProtectionItem').on('click.jobOrderEdit', '#addContainerProtectionItem', function (e) {
            e.preventDefault();
            var template = $('.container-protection-item-template').find('.container-protection-item').first();
            var newItem = template.clone(true, true); // Deep clone

            // Get current index
            var currentIndex = $('#containerProtectionItems').find('.container-protection-item').length;

            // Destroy any existing Select2 instances in cloned item
            newItem.find('select.select2').each(function () {
                var $select = $(this);
                if ($select.data('select2')) {
                    $select.select2('destroy');
                }
                // Remove Select2 containers
                $select.siblings('.select2-container').remove();
                $select.show().removeClass('select2-hidden-accessible');
            });

            // Update index in all inputs/selects
            newItem.find('input, select').each(function () {
                var name = $(this).attr('name');
                if (name) {
                    name = name.replace(/\[INDEX\]/g, '[' + currentIndex + ']');
                    $(this).attr('name', name);
                }
            });

            // Clear values
            newItem.find('input[type="number"]').val('');
            newItem.find('select').prop('selectedIndex', 0);

            // Show section if hidden
            $('#containerProtectionSection').show();

            // Append to container
            $('#containerProtectionItems').append(newItem);

            // Initialize Select2 for new selects after a small delay to ensure DOM is ready
            setTimeout(function () {
                newItem.find('select.select2').each(function () {
                    var $select = $(this);
                    // Make sure it's not already initialized
                    if (!$select.data('select2')) {
                        $select.select2();
                    }
                });
            }, 10);
        });

        // Remove Container Protection Item
        $(document).off('click.jobOrderEdit', '.remove-container-protection-item').on('click.jobOrderEdit', '.remove-container-protection-item', function () {
            $(this).closest('.container-protection-item').remove();

            // Hide section if no items left
            if ($('#containerProtectionItems').find('.container-protection-item').length === 0) {
                $('#containerProtectionSection').hide();
            }

            // Re-index remaining items
            reindexContainerProtectionItems();
        });

        // Re-index container protection items
        function reindexContainerProtectionItems() {
            $('#containerProtectionItems').find('.container-protection-item').each(function (index) {
                $(this).find('input, select').each(function () {
                    var name = $(this).attr('name');
                    if (name) {
                        // Extract current index and replace with new index
                        name = name.replace(/container_protection_items\[\d+\]/, 'container_protection_items[' + index + ']');
                        $(this).attr('name', name);
                    }
                });
            });
        }
    });
</script>