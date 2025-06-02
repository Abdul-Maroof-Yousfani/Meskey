<form action="{{ route('inner-sampling-request.store') }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    <input type="hidden" id="listRefresh" value="{{ route('get.inner-sampling-request') }}" />
    <div class="row form-mar">
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Ticket:</label>
                <select class="form-control select2" name="ticket_id">
                    <option value="">Select Ticket</option>
                    @foreach ($ArrivalTickets as $arrivalTicket)

                        @if($arrivalTicket->document_approval_status != null)
                            @if($arrivalTicket->second_qc_status != 'rejected')
                                <option value="{{ $arrivalTicket->id }}">
                                    Ticket No: {{ $arrivalTicket->unique_no }} --
                                    Truck No: {{ $arrivalTicket->truck_no ?? '-' }}
                                </option>
                            @endif
                        @endif
                    @endforeach
                </select>
            </div>
        </div>

        <!-- Description -->
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Remarks:</label>
                <textarea name="remark" placeholder="Remarks" class="form-control" rows="5"></textarea>
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
    $(document).ready(function () {
        $('.select2').select2();
    });
</script>