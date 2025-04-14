<form action="{{ route('arrival-location.store') }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    <input type="hidden" id="listRefresh" value="{{ route('get.arrival-location') }}" />
    <div class="row form-mar">
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Ticket:</label>
                <select class="form-control select2" name="arrival_ticket_id">
                    <option value="">Select Ticket</option>
                    @foreach ($ArrivalTickets as $arrivalTicket)
                        <option value="{{ $arrivalTicket->id }}">
                            Ticket No: {{ $arrivalTicket->unique_no }} --
                            ITEM: {{ optional($arrivalTicket->product)->name }}
                        </option>
                    @endforeach

                </select>
            </div>
        </div>
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group ">
                <label>Gala Name:</label>
                <input type="text" name="truck_no" placeholder="Truck No" class="form-control" autocomplete="off" />
            </div>
        </div>
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group ">
                <label>Filling Bags: </label>
                <input type="number" name="bilty_no" placeholder="Filling Bags" class="form-control"
                    autocomplete="off" />
            </div>
        </div>
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group">
                <label>Bag type:</label>
                <select class="form-control" name="status">
                    <option value="active">Select Bag type</option>
                    <option value="active">P.P</option>
                    <option value="inactive">Jute</option>
                </select>
            </div>
        </div>
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group">
                <label>Bag Condition:</label>
                <select class="form-control" name="status">
                    <option value="active">Select Condition</option>
                    <option value="active">Old</option>
                    <option value="inactive">New</option>
                </select>
            </div>
        </div>
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group">
                <label>Bag Packing:</label>
                <select class="form-control" name="status">
                    <option value="active">Select Bag Packing</option>
                    <option value="active">P.P</option>
                    <option value="inactive">Jute</option>
                </select>
            </div>
        </div>
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Bag Packing:</label>
                <div class="input-group mt-2">
                    <div class="radio d-inline-block mr-2 mb-1">
                        <input type="radio" name="approval_type" id="striped-form-6" name="striped-radio"
                            value="1">
                        <label for="striped-form-6">Half Approved</label>
                    </div>
                    <div class="radio d-inline-block">
                        <input type="radio" name="approval_type" id="striped-form-7" checked=""
                            name="striped-radio" value="2">
                        <label for="striped-form-7">Full Approved</label>
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
            <div class="form-group ">
                <label>Total Bags : </label>
                <input type="number" name="bilty_no" placeholder="Total Bags" class="form-control"
                    autocomplete="off" />
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
            <div class="form-group ">
                <label>Total Bags : </label>
                <input type="number" name="bilty_no" placeholder="Total Bags" class="form-control"
                    autocomplete="off" />
            </div>
        </div>
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Amanat:</label>
                <select class="form-control" name="status">
                    <option value="active">No</option>
                    <option value="inactive">Yes</option>
                </select>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Description -->
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Note:</label>
                <textarea name="remark" placeholder="Note" class="form-control" rows="5"></textarea>
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
        $('input[name="approval_type"]').change(function() {
            if ($(this).val() == "1") {
                $(".total-rejection-section").slideDown();
            } else {
                $(".total-rejection-section").slideUp();
            }
        }).trigger('change'); // Page load pe bhi check karne ke liye


        $('.select2').select2();
    });
</script>
