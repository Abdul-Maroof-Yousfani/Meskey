<form action="{{ route('permission.store') }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    <input type="hidden" id="listRefresh" value="{{ route('get.permissions') }}" />

    <div class="row form-mar">
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Permission Name: <span class="text-danger">*</span></label>
                <input type="text" name="name" placeholder="e.g. arrival-ticket, order-create, reports-summary" class="form-control" required />
                <small class="text-muted">Use lowercase kebab-case (e.g. <code>module-action</code>).</small>
            </div>
        </div>

        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Parent Module / Permission:</label>
                <select class="form-control select2" name="parent_id" id="modal_parent_id">
                    <option value="">-- None (Root Level Permission) --</option>
                    @foreach ($parents as $parentId => $parentLabel)
                        <option value="{{ $parentId }}">{{ $parentLabel }}</option>
                    @endforeach
                </select>
                <small class="text-muted">Leave empty if this is a top-level module (like Master, Sales, Reports).</small>
            </div>
        </div>

        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Guard Name:</label>
                <input type="text" name="guard_name" value="web" placeholder="web" class="form-control" />
                <small class="text-muted">Default is <code>web</code>.</small>
            </div>
        </div>

        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Description (Optional):</label>
                <textarea name="description" placeholder="Brief explanation of what this permission allows..." class="form-control" rows="3"></textarea>
            </div>
        </div>
    </div>

    <div class="row bottom-button-bar">
        <div class="col-12">
            <a type="button" class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton">Close</a>
            <button type="submit" class="btn btn-primary submitbutton">Save Permission</button>
        </div>
    </div>
</form>

<script>
    $(document).ready(function() {
        if (typeof $.fn.select2 !== 'undefined') {
            $('#modal_parent_id').select2({
                dropdownAutoWidth: true,
                width: '100%'
            });
        }
    });
</script>
