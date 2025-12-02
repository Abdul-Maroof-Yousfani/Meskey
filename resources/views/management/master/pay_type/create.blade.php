<form action="{{ route('pay-type.store') }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    <input type="hidden" id="listRefresh" value="{{ route('get.pay-type') }}" />

    <div class="row form-mar">
        
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group ">
                <label>Name:<span class="text-danger">*</span></label>
                <input type="text" name="name" placeholder="Pay Type Name" class="form-control" autocomplete="off" />
            </div>
        </div>

        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group ">
                <label>Description:</label>
                <textarea name="description" placeholder="Description" class="form-control" rows="3"></textarea>
            </div>
        </div>

        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group ">
                <label>Status:<span class="text-danger">*</span></label>
                <select name="status" class="form-control">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
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

