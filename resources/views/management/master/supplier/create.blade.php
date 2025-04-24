<form action="{{ route('supplier.store') }}" method="POST" id="ajaxSubmit" autocomplete="off" enctype="multipart/form-data">
    @csrf
    <input type="hidden" id="listRefresh" value="{{ route('get.supplier') }}" />

    <div class="row form-mar">
        <div class="col-12">
            <h6 class="header-heading-sepration">
                Basic Detail
            </h6>
        </div>
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Name:</label>
                <input type="text" name="name" placeholder="Supplier Name" class="form-control"
                    autocomplete="off" />
            </div>
        </div>

        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group">
                <label>Email: <small>(Optional)</small></label>
                <input type="email" name="email" placeholder="Supplier Email" class="form-control"
                    autocomplete="off" />
            </div>
        </div>
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group">
                <label>Phone: <small>(Optional)</small></label>
                <input type="text" name="phone" placeholder="Phone Number" class="form-control"
                    autocomplete="off" />
            </div>
        </div>
        {{-- <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group">
                <label>Account Type:</label>
                <select name="account_type" id="account_type">
                    <option value="">Select Account Type</option>
                    <option value="debit">Debit</option>
                    <option value="credit">Credit</option>
                </select>
            </div>
        </div> --}}
    </div>
    <div class="row ">
        <div class="col-12">
            <h6 class="header-heading-sepration">
                Company Detail
            </h6>
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
            <div class="form-group">
                <label>Bank Detail:</label>
                <textarea name="company_bank_detail" rows="2" class="form-control" placeholder="Bank Detail"></textarea>
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
                <input type="text" name="owner_name" placeholder="Owner Name" class="form-control"
                    autocomplete="off" />
            </div>
        </div>

        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group">
                <label>Owner Mobile No:</label>
                <input type="text" name="owner_mobile_no" placeholder="Owner Mobile No" class="form-control"
                    autocomplete="off" />
            </div>
        </div>
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group">
                <label>Owner CNIC:</label>
                <input type="text" name="owner_cnic_no" placeholder="Owner CNIC" class="form-control"
                    autocomplete="off" />
            </div>
        </div>

        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Bank Detail:</label>
                <textarea name="company_bank_detail" rows="2" class="form-control" placeholder="Bank Detail"></textarea>
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
                <input type="text" name="next_to_kin_mobile_no" placeholder="Mobile No" class="form-control"
                    autocomplete="off" />
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
