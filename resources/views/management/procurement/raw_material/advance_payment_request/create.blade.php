<form action="{{ route('raw-material.advance-payment-request.store') }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    <input type="hidden" id="listRefresh" value="{{ route('raw-material.get.advance-payment-request') }}" />

    @if (isset($purchaseOrder))
    <div class="d-flex justify-content-end mb-2">
        <button type="button" class="btn btn-sm btn-outline-info" onclick="openModal(this,'{{ route('raw-material.advance-payment-request.audit-logs', $purchaseOrder->id) }}','Audit Logs: Contract #{{ $purchaseOrder->contract_no }}', false, '55%')">
            <i class="fa fa-history mr-1"></i> Audit Logs
        </button>
    </div>
    @endif

    <div class="row form-mar">
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Contract No:</label>
                <input type="text" class="form-control" name="contract_no"
                    value="{{ $purchaseOrder->contract_no ?? 'N/A' }}" readonly>
            </div>
        </div>
    </div>

    <div id="slabsContainer">
        {!! $html ?? '' !!}
    </div>

    @if (isset($ticket) && $ticket->purchaseOrder && $ticket->purchaseOrder->decision_making == 1)
        <div id="decisionWarning" class="alert alert-warning">
            <strong>Warning!</strong> You cannot create a payment request for this ticket. Please apply deductions
            first.
        </div>
    @endif

    <div class="row bottom-button-bar">
        <div class="col-12">
            <a type="button" class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton">Close</a>
            @if (!isset($ticket) || !$ticket->purchaseOrder || $ticket->purchaseOrder->decision_making == 0)
                <button type="submit" class="btn btn-primary submitbutton" id="saveButton">Save</button>
            @endif
        </div>
    </div>
</form>
