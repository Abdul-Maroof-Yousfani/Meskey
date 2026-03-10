<form action="{{ route('sales.logistics.store') }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf

    <input type="hidden" id="listRefresh" value="{{ route('sales.get.logistics.list') }}" data-appenddiv="filteredData" />

    <div class="row form-mar">
        <div class="col-md-12">
            <div class="row">
                <div class="col-12">
                    <h6 class="header-heading-sepration">Document Information</h6>
                </div>
                
                <!-- Loading Request (Dropdown - Sale Orders) -->
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="sale_order_id">Loading Request (Sale Order)</label>
                        <select name="sale_order_id" id="sale_order_id" class="form-control select2" required>
                            <option value="">Select Sale Order</option>
                            @foreach($saleOrders as $order)
                                <option value="{{ $order->id }}">
                                    {{ $order->reference_no }}
                                </option>
                            @endforeach
                        </select>
                        <input type="hidden" name="loading_request" id="loading_request">
                    </div>
                </div>

                <!-- Date (readonly - pre-filled on selection) -->
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="date">Date</label>
                        <input type="date" name="date" id="date" class="form-control" readonly required>
                    </div>
                </div>

                <!-- SO # (readonly) -->
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="so_no">SO #</label>
                        <input type="text" name="so_no" id="so_no" class="form-control" readonly>
                    </div>
                </div>

                <!-- Sales Order Qty (kg) (readonly) -->
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="so_qty">Sales Order Qty (kg)</label>
                        <input type="number" name="so_qty" id="so_qty" class="form-control" readonly>
                    </div>
                </div>

                <!-- Commodity (readonly) -->
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="commodity">Commodity</label>
                        <input type="text" name="commodity" id="commodity" class="form-control" readonly>
                    </div>
                </div>

                <!-- Sauda Type (readonly) -->
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="sauda_type">Sauda Type</label>
                        <input type="text" name="sauda_type" id="sauda_type" class="form-control" readonly>
                    </div>
                </div>

                <!-- Delivery Address (readonly) -->
                <div class="col-md-4" style="display: none;">
                    <div class="form-group">
                        <label for="delivery_address">Delivery Address</label>
                        <input type="text" name="delivery_address" id="delivery_address" class="form-control" readonly>
                    </div>
                </div>

                <!-- Location (readonly) -->
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="location">Location</label>
                        <input type="text" name="location" id="location" class="form-control" readonly>
                    </div>
                </div>

                <!-- Factory (readonly) -->
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="factory">Factory</label>
                        <input type="text" name="factory" id="factory" class="form-control" readonly>
                    </div>
                </div>

                <!-- Section (readonly) -->
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="section">Section</label>
                        <input type="text" name="section" id="section" class="form-control" readonly>
                    </div>
                </div>

                <!-- Customer (readonly) -->
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="customer">Customer</label>
                        <input type="text" name="customer" id="customer" class="form-control" readonly>
                    </div>
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-12">
                    <h6 class="header-heading-sepration">Logistics Items</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered" id="itemsTable">
                            <thead class="thead-light">
                                <tr>
                                    <th>Rate Type</th>
                                    <th>Rate</th>
                                    <th>Transporter</th>
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
                                            <option value="Per Truck">Per Truck</option>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="number" name="items[0][rate]" class="form-control"
                                            step="0.01" required>
                                    </td>
                                    <td>
                                        <input type="text" name="items[0][transporter]"
                                            class="form-control" required>
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

        let rowCount = 1;

        function addRow(data = null) {
            let rateType = data ? data.rate_type : '';
            let rate = data ? data.rate : '';
            let transporter = data ? data.transporter : '';
            let qty = data ? data.qty : '';

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
                        <input type="text" name="items[${rowCount}][transporter]" class="form-control" value="${transporter}" required>
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
            rowCount++;
            updateRemoveButtons();
        }

        // Handle Sale Order Selection
        $('#sale_order_id').on('change', function() {
            let orderId = $(this).val();
            if (orderId) {
                $('.loader-container').show();
                let url = "{{ route('sales.logistics.getOrderDetails', ['id' => ':id']) }}";
                url = url.replace(':id', orderId);
                $.ajax({
                    url: url,
                    method: 'GET',
                    success: function(data) {
                        $('#date').val(data.date);
                        $('#so_no').val(data.so_no);
                        $('#so_qty').val(data.so_qty);
                        $('#commodity').val(data.commodity);
                        $('#sauda_type').val(data.sauda_type);
                        $('#customer').val(data.customer);
                        $('#delivery_address').val(data.delivery_address);
                        $('#location').val(data.location);
                        $('#factory').val(data.factory);
                        $('#section').val(data.section);
                        $('#loading_request').val($('#sale_order_id option:selected').text().trim());

                        // Handle existing logistics data
                        if (data.logistics) {
                            $('#date').val(data.logistics.date);
                            if(data.logistics.delivery_address) $('#delivery_address').val(data.logistics.delivery_address);
                            
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
                $('#date, #so_no, #so_qty, #commodity, #sauda_type, #customer, #delivery_address, #location, #factory, #section, #loading_request').val('');
                $('#itemsBody').empty();
                rowCount = 0;
                addRow();
            }
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
    });
</script>
