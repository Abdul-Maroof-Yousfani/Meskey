<form action="{{ route('location-transfer.store') }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    <input type="hidden" id="listRefresh" value="{{ route('get.location-transfer') }}" />
    <div class="row form-mar">
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Ticket:</label>
                <select class="form-control select2" name="arrival_ticket_id" id="arrival_ticket_id">
                    <option value="">Select Ticket</option>
                    @foreach ($ArrivalTickets as $arrivalTicket)
                        <option value="{{ $arrivalTicket->id }}" data-location-id="{{ $arrivalTicket->location_id }}"
                            data-product="{{ $arrivalTicket->product->name ?? 'N/A' }}">
                            Ticket No: {{ $arrivalTicket->unique_no }} --
                            Truck No: {{ $arrivalTicket->truck_no }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Location:</label>
                <select class="form-control select2" name="arrival_location_id" id="arrival_location_id">
                    <option value="">Select Location</option>
                    @foreach ($ArrivalLocations as $ArrivalLocations)
                        <option data-location-id="{{ $ArrivalLocations->company_location_id }}" value="{{ $ArrivalLocations->id }}">
                            {{ $ArrivalLocations->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <!-- Rest of your form remains the same -->
    <div class="row">
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Remarks:</label>
                <textarea name="remark" placeholder="Remarks" class="form-control"></textarea>
            </div>
        </div>
    </div>

    <div id="slabsContainer">
    </div>
    <div class="row bottom-button-bar">
        <div class="col-12">
            <a type="button" class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton">Close</a>
            <button type="submit" class="btn btn-primary submitbutton">Save</button>
        </div>
    </div>
</form>

<script>

// 1️⃣ Select2 init (SIRF EK DAFA)
$('#arrival_location_id').select2({
    placeholder: 'Select Arrival Location',
    width: '100%'
});

// 2️⃣ Original options backup
var originalOptions = $('#arrival_location_id').html();

// 3️⃣ Ticket change
$('#arrival_ticket_id').on('change', function () {

    var locationId = $(this).find('option:selected').data('location-id');

    // reset locations
    $('#arrival_location_id').html(originalOptions);

    if (!locationId) {
        $('#arrival_location_id').val(null).trigger('change');
        return;
    }

    // sirf matching location rakho (BAQI REMOVE)
    $('#arrival_location_id option').each(function () {
        if ($(this).data('location-id') != locationId && $(this).val() !== "") {
            $(this).remove();
        }
    });

    // select2 refresh
    $('#arrival_location_id').select2({
        placeholder: 'Select Arrival Location',
        width: '100%'
    });

});




    
    $(document).ready(function() {
        $('.select2').select2();

        $('#arrival_ticket_id').change(function() {
            var selectedOption = $(this).find('option:selected');
            var productName = selectedOption.data('product');

            // Update commodity field
            $('#commodity_name').val(productName);

            var arrival_ticket_id = $(this).val();
            if (arrival_ticket_id) {
                $.ajax({
                    url: '{{ route('getInitialSamplingResultByTicketId') }}',
                    type: 'GET',
                    data: {
                        arrival_ticket_id: arrival_ticket_id
                    },
                    dataType: 'json',
                    beforeSend: function() {
                        Swal.fire({
                            title: "Processing...",
                            text: "Please wait while fetching slabs.",
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });
                    },
                    success: function(response) {
                        Swal.close();
                        if (response.success) {
                            $('#slabsContainer').html(response.html);
                        } else {
                            Swal.fire("No Data", "No slabs found for this product.",
                                "info");
                        }
                    },
                    error: function() {
                        Swal.close();
                        Swal.fire("Error", "Something went wrong. Please try again.",
                            "error");
                    }
                });
            } else {
                // Clear commodity field if no ticket selected
                $('#commodity_name').val('');
            }
        });
    });
</script>
