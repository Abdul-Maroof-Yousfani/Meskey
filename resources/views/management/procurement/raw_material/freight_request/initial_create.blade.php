<div class="row">
    <div class="col-xs-12 col-sm-12 col-md-12">
        <div class="form-group">
            <label class="font-weight-bold">Arrival Slip:</label>
            <select name="arrival_ticket_id" id="arrival_ticket_id_initial" class="form-control select2-initial" required>
                <option value="">Select Arrival Slip</option>
                @foreach ($tickets as $ticket)
                    <option value="{{ $ticket->id }}">
                        Slip: {{ $ticket->arrivalSlip->unique_no }} - Ticket: {{ $ticket->unique_no }} ({{ $ticket->truck_no }})
                    </option>
                @endforeach
            </select>
        </div>
    </div>
</div>

<div id="fullFreightFormContainer"></div>

<script>
    $(document).ready(function() {
        $('.select2-initial').select2({
            width: '100%',
            dropdownParent: $('#modal-sidebar')
        });

        $('#arrival_ticket_id_initial').on('change', function() {
            var ticketId = $(this).val();

            if (ticketId) {
                $.ajax({
                    url: '{{ route('raw-material.freight-request.getFreightRequestForm') }}',
                    type: 'GET',
                    data: {
                        arrival_ticket_id: ticketId
                    },
                    beforeSend: function() {
                        Swal.fire({
                            title: "Loading...",
                            text: "Fetching freight request form",
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });
                    },
                    success: function(response) {
                        Swal.close();
                        if (response.success) {
                            $('#fullFreightFormContainer').html(response.html);
                            
                            // Initialize select2 for the newly loaded content
                             $('#fullFreightFormContainer .select2').select2({
                                width: '100%',
                                dropdownParent: $('#modal-sidebar')
                            });
                        } else {
                            Swal.fire("Error", response.message, "error");
                            $('#fullFreightFormContainer').empty();
                        }
                    },
                    error: function() {
                        Swal.close();
                        Swal.fire("Error", "Failed to load freight request form", "error");
                        $('#fullFreightFormContainer').empty();
                    }
                });
            } else {
                $('#fullFreightFormContainer').empty();
            }
        });
    });
</script>
