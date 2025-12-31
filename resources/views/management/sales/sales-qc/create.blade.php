<form action="{{ route('sales.sales-qc.store') }}" method="POST" id="ajaxSubmit" autocomplete="off" enctype="multipart/form-data">
    @csrf
    <input type="hidden" id="listRefresh" value="{{ route('sales.get.sales-qc') }}" />

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

    <div class="row" id="ticketDataContainer">
        <!-- Ticket data will be populated here -->
    </div>

    <div class="row">
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>QC Remarks:</label>
                <textarea name="qc_remarks" placeholder="Enter QC remarks" class="form-control" rows="3"></textarea>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xs-12 col-sm-6 col-md-6">
            <div class="form-group">
                <label>Status:</label>
                <select class="form-control" name="status">
                    <option value="">Select Status</option>
                    <option value="accept">Accept</option>
                    <option value="reject">Reject</option>
                </select>
            </div>
        </div>
        <div class="col-xs-12 col-sm-6 col-md-6">
            <div class="form-group">
                <label>Attachments:</label>
                <input type="file" name="attachments[]" class="form-control" multiple accept="image/*,application/pdf,.doc,.docx">
                <small class="text-muted">Allowed: Images, PDF, DOC, DOCX (Max 10MB each)</small>
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
                    url: '{{ route('sales.getTicketRelatedData') }}',
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
            <div class="col-xs-12 col-sm-6 col-md-6">
                <div class="form-group">
                    <label>Factory: <span class="text-danger">*</span></label>
                    <input type="text" name="factory" value="${data.factory}" class="form-control" readonly required />
                </div>
            </div>
            <div class="col-xs-12 col-sm-6 col-md-6">
                <div class="form-group">
                    <label>Gala: <span class="text-danger">*</span></label>
                    <input type="text" name="gala" value="${data.gala}" class="form-control" readonly required />
                </div>
            </div>
            </div>
        `;

        $('#ticketDataContainer').html(html);
    }
</script>
