<form action="{{ route('currency.update', $currency->id) }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    @method('PUT')

    <input type="hidden" id="listRefresh" value="{{ route('get.currency') }}" />

    <div class="row">

        <div class="col-md-12">
            <label>Company:</label>
            <select name="company" class="form-control select2">
                @foreach ($companies as $company)
                    <option value="{{ $company->id }}" {{ $company->id == $currency->company_id ? 'selected' : '' }}>
                        {{ $company->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-md-12 mt-3">
            <label>Currency Name:</label>
            <input type="text" name="currency_name" class="form-control" value="{{ $currency->currency_name }}" />
        </div>

        <div class="col-md-12 mt-3">
            <label>Currency Code:</label>
            <input type="text" name="currency_code" class="form-control" value="{{ $currency->currency_code }}" />
        </div>

        <div class="col-md-12 mt-3">
            <label>Rate:</label>
            <input type="number" step="0.01" min="0" name="rate" class="form-control"
                value="{{ $currency->rate }}" />
        </div>

        <div class="col-md-12 mt-3">
            <label>Description:</label>
            <textarea name="description" class="form-control" rows="3">{{ $currency->description }}</textarea>
        </div>

        <div class="col-md-12 mt-3">
            <label>Status:</label>
            <select name="status" class="form-control select2">
                <option value="active" {{ $currency->status == 'active' ? 'selected' : '' }}>Active</option>
                <option value="inactive" {{ $currency->status == 'inactive' ? 'selected' : '' }}>Inactive</option>
            </select>
        </div>

        <div class="bottom-button-bar mt-3">
            <div class="col-12">
                <a type="button" class="btn btn-danger modal-sidebar-close closebutton">Close</a>
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
