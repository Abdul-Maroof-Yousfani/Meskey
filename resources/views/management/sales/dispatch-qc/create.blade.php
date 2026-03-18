<form action="{{ route('sales.dispatch-qc.store') }}" method="POST" id="ajaxSubmit" autocomplete="off" enctype="multipart/form-data">
    @csrf
    <input type="hidden" id="listRefresh" value="{{ route('sales.get.dispatch-qc') }}" />

    <div class="row form-mar">
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Tickets:</label>
                <select class="form-control select2" name="loading_program_item_id" id="loading_program_item_id">
                    <option value="">Select Ticket</option>
                    @foreach ($Tickets as $ticket)
                        <option value="{{ $ticket->id }}">
                            {{ $ticket->transaction_number }} -- {{ $ticket->truck_number }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <div class="row" id="ticketDataContainer" style="margin-left: 4px; margin-right: 4px;">
        <!-- Ticket data will be populated here -->
    </div>

    <div class="row">
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>QC Remarks:</label>
                <textarea name="qc_remarks" placeholder="Enter QC remarks" class="form-control" rows="3"></textarea>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xs-12 col-sm-6 col-md-6">
            <div class="form-group">
                <label>Status:</label>
                <select class="form-control" name="status">
                    <option value="">Select Status</option>
                    <option value="accept">Accept</option>
                    <option value="reject">Reject</option>
                </select>
            </div>
        </div>
        <div class="col-xs-12 col-sm-6 col-md-6">
            <div class="form-group">
                <label>Attachments:</label>
                <input type="file" name="attachments[]" class="form-control" multiple accept="image/*,application/pdf,.doc,.docx">
                <small class="text-muted">Allowed: Images, PDF, DOC, DOCX (Max 10MB each)</small>
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
        $('.select2').select2();
    });

    $(document).ready(function() {
        // Handle ticket selection
        $('#loading_program_item_id').change(function() {
            var loading_program_item_id = $(this).val();

            if (loading_program_item_id) {
                $.ajax({
                    url: '{{ route('sales.getDispatchQcTicketData') }}',
                    type: 'GET',
                    data: {
                        loading_program_item_id: loading_program_item_id
                    },
                    dataType: 'json',
                    beforeSend: function() {
                        Swal.fire({
                            title: "Processing...",
                            text: "Please wait while fetching ticket details.",
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });
                    },
                    success: function(response) {
                        Swal.close();
                        if (response.success) {
                            // Populate the form with ticket data
                            populateTicketData(response.data);
                        } else {
                            Swal.fire("No Data", "No ticket details found.",
                                "info");
                        }
                    },
                    error: function() {
                        Swal.close();
                        Swal.fire("Error", "Something went wrong. Please try again.",
                            "error");
                    }
                });
            } else {
                // Clear ticket data container if no ticket selected
                $('#ticketDataContainer').html('');
            }
        });
    });

    function populateTicketData(data) {
        if (!data.orders || data.orders.length === 0) {
            $('#ticketDataContainer').html('<div class="col-12 text-center">No order data found.</div>');
            return;
        }

        var tabsHtml = '<ul class="nav nav-tabs nav-justified w-100" id="orderTabs" role="tablist">';
        var contentHtml = '<div class="tab-content pt-1 w-100" id="orderTabsContent">';

        data.orders.forEach((order, index) => {
            var activeClass = index === 0 ? 'active' : '';
            var selectedAttr = index === 0 ? 'true' : 'false';
            var tabId = `order-tab-${index}`;
            var contentId = `order-content-${index}`;

            tabsHtml += `
                <li class="nav-item">
                    <a class="nav-link ${activeClass}" id="${tabId}" data-toggle="tab" href="#${contentId}" role="tab" aria-controls="${contentId}" aria-selected="${selectedAttr}">
                        ${order.type}: ${order.number}
                    </a>
                </li>
            `;

            var factoryOptions = order.factory_names && order.factory_names.length > 0 ?
                order.factory_names.map(name => `<option value="" selected>${name}</option>`).join('') : '';
            var galaOptions = order.gala_names && order.gala_names.length > 0 ?
                order.gala_names.map(name => `<option value="" selected>${name}</option>`).join('') : '';

            contentHtml += `
                <div class="tab-pane fade show ${activeClass}" id="${contentId}" role="tabpanel" aria-labelledby="${tabId}">
                    <div class="row">
                        <div class="col-xs-12 col-sm-6 col-md-3">
                            <div class="form-group">
                                <label>Customer:</label>
                                <input type="text" value="${order.customer}" class="form-control" readonly />
                            </div>
                        </div>
                        <div class="col-xs-12 col-sm-6 col-md-3">
                            <div class="form-group">
                                <label>Commodity:</label>
                                <input type="text" value="${order.commodity}" class="form-control" readonly />
                            </div>
                        </div>
                        <div class="col-xs-12 col-sm-6 col-md-3">
                            <div class="form-group">
                                <label>SO Qty:</label>
                                <input type="number" value="${order.so_qty}" class="form-control" readonly step="0.01" />
                            </div>
                        </div>
                        <div class="col-xs-12 col-sm-6 col-md-3">
                            <div class="form-group">
                                <label>DO Qty:</label>
                                <input type="number" value="${order.do_qty}" class="form-control" readonly step="0.01" />
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-xs-12 col-sm-6 col-md-6">
                            <div class="form-group">
                                <label>Factory:</label>
                                <select class="form-control select2 w-100" multiple disabled style="width: 100% !important;">
                                    ${factoryOptions}
                                </select>
                            </div>
                        </div>
                        <div class="col-xs-12 col-sm-6 col-md-6">
                            <div class="form-group">
                                <label>Gala:</label>
                                <select class="form-control select2 w-100" multiple disabled style="width: 100% !important;">
                                    ${galaOptions}
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });

        tabsHtml += '</ul>';
        contentHtml += '</div>';

        // Add hidden inputs for the main form submission (using data from first order for backward compatibility)
        var hiddenInputs = `
            <input type="hidden" name="customer" value="${data.customer}" />
            <input type="hidden" name="commodity" value="${data.commodity}" />
            <input type="hidden" name="so_qty" value="${data.so_qty}" />
            <input type="hidden" name="do_qty" value="${data.do_qty}" />
            <input type="hidden" name="factory" value="${data.factory_names ? data.factory_names.join(', ') : ''}" />
            <input type="hidden" name="gala" value="${data.gala_names ? data.gala_names.join(', ') : ''}" />
        `;

        $('#ticketDataContainer').html(tabsHtml + contentHtml + hiddenInputs);
        // Initialize select2 for the new elements
        $('.select2').select2();
    }
</script>
