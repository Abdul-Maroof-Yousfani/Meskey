<form action="{{ route('raw-material.gate-buy.payment-request.store') }}" method="POST" id="ajaxSubmit"
    autocomplete="off">
    @csrf
    <input type="hidden" id="listRefresh" value="{{ route('raw-material.ticket.get.payment-request') }}" />

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