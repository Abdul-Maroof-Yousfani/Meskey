<form action="{{ route('ports.store') }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    <input type="hidden" id="listRefresh" value="{{ route('get.port') }}" />

    <div class="row">

        <div class="col-md-12">
            <label>Company:</label>
            <select name="company_id" class="form-control select2" required>
                <option value="">-- Select Company --</option>
                @foreach ($companies as $company)
                    <option value="{{ $company->id }}">{{ $company->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-12 mt-3">
            <label>Port Name:</label>
            <input type="text" name="name" class="form-control" maxlength="255" placeholder="Port Name"
                required />
        </div>

        <div class="col-md-12 mt-3">
            <label>Description:</label>
            <textarea name="description" class="form-control" rows="3"></textarea>
        </div>

        <div class="col-md-12 mt-3">
            <label>Country:</label>
            <select name="country_id" id="countrySelect" class="form-control select2" required>
                <option value="">-- Select Country --</option>
                @foreach ($countries as $country)
                    <option value="{{ $country->id }}">{{ $country->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-12 mt-3">
            <label>City:</label>
            <select name="city_id" id="citySelect" class="form-control select2" required>
                <option value="">-- Select City --</option>
            </select>
        </div>

        <div class="col-md-12 mt-3">
            <label>Type:</label>
            <select name="type" class="form-control select2" required>
                <option value="">-- Select Type --</option>
                <option value="Sea">Sea</option>
                <option value="Air">Air</option>
                <option value="Land">Land</option>
            </select>
        </div>


        <div class="col-md-12 mt-3">
            <label>Status:</label>
            <select name="status" class="form-control" required>
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

        // Load cities only when country is selected
        $('#countrySelect').on('change', function() {
            var country_id = $(this).val();

            $('#citySelect').html('<option value="">Loading...</option>');

            $.get('/master/get-cities/' + country_id, function(data) {
                $('#citySelect').empty().append('<option value="">-- Select City --</option>');
                $.each(data, function(index, city) {
                    $('#citySelect').append('<option value="' + city.id + '">' + city
                        .name + '</option>');
                });
            });
        });
    });
</script>
