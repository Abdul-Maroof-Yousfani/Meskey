<form action="{{ route('raw-material.ticket.payment-request.store') }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    <input type="hidden" id="listRefresh" value="{{ route('raw-material.ticket.get.payment-request') }}" />

    @php
        $ticketIdForLogs = $arrivalTicket->id ?? ($ticket->id ?? null);
        $ticketNoForLogs = $arrivalTicket->unique_no ?? ($ticket->unique_no ?? 'N/A');
    @endphp
    @if ($ticketIdForLogs)
    <div class="d-flex justify-content-end mb-2">
        <button type="button" class="btn btn-sm btn-outline-info" onclick="openModal(this,'{{ route('raw-material.ticket.payment-request.audit-logs', $ticketIdForLogs) }}','Audit Logs: Ticket #{{ $ticketNoForLogs }}', false, '55%')">
            <i class="fa fa-history mr-1"></i> Audit Logs
        </button>
    </div>
    @endif

    <div class="row form-mar">
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Contract:</label>
                <input type="hidden" name="arrival_purchase_order_id" value="{{ $purchaseOrder->id }}">
                <select name="arrival_purchase_order_id_display" id="{{ 'arrival_purchase_order_id' }}"
                    class="form-control select2" disabled>
                    <option value="{{ $purchaseOrder->id }}"
                        data-is-decision-pending="{{ $purchaseOrder->decision_making }}">
                        {{ $purchaseOrder->contract_no }}
                        {{ isset($purchaseOrder->qcProduct->name) ? "({$purchaseOrder->qcProduct->name})" : '' }}
                        {{ isset($purchaseOrder->truck_no) ? " - Truck: {$purchaseOrder->truck_no}" : '' }}
                        {{ isset($purchaseOrder->supplier->name) ? " - Supplier: {$purchaseOrder->supplier->name}" : '' }}
                    </option>
                </select>
            </div>
        </div>
    </div>

    <div id="slabsContainer">
        {!! $html !!}
    </div>

    @if ($purchaseOrder->decision_making == 1)
        <div id="decisionWarning" class="alert alert-warning">
            <strong>Warning!</strong> You cannot create a payment request for this contract. Please apply deductions
            first.
        </div>
    @endif

    <div class="row bottom-button-bar">
        <div class="col-12">
            <a type="button" class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton">Close</a>
            @if ($purchaseOrder->decision_making == 0)
                <button type="submit" class="btn btn-primary submitbutton" id="saveButton">Save</button>
            @endif
        </div>
    </div>
</form>
