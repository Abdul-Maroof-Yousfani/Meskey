<div class="row">

    <div class="col-md-12">
        <label>Company:</label>
        <select class="form-control select2" disabled>
            @foreach ($companies as $company)
                <option value="{{ $company->id }}" {{ $bank->company_id == $company->id ? 'selected' : '' }}>
                    {{ $company->name }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-md-12 mt-3">
        <label>Account Title:</label>
        <input type="text" class="form-control" value="{{ $bank->account_title }}" disabled>
    </div>

    <div class="col-md-12 mt-3">
        <label>Bank Name:</label>
        <input type="text" class="form-control" value="{{ $bank->bank_name }}" disabled>
    </div>

    <div class="col-md-12 mt-3">
        <label>IBAN:</label>
        <input type="text" class="form-control" value="{{ $bank->iban }}" disabled>
    </div>

    <div class="col-md-12 mt-3">
        <label>Account Number:</label>
        <input type="text" class="form-control" value="{{ $bank->account_no }}" disabled>
    </div>

    <div class="col-md-12 mt-3">
        <label>SWIFT Code:</label>
        <input type="text" class="form-control" value="{{ $bank->swift_code }}" disabled>
    </div>

    <div class="col-md-12 mt-3">
        <label>Bank Address:</label>
        <input type="text" class="form-control" value="{{ $bank->bank_address }}" disabled>
    </div>

    <div class="col-md-12 mt-3">
        <label>Description:</label>
        <textarea class="form-control" rows="3" disabled>{{ $bank->description }}</textarea>
    </div>

    <div class="col-md-12 mt-3">
        <label>Status:</label>
        <select class="form-control select2" disabled>
            <option value="active" {{ $bank->status == 'active' ? 'selected' : '' }}>Active</option>
            <option value="inactive" {{ $bank->status == 'inactive' ? 'selected' : '' }}>Inactive</option>
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
