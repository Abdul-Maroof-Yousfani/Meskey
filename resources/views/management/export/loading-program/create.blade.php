<form action="{{ route('export-loading-program.store') }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    <input type="hidden" id="listRefresh" value="{{ route('get.export-loading-program') }}" />
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
                <label>Export Order:</label>
                <select class="form-control select2" name="export_order_id[]" id="export_order_id" multiple disabled>
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
            </div>
        </div>
        <input type="hidden" id="is_delivery_order_optional" value="0">
    </div>
    
    <div class="row" id="exportOrderDataContainer">
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

    <div class="row" id="lineItemsContainer" style="display: none;">
        <style>
            #itemsTable {
                table-layout: fixed !important;
                min-width: 2700px !important;
                width: 2700px !important;
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
                            <th style="width: 300px">Export Order *</th>
                            <th style="width: 300px">Delivery Order</th>
                            <th style="width: 200px">Truck Number *</th>
                            <th style="width: 200px">Container Number</th>
                            <th style="width: 180px">Packing</th>
                            <th style="width: 250px">Brand</th>
                            <th style="width: 280px">Factory/Arrival Location *</th>
                            <th style="width: 280px">Gala/Sub Arrival Location *</th>
                            <th style="width: 220px">Driver Name</th>
                            <th style="width: 220px">Contact Details</th>
                            <th style="width: 250px">Transporter</th>
                            <th style="width: 120px">Sug. Qty</th>
                            <th style="width: 100px">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="itemsList">
                        <!-- Items will be added here dynamically -->
                        <tr id="noItemsRow">
                            <td colspan="13" class="text-center text-muted py-3">
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
    // Global UI state guards
    window.isUpdatingUI = false;
    window.isSelectingEO = false;
    var allSubArrivalLocations = @json(\App\Models\Master\ArrivalSubLocation::all());

    $('#main_company_location_id').change(function() {
        if (window.isUpdatingUI) return;
        const locationId = $(this).val();
        const $eoSelect = $('#export_order_id');
        const $doSelect = $('#delivery_order_id');
        
        window.isUpdatingUI = true;
        $eoSelect.empty().prop('disabled', true).trigger('change.select2');
        $doSelect.empty().prop('disabled', true).trigger('change.select2');
        $('#exportOrderDataContainer').html('').hide();
        $('#lineItemsContainer').hide();
        $('#locationContainer').hide();
        window.isUpdatingUI = false;

        if (locationId) {
            $.ajax({
                url: '{{ route('fetch.export.orders.by.location') }}',
                type: 'GET',
                data: { location_id: locationId },
                success: function(response) {
                    if (response.success) {
                        window.isUpdatingUI = true;
                        $eoSelect.append('<option value="">Select Export Order</option>');
                        response.export_orders.forEach(eo => {
                            $eoSelect.append(`<option value="${eo.id}">${eo.reference_no}</option>`);
                        });
                        $eoSelect.prop('disabled', false).trigger('change.select2');

                        // Reset line items as location has changed
                        $('#itemsList').empty().append('<tr id="noItemsRow"><td colspan="13" class="text-center text-muted py-3">No items added yet. Click "Add Item" to add loading program items.</td></tr>');

                        window.isUpdatingUI = false;
                    }
                }
            });
        }
    });

    $(document).ready(function() {
        // Initial setup
        $('.select2').select2({ width: '100%' });

        $('#export_order_id').change(function() {
            if (window.isUpdatingUI) return;
            
            var export_order_ids = $(this).val();
            const $doSelect = $('#delivery_order_id');
            const company_location_id = $('#main_company_location_id').val();

            if (export_order_ids && export_order_ids.length > 0) {
                window.isSelectingEO = true;
                $doSelect.prop('disabled', false);
                get_export_order(export_order_ids, company_location_id);
            } else {
                window.isUpdatingUI = true;
                $('#exportOrderDataContainer').html('').hide();
                $('#delivery_order_id').empty().prop('disabled', true).trigger('change.select2');
                $('#lineItemsContainer').hide();
                $('#locationContainer').hide();
                updateAllRowExportOrderOptions();
                window.isUpdatingUI = false;
            }
        });

        $('#delivery_order_id').change(function() {
            if (window.isUpdatingUI) return;
            var delivery_order_ids = $(this).val() || [];
            
            if (delivery_order_ids.length > 0) {
                var exportOrderId = $('#export_order_id').val();
                $.ajax({
                    url: '{{ route('get.delivery-orders.by.export-order.loading') }}',
                    type: 'GET',
                    data: { 
                        export_order_id: exportOrderId,
                        company_location_id: $('#main_company_location_id').val()
                    },
                    success: function(response) {
                        if (response.success && response.delivery_orders) {
                            window.allFetchedDOs = response.delivery_orders;
                            syncDOSelectionState(delivery_order_ids, response.delivery_orders);
                        }
                    }
                });
                $('#locationContainer').show();
                $('#lineItemsContainer').show();
            } else {
                hideDODetailsAndLocations();
            }
        });

        function syncDOSelectionState(selectedIds, allDOs) {
            const selectedDOs = (allDOs || []).filter(d => selectedIds.includes(d.id.toString()));
            
            if (selectedDOs.length > 0) {
                window.isUpdatingUI = true;
                
                // Show/Hide sections
                $('#exportOrderDataContainer').show();
                var $wrapper = $('#delivery_order_details_wrapper');
                if ($wrapper.length) {
                    $wrapper.show();
                    $('.do-tab-item, .do-pane').hide().removeClass('show active');
                    selectedIds.forEach(function(id, idx) {
                        var $tab = $('.do-tab-item[data-do-id="' + id + '"]');
                        var $pane = $('.do-pane[data-do-id="' + id + '"]');
                        $tab.show();
                        $pane.show();
                        if (idx === 0) {
                            $tab.find('a').addClass('active');
                            $pane.addClass('show active');
                        } else {
                            $tab.find('a').removeClass('active');
                        }
                    });
                }

                populateLocationFields(selectedDOs);
                updateDeliveryOrderOptionsForAllRows();
                
                // Auto-fill row metadata if empty
                $('.item-row').each(function() {
                    var $row = $(this);
                    if (!$row.find('.delivery-order-select').val()) {
                        updateRowMetadata($row, [selectedDOs[0]]);
                    }
                });

                window.isUpdatingUI = false;
                updateItemLocations();
            } else {
                hideDODetailsAndLocations();
            }
        }

        function hideDODetailsAndLocations() {
            window.isUpdatingUI = true;
            $('.delivery-order-select').each(function() {
                $(this).empty().append('<option value="">Select Delivery Order</option>').select2();
            });
            $('#delivery_order_details_wrapper').hide();
            $('#locationContainer').hide();
            $('#lineItemsContainer').hide();
            window.isUpdatingUI = false;
        }

        // Add item button handler
        let itemIndex = 0;
        $('#addItemBtn').click(function() {
            addItemRow(itemIndex);
            itemIndex++;
        });

        // Functions
        function get_export_order(export_order_ids, company_location_id) {
            $.ajax({
                url: '{{ route('get.export-order.related.data') }}',
                type: 'GET',
                data: { 
                    export_order_id: export_order_ids,
                    company_location_id: company_location_id
                },
                dataType: 'json',
                beforeSend: function() {
                    Swal.fire({
                        title: "Processing...",
                        text: "Please wait while fetching export order details.",
                        allowOutsideClick: false,
                        didOpen: () => { Swal.showLoading(); }
                    });
                },
                success: function(response) {
                    Swal.close();
                    if (response.success) {
                        window.isUpdatingUI = true;
                        $('#exportOrderDataContainer').html(response.html).show();
                        // Initially only EO Details are shown in the blade (DO details are display: none)
                        $('.select2').select2({ width: '100%' });

                        if (response.export_order_data) {
                            window.exportOrderData = response.export_order_data;
                        }

                        if (response.transporters_map) {
                            window.transportersMap = response.transporters_map;
                        }

                        if (response.delivery_orders) {
                            window.allFetchedDOs = response.delivery_orders;
                        }

                        if (response.export_orders) {
                            window.allFetchedEOs = response.export_orders;
                        }

                        var currentDOVals = $('#delivery_order_id').val() || [];
                        $('#delivery_order_id').empty();
                        (response.delivery_orders || []).forEach(doItem => {
                            $('#delivery_order_id').append('<option value="' + doItem.id + '">' + doItem.reference_no + '</option>');
                        });
                        $('#delivery_order_id').val(currentDOVals).trigger('change.select2');
                        
                        updateAllRowExportOrderOptions();
                        window.isUpdatingUI = false;

                        // Call sync directly to handle visibility and locations
                        if (currentDOVals.length > 0) {
                            syncDOSelectionState(currentDOVals, response.delivery_orders);
                        }
                    }
                },
                complete: function() {
                    window.isSelectingEO = false;
                },
                error: function() {
                    Swal.close();
                    Swal.fire("Error", "Something went wrong.", "error");
                }
            });
        }

        function updateAllRowExportOrderOptions() {
            const mainEOOptions = [];
            $('#export_order_id option:selected').each(function() {
                mainEOOptions.push({id: $(this).val(), text: $(this).text()});
            });

            $('.row-eo-select').each(function() {
                const $select = $(this);
                const currentValues = $select.val() || [];
                $select.empty();
                mainEOOptions.forEach(opt => {
                    let newOption = new Option(opt.text, opt.id, false, currentValues.includes(opt.id.toString()));
                    $select.append(newOption);
                });
                $select.trigger('change.select2');
            });
        }

        function updateDeliveryOrderOptionsForAllRows() {
            const mainDOOptions = [];
            $('#delivery_order_id option:selected').each(function() {
                mainDOOptions.push({id: $(this).val(), text: $(this).text()});
            });

            $('.delivery-order-select').each(function() {
                const $select = $(this);
                const currentValues = $select.val() || [];
                $select.empty();
                $select.append('<option value="">Select Delivery Order</option>');
                mainDOOptions.forEach(opt => {
                    let newOption = new Option(opt.text, opt.id, false, currentValues.includes(opt.id.toString()));
                    $select.append(newOption);
                });
                $select.trigger('change.select2');
            });
        }

        function addItemRow(index) {
            const mainEOOptions = [];
            $('#export_order_id option:selected').each(function() {
                mainEOOptions.push({id: $(this).val(), text: $(this).text()});
            });

            const mainDOOptions = [];
            $('#delivery_order_id option:selected').each(function() {
                mainDOOptions.push({id: $(this).val(), text: $(this).text()});
            });

            const itemHtml = `
                <tr class="item-row" data-index="${index}">
                    <td>
                        <select name="loading_program_items[${index}][export_order_id][]" class="form-control select2 row-eo-select" multiple>
                            ${mainEOOptions.map(opt => `<option value="${opt.id}">${opt.text}</option>`).join('')}
                        </select>
                    </td>
                    <td>
                        <div class="row-do-container">
                            <select name="loading_program_items[${index}][delivery_order_id][]" class="form-control form-control-sm select2 delivery-order-select" multiple>
                                <option value="">Select Delivery Order</option>
                                ${mainDOOptions.map(opt => `<option value="${opt.id}">${opt.text}</option>`).join('')}
                            </select>
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
                    <td>
                        <select name="loading_program_items[${index}][transporter_id]" class="form-control form-control-sm select2 transporter-select">
                            <option value="">Select Transporter</option>
                            @foreach($Transporters as $transporter)
                                <option value="{{ $transporter->id }}">{{ $transporter->name }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td><input type="number" name="loading_program_items[${index}][qty]" class="form-control form-control-sm" step="0.01"></td>
                    <td class="text-center"><button type="button" class="btn btn-sm btn-danger remove-item-btn"><i class="ft-trash-2"></i></button></td>
                </tr>
            `;

            $('#itemsList').append(itemHtml);
            $('#noItemsRow').hide();
            const $newRow = $('#itemsList tr.item-row').last();
            $newRow.find('.select2').select2({ width: '100%' });

            $newRow.find('.row-eo-select').change(function() {
                if (window.isUpdatingUI) return;
                const $row = $(this).closest('tr');
                const rowEOIds = $(this).val() || [];
                const $doSelect = $row.find('.delivery-order-select');
                const mainSelectedDOs = $('#delivery_order_id').val() || [];
                
                if (rowEOIds.length > 0) {
                    const company_location_id = $('#main_company_location_id').val();
                    $.ajax({
                        url: '{{ route('get.delivery-orders.by.export-order.loading') }}',
                        type: 'GET',
                        data: { 
                            export_order_id: rowEOIds,
                            company_location_id: company_location_id
                        },
                        success: function(response) {
                            if (response.success) {
                                window.isUpdatingUI = true;
                                const currentDOVals = $doSelect.val() || [];
                                $doSelect.empty();
                                $doSelect.append('<option value="">Select Delivery Order</option>');
                                (response.delivery_orders || []).forEach(do_item => {
                                    if (mainSelectedDOs.includes(do_item.id.toString())) {
                                        $doSelect.append(new Option(do_item.reference_no, do_item.id, false, currentDOVals.includes(do_item.id.toString())));
                                    }
                                });
                                $doSelect.trigger('change.select2');
                                $row.data('delivery_orders', response.delivery_orders);
                                
                                const selectedDoIds = $doSelect.val() || [];
                                const filteredDOs = (response.delivery_orders || []).filter(d => selectedDoIds.includes(d.id.toString()));
                                updateRowMetadata($row, filteredDOs);

                                window.isUpdatingUI = false;
                                updateItemLocations($row);
                            }
                        }
                    });
                } else {
                    window.isUpdatingUI = true;
                    $doSelect.empty().append('<option value="">Select Delivery Order</option>').trigger('change.select2');
                    $row.find('.packing-select').empty().trigger('change.select2');
                    $row.find('.brand-select').val([]).trigger('change.select2');
                    window.isUpdatingUI = false;
                    updateItemLocations($row);
                }
            });

            $newRow.find('.delivery-order-select').change(function() {
                if (window.isUpdatingUI) return;
                const $row = $(this).closest('tr');
                const selectedDoIds = $(this).val() || [];
                const allDOs = $row.data('delivery_orders') || window.allFetchedDOs || [];
                const filteredDOs = allDOs.filter(d => selectedDoIds.includes(d.id.toString()));
                
                updateRowMetadata($row, filteredDOs);
                updateItemLocations($row);
            });

            $newRow.find('.arrival-location-select').change(function() {
                if (window.isUpdatingUI) return;
                updateGalaOptions($(this));
            });

            updateItemLocations($newRow);
        }

        function updateRowMetadata($row, deliveryOrders = null) {
            let packings = [];
            let brandIds = [];
            
            // Try DOs first
            if (deliveryOrders && deliveryOrders.length > 0) {
                deliveryOrders.forEach(do_item => {
                    var items = do_item.export_packing_items || do_item.exportPackingItems;
                    if (items) {
                        items.forEach(item => {
                            if (item.bag_size) packings.push(item.bag_size);
                            if (item.brand_id) brandIds.push(item.brand_id.toString());
                        });
                    }
                });
            }
            
            // Fallback to row-level EOs if DOs have no metadata
            if (packings.length === 0 || brandIds.length === 0) {
                const rowEOIds = $row.find('.row-eo-select').val() || [];
                const allEOs = window.allFetchedEOs || [];
                allEOs.filter(eo => rowEOIds.includes(eo.id.toString())).forEach(eo => {
                    var items = eo.packing_items || eo.packingItems;
                    if (items) {
                        items.forEach(item => {
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

            $packingSelect.empty();
            packings.forEach(p => {
                $packingSelect.append(new Option(p, p, true, true));
            });
            $packingSelect.trigger('change.select2');
            $row.find('.packing-hidden').val(packings.join(', '));

            $brandSelect.val(brandIds).trigger('change.select2');
            $row.find('.brand-hidden').val(brandIds.length > 0 ? brandIds[0] : '');
        }

        function populateLocationFields(deliveryOrders) {
            window.isUpdatingUI = true;
            
            var arrivalLocations = @json(\App\Models\Master\ArrivalLocation::all());
            var subArrivalLocations = @json(\App\Models\Master\ArrivalSubLocation::all());

            var selectedArrivalIds = [];
            var selectedSubArrivalIds = [];
            var selectedCompanyIds = [];

            deliveryOrders.forEach(function(doItem) {
                if (doItem.location_id) {
                    var ids = doItem.location_id.toString().split(',');
                    ids.forEach(id => { if (id.trim() && !selectedCompanyIds.includes(id.trim())) selectedCompanyIds.push(id.trim()); });
                }
                if (doItem.arrival_location_id) {
                    var ids = doItem.arrival_location_id.toString().split(',');
                    ids.forEach(id => { if (id.trim() && !selectedArrivalIds.includes(id.trim())) selectedArrivalIds.push(id.trim()); });
                }
                if (doItem.sub_arrival_location_id) {
                    var ids = doItem.sub_arrival_location_id.toString().split(',');
                    ids.forEach(id => { if (id.trim() && !selectedSubArrivalIds.includes(id.trim())) selectedSubArrivalIds.push(id.trim()); });
                }
            });

            const uniqueArrivalIds = [...new Set(selectedArrivalIds)];
            const uniqueSubArrivalIds = [...new Set(selectedSubArrivalIds)];
            const uniqueCompanyIds = [...new Set(selectedCompanyIds)];

            // Temporarily enable to ensure Select2 updates its visual display correctly
            $('#company_locations, #arrival_locations, #sub_arrival_locations').prop('disabled', false);

            $('#company_locations').empty();
            @json(get_locations()).forEach(loc => {
                if (uniqueCompanyIds.includes(loc.id.toString())) {
                    $('#company_locations').append(new Option(loc.name, loc.id, true, true));
                }
            });

            $('#arrival_locations').empty();
            arrivalLocations.forEach(loc => {
                if (uniqueArrivalIds.includes(loc.id.toString())) {
                    $('#arrival_locations').append(new Option(loc.name, loc.id, true, true));
                }
            });

            $('#sub_arrival_locations').empty();
            subArrivalLocations.forEach(loc => {
                if (uniqueSubArrivalIds.includes(loc.id.toString())) {
                    $('#sub_arrival_locations').append(new Option(loc.name, loc.id, true, true));
                }
            });

            $('#company_locations').val(uniqueCompanyIds).trigger('change.select2');
            $('#arrival_locations').val(uniqueArrivalIds).trigger('change.select2');
            $('#sub_arrival_locations').val(uniqueSubArrivalIds).trigger('change.select2');
            
            // Re-disable after sync
            $('#company_locations, #arrival_locations, #sub_arrival_locations').prop('disabled', true);
            
            window.isUpdatingUI = false;
        }

        function updateItemLocations($singleRow) {
            const $rows = $singleRow || $('.item-row');
            const globalSelectedArrivalLocations = $('#arrival_locations').val() || [];
            const globalArrivalOptions = $('#arrival_locations option').map(function() { return { id: $(this).val(), text: $(this).text() }; }).get();

            $rows.each(function() {
                const $row = $(this);
                const $select = $row.find('.arrival-location-select');
                const rowDOIds = $row.find('.delivery-order-select').val() || [];
                const rowDOs = $row.data('delivery_orders') || [];
                let allowedArrivalIds = [];

                if (rowDOIds.length > 0) {
                    (rowDOs || []).filter(doItem => rowDOIds.includes(doItem.id.toString())).forEach(doItem => {
                        if (doItem.arrival_location_id) {
                            doItem.arrival_location_id.split(',').forEach(id => {
                                if (id.trim() && !allowedArrivalIds.includes(id.trim())) allowedArrivalIds.push(id.trim());
                            });
                        }
                    });
                } else {
                    allowedArrivalIds = globalSelectedArrivalLocations;
                }

                const currentVal = $select.val();
                $select.empty().append('<option value="">Select Location</option>');
                globalArrivalOptions.forEach(opt => {
                    if (allowedArrivalIds.includes(opt.id.toString())) {
                        $select.append(new Option(opt.text, opt.id, false, opt.id == currentVal));
                    }
                });
                $select.trigger('change.select2');
                updateGalaOptions($select);
            });
        }

        function updateGalaOptions($factorySelect) {
            const selectedFactoryId = $factorySelect.val();
            const $row = $factorySelect.closest('tr');
            const $galaSelect = $row.find('.sub-arrival-location-select');
            const rowDOIds = $row.find('.delivery-order-select').val() || [];
            const rowDOs = $row.data('delivery_orders') || [];
            const globalSelectedSubArrivalLocations = $('#sub_arrival_locations').val() || [];

            let allowedSubArrivalIds = [];
            if (rowDOIds.length > 0) {
                (rowDOs || []).filter(doItem => rowDOIds.includes(doItem.id.toString())).forEach(doItem => {
                    if (doItem.sub_arrival_location_id) {
                        doItem.sub_arrival_location_id.split(',').forEach(id => {
                            if (id.trim() && !allowedSubArrivalIds.includes(id.trim())) allowedSubArrivalIds.push(id.trim());
                        });
                    }
                });
            } else {
                allowedSubArrivalIds = globalSelectedSubArrivalLocations;
            }

            const currentVal = $galaSelect.val();
            $galaSelect.empty().append('<option value="">Select Sub Location</option>');
            allSubArrivalLocations.forEach(function(subLocation) {
                if (subLocation.arrival_location_id == selectedFactoryId && 
                    globalSelectedSubArrivalLocations.includes(subLocation.id.toString()) && 
                    allowedSubArrivalIds.includes(subLocation.id.toString())) {
                    $galaSelect.append(new Option(subLocation.name, subLocation.id, false, subLocation.id == currentVal));
                }
            });
            $galaSelect.trigger('change.select2');
        }

        $(document).on('click', '.remove-item-btn', function() {
            $(this).closest('tr').remove();
            if ($('#itemsList tr.item-row').length === 0) {
                $('#noItemsRow').show();
            }
        });

        $('#ajaxSubmit').submit(function(e) {
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
    });
</script>