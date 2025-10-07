<form action="{{ route('company-location.update', $company_location->id) }}" method="POST" id="ajaxSubmit"
    autocomplete="off">
    @csrf
    @method('PUT')
    <input type="hidden" id="listRefresh" value="{{ route('get.company-location') }}" />
    <div class="row form-mar">
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group">
                <label>Name:</label>
                <input type="text" name="name" value="{{ $company_location->name }}" placeholder="Name"
                    class="form-control" />
            </div>
        </div>
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group">
                <label>Code:</label>
                <input type="text" name="code" value="{{ $company_location->code }}" placeholder="Name"
                    class="form-control" />
            </div>
        </div>
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group ">
                <label>Status:</label>
                <select name="status" class="form-control">
                    <option {{ $company_location->status == 'active' ? 'selected' : '' }} value="active">Active</option>
                    <option {{ $company_location->status == 'inactive' ? 'selected' : '' }} value="inactive">Inactive
                    </option>
                </select>
            </div>
        </div>
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group">
                <label for="city_id">City:</label>
                <select class="form-control select2" name="city_id" id="city_id">
                    <option value="">-- Select City --</option>
                    @foreach ($cities as $city)
                        <option value="{{ $city->id }}" {{ $company_location->city_id == $city->id ? 'selected' : '' }}>
                            {{ $city->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Description:</label>
                <textarea name="description" placeholder="Description"
                    class="form-control">{{ $company_location->description }}</textarea>
            </div>
        </div>
        <div class="col-12" bis_skin_checked="1">
            <h6 class="header-heading-sepration">
                Settings
            </h6>
        </div>
        <div class="col-6">
            <div class="form-group">
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" @checked($company_location->truck_no_format)
                        id="truckFormatCheck" name="truck_no_format">
                    <label class="custom-control-label" for="truckFormatCheck">Enable Truck Number Format</label>
                </div>
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
        $('.select2').select2();
    });
</script>