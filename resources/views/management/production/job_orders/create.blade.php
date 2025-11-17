<form action="{{ route('job-orders.store') }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    <input type="hidden" id="listRefresh" value="{{ route('get.job_orders') }}" />

    <div class="row form-mar">
        <!-- Basic Information -->
        <div class="col-md-12">
            <h6 class="header-heading-sepration">Basic Information</h6>
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Job Order No:</label>
                        <input type="text" name="job_order_no" class="form-control">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Job Order Date:</label>
                        <input type="date" name="job_order_date" class="form-control">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Location:</label>
                        <select name="location" class="form-control">
                            <option value="">Select Location</option>
                            @foreach($companyLocations as $location)
                                <option value="{{ $location->id }}">{{ $location->name }}</option>
                            @endforeach
                        </select>
                    </div>
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
                                <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Remarks:</label>
                        <input type="text" name="remarks" class="form-control">
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="form-group">
                        <label>Order Description:</label>
                        <textarea name="order_description" class="form-control" rows="4"></textarea>
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

        <!-- Packing Details -->
        <div class="col-md-12">
            <h6 class="header-heading-sepration d-flex justify-content-between align-items-center">Packing Details
                <button type="button" class="btn btn-sm btn-success" id="addPackingItem">Add More Packing Item</button>
            </h6>

            <div id="packingItems">
                <div class="packing-item row border-bottom pb-3 mb-3 w-100 mx-auto">
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Bag Type:</label>
                            <select name="packing_items[0][bag_type]" class="form-control">
                                <option value="">Select Bag Type</option>
                                @foreach($bagTypes as $bagType)
                                    <option value="{{ $bagType->name }}">{{ $bagType->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Bag Condition:</label>
                            <select name="packing_items[0][bag_condition]" class="form-control">
                                <option value="">Select Condition</option>
                                @foreach($bagConditions as $condition)
                                    <option value="{{ $condition->name }}">{{ $condition->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-1">
                        <div class="form-group">
                            <label>Bag Size (kg):</label>
                            <input type="number" name="packing_items[0][bag_size]" class="form-control bag-size" step="0.01">
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
                            <input type="number" name="packing_items[0][extra_bags]" class="form-control extra-bags" value="0">
                        </div>
                    </div>
                    <div class="col-md-1">
                        <div class="form-group">
                            <label>Empty Bags:</label>
                            <input type="number" name="packing_items[0][empty_bags]" class="form-control empty-bags" value="0">
                        </div>
                    </div>
                    <div class="col-md-1">
                        <div class="form-group">
                            <label>Total Bags:</label>
                            <input type="number" name="packing_items[0][total_bags]" class="form-control total-bags" readonly>
                        </div>
                    </div>
                    <div class="col-md-1">
                        <div class="form-group">
                            <label>Total KGs:</label>
                            <input type="number" name="packing_items[0][total_kgs]" class="form-control total-kgs" step="0.01" readonly>
                        </div>
                    </div>
                    <div class="col-md-1">
                        <div class="form-group">
                            <label>Metric Tons:</label>
                            <input type="number" name="packing_items[0][metric_tons]" class="form-control metric-tons" step="0.01" readonly>
                        </div>
                    </div>
                    <div class="col-md-1">
                        <div class="form-group">
                            <label>Stuffing (MTs):</label>
                            <input type="number" name="packing_items[0][stuffing_in_container]" class="form-control stuffing" step="0.01">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>No. of Containers:</label>
                            <input type="number" name="packing_items[0][no_of_containers]" class="form-control containers" value="0">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Brand:</label>
                            <select name="packing_items[0][brand]" class="form-control">
                                <option value="">Select Brand</option>
                                @foreach($brands as $brand)
                                    <option value="{{ $brand->name }}">{{ $brand->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Bag Color:</label>
                            <select name="packing_items[0][bag_color]" class="form-control">
                                <option value="">Select Color</option>
                                @foreach($bagColors as $color)
                                    <option value="{{ $color->id }}">{{ $color->color }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Min Weight Empty Bags (g):</label>
                            <input type="number" name="packing_items[0][min_weight_empty_bags]" class="form-control min-weight" step="0.01">
                        </div>
                    </div>
                    <div class="col-md-1">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <button type="button" class="btn btn-sm btn-danger remove-packing-item form-control">Remove</button>
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
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Fumigation By:</label>
                        <select name="fumigation_company_id[]" class="form-control select2" multiple>
                            <option value="">Select Fumigation Company</option>
                            @foreach($fumigationCompanies as $company)
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
                        <label>Delivery Date:</label>
                        <input type="date" name="delivery_date" class="form-control">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Loading Date:</label>
                        <input type="date" name="loading_date" class="form-control">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Packing Description:</label>
                        <textarea name="packing_description" class="form-control" rows="1"></textarea>
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
            var firstItem = $('.packing-item').first();
            var newItem = firstItem.clone();

            // Update indexes
            var newIndex = $('.packing-item').length;
            newItem.find('input, select').each(function () {
                var name = $(this).attr('name');
                if (name) {
                    name = name.replace(/\[\d+\]/, '[' + newIndex + ']');
                    $(this).attr('name', name);
                    $(this).val(''); // Clear values
                }
            });

            // Clear specific values
            newItem.find('.bag-size, .no-of-bags, .extra-bags, .empty-bags, .stuffing, .containers, .min-weight').val('');
            newItem.find('.total-bags, .total-kgs, .metric-tons').val('0');
            newItem.find('select').prop('selectedIndex', 0);

            // Add to container
            $('#packingItems').append(newItem);

            // Re-initialize Select2 for new selects
            newItem.find('select').select2();
        });

        // Remove packing item
        $(document).on('click', '.remove-packing-item', function () {
            if ($('.packing-item').length > 1) {
                $(this).closest('.packing-item').remove();
                // Re-index remaining items
                reindexPackingItems();
            }
        });

        // Auto-calculate totals
        $(document).on('input', '.bag-size, .no-of-bags, .extra-bags, .empty-bags', function () {
            var item = $(this).closest('.packing-item');
            calculateTotals(item);
        });

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
        }

        function reindexPackingItems() {
            $('.packing-item').each(function (index) {
                $(this).find('input, select').each(function () {
                    var name = $(this).attr('name');
                    if (name) {
                        name = name.replace(/\[\d+\]/, '[' + index + ']');
                        $(this).attr('name', name);
                    }
                });
            });
        }

        // Initial calculation for first item
        calculateTotals($('.packing-item').first());
    });
</script>