
<form action="{{ route('store.debit-note.update', $debitNote->id) }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    @method('PUT')
    <input type="hidden" id="listRefresh" value="{{ route('store.get.debit-notes') }}" />

    <div class="row form-mar">
        <div class="col-md-6">
            <div class="form-group">
                <label for="grn_id">GRN:</label>
                <select class="form-control select2" name="grn_id" id="grn_id">
                    <option value="">Select GRN</option>
                    @foreach ($grns as $grn)
                        <option value="{{ $grn->id }}" {{ $debitNote->grn_id == $grn->id ? 'selected' : '' }}>
                            {{ $grn->purchase_order_receiving_no }} - 
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="bill_id">Bill:</label>
                <select class="form-control select2" name="bill_id" id="bill_id">
                    <option value="">Select Bill</option>
                    @foreach ($bills as $bill)
                        <option value="{{ $bill->id }}" {{ $debitNote->bill_id == $bill->id ? 'selected' : '' }}>
                            {{ $bill->bill_no }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>
    <div class="row form-mar">
        <div class="col-md-6">
            <div class="form-group">
                <label>Date:</label>
                <input type="date" class="form-control" name="transaction_date" id="transaction_date" value="{{ old('transaction_date', $debitNote->transaction_date ? \Carbon\Carbon::parse($debitNote->transaction_date)->format('Y-m-d') : '') }}" onchange="get_transaction_number()">
            </div>
        </div>
         <div class="col-md-6">
            <div class="form-group">
                <label>Reference Number:</label>
                <input type="text" name="reference_number" readonly id="reference_number" class="form-control" value="{{ old('reference_number', $debitNote->reference_number) }}">
            </div>
        </div>
    </div>

    <div class="row form-mar">
        <div class="col-md-12">
            <div id="debitNoteItems" style="display: block;">
                <h4>Debit Note Items</h4>
                <table class="table table-bordered" id="debitNoteTable">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>GRN Qty</th>
                            <th>Debit Note Qty</th>
                            <th>Rate</th>
                            <th>Amount</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="debitNoteBody">
                        <!-- Items will be populated here -->
                        @foreach($debitNote->debit_note_data as $item)
                            <tr>
                                <td>
                                    <input type="hidden" name="item_id[]" value="{{ $item->item_id }}">
                                    <input type="hidden" name="bill_data_id[]" value="{{ $item->purchase_bill_data_id }}">
                                    {{ $item->item->name ?? 'N/A' }}
                                </td>
                                <td>
                                    <input type="hidden" name="grn_qty[]" value="{{ purchaseBillDistribution($item->purchase_bill_data_id) }}">
                                    {{ purchaseBillDistribution($item->purchase_bill_data_id) + $item->debit_note_quantity }}
                                </td>
                                <td>
                                    <input type="number" oninput="calc(this)" onchange="calc(this)" name="debit_note_quantity[]" class="form-control" step="0.01" min="0" max="{{ purchaseBillDistribution($item->purchase_bill_data_id) + $item->debit_note_quantity }}" value="{{ $item->debit_note_quantity }}" required>
                                </td>
                                <td>
                                    <input type="hidden" name="rate[]" value="{{ $item->rate }}">
                                    {{ $item->rate }}
                                </td>
                                <td>
                                    <input type="hidden" name="amount[]" value="{{ $item->amount }}">
                                    <input type="number" class="form-control amount-field" value="{{ number_format($item->amount, 2) }}" readonly step="0.01">
                                </td>
                                <td>
                                    <button type="button" class="btn btn-danger btn-sm" onclick="removeRow(this)">Remove</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
        </div>
    </div>
    <input type="hidden" id="rowCount" value="0">
    <div class="row bottom-button-bar">
        <div class="col-12">
            <a type="button" class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton">Close</a>
            <button type="submit" class="btn btn-primary submitbutton">Update</button>
        </div>
    </div>
</form>



<script>
    $(document).ready(function () {
        $('.select2').select2({
            placeholder: 'Please Select',
            width: '100%'
        });

        // When GRN is selected, populate bills
        $('#grn_id').on('change', function () {
            const grnId = $(this).val();
            if (grnId) {
                getBillsForGRN(grnId);
            } else {
                $('#bill_id').html('<option value="">Select Bill</option>');
                $('#debitNoteItems').hide();
                $('#debitNoteBody').html('');
            }
        });

        // When bill is selected, populate items
        $('#bill_id').on('change', function () {
            const billId = $(this).val();
            if (billId) {
                getItemsForBill(billId);
            } else {
                $('#debitNoteItems').hide();
                $('#debitNoteBody').html('');
            }
        });

        // Show items initially since we have existing data
        $('#debitNoteItems').show();

        // Generate reference number on page load based on existing transaction date
        if ($('#transaction_date').val()) {
            get_transaction_number();
        }
    });

    function getBillsForGRN(grnId) {
        $.ajax({
            url: "{{ url('/procurement/store/debit-note/get-bills') }}/" + grnId,
            type: "GET",
            beforeSend: function () {
                $('#bill_id').html('<option value="">Loading...</option>');
            },
            success: function (response) {
                let options = '<option value="">Select Bill</option>';
                response.forEach(function(bill) {
                    options += `<option value="${bill.id}">${bill.bill_no}</option>`;
                });
                $('#bill_id').html(options);
                $('#bill_id').trigger('change.select2');
            },
            error: function () {
                $('#bill_id').html('<option value="">Error loading bills</option>');
                toastr.error('Error loading bills for selected GRN');
            }
        });
    }

    function getItemsForBill(billId) {
        $.ajax({
            url: "{{ url('/procurement/store/debit-note/get-bill-items') }}/" + billId,
            type: "GET",
            success: function (response) {
                populateDebitNoteItems(response.items || []);
            },
            error: function () {
                toastr.error('Error loading items for selected bill');
            }
        });
    }

    function get_transaction_number() {
        $.ajax({
            url: "{{ route('store.debit-note.get-number') }}",
            data: {
                contract_date: $("#transaction_date").val()
            },
            type: "GET",
            success: function (response) {
                $("#reference_number").val(response);

            },
            error: function () {
                toastr.error('Error loading items for selected bill');
            }
        });
    }


    function populateDebitNoteItems(items) {
        let html = '';
        items.forEach(function(item, index) {
            html += `
                <tr>
                    <td>
                        <input type="hidden" name="item_id[]" value="${item.item_id}">
                        <input type="hidden" name="bill_data_id[]" value="${item.id}">
                        ${item.item?.name || 'N/A'}
                    </td>
                    <td>
                        <input type="hidden" name="grn_qty[]" value="${item.remaining_qty || 0}">
                        ${item.remaining_qty || 0}
                    </td>
                    <td>
                        <input type="number" oninput="calc(this)" onchange="calc(this)" name="debit_note_quantity[]" class="form-control" step="0.01" min="0" max="${item.remaining_qty || 0}" required>
                    </td>
                    <td>
                        <input type="hidden" name="rate[]" value="${item.rate || 0}">
                        ${item.rate || 0}
                    </td>
                    <td>
                        <input type="hidden" name="amount[]" value="0">
                        <input type="number" class="form-control amount-field" value="0.00" readonly step="0.01">
                    </td>
                    <td>
                        <button type="button" class="btn btn-danger btn-sm" onclick="removeRow(this)">Remove</button>
                    </td>
                </tr>
            `;
        });

        $('#debitNoteBody').html(html);
        $('#debitNoteItems').show();
    }

    function removeRow(button) {
        $(button).closest('tr').remove();
        if ($('#debitNoteBody tr').length === 0) {
            $('#debitNoteItems').hide();
        }
    }


    function calc(el) {
        const row = $(el).closest('tr');
        const quantity = parseFloat($(el).val()) || 0;
        const rate = parseFloat(row.find('input[name="rate[]"]').val()) || 0;
        const amount = quantity * rate;

        // Update the amount fields with 2 decimal places
        row.find('input[name="amount[]"]').val(amount.toFixed(2));
        row.find('.amount-field').val(amount.toFixed(2));

        console.log('Calculation:', { quantity, rate, amount: amount.toFixed(2) });
    }
</script>
