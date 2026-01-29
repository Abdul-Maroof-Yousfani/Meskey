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
                        <option value="{{ $SaleOrder->id }}" data-type="{{ $SaleOrder->pay_type_id }}">
                            {{ $SaleOrder->reference_no }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group">
                <label id="delivery_order_label">Delivery Order: <span id="delivery_order_required_mark"
                        class="text-danger">*</span></label>
                <select class="form-control select2" name="delivery_order_id[]" id="delivery_order_id" multiple>
                    <option value="">Select Delivery Order</option>
                    @foreach ($DeliveryOrders as $deliveryOrder)
                        <option value="{{ $deliveryOrder->id }}">
                            {{ $deliveryOrder->reference_no }}
                        </option>
                    @endforeach
                </select>
                <small id="delivery_order_optional_note" class="text-muted" style="display: none;">
                    Delivery Order is optional for this Sale Order. You can add it later during Second Weighbridge.
                </small>
            </div>
        </div>
        <input type="hidden" id="is_delivery_order_optional" value="0">
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
                <select class="form-control select2 w-100" name="company_locations" id="company_locations"
                    style="width: 100% !important;" multiple disabled>
                    <!-- Options will be populated dynamically -->
                </select>
            </div>
        </div>
        <div class="col-xs-12 col-sm-4 col-md-4">
            <div class="form-group">
                <label>Arrival Location</label>
                <select class="form-control select2 w-100" name="arrival_locations[]" id="arrival_locations" multiple
                    disabled style="width: 100% !important;">
                    <!-- Options will be populated dynamically -->
                </select>
            </div>
        </div>
        <div class="col-xs-12 col-sm-4 col-md-4">
            <div class="form-group">
                <label>Sub Arrival Location</label>
                <select class="form-control select2 w-100" name="sub_arrival_locations[]" id="sub_arrival_locations"
                    multiple disabled style="width: 100% !important;">
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
                            <th width="10%">Delivery Order</th>
                            <th width="12%">DO Qty</th>
                            <th width="12%">Truck Number *</th>
                            <th width="12%">Container Number</th>
                            <th width="10%">Packing</th>
                            <th width="10%">Brand</th>
                            <th width="13%">Factory/Arrival Location *</th>
                            <th width="13%">Gala/Sub Arrival Location *</th>
                            <th width="8%">Driver Name</th>
                            <th width="8%">Contact Details</th>
                            <th width="6%">Suggested Qty</th>
                            <th width="8%">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="itemsList">
                        <!-- Items will be added here dynamically -->
                        <tr id="noItemsRow">
                            <td colspan="11" class="text-center text-muted py-3">
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

    <div class="alert alert-danger mt-3" id="incompatible-dos" style="display: none">
        <span style="font-weight: bold">Alert: </span>All selected delivery orders must have the same location. Selected Delivery Orders are not compatible
    </div>  
<script>
    $(document).ready(function() {
        $('.select2').select2();

    });

    // Function to update delivery order options for a specific row
    function updateDeliveryOrderOptionsForRow($select) {
        const currentValue = $select.val();
        $select.empty().append('<option value="">Select Delivery Order</option>');

        // Get selected delivery orders from top
        const selectedDeliveryOrderIds = $('#delivery_order_id').val() || [];

        // If no delivery orders selected at top, don't show any options
        if (selectedDeliveryOrderIds.length === 0) {
            return;
        }

        // Get delivery order options from the main delivery order select
        $('#delivery_order_id option').each(function() {
            const value = $(this).val();
            const text = $(this).text();
            if (value && selectedDeliveryOrderIds.includes(value)) {
                const option = new Option(text, value, false, currentValue == value);
                $select.append(option);
            }
        });

        // Reinitialize select2
        $select.select2();
    }

    // Function to update delivery order options for all rows
    function updateDeliveryOrderOptionsForAllRows() {
        $('.delivery-order-select').each(function() {
            updateDeliveryOrderOptionsForRow($(this));
        });
    }

    function get_sale_order(sale_order_id, type_id) {
        if(type_id == 11) {
             $.ajax({
            url: '{{ route('sales.so.locations') }}',
            type: 'GET',
            data: {
                so_id: sale_order_id
            },
            dataType: 'json',
            success: function(response) {
                const [arrivalLocations, subArrivalLocations] = response;
                
                $(".arrival-location-select").each(function(index, element) {
                    $(element).empty();
                    $(element).select2({
                        data: arrivalLocations
                    });
                });

                $(".sub-arrival-location-select").each(function(index, element) {
                    $(element).closest("tr").find("arrival-location-select").trigger("change");
                    $(element).empty();
                    $(element).select2({
                        data: subArrivalLocations
                    });
                })
            },
            error: function() {
                Swal.close();
                Swal.fire("Error", "Something went wrong. Please try again.",
                    "error");
            }
        });
        } else {
            $(".arrival-location-select").each(function(index, element) {
                $(element).empty();
          
            });

            $(".sub-arrival-location-select").each(function(index, element) {
                $(element).empty();
            })
        }

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
                    if (type_id != 11) {
                        $("#locationContainer").hide();
                    } else {
                        $("#locationContainer").show();
                    }

                    // Append the rendered HTML to a container element
                    $('#saleOrderDataContainer').html(response.html);

                    // Reinitialize select2 for any new dropdowns
                    $('.select2').select2();

                    // Store sale order data for use when no delivery order is selected
                    if (response.sale_order_data) {
                        window.saleOrderData = response.sale_order_data;
                    }

                    // Handle delivery order optional status
                    if (response.is_delivery_order_optional) {
                        $('#is_delivery_order_optional').val('1');
                        $('#delivery_order_required_mark').hide();
                        $('#delivery_order_optional_note').show();
                        // Show location and line items containers even without delivery order
                        $('#locationContainer').show();
                        $('#lineItemsContainer').show();
                    } else {
                        $('#is_delivery_order_optional').val('0');
                        $('#delivery_order_required_mark').show();
                        $('#delivery_order_optional_note').hide();
                    }

                    // Fetch and populate delivery orders for this sale order
                    $.ajax({
                        url: '{{ route('sales.getDeliveryOrdersBySaleOrderLoading') }}',
                        type: 'GET',
                        data: { sale_order_id: sale_order_id },
                        success: function(deliveryResponse) {
                            if (deliveryResponse.success && deliveryResponse.delivery_orders) {
                                // Update delivery order dropdown
                                var $deliveryOrderSelect = $('#delivery_order_id');
                                $deliveryOrderSelect.empty().append('<option value="">Select Delivery Order</option>');

                                var deliveryOrders = Array.isArray(deliveryResponse.delivery_orders)
                                    ? deliveryResponse.delivery_orders
                                    : [deliveryResponse.delivery_orders]; // safety fallback

                                deliveryOrders.forEach(function(deliveryOrder, index) {
                                    if (!deliveryOrder) return; // in case null
                                    var option = new Option(deliveryOrder.reference_no, deliveryOrder.id, false, false);
                                    $deliveryOrderSelect.append(option);
                                });

                                $deliveryOrderSelect.select2();

                                // Update delivery order options in existing line items
                                updateDeliveryOrderOptionsForAllRows();
                            }
                        },
                        error: function() {
                            console.log('Error fetching delivery orders');
                        }
                    });
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
    }

    $(document).ready(function() {
        // Handle sale order change
        $('#sale_order_id').change(function() {
            var sale_order_id = $(this).val();
            const type_id = $(this).find(':selected').data('type');
    

            if (sale_order_id) {
                get_sale_order(sale_order_id, type_id);
            } else {
                // Clear containers if no sale order selected
                $('#saleOrderDataContainer').html('');
                $('#delivery_order_id').empty().append(
                    '<option value="">Select Delivery Order</option>');
                    
                $('#lineItemsContainer').hide();
                $('#locationContainer').hide();
                // Reset optional status
                $('#is_delivery_order_optional').val('0');
                $('#delivery_order_required_mark').show();
                $('#delivery_order_optional_note').hide();

                // Clear delivery order options in existing line items
                updateDeliveryOrderOptionsForAllRows();

                // Clear location dropdowns
                $('#company_locations').empty();
                $('#arrival_locations').empty();
                $('#sub_arrival_locations').empty();
                $('#company_locations').select2();
                $('#arrival_locations').select2();
                $('#sub_arrival_locations').select2();

                // Clear location options in existing line items
                updateItemLocations();
            }

            const elements_to_reset = [
               ".delivery-order-select",
               ".do_qty",
               ".arrival-location-select",
               ".sub-arrival-location-select" 
            ];

            elements_to_reset.forEach(function(selector) {
                $(selector).each(function () {
                    $(this).val("");
                });
            });
            
        });

        // Handle delivery order change
        $('#delivery_order_id').change(function() {
            var delivery_order_ids = $(this).val();
            const type_id = $("#sale_order_id option:selected").data("type");
            const submitBtn = $(".submitbutton");
            
            var delivery_order_texts = [...new Set(
                $(this).find('option:selected').map(function() {
                    return $(this).text().split(" - ")[1];
                }).get()
            )];

            if(delivery_order_texts.length > 1) {
                $("#incompatible-dos").css("display", "block");
                submitBtn.attr("disabled", "disabled");
            } else {
                $("#incompatible-dos").css("display", "none ");
                submitBtn.removeAttr("disabled");
            }


         

            if (delivery_order_ids.length === 0) {
                get_sale_order($("#sale_order_id").val(), type_id);
            }
            if (delivery_order_ids && delivery_order_ids.length > 0) {
                // Fetch delivery order data to get packing and brand
                var saleOrderId = $('#sale_order_id').val();
                if (saleOrderId) {
                    $.ajax({
                        url: '{{ route('sales.getDeliveryOrdersBySaleOrderLoading') }}',
                        type: 'GET',
                        data: {
                            sale_order_id: saleOrderId
                        },
                        success: function(response) {
                            if (response.success && response.delivery_orders) {
                                // Find all selected delivery orders
                                var selectedDeliveryOrders = [];
                                if (Array.isArray(response.delivery_orders)) {
                                    selectedDeliveryOrders = response.delivery_orders
                                        .filter(function(d_o) {
                                            return delivery_order_ids.includes(d_o.id
                                                .toString());
                                        });
                                } else {
                                    // Handle single delivery order case
                                    if (delivery_order_ids.includes(response.delivery_orders
                                            .id.toString())) {
                                        selectedDeliveryOrders = [response.delivery_orders];
                                    }
                                }

                                if (selectedDeliveryOrders.length > 0) {
                                    populateLocationFields(selectedDeliveryOrders);

                                    // Update line item location dropdowns
                                    updateItemLocations();

                                    // Use data from the first delivery order for packing and brand
                                    var firstDeliveryOrder = selectedDeliveryOrders[0];
                                    if (firstDeliveryOrder.delivery_order_data &&
                                        firstDeliveryOrder.delivery_order_data.length > 0) {
                                        var firstItem = firstDeliveryOrder
                                            .delivery_order_data[0];

                                        // Set default packing and brand for new items
                                        $('input[name="loading_program_items[0][packing]"]')
                                            .val(firstItem.bag_size || '');
                                        $('input[name="loading_program_items[0][brand_id]"]')
                                            .val(firstItem.brand_id || '');
                                        $('input[name="loading_program_items[0][brand_name]"]')
                                            .val(firstItem.brand ? firstItem.brand.name :
                                                '');
                                    }
                                }
                            }
                        }
                    });
                }

                // Update delivery order options in line items
                updateDeliveryOrderOptionsForAllRows();

                // Show location and line items containers
                $('#locationContainer').show();
                $('#lineItemsContainer').show();
                      updateItemLocations();
          
            } else {
                // Clear delivery order options in line items when no delivery orders selected
                $('.delivery-order-select').each(function() {
                    $(this).empty().append('<option value="">Select Delivery Order</option>');
                    $(this).select2();
                });

                // When no delivery orders are selected, populate locations from sale order data
                var isOptional = $('#is_delivery_order_optional').val() === '1';
                var currentSaleOrderType = $('#sale_order_id').find(':selected').data('type');

                if ((isOptional || currentSaleOrderType == 11) && window.saleOrderData) {
                    // Populate locations from sale order data for type 11 or optional delivery orders
                    populateLocationsFromSaleOrder(window.saleOrderData);
                } else {
                    // Clear location dropdowns when no delivery orders selected and not type 11
                    $('#company_locations').empty();
                    $('#arrival_locations').empty();
                    $('#sub_arrival_locations').empty();
                    $('#company_locations').select2();
                    $('#arrival_locations').select2();
                    $('#sub_arrival_locations').select2();
                }

                // Update location options in existing line items
                updateItemLocations();

                // Hide location and line items containers only if delivery order is required
                if (!isOptional) {
                    $('#locationContainer').hide();
                    $('#lineItemsContainer').hide();
                }
            }
        });

        // Function to populate locations from sale order data (for when no delivery orders are selected)
        function populateLocationsFromSaleOrder(saleOrderData) {
            // Clear existing options
            $('#company_locations').empty();
            $('#arrival_locations').empty();
            $('#sub_arrival_locations').empty();

            // Get all locations data
            var companyLocations = @json(get_locations());
            var arrivalLocations = @json(\App\Models\Master\ArrivalLocation::all());
            var subArrivalLocations = @json(\App\Models\Master\ArrivalSubLocation::all());

            // For sale order locations, we need to get the location data
            // This assumes saleOrderData has the location IDs
            if (saleOrderData.arrival_location_id) {
                var arrivalLocationIds = Array.isArray(saleOrderData.arrival_location_id)
                    ? saleOrderData.arrival_location_id
                    : [saleOrderData.arrival_location_id];

                // Populate arrival locations
                $.each(arrivalLocations, function(index, location) {
                    if (arrivalLocationIds.includes(location.id.toString())) {
                        var option = new Option(location.name, location.id, false, false);
                        $('#arrival_locations').append(option);
                    }
                });

                // Set selected values
                $('#arrival_locations').val(arrivalLocationIds).trigger('change');
            }

            if (saleOrderData.sub_arrival_location_id) {
                var subArrivalLocationIds = Array.isArray(saleOrderData.sub_arrival_location_id)
                    ? saleOrderData.sub_arrival_location_id
                    : [saleOrderData.sub_arrival_location_id];

                // Populate sub arrival locations
                $.each(subArrivalLocations, function(index, location) {
                    if (subArrivalLocationIds.includes(location.id.toString())) {
                        var option = new Option(location.name, location.id, false, false);
                        $('#sub_arrival_locations').append(option);
                    }
                });

                // Set selected values
                $('#sub_arrival_locations').val(subArrivalLocationIds).trigger('change');
            }

            // Initialize select2
            $('#company_locations').select2();
            $('#arrival_locations').select2();
            $('#sub_arrival_locations').select2();
        }

        // Function to populate location fields from delivery order(s)
        function populateLocationFields(deliveryOrders) {
            console.log('Populating locations for delivery orders:', deliveryOrders);

            // Ensure deliveryOrders is an array
            if (!Array.isArray(deliveryOrders)) {
                deliveryOrders = [deliveryOrders];
            }

            // Clear existing options
            $('#company_locations').empty();
            $('#arrival_locations').empty();
            $('#sub_arrival_locations').empty();

            // Get all locations data
            var companyLocations = @json(get_locations());
            var arrivalLocations = @json(\App\Models\Master\ArrivalLocation::all());
            var subArrivalLocations = @json(\App\Models\Master\ArrivalSubLocation::all());

            // Collect unique location IDs from all selected delivery orders
            var selectedCompanyIds = [];
            var selectedArrivalIds = [];
            var selectedSubArrivalIds = [];

            deliveryOrders.forEach(function(deliveryOrder) {
                // Each delivery order has exactly one company location
                if (deliveryOrder.location_id && !selectedCompanyIds.includes(deliveryOrder.location_id
                        .toString())) {
                    selectedCompanyIds.push(deliveryOrder.location_id.toString());
                }

                // Parse comma-separated arrival location IDs
                if (deliveryOrder.arrival_location_id) {
                    var arrivalIds = deliveryOrder.arrival_location_id.split(',').map(id => id.trim());
                    arrivalIds.forEach(function(id) {
                        if (id && !selectedArrivalIds.includes(id)) {
                            selectedArrivalIds.push(id);
                        }
                    });
                }

                // Parse comma-separated sub-arrival location IDs
                if (deliveryOrder.sub_arrival_location_id) {
                    var subArrivalIds = deliveryOrder.sub_arrival_location_id.split(',').map(id => id
                        .trim());
                    subArrivalIds.forEach(function(id) {
                        if (id && !selectedSubArrivalIds.includes(id)) {
                            selectedSubArrivalIds.push(id);
                        }
                    });
                }
            });

            // Populate company locations
            $.each(companyLocations, function(index, location) {
                var option = new Option(location.name, location.id, false, false);
                $('#company_locations').append(option);
            });

            // Populate arrival locations
            $.each(arrivalLocations, function(index, location) {
                var option = new Option(location.name, location.id, false, false);
                $('#arrival_locations').append(option);
            });

            // Populate sub-arrival locations
            $.each(subArrivalLocations, function(index, location) {
                var option = new Option(location.name, location.id, false, false);
                $('#sub_arrival_locations').append(option);
            });

            // Re-initialize select2 to reflect changes
            $('#company_locations').select2();
            $('#arrival_locations').select2();
            $('#sub_arrival_locations').select2();

            // Set the selected values after select2 initialization
            if (selectedCompanyIds.length > 0) {
                $('#company_locations').val(selectedCompanyIds[0]).trigger('change');
            }
            if (selectedArrivalIds.length > 0) {
                $('#arrival_locations').val(selectedArrivalIds).trigger('change');
            }
            if (selectedSubArrivalIds.length > 0) {
                $('#sub_arrival_locations').val(selectedSubArrivalIds).trigger('change');
            }
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
                        <select onchange="getDoQty(this)" name="loading_program_items[${index}][delivery_order_id]" class="form-control form-control-sm select2 delivery-order-select" style="min-width: 100px;">
                            <option value="">Select Delivery Order</option>
                        </select>
                    </td>
                    <td>
                        <input type='text' name="do_qty" class="form-control do_qty" style="min-width: 100px;" readonly />
                        
                    </td>
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

            // Populate delivery order options for the new row
            updateDeliveryOrderOptionsForRow($('tr[data-index="' + index + '"] .delivery-order-select'));

            // Set packing and brand from delivery order or sale order for new item
            var deliveryOrderIds = $('#delivery_order_id').val();
            if (deliveryOrderIds && deliveryOrderIds.length > 0) {
                // Use delivery order data - we can access it from the global variable or make a call
                var saleOrderId = $('#sale_order_id').val();
                if (saleOrderId) {
                    $.ajax({
                        url: '{{ route('sales.getDeliveryOrdersBySaleOrderLoading') }}',
                        type: 'GET',
                        data: {
                            sale_order_id: saleOrderId
                        },
                        success: function(response) {
                            if (response.success && response.delivery_orders) {
                                // Find the first selected delivery order for packing/brand data
                                var selectedDeliveryOrder = Array.isArray(response
                                    .delivery_orders) ?
                                    response.delivery_orders.find(function(d_o) {
                                        return deliveryOrderIds.includes(d_o.id.toString());
                                    }) :
                                    (deliveryOrderIds.includes(response.delivery_orders.id
                                    .toString()) ? response.delivery_orders : null);

                                if (selectedDeliveryOrder && selectedDeliveryOrder
                                    .delivery_order_data && selectedDeliveryOrder
                                    .delivery_order_data.length > 0) {
                                    var firstItem = selectedDeliveryOrder.delivery_order_data[0];

                                    $('input[name="loading_program_items[' + index + '][packing]"]')
                                        .val(firstItem.bag_size || '');
                                    $('input[name="loading_program_items[' + index +
                                        '][brand_id]"]').val(firstItem.brand_id || (firstItem
                                        .brand ? firstItem.brand.id : ''));
                                    $('input[name="loading_program_items[' + index +
                                        '][brand_name]"]').val(firstItem.brand ? firstItem.brand
                                        .name : '');
                                }
                            }
                        }
                    });
                }
            } else if (window.saleOrderData) {
                // Use sale order data when no delivery order is selected
                $('input[name="loading_program_items[' + index + '][packing]"]').val(window.saleOrderData
                    .packing || '');
                $('input[name="loading_program_items[' + index + '][brand_id]"]').val(window.saleOrderData
                    .brand_id || '');
                $('input[name="loading_program_items[' + index + '][brand_name]"]').val(window.saleOrderData
                    .brand_name || '');

                // Pre-select factory and gala from sale order data
                var $factorySelect = $('select[name="loading_program_items[' + index +
                    '][arrival_location_id]"]');
                var $galaSelect = $('select[name="loading_program_items[' + index +
                    '][sub_arrival_location_id]"]');

                if (window.saleOrderData.arrival_location_id) {
                    setTimeout(function() {
                        $factorySelect.val(window.saleOrderData.arrival_location_id).trigger('change');

                        // Set gala after factory is set
                        setTimeout(function() {
                            if (window.saleOrderData.sub_arrival_location_id) {
                                $galaSelect.val(window.saleOrderData.sub_arrival_location_id)
                                    .trigger('change');
                            }
                        }, 100);
                    }, 100);
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

        // Store all sub arrival locations with their parent arrival_location_id
        var allSubArrivalLocations = @json(\App\Models\Master\ArrivalSubLocation::all());




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
                        const option = new Option(text, value, false, false);
                        $select.append(option);
                    }
                });

                // Trigger change to update corresponding gala dropdown
                if ($select.val()) {
                    $select.trigger('change');
                }
            });

            // Update sub arrival location options based on selected factory
            updateGalaOptionsForAllRows();
        }

        // Function to update gala options for a specific row based on selected factory
        function updateGalaOptions($factorySelect) {
            const selectedFactoryId = $factorySelect.val();
            const $row = $factorySelect.closest('tr');
            const $galaSelect = $row.find('.sub-arrival-location-select');
            const currentGalaValue = $galaSelect.val();
            const selectedSubArrivalLocations = $('#sub_arrival_locations').val() || [];

            $galaSelect.empty().append('<option value="">Select Sub Location</option>');

            if (selectedFactoryId) {
                // Filter sub arrival locations that:
                // 1. Belong to the selected factory (arrival_location_id matches)
                // 2. Are in the delivery order's sub arrival locations
                allSubArrivalLocations.forEach(function(subLocation) {
                    if (subLocation.arrival_location_id == selectedFactoryId &&
                        selectedSubArrivalLocations.includes(subLocation.id.toString())) {
                        const option = new Option(subLocation.name, subLocation.id, false,
                            currentGalaValue == subLocation.id);
                        $galaSelect.append(option);
                    }
                });
            }

            // Reinitialize select2
            $galaSelect.select2();
        }

        // Function to update gala options for all rows
        function updateGalaOptionsForAllRows() {
            $('.arrival-location-select').each(function() {
                updateGalaOptions($(this));
            });
        }

        // Event listener for factory select change in rows
        $(document).on('change', '.arrival-location-select', function() {
            updateGalaOptions($(this));
        });
    });

    function getDoQty(el) {
        $.ajax({
            url: '{{ route('sales.getDoQty') }}',
            type: 'GET',
            data: {
                do_id: $(el).val()
            },
            dataType: 'json',

            success: function(response) {
                $(el).closest("tr").find(".do_qty").val(response);
                console.log(response);
            },
            error: function() {
                Swal.close();
                Swal.fire("Error", "Something went wrong. Please try again.",
                    "error");
            }
        });
    }
</script>
