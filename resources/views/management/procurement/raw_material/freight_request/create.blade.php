<form action="{{ route('raw-material.payment-request.store') }}" method="POST" id="paymentRequestForm"
    class="needs-validation" novalidate>
    @csrf
    <input type="hidden" name="ticket_id" value="{{ $ticket->id ?? '' }}">
    <input type="hidden" name="ticket_type" value="{{ $ticketType ?? '' }}">

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
                <label class="font-weight-bold">Freight Party Name</label>
                <input type="text" class="form-control editable-field" name="freight_party_name"
                    value="{{ $ticket->freight->freight_party_name ?? '' }}" placeholder="Freight Party Name">
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
                <input type="text" class="form-control editable-field" name="contract_rate"
                    value="{{ $ticket->purchaseOrder->rate_per_kg ?? '0' }}" placeholder="Contract Rate">
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <h6 class="header-heading-sepration">
                Weight Information
            </h6>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Loading Weight</label>
                <input type="text" class="form-control bg-light"
                    value="{{ $ticket->purchaseFreight->loading_weight ?? ($ticket->loading_weight ?? '0') }}"
                    readonly placeholder="Loading Weight">
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Arrival Weight</label>
                <input type="text" class="form-control bg-light" value="{{ $ticket->arrived_net_weight ?? '0' }}"
                    readonly placeholder="Arrival Weight">
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Difference Weight</label>
                <input type="text" class="form-control bg-light"
                    value="{{ ($ticket->loading_weight ?? 0) - ($ticket->arrived_net_weight ?? 0) }}" readonly
                    placeholder="Difference Weight">
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Exempt</label>
                <input type="text" class="form-control editable-field" name="exempt"
                    value="{{ $ticket->exempt ?? '0' }}" placeholder="Exempt">
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
                <input type="text" class="form-control editable-field" name="freight_amount"
                    value="{{ $ticket->freight->freight_amount ?? ($ticket->purchaseFreight->freight_amount ?? '0') }}"
                    placeholder="Freight (Rs)">
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Freight Per Ton</label>
                <input type="text" class="form-control editable-field" name="freight_per_ton"
                    value="{{ $ticket->freight->freight_per_ton ?? ($ticket->purchaseFreight->freight_per_ton ?? '0') }}"
                    placeholder="Freight Per Ton">
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Loading Kanta</label>
                <input type="text" class="form-control editable-field" name="loading_kanta"
                    value="{{ $ticket->purchaseFreight->kanta_charges ?? '0' }}" placeholder="Loading Kanta">
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Arrived Kanta</label>
                <input type="text" class="form-control editable-field" name="arrived_kanta"
                    value="{{ $ticket->freight->karachi_kanta_charges ?? '0' }}" placeholder="Arrived Kanta">
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
                <input type="text" class="form-control editable-field" name="other_labour_positive"
                    value="{{ $ticket->freight->other_labour_charges ?? '0' }}" placeholder="Other(+)/Labour">
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Dehari(+)/Extra</label>
                <input type="text" class="form-control editable-field" name="dehari_extra"
                    value="{{ $ticket->freight->other_labour_charges ?? '0' }}" placeholder="Dehari(+)/Extra">
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Market Comm</label>
                <input type="text" class="form-control editable-field" name="market_comm"
                    value="{{ $ticket->freight->market_comm ?? '0' }}" placeholder="Market Comm">
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
                    value="{{ $ticket->freight->over_weight_ded ?? '0' }}" placeholder="Over Weight Ded">
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Godown Penalty</label>
                <input type="text" class="form-control editable-field" name="godown_penalty"
                    value="{{ $ticket->freight->godown_penalty ?? '0' }}" placeholder="Godown Penalty">
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Other(-)/Labour</label>
                <input type="text" class="form-control editable-field" name="other_labour_negative"
                    value="{{ $ticket->freight->other_labour_charges ?? '0' }}" placeholder="Other(-)/Labour">
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Extra(-) Ded</label>
                <input type="text" class="form-control editable-field" name="extra_ded"
                    value="{{ $ticket->freight->extra_ded ?? '0' }}" placeholder="Extra(-) Ded">
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Commission % Ded</label>
                <input type="text" class="form-control editable-field" name="commission_ded"
                    value="{{ $ticket->freight->commission_ded ?? '0' }}" placeholder="Commission % Ded">
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
                <label class="font-weight-bold">Final Figure</label>
                <input type="text" class="form-control bg-light font-weight-bold text-success"
                    value="{{ $finalFigure ?? '0' }}" readonly placeholder="Final Figure">
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Freight Paid</label>
                <input type="text" class="form-control bg-light font-weight-bold text-success"
                    value="{{ $freightPaid ?? '0' }}" readonly placeholder="Freight Paid">
            </div>
        </div>
    </div>

    <div class="row bottom-button-bar">
        <div class="col-12">
            <a type="button" class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton">Close</a>
            <button type="submit" class="btn btn-primary submitbutton" id="saveButton">Save</button>
        </div>
    </div>
</form>
{{-- <script>
    $(document).ready(function() {
        (function() {
            'use strict';
            window.addEventListener('load', function() {
                var form = document.getElementById('paymentRequestForm');
                form.addEventListener('submit', function(event) {
                    if (form.checkValidity() === false) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            }, false);
        })();

        $('.editable-field').on('change keyup', function() {
            calculateFinalFigures();
        });

        function calculateFinalFigures() {
            let freightAmount = parseFloat($('[name="freight_amount"]').val()) || 0;
            let loadingKanta = parseFloat($('[name="loading_kanta"]').val()) || 0;
            let arrivedKanta = parseFloat($('[name="arrived_kanta"]').val()) || 0;
            let otherPositive = parseFloat($('[name="other_labour_positive"]').val()) || 0;
            let dehariExtra = parseFloat($('[name="dehari_extra"]').val()) || 0;
            let marketComm = parseFloat($('[name="market_comm"]').val()) || 0;

            let overWeightDed = parseFloat($('[name="over_weight_ded"]').val()) || 0;
            let godownPenalty = parseFloat($('[name="godown_penalty"]').val()) || 0;
            let otherNegative = parseFloat($('[name="other_labour_negative"]').val()) || 0;
            let extraDed = parseFloat($('[name="extra_ded"]').val()) || 0;
            let commissionDed = parseFloat($('[name="commission_ded"]').val()) || 0;

            let totalPositive = freightAmount + loadingKanta + arrivedKanta + otherPositive + dehariExtra +
                marketComm;
            let totalNegative = overWeightDed + godownPenalty + otherNegative + extraDed + commissionDed;
            let finalFigure = totalPositive - totalNegative;

            $('[value="final_figure"]').val(finalFigure.toFixed(2));
            $('[value="freight_paid"]').val(finalFigure.toFixed(2));
        }

        calculateFinalFigures();
    });
</script> --}}
