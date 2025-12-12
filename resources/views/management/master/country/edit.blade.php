<form action="{{ route('country.update', $country->id) }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    @method('PUT')

    <input type="hidden" id="listRefresh" value="{{ route('get.country') }}" />

    <div class="row">

        <div class="col-md-12">
            <label>Country Name:</label>
            <input type="text" name="name" class="form-control" maxlength="255" placeholder="Country Name"
                value="{{ $country->name }}" required />
        </div>

        <div class="col-md-12 mt-3">
            <label>Alpha-2 Code:</label>
            <input type="text" name="alpha_2_code" class="form-control" maxlength="2" placeholder="e.g. US"
                value="{{ $country->alpha_2_code }}" required />
        </div>

        <div class="col-md-12 mt-3">
            <label>Alpha-3 Code:</label>
            <input type="text" name="alpha_3_code" class="form-control" maxlength="3" placeholder="e.g. USA"
                value="{{ $country->alpha_3_code }}" required />
        </div>

        <div class="col-md-12 mt-3">
            <label>Phone Code:</label>
            <input type="text" name="phone_code" class="form-control" maxlength="20" placeholder="e.g. +1"
                value="{{ $country->phone_code }}" />
        </div>


        <div class="bottom-button-bar mt-3">
            <div class="col-12">
                <a type="button" class="btn btn-danger modal-sidebar-close closebutton">Close</a>
                <button type="submit" class="btn btn-primary submitbutton">Update</button>
            </div>
        </div>

    </div>
</form>
