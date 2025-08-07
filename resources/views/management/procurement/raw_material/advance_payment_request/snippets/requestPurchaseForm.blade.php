@php
    $hasLoadingWeight = false;
    $loadingWeight = 0;
    $totalAmount = 0;
    $remaining = 0;
    $bagWeight = $purchaseOrder->bag_weight ?? 0;
    $bagRate = $purchaseOrder->bag_rate ?? 0;

    $totalDeductions = 0;
    $ratePerKg = $purchaseOrder->rate_per_kg ?? 0;
    $netWeight = 0;

    $requestedAmount = $requestedAmount ?? 0;
    $paidAmount = $approvedAmount ?? 0;
    $grossAmount = $ratePerKg * $loadingWeight;
    $existingOtherDeductionKg = $otherDeduction->other_deduction_kg ?? 0;
    $existingOtherDeductionAmount = $otherDeduction->other_deduction_value ?? 0;
    $isApprovalPage = isset($isRequestApprovalPage) && $isRequestApprovalPage;
    $currentPaymentAmount = 0;
    $currentFreightAmount = 0;
    $isPaymentType = 0;
    if ($isApprovalPage && isset($paymentRequest)) {
        if ($paymentRequest->request_type === 'payment') {
            $currentPaymentAmount = $paymentRequest->amount;
            $isPaymentType = 1;
        } else {
            $currentFreightAmount = $paymentRequest->amount;
            $isPaymentType = 2;
        }
    }

@endphp

<style>
    .tooltip-container {
        position: relative;
        cursor: pointer;
    }

    .tooltip-content {
        display: none;
        position: absolute;
        bottom: 100%;
        left: 50%;
        transform: translateX(-50%);
        background: white;
        border: 1px solid #ddd;
        padding: 10px;
        min-width: 200px;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        z-index: 100;
    }

    .tooltip-container:hover .tooltip-content {
        display: block;
    }

    .section-title {
        color: #2c3e50;
        font-weight: 600;
        margin-bottom: 15px;
    }

    .form-control[readonly] {
        background-color: #f8f9fa;
        opacity: 1;
    }

    .editable-field {
        background-color: #fff !important;
        border-color: #27489a;
    }

    .other-deduction-row {
        background-color: #f8f9fa;
        border-left: 4px solid #27489a;
    }

    /* .lumpsum-row {
        background-color: #e8f4fd;
        border-left: 4px solid #007bff;
    } */

    .approval-editable {
        background-color: #fff3cd !important;
        border-color: #ffc107 !important;
    }
</style>

<input type="hidden" name="purchase_order_id" value="{{ $purchaseOrder->id ?? '' }}">
<input type="hidden" id="original_bag_weight" value="{{ $bagWeight }}">
<input type="hidden" id="original_bag_rate" value="{{ $bagRate }}">
<input type="hidden" id="rate_per_kg" value="{{ $ratePerKg }}">

<div class="row">
    <div class="col-12">
        <h6 class="header-heading-sepration">
            Basic Information
        </h6>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label>Contract #</label>
            <input type="text" class="form-control" name="contract_no"
                value="{{ $purchaseOrder->contract_no ?? 'N/A' }}" readonly>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label>Supplier Name</label>
            <input type="text" class="form-control" name="supplier_name"
                value="{{ $purchaseOrder->supplier_name ?? ($purchaseOrder->supplier->name ?? 'N/A') }}" readonly>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label>Contract Rate</label>
            <input type="text" class="form-control" name="contract_rate"
                value="{{ $purchaseOrder->rate_per_kg ?? 0 }}" readonly>
        </div>
    </div>
    <div class="col-md-6 contract-range-field">
        <div class="form-group">
            <label>Contract Range</label>
            <input type="text" class="form-control" name="contract_range"
                value="{{ $purchaseOrder->min_quantity ?? 0 }} - {{ $purchaseOrder->max_quantity ?? 0 }}" readonly>
            <input type="hidden" name="min_contract_range" value="{{ $purchaseOrder->min_quantity ?? 0 }}">
            <input type="hidden" name="max_contract_range" value="{{ $purchaseOrder->max_quantity ?? 0 }}">
        </div>
    </div>
</div>

<div class="row mt-3">
    <div class="col-12">
        <h6 class="header-heading-sepration">
            Loading Information
        </h6>
    </div>
    <div class="col-md-12 mb-3">
        <div class="form-check form-check-inline">
            <input class="form-check-input" disabled type="radio" name="loading_type" id="loading" value="loading"
                {{ $hasLoadingWeight ? 'checked' : '' }} {{ $hasLoadingWeight ? '' : 'disabled' }}>
            <label class="form-check-label" for="loading">Loading</label>
        </div>
        <div class="form-check form-check-inline">
            <input class="form-check-input" disabled type="radio" name="loading_type" id="without_loading"
                value="without_loading" {{ !$hasLoadingWeight ? 'checked' : '' }}
                {{ $hasLoadingWeight ? '' : 'disabled' }}>
            <label class="form-check-label" for="without_loading">Without Loading</label>
        </div>
        <input type="hidden" name="{{ $hasLoadingWeight ? '' : 'loading_type' }}"
            value="{{ $hasLoadingWeight ? 'loading' : 'without_loading' }}">
    </div>
    @php
        $totalSupplierCommission = 0;

        $maxCalculatedAmount = ($purchaseOrder->max_quantity ?? 0) * ($purchaseOrder->rate_per_kg ?? 0);
        $minCalculatedAmount = ($purchaseOrder->min_quantity ?? 0) * ($purchaseOrder->rate_per_kg ?? 0);

        $totalAmount = $maxCalculatedAmount;
    @endphp
    <div class="col mb-3 px-0">
        <div class="row mx-auto">
            <div class="col-md-6 contract-range-field">
                <div class="form-group">
                    <label>Amount</label>
                    <input type="text" class="form-control" name="contract_range"
                        value="{{ $maxCalculatedAmount ?? 0 }} - {{ $minCalculatedAmount ?? 0 }}" readonly>
                </div>
            </div>
            <div class="col-md-6 d-none">
                <div class="form-group">
                    <label>Amount</label>
                    <input type="text" class="form-control" name="total_amount_display" id="total_amount_display"
                        value="{{ number_format($totalAmount, 2) }}" readonly>
                    <input type="hidden" class="form-control" name="total_amount" id="total_amount"
                        value="{{ $totalAmount }}" readonly>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Requested Amount</label>
                    <input type="number" step="0.01" readonly class="form-control" name="requested_amount"
                        id="requested_amount" value="{{ $requestedAmount }}" placeholder="Enter requested amount">
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Paid Amount</label>
                    <input type="number" step="0.01" readonly class="form-control" name="paid_amount"
                        value="{{ $paidAmount }}" placeholder="Enter paid amount">
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>Remaining</label>
                    <input type="text" class="form-control" name="remaining_amount" id="remaining_amount"
                        value="{{ $totalAmount - $requestedAmount }}" readonly>
                </div>
            </div>
            @if (!$isApprovalPage)
                <div class="col">
                    <div class="form-group">
                        <label>Percentage</label>
                        <input type="number" min="0" max="100" step="0.01"
                            class="form-control percentage-input" value="0" placeholder="Enter percentage">
                    </div>
                </div>
                <div class="col">
                    <div class="form-group">
                        <label>Payment Request</label>
                        <input type="number" step="0.01" class="form-control payment-request-input"
                            name="{{ $isApprovalPage ? '' : 'payment_request_amount' }}"
                            value="{{ $currentPaymentAmount }}" placeholder="Enter payment request">
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        $('.select_b').select2();
        $('[data-toggle="tooltip"]').tooltip();
        const $loadingRadio = $('#loading');
        const $withoutLoadingRadio = $('#without_loading');
        const $loadingSection = $('#loading-section');
        const $contractRangeField = $('.contract-range-field');
        const originalBagWeight = parseFloat($('#original_bag_weight').val()) || 0;
        const originalBagRate = parseFloat($('#original_bag_rate').val()) || 0;
        const ratePerKg = parseFloat($('#rate_per_kg').val()) || 0;
        const paidAmount = parseFloat({{ $requestedAmount }});
        const isApprovalPage = {{ $isApprovalPage ? 1 : 0 }} ? true : false;

        if (!isApprovalPage) {
            $('input[name="payment_request_amount"]').on('input', function() {
                const totalAmount = parseFloat($('#total_amount').val()) || 0;
                const paymentRequest = parseFloat($(this).val()) || 0;
                const remaining = totalAmount - paymentRequest - paidAmount;
                $('#remaining_amount').val(remaining.toFixed(2));
            });
        }

        const percentageInput = $('.percentage-input');
        const paymentRequestInput = $('.payment-request-input');
        percentageInput.on('input', function() {
            let percentage = parseFloat($(this).val()) || 0;
            if (percentage > 100) {
                percentage = 100;
                $(this).val(100);
            }
            const totalAmount = parseFloat($('#total_amount').val()) || 0;
            const remainingAmount = totalAmount - paidAmount;
            const amount = (remainingAmount * percentage) / 100;
            paymentRequestInput.val(amount.toFixed(2));
        });

        paymentRequestInput.on('input', function() {
            const totalAmount = parseFloat($('#total_amount').val()) || 0;
            const remainingAmount = totalAmount - paidAmount;
            let amount = parseFloat($(this).val()) || 0;
            if (amount > remainingAmount) {
                amount = remainingAmount;
                $(this).val(remainingAmount.toFixed(2));
            }
            const percentage = remainingAmount > 0 ? (amount / remainingAmount) * 100 : 0;
            percentageInput.val(percentage.toFixed(2));
        });
    });
</script>
