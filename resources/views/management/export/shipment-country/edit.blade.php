<form action="{{ route('shipment-country.update', $shipmentCountry->id) }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    @method('PUT')
    <input type="hidden" id="listRefresh" value="{{ route('get.shipment-country') }}" />

    <div class="row form-mar">
        <div class="col-md-12">
            <div class="form-group">
                <label>Name</label>
                <input type="text" name="name" value="{{ $shipmentCountry->name }}" placeholder="Name" class="form-control" />
            </div>
        </div>
        <div class="col-md-12 d-none">
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" placeholder="Description" class="form-control" rows="3">{{ $shipmentCountry->description }}</textarea>
            </div>
        </div>
        <div class="col-md-12">
            <div class="form-group">
                <label>Status</label>
                <select name="status" class="form-control">
                    <option value="active" {{ $shipmentCountry->status == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ $shipmentCountry->status == 'inactive' ? 'selected' : '' }}>Inactive</option>
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
