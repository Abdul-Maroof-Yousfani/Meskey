<form action="{{ route('arrival-location.update', $locationTransfer->id) }}" method="POST" id="ajaxSubmit"
    autocomplete="off">
    @csrf
    @method('PUT')
    <input type="hidden" id="listRefresh" value="{{ route('get.arrival-location') }}" />
    <div class="row form-mar">
        <!-- Company Location Dropdown -->
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Company Location:</label>
                <select name="company_location_id" class="form-control select2" required>
                    <option value="">Select Location</option>
                    @foreach ($companyLocations as $location)
                        <option value="{{ $location->id }}"
                            {{ $locationTransfer->company_location_id == $location->id ? 'selected' : '' }}>
                            {{ $location->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Name:</label>
                <input type="text" name="name" placeholder="Name" class="form-control"
                    value="{{ $locationTransfer->name }}" required />
            </div>
        </div>

        <!-- Description -->
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Description:</label>
                <textarea name="description" placeholder="Description" class="form-control">{{ $locationTransfer->description }}</textarea>
            </div>
        </div>

        <!-- Status -->
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Status:</label>
                <select class="form-control" name="status" required>
                    <option value="active" {{ $locationTransfer->status == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ $locationTransfer->status == 'inactive' ? 'selected' : '' }}>Inactive
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
