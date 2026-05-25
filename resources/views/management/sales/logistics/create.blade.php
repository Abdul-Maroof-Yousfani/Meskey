<form action="{{ route('sales.logistics.store') }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf

    <input type="hidden" id="listRefresh" value="{{ route('sales.get.logistics.list') }}" data-appenddiv="filteredData" />

    <div class="row form-mar">
        <div class="col-md-12">
            <div class="row mb-2">
                <div class="col-12">
                    <h6 class="header-heading-sepration">Selection Detail</h6>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="type">Type</label>
                        <select name="type" id="type" class="form-control select2" required>
                            <option value="">Select Type</option>
                            <option value="sale_order" selected>Sale Order</option>
                            <option value="export_order">Export Order</option>
                        </select>
                    </div>
                </div>
            </div>

            <div id="document_info_container" style="display: none;">
                <div class="row">
                    <div class="col-12">
                        <h6 class="header-heading-sepration text-uppercase">Document Information</h6>
                    </div>
                
                    <!-- Row 1: Loading Request, Date, SO # -->
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="sale_order_id" class="text-uppercase" id="document_select_label">Loading Request (Sale Order)</label>
                            <select name="sale_order_id" id="sale_order_id" class="form-control select2 document-select" style="width: 100%;">
                                <option value="">Select Sale Order</option>
                                @foreach($saleOrders as $order)
                                    <option value="{{ $order->id }}">
                                        {{ $order->reference_no }}
                                    </option>
                                @endforeach
                            </select>
                            <select name="export_order_id" id="export_order_id" class="form-control select2 document-select" style="width: 100%; display: none;">
                                <option value="">Select Export Order</option>
                                @foreach($exportOrders as $order)
                                    <option value="{{ $order->id }}">
                                        {{ $order->voucher_no }}
                                    </option>
                                @endforeach
                            </select>
                            <input type="hidden" name="loading_request" id="loading_request">
                        </div>
                    </div>

                    <!-- Date (readonly - pre-filled on selection) -->
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="date" class="text-uppercase">Date</label>
                            <input type="date" name="date" id="date" class="form-control" readonly required>
                        </div>
                    </div>

                    <!-- SO # (readonly) -->
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="so_no" class="text-uppercase" id="document_no_label">SO #</label>
                            <input type="text" name="so_no" id="so_no" class="form-control" readonly>
                        </div>
                    </div>

                    <!-- Row 2: Order Qty, Commodity, Trade Term -->
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="so_qty" class="text-uppercase" id="order_qty_label">Sales Order Qty (kg)</label>
                            <input type="number" name="so_qty" id="so_qty" class="form-control" readonly>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="commodity" class="text-uppercase">Commodity</label>
                            <input type="text" name="commodity" id="commodity" class="form-control" readonly>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="sauda_type" class="text-uppercase" id="trade_term_label">Sauda Type</label>
                            <input type="text" name="sauda_type" id="sauda_type" class="form-control" readonly>
                        </div>
                    </div>

                    <!-- Row 3: Locations -->
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="location" class="text-uppercase">From Location</label>
                            <select name="location" id="location" class="form-control select2" required style="width: 100%;">
                                <option value="">Select From Location</option>
                            </select>
                        </div>
                    </div>


                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="to_location" class="text-uppercase" id="to_location_label">To Location</label>
                             <select name="to_location" id="to_location" class="form-control select2" required style="width: 100%;">
                                <option value="">Select To Location</option>
                                @foreach($companyLocations as $location)
                                    <option value="{{ $location->id }}">
                                        {{ $location->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- Row 4: Customer -->
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="customer" class="text-uppercase">Customer</label>
                            <input type="text" name="customer" id="customer" class="form-control" readonly>
                        </div>
                    </div>

                    <div class="col-md-4" style="display: none;">
                        <div class="form-group">
                            <label for="delivery_address" class="text-uppercase">Delivery Address</label>
                            <input type="text" name="delivery_address" id="delivery_address" class="form-control" readonly>
                        </div>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-12">
                        <h6 class="header-heading-sepration text-uppercase">Logistics Items</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered" id="itemsTable">
                            <thead class="thead-light">
                                <tr>
                                    <th>Rate Type</th>
                                    <th>Rate</th>
                                    <th id="partner_column_label">Transporter</th>
                                    <th>Qty</th>
                                    <th width="50">Action</th>
                                </tr>
                            </thead>
                            <tbody id="itemsBody">
                                <tr class="item-row">
                                    <td>
                                        <select name="items[0][rate_type]" class="form-control" required>
                                            <option value="">Select Type</option>
                                            <option value="Per MT">Per MT</option>
                                            <option value="Per KG">Per KG</option>
                                            <option value="Per Truck" selected>Per Truck</option>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="number" name="items[0][rate]" class="form-control"
                                            step="0.01" required>
                                    </td>
                                    <td>
                                        <select name="items[0][transporter]" class="form-control transporter-select" required>
                                            <option value="">Select Transporter</option>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="number" name="items[0][qty]" class="form-control"
                                            step="0.01" required>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-danger remove-row"
                                            disabled>
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="5">
                                        <button type="button" class="btn btn-sm btn-info" id="addRow">
                                            <i class="fa fa-plus"></i> Add row
                                        </button>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                </div>
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
    $(document).ready(function() {
        // Initialize Select2 if available
        if ($.fn.select2) {
            $('.select2').select2({
                dropdownParent: $('#modal-sidebar')
            });
        }

        const saleToLocationOptions = @json($companyLocations->map(fn($location) => ['id' => $location->id, 'name' => $location->name])->values());

        function initTransporterSelect(selector) {
            const config = {
                table: 'transporters',
                column: 'company_name',
                idColumn: 'id',
                placeholder: 'Select Transporter'
            };

            $(selector).each(function () {
                const $select = $(this);
                if ($select.hasClass('select2-hidden-accessible')) {
                    $select.select2('destroy');
                }

                const selectedValue = $select.val();
                const selectedText = $select.find('option:selected').text();

                $select.empty().append(new Option(config.placeholder, '', !selectedValue, !selectedValue));

                if (selectedValue) {
                    $select.append(new Option(selectedText, selectedValue, true, true));
                }

                initializeDynamicSelect2($select, config.table, config.column, config.idColumn, false, false);
            });
        }

        let rowCount = 1;

        function addRow(data = null) {
            let rateType = data ? data.rate_type : 'Per Truck';
            let rate = data ? data.rate : '';
            let transporterId = data ? data.transporter_id : '';
            let transporterName = data
                ? (data.transporter_name || data.transporter?.company_name || data.transporter?.name)
                : '';
            let qty = data ? data.qty : '';

            let selectValue = transporterId || transporterName;
            let selectText = transporterName || getPartnerPlaceholder();

            let newRow = `
                <tr class="item-row">
                    <td>
                        <select name="items[${rowCount}][rate_type]" class="form-control" required>
                            <option value="">Select Type</option>
                            <option value="Per MT" ${rateType === 'Per MT' ? 'selected' : ''}>Per MT</option>
                            <option value="Per KG" ${rateType === 'Per KG' ? 'selected' : ''}>Per KG</option>
                            <option value="Per Truck" ${rateType === 'Per Truck' ? 'selected' : ''}>Per Truck</option>
                        </select>
                    </td>
                    <td>
                        <input type="number" name="items[${rowCount}][rate]" class="form-control" step="0.01" value="${rate}" required>
                    </td>
                    <td>
                        <select name="items[${rowCount}][transporter]" class="form-control transporter-select" required>
                            ${selectValue ? `<option value="${selectValue}" selected>${selectText}</option>` : `<option value="">${getPartnerPlaceholder()}</option>`}
                        </select>
                    </td>
                    <td>
                        <input type="number" name="items[${rowCount}][qty]" class="form-control" step="0.01" value="${qty}" required>
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-danger remove-row">
                            <i class="fa fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
            $('#itemsBody').append(newRow);
            
            // Re-initialize Select2 for the new row
            if ($.fn.select2) {
                initTransporterSelect('#itemsBody tr:last .transporter-select');
            }

            rowCount++;
            updateRemoveButtons();
        }

        function getPartnerPlaceholder() {
            return 'Select Transporter';
        }

        function populateSelectOptions($select, options, selectedValue, placeholder) {
            $select.empty().append(new Option(placeholder, '', !selectedValue, !selectedValue));

            (options || []).forEach(function(option) {
                const isSelected = selectedValue && option.id.toString() === selectedValue.toString();
                $select.append(new Option(option.name, option.id, isSelected, isSelected));
            });

            $select.trigger('change.select2');
        }

        function updateLocationFields(type, data, selectedLogistics = null) {
            const isExport = type === 'export_order';
            const savedFromLocation = selectedLogistics && selectedLogistics.location ? selectedLogistics.location : '';
            const savedToLocation = selectedLogistics && selectedLogistics.to_location ? selectedLogistics.to_location : '';
            const fromLocationValue = /^\d+$/.test(String(savedFromLocation)) ? savedFromLocation : (data.from_location_id || '');
            const toLocationValue = /^\d+$/.test(String(savedToLocation)) ? savedToLocation : (data.to_location_id || '');
            const fromLocationOptions = isExport ? (data.from_location_options || []) : (data.from_location_options || []).filter(function(option) {
                return fromLocationValue && option.id.toString() === fromLocationValue.toString();
            });

            $('#to_location_label').text(isExport ? 'Port of Loading' : 'To Location');
            populateSelectOptions(
                $('#location'),
                fromLocationOptions,
                fromLocationValue,
                'Select From Location'
            );
            $('#location').prop('disabled', !isExport);
            populateSelectOptions(
                $('#to_location'),
                isExport ? (data.to_location_options || []) : saleToLocationOptions,
                toLocationValue,
                isExport ? 'Select Port of Loading' : 'Select To Location'
            );
        }

        function updateTypeUI(type) {
            const isExport = type === 'export_order';

            $('#document_select_label').text(isExport ? 'Loading Request (Export Order)' : 'Loading Request (Sale Order)');
            $('#document_no_label').text(isExport ? 'EO #' : 'SO #');
            $('#order_qty_label').text(isExport ? 'Export Order Qty (MT)' : 'Sales Order Qty (kg)');
            $('#trade_term_label').text(isExport ? 'Inco Term' : 'Sauda Type');
            $('#partner_column_label').text('Transporter');

            $('#sale_order_id').prop('required', !isExport).toggle(!isExport);
            $('#export_order_id').prop('required', isExport).toggle(isExport);
            $('#sale_order_id').next('.select2-container').toggle(!isExport);
            $('#export_order_id').next('.select2-container').toggle(isExport);
            $('#to_location_label').text(isExport ? 'Port of Loading' : 'To Location');

            $('#itemsBody .transporter-select').each(function () {
                const currentText = $(this).find('option:selected').text();
                if (!$(this).val() || currentText === 'Select Transporter') {
                    $(this).empty().append(new Option(getPartnerPlaceholder(), '', true, true));
                }
            });

            populateSelectOptions($('#location'), [], '', 'Select From Location');
            $('#location').prop('disabled', !isExport);
            populateSelectOptions(
                $('#to_location'),
                isExport ? [] : saleToLocationOptions,
                '',
                isExport ? 'Select Port of Loading' : 'Select To Location'
            );
            initTransporterSelect('.transporter-select');
        }

        // Handle Type Selection
        $('#type').on('change', function() {
            let type = $(this).val();
            if (type === 'sale_order' || type === 'export_order') {
                $('#document_info_container').show();
                updateTypeUI(type);
            } else {
                $('#document_info_container').hide();
            }
        }).trigger('change');

        function fetchOrderDetails(orderId, type, loadingRequestText) {
            if (orderId) {
                $('.loader-container').show();
                let url = "{{ route('sales.logistics.getOrderDetails', ['id' => ':id']) }}";
                url = url.replace(':id', orderId);
                $.ajax({
                    url: url,
                    method: 'GET',
                    data: { type: type },
                    success: function(data) {
                        $('#date').val(data.date);
                        $('#so_no').val(data.so_no);
                        $('#so_qty').val(data.so_qty);
                        $('#commodity').val(data.commodity);
                        $('#sauda_type').val(data.sauda_type);
                        $('#customer').val(data.customer);
                        $('#delivery_address').val(data.delivery_address);
                        updateLocationFields(type, data);
                        $('#loading_request').val(loadingRequestText);

                        // Handle existing logistics data
                        if (data.logistics) {
                            if (data.logistics.type) {
                                $('#type').val(data.logistics.type).trigger('change');
                            }
                            $('#date').val(data.logistics.date);
                            if(data.logistics.delivery_address) $('#delivery_address').val(data.logistics.delivery_address);
                            updateLocationFields(type, data, data.logistics);
                            
                            $('#itemsBody').empty();
                            rowCount = 0;
                            if (data.logistics.items && data.logistics.items.length > 0) {
                                data.logistics.items.forEach(item => {
                                    addRow(item);
                                });
                            } else {
                                addRow();
                            }
                        } else {
                            // Reset items if no existing logistics
                            $('#itemsBody').empty();
                            rowCount = 0;
                            addRow();
                        }

                        $('.loader-container').hide();
                    },
                    error: function() {
                        Swal.fire('Error', 'Failed to fetch order details', 'error');
                        $('.loader-container').hide();
                    }
                });
            } else {
                // Clear fields if no order selected
                $('#date, #so_no, #so_qty, #commodity, #sauda_type, #customer, #delivery_address, #loading_request').val('');
                populateSelectOptions($('#location'), [], '', 'Select From Location');
                $('#location').prop('disabled', $('#type').val() !== 'export_order');
                populateSelectOptions($('#to_location'), $('#type').val() === 'export_order' ? [] : saleToLocationOptions, '', $('#type').val() === 'export_order' ? 'Select Port of Loading' : 'Select To Location');
                $('#itemsBody').empty();
                rowCount = 0;
                addRow();
            }
        }

        $('#sale_order_id').on('change', function() {
            if ($('#type').val() !== 'sale_order') return;
            fetchOrderDetails($(this).val(), 'sale_order', $('#sale_order_id option:selected').text().trim());
        });

        $('#export_order_id').on('change', function() {
            if ($('#type').val() !== 'export_order') return;
            fetchOrderDetails($(this).val(), 'export_order', $('#export_order_id option:selected').text().trim());
        });

        $('#addRow').off('click').on('click', function() {
            addRow();
        });

        $(document).off('click', '.remove-row').on('click', '.remove-row', function() {
            $(this).closest('tr').remove();
            updateRemoveButtons();
        });

        function updateRemoveButtons() {
            if ($('#itemsBody tr').length <= 1) {
                $('#itemsBody tr').find('.remove-row').prop('disabled', true);
            } else {
                $('#itemsBody tr').find('.remove-row').prop('disabled', false);
            }
        }

        initTransporterSelect('.transporter-select');
    });
</script>
