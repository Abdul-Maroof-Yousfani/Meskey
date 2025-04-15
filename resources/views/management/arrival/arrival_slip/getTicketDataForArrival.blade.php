<div class="row form-mar">
    {{-- <div class="col-xs-12 col-sm-12 col-md-4">
        <div class="form-group ">
            <label>Product:</label>
            <input type="text" name="" value="{{$ArrivalTicket->product->name}}" placeholder="Weight"
                class="form-control" autocomplete="off" />
        </div>
    </div> --}}
    <div class="col-xs-12 col-sm-12 col-md-12">
        <div class="form-group ">
            <label>Supplier Name:</label>
            <input type="text" disabled name="" value="{{$ArrivalTicket->supplier_name}}" placeholder="Weight"
                class="form-control" autocomplete="off" />
        </div>
    </div>
    <div class="col-xs-4 col-sm-4 col-md-4">
        <div class="form-group ">
            <label>Truck No:</label>
            <input type="text" disabled name="" value="{{$ArrivalTicket->truck_no}}" placeholder="Weight"
                class="form-control" autocomplete="off" />
        </div>
    </div>
    <div class="col-xs-4 col-sm-4 col-md-4">
        <div class="form-group ">
            <label>Truck No:</label>
            <input type="text" disabled name="" value="{{$ArrivalTicket->bilty_no}}" placeholder="Weight"
                class="form-control" autocomplete="off" />
        </div>
    </div>
    <div class="col-xs-4 col-sm-4 col-md-4">
        <div class="form-group ">
            <label>Loading Date:</label>
            <input type="date" disabled name="" value="{{$ArrivalTicket->loading_date}}" placeholder="Weight"
                class="form-control" autocomplete="off" />
        </div>
    </div>

    <div class="col-xs-12 col-sm-12 col-md-12 table-responsive mb-3">
        <table class="table m-0">
            <thead>
                <tr >
                    <th class="col-md-3">Product</th>
                    <th class="col-md-3">Unloading Location</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{$ArrivalTicket->product->name}}</td>
                    <td>{{$ArrivalTicket->unloadingLocation->arrivalLocation->name}}</td>
                </tr>
            </thead>
        </table>
    </div>



    <div class="col-xs-12 col-sm-12 col-md-12">
        <div class="form-group ">
            <label>Comment (Optional):</label>
            <textarea name="remarks" row="4" class="form-control" placeholder="Description"></textarea>
        </div>
    </div>
</div>