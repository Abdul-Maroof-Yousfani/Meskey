<form action="{{ route('modeoftransport.store') }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    <input type="hidden" id="listRefresh" value="{{ route('get.transport.modes') }}" />

    <div class="row">

        <div class="col-md-12">
            <label>Company:</label>
            <select name="company" class="form-control select2">
                @foreach ($companies as $company)
                    <option value="{{ $company->id }}">{{ $company->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-xs-12 col-sm-12 col-md-12 mt-3">
            <div class="form-group">
                <label class="form-label">Name:</label>
                <input type="text" name="name" placeholder="Name" class="form-control" />
            </div>
        </div>

        <div class="col-xs-12 col-sm-12 col-md-12 mt-2">
            <div class="form-group">
                <label class="form-label">Description:</label>
                <textarea name="description" placeholder="Description" class="form-control" rows="3"></textarea>
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
