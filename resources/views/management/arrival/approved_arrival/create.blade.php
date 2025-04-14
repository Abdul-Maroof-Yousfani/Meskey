<form action="{{ route('arrival-approve.store') }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    <input type="hidden" id="listRefresh" value="{{ route('get.arrival-approve') }}" />
    <div class="row form-mar">
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Ticket:</label>
                <select class="form-control select2" name="arrival_ticket_id" required>
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
            <div class="form-group">
                <label>Gala Name:</label>
                <input type="text" name="gala_name" placeholder="Gala Name" class="form-control" autocomplete="off"
                    required />
            </div>
        </div>
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Truck No:</label>
                <input type="text" name="truck_no" placeholder="Truck No" class="form-control" autocomplete="off"
                    required />
            </div>
        </div>
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group">
                <label>Filling Bags: </label>
                <input type="number" name="filling_bags_no" placeholder="Filling Bags" class="form-control"
                    autocomplete="off" required />
            </div>
        </div>
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group">
                <label>Bag type:</label>
                <select class="form-control" name="bag_type_id" required>
                    <option value="">Select Bag type</option>
                    @foreach ($bagTypes as $bagType)
                        <option value="{{ $bagType->id }}">{{ $bagType->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group">
                <label>Bag Condition:</label>
                <select class="form-control" name="bag_condition_id" required>
                    <option value="">Select Condition</option>
                    @foreach ($bagConditions as $condition)
                        <option value="{{ $condition->id }}">{{ $condition->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group">
                <label>Bag Packing:</label>
                <select class="form-control" name="bag_packing_id" required>
                    <option value="">Select Bag Packing</option>
                    @foreach ($bagPackings as $packing)
                        <option value="{{ $packing->id }}">{{ $packing->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Bag Packing Approval:</label>
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
                    required />
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
                <input type="number" name="total_rejection" placeholder="Total Rejection Bags" class="form-control"
                    autocomplete="off" />
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
    $(document).ready(function() {
        $('input[name="bag_packing_approval"]').change(function() {
            if ($(this).val() == "Half Approved") {
                $(".total-rejection-section").slideDown();
            } else {
                $(".total-rejection-section").slideUp();
            }
        }).trigger('change');

        $('.select2').select2();
    });
</script>
