<form action="{{ route('job-orders.store') }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    <input type="hidden" id="listRefresh" value="{{ route('get.job_orders') }}" />

    <div class="row form-mar">
        <!-- Basic Information -->
        <div class="col-md-12">
            <h6 class="header-heading-sepration">Basic Information</h6>
            <div class="row">
                <div class="col-md-3">
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

                <div class="col-md-3">
                    <div class="form-group">
                        <label>Job Order Date:</label>
                        <input type="date" name="job_order_date" class="form-control">
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        <label>Ref No:</label>
                        <input type="text" name="ref_no" class="form-control">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Job Order Type:</label>
                        <select name="job_order_type" class="form-control select2">
                            <option value="">Select Job Order Type</option>
                            <option value="local">Local</option>
                            <option value="export">Export</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-12">
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
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Remarks:</label>
                        <textarea name="remarks" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Order Description:</label>
                        <textarea name="order_description" class="form-control" rows="3"></textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- Product Selection -->
        <div class="col-md-12">
            <div class="form-group">
                <label>Commodity/Product:</label>
                <select name="product_id" class="form-control select2" id="productSelect">
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

        <!-- Crop Year -->
        <div class="col-md-12">
            <div class="form-group">
                <label>Crop Year:</label>
                <select name="crop_year_id" class="form-control select2">
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
                <textarea name="other_specifications" class="form-control" rows="3"></textarea>
            </div>
        </div>

        <!-- Packing Details -->
        <div class="col-md-12">
            <h6 class="header-heading-sepration d-flex justify-content-between align-items-center mb-3">Packing Details
                <button type="button" class="btn btn-sm btn-success" id="addPackingItem">Add More Packing Item</button>
            </h6>
            <div id="packingItems">
                <div class="packing-item border-bottom pb-3 mb-3">
                    <div class="row header-heading-sepration w-100 mx-auto align-items-center mb-3  ">
                        <div class="col-md-6">
                            <h6 class="mb-0">Packing Details</h6>
                        </div>
                        <div class="col-md-6 text-right">
                            <button type="button" class="btn btn-sm btn-danger remove-packing-item"><i
                                    class="ft-trash-2"></i></button>
                        </div>
                    </div>
                    <div class="row">
                        <!-- Company Location -->
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Company Location:</label>
                                <select name="packing_items[0][company_location_id]"
                                    class="form-control select2 company-location-select">
                                    <option value="">Select Location</option>
                                    @foreach($companyLocations as $location)
                                        <option data-code="{{ $location->code }}" value="{{ $location->id }}">
                                            {{ $location->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- Bag Type -->
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Bag Type:</label>
                                <select name="packing_items[0][bag_type_id][]"
                                    class="form-control select2 bag-type-select" multiple>
                                    <option value="">Select Bag Type</option>
                                    @foreach($bagTypes as $bagType)
                                        <option value="{{ $bagType->id }}" data-name="{{ $bagType->name }}">
                                            {{ $bagType->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- Fumigation By -->
                        <div class="col-md-3">
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

                        <!-- Delivery Date -->
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Delivery Date:</label>
                                <input type="date" name="packing_items[0][delivery_date]" class="form-control">
                            </div>
                        </div>

                        <!-- Total KGs (from Primary Bag) -->
                        <div class="col-md-1">
                            <div class="form-group">
                                <label>Total KGs:</label>
                                <input type="number" name="packing_items[0][total_kgs]" class="form-control total-kgs"
                                    step="0.01" readonly value="0">
                            </div>
                        </div>

                        <!-- PRIMARY SELECTION SECTION - UPPER -->
                        <div class="col-md-12 mt-2 d-none" id="primarySelectionSection0" style="display: none;">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">Select Primary Bag Type</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row" id="primaryBagTypes0">
                                        <!-- Primary options will appear here -->
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Bag Type Sub Items Section -->
                        <div class="col-md-12 mt-3">
                            <h6 class="header-heading-sepration">Bag Type Details</h6>
                            <div class="bag-sub-items-container" data-index="0">
                                <div class="alert alert-light">
                                    <i class="ft-info mr-1"></i>
                                    <strong>Please select bag types above to add details.</strong>
                                </div>
                            </div>
                        </div>

                        <!-- Remove Button -->
                        <div class="col-md-2 d-none">
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <button type="button"
                                    class="btn btn-sm btn-danger remove-packing-item form-control">Remove</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Selected Locations Summary -->
        <div class="col-md-12 mt-2" id="selectedLocationsWrapper" style="display: none;">
            <h6 class="header-heading-sepration">Location Details</h6>
            <div id="selectedLocationsList"></div>
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
    </div>

    <div class="row bottom-button-bar">
        <div class="col-12">
            <a type="button" class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton">Close</a>
            <button type="submit" class="btn btn-primary submitbutton">Save Job Order</button>
        </div>
    </div>
</form>

<script>
    $(document).ready(function () {
        // Initialize Select2 for all multi-selects
        $('.select2').select2();

        // Product selection change
        $('#productSelect').change(function () {
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
        $('#addPackingItem').click(function () {
            addNewPackingItem();
        });

        // Add new packing item function
        function addNewPackingItem() {
            var firstItem = $('.packing-item').first();
            var newItem = firstItem.clone();

            // Update indexes
            var newIndex = $('.packing-item').length;
            newItem.find('input, select, textarea, div').each(function () {
                var name = $(this).attr('name');
                var id = $(this).attr('id');
                if (name) {
                    name = name.replace(/\[\d+\]/, '[' + newIndex + ']');
                    $(this).attr('name', name);
                    $(this).val(''); // Clear values
                }
                if (id) {
                    id = id.replace(/\d+$/, newIndex);
                    $(this).attr('id', id);
                }
            });

            // Update data-index for sub-items container
            newItem.find('.bag-sub-items-container').attr('data-index', newIndex);

            // Clear sub-items container
            newItem.find('.bag-sub-items-container').html('<div class="alert alert-light"><i class="ft-info mr-1"></i><strong>Please select bag types above to add details.</strong></div>');

            // Clear primary selection section
            newItem.find('#primarySelectionSection' + newIndex).hide().find('#primaryBagTypes' + newIndex).empty();

            // Clear specific values
            newItem.find('.total-kgs').val('0');
            newItem.find('select').prop('selectedIndex', 0);

            // Reset select fields
            newItem.find('select').each(function () {
                if ($(this).hasClass('select2-hidden-accessible')) {
                    // Remove Select2 initialization
                    $(this).siblings('.select2-container').remove();
                    $(this).show().removeClass('select2-hidden-accessible');
                    $(this).next('.select2-container').remove();
                }
                $(this).val(null).trigger('change');
            });

            // Add to container
            $('#packingItems').append(newItem);

            // Re-initialize Select2 for new selects
            newItem.find('select').select2();
            firstItem.find('select').select2();

            updateLocationSummary();
        }

        // Remove packing item
        $(document).on('click', '.remove-packing-item', function () {
            if ($('.packing-item').length > 1) {
                $(this).closest('.packing-item').remove();
                // Re-index remaining items
                reindexPackingItems();
                updateLocationSummary();
            }
        });

        // Handle bag type selection change - show sub-items AND primary selection
        $(document).on('change', '.bag-type-select', function () {
            var $select = $(this);
            var packingItem = $select.closest('.packing-item');
            var selectedBagTypes = $select.val() || [];
            var subItemsContainer = packingItem.find('.bag-sub-items-container');
            var primarySection = packingItem.find('[id^="primarySelectionSection"]');
            var primaryContainer = packingItem.find('[id^="primaryBagTypes"]');
            var indexMatch = packingItem.find('select[name*="company_location_id"]').attr('name').match(/\[(\d+)\]/);
            var index = indexMatch ? indexMatch[1] : '0';

            // Clear existing sub-items
            subItemsContainer.empty();
            primaryContainer.empty();

            if (selectedBagTypes.length === 0) {
                subItemsContainer.html('<div class="alert alert-light"><i class="ft-info mr-1"></i><strong>Please select bag types above to add details.</strong></div>');
                primarySection.hide();
                calculateTotalsFromSubItems(packingItem);
                return;
            }

            // Show primary selection section
            primarySection.show();

            // Create primary selection buttons
            selectedBagTypes.forEach(function (bagTypeId, idx) {
                var $option = $select.find('option[value="' + bagTypeId + '"]');
                var bagName = $option.data('name') || $option.text() || 'Bag Type ' + bagTypeId;

                var primaryBtn = $('<div class="col-md-3 mb-2">' +
                    '<button type="button" class="btn btn-outline-primary btn-block primary-bag-btn" ' +
                    'data-bag-type-id="' + bagTypeId + '" data-index="' + index + '">' +
                    bagName + ' <i class="ft-check-circle d-none"></i>' +
                    '</button>' +
                    '</div>');

                primaryContainer.append(primaryBtn);
            });

            // Set first one as default primary
            setTimeout(function () {
                primaryContainer.find('.primary-bag-btn').first().addClass('btn-primary').removeClass('btn-outline-primary')
                    .find('.ft-check-circle').removeClass('d-none');
                createSubItemsTable(packingItem, selectedBagTypes, index);
            }, 100);
        });

        // Handle primary bag type selection
        $(document).on('click', '.primary-bag-btn', function () {
            var bagTypeId = $(this).data('bag-type-id');
            var index = $(this).data('index');
            var packingItem = $(this).closest('.packing-item');
            var selectedBagTypes = packingItem.find('.bag-type-select').val() || [];

            // Update all buttons
            packingItem.find('.primary-bag-btn').removeClass('btn-primary').addClass('btn-outline-primary')
                .find('.ft-check-circle').addClass('d-none');

            // Select current button
            $(this).removeClass('btn-outline-primary').addClass('btn-primary')
                .find('.ft-check-circle').removeClass('d-none');

            // Recreate sub-items table with new primary
            createSubItemsTable(packingItem, selectedBagTypes, index);

            // Update total kgs after primary change
            setTimeout(function () {
                updateTotalKgsFromPrimary(packingItem);
            }, 200);
        });

        // Create sub-items table function WITH ALL FIELDS
        function createSubItemsTable(packingItem, selectedBagTypes, index) {
            var subItemsContainer = packingItem.find('.bag-sub-items-container');
            var primaryBagTypeId = packingItem.find('.primary-bag-btn.btn-primary').data('bag-type-id') || selectedBagTypes[0];

            // Clear existing sub-items
            subItemsContainer.empty();

            // Get options for dropdowns
            var bagConditionOptions = '';
            @foreach($bagConditions as $condition)
                bagConditionOptions += '<option value="{{ $condition->id }}">{{ $condition->name }}</option>';
            @endforeach
        
        var brandOptions = '';
            @foreach($brands as $brand)
                brandOptions += '<option value="{{ $brand->id }}">{{ $brand->name }}</option>';
            @endforeach
        
        var bagColorOptions = '';
            @foreach($bagColors as $color)
                bagColorOptions += '<option value="{{ $color->id }}">{{ $color->color }}</option>';
            @endforeach
        
        var threadColorOptions = bagColorOptions;

            // Create table for sub-items with ALL FIELDS
            var tableHtml = '<div class="table-responsive"><table class="table table-bordered table-sm"><thead><tr>' +
                '<th>Bag</th>' +
                '<th>Primary</th>' +
                '<th>Bag Size (kg)</th>' +
                '<th>No. of Bags</th>' +
                '<th>Extra Bags</th>' +
                '<th>Total Bags</th>' +
                '<th>Total KGs</th>' +
                '<th>Emp Bags</th>' +
                '<th>Emp Bag Weight (g)</th>' +
                '<th>Bag Condition</th>' +
                '<th>Brand</th>' +
                '<th>Bag Color</th>' +
                '<th>Thread Color</th>' +
                '</tr></thead><tbody class="bag-sub-items-tbody">';

            selectedBagTypes.forEach(function (bagTypeId, idx) {
                var $option = packingItem.find('.bag-type-select option[value="' + bagTypeId + '"]');
                var bagName = $option.data('name') || $option.text() || 'Bag Type ' + bagTypeId;

                // Check if this is primary
                var isPrimary = (bagTypeId == primaryBagTypeId) ? '<span class="badge badge-success">PRIMARY</span>' : '';

                tableHtml += '<tr class="bag-sub-item-row" data-bag-type-id="' + bagTypeId + '" data-index="' + idx + '">' +
                    '<td><strong>' + bagName + '</strong>' +
                    '<input type="hidden" name="packing_items[' + index + '][sub_items][' + idx + '][bag_type_id]" value="' + bagTypeId + '">' +
                    '<input type="hidden" class="is-primary" value="' + (bagTypeId == primaryBagTypeId ? '1' : '0') + '"></td>' +
                    '<td class="text-center">' + isPrimary + '</td>' +
                    '<td><input type="number" name="packing_items[' + index + '][sub_items][' + idx + '][bag_size]" ' +
                    'class="form-control form-control-sm sub-bag-size" step="0.01" data-index="' + idx + '" placeholder="kg"></td>' +
                    '<td><input type="number" name="packing_items[' + index + '][sub_items][' + idx + '][no_of_bags]" ' +
                    'class="form-control form-control-sm sub-no-of-bags" data-index="' + idx + '" placeholder="No. of bags"></td>' +
                    '<td><input type="number" name="packing_items[' + index + '][sub_items][' + idx + '][extra_bags]" ' +
                    'class="form-control form-control-sm sub-extra-bags" value="0" min="0" data-index="' + idx + '" placeholder="Extra bags"></td>' +
                    '<td><input type="number" name="packing_items[' + index + '][sub_items][' + idx + '][total_bags]" ' +
                    'class="form-control form-control-sm sub-total-bags" readonly value="0"></td>' +
                    '<td><input type="number" name="packing_items[' + index + '][sub_items][' + idx + '][total_kgs]" ' +
                    'class="form-control form-control-sm sub-total-kgs" readonly step="0.01"></td>' +
                    '<td><input type="number" name="packing_items[' + index + '][sub_items][' + idx + '][empty_bags]" ' +
                    'class="form-control form-control-sm sub-empty-bags" value="0" min="0" data-index="' + idx + '"></td>' +
                    '<td><input type="number" name="packing_items[' + index + '][sub_items][' + idx + '][empty_bag_weight]" ' +
                    'class="form-control form-control-sm sub-empty-bag-weight" value="0" min="0" step="0.01" data-index="' + idx + '"></td>' +
                    '<td><select name="packing_items[' + index + '][sub_items][' + idx + '][bag_condition_id]" class="form-control form-control-sm sub-bag-condition">' +
                    '<option value="">Select Condition</option>' + bagConditionOptions + '</select></td>' +
                    '<td><select name="packing_items[' + index + '][sub_items][' + idx + '][brand_id]" class="form-control form-control-sm sub-brand">' +
                    '<option value="">Select Brand</option>' + brandOptions + '</select></td>' +
                    '<td><select name="packing_items[' + index + '][sub_items][' + idx + '][bag_color_id]" class="form-control form-control-sm sub-bag-color">' +
                    '<option value="">Select Color</option>' + bagColorOptions + '</select></td>' +
                    '<td><select name="packing_items[' + index + '][sub_items][' + idx + '][thread_color_id]" class="form-control form-control-sm sub-thread-color">' +
                    '<option value="">Select Color</option>' + threadColorOptions + '</select></td>' +
                    '</tr>';
            });

            tableHtml += '</tbody></table></div>';
            subItemsContainer.html(tableHtml);

            // Add event listeners for calculations
            packingItem.find('.sub-bag-size, .sub-no-of-bags, .sub-extra-bags, .sub-empty-bags').on('input', function () {
                var row = $(this).closest('.bag-sub-item-row');
                var rowIndex = row.data('index');
                calculateSubItemTotals(packingItem, row);

                // If primary row data changes, auto-calculate other rows
                if (row.find('.is-primary').val() == '1') {
                    autoCalculateOtherBagTypes(packingItem);
                    updateTotalKgsFromPrimary(packingItem);
                }
            });

            // Initialize any auto-calculation if primary has data
            var primaryRow = packingItem.find('.bag-sub-item-row .is-primary[value="1"]').closest('.bag-sub-item-row');
            if (primaryRow.length) {
                var primaryBagSize = parseFloat(primaryRow.find('.sub-bag-size').val()) || 0;
                var primaryNoOfBags = parseInt(primaryRow.find('.sub-no-of-bags').val()) || 0;

                if (primaryBagSize > 0 && primaryNoOfBags > 0) {
                    autoCalculateOtherBagTypes(packingItem);
                    updateTotalKgsFromPrimary(packingItem);
                }
            }
        }

        // Handle location selection change - update location summary
        $(document).on('change', 'select[name*="company_location_id"]', function () {
            updateLocationSummary();
        });

        // Remove sub-item row
        $(document).on('click', '.remove-sub-item', function () {
            var row = $(this).closest('.bag-sub-item-row');
            var packingItem = $(this).closest('.packing-item');
            var bagTypeId = row.data('bag-type-id');
            var isPrimary = row.find('.is-primary').val() == '1';

            // Remove from bag type select
            packingItem.find('.bag-type-select option[value="' + bagTypeId + '"]').prop('selected', false);
            packingItem.find('.bag-type-select').trigger('change');

            calculateTotalsFromSubItems(packingItem);
        });

        // Calculate individual sub-item totals
        function calculateSubItemTotals(item, row) {
            var bagSize = parseFloat(row.find('.sub-bag-size').val()) || 0;
            var noOfBags = parseInt(row.find('.sub-no-of-bags').val()) || 0;
            var extraBags = parseInt(row.find('.sub-extra-bags').val()) || 0;
            var emptyBags = parseInt(row.find('.sub-empty-bags').val()) || 0;

            // Calculate total bags: No. of Bags + Extra Bags + Empty Bags
            var totalBags = noOfBags + extraBags + emptyBags;

            // Calculate total kgs
            var totalKgs = noOfBags * bagSize;

            // Update fields
            row.find('.sub-total-bags').val(totalBags);
            row.find('.sub-total-kgs').val(totalKgs.toFixed(2));

            // Trigger totals calculation
            calculateTotalsFromSubItems(item);
        }

        // Update Total KGs from PRIMARY bag's total kgs
        function updateTotalKgsFromPrimary(item) {
            // Find PRIMARY bag row
            var primaryRow = item.find('.bag-sub-item-row .is-primary[value="1"]').closest('.bag-sub-item-row');
            if (!primaryRow.length) {
                item.find('.total-kgs').val('0');
                return;
            }

            // Get PRIMARY bag's total kgs
            var primaryTotalKgs = parseFloat(primaryRow.find('.sub-total-kgs').val()) || 0;

            // Update main total kgs field
            item.find('.total-kgs').val(primaryTotalKgs.toFixed(2));
        }

        // CORRECTED: Auto-calculate other bag types based on PRIMARY bag's TOTAL KGs
        function autoCalculateOtherBagTypes(item) {
            var rows = item.find('.bag-sub-item-row');
            if (rows.length < 2) return;

            // Find PRIMARY row
            var primaryRow = item.find('.bag-sub-item-row .is-primary[value="1"]').closest('.bag-sub-item-row');
            if (!primaryRow.length) return;

            // Get primary's TOTAL KGs from the sub-total-kgs field (already calculated)
            var primaryTotalKgs = parseFloat(primaryRow.find('.sub-total-kgs').val()) || 0;

            // If primary has total KGs
            if (primaryTotalKgs > 0) {
                // Calculate for other rows (non-primary)
                rows.each(function () {
                    var currentRow = $(this);
                    // Skip primary row
                    if (currentRow.find('.is-primary').val() == '1') return;

                    var currentBagSizeInput = currentRow.find('.sub-bag-size');
                    var currentNoOfBagsInput = currentRow.find('.sub-no-of-bags');
                    var currentBagSize = parseFloat(currentBagSizeInput.val()) || 0;
                    var currentNoOfBags = parseInt(currentNoOfBagsInput.val()) || 0;

                    // If other row has bag size
                    if (currentBagSize > 0) {
                        // FORMULA: primaryTotalKgs ÷ currentBagSize
                        var calculatedBags = Math.floor(primaryTotalKgs / currentBagSize);

                        // Only auto-calculate if user hasn't manually entered value
                        if (currentNoOfBags === 0 || currentNoOfBags === '' ||
                            currentNoOfBagsInput.data('auto-calculated')) {

                            if (calculatedBags > 0) {
                                currentNoOfBagsInput.val(calculatedBags)
                                    .data('auto-calculated', true)
                                    .trigger('input');
                            }
                        }
                    }
                });
            }
        }

        // Track user manual input
        $(document).on('focus', '.sub-no-of-bags', function () {
            $(this).data('user-manual', true);
        });

        $(document).on('input', '.sub-no-of-bags', function () {
            // Clear auto-calculated flag if user manually inputs
            if ($(this).data('user-manual')) {
                $(this).data('auto-calculated', false);
            }

            var row = $(this).closest('.bag-sub-item-row');
            var packingItem = $(this).closest('.packing-item');
            calculateSubItemTotals(packingItem, row);

            // If primary row data changes, auto-calculate others
            if (row.find('.is-primary').val() == '1') {
                autoCalculateOtherBagTypes(packingItem);
                updateTotalKgsFromPrimary(packingItem);
            }
        });

        // Bag size change
        $(document).on('input', '.sub-bag-size', function () {
            var row = $(this).closest('.bag-sub-item-row');
            var packingItem = $(this).closest('.packing-item');
            calculateSubItemTotals(packingItem, row);

            // If primary row bag size changes
            if (row.find('.is-primary').val() != '1') {
                autoCalculateOtherBagTypes(packingItem);
            } else {
                // If non-primary row bag size changes
                var primaryRow = packingItem.find('.bag-sub-item-row .is-primary[value="1"]').closest('.bag-sub-item-row');
                if (primaryRow.length) {
                    var primaryTotalKgs = parseFloat(primaryRow.find('.sub-total-kgs').val()) || 0;
                    var currentBagSize = parseFloat($(this).val()) || 0;
                    var currentNoOfBagsInput = row.find('.sub-no-of-bags');

                    if (currentBagSize > 0 && currentNoOfBagsInput.data('auto-calculated')) {
                        var calculatedBags = Math.floor(primaryTotalKgs / currentBagSize);
                        console.log(calculatedBags);
                        if (calculatedBags > 0) {
                            currentNoOfBagsInput.val(calculatedBags);
                        }
                    }
                }
            }
        });

        // Extra bags and empty bags change - global listener
        $(document).on('input', '.sub-extra-bags, .sub-empty-bags', function () {
            var row = $(this).closest('.bag-sub-item-row');
            var packingItem = $(this).closest('.packing-item');
            calculateSubItemTotals(packingItem, row);
        });

        // Calculate totals from all sub-items
        function calculateTotalsFromSubItems(item) {
            // Update total kgs from primary bag only
            updateTotalKgsFromPrimary(item);
        }


        function reindexPackingItems() {
            $('.packing-item').each(function (index) {
                $(this).find('input, select, textarea').each(function () {
                    var name = $(this).attr('name');
                    if (name) {
                        name = name.replace(/packing_items\[\d+\]/, 'packing_items[' + index + ']');
                        $(this).attr('name', name);
                    }
                });
                // Update data-index for sub-items container
                $(this).find('.bag-sub-items-container').attr('data-index', index);
            });

            updateLocationSummary();
        }

        function updateLocationSummary() {
            var uniqueLocations = {};
            $('.packing-item select[name*="company_location_id"]').each(function () {
                var val = $(this).val();
                var text = $(this).find('option:selected').text();
                if (val) {
                    uniqueLocations[val] = text;
                }
            });

            var wrapper = $('#selectedLocationsWrapper');
            var list = $('#selectedLocationsList');
            list.empty();

            var entries = Object.entries(uniqueLocations);
            if (entries.length === 0) {
                wrapper.hide();
                return;
            }

            // Create section for each unique location
            entries.forEach(function ([id, name]) {
                var locationSection = $('<div class="location-detail-section border p-3 mb-3"></div>');

                // Location heading
                locationSection.append('<h6 class="mb-3"><strong>' + name + '</strong></h6>');

                // Row for fields
                var row = $('<div class="row"></div>');

                // No of Containers
                var containersCol = $('<div class="col-md-3"></div>');
                containersCol.append('<div class="form-group"><label>No. of Containers:</label>' +
                    '<input type="number" name="location_details[' + id + '][no_of_containers]" ' +
                    'class="form-control location-containers" value="0" min="0"></div>');
                row.append(containersCol);

                // Description
                var descCol = $('<div class="col-md-8"></div>');
                descCol.append('<div class="form-group"><label>Description:</label>' +
                    '<textarea name="location_details[' + id + '][description]" ' +
                    'class="form-control location-description" rows="2"></textarea></div>');
                row.append(descCol);

                locationSection.append(row);
                list.append(locationSection);
            });

            wrapper.show();
        }

        // Job order number generation
        $('input[name="job_order_date"]').on('change', function () {
            let selectedDate = $('input[name="job_order_date"]').val();

            getUniversalNumber({
                table: 'job_orders',
                prefix: 'JOB',
                with_date: 1,
                column: 'job_order_no',
                custom_date: selectedDate,
                date_format: 'm-Y',
                serial_at_end: 1,
            }, function (no) {
                $('input[name="job_order_no"]').val(no);
            });
        });

        // Initial setup
        updateLocationSummary();
    });
</script>