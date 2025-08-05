<form action="{{ route('truck-size-ranges.store') }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    <input type="hidden" id="listRefresh" value="{{ route('get.truck_size_ranges') }}" />

    <div class="row">
        <div class="col-12">
            <h6 class="header-heading-sepration">
                Truck Size Range Information
            </h6>
        </div>
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group">
                <label>Minimum Number (kg):</label>
                <input type="number" step="0.01" name="min_number" placeholder="Minimum kg" class="form-control"
                    required autocomplete="off" />
            </div>
        </div>
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group">
                <label>Maximum Number (kg):</label>
                <input type="number" step="0.01" name="max_number" placeholder="Maximum kg" class="form-control"
                    required autocomplete="off" />
            </div>
        </div>
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Status:</label>
                <select name="status" class="form-control" required>
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
