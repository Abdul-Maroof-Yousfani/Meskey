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
                        <option value="{{ $arrivalTicket->id }}">
                            Ticket No: {{ $arrivalTicket->unique_no }} --
                            Truck No: {{ $arrivalTicket->truck_no ?? 'N/A' }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="col-xs-12 col-sm-12 col-md-12">
            <div id="slabsContainer">
            </div>
        </div>
    </div>

    <div class="row bottom-button-bar">
        <div class="col-12">
            <a type="button" class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton">Close</a>
            <button type="submit" class="btn btn-primary submitbutton d-none" id="buttonsBar">Generate</button>
        </div>
    </div>
</form>

<script>
    $(document).ready(function() {
        $('#arrival_ticket_id').change(function() {
            var arrival_ticket_id = $(this).val();

            if (arrival_ticket_id) {
                $.ajax({
                    url: '{{ route('getTicketDataForArrival') }}',
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

                            if (response.isNotGeneratable) {
                                $('#buttonsBar').addClass('d-none');
                                $('#printButton').addClass('d-none');
                            } else {
                                $('#buttonsBar').removeClass('d-none');
                                $('#printButton').removeClass('d-none');
                            }

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
            }
        });


        $('.select2').select2();
    });
</script>
