<form action="{{ route('broker.update', $broker->id) }}" method="POST" id="ajaxSubmit" autocomplete="off"
    enctype="multipart/form-data">
    @csrf
    @method('PUT')
    <input type="hidden" id="listRefresh" value="{{ route('get.broker') }}" />

    <div class="row form-mar">
        <!-- Name Field -->
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group">
                <label>Name:</label>
                <input type="text" name="name" value="{{$broker->name}}" placeholder="Supplier Name" class="form-control" autocomplete="off"
                    required />
            </div>
        </div>

        <!-- Email Field -->
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group">
                <label>Email: <small>(Optional)</small></label>
                <input type="email" name="email" value="{{$broker->email}}"  placeholder="Supplier Email" class="form-control" autocomplete="off" />
            </div>
        </div>

        <!-- Phone Field -->
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group">
                <label>Phone: <small>(Optional)</small></label>
                <input type="text" name="phone" value="{{$broker->phone}}"  placeholder="Phone Number" class="form-control" autocomplete="off" />
            </div>
        </div>



        <!-- NTN Field -->
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group">
                <label>NTN#: <small>(Optional)</small></label>
                <input type="text" name="ntn"  value="{{$broker->ntn}}"  placeholder="NTN No" class="form-control" autocomplete="off" />
            </div>
        </div>

        <!-- STN Field -->
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group">
                <label>STN#: <small>(Optional)</small></label>
                <input type="text" name="stn"  value="{{$broker->stn}}"  placeholder="STN No" class="form-control" />
            </div>
        </div>
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group ">
                <label>Status:</label>
                <select name="status" class="form-control">
                    <option {{$broker->status == 'active' ? 'selected' : ''}} value="active">Active</option>
                    <option {{$broker->status == 'inactive' ? 'selected' : ''}}  value="inactive">Inactive</option>
                </select>
            </div>
        </div>
        <!-- Address Field -->
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Address:</label>
                <textarea name="address" rows="2" class="form-control" placeholder="Supplier Address"
                    >{{$broker->address}}</textarea>
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