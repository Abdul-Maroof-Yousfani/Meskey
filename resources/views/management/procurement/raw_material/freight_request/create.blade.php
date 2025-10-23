@php
    $param = isset($isRequestApprovalPage) && $isRequestApprovalPage ? 'readonly' : '';
    $param0 = isset($isRequestApprovalPage) && $isRequestApprovalPage ? 'disabled' : '';
    $paymentRequest = isset($paymentRequest) ? $paymentRequest : null;
    $isUpdated = isset($isUpdated) ? $isUpdated : null;
    $approval = isset($approval) ? $approval : null;
    
    // Use paymentRequestData if available, otherwise use existing logic
    $exempt = $paymentRequestData->exempt ?? $freightPaymentRequest?->exempt ?? ($ticket->freight->exempted_weight ?? '0');
    $freight_rs = $paymentRequestData->freight_rs ?? $freightPaymentRequest?->freight_rs ?? ($ticket->freight->net_freight ?? '0');
    $freight_per_ton = $paymentRequestData->freight_per_ton ?? $freightPaymentRequest?->freight_per_ton ?? ($ticket->freight->freight_per_ton ?? '0');
    $loading_kanta = $paymentRequestData->loading_kanta ?? $freightPaymentRequest?->loading_kanta ?? ($ticket->freight->kanta_golarchi_charges ?? '0');
    $arrived_kanta = $paymentRequestData->arrived_kanta ?? $freightPaymentRequest?->arrived_kanta ?? ($ticket->freight->karachi_kanta_charges ?? '0');
    $other_plus_labour = $paymentRequestData->other_plus_labour ?? $freightPaymentRequest?->other_plus_labour ?? ($ticket->freight->other_labour_charges ?? '0');
    $dehari_plus_extra = $paymentRequestData->dehari_plus_extra ?? $freightPaymentRequest?->dehari_plus_extra ?? ($ticket->freight->other_labour_charges ?? '0');
    $market_comm = $paymentRequestData->market_comm ?? $freightPaymentRequest?->market_comm ?? 0;
    $over_weight_ded = $paymentRequestData->over_weight_ded ?? $freightPaymentRequest?->over_weight_ded ?? 0;
    $godown_penalty = $paymentRequestData->godown_penalty ?? $freightPaymentRequest?->godown_penalty ?? 0;
    $other_minus_labour = $paymentRequestData->other_minus_labour ?? $freightPaymentRequest?->other_minus_labour ?? ($ticket->freight->other_labour_charges ?? '0');
    $extra_minus_ded = $paymentRequestData->extra_minus_ded ?? $freightPaymentRequest?->extra_minus_ded ?? 0;
    $commission_percent_ded = $paymentRequestData->commission_percent_ded ?? $freightPaymentRequest?->commission_percent_ded ?? 0;
    $commission_amount = $paymentRequestData->commission_amount ?? $freightPaymentRequest?->commission_amount ?? 0;
@endphp
<form
    action="{{ route(isset($isRequestApprovalPage, $freightPaymentRequest->vendor_id) ? 'raw-material.advance-payment-request-approval.store' : 'raw-material.freight-request.store') }}"
    method="POST" id="ajaxSubmit" class="needs-validation" novalidate>
    @csrf
    <input type="hidden" name="arrival_slip_no" value="{{ $ticket->arrivalSlip->unique_no ?? '' }}">
    <input type="hidden" name="arrival_slip_id" value="{{ $ticket->arrivalSlip->id ?? '' }}">
    <input type="hidden" name="purchase_order_id" value="{{ $ticket->arrival_purchase_order_id ?? '' }}">
    <input type="hidden" name="ticket_id" value="{{ $ticket->id ?? '' }}">
    <input type="hidden" name="arrival_ticket_id" value="{{ $ticket->id ?? '' }}">
    <input type="hidden" name="ticket_type" value="{{ $ticketType ?? '' }}">
    <input type="hidden" name="payment_request_id" value="{{ $paymentRequest?->id ?? null }}">

    @if (isset($isRequestApprovalPage, $freightPaymentRequest->vendor_id))
        <input type="hidden" id="listRefresh" value="{{ route('raw-material.get.payment-request-approval') }}" />
    @else
        <input type="hidden" id="listRefresh" value="{{ route('raw-material.get.freight-request') }}" />
    @endif

    <div class="row">
        <div class="col-12">
            <h6 class="header-heading-sepration">
                Ticket Information
            </h6>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Ticket #</label>
                <input type="text" class="form-control bg-light" value="#{{ $ticket->unique_no }}" readonly
                    placeholder="Ticket #">
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Date</label>
                <input type="text" class="form-control bg-light"
                    value="{{ $ticket->created_at->format('d-M-Y') ?? 'N/A' }}" readonly placeholder="Date">
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Truck No.</label>
                <input type="text" class="form-control bg-light"
                    value="{{ $ticket->truck_no ?? ($ticket->purchaseFreight->truck_no ?? 'N/A') }}" readonly
                    placeholder="Truck No.">
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Bill/T</label>
                <input type="text" class="form-control bg-light"
                    value="{{ $ticket->purchaseFreight->bilty_no ?? 'N/A' }}" readonly placeholder="Bill/T">
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label class="font-weight-bold">GRN No.</label>
                <input type="text" class="form-control bg-light"
                    value="#{{ $ticket->arrivalSlip->unique_no ?? 'N/A' }}" readonly placeholder="GRN No.">
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label class="font-weight-bold">Contract No.</label>
                <input type="text" class="form-control bg-light"
                    value="{{ $ticket->purchaseOrder->contract_no ?? 'N/A' }}" readonly placeholder="Contract No.">
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <h6 class="header-heading-sepration">
                Party Details
            </h6>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label class="font-weight-bold">Supplier Name</label>
                <input type="text" class="form-control bg-light"
                    value="{{ $ticket->purchaseOrder->supplier->name ?? 'N/A' }}" readonly placeholder="Supplier Name">
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label class="font-weight-bold">Broker Name</label>
                <input type="text" class="form-control bg-light"
                    value="{{ $ticket->broker->name ?? ($ticket->purchaseOrder->broker->name ?? 'N/A') }}" readonly
                    placeholder="Broker Name">
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label class="font-weight-bold">Freight Party {{ $paymentRequestData->payment_to }}</label>
                <select class="form-control editable-field select2" name="vendor_id" @disabled($param0)>
                    <option value="">Select Freight Party</option>
                    @foreach ($vendors as $vendor)
                        <option value="{{ $vendor->id }}" @selected($paymentRequestData->payment_to == $vendor->id)>
                            {{ $vendor->name }}
                        </option>
                    @endforeach
                </select>
                @if (isset($isRequestApprovalPage, $freightPaymentRequest->vendor_id))
                    <input type="hidden" name="vendor_id" value="{{ $freightPaymentRequest->vendor_id }}" readonly>
                @endif
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <h6 class="header-heading-sepration">
                Commodity Details
            </h6>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label class="font-weight-bold">Commodity</label>
                <input type="text" class="form-control bg-light"
                    value="{{ $ticket->product->name ?? ($ticket->qcProduct->name ?? 'N/A') }}" readonly
                    placeholder="Commodity">
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label class="font-weight-bold">Godown</label>
                <input type="text" class="form-control bg-light" value="{{ $ticket->location->name ?? 'N/A' }}"
                    readonly placeholder="Godown">
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label class="font-weight-bold">Station</label>
                <input type="text" class="form-control bg-light"
                    value="{{ $ticket->station->name ?? ($ticket->purchaseOrder->station_name ?? 'N/A') }}" readonly
                    placeholder="Station">
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-4">
            <div class="form-group">
                <label class="font-weight-bold">Sauda Term</label>
                <input type="text" class="form-control bg-light"
                    value="{{ $ticket->saudaType->name ?? ($ticket->purchaseOrder->saudaType->name ?? 'N/A') }}"
                    readonly placeholder="Sauda Term">
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label class="font-weight-bold">Contract Rate</label>
                <input type="text" class="form-control editable-field contract-rate" name="contract_rate"
                    value="{{ $ticket->purchaseOrder->rate_per_kg ?? '0' }}" placeholder="Contract Rate" readonly>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <h6 class="header-heading-sepration">
                Weight Information
            </h6>
        </div>
        <div class="col-md col-4">
            <div class="form-group">
                <label class="font-weight-bold">Loading Weight</label>
                <input type="text" class="form-control bg-light" value="{{ $ticket->net_weight ?? '0' }}"
                    readonly placeholder="Loading Weight">
            </div>
        </div>
        <div class="col-md col-4">
            <div class="form-group">
                <label class="font-weight-bold">Arrival Weight</label>
                <input type="text" class="form-control bg-light" value="{{ $ticket->arrived_net_weight }}"
                    readonly placeholder="Arrival Weight">
            </div>
        </div>
        <div class="col-md col-4">
            <div class="form-group">
                <label class="font-weight-bold">Difference Weight</label>
                <input type="text" class="form-control bg-light" id="differenceWeight"
                    value="{{ ($ticket->arrived_net_weight ?? 0) - ($ticket->net_weight ?? 0) }}" readonly
                    placeholder="Difference Weight">
            </div>
        </div>
        <div class="col-md col-6">
            <div class="form-group">
                <label class="font-weight-bold">Exempt</label>
                <input type="text" class="form-control editable-field" name="exempt" id="exemptedWeight"
                    value="{{ $exempt }}" {{ $param }} placeholder="Exempt">
            </div>
        </div>
        <div class="col-md col-6">
            <div class="form-group">
                <label class="form-label">Net Shortage</label>
                <input type="number" class="form-control bg-light" name="net_shortage" id="netShortage"
                    value="150" readonly>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <h6 class="header-heading-sepration">
                Freight & Charges
            </h6>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Freight (Rs)</label>
                <input type="text" class="form-control editable-field freight-rs" name="freight_rs"
                    value="{{ $freight_rs }}" {{ $param }} placeholder="Freight (Rs)">
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Freight Per Ton</label>
                <input type="text" class="form-control editable-field freight-per-ton" name="freight_per_ton"
                    value="{{ $freight_per_ton }}" {{ $param }} placeholder="Freight Per Ton">
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Loading Kanta</label>
                <input type="text" class="form-control editable-field" name="loading_kanta"
                    value="{{ $loading_kanta }}" {{ $param }} placeholder="Loading Kanta">
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Arrived Kanta</label>
                <input type="text" class="form-control editable-field" name="arrived_kanta"
                    value="{{ $arrived_kanta }}" {{ $param }} placeholder="Arrived Kanta">
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <h6 class="header-heading-sepration">
                Additions
            </h6>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Other(+)/Labour</label>
                <input type="text" class="form-control editable-field" name="other_plus_labour"
                    value="{{ $other_plus_labour }}" {{ $param }} placeholder="Other(+)/Labour">
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Dehari(+)/Extra</label>
                <input type="text" class="form-control editable-field" name="dehari_plus_extra"
                    value="{{ $dehari_plus_extra }}" {{ $param }} placeholder="Dehari(+)/Extra">
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Market Comm</label>
                <input type="text" class="form-control editable-field" name="market_comm"
                    value="{{ $market_comm }}" {{ $param }} placeholder="Market Comm">
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <h6 class="header-heading-sepration">
                Deductions
            </h6>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Over Weight Ded</label>
                <input type="text" class="form-control editable-field" name="over_weight_ded"
                    value="{{ $over_weight_ded }}" {{ $param }} placeholder="Over Weight Ded">
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Godown Penalty</label>
                <input type="text" class="form-control editable-field" name="godown_penalty"
                    value="{{ $godown_penalty }}" {{ $param }} placeholder="Godown Penalty">
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Other(-)/Labour</label>
                <input type="text" class="form-control editable-field" name="other_minus_labour"
                    value="{{ $other_minus_labour }}" {{ $param }} placeholder="Other(-)/Labour">
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Extra(-) Ded</label>
                <input type="text" class="form-control editable-field" name="extra_minus_ded"
                    value="{{ $extra_minus_ded }}" {{ $param }} placeholder="Extra(-) Ded">
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Commission % Ded</label>
                <input type="text" class="form-control editable-field commission-percent" name="commission_percent_ded"
                    value="{{ $commission_percent_ded }}" {{ $param }} placeholder="Commission % Ded">
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Commission Amount</label>
                <input type="text" class="form-control bg-light commission-amount" name="commission_amount"
                    value="{{ $commission_amount }}" readonly placeholder="Commission Amount">
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <h6 class="header-heading-sepration">
                Payment Summary
            </h6>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Gross Amount</label>
                <input type="text" class="form-control bg-light" name="gross_amount" value="0" readonly>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Total Deductions</label>
                <input type="text" class="form-control bg-light" name="total_deductions" value="0" readonly>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Net Amount</label>
                <input type="text" class="form-control bg-light font-weight-bold" name="net_amount"
                    value="0" readonly>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Request Amount</label>
                <input type="number" step="0.01" class="form-control" name="request_amount" value="0"
                    min="0" required >
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="form-group">
                <label class="font-weight-bold">Requested Amount</label>
                <input type="number" step="0.01" class="form-control bg-light" name="paid_amount"
                    value="{{ $requestedAmount ?? 0 }}" readonly>
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label class="font-weight-bold">Remaining Amount</label>
                <input type="number" step="0.01" class="form-control bg-light" name="remaining_amount"
                    value="0" readonly>
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label class="font-weight-bold">Percentage</label>
                <input type="number" min="0" max="100" step="0.01"
                    class="form-control percentage-input" value="0" placeholder="Enter percentage">
            </div>
        </div>
    </div>

    @if (isset($isRequestApprovalPage) && $isRequestApprovalPage)
        <div class="row">
            <div class="col-12">
                <h6 class="header-heading-sepration">
                    Payment Request Approval
                </h6>
            </div>
        </div>

        <div class="row">
            <div class="{{ 'col-md-6' }}">
                <div class="form-group">
                    <label>Contract No</label>
                    <input type="text" class="form-control"
                        value="#{{ $paymentRequest->paymentRequestData->purchaseOrder->contract_no ?? 'N/A' }}"
                        readonly>
                </div>
            </div>
            <div class="{{ 'col-md-6' }}">
                <div class="form-group">
                    <label>Supplier</label>
                    <input type="text" class="form-control"
                        value="{{ $paymentRequest->paymentRequestData->supplier_name ?? 'N/A' }}" readonly>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Request Type</label>
                    <input type="text" class="form-control"
                        value="{{ isset($paymentRequest) ? formatEnumValue($paymentRequest->request_type) : '' }}"
                        readonly>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Original Amount</label>
                    <input type="text" class="form-control" name="payment_request_amount" readonly
                        value="{{ isset($paymentRequest) ? $paymentRequest->amount : '' }}">
                </div>
            </div>
            <div class="col-md-6">
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
            <div class="col-md-12">
                <div class="form-group">
                    <label>Remarks</label>
                    <textarea id="approvalRemarks" name="remarks" class="form-control" rows="3"
                        {{ $isUpdated ? 'readonly' : '' }}>{{ $approval->remarks ?? '' }}</textarea>
                </div>
            </div>
        </div>
    @endif

    <div class="row bottom-button-bar">
        <div class="col-12">
            <a type="button" class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton">Close</a>
            <button type="submit" class="btn btn-primary submitbutton" id="saveButton">Save</button>
        </div>
    </div>
</form>
<script>
    $(document).ready(function() {
        $('.editable-field').on('input', calculatePaymentSummary);

        // Freight calculations
        $('.freight-rs').on('input', function() {
            calculateFreightFromRs();
            calculateCommission();
        });
        
        $('.freight-per-ton').on('input', function() {
            calculateFreightFromTon();
            calculateCommission();
        });
        
        // Commission calculation
        $('.commission-percent').on('input', calculateCommission);

        function calculateNetShortage() {
            const differenceWeight = parseFloat(document.getElementById('differenceWeight').value) || 0;
            const exemptedWeight = parseFloat(document.getElementById('exemptedWeight').value) || 0;
            const netShortage = differenceWeight - exemptedWeight;
            document.getElementById('netShortage').value = netShortage > 0 ? netShortage : 0;
        }

        function calculateFreightFromRs() {
            const freightRs = parseFloat($('.freight-rs').val()) || 0;
            const loadingWeight = parseFloat('{{ $ticket->net_weight ?? 0 }}') || 0;
            
            if (loadingWeight > 0) {
                const freightPerTon = (freightRs / loadingWeight) * 1000;
                $('.freight-per-ton').val(freightPerTon.toFixed(2));
            }
            
            calculatePaymentSummary();
        }

        function calculateFreightFromTon() {
            const freightPerTon = parseFloat($('.freight-per-ton').val()) || 0;
            const loadingWeight = parseFloat('{{ $ticket->net_weight ?? 0 }}') || 0;
            
            if (loadingWeight > 0) {
                const freightRs = (freightPerTon * loadingWeight) / 1000;
                $('.freight-rs').val(freightRs.toFixed(2));
            }
            
            calculatePaymentSummary();
        }

        function calculateCommission() {
            const commissionPercent = parseFloat($('.commission-percent').val()) || 0;
            const freightRs = parseFloat($('.freight-rs').val()) || 0;
            
            if (commissionPercent > 0) {
                const commissionAmount = (freightRs * commissionPercent) / 100;
                $('.commission-amount').val(commissionAmount.toFixed(2));
            } else {
                $('.commission-amount').val('0');
            }
            
            calculatePaymentSummary();
        }

        function calculatePaymentSummary() {
            let freightRs = parseFloat($('[name="freight_rs"]').val()) || 0;
            let loadingKanta = parseFloat($('[name="loading_kanta"]').val()) || 0;
            let arrivedKanta = parseFloat($('[name="arrived_kanta"]').val()) || 0;
            let otherPlusLabour = parseFloat($('[name="other_plus_labour"]').val()) || 0;
            let dehariPlusExtra = parseFloat($('[name="dehari_plus_extra"]').val()) || 0;
            let marketComm = parseFloat($('[name="market_comm"]').val()) || 0;

            let overWeightDed = parseFloat($('[name="over_weight_ded"]').val()) || 0;
            let godownPenalty = parseFloat($('[name="godown_penalty"]').val()) || 0;
            let otherMinusLabour = parseFloat($('[name="other_minus_labour"]').val()) || 0;
            let extraMinusDed = parseFloat($('[name="extra_minus_ded"]').val()) || 0;
            let commissionAmount = parseFloat($('[name="commission_amount"]').val()) || 0;

            let netShortage = parseFloat(document.getElementById('netShortage').value) || 0;
            let contractRate = parseFloat($('.contract-rate').val()) || 0;
            let netShortageDeduction = netShortage * contractRate;

            let grossAmount = freightRs + loadingKanta + arrivedKanta + otherPlusLabour + dehariPlusExtra + marketComm;
            let totalDeductions = overWeightDed + godownPenalty + otherMinusLabour + extraMinusDed + commissionAmount + netShortageDeduction;
            let netAmount = grossAmount - totalDeductions;

            $('[name="gross_amount"]').val(grossAmount.toFixed(2));
            $('[name="total_deductions"]').val(totalDeductions.toFixed(2));
            $('[name="net_amount"]').val(netAmount.toFixed(2));

            let paidAmount = parseFloat($('[name="paid_amount"]').val()) || 0;
            let requestAmount = parseFloat($('[name="request_amount"]').val()) || 0;
            let remainingAmount = netAmount - paidAmount - requestAmount;
            $('[name="remaining_amount"]').val(remainingAmount.toFixed(2));

            // Set maximum limit for request amount
            $('[name="request_amount"]').attr('max', Math.max(0, netAmount - paidAmount).toFixed(2));
        }

        // Percentage input handler for multiple requests
        $('.percentage-input').on('input', function() {
            let percentage = parseFloat($(this).val()) || 0;
            if (percentage > 100) {
                percentage = 100;
                $(this).val(100);
            }

            const netAmount = parseFloat($('[name="net_amount"]').val()) || 0;
            const paidAmount = parseFloat($('[name="paid_amount"]').val()) || 0;
            const remainingAmount = netAmount - paidAmount;
            const amount = (remainingAmount * percentage) / 100;
            
            $('[name="request_amount"]').val(amount.toFixed(2));
            
            // Update remaining amount
            const finalRemaining = netAmount - (paidAmount + amount);
            $('[name="remaining_amount"]').val(finalRemaining.toFixed(2));
        });

        // Request amount input handler
        $('[name="request_amount"]').on('input', function() {
            const netAmount = parseFloat($('[name="net_amount"]').val()) || 0;
            const paidAmount = parseFloat($('[name="paid_amount"]').val()) || 0;
            const newRequested = parseFloat($(this).val()) || 0;
            const remainingAmount = netAmount - paidAmount;

            // Ensure payment request doesn't exceed remaining amount
            if (newRequested > remainingAmount) {
                $(this).val(remainingAmount.toFixed(2));
            }

            // Update percentage
            const percentageInput = $('.percentage-input');
            const finalRequested = parseFloat($(this).val()) || 0;
            const percentage = remainingAmount > 0 ? (finalRequested / remainingAmount) * 100 : 0;
            percentageInput.val(percentage.toFixed(2));

            // Update remaining amount display
            const finalRemaining = netAmount - (paidAmount + finalRequested);
            $('[name="remaining_amount"]').val(finalRemaining.toFixed(2));
        });

        document.getElementById('exemptedWeight').addEventListener('input', function() {
            calculateNetShortage();
            calculatePaymentSummary();
        });

        // Initialize calculations on page load
        function initializeCalculations() {
            calculateNetShortage();
            
            // Calculate freight per ton if freight Rs has value
            const initialFreightRs = parseFloat($('.freight-rs').val()) || 0;
            if (initialFreightRs > 0) {
                calculateFreightFromRs();
            } else {
                // If freight per ton has value but freight Rs doesn't, calculate from ton
                const initialFreightPerTon = parseFloat($('.freight-per-ton').val()) || 0;
                if (initialFreightPerTon > 0) {
                    calculateFreightFromTon();
                } else {
                    calculatePaymentSummary();
                }
            }
            
            calculateCommission();
        }

        // Run initialization
        initializeCalculations();
    });
</script>