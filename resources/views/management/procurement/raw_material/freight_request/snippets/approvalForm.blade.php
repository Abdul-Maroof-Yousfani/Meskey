<form action="{{ route('raw-material.advance-payment-request-approval.store') }}" method="POST" id="ajaxSubmit">
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

    @if ($isUpdated && $approval)
        <div class="row">
            <div class="col-12">
                <div class="alert alert-info">
                    <strong>Approval Information:</strong><br>
                    <strong>Status:</strong> {{ ucfirst($approval->status) }}<br>
                    <strong>Approved By:</strong> {{ $approval->approver->name ?? 'N/A' }}<br>
                    <strong>Approved At:</strong> {{ $approval->created_at->format('Y-m-d H:i:s') }}<br>
                    @if ($approval->remarks)
                        <strong>Remarks:</strong> {{ $approval->remarks }}
                    @endif
                </div>
            </div>
        </div>
    @endif

    <div class="row bottom-button-bar">
        <div class="col-12">
            <a type="button" class="btn btn-danger modal-sidebar-close position-relative top-1">Close</a>
            @if (!$isUpdated)
                <button type="submit" class="btn btn-primary submitbutton">Save</button>
            @endif
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
            }
        });

        @if ($isUpdated)
            $('#ajaxSubmit input, #ajaxSubmit textarea, #ajaxSubmit select').not('[type="hidden"]').prop(
                'disabled', true);
        @endif
    });
</script>
