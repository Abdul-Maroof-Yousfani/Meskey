<form action="{{ route('account.store') }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    <input type="hidden" id="listRefresh" value="{{ route('get.account') }}" />
    <div class="row form-mar">

        <?php
        $datePrefix = 'ACC-';
        $unique_no = generateUniqueNumber('accounts', $datePrefix, null, 'unique_no');
        ?>

        <div class="col-xs-6 col-sm-6 col-md-6">
            <fieldset>
                <div class="input-group">
                    <div class="input-group-prepend">
                        <button class="btn btn-primary" type="button">Account Code#</button>
                    </div>
                    <input type="text" name="unique_no" readonly class="form-control" value="{{ $unique_no }}"
                        placeholder="Account Code">
                </div>
            </fieldset>
        </div>

        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label for="name">Account Name:</label>
                <input type="text" name="name" class="form-control" placeholder="Enter Account Name">
            </div>
        </div>

        <div class="col-xs-6 col-sm-6 col-md-4">
            <div class="form-group">
                <label>Account Type:</label>
                <select name="account_type" class="form-control select2">
                    <option value="">Select Account Type</option>
                    <option value="debit">Debit</option>
                    <option value="credit">Credit</option>
                </select>
            </div>
        </div>

        <div class="col-xs-6 col-sm-6 col-md-4">
            <div class="form-group">
                <label>Parent Account:</label>
                <select name="parent_id" class="form-control select2">
                    <option value="">Select Parent Account</option>
                    @foreach ($parentAccounts as $account)
                        <option value="{{ $account->id }}">{{ $account->name }} ({{ $account->unique_no }})</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="col-xs-6 col-sm-6 col-md-4">
            <div class="form-group">
                <label>Operational:</label>
                <select name="is_operational" class="form-control select2">
                    <option value="yes">Yes</option>
                    <option value="no">No</option>
                </select>
            </div>
        </div>

        <div class="col-xs-6 col-sm-6 col-md-6 d-none">
            <div class="form-group">
                <label>Status:</label>
                <select name="status" class="form-control select2">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
        </div>

        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Description:</label>
                <textarea name="description" rows="3" class="form-control" placeholder="Account description"></textarea>
            </div>
        </div>
    </div>
    <div class="row bottom-button-bar">
        <div class="col-12">
            <a type="button" class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton">Close</a>
            <button type="submit" class="btn btn-primary submitbutton">Save</button>
        </div>
    </div>
</form>

<script>
    $('.select2').select2();
</script>
