<style>
    /* Chrome, Safari, Edge, Opera */
    input[type=number]::-webkit-outer-spin-button,
    input[type=number]::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }

    /* Firefox */
    input[type=number] {
        -moz-appearance: textfield;
    }

    .spacing-table td {
        padding-top: 15px !important;
        padding-bottom: 15px !important;
    }
</style>

<form action="{{ route('export-form-e.store') }}" method="POST" id="ajaxSubmit" autocomplete="off"
    enctype="multipart/form-data">
    @csrf
    <input type="hidden" id="listRefresh" value="{{ route('get.export-form-e') }}" />

    <!-- Details -->
    <div class="row form-mar">
        <div class="col-md-12">
            <h6 class="header-heading-sepration">Form-E Details</h6>
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Buyer:</label>
                        <select name="buyer_id" id="buyer_id" class="form-control select2" required>
                            <option value="">Select Buyer</option>
                            @foreach ($buyers as $buyer)
                                <option value="{{ $buyer->id }}">{{ $buyer->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Export Order:</label>
                        <select name="export_order_id" id="export_order_id" class="form-control select2" required>
                            <option value="">Select Export Order</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Job Order:</label>
                        <select name="job_order_id" id="job_order_id" class="form-control select2" required>
                            <option value="">Select Job Order</option>
                            @foreach ($job_orders as $jo)
                                <option value="{{ $jo->id }}">{{ $jo->job_order_no }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Form-E No: <span class="text-danger">*</span></label>
                        <input type="text" name="form_e_no" class="form-control" placeholder="Enter Form-E No"
                            required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Form-E Date: <span class="text-danger">*</span></label>
                        <input type="date" name="form_e_date" class="form-control" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Attachment:</label>
                        <input type="file" name="attachment" class="form-control">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Product Details Snapshot -->
    <div id="exportOrderSnapshot" style="pointer-events: none; opacity: 0.9;">
        <div class="row form-mar">
            <div class="col-12">
                <h6 class="header-heading-sepration">Product Details</h6>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Commodity/Product:</label>
                            <select data-name="product_id" class="form-control select2">
                                <option value="">Select Product</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Visual Name:</label>
                            <input type="text" data-name="visual_name" class="form-control">
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Quantity Details -->
    <div class="row form-mar mt-3">
        <div class="col-md-12">
            <h6 class="header-heading-sepration">Quantity Details</h6>
            <div class="row">
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Total Quantity (MT):</label>
                        <input type="text" id="total_quantity" class="form-control" readonly>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Remaining Quantity (MT):</label>
                        <input type="text" id="remaining_quantity" class="form-control" readonly>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Input Quantity (MT): <span class="text-danger">*</span></label>
                        <input type="number" name="input_quantity" id="input_quantity" class="form-control"
                            step="0.01" min="0.01" required>
                        <span id="qty_error" class="text-danger small" style="display:none;"></span>
                    </div>
                </div>
                <div class="col-md-6" id="close_order_div" style="display: none;">
                    <div class="form-group">
                        <label for="close_order_reason">Close Reason:</label>
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <select name="close_order_reason" id="close_order_reason" class="form-control">
                                    <option value="">Select Reason</option>
                                    <option value="abc">abc</option>
                                    <option value="xyz">xyz</option>
                                    <option value="def">def</option>
                                </select>
                            </div>
                            <button type="button" id="close_order_btn"
                                class="btn btn-danger ml-2 text-nowrap d-flex align-items-center justify-content-center"
                                style="height: calc(1.5em + 0.75rem + 2px);">
                                <i class="ft-x-circle mr-1"></i> Close Export Order
                            </button>
                        </div>
                    </div>
                </div>
            </div>


        </div>
    </div>


    <div class="row bottom-button-bar mt-3">
        <div class="col-12 mb-3">
            <a type="button" class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton">Close</a>
            <button type="submit" class="btn btn-primary submitbutton" id="save_btn">Save Form-E</button>
        </div>
    </div>
</form>

<script>
    $(document).ready(function() {
        $('.select2').select2({
            width: '100%'
        });

        $('#buyer_id').on('change', function() {
            var buyerId = $(this).val();
            $('#export_order_id').html('<option value="">Select Export Order</option>').trigger(
                'change');
            if (buyerId) {
                $.get("{{ route('export.get-orders-by-buyer-form-e', '') }}/" + buyerId, function(res) {
                    if (res.success && res.data) {
                        var opts = '<option value="">Select Export Order</option>';
                        res.data.forEach(function(eo) {
                            opts += '<option value="' + eo.id + '">#' + eo.voucher_no +
                                '</option>';
                        });
                        $('#export_order_id').html(opts).trigger('change');
                    }
                });
            }
        });

        // Export order limit (remaining + positive tolerance), set when the order details load
        var orderMaxAllowed = NaN;

        $('#export_order_id').on('change', function() {
            var orderId = $(this).val();
            orderMaxAllowed = NaN;
            if (!orderId) {
                // Clear inputs if nothing selected
                $('[data-name]').val('');
                $('#snap_productSpecs').html('');
                $('#total_quantity').val('');
                $('#remaining_quantity').val('');
                $('#input_quantity').val('').prop('disabled', false);
                $('#input_quantity').attr('max', '');
                $('#close_order_reason').val('').prop('disabled', false);
                $('#close_order_btn').prop('disabled', false).removeClass('btn-secondary').addClass(
                    'btn-danger').html('<i class="ft-x-circle mr-1"></i> Close Export Order');
                $('#close_order_div').hide();
                return;
            }

            $.ajax({
                url: "{{ route('export.get-export-order-details-form-e', '') }}/" + orderId,
                type: 'GET',
                success: function(response) {
                    if (response.success && response.data) {
                        populateSnapshot(response.data);
                        $('#total_quantity').val(response.total_quantity);
                        $('#remaining_quantity').val(response.remaining_quantity);
                        // Max includes the export order's positive tolerance
                        orderMaxAllowed = parseFloat(response.max_remaining_allowed ?? response.remaining_quantity);
                        $('#input_quantity').attr('max', orderMaxAllowed);
                        $('#input_quantity').val(response.remaining_quantity);

                        if (response.data && response.data.close_order_reason) {
                            $('#close_order_reason').val(response.data.close_order_reason)
                                .prop('disabled', true);
                            $('#close_order_btn').prop('disabled', true).removeClass(
                                'btn-danger').addClass('btn-secondary').html(
                                '<i class="ft-check mr-1"></i> Order Closed');
                            $('#close_order_div').show();
                            $('#input_quantity').prop('disabled', true);
                        } else {
                            $('#close_order_reason').val('').prop('disabled', false);
                            $('#close_order_btn').prop('disabled', false).removeClass(
                                'btn-secondary').addClass('btn-danger').html(
                                '<i class="ft-x-circle mr-1"></i> Close Export Order');
                            $('#close_order_div').hide();
                            $('#input_quantity').prop('disabled', false);
                            $('#input_quantity').trigger('input');
                        }
                    }
                },
                error: function(err) {
                    console.error("Failed to fetch order details", err);
                    alert("Failed to fetch export order details.");
                }
            });

            // Fetch Job Orders for this Export Order
            $('#job_order_id').html('<option value="">Select Job Order</option>').trigger('change');
            $.get("{{ route('export.get-job-orders-by-order-form-e', '') }}/" + orderId, function(res) {
                if (res.success && res.data) {
                    var opts = '<option value="">Select Job Order</option>';
                    // Only job orders with capacity left come back from the server
                    res.data.forEach(function(jo) {
                        opts += '<option value="' + jo.id + '" data-remaining="' + jo.remaining_mt + '">' +
                            jo.job_order_no + ' (Capacity: ' + jo.capacity_mt + ' MT, Remaining with tolerance: ' +
                            jo.remaining_mt + ' MT)</option>';
                    });
                    $('#job_order_id').html(opts).trigger('change');
                }
            });
        });

        // Input quantity cannot go above what is left on the selected job order (both limits include tolerance)
        $('#job_order_id').on('change', function() {
            var orderRemaining = orderMaxAllowed;
            var jobRemaining = parseFloat($(this).find(':selected').data('remaining'));
            var max = isNaN(jobRemaining) ? orderRemaining : (isNaN(orderRemaining) ? jobRemaining : Math.min(orderRemaining, jobRemaining));

            if (isNaN(max)) return;

            $('#input_quantity').attr('max', max);
            var qty = parseFloat($('#input_quantity').val());
            if (!isNaN(qty) && qty > max && !$('#input_quantity').prop('disabled')) {
                $('#input_quantity').val(max).trigger('input');
            }
        });

        $('#input_quantity').on('input', function() {
            var orderId = $('#export_order_id').val();
            if (!orderId) {
                $('#close_order_div').hide();
                return;
            }

            $.ajax({
                url: "{{ route('get.toll-range-by-order-id') }}",
                method: 'POST',
                data: {
                    qty: $(this).val(),
                    order_id: orderId
                },
                success: function(r) {
                    let qty = parseFloat($('#input_quantity').val()) || 0;
                    let max = parseFloat(r.max !== undefined ? r.max : r[0]);
                    let min = parseFloat(r.min !== undefined ? r.min : r[1]);

                    // Tolerance check: valid if qty falls within remaining tolerance range [min, max]
                    let inTolerance = (qty >= min && qty <= max && qty > 0);

                    if (inTolerance) {
                        $('#close_order_div').show();
                    } else {
                        if (!$('#close_order_btn').hasClass('btn-secondary')) {
                            $('#close_order_div').hide();
                        }
                    }
                },
                error: function(err) {}
            });
        });

        $('#close_order_btn').on('click', function(e) {
            e.preventDefault();

            var orderId = $('#export_order_id').val();
            if (!orderId) {
                if (typeof toastr !== 'undefined') {
                    toastr.warning('Please select an Export Order first.');
                } else {
                    alert('Please select an Export Order first.');
                }
                return;
            }

            var inputQty = $('#input_quantity').val();
            if (!inputQty || parseFloat(inputQty) <= 0) {
                if (typeof toastr !== 'undefined') {
                    toastr.warning('Please enter valid Input Quantity first.');
                } else {
                    alert('Please enter valid Input Quantity first.');
                }
                $('#input_quantity').focus();
                return;
            }

            var reason = $('#close_order_reason').val();
            if (!reason) {
                if (typeof toastr !== 'undefined') {
                    toastr.warning('Please select a reason first.');
                } else {
                    alert('Please select a reason first.');
                }
                $('#close_order_reason').focus();
                return;
            }

            var $btn = $(this);
            $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin mr-1"></i> Closing...');

            $.ajax({
                url: "{{ route('export-order.close') }}",
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    order_id: orderId,
                    reason: reason,
                    input_quantity: inputQty
                },
                success: function(response) {
                    if (response.success) {
                        if (typeof toastr !== 'undefined') {
                            toastr.success(response.message ||
                                'Export Order closed successfully.');
                        } else {
                            alert(response.message || 'Export Order closed successfully.');
                        }
                        $btn.removeClass('btn-danger').addClass('btn-secondary')
                            .html('<i class="ft-check mr-1"></i> Order Closed');
                        $('#close_order_reason').prop('disabled', true);

                        // Disable Input Quantity so no more changes can be made
                        $('#input_quantity').prop('disabled', true);
                    } else {
                        $btn.prop('disabled', false).html(
                            '<i class="ft-x-circle mr-1"></i> Close Export Order');
                        if (typeof toastr !== 'undefined') {
                            toastr.error(response.message || 'Failed to close order.');
                        } else {
                            alert(response.message || 'Failed to close order.');
                        }
                    }
                },
                error: function(xhr) {
                    $btn.prop('disabled', false).html(
                        '<i class="ft-x-circle mr-1"></i> Close Export Order');
                    var msg = xhr.responseJSON && xhr.responseJSON.message ? xhr
                        .responseJSON.message : (xhr.responseJSON && xhr.responseJSON
                            .error ? xhr.responseJSON.error : 'Error closing order.');
                    if (typeof toastr !== 'undefined') {
                        toastr.error(msg);
                    } else {
                        alert(msg);
                    }
                }
            });
        });

        // Ensure disabled inputs are submitted with form data
        $('#ajaxSubmit').on('submit', function() {
            $('#input_quantity').prop('disabled', false);
            $('#close_order_reason').prop('disabled', false);
        });

        function populateSnapshot(data) {
            // General structure fields mapped
            $('[data-name="visual_name"]').val(data.visual_name);

            // Reconstruct single options for dropdowns with dynamic labels
            $('[data-name="product_id"]').html('<option value="' + data.product_id + '" selected>' + (data
                .product ? data.product.name : '') + '</option>').trigger('change.select2');
        }
    });
</script>
