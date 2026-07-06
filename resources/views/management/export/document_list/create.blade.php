<form action="{{ route('document-list.store') }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    <input type="hidden" id="listRefresh" value="{{ route('get.document-list') }}" />

    <div class="row">

        <div class="col-xs-12 col-sm-12 col-md-12 mt-3">
            <div class="form-group">
                <label class="form-label">Name:</label>
                <input type="text" name="name" placeholder="Name" class="form-control" />
            </div>
        </div>

        {{-- <div class="col-xs-12 col-sm-12 col-md-12 mt-2">
            <div class="form-group">
                <label class="form-label">Feature:</label>
                <input type="text" name="feature" placeholder="Feature" class="form-control" />
            </div>
        </div> --}}

        <div class="col-xs-12 col-sm-12 col-md-12 mt-2">
            <div class="form-group">
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" id="is_required" name="is_required" value="1">
                    <label class="custom-control-label" for="is_required">Is Required</label>
                </div>
            </div>
        </div>

        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Status:</label>
                <select class="form-control select2" name="status">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
        </div>

        <div class="bottom-button-bar mt-2">
            <div class="col-12">
                <a type="button"
                    class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton">Close</a>
                <button type="submit" class="btn btn-primary submitbutton">Save</button>
            </div>
        </div>
    </div>
</form>

<script>
    $(document).ready(function() {
        $('.select2').select2();
    });
</script>
