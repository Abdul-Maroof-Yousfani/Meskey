<form action="{{ route('arrival-location.update', $SecondWeighbridge->id) }}" method="POST" id="ajaxSubmit"
    autocomplete="off">
    @csrf
    @method('PUT')
    <input type="hidden" id="listRefresh" value="{{ route('get.arrival-location') }}" />
    <div class="row form-mar">
        {{-- <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Ticket:</label>
                <select class="form-control select2" name="arrival_ticket_id">
                    <option value="">Select Ticket</option>
                    @foreach ($ArrivalTickets as $arrivalTicket)
                    <option {{$ArrivalTicket->id == $SecondWeighbridge->arrival_ticket_id ? 'selected' : ''}} value="{{
                        $arrivalTicket->id }}">
                        Ticket No: {{ $arrivalTicket->unique_no }} --
                        Truck No: {{ $arrivalTicket->truck_no ?? '-' }}
                    </option>
                    @endforeach
                </select>
            </div>
        </div> --}}




        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Ticket:</label>
                <input type="text" placeholder="First Weight" value="{{ $ArrivalTicket->unique_no }}" disabled
                    class="form-control" autocomplete="off" />
            </div>
        </div>
        <div class="col-12">
            <h6 class="header-heading-sepration">
                Ticket Detail
            </h6>
        </div>
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group ">
                <label>Commodity:</label>
                <input type="text" placeholder="First Weight"
                    value="{{ optional($ArrivalTicket->qcProduct)->name ?? 'N/A' }}" disabled class="form-control"
                    autocomplete="off" />
            </div>
        </div>
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group">
                <label><i class="ft-truck"></i> Truck Type:</label>
                <input type="text" placeholder="First Weight" value="{{ $ArrivalTicket->truckType->name }}" disabled
                    class="form-control" autocomplete="off" />
            </div>
        </div>
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group ">
                <label>Weighbridge Money:</label>
                <input type="text" placeholder="First Weight"
                    value="{{ $ArrivalTicket->truckType->weighbridge_amount }}" disabled class="form-control"
                    autocomplete="off" />
            </div>
        </div>
        <div class="col-12">
            <h6 class="header-heading-sepration">
                Loaded Weight
            </h6>
        </div>
        <div class="col-xs-4 col-sm-4 col-md-4">
            <div class="form-group ">
                <label>1st Weight:</label>
                <input type="text" id="first_weight" placeholder="First Weight"
                    value="{{ $ArrivalTicket->first_weight }}" readonly class="form-control" autocomplete="off" />
            </div>
        </div>
        <div class="col-xs-4 col-sm-4 col-md-4">
            <div class="form-group ">
                <label>2nd Weight:</label>
                <input type="text" id="second_weight" placeholder="Second Weight"
                    value="{{ $ArrivalTicket->second_weight }}" disabled class="form-control" autocomplete="off" />
            </div>
        </div>
        <div class="col-xs-4 col-sm-4 col-md-4">
            <div class="form-group ">
                <label>Net Weight:</label>
                <input type="text" id="loaded_net_weight" placeholder="Net Weight"
                    value="{{ $ArrivalTicket->net_weight }}" disabled class="form-control" autocomplete="off" />
            </div>
        </div>

        <div class="col-12">
            <h6 class="header-heading-sepration">
                Arrived Weighbridge
            </h6>
        </div>
        <div class="col-xs-4 col-sm-4 col-md-4">
            <div class="form-group ">
                <label>1st Weighbridge Weight:</label>
                <input type="text" id="first_weighbridge" value="{{ $ArrivalTicket->firstWeighbridge->weight }}"
                    class="form-control" autocomplete="off" disabled />
            </div>
        </div>
        <div class="col-xs-4 col-sm-4 col-md-4">
            <div class="form-group">
                <label>2nd Weighbridge Weight:</label>
                <input type="number" id="second_weighbridge" name="second_weight"
                    placeholder="Enter Second Weighbridge" class="form-control" autocomplete="off"
                    value="{{ $SecondWeighbridge->weight }}" max="{{ $ArrivalTicket->firstWeighbridge->weight }}" />
            </div>
        </div>
        <div class="col-xs-4 col-sm-4 col-md-4">
            <div class="form-group ">
                <label>Net Weighbridge Weight:</label>
                <input type="text" id="weighbridge_net_weight" name="weighbridge_net_weight"
                    placeholder="Net Weighbridge" readonly class="form-control" autocomplete="off"
                    value="{{ $ArrivalTicket->firstWeighbridge->weight - $SecondWeighbridge->weight }}" />
            </div>
        </div>
        <div class="col-xs-12 col-sm-12 col-md-12">
            <fieldset>
                <div class="input-group">
                    <div class="input-group-prepend">
                        <button class="btn btn-primary" type="button">Weight Difference</button>
                    </div>
                    <input type="text" id="weight_difference" name="weight_difference"
                        placeholder="Weight Difference" readonly class="form-control" autocomplete="off"
                        value="{{ $ArrivalTicket->net_weight - ($SecondWeighbridge->weight - $ArrivalTicket->firstWeighbridge->weight) }}" />
                </div>
            </fieldset>
        </div>
        <!-- Description -->
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Comment:</label>
                <textarea name="remark" placeholder="Remarks" class="form-control">{{ $SecondWeighbridge->remark }}</textarea>
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
