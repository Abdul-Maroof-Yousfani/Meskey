<form action="{{ route('raw-material.payment-request.store') }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    <input type="hidden" id="listRefresh" value="{{ route('raw-material.get.payment-request') }}" />

    <div class="row form-mar">
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Ticket:</label>
                <input type="hidden" name="purchase_ticket_id" value="{{ $ticket->id ?? '' }}">
                <select name="ticket_id_display" id="{{ 'ticket_id' }}" class="form-control select2"
                    {{ isset($ticket) ? 'disabled' : '' }}>
                    @if (isset($ticket))
                        <option value="{{ $ticket->id }}"
                            data-is-decision-pending="{{ $ticket->purchaseOrder->decision_making ?? 0 }}">
                            Ticket: {{ $ticket->unique_no }}
                            {{ isset($ticket->qcProduct->name) ? "({$ticket->qcProduct->name})" : '' }}
                            {{ isset($ticket->purchaseFreight->truck_no) ? " - Truck: {$ticket->purchaseFreight->truck_no}" : '' }}
                            {{ isset($ticket->purchaseOrder->supplier->name) ? " - Supplier: {$ticket->purchaseOrder->supplier->name}" : '' }}
                            - Contract: {{ $ticket->purchaseOrder->contract_no ?? 'N/A' }}
                        </option>
                    @else
                        <option value="">Select Ticket</option>
                        @foreach ($tickets ?? [] as $ticketOption)
                            <option value="{{ $ticketOption->id }}"
                                data-is-decision-pending="{{ $ticketOption->purchaseOrder->decision_making ?? 0 }}">
                                Ticket: {{ $ticketOption->unique_no }}
                                {{ isset($ticketOption->qcProduct->name) ? "({$ticketOption->qcProduct->name})" : '' }}
                                {{ isset($ticketOption->purchaseFreight->truck_no) ? " - Truck: {$ticketOption->purchaseFreight->truck_no}" : '' }}
                                {{ isset($ticketOption->purchaseOrder->supplier->name) ? " - Supplier: {$ticketOption->purchaseOrder->supplier->name}" : '' }}
                                - Contract: {{ $ticketOption->purchaseOrder->contract_no ?? 'N/A' }}
                            </option>
                        @endforeach
                    @endif
                </select>
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
