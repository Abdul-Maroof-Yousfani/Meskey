<div class="row">

    <div class="col-md-12">
        <label>Country Name:</label>
        <input type="text" class="form-control" value="{{ $country->name }}" disabled />
    </div>

    <div class="col-md-12 mt-3">
        <label>Alpha-2 Code:</label>
        <input type="text" class="form-control" value="{{ $country->alpha_2_code }}" disabled />
    </div>

    <div class="col-md-12 mt-3">
        <label>Alpha-3 Code:</label>
        <input type="text" class="form-control" value="{{ $country->alpha_3_code }}" disabled />
    </div>

    <div class="col-md-12 mt-3">
        <label>Phone Code:</label>
        <input type="text" class="form-control" value="{{ $country->phone_code }}" disabled />
    </div>


    <div class="text-center mt-3">
        <div class="col-12">
            <button type="button" class="btn btn-danger modal-sidebar-close">Close</button>
        </div>
    </div>

</div>
