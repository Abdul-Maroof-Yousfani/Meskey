<form action="{{ route('arrival-location.update', $locationTransfer->id) }}" method="POST" id="ajaxSubmit"
    autocomplete="off">
    @csrf
    @method('PUT')
    <input type="hidden" id="listRefresh" value="{{ route('get.arrival-location') }}" />
    <div class="row form-mar">
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Ticket:</label>
                <select class="form-control select2" name="arrival_ticket_id">
                  
                        <option  value="{{ $locationTransfer->arrivalTicket->id }}">
                            Ticket No: {{ $locationTransfer->arrivalTicket->unique_no }} --
                            Truck No: {{ $locationTransfer->arrivalTicket->truck_no ?? '-' }}
                        </option>
                    
                </select>
            </div>
        </div>
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Location:</label>
                <select class="form-control select2" name="arrival_location_id">
                    <option value="">{{$locationTransfer->arrivalLocation->name}}</option>
                    
                </select>
            </div>
        </div>

        <!-- Description -->
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Remarks:</label>
                <textarea name="remark" placeholder="Remarks" class="form-control">{{$locationTransfer->remark}}</textarea>
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
