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
    .snapshot-area {
        opacity: 0.9;
    }
</style>

<form action="{{ route('export-form-e.update', $formE->id) }}" method="POST" id="ajaxSubmit" autocomplete="off" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    <input type="hidden" id="listRefresh" value="{{ route('get.export-form-e') }}" />

    <!-- Details (Editable) -->
    <div class="row form-mar">
        <div class="col-md-12">
            <h6 class="header-heading-sepration">Form-E Details</h6>
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Buyer:</label>
                        <select name="buyer_id" id="buyer_id_edit" class="form-control select2" disabled>
                            @foreach ($buyers as $buyer)
                                <option value="{{ $buyer->id }}" {{ $formE->buyer_id == $buyer->id ? 'selected' : '' }}>{{ $buyer->name }}</option>
                            @endforeach
                        </select>
                        <input type="hidden" name="buyer_id" value="{{ $formE->buyer_id }}">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Export Order:</label>
                        <select name="export_order_id" id="export_order_id_edit" class="form-control select2" disabled>
                            @foreach ($export_orders as $eo)
                                <option value="{{ $eo->id }}" {{ $formE->export_order_id == $eo->id ? 'selected' : '' }}>#{{ $eo->voucher_no }}</option>
                            @endforeach
                        </select>
                        <input type="hidden" name="export_order_id" value="{{ $formE->export_order_id }}">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Job Order:</label>
                        <select name="job_order_id" id="job_order_id_edit" class="form-control select2">
                            <option value="">Select Job Order</option>
                            @foreach ($job_orders as $jo)
                                <option value="{{ $jo->id }}" {{ $formE->job_order_id == $jo->id ? 'selected' : '' }}>{{ $jo->job_order_no }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Form-E No: <span class="text-danger">*</span></label>
                        <input type="text" name="form_e_no" class="form-control" value="{{ $formE->form_e_no }}" placeholder="Enter Form-E No" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Form-E Date: <span class="text-danger">*</span></label>
                        <input type="date" name="form_e_date" class="form-control" value="{{ $formE->form_e_date }}" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Attachment:</label>
                        <input type="file" name="attachment" class="form-control">
                        @if($formE->attachment)
                            <small class="text-muted">Current: <a href="{{ asset('storage/'.$formE->attachment) }}" target="_blank">View File</a></small>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Export Order Snapshot Area (Read-Only) -->
    <div id="exportOrderSnapshotEdit" class="snapshot-area" style="pointer-events: none; opacity: 0.9;">
        <div class="row form-mar">
            <div class="col-12">
                <h6 class="header-heading-sepration">Product Details</h6>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Commodity/Product:</label>
                            <input type="text" id="snap_product_name_edit" class="form-control" readonly>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Visual Name:</label>
                            <input type="text" id="snap_visual_name_edit" class="form-control" readonly>
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
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Total Quantity (MT):</label>
                        <input type="text" id="total_quantity_edit" class="form-control" value="{{ $formE->total_quantity }}" readonly>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Remaining Quantity (MT):</label>
                        <input type="text" id="remaining_quantity_edit" class="form-control" value="{{ $formE->remaining_quantity }}" readonly>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Input Quantity (MT): <span class="text-danger">*</span></label>
                        <input type="number" name="input_quantity" id="input_quantity_edit" class="form-control" step="0.01" min="0.01" value="{{ $formE->input_quantity }}" required max="{{ $formE->remaining_quantity + $formE->input_quantity }}">
                        <span id="qty_error_edit" class="text-danger small" style="display:none;"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row bottom-button-bar">
        <div class="col-12 mb-3">
            <a type="button" class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton">Close</a>
            <button type="submit" class="btn btn-primary submitbutton">Update Form-E</button>
        </div>
    </div>
</form>

<script>
    $(document).ready(function() {
        $('.select2').select2({ width: '100%' });

        $('#input_quantity_edit').on('input', function() {
            var max = parseFloat($(this).attr('max'));
            var val = parseFloat($(this).val());
            if (val > max) {
                $('#qty_error_edit').text("Quantity cannot exceed available " + max + " MT").show();
                $('.submitbutton').prop('disabled', true);
            } else {
                $('#qty_error_edit').hide();
                $('.submitbutton').prop('disabled', false);
            }
        });

        // Render JSON dumped from backend
        let snapshotData = @json($formE->export_snapshot);

        if (snapshotData) {
            populateSnapshotEdit(snapshotData);
        }

        function populateSnapshotEdit(data) {
            $('#snap_product_name_edit').val(data.product ? data.product.name : '');
            $('#snap_visual_name_edit').val(data.visual_name || '');
        }
    });
</script>
