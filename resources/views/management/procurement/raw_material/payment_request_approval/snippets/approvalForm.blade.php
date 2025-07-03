<form action="{{ route('raw-material.payment-request-approval.store') }}" method="POST" id="ajaxSubmit">
    @csrf
    <input type="hidden" id="listRefresh" value="{{ route('raw-material.get.payment-request-approval') }}" />
    <input type="hidden" name="payment_request_id" value="{{ $paymentRequest->id }}">

    {!! $requestPurchaseForm !!}

    <div class="row">
        <div class="col-12">
            <h6 class="header-heading-sepration">
                Payment Request Approval
            </h6>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label>Contract No</label>
                <input type="text" class="form-control"
                    value="#{{ $paymentRequest->paymentRequestData->purchaseOrder->contract_no ?? 'N/A' }}" readonly>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label>Supplier</label>
                <input type="text" class="form-control"
                    value="{{ $paymentRequest->paymentRequestData->supplier_name ?? 'N/A' }}" readonly>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label>Request Type</label>
                <input type="text" class="form-control" value="{{ formatEnumValue($paymentRequest->request_type) }}"
                    readonly>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label>Original Amount</label>
                <input type="text" class="form-control" value="{{ number_format($paymentRequest->amount, 2) }}"
                    readonly>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label>Status:</label>
                <select name="status" id="approvalStatus" class="form-control select2">
                    <option value="">Select Status</option>
                    <option value="approved" {{ $approval && $approval->status == 'approved' ? 'selected' : '' }}>
                        Approved</option>
                    <option value="rejected" {{ $approval && $approval->status == 'rejected' ? 'selected' : '' }}>
                        Rejected</option>
                </select>
            </div>
        </div>
        <div class="col-md-12">
            <div class="form-group">
                <label>Remarks</label>
                <textarea id="approvalRemarks" name="remarks" class="form-control" rows="3">{{ $approval->remarks ?? '' }}</textarea>
            </div>
        </div>
    </div>

    <div class="row bottom-button-bar">
        <div class="col-12">
            <a type="button" class="btn btn-danger modal-sidebar-close position-relative top-1">Close</a>
            <button type="submit" class="btn btn-primary submitbutton">Save</button>
        </div>
    </div>
</form>

<script>
    $(document).ready(function() {
        $('#approvalStatus').select2();

        $('#ajaxSubmit').on('submit', function(e) {
            const status = $('#approvalStatus').val();
            if (status === 'approved') {
                const paymentRequestAmount = parseFloat($('input[name="payment_request_amount"]')
                    .val()) || 0;
                const totalAmount = parseFloat($('#total_amount').val()) || 0;

                if (paymentRequestAmount > totalAmount) {
                    e.preventDefault();
                    alert('Payment request amount cannot exceed total amount');
                    return false;
                }
            }
        });
    });
</script>
