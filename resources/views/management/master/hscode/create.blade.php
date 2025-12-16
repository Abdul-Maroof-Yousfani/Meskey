<form action="{{ route('hs-code.store') }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    <input type="hidden" id="listRefresh" value="{{ route('get.hscode') }}" />

    <div class="row">

        <div class="col-md-12">
            <label>Company:</label>
            <select name="company" class="form-control select2">
                @foreach ($companies as $company)
                    <option value="{{ $company->id }}">{{ $company->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-12 mt-3">
            <label>HS Code:</label>
            <input type="text" name="code" class="form-control" placeholder="e.g. 85177090" maxlength="20" />
        </div>

        <div class="col-md-12 mt-3">
            <label>Description:</label>
            <textarea name="description" class="form-control" rows="3" maxlength="1000" placeholder="Description"></textarea>
        </div>

        <div class="col-md-12 mt-3">
            <label>Custom Duty (Amount):</label>
            <input type="number" step="0.01" min="0" name="custom_duty" class="form-control" placeholder="Amount" />
        </div>

        <div class="col-md-12 mt-3">
            <label>Excise Duty (%):</label>
            <input type="number" step="0.01" min="0" max="100" name="excise_duty" class="form-control" placeholder="%" />
        </div>

        <div class="col-md-12 mt-3">
            <label>Sales Tax (%):</label>
            <input type="number" step="0.01" min="0" max="100" name="sales_tax" class="form-control" placeholder="%" />
        </div>

        <div class="col-md-12 mt-3">
            <label>Income Tax (%):</label>
            <input type="number" step="0.01" min="0" max="100" name="income_tax" class="form-control" placeholder="%" />
        </div>

        <div class="col-md-12 mt-3">
            <label>Status:</label>
            <select name="status" class="form-control select2">
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
        </div>

        <div class="bottom-button-bar mt-3">
            <div class="col-12">
                <a type="button" class="btn btn-danger modal-sidebar-close closebutton">Close</a>
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
