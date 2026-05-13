<form action="{{ route('export-loading-slip.store') }}" method="POST" id="ajaxSubmit" autocomplete="off" enctype="multipart/form-data">
    @csrf
    <input type="hidden" id="listRefresh" value="{{ route('get.export-loading-slip') }}" />
    <style>
        .select2-container, .select2-container--default, .select2-selection--single { width: 100% !important; }
        .header-heading-sepration { border-bottom: 2px solid #ebeef3; padding-bottom: 5px; margin-bottom: 10px; }
    </style>

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

    <div id="ticketDataContainer"></div>

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
    $(document).ready(function() {
        $('.select2').select2();
    });

    $(document).ready(function() {
        $('#loading_program_item_id').change(function() {
            var loading_program_item_id = $(this).val();

            if (loading_program_item_id) {
                $.ajax({
                    url: '{{ route("export.getLoadingSlipTicketData") }}',
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
                            populateTicketData(response.data);
                        } else {
                            Swal.fire("No Data", "No ticket details found.", "info");
                        }
                    },
                    error: function() {
                        Swal.close();
                        Swal.fire("Error", "Something went wrong. Please try again.", "error");
                    }
                });
            } else {
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

            var factoryNames = order.factory_names && order.factory_names.length > 0 ? order.factory_names.join(', ') : 'N/A';

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
                                <label>EO Qty (MT):</label>
                                <input type="number" value="${order.so_qty}" class="form-control" readonly step="0.01" />
                            </div>
                        </div>
                        <div class="col-xs-12 col-sm-6 col-md-3">
                            <div class="form-group">
                                <label>DO Qty (MT):</label>
                                <input type="number" value="${order.do_qty}" class="form-control" readonly step="0.01" />
                            </div>
                        </div>
                        </div>
                        <div class="row">
                        <div class="col-xs-12 col-sm-6 col-md-12">
                            <div class="form-group">
                                <label>Factory:</label>
                                <input type="text" value="${factoryNames}" class="form-control" readonly />
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });

        tabsHtml += '</ul>';
        contentHtml += '</div>';

        var galaOptions = data.gala_names && data.gala_names.length > 0
            ? data.gala_names.map(name => `<option value="${name}">${name}</option>`).join('')
            : '';
        var factoryNames = data.factory_names && data.factory_names.length > 0
            ? data.factory_names.join(', ')
            : '';

        var commonInputsHtml = `
            <div class="row pt-2">
                <div class="col-xs-12 col-sm-6 col-md-6">
                    <div class="form-group">
                        <label>Bag Size:</label>
                        <input type="number" name="bag_size_display" value="${data.bag_size}" class="form-control" readonly step="0.01" />
                        <input type="hidden" name="bag_size" value="${data.bag_size}" />
                    </div>
                </div>
                <div class="col-xs-12 col-sm-6 col-md-6">
                    <div class="form-group">
                        <label>No. of Bags: <span class="text-danger">*</span></label>
                        <input type="number" name="no_of_bags" id="no_of_bags" class="form-control" min="1" max="${data.remaining_bags || ''}" required>
                        <small class="text-muted">Available bags: ${typeof data.remaining_bags !== 'undefined' ? data.remaining_bags : 0}</small>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-xs-12 col-sm-6 col-md-6">
                    <div class="form-group">
                        <label>Empty Bags:</label>
                        <input type="text" name="empty_bags" id="empty_bags" class="form-control" placeholder="Enter Empty Bags">
                    </div>
                </div>
                <div class="col-xs-12 col-sm-6 col-md-6">
                    <div class="form-group">
                        <label>Gala: <span class="text-danger">*</span></label>
                        <select class="form-control select2-common w-100" name="gala[]" id="gala_select" multiple required style="width: 100% !important;">
                            ${galaOptions}
                        </select>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-xs-12 col-sm-4 col-md-4">
                    <div class="form-group">
                        <label>Kilogram: <span class="text-danger">*</span></label>
                        <input type="number" name="kilogram" id="kilogram" value="0.00" class="form-control" readonly required step="0.01" />
                    </div>
                </div>
                <div class="col-xs-12 col-sm-4 col-md-4">
                    <div class="form-group">
                        <label>Metric Tons:</label>
                        <input type="number" id="metric_tons_display" value="0.00" class="form-control" readonly step="0.01" />
                    </div>
                </div>
                <div class="col-xs-12 col-sm-4 col-md-4">
                    <div class="form-group">
                        <label>Seal No: <span class="text-danger">*</span></label>
                        <input type="text" name="seal_no" id="seal_no" class="form-control" placeholder="Enter Seal No" required />
                    </div>
                </div>
                <input type="hidden" name="company_id" value="{{ auth()->user()->current_company_id }}" />
            </div>

            <div class="row pt-3">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <h6 class="header-heading-sepration mb-0" style="flex-grow: 1;">Stack</h6>
                        <button type="button" class="btn btn-sm btn-success" id="add_stack_row"><i class="ft-plus"></i> Add More</button>
                    </div>
                    <table class="table table-bordered table-striped" id="stacks_table">
                        <thead>
                            <tr>
                                <th>Bag Type <span class="text-danger">*</span></th>
                                <th>Packing Size (KG) <span class="text-danger">*</span></th>
                                <th>Input Size (KG) <span class="text-danger">*</span></th>
                                <th width="50px">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Rows will be added here -->
                        </tbody>
                    </table>
                </div>
            </div>

            <input type="hidden" name="customer" value="${data.customer}" />
            <input type="hidden" name="commodity" value="${data.commodity}" />
            <input type="hidden" name="so_qty" value="${data.so_qty}" />
            <input type="hidden" name="do_qty" value="${data.do_qty}" />
            <input type="hidden" name="factory" value="${factoryNames}" />
        `;

        $('#ticketDataContainer').html(tabsHtml + contentHtml + commonInputsHtml);
        $('.select2-common').select2({ width: '100%' });

        $(document).on('select2:select select2:unselect', '.select2-common, .select2-dynamic', function() {
            $(this).next('.select2-container').css('width', '100%');
        });

        // Store bag types and packing sizes for dynamic row addition
        window.availableBagTypes = data.bag_types || [];
        window.availablePackingSizes = data.packing_sizes || [];

        // Add first row
        addStackRow();

        $('#add_stack_row').on('click', function() {
            addStackRow();
        });

        $(document).off('click', '.remove_stack_row').on('click', '.remove_stack_row', function() {
            if ($('#stacks_table tbody tr').length > 1) {
                $(this).closest('tr').remove();
                updateStackIndices();
            } else {
                Swal.fire("Warning", "At least one stack row is required.", "warning");
            }
        });

        $('#no_of_bags').on('input', function() {
            var noOfBags = parseFloat($('#no_of_bags').val()) || 0;
            var bagSize = parseFloat($('input[name="bag_size"]').val()) || 0;
            var kilogram = noOfBags * bagSize;
            $('#kilogram').val(kilogram.toFixed(2));
            $('#metric_tons_display').val((kilogram / 1000).toFixed(2));
        });
    }

    function addStackRow() {
        var idx = $('#stacks_table tbody tr').length;
        var bagTypeOptions = window.availableBagTypes.map(type => `<option value="${type}">${type}</option>`).join('');
        var packingSizeOptions = window.availablePackingSizes.map(size => `<option value="${size}">${size}</option>`).join('');

        var row = `
            <tr>
                <td>
                    <select name="stacks[${idx}][bag_type]" class="form-control select2-dynamic" required style="width: 100%;">
                        <option value="">Select Bag Type</option>
                        ${bagTypeOptions}
                    </select>
                </td>
                <td>
                    <select name="stacks[${idx}][packing_size]" class="form-control select2-dynamic" required style="width: 100%;">
                        <option value="">Select Packing Size</option>
                        ${packingSizeOptions}
                    </select>
                </td>
                <td>
                    <input type="text" name="stacks[${idx}][input_size]" class="form-control" placeholder="Enter Input Size" required>
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-danger remove_stack_row"><i class="ft-trash-2"></i></button>
                </td>
            </tr>
        `;
        $('#stacks_table tbody').append(row);
        $('#stacks_table tbody tr').last().find('.select2-dynamic').select2({ width: '100%' });
    }

    function updateStackIndices() {
        $('#stacks_table tbody tr').each(function(idx) {
            $(this).find('select, input').each(function() {
                var name = $(this).attr('name');
                if (name) {
                    $(this).attr('name', name.replace(/stacks\[\d+\]/, `stacks[${idx}]`));
                }
            });
        });
    }

</script>
