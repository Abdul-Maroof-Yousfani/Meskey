<form action="{{ route('document-list.update', $document->id) }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    @method('PUT')
    <input type="hidden" id="listRefresh" value="{{ route('get.document-list') }}" />

    <div class="row">

        <div class="col-xs-12 col-sm-12 col-md-12 mt-3">
            <div class="form-group">
                <label class="form-label">Name:</label>
                <input type="text" name="name" value="{{ $document->name }}" placeholder="Name" class="form-control" />
            </div>
        </div>

        {{-- <div class="col-xs-12 col-sm-12 col-md-12 mt-2">
            <div class="form-group">
                <label class="form-label">Feature:</label>
                <input type="text" name="feature" value="{{ $document->feature }}" placeholder="Feature" class="form-control" />
            </div>
        </div> --}}

        <div class="col-xs-12 col-sm-12 col-md-12 mt-2">
            <div class="form-group">
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" id="is_required_{{ $document->id }}" name="is_required" value="1" {{ $document->is_required ? 'checked' : '' }}>
                    <label class="custom-control-label" for="is_required_{{ $document->id }}">Is Required</label>
                </div>
            </div>
        </div>

        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Status:</label>
                <select class="form-control select2" name="status">
                    <option value="active" {{ $document->status == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ $document->status == 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
        </div>

        <div class="bottom-button-bar mt-2">
            <div class="col-12">
                <a type="button"
                    class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton">Close</a>
                <button type="submit" class="btn btn-primary submitbutton">Update</button>
            </div>
        </div>
    </div>
</form>

<script>
    $(document).ready(function() {
        $('.select2').select2();
    });
</script>
