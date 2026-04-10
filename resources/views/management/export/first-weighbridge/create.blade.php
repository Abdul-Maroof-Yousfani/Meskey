<form action="{{ route('export-first-weighbridge.store') }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    <input type="hidden" id="listRefresh" value="{{ route('get.export-first-weighbridge') }}" />
    <div class="row form-mar">
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Tickets:</label>
                <select class="form-control select2" name="loading_program_item_id" id="loading_program_item_id">
                    <option value="">Select Ticket</option>
                    @foreach ($Tickets as $ticket)
                        <option value="{{ $ticket->id }}">
                            {{ $ticket->transaction_number }} -- {{ $ticket->truck_number }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>
    <div class="row" id="slabsContainer"></div>

    <div class="row bottom-button-bar">
        <div class="col-12">
            <a type="button" class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton">Close</a>
            <button type="submit" class="btn btn-primary submitbutton">Save</button>
        </div>
    </div>
</form>

<script>
    $(document).ready(function() {
        $('.select2').select2();
    });

    $(document).ready(function() {
        $('#loading_program_item_id').change(function() {
            var loading_program_item_id = $(this).val();

            if (loading_program_item_id) {
                $.ajax({
                    url: '{{ route('export.getFirstWeighbridgeRelatedData') }}',
                    type: 'GET',
                    data: {
                        loading_program_item_id: loading_program_item_id
                    },
                    dataType: 'json',
                    beforeSend: function() {
                        Swal.fire({
                            title: "Processing...",
                            text: "Please wait while fetching ticket details.",
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
                            Swal.fire("No Data", "No ticket details found.", "info");
                        }
                    },
                    error: function() {
                        Swal.close();
                        Swal.fire("Error", "Something went wrong. Please try again.", "error");
                    }
                });
            } else {
                $('#slabsContainer').html('');
            }
        });
    });
</script>
