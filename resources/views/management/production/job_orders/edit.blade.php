<form action="{{ route('job-orders.update', $jobOrder->id) }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    @method('PUT')
    <input type="hidden" id="listRefresh" value="{{ route('get.job_orders') }}" />

    <div class="row form-mar">
        <!-- Basic Information -->
        <div class="col-md-12">
            <h6 class="header-heading-sepration">Basic Information</h6>
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Location:</label>
                        <select name="company_location_id" class="form-control" disabled>
                            <option value="">Select Location</option>
                            @foreach($companyLocations as $location)
                                <option value="{{ $location->id }}" {{ $jobOrder->company_location_id == $location->id ? 'selected' : '' }}>
                                    {{ $location->name }}
                                </option>
                            @endforeach
                        </select>
                        <input type="hidden" value="{{ $jobOrder->company_location_id }}" name="company_location_id">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Job Order No:</label>
                        <input type="text" readonly name="job_order_no" class="form-control"
                            value="{{ $jobOrder->job_order_no }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Job Order Date:</label>
                        <input type="date" name="job_order_date" class="form-control"
                            value="{{ $jobOrder->job_order_date->format('Y-m-d') }}">
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        <label>Ref No:</label>
                        <input type="text" name="ref_no" class="form-control" value="{{ $jobOrder->ref_no }}">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Attention To:</label>
                        <select name="attention_to[]" class="form-control select2" multiple>
                            <option value="">Select Users</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" {{ in_array($user->id, json_decode($jobOrder->attention_to, true) ?? []) ? 'selected' : '' }}>
                                    {{ $user->name }} ({{ $user->email }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Remarks:</label>
                        <input type="text" name="remarks" class="form-control" value="{{ $jobOrder->remarks }}">
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="form-group">
                        <label>Order Description:</label>
                        <textarea name="order_description" class="form-control"
                            rows="4">{{ $jobOrder->order_description }}</textarea>
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
                                                <input type="hidden" name="specifications[{{ $index }}][product_slab_id]"
                                                    value="{{ $spec->product_slab_id }}">
                                                <input type="hidden" name="specifications[{{ $index }}][spec_name]"
                                                    value="{{ $spec->spec_name }}">
                                                <input type="hidden" name="specifications[{{ $index }}][uom]"
                                                    value="{{ $spec->uom }}">
                                            </td>
                                            <td>
                                                <input type="text" name="specifications[{{ $index }}][spec_value]"
                                                    value="{{ $spec->spec_value }}"
                                                    class="form-control form-control-sm spec-value-input"
                                                    placeholder="Enter value">
                                            </td>
                                            <td>
                                                {{ $spec->uom }}
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

        <!-- Packing Details -->
        <div class="col-md-12">
            <h6 class="header-heading-sepration d-flex justify-content-between align-items-center">Packing Details
                <button type="button" class="btn btn-sm btn-success" id="addPackingItem">Add More Packing Item</button>
            </h6>

            <div id="packingItems">
                @foreach($jobOrder->packingItems as $packingIndex => $packingItem)
                    <div class="packing-item row border-bottom pb-3 mb-3 w-100 mx-auto">
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Bag Type:</label>
                                <select name="packing_items[{{ $packingIndex }}][bag_type]" class="form-control">
                                    <option value="">Select Bag Type</option>
                                    @foreach($bagTypes as $bagType)
                                        <option value="{{ $bagType->name }}" {{ $packingItem->bag_type == $bagType->name ? 'selected' : '' }}>
                                            {{ $bagType->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Bag Condition:</label>
                                <select name="packing_items[{{ $packingIndex }}][bag_condition]" class="form-control">
                                    <option value="">Select Condition</option>
                                    @foreach($bagConditions as $condition)
                                        <option value="{{ $condition->name }}" {{ $packingItem->bag_condition == $condition->name ? 'selected' : '' }}>
                                            {{ $condition->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-1">
                            <div class="form-group">
                                <label>Bag Size (kg):</label>
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
                                    class="form-control containers" value="{{ $packingItem->no_of_containers }}">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Brand:</label>
                                <select name="packing_items[{{ $packingIndex }}][brand]" class="form-control">
                                    <option value="">Select Brand</option>
                                    @foreach($brands as $brand)
                                        <option value="{{ $brand->name }}" {{ $packingItem->brand == $brand->name ? 'selected' : '' }}>
                                            {{ $brand->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Bag Color:</label>
                                <select name="packing_items[{{ $packingIndex }}][bag_color]" class="form-control">
                                    <option value="">Select Color</option>
                                    @foreach($bagColors as $color)
                                        <option value="{{ $color->id }}" {{ $packingItem->bag_color == $color->id ? 'selected' : '' }}>
                                            {{ $color->color }}
                                        </option>
                                    @endforeach
                                </select>
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
                <div class="col-md-4">
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
                </div>
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
                        <label>Delivery Date:</label>
                        <input type="date" name="delivery_date" class="form-control"
                            value="{{ $jobOrder->delivery_date ? $jobOrder->delivery_date->format('Y-m-d') : '' }}">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Loading Date:</label>
                        <input type="date" name="loading_date" class="form-control"
                            value="{{ $jobOrder->loading_date ? $jobOrder->loading_date->format('Y-m-d') : '' }}">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Packing Description:</label>
                        <textarea name="packing_description" class="form-control"
                            rows="1">{{ $jobOrder->packing_description }}</textarea>
                    </div>
                </div>
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

        // Initial calculation for all items
        $('.packing-item').each(function () {
            calculateTotals($(this));
        });
    });
</script>