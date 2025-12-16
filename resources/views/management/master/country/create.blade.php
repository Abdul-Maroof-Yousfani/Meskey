<form action="{{ route('country.store') }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    <input type="hidden" id="listRefresh" value="{{ route('get.country') }}" />

    <div class="row">

        <div class="col-md-12">
            <label>Country Name:</label>
            <input type="text" name="name" class="form-control" maxlength="255" placeholder="Country Name"
                required />
        </div>

        <div class="col-md-12 mt-3">
            <label>Alpha-2 Code:</label>
            <input type="text" name="alpha_2_code" class="form-control" maxlength="2" placeholder="e.g. US"
                required />
        </div>

        <div class="col-md-12 mt-3">
            <label>Alpha-3 Code:</label>
            <input type="text" name="alpha_3_code" class="form-control" maxlength="3" placeholder="e.g. USA"
                required />
        </div>

        <div class="col-md-12 mt-3">
            <label>Phone Code:</label>
            <input type="text" name="phone_code" class="form-control" maxlength="20" placeholder="e.g. +1" />
        </div>

        <div class="bottom-button-bar mt-3">
            <div class="col-12">
                <a type="button" class="btn btn-danger modal-sidebar-close closebutton">Close</a>
                <button type="submit" class="btn btn-primary submitbutton">Save</button>
            </div>
        </div>

    </div>
</form>
