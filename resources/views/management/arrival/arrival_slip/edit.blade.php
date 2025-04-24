<style>
    [readonly] {
        background-color: white !important
    }
</style>
<form action="{{ route('arrival-slip.store') }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    <input type="hidden" id="listRefresh" value="{{ route('get.arrival-slip') }}" />
    <div class="row form-mar">
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Ticket:</label>
                <select class="form-control select2" name="arrival_ticket_id" id="arrival_ticket_id">
                    <option value="">Select Ticket</option>
                    @foreach ($ArrivalTickets as $arrivalTicket)
                        <option @selected($arrivalTicket->id == $arrival_slip->arrival_ticket_id) value="{{ $arrivalTicket->id }}">
                            Ticket No: {{ $arrivalTicket->unique_no }} --
                            Truck No: {{ $arrivalTicket->truck_no ?? 'N/A' }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="col-xs-12 col-sm-12 col-md-12">
            <div id="slabsContainer">
                <div class="row form-mar">
                    <div class="col-12" bis_skin_checked="1">
                        <h6 class="header-heading-sepration">
                            Arrival Slip
                        </h6>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="font-weight-bold">Date</label>
                            <input type="text" class="form-control bg-light" value="{{ now()->format('d-M-Y') }}"
                                readonly>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="font-weight-bold">No. of Bags</label>
                            <input type="text" class="form-control bg-light" value="{{ $arrivalTicket->bags }}"
                                readonly>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="font-weight-bold">Packing</label>
                            <input type="text" class="form-control bg-light"
                                value="{{ $arrivalTicket->approvals->bagType->name ?? 'N/A' }} ⸺ {{ $arrivalTicket->approvals->bagPacking->name ?? 'N/A' }}"
                                readonly>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="font-weight-bold">Party Name</label>
                            <input type="text" class="form-control bg-light"
                                value="{{ $arrivalTicket->supplier_name }}" readonly>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="font-weight-bold">Broker Name</label>
                            <input type="text" class="form-control bg-light"
                                value="{{ $arrivalTicket->broker_name ?? 'N/A' }}" readonly>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="font-weight-bold">On A/C of</label>
                            <input type="text" class="form-control bg-light"
                                value="{{ $arrivalTicket->accounts_of_id ?? 'N/A' }}" readonly>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="font-weight-bold">Station</label>
                            <input type="text" class="form-control bg-light"
                                value="{{ $arrivalTicket->station_name }}" readonly>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="font-weight-bold">Commodity</label>
                            <input type="text" class="form-control bg-light"
                                value="{{ $arrivalTicket->product->name }}" readonly>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="font-weight-bold">U/L Slip #</label>
                            <input type="text" class="form-control bg-light" value="00013:44:42" readonly>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="font-weight-bold">Deductions</label>
                            <input type="text" class="form-control bg-light"
                                value="{{ $arrivalTicket->lumpsum_deduction ?? 'N/A' }}" readonly>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="font-weight-bold">Sauda Term</label>
                            <input type="text" class="form-control bg-light"
                                value="{{ $arrivalTicket->saudaType->name ?? 'N/A' }}" readonly>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="font-weight-bold">Gala No.</label>
                            <input type="text" class="form-control bg-light"
                                value="{{ $arrivalTicket->approvals->gala_name ?? 'N/A' }}" readonly>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="font-weight-bold">Godown</label>
                            <input type="text" class="form-control bg-light"
                                value="{{ $arrivalTicket->unloadingLocation->arrivalLocation->name ?? 'N/A' }}"
                                readonly>
                        </div>
                    </div>

                    <div class="col-12" bis_skin_checked="1">
                        <h6 class="header-heading-sepration">
                            Weight Information
                        </h6>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Gross Weight</label>
                            <input type="text" class="form-control bg-light"
                                value="{{ ($arrivalTicket->first_weight ?? 0) - ($arrivalTicket->second_weight ?? 0) }}"
                                readonly>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Net Weight</label>
                            <input type="text" class="form-control bg-light"
                                value="{{ $arrivalTicket->net_weight ?? 'N/A' }}" readonly>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Loading Weight</label>
                            <input type="text" class="form-control bg-light"
                                value="{{ $arrivalTicket->net_weight ?? 'N/A' }}" readonly>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Avg. Weight</label>
                            <input type="text" class="form-control bg-light"
                                value="{{ $arrivalTicket->net_weight ?? 'N/A' }}" readonly>
                        </div>
                    </div>

                    <div class="col-12" bis_skin_checked="1">
                        <h6 class="header-heading-sepration">
                            Freight Information
                        </h6>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Filling:</label>
                            <div class="row w-100 mx-auto">
                                <input type="text" class="col form-control bg-light"
                                    value="{{ $arrivalTicket->approvals->filling_bags_no ?? '0' }}" readonly>
                                <div class="col">
                                    <span class="input-group-text" readonly>x 10 =</span>
                                </div>
                                <input type="text" class="col form-control bg-light"
                                    value="{{ isset($arrivalTicket->approvals->filling_bags_no) ? $arrivalTicket->approvals->filling_bags_no * 10 : '0' }}"
                                    readonly>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Freight (Rs.)</label>
                            <input type="text" class="form-control bg-light mb-1"
                                value="{{ $arrivalTicket->freight->gross_freight_amount ?? '0.00' }}" readonly>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Freight per Ton</label>
                            <input type="text" class="form-control bg-light mb-1"
                                value="{{ $arrivalTicket->freight->freight_per_ton ?? '0.00' }}" readonly>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Other (+)/ Labour Charges</label>
                            <input type="text" class="form-control bg-light mb-1"
                                value="{{ $arrivalTicket->freight->other_labour_charges ?? '0.00' }}" readonly>
                            <input type="text" class="form-control bg-light"
                                value="{{ numberToWords($arrivalTicket->freight->other_labour_charges ?? 0) }}"
                                readonly>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Other Deduction</label>
                            <input type="text" class="form-control bg-light mb-1"
                                value="{{ $arrivalTicket->freight->other_deduction ?? '0.00' }}" readonly>
                            <input type="text" class="form-control bg-light"
                                value="{{ numberToWords($arrivalTicket->freight->other_deduction ?? 0) }}" readonly>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Total Freight Payable (Rs.)</label>
                            <input type="text" class="form-control bg-light mb-1"
                                value="{{ $arrivalTicket->freight->gross_freight_amount ?? '0.00' }}" readonly>
                            <input type="text" class="form-control bg-light"
                                value="{{ numberToWords($arrivalTicket->freight->gross_freight_amount ?? 0) }}"
                                readonly>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Unpaid Labour Charge</label>
                            <input type="text" class="form-control bg-light mb-1"
                                value="{{ $arrivalTicket->freight->unpaid_labor_charges ?? '0.00' }}" readonly>
                            <input type="text" class="form-control bg-light"
                                value="{{ numberToWords($arrivalTicket->freight->unpaid_labor_charges ?? 0) }}"
                                readonly>
                        </div>
                    </div>
                    <div class="col">
                        <div class="form-group">
                            <label>Final Figure</label>
                            <div class="d-flex">
                                <input type="text" class="form-control bg-light mb-1"
                                    value="{{ $arrivalTicket->freight->net_freight ?? '0.00' }}" readonly>
                                <input type="text" class="form-control bg-light"
                                    value="{{ numberToWords($arrivalTicket->freight->net_freight ?? 0) }}" readonly>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 mt-4">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="font-weight-bold">Confirmed Form</label>
                                    <input type="text" class="form-control bg-light"
                                        value="{{ $arrivalTicket->purchaseOrder->unique_no ?? 'N/A' }}" readonly>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="font-weight-bold">Contract Number</label>
                                    <input type="text" class="form-control bg-light"
                                        value="{{ $arrivalTicket->purchaseOrder->unique_no ?? 'N/A' }}" readonly>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="font-weight-bold">Prepared By:</label>
                                    <input type="text" class="form-control bg-light"
                                        value="{{ auth()->user()->name }}" readonly>
                                </div>
                            </div>
                        </div>
                    </div>
                    @if ($isNotGeneratable)
                        <div class="col-12 mt-4">
                            <div class="alert alert-danger">
                                <strong>Important!</strong> Please apply deductions first before generating the arrival
                                slip.
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row bottom-button-bar">
        <div class="col-12">
            <a type="button" class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton">Close</a>
            <button type="button" class="btn btn-info mr-2" id="printButton">
                <i class="ft-printer mr-1"></i> Print
            </button>
        </div>
    </div>
</form>


<script>
    $('#printButton').click(function() {
        // Use Print.js to print just the slabsContainer content
        printJS({
            printable: 'slabsContainer',
            type: 'html',
            css: [
                // Add any additional CSS files if needed
                'https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css',
                // Add your custom CSS file if needed
            ],
            style: `
            @page { size: auto; margin: 5mm; }
            body { padding: 20px; }
            table { width: 100%; border-collapse: collapse; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            th { background-color: #f2f2f2; }
        `,
            scanStyles: false, // Set to true if you want to use the page styles
            targetStyles: ['*'] // Include all styles
        });
    });
</script>
