@php
    $paymentType = isset($paymentType)
        ? $paymentType
        : old('payment_type') ?? ($paymentRequest->payment_type ?? 'against_receiving');
    $selectedPurchaseOrderId = old('purchase_order_id') ?? ($paymentRequest->purchaseOrder->id ?? null);
    $selectedGrnId = old('grn_id') ?? ($paymentRequest->grn->id ?? null);
    $amount = old('amount') ?? ($paymentRequest->amount ?? null);
    $description = old('description') ?? ($paymentRequest->description ?? null);
    $supplierId = old('supplier_id') ?? ($paymentRequest->supplier_id ?? null);
    $supplierName = old('supplier_name') ?? ($paymentRequest->supplier->name ?? null);
    $approvedAmount = $approvedAmount ?? null;
    $total = $model->purchaseOrderData[0]->total ?? $model->price;
@endphp

<form action="{{ route('raw-material.purchase-order-payment-request-approval.store') }}" method="POST" id="ajaxSubmit"
    autocomplete="off">
    @csrf
    <input type="hidden" id="listRefresh" value="{{ route('raw-material.get.payment-request-approval') }}" />
    <input type="hidden" name="total_amount" value="{{ $total }}" />
    <input type="hidden" name="payment_request_id" value="{{ $paymentRequest->id }}" />

    <div class="row form-mar">
        <div class="col-md-6">
            <div class="form-group">
                <label>Payment Type:</label>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="is_advance" name="is_advance" value="1"
                        {{ $paymentType == 'advance' ? 'checked' : '' }} disabled>
                    <label class="form-check-label" for="is_advance">
                        Advance Payment
                    </label>
                </div>
            </div>
        </div>
    </div>

    <div id="advanceSection" style="{{ $paymentType == 'advance' ? '' : 'display: none;' }}">
        <div class="row form-mar">
            <div class="col-md-6">
                <div class="form-group d-flex flex-column mb-1">
                    <label>Select Purchase Order:</label>
                    <select class="form-control select2 w-100 d-block" id="purchase_order_id" name="purchase_order_id"
                        disabled>
                        <option value="{{ $model->id }}">{{ $model->purchase_order_no }}</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="row form-mar">
            <div class="col-md-6">
                <div class="form-group">
                    <label>PO Total Amount:</label>
                    <input type="text" class="form-control" id="po_total_amount" readonly
                        value="{{ $total }}">
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>Requested Amount:</label>
                    <input type="text" class="form-control" id="po_paid_amount" readonly
                        value="{{ $requestedAmount }}">
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>Paid Amount:</label>
                    <input type="text" class="form-control" id="po_paid_amount" readonly
                        value="{{ $approvedAmount }}">
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>Remaining Amount:</label>
                    <input type="text" class="form-control" id="po_remaining_amount" readonly
                        value="{{ $total - $approvedAmount }}">
                </div>
            </div>
        </div>
    </div>
    <input type="hidden" class="form-control" id="remaining_amount" name="remaining_amount" readonly>

    <div id="receivingSection" style="{{ $paymentType == 'advance' ? 'display: none;' : '' }}">
        <div class="row form-mar">
            <div class="col-md-6">
                <div class="form-group">
                    <label>Select GRN:</label>
                    <select class="form-control select2" id="grn_id" name="grn_id" disabled>
                        <option value="{{ $model->id }}">{{ $model->grn_number }}</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="row form-mar">
            <div class="col-md-6">
                <div class="form-group">
                    <label>GRN Total Amount:</label>
                    <input type="text" class="form-control" value="{{ $total }}" id="grn_total_amount"
                        readonly>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>Requested Amount:</label>
                    <input type="text" class="form-control" id="po_paid_amount" readonly
                        value="{{ $requestedAmount }}">
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>Approved Amount:</label>
                    <input type="text" class="form-control" value="{{ $approvedAmount }}" id="grn_paid_amount"
                        readonly>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>Remaining Amount:</label>
                    <input type="text" class="form-control" value="{{ $total - $approvedAmount }}"
                        id="grn_remaining_amount" readonly>
                </div>
            </div>
        </div>
    </div>

    <div class="row form-mar">
        <div class="col-md-6">
            <div class="form-group">
                <label>Supplier:</label>
                <input type="hidden" class="form-control" id="supplier_id" name="supplier_id"
                    value="{{ $supplierId }}" readonly>
                <input type="text" class="form-control" id="supplier_name" name="supplier_name"
                    value="{{ $supplierName }}" readonly>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label>Amount:</label>
                <input type="number" class="form-control" id="amount" name="amount" step="0.01"
                    min="0" required value="{{ $amount }}">
            </div>
        </div>
    </div>


    <div class="row form-mar">
        <div class="col-md-12">
            <div class="form-group">
                <label>Description:</label>
                <textarea class="form-control" id="description" disabled name="description" rows="3">{{ $description }}</textarea>
            </div>
        </div>
        <div class="col-12">
            <div class="form-group">
                <label>Status:</label>
                <select name="status" id="approvalStatus" class="form-control select2"
                    {{ $isUpdated ? 'disabled' : '' }}>
                    <option value="">Select Status</option>
                    <option value="approved" {{ $approval && $approval->status == 'approved' ? 'selected' : '' }}>
                        Approved</option>
                    <option value="rejected" {{ $approval && $approval->status == 'rejected' ? 'selected' : '' }}>
                        Rejected</option>
                </select>
                @if ($isUpdated)
                    <input type="hidden" name="status" value="{{ $approval->status ?? '' }}">
                @endif
            </div>
        </div>
    </div>

    <div class="row bottom-button-bar">
        <div class="col-12">
            <a type="button" class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton">Close</a>
            <button type="submit" class="btn btn-primary submitbutton">Approve Payment Request</button>
        </div>
    </div>
</form>

<script>
    $(document).ready(function() {
        $('.select2').select2();

        // Store amounts for calculations
        let poTotalAmount = 0;
        let poPaidAmount = 0;
        let grnTotalAmount = 0;
        let grnPaidAmount = 0;

        // Set initial section visibility based on paymentType
        function toggleSections() {
            if ($('#is_advance').is(':checked')) {
                $('#advanceSection').show();
                $('#receivingSection').hide();
            } else {
                $('#advanceSection').hide();
                $('#receivingSection').show();
            }
        }
        toggleSections();

        $('#is_advance').change(function() {
            toggleSections();
        });

        // Load options if not already loaded from backend
        @if (!isset($purchaseOrders))
            // function loadPurchaseOrders(selectedId = null) {
            //     $.ajax({
            //         url: '{{ route('store.purchase-order-payment-request.get-sources') }}',
            //         type: 'GET',
            //         data: {
            //             is_advance: true
            //         },
            //         success: function(response) {
            //             $('#purchase_order_id').empty().append(
            //                 '<option value="">Select Purchase Order</option>');
            //             response.purchase_orders.forEach(function(po) {
            //                 $('#purchase_order_id').append('<option value="' + po.id +
            //                     '" data-supplier-id="' + po.supplier_id +
            //                     '" data-supplier-name="' + po.supplier_name +
            //                     '" data-total="' + po.total_amount + '"' +
            //                     (selectedId && selectedId == po.id ? ' selected' : '') +
            //                     '>' + po.purchase_order_no + (po.supplier?.name || '') +
            //                     '</option>');
            //             });
            //             if (selectedId) {
            //                 $('#purchase_order_id').val(selectedId).trigger('change');
            //             }
            //         }
            //     });
            // }
        @endif

        @if (!isset($grns))
            // function loadGRNs(selectedId = null) {
            //     $.ajax({
            //         url: '{{ route('store.purchase-order-payment-request.get-sources') }}',
            //         type: 'GET',
            //         data: {
            //             is_advance: false
            //         },
            //         success: function(response) {
            //             $('#grn_id').empty().append('<option value="">Select GRN</option>');
            //             response.grns.forEach(function(grn) {
            //                 $('#grn_id').append('<option value="' + grn.id +
            //                     '" data-supplier-id="' + grn.supplier.id +
            //                     '" data-supplier-name="' + grn.supplier.name +
            //                     '" data-total="' + grn.price + '"' +
            //                     (selectedId && selectedId == grn.id ? ' selected' :
            //                         '') +
            //                     '>' + grn.grn_number + (grn.product?.name || '') +
            //                     '</option>');
            //             });
            //             if (selectedId) {
            //                 $('#grn_id').val(selectedId).trigger('change');
            //             }
            //         }
            //     });
            // }
        @endif

        // Default select for edit/view
        @if ($paymentType == 'advance')
            @if (!isset($purchaseOrders))
                loadPurchaseOrders({{ $selectedPurchaseOrderId ?? 'null' }});
            @else
                $('#purchase_order_id').val('{{ $selectedPurchaseOrderId }}').trigger('change');
            @endif
        @else
            @if (!isset($grns))
                loadGRNs({{ $selectedGrnId ?? 'null' }});
            @else
                $('#grn_id').val('{{ $selectedGrnId }}').trigger('change');
            @endif
        @endif

        $('#purchase_order_id').change(function() {
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
                    success: function(response) {
                        poPaidAmount = response.paid_amount || 0;

                        $('#po_total_amount').val(formatCurrency(poTotalAmount));
                        $('#po_paid_amount').val(formatCurrency(poPaidAmount));

                        updatePORemainingAmount();

                        $('#amount').val((poTotalAmount - poPaidAmount).toFixed(2));
                    }
                });
            } else {
                resetPOFields();
            }
        });

        $('#grn_id').change(function() {
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
                        grn_id: grnId
                    },
                    success: function(response) {
                        grnPaidAmount = response.paid_amount || 0;

                        $('#grn_total_amount').val(formatCurrency(grnTotalAmount));
                        $('#grn_paid_amount').val(formatCurrency(grnPaidAmount));

                        updateGRNRemainingAmount();

                        $('#amount').val((grnTotalAmount - grnPaidAmount).toFixed(2));
                    }
                });
            } else {
                resetGRNFields();
            }
        });

        $('#po_payment_amount').on('input', function() {
            updatePORemainingAmount();
            $('#amount').val($(this).val());
        });

        $('#grn_payment_amount').on('input', function() {
            updateGRNRemainingAmount();
            $('#amount').val($(this).val());
        });

        $('#amount').on('input', function() {
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
            let remainingAmount = poTotalAmount - poPaidAmount - paymentAmount;

            $('#po_remaining_amount').val(formatCurrency(remainingAmount));
            $('#remaining_amount').val((remainingAmount));

            if (paymentAmount > (poTotalAmount - poPaidAmount)) {
                $('#po_payment_amount').addClass('is-invalid');
            } else {
                $('#po_payment_amount').removeClass('is-invalid');
            }
        }

        function updateGRNRemainingAmount() {
            let paymentAmount = parseFloat($('#grn_payment_amount').val()) || 0;
            let remainingAmount = grnTotalAmount - grnPaidAmount - paymentAmount;

            $('#grn_remaining_amount').val(formatCurrency(remainingAmount));
            $('#remaining_amount').val((remainingAmount));

            if (paymentAmount > (grnTotalAmount - grnPaidAmount)) {
                $('#grn_payment_amount').addClass('is-invalid');
            } else {
                $('#grn_payment_amount').removeClass('is-invalid');
            }
        }

        function resetPOFields() {
            poTotalAmount = 0;
            poPaidAmount = 0;
            $('#po_total_amount').val('');
            $('#po_paid_amount').val('');
            $('#po_payment_amount').val('0');
            $('#po_remaining_amount').val('');
            $('#remaining_amount').val('');
            $('#amount').val('0');
        }

        function resetGRNFields() {
            grnTotalAmount = 0;
            grnPaidAmount = 0;
            $('#grn_total_amount').val('');
            $('#grn_paid_amount').val('');
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
    });
</script>
