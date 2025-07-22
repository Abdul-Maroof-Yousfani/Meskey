<form action="{{ route('first-weighbridge.store') }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    <input type="hidden" id="listRefresh" value="{{ route('get.first-weighbridge') }}" />
    <div class="row form-mar">
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Ticket:</label>
                <select class="form-control select2" disabled name="arrival_ticket_id" id="arrival_ticket_id">
                    <option value="">Select Ticket</option>
                    @foreach ($arrivalTickets as $arrivalTicket)
                        <option @selected($firstWeighbridge->arrival_ticket_id == $arrivalTicket->id) value="{{ $arrivalTicket->id }}">
                            Ticket No: {{ $arrivalTicket->unique_no }} --
                            Truck No: {{ $arrivalTicket->truck_no }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>
    <div class="row" id="slabsContainer">
        <div class="col-12">
            <h6 class="header-heading-sepration">
                Ticket Detail
            </h6>
        </div>
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group ">
                <label>Commodity:</label>
                <input type="text" placeholder="First Weight"
                    value="{{ optional($firstWeighbridge->arrivalTicket->qcProduct)->name ?? 'N/A' }}" disabled
                    class="form-control" autocomplete="off" />
            </div>
        </div>
        <div class="col-xs-4 col-sm-4 col-md-4">
            <div class="form-group ">
                <label>First Weight:</label>
                <input type="text" placeholder="First Weight"
                    value="{{ $firstWeighbridge->arrivalTicket->first_weight }}" disabled class="form-control"
                    autocomplete="off" />
            </div>
        </div>
        <div class="col-xs-4 col-sm-4 col-md-4">
            <div class="form-group ">
                <label>2nd Weight:</label>
                <input type="text" placeholder="2nd Weight"
                    value="{{ $firstWeighbridge->arrivalTicket->second_weight }}" disabled class="form-control"
                    autocomplete="off" />
            </div>
        </div>
        <div class="col-xs-4 col-sm-4 col-md-4">
            <div class="form-group ">
                <label>Net Weight:</label>
                <input type="text" placeholder="Net Weight"
                    value="{{ $firstWeighbridge->arrivalTicket->net_weight }}" disabled class="form-control"
                    autocomplete="off" />
            </div>
        </div>
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group ">
                <label>Truck Type:</label>
                <input type="text" placeholder="Truck Type"
                    value="{{ $firstWeighbridge->arrivalTicket->truckType->name }}" disabled class="form-control"
                    autocomplete="off" />
            </div>
        </div>

        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group ">
                <label>Weighbridge Money:</label>
                <input type="text" placeholder="Weighbridge Money"
                    value="{{ $firstWeighbridge->arrivalTicket->truckType->weighbridge_amount }}" disabled
                    class="form-control" autocomplete="off" />
            </div>
        </div>

        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group ">
                <label>First Weight:</label>
                <input type="text" name="first_weight" value="{{ $firstWeighbridge->weight }}" placeholder="Weight"
                    class="form-control" autocomplete="off" />
            </div>
        </div>


        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Comment:</label>
                <textarea name="remark" placeholder="Remarks" class="form-control">{{ $firstWeighbridge->remark }}</textarea>
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
        $('#arrival_ticket_id').change(function() {
            var arrival_ticket_id = $(this).val();

            if (arrival_ticket_id) {
                $.ajax({
                    url: '{{ route('getFirstWeighbridgeRelatedData') }}',
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
                            // Append the rendered HTML to a container element
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
    });

    $(document).ready(function() {
        $('.select2').select2();
    });
</script>
