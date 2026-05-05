<form action="{{ route('export-outer-item.store') }}" method="POST" id="ajaxSubmit" autocomplete="off" enctype="multipart/form-data">
    @csrf
    <input type="hidden" id="listRefresh" value="{{ route('get.export-outer-item') }}" />

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

    <div id="ticketInfo" class="row mb-3" style="display: none;">
        <div class="col-md-6">
            <label>Truck No:</label>
            <input type="text" id="info_truck_no" class="form-control" readonly>
        </div>
        <div class="col-md-6">
            <label>Container No:</label>
            <input type="text" id="info_container_no" class="form-control" readonly>
        </div>
    </div>

    <div id="itemsSection" style="display: none;">
        <div class="row">
            <div class="col-12">
                <h6 class="header-heading-sepration">Outer Items</h6>
                <table class="table table-bordered table-striped" id="itemsTable">
                    <thead>
                        <tr>
                            <th width="35%">Item Name</th>
                            <th width="20%">Weight (Per Item)</th>
                            <th width="15%">Qty</th>
                            <th width="20%">Total Weight</th>
                            <th width="10%">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="item-row">
                            <td>
                                <select name="items[0][item_name]" class="form-control select2" required>
                                    <option value="">Select Item</option>
                                    @foreach($itemOptions as $option)
                                        <option value="{{ $option }}">{{ $option }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <input type="number" name="items[0][weight]" class="form-control item-weight" min="0" step="0.001" required value="0">
                            </td>
                            <td>
                                <input type="number" name="items[0][qty]" class="form-control item-qty" min="0" step="0.001" required value="0">
                            </td>
                            <td>
                                <input type="number" name="items[0][total_weight]" class="form-control item-total" step="0.001" readonly value="0">
                            </td>
                            <td>
                                <button type="button" class="btn btn-danger btn-sm remove-row"><i class="ft-x"></i></button>
                            </td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" class="text-right"><strong>Grand Total:</strong></td>
                            <td><input type="number" id="grand_total" class="form-control" readonly value="0"></td>
                            <td>
                                <button type="button" class="btn btn-success btn-sm" id="add-row"><i class="ft-plus"></i> Add</button>
                            </td>
                        </tr>
                    </tfoot>
                </table>
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
        $('.select2').select2({ dropdownParent: $('#modal-sidebar') });

        let rowIndex = 1;

        $('#add-row').click(function() {
            let options = '';
            @foreach($itemOptions as $option)
                options += '<option value="{{ $option }}">{{ $option }}</option>';
            @endforeach

            let newRow = `
                <tr class="item-row">
                    <td>
                        <select name="items[${rowIndex}][item_name]" class="form-control select2-new" required>
                            <option value="">Select Item</option>
                            ${options}
                        </select>
                    </td>
                    <td>
                        <input type="number" name="items[${rowIndex}][weight]" class="form-control item-weight" min="0" step="0.001" required value="0">
                    </td>
                    <td>
                        <input type="number" name="items[${rowIndex}][qty]" class="form-control item-qty" step="0.001" min="0" required value="0">
                    </td>
                    <td>
                        <input type="number" name="items[${rowIndex}][total_weight]" class="form-control item-total" step="0.001" readonly value="0">
                    </td>
                    <td>
                        <button type="button" class="btn btn-danger btn-sm remove-row"><i class="ft-x"></i></button>
                    </td>
                </tr>
            `;
            $('#itemsTable tbody').append(newRow);
            $('.select2-new').select2({ dropdownParent: $('#modal-sidebar') }).removeClass('select2-new');
            rowIndex++;
        });

        $(document).on('click', '.remove-row', function() {
            if ($('#itemsTable tbody tr').length > 1) {
                $(this).closest('tr').remove();
                calculateGrandTotal();
            } else {
                Swal.fire("Warning", "At least one row is required.", "warning");
            }
        });

        $(document).on('input', '.item-weight, .item-qty', function() {
            let row = $(this).closest('tr');
            let weight = parseFloat(row.find('.item-weight').val()) || 0;
            let qty = parseFloat(row.find('.item-qty').val()) || 0;
            let total = weight * qty;
            row.find('.item-total').val(total.toFixed(3));
            calculateGrandTotal();
        });

        function calculateGrandTotal() {
            let grandTotal = 0;
            $('.item-total').each(function() {
                grandTotal += parseFloat($(this).val()) || 0;
            });
            $('#grand_total').val(grandTotal.toFixed(3));
        }

        $('#loading_program_item_id').change(function() {
            let id = $(this).val();
            if (id) {
                let url = '{{ route("export.getOuterItemTicketData", ":id") }}';
                url = url.replace(':id', id);
                
                $.ajax({
                    url: url,
                    type: 'GET',
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
                            $('#info_truck_no').val(response.data.truck_number);
                            $('#info_container_no').val(response.data.container_number);
                            $('#info_ls_no').val(response.data.loading_slip_no);
                            $('#info_brand').val(response.data.brand);
                            $('#ticketInfo').show();
                            $('#itemsSection').show();
                        }
                    },
                    error: function() {
                        Swal.close();
                        Swal.fire("Error", "Failed to fetch ticket data.", "error");
                    }
                });
            } else {
                $('#ticketInfo').hide();
                $('#itemsSection').hide();
            }
        });
    });
</script>
