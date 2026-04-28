
<form action="{{ route('sales.loading-program.update', $LoadingProgram->id) }}" method="POST" id="ajaxSubmit"
    autocomplete="off">
    @csrf

    @method('PUT')
    <input type="hidden" id="listRefresh" value="{{ route('sales.get.loading-program') }}" />
    @php
        $mainCompanyLocationId = $LoadingProgram->company_location_id ?: (is_array($LoadingProgram->company_locations) ? $LoadingProgram->company_locations[0] ?? null : null);
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
                <label>Sale Order:</label>
                <select class="form-control select2" name="sale_order_id[]" id="sale_order_id" multiple>
                    @foreach ($SaleOrders as $SaleOrder)
                        <option value="{{ $SaleOrder->id }}" data-type="{{ $SaleOrder->pay_type_id }}"
                            @selected($LoadingProgram->saleOrders->contains($SaleOrder->id))>
                            {{ $SaleOrder->reference_no }}
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
                    {{-- Options will be populated via AJAX --}}
                    @foreach($LoadingProgram->deliveryOrders as $do)
                        <option value="{{ $do->id }}" selected>{{ $do->reference_no }}</option>
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
        {{-- Populated via AJAX on load --}}
    </div>

    <div class="row" id="locationContainer">
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
                        <option value="{{ $loc->id }}" @selected($LoadingProgram->company_location_id == $loc->id || (is_array($LoadingProgram->company_locations) && in_array($loc->id, $LoadingProgram->company_locations)) || (is_string($LoadingProgram->company_locations) && trim($LoadingProgram->company_locations, '"') == $loc->id))>{{ $loc->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-xs-12 col-sm-4 col-md-4">
            <div class="form-group">
                <label>Arrival Location</label>
                <select class="form-control select2 w-100" name="arrival_locations[]" id="arrival_locations" multiple
                    disabled style="width: 100% !important;">
                    @foreach($locations[1] as $factory_location)
                        <option value="{{ $factory_location["id"] }}" selected>{{ $factory_location["text"] }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-xs-12 col-sm-4 col-md-4">
            <div class="form-group">
                <label>Sub Arrival Location</label>
                <select class="form-control select2 w-100" name="sub_arrival_locations[]" id="sub_arrival_locations"
                    multiple disabled style="width: 100% !important;">
                    @foreach($locations[2] as $section_location)
                        <option value="{{ $section_location["id"] }}" selected>{{ $section_location["text"] }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <div class="row" id="lineItemsContainer">
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
                            <th style="width: 300px">Sale Order *</th>
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
                        @forelse($LoadingProgram->loadingProgramItems as $index => $item)
                            <tr class="item-row" data-index="{{ $index }}">
                                <td>
                                    <div @if($item->firstWeighbridge) data-toggle="tooltip" title="Locked: Ticket already in Weighbridge" @endif>
                                        <select name="loading_program_items[{{ $index }}][sale_order_id][]"
                                            class="form-control form-control-sm select2 row-so-select"
                                            multiple @disabled($item->firstWeighbridge)>
                                            @foreach ($LoadingProgram->saleOrders as $so)
                                                <option value="{{ $so->id }}" data-type="{{ $so->pay_type_id }}" @selected($item->saleOrders->contains($so->id))>
                                                    {{ $so->reference_no }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </td>
                                <td>
                                        <div class="row-do-container">
                                            <select name="loading_program_items[{{ $index }}][delivery_order_id][]"
                                                class="form-control form-control-sm select2 delivery-order-select"
                                                multiple @disabled($item->firstWeighbridge)>
                                                @php
                                                    $itemSoIds = $item->saleOrders->pluck('id')->toArray();
                                                    $rowDos = \App\Models\Sales\DeliveryOrder::whereIn('so_id', $itemSoIds)->where('am_approval_status', 'approved')->get();
                                                @endphp
                                                @php
                                                    $mainSelectedDoIds = $LoadingProgram->deliveryOrders->pluck('id')->toArray();
                                                    if ($LoadingProgram->deliveryOrder) $mainSelectedDoIds[] = $LoadingProgram->deliveryOrder->id;
                                                    $mainSelectedDoIds = array_unique($mainSelectedDoIds);
                                                @endphp
                                                @foreach ($rowDos as $do)
                                                    @if (in_array($do->id, $mainSelectedDoIds))
                                                        <option value="{{ $do->id }}" @selected($item->deliveryOrders->contains($do->id))>
                                                            {{ $do->reference_no }}
                                                        </option>
                                                    @endif
                                                @endforeach
                                            </select>
                                            <span class="text-danger row-do-required-mark" style="display: none;">*</span>
                                        </div>
                                </td>
                                <td>
                                    <div
                                        @if($item->firstWeighbridge)
                                            data-toggle="tooltip"
                                            title="You cannot update information because Ticket is already passed to first Weighbridge"
                                            data-placement="left"
                                        @endif
                                    >
                                        <input type="text"
                                            name="loading_program_items[{{ $index }}][truck_number]"
                                            class="form-control form-control-sm" required
                                            value="{{ $item->truck_number }}" @disabled($item->firstWeighbridge)>
                                        <input type="hidden"
                                            name="loading_program_items[{{ $index }}][transaction_number]"
                                            class="form-control form-control-sm" required
                                            value="{{ $item->transaction_number }}" @disabled($item->firstWeighbridge)>
                                    </div>
                                </td>
                                <td>
                                    <div
                                        @if($item->firstWeighbridge)
                                            data-toggle="tooltip"
                                            title="You cannot update information because Ticket is already passed to first Weighbridge"
                                            data-placement="left"
                                        @endif
                                    >
                                    <input type="text"
                                        name="loading_program_items[{{ $index }}][container_number]"
                                        class="form-control form-control-sm"
                                        value="{{ $item->container_number }}" @disabled($item->firstWeighbridge)>
                                    </div>
                                </td>
                                <td>
                                    <div
                                        @if($item->firstWeighbridge)
                                            data-toggle="tooltip"
                                            title="You cannot update information because Ticket is already passed to first Weighbridge"
                                            data-placement="left"
                                        @endif
                                    >
                                        @php
                                            $selectedPackings = array_filter(explode(', ', $item->packing));
                                        @endphp
                                        <input type="hidden" name="loading_program_items[{{ $index }}][packing]" class="packing-hidden" value="{{ $item->packing }}">
                                        <select class="form-control form-control-sm select2 packing-select" multiple disabled>
                                            @foreach($selectedPackings as $p)
                                                <option value="{{ $p }}" selected>{{ $p }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </td>
                                <td>
                                    <div
                                        @if($item->firstWeighbridge)
                                            data-toggle="tooltip"
                                            title="You cannot update information because Ticket is already passed to first Weighbridge"
                                            data-placement="left"
                                        @endif
                                    >
                                        @php
                                            $itemBrandIds = $item->deliveryOrders->flatMap(function($do) {
                                                return $do->delivery_order_data->pluck('brand_id');
                                            })->unique()->toArray();
                                            if (empty($itemBrandIds) && $item->brand_id) {
                                                $itemBrandIds = [$item->brand_id];
                                            }
                                        @endphp
                                        <input type="hidden" name="loading_program_items[{{ $index }}][brand_id]" class="brand-hidden" value="{{ $item->brand_id }}">
                                        <select class="form-control form-control-sm select2 brand-select" multiple disabled>
                                            @foreach($Brands as $brand)
                                                <option value="{{ $brand->id }}" @selected(in_array($brand->id, $itemBrandIds))>{{ $brand->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </td>
                                <td>
                                    <div
                                        @if($item->firstWeighbridge)
                                            data-toggle="tooltip"
                                            title="You cannot update information because Ticket is already passed to first Weighbridge"
                                            data-placement="left"
                                        @endif
                                    >
                                        <select name="loading_program_items[{{ $index }}][arrival_location_id]"
                                            class="form-control form-control-sm select2 arrival-location-select" required
                                            data-arrival="{{ $item->arrival_location_id }}" @disabled($item->firstWeighbridge)>
                                            <option value="">Select Location</option>
                                            @foreach($locations[1] as $factory)
                                                <option value="{{ $factory["id"] }}" @selected($item->arrival_location_id == $factory["id"])>{{ $factory["text"] }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </td>
                                <td>
                                    <div
                                        @if($item->firstWeighbridge)
                                            data-toggle="tooltip"
                                            title="You cannot update information because Ticket is already passed to first Weighbridge"
                                            data-placement="left"
                                        @endif
                                    >
                                        <select name="loading_program_items[{{ $index }}][sub_arrival_location_id]"
                                            class="form-control form-control-sm select2 sub-arrival-location-select"
                                            required data-subarrival="{{ $item->sub_arrival_location_id }}" @disabled($item->firstWeighbridge)>
                                            <option value="">Select Sub Location</option>
                                            @foreach($locations[2] as $section)
                                                <option value="{{ $section["id"] }}" @selected($item->sub_arrival_location_id == $section["id"])>{{ $section["text"] }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </td>
                                <td>
                                    <div
                                        @if($item->firstWeighbridge)
                                            data-toggle="tooltip"
                                            title="You cannot update information because Ticket is already passed to first Weighbridge"
                                            data-placement="left"
                                        @endif
                                    >
                                        <input type="text"
                                            name="loading_program_items[{{ $index }}][driver_name]"
                                            class="form-control form-control-sm"
                                            value="{{ $item->driver_name }}" @disabled($item->firstWeighbridge)>
                                    </div>
                                </td>
                                <td>
                                    <div
                                        @if($item->firstWeighbridge)
                                            data-toggle="tooltip"
                                            title="You cannot update information because Ticket is already passed to first Weighbridge"
                                            data-placement="left"
                                        @endif
                                    >
                                        <input type="text"
                                            name="loading_program_items[{{ $index }}][contact_details]"
                                            class="form-control form-control-sm"
                                            value="{{ $item->contact_details }}" @disabled($item->firstWeighbridge)>
                                    </div>
                                </td>
                                <td>
                                    <div
                                        @if($item->firstWeighbridge)
                                            data-toggle="tooltip"
                                            title="You cannot update information because Ticket is already passed to first Weighbridge"
                                            data-placement="left"
                                        @endif
                                    >
                                        <select name="loading_program_items[{{ $index }}][transporter_id]" 
                                            class="form-control form-control-sm select2 transporter-select" @disabled($item->firstWeighbridge)>
                                            @if($item->transporter_id)
                                                <option value="{{ $item->transporter_id }}" selected>{{ $item->transporter?->name }}</option>
                                            @else
                                                <option value="">Select Transporter</option>
                                            @endif
                                        </select>
                                        <span class="transporter-placeholder text-muted" style="display: none;">-</span>
                                    </div>
                                </td>
                                <td>
                                    <div
                                        @if($item->firstWeighbridge)
                                            data-toggle="tooltip"
                                            title="You cannot update information because Ticket is already passed to first Weighbridge"
                                            data-placement="left"
                                        @endif
                                    >
                                        <input type="number" name="loading_program_items[{{ $index }}][qty]"
                                            class="form-control form-control-sm" step="0.01"
                                            value="{{ $item->qty }}" @disabled($item->firstWeighbridge)>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <button 
                                        type="button" 
                                        class="btn btn-sm btn-danger remove-item-btn" 
                                        @disabled($item->firstWeighbridge)
                                        @if($item->firstWeighbridge)
                                            data-toggle="tooltip"
                                            title="You cannot update information because Ticket already in Weighbridge"
                                        @endif
                                    >
                                        <i class="ft-trash-2"></i>
                                    </button>
                                </td>
                            </tr>
@empty
                            <tr id="noItemsRow">
                                <td colspan="13" class="text-center text-muted py-3">
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
    // Global UI state guards
    window.isSelectingSO = false;
    window.isUpdatingUI = false;

    var allArrivalLocations = @json(\App\Models\Master\ArrivalLocation::all());
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

        if (locationId) {
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
        $('.select2').select2({ width: '100%' });

        // Initial load of SO details
        const initialSOIds = $('#sale_order_id').val();
        if (initialSOIds) {
            // Initialize existing rows
            $('.item-row').each(function() {
                const $row = $(this);
                const rowSOIds = $row.find('.row-so-select').val() || [];
                
                // Re-bind handlers for existing rows
                $row.find('.row-so-select').change(function() {
                    if (window.isUpdatingUI) return;
                    const soIds = $(this).val() || [];
                    const $doSelect = $row.find('.delivery-order-select');
                    if (soIds.length > 0) {
                        $.ajax({
                            url: '{{ route('sales.getDeliveryOrdersBySaleOrderLoadingEdit') }}',
                            type: 'GET',
                            data: { 
                                sale_order_id: soIds,
                                company_location_id: $('#main_company_location_id').val()
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
                                    
                                    // Aggregate Packing and Brands
                                    const selectedDoIds = $doSelect.val() || [];
                                    const filteredDOs = response.delivery_orders.filter(d => selectedDoIds.includes(d.id.toString()));
                                    updateRowMetadata($row, filteredDOs);

                                    // Update global transporters map
                                    if (response.transporters_map) {
                                        window.transportersMap = window.transportersMap || {};
                                        Object.assign(window.transportersMap, response.transporters_map);
                                    }

                                    window.isUpdatingUI = false;
                                    updateItemLocations($row);
                                    updateRowDORequiredStatus($row);
                                    updateTransporterOptions($row);
                                }
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
                    updateTransporterOptions($row);
                }
            });

            $row.find('.delivery-order-select').change(function() {
                if (window.isUpdatingUI) return;
                const $row = $(this).closest('tr');
                const selectedDoIds = $(this).val() || [];
                const allDOs = $row.data('delivery_orders') || [];
                const filteredDOs = allDOs.filter(d => selectedDoIds.includes(d.id.toString()));
                updateRowMetadata($row, filteredDOs);
                updateItemLocations($row);
            });

            // Trigger initial fetch for existing rows to populate data()
            if (rowSOIds.length > 0) {
                $row.find('.row-so-select').trigger('change');
            } else {
                updateRowDORequiredStatus($row);
                updateTransporterOptions($row);
            }
        });
    get_sale_order(initialSOIds, $('#sale_order_id option:selected').first().data('type'));
        }

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
                $('#saleOrderDataContainer').html('');
                $('#delivery_order_id').empty();
                $('#lineItemsContainer').hide();
                $('#locationContainer').hide();
                $('#is_delivery_order_optional').val('0');
                updateAllRowSaleOrderOptions();
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

            if(delivery_order_texts.length > 1) {
                $("#incompatible-dos").css("display", "block");
                submitBtn.attr("disabled", "disabled");
            } else {
                $("#incompatible-dos").css("display", "none");
                submitBtn.removeAttr("disabled");
            }

            if (delivery_order_ids.length === 0 && !window.isSelectingSO) {
                get_sale_order($("#sale_order_id").val(), type_id);
            }

            if (delivery_order_ids && delivery_order_ids.length > 0) {
                var saleOrderId = $('#sale_order_id').val();
                    $.ajax({
                        url: '{{ route('sales.getDeliveryOrdersBySaleOrderLoadingEdit') }}',
                        type: 'GET',
                        data: { 
                            sale_order_id: saleOrderId,
                            company_location_id: $('#main_company_location_id').val(),
                            loading_program_id: '{{ $LoadingProgram->id }}'
                        },
                        success: function(response) {
                        if (response.success && response.delivery_orders) {
                            var selectedDeliveryOrders = response.delivery_orders.filter(d_o => delivery_order_ids.includes(d_o.id.toString()));
                            if (selectedDeliveryOrders.length > 0) {
                                populateLocationFields(selectedDeliveryOrders);
                                updateItemLocations();
                            }
                        }
                    }
                });
                updateDeliveryOrderOptionsForAllRows();
                $('#locationContainer').show();
                $('#lineItemsContainer').show();
            } else {
                $('.delivery-order-select').each(function() {
                    $(this).empty().append('<option value="">Select Delivery Order</option>').select2();
                });
                var isOptional = $('#is_delivery_order_optional').val() === '1';
                if (isOptional && window.saleOrderData) {
                    populateLocationsFromSaleOrder(window.saleOrderData);
                }
                updateItemLocations();
                if (window.updateTabsVisibility) window.updateTabsVisibility();
            }
        });

        // Add item functionality
        let itemIndex = {{ $LoadingProgram->loadingProgramItems->count() }};
        $('#addItemBtn').click(function() {
            addItemRow(itemIndex);
            itemIndex++;
        });

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

        $(document).on('change', '.arrival-location-select', function() {
            if (window.isUpdatingUI) return;
            updateGalaOptions($(this));
        });

        $("#company_locations").change(function() {
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
    });

    function get_sale_order(sale_order_ids, type_id) {
        if (!sale_order_ids || sale_order_ids.length === 0) {
            $('#saleOrderDataContainer').html('');
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
                    text: "Please wait...",
                    allowOutsideClick: false,
                    didOpen: () => { Swal.showLoading(); }
                });
            },
            success: function(response) {
                Swal.close();
                if (response.success) {
                    window.isUpdatingUI = true;
                    $('#saleOrderDataContainer').html(response.html);
                    $('.select2').select2({ width: '100%' });
                    if (response.sale_order_data) window.saleOrderData = response.sale_order_data;
                    if (response.transporters_map) window.transportersMap = response.transporters_map;
                    
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
                        // Company locations should remain disabled
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

    function updateDeliveryOrderOptionsForRow($select) {
        const currentValue = $select.val();
        $select.empty().append('<option value="">Select Delivery Order</option>');
        const selectedDeliveryOrderIds = $('#delivery_order_id').val() || [];
        $('#delivery_order_id option').each(function() {
            const value = $(this).val();
            const text = $(this).text();
            if (value && selectedDeliveryOrderIds.includes(value)) {
                $select.append(new Option(text, value, false, currentValue == value));
            }
        });
        $select.select2({ width: '100%' });
    }

    function updateDeliveryOrderOptionsForAllRows() {
        $('.delivery-order-select').each(function() {
            updateDeliveryOrderOptionsForRow($(this));
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
                    <select name="loading_program_items[${index}][sale_order_id][]" class="form-control form-control-sm select2 row-so-select" multiple>
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
                <td><input type="text" name="loading_program_items[${index}][driver_name]" class="form-control form-control-sm"></td>
                <td><input type="text" name="loading_program_items[${index}][contact_details]" class="form-control form-control-sm"></td>
                <td>
                    <select name="loading_program_items[${index}][transporter_id]" class="form-control form-control-sm select2 transporter-select">
                        <option value="">Select Transporter</option>
                    </select>
                    <span class="transporter-placeholder text-muted" style="display: none;">-</span>
                </td>
                <td><input type="number" name="loading_program_items[${index}][qty]" class="form-control form-control-sm" step="0.01"></td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-danger remove-item-btn"><i class="ft-trash-2"></i></button>
                </td>
            </tr>
        `;

        $('#itemsList').append(itemHtml);
        $('#noItemsRow').hide();
        const $newRow = $('#itemsList tr.item-row').last();
        $newRow.find('.select2').select2({ width: '100%' });

        $newRow.find('.row-so-select').change(function() {
            if (window.isUpdatingUI) return;
            const rowSOIds = $(this).val() || [];
            const $doSelect = $(this).closest('tr').find('.delivery-order-select');
            if (rowSOIds.length > 0) {
                const company_location_id = $('#main_company_location_id').val();
                    $.ajax({
                        url: '{{ route('sales.getDeliveryOrdersBySaleOrderLoadingEdit') }}',
                        type: 'GET',
                        data: { 
                            sale_order_id: rowSOIds,
                            company_location_id: company_location_id,
                            loading_program_id: '{{ $LoadingProgram->id }}'
                        },
                    success: function(response) {
                        if (response.success) {
                            window.isUpdatingUI = true;
                            const currentDOVals = $doSelect.val() || [];
                            const selectedGlobalDoIds = $('#delivery_order_id').val() || [];
                            $doSelect.empty();
                            $doSelect.append('<option value="">Select Delivery Order</option>');
                            response.delivery_orders.forEach(do_item => {
                                if (selectedGlobalDoIds.includes(do_item.id.toString())) {
                                    $doSelect.append(new Option(do_item.reference_no, do_item.id, false, currentDOVals.includes(do_item.id.toString())));
                                }
                            });
                            $doSelect.trigger('change.select2');
                            $newRow.data('delivery_orders', response.delivery_orders);
                            
                            // Aggregate Packing and Brands
                            const selectedDoIds = $doSelect.val() || [];
                            const filteredDOs = response.delivery_orders.filter(d => selectedDoIds.includes(d.id.toString()));
                            updateRowMetadata($newRow, filteredDOs);

                            // Update global transporters map
                            if (response.transporters_map) {
                                window.transportersMap = window.transportersMap || {};
                                Object.assign(window.transportersMap, response.transporters_map);
                            }

                            window.isUpdatingUI = false;
                            updateItemLocations($newRow);
                            updateRowDORequiredStatus($newRow);
                            updateTransporterOptions($newRow);
                        }
                    },
                });
            } else {
                window.isUpdatingUI = true;
                $doSelect.empty().trigger('change.select2');
                $newRow.data('delivery_orders', []);
                $newRow.find('.packing-select').empty().trigger('change.select2');
                $newRow.find('.brand-select').val([]).trigger('change.select2');
                $newRow.find('.packing-hidden').val('');
                $newRow.find('.brand-hidden').val('');
                window.isUpdatingUI = false;
                updateItemLocations($newRow);
                updateRowDORequiredStatus($newRow);
                updateTransporterOptions($newRow);
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
        updateRowDORequiredStatus($newRow);
        updateTransporterOptions($newRow);
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

    function updateTransporterOptions($row) {
        const selectedSOIds = $row.find('.row-so-select').val() || [];
        const $transporterSelect = $row.find('.transporter-select');
        const $transporterPlaceholder = $row.find('.transporter-placeholder');
        
        let allTransporters = [];
        let isTransporterRequired = false;

        selectedSOIds.forEach(soId => {
            if (window.transportersMap && window.transportersMap[soId]) {
                const soData = window.transportersMap[soId];
                if (soData.transporter_used === 'yes') {
                    isTransporterRequired = true;
                    if (soData.transporters) {
                        allTransporters = allTransporters.concat(soData.transporters);
                    }
                }
            }
        });

        // Unique transporters
        const uniqueTransporters = Array.from(new Map(allTransporters.map(t => [t.id, t])).values());

        const currentVal = $transporterSelect.val();
        $transporterSelect.empty();

        if (isTransporterRequired) {
            $transporterSelect.parent().find('.select2-container').show();
            $transporterPlaceholder.hide();

            $transporterSelect.append('<option value="">Select Transporter</option>');
            uniqueTransporters.forEach(t => {
                $transporterSelect.append(new Option(t.name, t.id, false, t.id.toString() === currentVal));
            });
        } else {
            $transporterSelect.parent().find('.select2-container').hide();
            $transporterPlaceholder.show();
            $transporterSelect.val('');
        }

        $transporterSelect.trigger('change.select2');
    }

    function updateItemLocations($singleRow = null) {
        window.isUpdatingUI = true;
        const $rows = $singleRow || $('.item-row');
        
        const arrivalLocationMap = {};
        allArrivalLocations.forEach(loc => {
            arrivalLocationMap[loc.id.toString()] = loc.name;
        });

        const globalSelectedArrivalLocations = $('#arrival_locations').val() || [];

        $rows.each(function() {
            const $row = $(this);
            const $select = $row.find('.arrival-location-select');
            const rowDOIds = $row.find('.delivery-order-select').val() || [];
            const rowDOs = $row.data('delivery_orders');
            
            if (rowDOIds.length > 0 && typeof rowDOs === 'undefined') return;

            let currentValue = $select.val() || $select.attr('data-arrival');
            let allowedArrivalIds = [];
            const finalRowDOs = rowDOs || [];

            if (rowDOIds.length > 0) {
                finalRowDOs.filter(do_item => rowDOIds.includes(do_item.id.toString())).forEach(do_item => {
                    if (do_item.arrival_location_id) {
                        (do_item.arrival_location_id.toString()).split(',').forEach(id => {
                            id = id.trim();
                            if (id && !allowedArrivalIds.includes(id)) allowedArrivalIds.push(id);
                        });
                    }
                });
            } else {
                allowedArrivalIds = globalSelectedArrivalLocations.map(id => id.toString());
            }

            $select.empty().append('<option value="">Select Location</option>');
            allowedArrivalIds.forEach(id => {
                const text = arrivalLocationMap[id] || ("Location " + id);
                const isSelected = currentValue && currentValue.toString() == id.toString();
                $select.append(new Option(text, id, false, isSelected));
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
        const globalSelectedSubArrivalLocations = $('#sub_arrival_locations').val() || [];

        if (rowDOIds.length > 0 && typeof rowDOs === 'undefined') return;

        let currentGalaValue = $galaSelect.val() || $galaSelect.attr('data-subarrival');
        let allowedSubArrivalIds = [];
        const finalRowDOs = rowDOs || [];
        if (rowDOIds.length > 0) {
            finalRowDOs.filter(do_item => rowDOIds.includes(do_item.id.toString())).forEach(do_item => {
                if (do_item.sub_arrival_location_id) {
                    (do_item.sub_arrival_location_id.toString()).split(',').forEach(id => {
                        id = id.trim();
                        if (id && !allowedSubArrivalIds.includes(id)) allowedSubArrivalIds.push(id);
                    });
                }
            });
        } else {
            allowedSubArrivalIds = globalSelectedSubArrivalLocations.map(id => id.toString());
        }

        $galaSelect.empty().append('<option value="">Select Sub Location</option>');
        if (selectedFactoryId) {
            allSubArrivalLocations.forEach(subLocation => {
                if (subLocation.arrival_location_id == selectedFactoryId && 
                    allowedSubArrivalIds.includes(subLocation.id.toString())) {
                    const isSelected = currentGalaValue && currentGalaValue.toString() == subLocation.id.toString();
                    $galaSelect.append(new Option(subLocation.name, subLocation.id, false, isSelected));
                }
            });
        }
        $galaSelect.select2({ width: '100%' });
    }

    function updateGalaOptionsForAllRows() {
        $('.arrival-location-select').each(function() {
            updateGalaOptions($(this));
        });
    }

    function populateLocationFields(deliveryOrders) {
        window.isUpdatingUI = true;
        $('#arrival_locations, #sub_arrival_locations').empty();
        var arrivalLocations = @json(\App\Models\Master\ArrivalLocation::all());
        var subArrivalLocations = @json(\App\Models\Master\ArrivalSubLocation::all());

        var selectedCompanyIds = [];
        var selectedArrivalIds = [];
        var selectedSubArrivalIds = [];

        deliveryOrders.forEach(function(do_item) {
            if (do_item.location_id && !selectedCompanyIds.includes(do_item.location_id.toString())) selectedCompanyIds.push(do_item.location_id.toString());
            if (do_item.arrival_location_id) do_item.arrival_location_id.split(',').forEach(id => { if(id && !selectedArrivalIds.includes(id.trim())) selectedArrivalIds.push(id.trim()); });
            if (do_item.sub_arrival_location_id) do_item.sub_arrival_location_id.split(',').forEach(id => { if(id && !selectedSubArrivalIds.includes(id.trim())) selectedSubArrivalIds.push(id.trim()); });
        });

        arrivalLocations.forEach(loc => $('#arrival_locations').append(new Option(loc.name, loc.id, false, false)));
        subArrivalLocations.forEach(loc => $('#sub_arrival_locations').append(new Option(loc.name, loc.id, false, false)));

        $('.select2').select2({ width: '100%' });

        if (selectedCompanyIds.length > 0) {
            $('#company_locations').val(selectedCompanyIds[0]).trigger('change.select2');
        }
        if (selectedArrivalIds.length > 0) $('#arrival_locations').val(selectedArrivalIds).trigger('change.select2');
        if (selectedSubArrivalIds.length > 0) $('#sub_arrival_locations').val(selectedSubArrivalIds).trigger('change.select2');
        
        if (window.updateTabsVisibility) window.updateTabsVisibility();
        window.isUpdatingUI = false;
        updateItemLocations();
    }

    function populateLocationsFromSaleOrder(saleOrderData) {
        window.isUpdatingUI = true;
        $('#arrival_locations, #sub_arrival_locations').empty();
        var arrivalLocations = @json(\App\Models\Master\ArrivalLocation::all());
        var subArrivalLocations = @json(\App\Models\Master\ArrivalSubLocation::all());

        if (saleOrderData.company_location_id) {
            $('#company_locations').val(saleOrderData.company_location_id).trigger('change.select2');
        }

        var arrivalIds = Array.isArray(saleOrderData.arrival_location_id) ? saleOrderData.arrival_location_id : (saleOrderData.arrival_location_id ? [saleOrderData.arrival_location_id.toString()] : []);
        var subIds = Array.isArray(saleOrderData.sub_arrival_location_id) ? saleOrderData.sub_arrival_location_id : (saleOrderData.sub_arrival_location_id ? [saleOrderData.sub_arrival_location_id.toString()] : []);

        $.each(arrivalLocations, function(i, loc) {
            if (arrivalIds.includes(loc.id.toString())) {
                $('#arrival_locations').append(new Option(loc.name, loc.id, false, false));
            }
        });

        $.each(subArrivalLocations, function(i, loc) {
            if (subIds.includes(loc.id.toString())) {
                $('#sub_arrival_locations').append(new Option(loc.name, loc.id, false, false));
            }
        });

        $('#arrival_locations').val(arrivalIds).trigger('change.select2');
        $('#sub_arrival_locations').val(subIds).trigger('change.select2');

        $('.select2').select2({ width: '100%' });
        window.isUpdatingUI = false;
        updateItemLocations();
    }

    function getDoQty(el) {
        if(!$(el).val()) { $(el).closest("tr").find(".do_qty").val(""); return; }
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

    $('.select2').on('select2:open', function (e) {
        // Remove all Select2 scroll blockers from window & parents
        $(document).off('scroll.select2');
        $(window).off('scroll.select2');
        $('*').off('scroll.select2');           // aggressive but often works
    });
</script>
