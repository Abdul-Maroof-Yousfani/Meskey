<form action="{{ route('export-loading-program-complete.update', $loadingProgram->id) }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    @method('PUT')
    <input type="hidden" id="listRefresh" value="{{ route('get.export-loading-program-complete') }}" />
    
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
        <div class="col-xs-12 col-sm-4 col-md-4">
            <div class="form-group">
                <label>Vessel Name:</label>
                <input type="text" name="vessel_name" class="form-control" value="{{ $loadingProgram->vessel_name }}" placeholder="Enter Vessel Name">
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
                width: 100% !important;
            }
            #itemsTable th, #itemsTable td {
                padding: 8px 4px !important;
                vertical-align: middle !important;
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
                            <th style="width: 15%">Truck Number *</th>
                            <th style="width: 15%">Container Number</th>
                            <th style="width: 10%">Berth No</th>
                            <th style="width: 10%">S.Bill No</th>
                            <th style="width: 15%">Driver Name</th>
                            <th style="width: 15%">Contact Details</th>
                            <th style="width: 15%">Transporter</th>
                            <th style="width: 5%">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="itemsList">
                        @foreach($loadingProgram->loadingProgramItems as $index => $item)
                            <tr class="item-row" data-index="{{ $index }}">
                                <td>
                                    <input type="text" name="loading_program_items[{{ $index }}][truck_number]"
                                        class="form-control form-control-sm" required
                                        value="{{ $item->truck_number }}" @disabled($item->firstWeighbridge)>
                                    <input type="hidden" name="loading_program_items[{{ $index }}][id]" value="{{ $item->id }}">
                                    <input type="hidden" name="loading_program_items[{{ $index }}][transaction_number]"
                                        value="{{ $item->transaction_number }}">
                                </td>
                                <td>
                                    <input type="text" name="loading_program_items[{{ $index }}][container_number]" 
                                        class="form-control form-control-sm" value="{{ $item->container_number }}" @disabled($item->firstWeighbridge)>
                                </td>
                                <td>
                                    <input type="text" name="loading_program_items[{{ $index }}][berth_no]" 
                                        class="form-control form-control-sm" value="{{ $item->berth_no }}" @disabled($item->firstWeighbridge)>
                                </td>
                                <td>
                                    <input type="text" name="loading_program_items[{{ $index }}][s_bill_no]" 
                                        class="form-control form-control-sm" value="{{ $item->s_bill_no }}" @disabled($item->firstWeighbridge)>
                                </td>
                                <td>
                                    <input type="text" name="loading_program_items[{{ $index }}][driver_name]" 
                                        class="form-control form-control-sm" value="{{ $item->driver_name }}" @disabled($item->firstWeighbridge)>
                                </td>
                                <td>
                                    <input type="text" name="loading_program_items[{{ $index }}][contact_details]" 
                                        class="form-control form-control-sm" value="{{ $item->contact_details }}" @disabled($item->firstWeighbridge)>
                                </td>
                                <td>
                                    <select name="loading_program_items[{{ $index }}][transporter_id]" class="form-control form-control-sm select2 transporter-select" @disabled($item->firstWeighbridge)>
                                        <option value="">Select Transporter</option>
                                        @foreach($Transporters as $transporter)
                                            <option value="{{ $transporter->id }}" @selected($item->transporter_id == $transporter->id)>{{ $transporter->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
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
            <button type="submit" class="btn btn-primary submitbutton">Save & Complete</button>
        </div>
    </div>
</form>

<script>
    window.isUpdatingUI = false;
    window.isSelectingEO = false;
    
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
            syncDOSelectionState(initialDOIds);
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

        function syncDOSelectionState(selectedIds) {
            if (window.isUpdatingUI) return;
            window.isUpdatingUI = true;
            
            var selectedDOs = [];
            if (window.allFetchedDOs) {
                selectedDOs = window.allFetchedDOs.filter(function(d) {
                    return selectedIds.includes(d.id.toString());
                });
            }

            if (selectedIds.length > 0) {
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
                $('#locationContainer').show();
                $('#lineItemsContainer').show();
                populateLocationFields(selectedDOs);
                window.isUpdatingUI = false;
            } else {
                hideDODetailsAndLocations();
            }
        }

        function populateLocationFields(deliveryOrders) {
            window.isUpdatingUI = true;
            let selA = []; let selS = []; let selC = [];
            deliveryOrders.forEach(d => {
                if (d.locations) d.locations.forEach(l => {
                    if (l.company_location_id && !selC.includes(l.company_location_id.toString())) selC.push(l.company_location_id.toString());
                    if (l.arrival_location_ids) l.arrival_location_ids.split(',').forEach(id => { if (id.trim() && !selA.includes(id.trim())) selA.push(id.trim()); });
                    if (l.sub_arrival_location_ids) l.sub_arrival_location_ids.split(',').forEach(id => { if (id.trim() && !selS.includes(id.trim())) selS.push(id.trim()); });
                });
            });

            $('#company_locations, #arrival_locations, #sub_arrival_locations').prop('disabled', false);
            $('#company_locations').empty(); 
            @json(get_locations()).forEach(l => { if (selC.includes(l.id.toString())) $('#company_locations').append(new Option(l.name, l.id, true, true)); });
            
            $('#arrival_locations').empty();
            deliveryOrders.forEach(d => {
                if (d.locations) d.locations.forEach(l => {
                    if (l.arrival_locations) l.arrival_locations.forEach(al => $('#arrival_locations').append(new Option(al.name, al.id, true, true)));
                });
            });

            $('#sub_arrival_locations').empty();
            deliveryOrders.forEach(d => {
                if (d.locations) d.locations.forEach(l => {
                    if (l.sub_arrival_locations) l.sub_arrival_locations.forEach(sal => $('#sub_arrival_locations').append(new Option(sal.name, sal.id, true, true)));
                });
            });

            $('#company_locations').val(selC).trigger('change.select2');
            $('#company_locations, #arrival_locations, #sub_arrival_locations').prop('disabled', true);
            window.isUpdatingUI = false;
        }

        function hideDODetailsAndLocations() {
            window.isUpdatingUI = true;
            $('#exportOrderDataContainer').hide();
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
                        window.isUpdatingUI = false;
                        if (currentDOs.length > 0) syncDOSelectionState(currentDOs);
                    }
                },
                complete: function() { window.isSelectingEO = false; }
            });
        }

        function addItemRow(index) {
            const itemHtml = `
                <tr class="item-row" data-index="${index}">
                    <td><input type="text" name="loading_program_items[${index}][truck_number]" class="form-control form-control-sm" required></td>
                    <td><input type="text" name="loading_program_items[${index}][container_number]" class="form-control form-control-sm"></td>
                    <td><input type="text" name="loading_program_items[${index}][berth_no]" class="form-control form-control-sm"></td>
                    <td><input type="text" name="loading_program_items[${index}][s_bill_no]" class="form-control form-control-sm"></td>
                    <td><input type="text" name="loading_program_items[${index}][driver_name]" class="form-control form-control-sm"></td>
                    <td><input type="text" name="loading_program_items[${index}][contact_details]" class="form-control form-control-sm"></td>
                    <td><select name="loading_program_items[${index}][transporter_id]" class="form-control form-control-sm select2 transporter-select"><option value="">Select Transporter</option>@foreach($Transporters as $transporter)<option value="{{ $transporter->id }}">{{ $transporter->name }}</option>@endforeach</select></td>
                    <td class="text-center"><button type="button" class="btn btn-sm btn-danger remove-item-btn"><i class="ft-trash-2"></i></button></td>
                </tr>
            `;
            $('#itemsList').append(itemHtml);
            const $newRow = $('#itemsList tr.item-row').last();
            $newRow.find('.select2').select2({ width: '100%' });
        }

        $(document).on('click', '.remove-item-btn', function() { $(this).closest('tr').remove(); });
    });
</script>
