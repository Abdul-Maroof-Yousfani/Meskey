<form action="{{ route('supplier.update', $supplier->id) }}" method="POST" id="ajaxSubmit" autocomplete="off"
    enctype="multipart/form-data">
    @csrf
    @method('PUT')
    <input type="hidden" id="listRefresh" value="{{ route('get.supplier') }}" />
    <div class="row form-mar">
     <div class="col-12">
            <h6 class="header-heading-sepration">
                Basic Detail
            </h6>
        </div>
        <!-- Name Field -->
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Name:</label>
                <input type="text" name="name" placeholder="Supplier Name" value="{{$supplier->name}}" class="form-control" autocomplete="off"
                     />
            </div>
        </div>

        <!-- Email Field -->
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group">
                <label>Email: <small>(Optional)</small></label>
                <input type="email" name="email" placeholder="Supplier Email"  value="{{$supplier->email}}" class="form-control" autocomplete="off" />
            </div>
        </div>

        <!-- Phone Field -->
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group">
                <label>Phone: <small>(Optional)</small></label>
                <input type="text" name="phone" placeholder="Phone Number"  value="{{$supplier->phone}}" class="form-control" autocomplete="off" />
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
                <label>Company Name:</label>
                <input type="text" name="company_name" placeholder="Company Name"  value="{{$supplier->company_name}}" class="form-control" autocomplete="off" />
            </div>
        </div>
        <!-- NTN Field -->
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group">
                <label>NTN#: <small>(Optional)</small></label>
                <input type="text" name="ntn" placeholder="NTN No" class="form-control"  value="{{$supplier->ntn}}" autocomplete="off" />
            </div>
        </div>
        <!-- STN Field -->
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group">
                <label>STN#: <small>(Optional)</small></label>
                <input type="text" name="stn" placeholder="STN No"  value="{{$supplier->stn}}" class="form-control" />
            </div>
        </div>
        <!-- Address Field -->
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Bank Detail:</label>
                <textarea name="company_bank_detail" rows="2" class="form-control"  placeholder="Bank Detail">{{$supplier->company_bank_detail}}</textarea>
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
                <input type="text" name="owner_name" placeholder="Owner Name" value="{{$supplier->owner_name}}" class="form-control" autocomplete="off" />
            </div>
        </div>
        
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group">
                <label>Owner Mobile No:</label>
                <input type="text" name="owner_mobile_no" placeholder="Owner Mobile No" value="{{$supplier->owner_mobile_no}}" class="form-control" autocomplete="off" />
            </div>
        </div>
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group">
                <label>Owner CNIC:</label>
                <input type="text" name="owner_cnic_no" placeholder="Owner CNIC" value="{{$supplier->owner_cnic_no}}" class="form-control" autocomplete="off" />
            </div>
        </div>
      
        <!-- Address Field -->
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Bank Detail:</label>
                <textarea name="owner_bank_detail" rows="2" class="form-control" placeholder="Bank Detail">{{$supplier->owner_bank_detail}}</textarea>
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
                <input type="text" name="next_to_kin" placeholder="Name" value="{{$supplier->next_to_kin}}"  class="form-control" autocomplete="off" />
            </div>
        </div>
        
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group">
                <label>Mobile No:</label>
                <input type="text" name="next_to_kin_mobile_no" placeholder="Mobile No" value="{{$supplier->next_to_kin_mobile_no}}"  class="form-control" autocomplete="off" />
            </div>
        </div>
    </div>


    <div class="row ">



 <div class="col-12">
            <h6 class="header-heading-sepration">
                Other
            </h6>
        </div>

        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group ">
                <label>Status:</label>
                <select name="status" class="form-control">
                    <option {{$supplier->status == 'active' ? 'selected' : ''}} value="active">Active</option>
                    <option {{$supplier->status == 'inactive' ? 'selected' : ''}}  value="inactive">Inactive</option>
                </select>
            </div>
        </div>
        <!-- Address Field -->
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Address:</label>
                <textarea name="address" rows="2" class="form-control" placeholder="Supplier Address"
                >{{$supplier->address}}</textarea>
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