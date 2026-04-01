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

<form action="{{ route('export-form-e.store') }}" method="POST" id="ajaxSubmit" autocomplete="off" enctype="multipart/form-data">
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
                        <select name="job_order_id" id="job_order_id" class="form-control select2">
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
                        <input type="text" name="form_e_no" class="form-control" placeholder="Enter Form-E No" required>
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

                <!-- Specifications Section -->
                <div class="mt-2" id="snap_specificationsSection" style="display: none;">
                    <h6 class="header-heading-sepration">Specifications</h6>
                    <div id="snap_productSpecs"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quantity Details -->
    <div class="row form-mar mt-3">
        <div class="col-md-12">
            <h6 class="header-heading-sepration">Quantity Details</h6>
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Total Quantity (MT):</label>
                        <input type="text" id="total_quantity" class="form-control" readonly>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Remaining Quantity (MT):</label>
                        <input type="text" id="remaining_quantity" class="form-control" readonly>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Input Quantity (MT): <span class="text-danger">*</span></label>
                        <input type="number" name="input_quantity" id="input_quantity" class="form-control" step="0.01" min="0.01" required>
                        <span id="qty_error" class="text-danger small" style="display:none;"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <div class="row bottom-button-bar mt-3">
        <div class="col-12 mb-3">
            <a type="button" class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton">Close</a>
            <button type="submit" class="btn btn-primary submitbutton">Save Form-E</button>
        </div>
    </div>
</form>

<script>
    $(document).ready(function() {
        $('.select2').select2({ width: '100%' });

        $('#buyer_id').on('change', function() {
            var buyerId = $(this).val();
            $('#export_order_id').html('<option value="">Select Export Order</option>').trigger('change');
            if(buyerId) {
                $.get("{{ route('export.get-orders-by-buyer-form-e', '') }}/" + buyerId, function(res) {
                    if(res.success && res.data) {
                        var opts = '<option value="">Select Export Order</option>';
                        res.data.forEach(function(eo) {
                            opts += '<option value="'+eo.id+'">#'+eo.voucher_no+'</option>';
                        });
                        $('#export_order_id').html(opts).trigger('change');
                    }
                });
            }
        });

        $('#export_order_id').on('change', function() {
            var orderId = $(this).val();
            if (!orderId) {
                // Clear inputs if nothing selected
                $('[data-name]').val('');
                $('#snap_productSpecs').html('');
                $('#total_quantity').val('');
                $('#remaining_quantity').val('');
                $('#input_quantity').val('');
                $('#input_quantity').attr('max', '');
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
                        $('#input_quantity').attr('max', response.remaining_quantity);
                        $('#input_quantity').val(response.remaining_quantity); 
                    }
                },
                error: function(err) {
                    console.error("Failed to fetch order details", err);
                    alert("Failed to fetch export order details.");
                }
            });
        });

        $('#input_quantity').on('input', function() {
            var max = parseFloat($(this).attr('max'));
            var val = parseFloat($(this).val());
            if (val > max) {
                $('#qty_error').text("Quantity cannot exceed available " + max + " MT").show();
                $('.submitbutton').prop('disabled', true);
            } else {
                $('#qty_error').hide();
                $('.submitbutton').prop('disabled', false);
            }
        });

        function populateSnapshot(data) {
            // General structure fields mapped
            $('[data-name="visual_name"]').val(data.visual_name);
            
            // Reconstruct single options for dropdowns with dynamic labels
            $('[data-name="product_id"]').html('<option value="'+data.product_id+'" selected>'+(data.product ? data.product.name : '')+'</option>').trigger('change.select2');

            // Specs
            if(data.specifications && data.specifications.length > 0) {
                let specHtml = '<table class="table table-bordered table-sm">';
                data.specifications.forEach(spec => {
                    specHtml += `<tr><td width="50%"><strong>${spec.spec_name}</strong></td><td>${spec.spec_value}</td></tr>`;
                });
                specHtml += '</table>';
                $('#snap_productSpecs').html(specHtml);
                $('#snap_specificationsSection').show();
            } else {
                $('#snap_specificationsSection').hide();
            }
        }
    });
</script>
