<form action="{{ route('freight.store') }}" method="POST" id="ajaxSubmit" autocomplete="off"
    enctype="multipart/form-data">
    @csrf
    <input type="hidden" name="arrival_ticket_id" value="{{ $ticket->id }}" />
    <input type="hidden" id="listRefresh" value="{{ route('get.freight') }}" />
    <a onclick="openModal(this,'{{ route('arrival-slip.edit', 0) }}','View Arrival Slip', true, '100%')"
        data-variable="slip" id="afterAjax" class="d-none">
    </a>

    <div class="row form-mar">
        <div class="col-md-6">
            <div class="form-group">
                <label>Ticket #</label>
                <input type="text" name="ticket_number" class="form-control" value="{{ $ticket->unique_no }}"
                    readonly />
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label>Miller</label>
                <input type="text" name="supplier" class="form-control" value="{{ $ticket->miller->name }}" readonly />
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label>Accounts Of</label>
                <input type="text" name="supplier" class="form-control"
                    value="{{ $ticket->accounts_of_name ?? 'N/A' }} " readonly />
            </div>
        </div>


        <div class="col-md-6">
            <div class="form-group">
                <label>Commodity</label>
                <input type="text" name="commodity" class="form-control" value="{{ $ticket->product->name ?? '' }}"
                    readonly />
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label>Truck #</label>
                <input type="text" name="truck_number" class="form-control" value="{{ $ticket->truck_no }}" readonly />
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label>Bilty #</label>
                <input type="text" name="billy_number" class="form-control" value="{{ $ticket->bilty_no }}" readonly />
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label>Sauda Type</label>
                <input type="text" disabled name="sauda_type" class="form-control"
                    value="{{ optional($ticket->saudaType)->name }}" />
            </div>
        </div>
        <div class="col-12" bis_skin_checked="1">
            <h6 class="header-heading-sepration">
                Estimated Freight
            </h6>
        </div>
        <div class="col-md col-6">
            <div class="form-group">
                <label>Loaded Weight</label>
                <input type="number" name="loaded_weight" class="form-control" value="{{ $ticket->net_weight }}"
                    readonly />
            </div>
        </div>
        <div class="col-md col-6">
            <div class="form-group">
                <label>Arrived Weight</label>
                <input type="number" name="arrived_weight" class="form-control"
                    value="{{ $ticket->arrived_net_weight }}" readonly />
            </div>
        </div>
        <div class="col-md col-6">
            <div class="form-group">
                <label>Difference</label>
                <input type="number" name="difference" class="form-control"
                    value="{{ ($ticket->arrived_net_weight ?? 0) - ($ticket->net_weight ?? 0) }}" readonly />
            </div>
        </div>

        <div class="col-md col-6">
            <div class="form-group">
                <label>Exempted Weight</label>
                <input type="number" name="exempted_weight" class="form-control calculate-net-shortage" value="0" />
            </div>
        </div>

        <div class="col-md col-6">
            <div class="form-group">
                <label>Net Shortage</label>
                <input type="number" name="net_shortage" class="form-control" value="0" readonly />
            </div>
        </div>
        <div class="col-12" bis_skin_checked="1">
            <h6 class="header-heading-sepration">
                Freight &amp; Charges
            </h6>
        </div>
        <div class="col-md col-6">
            <div class="form-group">
                <label>Freight Written on Bilty</label>
                <input type="number" step="0.01" name="freight_written_on_bilty"
                    class="form-control calculate-final freight-rs" value="0" min="0" />
            </div>
        </div>
        <div class="col-md col-6">
            <div class="form-group">
                <label>Freight per Ton</label>
                <input type="number" step="0.01" value="0" name="freight_per_ton"
                    class="form-control calculate-freight freight-per-ton" min="0" required />
            </div>
        </div>
        <div class="col-md col-6">
            <div class="form-group">
                <label>Kanta Loading Charges</label>
                <input type="number" step="0.01" min="0" name="kanta_golarchi_charges"
                    class="form-control calculate-final" value="0" />
            </div>
        </div>

        <div class="col-md col-6">
            <div class="form-group">
                <label>Arrived Kanta Charges</label>
                <input type="number" step="0.01" name="karachi_kanta_charges" readonly
                    class="form-control calculate-final" min="0"
                    value="{{ $ticket->truckType->weighbridge_amount ?? 0 }}" />
            </div>
        </div>

        <div class="col-md col-6">
            <div class="form-group">
                <label>Other (+)/Labour Charges</label>
                <input type="number" step="0.01" name="other_labour_charges" class="form-control calculate-final"
                    value="0" min="0" />
            </div>
        </div>

        <div class="col-12" bis_skin_checked="1">
            <h6 class="header-heading-sepration">
                Deductions
            </h6>
        </div>

        <div class="col-md-6">
            <div class="form-group">
                <label>Godown Penalty</label>
                <input type="number" step="0.01" name="other_deduction" min="0" class="form-control calculate-final"
                    value="0" />
            </div>
        </div>

        <div class="col-md-6">
            <div class="form-group">
                <label>Unpaid Labor Charges</label>
                <input type="number" step="0.01" name="unpaid_labor_charges" class="form-control calculate-final"
                    value="0" min="0" />
            </div>
        </div>



        <div class="col-12" bis_skin_checked="1">
            <h6 class="header-heading-sepration">
                Sub Total
            </h6>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label>Gross Freight Amount</label>
                <input type="number" step="0.01" name="gross_freight_amount" class="form-control" value="0" readonly />
            </div>
        </div>

        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Penalty</label>
                <input type="text" class="form-control bg-light" name="penalty" value="0" disabled>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label>Labor Charges</label>
                <input type="number" step="0.01" disabled name="labor_charges" class="form-control "
                    value="0" min="0" />
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label>Net Freight</label>
                <input type="number" step="0.01" name="net_freight" class="form-control" value="0" readonly />
            </div>
        </div>

        <div class="col-md-6 d-none">
            <div class="form-group">
                <label>Status</label>
                <input type="hidden" name="status" value="approved">
            </div>
        </div>

        <div class="col-12" bis_skin_checked="1">
            <h6 class="header-heading-sepration">
                Document Attachments
            </h6>
            <!-- <div class="alert alert-info">
                <strong>Attachment Guidelines:</strong>
                <ul class="mb-0">
                    <li>Only image formats are allowed (JPEG, PNG, JPG)</li>
                    <li>Uploaded images will be compressed automatically to reduce resolution</li>
                    <li>Maximum file size: 5MB per file</li>
                </ul>
            </div> -->
        </div>

        <div class="col-md-3">
            <div class="form-group">
                <label>Attach Bilty</label>
                <input type="file" name="bilty_document" class="form-control-file" />
            </div>
        </div>

        <div class="col-md-3">
            <div class="form-group">
                <label>Attach Loading Weight</label>
                <input type="file" name="loading_weight_document" class="form-control-file" />
            </div>
        </div>

        <div class="col-md-3">
            <div class="form-group">
                <label>Other Document (Optional)</label>
                <input type="file" name="other_document" class="form-control-file" />
            </div>
        </div>

        <div class="col-md-3">
            <div class="form-group">
                <label>Other Document 2 (Optional)</label>
                <input type="file" name="other_document_2" class="form-control-file" />
            </div>
        </div>
    </div>

    @if ($isNotGeneratable)
        <div style="margin-top: 15px; padding: 10px; background-color: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; border-radius: 4px;"
            class="mb-3">
            <strong>Important!</strong> Please apply deductions first before generating the arrival
            slip.
        </div>
    @endif

    <div class="row bottom-button-bar">
        <div class="col-12">
            <a type="button" class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton">Close</a>
            @if (!$isNotGeneratable)
                <button type="submit" class="btn btn-primary submitbutton">Save</button>
            @endif
        </div>
    </div>
</form>

<script>
    $('.calculate-net-shortage').on('input', function () {
        const exemptedWeight = parseFloat($(this).val()) || 0;
        const netShortage = parseFloat(
            "{{ ($ticket->arrived_net_weight ?? 0) - ($ticket->net_weight ?? 0) }}") + exemptedWeight;
        $('input[name="net_shortage"]').val(netShortage);
        calculateFinalAmounts();
    });

    $('.calculate-final, .calculate-freight').on('input', calculateFinalAmounts);

    function calculateFinalAmounts() {
        const kantaCharges = parseFloat($('input[name="kanta_golarchi_charges"]').val()) || 0;
        const karachiKanta = parseFloat($('input[name="karachi_kanta_charges"]').val()) || 0;
        const labourCharges = parseFloat($('input[name="other_labour_charges"]').val()) || 0;
        const writtenOnBilly = parseFloat($('input[name="freight_written_on_bilty"]').val()) || 0;
        const otherDeduction = parseFloat($('input[name="other_deduction"]').val()) || 0;
        const unpaidLabor = parseFloat($('input[name="unpaid_labor_charges"]').val()) || 0;

        // Calculate total charges to be added
        const totalCharges = kantaCharges + karachiKanta + labourCharges;

        // Calculate final freight amount
        // let finalVal = (writtenOnBilly + totalCharges) - (otherDeduction + unpaidLabor);
        let gross = (writtenOnBilly + totalCharges);
        let finalVal = (writtenOnBilly + totalCharges) - (unpaidLabor) - otherDeduction;

        $('input[name="penalty"]').val(otherDeduction.toFixed(2));
        $('input[name="labor_charges"]').val(unpaidLabor.toFixed(2));
        $('input[name="gross_freight_amount"]').val(gross.toFixed(2));
        $('input[name="net_freight"]').val(finalVal.toFixed(2));
    }

    $('.calculate-net-shortage').trigger('input');


    $('.freight-rs').on('input', function () {
        calculateFreightFromRs();
    });

    $('.freight-per-ton').on('input', function () {
        calculateFreightFromTon();
    });

    function calculateFreightFromRs() {
        const freightRs = parseFloat($('.freight-rs').val()) || 0;
        const loadingWeight = parseFloat('{{ $ticket->net_weight ?? 0 }}') || 0;

        if (loadingWeight > 0) {
            const freightPerTon = (freightRs / loadingWeight) * 1000;
            $('.freight-per-ton').val(freightPerTon.toFixed(2));
        }

    }

    function calculateFreightFromTon() {
        const freightPerTon = parseFloat($('.freight-per-ton').val()) || 0;
        const loadingWeight = parseFloat('{{ $ticket->net_weight ?? 0 }}') || 0;

        if (loadingWeight > 0) {
            const freightRs = (freightPerTon * loadingWeight) / 1000;
            $('.freight-rs').val(freightRs.toFixed(2));
        }

    }
</script>