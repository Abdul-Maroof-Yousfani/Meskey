<form action="{{ route('sales.loading-slip.store') }}" method="POST" id="ajaxSubmit" autocomplete="off"
    enctype="multipart/form-data">
    @csrf
    <input type="hidden" id="listRefresh" value="{{ route('sales.get.loading-slip') }}" />

    <div class="row form-mar">
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Tickets:</label>
                <select class="form-control select2" name="loading_program_item_id" id="loading_program_item_id">
                    <option value="">Select Ticket</option>
                    @foreach ($availableTickets as $ticket)
                        <option value="{{ $ticket->id }}">
                            {{ $ticket->transaction_number }} -- {{ $ticket->truck_number }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <div id="ticketDataContainer">
        <!-- Ticket data will be populated here -->
    </div>

    <div class="row">
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Remarks:</label>
                <textarea name="remarks" placeholder="Enter remarks" class="form-control" rows="3"></textarea>
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
    $(document).ready(function () {
        $('.select2').select2();
    });

    $(document).ready(function () {
        // Handle ticket selection
        $('#loading_program_item_id').change(function () {
            var loading_program_item_id = $(this).val();

            if (loading_program_item_id) {
                $.ajax({
                    url: '{{ route("sales.getLoadingSlipTicketData") }}',
                    type: 'GET',
                    data: {
                        loading_program_item_id: loading_program_item_id
                    },
                    dataType: 'json',
                    beforeSend: function () {
                        Swal.fire({
                            title: "Processing...",
                            text: "Please wait while fetching ticket details.",
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });
                    },
                    success: function (response) {
                        Swal.close();
                        if (response.success) {
                            // Populate the form with ticket data
                            populateTicketData(response.data);
                        } else {
                            Swal.fire("No Data", "No ticket details found.",
                                "info");
                        }
                    },
                    error: function () {
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
            var autoBadge = order.is_auto ? '<span class="badge badge-primary" style="font-size: 0.6rem;">Dummy DO</span>' : '';

            tabsHtml += `
                <li class="nav-item">
                    <a class="nav-link ${activeClass}" id="${tabId}" data-toggle="tab" href="#${contentId}" role="tab" aria-controls="${contentId}" aria-selected="${selectedAttr}">
                        ${order.type}: ${order.number} ${autoBadge}
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
                        <div class="col-xs-12 col-sm-6 col-md-3">
                            <div class="form-group">
                                <label>Factory:</label>
                                <select class="form-control select2 w-100" multiple disabled style="width: 100% !important;">
                                    ${factoryOptions}
                                </select>
                            </div>
                        </div>
                        <div class="col-xs-12 col-sm-6 col-md-3">
                            <div class="form-group">
                                <label>Gala:</label>
                                <select class="form-control select2 w-100" multiple disabled style="width: 100% !important;">
                                    ${galaOptions}
                                </select>
                            </div>
                        </div>
                        <div class="col-xs-12 col-sm-6 col-md-3">
                            <div class="form-group">
                                <label>Bag Size:</label>
                                <input type="number" value="${order.bag_size}" class="form-control" readonly step="0.01" />
                            </div>
                        </div>
                        <div class="col-xs-12 col-sm-6 col-md-3">
                            <div class="form-group">
                                <label>Brand:</label>
                                <input type="text" value="${order.brand}" class="form-control" readonly />
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });

        tabsHtml += '</ul>';
        contentHtml += '</div>';

        // Add common inputs and hidden inputs for main form submission
        var commonInputsHtml = `
            <div class="row pt-2">
                <div class="col-xs-12 col-sm-6 col-md-4">
                    <div class="form-group">
                        <label>No. of Bags: <span class="text-danger">*</span></label>
                        <input type="number" name="no_of_bags" id="no_of_bags" class="form-control" min="1" required>
                    </div>
                </div>
                <div class="col-xs-12 col-sm-6 col-md-4">
                    <div class="form-group">
                        <label>Suggested Qty: <span class="text-danger">*</span></label>
                        <input type="number" name="suggested_qty" id="suggested_qty" value="${data.suggested_qty}" class="form-control" readonly required step="0.01" />
                    </div>
                </div>
                <div class="col-xs-12 col-sm-6 col-md-4">
                    <div class="form-group">
                        <label>Labour:</label>
                        <select name='labour' id='labour' class='form-control select2'>
                            <option value='paid' ${data.labour === 'paid' ? 'selected' : ''}>Paid</option>
                            <option value='not_paid' ${data.labour === 'not_paid' ? 'selected' : ''}>Not Paid</option>    
                        </select>
                    </div>
                </div>
                  <div style="display: none;">
                    <div class="form-group">
                        <label>QTY KG: <span class="text-danger">*</span> (No. Bags x Bag Size)</label>
                        <input type="number" name="kilogram" id="kilogram" value="0.00" class="form-control" readonly required step="0.01" />
                    </div>
                </div>
                <input type="hidden" name="bag_size" value="${data.bag_size}" />
                <input type="hidden" name="company_id" value="{{ auth()->user()->current_company_id }}" />
            </div>

            <input type="hidden" name="customer" value="${data.customer}" />
            <input type="hidden" name="commodity" value="${data.commodity}" />
            <input type="hidden" name="brand" value="${data.brand}" />
            <input type="hidden" name="so_qty" value="${data.so_qty}" />
            <input type="hidden" name="do_qty" value="${data.do_qty}" />
            <input type="hidden" name="factory" value="${data.factory_names ? data.factory_names.join(', ') : ''}" />
            <input type="hidden" name="gala" value="${data.gala_names ? data.gala_names.join(', ') : ''}" />
        `;

        $('#ticketDataContainer').html(tabsHtml + contentHtml + commonInputsHtml);
        // Initialize select2 for the new elements
        $('.select2').select2({
            dropdownParent: $('#modal-sidebar')
        });

        // Calculate kilogram when no_of_bags changes
        $('#no_of_bags').on('input', function () {
            calculateKilogram();
        });

        function calculateKilogram() {
            var noOfBags = parseFloat($('#no_of_bags').val()) || 0;
            var bagSize = parseFloat($('input[name="bag_size"]').val()) || 0;
            var kilogram = noOfBags * bagSize;
            $('#kilogram').val(kilogram.toFixed(2));
        }
    }
</script>