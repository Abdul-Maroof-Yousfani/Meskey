<form action="{{ route('cities.update', $city->id) }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    @method('PUT')

    <input type="hidden" id="listRefresh" value="{{ route('get.city') }}" />

    <div class="row">

        <div class="col-md-12">
            <label>City Name:</label>
            <input type="text" name="name" class="form-control" maxlength="255" placeholder="City Name"
                value="{{ $city->name }}" required />
        </div>

        <div class="col-md-12 mt-3">
            <label>Country:</label>
            <select name="country_id" id="countrySelect" class="form-control select2" required>
                <option value="">-- Select Country --</option>
                @foreach ($countries as $country)
                    <option value="{{ $cportountry->id }}" data-code="{{ $country->alpha_2_code }}"
                        {{ $city->country_id == $country->id ? 'selected' : '' }}>
                        {{ $country->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-md-12 mt-3">
            <label>Country Code:</label>
            <input type="text" name="country_code" id="countryCode" class="form-control" maxlength="2"
                value="{{ $city->country_code }}" readonly required />
        </div>

        <div class="bottom-button-bar mt-3">
            <div class="col-12">
                <a type="button" class="btn btn-danger modal-sidebar-close closebutton">Close</a>
                <button type="submit" class="btn btn-primary submitbutton">Update</button>
            </div>
        </div>

    </div>
</form>

<script>
    $(document).ready(function() {
        $('.select2').select2();

        // Auto fill country code when country changes
        $('#countrySelect').on('change', function() {
            var code = $(this).find(':selected').data('code');
            $('#countryCode').val(code || '');
        });
    });
</script>
