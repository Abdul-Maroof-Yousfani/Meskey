<style>
    html,
    body {
        overflow-x: hidden;
    }
    #purchaseRequestTable input,
#purchaseRequestTable select {
    width: 100% !important;
    min-width: 120px; /* optional: prevents inputs from being too narrow */
    box-sizing: border-box; /* ensures padding/border don't break width */
}
</style>

<form action="{{ route('store.purchase-bill.update', $purchase_bill->id) }}" method="POST" id="ajaxSubmit"
    autocomplete="off">
    @csrf

    @method('PUT')
    <input type="hidden" id="listRefresh" value="{{ route('store.get.purchase-bill') }}" />
    <div class="row form-mar">
        <div class="col-md-3">
            <div class="form-group">
                <label class="form-label">Supplier:</label>
                <select id="supplier_id" name="supplier_id" class="form-control item-select select2">
                    <option value="">Select Vendor</option>
                    @foreach (get_supplier() as $supplier)
                        <option @selected($supplier->id == $purchase_bill->supplier_id) value="{{ $supplier->id }}">
                            {{ $supplier->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label>GRN:</label>
                <select class="form-control select2" name="grn_no" id="grn_no">
                    <option value="">Select GRN</option>
                    <option value="{{ $purchase_bill->grn->purchase_order_receiving_no }}" selected>
                        {{ $purchase_bill->grn->purchase_order_receiving_no }}</option>
                </select>
            </div>
        </div>


        <div class="col-md-3">
            <div class="form-group">
                <label>Bill Date:</label>
                <input type="date" id="purchase_date" name="purchase_bill_date"
                    value="{{ $purchase_bill->bill_date }}" class="form-control">
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="form-label">Reference No:</label>
                <input type="text" name="reference_no" value="{{ $purchase_bill->reference_no }}"
                    placeholder="Please select location and date." id="reference_no" class="form-control">
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label>Location:</label>
                <select name="company_location" id="company_location_id" class="form-control select2">
                    <option value="">Select Location</option>
                    @foreach (get_locations() as $value)
                        <option value="{{ $value->id }}" @selected($value->id == $purchase_bill->location_id)>{{ $value->name }}</option>
                    @endforeach
                    <input type="hidden" name="location_id" id="location_id">
                </select>
            </div>
        </div>
        <div class="col-xs-12 col-sm-12 col-md-12 row">
            <div class="form-group col-6">
                <label>Description (Optional):</label>
                <textarea name="description" id="description" placeholder="Description" class="form-control">{{ $purchase_bill->description }}</textarea>
            </div>
        </div>
    </div>
    <div class="row form-mar">
        <div class="col-md-12">
            <div style="overflow-x: auto; white-space: nowrap; width: 100%;">
                <table class="table table-bordered" id="purchaseRequestTable">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Description</th>
                            <th>Qty</th>
                            <th>Rate</th>
                            <th>Gross Amount</th>
                            <th>Discount %</th>
                            <th>Discount Amount</th>
                            <th>Deduction Per Piece</th>
                            <th>Deduction</th>
                            <th>Amount</th>
                            <th>GST %</th>
                            <th>GST Amount</th>
                            <th>Net Amount</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody id="billBody">
                        @foreach ($purchaseBillData as $key => $data)
                            <tr id="row_{{ $key }}">

                                <td style="width: 20%">
                                    
                                    <input type="text" style="width: 100%;" name="item[]" value="{{ getItem($data->item_id)?->name }}"
                                        id="item_{{ $key }}" class="form-control item" readonly>

                                    <input type="hidden" name="item_id[]" value="{{ $data->item_id }}">
                                    <input type="hidden" name="purchase_order_receiving_data_id[]" value="{{ $data->purchase_order_receiving_data_id }}">
                                </td>

                                <td style="width: 30%">
                                    <input type="text" style="width: 100%;" name="description[]" value="{{ $data->description }}"
                                        id="description_{{ $key }}" class="form-control uom" readonly>
                                </td>

                                <td style="width: 30%">
                                    <input style="width: 100%" type="number"
                                        onkeyup=""
                                        onblur="" name="qty[]" value="{{ $data->qty }}"
                                        id="qty_{{ $key }}" class="form-control qty" step="0.01" readonly
                                        {{-- {{ $isQuotationAvailable ? 'readonly' : '' }} --}}>
                                </td>

                                <td style="width: 30%">
                                    <input style="width: 100px" type="number"
                                        onkeyup=""
                                        onblur="" name="rate[]" value="{{ $data->rate }}"
                                        id="rate_{{ $key }}" class="form-control rate" step="0.01"
                                        readonly>
                                </td>

                                <td style="width: 30%">
                                    <input type="text" style="width: 100px;" name="gross_amount[]"
                                        value="{{ $data->gross_amount }}" id="gross_amount{{ $key }}"
                                        class="form-control gross_amount" readonly>
                                </td>

                                  <td style="width: 30%">


                                    <input style="width: 100px" type="number" name="discount_id[]"
                                        value="{{ $data->discount_percent }}" id="total_{{ $key }}"
                                        class="form-control discounts" onkeyup="calculatePercentage(this)"
                                        step="0.01" min="0">
                                </td>

                                <td style="width: 30%">
                                    <input style="width: 100px" type="number" readonly name="discount_amount[]"
                                        value="{{ $data->discount_amount }}"
                                        id="discount_amount_{{ $key }}" class="form-control discount_amount"
                                        step="0.01" min="0" readonly>
                                </td>
                                <td style="width: 30%">
                                    <input style="width: 100px" type="number" readonly name="deduction_per_piece[]"
                                        id="deduction_per_piece_{{ $key }}"
                                        value="{{ $data->deduction_per_piece }}"
                                        class="form-control deduction_per_piece" step="0.01" min="0"
                                        readonly>
                                </td>

                                <td style="width: 30%">
                                    <input style="width: 100px" type="number" readonly name="deduction[]"
                                        value="{{ $data->deduction }}" id="deduction_{{ $key }}"
                                        class="form-control deduction" step="0.01" min="0" readonly>
                                </td>

                                <td style="width: 30%">
                                    <input style="width: 100px" type="number" readonly name="net_amount[]"
                                        value="{{ $data->net_amount }}" id="total_{{ $key }}"
                                        class="form-control net_amount" step="0.01" min="0" readonly>
                                </td>

                                <td style="width: 30%">
                                    <input style="width: 100px" type="number" onkeyup="calculatePercentage(this)"
                                        name="tax_id[]" value="{{ $data->tax_percent }}"
                                        id="tax_id_{{ $key }}" class="form-control tax_id" step="0.01"
                                        min="0" readonly>
                                </td>
                                <td style="width: 30%">
                                    <input style="width: 100px" type="number" readonly
                                        onkeyup="calculatePercentage(this)" name="tax_amount[]"
                                        value="{{ $data->tax_amount }}" id="tax_id_{{ $key }}"
                                        class="form-control tax_amount" step="0.01" min="0" readonly>
                                </td>

                              
                                <td style="width: 30%">
                                    <input style="width: 100px" type="number" readonly name="final_amount[]"
                                        value="{{ $data->final_amount }}" id="final_amount_{{ $key }}"
                                        class="form-control final_amount" step="0.01" min="0" readonly>
                                </td>


                                <td>
                                    <button type="button" class="btn btn-danger btn-sm removeRowBtn"
                                        onclick="remove({{ $key }})"
                                        data-id="{{ $key }}" disabled>Remove</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <div class="row form-mar">
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Other Terms:</label>
                <textarea name="other_term" id="other_term" placeholder="Other Terms" class="form-control">1. EVERY BILL MUST SHOW OUR CONTRACT NUMBER 
2. SELLER HAS TO REPLACE THE REJECTED ITEMS (IF ANY) WITHIN THE STIPULATED TIME</textarea>
            </div>
        </div>
    </div>

    <input type="hidden" id="rowCount" value="0">
    <div class="row bottom-button-bar">
        <div class="col-12">
            <a type="button" class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton">Close</a>
            <button type="submit" class="btn btn-primary submitbutton">Save</button>
        </div>
    </div>
</form>




<script>
    $(document).ready(function() {

        $(document).on('change', '#purchase_date', function() {
            fetchUniqueNumber();
        });

        $(document).on('change', 'select[name="grn_no"]', function() {
            const purchaseRequestId = $(this).val();
            if (purchaseRequestId) {
                get_purchase(purchaseRequestId);
            }
        });

        $(document).on('change', '#quotation_no', function() {
            const purchaseRequestId = $('select[name="purchase_request_id"]').val();
            if (purchaseRequestId) {
                get_purchase(purchaseRequestId);
            }
        });

        function get_purchase(purchaseOrderReceivingId) {
            if (!purchaseOrderReceivingId) return;
            const supplierId = $('#supplier_id').val();
            $.ajax({
                url: "{{ route('store.purchase-bill.approve-item') }}",
                type: "GET",
                data: {
                    id: purchaseOrderReceivingId,
                    supplier_id: supplierId
                },
                cache: false,
                beforeSend: function() {
                    $('#billBody').html('<p>Loading...</p>');
                },
                success: function(response) {
                    $('#billBody').html(response.html);

                },
                error: function() {
                    $('#purchaseRequestBody').html('<p>Error loading data.</p>');
                }
            });
        }

        function getGrns() {
            let url = '/procurement/store/get-grns/';
            const $targetEl = $("#grn_no");
            $targetEl.select2({
                ajax: {
                    url: url,
                    dataType: "json",
                    delay: 250,
                    data: function(params) {
                        return {
                            supplier_id: $("#supplier_id").val()
                        };
                    },
                    processResults: function(data) {
                        console.log(data);
                        return {
                            results: data,
                        };
                    },
                },
                minimumInputLength: 0,
                allowClear: true,
                placeholder: "Select options",
            });

            // $.ajax({
            //     url: url,
            //     type: 'GET',
            //     data: {
            //         supplier_id: $("#supplier_id").val()
            //     },
            //     success: function(response) {
            //         console.log(response);
            //     },
            //     error: function(xhr, status, error) {
            //         $('#reference_no').val('');
            //     }
            // });
        }
        $(document).on('change', '#supplier_id, [name="grn_no"]', function() {
            getGrns();
            // const supplierId = $('#supplier_id').val();
            // const purchaseRequestId = $('[name="grn_no"]').val();
            // $('#quotation_no').empty();
            // if (supplierId) {
            //     initializeDynamicDependentCall1Select2(
            //         '#supplier_id',
            //         '#grn_no',
            //         'suppliers',
            //         'purchase_order_receiving_no',
            //         'id',
            //         'purchase_order_receivings',
            //         'supplier_id',
            //         'purchase_order_receiving_no',
            //         true,
            //         false,
            //         true,
            //         true,
            //     );
            // }
        });

    });
    $(".select2").select2();
    rowIndex = 1;



    function fetchUniqueNumber() {
        let locationId = $('#company_location_id').val();
        let contractDate = $('#purchase_date').val();
        if (locationId && contractDate) {
            let url = '/procurement/store/get-unique-number-purchase-bill/' + locationId + '/' + contractDate;
            $.ajax({
                url: url,
                type: 'GET',
                success: function(response) {
                    if (typeof response === 'string') {
                        $('#reference_no').val(response);
                    } else {
                        $('#reference_no').val('');
                    }
                },
                error: function(xhr, status, error) {
                    $('#reference_no').val('');
                }
            });
        } else {
            $('#reference_no').val('');
        }
    }
    $('#company_location_id, #purchase_date').on('change', fetchUniqueNumber);

    function get_purchase(purchaseRequestId = null) {
        const quotationNo = $('#quotation_no').val();
        const supplierId = $('#supplier_id').val();

        if (!purchaseRequestId && !quotationNo) return;

        if (!purchaseRequestId) {
            purchaseRequestId = $('select[name="purchase_request_id"]').val();
        }

        $.ajax({
            url: "{{ route('store.purchase-order.approve-item') }}",
            type: "GET",
            data: {
                id: purchaseRequestId,
                quotation_no: quotationNo,
                supplier_id: supplierId
            },
            beforeSend: function() {
                $('#purchaseOrderBody').html('<p>Loading...</p>');
            },
            success: function(response) {
                let html = response.html;
                let master = response.master;
                $('#company_location_id').val(master.location_id);
                $('#location_id').val(master.location_id);
                $('#description').val(master.description);
                $('#company_location_id').val(master.location_id).trigger('change');
                $('#purchaseOrderBody').html(html);
                $('.select2').select2({
                    placeholder: 'Please Select',
                    width: '100%'
                });
            },
            error: function() {
                $('#purchaseOrderBody').html('<p>Error loading data.</p>');
            }
        });
    }

    // $('#quotation_no, select[name="purchase_request_id"]').on('change', function () {
    //     const purchaseRequestId = $('select[name="purchase_request_id"]').val();
    //     $('input[name="qty[]"], input[name="rate[]"], input[name="total[]"]').each(function () {
    //         $(this).val(''); // set to empty
    //     });
    //     get_purchase(purchaseRequestId);
    // });


    function calc(num) {
        var excise_duty = parseFloat($('#excise_duty_' + num).val()) || 0;
        var qty = parseFloat($('#qty_' + num).val()) || 0;
        var rate = parseFloat($('#rate_' + num).val()) || 0;

        // get selected option and its data attribute
        var selectedOption = $('#tax_id_' + num + ' option:selected');
        var tax_percentage = parseFloat(selectedOption.data('percentage')) || 0;

        var subtotal = qty * rate;
        var tax_amount = subtotal * (tax_percentage / 100);
        var total = subtotal + tax_amount + excise_duty;

        $('#total_' + num).val(total.toFixed(2));
    }
    function round(num, decimals = 2) {
        return Number(Math.round(num + "e" + decimals) + "e-" + decimals);
    }

  function calculatePercentage(el) {
       const row = $(el).closest("tr");

    const gross_amount = row.find(".gross_amount");
    const rate = row.find(".rate");
    const qty = row.find(".qty");
    const discount_percent = row.find(".discounts");
    const final_amount = row.find(".final_amount");
    const tax_amount_input = row.find(".tax_amount");
    const discount_amount = row.find(".discount_amount");
    const tax_percent = row.find(".tax_id");
    const percent_amount = row.find(".percent_amount");
    const net_amount = row.find(".net_amount");
    const deduction_amount = row.find(".deduction").val();

    const rateVal = parseFloat(rate.val()) || 0;
    const qtyVal = parseFloat(qty.val()) || 0;
    const discountPercentVal = parseFloat(discount_percent.val()) || 0;
    const taxPercentVal = parseFloat(tax_percent.val()) || 0;

    // const percent_amount_of_gross = 1;

    // Clean values
    const gross = rateVal * qtyVal;
    gross_amount.val(gross);

    const net_amount_value = gross;
    const discount_amount_value =
        (discountPercentVal / 100) * gross;

    // Tax calculation
    const tax_amount =
        (taxPercentVal / 100) * ((net_amount_value - discount_amount_value) - deduction_amount);

    const tax_amount_rounded = round(tax_amount);
    const net_amount_rounded = round(gross - discount_amount_value);

    // Set values
    tax_amount_input.val(tax_amount_rounded);
    net_amount.val((net_amount_rounded - deduction_amount));
    discount_amount.val((discountPercentVal / 100) * net_amount_value);
    console.log(net_amount_value);
    // IMPORTANT: Use rounded tax value
    final_amount.val(round((net_amount_rounded - deduction_amount) + tax_amount_rounded));
    }
</script>
