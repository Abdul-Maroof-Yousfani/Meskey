<div class="row">

    <div class="col-md-12">
        <label>Company:</label>
        <select class="form-control select2" disabled>
            @foreach ($companies as $company)
                <option value="{{ $company->id }}" {{ $currency->company_id == $company->id ? 'selected' : '' }}>
                    {{ $company->name }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-xs-12 col-sm-12 col-md-12 mt-3">
        <div class="form-group">
            <label class="form-label">Currency Name:</label>
            <input type="text" class="form-control" value="{{ $currency->currency_name }}" disabled>
        </div>
    </div>

    <div class="col-xs-12 col-sm-12 col-md-12 mt-3">
        <div class="form-group">
            <label class="form-label">Currency Code:</label>
            <input type="text" class="form-control" value="{{ $currency->currency_code }}" disabled>
        </div>
    </div>

    <div class="col-xs-12 col-sm-12 col-md-12 mt-3">
        <div class="form-group">
            <label class="form-label">Rate:</label>
            <input type="text" class="form-control" value="{{ $currency->rate }}" disabled>
        </div>
    </div>

    <div class="col-xs-12 col-sm-12 col-md-12 mt-2">
        <div class="form-group">
            <label class="form-label">Description:</label>
            <textarea class="form-control" rows="3" disabled>{{ $currency->description }}</textarea>
        </div>
    </div>

    <div class="col-xs-12 col-sm-12 col-md-12">
        <div class="form-group">
            <label>Status:</label>
            <select class="form-control select2" disabled>
                <option value="active" {{ $currency->status == 'active' ? 'selected' : '' }}>Active</option>
                <option value="inactive" {{ $currency->status == 'inactive' ? 'selected' : '' }}>Inactive</option>
            </select>
        </div>
    </div>

    <div class="text-center mt-3">
        <div class="col-12">
            <button type="button" class="btn btn-danger modal-sidebar-close">Close</button>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        $('.select2').select2();
    });
</script>
