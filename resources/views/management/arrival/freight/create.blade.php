<div class="row form-mar">
    <div class="col-xs-12 col-sm-12 col-md-12">
        <div class="form-group">
            <label>Select Ticket:</label>
            <select name="ticket_id" id="ticket_id" class="form-control select2">
                <option value="">Select Ticket</option>
                @foreach ($tickets as $ticket)
                    <option value="{{ $ticket->id }}">
                        {{ $ticket->unique_no }} - {{ $ticket->supplier_name }} - {{ optional($ticket->product)->name }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>
</div>

<div id="freightFormContainer"></div>

<script>
    $(document).ready(function() {
        $('.select2').select2();

        $('#ticket_id').change(function() {
            var ticketId = $(this).val();

            if (ticketId) {
                $.ajax({
                    url: '{{ route('freight.getFreightForm') }}',
                    type: 'GET',
                    data: {
                        ticket_id: ticketId
                    },
                    beforeSend: function() {
                        Swal.fire({
                            title: "Loading...",
                            text: "Fetching freight form",
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });
                    },
                    success: function(response) {
                        Swal.close();
                        if (response.success) {
                            $('#freightFormContainer').html(response.html);
                        } else {
                            Swal.fire("Error", response.message, "error");
                            $('#freightFormContainer').empty();
                        }
                    },
                    error: function() {
                        Swal.close();
                        Swal.fire("Error", "Failed to load freight form", "error");
                        $('#freightFormContainer').empty();
                    }
                });
            } else {
                $('#freightFormContainer').empty();
            }
        });
    });
</script>
