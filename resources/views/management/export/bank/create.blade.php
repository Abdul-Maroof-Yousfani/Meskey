<form action="{{ route('bank.store') }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    <input type="hidden" id="listRefresh" value="{{ route('get.bank') }}" />

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
            <label>Account Title:</label>
            <input type="text" name="account_title" class="form-control" placeholder="Account Title"
                maxlength="255" />
        </div>

        <div class="col-md-12 mt-3">
            <label>Bank Name:</label>
            <input type="text" name="bank_name" class="form-control" placeholder="Bank Name" maxlength="255" />
        </div>

        <div class="col-md-12 mt-3">
            <label>IBAN:</label>
            <input type="text" name="iban" class="form-control" placeholder="IBAN" maxlength="34" />
        </div>

        <div class="col-md-12 mt-3">
            <label>Account Number:</label>
            <input type="text" name="account_no" class="form-control" placeholder="Account Number" maxlength="20" />
        </div>

        <div class="col-md-12 mt-3">
            <label>SWIFT Code:</label>
            <input type="text" name="swift_code" class="form-control" placeholder="SWIFT Code" maxlength="20" />
        </div>

        <div class="col-md-12 mt-3">
            <label>Bank Address:</label>
            <input type="text" name="bank_address" class="form-control" placeholder="Bank Address" maxlength="255" />
        </div>

        <div class="col-md-12 mt-3">
            <label>Description:</label>
            <textarea name="description" class="form-control" rows="3" placeholder="Description" maxlength="1000"></textarea>
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
