<form action="{{ route('ports.update', $port->id) }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    @method('PUT')

    <input type="hidden" id="listRefresh" value="{{ route('get.port') }}" />

    <div class="row">

        <div class="col-md-12">
            <label>Company:</label>
            <select name="company_id" class="form-control select2" required>
                @foreach ($companies as $company)
                    <option value="{{ $company->id }}" {{ $company->id == $port->company_id ? 'selected' : '' }}>
                        {{ $company->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-md-12 mt-3">
            <label>Port Name:</label>
            <input type="text" name="name" value="{{ $port->name }}" class="form-control" maxlength="255"
                required />
        </div>

        <div class="col-md-12 mt-3">
            <label>Description:</label>
            <textarea name="description" class="form-control" rows="3">{{ $port->description }}</textarea>
        </div>

        <div class="col-md-12 mt-3">
            <label>Country:</label>
            <select name="country_id" id="countrySelect" class="form-control select2" required>
                <option value="">-- Select Country --</option>
                @foreach ($countries as $country)
                    <option value="{{ $country->id }}" {{ $country->id == $port->country_id ? 'selected' : '' }}>
                        {{ $country->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-md-12 mt-3">
            <label>City:</label>
            <select name="city_id" id="citySelect" class="form-control select2" required>
                @foreach ($cities as $city)
                    <option value="{{ $city->id }}" {{ $city->id == $port->city_id ? 'selected' : '' }}>
                        {{ $city->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-md-12 mt-3">
            <label>Type:</label>
            <select name="type" class="form-control select2" required>
                <option value="">-- Select Type --</option>
                <option value="Sea" {{ $port->type == 'Sea' ? 'selected' : '' }}>Sea</option>
                <option value="Air" {{ $port->type == 'Air' ? 'selected' : '' }}>Air</option>
                <option value="Land" {{ $port->type == 'Land' ? 'selected' : '' }}>Land</option>
            </select>
        </div>


        <div class="col-md-12 mt-3">
            <label>Status:</label>
            <select name="status" class="form-control" required>
                <option value="active" {{ $port->status == 'active' ? 'selected' : '' }}>Active</option>
                <option value="inactive" {{ $port->status == 'inactive' ? 'selected' : '' }}>Inactive</option>
            </select>
        </div>

        <div class="bottom-button-bar mt-3">
            <div class="col-12">
                <a type="button" class="btn btn-danger modal-sidebar-close">Close</a>
                <button type="submit" class="btn btn-primary">Update</button>
            </div>
        </div>

    </div>
</form>

<script>
    $(document).ready(function() {
        $('.select2').select2();

        // Load cities when country changes
        $('#countrySelect').on('change', function() {
            let countryId = $(this).val();
            $('#citySelect').html('<option value="">Loading...</option>');

            $.get('/master/get-cities/' + countryId, function(data) {
                $('#citySelect').empty().append('<option value="">-- Select City --</option>');
                $.each(data, function(index, city) {
                    $('#citySelect').append('<option value="' + city.id + '">' + city
                        .name + '</option>');
                });
            });
        });
    });
</script>
