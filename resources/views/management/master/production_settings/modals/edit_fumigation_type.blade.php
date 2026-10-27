<form action="{{ route('production-settings.fumigation-types.update', $index) }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    @method('PUT')
    <input type="hidden" id="listRefresh" value="{{ route('production-settings.fumigation-types.list') }}" />
    <div class="row form-mar">
        <div class="col-12">
            <div class="form-group">
                <label class="font-weight-bold">Fumigation Type Name: <span class="text-danger">*</span></label>
                <input type="text" name="name" value="{{ $type }}" placeholder="e.g. phosphorate, methale" class="form-control" autocomplete="off" required />
                <small class="form-text text-muted">Update the fumigation type name.</small>
            </div>
        </div>
    </div>
    <div class="row bottom-button-bar">
        <div class="col-12 text-right">
            <a type="button" class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton mr-1">Close</a>
            <button type="submit" class="btn btn-primary submitbutton">Update</button>
        </div>
    </div>
</form>
