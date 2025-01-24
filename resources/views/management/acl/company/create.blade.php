<form action="{{ route('company.store') }}" method="POST" id="ajaxSubmit" autocomplete="off">
  @csrf
  <input type="hidden" id="listRefresh" value="{{ route('get.company') }}" />

<div class="row form-mar">
    <div class="col-md-12 mb-4">
        <div class="avatar-upload">
            <div class="avatar-edit">
                <input type='file' id="imageUpload" name="logo" accept=".png, .jpg, .jpeg" />
                <label for="imageUpload">
                     <i class="ft-camera"></i>
                </label>
            </div>
            <div class="avatar-preview">
                <div id="imagePreview" style="background-image: url('{{ image_path('') }}');">
                </div>
            </div>
        </div>
    </div>
    <div class="col-xs-6 col-sm-6 col-md-6">
        <div class="form-group ">
            <label>Name:</label>
            <input type="text" name="name" placeholder="Name" class="form-control" autocomplete="off" />
        </div>
    </div>
    <div class="col-xs-6 col-sm-6 col-md-6">
        <div class="form-group ">
            <label>Email: <small>(Optional)</small></label>
            <input type="email" name="email" placeholder="Email" class="form-control"  autocomplete="off"/>
        </div>
    </div>
    <div class="col-xs-6 col-sm-6 col-md-6">
        <div class="form-group ">
            <label>Phone: <small>(Optional)</small></label>
            <input type="text" name="phone" placeholder="Phone" class="form-control" autocomplete="off" />
        </div>
    </div>
    <div class="col-xs-6 col-sm-6 col-md-6">
        <div class="form-group ">
            <label>Registeration No: <small>(Optional)</small></label>
            <input type="text" name="registration_no" placeholder="Registeration No" class="form-control" autocomplete="off"/>
        </div>
    </div>
    <div class="col-xs-6 col-sm-6 col-md-6">
        <div class="form-group ">
            <label>NTN#: <small>(Optional)</small></label>
            <input type="text" name="ntn" placeholder="NTN No" class="form-control" autocomplete="off"/>
        </div>
    </div>
    <div class="col-xs-6 col-sm-6 col-md-6">
        <div class="form-group ">
            <label>STN# <small>(Optional)</small></label>
            <input type="text" name="stn" placeholder="ST No" class="form-control" />
        </div>
    </div>
    <div class="col-xs-12 col-sm-12 col-md-12">
        <div class="form-group ">
            <label>Address:</label>
            <textarea name="address" row="2" class="form-control" placeholder="Address"></textarea>
        </div>
    </div>
        <div class="col-xs-12 col-sm-12 col-md-12">
        <div class="form-group ">
            <label>Connection Name: <small>(Optional)</small></label>
            <input type="text" name="connection_database" placeholder="Registeration No" class="form-control" />
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
    $(document).ready(function() {
        $('.parent').change(function() {
            $(this).parents('.permission-container').find('.sub-permissions').find('.child').prop(
                'checked', $(this).prop('checked'));
        });
        $('.child').change(function() {
            var parentCheckbox = $(this).closest('.permission-container').find('.parent');
            parentCheckbox.prop('checked', $(this).closest('.sub-permissions').find('.child:checked')
                .length > 0);
        });
    });
    $('.custom-accordion > span').on('click', function() {
        $(this).parents('.custom-accordion').siblings('.sub-permissions').toggle('slow');
    })
</script>
