<div class="row">

    <div class="col-md-12">
        <label>Company:</label>
        <input type="text" class="form-control" value="{{ $port->company->name }}" disabled />
    </div>

    <div class="col-md-12 mt-3">
        <label>Port Name:</label>
        <input type="text" class="form-control" value="{{ $port->name }}" disabled />
    </div>

    <div class="col-md-12 mt-3">
        <label>Description:</label>
        <textarea class="form-control" rows="3" disabled>{{ $port->description }}</textarea>
    </div>

    <div class="col-md-12 mt-3">
        <label>Country:</label>
        <input type="text" class="form-control" value="{{ $port->country->name }}" disabled />
    </div>

    <div class="col-md-12 mt-3">
        <label>City:</label>
        <input type="text" class="form-control" value="{{ $port->city->name }}" disabled />
    </div>

    <div class="col-md-12 mt-3">
        <label>Type:</label>
        <input type="text" class="form-control" value="{{ $port->type }}" disabled />
    </div>

    <div class="col-md-12 mt-3">
        <label>Status:</label>
        <input type="text" class="form-control" value="{{ ucfirst($port->status) }}" disabled />
    </div>

    <div class="text-center mt-3">
        <div class="col-12">
            <button type="button" class="btn btn-danger modal-sidebar-close">Close</button>
        </div>
    </div>

</div>
