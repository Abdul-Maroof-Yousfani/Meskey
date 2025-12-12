<form action="{{ route('hs-code.update', $hs->id) }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    @method('PUT')

    <input type="hidden" id="listRefresh" value="{{ route('get.hscode') }}" />

    <div class="row">

        <div class="col-md-12">
            <label>Company:</label>
            <select name="company" class="form-control select2">
                @foreach ($companies as $company)
                    <option value="{{ $company->id }}" {{ $company->id == $hs->company_id ? 'selected' : '' }}>
                        {{ $company->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-md-12 mt-3">
            <label>HS Code:</label>
            <input type="text" name="code" class="form-control" value="{{ $hs->code }}" />
        </div>

        <div class="col-md-12 mt-3">
            <label>Description:</label>
            <textarea name="description" class="form-control" rows="3">{{ $hs->description }}</textarea>
        </div>

        <div class="col-md-12 mt-3">
            <label>Custom Duty (Amount):</label>
            <input type="number" step="0.01" min="0" name="custom_duty" class="form-control"
                value="{{ $hs->custom_duty }}" />
        </div>

        <div class="col-md-12 mt-3">
            <label>Excise Duty (%):</label>
            <input type="number" step="0.01" min="0" max="100" name="excise_duty" class="form-control"
                value="{{ $hs->excise_duty }}" />
        </div>

        <div class="col-md-12 mt-3">
            <label>Sales Tax (%):</label>
            <input type="number" step="0.01" min="0" max="100" name="sales_tax" class="form-control"
                value="{{ $hs->sales_tax }}" />
        </div>

        <div class="col-md-12 mt-3">
            <label>Income Tax (%):</label>
            <input type="number" step="0.01" min="0" max="100" name="income_tax" class="form-control"
                value="{{ $hs->income_tax }}" />
        </div>

        <div class="col-md-12 mt-3">
            <label>Status:</label>
            <select name="status" class="form-control select2">
                <option value="active" {{ $hs->status == 'active' ? 'selected' : '' }}>Active</option>
                <option value="inactive" {{ $hs->status == 'inactive' ? 'selected' : '' }}>Inactive</option>
            </select>
        </div>

        <div class="bottom-button-bar mt-3">
            <div class="col-12">
                <a class="btn btn-danger modal-sidebar-close closebutton">Close</a>
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
