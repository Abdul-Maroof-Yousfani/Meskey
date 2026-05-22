<div class="row form-mar">
    <div class="col-md-12">
        <div class="form-group">
            <label>Name</label>
            <input type="text" value="{{ $gafta->name }}" class="form-control" readonly />
        </div>
    </div>
    <div class="col-md-12 d-none">
        <div class="form-group">
            <label>Description</label>
            <textarea class="form-control" rows="3" readonly>{{ $gafta->description }}</textarea>
        </div>
    </div>
    <div class="col-md-12">
        <div class="form-group">
            <label>Status</label>
            <input type="text" value="{{ ucfirst($gafta->status) }}" class="form-control" readonly />
        </div>
    </div>
</div>

<div class="row bottom-button-bar">
    <div class="col-12 text-right">
        <a type="button" class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton">Close</a>
    </div>
</div>
