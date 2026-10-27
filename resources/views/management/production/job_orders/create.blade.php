@extends('management.layouts.master')
@section('title')
    Create Job Order
@endsection
@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">Create Job Order</h4>
                    <a href="{{ route('job-orders.index') }}" onclick="loadPageContent('{{ route('job-orders.index') }}')" class="btn btn-sm btn-secondary">
                        <i class="ft-arrow-left mr-1"></i>Back to List
                    </a>
                </div>
                <div class="card-body">
                    <form action="{{ route('job-orders.store') }}" method="POST" id="ajaxSubmit" autocomplete="off" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" id="url" value="{{ route('job-orders.index') }}" />
                        <input type="hidden" id="listRefresh" value="{{ route('get.job_orders') }}" />

                        @php
                            $selectedLocation = $selectedLocation ?? ($companyLocations->first() ?? null);
                        @endphp
                        <div class="row form-mar">
                            <!-- Basic Information -->
                            <div class="col-md-12">
            <h6 class="header-heading-sepration">Basic Information</h6>
            <div class="row">
                <!-- Row 1: Job Order Request | Export Order | Job Order Date (Company Location hidden) -->
                <div style="display: none;">
                    <select name="company_location_id" id="company_location_id" class="form-control select2">
                        <option value="">Select Location</option>
                        @foreach($companyLocations as $location)
                            <option data-code="{{ $location->code }}" value="{{ $location->id }}" {{ (($selectedLocation && $selectedLocation->id == $location->id) || $loop->first) ? 'selected' : '' }}>
                                {{ $location->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <fieldset>
                        <label>Job Order Request (Optional):</label>
                        <div class="input-group">
                            @php
                                $selectedRequestId = request('job_order_request_id', request('request_id'));
                            @endphp
                            <select name="job_order_request_id" class="form-control select2" id="jobOrderRequestSelect">
                                <option value="">Select Request</option>
                                @foreach($jobOrderRequests as $jor)
                                    <option value="{{ $jor->id }}" data-remaining="{{ $jor->remaining_kgs }}" {{ $selectedRequestId == $jor->id ? 'selected' : '' }}>
                                        {{ $jor->request_no }} (Rem: {{ number_format($jor->remaining_kgs) }} KG)
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </fieldset>
                </div>
                <div class="col-md-4">
                    <fieldset>
                        <label>Export Order:</label>
                        <div class="input-group">
                            <select name="export_order_id" class="form-control select2" id="exportOrderSelect">
                                <option value="">Select Export Order</option>
                                @foreach($exportOrders as $eo)
                                    <option value="{{ $eo->id }}">{{ $eo->voucher_no }}</option>
                                @endforeach
                            </select>
                        </div>
                    </fieldset>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Job Order Date:</label>
                        <input type="date" name="job_order_date" class="form-control" value="{{ date('Y-m-d') }}">
                    </div>
                </div>

                <!-- Row 2: Job Order No# | Ref No | Attention To -->
                <div class="col-md-3">
                    <fieldset>
                        <label>Job Order No#</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <button class="btn btn-primary" type="button">Job Order No#</button>
                            </div>
                            <input type="text" readonly name="job_order_no" id="job_order_no" class="form-control" value="{{ $jobOrderNo ?? '' }}">
                        </div>
                    </fieldset>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Ref No:</label>
                        <input type="text" name="ref_no" class="form-control">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Attention To:</label>
                        <select name="attention_to[]" class="form-control select2" multiple>
                            <option value="">Select Users</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Row 3: Remarks | Order Description -->
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Remarks:</label>
                        <textarea name="remarks" class="form-control" rows="5"></textarea>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Order Description:</label>
                        <textarea name="order_description" class="form-control" rows="5"></textarea>
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
                        <option value="{{ $product->id }}">{{ $product->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>


        <!-- Specifications Section -->
        <div class="col-md-12" id="specificationsSection" style="display: ;">
            <h6 class="header-heading-sepration">Specifications</h6>
            <div id="productSpecs">
                <div class="alert bg-light-warning mb-2 alert-light-warning" role="alert">
                    <i class="ft-info mr-1"></i>
                    <strong>No specifications found!</strong> Please select a commodity first!
                </div>
            </div>
        </div>
        <!-- Product Selection -->
        <div class="col-md-12">
            <div class="form-group">
                <label>Crop Year:</label>
                <select name="crop_year_id" class="form-control select2" id="cropYearSelect">
                    <option value="">Select Crop Year</option>
                    @foreach($cropYears as $cropYear)
                        <option value="{{ $cropYear->id }}">{{ $cropYear->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-md-12">
            <div class="form-group">
                <label>Other Specification:</label>
                <textarea name="other_specifications" class="form-control" rows="4"></textarea>
            </div>
        </div>

        <!-- Packing Details -->
        <div class="col-md-12">
            <h6 class="header-heading-sepration d-flex justify-content-between align-items-center">Packing Details
                <button type="button" class="btn btn-sm btn-success" id="addPackingItem">Add More Packing Item</button>
            </h6>

            <div id="job-order-request-quantity-info" class="mb-3" style="display: none;"></div>
            <div id="export-order-quantity-info" class="mb-3" style="display: none;"></div>

            <div id="packingItems">
                <div class="packing-item row border-bottom pb-3 mb-3 w-100 mx-auto">
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Location: <span class="text-danger">*</span></label>
                            <select name="packing_items[0][company_location_id]" class="form-control select2 packing-company-location-id" required>
                                <option value="">Select Location</option>
                                @foreach($companyLocations as $location)
                                    <option data-code="{{ $location->code }}" value="{{ $location->id }}" {{ (($selectedLocation && $selectedLocation->id == $location->id) || $loop->first) ? 'selected' : '' }}>
                                        {{ $location->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Brand:</label>
                            <select name="packing_items[0][brand_id]" class="form-control select2">
                                <option value="">Select Brand</option>
                                @foreach($brands as $brand)
                                    <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <!-- <div class="col-md-2">
                        <div class="form-group">
                            <label>Bag Type:</label>
                            <select name="packing_items[0][bag_type_id]" class="form-control select2">
                                <option value="">Select Bag Type</option>
                                @foreach($bagTypes as $bagType)
                                    <option value="{{ $bagType->id }}">{{ $bagType->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div> -->
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Bag Type/Product:</label>
                            <select name="packing_items[0][bag_product_id]" class="form-control select2">
                                <option value="">Select Bag Type/Product</option>
                                @foreach($bagProducts as $bagProduct)
                                    <option value="{{ $bagProduct->id }}">{{ $bagProduct->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Bag Condition:</label>
                            <select name="packing_items[0][bag_condition_id]" class="form-control select2">
                                <option value="">Select Condition</option>
                                @foreach($bagConditions as $condition)
                                    <option value="{{ $condition->id }}">{{ $condition->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Bag Color: <span class="text-danger">*</span></label>
                            <select name="packing_items[0][bag_color_id]" class="form-control select2" required>
                                <option value="">Select Color</option>
                                @foreach($bagColors as $color)
                                    <option value="{{ $color->id }}">{{ $color->color }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-1">
                        <div class="form-group">
                            <label>Thread Color:</label>
                            <select name="packing_items[0][thread_color_id]" class="form-control select2">
                                <option value="">Select Color</option>
                                @foreach($bagColors as $color)
                                    <option value="{{ $color->id }}">{{ $color->color }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-1">
                        <div class="form-group">
                            <label>Stitching:</label>
                            <select name="packing_items[0][stitching_id]" class="form-control select2">
                                <option value="">Select Stitching</option>
                                @foreach($stitchings as $stitching)
                                    <option value="{{ $stitching->id }}">{{ $stitching->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-1">
                        <div class="form-group">
                            <label>Packing Size (kg): <span class="text-danger">*</span></label>
                            <input type="number" name="packing_items[0][bag_size]" class="form-control bag-size"
                                step="0.01" min="0.01" required>
                        </div>
                    </div>
                    <div class="col-md-1">
                        <div class="form-group">
                            <label>No. of Bags: <span class="text-danger">*</span></label>
                            <input type="number" name="packing_items[0][no_of_bags]" class="form-control no-of-bags" min="1" required>
                        </div>
                    </div>
                    <div class="col-md-1">
                        <div class="form-group">
                            <label>Extra Bags:</label>
                            <input type="number" name="packing_items[0][extra_bags]" class="form-control extra-bags"
                                value="0">
                        </div>
                    </div>
                    <div class="col-md-1">
                        <div class="form-group">
                            <label>Extra Bags %:</label>
                            <input type="number" name="packing_items[0][extra_bags_percentage]" class="form-control extra-bags-percentage"
                                step="0.01" value="0">
                        </div>
                    </div>
                    <div class="col-md-1">
                        <div class="form-group">
                            <label>Empty Bags:</label>
                            <input type="number" name="packing_items[0][empty_bags]" class="form-control empty-bags"
                                value="0">
                        </div>
                    </div>
                    <div class="col-md-1">
                        <div class="form-group">
                            <label>Empty Bags %:</label>
                            <input type="number" name="packing_items[0][empty_bags_percentage]" class="form-control empty-bags-percentage"
                                step="0.01" value="0">
                        </div>
                    </div>
                    <div class="col-md-1">
                        <div class="form-group">
                            <label>Total Bags:</label>
                            <input type="number" min="0" name="packing_items[0][total_bags]"
                                class="form-control total-bags" readonly>
                        </div>
                    </div>
                    <div class="col-md-1">
                        <div class="form-group">
                            <label>Total KGs:</label>
                            <input type="number" name="packing_items[0][total_kgs]" class="form-control total-kgs"
                                step="0.01" readonly>
                        </div>
                    </div>
                    <div class="col-md-1">
                        <div class="form-group">
                            <label>Metric Tons:</label>
                            <input type="number" name="packing_items[0][metric_tons]" class="form-control metric-tons"
                                step="0.01" min="0" readonly>
                        </div>
                    </div>
                    <div class="col-md-1">
                        <div class="form-group">
                            <label>Stuffing (MTs):</label>
                            <input type="number" name="packing_items[0][stuffing_in_container]" 
                                value="0" class="form-control stuffing" step="0.01" min="0">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>No. of Containers:</label>
                            <input type="number" name="packing_items[0][no_of_containers]"
                                class="form-control containers" value="0" min="0">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Min Weight Empty Bags (g):</label>
                            <input type="number" name="packing_items[0][min_weight_empty_bags]"
                                class="form-control min-weight" value="0" min="0" step="0.01">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Delivery Date:</label>
                            <input type="date" name="packing_items[0][delivery_date]" class="form-control">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Fumigation By: <span class="text-danger fumigation-required-star" style="display: none;">*</span></label>
                            <select name="packing_items[0][fumigation_company_id][]" class="form-control select2"
                                multiple data-placeholder="Select Fumigation Company">
                                <option value="null">N/A</option>
                                @foreach($fumigationCompanies as $company)
                                    <option value="{{ $company->id }}">{{ $company->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Fumigation Type:</label>
                            <select name="packing_items[0][fumigation_type]" class="form-control select2" data-placeholder="Select Fumigation Type">
                                <option value="">Select Fumigation Type</option>
                                @foreach($fumigationTypes as $fType)
                                    <option value="{{ $fType }}">{{ ucfirst($fType) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Attachment:</label>
                            <div class="existing-attachment-wrap"></div>
                            <input type="file" name="packing_items[0][attachment]" class="form-control packing-attachment" accept="image/*,application/pdf,.doc,.docx">
                        </div>
                    </div>
                    <!-- <div class="col-md-2">
                        <div class="form-group">
                            <label>Brand:</label>
                            <select name="packing_items[0][brand_id]" class="form-control">
                                <option value="">Select Brand</option>
                                @foreach($brands as $brand)
                                    <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Bag Color:</label>
                            <select name="packing_items[0][bag_color_id]" class="form-control">
                                <option value="">Select Color</option>
                                @foreach($bagColors as $color)
                                    <option value="{{ $color->id }}">{{ $color->color }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div> -->

                    <!-- <div class="col-md-1">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <button type="button"
                                class="btn btn-sm btn-info duplicate-packing-item form-control">Duplicate</button>
                        </div>
                    </div> -->

                    <!-- Master Packing Section -->
                    <div class="col-md-12 mt-4">
                        <div class="card border-primary shadow-sm">
                            <div
                                class="header-heading-sepration rounded-0 d-flex justify-content-between align-items-center">
                                <h6 class="mb-0 font-weight-bold">Master Packing</h6>
                                <button type="button" class="btn btn-sm btn-primary add-sub-packing-item"
                                    data-index="0">
                                    <i class="ft-plus"></i> Add Master Packing Item
                                </button>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive special">
                                    <table class="table table-bordered table-sm mb-0">
                                        <thead class="thead-light">
                                            <tr>
                                                <th class="col-2">Bag Type</th>

                                                <th>Bag Size </th>
                                                <th>Packing Size (kg)</th>
                                                <th>No of Primary Bags fit in master bag</th>
                                                <th>No. of Bags</th>
                                                <th>Empty Bags</th>
                                                <th>Empty Bags %</th>
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
                                        <tbody class="sub-packing-items-container" data-index="0">
                                            <!-- Master packing items will be added here -->
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
            </div>
        </div>

        <!-- Operational Details -->
        <div class="col-md-12">
            <h6 class="header-heading-sepration">Operational Details</h6>
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Inspection By:</label>
                        <select name="inspection_company_id[]" class="form-control select2" multiple data-placeholder="Select Inspection Company">
                            <option value="null">N/A</option>
                            @foreach($inspectionCompanies as $company)
                                <option value="{{ $company->id }}">{{ $company->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <!-- <div class="col-md-4">
                    <div class="form-group">
                        <label>Fumigation By:</label>
                        <select name="fumigation_company_id[]" class="form-control select2" multiple data-placeholder="Select Fumigation Company">
                            <option value="null">N/A</option>
                            @foreach($fumigationCompanies as $company)
                                <option value="{{ $company->id }}">{{ $company->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div> -->
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Load From/Location:</label>
                        <select name="arrival_locations[]" class="form-control select2" multiple>
                            @foreach($arrivalLocations as $location)
                                <option value="{{ $location->id }}">{{ $location->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <!-- <div class="col-md-4">
                    <div class="form-group">
                        <label>Delivery Date:</label>
                        <input type="date" name="delivery_date" class="form-control">
                    </div>
                </div> -->
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Loading Date:</label>
                        <input type="date" name="loading_date" class="form-control">
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="form-group">
                        <label>Packing Description:</label>
                        <textarea name="packing_description" class="form-control" rows="4"></textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- Container Protection & Packing Materials -->
        <div class="col-md-12" id="containerProtectionSection">
            <h6 class="header-heading-sepration d-flex justify-content-between align-items-center">
                Container Protection & Packing Materials
                <button type="button" class="btn btn-sm btn-success" id="addContainerProtectionItem">
                    <i class="ft-plus"></i> Add More
                </button>
            </h6>
            <div id="containerProtectionItems">
                <!-- Items will be added here dynamically -->
            </div>
        </div>
    </div>

                        <div class="row mt-3">
                            <div class="col-12 text-right">
                                <a href="{{ route('job-orders.index') }}" onclick="loadPageContent('{{ route('job-orders.index') }}')" class="btn btn-danger mr-1">Cancel</a>
                                <button type="submit" class="btn btn-primary submitbutton">Save Job Order</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Hidden Template for Container Protection & Packing Materials -->
<div class="container-protection-item-template d-none">
    <div class="container-protection-item row border-bottom pb-3 mb-3 w-100 mx-auto">
        <div class="col-md-5">
            <div class="form-group">
                <label>Product:</label>
                <select name="container_protection_items[INDEX][product_id]"
                    class="form-control select2 container-protection-product">
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
                <select name="packing_items[INDEX][sub_items][SUB_INDEX][bag_type_id]"
                    class="form-control form-control-sm select2 sub-bag-type">
                    <option value="">Select Bag Type</option>
                    @foreach($bagTypes as $bagType)
                        <option value="{{ $bagType->id }}">{{ $bagType->name }}</option>
                    @endforeach
                </select>
            </td>
            <td>
                <select type="number" name="packing_items[INDEX][sub_items][SUB_INDEX][bag_size_id]"
                    class="form-control form-control-sm select2 sub-bag-size" placeholder="Select Size">
                    <option value="">Select Size</option>
                    @foreach($sizes as $size)
                        <option value="{{ $size->id }}">{{ $size->size }}</option>
                    @endforeach
                </select>
            </td>
            <td>
                <input type="number" step="0.01" name="packing_items[INDEX][sub_items][SUB_INDEX][packing_size]" class="form-control form-control-sm sub-calculated-packing-size" placeholder="Enter / Auto calc">
            </td>
            <td>
                <input type="number" name="packing_items[INDEX][sub_items][SUB_INDEX][no_of_primary_bags]"
                    class="form-control form-control-sm sub-no-of-primary-bags"
                    placeholder="Enter No of Primary Bags fit in master bag">
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
                <input type="number" name="packing_items[INDEX][sub_items][SUB_INDEX][empty_bags_percentage]"
                    class="form-control form-control-sm sub-empty-bags-percentage" value="0" min="0" step="0.01">
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
                <select name="packing_items[INDEX][sub_items][SUB_INDEX][stitching_id]"
                    class="form-control form-control-sm select2 sub-stitching" placeholder="Select Stitching">
                    <option value="">Select Stitching</option>
                    @foreach($stitchings as $stitching)
                        <option value="{{ $stitching->id }}">{{ $stitching->name }}</option>
                    @endforeach
                </select>
            </td>

            <td class="col-1">
                <select name="packing_items[INDEX][sub_items][SUB_INDEX][bag_color_id]"
                    class="form-control form-control-sm select2 sub-bag-color">
                    <option value="">Select Color</option>
                    @foreach($bagColors as $color)
                        <option value="{{ $color->id }}">{{ $color->color }}</option>
                    @endforeach
                </select>
            </td>

            <td class="col-1">
                <select name="packing_items[INDEX][sub_items][SUB_INDEX][brand_id]"
                    class="form-control form-control-sm select2 sub-brand">
                    <option value="">Select Brand</option>
                    @foreach($brands as $brand)
                        <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                    @endforeach
                </select>
            </td>

            <td class="col-1">
                <select name="packing_items[INDEX][sub_items][SUB_INDEX][thread_color_id]"
                    class="form-control form-control-sm select2 sub-thread-color">
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
@endsection

@section('script')
<script>
    $(document).ready(function () {
        // Remove all existing event handlers to prevent multiple bindings when modal is loaded multiple times
        // Clean up both create and edit handlers to prevent duplication when switching between modals
        $(document).off('.jobOrderEdit .jobOrderCreate');
        $('#productSelect').off('.jobOrderEdit .jobOrderCreate');
        $('input[name="job_order_date"]').off('.jobOrderEdit .jobOrderCreate');

        // Destroy any existing Select2 instances to prevent multiple initializations
        $('.select2').each(function () {
            if ($(this).data('select2')) {
                $(this).select2('destroy');
            }
        });

        // Initialize Select2 for all multi-selects (excluding templates)
        $('.select2').not('.sub-packing-item-template .select2').not('.container-protection-item-template .select2').select2();

        // Product selection change
        $('#mainProductSelect').off('change.jobOrderCreate').on('change.jobOrderCreate', function () {
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

        // Export Order selection change
        $('#exportOrderSelect').off('change.jobOrderCreate').on('change.jobOrderCreate', function() {
            let id = $(this).val();
            if(!id) {
                $('#mainProductSelect').prop('disabled', false);
                $('#mainProductSelect').next('.select2-container').css('pointer-events', '');
                $('#hidden_product_id').remove();
                $('#mainProductSelect').val('').trigger('change.select2');

                $('input[name="ref_no"]').val('');
                $('textarea[name="other_specifications"]').val('');
                $('#export-order-quantity-info').html('').hide();

                $('#specificationsSection').hide();
                $('#productSpecs').html(`
                    <div class="alert bg-light-warning mb-2 alert-light-warning" role="alert">
                        <i class="ft-info mr-1"></i>
                        <strong>No specifications found!</strong> Please select a commodity first!
                    </div>
                `);

                resetPackingItemsToDefault();
                updateFumigationRequirement();
                return;
            }

            $.get('{{ url("production/get-export-order-details") }}/' + id, function(data) {
                // Fill simple text fields
                if (data.ref_no) {
                    $('input[name="ref_no"]').val(data.ref_no);
                }
                if (data.other_specifications) {
                    $('textarea[name="other_specifications"]').val(data.other_specifications);
                }

                // Set product and make it readonly
                $('#mainProductSelect').val(data.product_id).trigger('change');
                $('#mainProductSelect').prop('disabled', true);
                $('#mainProductSelect').next('.select2-container').css('pointer-events', 'none');

                // Hidden input to submit product_id when select is disabled
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

                // Wait for product specs AJAX to load then overwrite with export order specs
                setTimeout(function() {
                    buildSpecsTable(data.specifications);
                }, 900);

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

                // Rebuild packing rows
                addPackingRowsFromExportOrder(data.packing_items);
                updateFumigationRequirement();
            }).fail(function(xhr) {
                console.error('Export Order Details Error:', xhr.status, xhr.responseText);
                alert('Failed to load export order details. Please check console.');
            });
        });

        // Job Order Request selection change
        $('#jobOrderRequestSelect').off('change.jobOrderCreate').on('change.jobOrderCreate', function() {
            let id = $(this).val();
            if(!id) {
                $('input[name="ref_no"]').val('');
                $('textarea[name="remarks"]').val('');
                $('textarea[name="order_description"]').val('');
                $('textarea[name="other_specifications"]').val('');
                $('textarea[name="packing_description"]').val('');
                $('input[name="loading_date"]').val('');
                $('select[name="attention_to[]"]').val([]).trigger('change');
                $('select[name="inspection_company_id[]"]').val([]).trigger('change');
                $('select[name="arrival_locations[]"]').val([]).trigger('change');
                $('#cropYearSelect').val('').trigger('change');
                $('#exportOrderSelect').prop('disabled', false);
                $('#exportOrderSelect').next('.select2-container').css({'pointer-events': '', 'opacity': ''});
                $('#hidden_export_order_id').remove();
                $('#exportOrderSelect').val('').trigger('change.select2');
                $('#export-order-quantity-info').html('').hide();
                $('#job-order-request-quantity-info').html('').hide();
                updateFumigationRequirement();

                $('#mainProductSelect').prop('disabled', false);
                $('#mainProductSelect').next('.select2-container').css('pointer-events', '');
                $('#hidden_product_id').remove();
                $('#mainProductSelect').val('').trigger('change.select2');

                $('#specificationsSection').hide();
                $('#productSpecs').html(`
                    <div class="alert bg-light-warning mb-2 alert-light-warning" role="alert">
                        <i class="ft-info mr-1"></i>
                        <strong>No specifications found!</strong> Please select a commodity first!
                    </div>
                `);

                resetPackingItemsToDefault();
                $('#containerProtectionItems').empty();
                updateFumigationRequirement();
                return;
            }

            $.get('{{ url("production/job-orders/get-request-details") }}/' + id, function(data) {
                // Show quantity info
                let totalKg = data.total_request_kgs || 0;
                let consumedKg = data.consumed_kgs || 0;
                let remainingKg = data.remaining_kgs || 0;
                let totalMt = data.total_request_mt || (totalKg / 1000).toFixed(3);
                let remainingMt = data.remaining_mt || (remainingKg / 1000).toFixed(3);

                let reqAlertHtml = `
                    <div class="mb-2 alert alert-info py-2" style="font-weight: 500;">
                        <i class="ft-info mr-1"></i>
                        Job Order Request Capacity: Total: <strong>${Number(totalKg).toLocaleString()} KG (${totalMt} MT)</strong> | Consumed: <strong>${Number(consumedKg).toLocaleString()} KG</strong> | Remaining: <strong class="text-success">${Number(remainingKg).toLocaleString()} KG (${remainingMt} MT)</strong>
                    </div>
                `;
                $('#job-order-request-quantity-info').html(reqAlertHtml).show();

                // Fill simple text fields
                if (data.ref_no) {
                    $('input[name="ref_no"]').val(data.ref_no);
                }
                if (data.remarks) {
                    $('textarea[name="remarks"]').val(data.remarks);
                }
                if (data.order_description) {
                    $('textarea[name="order_description"]').val(data.order_description);
                }
                if (data.other_specifications) {
                    $('textarea[name="other_specifications"]').val(data.other_specifications);
                }
                if (data.packing_description) {
                    $('textarea[name="packing_description"]').val(data.packing_description);
                }
                if (data.loading_date) {
                    $('input[name="loading_date"]').val(data.loading_date);
                }

                // Attention To
                if (data.attention_to && data.attention_to.length > 0) {
                    let attentionValues = data.attention_to.map(String);
                    $('select[name="attention_to[]"]').val(attentionValues).trigger('change');
                }

                // Inspection Company
                if (data.inspection_company_id && data.inspection_company_id.length > 0) {
                    let inspectionValues = data.inspection_company_id.map(String);
                    $('select[name="inspection_company_id[]"]').val(inspectionValues).trigger('change');
                }

                // Crop Year
                if (data.crop_year_id) {
                    $('#cropYearSelect').val(data.crop_year_id).trigger('change');
                }

                // Export Order (if present in request, select, fix and disable it)
                $('#hidden_export_order_id').remove();

                if (data.export_order_id) {
                    if ($('#exportOrderSelect option[value="' + data.export_order_id + '"]').length === 0) {
                        let optText = data.export_order_voucher_no || ('Export Order #' + data.export_order_id);
                        $('#exportOrderSelect').append(new Option(optText, data.export_order_id, true, true));
                    }
                    $('#exportOrderSelect').val(data.export_order_id).trigger('change.select2');
                    $('#exportOrderSelect').prop('disabled', true);
                    $('#exportOrderSelect').next('.select2-container').css({'pointer-events': 'none', 'opacity': '0.75'});
                    $('#exportOrderSelect').after('<input type="hidden" name="export_order_id" id="hidden_export_order_id" value="' + data.export_order_id + '">');
                    updateFumigationRequirement();
                } else {
                    $('#exportOrderSelect').prop('disabled', false);
                    $('#exportOrderSelect').next('.select2-container').css({'pointer-events': '', 'opacity': ''});
                    $('#exportOrderSelect').val('').trigger('change.select2');
                    updateFumigationRequirement();
                }

                // Company Location & Arrival Locations
                let arrivalLocationsToSelect = (data.arrival_locations || []).map(String);
                let companyLocationToSet = data.company_location_id || $('#company_location_id').val();

                window.isPopulatingFromJobOrderRequest = true;

                if (data.company_location_id) {
                    $('#company_location_id').val(data.company_location_id).trigger('change.select2');
                    fetchJobOrderNumber();
                }

                // Load arrival locations and select saved arrival locations
                let locIds = companyLocationToSet ? [companyLocationToSet] : [];
                $.ajax({
                    url: "{{ route('job-orders.get-arrival-locations') }}",
                    type: "GET",
                    data: {
                        company_location_ids: locIds,
                        include_arrival_ids: arrivalLocationsToSelect
                    },
                    success: function(response) {
                        var arrivalSelect = $('select[name="arrival_locations[]"]');
                        arrivalSelect.empty();
                        $.each(response, function(index, location) {
                            var isSelected = arrivalLocationsToSelect.includes(location.id.toString());
                            var newOption = new Option(location.name, location.id, isSelected, isSelected);
                            arrivalSelect.append(newOption);
                        });
                        arrivalSelect.val(arrivalLocationsToSelect).trigger('change');
                    },
                    complete: function() {
                        window.isPopulatingFromJobOrderRequest = false;
                    }
                });

                // Commodity / Product (remain editable)
                if (data.product_id) {
                    $('#mainProductSelect').prop('disabled', false);
                    $('#mainProductSelect').next('.select2-container').css('pointer-events', '');
                    $('#hidden_product_id').remove();
                    $('#mainProductSelect').val(data.product_id).trigger('change.select2');
                    $('#specificationsSection').show();

                    if (data.specifications && data.specifications.length > 0) {
                        buildSpecsTable(data.specifications);
                    } else {
                        $.get('{{ route("get.product_specs", "") }}/' + data.product_id, function (specsHtml) {
                            $('#productSpecs').html(specsHtml);
                        });
                    }
                }

                // Packing Details & Master Packing Sub Items
                if (data.packing_items && data.packing_items.length > 0) {
                    addPackingRowsFromJobOrderRequest(data.packing_items);
                    updateFumigationRequirement();
                }

                // Container Protection Items
                if (data.container_protection_items && data.container_protection_items.length > 0) {
                    addContainerProtectionRows(data.container_protection_items);
                }

            }).fail(function(xhr) {
                window.isPopulatingFromJobOrderRequest = false;
                console.error('Job Order Request Details Error:', xhr.status, xhr.responseText);
                alert('Failed to load job order request details. Please check console.');
            });
        });

        function buildSpecsTable(specs) {
            if(!specs || specs.length === 0) return;
            
            let html = '<div class="table-responsive"><table class="table table-bordered table-striped"><thead class="thead-dark"><tr><th width="40%">Specification Name</th><th width="30%">Value</th><th width="30%">UOM</th></tr></thead><tbody>';
            specs.forEach(function(spec, idx) {
                let specName = spec.product_slab_type ? spec.product_slab_type.name : spec.spec_name;
                let uom = spec.product_slab_type ? spec.product_slab_type.qc_symbol : (spec.uom || '');
                let valueType = spec.value_type || 'min';
                let slabTypeId = spec.product_slab_type_id || '';
                
                html += `<tr>
                    <td>
                        <strong>${specName}</strong>
                        <input type="hidden" name="specifications[${idx}][product_slab_type_id]" value="${slabTypeId}">
                        <input type="hidden" name="specifications[${idx}][spec_name]" value="${spec.spec_name || specName}">
                        <input type="hidden" name="specifications[${idx}][uom]" value="${uom}">
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

        function resetPackingItemsToDefault() {
            let container = $('#packingItems');
            let firstRow = container.find('.packing-item').first();
            if (!firstRow.length) return;
            let cleanRow = firstRow.clone();

            cleanRow.find('.select2-container').remove();
            cleanRow.find('select').removeClass('select2-hidden-accessible').removeAttr('data-select2-id').show();
            cleanRow.find('option').removeAttr('data-select2-id');

            cleanRow.find('input, select, textarea').each(function() {
                let name = $(this).attr('name');
                if (name) {
                    name = name.replace(/\[\d+\]/, '[0]');
                    $(this).attr('name', name);
                }
            });

            cleanRow.find('select').prop('selectedIndex', 0);
            cleanRow.find('select[multiple]').val([]);

            cleanRow.find('input[type="text"], input[type="date"]').val('');
            cleanRow.find('.bag-size, .no-of-bags, .total-bags, .total-kgs, .metric-tons').val('');
            cleanRow.find('.extra-bags, .extra-bags-percentage, .empty-bags, .stuffing, .containers, .min-weight').val('0');
            cleanRow.find('.packing-company-location-id').val($('#company_location_id').val() || '');

            cleanRow.find('.sub-packing-items-container').attr('data-index', 0).empty();
            cleanRow.find('.existing-attachment-wrap').empty();
            cleanRow.find('.old-attachment-input').remove();
            cleanRow.find('input[type="file"]').val('');
            cleanRow.find('.add-sub-packing-item').attr('data-index', 0);
            cleanRow.attr('data-index', 0);

            container.empty().append(cleanRow);

            cleanRow.find('select.select2').each(function() {
                $(this).select2({
                    width: '100%',
                    dropdownParent: $(this).closest('.table-responsive').length ? $(this).closest('.table-responsive') : $('body')
                });
            });

            reindexPackingItems();
            updateFumigationRequirement();
        }

        function updateFumigationRequirement() {
            let hasExportOrder = !!$('#exportOrderSelect').val() || !!$('#hidden_export_order_id').val();
            
            $('.packing-item').each(function() {
                let formGroup = $(this).find('select[name*="[fumigation_company_id][]"]').closest('.form-group');
                let label = formGroup.find('label').first();
                let select = formGroup.find('select[name*="[fumigation_company_id][]"]');
                let naOption = select.find('option[value="null"]');
                
                if (hasExportOrder) {
                    if (label.find('.fumigation-required-star').length === 0) {
                        label.append(' <span class="text-danger fumigation-required-star">*</span>');
                    } else {
                        label.find('.fumigation-required-star').show();
                    }
                    naOption.prop('disabled', true).hide();
                    
                    let currentVal = select.val();
                    if (Array.isArray(currentVal) && currentVal.includes('null')) {
                        let filtered = currentVal.filter(v => v !== 'null');
                        select.val(filtered).trigger('change.select2');
                    }
                } else {
                    label.find('.fumigation-required-star').hide();
                    naOption.prop('disabled', false).show();
                    select.next('.select2-container').find('.select2-selection').css('border', '');
                }
            });
        }

        function addPackingRowsFromExportOrder(items) {
            if (!items || items.length === 0) {
                resetPackingItemsToDefault();
                return;
            }

            let container = $('#packingItems');
            let templateRow = container.find('.packing-item').first().clone();
            // Clean up select2 from template
            templateRow.find('.select2-container').remove();
            templateRow.find('select').removeClass('select2-hidden-accessible').removeAttr('data-select2-id').show();
            
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

                // Location sync
                row.find(`select[name="packing_items[${index}][company_location_id]"]`).val(item.company_location_id || $('#company_location_id').val());

                row.find(`select[name="packing_items[${index}][bag_condition_id]"]`).val(item.bag_condition_id);
                row.find(`select[name="packing_items[${index}][bag_color_id]"]`).val(item.bag_color_id);
                row.find(`select[name="packing_items[${index}][thread_color_id]"]`).val(item.thread_color_id);
                row.find(`select[name="packing_items[${index}][stitching_id]"]`).val(item.stitching_id);
                
                row.find(`input[name="packing_items[${index}][bag_size]"]`).val(item.bag_size);
                row.find(`input[name="packing_items[${index}][no_of_bags]"]`).val(item.no_of_bags);
                row.find(`input[name="packing_items[${index}][extra_bags]"]`).val(item.extra_bags);
                row.find(`input[name="packing_items[${index}][extra_bags_percentage]"]`).val(item.extra_bags_percentage || (item.no_of_bags > 0 && item.extra_bags > 0 ? ((item.extra_bags / item.no_of_bags) * 100).toFixed(2) : 0));
                row.find(`input[name="packing_items[${index}][empty_bags]"]`).val(item.empty_bags);
                row.find(`input[name="packing_items[${index}][empty_bags_percentage]"]`).val(item.empty_bags_percentage || (item.no_of_bags > 0 && item.empty_bags > 0 ? ((item.empty_bags / item.no_of_bags) * 100).toFixed(2) : 0));
                row.find(`input[name="packing_items[${index}][total_bags]"]`).val(item.total_bags);
                row.find(`input[name="packing_items[${index}][total_kgs]"]`).val(item.total_kgs);
                row.find(`input[name="packing_items[${index}][metric_tons]"]`).val(item.metric_tons);
                row.find(`input[name="packing_items[${index}][stuffing_in_container]"]`).val(item.stuffing_in_container);
                row.find(`input[name="packing_items[${index}][no_of_containers]"]`).val(item.no_of_containers);
                row.find(`input[name="packing_items[${index}][min_weight_empty_bags]"]`).val(item.min_weight_empty_bags);
                
                // Fumigation sync
                if (item.fumigation_company_id) {
                    let fumigationSelect = row.find(`select[name="packing_items[${index}][fumigation_company_id][]"]`);
                    fumigationSelect.val(item.fumigation_company_id);
                }
                if (item.fumigation_type) {
                    let fumigationTypeSelect = row.find(`select[name="packing_items[${index}][fumigation_type]"]`);
                    fumigationTypeSelect.val(item.fumigation_type);
                }
                
                // Clear sub items container
                let subContainer = row.find('.sub-packing-items-container');
                subContainer.attr('data-index', index);
                subContainer.empty();
                row.find('.add-sub-packing-item').attr('data-index', index);

                container.append(row);
                
                // Initialize Select2 and trigger change to show selected values
                row.find('select.select2').each(function() {
                    $(this).select2({
                        width: '100%',
                        dropdownParent: $(this).closest('.table-responsive').length ? $(this).closest('.table-responsive') : $('body')
                    });
                    if ($(this).val()) {
                        $(this).trigger('change');
                    }
                });

                // Trigger percentage calculation manually
                if (item.no_of_bags > 0 && item.extra_bags > 0) {
                    let perc = (item.extra_bags / item.no_of_bags) * 100;
                    row.find(`input[name="packing_items[${index}][extra_bags_percentage]"]`).val(perc.toFixed(2));
                }
                if (item.no_of_bags > 0 && item.empty_bags > 0) {
                    let percEmpty = (item.empty_bags / item.no_of_bags) * 100;
                    row.find(`input[name="packing_items[${index}][empty_bags_percentage]"]`).val(percEmpty.toFixed(2));
                }
                
                // Inside sub items
                if(item.sub_items && item.sub_items.length > 0) {
                    item.sub_items.forEach(function(sub, sIdx) {
                        // Use a more robust way to get the template HTML
                        let $template = $('.sub-packing-item-template tbody');
                        let subRowHtml = $template.html();
                        
                        // Replace placeholders securely - SUB_INDEX first so INDEX doesn't partially match it!
                        subRowHtml = subRowHtml.replace(/\[SUB_INDEX\]/g, '[' + sIdx + ']').replace(/\[INDEX\]/g, '[' + index + ']');
                        let subRow = $(subRowHtml);
                        
                        // Sub Bag Type mapping
                        let subBagTypeSelect = subRow.find(`select[name="packing_items[${index}][sub_items][${sIdx}][bag_type_id]"]`);
                        if (sub.bag_type_id) {
                            subBagTypeSelect.val(sub.bag_type_id);
                        } else if (sub.bag_product_id) {
                            subBagTypeSelect.val(sub.bag_product_id);
                        }

                        subRow.find(`select[name="packing_items[${index}][sub_items][${sIdx}][bag_size_id]"]`).val(sub.bag_size_id);
                        subRow.find(`select[name="packing_items[${index}][sub_items][${sIdx}][stitching_id]"]`).val(sub.stitching_id);
                        subRow.find(`select[name="packing_items[${index}][sub_items][${sIdx}][bag_color_id]"]`).val(sub.bag_color_id);
                        subRow.find(`select[name="packing_items[${index}][sub_items][${sIdx}][brand_id]"]`).val(sub.brand_id);
                        subRow.find(`select[name="packing_items[${index}][sub_items][${sIdx}][thread_color_id]"]`).val(sub.thread_color_id);
                        
                        let calcPackingSize = sub.packing_size || (parseFloat(item.bag_size) * parseInt(sub.no_of_primary_bags));
                        subRow.find(`input[name="packing_items[${index}][sub_items][${sIdx}][packing_size]"]`).val(calcPackingSize || '');
                        subRow.find(`input[name="packing_items[${index}][sub_items][${sIdx}][no_of_primary_bags]"]`).val(sub.no_of_primary_bags);
                        subRow.find(`input[name="packing_items[${index}][sub_items][${sIdx}][no_of_bags]"]`).val(sub.no_of_bags);
                        subRow.find(`input[name="packing_items[${index}][sub_items][${sIdx}][empty_bags]"]`).val(sub.empty_bags);
                        subRow.find(`input[name="packing_items[${index}][sub_items][${sIdx}][empty_bags_percentage]"]`).val(sub.empty_bags_percentage || (sub.no_of_bags > 0 && sub.empty_bags > 0 ? ((sub.empty_bags / sub.no_of_bags) * 100).toFixed(2) : 0));
                        subRow.find(`input[name="packing_items[${index}][sub_items][${sIdx}][extra_bags]"]`).val(sub.extra_bags);
                        subRow.find(`input[name="packing_items[${index}][sub_items][${sIdx}][extra_bags_percentage]"]`).val(sub.extra_bags_percentage || (sub.no_of_bags > 0 && sub.extra_bags > 0 ? ((sub.extra_bags / sub.no_of_bags) * 100).toFixed(2) : 0));
                        subRow.find(`input[name="packing_items[${index}][sub_items][${sIdx}][empty_bag_weight]"]`).val(sub.empty_bag_weight);
                        subRow.find(`input[name="packing_items[${index}][sub_items][${sIdx}][total_bags]"]`).val(sub.total_bags);

                        subContainer.append(subRow);
                        subRow.find('select.select2').select2({width: '100%'});
                        
                        // Trigger sub total bags calculation
                        subRow.find('.sub-no-of-bags').trigger('input');
                    });
                }

                // Trigger total bags calculation AFTER sub-items are added
                row.find('.no-of-bags').trigger('input');
            });
            reindexPackingItems();
            updateFumigationRequirement();
        }

        function addPackingRowsFromJobOrderRequest(items) {
            if (!items || items.length === 0) {
                resetPackingItemsToDefault();
                return;
            }

            let container = $('#packingItems');
            let templateRow = container.find('.packing-item').first().clone();
            // Clean up select2 from template
            templateRow.find('.select2-container').remove();
            templateRow.find('select').removeClass('select2-hidden-accessible').removeAttr('data-select2-id').show();

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

                // Set location
                row.find('.packing-company-location-id').val(item.company_location_id || $('#company_location_id').val());

                // Set packing dropdown values
                row.find(`select[name="packing_items[${index}][brand_id]"]`).val(item.brand_id);
                row.find(`select[name="packing_items[${index}][bag_product_id]"]`).val(item.bag_product_id);
                row.find(`select[name="packing_items[${index}][bag_condition_id]"]`).val(item.bag_condition_id);
                row.find(`select[name="packing_items[${index}][bag_color_id]"]`).val(item.bag_color_id);
                row.find(`select[name="packing_items[${index}][thread_color_id]"]`).val(item.thread_color_id);
                row.find(`select[name="packing_items[${index}][stitching_id]"]`).val(item.stitching_id);

                // Set packing inputs
                row.find(`input[name="packing_items[${index}][bag_size]"]`).val(item.bag_size);
                row.find(`input[name="packing_items[${index}][no_of_bags]"]`).val(item.no_of_bags);
                row.find(`input[name="packing_items[${index}][extra_bags]"]`).val(item.extra_bags);
                row.find(`input[name="packing_items[${index}][extra_bags_percentage]"]`).val(item.extra_bags_percentage || (item.no_of_bags > 0 && item.extra_bags > 0 ? ((item.extra_bags / item.no_of_bags) * 100).toFixed(2) : 0));
                row.find(`input[name="packing_items[${index}][empty_bags]"]`).val(item.empty_bags);
                row.find(`input[name="packing_items[${index}][empty_bags_percentage]"]`).val(item.empty_bags_percentage || (item.no_of_bags > 0 && item.empty_bags > 0 ? ((item.empty_bags / item.no_of_bags) * 100).toFixed(2) : 0));
                row.find(`input[name="packing_items[${index}][total_bags]"]`).val(item.total_bags);
                row.find(`input[name="packing_items[${index}][total_kgs]"]`).val(item.total_kgs);
                row.find(`input[name="packing_items[${index}][metric_tons]"]`).val(item.metric_tons);
                row.find(`input[name="packing_items[${index}][stuffing_in_container]"]`).val(item.stuffing_in_container);
                row.find(`input[name="packing_items[${index}][no_of_containers]"]`).val(item.no_of_containers);
                row.find(`input[name="packing_items[${index}][min_weight_empty_bags]"]`).val(item.min_weight_empty_bags);
                if (item.delivery_date) {
                    row.find(`input[name="packing_items[${index}][delivery_date]"]`).val(item.delivery_date);
                }

                // Fumigation sync
                if (item.fumigation_company_id) {
                    let fumigationSelect = row.find(`select[name="packing_items[${index}][fumigation_company_id][]"]`);
                    fumigationSelect.val(item.fumigation_company_id);
                }
                if (item.fumigation_type) {
                    let fumigationTypeSelect = row.find(`select[name="packing_items[${index}][fumigation_type]"]`);
                    fumigationTypeSelect.val(item.fumigation_type);
                }

                // Attachment sync
                if (item.attachment_path || item.attachment) {
                    let attachPath = item.attachment_path || item.attachment;
                    let attachUrl = item.attachment || ('/storage/' + attachPath);
                    row.find('.existing-attachment-wrap').html(`
                        <a href="${attachUrl}" target="_blank" class="btn btn-sm btn-info mb-1">
                            <i class="ft-eye"></i> View Attachment
                        </a>
                        <input type="hidden" name="packing_items[${index}][old_attachment]" value="${attachPath}" class="old-attachment-input">
                    `);
                }

                // Clear sub items container
                let subContainer = row.find('.sub-packing-items-container');
                subContainer.attr('data-index', index);
                subContainer.empty();
                row.find('.add-sub-packing-item').attr('data-index', index);

                container.append(row);

                // Initialize Select2 for packing item selects
                row.find('select.select2').each(function() {
                    $(this).select2({
                        width: '100%',
                        dropdownParent: $(this).closest('.table-responsive').length ? $(this).closest('.table-responsive') : $('body')
                    });
                    if ($(this).val()) {
                        $(this).trigger('change');
                    }
                });

                // Master Packing sub items
                if (item.sub_items && item.sub_items.length > 0) {
                    item.sub_items.forEach(function(sub, sIdx) {
                        let $template = $('.sub-packing-item-template tbody');
                        let subRowHtml = $template.html();

                        subRowHtml = subRowHtml.replace(/\[SUB_INDEX\]/g, '[' + sIdx + ']').replace(/\[INDEX\]/g, '[' + index + ']');
                        let subRow = $(subRowHtml);

                        subRow.find(`select[name="packing_items[${index}][sub_items][${sIdx}][bag_type_id]"]`).val(sub.bag_type_id);
                        subRow.find(`select[name="packing_items[${index}][sub_items][${sIdx}][bag_size_id]"]`).val(sub.bag_size_id);
                        subRow.find(`select[name="packing_items[${index}][sub_items][${sIdx}][stitching_id]"]`).val(sub.stitching_id);
                        subRow.find(`select[name="packing_items[${index}][sub_items][${sIdx}][bag_color_id]"]`).val(sub.bag_color_id);
                        subRow.find(`select[name="packing_items[${index}][sub_items][${sIdx}][brand_id]"]`).val(sub.brand_id);
                        subRow.find(`select[name="packing_items[${index}][sub_items][${sIdx}][thread_color_id]"]`).val(sub.thread_color_id);

                        subRow.find(`input[name="packing_items[${index}][sub_items][${sIdx}][packing_size]"]`).val(sub.packing_size || (parseFloat(item.bag_size) * parseInt(sub.no_of_primary_bags)));
                        subRow.find(`input[name="packing_items[${index}][sub_items][${sIdx}][no_of_primary_bags]"]`).val(sub.no_of_primary_bags);
                        subRow.find(`input[name="packing_items[${index}][sub_items][${sIdx}][no_of_bags]"]`).val(sub.no_of_bags);
                        subRow.find(`input[name="packing_items[${index}][sub_items][${sIdx}][empty_bags]"]`).val(sub.empty_bags);
                        subRow.find(`input[name="packing_items[${index}][sub_items][${sIdx}][empty_bags_percentage]"]`).val(sub.empty_bags_percentage || (sub.no_of_bags > 0 && sub.empty_bags > 0 ? ((sub.empty_bags / sub.no_of_bags) * 100).toFixed(2) : 0));
                        subRow.find(`input[name="packing_items[${index}][sub_items][${sIdx}][extra_bags]"]`).val(sub.extra_bags);
                        subRow.find(`input[name="packing_items[${index}][sub_items][${sIdx}][extra_bags_percentage]"]`).val(sub.extra_bags_percentage || (sub.no_of_bags > 0 && sub.extra_bags > 0 ? ((sub.extra_bags / sub.no_of_bags) * 100).toFixed(2) : 0));
                        subRow.find(`input[name="packing_items[${index}][sub_items][${sIdx}][empty_bag_weight]"]`).val(sub.empty_bag_weight);
                        subRow.find(`input[name="packing_items[${index}][sub_items][${sIdx}][total_bags]"]`).val(sub.total_bags);

                        if (sub.attachment_path || sub.attachment) {
                            let subAttachPath = sub.attachment_path || sub.attachment;
                            let subAttachUrl = sub.attachment || ('/storage/' + subAttachPath);
                            let attachCell = subRow.find('.sub-attachment').parent();
                            attachCell.prepend(`
                                <a href="${subAttachUrl}" target="_blank" class="btn btn-sm btn-info mb-1 d-inline-block">View</a>
                                <input type="hidden" name="packing_items[${index}][sub_items][${sIdx}][old_attachment]" value="${subAttachPath}" class="sub-old-attachment">
                            `);
                        }

                        subContainer.append(subRow);
                        subRow.find('select.select2').select2({ width: '100%' });
                    });
                }

                // Trigger total bags calculation
                row.find('.no-of-bags').trigger('input');
            });
            reindexPackingItems();
            updateFumigationRequirement();
            let firstLocAfterPopulate = container.find('.packing-item').first().find('.packing-company-location-id').val();
            if (firstLocAfterPopulate && firstLocAfterPopulate !== $('#company_location_id').val()) {
                $('#company_location_id').val(firstLocAfterPopulate).trigger('change');
            }
        }

        function addContainerProtectionRows(items) {
            let container = $('#containerProtectionItems');
            container.empty();
            if (!items || items.length === 0) return;

            items.forEach(function(item, index) {
                var template = $('.container-protection-item-template').find('.container-protection-item').first();
                var newItem = template.clone(true, true);

                newItem.find('select.select2').each(function () {
                    var $select = $(this);
                    if ($select.data('select2')) {
                        $select.select2('destroy');
                    }
                    $select.siblings('.select2-container').remove();
                    $select.show().removeClass('select2-hidden-accessible');
                });

                newItem.find('input, select').each(function () {
                    var name = $(this).attr('name');
                    if (name) {
                        name = name.replace(/\[INDEX\]/g, '[' + index + ']');
                        $(this).attr('name', name);
                    }
                });

                newItem.find('.container-protection-product').val(item.product_id);
                newItem.find('.container-protection-quantity').val(item.quantity_per_container);

                $('#containerProtectionSection').show();
                container.append(newItem);
                newItem.find('select.select2').select2({ width: '100%' });
            });
        }

        // Add more packing items using clone
        $(document).off('click.jobOrderCreate', '#addPackingItem').on('click.jobOrderCreate', '#addPackingItem', function (e) {
            e.preventDefault();
            addNewPackingItem();
        });

        // Add new packing item function
        var isAddingItem = false;
        function addNewPackingItem() {
            if (isAddingItem) return;
            isAddingItem = true;

            var $firstItem = $('.packing-item').first();
            // 1. Clone without events & data to get a clean DOM copy
            var $newItem = $firstItem.clone(false); 

            var newIndex = $('.packing-item').length;

            // 2. Fix names & clear values
            $newItem.find('input, select').each(function () {
                var $this = $(this);
                var name = $this.attr('name');
                if (name) {
                    // Update index in name attribute (e.g., packing_items[0] -> packing_items[1])
                    name = name.replace(/\[\d+\]/, '[' + newIndex + ']');
                    $this.attr('name', name);
                }
                
                // Clear values
                if ($this.is('select')) {
                    if ($this.hasClass('packing-company-location-id')) {
                        $this.val($('#company_location_id').val() || '');
                    } else {
                        $this.prop('selectedIndex', 0);
                        $this.val('');
                    }
                } else {
                    if($this.hasClass('empty-bags') || $this.hasClass('extra-bags') || $this.hasClass('extra-bags-percentage') || $this.hasClass('min-weight') || $this.hasClass('containers') || $this.hasClass('stuffing')){
                        $this.val('0');
                    } else if ($this.hasClass('packing-company-location-id')) {
                        $this.val($('#company_location_id').val());
                    } else {
                        $this.val('');
                    }
                }

                // IMPORTANT: Remove all Select2 internal attributes and markers
                $this.removeClass('select2-hidden-accessible');
                $this.removeAttr('data-select2-id');
                $this.find('option').removeAttr('data-select2-id');
            });

            // 3. Remove any Select2 UI elements that were cloned
            $newItem.find('.select2-container').remove();

            // 4. Update data-index attributes for sub-items
            $newItem.find('.sub-packing-items-container').attr('data-index', newIndex);
            $newItem.find('.add-sub-packing-item').attr('data-index', newIndex);

            // 5. Clean sub-items container and attachments
            $newItem.find('.sub-packing-items-container').empty();
            $newItem.find('.existing-attachment-wrap').empty();
            $newItem.find('.old-attachment-input').remove();
            $newItem.find('input[type="file"]').val('');

            // 6. Reset calculation fields
            $newItem.find('.total-bags, .total-kgs, .metric-tons').val('0');

            // 7. Finally append to the DOM
            $('#packingItems').append($newItem);

            // 8. Initialize fresh Select2 ONLY for the NEW item
            $newItem.find('select.select2').each(function() {
                $(this).select2({
                    width: '100%'
                });
            });

            updateFumigationRequirement();

            isAddingItem = false;
        }

        // Duplicate packing item - PROPERLY FIXED VERSION
        $(document).off('click.jobOrderCreate', '.duplicate-packing-item').on('click.jobOrderCreate', '.duplicate-packing-item', function () {
            var currentItem = $(this).closest('.packing-item');

            // Pehle original item ki values capture karo BEFORE destroying Select2
            var originalValues = {};
            currentItem.find('select').each(function () {
                var $select = $(this);
                originalValues[$select.attr('name')] = $select.val();
            });

            // Ab clone karo WITHOUT destroying Select2 first
            var newItem = currentItem.clone();

            // Update indexes for new item
            var newIndex = $('.packing-item').length;
            newItem.find('input, select').each(function () {
                var name = $(this).attr('name');
                if (name) {
                    name = name.replace(/\[\d+\]/, '[' + newIndex + ']');
                    $(this).attr('name', name);
                }
            });

            // New item ke Select2 containers ko properly handle karo
            newItem.find('select').each(function () {
                var $select = $(this);

                // Select2 container remove karo
                $select.siblings('.select2-container').remove();
                $select.show().removeClass('select2-hidden-accessible');
                $select.next('.select2-container').remove();
            });

            // Insert after current item
            currentItem.after(newItem);

            // Re-initialize Select2 for duplicated item with ORIGINAL values
            newItem.find('select').each(function () {
                var $select = $(this);
                var originalName = $select.attr('name').replace(/\[\d+\]/, '[0]'); // Get original name
                var preservedValue = originalValues[originalName];

                $select.select2();
                if (preservedValue) {
                    $select.val(preservedValue).trigger('change');
                } else if ($select.hasClass('packing-company-location-id')) {
                    $select.val($('#company_location_id').val() || '').trigger('change');
                }
            });
            currentItem.find('select').select2();

            // Re-index all items
            reindexPackingItems();
            updateFumigationRequirement();
        });


        // Remove packing item
        $(document).off('click.jobOrderCreate', '.remove-packing-item').on('click.jobOrderCreate', '.remove-packing-item', function () {
            if ($('.packing-item').length > 1) {
                $(this).closest('.packing-item').remove();
                // Re-index remaining items
                reindexPackingItems();
                $('#company_location_id').trigger('change');
            }
        });

        // Add Sub Packing Item
        $(document).off('click.jobOrderCreate', '.add-sub-packing-item').on('click.jobOrderCreate', '.add-sub-packing-item', function (e) {
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
            // Fix: Standardize append to the end of the container
            container.append(newRow);
            
            // Re-initialize Select2 for the new row
            newRow.find('.select2').select2({ width: '100%' });

            // Initialize Select2 for new selects
            newRow.find('select.select2').each(function () {
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
        $(document).off('click.jobOrderCreate', '.remove-sub-packing-item').on('click.jobOrderCreate', '.remove-sub-packing-item', function () {
            $(this).closest('.sub-packing-item-row').remove();
        });

        // Calculate No. of Primary Bags when sub packing size changes
        $(document).off('input.jobOrderCreate', '.sub-calculated-packing-size').on('input.jobOrderCreate', '.sub-calculated-packing-size', function () {
            if ($(this).hasClass('is-calculating')) return;
            var subRow = $(this).closest('.sub-packing-item-row');
            var packingItem = subRow.closest('.packing-item');
            var parentBagSize = parseFloat(packingItem.find('.bag-size').val()) || 0;
            var packingSize = parseFloat($(this).val()) || 0;

            if (parentBagSize > 0 && packingSize > 0) {
                var noOfPrimaryBags = Math.round(packingSize / parentBagSize);
                subRow.find('.sub-no-of-primary-bags').addClass('is-calculating').val(noOfPrimaryBags).removeClass('is-calculating');
                
                var noOfBagsPrimary = parseInt(packingItem.find('.no-of-bags').val()) || 0;
                if (noOfBagsPrimary > 0 && noOfPrimaryBags > 0) {
                    var noOfBags = Math.floor(noOfBagsPrimary / noOfPrimaryBags);
                    subRow.find('.sub-no-of-bags').val(noOfBags).trigger('input');
                } else {
                    subRow.find('.sub-no-of-bags').val('0').trigger('input');
                }
            }
        });

        // Calculate No. of Bags for sub item when no_of_primary_bags changes
        $(document).off('input.jobOrderCreate', '.sub-no-of-primary-bags').on('input.jobOrderCreate', '.sub-no-of-primary-bags', function () {
            if ($(this).hasClass('is-calculating')) return;
            var subRow = $(this).closest('.sub-packing-item-row');
            var packingItem = subRow.closest('.packing-item');
            calculateSubItemNoOfBags(subRow, packingItem);
        });

        // Calculate No. of Bags for sub item when packing item's no_of_bags changes
        $(document).off('input.jobOrderCreate', '.no-of-bags').on('input.jobOrderCreate', '.no-of-bags', function () {
            var packingItem = $(this).closest('.packing-item');
            packingItem.find('.sub-packing-item-row').each(function () {
                calculateSubItemNoOfBags($(this), packingItem);
            });
        });

        // Calculate total bags for sub item & sync percentages
        $(document).off('input.jobOrderCreate', '.sub-no-of-bags, .sub-empty-bags, .sub-extra-bags').on('input.jobOrderCreate', '.sub-no-of-bags, .sub-empty-bags, .sub-extra-bags', function () {
            var subRow = $(this).closest('.sub-packing-item-row');
            var noOfBags = parseInt(subRow.find('.sub-no-of-bags').val()) || 0;
            var emptyBags = parseInt(subRow.find('.sub-empty-bags').val()) || 0;
            var extraBags = parseInt(subRow.find('.sub-extra-bags').val()) || 0;

            // If sub-no-of-bags changed, update extra and empty bags from percentage
            if ($(this).hasClass('sub-no-of-bags')) {
                var extraPercentageVal = subRow.find('.sub-extra-bags-percentage').val();
                if (extraPercentageVal !== '' && noOfBags > 0) {
                    var extraPercentage = parseFloat(extraPercentageVal) || 0;
                    extraBags = Math.round((extraPercentage / 100) * noOfBags);
                    subRow.find('.sub-extra-bags').val(extraBags);
                }
                var emptyPercentageVal = subRow.find('.sub-empty-bags-percentage').val();
                if (emptyPercentageVal !== '' && noOfBags > 0) {
                    var emptyPercentage = parseFloat(emptyPercentageVal) || 0;
                    emptyBags = Math.round((emptyPercentage / 100) * noOfBags);
                    subRow.find('.sub-empty-bags').val(emptyBags);
                }
            }

            // Update extra bags percentage only if extra-bags was changed directly
            if ($(this).hasClass('sub-extra-bags') && !$(this).hasClass('is-calculating')) {
                if (noOfBags > 0) {
                    if ($(this).val() === '') {
                        subRow.find('.sub-extra-bags-percentage').val('');
                    } else {
                        var extraPerc = (extraBags / noOfBags) * 100;
                        subRow.find('.sub-extra-bags-percentage').val(extraPerc.toFixed(2));
                    }
                }
            }

            // Update empty bags percentage only if empty-bags was changed directly
            if ($(this).hasClass('sub-empty-bags') && !$(this).hasClass('is-calculating')) {
                if (noOfBags > 0) {
                    if ($(this).val() === '') {
                        subRow.find('.sub-empty-bags-percentage').val('');
                    } else {
                        var emptyPerc = (emptyBags / noOfBags) * 100;
                        subRow.find('.sub-empty-bags-percentage').val(emptyPerc.toFixed(2));
                    }
                }
            }

            var totalBags = noOfBags + emptyBags + extraBags;
            subRow.find('.sub-total-bags').val(totalBags);
        });

        // Calculate extra bags for sub item from percentage
        $(document).off('input.jobOrderCreate', '.sub-extra-bags-percentage').on('input.jobOrderCreate', '.sub-extra-bags-percentage', function () {
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

        // Calculate empty bags for sub item from percentage
        $(document).off('input.jobOrderCreate', '.sub-empty-bags-percentage').on('input.jobOrderCreate', '.sub-empty-bags-percentage', function () {
            var subRow = $(this).closest('.sub-packing-item-row');
            var noOfBags = parseInt(subRow.find('.sub-no-of-bags').val()) || 0;
            var val = $(this).val();

            if (val === '') {
                subRow.find('.sub-empty-bags').addClass('is-calculating').val('').trigger('input').removeClass('is-calculating');
                return;
            }

            var percentage = parseFloat(val) || 0;
            if (noOfBags > 0) {
                var emptyBags = Math.round((percentage / 100) * noOfBags);
                subRow.find('.sub-empty-bags').addClass('is-calculating').val(emptyBags).trigger('input').removeClass('is-calculating');
            }
        });

        // Function to calculate no of bags from packing item's no_of_bags / no_of_primary_bags
        function calculateSubItemNoOfBags(subRow, packingItem) {
            var noOfBagsPrimary = parseInt(packingItem.find('.no-of-bags').val()) || 0;
            var noOfPrimaryBags = parseInt(subRow.find('.sub-no-of-primary-bags').val()) || 0;

            // Also calculate calculated packing size (kg)
            var parentBagSize = parseFloat(packingItem.find('.bag-size').val()) || 0;
            var calculatedPackingSize = parentBagSize * noOfPrimaryBags;
            subRow.find('.sub-calculated-packing-size').addClass('is-calculating').val(calculatedPackingSize > 0 ? calculatedPackingSize.toFixed(2) : '0').removeClass('is-calculating');

            if (noOfBagsPrimary > 0 && noOfPrimaryBags > 0) {
                var noOfBags = Math.floor(noOfBagsPrimary / noOfPrimaryBags);
                subRow.find('.sub-no-of-bags').val(noOfBags);
                // Trigger total bags calculation
                subRow.find('.sub-no-of-bags').trigger('input');
            } else {
                subRow.find('.sub-no-of-bags').val('0');
                subRow.find('.sub-no-of-bags').trigger('input');
            }
        }

        // Auto-calculate totals for parent packing items
        $(document).off('input.jobOrderCreate', '.bag-size, .no-of-bags, .extra-bags, .empty-bags').on('input.jobOrderCreate', '.bag-size, .no-of-bags, .extra-bags, .empty-bags', function () {
            var item = $(this).closest('.packing-item');
            
            // If no-of-bags changed, update extra-bags and empty-bags from percentage if percentage exists
            if ($(this).hasClass('no-of-bags')) {
                var noOfBags = parseInt($(this).val()) || 0;
                var extraPercentageVal = item.find('.extra-bags-percentage').val();
                if (extraPercentageVal !== '' && noOfBags > 0) {
                    var extraPercentage = parseFloat(extraPercentageVal) || 0;
                    var extraBags = Math.round((extraPercentage / 100) * noOfBags);
                    item.find('.extra-bags').addClass('is-calculating').val(extraBags).removeClass('is-calculating');
                }
                var emptyPercentageVal = item.find('.empty-bags-percentage').val();
                if (emptyPercentageVal !== '' && noOfBags > 0) {
                    var emptyPercentage = parseFloat(emptyPercentageVal) || 0;
                    var emptyBags = Math.round((emptyPercentage / 100) * noOfBags);
                    item.find('.empty-bags').addClass('is-calculating').val(emptyBags).removeClass('is-calculating');
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
                        var extraPercentage = (extraBags / noOfBags) * 100;
                        item.find('.extra-bags-percentage').val(extraPercentage.toFixed(2));
                    }
                }
            }

            // If empty-bags changed, update percentage
            if ($(this).hasClass('empty-bags') && !$(this).hasClass('is-calculating')) {
                var noOfBags = parseInt(item.find('.no-of-bags').val()) || 0;
                var emptyBags = parseInt($(this).val());
                if (noOfBags > 0) {
                    if ($(this).val() === '') {
                        item.find('.empty-bags-percentage').val('');
                    } else {
                        var emptyPercentage = (emptyBags / noOfBags) * 100;
                        item.find('.empty-bags-percentage').val(emptyPercentage.toFixed(2));
                    }
                }
            }
            
            calculateTotals(item);
        });

        // Calculate extra bags from percentage
        $(document).off('input.jobOrderCreate', '.extra-bags-percentage').on('input.jobOrderCreate', '.extra-bags-percentage', function () {
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

        // Calculate empty bags from percentage
        $(document).off('input.jobOrderCreate', '.empty-bags-percentage').on('input.jobOrderCreate', '.empty-bags-percentage', function () {
            var item = $(this).closest('.packing-item');
            var noOfBags = parseInt(item.find('.no-of-bags').val()) || 0;
            var val = $(this).val();

            if (val === '') {
                item.find('.empty-bags').addClass('is-calculating').val('').trigger('input').removeClass('is-calculating');
                return;
            }

            var percentage = parseFloat(val) || 0;
            if (noOfBags > 0) {
                var emptyBags = Math.round((percentage / 100) * noOfBags);
                item.find('.empty-bags').addClass('is-calculating').val(emptyBags).trigger('input').removeClass('is-calculating');
            }
        });

        // Update master packing items when packing item's bag type changes
        $(document).off('change.jobOrderCreate', 'select[name*="packing_items"][name*="[bag_type_id]"]:not([name*="[sub_items]"])').on('change.jobOrderCreate', 'select[name*="packing_items"][name*="[bag_type_id]"]:not([name*="[sub_items]"])', function () {
            var item = $(this).closest('.packing-item');
            // Trigger recalculation of totals which will update sub items
            calculateTotals(item);
        });

        // Auto-calculate stuffing based on metric tons and containers
        $(document).off('input.jobOrderCreate', '.metric-tons, .containers').on('input.jobOrderCreate', '.metric-tons, .containers', function () {
            var item = $(this).closest('.packing-item');
            calculateStuffing(item);
        });

        // Auto-calculate containers based on metric tons and stuffing
        $(document).off('input.jobOrderCreate', '.metric-tons, .stuffing').on('input.jobOrderCreate', '.metric-tons, .stuffing', function () {
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

        function calculateTotals(item) {
            var bagSize = parseFloat(item.find('.bag-size').val()) || 0;
            var noOfBags = parseInt(item.find('.no-of-bags').val()) || 0;
            var extraBags = parseInt(item.find('.extra-bags').val()) || 0;
            var emptyBags = parseInt(item.find('.empty-bags').val()) || 0;

            // Calculate totals
            var totalBags = noOfBags + extraBags + emptyBags;
            var totalKgs = bagSize * noOfBags;
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
            item.find('.sub-packing-item-row').each(function () {
                calculateSubItemNoOfBags($(this), item);
            });
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

        // Initial calculation for first item
        calculateTotals($('.packing-item').first());

        // Container Protection & Packing Materials
        // Add Container Protection Item
        $(document).off('click.jobOrderCreate', '#addContainerProtectionItem').on('click.jobOrderCreate', '#addContainerProtectionItem', function (e) {
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
        $(document).off('click.jobOrderCreate', '.remove-container-protection-item').on('click.jobOrderCreate', '.remove-container-protection-item', function () {
            $(this).closest('.container-protection-item').remove();

            // Hide section if no items left
            // if ($('#containerProtectionItems').find('.container-protection-item').length === 0) {
            //     $('#containerProtectionSection').hide();
            // }

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

    function fetchJobOrderNumber() {
        let locationId = $('#company_location_id').val();
        let selectedDate = $('input[name="job_order_date"]').val();

        if (!locationId) {
            $('input[name="job_order_no"]').val('');
            return;
        }

        $.ajax({
            url: "{{ route('job-orders.get-number') }}",
            type: "GET",
            data: {
                company_location_id: locationId,
                job_order_date: selectedDate
            },
            success: function(response) {
                if (response.success && response.job_order_no) {
                    $('input[name="job_order_no"]').val(response.job_order_no);
                } else if (typeof response === 'string') {
                    $('input[name="job_order_no"]').val(response);
                }
            }
        });
    }

    $('input[name="job_order_date"], #company_location_id').on('change', function () {
        fetchJobOrderNumber();
    });

    // When the first Packing Details location changes, auto-update company_location_id and regenerate Job Order No
    $(document).on('change', '.packing-company-location-id', function() {
        var $item = $(this).closest('.packing-item');
        if ($item.is(':first-child') || $item.index() === 0) {
            var firstLoc = $(this).val();
            if (firstLoc && firstLoc !== $('#company_location_id').val()) {
                $('#company_location_id').val(firstLoc).trigger('change');
            }
        }
    });

    // Auto-populate arrival locations based on company location selection
    $(document).on('change', '#company_location_id, select[name*="company_location_id"]', function() {
        if (window.isPopulatingFromJobOrderRequest) return;

        var locationIds = [];
        var mainLoc = $('#company_location_id').val();
        if (mainLoc) locationIds.push(mainLoc);
        $('select[name*="company_location_id"]').each(function() {
            var val = $(this).val();
            if (val && !locationIds.includes(val)) locationIds.push(val);
        });

        if (mainLoc && $(this).is('#company_location_id')) {
            $('.packing-company-location-id').each(function() {
                if (!$(this).val()) {
                    $(this).val(mainLoc).trigger('change.select2');
                }
            });
        }
        
        if (locationIds.length > 0) {
            var arrivalSelect = $('select[name="arrival_locations[]"]');
            var currentValues = (arrivalSelect.val() || []).map(String);
            $.ajax({
                url: "{{ route('job-orders.get-arrival-locations') }}",
                type: "GET",
                data: {
                    company_location_ids: locationIds,
                    include_arrival_ids: currentValues
                },
                success: function(response) {
                    var currentValues = (arrivalSelect.val() || []).map(String);
                    arrivalSelect.empty();
                    $.each(response, function(index, location) {
                        var isSelected = currentValues.includes(location.id.toString());
                        var newOption = new Option(location.name, location.id, isSelected, isSelected);
                        arrivalSelect.append(newOption);
                    });
                    arrivalSelect.val(currentValues).trigger('change');
                }
            });
        } else {
            $('select[name="arrival_locations[]"]').empty().trigger('change');
        }
    });

    $(document).on('change', 'select[name*="fumigation_company_id"], select[name*="inspection_company_id"]', function() {
        var select = $(this);
        var val = select.val();
        var hasExportOrder = !!$('#exportOrderSelect').val() || !!$('#hidden_export_order_id').val();
        var isFumigation = select.attr('name') && select.attr('name').indexOf('fumigation_company_id') !== -1;

        if (isFumigation && hasExportOrder && Array.isArray(val) && val.indexOf('null') !== -1) {
            val = val.filter(function(v) { return v !== 'null'; });
            select.val(val).trigger('change.select2');
        } else if (Array.isArray(val) && val.length > 1) {
            if (val.indexOf('null') !== -1) {
                if (val[val.length - 1] === 'null') {
                    select.val(['null']).trigger('change.select2');
                } else {
                    var filtered = val.filter(function(v) { return v !== 'null'; });
                    select.val(filtered).trigger('change.select2');
                }
            }
        }

        if (isFumigation) {
            var validVals = Array.isArray(val) ? val.filter(function(v) { return v !== 'null' && v !== null && v !== ''; }) : [];
            if (validVals.length > 0) {
                select.next('.select2-container').find('.select2-selection').css('border', '');
            }
        }
    });

    $('form#ajaxSubmit').on('submit', function(e) {
        var hasExportOrder = !!$('#exportOrderSelect').val() || !!$('#hidden_export_order_id').val();
        if (hasExportOrder) {
            var missingFumigation = false;
            $('.packing-item').each(function() {
                var select = $(this).find('select[name*="[fumigation_company_id][]"]');
                var val = select.val() || [];
                var valid = Array.isArray(val) && val.filter(function(v) { return v !== 'null' && v !== null && v !== ''; }).length > 0;
                if (!valid) {
                    missingFumigation = true;
                    select.next('.select2-container').find('.select2-selection').css('border', '1px solid #d9534f');
                } else {
                    select.next('.select2-container').find('.select2-selection').css('border', '');
                }
            });
            if (missingFumigation) {
                e.preventDefault();
                e.stopImmediatePropagation();
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Validation Error',
                        text: 'Fumigation Company is required for each packing item when an Export Order is selected.',
                        confirmButtonColor: '#D95000'
                    });
                } else {
                    alert('Fumigation Company is required for each packing item when an Export Order is selected.');
                }
                return false;
            }
        }
    });

    updateFumigationRequirement();

    var initialPackingLoc = $('#packingItems .packing-item').first().find('.packing-company-location-id').val();
    if (initialPackingLoc) {
        $('#company_location_id').val(initialPackingLoc);
    }
    if ($('#company_location_id').val()) {
        $('#company_location_id').trigger('change');
    }
    if ($('#jobOrderRequestSelect').val()) {
        $('#jobOrderRequestSelect').trigger('change');
    }
</script>
@endsection