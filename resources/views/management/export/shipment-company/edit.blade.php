<form action="{{ route('shipment-company.update', $shipmentCompany->id) }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    @method('PUT')
    <input type="hidden" id="listRefresh" value="{{ route('get.shipment-company') }}" />

    <div class="row form-mar">
        <div class="col-md-12">
            <div class="form-group">
                <label>Name</label>
                <input type="text" name="name" value="{{ $shipmentCompany->name }}" placeholder="Name" class="form-control" />
            </div>
        </div>
        <div class="col-md-12">
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" placeholder="Description" class="form-control" rows="3">{{ $shipmentCompany->description }}</textarea>
            </div>
        </div>
        <div class="col-md-12">
            <div class="form-group">
                <label>Status</label>
                <select name="status" class="form-control">
                    <option value="active" {{ $shipmentCompany->status == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ $shipmentCompany->status == 'inactive' ? 'selected' : '' }}>Inactive</option>
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
