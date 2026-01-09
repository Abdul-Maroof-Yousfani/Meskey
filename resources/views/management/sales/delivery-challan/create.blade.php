<style>
    html,
    body {
        overflow-x: hidden;
    }

    .amount-info-box {
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 15px;
        background-color: #f8f9fa;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .amount-info-box .form-group {
        margin-bottom: 10px;
    }

    .amount-info-box .form-group:last-child {
        margin-bottom: 0;
    }

    .amount-info-box .form-label {
        font-weight: 600;
        font-size: 13px;
    }
</style>

<form action="{{ route('sales.delivery-challan.store') }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf

    <input type="hidden" id="listRefresh" value="{{ route('sales.get.delivery-challan.list') }}" />

    <div class="row form-mar">
        <!-- Left side fields (2 columns) -->
        <div class="col-md-12">
            <!-- Row 1: Dispatch Date, Do No -->

            <div class="row" style="margin-top: 10px">
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">DC NO:</label>
                        <input type="text" name="dc_no" id="dc_no" id="text" class="form-control" readonly>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">Date:</label>
                        <input 
                            type="date" 
                            name="date" 
                            onchange="getNumber()" 
                            id="date" 
                            class="form-control"
                            value="{{ date('Y-m-d') }}"
                            readonly
                        >
                    </div>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Contract Types:</label>
                    <select name="sauda_type" id="sauda_type" class="form-control select2">
                        <option value="">Select Contract type</option>
                        <option value="pohanch">Pohanch</option>
                        <option value="x-mill">X-mill</option>
                    </select>
                </div>
                {{-- <div class="col-md-4">
                    <label class="form-label">Customer:</label>
                    <select name="customer_id" id="customer_id" onchange="get_delivery_orders()"
                        class="form-control select2">
                        <option value="">Select Customer</option>
                        @foreach ($customers ?? [] as $customer)
                            <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                        @endforeach
                    </select>
                </div> --}}

            </div>

            <div class="row">
                <div class="col-md-4">
                    <label class="form-label">Select Ticket: <span class="text-danger">*</span></label>
                    <select name="initial_ticket_id" id="initial_ticket_id" onchange="onInitialTicketSelect(this)"
                        class="form-control select2">
                        <option value="">Select Ticket</option>
                    </select>
                </div>
                

                <div class="col-md-4">
                    <label class="form-label">Customer:</label>
                    <select id="customer_id_display" 
                        class="form-control select2" disabled>
                        <option value="">Select Customer</option>
                        @foreach ($customers ?? [] as $customer)
                            <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                        @endforeach
                    </select>
                    <input type="hidden" name="customer_id" id="customer_id">
                </div>

                <div class="col-md-4">
                    <label class="form-label">DO Number:</label>
                    <select name="do_no[]" id="do_no" onchange="get_items(this)" class="form-control select2" disabled>
                        <option value="">Select Delivery Order</option>
                    </select>
                </div>

                <input type='hidden' name="delivery_order_id" id="delivery_order_id" />
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Reference Number:</label>
                        <input type="text" name="reference_number" id="reference_number" class="form-control" disabled>
                    </div>
                </div>
                
          
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Ticket Labour:</label>
                        <input type="text" id="ticket_labour" class="form-control" readonly placeholder="Select a ticket first">
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <label class="form-label">Locations:</label>
                    <select name="locations[]" id="locations" class="form-control select2" multiple disabled>
                        <option value="">Select Locations</option>
                    </select>
                    <div id="locations_hidden">
                    </div>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Factory:</label>
                    <select name="arrival_locations[]" id="arrivals" class="form-control select2" multiple disabled>
                        <option value="">Select Factory</option>
                    </select>
                    <div id="arrivals_hidden">
                        <div id="storages_hidden">
                            <input type="hidden" name="arrival_location_csv" id="arrival_location_csv" />
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Gala:</label>
                    <select name="storage_id[]" id="storages" class="form-control select2" multiple disabled>
                        <option value="">Select Gala</option>
                    </select>
                    <div id="storages_hidden">
                        <input type="hidden" name="storage_location_csv" id="storage_location_csv" />
                    </div>
                </div>
            </div>

            <div class="row">
             

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">Labour:</label>
                        <select name="labour" id="labour" onchange="" class="form-control select2">
                            <option value="">Select Labours</option>
                            <option value="1">Labour 1</option>
                            <option value="2">Labour 2</option>
                        </select>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">Transporter:</label>
                        <select name="transporter" id="transporter" onchange="" class="form-control select2">
                            <option value="">Select Transporter</option>
                            <option value="1">Transporter 1</option>
                            <option value="2">Transporter 2</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">In-house Weighbridge:</label>
                        <select name="weighbridge" id="weighbridge" onchange="" class="form-control select2">
                            <option value="">Select Weighbridge</option>
                            <option value="1">Weighbridge 1</option>
                            <option value="2">Weighbridge 2</option>
                        </select>
                    </div>
                </div>
            </div>

           

            <div class="row">
                
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">Labour Amount:</label>
                        <input type="number" name="labour_amount" onchange="" id="labour_amount"
                            class="form-control">
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">Transporter Amount:</label>
                        <input type="number" name="transporter_amount" onchange="" id="transporter_amount"
                            class="form-control">
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">Weighbridge Amount:</label>
                        <input type="number" name="weighbridge_amount" onchange="" id="weighbridge_amount"
                            class="form-control">
                    </div>
                </div>
                {{-- <div class="col-md-4">
                    <label class="form-label">Sauda Types:</label>
                    <select name="sauda_type" id="sauda_type" class="form-control select2">
            <div class="row">
                <div class="col-md-4">
                    <label class="form-label">Labour:</label>
                    <select name="labour" id="labour" onchange="" class="form-control select2">
                        <option value="">Select Labours</option>
                    </select>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <label class="form-label">Labour Amount:</label>
                    <input type="number" name="labour_amount" onchange="" id="labour_amount" class="form-control">
                </div>
            </div>

                        <option value="pohanch">Pohanch</option>
                        <option value="x-mill">X-mill</option>
                    </select>
                </div> --}}
            </div>

            <div class="row">
                
              
                {{-- <div class="col-md-4">
                    <label class="form-label">DO Numbers:</label>
                    <select name="do_no[]" id="do_no" onchange="get_items(this)" class="form-control select2"
                        multiple>
                        <option value="">Select Delivery Orders</option>

                    </select>
                </div> --}}
            </div>

            <div class="row">
                
                
                <div class="col-md-12">
                    <label class="form-label">Remarks:</label>
                    <textarea name="remarks" id="remarks" class="form-control"></textarea>
                </div>
            </div>
        </div>


    </div>



    <!-- Row 3: Customer, Contract Terms, Locations -->






    </div>

    <div class="row form-mar">

        <div class="col-12 text-right mb-2">
            <button type="button" style="float: right" class="btn btn-sm btn-primary" onclick="addRow()"
                id="addRowBtn" disabled>
                <i class="fa fa-plus"></i>&nbsp; Add New Item
            </button>
        </div>

        <div class="col-md-12">
            <div class="table-responsive" style="overflow-x: auto; white-space: nowrap;">
                <table class="table table-bordered" id="salesInquiryTable" style="min-width:2000px;">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Bag Type</th>
                            <th>Packing</th>
                            <th>No of Bags</th>
                            <th>Quantity (kg)</th>
                            <th>Rate per Kg</th>
                            <th>Amount</th>
                            <th>Brand</th>
                            <th>Truck No.</th>
                            <th>Bilty No.</th>
                            <th>Desc</th>
                            <th style="display: none">Packing</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="dcTableBody">

                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <input type="hidden" id="rowCount" value="0">

    <div class="row bottom-button-bar">
        <div class="col-12 text-end">
            <a type="button"
                class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton me-2">Close</a>
            <button type="submit" class="btn btn-primary submitbutton">Save</button>
        </div>
    </div>
</form>

<script>
    salesInquiryRowIndex = 1;

    $(document).ready(function() {
        $('.select2').select2();
        
        // Load tickets with accepted Dispatch QC on page load
        loadTicketsWithDispatchQc();
        getNumber();
    });

    // Load tickets with accepted Dispatch QC
    function loadTicketsWithDispatchQc() {
        $.ajax({
            url: "{{ route('sales.delivery-challan.get-tickets-with-dispatch-qc') }}",
            method: "GET",
            dataType: "json",
            success: function(response) {
                const select = $("#initial_ticket_id");
                select.empty().append('<option value="">Select Ticket</option>');
                
                if (response.tickets && response.tickets.length > 0) {
                    response.tickets.forEach(function(ticket) {
                        select.append(`<option value="${ticket.id}">${ticket.text}</option>`);
                    });
                }
                
                select.select2();
            },
            error: function(error) {
                console.error('Error loading tickets:', error);
            }
        });
    }

    // Handle initial ticket selection - auto-fill form fields
    function onInitialTicketSelect(el) {
        const ticketId = $(el).val();
        
        if (!ticketId) {
            // Clear all fields
            resetFormFields();
            return;
        }

        $.ajax({
            url: "{{ route('sales.delivery-challan.get-ticket-data') }}",
            method: "GET",
            data: { ticket_id: ticketId },
            dataType: "json",
            beforeSend: function() {
                Swal.fire({
                    title: "Loading...",
                    text: "Fetching ticket data",
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
            },
            success: function(response) {
                Swal.close();
                
                if (response.success) {
                    // Set Ticket Labour (readonly)
                    if (response.loading_slip_labour) {
                        const labourDisplay = response.loading_slip_labour === 'paid' ? 'Paid' : 'Not Paid';
                        $("#ticket_labour").val(labourDisplay);
                    } else {
                        $("#ticket_labour").val('N/A');
                    }

                    // Set Contract Type (readonly)
                    $("#sauda_type").val(response.delivery_order.sauda_type).trigger('change');
                    $("#delivery_order_id").val(response.delivery_order.id);
                    $("#sauda_type").prop('disabled', true);
                    // Add hidden field for sauda_type so it gets submitted
                    if (!$("#sauda_type_hidden").length) {
                        $("#sauda_type").after('<input type="hidden" name="sauda_type" id="sauda_type_hidden">');
                    }
                    $("#sauda_type_hidden").val(response.delivery_order.sauda_type);

                    // Set Customer (readonly)
                    $("#customer_id_display").val(response.customer.id).trigger('change');
                    $("#customer_id").val(response.customer.id);

                    // Set DO Number (readonly)
                    const doSelect = $("#do_no");
                    doSelect.empty().append('<option value="">Select Delivery Order</option>');
                    doSelect.append(`<option value="${response.delivery_order.id}" selected>${response.delivery_order.reference_no}</option>`);
                    doSelect.trigger('change');

                    // Enable Reference Number field (don't auto-populate, leave it editable)
                    $("#reference_number").prop('disabled', false);

                    // Set Locations (readonly)
                    const locSelect = $("#locations");
                    locSelect.empty();
                    response.locations.company_locations.forEach(loc => {
                        locSelect.append(`<option value="${loc.id}" selected>${loc.text}</option>`);
                    });
                    locSelect.trigger('change');
                    setHidden("locations", response.locations.company_location_ids);

                    // Set Factory (readonly)
                    const arrSelect = $("#arrivals");
                    arrSelect.empty();
                    response.locations.arrival_locations.forEach(loc => {
                        arrSelect.append(`<option value="${loc.id}" selected>${loc.text}</option>`);
                    });
                    arrSelect.trigger('change');
                    setHidden("arrival_locations", response.locations.arrival_location_ids);
                    $("#arrival_location_csv").val(response.locations.arrival_location_ids.join(','));

                    // Set Gala (readonly)
                    const secSelect = $("#storages");
                    secSelect.empty();
                    response.locations.sub_arrival_locations.forEach(loc => {
                        secSelect.append(`<option value="${loc.id}" selected>${loc.text}</option>`);
                    });
                    secSelect.trigger('change');
                    setHidden("storage_id", response.locations.sub_arrival_location_ids);
                    $("#storage_location_csv").val(response.locations.sub_arrival_location_ids.join(','));

                    // Store doMeta for the selected DO
                    doMeta[response.delivery_order.id] = {
                        location_id: response.locations.company_location_ids[0] || null,
                        arrival_location_id: response.locations.arrival_location_ids.join(','),
                        sub_arrival_location_id: response.locations.sub_arrival_location_ids.join(','),
                        location_name: response.locations.company_locations[0]?.text || "",
                        arrival_names: Object.fromEntries(response.locations.arrival_locations.map(l => [l.id, l.text])),
                        section_names: Object.fromEntries(response.locations.sub_arrival_locations.map(l => [l.id, l.text])),
                    };

                    // Trigger get_items to load the ticket item row
                    $.ajax({
                        url: "{{ route('sales.delivery-challan.get-ticket-items') }}",
                        method: "GET",
                        data: { ticket_id: ticketId },
                        dataType: "html",
                        success: function(res) {
                            $("#dcTableBody").empty();
                            $("#dcTableBody").append(res);
                            $(".select2").select2();
                            
                            // Track added ticket IDs
                            addedTicketIds = [parseInt(ticketId)];
                            
                            // Load additional tickets for the same customer/DO
                            loadAdditionalTickets(response.delivery_order.id, ticketId);
                        },
                        error: function(error) {
                            console.error('Error loading ticket items:', error);
                        }
                    });
                }
            },
            error: function(error) {
                Swal.close();
                Swal.fire("Error", "Failed to load ticket data", "error");
                console.error('Error fetching ticket data:', error);
            }
        });
    }

    function resetFormFields() {
        $("#sauda_type").val('').trigger('change').prop('disabled', false);
        $("#customer_id_display").val('').trigger('change');
        $("#customer_id").val('');
        $("#do_no").empty().append('<option value="">Select Delivery Order</option>').trigger('change');
        $("#reference_number").val('').prop('disabled', true);
        $("#locations").empty().trigger('change');
        $("#arrivals").empty().trigger('change');
        $("#storages").empty().trigger('change');
        $("#dcTableBody").empty();
        $("#addTicketContainer").hide();
        $("#add_ticket_id").empty().append('<option value="">Select Ticket to Add</option>');
        $("#ticket_labour").val('');
        addedTicketIds = [];
        doMeta = {};
    }
    
    // Track which tickets have been added
    addedTicketIds = [];
    
    // Load additional tickets for the same delivery order
    function loadAdditionalTickets(deliveryOrderId, excludeTicketId) {
        $.ajax({
            url: "{{ route('sales.delivery-challan.get-tickets') }}",
            method: "GET",
            data: {
                delivery_order_ids: [deliveryOrderId]
            },
            success: function(response) {
                const select = $("#add_ticket_id");
                select.empty().append('<option value="">Select Ticket to Add</option>');
                
                if (response.tickets && response.tickets.length > 0) {
                    let hasOptions = false;
                    response.tickets.forEach(function(ticket) {
                        // Exclude already added tickets
                        if (!addedTicketIds.includes(ticket.id)) {
                            select.append(`<option value="${ticket.id}">${ticket.text}</option>`);
                            hasOptions = true;
                        }
                    });
                    
                    if (hasOptions) {
                        $("#addTicketContainer").show();
                    } else {
                        $("#addTicketContainer").hide();
                    }
                }
                
                select.select2();
            },
            error: function(error) {
                console.error('Error loading additional tickets:', error);
            }
        });
    }
    
    // Handle adding more tickets
    $("#initial_ticket_id").on("change", function() {
        const ticketId = $(this).val();
        
        if (!ticketId) return;
        
        // Check if already added
        if (addedTicketIds.includes(parseInt(ticketId))) {
            Swal.fire("Warning", "This ticket has already been added", "warning");
            $(this).val('').trigger('change');
            return;
        }
        
        // Load the ticket item row and append to table
        $.ajax({
            url: "{{ route('sales.delivery-challan.get-ticket-items') }}",
            method: "GET",
            data: { ticket_id: ticketId },
            dataType: "html",
            success: function(res) {
                console.log(res);
                $("#dcTableBody").append(res);
                $(".select2").select2();
                
                // Track this ticket as added
                addedTicketIds.push(parseInt(ticketId));
                
                // Remove this ticket from the dropdown
                $("#add_ticket_id option[value='" + ticketId + "']").remove();
                $("#add_ticket_id").val('').trigger('change');
                
                // Hide the dropdown if no more tickets available
                if ($("#add_ticket_id option").length <= 1) {
                    $("#addTicketContainer").hide();
                }
            },
            error: function(error) {
                console.error('Error loading ticket items:', error);
                Swal.fire("Error", "Failed to load ticket data", "error");
            }
        });
    });
    
    // Remove ticket row from table
    function removeTicketRow(btn) {
        const ticketId = $(btn).data('ticket-id');
        const ticketText = $(btn).data('ticket-text');
        
        // Check if this is the last row
        if ($("#dcTableBody tr").length <= 1) {
            Swal.fire("Warning", "Cannot remove the last ticket. At least one ticket is required.", "warning");
            return;
        }
        
        // Remove from table
        $(btn).closest('tr').remove();
        
        // Remove from tracked IDs
        addedTicketIds = addedTicketIds.filter(id => id !== parseInt(ticketId));
        
        // Add back to dropdown
        const select = $("#add_ticket_id");
        select.append(`<option value="${ticketId}">${ticketText}</option>`);
        select.select2();
        
        // Show the dropdown if it was hidden
        $("#addTicketContainer").show();
    }

    sum = 0;
    so_amount = 0;
    remaining_amount = 0;
    doMeta = {};

    function setHidden(name, values) {
        const container = $(`#${name}_hidden`);
        container.empty();
        (values || []).forEach(v => {
            container.append(`<input type="hidden" name="${name}[]" value="${v}">`);
        });
    }

    function hydrateLocationsFromDos(selectedIds) {
        const locSet = new Set();
        const arrSet = new Set();
        const secSet = new Set();
        const locOptions = [];
        const arrOptions = [];
        const secOptions = [];
        
        (selectedIds || []).forEach(id => {
            const meta = doMeta[id];
            console.log(meta);
            if (!meta) return;
            
            // Handle location
            if (meta.location_id && !locSet.has(meta.location_id)) {
                locSet.add(meta.location_id);
                locOptions.push({ id: meta.location_id, text: meta.location_name || meta.location_id });
            }
            
            // Handle arrival locations (factories) - now using arrival_names object
            $("#arrival_location_csv").val(meta.arrival_location_id || "");
            if (meta.arrival_location_id) {
                const arrivalIds = meta.arrival_location_id.split(",");
                arrivalIds.forEach(function(arrival_location_id) {
                    arrival_location_id = arrival_location_id.trim();
                    if (arrival_location_id && !arrSet.has(arrival_location_id)) {
                        arrSet.add(arrival_location_id);
                        // Get name from arrival_names object, fallback to ID
                        const name = (meta.arrival_names && meta.arrival_names[arrival_location_id]) || arrival_location_id;
                        arrOptions.push({ id: arrival_location_id, text: name });
                    }
                });
            }

            // Handle sub arrival locations (sections) - now using section_names object
            $("#storage_location_csv").val(meta.sub_arrival_location_id || "");
            if (meta.sub_arrival_location_id) {
                const sectionIds = meta.sub_arrival_location_id.split(",");
                sectionIds.forEach(function(section_id) {
                    section_id = section_id.trim();
                    if (section_id && !secSet.has(section_id)) {
                        secSet.add(section_id);
                        // Get name from section_names object, fallback to ID
                        const name = (meta.section_names && meta.section_names[section_id]) || section_id;
                        secOptions.push({ id: section_id, text: name });
                    }
                });
            }
        });

        // Locations (readonly)
        const locSelect = $("#locations");
        locSelect.empty().append(`<option value=''>Select Locations</option>`);
        locOptions.forEach(o => locSelect.append(`<option value="${o.id}" selected>${o.text}</option>`));
        locSelect.prop("disabled", true).select2();
        setHidden("locations", Array.from(locSet));

        // Factories (readonly)
        const arrSelect = $("#arrivals");
        arrSelect.empty().append(`<option value=''>Select Factory</option>`);
        arrOptions.forEach(o => arrSelect.append(`<option value="${o.id}" selected>${o.text}</option>`));
        arrSelect.prop("disabled", true).select2();
        setHidden("arrival_locations", Array.from(arrSet));

        // Sections (readonly)
        const secSelect = $("#storages");
        secSelect.empty().append(`<option value=''>Select Section</option>`);
        secOptions.forEach(o => secSelect.append(`<option value="${o.id}" selected>${o.text}</option>`));
        secSelect.prop("disabled", true).select2();
        setHidden("storage_id", Array.from(secSet));
    }

    function check_so_type() {
        const type = $("#sale_order").find("option:selected").data("type");
        if (type == 8) {
            $(".advanced").css("display", "block");
        } else {
            $(".advanced").css("display", "none");
        }
    }

    function selectLocation(el) {
        const company = $(el).val();

        // reset downstream selects
        $("#arrivals").prop("disabled", true).empty().append(`<option value=''>Select Factory</option>`);
        $("#storages").prop("disabled", true).empty().append(`<option value=''>Select Section</option>`);

        if (!company) {
            return;
        }

        $.ajax({
            url: "{{ route('sales.get.arrival-locations') }}",
            method: "GET",
            data: {
                location_id: company
            },
            dataType: "json",
            success: function(res) {
                $("#arrivals").empty();
                $("#arrivals").append(`<option value=''>Select Factory</option>`)

                res.forEach(delivery_order => {
                    $("#arrivals").append(`
                        <option value="${delivery_order.id}" >
                            ${delivery_order.text}
                        </option>
                    `);
                });

                $("#arrivals").prop("disabled", false).select2();
            },
            error: function(error) {

            }
        });
    }


    function get_items(el) {
        // get.delivery-challan.get-items
        const delivery_orders = $(el).val();
      
        // Update readonly multi-selects for location/factory/section
        hydrateLocationsFromDos([delivery_orders]);
    }

    function get_delivery_orders() {

        const customer_id = $("#customer_id").val();

        if (!customer_id) {
            $("#do_no").empty().append(`<option value=''>Select Delivery Order</option>`);
            return;
        }

        $.ajax({
            url: "{{ route('sales.get.delivery-challan.get-do') }}",
            method: "GET",
            data: {
                customer_id: customer_id
            },
            dataType: "json",
            success: function(res) {
                $("#do_no").empty();
                $("#do_no").append(`<option value=''>Select Delivery Order</option>`)

                doMeta = {};

                res.forEach(delivery_order => {
                    $("#do_no").append(`
                    <option value="${delivery_order.id}" >
                        ${delivery_order.text}
                    </option>
                `);

                    doMeta[delivery_order.id] = {
                        location_id: delivery_order.location_id || null,
                        arrival_location_id: delivery_order.arrival_location_id || null,
                        sub_arrival_location_id: delivery_order.sub_arrival_location_id || null,
                        location_name: delivery_order.location_name || "",
                        arrival_names: delivery_order.arrival_names || {}, // Object with id => name
                        section_names: delivery_order.section_names || {}, // Object with id => name
                    };
                });

                $("#do_no").select2();
            },
            error: function(error) {

            }
        });
    }

    function selectStorage(el) {
        const arrival = $(el).val();
        console.log(arrival);
        if (!arrival) {
            $("#storages").prop("disabled", true);
            $("#storages").empty();
            return;
        } else {
            // get.arrival-locations; send request to this url
            $("#storages").prop("disabled", false);
            $.ajax({
                url: "{{ route('sales.get.storage-locations') }}",
                method: "GET",
                data: {
                    arrival_id: arrival
                },
                dataType: "json",
                success: function(res) {
                    console.log(res);
                    $("#storages").empty();
                    $("#storages").append(`<option value=''>Select Storage</option>`)
                    res.forEach(loc => {
                        $("#storages").append(`
                        <option value="${loc.id}">
                            ${loc.text}
                        </option>
                    `);
                    });

                    $("#storages").select2();
                },
                error: function(error) {

                }
            });
        }
    }

    function add_advance_amount() {
        let selectedAmounts = $("#receipt_vouchers option:selected")
            .map(function() {
                return $(this).data("amount");
            }).get();


        sum = 0;
        selectedAmounts.forEach(selectedAmount => {
            sum += parseFloat(selectedAmount);
        });



        if (sum > 0) {
            $("#advance_amount").val(sum.toFixed(2));
        } else {
            $("#advance_amount").val("");
        }

    }

    function change_withhold_amount() {
        remaining_amount = parseFloat($("#advance_amount").val() ?? 0) - parseFloat($("#withhold_amount").val() ?? 0);
        rate = $("#rate_0").val();
        $("#qty_0").val((remaining_amount / rate).toFixed(2));

        $("withhold_for_rv").val("").trigger("change");
        $('#withhold_for_rv').select2({
            templateResult: function(data) {

                if (!data.id) return data.text;

                let amount = $(data.element).data('amount');


                if (parseFloat($("#withhold_amount").val()) > parseFloat(amount)) {
                    return null; // Hides this option
                }

                let $item = $(`
                    <span>
                        ${data.text}
                        <strong style="color: green; margin-left: 6px;">(${amount})</strong>
                    </span>
                `);

                return $item;
            }
        });

    }

    function addRow() {
        let index = salesInquiryRowIndex++;
        let row = `
        <tr id="row_${index}">
            <td>
                <select name="item_id[]" id="item_id_${index}" class="form-control select2">
                    <option value="">Select Item</option>
                    @foreach ($items ?? [] as $item)
                        <option value="{{ $item->id }}">{{ $item->name }}</option>
                    @endforeach
                </select>
            </td>
            <td>
                <input type="number" name="qty[]" id="qty_${index}" onkeyup="calc(this)" class="form-control qty" step="0.01" min="0">
            </td>
            <td>
                <input type="number" name="rate[]" id="rate_${index}" onkeyup="calc(this)" class="form-control rate" step="0.01" min="0">
            </td>
            <td>
                <input type="text" name="amount[]" id="amount_${index}" class="form-control amount" readonly>
            </td>
            <td>
                <button type="button" class="btn btn-danger btn-sm removeRowBtn" onclick="removeRow(${index})" style="width:60px;">
                    <i class="fa fa-trash"></i>
                </button>
            </td>
        </tr>
    `;
        $('#salesInquiryBody').append(row);
        $(`#item_id_${index}`).select2();
        $('#row_0 .removeRowBtn').prop('disabled', true);
        $('.removeRowBtn').not('#row_0 .removeRowBtn').prop('disabled', false);
    }

    function removeRow(index) {
        $('#row_' + index).remove();
        if ($('#salesInquiryBody tr').length === 1) {
            $('#row_0 .removeRowBtn').prop('disabled', true);
        }
    }

    function calc(el) {
        const element = $(el).closest("tr");

        const rate = parseFloat($(element).find(".rate").val()) || 0;
        const qty = parseFloat($(element).find(".qty").val()) || 0;

        const amount = $(element).find(".amount");

        amount.val(rate * qty);
    }

    function get_sale_orders() {
        const customer_id = $("#customer_id").val();
        // get-sale-inquiries-against-customer

        $.ajax({
            url: "{{ route('sales.get.delivery-order.getSoAgainstCustomer') }}",
            method: "GET",
            data: {
                customer_id: customer_id
            },
            dataType: "json",
            success: function(res) {
                $("#sale_order").empty();

                res.forEach(item => {
                    $("#sale_order").append(`
                        <option value="${item.id}" 
                                data-type="${item.type}">
                            ${item.text}
                        </option>
                    `);
                });

                $("#sale_order").select2();
            },
            error: function(error) {

            }
        });

        // get-sale-inquiry-data
    }

    function get_inquiry_data() {
        const inquiry_id = $("#inquiry_id").val();

        $.ajax({
            url: "{{ route('sales.get-sale-inquiry-data') }}",
            method: "GET",
            data: {
                inquiry_id: inquiry_id
            },
            dataType: "html",
            success: function(res) {
                console.log("success");
                $("#alesInquiryBody").empty();
                $("#salesInquiryBody").html(res);
            },
            error: function(error) {
                console.log(error);
            }
        });

    }

    function getNumber() {
        $.ajax({
            url: "{{ route('sales.get.delivery-challan.getNumber') }}",
            method: "GET",
            data: {
                contract_date: $("#date").val()
            },
            dataType: "json",
            success: function(res) {
                $("#dc_no").val(res.dc_no)
            },
            error: function(error) {
                // Handle errors here
                $('.loader-container').hide();
                console.error("Error:", error);
            }
        });
    }


    function calculate_percentage(el) {
        const percentage = parseFloat($(el).val()) || 0;
        const unused_amount = $("#unused_amount").val();
        const err_message = $(".advance-amount-err-message");

        if (!percentage) {
            $("#advance_amount").val("");
            $("#advance_amount").prop("disabled", false);
            return;
        }
        const so_amount = parseFloat($("#so_amount").val()) || 0;


        const result = (so_amount * percentage) / 100;

        if (result > unused_amount) {
            $(".submitbutton").prop("disabled", true);
            $("#advance_amount").addClass("is-invalid");
            err_message.css("display", "block");
        } else {
            $(".submitbutton").prop("disabled", false);
            $("#advance_amount").removeClass("is-invalid");
            err_message.css("display", "none");
        }

        $("#advance_amount").prop("disabled", true);
        $("#advance_amount").val(result);

    }

    function manualChecking() {
        const advance_amount = $("#advance_amount").val();
        const unused_amount = $("#unused_amount").val();
        const err_message = $(".advance-amount-err-message");

        if (parseFloat(advance_amount) > parseFloat(unused_amount)) {
            $(".submitbutton").prop("disabled", true);
            $("#advance_amount").addClass("is-invalid");
            err_message.css("display", "block");
        } else {
            $(".submitbutton").prop("disabled", false);
            $("#advance_amount").removeClass("is-invalid");
            err_message.css("display", "none");
        }
    }


    function get_so_detail() {
        $.ajax({
            url: "{{ route('sales.get.delivery-order.details') }}",
            method: "GET",
            data: {
                so_id: $("#sale_order").val(),
            },
            dataType: "json",
            success: function(res) {
                // $("#amount_received").val(res.amount_received)
                // $("#so_amount").val(res.so_amount)
                // $("#unused_amount").val(res.unused_amount)

                // $("#sauda_type").val(res.sauda_type)
                // $("#sauda_type").trigger("change");

                // $("#payment_term_id").val(res.payment_term_id);
                // $("#payment_term_id").trigger("change");

                so_amount = res.so_amount;

                // $("#locations").val(res.locations).trigger("change");
            },
            error: function(error) {
                // Handle errors here
                $('.loader-container').hide();
                console.error("Error:", error);
            }
        });
    }

    // get.delivery-order.getRvAgainstSo

    function get_receipt_vouchers() {
        $.ajax({
            url: "{{ route('sales.get.delivery-order.getRvAgainstSo') }}",
            method: "GET",
            data: {
                customer_id: $("#customer_id").val(),
            },
            dataType: "json",
            success: function(res) {
                // withhold_for_rv

                let select = $("#receipt_vouchers");
                select.empty();
                select.append(
                    `<option value='' data-amount="0">Select Receipt Voucher</option>`
                );

                res.forEach(item => {
                    select.append(
                        `<option value="${item.id}"
                                data-amount="${item.amount}">
                            ${item.text}
                        </option>`
                    );
                });

                select.select2();


                select = $("#withhold_for_rv");
                select.empty();

                select.append(
                    `<option value='' data-amount="0">Select Receipt Voucher</option>`
                );
                res.forEach(item => {
                    select.append(
                        `<option value="${item.id}"
                                data-amount="${item.amount}">
                            ${item.text}
                        </option>`
                    );
                });

                select.select2();


            },
            error: function(error) {
                // Handle errors here
                $('.loader-container').hide();
                console.error("Error:", error);
            }
        });
    }

    function get_so_items() {

        $('#soTableBody').empty();
        $.ajax({
            url: "{{ route('sales.get.delivery-order.getSoItems') }}",
            method: "GET",
            data: {
                so_id: $("#sale_order").val(),
            },
            dataType: "html",
            success: function(res) {
                $('#soTableBody').empty();

                $('#soTableBody').html(res);

            },
            error: function(error) {
                // Handle errors here
                $('.loader-container').hide();
                console.error("Error:", error);
            }
        });
    }
</script>
