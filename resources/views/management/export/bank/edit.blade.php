<form action="{{ route('bank.update', $bank->id) }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    @method('PUT')
    <input type="hidden" id="listRefresh" value="{{ route('get.bank') }}" />

    <div class="row">

        <div class="col-md-12">
            <label>Company:</label>
            <select name="company" class="form-control select2">
                @foreach ($companies as $company)
                    <option value="{{ $company->id }}" {{ $bank->company_id == $company->id ? 'selected' : '' }}>
                        {{ $company->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-md-12 mt-3">
            <label>Account Title:</label>
            <input type="text" name="account_title" class="form-control" value="{{ $bank->account_title }}"
                maxlength="255" />
        </div>

        <div class="col-md-12 mt-3">
            <label>Bank Name:</label>
            <input type="text" name="bank_name" class="form-control" value="{{ $bank->bank_name }}"
                maxlength="255" />
        </div>

        <div class="col-md-12 mt-3">
            <label>IBAN:</label>
            <input type="text" name="iban" class="form-control" value="{{ $bank->iban }}" maxlength="34" />
        </div>

        <div class="col-md-12 mt-3">
            <label>Account Number:</label>
            <input type="text" name="account_no" class="form-control" value="{{ $bank->account_no }}"
                maxlength="20" />
        </div>

        <div class="col-md-12 mt-3">
            <label>SWIFT Code:</label>
            <input type="text" name="swift_code" class="form-control" value="{{ $bank->swift_code }}"
                maxlength="20" />
        </div>

        <div class="col-md-12 mt-3">
            <label>Bank Address:</label>
            <input type="text" name="bank_address" class="form-control" value="{{ $bank->bank_address }}"
                maxlength="255" />
        </div>

        <div class="col-md-12 mt-3">
            <label>Description:</label>
            <textarea name="description" class="form-control" rows="3" maxlength="1000">{{ $bank->description }}</textarea>
        </div>

        <div class="col-md-12 mt-3">
            <label>Status:</label>
            <select name="status" class="form-control select2">
                <option value="active" {{ $bank->status == 'active' ? 'selected' : '' }}>Active</option>
                <option value="inactive" {{ $bank->status == 'inactive' ? 'selected' : '' }}>Inactive</option>
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
