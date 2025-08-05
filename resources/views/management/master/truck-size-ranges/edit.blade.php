<form action="{{ route('truck-size-ranges.update', $truckSizeRange->id) }}" method="POST" id="ajaxSubmit"
    autocomplete="off">
    @csrf
    @method('PUT')
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
                    required autocomplete="off" value="{{ $truckSizeRange->min_number }}" />
            </div>
        </div>
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group">
                <label>Maximum Number (kg):</label>
                <input type="number" step="0.01" name="max_number" placeholder="Maximum kg" class="form-control"
                    required autocomplete="off" value="{{ $truckSizeRange->max_number }}" />
            </div>
        </div>
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Status:</label>
                <select name="status" class="form-control" required>
                    <option value="active" {{ $truckSizeRange->status == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ $truckSizeRange->status == 'inactive' ? 'selected' : '' }}>Inactive
                    </option>
                </select>
            </div>
        </div>
    </div>

    <div class="row bottom-button-bar">
        <div class="col-12">
            <a type="button" class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton">Close</a>
            <button type="submit" class="btn btn-primary submitbutton">Update</button>
        </div>
    </div>
</form>
