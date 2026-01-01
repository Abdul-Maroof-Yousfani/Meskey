<form action="{{ route('sales.loading-program.store') }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    <input type="hidden" id="listRefresh" value="{{ route('sales.get.loading-program') }}" />
    <div class="row form-mar">

        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group">
                <label>Sale Order:</label>
                <select class="form-control select2" name="sale_order_id" id="sale_order_id">
                    <option value="">Select Sale Order</option>
                    @foreach ($SaleOrders as $SaleOrder)
                        <option value="{{ $SaleOrder->id }}">
                            {{ $SaleOrder->reference_no }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group">
                <label>Delivery Order:</label>
                <select class="form-control select2" name="delivery_order_id" id="delivery_order_id">
                    <option value="">Select Delivery Order</option>
                    @foreach ($DeliveryOrders as $deliveryOrder)
                        <option value="{{ $deliveryOrder->id }}">
                            {{ $deliveryOrder->reference_no }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>
    <div class="row" id="saleOrderDataContainer">
    </div>

    <div class="row" id="locationContainer" style="display: none;">
        <style>
            .select2-container {
                width: 100% !important;
            }
            .select2-container .select2-selection--multiple {
                width: 100% !important;
            }
        </style>
        <div class="col-xs-12 col-sm-4 col-md-4">
            <div class="form-group">
                <label>Company Location</label>
                <select class="form-control select2 w-100" name="company_locations" id="company_locations" disabled style="width: 100% !important;">
                    <!-- Options will be populated dynamically -->
                </select>
            </div>
        </div>
        <div class="col-xs-12 col-sm-4 col-md-4">
            <div class="form-group">
                <label>Arrival Location</label>
                <select class="form-control select2 w-100" name="arrival_locations[]" id="arrival_locations" multiple disabled style="width: 100% !important;">
                    <!-- Options will be populated dynamically -->
                </select>
            </div>
        </div>
        <div class="col-xs-12 col-sm-4 col-md-4">
            <div class="form-group">
                <label>Sub Arrival Location</label>
                <select class="form-control select2 w-100" name="sub_arrival_locations[]" id="sub_arrival_locations" multiple disabled style="width: 100% !important;">
                    <!-- Options will be populated dynamically -->
                </select>
            </div>
        </div>
    </div>

    <div class="row" id="lineItemsContainer">
        <div class="col-12">
            <h6 class="header-heading-sepration">
                Loading Program Items
                <button type="button" class="btn btn-sm btn-primary float-right" id="addItemBtn">Add Item</button>
            </h6>
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="itemsTable">
                    <thead class="thead-light">
                        <tr>
                            <th width="12%">Truck Number *</th>
                            <th width="12%">Container Number</th>
                            <th width="10%">Packing</th>
                            <th width="10%">Brand</th>
                            <th width="15%">Factory/Arrival Location *</th>
                            <th width="15%">Gala/Sub Arrival Location *</th>
                            <th width="10%">Driver Name</th>
                            <th width="10%">Contact Details</th>
                            <th width="8%">Qty</th>
                            <th width="8%">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="itemsList">
                        <!-- Items will be added here dynamically -->
                        <tr id="noItemsRow">
                            <td colspan="10" class="text-center text-muted py-3">
                                No items added yet. Click "Add Item" to add loading program items.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Remark:</label>
                <textarea name="remark" placeholder="Remarks" class="form-control"></textarea>
            </div>
        </div>
    </div>
    <div class="row bottom-button-bar">
        <div class="col-12">
            <a type="button" class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton">Close</a>
            <button type="submit" class="btn btn-primary submitbutton">Save</button>
        </div>
    </div>
</form>

<script>
    $(document).ready(function() {
        $('.select2').select2();

    });

    $(document).ready(function() {
        // Handle sale order change
        $('#sale_order_id').change(function() {
            var sale_order_id = $(this).val();

            if (sale_order_id) {
                $.ajax({
                    url: '{{ route('sales.getSaleOrderRelatedData') }}',
                    type: 'GET',
                    data: {
                        sale_order_id: sale_order_id
                    },
                    dataType: 'json',
                    beforeSend: function() {
                        Swal.fire({
                            title: "Processing...",
                            text: "Please wait while fetching sale order details.",
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });
                    },
                    success: function(response) {
                        Swal.close();
                        if (response.success) {
                            // Append the rendered HTML to a container element
                            $('#saleOrderDataContainer').html(response.html);

                            // Reinitialize select2 for any new dropdowns
                            $('.select2').select2();
                        } else {
                            Swal.fire("No Data", "No sale order details found.",
                                "info");
                        }
                    },
                    error: function() {
                        Swal.close();
                        Swal.fire("Error", "Something went wrong. Please try again.",
                            "error");
                    }
                });
            } else {
                // Clear containers if no sale order selected
                $('#saleOrderDataContainer').html('');
                $('#delivery_order_id').empty().append('<option value="">Select Delivery Order</option>');
                $('#lineItemsContainer').hide();
            }
        });

        // Handle delivery order change
        $('#delivery_order_id').change(function() {
    var delivery_order_id = $(this).val();

    if (delivery_order_id) {
        // Fetch delivery order data to get packing and brand
        var saleOrderId = $('#sale_order_id').val();
        
        if (saleOrderId) {
            $.ajax({
                url: '{{ route('sales.getDeliveryOrdersBySaleOrderLoading') }}',
                type: 'GET',
                data: { sale_order_id: saleOrderId },
                success: function(response) {
                    if (response.success && response.delivery_orders) {
                        // Find the selected delivery order
                        var selectedDeliveryOrder = response.delivery_orders.find(function(d_o) {
                            return d_o.id == delivery_order_id;
                        });  // <-- Fixed: added missing closing parenthesis

                        if (selectedDeliveryOrder) {
                            // Populate location fields
                            populateLocationFields(selectedDeliveryOrder);

                            if (selectedDeliveryOrder.delivery_order_data && selectedDeliveryOrder.delivery_order_data.length > 0) {
                                var firstItem = selectedDeliveryOrder.delivery_order_data[0];

                                // Set default packing and brand for new items
                                $('input[name="loading_program_items[0][packing]"]').val(firstItem.bag_size || '');
                                $('input[name="loading_program_items[0][brand_id]"]').val(firstItem.brand_id || '');
                                $('input[name="loading_program_items[0][brand_name]"]').val(firstItem.brand ? firstItem.brand.name : '');
                            }
                        }
                    }
                }
            });
        }

        // Show location and line items containers
        $('#locationContainer').show();
        $('#lineItemsContainer').show();
    } else {
        // Hide location and line items containers
        $('#locationContainer').hide();
        $('#lineItemsContainer').hide();
    }
});

        // Function to populate location fields from delivery order
        function populateLocationFields(deliveryOrder) {
            console.log('Populating locations for delivery order:', deliveryOrder);

            // Clear existing options
            $('#company_locations').empty();
            $('#arrival_locations').empty();
            $('#sub_arrival_locations').empty();

            // Get all locations data
            var companyLocations = @json(get_locations());
            var arrivalLocations = @json(\App\Models\Master\ArrivalLocation::all());
            var subArrivalLocations = @json(\App\Models\Master\ArrivalSubLocation::all());

            // Parse comma-separated IDs
            var selectedCompanyIds = deliveryOrder.location_id ? [deliveryOrder.location_id] : [];
            var selectedArrivalIds = deliveryOrder.arrival_location_id ? deliveryOrder.arrival_location_id.split(',').map(id => id.trim()) : [];
            var selectedSubArrivalIds = deliveryOrder.sub_arrival_location_id ? deliveryOrder.sub_arrival_location_id.split(',').map(id => id.trim()) : [];

            // Populate company locations with selection (single select)
            $.each(companyLocations, function(index, location) {
                var isSelected = selectedCompanyIds.includes(location.id.toString());
                var option = new Option(location.name, location.id, isSelected, isSelected);
                $('#company_locations').append(option);
            });

            // Set the value for single select company location
            if (selectedCompanyIds.length > 0) {
                $('#company_locations').val(selectedCompanyIds[0]);
            }

            // Populate arrival locations with selection
            $.each(arrivalLocations, function(index, location) {
                var isSelected = selectedArrivalIds.includes(location.id.toString());
                var option = new Option(location.name, location.id, isSelected, isSelected);
                $('#arrival_locations').append(option);
            });

            // Populate sub-arrival locations with selection
            $.each(subArrivalLocations, function(index, location) {
                var isSelected = selectedSubArrivalIds.includes(location.id.toString());
                var option = new Option(location.name, location.id, isSelected, isSelected);
                $('#sub_arrival_locations').append(option);
            });

            // Re-initialize select2 to reflect changes
            $('#company_locations').select2();
            $('#arrival_locations').select2();
            $('#sub_arrival_locations').select2();
        }

        // Add item functionality
        let itemIndex = 0;
        $('#addItemBtn').click(function() {
            addItemRow(itemIndex);
            itemIndex++;
        });

        function addItemRow(index) {
            const itemHtml = `
                <tr class="item-row" data-index="${index}">
                    <td>
                        <input type="text" name="loading_program_items[${index}][truck_number]" class="form-control form-control-sm" required style="min-width: 100px;">
                    </td>
                    <td>
                        <input type="text" name="loading_program_items[${index}][container_number]" class="form-control form-control-sm" style="min-width: 100px;">
                    </td>
                    <td>
                        <input type="text" name="loading_program_items[${index}][packing]" class="form-control form-control-sm" readonly style="min-width: 80px;">
                    </td>
                    <td>
                                    <input type="hidden" name="loading_program_items[${index}][brand_id]" class="form-control form-control-sm" style="min-width: 80px;">
                                    <input type="text" name="loading_program_items[${index}][brand_name]" class="form-control form-control-sm" readonly style="min-width: 80px;">
                    </td>
                    <td>
                        <select name="loading_program_items[${index}][arrival_location_id]" class="form-control form-control-sm select2 arrival-location-select" required style="min-width: 120px;">
                            <option value="">Select Location</option>
                        </select>
                    </td>
                    <td>
                        <select name="loading_program_items[${index}][sub_arrival_location_id]" class="form-control form-control-sm select2 sub-arrival-location-select" required style="min-width: 120px;">
                            <option value="">Select Sub Location</option>
                        </select>
                    </td>
                    <td>
                        <input type="text" name="loading_program_items[${index}][driver_name]" class="form-control form-control-sm" style="min-width: 100px;">
                    </td>
                    <td>
                        <input type="text" name="loading_program_items[${index}][contact_details]" class="form-control form-control-sm" style="min-width: 100px;">
                    </td>
                    <td>
                        <input type="number" name="loading_program_items[${index}][qty]" class="form-control form-control-sm" step="0.01" style="min-width: 70px;">
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-danger remove-item-btn">
                            <i class="ft-trash-2"></i>
                        </button>
                    </td>
                </tr>
            `;

            $('#itemsList').append(itemHtml);
            $('#noItemsRow').hide();
            $('.select2').select2();

            // Populate arrival locations based on selected ones in the top
            updateItemLocations();

            // Set packing and brand from delivery order for new item
            var deliveryOrderId = $('#delivery_order_id').val();
            if (deliveryOrderId) {
                var saleOrderId = $('#sale_order_id').val();
                if (saleOrderId) {
                    $.ajax({
                        url: '{{ route('sales.getDeliveryOrdersBySaleOrderLoading') }}',
                        type: 'GET',
                        data: { sale_order_id: saleOrderId },
                        success: function(response) {
                            if (response.success && response.delivery_orders) {
                                var selectedDeliveryOrder = response.delivery_orders.find(function(d_o) {
                                    return d_o.id == deliveryOrderId;
                                });

                                if (selectedDeliveryOrder && selectedDeliveryOrder.delivery_order_data && selectedDeliveryOrder.delivery_order_data.length > 0) {
                                    var firstItem = selectedDeliveryOrder.delivery_order_data[0];
                                    console.log('Delivery order data:', selectedDeliveryOrder);
                                    console.log('First item:', firstItem);
                                    console.log('Brand data:', firstItem.brand);

                                    $('input[name="loading_program_items[' + index + '][packing]"]').val(firstItem.bag_size || '');
                                    $('input[name="loading_program_items[' + index + '][brand_id]"]').val(firstItem.brand_id || (firstItem.brand ? firstItem.brand.id : ''));
                                    $('input[name="loading_program_items[' + index + '][brand_name]"]').val(firstItem.brand ? firstItem.brand.name : '');

                                    console.log('Setting brand for item ' + index + ':', {
                                        brand_id: firstItem.brand_id || (firstItem.brand ? firstItem.brand.id : ''),
                                        brand_name: firstItem.brand ? firstItem.brand.name : '',
                                        firstItem: firstItem
                                    });
                                }
                            }
                        }
                    });
                }
            }
        }

        // Remove item functionality
        $(document).on('click', '.remove-item-btn', function() {
            $(this).closest('tr').remove();

            // Show "no items" row if no items remain
            if ($('#itemsList tr.item-row').length === 0) {
                $('#noItemsRow').show();
            }
        });

        function updateItemLocations() {
            const selectedArrivalLocations = $('#arrival_locations').val() || [];
            const selectedSubArrivalLocations = $('#sub_arrival_locations').val() || [];

            // Update arrival location options
            $('.arrival-location-select').each(function() {
                const $select = $(this);
                const currentValue = $select.val(); // Store current selected value
                $select.empty().append('<option value="">Select Location</option>');

                // Get location names from the main arrival_locations select
                $('#arrival_locations option').each(function() {
                    const value = $(this).val();
                    const text = $(this).text();
                    if (value && selectedArrivalLocations.includes(value)) {
                        const option = new Option(text, value, false, currentValue == value);
                        $select.append(option);
                    }
                });
            });

            // Update sub arrival location options
            $('.sub-arrival-location-select').each(function() {
                const $select = $(this);
                const currentValue = $select.val(); // Store current selected value
                $select.empty().append('<option value="">Select Sub Location</option>');

                // Get location names from the main sub_arrival_locations select
                $('#sub_arrival_locations option').each(function() {
                    const value = $(this).val();
                    const text = $(this).text();
                    if (value && selectedSubArrivalLocations.includes(value)) {
                        const option = new Option(text, value, false, currentValue == value);
                        $select.append(option);
                    }
                });
            });
        }
    });
</script>

