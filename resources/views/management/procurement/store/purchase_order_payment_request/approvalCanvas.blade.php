
    <input type="hidden" id="listRefresh" value="{{ route('store.get.purchase-order-payment-request') }}" />

    {{-- Payment Type --}}
    <div class="row form-mar">
        <div class="col-md-6">
            <div class="form-group">
                <label>Payment Type:</label>
                <div class="form-check">
                    <input class="form-check-input" 
                           type="checkbox" 
                           id="is_advance" 
                           name="is_advance" 
                           value="1" 
                           {{ $paymentRequest->is_advance_payment == 1 ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_advance">
                        Advance Payment
                    </label>
                </div>
            </div>
        </div>
    </div>

    {{-- Advance Section --}}
    <div id="advanceSection" style="display: none;">
        <div class="row form-mar">
            <div class="col-md-6">
                <div class="form-group d-flex flex-column mb-1">
                    <label>Select Purchase Order:</label>
                    <select class="form-control select2 w-100 d-block" id="purchase_order_id" name="purchase_order_id">
                        <option value="">Select Purchase Order</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="row form-mar">
            <div class="col-md-6">
                <label>PO Total Amount:</label>
                <input type="text" class="form-control" id="po_total_amount" readonly>
            </div>
            <div class="col-md-6">
                <label>Requested Amount:</label>
                <input type="text" class="form-control" id="po_requested_amount" readonly>
            </div>
            <div class="col-md-6">
                <label>Approved Amount:</label>
                <input type="text" class="form-control" id="po_paid_amount" readonly>
            </div>
            <div class="col-md-6 d-none">
                <label>Payment Amount:</label>
                <input type="number" class="form-control" id="po_payment_amount" step="0.01" min="0" value="0">
            </div>
            <div class="col-md-6">
                <label>Remaining Amount:</label>
                <input type="text" class="form-control" id="po_remaining_amount" readonly>
            </div>
        </div>
    </div>

    <input type="hidden" class="form-control" id="remaining_amount" name="remaining_amount" readonly>

    {{-- Receiving Section --}}
    <div id="receivingSection">
        <div class="row form-mar">
            <div class="col-md-6">
                <div class="form-group">
                    <label>Select GRN:</label>
                    <select class="form-control select2" id="purchase_order_receiving_id" name="purchase_order_receiving_id">
                        <option value="">Select GRN</option>
                        @if($paymentRequest->grn)
                            <option value="{{ $paymentRequest->grn->id }}" selected>
                                {{ $paymentRequest->grn->purchase_order_receiving_no }}
                            </option>
                        @endif
                    </select>
                </div>
            </div>
        </div>

        <div class="row form-mar">
            <div class="col-md-6">
                <label>GRN Total Amount:</label>
                <input type="text" class="form-control" id="grn_total_amount" readonly>
            </div>
            <div class="col-md-6">
                <label>Requested Paid:</label>
                <input type="text" class="form-control" id="grn_requested_amount" readonly>
            </div>
            <div class="col-md-6">
                <label>Approved Paid:</label>
                <input type="text" class="form-control" id="grn_paid_amount" readonly>
            </div>
            <div class="col-md-6 d-none">
                <label>Payment Amount:</label>
                <input type="number" class="form-control" id="grn_payment_amount" step="0.01" min="0" value="0">
            </div>
            <div class="col-md-6">
                <label>Remaining Amount:</label>
                <input type="text" class="form-control" id="grn_remaining_amount" readonly>
            </div>
        </div>
    </div>

    {{-- Supplier and Amount --}}
    <div class="row form-mar">
        <div class="col-md-6">
            <label>Supplier:</label>
            <input type="hidden" class="form-control" id="supplier_id" name="supplier_id" value="{{ $paymentRequest->supplier_id }}" readonly>
            <input type="text" class="form-control" id="supplier_name" name="supplier_name" value="{{ optional($paymentRequest->supplier)->name }}" readonly>
        </div>
        <div class="col-md-6">
            <label>Amount:</label>
            <input type="number" class="form-control" id="amount" name="amount" step="0.01" min="0" value="{{ $paymentRequest->amount }}" required>
        </div>
    </div>

    {{-- Description --}}
    <div class="row form-mar">
        <div class="col-md-12">
            <label>Description:</label>
            <textarea class="form-control" id="description" name="description" rows="3">{{ $paymentRequest->description }}</textarea>
        </div>
    </div>
<br>
    <div class="row">
        <div class="col-12">
            <x-approval-status :model="$data1" />
        </div>
    </div>

    <div class="row bottom-button-bar">
        <div class="col-12">
            <a type="button" class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton">Close</a>
            <button type="submit" class="btn btn-primary submitbutton">Update Payment Request</button>
        </div>
    </div>


<script>
    $(document).ready(function () {
        $('.select2').select2();

        // Store amounts for calculations
        let poTotalAmount = 0;
        let poPaidAmount = 0;
        let grnTotalAmount = 0;
        let grnPaidAmount = 0;
        let grnRequestedAmount = 0;
        let poRequestedAmount = 0;

        $('#is_advance').change(function () {
            if ($(this).is(':checked')) {
                $('#advanceSection').show();
                $('#receivingSection').hide();
                loadPurchaseOrders();
                $('#purchase_order_receiving_id').val('').trigger('change');
            } else {
                $('#advanceSection').hide();
                $('#receivingSection').show();
                loadGRNs();
                $('#purchase_order_id').val('').trigger('change');
            }
        });

        loadGRNs();

        function loadPurchaseOrders() {
            $.ajax({
                url: '{{ route('store.purchase-order-payment-request.get-sources') }}',
                type: 'GET',
                data: {
                    is_advance: true
                },
                success: function (response) {
                    $('#purchase_order_id').empty().append(
                        '<option value="">Select Purchase Order</option>');
                    response.purchase_orders.forEach(function (po) {
                        $('#purchase_order_id').append('<option value="' + po.id +
                            '" data-supplier-id="' + po.supplier_id +
                            '" data-supplier-name="' + po.supplier_name +
                            '" data-total="' + po.total_amount + '">' + '#' + po
                                .purchase_order_no + (po.supplier_name ? ' - ' + po
                                    .supplier_name : '') +
                            '</option>');
                    });
                }
            });
        }

        function loadGRNs(callback) {
    $.ajax({
        url: '{{ route('store.purchase-order-payment-request.get-sources') }}',
        type: 'GET',
        data: {
            is_advance: false
        },
        success: function (response) {
            $('#purchase_order_receiving_id').empty().append('<option value="">Select GRN</option>');

            response.grns.forEach(function (grn) {
                var totalAmount = grn.purchase_order_receiving_data.reduce(function (sum, item) {
                    return sum + parseFloat(item.total || 0);
                }, 0);
                $('#purchase_order_receiving_id').append('<option value="' + grn.id +
                    '" data-supplier-id="' + grn.supplier.id +
                    '" data-supplier-name="' + grn.supplier.name +
                    '" data-total="' + totalAmount + '">' + '#' + grn
                        .purchase_order_receiving_no + (grn.product?.name ? ' - ' + grn.product.name : '') +
                    '</option>');
            });

            if (typeof callback === 'function') callback();
        }
    });
}


        $('#purchase_order_id').change(function () {
            var purchaseOrderId = $(this).val();
            var supplierId = $(this).find(':selected').data('supplier-id');
            var supplierName = $(this).find(':selected').data('supplier-name');
            poTotalAmount = $(this).find(':selected').data('total');

            if (supplierId && supplierName) {
                $('#supplier_id').val(supplierId);
                $('#supplier_name').val(supplierName);
            }

            if (purchaseOrderId) {
                $.ajax({
                    url: '{{ route('store.purchase-order-payment-request.get-paid-amount') }}',
                    type: 'GET',
                    data: {
                        purchase_order_id: purchaseOrderId
                    },
                    success: function (response) {
                        poPaidAmount = response.paid_amount || 0;
                        poRequestedAmount = response.requested_amount || 0;

                        $('#po_total_amount').val(formatCurrency(poTotalAmount));
                        $('#po_paid_amount').val(formatCurrency(poPaidAmount));
                        $('#po_requested_amount').val(formatCurrency(poRequestedAmount));

                        updatePORemainingAmount();

                        $('#amount').val((poTotalAmount - poRequestedAmount).toFixed(2));
                    }
                });
            } else {
                resetPOFields();
            }
        });

        $('#purchase_order_receiving_id').change(function () {
            var grnId = $(this).val();
            var supplierId = $(this).find(':selected').data('supplier-id');
            var supplierName = $(this).find(':selected').data('supplier-name');
            grnTotalAmount = $(this).find(':selected').data('total');
            if (supplierId && supplierName) {
                $('#supplier_id').val(supplierId);
                $('#supplier_name').val(supplierName);
            }

            if (grnId) {
                $.ajax({
                    url: '{{ route('store.purchase-order-payment-request.get-paid-amount') }}',
                    type: 'GET',
                    data: {
                        purchase_order_receiving_id: grnId
                    },
                    success: function (response) {
                        grnPaidAmount = response.paid_amount || 0;
                        grnRequestedAmount = response.requested_amount || 0;

                        $('#grn_total_amount').val(formatCurrency(grnTotalAmount));
                        $('#grn_paid_amount').val(formatCurrency(grnPaidAmount));
                        $('#grn_requested_amount').val(formatCurrency(grnRequestedAmount));
                        let remaining = grnRequestedAmount - grnPaidAmount;
$('#grn_remaining_amount').val(formatCurrency(remaining));

                       // updateGRNRemainingAmount();

                     //   $('#amount').val((grnTotalAmount - grnRequestedAmount).toFixed(2));
                    }
                });
            } else {
                resetGRNFields();
            }
        });

        $('#po_payment_amount').on('input', function () {
            updatePORemainingAmount();
            $('#amount').val($(this).val());
        });

        $('#grn_payment_amount').on('input', function () {
            updateGRNRemainingAmount();
            $('#amount').val($(this).val());
        });

        $('#amount').on('input', function () {
            if ($('#is_advance').is(':checked')) {
                $('#po_payment_amount').val($(this).val());
                updatePORemainingAmount();
            } else {
                $('#grn_payment_amount').val($(this).val());
                updateGRNRemainingAmount();
            }
        });

        function updatePORemainingAmount() {
            let paymentAmount = parseFloat($('#po_payment_amount').val()) || 0;
            let remainingAmount = poTotalAmount - poRequestedAmount - paymentAmount;

            $('#po_remaining_amount').val(formatCurrency(remainingAmount));
            $('#remaining_amount').val((remainingAmount));

            if (paymentAmount > (poTotalAmount - poRequestedAmount)) {
                $('#po_payment_amount').addClass('is-invalid');
            } else {
                $('#po_payment_amount').removeClass('is-invalid');
            }
        }

function updateGRNRemainingAmount() {
 
    let enteredAmount = parseFloat($('#amount').val()) || 0;
    let remainingAmount = (grnTotalAmount - grnPaidAmount) - enteredAmount;
    $('#grn_remaining_amount').val(formatCurrency(remainingAmount));
    $('#remaining_amount').val(remainingAmount);

    if (enteredAmount > (grnTotalAmount - grnPaidAmount)) {
        $('#grn_payment_amount').addClass('is-invalid');
    } else {
        $('#grn_payment_amount').removeClass('is-invalid');
    }
}


        function resetPOFields() {
            poTotalAmount = 0;
            poPaidAmount = 0;
            poRequestedAmount = 0;
            $('#po_total_amount').val('');
            $('#po_paid_amount').val('');
            $('#po_requested_amount').val('');
            $('#po_payment_amount').val('0');
            $('#po_remaining_amount').val('');
            $('#remaining_amount').val('');
            $('#amount').val('0');
        }

        function resetGRNFields() {
            grnTotalAmount = 0;
            grnPaidAmount = 0;
            grnRequestedAmount = 0;
            $('#grn_total_amount').val('');
            $('#grn_paid_amount').val('');
            $('#grn_requested_amount').val('');
            $('#grn_payment_amount').val('0');
            $('#remaining_amount').val('');
            $('#grn_remaining_amount').val('');
            $('#amount').val('0');
        }

        function formatCurrency(amount) {
            return 'Rs. ' + parseFloat(amount).toLocaleString('en-PK', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        let isAdvance = {{ $paymentRequest->is_advance_payment }};
        let purchaseOrderId = "{{ $paymentRequest->purchase_order_id ?? '' }}";
        let grnId = "{{ $paymentRequest->purchase_order_receiving_id ?? '' }}";

        if (isAdvance == 1) {
            $('#advanceSection').show();
            $('#receivingSection').hide();

            loadPurchaseOrders();
            $(document).ajaxStop(function() {
                if (purchaseOrderId) {
                    $('#purchase_order_id').val(purchaseOrderId).trigger('change');
                }
            });
        } else {
            $('#advanceSection').hide();
            $('#receivingSection').show();

           loadGRNs(function() {
    if (grnId) {
        $('#purchase_order_receiving_id').val(grnId).trigger('change');
    }
});

        }

    });
</script>