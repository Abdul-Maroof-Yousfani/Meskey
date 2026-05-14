<form action="{{ route('export-outer-item.update', $ticket->id) }}" method="POST" id="ajaxSubmit" autocomplete="off" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    <input type="hidden" id="listRefresh" value="{{ route('get.export-outer-item') }}" />

    <div class="row form-mar">
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Ticket:</label>
                <input type="text" class="form-control" value="{{ $ticket->transaction_number }} -- {{ $ticket->truck_number }}" readonly>
                <input type="hidden" name="loading_program_item_id" value="{{ $ticket->id }}">
            </div>
        </div>
    </div>

    <div id="ticketInfo" class="row mb-3">
        <div class="col-md-6">
            <label>Truck No:</label>
            <input type="text" value="{{ $ticket->truck_number }}" class="form-control" readonly>
        </div>
        <div class="col-md-6">
            <label>Container No:</label>
            <input type="text" value="{{ $ticket->container_number }}" class="form-control" readonly>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <h6 class="header-heading-sepration">Other Items</h6>
            <table class="table table-bordered table-striped" id="itemsTable">
                <thead>
                    <tr>
                        <th width="35%">Item Name</th>
                        <th width="20%">Weight (Per Item) KG</th>
                        <th width="15%">Qty</th>
                        <th width="20%">Total Weight</th>
                        <th width="10%">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($ticket->outerItems as $index => $item)
                    <tr class="item-row">
                        <td>
                            <select name="items[{{ $index }}][item_name]" class="form-control select2" required>
                                <option value="">Select Item</option>
                                @foreach($itemOptions as $option)
                                    <option value="{{ $option }}" {{ $item->item_name == $option ? 'selected' : '' }}>{{ $option }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td>
                            <input type="number" name="items[{{ $index }}][weight]" class="form-control item-weight" min="0" step="0.001" required value="{{ $item->weight }}">
                        </td>
                        <td>
                            <input type="number" name="items[{{ $index }}][qty]" class="form-control item-qty" min="0" step="0.001" required value="{{ $item->qty }}">
                        </td>
                        <td>
                            <input type="number" name="items[{{ $index }}][total_weight]" class="form-control item-total" step="0.001" readonly value="{{ $item->total_weight }}">
                        </td>
                        <td>
                            <button type="button" class="btn btn-danger btn-sm remove-row"><i class="ft-x"></i></button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" class="text-right"><strong>Grand Total:</strong></td>
                        <td><input type="number" id="grand_total" class="form-control" readonly value="{{ $ticket->outerItems->sum('total_weight') }}"></td>
                        <td>
                            <button type="button" class="btn btn-success btn-sm" id="add-row"><i class="ft-plus"></i> Add</button>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <div class="row bottom-button-bar">
        <div class="col-12">
            <a type="button" class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton">Close</a>
            <button type="submit" class="btn btn-primary submitbutton">Update</button>
        </div>
    </div>
</form>

<script>
    $(document).ready(function() {
        $('.select2').select2({ dropdownParent: $('#modal-sidebar') });

        let rowIndex = {{ $ticket->outerItems->count() }};

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
                        <input type="number" name="items[${rowIndex}][qty]" class="form-control item-qty" min="0" step="0.001" required value="0">
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
    });
</script>
