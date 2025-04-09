<form action="{{ route('ticket.update', $ArrivalTicket->id) }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    @method('PUT')
    <input type="hidden" id="listRefresh" value="{{ route('get.ticket') }}" />
    <div class="row form-mar">



        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group ">
                <label>Product:</label>
                <select name="product_id" id="product_id" class="form-control select2 ">
                    <option value="{{$ArrivalTicket->product->id}}">{{$ArrivalTicket->product->name}}</option>
                    <option value="">Product Name</option>
                </select>
            </div>
        </div>
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group ">
                <label>Supplier:</label>
                <select name="supplier_name" id="supplier_name" class="form-control select2 ">
                    <option value="{{$ArrivalTicket->supplier_name}}">{{$ArrivalTicket->supplier_name}}</option>
                </select>
            </div>
        </div>

        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group ">
                <label>Broker:</label>
                <select name="broker_name" id="broker_name" class="form-control select2 ">
                    <option value="{{$ArrivalTicket->broker_name}}">{{$ArrivalTicket->broker_name}}</option>
                </select>
            </div>
        </div>
     <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group ">
                <label>Accounts Of:</label>
                <select name="accounts_of" id="accounts_of" class="form-control select2 ">
                    <option value="">Accounts Of</option>
                </select>
            </div>
        </div>
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group ">
                <label>Station:</label>
                <input type="text" name="station_name" placeholder="Station" class="form-control" autocomplete="off" />
            </div>
        </div>
         <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group ">
                <label>Truck Type:</label>
                <select name="arrival_truck_type_id" id="arrival_truck_type_id" class="form-control select2 ">
                    <option value="">Truck Type</option>
                </select>
            </div>
        </div>
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group ">
                <label>Truck No:</label>
                <input type="text" name="truck_no" value="{{$ArrivalTicket->truck_no}}" placeholder="Truck No"
                    class="form-control" autocomplete="off" />
            </div>
        </div>
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group ">
                <label>Bilty No: </label>
                <input type="text" name="bilty_no" value="{{$ArrivalTicket->bilty_no}}" placeholder="Bilty No"
                    class="form-control" autocomplete="off" />
            </div>
        </div>
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group ">
                <label>No of bags: </label>
                <input type="text" name="bags" placeholder="No of bags" class="form-control" autocomplete="off" />
            </div>
        </div>
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group ">
                <label>Sample Money: </label>
                <input type="text" name="sample_money" placeholder="No of bags" class="form-control" autocomplete="off" />
            </div>
        </div>
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group ">
                <label>LOading Date: (Optional)</label>
                <input type="date" name="loading_date" value="{{$ArrivalTicket->loading_date}}" placeholder="Bilty No"
                    class="form-control" autocomplete="off" />
            </div>
        </div>
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group ">
                <label>Loading Weight:</label>
                <input type="text" name="loading_weight" value="{{$ArrivalTicket->loading_weight}}" placeholder="Loading Weight" class="form-control"
                    autocomplete="off" />
            </div>
        </div>

        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group ">
                <label>Remarks (Optional):</label>
                <textarea name="remarks" row="2" class="form-control"
                    placeholder="Remarks">{{$ArrivalTicket->remarks}}</textarea>
            </div>
        </div>
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group ">
                <label>Status:</label>
                <select name="status" class="form-control">
                    <option {{$ArrivalTicket->status == 'active' ? 'selected' : ''}} value="active">Active</option>
                    <option {{$ArrivalTicket->status == 'inactive' ? 'selected' : ''}} value="inactive">Inactive</option>
                </select>
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
        initializeDynamicSelect2('#product_id', 'products', 'name', 'id', false, false);
        initializeDynamicSelect2('#supplier_name', 'suppliers', 'name', 'name', true, false);
                initializeDynamicSelect2('#broker_name', 'brokers', 'name', 'name', true, false);
        initializeDynamicSelect2('#arrival_truck_type_id', 'arrival_truck_types', 'name', 'id', true, false);

        //  function initializeDynamicSelect2(selector, tableName, columnName, idColumn = 'id', enableTags = false, isMultiple = true) {

    });
</script>