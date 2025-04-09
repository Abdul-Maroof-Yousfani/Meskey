<form action="{{ route('arrival-location.update',$arrival_location->id) }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    @method('PUT')
    <input type="hidden" id="listRefresh" value="{{ route('get.arrival-location') }}" />
    <div class="row form-mar">
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Name:</label>
                <input type="text" name="name" value="{{$arrival_location->name}}" placeholder="Name" class="form-control"  />
            </div>
        </div>

        <!-- Description -->
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Description:</label>
                <textarea name="description" placeholder="Description" class="form-control">{{$arrival_location->description}}</textarea>
            </div>
        </div>
        <!-- Status -->
       <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group ">
                <label>Status:</label>
                <select name="status" class="form-control">
                    <option {{$arrival_location->status == 'active' ? 'selected' : ''}} value="active">Active</option>
                    <option {{$arrival_location->status == 'inactive' ? 'selected' : ''}}  value="inactive">Inactive</option>
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