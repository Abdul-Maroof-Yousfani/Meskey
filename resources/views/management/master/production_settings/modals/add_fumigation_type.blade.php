<form action="{{ route('production-settings.fumigation-types.store') }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    <input type="hidden" id="listRefresh" value="{{ route('production-settings.fumigation-types.list') }}" />
    <div class="row form-mar">
        <div class="col-12">
            <div class="form-group">
                <label class="font-weight-bold">Fumigation Type Name: <span class="text-danger">*</span></label>
                <input type="text" name="name" placeholder="e.g. phosphorate, methale" class="form-control" autocomplete="off" required />
                <small class="form-text text-muted">This type will be saved into the settings table and available in Job Order under Packing Details.</small>
            </div>
        </div>
    </div>
    <div class="row bottom-button-bar">
        <div class="col-12 text-right">
            <a type="button" class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton mr-1">Close</a>
            <button type="submit" class="btn btn-primary submitbutton">Save</button>
        </div>
    </div>
</form>