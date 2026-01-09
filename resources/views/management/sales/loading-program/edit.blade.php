

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
                @php
                    $isDeliveryOrderOptional = $LoadingProgram->saleOrder && $LoadingProgram->saleOrder->pay_type_id == 11;
                @endphp
                <label id="delivery_order_label">Delivery Order: <span id="delivery_order_required_mark" class="text-danger" @if($isDeliveryOrderOptional) style="display: none;" @endif>*</span></label>
                <select class="form-control select2" name="delivery_order_id" id="delivery_order_id">
                    <option value="">Select Delivery Order</option>
                    @foreach ($DeliveryOrders as $deliveryOrder)
                        <option value="{{ $deliveryOrder->id }}" {{ $deliveryOrder->id == $LoadingProgram->delivery_order_id ? 'selected' : '' }}>
                            {{ $deliveryOrder->reference_no }}
                        </option>
                    @endforeach
                </select>
                <small id="delivery_order_optional_note" class="text-muted" @if(!$isDeliveryOrderOptional) style="display: none;" @endif>
                    Delivery Order is optional for this Sale Order. You can add it later during Second Weighbridge.
                </small>
            </div>
        </div>
        <input type="hidden" id="is_delivery_order_optional" value="{{ $isDeliveryOrderOptional ? '1' : '0' }}">
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
                            <th width="8%">Suggested Qty</th>
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

                            // Store sale order data for use when no delivery order is selected
                            if (response.sale_order_data) {
                                window.saleOrderData = response.sale_order_data;
                            }

                            // Handle delivery order optional status
                            if (response.is_delivery_order_optional) {
                                $('#is_delivery_order_optional').val('1');
                                $('#delivery_order_required_mark').hide();
                                $('#delivery_order_optional_note').show();
                            } else {
                                $('#is_delivery_order_optional').val('0');
                                $('#delivery_order_required_mark').show();
                                $('#delivery_order_optional_note').hide();
                            }
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

            // Set packing and brand from delivery order or sale order for new item
            var deliveryOrderId = $('#delivery_order_id').val();
            if (deliveryOrderId) {
                // Use delivery order data
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
            } else if (window.saleOrderData) {
                // Use sale order data when no delivery order is selected
                $('input[name="loading_program_items[' + index + '][packing]"]').val(window.saleOrderData.packing || '');
                $('input[name="loading_program_items[' + index + '][brand_id]"]').val(window.saleOrderData.brand_id || '');
                $('input[name="loading_program_items[' + index + '][brand_name]"]').val(window.saleOrderData.brand_name || '');

                // Pre-select factory and gala from sale order data
                var $factorySelect = $('select[name="loading_program_items[' + index + '][arrival_location_id]"]');
                var $galaSelect = $('select[name="loading_program_items[' + index + '][sub_arrival_location_id]"]');

                if (window.saleOrderData.arrival_location_id) {
                    setTimeout(function() {
                        $factorySelect.val(window.saleOrderData.arrival_location_id).trigger('change');
                        
                        // Set gala after factory is set
                        setTimeout(function() {
                            if (window.saleOrderData.sub_arrival_location_id) {
                                $galaSelect.val(window.saleOrderData.sub_arrival_location_id).trigger('change');
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
                        const option = new Option(text, value, false, currentValue == value);
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
                        const option = new Option(subLocation.name, subLocation.id, false, currentGalaValue == subLocation.id);
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

        // Initialize form on page load
        $(document).ready(function() {
            // Initialize sale order data from existing loading program's sale order
            @if($LoadingProgram && $LoadingProgram->saleOrder)
                @php
                    $firstSoData = $LoadingProgram->saleOrder->sales_order_data->first();
                @endphp
                window.saleOrderData = {
                    packing: '{{ $firstSoData->bag_size ?? '' }}',
                    brand_id: '{{ $firstSoData->brand_id ?? '' }}',
                    brand_name: '{{ $firstSoData->brand->name ?? '' }}',
                    arrival_location_id: '{{ $LoadingProgram->saleOrder->arrival_location_id ?? '' }}',
                    sub_arrival_location_id: '{{ $LoadingProgram->saleOrder->arrival_sub_location_id ?? '' }}'
                };
            @endif

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
            @elseif($LoadingProgram && $LoadingProgram->saleOrder)
                {{-- If no delivery order, populate from Sale Order --}}
                @php
                    // Get company location from Sale Order's locations relationship
                    $soCompanyLocationId = $LoadingProgram->saleOrder->locations->first()?->location_id;
                    $soArrivalLocationId = $LoadingProgram->saleOrder->arrival_location_id;
                    $soSubArrivalLocationId = $LoadingProgram->saleOrder->arrival_sub_location_id;
                    
                    $soCompanyLocation = $soCompanyLocationId ? \App\Models\Master\CompanyLocation::find($soCompanyLocationId) : null;
                    $allArrivalLocations = \App\Models\Master\ArrivalLocation::all();
                    $allSubArrivalLocations = \App\Models\Master\ArrivalSubLocation::all();
                    
                    $soArrivalLocation = $allArrivalLocations->where('id', $soArrivalLocationId)->first();
                    $soSubArrivalLocation = $allSubArrivalLocations->where('id', $soSubArrivalLocationId)->first();
                @endphp

                @if($soCompanyLocation)
                    var option = new Option('{{ $soCompanyLocation->name }}', '{{ $soCompanyLocation->id }}', true, true);
                    companyLocationsSelect.append(option);
                @endif

                @if($soArrivalLocation)
                    var option = new Option('{{ $soArrivalLocation->name }}', '{{ $soArrivalLocation->id }}', true, true);
                    arrivalLocationsSelect.append(option);
                @endif

                @if($soSubArrivalLocation)
                    var option = new Option('{{ $soSubArrivalLocation->name }}', '{{ $soSubArrivalLocation->id }}', true, true);
                    subArrivalLocationsSelect.append(option);
                @endif
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

            // Set selected values for existing items - first set factory, then gala after a small delay
            @if($LoadingProgram->loadingProgramItems)
                @foreach($LoadingProgram->loadingProgramItems as $index => $item)
                    // First set the factory value
                    $('select[name="loading_program_items[{{ $index }}][arrival_location_id]"]').val('{{ $item->arrival_location_id }}').trigger('change');
                @endforeach

                // After a small delay to ensure gala options are populated, set the gala values
                setTimeout(function() {
                    @foreach($LoadingProgram->loadingProgramItems as $index => $item)
                        $('select[name="loading_program_items[{{ $index }}][sub_arrival_location_id]"]').val('{{ $item->sub_arrival_location_id }}').trigger('change');
                    @endforeach
                }, 100);
            @endif
        });
    });
</script>