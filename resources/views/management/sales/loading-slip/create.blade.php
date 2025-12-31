<form action="{{ route('sales.loading-slip.store') }}" method="POST" id="ajaxSubmit" autocomplete="off" enctype="multipart/form-data">
    @csrf
    <input type="hidden" id="listRefresh" value="{{ route('sales.get.loading-slip') }}" />

    <div class="row form-mar">
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Tickets:</label>
                <select class="form-control select2" name="loading_program_item_id" id="loading_program_item_id">
                    <option value="">Select Ticket</option>
                    @foreach ($availableTickets as $ticket)
                        <option value="{{ $ticket->id }}">
                            {{ $ticket->transaction_number }} -- {{ $ticket->truck_number }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <div class="row" id="ticketDataContainer">
        <!-- Ticket data will be populated here -->
    </div>

    <div class="row">
        <div class="col-xs-12 col-sm-6 col-md-6">
            <div class="form-group">
                <label>Remarks:</label>
                <textarea name="remarks" placeholder="Enter remarks" class="form-control" rows="3"></textarea>
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
    $(document).ready(function() {
        $('.select2').select2();
    });

    $(document).ready(function() {
        // Handle ticket selection
        $('#loading_program_item_id').change(function() {
            var loading_program_item_id = $(this).val();

            if (loading_program_item_id) {
                $.ajax({
                    url: '{{ route("sales.getLoadingSlipTicketData") }}',
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
                            // Populate the form with ticket data
                            populateTicketData(response.data);
                        } else {
                            Swal.fire("No Data", "No ticket details found.",
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
                // Clear ticket data container if no ticket selected
                $('#ticketDataContainer').html('');
            }
        });
    });

    function populateTicketData(data) {
        var html = `
            <div class="row">
                <div class="col-xs-12 col-sm-6 col-md-3">
                    <div class="form-group">
                        <label>Customer: <span class="text-danger">*</span></label>
                        <input type="text" name="customer" value="${data.customer}" class="form-control" readonly required />
                    </div>
                </div>
                <div class="col-xs-12 col-sm-6 col-md-3">
                    <div class="form-group">
                        <label>Commodity: <span class="text-danger">*</span></label>
                        <input type="text" name="commodity" value="${data.commodity}" class="form-control" readonly required />
                    </div>
                </div>
                <div class="col-xs-12 col-sm-6 col-md-3">
                    <div class="form-group">
                        <label>SO Qty: <span class="text-danger">*</span></label>
                        <input type="number" name="so_qty" value="${data.so_qty}" class="form-control" readonly required step="0.01" />
                    </div>
                </div>
                <div class="col-xs-12 col-sm-6 col-md-3">
                    <div class="form-group">
                        <label>DO Qty: <span class="text-danger">*</span></label>
                        <input type="number" name="do_qty" value="${data.do_qty}" class="form-control" readonly required step="0.01" />
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-xs-12 col-sm-6 col-md-4">
                    <div class="form-group">
                        <label>Factory: <span class="text-danger">*</span></label>
                        <input type="text" name="factory" value="${data.factory}" class="form-control" readonly required />
                    </div>
                </div>
                <div class="col-xs-12 col-sm-6 col-md-4">
                    <div class="form-group">
                        <label>Gala:</label>
                        <input type="text" name="gala" value="${data.gala}" class="form-control" readonly />
                    </div>
                </div>
                <div class="col-xs-12 col-sm-6 col-md-4">
                    <div class="form-group">
                        <label>Bag Size: <span class="text-danger">*</span></label>
                        <input type="number" name="bag_size" value="${data.bag_size}" class="form-control" readonly required step="0.01" />
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-xs-12 col-sm-6 col-md-4">
                    <div class="form-group">
                        <label>No. of Bags: <span class="text-danger">*</span></label>
                        <input type="number" name="no_of_bags" id="no_of_bags" class="form-control" min="1" required>
                    </div>
                </div>
                <div class="col-xs-12 col-sm-6 col-md-4">
                    <div class="form-group">
                        <label>Kilogram: <span class="text-danger">*</span></label>
                        <input type="number" name="kilogram" id="kilogram" value="0.00" class="form-control" readonly required step="0.01" />
                    </div>
                </div>
                <div class="col-xs-12 col-sm-6 col-md-4">
                    <!-- Empty column for alignment -->
                </div>
            </div>
        `;

        $('#ticketDataContainer').html(html);

        // Calculate kilogram when no_of_bags changes
        $('#no_of_bags').on('input', function() {
            calculateKilogram();
        });

        function calculateKilogram() {
            var noOfBags = parseFloat($('#no_of_bags').val()) || 0;
            var bagSize = parseFloat($('input[name="bag_size"]').val()) || 0;
            var kilogram = noOfBags * bagSize;
            $('#kilogram').val(kilogram.toFixed(2));
        }
    }
</script>
