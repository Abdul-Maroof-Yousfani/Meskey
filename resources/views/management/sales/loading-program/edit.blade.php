

<div class="modal-body">
    <form action="{{ route('sales.loading-program.update', $LoadingProgram->id) }}" method="POST" id="ajaxSubmit" autocomplete="off">
        @csrf
        @method('PUT')
        <input type="hidden" id="listRefresh" value="{{ route('sales.get.loading-program') }}" />
    <div class="row form-mar">

        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group">
                <label>Sale Order:</label>
                <select class="form-control select2" name="sale_order_id" id="sale_order_id">
                    <option value="">Select Sale Order</option>
                    @foreach ($SaleOrders as $SaleOrder)
                        <option value="{{ $SaleOrder->id }}" {{ $SaleOrder->id == $LoadingProgram->sale_order_id ? 'selected' : '' }}>
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
                        <option value="{{ $deliveryOrder->id }}" {{ $deliveryOrder->id == $LoadingProgram->delivery_order_id ? 'selected' : '' }}>
                            {{ $deliveryOrder->reference_no }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>
    <div class="row" id="saleOrderDataContainer">
        @if($LoadingProgram->saleOrder)
            <div class="col-12">
                <h6 class="header-heading-sepration">
                    Sale Order Details
                </h6>
            </div>

            {{-- Sale Order Details Section --}}
            <div class="col-xs-12 col-sm-6 col-md-3">
                <div class="form-group">
                    <label>Buyer:</label>
                    <input type="text" value="{{ $LoadingProgram->saleOrder->customer->name ?? 'N/A' }}"
                        disabled class="form-control" autocomplete="off" readonly />
                </div>
            </div>

            <div class="col-xs-12 col-sm-6 col-md-3">
                <div class="form-group">
                    <label>Commodity:</label>
                    <input type="text" value="{{ $LoadingProgram->saleOrder->sales_order_data->first()->item->name ?? 'N/A' }}"
                        disabled class="form-control" autocomplete="off" readonly />
                </div>
            </div>

            <div class="col-xs-12 col-sm-6 col-md-3">
                <div class="form-group">
                    <label>SO Date:</label>
                    <input type="text" value="{{ $LoadingProgram->saleOrder->order_date ? $LoadingProgram->saleOrder->order_date : 'N/A' }}"
                        disabled class="form-control" autocomplete="off" readonly />
                </div>
            </div>

            <div class="col-xs-12 col-sm-6 col-md-3">
                <div class="form-group">
                    <label>SO Qty:</label>
                    <input type="text" value="{{ $LoadingProgram->saleOrder->sales_order_data->first()->qty ?? 'N/A' }}"
                        disabled class="form-control" autocomplete="off" readonly />
                </div>
            </div>
        @endif
    </div>

    <div class="row" id="locationContainer" >
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
                        <!-- Existing items will be populated here -->
                        @forelse($LoadingProgram->loadingProgramItems as $index => $item)
                            <tr class="item-row" data-index="{{ $index }}">
                                <td>
                                    <input type="text" name="loading_program_items[{{ $index }}][truck_number]" class="form-control form-control-sm" required style="min-width: 100px;" value="{{ $item->truck_number }}">
                                    <input type="hidden" name="loading_program_items[{{ $index }}][transaction_number]" class="form-control form-control-sm" required style="min-width: 100px;" value="{{ $item->transaction_number }}">
                                </td>
                                <td>
                                    <input type="text" name="loading_program_items[{{ $index }}][container_number]" class="form-control form-control-sm" style="min-width: 100px;" value="{{ $item->container_number }}">
                                </td>
                                <td>
                                    <input type="text" name="loading_program_items[{{ $index }}][packing]" class="form-control form-control-sm" readonly style="min-width: 80px;" value="{{ $item->packing }}">
                                </td>
                                <td>
                                    <input type="hidden" name="loading_program_items[{{ $index }}][brand_id]" class="form-control form-control-sm" style="min-width: 80px;" value="{{ $item->brand_id }}">
                                    <input type="text" name="loading_program_items[{{ $index }}][brand_name]" class="form-control form-control-sm" readonly style="min-width: 80px;" value="{{ $item?->brand?->name ?? ''}}">
                                </td>
                                <td>
                                    <select name="loading_program_items[{{ $index }}][arrival_location_id]" class="form-control form-control-sm select2 arrival-location-select" required style="min-width: 120px;">
                                        <option value="">Select Location</option>
                                    </select>
                                </td>
                                <td>
                                    <select name="loading_program_items[{{ $index }}][sub_arrival_location_id]" class="form-control form-control-sm select2 sub-arrival-location-select" required style="min-width: 120px;">
                                        <option value="">Select Sub Location</option>
                                    </select>
                                </td>
                                <td>
                                    <input type="text" name="loading_program_items[{{ $index }}][driver_name]" class="form-control form-control-sm" style="min-width: 100px;" value="{{ $item->driver_name }}">
                                </td>
                                <td>
                                    <input type="text" name="loading_program_items[{{ $index }}][contact_details]" class="form-control form-control-sm" style="min-width: 100px;" value="{{ $item->contact_details }}">
                                </td>
                                <td>
                                    <input type="number" name="loading_program_items[{{ $index }}][qty]" class="form-control form-control-sm" step="0.01" style="min-width: 70px;" value="{{ $item->qty }}">
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-danger remove-item-btn">
                                        <i class="ft-trash-2"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr id="noItemsRow">
                                <td colspan="10" class="text-center text-muted py-3">
                                    No items found. Click "Add Item" to add loading program items.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Remark:</label>
                <textarea name="remark" placeholder="Remarks" class="form-control">{{ $LoadingProgram->remark }}</textarea>
            </div>
        </div>
    </div>
    <div class="row bottom-button-bar" style="position: sticky; bottom: 0; background: white; padding: 15px 0; border-top: 1px solid #dee2e6; margin-top: 20px;">
        <div class="col-12 text-right">
            <a href="{{ route('sales.loading-program.index') }}" class="btn btn-secondary mr-2">Cancel</a>
            <button type="submit" class="btn btn-primary submitbutton">Update</button>
        </div>
    </div>
    </form>
</div>


<script>
    $(document).ready(function() {
        $('.select2').select2();

        // Ensure modal footer is visible
        $('.modal-footer').show();

        $("#delivery_order_id").trigger("change");
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
                                });

                                // Note: Packing and brand values are set when adding new items in addItemRow function
                                // Existing items should keep their original values
                            }
                        }
                    });
                }

                // For edit form, containers are always shown
                // $('#locationContainer').show();
                // $('#lineItemsContainer').show();
            } else {
                // For edit form, don't hide containers
                // $('#locationContainer').hide();
                // $('#lineItemsContainer').hide();
            }
        });

        // Add item functionality
        let itemIndex = {{ $LoadingProgram->loadingProgramItems->count() }};
        $('#addItemBtn').click(function() {
            addItemRow(itemIndex);
            itemIndex++;
        });

        function addItemRow(index) {
            const itemHtml = `
                <tr class="item-row" data-index="${index}">
                    <td>
                        <input type="text" name="loading_program_items[${index}][truck_number]" class="form-control form-control-sm" required style="min-width: 100px;">
                        <input type="hidden" name="loading_program_items[${index}][transaction_number]" class="form-control form-control-sm" required style="min-width: 100px;">
                             
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
                        url: '{{ route('sales.getDeliveryOrdersBySaleOrderLoadingEdit') }}',
                        type: 'GET',
                        data: { sale_order_id: saleOrderId },
                        success: function(response) {
                            console.log(response);
                            if (response.success && response.delivery_orders) {
                                var selectedDeliveryOrder = response.delivery_orders.find(function(d_o) {
                                    return d_o.id == deliveryOrderId;
                                });

                                if (selectedDeliveryOrder && selectedDeliveryOrder.delivery_order_data && selectedDeliveryOrder.delivery_order_data.length > 0) {
                                    var firstItem = selectedDeliveryOrder.delivery_order_data[0];
                                    $('input[name="loading_program_items[' + index + '][packing]"]').val(firstItem.bag_size || '');
                                    $('input[name="loading_program_items[' + index + '][brand_id]"]').val(firstItem.brand_id || '');
                                    $('input[name="loading_program_items[' + index + '][brand_name]"]').val(firstItem.brand ? firstItem.brand.name : '');
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

        // Initialize form on page load
        $(document).ready(function() {
            // Populate and pre-select company locations
            var companyLocationsSelect = $('#company_locations');
            companyLocationsSelect.empty();

            // Populate and pre-select arrival locations
            var arrivalLocationsSelect = $('#arrival_locations');
            arrivalLocationsSelect.empty();

            // Populate and pre-select sub arrival locations
            var subArrivalLocationsSelect = $('#sub_arrival_locations');
            subArrivalLocationsSelect.empty();

            @if($LoadingProgram && $LoadingProgram->deliveryOrder)
                @php
                    // Get locations from delivery order's comma-separated values
                    $companyLocationIds = [$LoadingProgram->deliveryOrder->location_id];
                    $arrivalLocationIds = $LoadingProgram->deliveryOrder->arrival_location_id ? explode(',', $LoadingProgram->deliveryOrder->arrival_location_id) : [];
                    $subArrivalLocationIds = $LoadingProgram->deliveryOrder->sub_arrival_location_id ? explode(',', $LoadingProgram->deliveryOrder->sub_arrival_location_id) : [];

                    $companyLocations = \App\Models\Master\CompanyLocation::whereIn('id', $companyLocationIds)->get();
                    $arrivalLocations = \App\Models\Master\ArrivalLocation::whereIn('id', $arrivalLocationIds)->get();
                    $subArrivalLocations = \App\Models\Master\ArrivalSubLocation::whereIn('id', $subArrivalLocationIds)->get();
                @endphp

                @foreach($companyLocations as $location)
                    var option = new Option('{{ $location->name }}', '{{ $location->id }}', true, true);
                    companyLocationsSelect.append(option);
                @endforeach

                @foreach($arrivalLocations as $location)
                    var option = new Option('{{ $location->name }}', '{{ $location->id }}', true, true);
                    arrivalLocationsSelect.append(option);
                @endforeach

                @foreach($subArrivalLocations as $location)
                    var option = new Option('{{ $location->name }}', '{{ $location->id }}', true, true);
                    subArrivalLocationsSelect.append(option);
                @endforeach
            @endif

            // Trigger change to refresh select2
            companyLocationsSelect.trigger('change');
            arrivalLocationsSelect.trigger('change');
            subArrivalLocationsSelect.trigger('change');

            // For edit form, don't automatically trigger sale order change on load
            // The sale order data should already be loaded from the server
            // Only trigger when user manually changes the sale order

            // Populate existing item location dropdowns
            updateItemLocations();

            // Set selected values for existing items
            @if($LoadingProgram->loadingProgramItems)
                @foreach($LoadingProgram->loadingProgramItems as $index => $item)
                    $('select[name="loading_program_items[{{ $index }}][arrival_location_id]"]').val('{{ $item->arrival_location_id }}').trigger('change');
                    $('select[name="loading_program_items[{{ $index }}][sub_arrival_location_id]"]').val('{{ $item->sub_arrival_location_id }}').trigger('change');
                @endforeach
            @endif
        });
    });
</script>