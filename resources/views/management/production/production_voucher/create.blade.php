@extends('management.layouts.master')
@section('title')
    Create Production Voucher
@endsection
@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">Create Production Voucher</h4>
                    </div>
                    <div class="card-body">

                        <form action="{{ route('production-voucher.store') }}" method="POST" id="ajaxSubmit"
                            autocomplete="off">
                            @csrf
                            <input type="hidden" id="listRefresh" value="{{ route('get.production-voucher') }}" />

                            <div class="row form-mar">
                                <!-- Basic Information -->
                                <div class="col-md-12">
                                    <h6 class="header-heading-sepration">Production Voucher</h6>
                                    <div class="row">
                                        <div class="col-md-3">
                                            <fieldset>
                                                <label>Prod. No:</label>
                                                <div class="input-group">
                                                    <div class="input-group-prepend">
                                                        <button class="btn btn-primary" type="button">Prod. No</button>
                                                    </div>
                                                    <input type="text" readonly name="prod_no" class="form-control">
                                                </div>
                                            </fieldset>
                                        </div>

                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label>Prod. Date:</label>
                                                <input type="date" name="prod_date" class="form-control"
                                                    value="{{ date('Y-m-d') }}" required>
                                            </div>
                                        </div>

                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label>Location:</label>
                                                <select name="location_id" id="location_id"
                                                    onchange="loadData();loadCommoditiesByLocation();loadSubLocationByLocation();filterAllInputLocations();"
                                                    class="form-control select2" required>
                                                    <option value="">Select Location</option>
                                                    @foreach ($companyLocations as $location)
                                                        <option value="{{ $location->id }}">{{ $location->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label>Sub Location:</label>
                                                <select name="sub_location_id" onchange="loadPlantsBySubLocation();"
                                                    id="sub_location_id" class="form-control select2" required>
                                                    <option value="">Select Sub Location</option>
                                                    <!-- Plants will be loaded dynamically based on location -->
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label>Plant:</label>
                                                <select name="plant_id" id="plant_id"
                                                    onchange="loadlabourCharges();loadMachinesByPlant();"
                                                    class="form-control select2" required>
                                                    <option value="">Select Plant</option>
                                                    <!-- Plants will be loaded dynamically based on location -->
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label>Head Product:</label>
                                                <select name="product_id" id="product_id" class="form-control select2"
                                                    required onchange="loadJobOrdersByLocation();loadHeadProductsData();loadB2ProductsData();autoSelectByProduct();">
                                                    <option value="">Select Head Product</option>
                                                    @foreach(getFinishGoodProducts() as $product)
                                                        <option value="{{ $product->id }}"
                                                            data-parent-id="{{ $product->parent_id ?? $product->id }}"
                                                            data-parent-name="{{ $product->parent ? $product->parent->name : $product->name }}">
                                                            {{ $product->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label>Job Ord. No:</label>
                                                <select name="job_order_id[]" id="job_order_id" class="form-control select2"
                                                    multiple required
                                                    onchange="loadPackingItems();loadHeadProductsData();loadB2ProductsData();loadData();">
                                                    <option value="">Select Job Order</option>
                                                </select>
                                            </div>
                                        </div>



                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label>By Product:</label>
                                                <select name="by_product_id" id="by_product_id" onchange="loadData()"
                                                    class="form-control select2">
                                                    <option value="">Select By Product</option>
                                                    @if (isset($byheadsproducts))
                                                        @foreach ($byheadsproducts as $product)
                                                            <option value="{{ $product->id }}">{{ $product->name }}
                                                            </option>
                                                        @endforeach
                                                    @endif
                                                </select>
                                            </div>
                                        </div>

                                        <input type="hidden" name="user_id" value="{{ auth()->id() }}">

                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label>Remarks:</label>
                                                <textarea name="remarks" class="form-control" rows="3"></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Packing Items Display Section -->
                                <div class="col-md-12 mt-3" id="packingItemsSection" style="display: none;">
                                    <h6 class="header-heading-sepration">Packing Items</h6>
                                    <div id="packingItemsContainer">
                                        <!-- Packing items will be loaded here via fetchDynamicHTML -->
                                    </div>
                                </div>
                            </div>




                            <div id="productionInputsSection" class="mt-3">
                                <div class="row header-heading-sepration w-100 mx-auto mb-1 align-items-center">
                                    <div class="col-md-10 col-8">
                                        <h6 class="m-0">Production Inputs</h6>
                                    </div>
                                    <div class="col-md-2 col-4 text-right">
                                        <button type="button" class="btn btn-sm btn-primary" id="addInputRowBtn" title="Add Input Row">
                                            <i class="fa fa-plus"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div id="productionInputsTable">
                                            <table class="table table-bordered">
                                                <thead>
                                                    <tr>
                                                        <th class="col-2">Commodity</th>
                                                        <th class="col-2">Location</th>
                                                        <th class="col-1">Qty (kg)</th>
                                                        <th class="col-1">Rate (PKR)</th>
                                                        <th class="col-1">Yield %</th>
                                                        <th class="col-4">Remarks</th>
                                                        <th class="col-1">Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr class="input-data-row">
                                                        <td>
                                                            <select name="input_product_id[]" class="form-control select2"
                                                                required onchange="loadCostingPreview();">
                                                                <option value="">Select Commodity</option>
                                                                @if (isset($products))
                                                                    @foreach ($products as $product)
                                                                        <option value="{{ $product->id }}">
                                                                            {{ $product->name }}
                                                                        </option>
                                                                    @endforeach
                                                                @endif
                                                            </select>
                                                        </td>
                                                        <td>
                                                            <select name="input_location_id[]"
                                                                class="form-control select2" required onchange="loadCostingPreview();">
                                                                <option value="">Select Location</option>
                                                                @if (isset($sublocations))
                                                                    @foreach ($sublocations as $sublocation)
                                                                        <option value="{{ $sublocation->id }}" data-location-id="{{ $sublocation->company_location_id }}">
                                                                            {{ $sublocation->name }}
                                                                        </option>
                                                                    @endforeach
                                                                @endif
                                                            </select>
                                                        </td>
                                                        <td>
                                                            <input type="number" name="input_qty[]" class="form-control"
                                                                step="0.01" min="0.01" required oninput="loadCostingPreview();">
                                                        </td>
                                                        <td>
                                                            <input type="number" name="input_rate[]" class="form-control"
                                                                step="0.01" min="0" placeholder="Auto" oninput="loadCostingPreview();" title="Leave blank for automatic stock-in cost price">
                                                        </td>
                                                        <td>
                                                            <input type="number" name="input_yield[]"
                                                                class="form-control" step="0.01" min="0.01"
                                                                readonly>
                                                        </td>
                                                        <td>
                                                            <textarea name="input_remarks[]" class="form-control" rows="1"></textarea>
                                                        </td>
                                                        <td>
                                                            <button type="button" class="btn btn-sm btn-primary copythis"><i class="fa fa-plus"></i></button>
                                                            <button type="button" class="btn btn-sm btn-danger removethis"><i class="fa fa-trash"></i></button>
                                                        </td>
                                                    </tr>
                                                    <tr id="inputTotalRow">
                                                        <td>
                                                            <strong>Total</strong>
                                                        </td>
                                                        <td></td>
                                                        <td>
                                                            <input type="number" name="input_total_qty[]"
                                                                class="form-control" step="0.01" min="0.01"
                                                                readonly>
                                                        </td>
                                                        <td></td>
                                                        <td>
                                                            <input type="number" name="input_total_yield[]"
                                                                class="form-control" step="0.01" min="0.01"
                                                                readonly>
                                                        </td>
                                                        <td></td>
                                                        <td></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <template id="inputRowTemplate">
                                <tr class="input-data-row">
                                    <td>
                                        <select name="input_product_id[]" class="form-control select2"
                                            required onchange="loadCostingPreview();">
                                            <option value="">Select Commodity</option>
                                            @if (isset($products))
                                                @foreach ($products as $product)
                                                    <option value="{{ $product->id }}">
                                                        {{ $product->name }}
                                                    </option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </td>
                                    <td>
                                        <select name="input_location_id[]"
                                            class="form-control select2" required onchange="loadCostingPreview();">
                                            <option value="">Select Location</option>
                                            @if (isset($sublocations))
                                                @foreach ($sublocations as $sublocation)
                                                    <option value="{{ $sublocation->id }}" data-location-id="{{ $sublocation->company_location_id }}">
                                                        {{ $sublocation->name }}
                                                    </option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </td>
                                    <td>
                                        <input type="number" name="input_qty[]" class="form-control"
                                            step="0.01" min="0.01" required oninput="loadCostingPreview();">
                                    </td>
                                    <td>
                                        <input type="number" name="input_rate[]" class="form-control"
                                            step="0.01" min="0" placeholder="Auto" oninput="loadCostingPreview();" title="Leave blank for automatic stock-in cost price">
                                    </td>
                                    <td>
                                        <input type="number" name="input_yield[]"
                                            class="form-control" step="0.01" min="0.01"
                                            readonly>
                                    </td>
                                    <td>
                                        <textarea name="input_remarks[]" class="form-control" rows="1"></textarea>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-primary copythis"><i class="fa fa-plus"></i></button>
                                        <button type="button" class="btn btn-sm btn-danger removethis"><i class="fa fa-trash"></i></button>
                                    </td>
                                </tr>
                            </template>
                            <div id="productionOutputsSection">
                                <div class="row header-heading-sepration w-100 mx-auto mb-1 align-items-center">
                                    <div class="col-md-12">
                                        <h6 class="m-0">Production Outputs</h6>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="row header-heading-sepration w-100 mx-auto mb-1 align-items-center"
                                            style="background-color: #93c3f2;">
                                            <div class="col-md-12">
                                                <h6 class="m-0">Head Products</h6>
                                            </div>
                                        </div>
                                        <div id="productionHeadProductsTable">
                                            @include('management.production.production_voucher.partials.head_products_table')
                                            <!-- <table class="table table-bordered">
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        <thead>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            <tr>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                <th class="col-2">Commodity</th>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                <th>Qty (kg)</th>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                <th>No of Bags</th>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                <th>Bag Size</th>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                <th>Avg Weight per Bag (kg)</th>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                <th>Yield %</th>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                <th>Storage </th>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                <th>Brand</th>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                <th>Job Order</th>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                <th>Remarks</th>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                <th class="col-1">Actions</th>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            </tr>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        </thead>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        <tbody>

                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            <tr>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                <td>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    <select name="product_id[]" class="form-control select2"
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        required>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        <option value="">Select Commodity</option>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        @if (isset($products))
    @foreach ($products as $product)
    <option value="{{ $product->id }}">{{ $product->name }}
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                </option>
    @endforeach
    @endif
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    </select>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                </td>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                <td>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    <input type="number" name="qty[]" class="form-control"
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        step="0.01" min="0.01" required>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                </td>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                <td>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    <input type="number" name="no_of_bags[]" class="form-control"
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        step="1" min="0" required>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                </td>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                <td>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    <input type="number" name="bag_size[]" class="form-control"
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        step="0.01" min="0.01" required>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                </td>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                <td>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    <input type="number" name="avg_weight_per_bag[]"
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        class="form-control" step="0.01" min="0.01" readonly>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                </td>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                <td>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    <input type="number" name="yield[]" class="form-control" step="0.01" min="0.01" readonly>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                </td>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                <td>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    <select name="arrival_sub_location_id[]"
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        class="form-control select2" required>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        <option value="">Select Storage Location</option>

                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    </select>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                </td>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                <td>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    <select name="brand_id[]" class="form-control select2" required>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        <option value="">Select Brand</option>

                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    </select>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                </td>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                              <td>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    <select name="job_order_id[]" class="form-control select2"
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        required>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        <option value="">Select Job Order</option>

                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    </select>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                </td>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                <td>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    <textarea name="remarks[]" class="form-control" rows="1"></textarea>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                </td>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                <td class="col-1"><button class="btn btn-sm btn-primary copythis"><i
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            class="fa fa-copy"></i></button>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    <button class="btn btn-sm btn-danger removethis"><i
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            class="fa fa-trash"></i></button>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                </td>

                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            </tr>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            <tr>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                <td>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    <strong>Total</strong>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                </td>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                <td>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    <input type="number" name="commodity_total_qty[]" class="form-control"
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        step="0.01" min="0.01" readonly>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                </td>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                <td>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    <input type="number" name="commodity_total_no_of_bags[]" class="form-control"
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        step="1" min="0" readonly>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                </td>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                <td></td>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                <td></td>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                <td>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    <input type="number" name="total_yield[]" class="form-control"
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        step="0.01" min="0.01" readonly>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                </td>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                <td colspan="5"></td>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        </tbody>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    </table> -->
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="row header-heading-sepration w-100 mx-auto mb-1 align-items-center"
                                            style="background-color: #93c3f2;">
                                            <div class="col-md-12">
                                                <h6 class="m-0">B2 Products</h6>
                                            </div>
                                        </div>
                                        <div id="productionB2ProductsTable">
                                            @include('management.production.production_voucher.partials.b2_products_table')
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="row header-heading-sepration w-100 mx-auto mb-1 align-items-center"
                                            style="background-color: #93c3f2;">
                                            <div class="col-md-12">
                                                <h6 class="m-0">By Products</h6>
                                            </div>
                                        </div>
                                        <div id="productionByProductsTable">
                                            @include('management.production.production_voucher.partials.by_product_table')

                                        </div>
                                    </div>
                                </div>
                                <!-- total input & output -->

                                <div class="row my-2">
                                    <div class="col-md-6">
                                        <div class="row header-heading-sepration w-100 mx-auto mb-1 align-items-center"
                                            style="background-color: #93c3f2;">
                                            <div class="col-md-12">
                                                <h6 class="m-0">Total Input</h6>
                                            </div>
                                        </div>
                                        <div>
                                            <input type="text" name="net_total_input" class="form-control" readonly>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="row header-heading-sepration w-100 mx-auto mb-1 align-items-center"
                                            style="background-color: #93c3f2;">
                                            <div class="col-md-12">
                                                <h6 class="m-0">Total Output</h6>
                                            </div>
                                        </div>
                                        <div id="productionTotalOutputTable">
                                            <input type="text" name="net_total_output" class="form-control" readonly>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-12">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th>Labour Charges / Kg</th>
                                                    <th>Total Labour Charges</th>
                                                    <th>Deduction</th>
                                                    <th>Net Amount</th>
                                                    <th>Deduction Remarks</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td><input type="number" name="labour_charges_per_kg"
                                                            id="production_labour_charges_per_kg" class="form-control"
                                                            step="0.01" min="0.01" readonly></td>
                                                    <td><input type="number" name="total_labour_charges"
                                                            class="form-control" step="0.01" min="0.01" readonly>
                                                    </td>
                                                    <td><input type="number" name="labour_deduction"
                                                            onkeyup="calculateNetTotalInput()" class="form-control"
                                                            step="0.01" min="0.01"></td>
                                                    <td><input type="number" name="labour_net_amount"
                                                            class="form-control" step="0.01" min="0.01" readonly>
                                                    </td>
                                                    <td><input type="text" name="labour_deduction_remarks"
                                                            class="form-control">
                                                    </td>
                                                </tr>

                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="row mt-3" id="productionMachinesSection" style="display: none;">
                                    <div class="col-md-12">
                                        <div class="row header-heading-sepration w-100 mx-auto mb-1 align-items-center"
                                            style="background-color: #93c3f2;">
                                            <div class="col-md-12 d-flex justify-content-between align-items-center py-1">
                                                <h6 class="m-0">Production Machines</h6>
                                                <div id="machinePlanSettingBadgeCreate"></div>
                                            </div>
                                        </div>
                                        <div id="productionMachinesContainer" class="p-3 border">
                                            <!-- Machines will be loaded here -->
                                        </div>
                                    </div>
                                </div>

                                <div class="row mt-3" id="overallPlantBreakdownSection">
                                    <div class="col-md-12">
                                        <div class="row header-heading-sepration w-100 mx-auto mb-2 align-items-center"
                                            style="background-color: #93c3f2;">
                                            <div class="col-md-12">
                                                <h6 class="m-0">Overall Plant Breakdown</h6>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div id="breakdownItemsTable" class="table-responsive">
                                                    <table class="table table-sm table-bordered mb-0"
                                                        style="font-size: 12px;">
                                                        <thead style="background: #f4f6f9;">
                                                            <tr>
                                                                <th style="width: 28%">Breakdown Type</th>
                                                                <th style="width: 14%">From</th>
                                                                <th style="width: 14%">To</th>
                                                                <th style="width: 10%">Hours</th>
                                                                <th style="width: 26%">Remarks</th>
                                                                <th style="width: 8%" class="text-center">Actions</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody id="breakdownItemsBody">
                                                            <tr>
                                                                <td>
                                                                    <select name="breakdown_type_id[]"
                                                                        class="form-control form-control-sm"
                                                                        style="height: 30px;">
                                                                        <option value="">Select Breakdown Type
                                                                        </option>
                                                                        @if (isset($breakdownTypes))
                                                                            @foreach ($breakdownTypes as $type)
                                                                                <option value="{{ $type->id }}">
                                                                                    {{ $type->name }}</option>
                                                                            @endforeach
                                                                        @endif
                                                                    </select>
                                                                </td>
                                                                <td>
                                                                    <input type="time" name="from[]"
                                                                        class="form-control form-control-sm from-time"
                                                                        style="height: 30px;">
                                                                </td>
                                                                <td>
                                                                    <input type="time" name="to[]"
                                                                        class="form-control form-control-sm to-time"
                                                                        style="height: 30px;">
                                                                </td>
                                                                <td>
                                                                    <input type="number" name="hours[]"
                                                                        class="form-control form-control-sm hours-input"
                                                                        step="0.01" min="0" readonly
                                                                        style="height: 30px; background: #f8f9fa; text-align: center;">
                                                                </td>
                                                                <td>
                                                                    <input type="text" name="breakdown_remarks[]"
                                                                        class="form-control form-control-sm"
                                                                        placeholder="Breakdown remarks..."
                                                                        style="height: 30px;">
                                                                </td>
                                                                <td class="text-center align-middle">
                                                                    <button type="button"
                                                                        class="btn btn-sm btn-primary copythis_breakdown py-0 px-2"
                                                                        style="height: 26px;"><i
                                                                            class="fa fa-plus"></i></button>
                                                                    <button type="button"
                                                                        class="btn btn-sm btn-danger removethis_breakdown py-0 px-2"
                                                                        style="height: 26px;"><i
                                                                            class="fa fa-trash"></i></button>
                                                                </td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Phase 3: Production Costing & Auto-JV Valuation Live Card --}}
                            <div class="row">
                                @include('management.production.production_voucher.partials.costing_card')
                            </div>

                            <div class="row bottom-button-bar text-right">
                                <div class="col-12">
                                    <a type="button"
                                        class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton">Close</a>
                                    <button type="submit" class="btn btn-primary submitbutton">Save Production
                                        Voucher</button>
                                </div>
                            </div>
                        </form>

                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('script')
    <script>
        var allInputSublocations = [
            @if (isset($sublocations))
                @foreach ($sublocations as $sublocation)
                    { id: {{ $sublocation->id }}, name: {!! json_encode($sublocation->name) !!}, company_location_id: {{ $sublocation->company_location_id ?? 'null' }} },
                @endforeach
            @endif
        ];

        function populateInputLocationsSelect($select, selectedValue) {
            var currentLocationId = $('#location_id').val();
            var currentValue = (selectedValue !== undefined && selectedValue !== null && selectedValue !== '')
                ? selectedValue
                : $select.val();

            $select.empty().append('<option value="">Select Location</option>');

            var hasSelected = false;
            allInputSublocations.forEach(function(item) {
                if (!currentLocationId || item.company_location_id == currentLocationId) {
                    var $opt = $('<option></option>')
                        .attr('value', item.id)
                        .attr('data-location-id', item.company_location_id)
                        .text(item.name);

                    if (currentValue && item.id == currentValue) {
                        $opt.prop('selected', true);
                        hasSelected = true;
                    }
                    $select.append($opt);
                }
            });

            if (hasSelected && currentValue) {
                $select.val(currentValue);
            } else {
                $select.val('');
            }
        }

        function filterAllInputLocations() {
            var currentSubLocation = $('#sub_location_id').val();
            $('#productionInputsTable select[name="input_location_id[]"]').each(function() {
                var $select = $(this);
                var currentVal = $select.val();
                populateInputLocationsSelect($select, currentVal);
                if (!$select.val() && currentSubLocation) {
                    $select.val(currentSubLocation);
                }
                if ($select.data('select2')) {
                    $select.trigger('change.select2');
                }
            });
        }

        var costingPreviewTimeout = null;
        function loadCostingPreview() {
            clearTimeout(costingPreviewTimeout);
            costingPreviewTimeout = setTimeout(function() {
                var locationId = $('#location_id').val();
                var plantId = $('#plant_id').val();
                var productId = $('#product_id').val();

                if (!locationId) return;

                var inputQtys = [];
                var inputProductIds = [];
                var inputRates = [];
                var inputLocationIds = [];
                $('#productionInputsTable tbody tr').each(function() {
                    var pid = $(this).find('select[name="input_product_id[]"]').val();
                    var qty = $(this).find('input[name="input_qty[]"]').val();
                    var rate = $(this).find('input[name="input_rate[]"]').val();
                    var loc = $(this).find('select[name="input_location_id[]"]').val() || locationId;
                    if (pid && qty && parseFloat(qty) > 0) {
                        inputProductIds.push(pid);
                        inputQtys.push(qty);
                        inputRates.push(rate || '');
                        inputLocationIds.push(loc);
                    }
                });

                var outputQtys = [];
                var outputProductIds = [];
                $('#productionHeadProductsTable tbody tr, #productionB2ProductsTable tbody tr, #productionByProductsTable tbody tr').each(function() {
                    var pid = $(this).find('input[name="output_product_id[]"], select[name="output_product_id[]"]').val();
                    var qty = $(this).find('input[name="output_qty[]"]').val();
                    if (pid && qty && parseFloat(qty) > 0) {
                        outputProductIds.push(pid);
                        outputQtys.push(qty);
                    }
                });

                $.ajax({
                    url: "{{ route('production-voucher.get-costing-preview') }}",
                    type: "POST",
                    data: {
                        _token: "{{ csrf_token() }}",
                        location_id: locationId,
                        plant_id: plantId,
                        product_id: productId,
                        input_qty: inputQtys,
                        input_product_id: inputProductIds,
                        input_rate: inputRates,
                        input_location_id: inputLocationIds,
                        output_qty: outputQtys,
                        output_product_id: outputProductIds
                    },
                    success: function(res) {
                        if (res && res.success) {
                            $('#displayRawMaterialCost').text('PKR ' + parseFloat(res.total_input_cost).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                            $('#displayInputQtySub').text('Total Input: ' + parseFloat(res.total_input_qty).toLocaleString() + ' KG');

                            $('#displayMillingRateBadge').text('+PKR ' + parseFloat(res.total_milling_rate).toFixed(2) + '/KG');
                            $('#displayMillingCost').text('+PKR ' + parseFloat(res.total_milling_cost).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}));

                            $('#displayByProductCost').text('-PKR ' + parseFloat(res.total_by_product_cost).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}));

                            $('#displayNetFgCost').text('PKR ' + parseFloat(res.net_finished_good_cost).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                            $('#displayFgRateBadge').text('Unit Rate: PKR ' + parseFloat(res.finished_good_rate).toFixed(2) + ' / KG');

                            if (res.variables && res.variables.length > 0) {
                                var varHtml = '';
                                res.variables.forEach(function(v) {
                                    var vAmt = (parseFloat(res.total_input_qty) * parseFloat(v.rate)).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
                                    varHtml += '<tr>' +
                                        '<td class="font-weight-bold text-left">' + v.title + '</td>' +
                                        '<td>PKR ' + parseFloat(v.rate).toFixed(2) + '</td>' +
                                        '<td>' + parseFloat(res.total_input_qty).toLocaleString() + ' KG</td>' +
                                        '<td class="font-weight-bold text-danger">PKR ' + vAmt + '</td>' +
                                        '<td><span class="badge badge-light border text-dark">' + (v.account_unique_no || 'ACC') + ' (' + (v.account_path || '2-10') + ')</span></td>' +
                                    '</tr>';
                                });
                                $('#variablesBreakdownTable tbody').html(varHtml);
                                $('#variablesBreakdownContainer').show();
                            } else {
                                $('#variablesBreakdownContainer').hide();
                            }
                        }
                    }
                });
            }, 300);
        }

        $(document).ready(function() {
            // Hook costing preview
            $('#location_id, #plant_id, #product_id').on('change', function() {
                loadCostingPreview();
            });
            $(document).on('input change keyup', '#productionInputsTable input, #productionHeadProductsTable input, #productionB2ProductsTable input, #productionByProductsTable input', function() {
                loadCostingPreview();
            });
            // Initialize Select2 for all selects
            $('.select2').select2();

            // Filter input locations on load if location already chosen
            if ($('#location_id').val()) {
                filterAllInputLocations();
            }

            function fetchProductionVoucherNumber() {
                let locationId = $('#location_id').val();
                let selectedDate = $('input[name="prod_date"]').val();

                if (!locationId) {
                    $('input[name="prod_no"]').val('');
                    return;
                }

                $.ajax({
                    url: "{{ route('production-voucher.get-number') }}",
                    type: "GET",
                    data: {
                        location_id: locationId,
                        prod_date: selectedDate
                    },
                    success: function(response) {
                        if (response && response.success && response.prod_no) {
                            $('input[name="prod_no"]').val(response.prod_no);
                        } else if (typeof response === 'string') {
                            $('input[name="prod_no"]').val(response);
                        }
                    }
                });
            }

            $('#location_id').on('change', function() {
                filterAllInputLocations();
                fetchProductionVoucherNumber();
            });

            $('#sub_location_id').on('change', function() {
                var subLoc = $(this).val();
                if (subLoc) {
                    $('#productionInputsTable select[name="input_location_id[]"]').each(function() {
                        var $loc = $(this);
                        if (!$loc.val()) {
                            $loc.val(subLoc);
                            if ($loc.data('select2')) {
                                $loc.trigger('change.select2');
                            }
                        }
                    });
                }
            });

            // Generate production number on date change
            $('input[name="prod_date"]').on('change', function() {
                fetchProductionVoucherNumber();
                loadMachinesByPlant();
            });

            // Generate production number on page load
            if ($('#location_id').val() && $('input[name="prod_date"]').val()) {
                fetchProductionVoucherNumber();
            }
        });

        function loadCommoditiesByLocation() {
            const locationId = $('#location_id').val();
            const commoditySelect = $('#product_id');
            const currentVal = commoditySelect.val();
            const jobOrderSelect = $('#job_order_id');
            const plantSelect = $('#plant_id');

            // Clear commodity and job order dropdowns
            commoditySelect.empty().append('<option value="">Select Head Product</option>');
            jobOrderSelect.empty().append('<option value="">Select Job Order</option>');
            plantSelect.empty().append('<option value="">Select Plant</option>');
            $('#packingItemsSection').hide();
            $('#packingItemsBody').empty();

            if (!locationId) {
                commoditySelect.trigger('change');
                jobOrderSelect.trigger('change');
                plantSelect.trigger('change');
                return;
            }

            // Show loading
            commoditySelect.prop('disabled', true);

            $.ajax({
                url: '{{ route('production-voucher.get-commodities-by-location') }}',
                method: 'POST',
                data: {
                    location_id: locationId,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.commodities && response.commodities.length > 0) {
                        $.each(response.commodities, function(index, commodity) {
                            commoditySelect.append(
                                $('<option></option>')
                                .attr('value', commodity.id)
                                .attr('data-parent-id', commodity.parent_id)
                                .attr('data-parent-name', commodity.parent_name)
                                .text(commodity.name)
                            );
                        });
                        if (currentVal) {
                            commoditySelect.val(currentVal);
                        }
                    } else {
                        commoditySelect.append('<option value="">No Commodities Found</option>');
                    }
                    commoditySelect.trigger('change');
                },
                complete: function() {
                    commoditySelect.prop('disabled', false);
                },
                error: function(xhr) {
                    console.error('Error loading commodities:', xhr);
                    commoditySelect.append('<option value="">Error loading commodities</option>');
                },
                complete: function() {
                    commoditySelect.prop('disabled', false);
                }
            });
        }

        function loadSubLocationByLocation() {
            const locationId = $('#location_id').val();
            const subLocationSelect = $('#sub_location_id');

            if (!locationId) {
                subLocationSelect.empty().append('<option value="">Select Sub Location</option>');
                return;
            }

            subLocationSelect.prop('disabled', true);

            $.ajax({
                url: '{{ route('production-voucher.get-sublocations-by-location') }}',
                method: 'POST',
                data: {
                    location_id: locationId,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    subLocationSelect.empty().append('<option value="">Select Sub Location</option>');
                    if (response.sublocations && response.sublocations.length > 0) {
                        $.each(response.sublocations, function(index, sublocation) {
                            subLocationSelect.append(
                                $('<option></option>')
                                .attr('value', sublocation.id)
                                .text(sublocation.name)
                            );
                        });
                    } else {
                        subLocationSelect.append('<option value="">No Sub Location Found</option>');
                    }
                    subLocationSelect.trigger('change');
                },
                error: function(xhr) {
                    console.error('Error loading plants:', xhr);
                    subLocationSelect.append('<option value="">Error loading Sub Location</option>');
                },
                complete: function() {
                    subLocationSelect.prop('disabled', false);
                }
            });
        }


        function loadPlantsBySubLocation() {
            const locationId = $('#location_id').val();
            const subLocationId = $('#sub_location_id').val();
            const plantSelect = $('#plant_id');

            if (!subLocationId) {
                plantSelect.empty().append('<option value="">Select Plant</option>');
                return;
            }

            plantSelect.prop('disabled', true);

            $.ajax({
                url: '{{ route('production-voucher.get-plants-by-location') }}',
                method: 'POST',
                data: {
                    subLocationId: subLocationId,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    plantSelect.empty().append('<option value="">Select Plant</option>');
                    if (response.plants && response.plants.length > 0) {
                        $.each(response.plants, function(index, plant) {
                            plantSelect.append(
                                $('<option></option>')
                                .attr('value', plant.id)
                                .text(plant.name)
                                .attr('data-production_labour_charges_per_kg', plant
                                    .production_labour_charges_per_kg)
                            );
                        });
                    } else {
                        plantSelect.append('<option value="">No Plants Found</option>');
                    }
                    plantSelect.trigger('change');
                },
                error: function(xhr) {
                    console.error('Error loading plants:', xhr);
                    plantSelect.append('<option value="">Error loading plants</option>');
                },
                complete: function() {
                    plantSelect.prop('disabled', false);
                }
            });
        }

        function loadlabourCharges() {
            const plantId = $('#plant_id').val();
            const plant = $('#plant_id option:selected');
            const productionLabourChargesPerKg = plant.data('production_labour_charges_per_kg');
            $('#production_labour_charges_per_kg').val(productionLabourChargesPerKg);
        }

        var isResettingPlant = false;

        function loadMachinesByPlant() {
            if (isResettingPlant) {
                return;
            }

            const plantId = $('#plant_id').val();
            const date = $('input[name="prod_date"]').val();
            const locationId = $('#location_id').val();
            const subLocationId = $('#sub_location_id').val();
            const container = $('#productionMachinesContainer');
            const section = $('#productionMachinesSection');

            if (plantId && date) {
                loadPlantBreakdowns(date, plantId);
            }

            if (!plantId) {
                container.empty();
                section.hide();
                return;
            }

            $.ajax({
                url: '{{ route('production-voucher.get-machines-by-plant') }}',
                method: 'POST',
                data: {
                    plant_id: plantId,
                    date: date,
                    location_id: locationId,
                    sub_location_id: subLocationId,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    container.empty();

                    if (!response || response.success === false || !response.machines || response.machines.length === 0) {
                        section.hide();
                        var errorMsg = (response && response.message) ? response.message : 'No Production Machine exists for the selected plant. Please create at least one Production Machine for this plant first.';
                        
                        Swal.fire({
                            title: "Validation Error",
                            html: errorMsg,
                            icon: "warning",
                            confirmButtonColor: "#D95000",
                            confirmButtonText: "OK",
                        });

                        isResettingPlant = true;
                        $('#plant_id').val(null).trigger('change');
                        $('#production_labour_charges_per_kg').val('');
                        isResettingPlant = false;
                        return;
                    }

                    if (response.machine_plan_setting) {
                        $('#machinePlanSettingBadgeCreate').html(`
                            <span class="badge badge-success" style="font-size: 11px;">
                                <i class="fa fa-calendar-check-o mr-1"></i> Machine Plan Setting Linked (${response.machine_plan_setting.date})
                            </span>
                        `);
                    } else {
                        $('#machinePlanSettingBadgeCreate').html(`
                            <span class="badge badge-secondary" style="font-size: 11px;">
                                <i class="fa fa-cogs mr-1"></i> Plant Machines
                            </span>
                        `);
                    }

                    section.show();
                    let html = `<div class="row">`;
                    $.each(response.machines, function(index, machine) {
                        const isEnabled = machine.is_enabled;
                        const headerBg = isEnabled ? '#e8f3fc' : '#f5f5f5';
                        const statusLabel = isEnabled ? 'Active' : 'Inactive';
                        const statusColor = isEnabled ? '#007bff' : '#6c757d';
                        const cardBorder = isEnabled ? '#93c3f2' : '#e0e0e0';

                        let timeSlotsHtml = '';
                        const slots = (machine.time_slots && machine.time_slots.length > 0) ?
                            machine.time_slots :
                            [{
                                start_time: machine.start_time || '',
                                end_time: machine.end_time || '',
                                breakdowns: []
                            }];

                        $.each(slots, function(sIdx, slot) {
                            const startTimeVal = slot.start_time || '';
                            const endTimeVal = slot.end_time || '';
                            let breakdownsHtml = '';
                            if (slot.breakdowns && slot.breakdowns.length > 0) {
                                $.each(slot.breakdowns, function(bIdx, bd) {
                                    breakdownsHtml += renderSlotBreakdownRowHtml(
                                        machine.id, sIdx, bd);
                                });
                            }

                            timeSlotsHtml += `
                                <div class="time-slot-block mb-3 p-3" data-slot-index="${sIdx}" style="border: 1px solid #dcdfe6; border-radius: 6px; background: #fafbfc;">
                                    <div class="row time-row align-items-end mb-2">
                                        <div class="col-md-3">
                                            <label style="font-size:13px; color:#444; font-weight:600; margin-bottom:4px;">Start Time</label>
                                            <input type="time" name="machine_start_time[${machine.id}][]" class="form-control start-time" value="${startTimeVal}" onchange="handleSlotTimeChange(this, ${machine.id})" style="font-size:14px; height:38px;">
                                        </div>
                                        <div class="col-md-3">
                                            <label style="font-size:13px; color:#444; font-weight:600; margin-bottom:4px;">End Time</label>
                                            <input type="time" name="machine_end_time[${machine.id}][]" class="form-control end-time" value="${endTimeVal}" onchange="handleSlotTimeChange(this, ${machine.id})" style="font-size:14px; height:38px;">
                                        </div>
                                        <div class="col-md-3">
                                            <label style="font-size:13px; color:#444; font-weight:600; margin-bottom:4px;">Duration</label>
                                            <input type="text" class="form-control duration-display" readonly style="background:#f1f3f5; font-weight:bold; font-size:14px; text-align:center; height:38px;">
                                        </div>
                                        <div class="col-md-3 text-right">
                                            <button type="button" class="btn btn-sm btn-outline-danger remove-time-row" onclick="removeMachineTimeRow(this, ${machine.id})" title="Remove Time Slot" style="height:38px; width:38px; padding:0; display:inline-flex; align-items:center; justify-content:center;"><i class="fa fa-trash"></i></button>
                                        </div>
                                    </div>

                                    <!-- Child Breakdowns Section -->
                                    <div class="slot-breakdowns-container mt-2 pt-2" style="border-top: 1px dashed #ced4da;">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span style="font-size:12px; font-weight:bold; color:#e06d53;"><i class="fa fa-wrench mr-1"></i> Inner Breakdowns (Within Time Slot)</span>
                                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="addSlotBreakdownRow(${machine.id}, this)" style="font-size:11px; padding: 2px 10px;">
                                                <i class="fa fa-plus mr-1"></i> Add Breakdown
                                            </button>
                                        </div>
                                        <div class="slot-breakdown-list">
                                            ${breakdownsHtml}
                                        </div>
                                    </div>
                                </div>
                            `;
                        });

                        html += `
                            <div class="col-md-12 mb-3">
                                <div class="machine-card" id="machine_card_${machine.id}" style="border: 1px solid ${cardBorder}; border-radius: 4px; overflow: hidden; transition: all 0.3s;">
                                    <div class="machine-card-header d-flex align-items-center justify-content-between px-3 py-2" id="machine_card_header_${machine.id}" style="background: ${headerBg}; border-bottom: 1px solid ${cardBorder};">
                                        <div class="d-flex align-items-center">
                                            <div>
                                                <div style="font-weight:bold; font-size:14px; color: #333;">${machine.name}</div>
                                                <div style="font-size:12px; color: #666;">Machine #${index + 1}</div>
                                            </div>
                                        </div>
                                        <div class="d-flex align-items-center">
                                            <label class="machine-toggle-label mb-0" for="machine_${machine.id}" style="cursor:pointer; display:flex; align-items:center; gap:6px;">
                                                <span id="machine_status_${machine.id}" style="font-size:12px; font-weight:bold; color:${statusColor};">${statusLabel}</span>
                                                <div class="custom-control custom-switch mb-0">
                                                    <input type="checkbox" class="custom-control-input machine-toggle" name="production_machine_id[]" value="${machine.id}" id="machine_${machine.id}" ${isEnabled ? 'checked' : ''} onchange="updateMachineCardStyle(this, ${machine.id})">
                                                    <label class="custom-control-label" for="machine_${machine.id}"></label>
                                                </div>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="machine-card-body px-3 pt-2 pb-2" style="background:#fff;">
                                        <div style="font-size:14px; font-weight:bold; color:#333; margin-bottom:12px;">Time Slots</div>
                                        <div id="machine_time_table_${machine.id}">
                                            ${timeSlotsHtml}
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center mt-3 pt-3" style="border-top: 1px solid #dee2e6;">
                                            <div class="d-flex align-items-center" style="gap:10px;">
                                                <span style="font-size:15px; font-weight:bold; color:#333;">Grand Total Time:</span>
                                                <input type="text" class="grand-total-display form-control" readonly style="width:120px; font-weight:bold; text-align:center; font-size:15px; height:42px;">
                                            </div>
                                            <button type="button" class="btn btn-primary" onclick="addMachineTimeRow(${machine.id})" style="height:42px; padding:0 20px;">
                                                <i class="fa fa-plus mr-1"></i> Add Time Slot
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                    html += `</div>`;
                    container.append(html);

                    $.each(response.machines, function(index, machine) {
                        calculateMachineTime(machine.id);
                        updateMachineCardStyle($(`#machine_${machine.id}`), machine.id);
                    });
                },
                error: function(xhr) {
                    console.error('Error loading machines:', xhr);
                    section.hide();
                }
            });
        }

        $(document).on('change', 'input[name="prod_date"]', function() {
            if ($('#plant_id').val()) {
                loadMachinesByPlant();
            }
        });

        function autoSelectByProduct() {
            const headProductId = $('#product_id').val();
            if (!headProductId) {
                return;
            }

            const selectedOption = $('#product_id option:selected');
            let parentId = selectedOption.attr('data-parent-id');
            let parentName = selectedOption.attr('data-parent-name');

            function applyParentToByProduct(pId, pName) {
                if (!pId) return;

                // Ensure the parent option exists in by_product_id dropdown
                if ($('#by_product_id option[value="' + pId + '"]').length === 0) {
                    $('#by_product_id').append(new Option(pName || 'Parent Product', pId, true, true));
                }

                // Auto select the parent product and trigger change to load by-product table
                if ($('#by_product_id').val() != pId) {
                    $('#by_product_id').val(pId).trigger('change');
                }
            }

            if (parentId) {
                applyParentToByProduct(parentId, parentName);
            } else {
                $.ajax({
                    url: '{{ route('production-voucher.get-parent-product') }}',
                    method: 'POST',
                    data: {
                        product_id: headProductId,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(res) {
                        if (res && res.parent_id) {
                            selectedOption.attr('data-parent-id', res.parent_id);
                            selectedOption.attr('data-parent-name', res.parent_name);
                            applyParentToByProduct(res.parent_id, res.parent_name);
                        }
                    }
                });
            }
        }

        function loadJobOrdersByLocation() {
            const locationId = $('#location_id').val();
            const productId = $('#product_id').val();
            const jobOrderSelect = $('#job_order_id');

            // Clear existing options
            jobOrderSelect.empty().append('<option value="">Select Job Order</option>');
            $('#packingItemsSection').hide();
            $('#packingItemsContainer').empty();

            if (!locationId || !productId) {
                jobOrderSelect.trigger('change');
                return;
            }

            // Show loading
            jobOrderSelect.prop('disabled', true);

            $.ajax({
                url: '{{ route('production-voucher.get-job-orders-by-location') }}',
                method: 'POST',
                data: {
                    location_id: locationId,
                    product_id: productId,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.jobOrders && response.jobOrders.length > 0) {
                        $.each(response.jobOrders, function(index, jobOrder) {
                            jobOrderSelect.append(
                                $('<option></option>')
                                .attr('value', jobOrder.id)
                                .text(jobOrder.job_order_no + (jobOrder.ref_no ? ' (' + jobOrder
                                    .ref_no + ')' : ''))
                            );
                        });
                    } else {
                        jobOrderSelect.append('<option value="">No Job Orders Found</option>');
                    }
                    jobOrderSelect.trigger('change');
                },
                error: function(xhr) {
                    console.error('Error loading job orders:', xhr);
                    jobOrderSelect.append('<option value="">Error loading job orders</option>');
                },
                complete: function() {
                    jobOrderSelect.prop('disabled', false);
                }
            });
        }

        function loadPackingItems() {
            const jobOrderIds = $('#job_order_id').val();
            const locationId = $('#location_id').val();

            if (!jobOrderIds || !locationId || jobOrderIds.length === 0) {
                $('#packingItemsSection').hide();
                $('#packingItemsContainer').empty();
                return;
            }

            $('#packingItemsSection').show();

            // Use fetchDynamicHTML to load packing items with produced quantity
            fetchDynamicHTML(
                '{{ route('production-voucher.get-packing-items-with-produced') }}',
                'packingItemsContainer', {
                    job_order_ids: jobOrderIds,
                    location_id: locationId
                }, {
                    method: 'POST',
                    loader: true,
                    loadingText: 'Loading packing items...'
                }
            );
        }

        // Add Input Row (+) Button Click Handler
        $(document).on('click', '#addInputRowBtn', function(e) {
            e.preventDefault();
            var templateHtml = $('#inputRowTemplate').html();
            var $newRow = $(templateHtml);

            var $targetTable = $('#productionInputsTable');
            var $tbody = $targetTable.find('tbody');
            var $totalRow = $tbody.find('#inputTotalRow');

            if ($totalRow.length > 0) {
                $totalRow.before($newRow);
            } else {
                $tbody.append($newRow);
            }

            // Populate input location dropdown
            var $locSelect = $newRow.find('select[name="input_location_id[]"]');
            var currentSubLocation = $('#sub_location_id').val() || '';
            populateInputLocationsSelect($locSelect, currentSubLocation);

            // Initialize select2 cleanly
            $newRow.find('select.select2').each(function() {
                var $select = $(this);
                $select.select2({
                    dropdownParent: $select.closest('.modal-body, .card-body, body')
                });
            });

            calculateProductionInputs();
            loadCostingPreview();
        });

        $(document).on('click', '.copythis', function(e) {
            e.stopImmediatePropagation();

            var $originalRow = $(this).closest('tr');
            var $tbody = $originalRow.closest('tbody');
            var $table = $tbody.closest('table');
            var $container = $table.closest('div');

            var isInputRow = ($container.attr('id') === 'productionInputsTable' || $table.closest('#productionInputsTable').length > 0 || $originalRow.find('select[name="input_location_id[]"]').length > 0);

            // Capture select values from original row before cloning
            var selectValues = [];
            $originalRow.find('select').each(function() {
                selectValues.push({
                    name: $(this).attr('name'),
                    val: $(this).val()
                });
            });

            var originalInputLocation = $originalRow.find('select[name="input_location_id[]"]').val();
            var originalInputProduct = $originalRow.find('select[name="input_product_id[]"]').val();

            var clone = $originalRow.clone();

            // Clear input values in cloned row (but keep readonly fields empty)
            clone.find('input[type="number"]:not([readonly]), input[type="text"]:not([readonly])').val('');
            clone.find('textarea').val('');
            clone.find('input[readonly]').val(''); // Clear readonly fields too
            clone.find('input[name="output_qty[]"]').removeData('manual-change'); // Reset manual change flag

            // Strip ALL Select2 artifacts, containers, classes and data-select2-id from clone completely
            clone.find('.select2-container').remove();
            clone.removeAttr('data-select2-id');
            clone.find('[data-select2-id]').removeAttr('data-select2-id');

            clone.find('select').each(function() {
                var $select = $(this);
                $select.removeClass('select2-hidden-accessible')
                       .removeAttr('data-select2-id')
                       .removeAttr('tabindex')
                       .removeAttr('aria-hidden')
                       .removeData('select2')
                       .removeData('applied-val');
                $select.find('option, optgroup').removeAttr('data-select2-id');
                if (!$select.hasClass('select2')) {
                    $select.addClass('select2');
                }
            });

            // Insert clone after the original row
            $originalRow.after(clone);

            if (isInputRow) {
                var locationToApply = originalInputLocation;
                if (!locationToApply || locationToApply === '') {
                    locationToApply = $('#sub_location_id').val() || '';
                }

                var $cloneLoc = clone.find('select[name="input_location_id[]"]');
                if ($cloneLoc.length > 0) {
                    populateInputLocationsSelect($cloneLoc, locationToApply);
                    $cloneLoc.find('option').prop('selected', false).removeAttr('selected');
                    if (locationToApply) {
                        $cloneLoc.find('option[value="' + locationToApply + '"]').prop('selected', true);
                        $cloneLoc.val(locationToApply);
                    } else {
                        $cloneLoc.val('');
                    }
                }

                var $cloneProd = clone.find('select[name="input_product_id[]"]');
                if ($cloneProd.length > 0) {
                    $cloneProd.find('option').prop('selected', false).removeAttr('selected');
                    if (originalInputProduct) {
                        $cloneProd.find('option[value="' + originalInputProduct + '"]').prop('selected', true);
                        $cloneProd.val(originalInputProduct);
                    } else {
                        $cloneProd.val('');
                    }
                }
            } else {
                clone.find('select').each(function(index) {
                    var $select = $(this);
                    var orig = selectValues[index];
                    if (orig && orig.val !== undefined && orig.val !== null && orig.val !== '') {
                        $select.find('option').prop('selected', false).removeAttr('selected');
                        $select.find('option[value="' + orig.val + '"]').prop('selected', true);
                        $select.val(orig.val);
                    }
                });
            }

            // Reinitialize select2 cleanly on cloned row
            setTimeout(function() {
                clone.find('select.select2').each(function() {
                    var $select = $(this);
                    if ($select.data('select2')) {
                        $select.select2('destroy');
                    }
                    $select.select2({
                        dropdownParent: $select.closest('.modal-body, .card-body, body')
                    });
                });
            }, 50);

            // Function to determine which calculation to run
            function runAppropriateCalculation() {
                var containerId = $container.attr('id');

                if (containerId === 'productionInputsTable') {
                    calculateProductionInputs();
                } else if (containerId === 'productionHeadProductsTable') {
                    calculateHeadProducts();
                    calculateB2Products();
                    calculateByProducts();
                } else if (containerId === 'productionB2ProductsTable') {
                    calculateHeadProducts();
                    calculateB2Products();
                    calculateByProducts();
                } else if (containerId === 'productionByProductsTable') {
                    calculateHeadProducts();
                    calculateB2Products();
                    calculateByProducts();
                } else {
                    var hasYield = $table.find('th:contains("Yield %")').length > 0;
                    var hasAvgWeight = $table.find('th:contains("Avg Weight")').length > 0;
                    var hasStorage = $table.find('th:contains("Storage")').length > 0;
                    var hasLocation = $table.find('th:contains("Location")').length > 0;

                    if (hasYield && hasAvgWeight && hasStorage) {
                        calculateHeadProducts();
                        calculateB2Products();
                        calculateByProducts();
                    } else if (hasYield && hasLocation) {
                        calculateProductionInputs();
                    } else {
                        calculateProductionInputs();
                        calculateHeadProducts();
                        calculateB2Products();
                        calculateByProducts();
                    }
                }
            }

            // Immediately trigger calculations
            setTimeout(runAppropriateCalculation, 50);

            // Also attach direct event handlers to cloned row inputs for immediate calculation
            clone.find('input[name="output_qty[]"]').on('input change keyup', function() {
                $(this).data('manual-change', true);
                setTimeout(runAppropriateCalculation, 10);
            });

            clone.find('input[name="output_no_of_bags[]"], input[name="output_bag_size[]"]').on(
                'input change keyup',
                function() {
                    $(this).closest('tr').find('input[name="output_qty[]"]').removeData('manual-change');
                    setTimeout(runAppropriateCalculation, 10);
                });
        });

        $(document).on('click', '.removethis', function() {
            var $row = $(this).closest('tr');
            var $tbody = $row.closest('tbody');
            var $table = $tbody.closest('table');
            var $container = $table.closest('div');
            var isInputRow = ($container.attr('id') === 'productionInputsTable' || $table.closest('#productionInputsTable').length > 0 || $row.find('select[name="input_location_id[]"]').length > 0);

            if (isInputRow) {
                $row.remove();
                setTimeout(function() {
                    calculateProductionInputs();
                    loadCostingPreview();
                }, 50);
                return;
            }

            if ($tbody.find('tr').length > 1) {
                $row.remove();

                setTimeout(function() {
                    var containerId = $container.attr('id');

                    if (containerId === 'productionHeadProductsTable' || $container.find('#productionHeadProductsTable').length > 0) {
                        calculateHeadProducts();
                        calculateB2Products();
                        calculateByProducts();
                    } else if (containerId === 'productionB2ProductsTable' || $container.find('#productionB2ProductsTable').length > 0) {
                        calculateHeadProducts();
                        calculateB2Products();
                        calculateByProducts();
                    } else if (containerId === 'productionByProductsTable' || $container.find('#productionByProductsTable').length > 0) {
                        calculateHeadProducts();
                        calculateB2Products();
                        calculateByProducts();
                    } else {
                        calculateProductionInputs();
                        calculateHeadProducts();
                        calculateB2Products();
                        calculateByProducts();
                    }
                }, 50);
            } else {
                toastr.error('You cannot remove the last row');
                return false;
            }
        });


        function loadData() {
            const byProductId = $('[name="by_product_id"]').val();
            const locationId = $('[name="location_id"]').val();
            const jobOrderIds = $('#job_order_id').val();
            console.log(jobOrderIds);
            const headProductId = $('[name="product_id"]').val();
            if (byProductId && locationId) {
                fetchDynamicHTML('{{ route('production-voucher.get-by-product-table') }}', 'productionByProductsTable', {
                    by_product_id: byProductId,
                    location_id: locationId,
                    job_order_ids: jobOrderIds,
                    head_product_id: headProductId
                }, {
                    method: 'POST',
                    onSuccess: function(response, target) {
                        target.html(response);
                        // Reinitialize calculations after table loads
                        setTimeout(function() {
                            initializeCalculations();
                        }, 300);
                    }
                });
            }
        }

        function loadHeadProductsData() {
            const productId = $('[name="product_id"]').val();
            const locationId = $('[name="location_id"]').val();
            const jobOrderIds = $('#job_order_id').val();
            console.log(jobOrderIds);
            if (productId && locationId) {
                fetchDynamicHTML('{{ route('production-voucher.get-head-products-data') }}',
                'productionHeadProductsTable', {
                    product_id: productId,
                    location_id: locationId,
                    job_order_ids: jobOrderIds
                }, {
                    method: 'POST',
                    onSuccess: function(response, target) {
                        target.html(response);
                        // Reinitialize calculations after table loads
                        setTimeout(function() {
                            initializeCalculations();
                        }, 300);
                    }
                });
            }
        }

        function loadB2ProductsData() {
            const productId = $('[name="product_id"]').val();
            const locationId = $('[name="location_id"]').val();
            const jobOrderIds = $('#job_order_id').val();
            if (productId && locationId) {
                fetchDynamicHTML('{{ route('production-voucher.get-b2-products-data') }}',
                'productionB2ProductsTable', {
                    product_id: productId,
                    location_id: locationId,
                    job_order_ids: jobOrderIds
                }, {
                    method: 'POST',
                    onSuccess: function(response, target) {
                        target.html(response);
                        setTimeout(function() {
                            initializeCalculations();
                        }, 300);
                    }
                });
            }
        }

        // ========== REAL-TIME CALCULATIONS ==========

        // Helper function to round to 2 decimal places
        function roundToTwo(num) {
            return Math.round((num + Number.EPSILON) * 100) / 100;
        }

        // Calculate Production Inputs Yield and Totals
        function calculateProductionInputs() {
            const tbody = $('#productionInputsTable tbody');

            if (tbody.length === 0) return;

            let totalQty = 0;
            const dataRows = tbody.find('tr').not('#inputTotalRow').not(':last');

            if (dataRows.length === 0) {
                const totalRow = tbody.find('#inputTotalRow, tr:last');
                totalRow.find('input[name="input_total_qty[]"]').val('');
                totalRow.find('input[name="input_total_yield[]"]').val('');
                calculateNetTotalInput();
                return;
            }

            // First, calculate total input qty (excluding total row)
            dataRows.each(function() {
                const qty = parseFloat($(this).find('input[name="input_qty[]"]').val()) || 0;
                totalQty += qty;
            });

            // Now calculate yield for each input row (total input = 100%)
            dataRows.each(function() {
                const qtyInput = $(this).find('input[name="input_qty[]"]');
                const yieldInput = $(this).find('input[name="input_yield[]"]');

                const qty = parseFloat(qtyInput.val()) || 0;

                // Calculate yield: (input_qty / total_input_qty) * 100
                if (qty > 0 && totalQty > 0) {
                    const yield = (qty / totalQty) * 100;
                    yieldInput.val(roundToTwo(yield));
                } else {
                    yieldInput.val('');
                }
            });

            // Update total row
            const totalQtyInput = tbody.find('tr:last').find('input[name="input_total_qty[]"]');
            const totalYieldInput = tbody.find('tr:last').find('input[name="input_total_yield[]"]');

            totalQtyInput.val(roundToTwo(totalQty));

            // Total yield should be 100% (since total input = 100%)
            if (totalQty > 0) {
                totalYieldInput.val(roundToTwo(100));
            } else {
                totalYieldInput.val('');
            }
            calculateNetTotalInput();
        }

        // Calculate total outputs (head products + b2 products + by products)
        function getTotalOutputs() {
            let totalOutputs = 0;

            // Sum all head products qty
            $('#productionHeadProductsTable tbody tr').not(':last').each(function() {
                const qty = parseFloat($(this).find('input[name="output_qty[]"]').val()) || 0;
                totalOutputs += qty;
            });

            // Sum all B2 products qty
            $('#productionB2ProductsTable tbody tr').not(':last').each(function() {
                const qty = parseFloat($(this).find('input[name="output_qty[]"]').val()) || 0;
                totalOutputs += qty;
            });

            // Sum all by products qty
            $('#productionByProductsTable tbody tr').each(function() {
                const row = $(this);
                // Skip total rows
                if (!row.find('strong').text().includes('Total') && !row.find('strong').text().includes(
                        'Commodity Total')) {
                    const qty = parseFloat(row.find('input[name="output_qty[]"]').val()) || 0;
                    totalOutputs += qty;
                }
            });

            return totalOutputs;
        }

        // Calculate Head Products Yield, Avg Weight, and Totals
        function calculateHeadProducts() {
            const tbody = $('#productionHeadProductsTable tbody');

            if (tbody.length === 0) return;

            // Get total outputs (head + b2 + by products) - this will be 100%
            const totalOutputs = getTotalOutputs();

            let totalQty = 0;
            let totalNoOfBags = 0;

            // Calculate for each product row (excluding total row)
            tbody.find('tr').not(':last').each(function() {
                const qtyInput = $(this).find('input[name="output_qty[]"]');
                const noOfBagsInput = $(this).find('input[name="output_no_of_bags[]"]');
                const bagSizeInput = $(this).find('input[name="output_bag_size[]"]');
                const avgWeightInput = $(this).find('input[name="output_avg_weight_per_bag[]"]');
                const yieldInput = $(this).find('input[name="output_yield[]"]');

                const noOfBags = parseFloat(noOfBagsInput.val()) || 0;
                const bagSize = parseFloat(bagSizeInput.val()) || 0;

                // Calculate Qty = No of Bags × Bag Size (only if qty wasn't manually changed)
                if (!qtyInput.data('manual-change')) {
                    if (noOfBags > 0 && bagSize > 0) {
                        const calculatedQty = noOfBags * bagSize;
                        qtyInput.val(roundToTwo(calculatedQty));
                    } else if (noOfBags === 0 || bagSize === 0) {
                        qtyInput.val('');
                    }
                }

                const qty = parseFloat(qtyInput.val()) || 0;

                // Calculate avg_weight_per_bag = qty / no_of_bags
                if (noOfBags > 0 && qty > 0) {
                    const avgWeight = qty / noOfBags;
                    avgWeightInput.val(roundToTwo(avgWeight));
                } else if (qty === 0 || noOfBags === 0) {
                    avgWeightInput.val('');
                }

                // Calculate yield: (qty / total_outputs) * 100
                if (qty > 0 && totalOutputs > 0) {
                    const yield = (qty / totalOutputs) * 100;
                    yieldInput.val(roundToTwo(yield));
                } else {
                    yieldInput.val('');
                }

                totalQty += qty;
                totalNoOfBags += noOfBags;
            });

            // Update total row - handle both input name variations
            const totalRow = tbody.find('tr:last');
            const totalQtyInput = totalRow.find('input[name="commodity_total_qty[]"], input[name="total_qty[]"]');
            const totalBagsInput = totalRow.find('input[name="commodity_total_no_of_bags[]"]');
            const totalYieldInput = totalRow.find('input[name="total_yield[]"]');

            if (totalQtyInput.length > 0) {
                totalQtyInput.val(roundToTwo(totalQty));
            }
            if (totalBagsInput.length > 0) {
                totalBagsInput.val(Math.round(totalNoOfBags));
            }

            // Calculate total yield: (total_qty / total_outputs) * 100
            if (totalYieldInput.length > 0) {
                if (totalQty > 0 && totalOutputs > 0) {
                    const overallYield = (totalQty / totalOutputs) * 100;
                    totalYieldInput.val(roundToTwo(overallYield));
                } else {
                    totalYieldInput.val('');
                }
            }
            calculateNetTotalInput();
        }

        // Calculate B2 Products Yield, Avg Weight, and Totals
        function calculateB2Products() {
            const tbody = $('#productionB2ProductsTable tbody');

            if (tbody.length === 0) return;

            // Get total outputs (head + b2 + by products) - this will be 100%
            const totalOutputs = getTotalOutputs();

            let totalQty = 0;
            let totalNoOfBags = 0;

            // Calculate for each product row (excluding total row)
            tbody.find('tr').not(':last').not('.b2-total-row').each(function() {
                const qtyInput = $(this).find('input[name="output_qty[]"]');
                const noOfBagsInput = $(this).find('input[name="output_no_of_bags[]"]');
                const bagSizeInput = $(this).find('input[name="output_bag_size[]"]');
                const avgWeightInput = $(this).find('input[name="output_avg_weight_per_bag[]"]');
                const yieldInput = $(this).find('input[name="output_yield[]"]');

                if (qtyInput.length === 0) return;

                const noOfBags = parseFloat(noOfBagsInput.val()) || 0;
                const bagSize = parseFloat(bagSizeInput.val()) || 0;

                // Calculate Qty = No of Bags × Bag Size (only if qty wasn't manually changed)
                if (!qtyInput.data('manual-change')) {
                    if (noOfBags > 0 && bagSize > 0) {
                        const calculatedQty = noOfBags * bagSize;
                        qtyInput.val(roundToTwo(calculatedQty));
                    } else if (noOfBags === 0 || bagSize === 0) {
                        qtyInput.val('');
                    }
                }

                const qty = parseFloat(qtyInput.val()) || 0;

                // Calculate avg_weight_per_bag = qty / no_of_bags
                if (noOfBags > 0 && qty > 0) {
                    const avgWeight = qty / noOfBags;
                    avgWeightInput.val(roundToTwo(avgWeight));
                } else if (qty === 0 || noOfBags === 0) {
                    avgWeightInput.val('');
                }

                // Calculate yield: (qty / total_outputs) * 100
                if (qty > 0 && totalOutputs > 0) {
                    const yieldVal = (qty / totalOutputs) * 100;
                    yieldInput.val(roundToTwo(yieldVal));
                } else {
                    yieldInput.val('');
                }

                totalQty += qty;
                totalNoOfBags += noOfBags;
            });

            // Update total row
            const totalRow = tbody.find('tr.b2-total-row, tr:last');
            if (totalRow.length > 0 && totalRow.find('strong').text().includes('Total')) {
                const totalQtyInput = totalRow.find('input[name="commodity_total_qty[]"], input[name="total_qty[]"]');
                const totalBagsInput = totalRow.find('input[name="commodity_total_no_of_bags[]"]');
                const totalYieldInput = totalRow.find('input[name="total_yield[]"]');

                if (totalQtyInput.length > 0) {
                    totalQtyInput.val(roundToTwo(totalQty));
                }
                if (totalBagsInput.length > 0) {
                    totalBagsInput.val(Math.round(totalNoOfBags));
                }

                // Calculate total yield: (total_qty / total_outputs) * 100
                if (totalYieldInput.length > 0) {
                    if (totalQty > 0 && totalOutputs > 0) {
                        const overallYield = (totalQty / totalOutputs) * 100;
                        totalYieldInput.val(roundToTwo(overallYield));
                    } else {
                        totalYieldInput.val('');
                    }
                }
            }
            calculateNetTotalInput();
        }

        // Calculate By Products Yield, Avg Weight, and Totals
        function calculateByProducts() {
            const tbody = $('#productionByProductsTable tbody');

            if (tbody.length === 0) return;

            // Get total outputs (head + by products) - this will be 100%
            const totalOutputs = getTotalOutputs();

            let grandTotalQty = 0;
            let grandTotalBags = 0;

            // Group rows by commodity (each commodity has its rows + total row)
            let currentCommodityRows = [];

            tbody.find('tr').each(function() {
                const row = $(this);

                // Check if it's a commodity total row
                if (row.find('strong').text().includes('Commodity Total')) {
                    // Calculate commodity totals
                    let commodityQty = 0;
                    let commodityBags = 0;

                    currentCommodityRows.forEach(function(commodityRow) {
                        const qty = parseFloat(commodityRow.find('input[name="output_qty[]"]').val()) || 0;
                        const bags = parseFloat(commodityRow.find('input[name="output_no_of_bags[]"]')
                        .val()) || 0;
                        commodityQty += qty;
                        commodityBags += bags;
                    });

                    // Update commodity total row
                    row.find('input[name="commodity_total_qty[]"]').val(roundToTwo(commodityQty));
                    row.find('input[name="commodity_total_no_of_bags[]"]').val(Math.round(commodityBags));

                    // Calculate commodity yield: (commodity_qty / total_outputs) * 100
                    if (commodityQty > 0 && totalOutputs > 0) {
                        const commodityYield = (commodityQty / totalOutputs) * 100;
                        row.find('input[name="total_yield[]"]').val(roundToTwo(commodityYield));
                    } else {
                        row.find('input[name="total_yield[]"]').val('');
                    }

                    grandTotalQty += commodityQty;
                    grandTotalBags += commodityBags;
                    currentCommodityRows = [];
                }
                // Check if it's grand total row
                else if (row.hasClass('table-success') || row.find('strong').text().includes('Total')) {
                    // This will be handled after the loop
                }
                // Regular product row
                else {
                    const qtyInput = row.find('input[name="output_qty[]"]');
                    const noOfBagsInput = row.find('input[name="output_no_of_bags[]"]');
                    const bagSizeInput = row.find('input[name="output_bag_size[]"]');
                    const avgWeightInput = row.find('input[name="output_avg_weight_per_bag[]"]');
                    const yieldInput = row.find('input[name="output_yield[]"]');

                    const noOfBags = parseFloat(noOfBagsInput.val()) || 0;
                    const bagSize = parseFloat(bagSizeInput.val()) || 0;

                    // Calculate Qty = No of Bags × Bag Size (only if qty wasn't manually changed)
                    if (!qtyInput.data('manual-change')) {
                        if (noOfBags > 0 && bagSize > 0) {
                            const calculatedQty = noOfBags * bagSize;
                            qtyInput.val(roundToTwo(calculatedQty));
                        } else if (noOfBags === 0 || bagSize === 0) {
                            qtyInput.val('');
                        }
                    }

                    const qty = parseFloat(qtyInput.val()) || 0;

                    // Calculate avg_weight_per_bag = qty / no_of_bags
                    // Only calculate if both qty and no_of_bags are provided
                    if (noOfBags > 0 && qty > 0) {
                        const avgWeight = qty / noOfBags;
                        avgWeightInput.val(roundToTwo(avgWeight));
                    } else if (qty === 0 || noOfBags === 0) {
                        avgWeightInput.val('');
                    }

                    // Calculate yield: (qty / total_outputs) * 100
                    if (qty > 0 && totalOutputs > 0) {
                        const yield = (qty / totalOutputs) * 100;
                        yieldInput.val(roundToTwo(yield));
                    } else {
                        yieldInput.val('');
                    }

                    currentCommodityRows.push(row);
                }
            });

            // Update grand total row
            const grandTotalRow = tbody.find('tr.table-success, tr:last');
            if (grandTotalRow.length > 0 && grandTotalRow.find('strong').text().includes('Total')) {
                grandTotalRow.find('input[name="commodity_total_qty[]"]').val(roundToTwo(grandTotalQty));
                grandTotalRow.find('input[name="commodity_total_no_of_bags[]"]').val(Math.round(grandTotalBags));

                if (grandTotalQty > 0 && totalOutputs > 0) {
                    const overallYield = (grandTotalQty / totalOutputs) * 100;
                    grandTotalRow.find('input[name="total_yield[]"]').val(roundToTwo(overallYield));
                } else {
                    grandTotalRow.find('input[name="total_yield[]"]').val('');
                }
            }
            calculateNetTotalInput();
        }

        function calculateNetTotalInput() {
            let totalInput = 0;
            $('#productionInputsTable tbody tr').each(function() {
                const qty = parseFloat($(this).find('input[name="input_qty[]"]').val()) || 0;
                totalInput += qty;
            });
            $('input[name="net_total_input"]').val(roundToTwo(totalInput));

            // Total Output is from total sum of by product Qty (kg) and Head Products Qty (kg)
            const totalOutput = roundToTwo(getTotalOutputs());
            $('input[name="net_total_output"]').val(totalOutput);

            const labourRate = parseFloat($('#production_labour_charges_per_kg').val()) || 0;
            const calcLabour = roundToTwo(totalOutput * labourRate);
            $('input[name="total_labour_charges"]').val(calcLabour);

            const deduction = parseFloat($('input[name="labour_deduction"]').val()) || 0;
            const netAmount = roundToTwo(calcLabour - deduction);
            $('input[name="labour_net_amount"]').val(netAmount);
        }
        // Initialize all calculations
        function initializeCalculations() {
            // Reinitialize Select2 for dynamically loaded selects - properly destroy and reinitialize
            setTimeout(function() {
                // Destroy existing select2 instances first
                $('#productionInputsTable select, #productionHeadProductsTable select, #productionB2ProductsTable select, #productionByProductsTable select')
                    .each(function() {
                        var $select = $(this);
                        if ($select.data('select2')) {
                            $select.select2('destroy');
                        }
                    });

                // Remove existing select2 containers
                $('#productionInputsTable .select2-container, #productionHeadProductsTable .select2-container, #productionB2ProductsTable .select2-container, #productionByProductsTable .select2-container')
                    .remove();

                // Clean up all selects
                $('#productionInputsTable select, #productionHeadProductsTable select, #productionB2ProductsTable select, #productionByProductsTable select')
                    .removeClass('select2-hidden-accessible')
                    .removeAttr('data-select2-id');

                // Reinitialize all select2 with proper settings
                $('#productionInputsTable select, #productionHeadProductsTable select, #productionB2ProductsTable select, #productionByProductsTable select')
                    .each(function() {
                        var $select = $(this);
                        // Make sure it has select2 class
                        if (!$select.hasClass('select2')) {
                            $select.addClass('select2');
                        }
                        // Initialize select2 with proper settings
                        $select.select2({
                            dropdownParent: $select.closest('.modal-body, .card-body, body')
                        });
                    });
            }, 200);

            // Attach event handlers using delegated events (works for dynamically added rows)
            // Production Inputs - qty changes
            $(document).off('input change keyup', '#productionInputsTable input[name="input_qty[]"]');
            $(document).on('input change keyup', '#productionInputsTable input[name="input_qty[]"]', function() {
                calculateProductionInputs();
            });

            // Head Products - no_of_bags and bag_size changes (auto calculate qty)
            $(document).off('input change keyup',
                '#productionHeadProductsTable input[name="output_no_of_bags[]"], #productionHeadProductsTable input[name="output_bag_size[]"]'
                );
            $(document).on('input change keyup',
                '#productionHeadProductsTable input[name="output_no_of_bags[]"], #productionHeadProductsTable input[name="output_bag_size[]"]',
                function() {
                    // Clear manual-change flag so qty can be recalculated
                    $(this).closest('tr').find('input[name="output_qty[]"]').removeData('manual-change');
                    calculateHeadProducts();
                    calculateB2Products();
                    calculateByProducts(); // Recalculate by products too since total outputs changed
                    validateOutputRow($(this).closest('tr'));
                });

            // Head Products - qty manual change (mark as manual, then recalculate)
            $(document).off('input change keyup', '#productionHeadProductsTable input[name="output_qty[]"]');
            $(document).on('input change keyup', '#productionHeadProductsTable input[name="output_qty[]"]', function() {
                $(this).data('manual-change', true);
                calculateHeadProducts();
                calculateB2Products();
                calculateByProducts(); // Recalculate by products too since total outputs changed
                validateOutputRow($(this).closest('tr'));
            });

            // B2 Products - no_of_bags and bag_size changes (auto calculate qty)
            $(document).off('input change keyup',
                '#productionB2ProductsTable input[name="output_no_of_bags[]"], #productionB2ProductsTable input[name="output_bag_size[]"]'
                );
            $(document).on('input change keyup',
                '#productionB2ProductsTable input[name="output_no_of_bags[]"], #productionB2ProductsTable input[name="output_bag_size[]"]',
                function() {
                    // Clear manual-change flag so qty can be recalculated
                    $(this).closest('tr').find('input[name="output_qty[]"]').removeData('manual-change');
                    calculateHeadProducts();
                    calculateB2Products();
                    calculateByProducts();
                    validateOutputRow($(this).closest('tr'));
                });

            // B2 Products - qty manual change (mark as manual, then recalculate)
            $(document).off('input change keyup', '#productionB2ProductsTable input[name="output_qty[]"]');
            $(document).on('input change keyup', '#productionB2ProductsTable input[name="output_qty[]"]', function() {
                $(this).data('manual-change', true);
                calculateHeadProducts();
                calculateB2Products();
                calculateByProducts();
                validateOutputRow($(this).closest('tr'));
            });

            // By Products - no_of_bags and bag_size changes (auto calculate qty)
            $(document).off('input change keyup',
                '#productionByProductsTable input[name="output_no_of_bags[]"], #productionByProductsTable input[name="output_bag_size[]"]'
                );
            $(document).on('input change keyup',
                '#productionByProductsTable input[name="output_no_of_bags[]"], #productionByProductsTable input[name="output_bag_size[]"]',
                function() {
                    // Clear manual-change flag so qty can be recalculated
                    $(this).closest('tr').find('input[name="output_qty[]"]').removeData('manual-change');
                    calculateHeadProducts(); // Recalculate head products too since total outputs changed
                    calculateB2Products();
                    calculateByProducts();
                    validateOutputRow($(this).closest('tr'));
                });

            // By Products - qty manual change (mark as manual, then recalculate)
            $(document).off('input change keyup', '#productionByProductsTable input[name="output_qty[]"]');
            $(document).on('input change keyup', '#productionByProductsTable input[name="output_qty[]"]', function() {
                $(this).data('manual-change', true);
                calculateHeadProducts(); // Recalculate head products too since total outputs changed
                calculateB2Products();
                calculateByProducts();
                validateOutputRow($(this).closest('tr'));
            });

            // Job order selection in row -> autofill No of Bags, Packed in (KG), and Qty (kg)
            $(document).off('change', 'select[name="output_job_order_id[]"]');
            $(document).on('change', 'select[name="output_job_order_id[]"]', function() {
                const $row = $(this).closest('tr');
                const selectedOption = $(this).find('option:selected');
                const noOfBags = selectedOption.data('no-of-bags');
                const bagSize = selectedOption.data('bag-size');
                const qty = selectedOption.data('qty');
                const brandId = selectedOption.data('brand-id');

                if (noOfBags !== undefined && noOfBags !== '') {
                    $row.find('input[name="output_no_of_bags[]"]').val(noOfBags);
                }
                if (bagSize !== undefined && bagSize !== '') {
                    $row.find('input[name="output_bag_size[]"]').val(bagSize);
                }
                if (qty !== undefined && qty !== '') {
                    $row.find('input[name="output_qty[]"]').val(qty).removeData('manual-change');
                } else if (noOfBags && bagSize) {
                    $row.find('input[name="output_qty[]"]').val(roundToTwo(parseFloat(noOfBags) * parseFloat(bagSize))).removeData('manual-change');
                }
                if (brandId) {
                    const $brandSelect = $row.find('select[name="output_brand_id[]"]');
                    if (!$brandSelect.val() || $brandSelect.val() == '') {
                        $brandSelect.val(brandId).trigger('change');
                    }
                }

                validateOutputRow($row);
                calculateHeadProducts();
                calculateB2Products();
                calculateByProducts();
                calculateNetTotalInput();
            });

            // Note: produced_qty_kg no longer affects production inputs yield calculation
            // Production inputs yield is now based on total input = 100%

            // Run initial calculations
            calculateProductionInputs();
            calculateHeadProducts();
            calculateB2Products();
            calculateByProducts();
            calculateNetTotalInput();
        }

        // Validate an individual output row
        function validateOutputRow($row) {
            if ($row.hasClass('commodity-total-row') || $row.find('strong').text().includes('Total') || $row.find('strong').text().includes('Commodity Total')) {
                return true;
            }
            const noOfBagsInput = $row.find('input[name="output_no_of_bags[]"]');
            const bagSizeInput = $row.find('input[name="output_bag_size[]"]');
            const qtyInput = $row.find('input[name="output_qty[]"]');

            if (noOfBagsInput.length === 0 || bagSizeInput.length === 0 || qtyInput.length === 0) {
                return true;
            }

            const noOfBagsVal = noOfBagsInput.val() ? noOfBagsInput.val().trim() : '';
            const bagSizeVal = bagSizeInput.val() ? bagSizeInput.val().trim() : '';
            const qtyVal = qtyInput.val() ? qtyInput.val().trim() : '';

            const noOfBags = parseFloat(noOfBagsVal) || 0;
            const bagSize = parseFloat(bagSizeVal) || 0;
            const qty = parseFloat(qtyVal) || 0;

            const hasAny = (noOfBagsVal !== '' && noOfBags > 0) || (bagSizeVal !== '' && bagSize > 0) || (qtyVal !== '' && qty > 0);

            if (hasAny) {
                let valid = true;
                if (noOfBagsVal === '' || noOfBags <= 0) {
                    noOfBagsInput.addClass('is-invalid');
                    valid = false;
                } else {
                    noOfBagsInput.removeClass('is-invalid');
                }

                if (bagSizeVal === '' || bagSize <= 0) {
                    bagSizeInput.addClass('is-invalid');
                    valid = false;
                } else {
                    bagSizeInput.removeClass('is-invalid');
                }

                if (qtyVal === '' || qty <= 0) {
                    qtyInput.addClass('is-invalid');
                    valid = false;
                } else {
                    qtyInput.removeClass('is-invalid');
                }
                return valid;
            } else {
                noOfBagsInput.removeClass('is-invalid');
                bagSizeInput.removeClass('is-invalid');
                qtyInput.removeClass('is-invalid');
                return true;
            }
        }

        // Validate all output rows across Head Products and By Products
        function validateAllOutputRows() {
            let allValid = true;
            let firstInvalid = null;
            let errorMessages = [];

            $('#productionHeadProductsTable tbody tr, #productionB2ProductsTable tbody tr, #productionByProductsTable tbody tr').each(function() {
                const $row = $(this);
                if ($row.hasClass('commodity-total-row') || $row.hasClass('b2-total-row') || $row.find('strong').text().includes('Total') || $row.find('strong').text().includes('Commodity Total')) {
                    return;
                }
                const noOfBagsInput = $row.find('input[name="output_no_of_bags[]"]');
                const bagSizeInput = $row.find('input[name="output_bag_size[]"]');
                const qtyInput = $row.find('input[name="output_qty[]"]');

                if (noOfBagsInput.length === 0 || bagSizeInput.length === 0 || qtyInput.length === 0) return;

                const commodity = $row.find('strong').first().text().trim() || 'Product';
                const noOfBagsVal = noOfBagsInput.val() ? noOfBagsInput.val().trim() : '';
                const bagSizeVal = bagSizeInput.val() ? bagSizeInput.val().trim() : '';
                const qtyVal = qtyInput.val() ? qtyInput.val().trim() : '';

                const noOfBags = parseFloat(noOfBagsVal) || 0;
                const bagSize = parseFloat(bagSizeVal) || 0;
                const qty = parseFloat(qtyVal) || 0;

                const hasAny = (noOfBagsVal !== '' && noOfBags > 0) || (bagSizeVal !== '' && bagSize > 0) || (qtyVal !== '' && qty > 0);

                if (hasAny) {
                    let missing = [];
                    if (noOfBagsVal === '' || noOfBags <= 0) {
                        noOfBagsInput.addClass('is-invalid');
                        missing.push('No of Bags');
                        if (!firstInvalid) firstInvalid = noOfBagsInput;
                    } else {
                        noOfBagsInput.removeClass('is-invalid');
                    }

                    if (bagSizeVal === '' || bagSize <= 0) {
                        bagSizeInput.addClass('is-invalid');
                        missing.push('Packed in (KG)');
                        if (!firstInvalid) firstInvalid = bagSizeInput;
                    } else {
                        bagSizeInput.removeClass('is-invalid');
                    }

                    if (qtyVal === '' || qty <= 0) {
                        qtyInput.addClass('is-invalid');
                        missing.push('Qty (kg)');
                        if (!firstInvalid) firstInvalid = qtyInput;
                    } else {
                        qtyInput.removeClass('is-invalid');
                    }

                    if (missing.length > 0) {
                        allValid = false;
                        errorMessages.push(`${commodity}: ${missing.join(' and ')} required.`);
                    }
                } else {
                    noOfBagsInput.removeClass('is-invalid');
                    bagSizeInput.removeClass('is-invalid');
                    qtyInput.removeClass('is-invalid');
                }
            });

            if (!allValid) {
                if (firstInvalid) {
                    firstInvalid.focus();
                }
                const msg = 'If any of No of Bags, Packed in (KG), or Qty (kg) is entered, all three are required:\n' + errorMessages.join('\n');
                if (typeof toastr !== 'undefined') {
                    toastr.error('If any of No of Bags, Packed in (KG), or Qty (kg) is entered, all three are required: ' + errorMessages.join(' | '));
                } else {
                    alert(msg);
                }
                return false;
            }
            return true;
        }

        // Initialize on page load
        $(document).ready(function() {
            initializeCalculations();

            if ($('#plant_id').val()) {
                loadMachinesByPlant();
            }

            // Intercept submit on form directly to validate output rows
            $('#ajaxSubmit').on('submit', function(e) {
                if (!validateAllOutputRows()) {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    return false;
                }
            });

            $(document).on('click', '#ajaxSubmit button[type="submit"], #ajaxSubmit .submitbutton', function(e) {
                if (!validateAllOutputRows()) {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    return false;
                }
            });

            // Reinitialize after dynamic content loads
            $(document).on('DOMNodeInserted', function(e) {
                if ($(e.target).find(
                        '#productionInputsTable, #productionHeadProductsTable, #productionB2ProductsTable, #productionByProductsTable')
                    .length > 0) {
                    setTimeout(function() {
                        initializeCalculations();
                    }, 100);
                }
            });
        });

        // Reinitialize calculations when tables are reloaded via fetchDynamicHTML
        const originalFetchDynamicHTML = window.fetchDynamicHTML;
        if (typeof originalFetchDynamicHTML === 'function') {
            // If fetchDynamicHTML returns a promise, we can hook into it
            // Otherwise, we'll use the DOMNodeInserted event above
        }

        function renderSlotBreakdownRowHtml(machineId, slotIdx, bd) {
            bd = bd || {};
            var fromVal = bd.from || '';
            var toVal = bd.to || '';
            var hoursVal = bd.hours ? parseFloat(bd.hours).toFixed(2) : '';
            var remarksVal = bd.remarks || '';

            return `
                <div class="row slot-breakdown-row align-items-center mb-2 px-2 py-1 mx-0" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 4px;">
                    <div class="col-md-3 px-1">
                        <label style="font-size:11px; color:#666; margin-bottom:2px;">From</label>
                        <input type="time" name="slot_breakdown_from[${machineId}][${slotIdx}][]" class="form-control form-control-sm slot-breakdown-from" value="${fromVal}" onchange="validateAndCalculateBreakdown(this, ${machineId})">
                    </div>
                    <div class="col-md-3 px-1">
                        <label style="font-size:11px; color:#666; margin-bottom:2px;">To</label>
                        <input type="time" name="slot_breakdown_to[${machineId}][${slotIdx}][]" class="form-control form-control-sm slot-breakdown-to" value="${toVal}" onchange="validateAndCalculateBreakdown(this, ${machineId})">
                    </div>
                    <div class="col-md-2 px-1">
                        <label style="font-size:11px; color:#666; margin-bottom:2px;">Hours</label>
                        <input type="number" step="0.01" min="0" name="slot_breakdown_hours[${machineId}][${slotIdx}][]" class="form-control form-control-sm slot-breakdown-hours" value="${hoursVal}" readonly style="background:#f8f9fa;">
                    </div>
                    <div class="col-md-3 px-1">
                        <label style="font-size:11px; color:#666; margin-bottom:2px;">Remarks</label>
                        <input type="text" name="slot_breakdown_remarks[${machineId}][${slotIdx}][]" class="form-control form-control-sm slot-breakdown-remarks" value="${remarksVal}" placeholder="Remarks...">
                    </div>
                    <div class="col-md-1 px-1 text-center pt-3">
                        <button type="button" class="btn btn-sm btn-link text-danger p-0" onclick="removeSlotBreakdownRow(this, ${machineId})" title="Delete Breakdown"><i class="fa fa-trash"></i></button>
                    </div>
                </div>
            `;
        }

        function addMachineTimeRow(machineId) {
            const container = $(`#machine_time_table_${machineId}`);
            const slotIdx = container.find('.time-slot-block').length;
            const slotHtml = `
                <div class="time-slot-block mb-3 p-3" data-slot-index="${slotIdx}" style="border: 1px solid #dcdfe6; border-radius: 6px; background: #fafbfc;">
                    <div class="row time-row align-items-end mb-2">
                        <div class="col-md-3">
                            <label style="font-size:13px; color:#444; font-weight:600; margin-bottom:4px;">Start Time</label>
                            <input type="time" name="machine_start_time[${machineId}][]" class="form-control start-time" onchange="handleSlotTimeChange(this, ${machineId})" style="font-size:14px; height:38px;">
                        </div>
                        <div class="col-md-3">
                            <label style="font-size:13px; color:#444; font-weight:600; margin-bottom:4px;">End Time</label>
                            <input type="time" name="machine_end_time[${machineId}][]" class="form-control end-time" onchange="handleSlotTimeChange(this, ${machineId})" style="font-size:14px; height:38px;">
                        </div>
                        <div class="col-md-3">
                            <label style="font-size:13px; color:#444; font-weight:600; margin-bottom:4px;">Duration</label>
                            <input type="text" class="form-control duration-display" readonly style="background:#f1f3f5; font-weight:bold; font-size:14px; text-align:center; height:38px;">
                        </div>
                        <div class="col-md-3 text-right">
                            <button type="button" class="btn btn-sm btn-outline-danger remove-time-row" onclick="removeMachineTimeRow(this, ${machineId})" title="Remove Time Slot" style="height:38px; width:38px; padding:0; display:inline-flex; align-items:center; justify-content:center;"><i class="fa fa-trash"></i></button>
                        </div>
                    </div>

                    <!-- Child Breakdowns Section -->
                    <div class="slot-breakdowns-container mt-2 pt-2" style="border-top: 1px dashed #ced4da;">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span style="font-size:12px; font-weight:bold; color:#e06d53;"><i class="fa fa-wrench mr-1"></i> Inner Breakdowns (Within Time Slot)</span>
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="addSlotBreakdownRow(${machineId}, this)" style="font-size:11px; padding: 2px 10px;">
                                <i class="fa fa-plus mr-1"></i> Add Breakdown
                            </button>
                        </div>
                        <div class="slot-breakdown-list">
                        </div>
                    </div>
                </div>
            `;
            container.append(slotHtml);
            reindexMachineTimeSlots(machineId);
            calculateMachineTime(machineId);
        }

        function removeMachineTimeRow(btn, machineId) {
            $(btn).closest('.time-slot-block').remove();
            reindexMachineTimeSlots(machineId);
            calculateMachineTime(machineId);
        }

        function reindexMachineTimeSlots(machineId) {
            $(`#machine_time_table_${machineId} .time-slot-block`).each(function(slotIdx) {
                $(this).attr('data-slot-index', slotIdx);
                $(this).find('.slot-breakdown-from').attr('name',
                `slot_breakdown_from[${machineId}][${slotIdx}][]`);
                $(this).find('.slot-breakdown-to').attr('name', `slot_breakdown_to[${machineId}][${slotIdx}][]`);
                $(this).find('.slot-breakdown-hours').attr('name',
                    `slot_breakdown_hours[${machineId}][${slotIdx}][]`);
                $(this).find('.slot-breakdown-remarks').attr('name',
                    `slot_breakdown_remarks[${machineId}][${slotIdx}][]`);
            });
        }

        function addSlotBreakdownRow(machineId, btn) {
            const $slotBlock = $(btn).closest('.time-slot-block');
            const slotIdx = $slotBlock.data('slot-index') !== undefined ? $slotBlock.data('slot-index') : $slotBlock
        .index();
            const rowHtml = renderSlotBreakdownRowHtml(machineId, slotIdx, {});
            $slotBlock.find('.slot-breakdown-list').append(rowHtml);
        }

        function removeSlotBreakdownRow(btn, machineId) {
            const $slotBlock = $(btn).closest('.time-slot-block');
            $(btn).closest('.slot-breakdown-row').remove();
            validateAllSlotBreakdowns($slotBlock, machineId);
        }

        function handleSlotTimeChange(input, machineId) {
            calculateMachineTime(machineId);
            const $slotBlock = $(input).closest('.time-slot-block');
            validateAllSlotBreakdowns($slotBlock, machineId);
        }

        function validateAndCalculateBreakdown(input, machineId) {
            const $slotBlock = $(input).closest('.time-slot-block');
            validateAllSlotBreakdowns($slotBlock, machineId, $(input));
        }

        function timeToMinutes(timeStr) {
            if (!timeStr) return null;
            var parts = timeStr.split(':');
            return parseInt(parts[0], 10) * 60 + parseInt(parts[1], 10);
        }

        function validateAllSlotBreakdowns($slotBlock = null, machineId = null, $changedInput = null) {
            if (!$slotBlock || $slotBlock.length === 0) {
                let allValid = true;
                $('.time-slot-block').each(function() {
                    const $card = $(this).closest('.machine-card');
                    if ($card.length && !$card.find('.machine-toggle').is(':checked')) {
                        return; // Skip inactive machines
                    }
                    if (!validateAllSlotBreakdowns($(this), null, null)) {
                        allValid = false;
                    }
                });
                return allValid;
            }

            const parentStart = $slotBlock.find('.start-time').val();
            const parentEnd = $slotBlock.find('.end-time').val();

            let pStartMin = timeToMinutes(parentStart);
            let pEndMin = timeToMinutes(parentEnd);

            if (pStartMin !== null && pEndMin !== null) {
                if (pEndMin < pStartMin) {
                    pEndMin += 1440;
                }
            }

            let rows = [];
            let hasError = false;

            $slotBlock.find('.slot-breakdown-row').each(function(index) {
                const $row = $(this);
                const $from = $row.find('.slot-breakdown-from');
                const $to = $row.find('.slot-breakdown-to');
                const $hours = $row.find('.slot-breakdown-hours');

                $from.removeClass('is-invalid');
                $to.removeClass('is-invalid');

                const fromVal = $from.val();
                const toVal = $to.val();

                if (fromVal && toVal) {
                    let fromMin = timeToMinutes(fromVal);
                    let toMin = timeToMinutes(toVal);

                    if (pStartMin !== null && pEndMin !== null && pEndMin > 1440) {
                        if (fromMin < (pStartMin % 1440) && fromMin < 720) {
                            fromMin += 1440;
                        }
                        if (toMin < (pStartMin % 1440) && toMin < 720) {
                            toMin += 1440;
                        }
                    }

                    if (toMin <= fromMin) {
                        $to.addClass('is-invalid');
                        toastr.error(`Breakdown End Time (${toVal}) must be after Start Time (${fromVal})`);
                        if ($changedInput && $changedInput.is($to)) {
                            $to.val('');
                        }
                        $hours.val('');
                        hasError = true;
                        return;
                    }

                    if (pStartMin !== null && pEndMin !== null) {
                        if (fromMin < pStartMin || toMin > pEndMin) {
                            $from.addClass('is-invalid');
                            $to.addClass('is-invalid');
                            toastr.warning(
                                `Breakdown time (${fromVal} - ${toVal}) must be within the slot time (${parentStart} - ${parentEnd})`
                                );
                            if ($changedInput && ($changedInput.is($from) || $changedInput.is($to))) {
                                $changedInput.val('');
                                $hours.val('');
                            }
                            hasError = true;
                            return;
                        }
                    }

                    const diffMins = toMin - fromMin;
                    $hours.val((diffMins / 60).toFixed(2));

                    rows.push({
                        index: index,
                        fromMin: fromMin,
                        toMin: toMin,
                        fromVal: fromVal,
                        toVal: toVal,
                        $from: $from,
                        $to: $to
                    });
                } else {
                    $hours.val('');
                }
            });

            if (hasError) return false;

            for (let i = 0; i < rows.length; i++) {
                for (let j = i + 1; j < rows.length; j++) {
                    let r1 = rows[i];
                    let r2 = rows[j];
                    if (Math.max(r1.fromMin, r2.fromMin) < Math.min(r1.toMin, r2.toMin)) {
                        r1.$from.addClass('is-invalid');
                        r1.$to.addClass('is-invalid');
                        r2.$from.addClass('is-invalid');
                        r2.$to.addClass('is-invalid');
                        toastr.error(`Breakdown (${r2.fromVal} - ${r2.toVal}) overlaps with (${r1.fromVal} - ${r1.toVal})`);
                        if ($changedInput) {
                            $changedInput.val('');
                            $changedInput.addClass('is-invalid');
                        }
                        return false;
                    }
                }
            }

            return true;
        }

        function calculateMachineTime(machineId) {
            let totalMinutes = 0;
            $(`#machine_time_table_${machineId} .time-slot-block`).each(function() {
                const startTime = $(this).find('.start-time').val();
                const endTime = $(this).find('.end-time').val();
                let durationInput = $(this).find('.duration-display');

                if (startTime && endTime) {
                    const start = new Date(`1970-01-01T${startTime}:00`);
                    let end = new Date(`1970-01-01T${endTime}:00`);
                    if (end < start) {
                        end.setDate(end.getDate() + 1);
                    }
                    const diffMs = end - start;
                    const diffMins = Math.floor(diffMs / 60000);
                    totalMinutes += diffMins;

                    const hours = Math.floor(diffMins / 60);
                    const mins = diffMins % 60;
                    durationInput.val(`${hours}h ${mins}m`);
                } else {
                    durationInput.val('');
                }
            });

            const grandTotalHours = Math.floor(totalMinutes / 60);
            const grandTotalMins = totalMinutes % 60;
            $(`#machine_time_table_${machineId}`).closest('.machine-card-body').find('.grand-total-display').val(
                `${grandTotalHours}h ${grandTotalMins}m`);
        }

        function updateMachineCardStyle(checkbox, machineId) {
            const isChecked = $(checkbox).is(':checked');
            const card = $(`#machine_card_${machineId}`);
            const header = $(`#machine_card_header_${machineId}`);
            const statusLabel = $(`#machine_status_${machineId}`);
            const cardBody = card.find('.machine-card-body');
            const addSlotBtn = card.find('button[onclick*="addMachineTimeRow"]');

            if (isChecked) {
                card.css({
                    'border-color': '#93c3f2',
                    'opacity': '1'
                });
                header.css({
                    'background': '#e8f3fc',
                    'border-bottom': '1px solid #93c3f2'
                });
                statusLabel.text('Active').css('color', '#007bff');

                // Enable Add Slot button
                addSlotBtn.prop('disabled', false).css({
                    'opacity': '1',
                    'pointer-events': 'auto',
                    'cursor': 'pointer'
                });

                // Enable inputs & buttons inside machine card body
                cardBody.css({
                    'opacity': '1',
                    'pointer-events': 'auto'
                });
                cardBody.find('input:not([type="hidden"])').prop('disabled', false);
                cardBody.find('input:not(.duration-display):not(.slot-breakdown-hours):not([type="hidden"])').prop(
                    'readonly', false).css({
                    'background': '#fff',
                    'cursor': 'text'
                });
                cardBody.find('button').prop('disabled', false).css({
                    'opacity': '1',
                    'pointer-events': 'auto',
                    'cursor': 'pointer'
                });
            } else {
                card.css({
                    'border-color': '#e2e8f0',
                    'opacity': '0.75'
                });
                header.css({
                    'background': '#f8f9fa',
                    'border-bottom': '1px solid #e2e8f0'
                });
                statusLabel.text('Inactive').css('color', '#6c757d');

                // Disable Add Slot button
                addSlotBtn.prop('disabled', true).css({
                    'opacity': '0.5',
                    'pointer-events': 'none',
                    'cursor': 'not-allowed'
                });

                // Make columns disabled & readonly & buttons disabled inside machine card body
                cardBody.css({
                    'opacity': '0.6',
                    'pointer-events': 'none'
                });
                cardBody.find('input:not([type="hidden"])').prop('disabled', true).prop('readonly', true).css({
                    'background': '#f1f5f9',
                    'cursor': 'not-allowed'
                });
                cardBody.find('button').prop('disabled', true).css({
                    'opacity': '0.5',
                    'pointer-events': 'none',
                    'cursor': 'not-allowed'
                });
            }
        }

        function loadPlantBreakdowns(date, plantId) {
            if (!date || !plantId) return;

            $.ajax({
                url: '{{ route('getBreakdownsByPlantAndDate') }}',
                type: 'GET',
                data: {
                    date: date,
                    plant_id: plantId
                },
                success: function(response) {
                    if (response.items && response.items.length > 0) {
                        var html = '';
                        $.each(response.items, function(index, item) {
                            var fromVal = item.from ? item.from.substring(0, 5) : '';
                            var toVal = item.to ? item.to.substring(0, 5) : '';
                            var hoursVal = item.hours || '';
                            var remarksVal = item.remarks || '';

                            var optionsHtml = '<option value="">Select Breakdown Type</option>';
                            @if (isset($breakdownTypes))
                                @foreach ($breakdownTypes as $type)
                                    var selected = (item.breakdown_type_id == '{{ $type->id }}') ?
                                        'selected' : '';
                                    optionsHtml +=
                                        `<option value="{{ $type->id }}" ${selected}>{{ $type->name }}</option>`;
                                @endforeach
                            @endif

                            html += `
                                <tr>
                                    <td>
                                        <select name="breakdown_type_id[]" class="form-control form-control-sm" style="height: 30px;">
                                            ${optionsHtml}
                                        </select>
                                    </td>
                                    <td>
                                        <input type="time" name="from[]" class="form-control form-control-sm from-time" value="${fromVal}" style="height: 30px;">
                                    </td>
                                    <td>
                                        <input type="time" name="to[]" class="form-control form-control-sm to-time" value="${toVal}" style="height: 30px;">
                                    </td>
                                    <td>
                                        <input type="number" name="hours[]" class="form-control form-control-sm hours-input" step="0.01" min="0" value="${hoursVal}" readonly style="height: 30px; background: #f8f9fa; text-align: center;">
                                    </td>
                                    <td>
                                        <input type="text" name="breakdown_remarks[]" class="form-control form-control-sm" placeholder="Breakdown remarks..." value="${remarksVal}" style="height: 30px;">
                                    </td>
                                    <td class="text-center align-middle">
                                        <button type="button" class="btn btn-sm btn-primary copythis_breakdown py-0 px-2" style="height: 26px;"><i class="fa fa-plus"></i></button>
                                        <button type="button" class="btn btn-sm btn-danger removethis_breakdown py-0 px-2" style="height: 26px;"><i class="fa fa-trash"></i></button>
                                    </td>
                                </tr>
                            `;
                        });
                        $('#breakdownItemsBody').html(html);
                        calculateBreakdownHours();
                    } else {
                        var optionsHtml = '<option value="">Select Breakdown Type</option>';
                        @if (isset($breakdownTypes))
                            @foreach ($breakdownTypes as $type)
                                optionsHtml +=
                                    `<option value="{{ $type->id }}">{{ $type->name }}</option>`;
                            @endforeach
                        @endif

                        var emptyRowHtml = `
                            <tr>
                                <td>
                                    <select name="breakdown_type_id[]" class="form-control form-control-sm" style="height: 30px;">
                                        ${optionsHtml}
                                    </select>
                                </td>
                                <td>
                                    <input type="time" name="from[]" class="form-control form-control-sm from-time" style="height: 30px;">
                                </td>
                                <td>
                                    <input type="time" name="to[]" class="form-control form-control-sm to-time" style="height: 30px;">
                                </td>
                                <td>
                                    <input type="number" name="hours[]" class="form-control form-control-sm hours-input" step="0.01" min="0" readonly style="height: 30px; background: #f8f9fa; text-align: center;">
                                </td>
                                <td>
                                    <input type="text" name="breakdown_remarks[]" class="form-control form-control-sm" placeholder="Breakdown remarks..." style="height: 30px;">
                                </td>
                                <td class="text-center align-middle">
                                    <button type="button" class="btn btn-sm btn-primary copythis_breakdown py-0 px-2" style="height: 26px;"><i class="fa fa-plus"></i></button>
                                    <button type="button" class="btn btn-sm btn-danger removethis_breakdown py-0 px-2" style="height: 26px;"><i class="fa fa-trash"></i></button>
                                </td>
                            </tr>
                        `;
                        $('#breakdownItemsBody').html(emptyRowHtml);
                        calculateBreakdownHours();
                    }
                },
                error: function(xhr) {
                    console.error('Error loading breakdowns:', xhr);
                }
            });
        }

        function timeToMinutesBreakdown(timeStr) {
            if (!timeStr) return null;
            var parts = timeStr.split(':');
            return parseInt(parts[0], 10) * 60 + parseInt(parts[1], 10);
        }

        function validateBreakdownTimes($changedInput) {
            var hasError = false;
            var rows = [];

            $('#breakdownItemsBody tr').each(function(index) {
                var $row = $(this);
                var $fromInput = $row.find('.from-time');
                var $toInput = $row.find('.to-time');
                var fromVal = $fromInput.val();
                var toVal = $toInput.val();

                $fromInput.removeClass('is-invalid');
                $toInput.removeClass('is-invalid');

                if (fromVal && toVal) {
                    var fromMin = timeToMinutesBreakdown(fromVal);
                    var toMin = timeToMinutesBreakdown(toVal);

                    if (fromMin >= toMin) {
                        $toInput.addClass('is-invalid');
                        toastr.error('End time (' + toVal + ') must be after start time (' + fromVal + ') in row ' +
                            (index + 1));
                        if ($changedInput && $changedInput.is($toInput)) {
                            $toInput.val('');
                        }
                        hasError = true;
                        return false;
                    }

                    rows.push({
                        index: index,
                        fromMin: fromMin,
                        toMin: toMin,
                        fromVal: fromVal,
                        toVal: toVal,
                        $fromInput: $fromInput,
                        $toInput: $toInput
                    });
                }
            });

            if (hasError) {
                calculateBreakdownHours();
                return false;
            }

            // Check for overlaps among rows
            for (var i = 0; i < rows.length; i++) {
                for (var j = i + 1; j < rows.length; j++) {
                    var r1 = rows[i];
                    var r2 = rows[j];

                    if (Math.max(r1.fromMin, r2.fromMin) < Math.min(r1.toMin, r2.toMin)) {
                        r1.$fromInput.addClass('is-invalid');
                        r1.$toInput.addClass('is-invalid');
                        r2.$fromInput.addClass('is-invalid');
                        r2.$toInput.addClass('is-invalid');

                        toastr.error('Time interval (' + r2.fromVal + ' - ' + r2.toVal + ') in row ' + (r2.index + 1) +
                            ' overlaps with (' + r1.fromVal + ' - ' + r1.toVal + ') in row ' + (r1.index + 1));

                        if ($changedInput) {
                            $changedInput.val('');
                            $changedInput.addClass('is-invalid');
                        }
                        calculateBreakdownHours();
                        return false;
                    }
                }
            }

            calculateBreakdownHours();
            return true;
        }

        function calculateBreakdownHours() {
            $('#breakdownItemsBody tr').each(function() {
                var $row = $(this);
                var fromTime = $row.find('.from-time').val();
                var toTime = $row.find('.to-time').val();
                var $hoursInput = $row.find('.hours-input');

                if (fromTime && toTime) {
                    var fromMin = timeToMinutesBreakdown(fromTime);
                    var toMin = timeToMinutesBreakdown(toTime);

                    if (toMin > fromMin) {
                        var diffMinutes = toMin - fromMin;
                        var diffHours = diffMinutes / 60;
                        $hoursInput.val(diffHours.toFixed(2));
                    } else {
                        $hoursInput.val('');
                    }
                } else {
                    $hoursInput.val('');
                }
            });
        }

        $(document).on('change', '#breakdownItemsBody .from-time, #breakdownItemsBody .to-time', function() {
            validateBreakdownTimes($(this));
        });

        $(document).on('click', '.copythis_breakdown', function(e) {
            e.preventDefault();
            e.stopImmediatePropagation();
            var $row = $(this).closest('tr');
            var clone = $row.clone();
            clone.find('select').val('');
            clone.find('input').val('');
            clone.find('.from-time, .to-time').removeClass('is-invalid');
            $row.after(clone);
            calculateBreakdownHours();
        });

        $(document).on('click', '.removethis_breakdown', function(e) {
            e.preventDefault();
            e.stopImmediatePropagation();
            if ($('#breakdownItemsBody tr').length > 1) {
                $(this).closest('tr').remove();
                validateBreakdownTimes();
            } else {
                var $row = $(this).closest('tr');
                $row.find('select').val('');
                $row.find('input').val('');
                $row.find('.from-time, .to-time').removeClass('is-invalid');
                calculateBreakdownHours();
            }
        });

        $(document).off('submit.pv_breakdown', '#ajaxSubmit').on('submit.pv_breakdown', '#ajaxSubmit', function(e) {
            var totalInput = parseFloat($('input[name="net_total_input"]').val()) || 0;
            var totalOutput = parseFloat($('input[name="net_total_output"]').val()) || 0;

            if (totalOutput > totalInput) {
                e.preventDefault();
                e.stopImmediatePropagation();
                toastr.error('Total input must be greater than or equal to total output (Total Output: ' + totalOutput.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' KG)');
                return false;
            }

            if (!validateBreakdownTimes() || !validateAllSlotBreakdowns()) {
                e.preventDefault();
                e.stopImmediatePropagation();
                return false;
            }
        });
    </script>
@endsection
