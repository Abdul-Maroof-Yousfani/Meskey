<div class="col-12">
    <h6 class="header-heading-sepration">
        Ticket Detail
    </h6>
</div>
<div class="col-xs-12 col-sm-12 col-md-12">
    <div class="form-group ">
        <label>Commodity:</label>
        <input type="text"  placeholder="First Weight" value="{{$ArrivalTicket->first_weight}}" disabled
            class="form-control" autocomplete="off" />
    </div>
</div>
<div class="col-xs-4 col-sm-4 col-md-4">
    <div class="form-group ">
        <label>1st Weight:</label>
        <input type="text"  placeholder="1st Weight" value="{{$ArrivalTicket->first_weight}}" disabled
            class="form-control" autocomplete="off" />
    </div>
</div>
<div class="col-xs-4 col-sm-4 col-md-4">
    <div class="form-group ">
        <label>2nd Weight:</label>
        <input type="text" placeholder="2nd Weight" value="{{$ArrivalTicket->second_weight}}" disabled
            class="form-control" autocomplete="off" />
    </div>
</div>
<div class="col-xs-4 col-sm-4 col-md-4">

    <div class="form-group ">
        <label>Net Weight:</label>
        <input type="text"  placeholder="Net Weight" value="{{$ArrivalTicket->net_weight}}" disabled
            class="form-control" autocomplete="off" />
    </div>
</div>
<div class="col-xs-6 col-sm-6 col-md-6">
    <div class="form-group ">
        <label>Truck Type:</label>
        <input type="text"  placeholder="Truck Type" value="{{$ArrivalTicket->truckType->name}}" disabled
            class="form-control" autocomplete="off" />
    </div>
</div>

<div class="col-xs-6 col-sm-6 col-md-6">
    <div class="form-group ">
        <label>Weighbridge Money:</label>
        <input type="text"  placeholder="Weighbridge Money" value="{{$ArrivalTicket->truckType->weighbridge_amount}}" disabled
            class="form-control" autocomplete="off" />
    </div>
</div>



<div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group ">
                <label>First Weight:</label>
                <input type="text" name="first_weight" placeholder="Weight" class="form-control"
                    autocomplete="off" />
            </div>
        </div>


        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Comment:</label>
                <textarea name="remark" placeholder="Remarks" class="form-control"></textarea>
            </div>
        </div>