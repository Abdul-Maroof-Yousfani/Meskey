<form action="{{ route('division.store') }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    <input type="hidden" id="listRefresh" value="{{ route('get.division') }}" />

    <div class="row">
        <div class="col-12">
            <h6 class="header-heading-sepration">
                Division Information
            </h6>
        </div>
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Division Name:</label>
                <input type="text" name="name" placeholder="Division Name" class="form-control" required
                    autocomplete="off" />
            </div>
        </div>
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Hours:</label>
                <input type="number" name="hours" placeholder="Working Hours" class="form-control"
                    autocomplete="off" />
                <small class="text-muted">Optional: Enter working hours for this division</small>
            </div>
        </div>
        <div class="col-xs-12 col-sm-12 col-md-12 d-none">
            <div class="form-group">
                <label>Status:</label>
                <select name="status" class="form-control" required>
                    <option value="active" selected>Active</option>
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
