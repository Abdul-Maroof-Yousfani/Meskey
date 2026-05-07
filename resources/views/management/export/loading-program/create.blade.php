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
                <select class="form-control select2" name="delivery_order_id[]" id="delivery_order_id" multiple
                    disabled>
                </select>
            </div>
        </div>
        <div class="col-xs-12 col-sm-4 col-md-4">
            <div class="form-group">
                <label>Vessel Name:</label>
                <input type="text" name="vessel_name" class="form-control" placeholder="Enter Vessel Name" readonly>
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

    $('#main_company_location_id').change(function () {
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
                success: function (response) {
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

    $(document).ready(function () {
        // Initial setup
        $('.select2').select2({ width: '100%' });

        $('#export_order_id').change(function () {
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

        $('#delivery_order_id').change(function () {
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
                    success: function (response) {
                        window.allFetchedDOs = response.delivery_orders;
                        syncDOSelectionState(delivery_order_ids, response.delivery_orders);

                        // Auto-fill vessel name from the first selected DO
                        if (delivery_order_ids.length > 0) {
                            const firstDO = response.delivery_orders.find(d => d.id == delivery_order_ids[0]);
                            if (firstDO && firstDO.vessel_name) {
                                $('input[name="vessel_name"]').val(firstDO.vessel_name);
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
                    selectedIds.forEach(function (id, idx) {
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
                beforeSend: function () {
                    Swal.fire({
                        title: "Processing...",
                        text: "Please wait while fetching export order details.",
                        allowOutsideClick: false,
                        didOpen: () => { Swal.showLoading(); }
                    });
                },
                success: function (response) {
                    Swal.close();
                    if (response.success) {
                        window.isUpdatingUI = true;
                        $('#exportOrderDataContainer').html(response.html).show();
                        $('.select2').select2({ width: '100%' });

                        if (response.delivery_orders) {
                            window.allFetchedDOs = response.delivery_orders;
                        }

                        var currentDOVals = $('#delivery_order_id').val() || [];
                        $('#delivery_order_id').empty();
                        (response.delivery_orders || []).forEach(doItem => {
                            $('#delivery_order_id').append('<option value="' + doItem.id + '">' + doItem.reference_no + '</option>');
                        });
                        $('#delivery_order_id').val(currentDOVals).trigger('change.select2');

                        window.isUpdatingUI = false;

                        if (currentDOVals.length > 0) {
                            syncDOSelectionState(currentDOVals, response.delivery_orders);
                        }
                    }
                },
                complete: function () {
                    window.isSelectingEO = false;
                },
                error: function () {
                    Swal.close();
                    Swal.fire("Error", "Something went wrong.", "error");
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

            deliveryOrders.forEach(function (doItem) {
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