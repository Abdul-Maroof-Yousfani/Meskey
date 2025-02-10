<form action="{{ route('ticket.update',$ArrivalTicket->id) }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    @method('PUT')
    <input type="hidden" id="listRefresh" value="{{ route('get.ticket') }}" />
    <div class="row form-mar">



        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group ">
                <label>Product:</label>
                <select name="product_id" id="product_id"class="form-control select2 ">
                    <option value="{{$ArrivalTicket->product->id}}">{{$ArrivalTicket->product->name}}</option>
                    <option value="">Product Name</option>
                </select>
            </div>
        </div>
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group ">
                <label>Supplier:</label>
                <select name="supplier_name" id="supplier_name" class="form-control select2 ">
                    <option value="{{$ArrivalTicket->supplier_name}}">{{$ArrivalTicket->supplier_name}}</option>
                </select>
            </div>
        </div>
    

        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group ">
                <label>Truck No:</label>
                <input type="text" name="truck_no" value="{{$ArrivalTicket->truck_no}}" placeholder="Truck No" class="form-control" autocomplete="off" />
            </div>
        </div>
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group ">
                <label>Bilty No: </label>
                <input type="text" name="bilty_no" value="{{$ArrivalTicket->bilty_no}}"  placeholder="Bilty No" class="form-control" autocomplete="off" />
            </div>
        </div>
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group ">
                <label>LOading Date: (Optional)</label>
                <input type="date" name="loading_date" value="{{$ArrivalTicket->loading_date}}"  placeholder="Bilty No" class="form-control" autocomplete="off" />
            </div>
        </div>

        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group ">
                <label>Remarks (Optional):</label>
                <textarea name="remarks" row="2" class="form-control" placeholder="Remarks">{{$ArrivalTicket->remarks}}</textarea>
            </div>
        </div>
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group ">
                <label>Status:</label>
                <select name="status" class="form-control">
                    <option {{$ArrivalTicket->status == 'active' ? 'selected' : ''}} value="active">Active</option>
                    <option {{$ArrivalTicket->status == 'inactive' ? 'selected' : ''}}  value="inactive">Inactive</option>
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
        //  function initializeDynamicSelect2(selector, tableName, columnName, idColumn = 'id', enableTags = false, isMultiple = true) {

    });
</script>