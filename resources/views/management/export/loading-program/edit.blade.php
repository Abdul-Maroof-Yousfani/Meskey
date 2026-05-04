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

                            // Auto-fill vessel name from the first selected DO
                            if (delivery_order_ids.length > 0) {
                                const selectedDO = response.delivery_orders.find(d => d.id == delivery_order_ids[0]);
                                if (selectedDO && selectedDO.vessel_name) {
                                    $('input[name="vessel_name"]').val(selectedDO.vessel_name);
                                }
                            }
                        }
                    }
                });
                $('#locationContainer').show();
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
                window.isUpdatingUI = false;
            } else {
                hideDODetailsAndLocations();
            }
        }

        function hideDODetailsAndLocations() {
            window.isUpdatingUI = true;
            $('#delivery_order_details_wrapper').hide();
            $('#locationContainer').hide();
            window.isUpdatingUI = false;
        }

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

        function populateLocationFields(deliveryOrders) {
            window.isUpdatingUI = true;
            
            var arrivalLocations = @json(\App\Models\Master\ArrivalLocation::all());
            var subArrivalLocations = @json(\App\Models\Master\ArrivalSubLocation::all());

            var selectedArrivalIds = [];
            var selectedSubArrivalIds = [];
            var selectedCompanyIds = [];

            deliveryOrders.forEach(function(doItem) {
                if (doItem.locations) {
                    doItem.locations.forEach(loc => {
                        if (loc.company_location_id && !selectedCompanyIds.includes(loc.company_location_id.toString())) {
                            selectedCompanyIds.push(loc.company_location_id.toString());
                        }
                        if (loc.arrival_location_ids) {
                            loc.arrival_location_ids.split(',').forEach(id => {
                                if (id.trim() && !selectedArrivalIds.includes(id.trim())) selectedArrivalIds.push(id.trim());
                            });
                        }
                        if (loc.sub_arrival_location_ids) {
                            loc.sub_arrival_location_ids.split(',').forEach(id => {
                                if (id.trim() && !selectedSubArrivalIds.includes(id.trim())) selectedSubArrivalIds.push(id.trim());
                            });
                        }
                    });
                }
            });

            const uniqueArrivalIds = [...new Set(selectedArrivalIds)];
            const uniqueSubArrivalIds = [...new Set(selectedSubArrivalIds)];
            const uniqueCompanyIds = [...new Set(selectedCompanyIds)];

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
            
            $('#company_locations, #arrival_locations, #sub_arrival_locations').prop('disabled', true);
            
            window.isUpdatingUI = false;
        }
    });
</script>