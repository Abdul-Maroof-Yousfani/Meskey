<form action="{{ route('export-loading-program.update', $loadingProgram->id) }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    @method('PUT')
    <input type="hidden" id="listRefresh" value="{{ route('get.export-loading-program') }}" />
    
    @php
        $mainCompanyLocationId = $loadingProgram->company_location_id ?: (is_array($loadingProgram->company_locations) ? $loadingProgram->company_locations[0] ?? null : null);
    @endphp

    <div class="row form-mar">
        <div class="col-xs-12 col-sm-4 col-md-4">
            <div class="form-group">
                <label>Company Location:</label>
                <select class="form-control select2" name="main_company_location_id" id="main_company_location_id">
                    <option value="">Select Company Location</option>
                    @foreach (get_locations() as $loc)
                        <option value="{{ $loc->id }}" @selected($mainCompanyLocationId == $loc->id)>{{ $loc->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="col-xs-12 col-sm-4 col-md-4">
            <div class="form-group">
                <label>Export Order:</label>
                <select class="form-control select2" name="export_order_id[]" id="export_order_id" multiple>
                    @foreach ($ExportOrders as $eo)
                        <option value="{{ $eo->id }}" @selected($loadingProgram->exportOrders->contains($eo->id))>
                            {{ $eo->voucher_no ?? $eo->contract_no ?? 'EO-' . $eo->id }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-xs-12 col-sm-4 col-md-4">
            <div class="form-group">
                <label id="delivery_order_label">Delivery Order: <span id="delivery_order_required_mark"
                        class="text-danger">*</span></label>
                <select class="form-control select2" name="delivery_order_id[]" id="delivery_order_id" multiple>
                    @foreach($loadingProgram->deliveryOrders as $do)
                        <option value="{{ $do->id }}" selected>{{ $do->reference_no }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <input type="hidden" id="is_delivery_order_optional" value="0">
    </div>

    <div class="row" id="exportOrderDataContainer" style="display: none;">
        {{-- Populated via AJAX --}}
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
                        <option value="{{ $loc->id }}" @selected($loadingProgram->company_location_id == $loc->id || (is_array($loadingProgram->company_locations) && in_array($loc->id, $loadingProgram->company_locations)))>{{ $loc->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-xs-12 col-sm-4 col-md-4">
            <div class="form-group">
                <label>Arrival Location</label>
                <select class="form-control select2 w-100" name="arrival_locations[]" id="arrival_locations" multiple
                    disabled style="width: 100% !important;">
                    @foreach($arrivalLocations as $loc)
                        <option value="{{ $loc['id'] }}" selected>{{ $loc['text'] }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-xs-12 col-sm-4 col-md-4">
            <div class="form-group">
                <label>Sub Arrival Location</label>
                <select class="form-control select2 w-100" name="sub_arrival_locations[]" id="sub_arrival_locations"
                    multiple disabled style="width: 100% !important;">
                    @foreach($subArrivalLocations as $loc)
                        <option value="{{ $loc['id'] }}" selected>{{ $loc['text'] }}</option>
                    @endforeach
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
                        @foreach($loadingProgram->loadingProgramItems as $index => $item)
                            <tr class="item-row" data-index="{{ $index }}">
                                <td>
                                    <div @if($item->firstWeighbridge) data-toggle="tooltip" title="Locked: Ticket already in Weighbridge" @endif>
                                        <select name="loading_program_items[{{ $index }}][export_order_id][]"
                                            class="form-control form-control-sm select2 row-eo-select"
                                            multiple @disabled($item->firstWeighbridge)>
                                            @foreach ($loadingProgram->exportOrders as $eo)
                                                <option value="{{ $eo->id }}" @selected($item->exportOrders->contains($eo->id))>
                                                    {{ $eo->voucher_no ?? $eo->contract_no ?? 'EO-' . $eo->id }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </td>
                                <td>
                                    <select name="loading_program_items[{{ $index }}][delivery_order_id][]"
                                        class="form-control form-control-sm select2 delivery-order-select"
                                        multiple @disabled($item->firstWeighbridge)>
                                        @foreach ($loadingProgram->deliveryOrders as $do)
                                            <option value="{{ $do->id }}" @selected($item->deliveryOrders->contains($do->id))>
                                                {{ $do->reference_no }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <input type="text" name="loading_program_items[{{ $index }}][truck_number]"
                                        class="form-control form-control-sm" required
                                        value="{{ $item->truck_number }}" @disabled($item->firstWeighbridge)>
                                    <input type="hidden" name="loading_program_items[{{ $index }}][transaction_number]"
                                        value="{{ $item->transaction_number }}">
                                </td>
                                <td>
                                    <input type="text" name="loading_program_items[{{ $index }}][container_number]"
                                        class="form-control form-control-sm"
                                        value="{{ $item->container_number }}" @disabled($item->firstWeighbridge)>
                                </td>
                                <td>
                                    <input type="hidden" name="loading_program_items[{{ $index }}][packing]" class="packing-hidden" value="{{ $item->packing }}">
                                    <select class="form-control form-control-sm select2 packing-select" multiple disabled>
                                        @foreach(array_filter(explode(', ', $item->packing)) as $p)
                                            <option value="{{ $p }}" selected>{{ $p }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <input type="hidden" name="loading_program_items[{{ $index }}][brand_id]" class="brand-hidden" value="{{ $item->brand_id }}">
                                    <select class="form-control form-control-sm select2 brand-select" multiple disabled>
                                        @foreach($Brands as $brand)
                                            <option value="{{ $brand->id }}" @selected($item->brand_id == $brand->id)>{{ $brand->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <select name="loading_program_items[{{ $index }}][arrival_location_id]"
                                        class="form-control form-control-sm select2 arrival-location-select" required
                                        @disabled($item->firstWeighbridge)>
                                        <option value="">Select Location</option>
                                        @foreach($locations[1] as $loc)
                                            <option value="{{ $loc['id'] }}" @selected($item->arrival_location_id == $loc['id'])>{{ $loc['text'] }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <select name="loading_program_items[{{ $index }}][sub_arrival_location_id]"
                                        class="form-control form-control-sm select2 sub-arrival-location-select"
                                        required @disabled($item->firstWeighbridge)>
                                        <option value="">Select Sub Location</option>
                                        @foreach($locations[2] as $loc)
                                            <option value="{{ $loc['id'] }}" @selected($item->sub_arrival_location_id == $loc['id'])>{{ $loc['text'] }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td><input type="text" name="loading_program_items[{{ $index }}][driver_name]" class="form-control form-control-sm" value="{{ $item->driver_name }}" @disabled($item->firstWeighbridge)></td>
                                <td><input type="text" name="loading_program_items[{{ $index }}][contact_details]" class="form-control form-control-sm" value="{{ $item->contact_details }}" @disabled($item->firstWeighbridge)></td>
                                <td>
                                    <select name="loading_program_items[{{ $index }}][transporter_id]" class="form-control form-control-sm select2 transporter-select" @disabled($item->firstWeighbridge)>
                                        <option value="">Select Transporter</option>
                                        @foreach($Transporters as $transporter)
                                            <option value="{{ $transporter->id }}" @selected($item->transporter_id == $transporter->id)>{{ $transporter->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td><input type="number" name="loading_program_items[{{ $index }}][qty]" class="form-control form-control-sm" step="0.01" value="{{ $item->qty }}" @disabled($item->firstWeighbridge)></td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-danger remove-item-btn" @disabled($item->firstWeighbridge)>
                                        <i class="ft-trash-2"></i>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Remark:</label>
                <textarea name="remark" placeholder="Remarks" class="form-control">{{ $loadingProgram->remark }}</textarea>
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
    window.isUpdatingUI = false;
    window.isSelectingEO = false;
    var allSubArrivalLocations = @json(\App\Models\Master\ArrivalSubLocation::all());
    
    // Inject existing data into JS context for immediate row initialization
    window.allFetchedDOs = @json($loadingProgram->deliveryOrders->load(['exportPackingItems', 'saleSecondWeighbridge']));
    window.allFetchedEOs = @json($loadingProgram->exportOrders->load(['packingItems']));

    $(document).ready(function() {
        $('.select2').select2({ width: '100%' });

        // Initial state logic
        const initialEOIds = $('#export_order_id').val();
        const initialDOIds = $('#delivery_order_id').val();
        
        if (initialEOIds && initialEOIds.length > 0) {
            get_export_order(initialEOIds, $('#main_company_location_id').val(), true);
        }

        if (initialDOIds && initialDOIds.length > 0) {
            $('#exportOrderDataContainer').show();
            $('#locationContainer').show();
            $('#lineItemsContainer').show();
            
            // Immediately initialize existing rows from injected data
            $('.item-row').each(function() {
                const $row = $(this);
                const selectedDoIds = $row.find('.delivery-order-select').val() || [];
                const filteredDOs = window.allFetchedDOs.filter(d => selectedDoIds.includes(d.id.toString()));
                $row.data('delivery_orders', filteredDOs);
                updateRowMetadata($row, filteredDOs);
            });
            updateItemLocations();

            // Also call sync for visual consistency of DO details
            syncDOSelectionState(initialDOIds, window.allFetchedDOs);
        }

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
                $.ajax({
                    url: '{{ route('get.delivery-orders.by.export-order.loading.edit') }}',
                    type: 'GET',
                    data: { 
                        export_order_id: $('#export_order_id').val(),
                        company_location_id: $('#main_company_location_id').val(),
                        loading_program_id: '{{ $loadingProgram->id }}'
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
                if (!$(this).prop('disabled')) {
                    $(this).empty().append('<option value="">Select Delivery Order</option>').select2();
                }
            });
            $('#delivery_order_details_wrapper').hide();
            $('#locationContainer').hide();
            $('#lineItemsContainer').hide();
            window.isUpdatingUI = false;
        }

        let itemIndex = {{ $loadingProgram->loadingProgramItems->count() }};
        $('#addItemBtn').click(function() {
            addItemRow(itemIndex);
            itemIndex++;
        });

        function get_export_order(export_order_ids, company_location_id, isInitial = false) {
            $.ajax({
                url: '{{ route('get.export-order.related.data') }}',
                type: 'GET',
                data: { 
                    export_order_id: export_order_ids,
                    company_location_id: company_location_id,
                    loading_program_id: '{{ $loadingProgram->id }}'
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        window.isUpdatingUI = true;
                        $('#exportOrderDataContainer').html(response.html).show();
                        $('.select2').select2({ width: '100%' });
                        
                        if (response.delivery_orders) {
                            window.allFetchedDOs = response.delivery_orders;
                        }

                        if (response.export_orders) {
                            window.allFetchedEOs = response.export_orders;
                        }

                        const currentDOs = $('#delivery_order_id').val() || [];
                        $('#delivery_order_id').empty();
                        (response.delivery_orders || []).forEach(doItem => {
                            $('#delivery_order_id').append(new Option(doItem.reference_no, doItem.id, false, currentDOs.includes(doItem.id.toString())));
                        });
                        $('#delivery_order_id').val(currentDOs).trigger('change.select2');
                        
                        updateAllRowExportOrderOptions();
                        window.isUpdatingUI = false;

                        if (currentDOs.length > 0) {
                            syncDOSelectionState(currentDOs, response.delivery_orders);
                        }
                    }
                },
                complete: function() {
                    window.isSelectingEO = false;
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
                if ($select.prop('disabled')) return;
                const currentValues = $select.val() || [];
                $select.empty();
                mainEOOptions.forEach(opt => {
                    $select.append(new Option(opt.text, opt.id, false, currentValues.includes(opt.id.toString())));
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
                if ($select.prop('disabled')) return;
                const currentValues = $select.val() || [];
                $select.empty();
                $select.append('<option value="">Select Delivery Order</option>');
                mainDOOptions.forEach(opt => {
                    $select.append(new Option(opt.text, opt.id, false, currentValues.includes(opt.id.toString())));
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
                        <select class="form-control form-control-sm select2 packing-select" multiple disabled></select>
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
                    <td><input type="text" name="loading_program_items[${index}][driver_name]" class="form-control form-control-sm"></td>
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
                    $.ajax({
                        url: '{{ route('get.delivery-orders.by.export-order.loading.edit') }}',
                        type: 'GET',
                        data: { 
                            export_order_id: rowEOIds,
                            company_location_id: $('#main_company_location_id').val()
                        },
                        success: function(response) {
                            if (response.success) {
                                window.isUpdatingUI = true;
                                const currentDOVals = $doSelect.val() || [];
                                $doSelect.empty();
                                $doSelect.append('<option value="">Select Delivery Order</option>');
                                (response.delivery_orders || []).forEach(d => {
                                    if (mainSelectedDOs.includes(d.id.toString())) {
                                        $doSelect.append(new Option(d.reference_no, d.id, false, currentDOVals.includes(d.id.toString())));
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

            // Try DO metadata first
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

            const $pSelect = $row.find('.packing-select');
            $pSelect.empty();
            packings.forEach(p => $pSelect.append(new Option(p, p, true, true)));
            $pSelect.trigger('change.select2');
            $row.find('.packing-hidden').val(packings.join(', '));

            $row.find('.brand-select').val(brandIds).trigger('change.select2');
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

            // Temporarily enable for visual update
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
            
            // Re-disable
            $('#company_locations, #arrival_locations, #sub_arrival_locations').prop('disabled', true);
            
            window.isUpdatingUI = false;
        }

        function updateItemLocations($singleRow) {
            const $rows = $singleRow || $('.item-row');
            const globalSelectedArrivals = $('#arrival_locations').val() || [];
            const globalArrivalOptions = $('#arrival_locations option').map(function() { return { id: $(this).val(), text: $(this).text() }; }).get();

            $rows.each(function() {
                const $row = $(this);
                if ($row.find('.arrival-location-select').prop('disabled')) return;
                
                const $select = $row.find('.arrival-location-select');
                const rowDOIds = $row.find('.delivery-order-select').val() || [];
                const rowDOs = $row.data('delivery_orders') || [];
                let allowedArrivalIds = [];

                if (rowDOIds.length > 0) {
                    (rowDOs || []).filter(d => rowDOIds.includes(d.id.toString())).forEach(d => {
                        if (d.arrival_location_id) d.arrival_location_id.split(',').forEach(id => { if (id.trim()) allowedArrivalIds.push(id.trim()); });
                    });
                } else {
                    allowedArrivalIds = globalSelectedArrivals;
                }

                const currentVal = $select.val();
                $select.empty().append('<option value="">Select Location</option>');
                globalArrivalOptions.forEach(opt => { if (allowedArrivalIds.includes(opt.id.toString())) $select.append(new Option(opt.text, opt.id, false, opt.id == currentVal)); });
                $select.trigger('change.select2');
                updateGalaOptions($select);
            });
        }

        function updateGalaOptions($factorySelect) {
            const selectedFactoryId = $factorySelect.val();
            const $row = $factorySelect.closest('tr');
            const $galaSelect = $row.find('.sub-arrival-location-select');
            if ($galaSelect.prop('disabled')) return;

            const rowDOIds = $row.find('.delivery-order-select').val() || [];
            const rowDOs = $row.data('delivery_orders') || [];
            const globalSelectedSubArrivals = $('#sub_arrival_locations').val() || [];

            let allowedSubArrivalIds = [];
            if (rowDOIds.length > 0) {
                (rowDOs || []).filter(d => rowDOIds.includes(d.id.toString())).forEach(d => {
                    if (d.sub_arrival_location_id) d.sub_arrival_location_id.split(',').forEach(id => { if (id.trim()) allowedSubArrivalIds.push(id.trim()); });
                });
            } else {
                allowedSubArrivalIds = globalSelectedSubArrivals;
            }

            const currentVal = $galaSelect.val();
            $galaSelect.empty().append('<option value="">Select Sub Location</option>');
            allSubArrivalLocations.forEach(function(sub) {
                if (sub.arrival_location_id == selectedFactoryId && globalSelectedSubArrivals.includes(sub.id.toString()) && allowedSubArrivalIds.includes(sub.id.toString())) {
                    $galaSelect.append(new Option(sub.name, sub.id, false, sub.id == currentVal));
                }
            });
            $galaSelect.trigger('change.select2');
        }

        $(document).on('click', '.remove-item-btn', function() {
            $(this).closest('tr').remove();
            if ($('#itemsList tr.item-row').length === 0) $('#noItemsRow').show();
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