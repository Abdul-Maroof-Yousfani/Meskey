<div class="row form-mar">
    <div class="col-md-12">
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label class="form-label">Customer</label>
                    <input type="text" class="form-control" value="{{ $payment_intimation->customer->name ?? 'N/A' }}" readonly>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label class="form-label">Sale Order</label>
                    <input type="text" class="form-control" value="{{ $payment_intimation->sale_order->reference_no ?? 'N/A' }}" readonly>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label class="form-label">Bank</label>
                    <input type="text" class="form-control" value="{{ $payment_intimation->bank->bank_name ?? 'N/A' }} - {{ $payment_intimation->bank->account_no ?? 'N/A' }}" readonly>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label class="form-label">Payment Deposit</label>
                    <input type="text" class="form-control" value="{{ number_format($payment_intimation->payment_deposit, 2) }}" readonly>
                </div>
            </div>
            @if($payment_intimation->attachment)
            <div class="col-md-6">
                <div class="form-group">
                    <label class="form-label d-block">Attachment</label>
                    <a href="{{ asset($payment_intimation->attachment) }}" target="_blank" class="btn btn-sm btn-info mt-1"><i class="ft-eye"></i> View Attachment</a>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
<div class="row bottom-button-bar mt-2">
    <div class="col-12 text-right">
        <a type="button" class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton me-2">Close</a>
    </div>
</div>
