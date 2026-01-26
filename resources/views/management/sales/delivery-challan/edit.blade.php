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

<form action="{{ route('sales.delivery-challan.update', [ 'delivery_challan' => $delivery_challan->id ]) }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    {{ method_field("PUT") }}
    <input type="hidden" id="listRefresh" value="{{ route('sales.get.delivery-challan.list') }}" />

    <div class="row form-mar">
        <!-- Left side fields (2 columns) -->
        <div class="col-md-12">
            <!-- Row 1: DC NO, Date, Contract Types -->
            <div class="row" style="margin-top: 10px">
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">DC NO:</label>
                        <input type="text" name="dc_no" value="{{ $delivery_challan->dc_no }}" id="dc_no"
                            class="form-control" readonly>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">Date:</label>
                        <input type="date" readonly name="date" onchange="getNumber()"
                            value="{{ $delivery_challan->dispatch_date }}" id="date" class="form-control">
                    </div>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Contract Types:</label>
                    <select name="sauda_type" id="sauda_type" class="form-control select2" disabled>
                        <option value="">Select Contract type</option>
                        <option value="pohanch" @selected($delivery_challan->sauda_type == 'pohanch')>Pohanch</option>
                        <option value="x-mill" @selected($delivery_challan->sauda_type == 'x-mill')>X-mill</option>
                    </select>
                    <input type="hidden" name="sauda_type" value="{{ $delivery_challan->sauda_type }}">
                </div>
            </div>

            <!-- Row 2: Ticket (display only), Customer, DO Number -->
            <div class="row">
                <div class="col-md-4">
                    <label class="form-label">Ticket:</label>
                    @php
                        $firstTicket = $delivery_challan->delivery_challan_data->first();
                        $ticketModel = $firstTicket ? \App\Models\Sales\LoadingProgramItem::find($firstTicket->ticket_id) : null;
                        $ticketDisplay = $ticketModel ? ($ticketModel->transaction_number . ' -- ' . $ticketModel->truck_number) : 'N/A';
                    @endphp
                    <input type="text" class="form-control" value="{{ $ticketDisplay }}" disabled>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Customer:</label>
                    <select id="customer_id_display" class="form-control select2" disabled>
                        <option value="">Select Customer</option>
                        @foreach ($customers ?? [] as $customer)
                            <option value="{{ $customer->id }}" @selected($delivery_challan->customer_id == $customer->id)>{{ $customer->name }}</option>
                        @endforeach
                    </select>
                    <input type="hidden" name="customer_id" id="customer_id" value="{{ $delivery_challan->customer_id }}">
                </div>

                <div class="col-md-4">
                    <label class="form-label">DO Number:</label>
                    <select name="do_no[]" id="do_no" class="form-control select2" disabled>
                        <option value="">Select Delivery Order</option>
                        @foreach ($delivery_orders as $delivery_order)
                            <option value="{{ $delivery_order->id }}" @selected(in_array($delivery_order->id, $delivery_challan->delivery_order->pluck('id')->toArray()))>
                                {{ $delivery_order->reference_no }}</option>
                        @endforeach
                    </select>
                </div>

                <input type='hidden' name="delivery_order_id" id="delivery_order_id"  value="{{ $delivery_challan->delivery_order->pluck('id')->toArray()[0] }}"/>
            </div>

            <!-- Row 3: Reference Number, Add More Tickets -->
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Reference Number:</label>
                        <input type="text" name="reference_number" id="reference_number"
                            value="{{ $delivery_challan->reference_number }}" class="form-control">
                    </div>
                </div>


                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Ticket Labour:</label>
                        @php
                            $firstTicketData = $delivery_challan->delivery_challan_data->first();
                            $ticketLabour = null;
                            if ($firstTicketData && $firstTicketData->ticket_id) {
                                $loadingSlip = \App\Models\Sales\LoadingProgramItem::find($firstTicketData->ticket_id)?->loadingSlip;
                                $ticketLabour = $loadingSlip?->labour;
                            }
                        @endphp
                        <input type="text" class="form-control" value="{{ $ticketLabour ? ($ticketLabour === 'paid' ? 'Paid' : 'Not Paid') : 'N/A' }}" readonly>
                    </div>
                </div>
                
            </div>

            <!-- Row 4: Locations, Factory, Gala -->
            <div class="row">
                <div class="col-md-4">
                    <label class="form-label">Locations:</label>
                    <select name="locations[]" id="locations" class="form-control select2" disabled>
                        <option value="">Select Locations</option>
                        @foreach (get_locations() as $location)
                            <option value="{{ $location->id }}" @selected($location->id == $delivery_challan->location_id)>
                                {{ $location->name }}
                            </option>
                        @endforeach
                    </select>
                    <div id="locations_hidden">
                    </div>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Factory:</label>
                    <select name="arrival_locations[]" id="arrivals" class="form-control select2" multiple disabled>
                        <option value="">Select Factory</option>
                        @foreach (($arrivalLocations ?? collect()) as $location)
                            <option value="{{ $location->id }}" @selected(in_array($location->id, explode(",", $delivery_challan->arrival_id)))>
                                {{ $location->name }}
                            </option>
                        @endforeach
                    </select>
                    <div id="arrivals_hidden">
                        <input type="hidden" name="arrival_location_csv" id="arrival_location_csv" value="{{ $delivery_challan->arrival_id }}" />
                    </div>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Gala:</label>
                    <select name="storage_id[]" id="storages" class="form-control select2" multiple disabled>
                        <option value="">Select Gala</option>
                        @foreach (($sections ?? collect()) as $section)
                            <option value="{{ $section->id }}" @selected(in_array($section->id, explode(",", $delivery_challan->section_id)))>
                                {{ $section->name }}
                            </option>
                        @endforeach
                    </select>
                    <div id="storages_hidden">
                        <input type="hidden" name="storage_location_csv" id="storage_location_csv" value="{{ $delivery_challan->section_id }}" />
                    </div>
                </div>
            </div>

            <!-- Row 5: Ticket Labour, Labour, Transporter -->
            <div class="row">
                

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">Labour:</label>
                        <select name="labour" id="labour" class="form-control select2">
                            <option value="">Select Labours</option>
                            <option value="1" @selected($delivery_challan->labour == 1)>Labour 1</option>
                            <option value="2" @selected($delivery_challan->labour == 2)>Labour 2</option>
                        </select>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">Transporter:</label>
                        <select name="transporter" id="transporter" class="form-control select2">
                            <option value="">Select Transporter</option>
                            <option value="1" @selected($delivery_challan->transporter == 1)>Transporter 1</option>
                            <option value="2" @selected($delivery_challan->transporter == 2)>Transporter 2</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">In-house Weighbridge:</label>
                        <select name="weighbridge" id="weighbridge" class="form-control select2">
                            <option value="">Select Weighbridge</option>
                            <option value="1" @selected($delivery_challan->{'inhouse-weighbridge'} == 1)>Weighbridge 1</option>
                            <option value="2" @selected($delivery_challan->{"inhouse-weighbridge"} == 2)>Weighbridge 2</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Row 5b: Weighbridge -->
          

            <!-- Row 6: Labour Amount, Transporter Amount, Weighbridge Amount -->
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">Labour Amount:</label>
                        <input type="number" name="labour_amount" value="{{ $delivery_challan->labour_amount }}"
                            id="labour_amount" class="form-control">
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">Transporter Amount:</label>
                        <input type="number" name="transporter_amount"
                            value="{{ $delivery_challan->transporter_amount }}" id="transporter_amount"
                            class="form-control">
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">Weighbridge Amount:</label>
                        <input type="number" name="weighbridge_amount"
                            value="{{ $delivery_challan->{"weighbridge-amount"} }}"
                            id="weighbridge_amount" class="form-control">
                    </div>
                </div>
            </div>

            <!-- Row 7: Remarks -->
            <div class="row">
                <div class="col-md-12">
                    <label class="form-label">Remarks:</label>
                    <textarea name="remarks" id="remarks" class="form-control">{{ $delivery_challan->remarks }}</textarea>
                </div>
            </div>
        </div>
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
                            <th>Rate per Mond</th>
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
                        @foreach ($delivery_challan->delivery_challan_data as $index => $data)
                        @php
                            $index = "TICKET-" . $data->ticket_id;
                        @endphp
                        <tr id="row_{{ $index }}">
                            <td>
                                <input type="text" name="" id="item_id_read_only{{ $index }}"
                                    value="{{ getItem($data->item_id)?->name }}" onkeyup="calc(this)"
                                    class="form-control bag_type" step="0.01" min="0" readonly>

                                <input type="hidden" name="item_id[]" id="item_id_{{ $index }}"
                                    value="{{ $data->item_id }}" onkeyup="calc(this)"
                                    class="form-control item_id" step="0.01" min="0">

                                <input type="hidden" name="ticket_id[]" id="ticket_id_{{ $index }}" value="{{ $data->ticket_id }}"
                                    onkeyup="calc(this)" class="form-control ticket_id" step="0.01" min="0">

                                <input type="hidden" name="do_data_id[]" id="do_data_id_{{ $index }}"
                                    value="{{ $data->do_data_id }}" onkeyup="calc(this)"
                                    class="form-control do_data_id" step="0.01" min="0">
                            </td>
                            
                            <td>
                                <input type="text" name="" id="bag_type_{{ $index }}"
                                    value="{{ $data->bag_type ? bag_type_name($data->bag_type) : '' }}" onkeyup="calc(this)"
                                    class="form-control bag_type" step="0.01" min="0" readonly>

                                <input type="hidden" name="bag_type[]" id="bag_type_{{ $index }}"
                                    value="{{ $data->bag_type }}" onkeyup="calc(this)"
                                    class="form-control bag_type" step="0.01" min="0">

                                <input type="hidden" name="so_data_id[]" id="so_data_id_{{ $index }}"
                                    value="{{ $data->id }}" onkeyup="calc(this)"
                                    class="form-control so_data_id" step="0.01" min="0">
                            </td>
                          
                            <td>
                                <input type="text" name="bag_size[]" id="bag_size_{{ $index }}"
                                    value="{{ $data->bag_size }}"
                                    class="form-control bag_size" step="0.01" min="0" readonly>
                            </td>
                            <td>
                                <input type="text" name="no_of_bags[]" id="no_of_bags_{{ $index }}"
                                    value="{{ $data->no_of_bags }}"
                                    class="form-control no_of_bags" step="0.01" min="0" readonly>
                            </td>
                            <td>
                                <input type="text" name="qty[]" id="qty_{{ $index }}"
                                    value="{{ $data->qty }}"
                                    class="form-control qty" step="0.01" min="0" oninput="calc(this)" readonly>
                            </td>
                            <td>
                                <input type="text" name="rate[]" id="rate_{{ $index }}"
                                    value="{{ $data->rate }}" class="form-control rate" step="0.01"
                                    min="0" readonly>
                            </td>
                            <td>
                                <input type="text" name="rate_per_mond[]" id="rate_per_mond_{{ $index }}"
                                    value="{{ $data->deliveryOrderData->salesOrderData->rate_per_mond }}" class="form-control rate" step="0.01"
                                    min="0" readonly>
                            </td>
                            <td>
                                <input type="text" name="amount[]" id="amount_{{ $index }}"
                                    value="{{ $data->rate * ($data->qty ?? 0) }}"
                                    class="form-control amount" readonly>
                            </td>
                            <td>
                                <input type="text" name="" id="brand_id_read_only{{ $index }}"
                                    value="{{ getBrandById($data->brand_id)?->name }}" onkeyup="calc(this)"
                                    class="form-control brand_id" step="0.01" min="0" readonly>

                                <input type="hidden" name="brand_id[]" id="brand_id_{{ $index }}"
                                    value="{{ $data->brand_id }}" onkeyup="calc(this)"
                                    class="form-control item_id" step="0.01" min="0">
                            </td>
                            <td>
                                <input type="text" name="truck_no[]" id="truck_no_{{ $index }}"
                                    class="form-control truck_no" value="{{ $data->truck_no }}" readonly>
                            </td>
                            <td>
                                <input type="text" name="bilty_no[]" id="bilty_no_{{ $index }}"
                                    value="{{ $data->bilty_no }}" class="form-control bilty_no">
                            </td>
                            <td>
                                <input type="text" name="desc[]" id="desc_{{ $index }}"
                                    class="form-control" value="{{ $data->description }}">
                            </td>

                            <td>
                                @php
                                    $ticket = \App\Models\Sales\LoadingProgramItem::find($data->ticket_id);
                                    $ticketText = $ticket ? ($ticket->transaction_number . ' -- ' . $ticket->truck_number) : '';
                                @endphp
                                <button type="button" class="btn btn-danger btn-sm removeRowBtn"
                                    data-ticket-id="{{ $data->ticket_id }}"
                                    data-ticket-text="{{ $ticketText }}"
                                    onclick="removeTicketRow(this)"
                                    style="width:60px;">
                                    <i class="fa fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        @endforeach
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
    
    // Track which tickets have been added
    addedTicketIds = @json($delivery_challan->delivery_challan_data->pluck('ticket_id')->filter()->unique()->values());
    
    $(document).ready(function() {
        $('.select2').select2();
        
        // Load additional tickets on page load
        const selectedDos = $("#do_no").val();
        if (selectedDos && selectedDos.length > 0) {
            loadAdditionalTickets(Array.isArray(selectedDos) ? selectedDos : [selectedDos]);
        }
    });

    // Load additional tickets for the same delivery order
    function loadAdditionalTickets(deliveryOrderIds) {
        $.ajax({
            url: "{{ route('sales.delivery-challan.get-tickets') }}",
            method: "GET",
            data: {
                delivery_order_ids: Array.isArray(deliveryOrderIds) ? deliveryOrderIds : [deliveryOrderIds],
                delivery_challan_id: {{ $delivery_challan->id }}
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
                } else {
                    $("#addTicketContainer").hide();
                }
                
                select.select2();
            },
            error: function(error) {
                console.error('Error loading additional tickets:', error);
            }
        });
    }
    
    // Handle adding more tickets
    $("#add_ticket_id").on("change", function() {
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

    function calcAmount(el) {
        const element = $(el).closest("tr");
        const qty = $(element).find(".qty");
        const rate = $(element).find(".rate");
        const amount = $(element).find(".amount");

        if (!qty.val() || !rate.val()) {
            amount.val("");
            return;
        }
        const result = parseFloat(qty.val()) * parseFloat(rate.val());
        amount.val(result);
    }

    function calc(el) {
        const element = $(el).closest("tr");
        const bag_size = $(element).find(".bag_size");
        const no_of_bags = $(element).find(".no_of_bags");
        const qty = $(element).find(".qty");

        const bagSizeVal = parseFloat(bag_size.val());
        const qtyVal = parseFloat(qty.val());

        if (!bagSizeVal || !qtyVal) {
            no_of_bags.val("");
            calcAmount(el);
            return;
        }

        const bagsResult = (qtyVal / bagSizeVal).toFixed();

        no_of_bags.val(bagsResult);
        calcAmount(el);
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
                $('.loader-container').hide();
                console.error("Error:", error);
            }
        });
    }

    $(".select2").select2();
</script>
