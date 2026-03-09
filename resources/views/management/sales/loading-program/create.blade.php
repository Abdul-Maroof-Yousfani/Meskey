<form action="{{ route('sales.loading-program.store') }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    <input type="hidden" id="listRefresh" value="{{ route('sales.get.loading-program') }}" />
    <div class="row form-mar">
        <div class="col-xs-12 col-sm-4 col-md-4">
            <div class="form-group">
                <label>Company Location:</label>
                <select class="form-control select2" name="main_company_location_id" id="main_company_location_id">
                    <option value="">Select Company Location</option>
                    @foreach (get_locations() as $loc)
                        <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="col-xs-12 col-sm-4 col-md-4">
            <div class="form-group">
                <label>Sale Order:</label>
                <select class="form-control select2" name="sale_order_id[]" id="sale_order_id" multiple disabled>
                    {{-- Populated by AJAX --}}
                </select>
            </div>
        </div>
        <div class="col-xs-12 col-sm-4 col-md-4">
            <div class="form-group">
                <label id="delivery_order_label">Delivery Order: <span id="delivery_order_required_mark"
                        class="text-danger">*</span></label>
                <select class="form-control select2" name="delivery_order_id[]" id="delivery_order_id" multiple disabled>
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
                <select class="form-control select2 w-100" name="company_locations[]" id="company_locations" multiple
                    disabled style="width: 100% !important;">
                    <option value="">Select Company Location</option>
                    @foreach(get_locations() as $loc)
                        <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                    @endforeach
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
        <style>
            #itemsTable {
                table-layout: fixed !important;
                min-width: 2010px !important;
                width: 2010px !important;
            }
            #itemsTable th, #itemsTable td {
                padding: 8px 4px !important;
                vertical-align: middle !important;
                overflow: hidden !important;
            }
            #itemsTable .form-control, 
            #itemsTable .select2-container {
                width: 100% !important;
            }
            .row-do-container {
                position: relative;
                width: 100%;
            }
            .row-do-required-mark {
                position: absolute;
                right: -10px;
                top: 5px;
            }
            .table-responsive {
                overflow-x: auto !important;
                -webkit-overflow-scrolling: touch;
            }
        </style>
        <div class="col-12">
            <h6 class="header-heading-sepration">
                Loading Program Items
                <button type="button" class="btn btn-sm btn-primary float-right" id="addItemBtn">Add Item</button>
            </h6>
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="itemsTable">
                    <thead class="thead-light">
                        <tr>
                            <th style="width: 250px">Sale Order *</th>
                            <th style="width: 250px">Delivery Order</th>
                            <th style="width: 150px">Truck Number *</th>
                            <th style="width: 150px">Container Number</th>
                            <th style="width: 140px">Packing</th>
                            <th style="width: 180px">Brand</th>
                            <th style="width: 220px">Factory/Arrival Location *</th>
                            <th style="width: 220px">Gala/Sub Arrival Location *</th>
                            <th style="width: 180px">Driver Name</th>
                            <th style="width: 180px">Contact Details</th>
                            <th style="width: 90px">Sug. Qty</th>
                            <th style="width: 90px">Actions</th>
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

    <!-- <div class="alert alert-danger mt-3" id="incompatible-dos" style="display: none">
        <span style="font-weight: bold">Alert: </span>All selected delivery orders must have the same location. Selected Delivery Orders are not compatible
    </div>   -->
<script>
    // Global UI state guards
    window.isUpdatingUI = false;
    window.isSelectingSO = false;
    var allSubArrivalLocations = @json(\App\Models\Master\ArrivalSubLocation::all());

    $('#main_company_location_id').change(function() {
        if (window.isUpdatingUI) return;
        const locationId = $(this).val();
        const $soSelect = $('#sale_order_id');
        const $doSelect = $('#delivery_order_id');
        
        window.isUpdatingUI = true;
        $soSelect.empty().prop('disabled', true).trigger('change.select2');
        $doSelect.empty().prop('disabled', true).trigger('change.select2');
        $('#saleOrderDataContainer').html('');
        $('#lineItemsContainer').hide();
        $('#locationContainer').hide();
        window.isUpdatingUI = false;

        if ($('#main_company_location_id').val()) {
            $.ajax({
                url: '{{ route('sales.fetchSaleOrdersByLocation') }}',
                type: 'GET',
                data: { location_id: locationId },
                success: function(response) {
                    if (response.success) {
                        window.isUpdatingUI = true;
                        $soSelect.append('<option value="">Select Sale Order</option>');
                        response.sale_orders.forEach(so => {
                            $soSelect.append(`<option value="${so.id}" data-type="${so.pay_type_id}">${so.reference_no}</option>`);
                        });
                        $soSelect.prop('disabled', false).trigger('change.select2');

                        // Reset line items as location has changed
                        $('.row-so-select').val([]).trigger('change.select2');
                        $('.delivery-order-select').empty().trigger('change.select2');
                        $('.packing-select').empty().trigger('change.select2');
                        $('.brand-select').val([]).trigger('change.select2');
                        $('.arrival-location-select').empty().trigger('change.select2');
                        $('.sub-arrival-location-select').empty().trigger('change.select2');

                        window.isUpdatingUI = false;
                    }
                }
            });
        }
    });

    $(document).ready(function() {
        // Initial setup
        $('.select2').select2({ width: '100%' });

        // Handle sale order change
        $('#sale_order_id').change(function() {
            if (window.isUpdatingUI) return;
            
            var sale_order_ids = $(this).val();
            const type_id = $(this).find(':selected').data('type');
            const $doSelect = $('#delivery_order_id');
            const company_location_id = $('#main_company_location_id').val();

            if (sale_order_ids && sale_order_ids.length > 0) {
                window.isSelectingSO = true;
         
                $doSelect.prop('disabled', (sale_order_ids.length === 0));
                get_sale_order(sale_order_ids, type_id, company_location_id);
         
                $('#lineItemsContainer').show();
            } else {
                window.isUpdatingUI = true;
                $('#saleOrderDataContainer').html('');
                $('#delivery_order_id').empty().trigger('change.select2');
                $('#lineItemsContainer').hide();
                $('#locationContainer').hide();
                $('#is_delivery_order_optional').val('0');
                $('#delivery_order_required_mark').show();
                $('#delivery_order_optional_note').hide();
                updateAllRowSaleOrderOptions();
                window.isUpdatingUI = false;
            }
        });

        // Handle delivery order change
        $('#delivery_order_id').change(function() {
            if (window.isUpdatingUI) return;
            
            var delivery_order_ids = $(this).val();
            const type_id = $("#sale_order_id option:selected").data("type");
            const submitBtn = $(".submitbutton");
            
            var delivery_order_texts = [...new Set(
                $(this).find('option:selected').map(function() {
                    return $(this).text().split(" - ")[1];
                }).get()
            )];

            // if(delivery_order_texts.length > 1) {
            //     $("#incompatible-dos").css("display", "block");
            //     submitBtn.attr("disabled", "disabled");
            // } else {
            //     $("#incompatible-dos").css("display", "none");
            //     submitBtn.removeAttr("disabled");
            // }

            if (delivery_order_ids.length === 0 && !window.isSelectingSO) {
                get_sale_order($("#sale_order_id").val(), type_id);
            }

            if (delivery_order_ids && delivery_order_ids.length > 0) {
                var saleOrderId = $('#sale_order_id').val();
                if (saleOrderId) {
                    $.ajax({
                        url: '{{ route('sales.getDeliveryOrdersBySaleOrderLoading') }}',
                        type: 'GET',
                        data: { 
                            sale_order_id: saleOrderId,
                            company_location_id: $('#main_company_location_id').val()
                        },
                        success: function(response) {
                            if (response.success && response.delivery_orders) {
                                var selectedDeliveryOrders = response.delivery_orders.filter(function(d_o) {
                                    return delivery_order_ids.includes(d_o.id.toString());
                                });

                                if (selectedDeliveryOrders.length > 0) {
                                    window.isUpdatingUI = true;
                                    populateLocationFields(selectedDeliveryOrders);
                                    updateItemLocations();
                                    
                                    var firstDeliveryOrder = selectedDeliveryOrders[0];
                                    if (firstDeliveryOrder.delivery_order_data && firstDeliveryOrder.delivery_order_data.length > 0) {
                                        var firstItem = firstDeliveryOrder.delivery_order_data[0];
                                        $('input[name="loading_program_items[0][packing]"]').val(firstItem.bag_size || '');
                                        $('input[name="loading_program_items[0][brand_id]"]').val(firstItem.brand_id || '');
                                        $('input[name="loading_program_items[0][brand_name]"]').val(firstItem.brand ? firstItem.brand.name : '');
                                    }
                                    window.isUpdatingUI = false;
                                }
                            }
                        }
                    });
                }

                window.isUpdatingUI = true;
                updateDeliveryOrderOptionsForAllRows();
                $('#locationContainer').show();
                $('#lineItemsContainer').show();
                updateItemLocations();
                window.isUpdatingUI = false;
                if (window.updateTabsVisibility) window.updateTabsVisibility();
            } else {
                window.isUpdatingUI = true;
                $('.delivery-order-select').each(function() {
                    $(this).empty().append('<option value="">Select Delivery Order</option>').select2();
                });

                var isOptional = $('#is_delivery_order_optional').val() === '1';
                var currentSaleOrderType = $('#sale_order_id').find(':selected').data('type');

                if ((isOptional || currentSaleOrderType == 11) && window.saleOrderData) {
                    populateLocationsFromSaleOrder(window.saleOrderData);
                } else {
                    $('#company_locations, #arrival_locations, #sub_arrival_locations').empty().select2();
                }

                updateItemLocations();

                if (!isOptional) {
                    $('#locationContainer').hide();
                    $('#lineItemsContainer').hide();
                }
                window.isUpdatingUI = false;
                if (window.updateTabsVisibility) window.updateTabsVisibility();
            }
        });

        // Add item button handler
        let itemIndex = 0;
        $('#addItemBtn').click(function() {
            addItemRow(itemIndex);
            itemIndex++;
        });

        // Remove item handler
        $(document).on('click', '.remove-item-btn', function() {
            $(this).closest('tr').remove();
            if ($('#itemsList tr.item-row').length === 0) {
                $('#noItemsRow').show();
            }
        });

        $('#loadingProgramForm').submit(function(e) {
            let truckNumbers = [];
            let duplicates = false;
            
            $('.item-row').each(function() {
                let truckNumber = $(this).find('input[name*="[truck_number]"]').val().trim();
                if (truckNumber) {
                    if (truckNumbers.includes(truckNumber)) {
                        duplicates = true;
                        return false;
                    }
                    truckNumbers.push(truckNumber);
                }
            });

            if (duplicates) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Duplicate Truck Number',
                    text: 'Same truck number cannot be added twice in the same loading program.'
                });
                return false;
            }
        });

        // Row factory change handler
        $(document).on('change', '.arrival-location-select', function() {
            if (window.isUpdatingUI) return;
            updateGalaOptions($(this));
        });

        $('#company_locations').change(function() {
            if (window.isUpdatingUI) return;
            const company_location_id = $(this).val();
            const sale_order_ids = $('#sale_order_id').val();
            
            if (company_location_id && sale_order_ids && sale_order_ids.length > 0) {
                $.ajax({
                    url: '{{ route('sales.get.locations') }}',
                    type: 'GET',
                    data: { 
                        sale_order_id: sale_order_ids, 
                        company_location: company_location_id 
                    },
                    success: function(response) {
                        window.isUpdatingUI = true;
                        const [arrivalLocs, subArrivalLocs] = response;
                        
                        $('#arrival_locations').empty();
                        arrivalLocs.forEach(loc => {
                            $('#arrival_locations').append(new Option(loc.text, loc.id, true, true));
                        });
                        $('#arrival_locations').trigger('change.select2');
                        
                        $('#sub_arrival_locations').empty();
                        subArrivalLocs.forEach(loc => {
                            $('#sub_arrival_locations').append(new Option(loc.text, loc.id, true, true));
                        });
                        $('#sub_arrival_locations').trigger('change.select2');
                        
                        window.isUpdatingUI = false;
                        updateItemLocations();
                    }
                });
            }
        });

        $('#arrival_locations, #sub_arrival_locations').change(function() {
            if (window.isUpdatingUI) return;
            updateItemLocations();
        });

        // Functions
        function get_sale_order(sale_order_ids, type_id) {
            if (!sale_order_ids || sale_order_ids.length === 0) {
                $('#saleOrderDataContainer').html('');
                $('#delivery_order_id').empty();
                $('#lineItemsContainer').hide();
                $('#locationContainer').hide();
                return;
            }
            

            const company_location_id = $('#main_company_location_id').val();

            $.ajax({
                url: '{{ route('sales.getSaleOrderRelatedData') }}',
                type: 'GET',
                data: { 
                    sale_order_id: sale_order_ids,
                    company_location_id: company_location_id
                },
                dataType: 'json',
                beforeSend: function() {
                    Swal.fire({
                        title: "Processing...",
                        text: "Please wait while fetching sale order details.",
                        allowOutsideClick: false,
                        didOpen: () => { Swal.showLoading(); }
                    });
                },
                success: function(response) {
                    Swal.close();
                    if (response.success) {
                        window.isUpdatingUI = true;
                        $('#saleOrderDataContainer').html(response.html);
                        $('.select2').select2();


                        if (response.sale_order_data) {
                            window.saleOrderData = response.sale_order_data;
                        }

                        

                        if (response.is_delivery_order_optional) {
                            $('#is_delivery_order_optional').val('1');
                            $('#delivery_order_required_mark').hide();
                            $('#delivery_order_optional_note').show();
                            $('#locationContainer').show();
                            $('#lineItemsContainer').show();
                            $('#company_locations, #arrival_locations, #sub_arrival_locations').prop('disabled', true);
                        } else {
                            $('#is_delivery_order_optional').val('0');
                            $('#delivery_order_required_mark').show();
                            $('#delivery_order_optional_note').hide();
                            $('#company_locations, #arrival_locations, #sub_arrival_locations').prop('disabled', true);
                        }
                        
                        updateAllRowSaleOrderOptions();
                        
                        window.isUpdatingUI = false;
                        if (window.updateTabsVisibility) window.updateTabsVisibility();
                    }
                },
                complete: function() {
                    window.isSelectingSO = false;
                },
                error: function() {
                    Swal.close();
                    Swal.fire("Error", "Something went wrong.", "error");
                }
            });
        }

        function updateAllRowSaleOrderOptions() {
            const mainSOOptions = [];
            $('#sale_order_id option:selected').each(function() {
                mainSOOptions.push({id: $(this).val(), text: $(this).text(), type: $(this).data('type')});
            });

            $('.row-so-select').each(function() {
                const $select = $(this);
                const currentValues = $select.val() || [];
                $select.empty();
                mainSOOptions.forEach(opt => {
                    let newOption = new Option(opt.text, opt.id, false, currentValues.includes(opt.id.toString()));
                    $(newOption).attr('data-type', opt.type);
                    $select.append(newOption);
                });
                $select.trigger('change.select2');
                updateRowDORequiredStatus($(this).closest('tr'));
            });
        }

        function addItemRow(index) {
            const mainSOOptions = [];
            $('#sale_order_id option:selected').each(function() {
                mainSOOptions.push({id: $(this).val(), text: $(this).text(), type: $(this).data('type')});
            });

            const itemHtml = `
                <tr class="item-row" data-index="${index}">
                    <td>
                        <select name="loading_program_items[${index}][sale_order_id][]" class="form-control select2 row-so-select" multiple>
                            ${mainSOOptions.map(opt => `<option value="${opt.id}" data-type="${opt.type}">${opt.text}</option>`).join('')}
                        </select>
                    </td>
                    <td>
                        <div class="row-do-container">
                            <select name="loading_program_items[${index}][delivery_order_id][]" class="form-control form-control-sm select2 delivery-order-select" multiple>
                                <option value="">Select Delivery Order</option>
                            </select>
                            <span class="text-danger row-do-required-mark" style="display: none;">*</span>
                        </div>
                    </td>
                    <td><input type="text" name="loading_program_items[${index}][truck_number]" class="form-control form-control-sm" required></td>
                    <td><input type="text" name="loading_program_items[${index}][container_number]" class="form-control form-control-sm"></td>
                    <td>
                        <input type="hidden" name="loading_program_items[${index}][packing]" class="packing-hidden">
                        <select class="form-control form-control-sm select2 packing-select" multiple disabled>
                        </select>
                    </td>
                    <td>
                        <input type="hidden" name="loading_program_items[${index}][brand_id]" class="brand-hidden">
                        <select class="form-control form-control-sm select2 brand-select" multiple disabled>
                            @foreach($Brands as $brand)
                                <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td>
                        <select name="loading_program_items[${index}][arrival_location_id]" class="form-control form-control-sm select2 arrival-location-select" required>
                            <option value="">Select Location</option>
                        </select>
                    </td>
                    <td>
                        <select name="loading_program_items[${index}][sub_arrival_location_id]" class="form-control form-control-sm select2 sub-arrival-location-select" required>
                            <option value="">Select Sub Location</option>
                        </select>
                    </td>
                    <td><input type="text" name="loading_program_items[${index}][driver_name]" class="form-control_sm form-control"></td>
                    <td><input type="text" name="loading_program_items[${index}][contact_details]" class="form-control form-control-sm"></td>
                    <td><input type="number" name="loading_program_items[${index}][qty]" class="form-control form-control-sm" step="0.01"></td>
                    <td class="text-center"><button type="button" class="btn btn-sm btn-danger remove-item-btn"><i class="ft-trash-2"></i></button></td>
                </tr>
            `;

            $('#itemsList').append(itemHtml);
            $('#noItemsRow').hide();
            const $newRow = $('#itemsList tr.item-row').last();
            $newRow.find('.select2').select2({ width: '100%' });

            $newRow.find('.row-so-select').change(function() {
                if (window.isUpdatingUI) return;
                const $row = $(this).closest('tr');
                const rowSOIds = $(this).val() || [];
                const $doSelect = $row.find('.delivery-order-select');
                
                if (rowSOIds.length > 0) {
                    const company_location_id = $('#main_company_location_id').val();
                    $.ajax({
                        url: '{{ route('sales.getDeliveryOrdersBySaleOrderLoading') }}',
                        type: 'GET',
                        data: { 
                            sale_order_id: rowSOIds,
                            company_location_id: company_location_id
                        },
                        success: function(response) {
                            if (response.success) {
                                window.isUpdatingUI = true;
                                const currentDOVals = $doSelect.val() || [];
                                $doSelect.empty();
                                response.delivery_orders.forEach(do_item => {
                                    $doSelect.append(new Option(do_item.reference_no, do_item.id, false, currentDOVals.includes(do_item.id.toString())));
                                });
                                $doSelect.trigger('change.select2');
                                $row.data('delivery_orders', response.delivery_orders);
                                
                                // Aggregate Packing and Brands from DOs
                                const selectedDoIds = $doSelect.val() || [];
                                const filteredDOs = response.delivery_orders.filter(d => selectedDoIds.includes(d.id.toString()));
                                updateRowMetadata($row, filteredDOs);

                                window.isUpdatingUI = false;
                                updateItemLocations($row);
                                updateRowDORequiredStatus($row);
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error("AJAX Error: ", error);
                            window.isUpdatingUI = false;
                        }
                    });
                } else {
                    window.isUpdatingUI = true;
                    $doSelect.empty().trigger('change.select2');
                    $row.data('delivery_orders', []);
                    $row.find('.packing-select').empty().trigger('change.select2');
                    $row.find('.brand-select').val([]).trigger('change.select2');
                    $row.find('.packing-hidden').val('');
                    $row.find('.brand-hidden').val('');
                    window.isUpdatingUI = false;
                    updateItemLocations($row);
                    updateRowDORequiredStatus($row);
                }
            });

            $newRow.find('.delivery-order-select').change(function() {
                if (window.isUpdatingUI) return;
                const $row = $(this).closest('tr');
                const selectedDoIds = $(this).val() || [];
                const allDOs = $row.data('delivery_orders') || [];
                const filteredDOs = allDOs.filter(d => selectedDoIds.includes(d.id.toString()));
                
                updateRowMetadata($row, filteredDOs);
                updateItemLocations($row);
            });

            updateItemLocations($newRow);
        }

        function updateRowMetadata($row, deliveryOrders) {
            let packings = [];
            let brandIds = [];

            if (deliveryOrders && deliveryOrders.length > 0) {
                deliveryOrders.forEach(do_item => {
                    if (do_item.delivery_order_data) {
                        do_item.delivery_order_data.forEach(item => {
                            if (item.bag_size) packings.push(item.bag_size);
                            if (item.brand_id) brandIds.push(item.brand_id.toString());
                        });
                    }
                });
            }

            packings = [...new Set(packings)];
            brandIds = [...new Set(brandIds)];

            const $packingSelect = $row.find('.packing-select');
            const $brandSelect = $row.find('.brand-select');
            const $packingHidden = $row.find('.packing-hidden');
            const $brandHidden = $row.find('.brand-hidden');

            // Packing
            $packingSelect.empty();
            packings.forEach(p => {
                $packingSelect.append(new Option(p, p, true, true));
            });
            $packingSelect.trigger('change.select2');
            $packingHidden.val(packings.join(', '));

            // Brands
            $brandSelect.val(brandIds).trigger('change.select2');
            $brandHidden.val(brandIds.length > 0 ? brandIds[0] : '');
        }

        function populateLocationsFromSaleOrder(saleOrderData) {
            window.isUpdatingUI = true;
            // Removed automatic emptying of company_locations to allow manual selection
            $('#arrival_locations, #sub_arrival_locations').empty();

            var arrivalLocations = @json(\App\Models\Master\ArrivalLocation::all());
            var subArrivalLocations = @json(\App\Models\Master\ArrivalSubLocation::all());

            if (saleOrderData.company_location_id) {
                $('#company_locations').val(saleOrderData.company_location_id).trigger('change.select2');
            }

            if (saleOrderData.arrival_location_id) {
                var arrivalIds = Array.isArray(saleOrderData.arrival_location_id) ? saleOrderData.arrival_location_id : [saleOrderData.arrival_location_id];
                $.each(arrivalLocations, function(i, loc) {
                    if (arrivalIds.includes(loc.id.toString())) {
                        $('#arrival_locations').append(new Option(loc.name, loc.id, false, false));
                    }
                });
                $('#arrival_locations').val(arrivalIds).trigger('change.select2');
            }

            if (saleOrderData.sub_arrival_location_id) {
                var subIds = Array.isArray(saleOrderData.sub_arrival_location_id) ? saleOrderData.sub_arrival_location_id : [saleOrderData.sub_arrival_location_id];
                $.each(subArrivalLocations, function(i, loc) {
                    if (subIds.includes(loc.id.toString())) {
                        $('#sub_arrival_locations').append(new Option(loc.name, loc.id, false, false));
                    }
                });
                $('#sub_arrival_locations').val(subIds).trigger('change.select2');
            }

            $('.select2').select2();
            window.isUpdatingUI = false;
            updateItemLocations();
        }

        function populateLocationFields(deliveryOrders) {
            window.isUpdatingUI = true;
            // Keeping company selection manual or pre-filled from first DO
            $('#arrival_locations, #sub_arrival_locations').empty();

            var arrivalLocations = @json(\App\Models\Master\ArrivalLocation::all());
            var subArrivalLocations = @json(\App\Models\Master\ArrivalSubLocation::all());

            var selectedCompanyIds = [];
            var selectedArrivalIds = [];
            var selectedSubArrivalIds = [];

            deliveryOrders.forEach(function(do_item) {
                if (do_item.location_id && !selectedCompanyIds.includes(do_item.location_id.toString())) {
                    selectedCompanyIds.push(do_item.location_id.toString());
                }
                if (do_item.arrival_location_id) {
                    do_item.arrival_location_id.split(',').forEach(id => {
                        if (id.trim() && !selectedArrivalIds.includes(id.trim())) selectedArrivalIds.push(id.trim());
                    });
                }
                if (do_item.sub_arrival_location_id) {
                    do_item.sub_arrival_location_id.split(',').forEach(id => {
                        if (id.trim() && !selectedSubArrivalIds.includes(id.trim())) selectedSubArrivalIds.push(id.trim());
                    });
                }
            });
            if (window.updateTabsVisibility) window.updateTabsVisibility();

            // Populate all options but select the ones from DOs
            arrivalLocations.forEach(loc => $('#arrival_locations').append(new Option(loc.name, loc.id, false, false)));
            subArrivalLocations.forEach(loc => $('#sub_arrival_locations').append(new Option(loc.name, loc.id, false, false)));

            $('.select2').select2();

            if (selectedCompanyIds.length > 0) {
                $('#company_locations').val(selectedCompanyIds).trigger('change.select2');
            }
            if (selectedArrivalIds.length > 0) $('#arrival_locations').val(selectedArrivalIds).trigger('change.select2');
            if (selectedSubArrivalIds.length > 0) $('#sub_arrival_locations').val(selectedSubArrivalIds).trigger('change.select2');
            
            window.isUpdatingUI = false;
            updateItemLocations();
        }

        function updateItemLocations($singleRow = null) {
            window.isUpdatingUI = true;
            const $rows = $singleRow || $('.item-row');
            const globalSelectedArrivalLocations = $('#arrival_locations').val() || [];
            const globalArrivalOptions = $('#arrival_locations option').map(function() {
                return { id: $(this).val(), text: $(this).text() };
            }).get().filter(opt => opt.id && globalSelectedArrivalLocations.includes(opt.id));

            $rows.each(function() {
                const $row = $(this);
                const $select = $row.find('.arrival-location-select');
                const rowDOIds = $row.find('.delivery-order-select').val() || [];
                const rowDOs = $row.data('delivery_orders');
                
                // Guard: If row has selected DOs but data hasn't arrived yet, skip filtering to preserve existing values
                if (rowDOIds.length > 0 && typeof rowDOs === 'undefined') return;

                let currentValue = $select.val() || $select.attr('data-arrival');
                let allowedArrivalIds = [];
                const finalRowDOs = rowDOs || [];
                if (rowDOIds.length > 0) {
                    finalRowDOs.filter(do_item => rowDOIds.includes(do_item.id.toString())).forEach(do_item => {
                        if (do_item.arrival_location_id) {
                            (do_item.arrival_location_id.toString()).split(',').forEach(id => {
                                if (id.trim() && !allowedArrivalIds.includes(id.trim())) allowedArrivalIds.push(id.trim());
                            });
                        }
                    });
                } else {
                    allowedArrivalIds = globalSelectedArrivalLocations;
                }

                $select.empty().append('<option value="">Select Location</option>');
                globalArrivalOptions.forEach(opt => {
                    if (allowedArrivalIds.includes(opt.id)) {
                        const isSelected = currentValue && currentValue.toString() === opt.id.toString();
                        $select.append(new Option(opt.text, opt.id, false, isSelected));
                    }
                });
                
                if ($select.val()) $select.trigger('change.select2');
                updateGalaOptions($select);
            });
            window.isUpdatingUI = false;
        }

        function updateGalaOptions($factorySelect) {
            const selectedFactoryId = $factorySelect.val();
            const $row = $factorySelect.closest('tr');
            const $galaSelect = $row.find('.sub-arrival-location-select');
            const rowDOIds = $row.find('.delivery-order-select').val() || [];
            const rowDOs = $row.data('delivery_orders');
            const globalSelectedSubArrivalLocations = $('#sub_arrival_locations').val() || []; // Define this variable

            // Guard: If row has selected DOs but data hasn't arrived yet, skip filtering to preserve existing values
            if (rowDOIds.length > 0 && typeof rowDOs === 'undefined') return;

            let currentGalaValue = $galaSelect.val() || $galaSelect.attr('data-subarrival');
            let allowedSubArrivalIds = [];
            const finalRowDOs = rowDOs || [];
            if (rowDOIds.length > 0) {
                finalRowDOs.filter(do_item => rowDOIds.includes(do_item.id.toString())).forEach(do_item => {
                    if (do_item.sub_arrival_location_id) {
                        (do_item.sub_arrival_location_id.toString()).split(',').forEach(id => {
                            if (id.trim() && !allowedSubArrivalIds.includes(id.trim())) allowedSubArrivalIds.push(id.trim());
                        });
                    }
                });
            } else {
                allowedSubArrivalIds = globalSelectedSubArrivalLocations;
            }

            $galaSelect.empty().append('<option value="">Select Sub Location</option>');
            if (selectedFactoryId) {
                allSubArrivalLocations.forEach(subLocation => {
                    if (subLocation.arrival_location_id == selectedFactoryId && 
                        globalSelectedSubArrivalLocations.includes(subLocation.id.toString()) &&
                        allowedSubArrivalIds.includes(subLocation.id.toString())) {
                        const isSelected = currentGalaValue && currentGalaValue.toString() === subLocation.id.toString();
                        $galaSelect.append(new Option(subLocation.name, subLocation.id, false, isSelected));
                    }
                });
            }
            $galaSelect.select2({ width: '100%' });
        }

        function updateGalaOptionsForAllRows() {
            // This is now handled by updateItemLocations or individually
            $('.arrival-location-select').each(function() {
                updateGalaOptions($(this));
            });
        }

        function updateDeliveryOrderOptionsForAllRows() {
            $('.delivery-order-select').each(function() {
                const $select = $(this);
                const currentValue = $select.val();
                $select.empty().append('<option value="">Select Delivery Order</option>');
                const selectedDOIds = $('#delivery_order_id').val() || [];
                $('#delivery_order_id option').each(function() {
                    if ($(this).val() && selectedDOIds.includes($(this).val())) {
                        $select.append(new Option($(this).text(), $(this).val(), false, currentValue == $(this).val()));
                    }
                });
                $select.select2({ width: '100%' });
            });
        }
    });

    function getDoQty(el) {
        $.ajax({
            url: '{{ route('sales.getDoQty') }}',
            type: 'GET',
            data: { do_id: $(el).val() },
            dataType: 'json',
            success: function(response) { $(el).closest("tr").find(".do_qty").val(response); },
            error: function() { Swal.fire("Error", "Something went wrong.", "error"); }
        });
    }

    function updateRowDORequiredStatus($row) {
        const $soSelect = $row.find('.row-so-select');
        const $doSelect = $row.find('.delivery-order-select');
        const $mark = $row.find('.row-do-required-mark');
        
        let allType11 = true;
        const selectedOptions = $soSelect.find('option:selected');
        
        if (selectedOptions.length === 0) {
            allType11 = false; 
        } else {
            selectedOptions.each(function() {
                if ($(this).data('type') != 11) {
                    allType11 = false;
                    return false;
                }
            });
        }
        
        // Required attribute removed as per user request
        $doSelect.removeAttr('required');
        $mark.hide();
    }
</script>
