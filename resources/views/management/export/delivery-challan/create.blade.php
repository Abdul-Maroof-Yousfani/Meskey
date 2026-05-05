<style>
    html, body { overflow-x: hidden; }
    .info-box {
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 12px 14px;
        background: #f8fafc;
        height: 100%;
    }
    .info-box .label {
        display: block;
        font-size: 12px;
        color: #6b7280;
        margin-bottom: 4px;
    }
    .info-box .value {
        font-size: 18px;
        font-weight: 700;
        color: #111827;
    }
    #salesInquiryTable td { padding: 6px 10px !important; }
</style>

<form action="{{ route('export-delivery-challan.store') }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    <input type="hidden" id="listRefresh" value="{{ route('get.export-delivery-challan.list') }}" />

    <div class="row form-mar">
        <div class="col-12"><h6 class="header-heading-sepration">General Information</h6></div>
        <div class="col-md-6">
            <div class="form-group">
                <label class="form-label">DC NO:</label>
                <input type="text" name="dc_no" id="dc_no" class="form-control" readonly>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label class="form-label">Date:</label>
                <input type="date" name="date" id="date" class="form-control" value="{{ date('Y-m-d') }}" readonly>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label class="form-label">Select Ticket: <span class="text-danger">*</span></label>
                <select name="initial_ticket_id" id="initial_ticket_id" class="form-control select2">
                    <option value="">Select Ticket</option>
                </select>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label class="form-label">Customer:</label>
                <select id="customer_id_display" class="form-control select2" disabled>
                    <option value="">Select Customer</option>
                    @foreach ($customers ?? [] as $customer)
                        <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                    @endforeach
                </select>
                <input type="hidden" name="customer_id" id="customer_id">
            </div>
        </div>
        <div class="col-md-6 d-none">
            <div class="form-group">
                <label class="form-label">DO Number:</label>
                <select id="do_no" class="form-control select2" disabled>
                    <option value="">Select Delivery Order</option>
                </select>
                <input type="hidden" name="delivery_order_id" id="delivery_order_id">
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label class="form-label">Reference Number:</label>
                <input type="text" name="reference_number" id="reference_number" class="form-control">
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label class="form-label">Ticket Labour Status:</label>
                <select id="labour_status" class="form-control select2" disabled>
                    <option value="paid">Paid</option>
                    <option value="not_paid">Not Paid</option>
                </select>
                <input type="hidden" name="labour_status" id="labour_status_hidden" value="paid">
            </div>
        </div>

        <div class="col-12 mt-3"><h6 class="header-heading-sepration">Location Details</h6></div>
        <div class="col-md-4">
            <div class="form-group">
                <label class="form-label">Locations:</label>
                <select id="locations" class="form-control select2" multiple disabled></select>
                <div id="locations_hidden"></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label class="form-label">Factory:</label>
                <select id="arrivals" class="form-control select2" multiple disabled></select>
                <input type="hidden" name="arrival_location_csv" id="arrival_location_csv">
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label class="form-label">Gala:</label>
                <select id="storages" class="form-control select2" multiple disabled></select>
                <input type="hidden" name="storage_location_csv" id="storage_location_csv">
            </div>
        </div>

        <div class="col-12 mt-3"><h6 class="header-heading-sepration">Service Providers</h6></div>
        <div class="col-md-6">
            <div class="form-group">
                <label class="form-label">Labour:</label>
                <select name="labour" id="labour" class="form-control select2">
                    <option value="">Select Labours</option>
                    <option value="1">Labour 1</option>
                    <option value="2">Labour 2</option>
                </select>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label class="form-label">Transporter:</label>
                <select name="transporter" id="transporter" class="form-control select2">
                    <option value="">Select Transporter</option>
                    @foreach ($Transporters ?? [] as $transporter)
                        <option value="{{ $transporter->id }}">{{ $transporter->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="col-12 mt-3"><h6 class="header-heading-sepration">Financials</h6></div>
        <div class="col-md-4">
            <div class="form-group">
                <label class="form-label">Labour Rate:</label>
                <input type="text" name="labour_rate" id="standard_labour_rate" class="form-control" readonly>
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label class="form-label">Labour Amount:</label>
                <input type="number" name="labour_amount" id="labour_amount" class="form-control" readonly>
            </div>
        </div>
        <div class="col-md-4 d-none">
            <div class="form-group">
                <label class="form-label">Transporter Amount:</label>
                <input type="number" name="transporter_amount" id="transporter_amount" class="form-control">
            </div>
        </div>
        <div class="col-12 mt-3">
            <div class="form-group">
                <label class="form-label">Remarks:</label>
                <textarea name="remarks" id="remarks" class="form-control" rows="3"></textarea>
            </div>
        </div>
    </div>

    <div class="row form-mar">
        <div class="col-md-4">
            <div class="info-box">
                <span class="label">Second Weighbridge Qty (MT)</span>
                <span class="value" id="second_weighbridge_qty_mt">0.000</span>
            </div>
        </div>
        <div class="col-md-4">
            <div class="info-box">
                <span class="label">Total Line Items Qty (MT)</span>
                <span class="value" id="line_items_total_qty_mt">0.000</span>
            </div>
        </div>
        <div class="col-md-4">
            <div class="info-box">
                <span class="label">Remaining Qty (MT)</span>
                <span class="value" id="remaining_qty_mt">0.000</span>
            </div>
        </div>
    </div>

    <div class="row form-mar">
        <div class="col-md-12">
            <div class="table-responsive" style="overflow-x:auto; white-space:nowrap;">
                <table class="table table-bordered" id="salesInquiryTable" style="min-width:1600px;">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Bag Type</th>
                            <th>Packing</th>
                            <th>No. of Bags</th>
                            <th>Quantity (MT)</th>
                            <th>Rate per MT</th>
                            <th style="display:none;">Rate per Mond</th>
                            <th>Amount</th>
                            <th>Brand</th>
                            <th>Truck No.</th>
                            <th>Container No.</th>
                            <th>Desc</th>
                        </tr>
                    </thead>
                    <tbody id="dcTableBody"></tbody>
                </table>
            </div>
            <small id="qty_validation_message" class="text-danger d-none">Total line item quantity couldn't exceed the second weighbridge quantity.</small>
        </div>
    </div>

    <div class="row bottom-button-bar">
        <div class="col-12 text-end">
            <a type="button" class="btn btn-danger modal-sidebar-close closebutton me-2">Close</a>
            <button type="submit" class="btn btn-primary submitbutton">Save</button>
        </div>
    </div>
</form>

<script>
    window.syncQtyFromBags = function (el) {
        const row = $(el).closest('tr');
        const form = $(el).closest('form');
        const bagSize = parseFloat(row.find('.bag_size').val()) || 0;
        const bags = parseFloat($(el).val()) || 0;
        const qty = bagSize > 0 ? ((bags * bagSize) / 1000) : 0;
        row.find('.qty').val(qty.toFixed(3));
        window.updateExportDcLineAmount(row);
        window.recalculateExportDcTotals(form);
    };

    window.syncBagsFromQty = function (el) {
        const row = $(el).closest('tr');
        const form = $(el).closest('form');
        window.updateExportDcLineAmount(row);
        window.recalculateExportDcTotals(form);
    };

    window.updateExportDcLineAmount = function (row) {
        const qty = parseFloat(row.find('.qty').val()) || 0;
        const rate = parseFloat(row.find('.rate').val()) || 0;
        row.find('.amount').val((qty * rate).toFixed(2));
    };

    window.recalculateExportDcTotals = function (form) {
        const $form = $(form);
        let totalQty = 0;
        let totalBags = 0;

        $form.find('#dcTableBody .qty').each(function () {
            totalQty += parseFloat($(this).val()) || 0;
        });
        $form.find('#dcTableBody .no_of_bags').each(function () {
            totalBags += parseFloat($(this).val()) || 0;
        });

        const currentSecondWeighbridgeQtyMt = parseFloat($form.data('second-weighbridge-qty') || 0);
        const remaining = currentSecondWeighbridgeQtyMt - totalQty;

        $form.find('#line_items_total_qty_mt').text(totalQty.toFixed(3));
        $form.find('#remaining_qty_mt').text(Math.max(remaining, 0).toFixed(3));

        const labourRate = parseFloat($form.find('#standard_labour_rate').val()) || 0;
        $form.find('#labour_amount').val((labourRate * totalBags).toFixed(2));

        const hasError = totalQty > currentSecondWeighbridgeQtyMt + 0.001;
        $form.find('#qty_validation_message').toggleClass('d-none', !hasError);
        $form.find('.submitbutton').prop('disabled', hasError);
    };

    (function () {
        const $form = $('form#ajaxSubmit').last();
        if (!$form.length) {
            return;
        }

        function $f(selector) {
            return $form.find(selector);
        }

        function initialize() {
            $f('.select2').each(function () {
                if ($(this).hasClass('select2-hidden-accessible')) {
                    $(this).select2('destroy');
                }
            });
            $f('.select2').select2({ width: '100%' });
            loadTicketsWithDispatchQc();
            getNumber();

            $f('#initial_ticket_id').off('change').on('change', function () {
                const ticketId = $(this).val();
                if (!ticketId) {
                    resetForm();
                    return;
                }
                loadTicketData(ticketId);
            });
        }

        function loadTicketsWithDispatchQc() {
            $.get("{{ route('export-delivery-challan.get-tickets-with-dispatch-qc') }}", function (response) {
                const select = $f('#initial_ticket_id');
                select.empty().append('<option value=\"\">Select Ticket</option>');
                (response.tickets || []).forEach(function (ticket) {
                    select.append(`<option value="${ticket.id}">${ticket.text}</option>`);
                });
                select.trigger('change.select2');
            });
        }

        function loadTicketData(ticketId) {
            $.ajax({
                url: "{{ route('export-delivery-challan.get-ticket-data') }}",
                method: 'GET',
                data: { ticket_id: ticketId },
                dataType: 'json',
                success: function (response) {
                    if (!response.success) {
                        return;
                    }

                    $form.data('second-weighbridge-qty', parseFloat(response.second_weighbridge_qty_mt || 0));
                    $f('#second_weighbridge_qty_mt').text(parseFloat(response.second_weighbridge_qty_mt || 0).toFixed(3));
                    $f('#customer_id_display').val(response.customer.id).trigger('change');
                    $f('#customer_id').val(response.customer.id);
                    $f('#delivery_order_id').val(response.delivery_order.id);
                    $f('#labour_status').val(response.loading_slip_labour || 'paid').trigger('change');
                    $f('#labour_status_hidden').val(response.loading_slip_labour || 'paid');
                    $f('#standard_labour_rate').val(response.rate || 'N/A');

                    if (response.transporter_id) {
                        $f('#transporter').val(response.transporter_id).trigger('change');
                    }

                    $f('#do_no').empty().append(`<option value="${response.delivery_order.id}" selected>${response.delivery_order.reference_no}</option>`).trigger('change');
                    setReadonlyMultiSelect('#locations', response.locations.company_locations || []);
                    setReadonlyMultiSelect('#arrivals', response.locations.arrival_locations || []);
                    setReadonlyMultiSelect('#storages', response.locations.sub_arrival_locations || []);
                    setHiddenInputs('locations', response.locations.company_location_ids || []);
                    $f('#arrival_location_csv').val((response.locations.arrival_location_ids || []).join(','));
                    $f('#storage_location_csv').val((response.locations.sub_arrival_location_ids || []).join(','));

                    loadTicketItems(ticketId);
                }
            });
        }

        function loadTicketItems(ticketId) {
            $.ajax({
                url: "{{ route('export-delivery-challan.get-ticket-items') }}",
                method: 'GET',
                data: { ticket_id: ticketId, delivery_challan_id: null },
                dataType: 'html',
                success: function (html) {
                    $f('#dcTableBody').html(html);
                    window.recalculateExportDcTotals($form[0]);
                }
            });
        }

        function setReadonlyMultiSelect(selector, options) {
            const select = $f(selector);
            select.empty();
            options.forEach(function (option) {
                select.append(`<option value="${option.id}" selected>${option.text}</option>`);
            });
            select.trigger('change');
        }

        function setHiddenInputs(name, values) {
            const container = $f('#' + name + '_hidden');
            container.empty();
            (values || []).forEach(function (value) {
                container.append(`<input type="hidden" name="${name}[]" value="${value}">`);
            });
        }

        function resetForm() {
            $form.data('second-weighbridge-qty', 0);
            $f('#customer_id_display, #do_no, #locations, #arrivals, #storages').val(null).trigger('change');
            $f('#customer_id, #delivery_order_id, #arrival_location_csv, #storage_location_csv').val('');
            $f('#labour_status_hidden').val('paid');
            $f('#dcTableBody').empty();
            $f('#second_weighbridge_qty_mt, #line_items_total_qty_mt, #remaining_qty_mt').text('0.000');
            $f('#standard_labour_rate, #labour_amount').val('');
            $f('#reference_number').val('');
            $f('.submitbutton').prop('disabled', false);
            $f('#qty_validation_message').addClass('d-none');
        }

        function getNumber() {
            $.get("{{ route('get.export-delivery-challan.getNumber') }}", { contract_date: $f('#date').val() }, function (response) {
                $f('#dc_no').val(response.dc_no);
            });
        }

        initialize();
    })();
</script>
