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
        <div class="col-6 truck-format-section">
            <div class="form-group">
                <label>Truck Number Format:</label>
                <select class="form-control select2" name="truck_no_format" id="truckFormat">
                    <option value="">N/A</option>
                    <option value="ABC-1234" {{ $company_location->truck_no_format == 'ABC-1234' ? 'selected' : '' }}>
                        ABC-1234</option>
                    <option value="1234-ABC" {{ $company_location->truck_no_format == '1234-ABC' ? 'selected' : '' }}>
                        1234-ABC</option>
                    <option value="AB-1234" {{ $company_location->truck_no_format == 'AB-1234' ? 'selected' : '' }}>
                        AB-1234</option>
                    <option value="1234-AB" {{ $company_location->truck_no_format == '1234-AB' ? 'selected' : '' }}>
                        1234-AB</option>
                </select>
            </div>
        </div>
        <div class="col-6">
            <div class="form-group ">
                <label>Status:</label>
                <select name="status" class="form-control">
                    <option {{ $company_location->status == 'active' ? 'selected' : '' }} value="active">Active</option>
                    <option {{ $company_location->status == 'inactive' ? 'selected' : '' }} value="inactive">Inactive
                    </option>
                </select>
            </div>
        </div>
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Description:</label>
                <textarea name="description" placeholder="Description" class="form-control">{{ $company_location->description }}</textarea>
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
    $(document).ready(function() {
        $('.select2').select2();
    });
</script>
