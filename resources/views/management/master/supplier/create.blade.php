<form action="{{ route('supplier.store') }}" method="POST" id="ajaxSubmit" autocomplete="off"
    enctype="multipart/form-data">
    @csrf
    <input type="hidden" id="listRefresh" value="{{ route('get.supplier') }}" />
    <div class="row form-mar mb-2">
        <div class="col-12">
            <h6 class="header-heading-sepration">
                Gate Buying Supplier
            </h6>
        </div>
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="checkbox">
                <input name="is_gate_buying_supplier" type="checkbox" id="is_gate_buying_supplier" value="Yes">
                <label for="is_gate_buying_supplier"><span>Gate buying supplier</span></label>
            </div>
        </div>
    </div>
    <div class="row ">

        <div class="col-12">
            <h6 class="header-heading-sepration">
                Company Detail
            </h6>
        </div>
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Supplier Type</label>
                <select class="form-control" name="type">
                    <option value="">Select Supplier Type</option>
                    <option value="raw_material" selected>Raw Material Supplier</option>
                    <option value="store_supplier">Store Supplier</option>
                </select>
            </div>
        </div>
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Company Name:</label>
                <input type="text" name="company_name" placeholder="Company Name" class="form-control"
                    autocomplete="off" />
            </div>
        </div>
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group">
                <label>NTN#: <small>(Optional)</small></label>
                <input type="text" name="ntn" placeholder="NTN No" class="form-control" autocomplete="off" />
            </div>
        </div>
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group">
                <label>STN#: <small>(Optional)</small></label>
                <input type="text" name="stn" placeholder="STN No" class="form-control" />
            </div>
        </div>
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div id="card-container" class="mb-4">
                <div class="clonecard border-1">
                    <hr>
                    <div class="row ">
                        <div class="col-xs-6 col-sm-6 col-md-6">
                            <div class="form-group">
                                <label>Bank Name:</label>
                                <input type="text" name="company_bank_name[]" placeholder="Bank Name"
                                    class="form-control" autocomplete="off" />
                            </div>
                        </div>
                        <div class="col-xs-6 col-sm-6 col-md-6">
                            <div class="form-group">
                                <label>Branch Name:</label>
                                <input type="text" name="company_branch_name[]" placeholder="Branch Name"
                                    class="form-control" autocomplete="off" />
                            </div>
                        </div>
                        <div class="col-xs-6 col-sm-6 col-md-6">
                            <div class="form-group">
                                <label>Branch Code:</label>
                                <input type="text" name="company_branch_code[]" placeholder="Branch Code"
                                    class="form-control" autocomplete="off" />
                            </div>
                        </div>
                        <div class="col-xs-6 col-sm-6 col-md-6">
                            <div class="form-group">
                                <label>Account Title:</label>
                                <input type="text" name="company_account_title[]" placeholder="Account Title"
                                    class="form-control" autocomplete="off" />
                            </div>
                        </div>
                        <div class="col-xs-12 col-sm-12 col-md-12">
                            <div class="form-group">
                                <label>Account Number:</label>
                                <input type="text" name="company_account_number[]" placeholder="Account number"
                                    class="form-control" autocomplete="off" />
                            </div>
                        </div>
                        <div class="col-xs-2 col-sm-2 col-md-2 d-flex align-items-end">
                            <div>
                                <button type="button" class="btn btn-warning btn-icon add-more mr-1"><i
                                        class="fa fa-plus"></i></button>
                                <button type="button" class="btn btn-danger btn-icon remove-card mr-1"><i
                                        class="fa fa-trash"></i></button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Link Existing Account:</label>
                <select name="account_id" class="form-control select2">
                    <option value="">-- Create New Account --</option>
                    @foreach ($accounts as $account)
                        <option value="{{ $account->id }}">{{ $account->name }} ({{ $account->unique_no }})</option>
                    @endforeach
                </select>
                <small class="text-muted">Select an existing account or leave blank to create a new one</small>
            </div>
        </div>
    </div>

    <div class="row ">
        <div class="col-12">
            <h6 class="header-heading-sepration">
                Owner Detail
            </h6>
        </div>
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Owner Name:</label>
                <input type="text" name="owner_name" placeholder="Owner Name" class="form-control" autocomplete="off" />
            </div>
        </div>

        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group">
                <label>Owner Mobile No:</label>
                <input type="text" name="owner_mobile_no" placeholder="03001234567" class="form-control"
                    autocomplete="off" maxlength="11" />
                <small class="text-muted">Enter 11 digit mobile number</small>

            </div>
        </div>
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group">
                <label>Owner CNIC:</label>
                <input type="text" name="owner_cnic_no" placeholder="12345-1234567-1" class="form-control cnic-input"
                    autocomplete="off" maxlength="15" />
                <small class="text-muted">Format: 12345-1234567-1</small>
            </div>
        </div>

        <div class="col-xs-12 col-sm-12 col-md-12">
            <div id="card-container2" class="mb-4">
                <div class="clonecard2 border-1">
                    <hr>
                    <div class="row ">
                        <div class="col-xs-6 col-sm-6 col-md-6">
                            <div class="form-group">
                                <label>Bank Name:</label>
                                <input type="text" name="owner_bank_name[]" placeholder="Bank Name" class="form-control"
                                    autocomplete="off" />
                            </div>
                        </div>
                        <div class="col-xs-6 col-sm-6 col-md-6">
                            <div class="form-group">
                                <label>Branch Name:</label>
                                <input type="text" name="owner_branch_name[]" placeholder="Branch Name"
                                    class="form-control" autocomplete="off" />
                            </div>
                        </div>
                        <div class="col-xs-6 col-sm-6 col-md-6">
                            <div class="form-group">
                                <label>Branch Code:</label>
                                <input type="text" name="owner_branch_code[]" placeholder="Branch Code"
                                    class="form-control" autocomplete="off" />
                            </div>
                        </div>
                        <div class="col-xs-6 col-sm-6 col-md-6">
                            <div class="form-group">
                                <label>Account Title:</label>
                                <input type="text" name="owner_account_title[]" placeholder="Account Title"
                                    class="form-control" autocomplete="off" />
                            </div>
                        </div>
                        <div class="col-xs-12 col-sm-12 col-md-12">
                            <div class="form-group">
                                <label>Account Number:</label>
                                <input type="text" name="owner_account_number[]" placeholder="Account number"
                                    class="form-control" autocomplete="off" />
                            </div>
                        </div>
                        <div class="col-xs-2 col-sm-2 col-md-2 d-flex align-items-end">
                            <div>
                                <button type="button" class="btn btn-warning btn-icon add-more2 mr-1"><i
                                        class="fa fa-plus"></i></button>
                                <button type="button" class="btn btn-danger btn-icon remove-card2 mr-1"><i
                                        class="fa fa-trash"></i></button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row ">
        <div class="col-12">
            <h6 class="header-heading-sepration">
                Next Of Kin
            </h6>
        </div>
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group">
                <label>Name:</label>
                <input type="text" name="next_to_kin" placeholder="Name" class="form-control" autocomplete="off" />
            </div>
        </div>
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group">
                <label>Mobile No:</label>
                <input type="text" name="next_to_kin_mobile_no" placeholder="03001234567" class="form-control"
                    autocomplete="off" maxlength="11" pattern="[0-9]{11}" />
                <small class="text-muted">Enter 11 digit mobile number</small>
            </div>
        </div>
    </div>
    <div class="row form-mar">
        <div class="col-12">
            <h6 class="header-heading-sepration">
                Locations
            </h6>
        </div>
        <div class="col-xs-12 col-sm-12 col-md-12">
            @foreach ($companyLocation as $companyLoc)
                <div class="checkbox">
                    <input name="company_location_ids[]" type="checkbox" id="checkbox{{ $companyLoc->id }}"
                        value="{{ $companyLoc->id }}">
                    <label for="checkbox{{ $companyLoc->id }}"><span>{{ $companyLoc->name }}</span></label>
                </div>
            @endforeach
        </div>
    </div>
    <div class="row form-mar mb-2">
        <div class="col-12">
            <h6 class="header-heading-sepration">
                Broker Option
            </h6>
        </div>
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="checkbox">
                <input name="create_as_broker" type="checkbox" id="create_as_broker" value="1">
                <label for="create_as_broker"><span>Create this supplier as a broker too</span></label>
            </div>
        </div>
    </div>

    <div class="row ">
        <div class="col-12">
            <h6 class="header-heading-sepration">
                Other
            </h6>
        </div>
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group ">
                <label>Status:</label>
                <select name="status" class="form-control">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
        </div>
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Address:</label>
                <textarea name="address" rows="2" class="form-control" placeholder="Supplier Address"></textarea>
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
    function toggleRemoveButton() {
        if ($('#card-container .clonecard').length === 1) {
            $('#card-container .clonecard .remove-card').hide();
        } else {
            $('#card-container .clonecard .remove-card').show();
        }
    }

    $(document).on('input', '.cnic-input', function () {
        let value = $(this).val().replace(/\D/g, '');
        let formattedValue = '';

        if (value.length > 0) {
            formattedValue = value.substring(0, 5);
        }
        if (value.length > 5) {
            formattedValue += '-' + value.substring(5, 12);
        }
        if (value.length > 12) {
            formattedValue += '-' + value.substring(12, 13);
        }

        $(this).val(formattedValue);
    });

    toggleRemoveButton();

    $('body').on('click', '.add-more', function () {
        var newCard = $('#card-container .clonecard:first').clone();
        newCard.find('input').val('');
        $('#card-container').append(newCard);
        toggleRemoveButton();
    });

    $(document).on('click', '.remove-card', function () {
        if ($('#card-container .clonecard').length > 1) {
            $(this).closest('.clonecard').remove();
            toggleRemoveButton();
        }
    });

    // Owner Bank Details
    function toggleRemoveButton2() {
        if ($('#card-container2 .clonecard2').length === 1) {
            $('#card-container2 .clonecard2 .remove-card2').hide();
        } else {
            $('#card-container2 .clonecard2 .remove-card2').show();
        }
    }

    toggleRemoveButton2();

    $('body').on('click', '.add-more2', function () {
        var newCard = $('#card-container2 .clonecard2:first').clone();
        newCard.find('input').val('');
        $('#card-container2').append(newCard);
        toggleRemoveButton2();
    });

    $(document).on('click', '.remove-card2', function () {
        if ($('#card-container2 .clonecard2').length > 1) {
            $(this).closest('.clonecard2').remove();
            toggleRemoveButton2();
        }
    });
</script>