<form action="{{ route('raw-material.payment-request.store') }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    <input type="hidden" id="listRefresh" value="{{ route('raw-material.get.payment-request') }}" />

    <div class="row form-mar">
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Contract:</label>
                <select name="arrival_payment_request_id" id="{{ 'arrival_payment_request_id' }}"
                    class="form-control select2">
                    <option value="">Select Contract</option>
                    @foreach ($purchaseOrders as $purchaseOrder)
                        <option value="{{ $purchaseOrder->id }}"
                            data-is-decision-pending="{{ $purchaseOrder->decision_making }}">
                            {{ $purchaseOrder->contract_no }}
                            {{ isset($purchaseOrder->qcProduct->name) ? "({$purchaseOrder->qcProduct->name})" : '' }}
                            {{ isset($purchaseOrder->truck_no) ? " - Truck: {$purchaseOrder->truck_no}" : '' }}
                            {{ isset($purchaseOrder->supplier->name) ? " - Supplier: {$purchaseOrder->supplier->name}" : '' }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <div id="slabsContainer">
    </div>

    <div id="decisionWarning" class="alert alert-warning" style="display: none;">
        <strong>Warning!</strong> You cannot create a payment request for this contract. Please apply deductions first.
    </div>

    <div class="row bottom-button-bar">
        <div class="col-12">
            <a type="button" class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton">Close</a>
            <button type="submit" class="btn btn-primary submitbutton" id="saveButton">Save</button>
        </div>
    </div>
</form>

<script>
    $(document).ready(function() {
        $('#arrival_payment_request_id').change(function() {
            var paymentRequestId = $(this).val();
            var selectedOption = $(this).find('option:selected');
            var isDecisionPending = selectedOption.data('is-decision-pending');

            if (isDecisionPending == 1) {
                $('#decisionWarning').show();
                $('#saveButton').hide();
            } else {
                $('#decisionWarning').hide();
                $('#saveButton').show();
            }

            if (paymentRequestId) {
                $.ajax({
                    url: '{{ route('getSlabsByPaymentRequestParams') }}',
                    type: 'GET',
                    data: {
                        purchase_order_id: paymentRequestId
                    },
                    dataType: 'json',
                    beforeSend: function() {
                        Swal.fire({
                            title: "Processing...",
                            text: "Please wait while fetching slabs.",
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });
                    },
                    success: function(response) {
                        Swal.close();
                        if (response.success) {
                            // Append the rendered HTML to a container element
                            $('#slabsContainer').html(response.html);
                        } else {
                            Swal.fire("No Data", "No slabs found for this product.",
                                "info");
                        }
                    },
                    error: function() {
                        Swal.close();
                        Swal.fire("Error", "Something went wrong. Please try again.",
                            "error");
                    }
                });
            }
        });

        $('.select2').select2();

        initializeDynamicSelect2('#party_ref_no', 'arrival_custom_sampling', 'party_ref_no', 'party_ref_no',
            true,
            false);
    });
</script>
