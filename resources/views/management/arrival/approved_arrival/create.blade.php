<form action="{{ route('arrival-approve.store') }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    <input type="hidden" id="listRefresh" value="{{ route('get.arrival-approve') }}" />
    <div class="row form-mar">
        <div class="col-xs-12 col-sm-12 col-md-12">
            {!! getUserMissingInfoAlert()  !!}
            <div class="form-group">
                <label>Ticket:</label>
                <select class="form-control select2" name="arrival_ticket_id" required>
                    <option value="">Select Ticket</option>
                    @foreach ($ArrivalTickets as $arrivalTicket)
                        <option data-locationid="{{ $arrivalTicket->location_id }}"
                            data-secondqcstatus="{{ $arrivalTicket->second_qc_status }}"
                            data-trucknumber="{{ $arrivalTicket->truck_no }}" data-bags="{{ $arrivalTicket->bags }}"
                            value="{{ $arrivalTicket->id }}">
                            Ticket No: {{ $arrivalTicket->unique_no }} --
                            Truck No: {{ $arrivalTicket->truck_no ?? '-' }} --
                            Arrival Location: {{ $arrivalTicket->unloadingLocation->arrivalLocation?->name ?? 'N' }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group">
                <label>Gala Name:</label>
                <select class="form-control select2" name="gala_id" id="gala_id">
                    <option value="">Select Gala</option>
                    @foreach ($arrivalSubLocations as $arrivalSubLocation)
                        <option data-locationid="{{ $arrivalSubLocation->arrival_location_id }}"
                            value="{{ $arrivalSubLocation->id }}">{{ $arrivalSubLocation->name }}</option>
                    @endforeach
                </select>

            </div>
        </div>
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group">
                <label>Truck No:</label>
                <input type="text" readonly name="truck_no" placeholder="Truck No" class="form-control"
                    autocomplete="off" required />
            </div>
        </div>
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group">
                <label>Bag type:</label>
                <select class="form-control select2" name="bag_type_id" id="bag_type_id">
                    <option value="">Select Bag type</option>
                    @foreach ($bagTypes as $bagType)
                        <option value="{{ $bagType->id }}">{{ $bagType->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group filling-bags-field">
                <label>Filling Bags: </label>
                <input type="number" min="0" name="filling_bags_no" placeholder="Filling Bags" class="form-control"
                    autocomplete="off" />
            </div>
        </div>
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group bag-condition-field">
                <label>Bag Condition:</label>
                <select class="form-control select2" name="bag_condition_id">
                    <option value="">Select Condition</option>
                    @foreach ($bagConditions as $condition)
                        <option value="{{ $condition->id }}">{{ $condition->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group bag-packing-field">
                <label>Bag Packing:</label>
                <select class="form-control" name="bag_packing_id">
                    <option value="">Select Bag Packing</option>
                    @foreach ($bagPackings as $packing)
                        <option value="{{ $packing->id }}">{{ $packing->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                {{-- <label>Bag Packing Approval:</label> --}}
                <div class="input-group mt-2">
                    <div class="radio d-inline-block mr-2 mb-1">
                        <input type="radio" name="bag_packing_approval" id="half-approved" value="Half Approved">
                        <label for="half-approved">Half Approved</label>
                    </div>
                    <div class="radio d-inline-block">
                        <input type="radio" name="bag_packing_approval" id="full-approved" checked
                            value="Full Approved">
                        <label for="full-approved">Full Approved</label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <h6 class="header-heading-sepration">
                Total Receivings
            </h6>
        </div>
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Total Bags : </label>
                <input type="number" name="total_bags" placeholder="Total Bags" class="form-control" autocomplete="off"
                    readonly required />
            </div>
        </div>
    </div>
    <div class="row total-rejection-section">
        <div class="col-12">
            <h6 class="header-heading-sepration" style="background:#ffafaf">
                Total Rejection
            </h6>
        </div>

        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Total Rejection Bags : </label>
                <input type="number" readonly name="total_rejection" id="total_rejection"
                    placeholder="Total Rejection Bags" class="form-control" autocomplete="off" />
            </div>
        </div>
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Amanat:</label>
                <select class="form-control" name="amanat">
                    <option value="No">No</option>
                    <option value="Yes">Yes</option>
                </select>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Note:</label>
                <textarea name="note" placeholder="Note" class="form-control" rows="5"></textarea>
            </div>
        </div>
    </div>

    <div class="row bottom-button-bar">
        <div class="col-12">
            <a type="button" class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton">Close</a>
            <button type="submit" class="btn btn-primary submitbutton">Save</button>
        </div>
    </div>
</form>

<script>
    $(document).ready(function () {


        // 1️⃣ Select2 init (sirf ek dafa)
        $('#gala_id').select2({
            placeholder: 'Select Gala',
            width: '100%'
        });

        // 2️⃣ Original options backup
        var originalGalaOptions = $('#gala_id').html();

        // 3️⃣ Ticket change
        $('select[name="arrival_ticket_id"]').on('change', function () {

            var locationId = $(this).find('option:selected').data('locationid');

            // Reset gala options
            $('#gala_id').html(originalGalaOptions);

            if (!locationId) {
                $('#gala_id').val(null).trigger('change');
                return;
            }

            // Sirf matching gala rakho, baqi remove
            $('#gala_id option').each(function () {
                if ($(this).data('locationid') != locationId && $(this).val() !== "") {
                    $(this).remove();
                }
            });

            // Select2 refresh
            $('#gala_id').select2({
                placeholder: 'Select Gala',
                width: '100%'
            });

        });




        function toggleBagFields() {
            let selectedBagType = $('#bag_type_id option:selected').text().toLowerCase();
            if (selectedBagType.includes('bulk')) {
                $('.filling-bags-field, .bag-condition-field, .bag-packing-field').hide();
            } else {
                $('.filling-bags-field, .bag-condition-field, .bag-packing-field').show();
            }
        }

        toggleBagFields();

        $('#bag_type_id').change(function () {
            toggleBagFields();
        });

        $('input[name="bag_packing_approval"]').change(function () {
            if ($(this).val() == "Half Approved") {
                $('input[name="total_bags"]').removeAttr('readonly');
                $(".total-rejection-section").slideDown();
            } else {
                $(".total-rejection-section").slideUp();
            }
        }).trigger('change');

        $('.select2').select2();

        $('input[name="total_bags"]').on('change input', function () {
            let selectedTicket = $('select[name="arrival_ticket_id"]').find(':selected');
            let totalTicketBags = selectedTicket.data('bags') || 0;
            let enteredBags = parseInt($(this).val()) || 0;

            let totalRejection = totalTicketBags - enteredBags;

            $('#total_rejection').val(totalRejection);
        });

        $('select[name="arrival_ticket_id"]').on('change', function () {
            let selectedOption = $(this).find(':selected');
            let truckNo = selectedOption.data('trucknumber') || '';
            let qcStatus = selectedOption.data('secondqcstatus') || '';

            $('input[name="truck_no"]').val(truckNo);

            if (qcStatus.toLowerCase() === 'rejected') {
                let totalTicketBags = selectedOption.data('bags') || 0;
                let enteredBags = parseInt($('input[name="total_bags"]').val()) || 0;

                let totalRejection = totalTicketBags - enteredBags;

                $('#total_rejection').val(totalRejection);

                $('#half-approved').prop('checked', true).trigger('change');
                $('input[name="bag_packing_approval"]').prop('disabled', true);

                $('<input>').attr({
                    type: 'hidden',
                    name: 'bag_packing_approval',
                    class: 'tempfield',
                    value: 'Half Approved'
                }).insertAfter($('input[name="bag_packing_approval"]').last());
            } else {
                let totalTicketBags = selectedOption.data('bags') || 0;

                $('input[name="total_bags"]').val(totalTicketBags)

                $('#full-approved').prop('checked', true).trigger('change');
                $('input[name="bag_packing_approval"]').prop('disabled', true);

                $('<input>').attr({
                    type: 'hidden',
                    name: 'bag_packing_approval',
                    class: 'tempfield',
                    value: 'Full Approved'
                }).insertAfter($('input[name="bag_packing_approval"]').last());
            }
        });

    });
</script>