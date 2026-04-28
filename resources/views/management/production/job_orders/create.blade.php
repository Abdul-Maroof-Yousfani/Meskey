<form action="{{ route('job-orders.store') }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    <input type="hidden" id="listRefresh" value="{{ route('get.job_orders') }}" />

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
                                    <option value="{{ $eo->id }}">{{ $eo->voucher_no }}</option>
                                @endforeach
                            </select>
                        </div>
                    </fieldset>
                </div>
                <div class="col-md-4">
                    <fieldset>
                        <label>Job Order No#</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <button class="btn btn-primary" type="button">Job Order No#</button>
                            </div>
                            <input type="text" readonly name="job_order_no" class="form-control">
                        </div>
                    </fieldset>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Job Order Date:</label>
                        <input type="date" name="job_order_date" class="form-control">
                    </div>
                </div>

                <!-- Row 2: Ref No | Attention To -->
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Ref No:</label>
                        <input type="text" name="ref_no" class="form-control">
                    </div>
                </div>
                <div class="col-md-8">
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

            <div id="export-order-quantity-info" class="mb-3" style="display: none;"></div>

            <div id="packingItems">
                <div class="packing-item row border-bottom pb-3 mb-3 w-100 mx-auto">
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Company Location:</label>
                            <select name="packing_items[0][company_location_id]" class="form-control select2">
                                <option value="">Select Location</option>
                                @foreach($companyLocations as $location)
                                    <option data-code="{{ $location->code }}" value="{{ $location->id }}">
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
                            <label>Bag Color:</label>
                            <select name="packing_items[0][bag_color_id]" class="form-control select2">
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
                            <label>Packing Size (kg):</label>
                            <input type="number" name="packing_items[0][bag_size]" class="form-control bag-size"
                                step="0.01">
                        </div>
                    </div>
                    <div class="col-md-1">
                        <div class="form-group">
                            <label>No. of Bags:</label>
                            <input type="number" name="packing_items[0][no_of_bags]" class="form-control no-of-bags">
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
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Fumigation By:</label>
                            <select name="packing_items[0][fumigation_company_id][]" class="form-control select2"
                                multiple>
                                <option value="">Select Fumigation Company</option>
                                @foreach($fumigationCompanies as $company)
                                    <option value="{{ $company->id }}">{{ $company->name }}</option>
                                @endforeach
                            </select>
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
                                                <th class="col-2">Bag Type/Product</th>

                                                <th>Bag Size </th>
                                                <th>No of Primary Bags fit in master bag</th>
                                                <th>Packing Size (kg)</th>
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
                        <select name="inspection_company_id[]" class="form-control select2" multiple>
                            <option value="">Select Inspection Company</option>
                            @foreach($inspectionCompanies as $company)
                                <option value="{{ $company->id }}">{{ $company->name }}</option>
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


    <div class="row bottom-button-bar">
        <div class="col-12">
            <a type="button" class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton">Close</a>
            <button type="submit" class="btn btn-primary submitbutton">Save Job Order</button>
        </div>
    </div>
</form>

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
            <!-- <td class="col-2">
            <input type="hidden" class="packing-item-ref" name="packing_items[INDEX][sub_items][SUB_INDEX][job_order_packing_item_id]" value="">
            <select name="packing_items[INDEX][sub_items][SUB_INDEX][bag_type_id]" class="form-control form-control-sm select2 sub-bag-type">
                <option value="">Select Bag Type</option>
                @foreach($bagTypes as $bagType)
                    <option value="{{ $bagType->id }}">{{ $bagType->name }}</option>
                @endforeach
            </select>
        </td> -->
            <td>
                <select name="packing_items[INDEX][sub_items][SUB_INDEX][bag_product_id]"
                    class="form-control form-control-sm select2 sub-bag-product">
                    <option value="">Select Bag Type/Product</option>
                    @foreach($bagProducts as $bagProduct)
                        <option value="{{ $bagProduct->id }}">{{ $bagProduct->name }}</option>
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
                <input type="number" name="packing_items[INDEX][sub_items][SUB_INDEX][no_of_primary_bags]"
                    class="form-control form-control-sm sub-no-of-primary-bags"
                    placeholder="Enter No of Primary Bags fit in master bag">
            </td>
            <td>
                <input type="number" step="0.01" name="packing_items[INDEX][sub_items][SUB_INDEX][packing_size]" readonly class="form-control form-control-sm sub-calculated-packing-size" placeholder="Auto calc">
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
                
                // Fumigation sync
                if (item.fumigation_company_id) {
                    let fumigationSelect = row.find(`select[name="packing_items[${index}][fumigation_company_id][]"]`);
                    fumigationSelect.val(item.fumigation_company_id);
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
                
                // Inside sub items
                if(item.sub_items && item.sub_items.length > 0) {
                    item.sub_items.forEach(function(sub, sIdx) {
                        // Use a more robust way to get the template HTML
                        let $template = $('.sub-packing-item-template tbody');
                        let subRowHtml = $template.html();
                        
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

                // Trigger total bags calculation AFTER sub-items are added
                row.find('.no-of-bags').trigger('input');
            });
            reindexPackingItems();
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
                    $this.prop('selectedIndex', 0);
                } else {
                    if($this.hasClass('empty-bags') || $this.hasClass('extra-bags') || $this.hasClass('extra-bags-percentage') || $this.hasClass('min-weight') || $this.hasClass('containers') || $this.hasClass('stuffing')){
                        $this.val('0');
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

            // 5. Clean sub-items container
            $newItem.find('.sub-packing-items-container').empty();

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
                }
            });
            currentItem.find('select').select2();

            // Re-index all items
            reindexPackingItems();
        });


        // Remove packing item
        $(document).off('click.jobOrderCreate', '.remove-packing-item').on('click.jobOrderCreate', '.remove-packing-item', function () {
            if ($('.packing-item').length > 1) {
                $(this).closest('.packing-item').remove();
                // Re-index remaining items
                reindexPackingItems();
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

        // Calculate No. of Bags for sub item when no_of_primary_bags changes
        $(document).off('input.jobOrderCreate', '.sub-no-of-primary-bags').on('input.jobOrderCreate', '.sub-no-of-primary-bags', function () {
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

        // Calculate total bags for sub item
        $(document).off('input.jobOrderCreate', '.sub-no-of-bags, .sub-empty-bags, .sub-extra-bags').on('input.jobOrderCreate', '.sub-no-of-bags, .sub-empty-bags, .sub-extra-bags', function () {
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

        // Function to calculate no of bags from packing item's no_of_bags / no_of_primary_bags
        function calculateSubItemNoOfBags(subRow, packingItem) {
            var noOfBagsPrimary = parseInt(packingItem.find('.no-of-bags').val()) || 0;
            var noOfPrimaryBags = parseInt(subRow.find('.sub-no-of-primary-bags').val()) || 0;

            // Also calculate calculated packing size (kg)
            var parentBagSize = parseFloat(packingItem.find('.bag-size').val()) || 0;
            var calculatedPackingSize = parentBagSize * noOfPrimaryBags;
            subRow.find('.sub-calculated-packing-size').val(calculatedPackingSize > 0 ? calculatedPackingSize.toFixed(2) : '0');

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
        $(document).off('input.jobOrderCreate', '.bag-size, .no-of-bags, .extra-bags, .empty-bags').on('input.jobOrderCreate', '.bag-size, .no-of-bags, .extra-bags, .empty-bags', function () {
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

    $('input[name="job_order_date"]').on('change', function () {
        let locationCode = $("#company_location_id option:selected").data('code');
        let selectedDate = $('input[name="job_order_date"]').val();

        getUniversalNumber({
            table: 'job_orders',
            prefix: 'JOB',
            with_date: 1,
            column: 'job_order_no',
            custom_date: selectedDate,
            date_format: 'Y',
            serial_at_end: 1,
        }, function (no) {
            $('input[name="job_order_no"]').val(no);
        });
    });
</script>