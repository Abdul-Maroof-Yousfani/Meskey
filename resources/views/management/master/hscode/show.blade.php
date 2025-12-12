<div class="row">

    <div class="col-md-12">
        <label>Company:</label>
        <select class="form-control select2" disabled>
            @foreach ($companies as $company)
                <option value="{{ $company->id }}" {{ $hs->company_id == $company->id ? 'selected' : '' }}>
                    {{ $company->name }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-md-12 mt-3">
        <label>HS Code:</label>
        <input type="text" class="form-control" value="{{ $hs->code }}" disabled>
    </div>

    <div class="col-md-12 mt-3">
        <label>Description:</label>
        <textarea class="form-control" rows="3" disabled>{{ $hs->description }}</textarea>
    </div>

    <div class="col-md-12 mt-3">
        <label>Custom Duty (Amount):</label>
        <input type="text" class="form-control" value="{{ $hs->custom_duty }}" disabled>
    </div>

    <div class="col-md-12 mt-3">
        <label>Excise Duty (%):</label>
        <input type="text" class="form-control" value="{{ $hs->excise_duty }}" disabled>
    </div>

    <div class="col-md-12 mt-3">
        <label>Sales Tax (%):</label>
        <input type="text" class="form-control" value="{{ $hs->sales_tax }}" disabled>
    </div>

    <div class="col-md-12 mt-3">
        <label>Income Tax (%):</label>
        <input type="text" class="form-control" value="{{ $hs->income_tax }}" disabled>
    </div>

    <div class="col-md-12 mt-3">
        <label>Status:</label>
        <select class="form-control select2" disabled>
            <option value="active" {{ $hs->status == 'active' ? 'selected' : '' }}>Active</option>
            <option value="inactive" {{ $hs->status == 'inactive' ? 'selected' : '' }}>Inactive</option>
        </select>
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
